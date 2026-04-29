<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property DB_query_builder $db
 * @property Hrm_nghi_phep_model $Hrm_nghi_phep_model
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Fileupload $fileupload
 * @property Common $common
 */



class Nghiphep extends REST_INSTANCE_Controller
{
    private $linkDuyet = '';
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');

        $this->load->model(['Hrm_nghi_phep_model', 'Hrm_nhan_vien_model', 'Hrm_danh_muc_loai_nghi_phep_model']);
        $this->load->library(['Validator', 'Fileupload', 'Pxl', 'upload']);
        $this->linkDuyet = $this->config->item('frontend_v2') . 'hrm/nghi-phep/duyet/';

        // Phân quyền: Bỏ qua bắt quyền đối với các chức năng công khai
        $publicSegments = [
            'nghiphep.index',
            'nghiphep.create',
            'nghiphep.update',
            'nghiphep.deletes',
            'nghiphep.approve_on_behalf',
            'nghiphep.get_employee_by_unit',
            'nghiphep.get_approvers',
            'nghiphep.statistics',
            'nghiphep.send_approval_email',
            'nghiphep.send_email_after_approved',
            'nghiphep.export_by_employee',
            'nghiphep.export_by_employee_ids',
            'nghiphep.minh_chung',
            'nghiphep.upload_minh_chung_duyet_ho',
            'nghiphep.download_template',
            'nghiphep.import',
            'nghiphep.detail',
            'nghiphep.sync',
            'nghiphep.submit_phuc_khao'
        ];

        if (!$this->inSegment($publicSegments)) {
            $this->permissionMiddleware();
        }
    }

    public function index_get()
    {
        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue');
        if (!$searchValue) {
            $search = commonRequest('search');
            if (is_array($search) && isset($search['value'])) {
                $searchValue = $search['value'];
            }
        }

        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];
        $orderBy = commonRequest('order') ? [
            'order' => commonRequest('order'),
            'columns' => commonRequest('columns') ?? []
        ] : [];

        // Lấy thông tin user hiện tại
        $auth = $this->getUserLogin();
        $qlNguoiDungId = $auth['ql_nguoi_dung_id'];

        // Lấy thông tin nhân viên của user hiện tại
        $nhanVien = $this->Hrm_nhan_vien_model
            ->select('hrm_nhan_vien.id_nhan_vien, hrm_nhan_vien.id_don_vi_cong_tac, e_don_vi.ma_don_vi')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_nhan_vien.id_don_vi_cong_tac', 'left')
            ->where('hrm_nhan_vien.ql_nguoi_dung_id', $qlNguoiDungId)
            ->first();

        $idNhanVien = $nhanVien ? $nhanVien['id_nhan_vien'] : null;
        $idDonViCongTac = $nhanVien ? $nhanVien['id_don_vi_cong_tac'] : $auth['id_don_vi'];
        $maDonVi = $nhanVien ? $nhanVien['ma_don_vi'] : null;

        $data = $this->Hrm_nghi_phep_model->getAll(
            $start,
            $length,
            $searchValue,
            $orderBy,
            $searchKey,
            $idNhanVien,
            $qlNguoiDungId,
            $idDonViCongTac,
            $maDonVi
        );
        resSuccess($data, 'Lấy danh sách nghỉ phép thành công', REST_Controller::HTTP_OK);
    }

    /**
     * Lấy chi tiết đơn nghỉ phép theo UUID
     * GET /api/v2/admin/hrm/nghiphep/detail/:uuid
     */
    public function detail_get($uuid = null)
    {
        if (!$uuid) {
            resError([], 'Thiếu mã định danh đơn', REST_Controller::HTTP_BAD_REQUEST);
        }

        $auth = $this->getUserLogin();
        $qlNguoiDungId = $auth['ql_nguoi_dung_id'];

        // Lấy thông tin nhân viên của user hiện tại
        $nhanVien = $this->Hrm_nhan_vien_model
            ->select('hrm_nhan_vien.id_nhan_vien, hrm_nhan_vien.id_don_vi_cong_tac, e_don_vi.ma_don_vi')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_nhan_vien.id_don_vi_cong_tac', 'left')
            ->where('hrm_nhan_vien.ql_nguoi_dung_id', $qlNguoiDungId)
            ->first();

        $idNhanVien = $nhanVien ? $nhanVien['id_nhan_vien'] : null;
        $idDonViCongTac = $nhanVien ? $nhanVien['id_don_vi_cong_tac'] : null;
        $maDonVi = $nhanVien ? $nhanVien['ma_don_vi'] : null;

        // Lấy dữ liệu chi tiết (tái sử dụng model getAll với filters)
        $result = $this->Hrm_nghi_phep_model->getAll(
            0,
            1,
            null,
            [],
            ['uuid_nghi_phep' => $uuid],
            $idNhanVien,
            $qlNguoiDungId,
            $idDonViCongTac,
            $maDonVi
        );

        if (empty($result['data'])) {
            resError([], 'Không tìm thấy đơn nghỉ phép hoặc bạn không có quyền xem', REST_Controller::HTTP_NOT_FOUND);
        }

        $detail = $result['data'][0];

        // Lấy lịch sử log phê duyệt
        $logs = $this->db
            ->select('lg.*, nd.ql_nguoi_dung_ho_ten as nguoi_thuc_hien_ho_ten, nd.ql_nguoi_dung_avatar as nguoi_thuc_hien_avatar')
            ->from('hrm_nghi_phep_log_duyet lg')
            ->join('ql_nguoi_dung nd', 'nd.ql_nguoi_dung_id = lg.id_nguoi_duyet', 'left')
            ->where('lg.id_nghi_phep', $detail['id_nghi_phep'])
            ->order_by('lg.thoi_gian_thay_doi', 'DESC')
            ->get()
            ->result_array();

        // Format avatar log
        foreach ($logs as &$log) {
            if (!empty($log['nguoi_thuc_hien_avatar'])) {
                $log['nguoi_thuc_hien_avatar'] = encryptString($log['nguoi_thuc_hien_avatar']);
            }
        }

        $detail['logs'] = $logs;

        resSuccess($detail, 'Lấy chi tiết nghỉ phép thành công', REST_Controller::HTTP_OK);
    }

    public function create_post()
    {
        $auth = $this->getUserLogin();
        // Nhận dữ liệu từ request
        $idNhanVien = commonRequest('id_nhan_vien');
        $idLoaiPhep = commonRequest('id_loai_phep');
        $loaiNghi = commonRequest('loai_nghi') ? commonRequest('loai_nghi') : 'Binh_thuong';
        $lyDoNghi = commonRequest('ly_do_nghi');
        $danhSachNgayNghi = commonRequest('danh_sach_ngay_nghi'); // Array of {ngay_nghi, sang, chieu}
        $minhChung = commonRequest('minh_chung'); // File upload

        if (is_string($danhSachNgayNghi)) {
            $danhSachNgayNghi = json_decode($danhSachNgayNghi, true);
        }

        // Gộp các ngày trùng nhau (merge buổi sáng/chiều)
        $ngayNghiMerged = [];
        if (is_array($danhSachNgayNghi)) {
            foreach ($danhSachNgayNghi as $item) {
                // Convert object to array nếu cần
                if (is_object($item)) {
                    $item = (array) $item;
                }

                $ngay = isset($item['ngay_nghi']) ? $item['ngay_nghi'] : null;

                if ($ngay) {
                    if (!isset($ngayNghiMerged[$ngay])) {
                        $ngayNghiMerged[$ngay] = [
                            'ngay_nghi' => $ngay,
                            'sang' => false,
                            'chieu' => false
                        ];
                    }
                    // Gộp buổi sáng/chiều
                    if (isset($item['sang']) && $item['sang']) {
                        $ngayNghiMerged[$ngay]['sang'] = true;
                    }
                    if (isset($item['chieu']) && $item['chieu']) {
                        $ngayNghiMerged[$ngay]['chieu'] = true;
                    }
                }
            }
            $danhSachNgayNghi = array_values($ngayNghiMerged);
        }

        // Validate dữ liệu
        $errors = [];

        if (empty($idNhanVien)) {
            $errors['id_nhan_vien'] = 'Vui lòng chọn nhân viên';
        }

        if (empty($idLoaiPhep)) {
            $errors['id_loai_phep'] = 'Vui lòng chọn loại nghỉ phép';
        }

        if (empty($danhSachNgayNghi) || !is_array($danhSachNgayNghi)) {
            $errors['danh_sach_ngay_nghi'] = 'Vui lòng chọn ít nhất một ngày nghỉ';
        }

        if (!empty($errors)) {
            resError($errors, 'Dữ liệu không hợp lệ', REST_Controller::HTTP_BAD_REQUEST);
        }

        // Xử lý upload file minh chứng
        $minhChungPath = null;
        if (!empty($minhChung)) {
            $folderName = 'hrm/minh-chung-nghi-phep';
            $uploadedFile = $this->fileupload->upload($minhChung, $folderName, 'jpg|jpeg|png');

            if ($uploadedFile['success']) {
                $minhChungPath = $uploadedFile['file_path'];
            } else {
                resError(['minh_chung' => 'Không thể tải lên file minh chứng'], 'Upload thất bại', REST_Controller::HTTP_BAD_REQUEST);
            }
        }

        // Bắt đầu transaction
        $this->db->trans_start();

        try {
            // Sinh UUID
            $uuid = sprintf(
                '%04x%04x',
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff)
            );

            // Insert vào bảng hrm_nghi_phep
            $dataNghiPhep = [
                'uuid_nghi_phep' => $uuid,
                'id_nhan_vien' => $idNhanVien,
                'id_don_vi' => $this->Hrm_nhan_vien_model->where('id_nhan_vien', $idNhanVien)->first()['id_don_vi_cong_tac'],
                'id_loai_phep' => $idLoaiPhep,
                'loai_nghi' => $loaiNghi,
                'ly_do_nghi' => $lyDoNghi,
                'minh_chung' => $minhChungPath,
                'created_user_id' => $auth['ql_nguoi_dung_id']
            ];

            $this->db->insert('hrm_nghi_phep', $dataNghiPhep);
            $idNghiPhep = $this->db->insert_id();

            // Insert chi tiết các ngày nghỉ
            foreach ($danhSachNgayNghi as $ngayNghi) {
                $ngay = $ngayNghi['ngay_nghi'];
                $buoiNghiList = [];

                // Kiểm tra buổi sáng
                if (isset($ngayNghi['sang']) && $ngayNghi['sang']) {
                    $buoiNghiList[] = [
                        'id_nghi_phep' => $idNghiPhep,
                        'ngay_nghi' => $ngay,
                        'buoi_nghi' => 'Sang',
                        'so_ngay_nghi' => 0.5
                    ];
                }

                // Kiểm tra buổi chiều
                if (isset($ngayNghi['chieu']) && $ngayNghi['chieu']) {
                    $buoiNghiList[] = [
                        'id_nghi_phep' => $idNghiPhep,
                        'ngay_nghi' => $ngay,
                        'buoi_nghi' => 'Chieu',
                        'so_ngay_nghi' => 0.5
                    ];
                }

                // Insert các buổi nghỉ
                if (!empty($buoiNghiList)) {
                    $this->db->insert_batch('hrm_nghi_phep_chi_tiet', $buoiNghiList);
                }
            }

            $ql_nguoi_dung_id = $auth['ql_nguoi_dung_id'];

            $nhanVien = $this->Hrm_nhan_vien_model
                ->select('hrm_nhan_vien.id_don_vi_cong_tac')
                ->where('hrm_nhan_vien.ql_nguoi_dung_id', $ql_nguoi_dung_id)
                ->first();

            $id_don_vi = $nhanVien ? $nhanVien['id_don_vi_cong_tac'] : $auth['id_don_vi'];

            $dsLanhDao = $this->db->select('ldv.ql_nguoi_dung_id AS id_nguoi_duyet')
                ->from('e_lanh_dao_don_vi ldv')
                ->where('ldv.id_don_vi', $id_don_vi)
                ->where('ldv.deleted_at IS NULL')
                ->get()
                ->result_array();

            foreach ($dsLanhDao as $lanhDao) { {
                    $dataNghiPhepNguoiDuyet = [
                        'id_nghi_phep' => $idNghiPhep,
                        'id_nguoi_duyet' => $lanhDao['id_nguoi_duyet'],
                        'cap_duyet' => 1,
                        'da_duyet' => 0,
                        'thoi_gian_duyet' => null,
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                    $this->db->insert('hrm_nghi_phep_nguoi_duyet', $dataNghiPhepNguoiDuyet);
                }
            }

            // Commit transaction
            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                resError([], 'Tạo đơn nghỉ phép thất bại', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Lấy thông tin đơn nghỉ phép vừa tạo
            $nghiPhepMoi = $this->db
                ->select('hrm_nghi_phep.*, hrm_nhan_vien.ho_va_ten, hrm_nhan_vien.ma_nhan_vien')
                ->join('hrm_nhan_vien', 'hrm_nhan_vien.id_nhan_vien = hrm_nghi_phep.id_nhan_vien', 'left')
                ->where('hrm_nghi_phep.id_nghi_phep', $idNghiPhep)
                ->get('hrm_nghi_phep')
                ->row_array();

            $nghiPhepMoi = $this->Hrm_nghi_phep_model->formatNghiPhep($nghiPhepMoi);
            
            // Log tạo đơn nghỉ phép
            $this->createLog(
                'create',
                'Tạo đơn nghỉ phép cho nhân viên ID: ' . $idNhanVien,
                null,
                ['id_nghi_phep' => $idNghiPhep, 'uuid' => $uuid],
                'hrm_nghi_phep'
            );
            
            resSuccess($nghiPhepMoi, 'Tạo đơn nghỉ phép thành công', REST_Controller::HTTP_CREATED);
        } catch (Exception $e) {
            $this->db->trans_rollback();
            resError(['message' => $e->getMessage()], 'Có lỗi xảy ra khi tạo đơn nghỉ phép', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approve_post()
    {
        $auth = $this->getUserLogin();
        $idNguoiDuyet = $auth['ql_nguoi_dung_id'];

        // Nhận dữ liệu từ request
        $uuidsNghiPhep = commonRequest('uuids_nghi_phep'); // String hoặc Array
        $hanhDong = commonRequest('hanh_dong'); // 'duyet' hoặc 'tu_choi'
        $lyDo = commonRequest('ly_do'); // Lý do duyệt/từ chối (optional)

        // Convert string JSON thành array
        if (is_string($uuidsNghiPhep)) {
            $decoded = json_decode($uuidsNghiPhep, true);
            $uuidsNghiPhep = $decoded ? $decoded : [$uuidsNghiPhep];
        }

        // Convert single UUID thành array
        if (!is_array($uuidsNghiPhep)) {
            $uuidsNghiPhep = [$uuidsNghiPhep];
        }

        // Validate
        if (empty($uuidsNghiPhep)) {
            resError(['uuids_nghi_phep' => 'Vui lòng chọn ít nhất một đơn nghỉ phép'], 'Dữ liệu không hợp lệ', REST_Controller::HTTP_BAD_REQUEST);
        }

        if (!in_array($hanhDong, ['duyet', 'tu_choi'])) {
            resError(['hanh_dong' => 'Hành động không hợp lệ'], 'Dữ liệu không hợp lệ', REST_Controller::HTTP_BAD_REQUEST);
        }

        // Bắt đầu transaction
        $this->db->trans_start();

        try {
            $ketQuaDuyet = [];

            foreach ($uuidsNghiPhep as $uuid) {
                // Lấy thông tin đơn nghỉ phép
                $nghiPhep = $this->Hrm_nghi_phep_model
                    ->where('uuid_nghi_phep', $uuid)
                    ->first();

                if (!$nghiPhep) {
                    $ketQuaDuyet[] = [
                        'uuid_nghi_phep' => $uuid,
                        'success' => false,
                        'message' => 'Đơn nghỉ phép không tồn tại'
                    ];
                    continue;
                }

                $idNghiPhep = $nghiPhep['id_nghi_phep'];

                // Lấy thông tin nhân viên và đơn vị
                $nhanVien = $this->db
                    ->select('nv.id_don_vi_cong_tac, dv.ma_don_vi, dv.ten_don_vi')
                    ->from('hrm_nhan_vien nv')
                    ->join('e_don_vi dv', 'dv.id_don_vi = nv.id_don_vi_cong_tac', 'left')
                    ->where('nv.id_nhan_vien', $nghiPhep['id_nhan_vien'])
                    ->get()
                    ->row_array();

                if (!$nhanVien) {
                    $ketQuaDuyet[] = [
                        'uuid_nghi_phep' => $uuid,
                        'success' => false,
                        'message' => 'Không tìm thấy thông tin nhân viên'
                    ];
                    continue;
                }

                $idDonViCongTac = $nhanVien['id_don_vi_cong_tac'];
                $maDonVi = $nhanVien['ma_don_vi'];

                // Tự động thêm người duyệt nếu là lãnh đạo nhưng chưa có trong bảng nguoi_duyet
                // Trường hợp: Đơn được tạo trước, lãnh đạo được thêm sau

                // Kiểm tra người duyệt có là lãnh đạo cấp 1 (lãnh đạo đơn vị n hân viên) không
                // $laLanhDaoCap1 = $this->db
                //     ->where('ql_nguoi_dung_id', $idNguoiDuyet)
                //     ->where('id_don_vi', $idDonViCongTac)
                //     ->where('deleted_at IS NULL')
                //     ->count_all_results('e_lanh_dao_don_vi');

                // if ($laLanhDaoCap1 > 0) {
                //     // Là lãnh đạo cấp 1, kiểm tra đã có trong hrm_nghi_phep_nguoi_duyet chưa
                //     $daCoCap1 = $this->db
                //         ->where('id_nghi_phep', $idNghiPhep)
                //         ->where('id_nguoi_duyet', $idNguoiDuyet)
                //         ->where('cap_duyet', 1)
                //         ->count_all_results('hrm_nghi_phep_nguoi_duyet');

                //     if ($daCoCap1 == 0) {
                //         // Chưa có -> Tự động thêm vào
                //         $this->db->insert('hrm_nghi_phep_nguoi_duyet', [
                //             'id_nghi_phep' => $idNghiPhep,
                //             'id_nguoi_duyet' => $idNguoiDuyet,
                //             'cap_duyet' => 1,
                //             'da_duyet' => 0,
                //             'created_at' => date('Y-m-d H:i:s')
                //         ]);

                //         // Cập nhật nguoi_duyet_cap_mot_id trong hrm_nghi_phep nếu chưa có
                //         if (empty($nghiPhep['nguoi_duyet_cap_mot_id'])) {
                //             $this->db->where('id_nghi_phep', $idNghiPhep)
                //                 ->update('hrm_nghi_phep', [
                //                     'nguoi_duyet_cap_mot_id' => $idNguoiDuyet
                //                 ]);

                //             // Cập nhật lại biến $nghiPhep để dùng cho logic tiếp theo
                //             $nghiPhep['nguoi_duyet_cap_mot_id'] = $idNguoiDuyet;
                //         }
                //     }
                // }

                // Kiểm tra người duyệt có quyền duyệt đơn này không (cả cấp 1 và cấp 2)
                // Lấy tất cả các bản ghi người duyệt (có thể có cả cấp 1 và cấp 2)
                $dsNguoiDuyet = $this->db
                    ->where('id_nghi_phep', $idNghiPhep)
                    ->where('id_nguoi_duyet', $idNguoiDuyet)
                    ->where_in('cap_duyet', [1, 2])
                    ->get('hrm_nghi_phep_nguoi_duyet')
                    ->result_array();

                if (empty($dsNguoiDuyet)) {
                    $ketQuaDuyet[] = [
                        'uuid_nghi_phep' => $uuid,
                        'success' => false,
                        'message' => 'Bạn không có quyền duyệt đơn này'
                    ];
                    continue;
                }

                // Xác định cấp duyệt phù hợp dựa vào trạng thái đơn
                // Nếu đơn chưa duyệt cấp 1 → ưu tiên cấp 1
                // Nếu đơn đã duyệt cấp 1 → ưu tiên cấp 2
                $nguoiDuyet = null;
                if ($nghiPhep['trang_thai_cap_mot'] != 'Da_duyet') {
                    // Ưu tiên lấy cấp 1
                    foreach ($dsNguoiDuyet as $nd) {
                        if ($nd['cap_duyet'] == 1) {
                            $nguoiDuyet = $nd;
                            break;
                        }
                    }
                    // Nếu không có cấp 1 thì lấy cấp 2 (trường hợp đặc biệt)
                    if (!$nguoiDuyet) {
                        $nguoiDuyet = $dsNguoiDuyet[0];
                    }
                } else {
                    // Ưu tiên lấy cấp 2
                    foreach ($dsNguoiDuyet as $nd) {
                        if ($nd['cap_duyet'] == 2) {
                            $nguoiDuyet = $nd;
                            break;
                        }
                    }
                    // Nếu không có cấp 2 thì lấy cấp 1 (trường hợp đặc biệt)
                    if (!$nguoiDuyet) {
                        $nguoiDuyet = $dsNguoiDuyet[0];
                    }
                }

                $capDuyet = $nguoiDuyet['cap_duyet'];

                // Kiểm tra quyền lãnh đạo trong bảng e_lanh_dao_don_vi
                if ($capDuyet == 1) {
                    // Cấp 1: Phải là lãnh đạo của đơn vị nhân viên đó
                    $laLanhDaoDonVi = $this->db
                        ->where('ql_nguoi_dung_id', $idNguoiDuyet)
                        ->where('id_don_vi', $idDonViCongTac)
                        ->where('deleted_at IS NULL')
                        ->count_all_results('e_lanh_dao_don_vi');

                    if ($laLanhDaoDonVi == 0) {
                        $ketQuaDuyet[] = [
                            'uuid_nghi_phep' => $uuid,
                            'success' => false,
                            'message' => 'Bạn không phải là lãnh đạo của đơn vị ' . $nhanVien['ten_don_vi']
                        ];
                        continue;
                    }
                } else if ($capDuyet == 2) {
                    // Cấp 2: Phải là lãnh đạo TCHC (id_don_vi = 15)
                    $laLanhDaoTCHC = $this->db
                        ->where('ql_nguoi_dung_id', $idNguoiDuyet)
                        ->where('id_don_vi', 15)
                        ->where('deleted_at IS NULL')
                        ->count_all_results('e_lanh_dao_don_vi');

                    if ($laLanhDaoTCHC == 0) {
                        $ketQuaDuyet[] = [
                            'uuid_nghi_phep' => $uuid,
                            'success' => false,
                            'message' => 'Bạn không phải là lãnh đạo phòng Tổ chức - Hành chính'
                        ];
                        continue;
                    }
                }

                // Xử lý theo cấp duyệt
                if ($capDuyet == 1) {
                    // CẤP 1: Lãnh đạo đơn vị

                    // Kiểm tra trạng thái cấp 1 - Không cho phép thay đổi nếu đã xử lý (đã duyệt hoặc đã từ chối)
                    if ($nghiPhep['trang_thai_cap_mot'] != 'Cho_duyet') {
                        $ketQuaDuyet[] = [
                            'uuid_nghi_phep' => $uuid,
                            'success' => false,
                            'message' => 'Đơn này đã được xử lý cấp 1 rồi, không thể thay đổi'
                        ];
                        continue;
                    }

                    // Kiểm tra trạng thái
                    if ($nghiPhep['trang_thai_cap_mot'] == 'Tu_choi') {
                        $ketQuaDuyet[] = [
                            'uuid_nghi_phep' => $uuid,
                            'success' => false,
                            'message' => 'Đơn này đã bị từ chối'
                        ];
                        continue;
                    }

                    if ($nghiPhep['trang_thai_cap_mot'] == 'Da_duyet' && $hanhDong == 'duyet') {
                        $ketQuaDuyet[] = [
                            'uuid_nghi_phep' => $uuid,
                            'success' => false,
                            'message' => 'Đơn này đã được duyệt cấp 1'
                        ];
                        continue;
                    }

                    // Xử lý duyệt/từ chối cấp 1
                    if ($hanhDong == 'duyet') {
                        // Update bảng hrm_nghi_phep_nguoi_duyet
                        $this->db->where('id_nghi_phep_nguoi_duyet', $nguoiDuyet['id_nghi_phep_nguoi_duyet'])
                            ->update('hrm_nghi_phep_nguoi_duyet', [
                                'da_duyet' => 1,
                                'thoi_gian_duyet' => date('Y-m-d H:i:s'),
                                'ly_do' => $lyDo
                            ]);

                        // Update bảng hrm_nghi_phep
                        $this->db->where('id_nghi_phep', $idNghiPhep)
                            ->update('hrm_nghi_phep', [
                                'trang_thai_cap_mot' => 'Da_duyet',
                                'nguoi_duyet_cap_mot_id' => $idNguoiDuyet,
                                'updated_user_id' => $idNguoiDuyet
                            ]);

                        // Log thay đổi trạng thái cấp 1
                        $this->db->insert('hrm_nghi_phep_log_duyet', [
                            'id_nghi_phep' => $idNghiPhep,
                            'cap_duyet' => 1,
                            'trang_thai_cu' => 'Cho_duyet',
                            'trang_thai_moi' => 'Da_duyet',
                            'hanh_dong' => 'duyet',
                            'id_nguoi_duyet' => $idNguoiDuyet,
                            'ly_do' => $lyDo,
                            'thoi_gian_thay_doi' => date('Y-m-d H:i:s')
                        ]);

                        // Thêm người duyệt cấp 2 (TC-HC) - Lấy từ bảng e_lanh_dao_don_vi
                        $dsNguoiDuyetCapHai = $this->db->select('ldv.ql_nguoi_dung_id AS id_nguoi_duyet')
                            ->from('e_lanh_dao_don_vi ldv')
                            ->where('ldv.id_don_vi', 15) // Phòng TC-HC
                            ->where('ldv.deleted_at IS NULL')
                            ->get()
                            ->result_array();

                        foreach ($dsNguoiDuyetCapHai as $nguoiDuyetCap2) {
                            $idNguoiDuyetCap2 = $nguoiDuyetCap2['id_nguoi_duyet'];
                            // Kiểm tra xem đã tồn tại chưa
                            $daTonTai = $this->db
                                ->where('id_nghi_phep', $idNghiPhep)
                                ->where('id_nguoi_duyet', $idNguoiDuyetCap2)
                                ->where('cap_duyet', 2)
                                ->count_all_results('hrm_nghi_phep_nguoi_duyet');

                            if ($daTonTai == 0) {
                                $this->db->insert('hrm_nghi_phep_nguoi_duyet', [
                                    'id_nghi_phep' => $idNghiPhep,
                                    'id_nguoi_duyet' => $idNguoiDuyetCap2,
                                    'cap_duyet' => 2,
                                    'da_duyet' => 0,
                                    'thoi_gian_duyet' => null,
                                    'created_at' => date('Y-m-d H:i:s')
                                ]);
                            }
                        }

                        $ketQuaDuyet[] = [
                            'uuid_nghi_phep' => $uuid,
                            'success' => true,
                            'message' => 'Duyệt đơn nghỉ phép cấp 1 thành công',
                            'hanh_dong' => 'duyet',
                            'cap_duyet' => 1
                        ];
                    } else {
                        // Từ chối cấp 1
                        $this->db->where('id_nghi_phep_nguoi_duyet', $nguoiDuyet['id_nghi_phep_nguoi_duyet'])
                            ->update('hrm_nghi_phep_nguoi_duyet', [
                                'da_duyet' => 0,
                                'thoi_gian_duyet' => date('Y-m-d H:i:s'),
                                'ly_do' => $lyDo
                            ]);

                        // Update bảng hrm_nghi_phep
                        $this->db->where('id_nghi_phep', $idNghiPhep)
                            ->update('hrm_nghi_phep', [
                                'trang_thai_cap_mot' => 'Tu_choi',
                                'nguoi_duyet_cap_mot_id' => $idNguoiDuyet,
                                'updated_user_id' => $idNguoiDuyet
                            ]);

                        // Log thay đổi trạng thái cấp 1
                        $this->db->insert('hrm_nghi_phep_log_duyet', [
                            'id_nghi_phep' => $idNghiPhep,
                            'cap_duyet' => 1,
                            'trang_thai_cu' => 'Cho_duyet',
                            'trang_thai_moi' => 'Tu_choi',
                            'hanh_dong' => 'tu_choi',
                            'id_nguoi_duyet' => $idNguoiDuyet,
                            'ly_do' => $lyDo,
                            'thoi_gian_thay_doi' => date('Y-m-d H:i:s')
                        ]);

                        $ketQuaDuyet[] = [
                            'uuid_nghi_phep' => $uuid,
                            'success' => true,
                            'message' => 'Từ chối đơn nghỉ phép cấp 1 thành công',
                            'hanh_dong' => 'tu_choi',
                            'cap_duyet' => 1
                        ];
                    }
                } else if ($capDuyet == 2) {
                    // CẤP 2: TC-HC

                    // Kiểm tra đơn đã được duyệt cấp 1 chưa
                    if ($nghiPhep['trang_thai_cap_mot'] != 'Da_duyet') {
                        $ketQuaDuyet[] = [
                            'uuid_nghi_phep' => $uuid,
                            'success' => false,
                            'message' => 'Đơn này chưa được duyệt cấp 1'
                        ];
                        continue;
                    }

                    // Lưu trạng thái cũ để log
                    $trangThaiCu = $nghiPhep['trang_thai_cap_hai'];

                    // Cho phép sửa đổi trạng thái duyệt cấp 2 (không block nữa)

                    // Xử lý duyệt/từ chối cấp 2
                    if ($hanhDong == 'duyet') {
                        // Update bảng hrm_nghi_phep_nguoi_duyet
                        $this->db->where('id_nghi_phep_nguoi_duyet', $nguoiDuyet['id_nghi_phep_nguoi_duyet'])
                            ->update('hrm_nghi_phep_nguoi_duyet', [
                                'da_duyet' => 1,
                                'thoi_gian_duyet' => date('Y-m-d H:i:s'),
                                'ly_do' => $lyDo
                            ]);

                        // Update bảng hrm_nghi_phep
                        $this->db->where('id_nghi_phep', $idNghiPhep)
                            ->update('hrm_nghi_phep', [
                                'trang_thai_cap_hai' => 'Da_duyet',
                                'nguoi_duyet_cap_hai_id' => $idNguoiDuyet,
                                'updated_user_id' => $idNguoiDuyet
                            ]);

                        // Log thay đổi trạng thái
                        $hanhDongLog = ($trangThaiCu == 'Da_duyet' || $trangThaiCu == 'Tu_choi') ? 'sua_duyet' : 'duyet';
                        $this->db->insert('hrm_nghi_phep_log_duyet', [
                            'id_nghi_phep' => $idNghiPhep,
                            'cap_duyet' => 2,
                            'trang_thai_cu' => $trangThaiCu,
                            'trang_thai_moi' => 'Da_duyet',
                            'hanh_dong' => $hanhDongLog,
                            'id_nguoi_duyet' => $idNguoiDuyet,
                            'ly_do' => $lyDo,
                            'thoi_gian_thay_doi' => date('Y-m-d H:i:s')
                        ]);

                        $ketQuaDuyet[] = [
                            'uuid_nghi_phep' => $uuid,
                            'success' => true,
                            'message' => 'Duyệt đơn nghỉ phép cấp 2 thành công',
                            'hanh_dong' => 'duyet',
                            'cap_duyet' => 2
                        ];
                    } else {
                        // Từ chối cấp 2
                        $this->db->where('id_nghi_phep_nguoi_duyet', $nguoiDuyet['id_nghi_phep_nguoi_duyet'])
                            ->update('hrm_nghi_phep_nguoi_duyet', [
                                'da_duyet' => 0,
                                'thoi_gian_duyet' => date('Y-m-d H:i:s'),
                                'ly_do' => $lyDo
                            ]);

                        // Update bảng hrm_nghi_phep
                        $this->db->where('id_nghi_phep', $idNghiPhep)
                            ->update('hrm_nghi_phep', [
                                'trang_thai_cap_hai' => 'Tu_choi',
                                'nguoi_duyet_cap_hai_id' => $idNguoiDuyet,
                                'updated_user_id' => $idNguoiDuyet
                            ]);

                        // Log thay đổi trạng thái
                        $hanhDongLog = ($trangThaiCu == 'Da_duyet' || $trangThaiCu == 'Tu_choi') ? 'sua_duyet' : 'tu_choi';
                        $this->db->insert('hrm_nghi_phep_log_duyet', [
                            'id_nghi_phep' => $idNghiPhep,
                            'cap_duyet' => 2,
                            'trang_thai_cu' => $trangThaiCu,
                            'trang_thai_moi' => 'Tu_choi',
                            'hanh_dong' => $hanhDongLog,
                            'id_nguoi_duyet' => $idNguoiDuyet,
                            'ly_do' => $lyDo,
                            'thoi_gian_thay_doi' => date('Y-m-d H:i:s')
                        ]);

                        $ketQuaDuyet[] = [
                            'uuid_nghi_phep' => $uuid,
                            'success' => true,
                            'message' => 'Từ chối đơn nghỉ phép cấp 2 thành công',
                            'hanh_dong' => 'tu_choi',
                            'cap_duyet' => 2
                        ];
                    }
                }
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                resError([], 'Xử lý đơn nghỉ phép thất bại', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Đếm số lượng thành công/thất bại
            $soLuongThanhCong = count(array_filter($ketQuaDuyet, function ($item) {
                return $item['success'] === true;
            }));
            $tongSoLuong = count($ketQuaDuyet);

            $message = "Đã xử lý {$soLuongThanhCong}/{$tongSoLuong} đơn nghỉ phép";

            // Log duyệt đơn
            $this->createLog(
                'approve',
                ($hanhDong == 'duyet' ? 'Duyệt' : 'Từ chối') . ' đơn nghỉ phép',
                ['uuids' => $uuidsNghiPhep],
                $ketQuaDuyet,
                'hrm_nghi_phep'
            );

            resSuccess([
                'ket_qua' => $ketQuaDuyet,
                'tong_so_luong' => $tongSoLuong,
                'thanh_cong' => $soLuongThanhCong,
                'that_bai' => $tongSoLuong - $soLuongThanhCong
            ], $message, REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->db->trans_rollback();
            resError(['message' => $e->getMessage()], 'Có lỗi xảy ra', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Văn thư duyệt hộ lãnh đạo (cấp 1 và cấp 2)
     */
    public function approve_on_behalf_post()
    {
        $auth = $this->getUserLogin();
        $idVanThu = $auth['ql_nguoi_dung_id'];

        // Nhận dữ liệu từ request
        $uuidsNghiPhep = commonRequest('uuids_nghi_phep'); // String hoặc Array
        $hanhDong = commonRequest('hanh_dong'); // 'duyet' hoặc 'tu_choi'
        $lyDo = commonRequest('ly_do'); // Lý do duyệt/từ chối (optional)
        $idNguoiDuyetHo = commonRequest('id_nguoi_duyet_ho'); // ID người duyệt hộ (BẮT BUỘC)
        $minhChungDuyetHo = commonRequest('minh_chung_duyet_ho'); // Minh chứng duyệt hộ (optional)

        // Convert string JSON thành array
        if (is_string($uuidsNghiPhep)) {
            $decoded = json_decode($uuidsNghiPhep, true);
            $uuidsNghiPhep = $decoded ? $decoded : [$uuidsNghiPhep];
        }

        // Convert single UUID thành array
        if (!is_array($uuidsNghiPhep)) {
            $uuidsNghiPhep = [$uuidsNghiPhep];
        }

        // Validate
        if (empty($uuidsNghiPhep)) {
            resError(['uuids_nghi_phep' => 'Vui lòng chọn ít nhất một đơn nghỉ phép'], 'Dữ liệu không hợp lệ', REST_Controller::HTTP_BAD_REQUEST);
        }

        if (!in_array($hanhDong, ['duyet', 'tu_choi'])) {
            resError(['hanh_dong' => 'Hành động không hợp lệ'], 'Dữ liệu không hợp lệ', REST_Controller::HTTP_BAD_REQUEST);
        }

        if (empty($idNguoiDuyetHo)) {
            resError(['id_nguoi_duyet_ho' => 'Văn thư phải chọn người duyệt hộ'], 'Dữ liệu không hợp lệ', REST_Controller::HTTP_BAD_REQUEST);
        }

        // Kiểm tra user có phải văn thư đơn vị không
        // $nhanVienVanThu = $this->db
        //     ->select('nv.id_don_vi_cong_tac')
        //     ->from('hrm_nhan_vien nv')
        //     ->where('nv.ql_nguoi_dung_id', $idVanThu)
        //     ->get()
        //     ->row_array();

        $nhanVienVanThu = $this->db->select('qlnd.id_don_vi')->from('ql_nguoi_dung qlnd')->where('qlnd.ql_nguoi_dung_id', $idVanThu)->get()
            ->row_array();

        if (!$nhanVienVanThu) {
            resError([], 'Không tìm thấy thông tin nhân viên', REST_Controller::HTTP_NOT_FOUND);
        }

        $idDonViVanThu = $nhanVienVanThu['id_don_vi'];

        // Kiểm tra có vai trò văn thư không
        $isVanThu = $this->db
            ->select('1')
            ->from('ql_vai_tro_nguoi_dung vtnd')
            ->join('ql_vai_tro vt', 'vt.ql_vai_tro_id = vtnd.ql_vai_tro_id')
            ->where('vtnd.ql_nguoi_dung_id', $idVanThu)
            ->where('vt.ql_ma_vai_tro', 'VAN_THU_DON_VI')
            ->count_all_results() > 0;

        if (!$isVanThu) {
            resError([], 'Bạn không có quyền văn thư đơn vị', REST_Controller::HTTP_FORBIDDEN);
        }

        // Xử lý upload file minh chứng duyệt hộ
        $minhChungDuyetHoPath = null;
        if (!empty($minhChungDuyetHo)) {
            $folderName = 'hrm/minh-chung-duyet-ho';
            $uploadedFile = $this->fileupload->upload($minhChungDuyetHo, $folderName, 'jpg|jpeg|png|pdf');

            if ($uploadedFile['success']) {
                $minhChungDuyetHoPath = $uploadedFile['file_path'];
            } else {
                resError(['minh_chung_duyet_ho' => 'Không thể tải lên file minh chứng'], 'Upload thất bại', REST_Controller::HTTP_BAD_REQUEST);
            }
        }

        // Bắt đầu transaction
        $this->db->trans_start();

        try {
            $ketQuaDuyet = [];

            foreach ($uuidsNghiPhep as $uuid) {
                // Lấy thông tin đơn nghỉ phép
                $nghiPhep = $this->Hrm_nghi_phep_model
                    ->where('uuid_nghi_phep', $uuid)
                    ->first();

                if (!$nghiPhep) {
                    $ketQuaDuyet[] = [
                        'uuid_nghi_phep' => $uuid,
                        'success' => false,
                        'message' => 'Đơn nghỉ phép không tồn tại'
                    ];
                    continue;
                }

                $idNghiPhep = $nghiPhep['id_nghi_phep'];

                // Kiểm tra người được chọn có phải người duyệt không và lấy thông tin cấp duyệt
                $nguoiDuyet = $this->db
                    ->select('*')
                    ->where('id_nghi_phep', $idNghiPhep)
                    ->where('id_nguoi_duyet', $idNguoiDuyetHo)
                    ->get('hrm_nghi_phep_nguoi_duyet')
                    ->row_array();

                if (!$nguoiDuyet) {
                    $ketQuaDuyet[] = [
                        'uuid_nghi_phep' => $uuid,
                        'success' => false,
                        'message' => 'Người được chọn không phải người duyệt của đơn này'
                    ];
                    continue;
                }

                // Xác định cấp duyệt từ thông tin người duyệt
                $capDuyet = $nguoiDuyet['cap_duyet'];

                // Kiểm tra chỉ cho phép duyệt hộ cấp 1
                if ($capDuyet != 1) {
                    $ketQuaDuyet[] = [
                        'uuid_nghi_phep' => $uuid,
                        'success' => false,
                        'message' => 'Hiện tại hệ thống chỉ hỗ trợ duyệt hộ cấp 1 (cấp đơn vị)'
                    ];
                    continue;
                }

                // Lấy thông tin nhân viên xin nghỉ để kiểm tra đơn vị
                $nhanVienNghi = $this->db
                    ->select('nv.id_don_vi_cong_tac')
                    ->from('hrm_nhan_vien nv')
                    ->where('nv.id_nhan_vien', $nghiPhep['id_nhan_vien'])
                    ->get()
                    ->row_array();

                // Kiểm tra quyền theo cấp duyệt
                if ($capDuyet == 1) {
                    // Cấp 1: Văn thư chỉ duyệt hộ cho đơn vị của mình
                    if (!$nhanVienNghi || $nhanVienNghi['id_don_vi_cong_tac'] != $idDonViVanThu) {
                        $ketQuaDuyet[] = [
                            'uuid_nghi_phep' => $uuid,
                            'success' => false,
                            'message' => 'Đơn này không thuộc đơn vị của bạn'
                        ];
                        continue;
                    }
                }
                // Cấp 2: Văn thư TC-HC duyệt hộ cho tất cả các đơn (không cần kiểm tra đơn vị)

                // Kiểm tra trạng thái theo cấp duyệt
                if ($capDuyet == 1) {
                    // Cấp 1: Không cho phép thay đổi nếu đã xử lý
                    if ($nghiPhep['trang_thai_cap_mot'] != 'Cho_duyet') {
                        $ketQuaDuyet[] = [
                            'uuid_nghi_phep' => $uuid,
                            'success' => false,
                            'message' => 'Đơn này đã được xử lý cấp 1 rồi, không thể thay đổi'
                        ];
                        continue;
                    }
                } else {
                    // Cấp 2: Phải đã được duyệt cấp 1 và chưa xử lý cấp 2
                    if ($nghiPhep['trang_thai_cap_mot'] != 'Da_duyet') {
                        $ketQuaDuyet[] = [
                            'uuid_nghi_phep' => $uuid,
                            'success' => false,
                            'message' => 'Đơn này chưa được duyệt cấp 1, không thể duyệt cấp 2'
                        ];
                        continue;
                    }
                    if ($nghiPhep['trang_thai_cap_hai'] != 'Cho_duyet') {
                        $ketQuaDuyet[] = [
                            'uuid_nghi_phep' => $uuid,
                            'success' => false,
                            'message' => 'Đơn này đã được xử lý cấp 2 rồi, không thể thay đổi'
                        ];
                        continue;
                    }
                }

                // Xử lý duyệt/từ chối
                if ($hanhDong == 'duyet') {
                    // Update bảng hrm_nghi_phep_nguoi_duyet
                    $updateNguoiDuyet = [
                        'da_duyet' => 1,
                        'thoi_gian_duyet' => date('Y-m-d H:i:s'),
                        'ly_do' => $lyDo,
                        'duyet_ho' => 1,
                        'id_duyet_ho' => $idVanThu
                    ];

                    if (!empty($minhChungDuyetHoPath)) {
                        $updateNguoiDuyet['minh_chung_duyet_ho'] = $minhChungDuyetHoPath;
                    }

                    $this->db->where('id_nghi_phep_nguoi_duyet', $nguoiDuyet['id_nghi_phep_nguoi_duyet'])
                        ->update('hrm_nghi_phep_nguoi_duyet', $updateNguoiDuyet);

                    if ($capDuyet == 1) {
                        // Cấp 1: Update bảng hrm_nghi_phep
                        $this->db->where('id_nghi_phep', $idNghiPhep)
                            ->update('hrm_nghi_phep', [
                                'trang_thai_cap_mot' => 'Da_duyet',
                                'nguoi_duyet_cap_mot_id' => $idNguoiDuyetHo,
                                'updated_user_id' => $idVanThu
                            ]);

                        // Thêm người duyệt cấp 2 (TC-HC)
                        $dsNguoiDuyetCapHai = $this->db->select('ldv.ql_nguoi_dung_id AS id_nguoi_duyet')
                            ->from('e_lanh_dao_don_vi ldv')
                            ->where('ldv.id_don_vi', 15) // Phòng TC-HC
                            ->where('ldv.deleted_at IS NULL')
                            ->get()
                            ->result_array();

                        foreach ($dsNguoiDuyetCapHai as $nguoiDuyetCap2) {
                            $idNguoiDuyetCap2 = $nguoiDuyetCap2['id_nguoi_duyet'];
                            $daTonTai = $this->db
                                ->where('id_nghi_phep', $idNghiPhep)
                                ->where('id_nguoi_duyet', $idNguoiDuyetCap2)
                                ->where('cap_duyet', 2)
                                ->count_all_results('hrm_nghi_phep_nguoi_duyet');

                            if ($daTonTai == 0) {
                                $this->db->insert('hrm_nghi_phep_nguoi_duyet', [
                                    'id_nghi_phep' => $idNghiPhep,
                                    'id_nguoi_duyet' => $idNguoiDuyetCap2,
                                    'cap_duyet' => 2,
                                    'da_duyet' => 0,
                                    'thoi_gian_duyet' => null,
                                    'created_at' => date('Y-m-d H:i:s')
                                ]);
                            }
                        }
                    } else {
                        // Cấp 2: Update bảng hrm_nghi_phep
                        $this->db->where('id_nghi_phep', $idNghiPhep)
                            ->update('hrm_nghi_phep', [
                                'trang_thai_cap_hai' => 'Da_duyet',
                                'nguoi_duyet_cap_hai_id' => $idNguoiDuyetHo,
                                'updated_user_id' => $idVanThu
                            ]);
                    }

                    // Log thay đổi
                    $this->db->insert('hrm_nghi_phep_log_duyet', [
                        'id_nghi_phep'      => $idNghiPhep,
                        'cap_duyet'         => $capDuyet,
                        'trang_thai_cu'     => 'Cho_duyet',
                        'trang_thai_moi'    => 'Da_duyet',
                        'hanh_dong'         => 'duyet',
                        'id_nguoi_duyet'    => $idVanThu,
                        'ly_do'             => $lyDo,
                        'thoi_gian_thay_doi' => date('Y-m-d H:i:s')
                    ]);

                    $ketQuaDuyet[] = [
                        'uuid_nghi_phep' => $uuid,
                        'success' => true,
                        'message' => "Văn thư duyệt hộ đơn nghỉ phép cấp {$capDuyet} thành công",
                        'hanh_dong' => 'duyet',
                        'cap_duyet' => $capDuyet,
                        'duyet_ho' => true
                    ];
                } else {
                    // Từ chối
                    $updateNguoiDuyet = [
                        'da_duyet' => 0,
                        'thoi_gian_duyet' => date('Y-m-d H:i:s'),
                        'ly_do' => $lyDo,
                        'duyet_ho' => 1,
                        'id_duyet_ho' => $idVanThu
                    ];

                    if (!empty($minhChungDuyetHoPath)) {
                        $updateNguoiDuyet['minh_chung_duyet_ho'] = $minhChungDuyetHoPath;
                    }

                    $this->db->where('id_nghi_phep_nguoi_duyet', $nguoiDuyet['id_nghi_phep_nguoi_duyet'])
                        ->update('hrm_nghi_phep_nguoi_duyet', $updateNguoiDuyet);

                    if ($capDuyet == 1) {
                        // Cấp 1: Update bảng hrm_nghi_phep
                        $this->db->where('id_nghi_phep', $idNghiPhep)
                            ->update('hrm_nghi_phep', [
                                'trang_thai_cap_mot' => 'Tu_choi',
                                'nguoi_duyet_cap_mot_id' => $idNguoiDuyetHo,
                                'updated_user_id' => $idVanThu
                            ]);
                    } else {
                        // Cấp 2: Update bảng hrm_nghi_phep
                        $this->db->where('id_nghi_phep', $idNghiPhep)
                            ->update('hrm_nghi_phep', [
                                'trang_thai_cap_hai' => 'Tu_choi',
                                'nguoi_duyet_cap_hai_id' => $idNguoiDuyetHo,
                                'updated_user_id' => $idVanThu
                            ]);
                    }

                    // Log thay đổi
                    $this->db->insert('hrm_nghi_phep_log_duyet', [
                        'id_nghi_phep'      => $idNghiPhep,
                        'cap_duyet'         => $capDuyet,
                        'trang_thai_cu'     => 'Cho_duyet',
                        'trang_thai_moi'    => 'Tu_choi',
                        'hanh_dong'         => 'tu_choi',
                        'id_nguoi_duyet'    => $idVanThu,
                        'ly_do'             => $lyDo,
                        'thoi_gian_thay_doi' => date('Y-m-d H:i:s')
                    ]);

                    $ketQuaDuyet[] = [
                        'uuid_nghi_phep' => $uuid,
                        'success' => true,
                        'message' => "Văn thư từ chối hộ đơn nghỉ phép cấp {$capDuyet} thành công",
                        'hanh_dong' => 'tu_choi',
                        'cap_duyet' => $capDuyet,
                        'duyet_ho' => true
                    ];
                }
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                resError([], 'Xử lý đơn nghỉ phép thất bại', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Đếm số lượng thành công/thất bại
            $soLuongThanhCong = count(array_filter($ketQuaDuyet, function ($item) {
                return $item['success'] === true;
            }));
            $tongSoLuong = count($ketQuaDuyet);

            $message = "Văn thư đã xử lý {$soLuongThanhCong}/{$tongSoLuong} đơn nghỉ phép";

            // Log duyệt hộ
            $this->createLog(
                'approve_on_behalf',
                "Văn thư duyệt hộ {$soLuongThanhCong}/{$tongSoLuong} đơn nghỉ phép cho người duyệt ID: {$idNguoiDuyetHo}",
                ['action' => $hanhDong, 'uuids' => $uuidsNghiPhep],
                $ketQuaDuyet,
                'hrm_nghi_phep'
            );

            

            resSuccess([
                'ket_qua' => $ketQuaDuyet,
                'tong_so_luong' => $tongSoLuong,
                'thanh_cong' => $soLuongThanhCong,
                'that_bai' => $tongSoLuong - $soLuongThanhCong
            ], $message, REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->db->trans_rollback();
            resError(['message' => $e->getMessage()], 'Có lỗi xảy ra', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update_post()
    {
        $auth = $this->getUserLogin();

        // Nhận dữ liệu từ request
        $uuidNghiPhep = commonRequest('uuid_nghi_phep');
        $idLoaiPhep = commonRequest('id_loai_phep');
        $loaiNghi = commonRequest('loai_nghi');
        $lyDoNghi = commonRequest('ly_do_nghi');
        $danhSachNgayNghi = commonRequest('danh_sach_ngay_nghi'); // Array of {ngay_nghi, sang, chieu}
        $minhChung = commonRequest('minh_chung'); // File upload (optional)

        // Validate
        if (empty($uuidNghiPhep)) {
            resError(['uuid_nghi_phep' => 'UUID nghỉ phép không được để trống'], 'Dữ liệu không hợp lệ', REST_Controller::HTTP_BAD_REQUEST);
        }

        // Lấy thông tin đơn nghỉ phép
        $nghiPhep = $this->Hrm_nghi_phep_model
            ->where('uuid_nghi_phep', $uuidNghiPhep)
            ->first();

        if (!$nghiPhep) {
            resError(['uuid_nghi_phep' => 'Đơn nghỉ phép không tồn tại'], 'Không tìm thấy', REST_Controller::HTTP_NOT_FOUND);
        }

        // Kiểm tra quyền sửa (chỉ được sửa nếu chưa duyệt cả 2 cấp)
        if ($nghiPhep['trang_thai_cap_mot'] != 'Cho_duyet') {
            resError([], 'Không thể sửa đơn đã được duyệt hoặc từ chối cấp 1', REST_Controller::HTTP_FORBIDDEN);
        }

        if ($nghiPhep['trang_thai_cap_hai'] != 'Cho_duyet') {
            resError([], 'Không thể sửa đơn đã được duyệt hoặc từ chối cấp 2', REST_Controller::HTTP_FORBIDDEN);
        }

        // Xử lý JSON string cho danh_sach_ngay_nghi
        if (is_string($danhSachNgayNghi)) {
            $danhSachNgayNghi = json_decode($danhSachNgayNghi, true);
        }

        // Gộp các ngày trùng nhau (merge buổi sáng/chiều)
        $ngayNghiMerged = [];
        if (is_array($danhSachNgayNghi) && !empty($danhSachNgayNghi)) {
            foreach ($danhSachNgayNghi as $item) {
                // Convert object to array nếu cần
                if (is_object($item)) {
                    $item = (array) $item;
                }

                $ngay = isset($item['ngay_nghi']) ? $item['ngay_nghi'] : null;

                if ($ngay) {
                    if (!isset($ngayNghiMerged[$ngay])) {
                        $ngayNghiMerged[$ngay] = [
                            'ngay_nghi' => $ngay,
                            'sang' => false,
                            'chieu' => false
                        ];
                    }
                    // Gộp buổi sáng/chiều
                    if (isset($item['sang']) && $item['sang']) {
                        $ngayNghiMerged[$ngay]['sang'] = true;
                    }
                    if (isset($item['chieu']) && $item['chieu']) {
                        $ngayNghiMerged[$ngay]['chieu'] = true;
                    }
                }
            }
            $danhSachNgayNghi = array_values($ngayNghiMerged);
        }

        // Xử lý upload file minh chứng nếu có
        $minhChungPath = $nghiPhep['minh_chung']; // Giữ nguyên nếu không upload mới
        if (!empty($minhChung)) {
            $folderName = 'hrm/minh-chung-nghi-phep';
            $uploadedFile = $this->fileupload->upload($minhChung, $folderName, 'jpg|jpeg|png');

            if ($uploadedFile['success']) {
                $minhChungPath = $uploadedFile['file_path'];
            } else {
                resError(['minh_chung' => 'Không thể tải lên file minh chứng'], 'Upload thất bại', REST_Controller::HTTP_BAD_REQUEST);
            }
        }

        // Bắt đầu transaction
        $this->db->trans_start();

        try {
            $idNghiPhep = $nghiPhep['id_nghi_phep'];

            // Update bảng hrm_nghi_phep
            $dataUpdate = [
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_user_id' => $auth['ql_nguoi_dung_id']
            ];

            if ($idLoaiPhep !== null) {
                $dataUpdate['id_loai_phep'] = $idLoaiPhep;
            }

            if ($loaiNghi !== null) {
                $dataUpdate['loai_nghi'] = $loaiNghi;
            }

            if ($lyDoNghi !== null) {
                $dataUpdate['ly_do_nghi'] = $lyDoNghi;
            }

            if ($minhChungPath !== null) {
                $dataUpdate['minh_chung'] = $minhChungPath;
            }

            $this->db->where('id_nghi_phep', $idNghiPhep)
                ->update('hrm_nghi_phep', $dataUpdate);

            // Nếu có cập nhật danh sách ngày nghỉ
            if (!empty($danhSachNgayNghi)) {
                // Xóa chi tiết cũ
                $this->db->where('id_nghi_phep', $idNghiPhep)
                    ->delete('hrm_nghi_phep_chi_tiet');

                // Insert chi tiết mới
                foreach ($danhSachNgayNghi as $ngayNghi) {
                    $ngay = $ngayNghi['ngay_nghi'];
                    $buoiNghiList = [];

                    // Kiểm tra buổi sáng
                    if (isset($ngayNghi['sang']) && $ngayNghi['sang']) {
                        $buoiNghiList[] = [
                            'id_nghi_phep' => $idNghiPhep,
                            'ngay_nghi' => $ngay,
                            'buoi_nghi' => 'Sang',
                            'so_ngay_nghi' => 0.5
                        ];
                    }

                    // Kiểm tra buổi chiều
                    if (isset($ngayNghi['chieu']) && $ngayNghi['chieu']) {
                        $buoiNghiList[] = [
                            'id_nghi_phep' => $idNghiPhep,
                            'ngay_nghi' => $ngay,
                            'buoi_nghi' => 'Chieu',
                            'so_ngay_nghi' => 0.5
                        ];
                    }

                    // Insert các buổi nghỉ
                    if (!empty($buoiNghiList)) {
                        $this->db->insert_batch('hrm_nghi_phep_chi_tiet', $buoiNghiList);
                    }
                }
            }

            // Commit transaction
            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                resError([], 'Cập nhật đơn nghỉ phép thất bại', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Lấy thông tin đơn nghỉ phép sau khi update
            $nghiPhepUpdated = $this->Hrm_nghi_phep_model
                ->select('hrm_nghi_phep.*, hrm_nhan_vien.ho_va_ten, hrm_nhan_vien.ma_nhan_vien')
                ->join('hrm_nhan_vien', 'hrm_nhan_vien.id_nhan_vien = hrm_nghi_phep.id_nhan_vien', 'left')
                ->find($idNghiPhep);

            $nghiPhepUpdated = $this->Hrm_nghi_phep_model->formatNghiPhep($nghiPhepUpdated);
            
            // Log cập nhật
            $this->createLog(
                'update',
                'Cập nhật đơn nghỉ phép ID: ' . $idNghiPhep,
                $nghiPhep,
                $nghiPhepUpdated,
                'hrm_nghi_phep'
            );
            
            resSuccess($nghiPhepUpdated, 'Cập nhật đơn nghỉ phép thành công', REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->db->trans_rollback();
            resError(['message' => $e->getMessage()], 'Có lỗi xảy ra khi cập nhật đơn nghỉ phép', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function minh_chung_post()
    {
        $auth = $this->getUserLogin();
        $uuidNghiPhep = commonRequest('uuid_nghi_phep');
        $minhChung = commonRequest('minh_chung');

        if (empty($uuidNghiPhep)) {
            resError(['uuid_nghi_phep' => 'UUID nghỉ phép không được để trống'], 'Dữ liệu không hợp lệ', REST_Controller::HTTP_BAD_REQUEST);
        }

        $nghiPhep = $this->Hrm_nghi_phep_model->where('uuid_nghi_phep', $uuidNghiPhep)->first();
        if (!$nghiPhep) {
            resError([], 'Đơn nghỉ phép không tồn tại', REST_Controller::HTTP_NOT_FOUND);
        }

        // Kiểm tra quyền (Chủ sở hữu, Admin hoặc Lãnh đạo)
        $isOwner = $nghiPhep['created_user_id'] == $auth['ql_nguoi_dung_id'];
        $isAdmin = (int) $auth['ql_nguoi_dung_is_admin'] === 1;

        // Kiểm tra xem có phải lãnh đạo không
        $isLanhDao = $this->db
            ->from('e_lanh_dao_don_vi')
            ->where('ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])
            ->where('deleted_at IS NULL')
            ->count_all_results() > 0;

        if (!$isOwner && !$isAdmin && !$isLanhDao) {
            resError([], 'Bạn không có quyền bổ sung minh chứng cho đơn này', REST_Controller::HTTP_FORBIDDEN);
        }

        // Chặn thay đổi nếu đơn đã có kết quả cuối (trừ Admin)
        // if (!$isAdmin && $nghiPhep['trang_thai_cap_hai'] !== 'Cho_duyet') {
        //     resError([], 'Đơn đã có kết quả phê duyệt cuối cùng, không thể thay đổi minh chứng', REST_Controller::HTTP_FORBIDDEN);
        // }

        $minhChungPath = null;
        if (!empty($minhChung)) {
            $folderName = 'hrm/minh-chung-nghi-phep';
            $uploadedFile = $this->fileupload->upload($minhChung, $folderName, 'jpg|jpeg|png');

            if ($uploadedFile['success']) {
                $minhChungPath = $uploadedFile['file_path'];

                // Xóa minh chứng cũ nếu có
                if (!empty($nghiPhep['minh_chung'])) {
                    $oldFilePath = FCPATH . $nghiPhep['minh_chung'];
                    // Kiểm tra đường dẫn tương đối (trong DB) hoặc tuyệt đối
                    if (file_exists($nghiPhep['minh_chung'])) {
                        @unlink($nghiPhep['minh_chung']);
                    } elseif (file_exists($oldFilePath)) {
                        @unlink($oldFilePath);
                    }
                }
            } else {
                resError(['minh_chung' => 'Không thể tải lên file minh chứng'], 'Upload thất bại', REST_Controller::HTTP_BAD_REQUEST);
            }
        } else {
            resError(['minh_chung' => 'Vui lòng chọn file minh chứng'], 'Thiếu dữ liệu', REST_Controller::HTTP_BAD_REQUEST);
        }

        $result = $this->db->where('id_nghi_phep', $nghiPhep['id_nghi_phep'])
            ->update('hrm_nghi_phep', [
                'minh_chung' => $minhChungPath,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_user_id' => $auth['ql_nguoi_dung_id']
            ]);

        if ($result) {
            $formatted = $this->Hrm_nghi_phep_model->formatNghiPhep(['minh_chung' => $minhChungPath]);
            resSuccess($formatted, 'Bổ sung minh chứng thành công', REST_Controller::HTTP_OK);
        } else {
            resError([], 'Cập nhật thất bại', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function get_employee_by_unit_get()
    {
        $auth = $this->getUserLogin();
        $qlNguoiDungId = $auth['ql_nguoi_dung_id'];
        // Lấy thông tin nhân viên của user hiện tại
        $nhanVien = $this->Hrm_nhan_vien_model
            ->select('hrm_nhan_vien.id_nhan_vien, hrm_nhan_vien.id_don_vi_cong_tac, e_don_vi.ma_don_vi')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_nhan_vien.id_don_vi_cong_tac', 'left')
            ->where('hrm_nhan_vien.ql_nguoi_dung_id', $qlNguoiDungId)
            ->first();

        $idDonViCongTac = $nhanVien ? $nhanVien['id_don_vi_cong_tac'] : $auth['id_don_vi'];
        $data = $this->Hrm_nhan_vien_model->getNhanVienCungDonVi($idDonViCongTac);

        $result = array_map(function ($item) {
            return [
                'value' => $item['id_nhan_vien'],
                'label' => $item['ho_va_ten'] . ' (' . $item['ma_nhan_vien'] . ')',
                'ql_nguoi_dung_id' => $item['ql_nguoi_dung_id'],
            ];
        }, $data);

        resSuccess($result, 'Lấy danh sách nhân viên', REST_Controller::HTTP_OK);
    }
    public function statistics_get()
    {
        $auth = $this->getUserLogin();
        $qlNguoiDungId = $auth['ql_nguoi_dung_id'];
        $year = commonRequest('year') ? commonRequest('year') : date('Y');

        $start_date = commonRequest('start_date');
        $end_date = commonRequest('end_date');

        // Nhận id_don_vi từ request (nếu có)
        $idDonViRequest = commonRequest('id_don_vi');



        if ($idDonViRequest) {
            // Nếu client truyền id_don_vi thì dùng đơn vị đó
            $idDonVi = $idDonViRequest;
        } else {
            // Fallback: Lấy đơn vị công tác hiện tại của user
            $nhanVien = $this->Hrm_nhan_vien_model
                ->select('id_don_vi_cong_tac')
                ->where('ql_nguoi_dung_id', $qlNguoiDungId)
                ->first();

            $idDonVi = $nhanVien ? $nhanVien['id_don_vi_cong_tac'] : $auth['id_don_vi'];
        }

        if (!$idDonVi) {
            resSuccess([], 'Không tìm thấy đơn vị công tác', REST_Controller::HTTP_OK);
            return;
        }

        $data = $this->Hrm_nghi_phep_model->getQuotaStats($idDonVi, $start_date, $end_date, $qlNguoiDungId);
        resSuccess($data, 'Lấy thống kê thành công', REST_Controller::HTTP_OK);
    }

    /**
     * Lấy danh sách người duyệt của một đơn nghỉ phép (cho văn thư chọn duyệt hộ)
     * Nếu là văn thư phòng TC-HC, trả về cả người duyệt cấp 1 và cấp 2
     */
    public function get_approvers_get()
    {
        $auth = $this->getUserLogin();
        $idVanThu = $auth['ql_nguoi_dung_id'];
        $uuidNghiPhep = commonRequest('uuid_nghi_phep');

        if (empty($uuidNghiPhep)) {
            resError(['uuid_nghi_phep' => 'UUID nghỉ phép không được để trống'], 'Dữ liệu không hợp lệ', REST_Controller::HTTP_BAD_REQUEST);
        }

        // Lấy thông tin đơn nghỉ phép
        $nghiPhep = $this->Hrm_nghi_phep_model->where('uuid_nghi_phep', $uuidNghiPhep)->first();
        if (!$nghiPhep) {
            resError(['uuid_nghi_phep' => 'Đơn nghỉ phép không tồn tại'], 'Không tìm thấy', REST_Controller::HTTP_NOT_FOUND);
        }

        $idNghiPhep = $nghiPhep['id_nghi_phep'];

        // Kiểm tra đơn vị của văn thư
        $nhanVienVanThu = $this->db
            ->select('nv.id_don_vi_cong_tac')
            ->from('hrm_nhan_vien nv')
            ->where('nv.ql_nguoi_dung_id', $idVanThu)
            ->get()
            ->row_array();

        $idDonViVanThu = $nhanVienVanThu ? $nhanVienVanThu['id_don_vi_cong_tac'] : null;
        $isPhongTCHC = ($idDonViVanThu == 15);

        // Lấy danh sách người duyệt theo cấp
        $query = $this->db
            ->select('
                npd.id_nguoi_duyet, 
                npd.cap_duyet,
                nd.ql_nguoi_dung_ho_ten as ho_ten,
                nv.ma_nhan_vien,
                npd.da_duyet,
                npd.thoi_gian_duyet
            ')
            ->from('hrm_nghi_phep_nguoi_duyet npd')
            ->join('ql_nguoi_dung nd', 'nd.ql_nguoi_dung_id = npd.id_nguoi_duyet', 'left')
            ->join('hrm_nhan_vien nv', 'nv.ql_nguoi_dung_id = nd.ql_nguoi_dung_id', 'left')
            ->where('npd.id_nghi_phep', $idNghiPhep);

        // Nếu là văn thư phòng TC-HC: lấy cả cấp 1 và cấp 2
        // Nếu không: chỉ lấy cấp 1
        if (!$isPhongTCHC) {
            $query->where('npd.cap_duyet', 1);
        }

        $danhSachNguoiDuyet = $query->order_by('npd.cap_duyet', 'ASC')->get()->result_array();

        resSuccess($danhSachNguoiDuyet, 'Lấy danh sách người duyệt thành công', REST_Controller::HTTP_OK);
    }

    /**
     * Gửi email thông báo sau khi duyệt/từ chối
     * @param string $uuidNghiPhep UUID đơn nghỉ phép
     * @param string $hanhDong 'duyet' hoặc 'tu_choi'
     * @param int $capDuyet 1 hoặc 2
     */
    public function send_email_after_approved_post()
    {
        $uuidNghiPhep = commonRequest('uuid_nghi_phep');
        $hanhDong = commonRequest('hanh_dong');
        $capDuyet = commonRequest('cap_duyet');

        // Lấy thông tin đơn nghỉ phép
        $nghiPhep = $this->Hrm_nghi_phep_model->where('uuid_nghi_phep', $uuidNghiPhep)->first();
        if (!$nghiPhep)
            return;

        $idNghiPhep = $nghiPhep['id_nghi_phep'];

        // Lấy chi tiết nghỉ phép với thông tin đầy đủ
        $chiTietNghiPhep = $this->db
            ->select('
                np.*,
                DATE_FORMAT(np.created_at, "%d/%m/%Y %H:%i:%s") AS created_at,
                nv.ho_va_ten, 
                nv.ma_nhan_vien, 
                nv.id_don_vi_cong_tac,
                nv.email as email_nhan_vien,
                dv.ten_don_vi,
                lnp.ten_loai_phep
            ')
            ->from('hrm_nghi_phep np')
            ->join('hrm_nhan_vien nv', 'nv.id_nhan_vien = np.id_nhan_vien', 'left')
            ->join('e_don_vi dv', 'dv.id_don_vi = nv.id_don_vi_cong_tac', 'left')
            ->join('hrm_danh_muc_loai_nghi_phep lnp', 'lnp.id_loai_phep = np.id_loai_phep', 'left')
            ->where('np.id_nghi_phep', $idNghiPhep)
            ->get()
            ->row_array();

        if (!$chiTietNghiPhep)
            return;

        // Lấy chi tiết ngày nghỉ
        $chiTietNgayNghi = $this->db
            ->select('ngay_nghi, buoi_nghi, so_ngay_nghi')
            ->from('hrm_nghi_phep_chi_tiet')
            ->where('id_nghi_phep', $idNghiPhep)
            ->order_by('ngay_nghi', 'ASC')
            ->get()
            ->result_array();

        // Gộp buổi sáng/chiều cùng ngày thành "Cả ngày"
        $chiTietNgayNghiMerged = [];
        foreach ($chiTietNgayNghi as $item) {
            $ngay = $item['ngay_nghi'];
            if (!isset($chiTietNgayNghiMerged[$ngay])) {
                $chiTietNgayNghiMerged[$ngay] = [
                    'ngay_nghi' => $ngay,
                    'buoi' => [],
                    'so_ngay_nghi' => 0
                ];
            }
            $chiTietNgayNghiMerged[$ngay]['buoi'][] = $item['buoi_nghi'];
            $chiTietNgayNghiMerged[$ngay]['so_ngay_nghi'] += $item['so_ngay_nghi'];
        }

        $chiTietNgayNghiFormatted = [];
        foreach ($chiTietNgayNghiMerged as $ngay => $info) {
            $buoiNghi = (count($info['buoi']) == 2) ? 'Ca_ngay' : $info['buoi'][0];
            $chiTietNgayNghiFormatted[] = [
                'ngay_nghi' => $ngay,
                'buoi_nghi' => $buoiNghi,
                'so_ngay_nghi' => $info['so_ngay_nghi']
            ];
        }

        // Tính tổng số ngày nghỉ
        $tongSoNgayNghi = array_sum(array_column($chiTietNgayNghiFormatted, 'so_ngay_nghi'));

        // Lấy danh sách người duyệt
        $danhSachNguoiDuyet = $this->db
            ->select('nd.ql_nguoi_dung_ho_ten as ho_ten, npd.cap_duyet, npd.da_duyet, npd.ly_do, npd.thoi_gian_duyet')
            ->from('hrm_nghi_phep_nguoi_duyet npd')
            ->join('ql_nguoi_dung nd', 'nd.ql_nguoi_dung_id = npd.id_nguoi_duyet', 'left')
            ->where('npd.id_nghi_phep', $idNghiPhep)
            ->order_by('npd.cap_duyet', 'ASC')
            ->get()
            ->result_array();

        // Link duyệt đơn
        $linkDuyet = $this->linkDuyet . $uuidNghiPhep;

        // Chuẩn bị dữ liệu email
        $emailData = [
            'ho_va_ten' => $chiTietNghiPhep['ho_va_ten'],
            'ma_nhan_vien' => $chiTietNghiPhep['ma_nhan_vien'],
            'ten_don_vi' => $chiTietNghiPhep['ten_don_vi'],
            'ten_loai_phep' => $chiTietNghiPhep['ten_loai_phep'],
            'loai_nghi' => $chiTietNghiPhep['loai_nghi'],
            'so_ngay_nghi' => $tongSoNgayNghi,
            'ly_do_nghi' => $chiTietNghiPhep['ly_do_nghi'],
            'uuid_nghi_phep' => $uuidNghiPhep,
            'trang_thai_cap_mot' => $chiTietNghiPhep['trang_thai_cap_mot'],
            'trang_thai_cap_hai' => $chiTietNghiPhep['trang_thai_cap_hai'],
            'chi_tiet_ngay_nghi' => $chiTietNgayNghiFormatted,
            'danh_sach_nguoi_duyet' => $danhSachNguoiDuyet,
            'link_duyet' => $linkDuyet,
            'file_dinh_kem' => !empty($chiTietNghiPhep['minh_chung']) ? [$chiTietNghiPhep['minh_chung']] : [],
            'created_at' => $chiTietNghiPhep['created_at']
        ];

        // // Kiểm tra domain trước khi gửi email
        $currentDomain = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : (isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER'], PHP_URL_SCHEME) . '://' . parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) : '');
        $isProduction = ($currentDomain === 'https://myoffice.nctu.edu.vn');

        if (!$isProduction) {
            log_message('info', 'Email không được gửi (domain test) trong send_email_after_approved_post: ' . $currentDomain);
            return; // Không gửi email trên môi trường test
        }

        // 1. Gửi email cho nhân viên (sử dụng template cá nhân)
        if (!empty($chiTietNghiPhep['email_nhan_vien'])) {
            $emailData['email'] = $chiTietNghiPhep['email_nhan_vien'];
            $this->sendApprovalEmail($emailData, true);
        }

        // 2. Nếu duyệt cấp 1 thành công, gửi email cho cấp 2 (TC-HC)
        if ($capDuyet == 1 && $hanhDong == 'duyet') {
            $emailCapHai = $this->db
                ->select('nv.ho_va_ten, nv.email')
                ->from('e_lanh_dao_don_vi ldv')
                ->join('ql_nguoi_dung nd', 'nd.ql_nguoi_dung_id = ldv.ql_nguoi_dung_id', 'left')
                ->join('hrm_nhan_vien nv', 'nv.ql_nguoi_dung_id = nd.ql_nguoi_dung_id', 'left')
                ->where('ldv.id_don_vi', 15) // Phòng TC-HC
                ->where('ldv.deleted_at IS NULL')
                ->where('nv.email IS NOT NULL')
                ->where('nv.email !=', '')
                ->get()
                ->result_array();

            foreach ($emailCapHai as $nguoiCapHai) {
                if (!empty($nguoiCapHai['email'])) {
                    $emailData['email'] = $nguoiCapHai['email'];
                    $this->sendApprovalEmail($emailData, false);
                }
            }
        }
    }

    private function sendApprovalEmail($emailData, $isPersonal = false)
    {
        try {
            // Kiểm tra domain - chỉ gửi email thật khi là production
            $currentDomain = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : (isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER'], PHP_URL_SCHEME) . '://' . parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) : '');
            $isProduction = ($currentDomain === 'https://myoffice.nctu.edu.vn');

            // Nếu không phải production, return true mà không gửi email
            if (!$isProduction) {
                log_message('info', 'Email không được gửi (domain test): ' . $currentDomain);
                return true; // Return true để không block workflow
            }

            $viewData = ['data' => $emailData];
            // Sử dụng template cá nhân nếu gửi cho bản thân, ngược lại dùng template lãnh đạo
            $template = $isPersonal ? 'email/nghiphep_canhan_template.php' : 'email/nghiphep_template.php';
            $message = $this->load->view($template, $viewData, true);

            // Địa chỉ email người nhận
            $user_info = isset($emailData['email']) ? $emailData['email'] : '';

            // Kiểm tra email có tồn tại không
            if (empty($user_info)) {
                return false;
            }

            // Tiêu đề email
            $hoVaTen = isset($emailData['ho_va_ten']) ? $emailData['ho_va_ten'] : '';
            $tenLoaiPhep = isset($emailData['ten_loai_phep']) ? $emailData['ten_loai_phep'] : '';

            // Xử lý ngày nghỉ cho subject
            $chiTietNgayNghi = isset($emailData['chi_tiet_ngay_nghi']) ? $emailData['chi_tiet_ngay_nghi'] : [];
            $ngayNghiText = '';

            if (!empty($chiTietNgayNghi)) {
                $soNgay = count($chiTietNgayNghi);
                if ($soNgay == 1) {
                    // Chỉ 1 ngày
                    $ngay = $chiTietNgayNghi[0]['ngay_nghi'];
                    $ngayNghiText = ' - Ngày ' . date('d/m/Y', strtotime($ngay));
                } else {
                    // Nhiều ngày
                    $tuNgay = $chiTietNgayNghi[0]['ngay_nghi'];
                    $denNgay = $chiTietNgayNghi[$soNgay - 1]['ngay_nghi'];
                    $ngayNghiText = ' - Từ ' . date('d/m/Y', strtotime($tuNgay)) . ' đến ' . date('d/m/Y', strtotime($denNgay));
                }
            }

            $subject = "Đơn xin nghỉ phép - {$hoVaTen} - {$tenLoaiPhep}{$ngayNghiText}";

            // File đính kèm (nếu có)
            $file_dinh_kem = isset($emailData['file_dinh_kem']) ? $emailData['file_dinh_kem'] : [];

            // CC và BCC
            $cc = isset($emailData['cc']) ? $emailData['cc'] : [];
            $bcc = isset($emailData['bcc']) ? $emailData['bcc'] : [];

            // Gửi email
            $result = send_email($user_info, $subject, $message, 'noreply@tchc.nctu.edu.vn', 'Trường Đại Học Nam Cần Thơ - DNC University', $file_dinh_kem, $cc, $bcc);

            return $result;
        } catch (Exception $e) {
            // Log lỗi và return false
            log_message('error', 'Lỗi gửi email nghỉ phép: ' . $e->getMessage());
            return false;
        }
    }

    public function send_approval_email_post()
    {
        $uuidNghiPhep = commonRequest('uuid_nghi_phep');

        // Validate
        if (empty($uuidNghiPhep)) {
            resError(['uuid_nghi_phep' => 'UUID nghỉ phép không được để trống'], 'Dữ liệu không hợp lệ', REST_Controller::HTTP_BAD_REQUEST);
        }

        try {
            // Lấy thông tin đơn nghỉ phép
            $nghiPhep = $this->Hrm_nghi_phep_model
                ->where('uuid_nghi_phep', $uuidNghiPhep)
                ->first();

            if (!$nghiPhep) {
                resError(['uuid_nghi_phep' => 'Đơn nghỉ phép không tồn tại'], 'Không tìm thấy', REST_Controller::HTTP_NOT_FOUND);
            }

            $idNghiPhep = $nghiPhep['id_nghi_phep'];

            // Lấy chi tiết nghỉ phép với thông tin đầy đủ
            $chiTietNghiPhep = $this->db
                ->select('
                    np.*, 
                    DATE_FORMAT(np.created_at, "%d/%m/%Y %H:%i:%s") as created_at,
                    nv.ho_va_ten, 
                    nv.ma_nhan_vien,
                    nv.email as email_nhan_vien,
                    nv.id_don_vi_cong_tac,
                    dv.ten_don_vi,
                    lnp.ten_loai_phep
                ')
                ->from('hrm_nghi_phep np')
                ->join('hrm_nhan_vien nv', 'nv.id_nhan_vien = np.id_nhan_vien', 'left')
                ->join('e_don_vi dv', 'dv.id_don_vi = nv.id_don_vi_cong_tac', 'left')
                ->join('hrm_danh_muc_loai_nghi_phep lnp', 'lnp.id_loai_phep = np.id_loai_phep', 'left')
                ->where('np.id_nghi_phep', $idNghiPhep)
                ->get()
                ->row_array();

            // Lấy chi tiết ngày nghỉ
            $chiTietNgayNghi = $this->db
                ->select('ngay_nghi, buoi_nghi, so_ngay_nghi')
                ->from('hrm_nghi_phep_chi_tiet')
                ->where('id_nghi_phep', $idNghiPhep)
                ->order_by('ngay_nghi', 'ASC')
                ->get()
                ->result_array();

            // Gộp buổi sáng/chiều cùng ngày thành "Cả ngày"
            $chiTietNgayNghiMerged = [];
            foreach ($chiTietNgayNghi as $item) {
                $ngay = $item['ngay_nghi'];
                if (!isset($chiTietNgayNghiMerged[$ngay])) {
                    $chiTietNgayNghiMerged[$ngay] = [
                        'ngay_nghi' => $ngay,
                        'buoi' => [],
                        'so_ngay_nghi' => 0
                    ];
                }
                $chiTietNgayNghiMerged[$ngay]['buoi'][] = $item['buoi_nghi'];
                $chiTietNgayNghiMerged[$ngay]['so_ngay_nghi'] += $item['so_ngay_nghi'];
            }

            $chiTietNgayNghiFormatted = [];
            foreach ($chiTietNgayNghiMerged as $ngay => $info) {
                $buoiNghi = (count($info['buoi']) == 2) ? 'Ca_ngay' : $info['buoi'][0];
                $chiTietNgayNghiFormatted[] = [
                    'ngay_nghi' => $ngay,
                    'buoi_nghi' => $buoiNghi,
                    'so_ngay_nghi' => $info['so_ngay_nghi']
                ];
            }

            // Tính tổng số ngày nghỉ
            $tongSoNgayNghi = array_sum(array_column($chiTietNgayNghiFormatted, 'so_ngay_nghi'));

            // Lấy danh sách người duyệt
            $danhSachNguoiDuyet = $this->db
                ->select('nd.ql_nguoi_dung_ho_ten as ho_ten, npd.cap_duyet, npd.da_duyet, npd.ly_do, npd.thoi_gian_duyet')
                ->from('hrm_nghi_phep_nguoi_duyet npd')
                ->join('ql_nguoi_dung nd', 'nd.ql_nguoi_dung_id = npd.id_nguoi_duyet', 'left')
                ->where('npd.id_nghi_phep', $idNghiPhep)
                ->order_by('npd.cap_duyet', 'ASC')
                ->get()
                ->result_array();

            // Lấy danh sách email lãnh đạo đơn vị
            $emailLanhDao = $this->db
                ->select('nv.ho_va_ten, nv.email')
                ->from('e_lanh_dao_don_vi ldv')
                ->join('ql_nguoi_dung nd', 'nd.ql_nguoi_dung_id = ldv.ql_nguoi_dung_id', 'left')
                ->join('hrm_nhan_vien nv', 'nv.ql_nguoi_dung_id = nd.ql_nguoi_dung_id', 'left')
                ->where('ldv.id_don_vi', $chiTietNghiPhep['id_don_vi_cong_tac'])
                ->where('ldv.deleted_at IS NULL')
                ->where('nv.email IS NOT NULL')
                ->where('nv.email !=', '')
                ->get()
                ->result_array();

            if (empty($emailLanhDao)) {
                resError([], 'Không tìm thấy email lãnh đạo đơn vị', REST_Controller::HTTP_BAD_REQUEST);
            }

            // Link duyệt đơn
            $linkDuyet = $this->linkDuyet . $uuidNghiPhep;

            // Chuẩn bị dữ liệu email
            $emailData = [
                'ho_va_ten' => $chiTietNghiPhep['ho_va_ten'],
                'ma_nhan_vien' => $chiTietNghiPhep['ma_nhan_vien'],
                'ten_don_vi' => $chiTietNghiPhep['ten_don_vi'],
                'ten_loai_phep' => $chiTietNghiPhep['ten_loai_phep'],
                'loai_nghi' => $chiTietNghiPhep['loai_nghi'],
                'so_ngay_nghi' => $tongSoNgayNghi,
                'ly_do_nghi' => $chiTietNghiPhep['ly_do_nghi'],
                'uuid_nghi_phep' => $uuidNghiPhep,
                'trang_thai_cap_mot' => $chiTietNghiPhep['trang_thai_cap_mot'],
                'chi_tiet_ngay_nghi' => $chiTietNgayNghiFormatted,
                'danh_sach_nguoi_duyet' => $danhSachNguoiDuyet,
                'link_duyet' => $linkDuyet,
                'file_dinh_kem' => !empty($chiTietNghiPhep['minh_chung']) ? [$chiTietNghiPhep['minh_chung']] : [],
                'created_at' => $chiTietNghiPhep['created_at']
            ];

            // Kiểm tra domain trước khi gửi email
            $currentDomain = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : (isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER'], PHP_URL_SCHEME) . '://' . parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) : '');
            $isProduction = ($currentDomain === 'https://myoffice.nctu.edu.vn');

            if (!$isProduction) {
                log_message('info', 'Email không được gửi (domain test) trong send_approval_email_post: ' . $currentDomain);
                resSuccess([
                    'ket_qua' => [],
                    'thanh_cong' => 0,
                    'that_bai' => 0,
                    'tong_so' => 0,
                    'test_mode' => true,
                    'message' => 'Môi trường test - Email không được gửi'
                ], "Email chỉ được gửi trên môi trường production (https://myoffice.nctu.edu.vn)", REST_Controller::HTTP_OK);
                return;
            }

            // Gửi email cho từng lãnh đạo
            $ketQua = [];
            $thanhCong = 0;
            $thatBai = 0;

            // Gửi email cho nhân viên đã tạo đơn (sử dụng template cá nhân)
            if (!empty($chiTietNghiPhep['email_nhan_vien'])) {
                $emailData['email'] = $chiTietNghiPhep['email_nhan_vien'];
                $result = $this->sendApprovalEmail($emailData, true);

                if ($result) {
                    $thanhCong++;
                    $ketQua[] = [
                        'email' => $chiTietNghiPhep['email_nhan_vien'],
                        'ho_ten' => $chiTietNghiPhep['ho_va_ten'],
                        'success' => true
                    ];
                } else {
                    $thatBai++;
                    $ketQua[] = [
                        'email' => $chiTietNghiPhep['email_nhan_vien'],
                        'ho_ten' => $chiTietNghiPhep['ho_va_ten'],
                        'success' => false
                    ];
                }
            }

            // Gửi email cho từng lãnh đạo (sử dụng template lãnh đạo)
            foreach ($emailLanhDao as $lanhDao) {
                if (!empty($lanhDao['email'])) {
                    $emailData['email'] = $lanhDao['email'];

                    $result = $this->sendApprovalEmail($emailData, false);

                    if ($result) {
                        $thanhCong++;
                        $ketQua[] = [
                            'email' => $lanhDao['email'],
                            'ho_ten' => $lanhDao['ho_va_ten'],
                            'success' => true
                        ];
                    } else {
                        $thatBai++;
                        $ketQua[] = [
                            'email' => $lanhDao['email'],
                            'ho_ten' => $lanhDao['ho_va_ten'],
                            'success' => false
                        ];
                    }
                }
            }

            resSuccess([
                'ket_qua' => $ketQua,
                'thanh_cong' => $thanhCong,
                'that_bai' => $thatBai,
                'tong_so' => count($emailLanhDao) + (!empty($chiTietNghiPhep['email_nhan_vien']) ? 1 : 0)
            ], "Đã gửi email: {$thanhCong} thành công, {$thatBai} thất bại", REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            log_message('error', 'Lỗi gửi email nghỉ phép: ' . $e->getMessage());
            resError(['message' => $e->getMessage()], 'Có lỗi xảy ra khi gửi email', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deletes_post()
    {
        $auth = $this->getUserLogin();
        $idNguoiXoa = $auth['ql_nguoi_dung_id'];

        $uuidsNghiPhep = commonRequest('uuids_nghi_phep'); // Mảng UUID đơn nghỉ phép

        // Validate
        if (empty($uuidsNghiPhep) || !is_array($uuidsNghiPhep)) {
            resError(['uuids_nghi_phep' => 'Danh sách UUID nghỉ phép không được để trống'], 'Dữ liệu không hợp lệ', REST_Controller::HTTP_BAD_REQUEST);
        }
        $this->db->trans_start();

        try {
            $ketQuaXoa = [];

            foreach ($uuidsNghiPhep as $uuid) {
                // Lấy thông tin đơn nghỉ phép
                $nghiPhep = $this->Hrm_nghi_phep_model
                    ->where('uuid_nghi_phep', $uuid)
                    ->first();

                if (!$nghiPhep) {
                    $this->db->trans_rollback();
                    resError(['uuid_nghi_phep' => $uuid], 'Đơn nghỉ phép không tồn tại', REST_Controller::HTTP_NOT_FOUND);
                }

                $idNghiPhep = $nghiPhep['id_nghi_phep'];

                // Kiểm tra quyền xóa (chỉ được xóa nếu chưa duyệt cả 2 cấp)
                if ($nghiPhep['trang_thai_cap_mot'] != 'Cho_duyet' || $nghiPhep['trang_thai_cap_hai'] != 'Cho_duyet') {
                    $this->db->trans_rollback();
                    resError('Chỉ có thể xóa đơn chưa được duyệt', REST_Controller::HTTP_BAD_REQUEST, [], false);
                }

                // Xóa mềm đơn nghỉ phép
                $this->db->where('id_nghi_phep', $idNghiPhep)
                    ->update('hrm_nghi_phep', [
                        'deleted_at' => date('Y-m-d H:i:s'),
                        'deleted_user_id' => $idNguoiXoa
                    ]);

                $ketQuaXoa[] = [
                    'uuid_nghi_phep' => $uuid,
                    'success' => true,
                    'message' => 'Xóa đơn nghỉ phép thành công'
                ];
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                resError('Xóa đơn nghỉ phép thất bại', REST_Controller::HTTP_BAD_REQUEST, [], false);
            }

            // Log xóa đơn
            $this->createLog(
                'delete',
                'Xóa đơn nghỉ phép',
                ['uuids' => $uuidsNghiPhep],
                $ketQuaXoa,
                'hrm_nghi_phep'
            );

            resSuccess($ketQuaXoa, 'Xóa đơn nghỉ phép thành công', REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Lỗi xóa đơn nghỉ phép: ' . $e->getMessage());
            resError(['message' => $e->getMessage()], 'Có lỗi xảy ra khi xóa đơn nghỉ phép', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function export_by_unit_get()
    {
        $auth = $this->getUserLogin();
        $qlNguoiDungId = $auth['ql_nguoi_dung_id'];

        // Lấy tham số đầu vào
        $idsDonVi = commonRequest('ids_don_vi');
        $year = commonRequest('year') ? commonRequest('year') : date('Y');
        $month = commonRequest('month') ? commonRequest('month') : null;

        // Xử lý ids_don_vi: chuyển thành array
        $exportAllUnits = false;

        if (empty($idsDonVi)) {
            // Nếu không truyền ids_don_vi thì export tất cả đơn vị
            $exportAllUnits = true;
            $idsDonVi = [];
        } elseif (!is_array($idsDonVi)) {
            // Nếu là string, tách bằng dấu phẩy
            $idsDonVi = explode(',', $idsDonVi);
            // Trim và loại bỏ giá trị rỗng
            $idsDonVi = array_filter(array_map('trim', $idsDonVi));

            // Nếu sau khi xử lý mà rỗng thì export tất cả
            if (empty($idsDonVi)) {
                $exportAllUnits = true;
                $idsDonVi = [];
            }
        }

        // Lấy thông tin nhân viên của user hiện tại
        $nhanVien = $this->Hrm_nhan_vien_model
            ->select('hrm_nhan_vien.id_nhan_vien, hrm_nhan_vien.id_don_vi_cong_tac, e_don_vi.ma_don_vi, e_don_vi.ten_don_vi')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_nhan_vien.id_don_vi_cong_tac', 'left')
            ->where('hrm_nhan_vien.ql_nguoi_dung_id', $qlNguoiDungId)
            ->first();

        $idDonViCongTac = $nhanVien ? $nhanVien['id_don_vi_cong_tac'] : null;

        // Kiểm tra xem có phải lãnh đạo đơn vị không
        $isLanhDao = $this->db
            ->from('e_lanh_dao_don_vi ldv')
            ->where('ldv.id_don_vi', $idDonViCongTac)
            ->where('ldv.ql_nguoi_dung_id', $qlNguoiDungId)
            ->where('ldv.deleted_at IS NULL')
            ->count_all_results() > 0;

        if (!$isLanhDao && (int) $auth['ql_nguoi_dung_is_admin'] !== 1) {
            resError('Chỉ có lãnh đạo đơn vị mới được phép xuất báo cáo', REST_Controller::HTTP_FORBIDDEN);
        }

        // Nếu export tất cả đơn vị, lấy danh sách tất cả đơn vị
        if ($exportAllUnits) {
            $allUnits = $this->db
                ->select('id_don_vi')
                ->from('e_don_vi')
                ->get()
                ->result_array();

            $idsDonVi = array_column($allUnits, 'id_don_vi');
        }

        // Lấy tham số lọc bổ sung
        $idLoaiPhep = commonRequest('id_loai_phep');
        $coTinhLuong = commonRequest('co_tinh_luong'); // 0: không tính lương, 1: có tính lương

        // Lấy danh sách nghỉ phép của các đơn vị
        $this->db->select('
            np.uuid_nghi_phep,
            np.id_nghi_phep,
            np.ly_do_nghi,
            np.loai_nghi,
            np.trang_thai_cap_mot,
            np.trang_thai_cap_hai,
            DATE_FORMAT(np.created_at, "%d/%m/%Y %H:%i:%s") as ngay_tao,
            nv.id_nhan_vien,
            nv.ma_nhan_vien,
            nv.ho_va_ten,
            dv.id_don_vi,
            dv.ten_don_vi,
            lnp.ten_loai_phep,
            lnp.co_tinh_luong
        ')
            ->from('hrm_nghi_phep np')
            ->join('hrm_nhan_vien nv', 'nv.id_nhan_vien = np.id_nhan_vien', 'left')
            ->join('e_don_vi dv', 'dv.id_don_vi = nv.id_don_vi_cong_tac', 'left')
            ->join('hrm_danh_muc_loai_nghi_phep lnp', 'lnp.id_loai_phep = np.id_loai_phep', 'left')
            ->join('hrm_nghi_phep_chi_tiet ct', 'ct.id_nghi_phep = np.id_nghi_phep', 'inner')
            ->where_in('nv.id_don_vi_cong_tac', $idsDonVi)
            ->where('np.deleted_at IS NULL')
            ->where('YEAR(ct.ngay_nghi)', $year);

        // Lọc theo tháng nếu có
        if ($month) {
            $this->db->where('MONTH(ct.ngay_nghi)', $month);
        }

        // Lọc theo loại phép nếu có
        if ($idLoaiPhep) {
            $this->db->where('np.id_loai_phep', $idLoaiPhep);
        }

        // Lọc theo tính lương nếu có
        if ($coTinhLuong !== null && $coTinhLuong !== '') {
            $this->db->where('lnp.co_tinh_luong', $coTinhLuong);
        }

        $this->db->group_by('np.id_nghi_phep');
        $this->db->order_by('dv.ten_don_vi', 'ASC');
        $this->db->order_by('nv.ma_nhan_vien', 'ASC');
        $this->db->order_by('MIN(ct.ngay_nghi)', 'ASC');

        $danhSachNghiPhep = $this->db->get()->result_array();

        // Lấy chi tiết ngày nghỉ cho từng đơn và nhóm theo đơn vị + tháng
        $danhSachTheoThang = [];

        foreach ($danhSachNghiPhep as &$item) {
            $chiTietNgayNghi = $this->db
                ->select('ngay_nghi, buoi_nghi, so_ngay_nghi')
                ->from('hrm_nghi_phep_chi_tiet')
                ->join('hrm_nghi_phep', 'hrm_nghi_phep.id_nghi_phep = hrm_nghi_phep_chi_tiet.id_nghi_phep')
                ->where('hrm_nghi_phep.uuid_nghi_phep', $item['uuid_nghi_phep'])
                ->order_by('ngay_nghi', 'ASC')
                ->get()
                ->result_array();

            // Tính tổng số ngày nghỉ và nhóm theo ngày
            $tongSoNgayNghi = 0;
            $ngayNghiGroup = []; // Nhóm theo ngày

            foreach ($chiTietNgayNghi as $ngay) {
                $tongSoNgayNghi += $ngay['so_ngay_nghi'];
                $ngayKey = $ngay['ngay_nghi'];

                if (!isset($ngayNghiGroup[$ngayKey])) {
                    $ngayNghiGroup[$ngayKey] = [];
                }

                $ngayNghiGroup[$ngayKey][] = $ngay['buoi_nghi'];
            }

            // Format danh sách ngày nghỉ
            $ngayNghiArray = [];
            foreach ($ngayNghiGroup as $ngay => $buois) {
                $ngayFormatted = date('d/m/Y', strtotime($ngay));

                if (count($buois) > 1) {
                    // Nếu nghỉ cả 2 buổi trong 1 ngày → chỉ hiện ngày
                    $ngayNghiArray[] = $ngayFormatted;
                } else {
                    // Chỉ nghỉ 1 buổi → hiện ngày + buổi
                    $buoi = $buois[0] == 'Sang' ? 'Sáng' : ($buois[0] == 'Chieu' ? 'Chiều' : $buois[0]);
                    $ngayNghiArray[] = $ngayFormatted . ' (' . $buoi . ')';
                }
            }

            $item['tong_so_ngay_nghi'] = $tongSoNgayNghi;
            $item['danh_sach_ngay_nghi'] = implode(', ', $ngayNghiArray);

            // Nhóm theo đơn vị và tháng
            $thangNghi = date('n', strtotime($chiTietNgayNghi[0]['ngay_nghi'])); // Lấy tháng từ ngày nghỉ đầu tiên
            $idDonVi = $item['id_don_vi'];

            if (!isset($danhSachTheoThang[$idDonVi])) {
                $danhSachTheoThang[$idDonVi] = [
                    'ten_don_vi' => $item['ten_don_vi'],
                    'thang' => []
                ];
            }

            if (!isset($danhSachTheoThang[$idDonVi]['thang'][$thangNghi])) {
                $danhSachTheoThang[$idDonVi]['thang'][$thangNghi] = [
                    'danh_sach' => [],
                    'tong_nguoi' => 0,
                    'nhan_vien_unique' => []
                ];
            }

            $danhSachTheoThang[$idDonVi]['thang'][$thangNghi]['danh_sach'][] = $item;

            // Đếm số người nghỉ (unique)
            if (!in_array($item['id_nhan_vien'], $danhSachTheoThang[$idDonVi]['thang'][$thangNghi]['nhan_vien_unique'])) {
                $danhSachTheoThang[$idDonVi]['thang'][$thangNghi]['nhan_vien_unique'][] = $item['id_nhan_vien'];
                $danhSachTheoThang[$idDonVi]['thang'][$thangNghi]['tong_nguoi']++;
            }
        }

        // Lấy thông tin tất cả đơn vị được chọn từ database
        $allUnitsInfo = $this->db
            ->select('id_don_vi, ten_don_vi, ten_viet_tat')
            ->from('e_don_vi')
            ->where_in('id_don_vi', $idsDonVi)
            ->get()
            ->result_array();

        // Đảm bảo tất cả đơn vị đều có entry trong danhSachTheoThang
        foreach ($allUnitsInfo as $unit) {
            $idDonVi = $unit['id_don_vi'];
            if (!isset($danhSachTheoThang[$idDonVi])) {
                $danhSachTheoThang[$idDonVi] = [
                    'ten_don_vi' => $unit['ten_don_vi'],
                    'ten_viet_tat' => $unit['ten_viet_tat'],
                    'thang' => []
                ];
            }
        }

        // Export Excel
        $objPHPExcel = new PHPExcel();
        $sheetIndex = 0;

        // Tạo sheet cho từng đơn vị
        foreach ($danhSachTheoThang as $idDonVi => $donVi) {
            if ($sheetIndex > 0) {
                $sheet = $objPHPExcel->createSheet();
            } else {
                $sheet = $objPHPExcel->setActiveSheetIndex(0);
            }

            // Sử dụng tên viết tắt nếu có, nếu không thì dùng tên đầy đủ
            $tenSheet = !empty($donVi['ten_viet_tat']) ? $donVi['ten_viet_tat'] : $donVi['ten_don_vi'];
            $tenSheet = mb_strtoupper($tenSheet, 'UTF-8');
            $tenSheet = mb_substr($tenSheet, 0, 31, 'UTF-8'); // Tên sheet tối đa 31 ký tự
            $sheet->setTitle($tenSheet);

            $tenDonVi = mb_strtoupper($donVi['ten_don_vi'], 'UTF-8'); // Giữ biến này để dùng trong header

            // Set default font cho toàn bộ sheet
            $sheet->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(12);

            // Thêm logo vào góc trái
            // $logoPath = 'assets/images/LOGO TA 3.png'; // Đường dẫn đến logo
            // if (file_exists($logoPath)) {
            //     $drawing = new PHPExcel_Worksheet_Drawing();
            //     $drawing->setName('Logo');
            //     $drawing->setDescription('Logo Trường');
            //     $drawing->setPath($logoPath);
            //     $drawing->setCoordinates('A1');
            //     $drawing->setHeight(60); // Chiều cao logo 60px
            //     $drawing->setOffsetX(10); // Offset X 10px
            //     $drawing->setOffsetY(5); // Offset Y 5px
            //     $drawing->setWorksheet($sheet);
            // }

            // Header
            $sheet->setCellValue('A1', 'TRƯỜNG ĐẠI HỌC NAM CẦN THƠ');
            $sheet->mergeCells('A1:I1');
            $sheet->getStyle('A1')->applyFromArray([
                'font' => ['name' => 'Times New Roman', 'bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER]
            ]);

            $titleText = 'DANH SÁCH NGHỈ PHÉP ' . ($month ? 'THÁNG ' . $month . '/' : 'NĂM ') . $year;
            $sheet->setCellValue('A2', $titleText);
            $sheet->mergeCells('A2:I2');
            $sheet->getStyle('A2')->applyFromArray([
                'font' => ['name' => 'Times New Roman', 'bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER]
            ]);

            $sheet->setCellValue('A3', strtoupper($tenDonVi));
            $sheet->mergeCells('A3:I3');
            $sheet->getStyle('A3')->applyFromArray([
                'font' => ['name' => 'Times New Roman', 'bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER]
            ]);

            $row = 5;

            // Tính tổng số ngày nghỉ theo nhân viên trong đơn vị này
            $tongNgayTheoNV = [];
            foreach ($donVi['thang'] as $thang => $duLieuThang) {
                foreach ($duLieuThang['danh_sach'] as $item) {
                    $idNV = $item['id_nhan_vien'];
                    if (!isset($tongNgayTheoNV[$idNV])) {
                        $tongNgayTheoNV[$idNV] = [
                            'ma_nhan_vien' => $item['ma_nhan_vien'],
                            'ho_va_ten' => $item['ho_va_ten'],
                            'tong_ngay' => 0,
                            'tong_ngay_co_luong' => 0,
                            'tong_ngay_khong_luong' => 0
                        ];
                    }
                    $tongNgayTheoNV[$idNV]['tong_ngay'] += $item['tong_so_ngay_nghi'];

                    // Phân loại theo có tính lương hay không
                    if ($item['co_tinh_luong'] == 1) {
                        $tongNgayTheoNV[$idNV]['tong_ngay_co_luong'] += $item['tong_so_ngay_nghi'];
                    } else {
                        $tongNgayTheoNV[$idNV]['tong_ngay_khong_luong'] += $item['tong_so_ngay_nghi'];
                    }
                }
            }

            // Kiểm tra nếu đơn vị không có dữ liệu
            if (empty($donVi['thang'])) {
                // Header bảng chi tiết
                $headers = [
                    'A' => 'STT',
                    'B' => 'Mã NV',
                    'C' => 'Họ và tên',
                    'D' => 'Loại phép',
                    'E' => 'Ngày nghỉ',
                    'F' => 'Số ngày',
                    'G' => 'Lý do',
                    'H' => 'TT Cấp đơn vị',
                    'I' => 'TT Cấp TC-HC'
                ];

                foreach ($headers as $col => $header) {
                    $sheet->setCellValue($col . $row, $header);
                    $sheet->getStyle($col . $row)->applyFromArray([
                        'font' => ['name' => 'Times New Roman', 'bold' => true, 'size' => 12],
                        'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
                        'fill' => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => 'D9E1F2']],
                        'alignment' => [
                            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
                        ]
                    ]);
                }
                $row++;

                // Hiển thị thông báo không có dữ liệu
                $sheet->setCellValue('A' . $row, 'Không có dữ liệu');
                $sheet->mergeCells('A' . $row . ':I' . $row);
                $sheet->getStyle('A' . $row)->applyFromArray([
                    'font' => ['name' => 'Times New Roman', 'italic' => true, 'size' => 12, 'color' => ['rgb' => '999999']],
                    'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER]
                ]);
                $row++;
            }

            // Duyệt qua từng tháng của đơn vị
            ksort($donVi['thang']); // Sắp xếp theo tháng tăng dần

            foreach ($donVi['thang'] as $thang => $duLieuThang) {
                // Tiêu đề tháng
                $sheet->setCellValue('A' . $row, 'THÁNG ' . $thang . '/' . $year);
                $sheet->mergeCells('A' . $row . ':I' . $row);
                $sheet->getStyle('A' . $row)->applyFromArray([
                    'font' => ['name' => 'Times New Roman', 'bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => '4472C4']],
                    'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER]
                ]);
                $row++;

                // Thống kê tháng
                $sheet->setCellValue('A' . $row, 'Số người nghỉ: ' . $duLieuThang['tong_nguoi'] . ' người');
                $sheet->mergeCells('A' . $row . ':I' . $row);
                $sheet->getStyle('A' . $row)->applyFromArray([
                    'font' => ['name' => 'Times New Roman', 'bold' => true, 'italic' => true, 'size' => 12],
                    'fill' => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => 'E7E6E6']]
                ]);
                $row++;

                // Header bảng chi tiết
                $headers = [
                    'A' => 'STT',
                    'B' => 'Mã NV',
                    'C' => 'Họ và tên',
                    'D' => 'Loại phép',
                    'E' => 'Ngày nghỉ',
                    'F' => 'Số ngày',
                    'G' => 'Lý do',
                    'H' => 'TT Cấp đơn vị',
                    'I' => 'TT Cấp TC-HC'
                ];

                foreach ($headers as $col => $header) {
                    $sheet->setCellValue($col . $row, $header);
                    $sheet->getStyle($col . $row)->applyFromArray([
                        'font' => ['name' => 'Times New Roman', 'bold' => true, 'size' => 12],
                        'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
                        'fill' => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => 'D9E1F2']],
                        'alignment' => [
                            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
                        ]
                    ]);
                }
                $row++;

                // Dữ liệu chi tiết
                $stt = 1;
                foreach ($duLieuThang['danh_sach'] as $item) {
                    $sheet->setCellValue('A' . $row, $stt);
                    $sheet->setCellValue('B' . $row, $item['ma_nhan_vien']);
                    $sheet->setCellValue('C' . $row, $item['ho_va_ten']);
                    $sheet->setCellValue('D' . $row, $item['ten_loai_phep']);
                    $sheet->setCellValue('E' . $row, $item['danh_sach_ngay_nghi']);
                    $sheet->setCellValue('F' . $row, $item['tong_so_ngay_nghi']);
                    $sheet->setCellValue('G' . $row, $item['ly_do_nghi']);

                    // Trạng thái cấp 1: chỉ hiển thị nếu khác Cho_duyet
                    $ttCap1 = '';
                    if ($item['trang_thai_cap_mot'] == 'Da_duyet') {
                        $ttCap1 = 'Đã duyệt';
                    } elseif ($item['trang_thai_cap_mot'] == 'Tu_choi') {
                        $ttCap1 = 'Từ chối';
                    }
                    $sheet->setCellValue('H' . $row, $ttCap1);

                    // Trạng thái cấp 2: chỉ hiển thị nếu khác Cho_duyet
                    $ttCap2 = '';
                    if ($item['trang_thai_cap_hai'] == 'Da_duyet') {
                        $ttCap2 = 'Đã duyệt';
                    } elseif ($item['trang_thai_cap_hai'] == 'Tu_choi') {
                        $ttCap2 = 'Từ chối';
                    }
                    $sheet->setCellValue('I' . $row, $ttCap2);

                    $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                        'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]]
                    ]);

                    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('I' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('E' . $row)->getAlignment()->setWrapText(true);
                    $sheet->getStyle('G' . $row)->getAlignment()->setWrapText(true);

                    $row++;
                    $stt++;
                }

                $row++; // Dòng trống giữa các tháng
            }

            // Thêm bảng tổng hợp số ngày nghỉ theo nhân viên bên phải
            $colStart = 'K'; // Cột bắt đầu bảng tổng hợp
            $rowStart = 5;

            // Header bảng tổng hợp
            $sheet->setCellValue($colStart . $rowStart, 'TỔNG HỢP THEO NHÂN VIÊN');
            $sheet->mergeCells($colStart . $rowStart . ':P' . $rowStart);
            $sheet->getStyle($colStart . $rowStart)->applyFromArray([
                'font' => ['name' => 'Times New Roman', 'bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => '70AD47']],
                'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER]
            ]);
            $rowStart++;

            // Header bảng
            $headersTongHop = [
                'K' => 'STT',
                'L' => 'Mã NV',
                'M' => 'Họ và tên',
                'N' => 'Tổng số ngày',
                'O' => 'Hưởng lương',
                'P' => 'Không hưởng lương'
            ];

            foreach ($headersTongHop as $col => $header) {
                $sheet->setCellValue($col . $rowStart, $header);
                $sheet->getStyle($col . $rowStart)->applyFromArray([
                    'font' => ['name' => 'Times New Roman', 'bold' => true, 'size' => 12],
                    'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
                    'fill' => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => 'D9E1F2']],
                    'alignment' => [
                        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                        'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
                    ]
                ]);
            }
            $rowStart++;

            // Dữ liệu tổng hợp
            $sttTongHop = 1;
            if (empty($tongNgayTheoNV)) {
                // Hiển thị thông báo không có dữ liệu cho bảng tổng hợp
                $sheet->setCellValue('K' . $rowStart, 'Không có dữ liệu');
                $sheet->mergeCells('K' . $rowStart . ':P' . $rowStart);
                $sheet->getStyle('K' . $rowStart)->applyFromArray([
                    'font' => ['name' => 'Times New Roman', 'italic' => true, 'size' => 12, 'color' => ['rgb' => '999999']],
                    'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER]
                ]);
                $rowStart++;
            } else {
                foreach ($tongNgayTheoNV as $nvData) {
                    $sheet->setCellValue('K' . $rowStart, $sttTongHop);
                    $sheet->setCellValue('L' . $rowStart, $nvData['ma_nhan_vien']);
                    $sheet->setCellValue('M' . $rowStart, $nvData['ho_va_ten']);
                    $sheet->setCellValue('N' . $rowStart, $nvData['tong_ngay']);
                    $sheet->setCellValue('O' . $rowStart, $nvData['tong_ngay_co_luong']);
                    $sheet->setCellValue('P' . $rowStart, $nvData['tong_ngay_khong_luong']);

                    $sheet->getStyle('K' . $rowStart . ':P' . $rowStart)->applyFromArray([
                        'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]]
                    ]);

                    $sheet->getStyle('K' . $rowStart)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('N' . $rowStart)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('O' . $rowStart)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('P' . $rowStart)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

                    $rowStart++;
                    $sttTongHop++;
                }
            }

            // Auto size columns cho bảng tổng hợp
            $sheet->getColumnDimension('K')->setWidth(6);
            $sheet->getColumnDimension('L')->setWidth(12);
            $sheet->getColumnDimension('M')->setWidth(25);
            $sheet->getColumnDimension('N')->setAutoSize(true);
            $sheet->getColumnDimension('O')->setAutoSize(true);
            $sheet->getColumnDimension('P')->setAutoSize(true);

            // Auto size columns bảng chính
            $sheet->getColumnDimension('A')->setWidth(6);
            $sheet->getColumnDimension('B')->setWidth(12);
            $sheet->getColumnDimension('C')->setWidth(25);
            $sheet->getColumnDimension('D')->setWidth(20);
            $sheet->getColumnDimension('E')->setWidth(35);
            $sheet->getColumnDimension('F')->setWidth(10);
            $sheet->getColumnDimension('G')->setWidth(35);
            $sheet->getColumnDimension('H')->setWidth(16);
            $sheet->getColumnDimension('I')->setWidth(16);

            $sheetIndex++;
        }

        // Set active sheet về sheet đầu tiên
        $objPHPExcel->setActiveSheetIndex(0);

        // File name
        if (count($idsDonVi) > 1) {
            $tenFileDonVi = 'Nhieu_don_vi';
        } else {
            // Lấy tên đơn vị từ danhSachTheoThang
            $firstUnitId = reset($idsDonVi);
            if (isset($danhSachTheoThang[$firstUnitId])) {
                $tenFileDonVi = str_replace(' ', '_', $danhSachTheoThang[$firstUnitId]['ten_don_vi']);
            } else {
                // Fallback: lấy từ database nếu không có trong danhSachTheoThang
                $donVi = $this->db
                    ->select('ten_don_vi')
                    ->from('e_don_vi')
                    ->where('id_don_vi', $firstUnitId)
                    ->get()
                    ->row_array();
                $tenFileDonVi = $donVi ? str_replace(' ', '_', $donVi['ten_don_vi']) : 'Don_vi';
            }
        }
        $tenFilePeriod = $month ? 'Thang_' . $month . '_' . $year : 'Nam_' . $year;
        $fileName = 'Bao_cao_nghi_phep_' . $tenFileDonVi . '_' . $tenFilePeriod . '_' . date('YmdHis') . '.xlsx';

        // Create directory if not exists
        $directory = 'uploads/export/' . date('Y') . '/';
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filePath = $directory . $fileName;

        // Save file
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);

        if (file_exists($filePath)) {
            $this->response([
                'status' => REST_Controller::HTTP_OK,
                'message' => 'Export thành công',
                'success' => true,
                'data' => base_url($filePath)
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => REST_Controller::HTTP_NOT_FOUND,
                'message' => 'Không thể tạo file',
                'success' => false,
                'data' => null
            ], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function export_by_employee_get()
    {
        try {
            $auth = $this->getUserLogin();
            $qlNguoiDungId = $auth['ql_nguoi_dung_id'];

            // Lấy năm từ request, mặc định năm hiện tại
            $year = commonRequest('year') ? commonRequest('year') : date('Y');

            // Lấy thông tin nhân viên
            $nhanVien = $this->db
                ->select('
                nv.id_nhan_vien,
                nv.ma_nhan_vien,
                nv.ho_va_ten,
                nv.so_dien_thoai,
                cv.ten_cong_viec as ten_chuc_vu,
                dv.ten_don_vi
            ')
                ->from('hrm_nhan_vien nv')
                ->join('hrm_vi_tri_cong_viec cv', 'cv.id_vi_tri_cong_viec = nv.id_vi_tri_cong_viec', 'left')
                ->join('e_don_vi dv', 'dv.id_don_vi = nv.id_don_vi_cong_tac', 'left')
                ->where('nv.ql_nguoi_dung_id', $qlNguoiDungId)
                ->where('nv.deleted_at IS NULL')
                ->get()
                ->row_array();

            if (!$nhanVien) {
                resError(['message' => 'Không tìm thấy thông tin nhân viên'], 'Không tìm thấy', REST_Controller::HTTP_NOT_FOUND);
            }

            $idNhanVien = $nhanVien['id_nhan_vien'];

            // Lấy danh sách đơn nghỉ phép của nhân viên trong năm
            $danhSachNghiPhep = $this->db
                ->select('
                np.uuid_nghi_phep,
                np.ly_do_nghi,
                np.loai_nghi,
                np.trang_thai_cap_mot,
                np.trang_thai_cap_hai,
                DATE_FORMAT(np.created_at, "%d/%m/%Y") as ngay_tao,
                lnp.ten_loai_phep,
                SUM(ct.so_ngay_nghi) as tong_so_ngay_nghi,
                MIN(ct.ngay_nghi) as ngay_bat_dau_nghi,
                nd1.ql_nguoi_dung_ho_ten AS nguoi_duyet_cap_mot_ho_ten, 
                nd2.ql_nguoi_dung_ho_ten AS nguoi_duyet_cap_hai_ho_ten
            ')
                ->from('hrm_nghi_phep np')
                ->join('hrm_danh_muc_loai_nghi_phep lnp', 'lnp.id_loai_phep = np.id_loai_phep', 'left')
                ->join('hrm_nghi_phep_chi_tiet ct', 'ct.id_nghi_phep = np.id_nghi_phep', 'left')
                ->join('ql_nguoi_dung nd1', 'nd1.ql_nguoi_dung_id = np.nguoi_duyet_cap_mot_id', 'left')
                ->join('ql_nguoi_dung nd2', 'nd2.ql_nguoi_dung_id = np.nguoi_duyet_cap_hai_id', 'left')
                ->where('np.id_nhan_vien', $idNhanVien)
                ->where('np.deleted_at IS NULL')
                ->where('YEAR(np.created_at)', $year)
                ->where('np.trang_thai_cap_mot IN ("Da_duyet", "Tu_choi")')
                ->group_by('np.id_nghi_phep')
                ->order_by('ngay_bat_dau_nghi', 'ASC')
                ->get()
                ->result_array();

            // dd($this->db->last_query());

            // Lấy chi tiết ngày nghỉ cho từng đơn
            foreach ($danhSachNghiPhep as &$item) {
                $chiTietNgayNghi = $this->db
                    ->select('ngay_nghi, buoi_nghi')
                    ->from('hrm_nghi_phep_chi_tiet')
                    ->join('hrm_nghi_phep', 'hrm_nghi_phep.id_nghi_phep = hrm_nghi_phep_chi_tiet.id_nghi_phep')
                    ->where('hrm_nghi_phep.uuid_nghi_phep', $item['uuid_nghi_phep'])
                    ->order_by('ngay_nghi', 'ASC')
                    ->get()
                    ->result_array();

                // Format thời gian nghỉ
                if (!empty($chiTietNgayNghi)) {
                    // Lấy ngày đầu và ngày cuối (unique)
                    $uniqueDates = array_unique(array_column($chiTietNgayNghi, 'ngay_nghi'));
                    sort($uniqueDates);

                    if (count($uniqueDates) > 1) {
                        // Nhiều ngày khác nhau: 20 - 21/01/2026
                        $ngayDau = date('d', strtotime($uniqueDates[0]));
                        $ngayCuoi = date('d/m/Y', strtotime($uniqueDates[count($uniqueDates) - 1]));
                        $item['thoi_gian'] = $ngayDau . ' - ' . $ngayCuoi;
                    } else {
                        // Chỉ 1 ngày: 20/01/2026
                        $item['thoi_gian'] = date('d/m/Y', strtotime($uniqueDates[0]));
                    }
                } else {
                    $item['thoi_gian'] = '';
                }

                // Xác định trạng thái cấp đơn vị
                if ($item['trang_thai_cap_mot'] == 'Da_duyet') {
                    $item['trang_thai_cap_don_vi'] = 'Đã duyệt';
                } elseif ($item['trang_thai_cap_mot'] == 'Tu_choi') {
                    $item['trang_thai_cap_don_vi'] = 'Từ chối';
                } else {
                    $item['trang_thai_cap_don_vi'] = 'Chờ duyệt';
                }

                // Xác định trạng thái TC-HC
                if ($item['trang_thai_cap_hai'] == 'Da_duyet') {
                    $item['trang_thai_tc_hc'] = 'Đã duyệt';
                } elseif ($item['trang_thai_cap_hai'] == 'Tu_choi') {
                    $item['trang_thai_tc_hc'] = 'Từ chối';
                } elseif ($item['trang_thai_cap_mot'] == 'Da_duyet') {
                    $item['trang_thai_tc_hc'] = 'Chờ duyệt';
                } else {
                    $item['trang_thai_tc_hc'] = '';
                }
            }

            // dd($danhSachNghiPhep);

            // Load template Word
            $templatePath = FCPATH . 'assets/template_nghi_phep.docx';

            if (!file_exists($templatePath)) {
                resError(['message' => 'Không tìm thấy file template'], 'File không tồn tại', REST_Controller::HTTP_NOT_FOUND);
            }

            // Create directory if not exists
            $directory = 'uploads/export/' . date('Y') . '/';
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Save file
            $fileName = 'Don_nghi_phep_' . $nhanVien['ma_nhan_vien'] . '_' . $year . '_' . date('YmdHis') . '.docx';
            $filePath = $directory . $fileName;

            // Copy template to new location
            copy($templatePath, $filePath);

            // Open as ZIP
            $zip = new ZipArchive();
            if ($zip->open($filePath) !== TRUE) {
                resError(['message' => 'Không thể mở file template'], 'Lỗi xử lý file', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Read document.xml
            $documentXml = $zip->getFromName('word/document.xml');
            if ($documentXml === false) {
                $zip->close();
                resError(['message' => 'Không thể đọc nội dung file template'], 'Lỗi xử lý file', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }

            // LÀM SẠCH XML: Nối các placeholder bị chia cắt bởi Word
            $documentXml = preg_replace('/(\$)(<[^>]+>)*(\{)(<[^>]+>)*([a-zA-Z0-9_]+)(<[^>]+>)*(\})/', '$1$3$5$7', $documentXml);
            for ($clean = 0; $clean < 5; $clean++) {
                $documentXml = preg_replace('/\$\{([a-zA-Z0-9_]*)(<[^>]+>)+([a-zA-Z0-9_]*)\}/', '${$1$3}', $documentXml);
            }
            $documentXml = preg_replace('/\$(<[^>]*>)*\{/', '${', $documentXml);
            $documentXml = preg_replace('/([a-zA-Z0-9_]+)(<[^>]*>)*\}/', '$1}', $documentXml);
            $documentXml = preg_replace_callback('/\$\{[^}]*\}/', function ($match) {
                return preg_replace('/<[^>]+>/', '', $match[0]);
            }, $documentXml);



            // Replace placeholders - Thông tin nhân viên
            $documentXml = str_replace('${ho_va_ten}', htmlspecialchars($nhanVien['ho_va_ten'] ?: '', ENT_XML1, 'UTF-8'), $documentXml);
            $documentXml = str_replace('${so_dien_thoai}', htmlspecialchars($nhanVien['so_dien_thoai'] ?: '', ENT_XML1, 'UTF-8'), $documentXml);
            $documentXml = str_replace('${chuc_vu}', htmlspecialchars($nhanVien['ten_chuc_vu'] ?: '', ENT_XML1, 'UTF-8'), $documentXml);
            $documentXml = str_replace('${don_vi}', htmlspecialchars($nhanVien['ten_don_vi'] ?: '', ENT_XML1, 'UTF-8'), $documentXml);
            // Điền dữ liệu vào 12 dòng cố định
            for ($i = 1; $i <= 12; $i++) {
                if (isset($danhSachNghiPhep[$i - 1])) {
                    $item = $danhSachNghiPhep[$i - 1];
                    $documentXml = str_replace('${thoi_gian_' . $i . '}', htmlspecialchars($item['thoi_gian'], ENT_XML1, 'UTF-8'), $documentXml);
                    $documentXml = str_replace('${so_ngay_' . $i . '}', htmlspecialchars($item['tong_so_ngay_nghi'] . ' ngày', ENT_XML1, 'UTF-8'), $documentXml);
                    $documentXml = str_replace('${ly_do_' . $i . '}', htmlspecialchars($item['ly_do_nghi'] ?: '', ENT_XML1, 'UTF-8'), $documentXml);
                    $documentXml = str_replace(
                        '${tt_cap_1_' . $i . '}',
                        htmlspecialchars($item['trang_thai_cap_don_vi'], ENT_XML1, 'UTF-8'),
                        $documentXml
                    );
                    $documentXml = str_replace('${ten_cap_1_' . $i . '}', htmlspecialchars($item['nguoi_duyet_cap_mot_ho_ten'], ENT_XML1, 'UTF-8'), $documentXml);
                    $documentXml = str_replace('${tt_cap_2_' . $i . '}', htmlspecialchars($item['trang_thai_tc_hc'], ENT_XML1, 'UTF-8'), $documentXml);
                    $documentXml = str_replace('${ten_cap_2_' . $i . '}', htmlspecialchars($item['nguoi_duyet_cap_hai_ho_ten'], ENT_XML1, 'UTF-8'), $documentXml);
                } else {
                    $documentXml = str_replace('${thoi_gian_' . $i . '}', '', $documentXml);
                    $documentXml = str_replace('${so_ngay_' . $i . '}', '', $documentXml);
                    $documentXml = str_replace('${ly_do_' . $i . '}', '', $documentXml);
                    $documentXml = str_replace('${tt_cap_1_' . $i . '}', '', $documentXml);
                    $documentXml = str_replace('${ten_cap_1_' . $i . '}', '', $documentXml);
                    $documentXml = str_replace('${tt_cap_2_' . $i . '}', '', $documentXml);
                    $documentXml = str_replace('${ten_cap_2_' . $i . '}', '', $documentXml);
                }
            }

            // Update document.xml in ZIP
            $zip->deleteName('word/document.xml');
            $zip->addFromString('word/document.xml', $documentXml);
            $zip->close();

            if (file_exists($filePath)) {
                $this->load->helper('url');
                $this->response([
                    'status' => REST_Controller::HTTP_OK,
                    'message' => 'Export thành công',
                    'success' => true,
                    'data' => base_url($filePath)
                ], REST_Controller::HTTP_OK);
            } else {
                $this->response([
                    'status' => REST_Controller::HTTP_NOT_FOUND,
                    'message' => 'Không thể tạo file',
                    'success' => false,
                    'data' => null
                ], REST_Controller::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            // Đóng file ZIP nếu đang mở
            if (isset($zip) && $zip instanceof ZipArchive) {
                $zip->close();
            }

            // Xóa file tạm nếu có lỗi
            if (isset($filePath) && file_exists($filePath)) {
                @unlink($filePath);
            }

            log_message('error', 'Lỗi export file Word: ' . $e->getMessage());
            resError([
                'message' => 'Có lỗi xảy ra khi export file',
                'error' => $e->getMessage()
            ], 'Lỗi xử lý', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function upload_minh_chung_post()
    {
        // Lấy thông tin user đang đăng nhập
        $auth = $this->getUserLogin();

        // Nhận dữ liệu từ request
        $idNghiPhep = commonRequest('id_nghi_phep');
        $minhChung = isset($_FILES['file_minh_chung']) ? $_FILES['file_minh_chung'] : null;

        // Validate
        $errors = [];

        if (!$idNghiPhep) {
            $errors['id_nghi_phep'] = 'Vui lòng cung cấp ID nghỉ phép';
        }

        if (!$minhChung || $minhChung['error'] === UPLOAD_ERR_NO_FILE) {
            $errors['file_minh_chung'] = 'Vui lòng chọn file minh chứng';
        }

        if (!empty($errors)) {
            resError($errors, 'Dữ liệu không hợp lệ', REST_Controller::HTTP_BAD_REQUEST);
        }

        // Kiểm tra nghỉ phép có tồn tại không
        $nghiPhep = $this->Hrm_nghi_phep_model
            ->select('id_nghi_phep, minh_chung')
            ->where('id_nghi_phep', $idNghiPhep)
            ->first();

        if (!$nghiPhep) {
            resError(['id_nghi_phep' => 'Không tìm thấy thông tin nghỉ phép'], 'Không tìm thấy', REST_Controller::HTTP_NOT_FOUND);
        }

        // Xử lý upload file minh chứng
        try {
            $folderName = 'hrm/minh-chung-nghi-phep';
            $uploadedFile = $this->fileupload->upload($minhChung, $folderName);

            if (!$uploadedFile['success']) {
                resError(['file_minh_chung' => 'Không thể tải lên file minh chứng'], 'Upload thất bại', REST_Controller::HTTP_BAD_REQUEST);
            }

            $minhChungPath = $uploadedFile['file_path'];

            // Xóa file cũ nếu có
            if (!empty($nghiPhep['minh_chung'])) {
                $this->fileupload->delete($nghiPhep['minh_chung']);
            }

            // Cập nhật đường dẫn file minh chứng vào database
            $this->db->where('id_nghi_phep', $idNghiPhep);
            $this->db->update('hrm_nghi_phep', [
                'minh_chung' => $minhChungPath,
                'updated_user_id' => $auth['ql_nguoi_dung_id'],
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // Lấy thông tin đã cập nhật
            $nghiPhepUpdated = $this->Hrm_nghi_phep_model
                ->select('id_nghi_phep, minh_chung')
                ->where('id_nghi_phep', $idNghiPhep)
                ->first();

            resSuccess([
                'id_nghi_phep' => $nghiPhepUpdated['id_nghi_phep'],
                'minh_chung' => $nghiPhepUpdated['minh_chung'],
                'file_url' => base_url($minhChungPath)
            ], 'Upload file minh chứng thành công', REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            log_message('error', 'Lỗi upload file minh chứng: ' . $e->getMessage());
            resError([
                'message' => 'Có lỗi xảy ra khi upload file',
                'error' => $e->getMessage()
            ], 'Lỗi xử lý', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Upload minh chứng duyệt hộ
     */
    public function upload_minh_chung_duyet_ho_post()
    {
        // Lấy thông tin user đang đăng nhập
        $auth = $this->getUserLogin();

        // Nhận dữ liệu từ request
        $minhChung = isset($_FILES['file_minh_chung_duyet_ho']) ? $_FILES['file_minh_chung_duyet_ho'] : null;

        // Validate
        if (!$minhChung || $minhChung['error'] === UPLOAD_ERR_NO_FILE) {
            resError(['file_minh_chung_duyet_ho' => 'Vui lòng chọn file minh chứng'], 'Dữ liệu không hợp lệ', REST_Controller::HTTP_BAD_REQUEST);
        }

        // Xử lý upload file minh chứng duyệt hộ
        try {
            $folderName = 'hrm/minh-chung-duyet-ho';
            $uploadedFile = $this->fileupload->upload($minhChung, $folderName);

            if (!$uploadedFile['success']) {
                resError(['file_minh_chung_duyet_ho' => 'Không thể tải lên file minh chứng'], 'Upload thất bại', REST_Controller::HTTP_BAD_REQUEST);
            }

            $minhChungPath = $uploadedFile['file_path'];

            resSuccess([
                'minh_chung_duyet_ho' => $minhChungPath,
                'file_url' => base_url($minhChungPath)
            ], 'Upload file minh chứng duyệt hộ thành công', REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            log_message('error', 'Lỗi upload file minh chứng duyệt hộ: ' . $e->getMessage());
            resError([
                'message' => 'Có lỗi xảy ra khi upload file',
                'error' => $e->getMessage()
            ], 'Lỗi xử lý', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Tải file mẫu Excel để import nghỉ phép
     * Format: Row 2=Slug (key DB), Row 3=Header tiếng Việt, Row 4+=Data
     */
    public function download_template_get()
    {
        try {
            $objPHPExcel = new PHPExcel();
            $objPHPExcel->getProperties()
                ->setTitle('Template Import Nghỉ Phép')
                ->setDescription('File mẫu import nghỉ phép');

            $sheet = $objPHPExcel->setActiveSheetIndex(0);

            // Tiêu đề chính
            $sheet->setCellValue('A1', 'FILE MẪU IMPORT DANH SÁCH NGHỈ PHÉP - Cột có dấu (*) là bắt buộc');
            $sheet->mergeCells('A1:K1');
            $sheet->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER]
            ]);

            // Row 2: Slug keys (tên field trong DB)
            $slugs = [
                'ma_nv',
                'email',
                'loai_phep',
                'loai_nghi',
                'ngay_nghi',
                'buoi_nghi',
                'ly_do_nghi',
                'tt_cap_1',
                'tt_cap_2',
                'ly_do_duyet_c1',
                'ly_do_duyet_c2'
            ];

            $col = 'A';
            foreach ($slugs as $slug) {
                $sheet->setCellValue($col . '2', $slug);
                $col++;
            }

            $sheet->getStyle('A2:K2')->applyFromArray([
                'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '666666']],
                'fill' => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => 'F2F2F2']],
                'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER]
            ]);

            // Row 3: Headers tiếng Việt
            $headers = [
                'Mã NV*',
                'Email',
                'Loại Phép*' . "\n" . '(Phép năm, Không hưởng lương)',
                'Loại Nghỉ*' . "\n" . '(Bình thường, Đột xuất)',
                'Ngày Nghỉ*',
                'Buổi Nghỉ*',
                'Lý Do Nghỉ',
                'TT Cấp 1',
                'TT Cấp 2',
                'Lý Do Duyệt C1',
                'Lý Do Duyệt C2'
            ];

            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '3', $header);
                $col++;
            }

            $sheet->getStyle('A3:K3')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => '4472C4']],
                'alignment' => [
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'wrap' => true,
                ]
            ]);
            // Tăng chiều cao row 3 để chứa text 2 dòng
            $sheet->getRowDimension(3)->setRowHeight(36);

            // Set width
            $sheet->getColumnDimension('A')->setWidth(12);
            $sheet->getColumnDimension('B')->setWidth(24);
            $sheet->getColumnDimension('C')->setWidth(32); // "Loại Phép* (Phép năm, Không hưởng lương)"
            $sheet->getColumnDimension('D')->setWidth(28); // "Loại Nghỉ* (Bình thường, Đột xuất)"
            $sheet->getColumnDimension('E')->setWidth(14);
            $sheet->getColumnDimension('F')->setWidth(14);
            $sheet->getColumnDimension('G')->setWidth(22);
            $sheet->getColumnDimension('H')->setWidth(14);
            $sheet->getColumnDimension('I')->setWidth(14);
            $sheet->getColumnDimension('J')->setWidth(22);
            $sheet->getColumnDimension('K')->setWidth(22);

            // ĐƠN 1: NV001 nghỉ 3 ngày (KHÔNG merge - dòng đầu điền đầy đủ)
            // Dòng 1 của đơn 1 - ĐIỀN ĐẦY ĐỦ
            $sheet->setCellValue('A4', 'NV001');
            $sheet->setCellValue('B4', 'nv001@company.com');
            $sheet->setCellValue('C4', 'Phép năm');
            $sheet->setCellValue('D4', 'Bình thường');
            $sheet->setCellValue('E4', '01/03/2026');
            $sheet->setCellValue('F4', 'Cả ngày');
            $sheet->setCellValue('G4', 'Nghỉ việc riêng');
            $sheet->setCellValue('H4', 'Đã duyệt');
            $sheet->setCellValue('I4', 'Đã duyệt');
            $sheet->setCellValue('J4', 'Đồng ý');
            $sheet->setCellValue('K4', 'Đồng ý');

            // Dòng 2 của đơn 1 - CHỈ điền Ngày và Buổi
            $sheet->setCellValue('E5', '02/03/2026');
            $sheet->setCellValue('F5', 'Sáng');

            // Dòng 3 của đơn 1 - CHỈ điền Ngày và Buổi
            $sheet->setCellValue('E6', '03/03/2026');
            $sheet->setCellValue('F6', 'Cả ngày');

            // Bôi màu và style cho đơn 1 (KHÔNG merge)
            $sheet->getStyle('A4:K6')->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ],
                'alignment' => [
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
                ]
            ]);
            $sheet->getStyle('A4:K6')->getFill()
                ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                ->getStartColor()->setRGB('E7F3FF');

            // ĐƠN 2: NV002 nghỉ 1 ngày - ĐIỀN ĐẦY ĐỦ
            $sheet->setCellValue('A7', 'NV002');
            $sheet->setCellValue('B7', 'nv002@company.com');
            $sheet->setCellValue('C7', 'Phép ốm');
            $sheet->setCellValue('D7', 'Đột xuất');
            $sheet->setCellValue('E7', '05/03/2026');
            $sheet->setCellValue('F7', 'Chiều');
            $sheet->setCellValue('G7', 'Không khỏe');
            $sheet->setCellValue('H7', 'Chờ duyệt');
            $sheet->setCellValue('I7', 'Chờ duyệt');
            $sheet->setCellValue('J7', '');
            $sheet->setCellValue('K7', '');

            $sheet->getStyle('A7:K7')->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ],
                'alignment' => [
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
                ]
            ]);
            $sheet->getStyle('A7:K7')->getFill()
                ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                ->getStartColor()->setRGB('FFF4E6');

            // ĐƠN 3: NV001 nghỉ thêm 2 ngày - Đơn mới (loại phép khác)
            // Dòng đầu - ĐIỀN ĐẦY ĐỦ
            $sheet->setCellValue('A8', 'NV001');
            $sheet->setCellValue('B8', 'nv001@company.com');
            $sheet->setCellValue('C8', 'Phép ốm');
            $sheet->setCellValue('D8', 'Đột xuất');
            $sheet->setCellValue('E8', '10/03/2026');
            $sheet->setCellValue('F8', 'Sáng');
            $sheet->setCellValue('G8', 'Đau đầu');
            $sheet->setCellValue('H8', 'Chờ duyệt');
            $sheet->setCellValue('I8', 'Chờ duyệt');
            $sheet->setCellValue('J8', '');
            $sheet->setCellValue('K8', '');

            // Dòng 2 - CHỈ điền Ngày và Buổi
            $sheet->setCellValue('E9', '11/03/2026');
            $sheet->setCellValue('F9', 'Chiều');

            $sheet->getStyle('A8:K9')->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ],
                'alignment' => [
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
                ]
            ]);
            $sheet->getStyle('A8:K9')->getFill()
                ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                ->getStartColor()->setRGB('F0F8E8');

            // Create directory
            $directory = 'uploads/template/' . date('Y') . '/';
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $filename = 'File mẫu import nghỉ phép - ' . date('YmdHis') . '.xlsx';
            $filePath = $directory . $filename;

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save($filePath);

            if (file_exists($filePath)) {
                $this->response([
                    'status' => REST_Controller::HTTP_OK,
                    'message' => 'Tạo file mẫu thành công',
                    'success' => true,
                    'data' => base_url($filePath)
                ], REST_Controller::HTTP_OK);
            } else {
                $this->response([
                    'status' => REST_Controller::HTTP_NOT_FOUND,
                    'message' => 'Không thể tạo file',
                    'success' => false,
                    'data' => null
                ], REST_Controller::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            resError(['message' => $e->getMessage()], 'Lỗi tạo file template', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Import dữ liệu nghỉ phép từ Excel
     * Format mới: Mỗi dòng = 1 ngày, tự động nhóm thành đơn
     */
    public function import_post()
    {
        try {
            $auth = $this->getUserLogin();
            $file_import = commonRequest('file_excel');

            if (!$file_import) {
                resError(['file_excel' => 'Vui lòng chọn file Excel'], 'Không có file được chọn', REST_Controller::HTTP_BAD_REQUEST);
            }

            $config['upload_path'] = 'uploads/excel/nghiphep/';
            $config['allowed_types'] = 'xls|xlsx';

            if (!is_dir($config['upload_path'])) {
                mkdir($config['upload_path'], 0755, true);
            }

            $newFileName = pathinfo($file_import['name'], PATHINFO_FILENAME) . '-' . date('Ymd-His') . '.' . pathinfo($file_import['name'], PATHINFO_EXTENSION);
            $config['file_name'] = $newFileName;

            $this->upload->initialize($config);

            if (!$this->upload->do_upload('file_excel')) {
                $message = 'Thất bại: ' . $this->upload->display_errors();
                resError(['message' => $message], $message, REST_Controller::HTTP_BAD_REQUEST);
            }

            $uploadData = $this->upload->data();
            $uploadedFilePath = $config['upload_path'] . $uploadData['file_name'];

            // Load PHPExcel object để có thể ghi kết quả
            $objPHPExcel = PHPExcel_IOFactory::load($uploadedFilePath);
            $sheet = $objPHPExcel->setActiveSheetIndex(0);

            // Đọc file Excel (header slug ở row 2, data từ row 4, bỏ qua row 3 là header tiếng Việt)
            $excelData = $this->pxl->importExcel(2, 4, $uploadedFilePath);

            if (empty($excelData)) {
                throw new Exception('File Excel không có dữ liệu hoặc sai định dạng');
            }

            // Map buổi nghỉ — key toàn chữ thường, tra cứu bằng mb_strtolower
            $buoiNghiMapping = [
                'ca ngay' => 'Ca_ngay',
                'cả ngày' => 'Ca_ngay',
                'sang' => 'Sang',
                'sáng' => 'Sang',
                'chieu' => 'Chieu',
                'chiều' => 'Chieu',
            ];

            $loaiNghiMapping = [
                'Bình thường' => 'Binh_thuong',
                'binh thuong' => 'Binh_thuong',
                'bình thường' => 'Binh_thuong',
                'Đột xuất' => 'Dot_xuat',
                'dot xuat' => 'Dot_xuat',
                'đột xuất' => 'Dot_xuat',
            ];

            $trangThaiMapping = [
                'Chờ duyệt' => 'Cho_duyet',
                'Cho duyệt' => 'Cho_duyet',
                'Chờ duyet' => 'Cho_duyet',
                'cho duyet' => 'Cho_duyet',
                'Đã duyệt' => 'Da_duyet',
                'Da duyệt' => 'Da_duyet',
                'da duyet' => 'Da_duyet',
                'Từ chối' => 'Tu_choi',
                'Tu chối' => 'Tu_choi',
                'Tu choi' => 'Tu_choi',
                'tu choi' => 'Tu_choi',
            ];

            // Nhóm các dòng thành đơn: dựa vào ma_nv có giá trị = đơn mới, ma_nv trống = tiếp tục đơn cũ
            // Lưu giá trị cuối cùng của mỗi field để fill cho dòng sau (xử lý merge cells)
            $lastValues = [
                'email' => '',
                'loai_phep' => '',
                'loai_nghi' => '',
                'ly_do_nghi' => '',
                'tt_cap_1' => '',
                'tt_cap_2' => '',
                'ly_do_duyet_c1' => '',
                'ly_do_duyet_c2' => ''
            ];

            $groupedData = [];
            $currentGroup = null;
            $groupIndex = 0;

            foreach ($excelData as $rowIndex => $row) {
                $maNV = isset($row['ma_nv']) ? trim($row['ma_nv']) : '';
                $email = isset($row['email']) ? trim($row['email']) : '';
                $loaiPhep = isset($row['loai_phep']) ? trim($row['loai_phep']) : '';
                $loaiNghi = isset($row['loai_nghi']) ? trim($row['loai_nghi']) : '';
                $ngayNghi = isset($row['ngay_nghi']) ? trim($row['ngay_nghi']) : '';
                $buoiNghi = isset($row['buoi_nghi']) ? trim($row['buoi_nghi']) : '';
                $lyDoNghi = isset($row['ly_do_nghi']) ? trim($row['ly_do_nghi']) : '';
                $ttCap1 = isset($row['tt_cap_1']) ? trim($row['tt_cap_1']) : '';
                $ttCap2 = isset($row['tt_cap_2']) ? trim($row['tt_cap_2']) : '';
                $lyDoDuyetC1 = isset($row['ly_do_duyet_c1']) ? trim($row['ly_do_duyet_c1']) : '';
                $lyDoDuyetC2 = isset($row['ly_do_duyet_c2']) ? trim($row['ly_do_duyet_c2']) : '';

                // Bỏ qua dòng trống hoàn toàn:
                // - Không có ma_nv (không phải đơn mới)
                // - Không có ngay_nghi và buoi_nghi (không phải ngày nghỉ tiếp theo)
                // → dòng thừa do border/formatting, người dùng không cần xóa thủ công
                if (empty($maNV) && empty($ngayNghi) && empty($buoiNghi)) {
                    continue;
                }

                // Fill các field trống từ lastValues (xử lý merge cells)
                if (empty($email))
                    $email = $lastValues['email'];
                if (empty($loaiPhep))
                    $loaiPhep = $lastValues['loai_phep'];
                if (empty($loaiNghi))
                    $loaiNghi = $lastValues['loai_nghi'];
                if (empty($lyDoNghi))
                    $lyDoNghi = $lastValues['ly_do_nghi'];
                if (empty($ttCap1))
                    $ttCap1 = $lastValues['tt_cap_1'];
                if (empty($ttCap2))
                    $ttCap2 = $lastValues['tt_cap_2'];
                if (empty($lyDoDuyetC1))
                    $lyDoDuyetC1 = $lastValues['ly_do_duyet_c1'];
                if (empty($lyDoDuyetC2))
                    $lyDoDuyetC2 = $lastValues['ly_do_duyet_c2'];

                // Update lastValues
                if (!empty($email))
                    $lastValues['email'] = $email;
                if (!empty($loaiPhep))
                    $lastValues['loai_phep'] = $loaiPhep;
                if (!empty($loaiNghi))
                    $lastValues['loai_nghi'] = $loaiNghi;
                if (!empty($lyDoNghi))
                    $lastValues['ly_do_nghi'] = $lyDoNghi;
                if (!empty($ttCap1))
                    $lastValues['tt_cap_1'] = $ttCap1;
                if (!empty($ttCap2))
                    $lastValues['tt_cap_2'] = $ttCap2;
                if (!empty($lyDoDuyetC1))
                    $lastValues['ly_do_duyet_c1'] = $lyDoDuyetC1;
                if (!empty($lyDoDuyetC2))
                    $lastValues['ly_do_duyet_c2'] = $lyDoDuyetC2;

                // Nếu ma_nv có giá trị → Bắt đầu đơn mới
                if (!empty($maNV)) {
                    $groupIndex++;
                    $currentGroup = 'group_' . $groupIndex;

                    $groupedData[$currentGroup] = [
                        'ma_nv' => $maNV,
                        'email' => $email,
                        'loai_phep' => $loaiPhep,
                        'loai_nghi' => $loaiNghi,
                        'ly_do_nghi' => $lyDoNghi,
                        'tt_cap_1' => $ttCap1,
                        'tt_cap_2' => $ttCap2,
                        'ly_do_duyet_c1' => $lyDoDuyetC1,
                        'ly_do_duyet_c2' => $lyDoDuyetC2,
                        'rows' => [],
                        'ngay_nghi' => []
                    ];
                }

                // Thêm ngày nghỉ vào đơn hiện tại
                if ($currentGroup !== null) {
                    $groupedData[$currentGroup]['rows'][] = $rowIndex + 4; // Excel row number
                    $groupedData[$currentGroup]['ngay_nghi'][] = [
                        'ngay' => $ngayNghi,
                        'buoi' => $buoiNghi,
                        'row' => $rowIndex + 4
                    ];
                }
            }

            // Xử lý từng nhóm (mỗi nhóm = 1 đơn nghỉ phép)
            $results = [];
            $successCount = 0;
            $errorCount = 0;

            foreach ($groupedData as $groupKey => $group) {
                $this->db->trans_start();

                try {
                    // Validate dữ liệu cơ bản
                    $maNV = $group['ma_nv'];
                    $email = $group['email'];
                    $loaiPhepText = $group['loai_phep'];
                    $loaiNghiText = $group['loai_nghi'];
                    $lyDoNghi = $group['ly_do_nghi'];

                    if (empty($maNV) && empty($email)) {
                        throw new Exception('Phải có Mã NV hoặc Email');
                    }

                    if (empty($loaiPhepText)) {
                        throw new Exception('Loại phép không được để trống');
                    }

                    if (empty($loaiNghiText)) {
                        throw new Exception('Loại nghỉ không được để trống');
                    }

                    // Tìm nhân viên
                    $nhanVien = null;
                    if (!empty($maNV)) {
                        $nhanVien = $this->Hrm_nhan_vien_model
                            ->where('ma_nhan_vien', $maNV)
                            ->where('deleted_at IS NULL')
                            ->first();
                    }

                    if (!$nhanVien && !empty($email)) {
                        $nhanVien = $this->Hrm_nhan_vien_model
                            ->where('email', $email)
                            ->where('deleted_at IS NULL')
                            ->first();
                    }

                    if (!$nhanVien) {
                        throw new Exception('Không tìm thấy nhân viên với mã: ' . $maNV);
                    }

                    // Tìm loại phép
                    $loaiPhep = $this->Hrm_danh_muc_loai_nghi_phep_model
                        ->where('ten_loai_phep', $loaiPhepText)
                        ->where('deleted_at IS NULL')
                        ->first();

                    if (!$loaiPhep) {
                        throw new Exception('Không tìm thấy loại phép: ' . $loaiPhepText);
                    }

                    // Map loại nghỉ
                    $loaiNghi = isset($loaiNghiMapping[$loaiNghiText]) ? $loaiNghiMapping[$loaiNghiText] : $loaiNghiText;
                    if (!in_array($loaiNghi, ['Binh_thuong', 'Dot_xuat'])) {
                        throw new Exception('Loại nghỉ phải là: Bình thường hoặc Đột xuất');
                    }

                    // Validate và parse từng ngày nghỉ
                    $danhSachNgayNghi = [];
                    foreach ($group['ngay_nghi'] as $ngayInfo) {
                        $ngayText = $ngayInfo['ngay'];
                        $buoiText = $ngayInfo['buoi'];

                        if (empty($ngayText)) {
                            throw new Exception('Dòng ' . $ngayInfo['row'] . ': Ngày nghỉ trống');
                        }

                        if (empty($buoiText)) {
                            throw new Exception('Dòng ' . $ngayInfo['row'] . ': Buổi nghỉ trống');
                        }

                        // Parse ngày nghỉ — xử lý cả 3 trường hợp PHPExcel trả về:
                        // 1. Excel serial number (General format): số như 46078
                        // 2. String dd/mm/yyyy (người dùng gõ tay)
                        // 3. String yyyy-mm-dd (Excel Date format đọc ra)
                        $ngayNghi = null;

                        if (is_numeric($ngayText)) {
                            // TH1: Excel serial date
                            $timestamp = PHPExcel_Shared_Date::ExcelToPHP((float) $ngayText);
                            $ngayNghi = new DateTime('@' . $timestamp);
                            $ngayNghi->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
                        } else {
                            // TH2 & TH3: thử lần lượt các định dạng string phổ biến
                            foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'd/m/y', 'Y/m/d'] as $fmt) {
                                $parsed = DateTime::createFromFormat($fmt, trim($ngayText));
                                if ($parsed) {
                                    $ngayNghi = $parsed;
                                    break;
                                }
                            }
                        }

                        if (!$ngayNghi) {
                            throw new Exception('Dòng ' . $ngayInfo['row'] . ': Ngày nghỉ "' . $ngayText . '" không đúng định dạng (hỗ trợ: dd/mm/yyyy, yyyy-mm-dd, hoặc ô định dạng Date)');
                        }

                        $buoiKey = mb_strtolower(trim($buoiText));
                        $buoiNghi = isset($buoiNghiMapping[$buoiKey]) ? $buoiNghiMapping[$buoiKey] : $buoiKey;
                        if (!in_array($buoiNghi, ['Ca_ngay', 'Sang', 'Chieu'])) {
                            throw new Exception('Dòng ' . $ngayInfo['row'] . ': Buổi nghỉ phải là: Cả ngày, Sáng hoặc Chiều');
                        }

                        $danhSachNgayNghi[] = [
                            'ngay' => $ngayNghi->format('Y-m-d'),
                            'buoi' => $buoiNghi
                        ];
                    }

                    if (empty($danhSachNgayNghi)) {
                        throw new Exception('Phải có ít nhất 1 ngày nghỉ');
                    }

                    // Kiểm tra trùng đơn nghỉ phép (cùng nhân viên + loại phép + cùng khoảng ngày)
                    $cacNgayNghi = array_column($danhSachNgayNghi, 'ngay');

                    $this->db->select('np.id_nghi_phep, np.uuid_nghi_phep, npct.ngay_nghi');
                    $this->db->from('hrm_nghi_phep np');
                    $this->db->join('hrm_nghi_phep_chi_tiet npct', 'npct.id_nghi_phep = np.id_nghi_phep');
                    $this->db->where('np.id_nhan_vien', $nhanVien['id_nhan_vien']);
                    $this->db->where('np.id_loai_phep', $loaiPhep['id_loai_phep']);
                    $this->db->where_in('npct.ngay_nghi', $cacNgayNghi);
                    $this->db->where('np.deleted_at IS NULL');
                    $donTrung = $this->db->get()->result_array();

                    if (!empty($donTrung)) {
                        $ngayTrung = array_unique(array_column($donTrung, 'ngay_nghi'));
                        $ngayTrungFormat = array_map(function ($d) {
                            return date('d/m/Y', strtotime($d));
                        }, $ngayTrung);

                        throw new Exception('Đơn nghỉ trùng lặp! Nhân viên "' . $maNV . '" đã có đơn nghỉ "' . $loaiPhepText . '" vào các ngày: ' . implode(', ', $ngayTrungFormat));
                    }

                    // Tạo UUID
                    $uuid = sprintf(
                        '%04x%04x',
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0xffff)
                    );

                    // Map trạng thái duyệt từ Excel
                    $ttCap1Text = $group['tt_cap_1'];
                    $ttCap2Text = $group['tt_cap_2'];
                    $lyDoDuyetC1 = $group['ly_do_duyet_c1'];
                    $lyDoDuyetC2 = $group['ly_do_duyet_c2'];

                    $trangThaiCap1 = !empty($ttCap1Text) && isset($trangThaiMapping[$ttCap1Text])
                        ? $trangThaiMapping[$ttCap1Text]
                        : 'Cho_duyet';

                    $trangThaiCap2 = !empty($ttCap2Text) && isset($trangThaiMapping[$ttCap2Text])
                        ? $trangThaiMapping[$ttCap2Text]
                        : 'Cho_duyet';

                    // Insert đơn nghỉ phép (KHÔNG có tu_ngay, den_ngay, so_ngay_nghi - chỉ lưu trong chi_tiet)
                    $dataNghiPhep = [
                        'uuid_nghi_phep' => $uuid,
                        'id_nhan_vien' => $nhanVien['id_nhan_vien'],
                        'id_loai_phep' => $loaiPhep['id_loai_phep'],
                        'loai_nghi' => $loaiNghi,
                        'ly_do_nghi' => $lyDoNghi,
                        'trang_thai_cap_mot' => $trangThaiCap1,
                        'trang_thai_cap_hai' => $trangThaiCap2,
                        'created_user_id' => $nhanVien['ql_nguoi_dung_id'],
                        'created_at' => date('Y-m-d H:i:s')
                    ];

                    $this->db->insert('hrm_nghi_phep', $dataNghiPhep);
                    $idNghiPhep = $this->db->insert_id();

                    // Insert chi tiết ngày nghỉ theo dữ liệu Excel (chính xác từng ngày)
                    foreach ($danhSachNgayNghi as $item) {
                        if ($item['buoi'] == 'Ca_ngay') {
                            // Cả ngày = 2 buổi
                            $this->db->insert('hrm_nghi_phep_chi_tiet', [
                                'id_nghi_phep' => $idNghiPhep,
                                'ngay_nghi' => $item['ngay'],
                                'buoi_nghi' => 'Sang',
                                'so_ngay_nghi' => 0.5
                            ]);
                            $this->db->insert('hrm_nghi_phep_chi_tiet', [
                                'id_nghi_phep' => $idNghiPhep,
                                'ngay_nghi' => $item['ngay'],
                                'buoi_nghi' => 'Chieu',
                                'so_ngay_nghi' => 0.5
                            ]);
                        } else {
                            // Chỉ 1 buổi
                            $this->db->insert('hrm_nghi_phep_chi_tiet', [
                                'id_nghi_phep' => $idNghiPhep,
                                'ngay_nghi' => $item['ngay'],
                                'buoi_nghi' => $item['buoi'],
                                'so_ngay_nghi' => 0.5
                            ]);
                        }
                    }

                    // Tự động gán người duyệt từ đơn vị
                    $idDonVi = $nhanVien['id_don_vi_cong_tac'];
                    if ($idDonVi) {
                        // Người duyệt cấp 1 (lãnh đạo đơn vị)
                        $lanhDaoCap1 = $this->db
                            ->select('ql_nguoi_dung_id')
                            ->from('e_lanh_dao_don_vi')
                            ->where('id_don_vi', $idDonVi)
                            ->limit(1)
                            ->get()
                            ->row_array();

                        if ($lanhDaoCap1) {
                            // Xác định da_duyet dựa trên trạng thái
                            $daDuyetCap1 = ($trangThaiCap1 === 'Da_duyet') ? 1 : 0;

                            $nguoiDuyetCap1Data = [
                                'id_nghi_phep' => $idNghiPhep,
                                'id_nguoi_duyet' => $lanhDaoCap1['ql_nguoi_dung_id'],
                                'cap_duyet' => 1,
                                'da_duyet' => $daDuyetCap1,
                                'created_at' => date('Y-m-d H:i:s')
                            ];

                            // Thêm lý do duyệt nếu có
                            if (!empty($lyDoDuyetC1)) {
                                $nguoiDuyetCap1Data['ly_do'] = $lyDoDuyetC1;
                            }

                            // Thêm thời gian duyệt nếu đã duyệt
                            if ($daDuyetCap1) {
                                $nguoiDuyetCap1Data['thoi_gian_duyet'] = date('Y-m-d H:i:s');
                            }

                            $this->db->insert('hrm_nghi_phep_nguoi_duyet', $nguoiDuyetCap1Data);

                            $this->db->where('id_nghi_phep', $idNghiPhep)
                                ->update('hrm_nghi_phep', [
                                    'nguoi_duyet_cap_mot_id' => $lanhDaoCap1['ql_nguoi_dung_id']
                                ]);
                        }

                        // Người duyệt cấp 2 — cố định ql_nguoi_dung_id = 2503
                        $idNguoiDuyetCap2 = 2503;
                        $daDuyetCap2 = ($trangThaiCap2 === 'Da_duyet') ? 1 : 0;

                        $nguoiDuyetCap2Data = [
                            'id_nghi_phep' => $idNghiPhep,
                            'id_nguoi_duyet' => $idNguoiDuyetCap2,
                            'cap_duyet' => 2,
                            'da_duyet' => $daDuyetCap2,
                            'created_at' => date('Y-m-d H:i:s')
                        ];

                        if (!empty($lyDoDuyetC2)) {
                            $nguoiDuyetCap2Data['ly_do'] = $lyDoDuyetC2;
                        }

                        if ($daDuyetCap2) {
                            $nguoiDuyetCap2Data['thoi_gian_duyet'] = date('Y-m-d H:i:s');
                        }

                        $this->db->insert('hrm_nghi_phep_nguoi_duyet', $nguoiDuyetCap2Data);

                        $this->db->where('id_nghi_phep', $idNghiPhep)
                            ->update('hrm_nghi_phep', [
                                'nguoi_duyet_cap_hai_id' => $idNguoiDuyetCap2
                            ]);
                    }

                    $this->db->trans_complete();

                    if ($this->db->trans_status() === FALSE) {
                        throw new Exception('Lỗi ghi dữ liệu vào database');
                    }

                    $successCount++;
                    $results[] = [
                        'rows' => implode(', ', $group['rows']),
                        'ma_nv' => $maNV,
                        'status' => 'success',
                        'message' => 'Import thành công ' . count($danhSachNgayNghi) . ' ngày nghỉ',
                        'uuid' => $uuid
                    ];

                    // Ghi "Thành công" vào cột L cho tất cả các dòng của đơn này
                    foreach ($group['rows'] as $excelRow) {
                        $sheet->setCellValue('L' . $excelRow, 'Thành công');
                        $sheet->getStyle('L' . $excelRow)->applyFromArray([
                            'fill' => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => 'C6EFCE']],
                            'font' => ['color' => ['rgb' => '006100']]
                        ]);
                    }
                } catch (Exception $e) {
                    $this->db->trans_rollback();
                    $errorCount++;
                    $errorMessage = $e->getMessage();
                    $results[] = [
                        'rows' => implode(', ', $group['rows']),
                        'ma_nv' => $group['ma_nv'],
                        'status' => 'error',
                        'message' => $errorMessage
                    ];

                    // Ghi lỗi vào cột L cho tất cả các dòng của đơn này
                    foreach ($group['rows'] as $excelRow) {
                        $sheet->setCellValue('L' . $excelRow, 'Lỗi: ' . $errorMessage);
                        $sheet->getStyle('L' . $excelRow)->applyFromArray([
                            'fill' => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => 'FFC7CE']],
                            'font' => ['color' => ['rgb' => '9C0006']]
                        ]);
                    }
                }
            }

            // Lưu file Excel với kết quả ở cột L
            $sheet->getColumnDimension('L')->setAutoSize(true);

            $resultFileName = 'result_' . $newFileName;
            $resultPath = $config['upload_path'] . $resultFileName;

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save($resultPath);

            $message = "Import hoàn tất: {$successCount} thành công, {$errorCount} lỗi";

            resSuccess([
                'file_result' => base_url($resultPath),
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'total' => count($groupedData),
                'details' => $results
            ], $message, REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            resError(['message' => $e->getMessage()], 'Có lỗi xảy ra khi import', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function export_by_employee_ids_get()
    {
        try {
            $auth = $this->getUserLogin();
            $qlNguoiDungId = $auth['ql_nguoi_dung_id'];

            // Lấy năm từ request, mặc định năm hiện tại
            $year = commonRequest('year') ? commonRequest('year') : date('Y');

            // Lấy danh sách employee_ids
            $employeeIdsStr = commonRequest('employee_ids');
            if (empty($employeeIdsStr)) {
                resError(['message' => 'Vui lòng chọn ít nhất một nhân viên'], 'Thiếu thông tin', REST_Controller::HTTP_BAD_REQUEST);
            }

            // Chuyển string thành array và loại bỏ giá trị rỗng
            $employeeIds = array_filter(array_map('trim', explode(',', $employeeIdsStr)));

            if (empty($employeeIds)) {
                resError(['message' => 'Danh sách nhân viên không hợp lệ'], 'Dữ liệu không hợp lệ', REST_Controller::HTTP_BAD_REQUEST);
            }

            // Create directory if not exists
            $directory = 'uploads/export/' . date('Y') . '/';
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Tạo thư mục tạm để chứa các file Word
            $tempDir = $directory . 'temp_' . uniqid() . '/';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $createdFiles = [];
            $successCount = 0;
            $errorEmployees = [];

            // Load template Word một lần
            $templatePath = FCPATH . 'assets/template_nghi_phep.docx';
            if (!file_exists($templatePath)) {
                resError(['message' => 'Không tìm thấy file template'], 'File không tồn tại', REST_Controller::HTTP_NOT_FOUND);
            }

            foreach ($employeeIds as $idNhanVien) {
                try {
                    // Lấy thông tin nhân viên
                    $nhanVien = $this->db
                        ->select('
                            nv.id_nhan_vien,
                            nv.ma_nhan_vien,
                            nv.ho_va_ten,
                            nv.so_dien_thoai,
                            cv.ten_cong_viec as ten_chuc_vu,
                            dv.ten_don_vi
                        ')
                        ->from('hrm_nhan_vien nv')
                        ->join('hrm_vi_tri_cong_viec cv', 'cv.id_vi_tri_cong_viec = nv.id_vi_tri_cong_viec', 'left')
                        ->join('e_don_vi dv', 'dv.id_don_vi = nv.id_don_vi_cong_tac', 'left')
                        ->where('nv.id_nhan_vien', $idNhanVien)
                        ->where('nv.deleted_at IS NULL')
                        ->get()
                        ->row_array();

                    if (!$nhanVien) {
                        $errorEmployees[] = "ID: $idNhanVien - Không tìm thấy";
                        continue;
                    }

                    // Lấy danh sách đơn nghỉ phép của nhân viên trong năm
                    $danhSachNghiPhep = $this->db
                        ->select('
                            np.uuid_nghi_phep,
                            np.ly_do_nghi,
                            np.loai_nghi,
                            np.trang_thai_cap_mot,
                            np.trang_thai_cap_hai,
                            DATE_FORMAT(np.created_at, "%d/%m/%Y") as ngay_tao,
                            lnp.ten_loai_phep,
                            SUM(ct.so_ngay_nghi) as tong_so_ngay_nghi,
                            MIN(ct.ngay_nghi) as ngay_bat_dau_nghi,
                            nd1.ql_nguoi_dung_ho_ten AS nguoi_duyet_cap_mot_ho_ten, 
                            nd2.ql_nguoi_dung_ho_ten AS nguoi_duyet_cap_hai_ho_ten
                        ')
                        ->from('hrm_nghi_phep np')
                        ->join('hrm_danh_muc_loai_nghi_phep lnp', 'lnp.id_loai_phep = np.id_loai_phep', 'left')
                        ->join('hrm_nghi_phep_chi_tiet ct', 'ct.id_nghi_phep = np.id_nghi_phep', 'left')
                        ->join('ql_nguoi_dung nd1', 'nd1.ql_nguoi_dung_id = np.nguoi_duyet_cap_mot_id', 'left')
                        ->join('ql_nguoi_dung nd2', 'nd2.ql_nguoi_dung_id = np.nguoi_duyet_cap_hai_id', 'left')
                        ->where('np.id_nhan_vien', $idNhanVien)
                        ->where('np.deleted_at IS NULL')
                        ->where('YEAR(np.created_at)', $year)
                        ->where('np.trang_thai_cap_mot IN ("Da_duyet", "Tu_choi")')
                        ->group_by('np.id_nghi_phep')
                        ->order_by('ngay_bat_dau_nghi', 'ASC')
                        ->get()
                        ->result_array();

                    // Lấy chi tiết ngày nghỉ cho từng đơn
                    foreach ($danhSachNghiPhep as &$item) {
                        $chiTietNgayNghi = $this->db
                            ->select('ngay_nghi, buoi_nghi')
                            ->from('hrm_nghi_phep_chi_tiet')
                            ->join('hrm_nghi_phep', 'hrm_nghi_phep.id_nghi_phep = hrm_nghi_phep_chi_tiet.id_nghi_phep')
                            ->where('hrm_nghi_phep.uuid_nghi_phep', $item['uuid_nghi_phep'])
                            ->order_by('ngay_nghi', 'ASC')
                            ->get()
                            ->result_array();

                        // Format thời gian nghỉ
                        if (!empty($chiTietNgayNghi)) {
                            // Lấy ngày đầu và ngày cuối (unique)
                            $uniqueDates = array_unique(array_column($chiTietNgayNghi, 'ngay_nghi'));
                            sort($uniqueDates);

                            if (count($uniqueDates) > 1) {
                                // Nhiều ngày khác nhau: 20 - 21/01/2026
                                $ngayDau = date('d', strtotime($uniqueDates[0]));
                                $ngayCuoi = date('d/m/Y', strtotime($uniqueDates[count($uniqueDates) - 1]));
                                $item['thoi_gian'] = $ngayDau . ' - ' . $ngayCuoi;
                            } else {
                                // Chỉ 1 ngày: 20/01/2026
                                $item['thoi_gian'] = date('d/m/Y', strtotime($uniqueDates[0]));
                            }
                        } else {
                            $item['thoi_gian'] = '';
                        }

                        // Xác định trạng thái cấp đơn vị
                        if ($item['trang_thai_cap_mot'] == 'Da_duyet') {
                            $item['trang_thai_cap_don_vi'] = 'Đã duyệt';
                        } elseif ($item['trang_thai_cap_mot'] == 'Tu_choi') {
                            $item['trang_thai_cap_don_vi'] = 'Từ chối';
                        } else {
                            $item['trang_thai_cap_don_vi'] = 'Chờ duyệt';
                        }

                        // Xác định trạng thái TC-HC
                        if ($item['trang_thai_cap_hai'] == 'Da_duyet') {
                            $item['trang_thai_tc_hc'] = 'Đã duyệt';
                        } elseif ($item['trang_thai_cap_hai'] == 'Tu_choi') {
                            $item['trang_thai_tc_hc'] = 'Từ chối';
                        } elseif ($item['trang_thai_cap_mot'] == 'Da_duyet') {
                            $item['trang_thai_tc_hc'] = 'Chờ duyệt';
                        } else {
                            $item['trang_thai_tc_hc'] = '';
                        }
                    }

                    // Tạo file Word cho nhân viên này
                    $fileName = 'Don_nghi_phep_' . $nhanVien['ma_nhan_vien'] . '_' . $year . '.docx';
                    $filePath = $tempDir . $fileName;

                    // Copy template to new location
                    copy($templatePath, $filePath);

                    // Open as ZIP
                    $zip = new ZipArchive();
                    if ($zip->open($filePath) !== TRUE) {
                        $errorEmployees[] = $nhanVien['ho_va_ten'] . " - Lỗi mở file";
                        continue;
                    }

                    // Read document.xml
                    $documentXml = $zip->getFromName('word/document.xml');
                    if ($documentXml === false) {
                        $zip->close();
                        $errorEmployees[] = $nhanVien['ho_va_ten'] . " - Lỗi đọc template";
                        continue;
                    }

                    // LÀM SẠCH XML: Nối các placeholder bị chia cắt bởi Word
                    $documentXml = preg_replace('/(\$)(<[^>]+>)*(\{)(<[^>]+>)*([a-zA-Z0-9_]+)(<[^>]+>)*(\})/', '$1$3$5$7', $documentXml);
                    for ($clean = 0; $clean < 5; $clean++) {
                        $documentXml = preg_replace('/\$\{([a-zA-Z0-9_]*)(<[^>]+>)+([a-zA-Z0-9_]*)\}/', '${$1$3}', $documentXml);
                    }
                    $documentXml = preg_replace('/\$(<[^>]*>)*\{/', '${', $documentXml);
                    $documentXml = preg_replace('/([a-zA-Z0-9_]+)(<[^>]*>)*\}/', '$1}', $documentXml);
                    $documentXml = preg_replace_callback('/\$\{[^}]*\}/', function ($match) {
                        return preg_replace('/<[^>]+>/', '', $match[0]);
                    }, $documentXml);

                    // Replace placeholders - Thông tin nhân viên
                    $documentXml = str_replace('${ho_va_ten}', htmlspecialchars($nhanVien['ho_va_ten'] ?: '', ENT_XML1, 'UTF-8'), $documentXml);
                    $documentXml = str_replace('${so_dien_thoai}', htmlspecialchars($nhanVien['so_dien_thoai'] ?: '', ENT_XML1, 'UTF-8'), $documentXml);
                    $documentXml = str_replace('${chuc_vu}', htmlspecialchars($nhanVien['ten_chuc_vu'] ?: '', ENT_XML1, 'UTF-8'), $documentXml);
                    $documentXml = str_replace('${don_vi}', htmlspecialchars($nhanVien['ten_don_vi'] ?: '', ENT_XML1, 'UTF-8'), $documentXml);

                    // Điền dữ liệu vào 12 dòng cố định
                    for ($i = 1; $i <= 12; $i++) {
                        if (isset($danhSachNghiPhep[$i - 1])) {
                            $item = $danhSachNghiPhep[$i - 1];
                            $documentXml = str_replace('${thoi_gian_' . $i . '}', htmlspecialchars($item['thoi_gian'], ENT_XML1, 'UTF-8'), $documentXml);
                            $documentXml = str_replace('${so_ngay_' . $i . '}', htmlspecialchars($item['tong_so_ngay_nghi'] . ' ngày', ENT_XML1, 'UTF-8'), $documentXml);
                            $documentXml = str_replace('${ly_do_' . $i . '}', htmlspecialchars($item['ly_do_nghi'] ?: '', ENT_XML1, 'UTF-8'), $documentXml);
                            $documentXml = str_replace(
                                '${tt_cap_1_' . $i . '}',
                                htmlspecialchars($item['trang_thai_cap_don_vi'], ENT_XML1, 'UTF-8'),
                                $documentXml
                            );
                            $documentXml = str_replace('${ten_cap_1_' . $i . '}', htmlspecialchars($item['nguoi_duyet_cap_mot_ho_ten'] ?: '', ENT_XML1, 'UTF-8'), $documentXml);
                            $documentXml = str_replace('${tt_cap_2_' . $i . '}', htmlspecialchars($item['trang_thai_tc_hc'], ENT_XML1, 'UTF-8'), $documentXml);
                            $documentXml = str_replace('${ten_cap_2_' . $i . '}', htmlspecialchars($item['nguoi_duyet_cap_hai_ho_ten'] ?: '', ENT_XML1, 'UTF-8'), $documentXml);
                        } else {
                            $documentXml = str_replace('${thoi_gian_' . $i . '}', '', $documentXml);
                            $documentXml = str_replace('${so_ngay_' . $i . '}', '', $documentXml);
                            $documentXml = str_replace('${ly_do_' . $i . '}', '', $documentXml);
                            $documentXml = str_replace('${tt_cap_1_' . $i . '}', '', $documentXml);
                            $documentXml = str_replace('${ten_cap_1_' . $i . '}', '', $documentXml);
                            $documentXml = str_replace('${tt_cap_2_' . $i . '}', '', $documentXml);
                            $documentXml = str_replace('${ten_cap_2_' . $i . '}', '', $documentXml);
                        }
                    }

                    // Update document.xml in ZIP
                    $zip->deleteName('word/document.xml');
                    $zip->addFromString('word/document.xml', $documentXml);
                    $zip->close();

                    $createdFiles[] = $filePath;
                    $successCount++;
                } catch (Exception $e) {
                    $errorEmployees[] = (isset($nhanVien) && isset($nhanVien['ho_va_ten']) ? $nhanVien['ho_va_ten'] : "ID: $idNhanVien") . " - " . $e->getMessage();
                    continue;
                }
            }

            // Kiểm tra nếu không có file nào được tạo
            if (empty($createdFiles)) {
                // Xóa thư mục tạm
                if (is_dir($tempDir)) {
                    array_map('unlink', glob("$tempDir/*.*"));
                    rmdir($tempDir);
                }

                $errorMessage = !empty($errorEmployees) ? implode('; ', $errorEmployees) : 'Không thể tạo file cho bất kỳ nhân viên nào';
                resError(['message' => $errorMessage, 'errors' => $errorEmployees], 'Xuất file thất bại', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Nếu chỉ có 1 file, trả về file đó trực tiếp
            if (count($createdFiles) == 1) {
                // Di chuyển file ra ngoài thư mục tạm
                $singleFile = $createdFiles[0];
                $finalFileName = basename($singleFile);
                $finalPath = $directory . $finalFileName;
                rename($singleFile, $finalPath);

                // Xóa thư mục tạm
                if (is_dir($tempDir)) {
                    rmdir($tempDir);
                }

                $this->load->helper('url');
                $this->response([
                    'status' => REST_Controller::HTTP_OK,
                    'message' => 'Export thành công',
                    'success' => true,
                    'data' => base_url($finalPath),
                    'summary' => [
                        'total' => count($employeeIds),
                        'success' => $successCount,
                        'failed' => count($errorEmployees)
                    ]
                ], REST_Controller::HTTP_OK);
                return;
            }

            // Nén tất cả file vào 1 file ZIP
            $zipFileName = 'Don_nghi_phep_' . count($createdFiles) . '_nhan_vien_' . $year . '_' . date('YmdHis') . '.zip';
            $zipFilePath = $directory . $zipFileName;

            $zipArchive = new ZipArchive();
            if ($zipArchive->open($zipFilePath, ZipArchive::CREATE) !== TRUE) {
                // Xóa các file tạm
                foreach ($createdFiles as $file) {
                    if (file_exists($file)) {
                        @unlink($file);
                    }
                }
                if (is_dir($tempDir)) {
                    @rmdir($tempDir);
                }
                resError(['message' => 'Không thể tạo file ZIP'], 'Lỗi nén file', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Thêm các file Word vào ZIP
            foreach ($createdFiles as $file) {
                if (file_exists($file)) {
                    $zipArchive->addFile($file, basename($file));
                }
            }

            $zipArchive->close();

            // Xóa các file Word tạm và thư mục tạm
            foreach ($createdFiles as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                }
            }
            if (is_dir($tempDir)) {
                @rmdir($tempDir);
            }

            // Kiểm tra file ZIP đã được tạo
            if (file_exists($zipFilePath)) {
                $this->load->helper('url');
                $this->response([
                    'status' => REST_Controller::HTTP_OK,
                    'message' => 'Export thành công ' . $successCount . ' nhân viên',
                    'success' => true,
                    'data' => base_url($zipFilePath),
                    'summary' => [
                        'total' => count($employeeIds),
                        'success' => $successCount,
                        'failed' => count($errorEmployees),
                        'errors' => $errorEmployees
                    ]
                ], REST_Controller::HTTP_OK);
            } else {
                $this->response([
                    'status' => REST_Controller::HTTP_NOT_FOUND,
                    'message' => 'Không thể tạo file ZIP',
                    'success' => false,
                    'data' => null
                ], REST_Controller::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            // Cleanup nếu có lỗi
            if (isset($tempDir) && is_dir($tempDir)) {
                array_map('unlink', glob("$tempDir/*.*"));
                @rmdir($tempDir);
            }

            log_message('error', 'Lỗi export file Word theo nhóm nhân viên: ' . $e->getMessage());
            resError([
                'message' => 'Có lỗi xảy ra khi export file',
                'error' => $e->getMessage()
            ], 'Lỗi xử lý', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Thống kê nghỉ phép theo đơn vị, nhóm theo LOẠI đơn vị hoặc TRƯỜNG (nếu có)
     * POST /api/v2/admin/hrm/nghiphep/thong_ke_don_vi
     * 
     * Parameters:
     * - start_date: Ngày bắt đầu tạo đơn (Y-m-d) - optional
     * - end_date: Ngày kết thúc tạo đơn (Y-m-d) - optional
     * - loai: Lọc theo loại đơn vị (KHOA_BOMON, PHONG, TRUNG_TAM, BAN, VIEN) - optional
     * - id_don_vi: Lọc theo id đơn vị cụ thể - optional
     * 
     * Response:
     * - Tổng số đơn (tất cả trạng thái)
     * - Số đơn đã duyệt cấp 2 (trang_thai_cap_mot = "Da_duyet" AND trang_thai_cap_hai = "Da_duyet")
     */
    public function thong_ke_don_vi_post()
    {
        try {
            $auth = $this->getUserLogin();

            // Lấy parameters
            $startDate = commonRequest('start_date');
            $endDate = commonRequest('end_date');
            $loaiFilter = commonRequest('loai'); // KHOA_BOMON, PHONG, etc.
            $idDonViFilter = commonRequest('id_don_vi');

            // Validate date format
            if ($startDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
                resError(['start_date' => 'Định dạng ngày không hợp lệ (Y-m-d)'], 'Dữ liệu không hợp lệ', REST_Controller::HTTP_BAD_REQUEST);
            }
            if ($endDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                resError(['end_date' => 'Định dạng ngày không hợp lệ (Y-m-d)'], 'Dữ liệu không hợp lệ', REST_Controller::HTTP_BAD_REQUEST);
            }

            // Query danh sách đơn vị - Lấy tất cả
            $this->db->select('id_don_vi, ten_don_vi, ma_don_vi, ten_viet_tat, loai, truong');
            $this->db->from('e_don_vi');
            // $this->db->where('deleted_at IS NULL');

            // Chỉ lọc theo id đơn vị cụ thể nếu có
            if ($idDonViFilter) {
                $this->db->where('id_don_vi', $idDonViFilter);
            }

            $this->db->order_by('loai', 'ASC');
            $this->db->order_by('ten_don_vi', 'ASC');

            $danhSachDonVi = $this->db->get()->result_array();

            if (empty($danhSachDonVi)) {
                resSuccess([
                    'data' => [],
                    'summary' => [
                        'tong_don_vi' => 0,
                        'tong_nhan_vien' => 0,
                        'tong_so_don' => 0,
                        'tong_so_don_da_duyet' => 0,
                        'tong_ngay_nghi' => 0
                    ],
                    'filters' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'loai' => $loaiFilter,
                        'id_don_vi' => $idDonViFilter
                    ]
                ], 'Không tìm thấy đơn vị nào', REST_Controller::HTTP_OK);
            }

            // Nhóm theo loại đơn vị -> đơn vị cụ thể -> nhân viên
            // Cấu trúc: { "KHOA_BOMON": { "KHOA_Y": [...nhan_vien], "KHOA_DUOC": [...] }, "PHONG": {...} }
            $result = [];
            $summaryTotal = [
                'tong_don_vi' => 0,
                'tong_nhan_vien' => 0,
                'tong_so_don' => 0,
                'tong_so_don_da_duyet' => 0,
                'tong_ngay_nghi' => 0
            ];

            foreach ($danhSachDonVi as $donVi) {
                $idDonVi = $donVi['id_don_vi'];
                $loai = $donVi['loai'] ?: 'KHAC';
                $truong = $donVi['truong'];

                // Nếu có truong thì ưu tiên nhóm theo truong, nếu không thì theo loai
                $nhomKey = !empty($truong) ? $truong : $loai;

                // Khởi tạo nhóm nếu chưa có (dạng array)
                if (!isset($result[$nhomKey])) {
                    $result[$nhomKey] = [];
                }

                // Query nhân viên thuộc đơn vị và thống kê đơn
                // Filter theo ngày nghỉ thực tế trong hrm_nghi_phep_chi_tiet
                $this->db->select('
                    nv.id_nhan_vien,
                    nv.ho_va_ten,
                    nv.ma_nhan_vien,
                    nv.avatar,
                    nv.gioi_tinh,
                    COUNT(DISTINCT np.id_nghi_phep) as so_don,
                    COUNT(DISTINCT CASE WHEN np.trang_thai_cap_mot = "Da_duyet" AND np.trang_thai_cap_hai = "Da_duyet" THEN np.id_nghi_phep END) as so_don_da_duyet,
                    COALESCE(SUM(ct.so_ngay_nghi), 0) as so_ngay_nghi
                ', false);

                $this->db->from('hrm_nhan_vien nv');
                $this->db->join('hrm_nghi_phep np', 'np.id_nhan_vien = nv.id_nhan_vien AND np.deleted_at IS NULL', 'left');
                $this->db->join('hrm_nghi_phep_chi_tiet ct', 'ct.id_nghi_phep = np.id_nghi_phep', 'left');

                $this->db->where('nv.id_don_vi_cong_tac', $idDonVi);
                $this->db->where('nv.deleted_at IS NULL');

                // Lọc theo ngày nghỉ thực tế trong chi tiết
                if ($startDate) {
                    $this->db->where('(np.id_nghi_phep IS NULL OR ct.ngay_nghi >= ' . $this->db->escape($startDate) . ')');
                }
                if ($endDate) {
                    $this->db->where('(np.id_nghi_phep IS NULL OR ct.ngay_nghi <= ' . $this->db->escape($endDate) . ')');
                }

                $this->db->group_by('nv.id_nhan_vien');
                $this->db->order_by('nv.ho_va_ten', 'ASC');

                $danhSachNhanVien = $this->db->get()->result_array();

                // Format dữ liệu nhân viên
                $nhanVienFormatted = [];
                $tongSoDon = 0;
                $tongSoDonDaDuyetCap2 = 0;
                $tongNgayNghi = 0;

                foreach ($danhSachNhanVien as $nv) {
                    $nhanVienFormatted[] = [
                        'id_nhan_vien' => $nv['id_nhan_vien'],
                        'ho_va_ten' => $nv['ho_va_ten'],
                        'ma_nhan_vien' => $nv['ma_nhan_vien'],
                        'avatar' => !empty($nv['avatar']) ? encryptString($nv['avatar']) : null,
                        'gioi_tinh' => $nv['gioi_tinh'],
                        'so_don' => intval($nv['so_don']),
                        'so_don_da_duyet' => intval($nv['so_don_da_duyet']),
                        'so_ngay_nghi' => floatval($nv['so_ngay_nghi'])
                    ];

                    $tongSoDon += intval($nv['so_don']);
                    $tongSoDonDaDuyetCap2 += intval($nv['so_don_da_duyet']);
                    $tongNgayNghi += floatval($nv['so_ngay_nghi']);
                }

                // Thêm đơn vị vào mảng nhóm
                $result[$nhomKey][] = [
                    'id_don_vi' => $donVi['id_don_vi'],
                    'ten_don_vi' => $donVi['ten_don_vi'],
                    'ma_don_vi' => $donVi['ma_don_vi'],
                    'ten_viet_tat' => $donVi['ten_viet_tat'],
                    'loai' => $donVi['loai'],
                    'nhan_vien' => $nhanVienFormatted,
                    'tong_nhan_vien' => count($nhanVienFormatted),
                    'tong_so_don' => $tongSoDon,
                    'tong_so_don_da_duyet' => $tongSoDonDaDuyetCap2,
                    'tong_ngay_nghi' => $tongNgayNghi
                ];

                // Cộng vào tổng
                $summaryTotal['tong_don_vi']++;
                $summaryTotal['tong_nhan_vien'] += count($nhanVienFormatted);
                $summaryTotal['tong_so_don'] += $tongSoDon;
                $summaryTotal['tong_so_don_da_duyet'] += $tongSoDonDaDuyetCap2;
                $summaryTotal['tong_ngay_nghi'] += $tongNgayNghi;
            }

            // Tính tổng theo từng loại
            $summaryByLoai = [];
            foreach ($result as $loai => $donViList) {
                $tongDonVi = 0;
                $tongNhanVien = 0;
                $tongSoDon = 0;
                $tongSoDonDaDuyetCap2 = 0;
                $tongNgayNghiLoai = 0;

                foreach ($donViList as $dv) {
                    $tongDonVi++;
                    $tongNhanVien += $dv['tong_nhan_vien'];
                    $tongSoDon += $dv['tong_so_don'];
                    $tongSoDonDaDuyetCap2 += $dv['tong_so_don_da_duyet'];
                    $tongNgayNghiLoai += $dv['tong_ngay_nghi'];
                }

                $summaryByLoai[$loai] = [
                    'tong_don_vi' => $tongDonVi,
                    'tong_nhan_vien' => $tongNhanVien,
                    'tong_so_don' => $tongSoDon,
                    'tong_so_don_da_duyet' => $tongSoDonDaDuyetCap2,
                    'tong_ngay_nghi' => $tongNgayNghiLoai
                ];
            }

            resSuccess([
                'data' => $result,
                'summary_total' => $summaryTotal,
                'summary_by_loai' => $summaryByLoai,
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'loai' => $loaiFilter,
                    'id_don_vi' => $idDonViFilter
                ]
            ], 'Lấy thống kê thành công', REST_Controller::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'Lỗi thống kê theo đơn vị: ' . $e->getMessage());
            resError([
                'message' => 'Có lỗi xảy ra khi thống kê',
                'error' => $e->getMessage()
            ], 'Lỗi xử lý', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Lấy danh sách đơn nghỉ phép của một nhân viên cụ thể (dùng trong modal chi tiết)
     * GET /api/v2/admin/hrm/nghiphep/don_by_nhan_vien/{id_nhan_vien}
     *
     * Parameters:
     * - id_nhan_vien: ID nhân viên (URL segment, bắt buộc)
     * - start_date: Lọc theo ngày nghỉ từ ngày này (Y-m-d) - optional
     * - end_date: Lọc theo ngày nghỉ đến ngày này (Y-m-d) - optional
     */
    public function don_by_nhan_vien_get($id_nhan_vien = null)
    {
        try {
            $this->getUserLogin();

            if (!$id_nhan_vien) {
                resError([], 'Vui lòng truyền id_nhan_vien trên URL', REST_Controller::HTTP_BAD_REQUEST);
            }

            $startDate = commonRequest('start_date');
            $endDate = commonRequest('end_date');

            // Validate date format
            if ($startDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
                resError(['start_date' => 'Định dạng ngày không hợp lệ (Y-m-d)'], 'Dữ liệu không hợp lệ', REST_Controller::HTTP_BAD_REQUEST);
            }
            if ($endDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                resError(['end_date' => 'Định dạng ngày không hợp lệ (Y-m-d)'], 'Dữ liệu không hợp lệ', REST_Controller::HTTP_BAD_REQUEST);
            }

            // Lấy danh sách đơn của nhân viên, có join chi tiết ngày nghỉ
            $this->db->select('
                np.id_nghi_phep,
                np.uuid_nghi_phep,
                np.ly_do_nghi,
                np.loai_nghi,
                np.trang_thai_cap_mot,
                np.trang_thai_cap_hai,
                DATE_FORMAT(np.created_at, "%d/%m/%Y") as ngay_tao,
                lp.ten_loai_phep,
                COALESCE(SUM(ct.so_ngay_nghi), 0) as tong_ngay_nghi,
                MIN(ct.ngay_nghi) as ngay_bat_dau,
                MAX(ct.ngay_nghi) as ngay_ket_thuc
            ');
            $this->db->from('hrm_nghi_phep np');
            $this->db->join('hrm_danh_muc_loai_nghi_phep lp', 'lp.id_loai_phep = np.id_loai_phep', 'left');
            $this->db->join('hrm_nghi_phep_chi_tiet ct', 'ct.id_nghi_phep = np.id_nghi_phep', 'left');
            $this->db->where('np.id_nhan_vien', $id_nhan_vien);
            $this->db->where('np.deleted_at IS NULL');

            // Lọc theo ngày nghỉ thực tế trong chi tiết
            if ($startDate) {
                $this->db->where('(ct.ngay_nghi IS NULL OR ct.ngay_nghi >= ' . $this->db->escape($startDate) . ')');
            }
            if ($endDate) {
                $this->db->where('(ct.ngay_nghi IS NULL OR ct.ngay_nghi <= ' . $this->db->escape($endDate) . ')');
            }

            $this->db->group_by('np.id_nghi_phep');
            $this->db->order_by('ngay_bat_dau', 'DESC');

            $danhSachDon = $this->db->get()->result_array();

            // Format kết quả
            $formatted = [];
            foreach ($danhSachDon as $don) {
                // Xác định trạng thái hiển thị
                if ($don['trang_thai_cap_mot'] === 'Da_duyet' && $don['trang_thai_cap_hai'] === 'Da_duyet') {
                    $trangThai = 'Đã duyệt';
                    $trangThaiColor = 'success';
                } elseif ($don['trang_thai_cap_mot'] === 'Tu_choi' || $don['trang_thai_cap_hai'] === 'Tu_choi') {
                    $trangThai = 'Từ chối';
                    $trangThaiColor = 'danger';
                } elseif ($don['trang_thai_cap_mot'] === 'Da_duyet') {
                    $trangThai = 'Chờ TC-HC';
                    $trangThaiColor = 'warning';
                } else {
                    $trangThai = 'Chờ duyệt';
                    $trangThaiColor = 'default';
                }

                // Format khoảng ngày nghỉ
                $ngayBatDau = $don['ngay_bat_dau'];
                $ngayKetThuc = $don['ngay_ket_thuc'];
                if ($ngayBatDau && $ngayKetThuc) {
                    if ($ngayBatDau === $ngayKetThuc) {
                        $thoiGian = date('d/m/Y', strtotime($ngayBatDau));
                    } else {
                        $thoiGian = date('d/m', strtotime($ngayBatDau)) . ' - ' . date('d/m/Y', strtotime($ngayKetThuc));
                    }
                } else {
                    $thoiGian = '';
                }

                $formatted[] = [
                    'uuid_nghi_phep' => $don['uuid_nghi_phep'],
                    'ly_do_nghi' => $don['ly_do_nghi'],
                    'ten_loai_phep' => $don['ten_loai_phep'] ?: 'Chưa xác định',
                    'tong_ngay_nghi' => floatval($don['tong_ngay_nghi']),
                    'thoi_gian' => $thoiGian,
                    'ngay_bat_dau' => $ngayBatDau,
                    'ngay_ket_thuc' => $ngayKetThuc,
                    'ngay_tao' => $don['ngay_tao'],
                    'trang_thai' => $trangThai,
                    'trang_thai_color' => $trangThaiColor,
                    'trang_thai_cap_mot' => $don['trang_thai_cap_mot'],
                    'trang_thai_cap_hai' => $don['trang_thai_cap_hai'],
                ];
            }

            resSuccess([
                'data' => $formatted,
                'total' => count($formatted),
                'filters' => [
                    'id_nhan_vien' => $id_nhan_vien,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]
            ], 'Lấy danh sách đơn thành công', REST_Controller::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'Lỗi lấy đơn theo nhân viên: ' . $e->getMessage());
            resError([
                'message' => 'Có lỗi xảy ra',
                'error' => $e->getMessage()
            ], 'Lỗi xử lý', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Đồng bộ người duyệt cấp 1 của một đơn nghỉ phép với bảng e_lanh_dao_don_vi
     *
     * URL:
     *   GET /api/v2/admin/hrm/nghiphep/sync/{uuid}
     *   GET /api/v2/admin/hrm/nghiphep/sync/{uuid}?id_don_vi=5
     *   GET /api/v2/admin/hrm/nghiphep/sync/{uuid}?kiem_thu=1
     *
     * Params:
     *   - uuid      (URL segment, bắt buộc) : UUID của đơn nghỉ phép cần đồng bộ
     *   - id_don_vi (query string, tuỳ chọn): Khi NV vừa chuyển đơn vị — đồng bộ theo đơn vị MỚI này
     *                                          Nếu không truyền → tự lấy đơn vị hiện tại của NV trong DB
     *   - kiem_thu  (query string, tuỳ chọn): 1 = kiểm thử, xem trước kết quả, không thay đổi DB
     *
     * Logic so sánh (chỉ đồng bộ cap_duyet = 1):
     *   - THÊM : có trong e_lanh_dao_don_vi nhưng chưa có trong hrm_nghi_phep_nguoi_duyet
     *   - GỠ   : có trong hrm_nghi_phep_nguoi_duyet nhưng không còn là lãnh đạo đơn vị (và chưa duyệt)
     *   - GIỮ  : đã duyệt → bảo toàn lịch sử, không gỡ dù lãnh đạo đã thay đổi
     */

    public function sync_get($uuid = null)
    {
        $auth = $this->getUserLogin();

        if (!$uuid) {
            resError([], 'Vui lòng truyền uuid đơn nghỉ phép trên URL: /nghiphep/sync/{uuid}', REST_Controller::HTTP_BAD_REQUEST);
        }

        $idDonViMoi = commonRequest('id_don_vi'); // null nếu không truyền
        $kiemThu = (int) commonRequest('kiem_thu'); // 1 = kiểm thử, không chạy thật

        // --- Lấy thông tin đơn + đơn vị công tác hiện tại của nhân viên ---
        $don = $this->db
            ->select('np.id_nghi_phep, np.uuid_nghi_phep, np.trang_thai_cap_mot, nv.id_don_vi_cong_tac, dv.ten_don_vi')
            ->from('hrm_nghi_phep np')
            ->join('hrm_nhan_vien nv', 'nv.id_nhan_vien = np.id_nhan_vien', 'inner')
            ->join('e_don_vi dv', 'dv.id_don_vi = nv.id_don_vi_cong_tac', 'left')
            ->where('np.uuid_nghi_phep', $uuid)
            ->where('np.deleted_at IS NULL')
            ->get()
            ->row_array();

        if (!$don) {
            resError([], 'Không tìm thấy đơn nghỉ phép: ' . $uuid, REST_Controller::HTTP_NOT_FOUND);
        }

        $idNghiPhep = $don['id_nghi_phep'];

        // Xác định đơn vị để tra lãnh đạo
        if ($idDonViMoi) {
            // TH1: NV chuyển đơn vị → dùng đơn vị được truyền vào
            $idDonViDungde = (int) $idDonViMoi;
            $donViInfo = $this->db
                ->select('ten_don_vi')
                ->where('id_don_vi', $idDonViDungde)
                ->get('e_don_vi')
                ->row_array();
            $tenDonVi = $donViInfo ? $donViInfo['ten_don_vi'] : 'Đơn vị #' . $idDonViDungde;
            $lyDo = 'Nhân viên chuyển đơn vị';
        } else {
            // TH2: Không truyền → dùng đơn vị hiện tại của NV trong DB
            $idDonViDungde = $don['id_don_vi_cong_tac'];
            $tenDonVi = $don['ten_don_vi'] ?? 'N/A';
            $lyDo = 'Kiểm tra & bổ sung người duyệt';
        }

        // --- Lãnh đạo cấp 1 của đơn vị cần đồng bộ ---
        $dsLanhDao = $this->db
            ->select('ql_nguoi_dung_id')
            ->where('id_don_vi', $idDonViDungde)
            ->where('deleted_at IS NULL')
            ->get('e_lanh_dao_don_vi')
            ->result_array();
        $ldDungDe = array_column($dsLanhDao, 'ql_nguoi_dung_id');

        // --- Người duyệt cấp 1 đang có trong bảng ---
        $nguoiDuyetHienCo = $this->db
            ->select('id_nghi_phep_nguoi_duyet, id_nguoi_duyet, da_duyet')
            ->where('id_nghi_phep', $idNghiPhep)
            ->where('cap_duyet', 1)
            ->get('hrm_nghi_phep_nguoi_duyet')
            ->result_array();
        $idNguoiDuyetHienCo = array_column($nguoiDuyetHienCo, 'id_nguoi_duyet');

        // --- So sánh ---
        $them = []; // cần thêm: có trong lãnh đạo đúng nhưng chưa có trong bảng
        $go = []; // cần gỡ:  có trong bảng nhưng không phải lãnh đạo đúng (và chưa duyệt)
        $skip = []; // đã duyệt → giữ nguyên dù sai (bảo toàn lịch sử)

        foreach ($nguoiDuyetHienCo as $nd) {
            if (!in_array($nd['id_nguoi_duyet'], $ldDungDe)) {
                if ($nd['da_duyet'] == 1) {
                    $skip[] = $nd['id_nguoi_duyet'];
                } else {
                    $go[] = $nd;
                }
            }
        }
        foreach ($ldDungDe as $ldId) {
            if (!in_array($ldId, $idNguoiDuyetHienCo)) {
                $them[] = $ldId;
            }
        }

        $ghiChu = [];
        $ghiChu[] = $lyDo . ' — Đơn vị: ' . $tenDonVi;
        if (!empty($skip)) {
            $ghiChu[] = 'Giữ ' . count($skip) . ' người đã duyệt (không gỡ để bảo toàn lịch sử)';
        }
        if (empty($ldDungDe)) {
            $ghiChu[] = 'CẢNH BÁO: Đơn vị "' . $tenDonVi . '" chưa có lãnh đạo trong hệ thống';
        }

        // --- Thực thi ---
        if (!$kiemThu) {
            $this->db->trans_start();

            foreach ($go as $ndGo) {
                $this->db
                    ->where('id_nghi_phep_nguoi_duyet', $ndGo['id_nghi_phep_nguoi_duyet'])
                    ->delete('hrm_nghi_phep_nguoi_duyet');
            }
            foreach ($them as $ldId) {
                $this->db->insert('hrm_nghi_phep_nguoi_duyet', [
                    'id_nghi_phep' => $idNghiPhep,
                    'id_nguoi_duyet' => $ldId,
                    'cap_duyet' => 1,
                    'da_duyet' => 0,
                    'thoi_gian_duyet' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                resError([], 'Đồng bộ thất bại, đã rollback', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        resSuccess(
            [
                'kiem_thu' => (bool) $kiemThu,
                'uuid' => $uuid,
                'id_nghi_phep' => $idNghiPhep,
                'don_vi_dong_bo' => $tenDonVi,
                'id_don_vi_dong_bo' => $idDonViDungde,
                'trang_thai_cap_mot' => $don['trang_thai_cap_mot'],
                'them_moi' => $them,
                'go_bo' => array_column($go, 'id_nguoi_duyet'),
                'giu_nguyen' => $skip,
                'ghi_chu' => $ghiChu,
            ],
            $kiemThu
            ? 'Kiểm thử: chưa thay đổi DB'
            : 'Đồng bộ hoàn tất / Thêm ' . count($them) . ' / Gỡ ' . count($go),
            REST_Controller::HTTP_OK
        );
    }


    /**
     * API Phúc khảo: Nhân viên yêu cầu xem xét lại đơn đã bị từ chối
     * POST /api/v2/admin/hrm/nghiphep/submit_phuc_khao
     *
     * Body params:
     *   - uuid_nghi_phep  (string, bắt buộc): UUID của đơn nghỉ phép
     *   - ly_do_nghi      (string, tuỳ chọn): Lý do mới (nếu muốn sửa)
     *   - danh_sach_ngay_nghi (array, tuỳ chọn): Danh sách ngày nghỉ mới
     *     Mỗi phần tử: { ngay_nghi: 'YYYY-MM-DD', sang: bool, chieu: bool }
     */
    public function submit_phuc_khao_post()
    {
        // ======================================================
        // 1. LẤY THÔNG TIN USER ĐANG ĐĂNG NHẬP
        // ======================================================
        $auth           = $this->getUserLogin();
        $qlNguoiDungId  = $auth['ql_nguoi_dung_id'];

        // ======================================================
        // 2. NHẬN DỮ LIỆU TỪ REQUEST
        // ======================================================
        $uuidNghiPhep     = commonRequest('uuid_nghi_phep');
        $lyDoMoi          = commonRequest('ly_do_nghi');
        $danhSachNgayNghi = commonRequest('danh_sach_ngay_nghi'); // tuỳ chọn

        // Xử lý upload minh chứng mới nếu có
        $minhChungUrl = null;
        if (!empty($_FILES['minh_chung']['name'])) {
            $this->load->helper('file');
            $uploadRes = $this->HrmBaseModel->uploadHrmNghiPhep('minh_chung');
            if (!$uploadRes['success']) {
                resError([], 'Lỗi upload minh chứng: ' . $uploadRes['message']);
            }
            $minhChungUrl = ltrim($uploadRes['url_full'], '/');
        }

        // Validate đầu vào cơ bản
        if (empty($uuidNghiPhep)) {
            resError(['uuid_nghi_phep' => 'Vui lòng truyền UUID đơn nghỉ phép'], 'Dữ liệu không hợp lệ', REST_Controller::HTTP_BAD_REQUEST);
        }

        // Parse danh sách ngày nghỉ nếu được truyền dưới dạng JSON string
        if (is_string($danhSachNgayNghi)) {
            $danhSachNgayNghi = json_decode($danhSachNgayNghi, true);
        }

        // ======================================================
        // 3. LẤY THÔNG TIN ĐƠN VÀ NHÂN VIÊN TỪ DB
        // ======================================================
        $donHienTai = $this->db
            ->where('uuid_nghi_phep', $uuidNghiPhep)
            ->where('deleted_at IS NULL')
            ->get('hrm_nghi_phep')
            ->row_array();

        if (!$donHienTai) {
            resError([], 'Không tìm thấy đơn nghỉ phép!', REST_Controller::HTTP_NOT_FOUND);
        }

        $idNghiPhep = $donHienTai['id_nghi_phep'];
        $idNhanVien = $donHienTai['id_nhan_vien'];

        // Lấy nhân viên của user đang đăng nhập để kiểm tra quyền sở hữu
        $nhanVienLogin = $this->Hrm_nhan_vien_model
            ->select('hrm_nhan_vien.id_nhan_vien')
            ->where('hrm_nhan_vien.ql_nguoi_dung_id', $qlNguoiDungId)
            ->first();

        // Chỉ chủ đơn mới được phúc khảo
        if (!$nhanVienLogin || $nhanVienLogin['id_nhan_vien'] != $idNhanVien) {
            resError([], 'Bạn không có quyền phúc khảo đơn này', REST_Controller::HTTP_FORBIDDEN);
        }

        // ======================================================
        // 4. TRẠM KIỂM ĐỊNH (VALIDATION)
        // ======================================================

        // Trạm 1: Đơn phải đã được duyệt đủ CẢ 2 cấp (Da_duyet)
        $trangThaiCap1 = $donHienTai['trang_thai_cap_mot'];
        $trangThaiCap2 = $donHienTai['trang_thai_cap_hai'];
        $daDuyetCa2Cap = ($trangThaiCap1 === 'Da_duyet' && $trangThaiCap2 === 'Da_duyet');

        if (!$daDuyetCa2Cap) {
            resError(
                ['trang_thai_cap_mot' => $trangThaiCap1, 'trang_thai_cap_hai' => $trangThaiCap2],
                'Chỉ có thể phúc khảo đơn đã được duyệt đủ cả 2 cấp (Cấp đơn vị & Cấp tổ chức)',
                REST_Controller::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // Trạm 2: Đơn này đã hết lượt phúc khảo chưa? (Tối đa 1 lần/đơn)
        if (intval($donHienTai['so_lan_phuc_khao']) >= 1) {
            resError(
                ['so_lan_phuc_khao' => $donHienTai['so_lan_phuc_khao']],
                'Đơn này đã hết lượt phúc khảo (Tối đa 1 lần/đơn)',
                REST_Controller::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // Trạm 3: Kiểm tra hạn mức phúc khảo trong năm (Tối đa 5 lần/năm/nhân viên)
        $namHienTai = date('Y');
        $soLanDaPhucKhaoTrongNam = $this->db
            ->where('id_nhan_vien', $idNhanVien)
            ->where('so_lan_phuc_khao >', 0)
            ->where('YEAR(created_at)', $namHienTai)
            ->where('deleted_at IS NULL')
            ->count_all_results('hrm_nghi_phep');

        if ($soLanDaPhucKhaoTrongNam >= 5) {
            resError(
                ['so_lan_trong_nam' => $soLanDaPhucKhaoTrongNam],
                'Bạn đã sử dụng hết 5 lượt phúc khảo trong năm ' . $namHienTai . '!',
                REST_Controller::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // ======================================================
        // 5. BẮT ĐẦU TRANSACTION CẬP NHẬT DATABASE
        // ======================================================
        $this->db->trans_start();

        try {
            // --- 5a. Cập nhật bảng CHÍNH: đưa về Chờ duyệt ---
            $dataCapNhat = [
                'so_lan_phuc_khao'   => 1,
                'thoi_gian_phuc_khao' => date('Y-m-d H:i:s'),
                'trang_thai_cap_mot'  => 'Cho_duyet',
                'trang_thai_cap_hai'  => 'Cho_duyet',
                'updated_user_id'     => $qlNguoiDungId,
            ];

            // Cập nhật lý do mới nếu nhân viên muốn thay đổi
            if (!empty($lyDoMoi)) {
                $dataCapNhat['ly_do_nghi'] = $lyDoMoi;
            }
            if ($minhChungUrl) {
                $dataCapNhat['minh_chung'] = $minhChungUrl;
            }

            $this->db->where('id_nghi_phep', $idNghiPhep)
                ->update('hrm_nghi_phep', $dataCapNhat);

            // --- 5b. Cập nhật bảng CHI TIẾT nếu có thay đổi ngày nghỉ ---
            if (!empty($danhSachNgayNghi) && is_array($danhSachNgayNghi)) {
                // Xóa lịch cũ
                $this->db->where('id_nghi_phep', $idNghiPhep)
                    ->delete('hrm_nghi_phep_chi_tiet');

                // Gộp buổi sáng/chiều tránh trùng lặp
                $ngayNghiMerged = [];
                foreach ($danhSachNgayNghi as $item) {
                    if (is_object($item)) {
                        $item = (array) $item;
                    }
                    $ngay = $item['ngay_nghi'] ?? null;
                    if (!$ngay) continue;

                    if (!isset($ngayNghiMerged[$ngay])) {
                        $ngayNghiMerged[$ngay] = ['sang' => false, 'chieu' => false];
                    }
                    if (!empty($item['sang']))  $ngayNghiMerged[$ngay]['sang']  = true;
                    if (!empty($item['chieu'])) $ngayNghiMerged[$ngay]['chieu'] = true;
                }

                $insertChiTiet = [];
                foreach ($ngayNghiMerged as $ngay => $buoi) {
                    if ($buoi['sang']) {
                        $insertChiTiet[] = [
                            'id_nghi_phep' => $idNghiPhep,
                            'ngay_nghi'    => $ngay,
                            'buoi_nghi'    => 'Sang',
                            'so_ngay_nghi' => 0.5,
                        ];
                    }
                    if ($buoi['chieu']) {
                        $insertChiTiet[] = [
                            'id_nghi_phep' => $idNghiPhep,
                            'ngay_nghi'    => $ngay,
                            'buoi_nghi'    => 'Chieu',
                            'so_ngay_nghi' => 0.5,
                        ];
                    }
                }

                if (!empty($insertChiTiet)) {
                    $this->db->insert_batch('hrm_nghi_phep_chi_tiet', $insertChiTiet);

                    // Tính tổng ngày nghỉ (chỉ để ghi log)
                    $tongSoNgay = array_sum(array_column($insertChiTiet, 'so_ngay_nghi'));
                }
            }

            // --- 5c. Reset bảng NGƯỜI DUYỆT (cấp 1 và cấp 2 về Chờ duyệt) ---
            $this->db->where('id_nghi_phep', $idNghiPhep)
                ->update('hrm_nghi_phep_nguoi_duyet', [
                    'da_duyet'       => 0,
                    'ly_do'          => null,
                    'thoi_gian_duyet' => null,
                ]);

            // --- 5d. Ghi LOG HÀNH ĐỘNG phúc khảo ---
            $logText = 'Nhân viên yêu cầu xem xét lại đơn (phúc khảo)';
            if (!empty($danhSachNgayNghi) && is_array($danhSachNgayNghi)) {
                $thongTinNgay = [];
                foreach ($ngayNghiMerged as $ngay => $buoi) {
                    $buoiHienThi = [];
                    if ($buoi['sang']) $buoiHienThi[] = 'Sáng';
                    if ($buoi['chieu']) $buoiHienThi[] = 'Chiều';
                    $thongTinNgay[] = date('d/m/Y', strtotime($ngay)) . ' (' . implode(', ', $buoiHienThi) . ')';
                }
                // Array sum đã tính ở trên, nếu ko có thì dùng biến khác nhưng ở block trước đã chạy nên an toàn
                $tongSoNgayTxt = isset($tongSoNgay) ? ' (Tổng: ' . $tongSoNgay . ' ngày)' : '';
                $logText .= '. Chi tiết cập nhật lịch nghỉ: ' . implode('; ', $thongTinNgay) . $tongSoNgayTxt;
            }
            if (!empty($lyDoMoi)) {
                $logText .= '. Lý do: ' . $lyDoMoi;
            }

            $this->db->insert('hrm_nghi_phep_log_duyet', [
                'id_nghi_phep'      => $idNghiPhep,
                'cap_duyet'         => 0, // 0 = hành động của người nộp đơn
                'trang_thai_cu'     => $trangThaiCap1 === 'Tu_choi' ? 'Tu_choi' : $trangThaiCap2,
                'trang_thai_moi'    => 'Cho_duyet',
                'hanh_dong'         => 'phuc_khao',
                'id_nguoi_duyet'    => $qlNguoiDungId,
                'ly_do'             => $logText,
                'thoi_gian_thay_doi' => date('Y-m-d H:i:s'),
            ]);

            // ======================================================
            // 6. KẾT THÚC TRANSACTION
            // ======================================================
            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                resError([], 'Lỗi hệ thống khi lưu phúc khảo. Vui lòng thử lại!', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Trả về thông tin đơn đã cập nhật
            $donMoi = $this->db
                ->select('np.*, nv.ho_va_ten, nv.ma_nhan_vien')
                ->from('hrm_nghi_phep np')
                ->join('hrm_nhan_vien nv', 'nv.id_nhan_vien = np.id_nhan_vien', 'left')
                ->where('np.id_nghi_phep', $idNghiPhep)
                ->get()
                ->row_array();

            $donMoi = $this->Hrm_nghi_phep_model->formatNghiPhep($donMoi);

            resSuccess(
                $donMoi,
                'Gửi yêu cầu phúc khảo thành công! Lãnh đạo sẽ xem xét lại đơn của bạn.',
                REST_Controller::HTTP_OK
            );
        } catch (Exception $e) {
            $this->db->trans_rollback();
            resError(
                ['exception' => $e->getMessage()],
                'Có lỗi xảy ra khi xử lý phúc khảo',
                REST_Controller::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function view_log_get()
    {
        $searchValue = commonRequest('searchValue');
        $start = (int)(commonRequest('start') ?? 0);
        $length = (int)(commonRequest('length') ?? 100);

        $this->db
            ->select('ql_nhat_ky.ql_nhat_ky_id, ql_nhat_ky.ql_nhat_ky_hanh_dong as hanh_dong, ql_nhat_ky.ql_nhat_ky_noi_dung as noi_dung, ql_nhat_ky.ql_nhat_ky_gia_tri_cu, ql_nhat_ky.ql_nhat_ky_gia_tri_moi, ql_nhat_ky.ql_nhat_ky_bang_du_lieu as bang_du_lieu, ql_nhat_ky.ql_nhat_ky_controller, ql_nhat_ky.ql_nhat_ky_ngay_tao as thoi_gian, ql_nguoi_dung.ql_nguoi_dung_id, ql_nguoi_dung.ql_nguoi_dung_ho_ten as ten_nguoi_thuc_hien, ql_nguoi_dung.ql_nguoi_dung_email as email_nguoi_thuc_hien')
            ->from('ql_nhat_ky')
            ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = ql_nhat_ky.ql_nguoi_dung_id', 'left')
            ->where('ql_nhat_ky_bang_du_lieu', 'hrm_nghi_phep');

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('ql_nhat_ky.ql_nhat_ky_hanh_dong', $searchValue);
            $this->db->or_like('ql_nhat_ky.ql_nhat_ky_noi_dung', $searchValue);
            $this->db->or_like('ql_nguoi_dung.ql_nguoi_dung_ho_ten', $searchValue);
            $this->db->or_like('ql_nguoi_dung.ql_nguoi_dung_email', $searchValue);
            $this->db->group_end();
        }

        $countQuery = clone $this->db;
        $total = $countQuery->count_all_results('', false);

        $this->db->order_by('ql_nhat_ky.ql_nhat_ky_ngay_tao', 'DESC');
        if ($length > 0) $this->db->limit($length, $start);
        
        $log = $this->db->get()->result_array();

        $hanhDongMap = [
            'create' => 'Tạo đơn mới',
            'update' => 'Cập nhật',
            'delete' => 'Xóa',
            'approve' => 'Duyệt',
            'reject' => 'Từ chối',
        ];

        $data = array_map(function ($item) use ($hanhDongMap) {
            $hanhDong = $item['hanh_dong'];
            $hanhDongLabel = isset($hanhDongMap[$hanhDong]) ? $hanhDongMap[$hanhDong] : $hanhDong;
            
            $chi_tiet = [];
            if (!empty($item['ql_nhat_ky_gia_tri_cu']) && $item['ql_nhat_ky_gia_tri_cu'] !== 'null') {
                $decoded = json_decode($item['ql_nhat_ky_gia_tri_cu'], true);
                if (is_array($decoded)) {
                    foreach ($decoded as $k => $v) {
                        $chi_tiet[$k] = ['cu' => $v, 'moi' => null];
                    }
                }
            }
            if (!empty($item['ql_nhat_ky_gia_tri_moi']) && $item['ql_nhat_ky_gia_tri_moi'] !== 'null') {
                $decoded = json_decode($item['ql_nhat_ky_gia_tri_moi'], true);
                if (is_array($decoded)) {
                    foreach ($decoded as $k => $v) {
                        if (!isset($chi_tiet[$k])) {
                            $chi_tiet[$k] = ['cu' => null, 'moi' => $v];
                        } else {
                            $chi_tiet[$k]['moi'] = $v;
                        }
                    }
                }
            }
            
            $item['hanh_dong'] = $hanhDongLabel;
            $item['chi_tiet'] = $chi_tiet;
            $item['id'] = $item['ql_nhat_ky_id'];
            return $item;
        }, $log);

        resSuccess($data, 'Lấy lịch sử nghỉ phép thành công', REST_Controller::HTTP_OK, true, [
            'total' => $total,
        ]);
    }
}
