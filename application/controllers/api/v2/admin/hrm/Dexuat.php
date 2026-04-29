<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Dx_de_xuat_model $Dx_de_xuat_model
 * @property Dx_nguoi_duyet_de_xuat_model $Dx_nguoi_duyet_de_xuat_model
 * @property Dx_binh_luan_model $Dx_binh_luan_model
 * @property Dx_file_dinh_kem_model $Dx_file_dinh_kem_model
 * @property Dx_binh_luan_log_model $Dx_binh_luan_log_model
 * @property Dx_loai_de_xuat_don_vi_model $Dx_loai_de_xuat_don_vi_model
 * @property Dx_de_xuat_gan_sao_model $Dx_de_xuat_gan_sao_model
 * @property Dx_de_xuat_quan_trong_model $Dx_de_xuat_quan_trong_model
 * @property Fileupload $fileupload
 * @property Common $common
 */



class Dexuat extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');

        $this->load->model([
            'Hrm_nghi_phep_model',
            'Hrm_nhan_vien_model',
            'Dx_de_xuat_model',
            'Dx_nguoi_duyet_de_xuat_model',
            'Dx_binh_luan_model',
            'Dx_file_dinh_kem_model',
            'Dx_binh_luan_log_model',
            'Dx_loai_de_xuat_don_vi_model',
            'Ql_nhat_ky_model',
            // 'Dx_de_xuat_gan_sao_model',
            // 'Dx_de_xuat_quan_trong_model'
        ]);
        $this->load->library(['Validator', 'Fileupload', 'Pxl']);
        $this->linkDuyet = $this->config->item('frontend_v2') . 'hrm/nghi-phep/duyet/';
    }

    /**
     * Lấy danh sách đề xuất
     * GET /api/v2/admin/hrm/dexuat
     */
    public function index_get()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Không có quyền truy cập', 401);
        }

        $loai_de_xuat = commonRequest('chon_loai_de_xuat');
        if ($loai_de_xuat) {
            $loaiDeXuatDonVi = $this->Dx_loai_de_xuat_don_vi_model->getCacCapTrinhKy($loai_de_xuat, $auth);

            if (!empty($loaiDeXuatDonVi)) {
                foreach ($loaiDeXuatDonVi as &$step) {
                    if (!empty($step['lanh_dao_don_vi'])) {
                        foreach ($step['lanh_dao_don_vi'] as &$ld) {
                            if (!empty($ld['ql_nguoi_dung_avatar']) && !filter_var($ld['ql_nguoi_dung_avatar'], FILTER_VALIDATE_URL)) {
                                $ld['ql_nguoi_dung_avatar'] = encryptString($ld['ql_nguoi_dung_avatar']);
                            }
                        }
                    }
                    if (!empty($step['nhan_su'])) {
                        foreach ($step['nhan_su'] as &$ns) {
                            if (!empty($ns['ql_nguoi_dung_avatar']) && !filter_var($ns['ql_nguoi_dung_avatar'], FILTER_VALIDATE_URL)) {
                                $ns['ql_nguoi_dung_avatar'] = encryptString($ns['ql_nguoi_dung_avatar']);
                            }
                        }
                    }
                }
            }

            resSuccess($loaiDeXuatDonVi);
        }

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
        $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
            'order' => commonRequest('order'),
            'columns' => commonRequest('columns')
        ] : [];

        // Lấy thông tin user hiện tại
        $auth = $this->getUserLogin();
        $qlNguoiDungId = $auth['ql_nguoi_dung_id'];


        // Lấy danh sách đề xuất
        $result = $this->Dx_de_xuat_model->getDanhSachDeXuat(
            $qlNguoiDungId,
            $start,
            $length,
            $searchValue,
            $searchKey,
            $orderBy
        );

        $this->response([
            'status' => true,
            'message' => 'Lấy danh sách đề xuất thành công',
            'data' => $result['data'],
            'sql' => $result['sql'],
            'recordsTotal' => $result['recordsTotal'],
            'recordsFiltered' => $result['recordsFiltered']
        ], REST_Controller::HTTP_OK);
    }

    /**
     * Lấy chi tiết đề xuất
     * GET /api/v2/admin/hrm/dexuat/detail/:id
     */
    public function detail_get($idDeXuat = null)
    {
        if (!$idDeXuat) {
            $this->response([
                'status' => false,
                'message' => 'Thiếu ID đề xuất'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $auth = $this->getUserLogin();
        $qlNguoiDungId = $auth['ql_nguoi_dung_id'];

        $deXuat = $this->Dx_de_xuat_model->getChiTietDeXuat($idDeXuat, $qlNguoiDungId);

        if (!$deXuat) {
            $this->response([
                'status' => false,
                'message' => 'Không tìm thấy đề xuất hoặc bạn không có quyền xem'
            ], REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        $this->response([
            'status' => true,
            'message' => 'Lấy chi tiết đề xuất thành công',
            'data' => $deXuat
        ], REST_Controller::HTTP_OK);
    }

    /**
     * Tạo đề xuất mới
     * POST /api/v2/admin/hrm/dexuat/create
     */
    public function create_post()
    {
        $auth = $this->getUserLogin();
        $qlNguoiDungId = $auth['ql_nguoi_dung_id'];

        $tieuDe = commonRequest('tieu_de');
        $noiDung = commonRequest('noi_dung');
        $id_loai_de_xuat = commonRequest('id_dx_loai_de_xuat');
        $id_nguoi_de_xuat = commonRequest('id_nguoi_de_xuat');
        $trangThai = commonRequest('trang_thai') ?: 'nhap'; // Mặc định là 'nhap'
        $danhSachDonVi = commonRequest('danh_sach_don_vi');

        // Kiểm tra loại đề xuất tồn tại
        if ($id_loai_de_xuat) {
            $loaiDeXuat = $this->db->where('id_dx_loai_de_xuat', $id_loai_de_xuat)->get('dx_loai_de_xuat')->row_array();

            if (!$loaiDeXuat) {
                $this->response([
                    'status' => false,
                    'message' => 'Không tìm thấy loại đề xuất'
                ], REST_Controller::HTTP_NOT_FOUND);
                return;
            }

            // if (($loaiDeXuat['chon_don_vi'] == 1) || ($loaiDeXuat['chon_don_vi'] == 3) || ($loaiDeXuat['chon_don_vi'] == 2 && isset($danhSachDonVi))) {

            // Parse JSON nếu là string hoặc convert stdClass thành array
            if (is_string($danhSachDonVi)) {
                $danhSachDonVi = json_decode($danhSachDonVi, true);
            } elseif (is_object($danhSachDonVi) || (is_array($danhSachDonVi) && isset($danhSachDonVi[0]) && is_object($danhSachDonVi[0]))) {
                // Convert stdClass objects to arrays
                $danhSachDonVi = json_decode(json_encode($danhSachDonVi), true);
            }

            // Validate
            if (!is_array($danhSachDonVi) || empty($danhSachDonVi)) {
                $this->response([
                    'status' => false,
                    'message' => 'Danh sách đơn vị không hợp lệ'
                ], REST_Controller::HTTP_BAD_REQUEST);
                return;
            }
            // }
        }

        // Validate trạng thái
        $validStatuses = ['nhap', 'dang_xu_ly'];
        if (!in_array($trangThai, $validStatuses)) {
            $this->response([
                'status' => false,
                'message' => 'Trạng thái không hợp lệ. Chỉ chấp nhận: nhap, dang_xu_ly'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }


        // Xác định cột nhap dựa vào trang_thai
        // trang_thai = 'nhap': Nháp chưa gửi → nhap = 1
        // trang_thai = 'dang_xu_ly': Đã gửi → nhap = 0
        $nhap = ($trangThai == 'nhap') ? 1 : 0;

        $this->db->trans_start();

        // Tạo đề xuất
        $data = [
            'tieu_de' => $tieuDe,
            'noi_dung' => $noiDung,
            'id_dx_loai_de_xuat' => $id_loai_de_xuat,
            'id_nguoi_de_xuat' => $id_nguoi_de_xuat,
            'nhap' => $nhap,
            'trang_thai' => $trangThai
        ];

        $idDeXuat = $this->Dx_de_xuat_model->taoDeXuat($data, $qlNguoiDungId);

        $danhSachDonViMerge = $danhSachDonVi;
        usort($danhSachDonViMerge, function ($a, $b) {
            return $a['cap'] <=> $b['cap'];
        });

        // Xử lý trùng lặp
        $donViDaCo = [];
        $danhSachDonViUnique = [];
        foreach ($danhSachDonViMerge as $item) {
            if (!isset($donViDaCo[$item['id_don_vi']])) {
                $donViDaCo[$item['id_don_vi']] = true;
                $danhSachDonViUnique[] = $item;
            }
        }

        usort($danhSachDonViUnique, function ($a, $b) {
            return $a['cap'] <=> $b['cap'];
        });

        $groupedByCap = [];

        foreach ($danhSachDonViUnique as $item) {
            $groupedByCap[$item['cap']][] = $item;
        }

        ksort($groupedByCap);

        $danhSachDonViReset = [];
        $newCap = 1;

        foreach ($groupedByCap as $itemsTrongCap) {
            foreach ($itemsTrongCap as $item) {
                $item['cap'] = $newCap;
                $danhSachDonViReset[] = $item;
            }
            $newCap++;
        }

        // Kiểm tra lại mảng
        // foreach ($danhSachDonViReset as &$donVi) {
        //     $dv = $this->E_don_vi_model->find($donVi['id_don_vi']);
        //     $donVi['ten_don_vi'] = $dv['ten_don_vi'];

        //     if (empty($donVi['ids_nguoi_duyet'])) {
        //         $lanhDaoDonVi = $this->db
        //             ->select('ql_nguoi_dung_id')
        //             ->from('e_lanh_dao_don_vi')
        //             ->where('id_don_vi', $donVi['id_don_vi'])
        //             ->get()
        //             ->result_array();
        //         $donVi['ids_nguoi_duyet'] = array_column($lanhDaoDonVi, 'ql_nguoi_dung_id');
        //     }
        // }
        // resError('Test', 500, $danhSachDonViReset);
        // END Kiểm tra lại mảng

        foreach ($danhSachDonViReset as $donVi) {
            $idDonVi = $donVi['id_don_vi'];
            $idsNguoiDuyet = isset($donVi['ids_nguoi_duyet']) ? $donVi['ids_nguoi_duyet'] : [];
            if (empty($idsNguoiDuyet)) {
                $lanhDaoDonVi = $this->db
                    ->select('ql_nguoi_dung_id')
                    ->from('e_lanh_dao_don_vi')
                    ->where('id_don_vi', $donVi['id_don_vi'])
                    ->get()
                    ->result_array();
                $idsNguoiDuyet = array_column($lanhDaoDonVi, 'ql_nguoi_dung_id');
            }

            // Xác định cấp duyệt: ưu tiên lấy từ 'cap' (0-based -> +1), nếu không có thì tự tăng
            if (isset($donVi['cap']) && is_numeric($donVi['cap'])) {
                $capDuyet = (int) $donVi['cap'];
            }

            if (!empty($idsNguoiDuyet) && is_array($idsNguoiDuyet)) {
                // Nếu có người duyệt: lưu từng người trong đơn vị với cấp 1
                foreach ($idsNguoiDuyet as $idNguoiDuyet) {
                    $existEmployee = $this->Hrm_nhan_vien_model->where('ql_nguoi_dung_id', $idNguoiDuyet)->get();
                    if (empty($existEmployee)) {
                        continue;
                    }

                    $this->Dx_nguoi_duyet_de_xuat_model->themNguoiDuyetTheoDonVi(
                        $idDeXuat,
                        $idDonVi,
                        $idNguoiDuyet,
                        $capDuyet,
                        $qlNguoiDungId
                    );
                }
            } else {
                // Nếu không có người duyệt: lưu chỉ đơn vị với cấp 1
                $this->Dx_nguoi_duyet_de_xuat_model->themNguoiDuyetTheoDonVi(
                    $idDeXuat,
                    $idDonVi,
                    null,
                    $capDuyet,
                    $qlNguoiDungId
                );
            }
        }

        // Upload file đính kèm nếu có (1 hoặc nhiều file)
        if (isset($_FILES['file_dinh_kem']) && !empty($_FILES['file_dinh_kem']['name'])) {
            $uploadPath = 'uploads/dexuat/' . $idDeXuat . '/';

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            // Xử lý cả trường hợp 1 file hoặc nhiều file
            $isMultiple = is_array($_FILES['file_dinh_kem']['name']);
            $filesCount = $isMultiple ? count($_FILES['file_dinh_kem']['name']) : 1;

            for ($i = 0; $i < $filesCount; $i++) {
                // Lấy thông tin file theo từng trường hợp
                if ($isMultiple) {
                    $_FILES['file']['name'] = $_FILES['file_dinh_kem']['name'][$i];
                    $_FILES['file']['type'] = $_FILES['file_dinh_kem']['type'][$i];
                    $_FILES['file']['tmp_name'] = $_FILES['file_dinh_kem']['tmp_name'][$i];
                    $_FILES['file']['error'] = $_FILES['file_dinh_kem']['error'][$i];
                    $_FILES['file']['size'] = $_FILES['file_dinh_kem']['size'][$i];
                    $originalName = $_FILES['file_dinh_kem']['name'][$i];
                } else {
                    $_FILES['file']['name'] = $_FILES['file_dinh_kem']['name'];
                    $_FILES['file']['type'] = $_FILES['file_dinh_kem']['type'];
                    $_FILES['file']['tmp_name'] = $_FILES['file_dinh_kem']['tmp_name'];
                    $_FILES['file']['error'] = $_FILES['file_dinh_kem']['error'];
                    $_FILES['file']['size'] = $_FILES['file_dinh_kem']['size'];
                    $originalName = $_FILES['file_dinh_kem']['name'];
                }

                // Bỏ qua nếu file rỗng
                if (empty($_FILES['file']['name'])) {
                    continue;
                }

                $config['upload_path'] = $uploadPath;
                $config['allowed_types'] = '*';
                $config['max_size'] = 10240; // 10MB
                $config['encrypt_name'] = true;

                $this->load->library('upload', $config);

                if ($this->upload->do_upload('file')) {
                    $uploadData = $this->upload->data();

                    $fileData = [
                        'ten_file_goc' => $originalName,
                        'dung_luong' => $uploadData['file_size'] . ' KB',
                        'duong_dan' => $uploadPath . $uploadData['file_name'],
                        'loai_file' => $uploadData['file_ext']
                    ];

                    $this->Dx_file_dinh_kem_model->themFileDinhKem($idDeXuat, $fileData, $qlNguoiDungId);
                }
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->response([
                'status' => false,
                'message' => 'Tạo đề xuất thất bại'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Log tạo đề xuất
        $this->createLog(
            'create',
            'Tạo đề xuất: ' . $tieuDe,
            null,
            ['id_de_xuat' => $idDeXuat, 'trang_thai' => $trangThai],
            'dx_de_xuat'
        );

        if ($trangThai === 'dang_xu_ly') {
            $this->load->model('Ql_thong_bao_model');

            // Lấy danh sách người duyệt cấp 1
            $nguoiDuyetCap1 = $this->db
                ->select('id_nguoi_duyet')
                ->from('dx_nguoi_duyet_de_xuat')
                ->where('id_de_xuat', $idDeXuat)
                ->where('cap_duyet', 1)
                ->where('deleted_at IS NULL')
                ->get()->result_array();

            $nguoiDuyetIds = array_column($nguoiDuyetCap1, 'id_nguoi_duyet');

            if (!empty($nguoiDuyetIds)) {
                $linkDuyet = $this->config->item('frontend_v2') . 'de-xuat/?id=' . $idDeXuat;
                
                $tieuDeThongBao = 'Có đề xuất mới cần duyệt: ' . $tieuDe;
                $noidungthongbao = 'Bạn có một Đề xuất mới cần duyệt: ' . $tieuDe . '. <br/> Click vào đây để <a href="' . $linkDuyet . '">xem chi tiết</a>.';
                
                $notification = $this->Ql_thong_bao_model->create([
                    'ql_thong_bao_link' => $linkDuyet,
                    'ql_thong_bao_tieu_de' => $tieuDeThongBao,
                    'ql_thong_bao_tieu_de_tieng_anh' => $tieuDeThongBao,
                    'ql_thong_bao_noi_dung' => $noidungthongbao,
                    'ql_thong_bao_noi_dung_tieng_anh' => $noidungthongbao,
                    'ql_thong_bao_ngay_gui' => date('Y-m-d H:i:s'),
                    'ql_thong_bao_loai' => 1,
                    'ql_thong_bao_doi_tuong' => 2,
                    'ql_thong_bao_da_gui' => 1,
                    'ql_thong_bao_tu_dong_gui' => 1,
                    'ql_thong_bao_cong_khai' => 0,
                    'created_user_id' => $qlNguoiDungId,
                    'updated_user_id' => $qlNguoiDungId
                ]);

                $dataInsertNotiUser = [];
                foreach (array_unique($nguoiDuyetIds) as $uId) {
                    $dataInsertNotiUser[] = [
                        'ql_thong_bao_id' => $notification['ql_thong_bao_id'],
                        'ql_nguoi_dung_id' => $uId,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'created_user_id' => $qlNguoiDungId,
                        'updated_user_id' => $qlNguoiDungId,
                    ];
                }
                if (!empty($dataInsertNotiUser)) {
                    $this->Ql_thong_bao_model->insertThongBaoNguoiDung($dataInsertNotiUser);
                    $this->load->helper('socket');
                    $userIdsArray = array_values(array_unique($nguoiDuyetIds));
                    // Lọc bỏ ID của người tạo ra khỏi danh sách push socket để không bị 2 toast cúng lúc (1 toast báo thành công, 1 toast báo có thông báo mới)
                    $userIdsArray = array_values(array_diff($userIdsArray, [$qlNguoiDungId]));
                    
                    if (!empty($userIdsArray)) {
                        send_socket_notify($userIdsArray, 'notification', [
                            'title' => 'Bạn có đề xuất mới',
                            'content' => $tieuDe
                        ]);
                    }
                }
            }
        }

        // Trả flag để frontend biết cần gọi send-email riêng
        $this->response([
            'status' => true,
            'message' => 'Tạo đề xuất thành công',
            'data' => [
                'id_de_xuat' => $idDeXuat,
                'trang_thai' => $trangThai,
                'should_send_email' => ($trangThai === 'dang_xu_ly'),
            ]
        ], REST_Controller::HTTP_CREATED);
    }

    /**
     * Gửi email thông báo đề xuất cho người liên quan.
     * Frontend gọi song song sau khi create/update thành công.
     * POST /api/v2/admin/hrm/dexuat/send-email
     */
    public function send_email_post()
    {
        $idDeXuat = commonRequest('id_de_xuat');
        if (!$idDeXuat) {
            $this->response(['status' => false, 'message' => 'Thiếu ID đề xuất'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $auth = $this->getUserLogin();
        if (!$auth) {
            $this->response(['status' => false, 'message' => 'Không có quyền'], REST_Controller::HTTP_UNAUTHORIZED);
            return;
        }

        // cap_gui: 0 = vừa tạo (gửi người tạo + cấp 1)
        //          N = cấp N vừa duyệt xong → gửi cấp N+1
        //          Không truyền = mặc định 0
        $capGui = commonRequest('cap_gui');
        $capGui = ($capGui !== null) ? (int) $capGui : 0;

        try {
            // Lấy thông tin đề xuất kèm tên loại
            $deXuat = $this->db
                ->select('dx.*, ldx.ten_loai')
                ->from('dx_de_xuat dx')
                ->join('dx_loai_de_xuat ldx', 'ldx.id_dx_loai_de_xuat = dx.id_dx_loai_de_xuat', 'left')
                ->where('dx.id_de_xuat', $idDeXuat)
                ->get()->row_array();

            if (!$deXuat) {
                $this->response(['status' => false, 'message' => 'Không tìm thấy đề xuất'], REST_Controller::HTTP_NOT_FOUND);
                return;
            }

            // Lấy thông tin người tạo
            $nguoiTao = $this->db
                ->select('nv.ho_va_ten, nv.ma_nhan_vien, nv.email, dv.ten_don_vi')
                ->from('hrm_nhan_vien nv')
                ->join('ql_nguoi_dung nd', 'nd.ql_nguoi_dung_id = nv.ql_nguoi_dung_id', 'left')
                ->join('e_don_vi dv', 'dv.id_don_vi = nv.id_don_vi_cong_tac', 'left')
                ->where('nv.ql_nguoi_dung_id', $deXuat['created_user_id'])
                ->get()->row_array();

            // Lấy danh sách file đính kèm
            $fileDinhKem = $this->db
                ->select('id_file_dinh_kem, ten_file_goc, duong_dan, dung_luong, loai_file')
                ->from('dx_file_dinh_kem')
                ->where('id_de_xuat', $idDeXuat)
                ->get()->result_array();

            // Dữ liệu chung cho email
            $emailDataBase = [
                'ho_va_ten' => $nguoiTao['ho_va_ten'] ?? '',
                'ma_nhan_vien' => $nguoiTao['ma_nhan_vien'] ?? '',
                'ten_don_vi' => $nguoiTao['ten_don_vi'] ?? '',
                'ten_loai_de_xuat' => $deXuat['ten_loai'] ?? '',
                'tieu_de' => $deXuat['tieu_de'] ?? '',
                'noi_dung' => $deXuat['noi_dung'] ?? '',
                'trang_thai' => $deXuat['trang_thai'] ?? '',
                'id_de_xuat' => $idDeXuat,
                'created_at' => date('d/m/Y H:i'),
                'link_duyet' => $this->config->item('frontend_v2') . 'de-xuat/?id=' . $idDeXuat,
                'link_dang_nhap' => $this->config->item('frontend_v2') . 'login?returnUrl=' . urlencode('/de-xuat/?id=' . $idDeXuat),
                'file_dinh_kem' => $fileDinhKem,
            ];

            $debug = ['sent' => [], 'skipped' => []];
            $emailDaDGui = [];

            if ($capGui === 0) {
                // ── Giai đoạn tạo mới: gửi người tạo + người duyệt cấp 1 ──

                // 1. Gửi cho người tạo
                $emailTao = $nguoiTao['email'] ?? '';
                if (!empty($emailTao)) {
                    $r = $this->sendDeXuatEmail(array_merge($emailDataBase, ['email' => $emailTao]));
                    $debug['sent'][] = ['role' => 'nguoi_tao', 'email' => $emailTao, 'result' => $r];
                    $emailDaDGui[] = $emailTao;
                } else {
                    $debug['skipped'][] = ['role' => 'nguoi_tao', 'ly_do' => 'Không có email'];
                }

                // 2. Gửi cho người duyệt cấp 1
                $capGuiNguoiDuyet = 1;

            } else {
                // ── Cấp N vừa duyệt xong: gửi người duyệt cấp N+1 ──
                $capGuiNguoiDuyet = $capGui + 1;
            }

            // Lấy danh sách người duyệt theo cấp cần gửi
            $danhSachNguoiDuyet = $this->db
                ->select('nv.email, nv.ho_va_ten, dx.cap_duyet')
                ->from('dx_nguoi_duyet_de_xuat dx')
                ->join('ql_nguoi_dung nd', 'nd.ql_nguoi_dung_id = dx.id_nguoi_duyet', 'left')
                ->join('hrm_nhan_vien nv', 'nv.ql_nguoi_dung_id = nd.ql_nguoi_dung_id', 'left')
                ->where('dx.id_de_xuat', $idDeXuat)
                ->where('dx.cap_duyet', $capGuiNguoiDuyet)
                ->where('dx.deleted_at IS NULL')
                ->get()->result_array();

            if (empty($danhSachNguoiDuyet)) {
                $debug['skipped'][] = ['role' => 'nguoi_duyet', 'ly_do' => "Không có người duyệt cấp {$capGuiNguoiDuyet}"];

                // Fallback: không có cấp tiếp theo → gửi về người tạo (cấp 0)
                if ($capGui > 0) {
                    $emailTao = $nguoiTao['email'] ?? '';
                    if (!empty($emailTao) && !in_array($emailTao, $emailDaDGui)) {
                        $r = $this->sendDeXuatEmail(array_merge($emailDataBase, ['email' => $emailTao]));
                        $debug['sent'][] = ['role' => 'nguoi_tao_fallback', 'email' => $emailTao, 'result' => $r];
                        $emailDaDGui[] = $emailTao;
                    }
                }
            } else {
                foreach ($danhSachNguoiDuyet as $nd) {
                    $emailNd = $nd['email'] ?? '';
                    if (empty($emailNd)) {
                        $debug['skipped'][] = ['role' => 'nguoi_duyet', 'ho_ten' => $nd['ho_va_ten'] ?? '', 'ly_do' => 'Không có email'];
                        continue;
                    }
                    if (in_array($emailNd, $emailDaDGui)) {
                        $debug['skipped'][] = ['role' => 'nguoi_duyet', 'email' => $emailNd, 'ly_do' => 'Email trùng'];
                        continue;
                    }
                    $emailDaDGui[] = $emailNd;
                    $r = $this->sendDeXuatEmail(array_merge($emailDataBase, ['email' => $emailNd]));
                    $debug['sent'][] = ['role' => "nguoi_duyet_cap_{$capGuiNguoiDuyet}", 'email' => $emailNd, 'result' => $r];
                }
            }

            $this->response([
                'status' => true,
                'message' => 'Gửi email thành công',
                'data' => $debug,
            ], REST_Controller::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'Lỗi send_email_post đề xuất: ' . $e->getMessage());
            $this->response(['status' => false, 'message' => $e->getMessage()], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Cập nhật đề xuất
     * POST /api/v2/admin/hrm/dexuat/update/:id
     */
    public function update_post($idDeXuat = null)
    {
        if (!$idDeXuat) {
            $this->response([
                'status' => false,
                'message' => 'Thiếu ID đề xuất'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $auth = $this->getUserLogin();
        $qlNguoiDungId = $auth['ql_nguoi_dung_id'];

        $tieuDe = commonRequest('tieu_de');
        $noiDung = commonRequest('noi_dung');
        $id_loai_de_xuat = commonRequest('id_dx_loai_de_xuat');
        $nhap = commonRequest('nhap');
        $deleted_file_ids = commonRequest('deleted_file_ids');

        // Các tham số cho việc cập nhật người ký từ Timeline
        // Các tham số cho việc cập nhật người ký từ Timeline
        $capDuyetRequest = commonRequest('cap_duyet');
        $idDonViRequest = commonRequest('id_don_vi');
        $danhSachNguoiDuyet = commonRequest('danh_sach_nguoi_duyet');

        $isUpdatingSigners = ($capDuyetRequest !== null && $idDonViRequest !== null);

        // Kiểm tra đề xuất tồn tại
        $deXuat = $this->Dx_de_xuat_model->getChiTietDeXuat($idDeXuat, $qlNguoiDungId);
        if (!$deXuat) {
            $this->response([
                'status' => false,
                'message' => 'Không tìm thấy đề xuất hoặc bạn không có quyền cập nhật'
            ], REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        // Kiểm tra quyền cập nhật
        $hasPermission = false;

        // Người tạo có toàn quyền
        if ($deXuat['created_user_id'] == $qlNguoiDungId) {
            $hasPermission = true;
        }

        // Kiểm tra nếu là nhân sự phòng TCHC thì có quyền cập nhật người ký (hoặc cập nhật đề xuất nếu cần)
        $userNV = $this->db->select('nv.*, dv.ma_don_vi')
            ->from('hrm_nhan_vien nv')
            ->join('e_don_vi dv', 'dv.id_don_vi = nv.id_don_vi_cong_tac', 'left')
            ->where('nv.ql_nguoi_dung_id', $qlNguoiDungId)
            ->get()->row_array();

        $isTCHC = (
            ($userNV && $userNV['ma_don_vi'] === 'PHONG_TCHC') ||
            (isset($auth['id_don_vi']) && $auth['id_don_vi'] == 15) ||
            (isset($auth['loai_lanh_dao']) && $auth['loai_lanh_dao'] === 'LANH_DAO_TCHC')
        );

        if ($isTCHC) {
            $hasPermission = true;
        } elseif ($isUpdatingSigners) {
            // Cho phép nếu người dùng thuộc đơn vị đang yêu cầu cập nhật
            if (isset($auth['id_don_vi']) && $auth['id_don_vi'] == $idDonViRequest) {
                $hasPermission = true;
            } else {
                // Kiểm tra xem người dùng có nằm trong danh sách người ký hiện tại của cấp/đơn vị này không
                $checkApprover = $this->db->where([
                    'id_de_xuat' => $idDeXuat,
                    'cap_duyet' => $capDuyetRequest,
                    'id_don_vi' => $idDonViRequest,
                    'id_nguoi_duyet' => $qlNguoiDungId
                ])->get('dx_nguoi_duyet_de_xuat')->num_rows();

                if ($checkApprover > 0) {
                    $hasPermission = true;
                }
            }
        }

        if (!$hasPermission) {
            $this->response([
                'status' => false,
                'message' => 'Bạn không có quyền cập nhật đề xuất này'
            ], REST_Controller::HTTP_FORBIDDEN);
            return;
        }

        // Chỉ cho phép cập nhật khi đang là nháp hoặc bị từ chối
        // if (!in_array($deXuat['trang_thai'], ['nhap', 'tu_choi'])) {
        //     $this->response([
        //         'status' => false,
        //         'message' => 'Chỉ có thể cập nhật đề xuất khi ở trạng thái Nháp hoặc Từ chối'
        //     ], REST_Controller::HTTP_BAD_REQUEST);
        //     return;
        // }

        $this->db->trans_start();

        // TRƯỜNG HỢP 1: Cập nhật danh sách người ký cho một cấp/đơn vị cụ thể
        if ($isUpdatingSigners) {
            // 1. Xóa danh sách người ký cũ của đơn vị này ở cấp này
            $this->db->where([
                'id_de_xuat' => $idDeXuat,
                'cap_duyet' => $capDuyetRequest,
                'id_don_vi' => $idDonViRequest
            ])->delete('dx_nguoi_duyet_de_xuat');

            // 2. Thêm danh sách người ký mới
            if (is_string($danhSachNguoiDuyet)) {
                $danhSachNguoiDuyet = json_decode($danhSachNguoiDuyet, true);
            }

            if (is_array($danhSachNguoiDuyet) && !empty($danhSachNguoiDuyet)) {
                foreach ($danhSachNguoiDuyet as $idNguoiDuyet) {
                    $this->Dx_nguoi_duyet_de_xuat_model->themNguoiDuyetTheoDonVi(
                        $idDeXuat,
                        $idDonViRequest,
                        $idNguoiDuyet,
                        $capDuyetRequest,
                        $qlNguoiDungId
                    );
                }
            } else {
                // Nếu danh sách trống, thêm một bản ghi rỗng cho đơn vị này (để giữ vị trí trong quy trình)
                $this->Dx_nguoi_duyet_de_xuat_model->themNguoiDuyetTheoDonVi(
                    $idDeXuat,
                    $idDonViRequest,
                    null,
                    $capDuyetRequest,
                    $qlNguoiDungId
                );
            }

            $this->db->trans_complete();
            $this->response([
                'status' => true,
                'message' => 'Cập nhật danh sách người ký thành công'
            ], REST_Controller::HTTP_OK);
            return;
        }

        // TRƯỜNG HỢP 2: Cập nhật thông tin đề xuất (Logic cũ cho người tạo)
        $data = [];
        if ($tieuDe !== null)
            $data['tieu_de'] = $tieuDe;
        if ($noiDung !== null)
            $data['noi_dung'] = $noiDung;
        if ($id_loai_de_xuat !== null)
            $data['id_dx_loai_de_xuat'] = $id_loai_de_xuat;
        if ($nhap !== null)
            $data['nhap'] = $nhap;

        if (!empty($data)) {
            $this->Dx_de_xuat_model->capNhatDeXuat($idDeXuat, $data, $qlNguoiDungId);
        }

        // Xử lý xóa file đính kèm cũ
        if (!empty($deleted_file_ids)) {
            if (is_string($deleted_file_ids)) {
                $deleted_file_ids = json_decode($deleted_file_ids, true);
            }

            if (is_array($deleted_file_ids) && !empty($deleted_file_ids)) {
                foreach ($deleted_file_ids as $fileId) {
                    // Kiểm tra file có thuộc về đề xuất này không
                    $fileInfo = $this->db->where([
                        'id_file_dinh_kem' => $fileId,
                        'id_de_xuat' => $idDeXuat
                    ])->get('dx_file_dinh_kem')->row_array();

                    if ($fileInfo) {
                        // Xóa file vật lý
                        $filePath = $fileInfo['duong_dan'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                        // Xóa record trong DB
                        $this->db->where('id_file_dinh_kem', $fileId)->delete('dx_file_dinh_kem');
                    }
                }
            }
        }

        // Cập nhật trạng thái nếu có yêu cầu thay đổi nháp/gửi
        if ($nhap !== null) {
            $trangThaiMoi = ($nhap == 1) ? 'nhap' : 'dang_xu_ly';
            $this->db->where('id_de_xuat', $idDeXuat);
            $this->db->update('dx_de_xuat', ['trang_thai' => $trangThaiMoi]);

            // Nếu chuyển về nháp, reset tiến trình duyệt
            if ($nhap == 1) {
                $this->db->where('id_de_xuat', $idDeXuat);
                $this->db->update('dx_nguoi_duyet_de_xuat', [
                    'da_duyet' => null,
                    'thoi_gian_duyet' => null,
                    'ly_do' => null,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
        }

        $danhSachDonVi = commonRequest('danh_sach_don_vi') ?? [];
        if (!empty($danhSachDonVi)) {
            // Cập nhật người duyệt: Xét các id của người duyệt có thay đổi thì cập nhật lại
            // 1. Lấy danh sách người duyệt hiện tại
            $currentApprovers = $this->db->where('id_de_xuat', $idDeXuat)
                ->get('dx_nguoi_duyet_de_xuat')
                ->result_array();

            $currentMap = [];
            foreach ($currentApprovers as $approver) {
                // Key format: level_unitId_approverId
                $key = $approver['cap_duyet'] . '_' . $approver['id_don_vi'] . '_' . ($approver['id_nguoi_duyet'] ?? 'NULL');
                $currentMap[$key] = $approver['id_nguoi_duyet_de_xuat'];
            }

            // 2. Parse và xử lý danh sách mới            
            if (is_string($danhSachDonVi)) {
                $danhSachDonVi = json_decode($danhSachDonVi, true);
            } elseif (is_object($danhSachDonVi) || (is_array($danhSachDonVi) && isset($danhSachDonVi[0]) && is_object($danhSachDonVi[0]))) {
                $danhSachDonVi = json_decode(json_encode($danhSachDonVi), true);
            }

            // Validate
            if (!is_array($danhSachDonVi) || empty($danhSachDonVi)) {
                $this->response([
                    'status' => false,
                    'message' => 'Danh sách đơn vị không hợp lệ'
                ], REST_Controller::HTTP_BAD_REQUEST);
                return;
            }

            $newKeys = [];
            if (is_array($danhSachDonVi)) {
                foreach ($danhSachDonVi as $donVi) {
                    $idDonVi = $donVi['id_don_vi'];
                    $idsNguoiDuyet = isset($donVi['ids_nguoi_duyet']) ? $donVi['ids_nguoi_duyet'] : [];

                    if (!empty($idsNguoiDuyet) && is_array($idsNguoiDuyet)) {
                        foreach ($idsNguoiDuyet as $idNguoiDuyet) {
                            $key = $donVi['cap'] . '_' . $idDonVi . '_' . $idNguoiDuyet;
                            $newKeys[$key] = true;

                            // Nếu chưa tồn tại trong DB thì thêm mới
                            if (!isset($currentMap[$key])) {
                                $this->Dx_nguoi_duyet_de_xuat_model->themNguoiDuyetTheoDonVi(
                                    $idDeXuat,
                                    $idDonVi,
                                    $idNguoiDuyet,
                                    $donVi['cap'],
                                    $qlNguoiDungId
                                );
                            }
                        }
                    } else {
                        $key = $donVi['cap'] . '_' . $idDonVi . '_NULL';
                        $newKeys[$key] = true;

                        // Nếu chưa tồn tại trong DB thì thêm mới
                        if (!isset($currentMap[$key])) {
                            $this->Dx_nguoi_duyet_de_xuat_model->themNguoiDuyetTheoDonVi(
                                $idDeXuat,
                                $idDonVi,
                                null,
                                $donVi['cap'],
                                $qlNguoiDungId
                            );
                        }
                    }
                }
            }

            // 3. Xóa những người duyệt cũ KHÔNG còn trong danh sách mới
            $idsToDelete = [];
            foreach ($currentMap as $key => $id) {
                if (!isset($newKeys[$key])) {
                    $idsToDelete[] = $id;
                }
            }

            if (!empty($idsToDelete)) {
                $this->db->where_in('id_nguoi_duyet_de_xuat', $idsToDelete);
                $this->db->delete('dx_nguoi_duyet_de_xuat');
            }
        }

        // Upload file đính kèm mới (giữ lại file cũ)
        if (isset($_FILES['file_dinh_kem']) && !empty($_FILES['file_dinh_kem']['name'])) {
            $uploadPath = 'uploads/dexuat/' . $idDeXuat . '/';

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            $isMultiple = is_array($_FILES['file_dinh_kem']['name']);
            $filesCount = $isMultiple ? count($_FILES['file_dinh_kem']['name']) : 1;

            for ($i = 0; $i < $filesCount; $i++) {
                if ($isMultiple) {
                    $_FILES['file']['name'] = $_FILES['file_dinh_kem']['name'][$i];
                    $_FILES['file']['type'] = $_FILES['file_dinh_kem']['type'][$i];
                    $_FILES['file']['tmp_name'] = $_FILES['file_dinh_kem']['tmp_name'][$i];
                    $_FILES['file']['error'] = $_FILES['file_dinh_kem']['error'][$i];
                    $_FILES['file']['size'] = $_FILES['file_dinh_kem']['size'][$i];
                    $originalName = $_FILES['file_dinh_kem']['name'][$i];
                } else {
                    $_FILES['file']['name'] = $_FILES['file_dinh_kem']['name'];
                    $_FILES['file']['type'] = $_FILES['file_dinh_kem']['type'];
                    $_FILES['file']['tmp_name'] = $_FILES['file_dinh_kem']['tmp_name'];
                    $_FILES['file']['error'] = $_FILES['file_dinh_kem']['error'];
                    $_FILES['file']['size'] = $_FILES['file_dinh_kem']['size'];
                    $originalName = $_FILES['file_dinh_kem']['name'];
                }

                if (empty($_FILES['file']['name'])) {
                    continue;
                }

                $config['upload_path'] = $uploadPath;
                $config['allowed_types'] = '*';
                $config['max_size'] = 10240;
                $config['encrypt_name'] = true;

                $this->load->library('upload', $config);
                // Reset upload data để tránh lỗi khi loop
                $this->upload->initialize($config);

                if ($this->upload->do_upload('file')) {
                    $uploadData = $this->upload->data();

                    $fileData = [
                        'ten_file_goc' => $originalName,
                        'dung_luong' => $uploadData['file_size'] . ' KB',
                        'duong_dan' => $uploadPath . $uploadData['file_name'],
                        'loai_file' => $uploadData['file_ext']
                    ];

                    $this->Dx_file_dinh_kem_model->themFileDinhKem($idDeXuat, $fileData, $qlNguoiDungId);
                }
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->response([
                'status' => false,
                'message' => 'Cập nhật đề xuất thất bại'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Lấy thông tin đề xuất sau khi update
        $deXuatUpdated = $this->Dx_de_xuat_model->getChiTietDeXuat($idDeXuat, $qlNguoiDungId);
        
        // Log cập nhật
        $this->createLog(
            'update',
            'Cập nhật đề xuất ID: ' . $idDeXuat,
            $deXuat,
            $deXuatUpdated,
            'dx_de_xuat'
        );

        $this->response([
            'status' => true,
            'message' => 'Cập nhật đề xuất thành công',
            'data' => [
                'id_de_xuat' => $idDeXuat,
                'trang_thai' => $trangThaiMoi
            ]
        ], REST_Controller::HTTP_OK);
    }

    /**
     * Duyệt đề xuất
     * POST /api/v2/admin/hrm/dexuat/duyet/:id
     * 
     * Body: {
     *   "da_duyet": 1,  // 1 = đồng ý, 0 = từ chối
     *   "ly_do": "Lý do duyệt/từ chối"
     * }
     * 
     * Chức năng: Cập nhật trạng thái duyệt của người đăng nhập trong bảng dx_nguoi_duyet_de_xuat
     */
    public function duyet_post($idDeXuat = null)
    {
        if (!$idDeXuat) {
            $this->response([
                'status' => false,
                'message' => 'Thiếu ID đề xuất'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $auth = $this->getUserLogin();
        $qlNguoiDungId = $auth['ql_nguoi_dung_id'];
        // dd($qlNguoiDungId);

        $daDuyet = commonRequest('da_duyet'); // 1 = đồng ý, 0 = từ chối
        $lyDo = commonRequest('ly_do');

        // Kiểm tra da_duyet hợp lệ
        if (!in_array($daDuyet, ['0', '1', 0, 1], true)) {
            $this->response([
                'status' => false,
                'message' => 'Giá trị da_duyet không hợp lệ. Phải là 0 (từ chối) hoặc 1 (đồng ý)'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Kiểm tra xem user có phải thuộc phòng TCHC hay không 
        $userNV = $this->db->select('nv.*, dv.ma_don_vi')
            ->from('hrm_nhan_vien nv')
            ->join('e_don_vi dv', 'dv.id_don_vi = nv.id_don_vi_cong_tac', 'left')
            ->where('nv.ql_nguoi_dung_id', $qlNguoiDungId)
            ->get()->row_array();

        $isTCHC = ($userNV && $userNV['ma_don_vi'] === 'PHONG_TCHC');

        // Tìm người duyệt trong bảng dx_nguoi_duyet_de_xuat
        $this->db->where('id_de_xuat', $idDeXuat);
        $this->db->where('id_nguoi_duyet', $qlNguoiDungId);
        // $this->db->where('deleted_at IS NULL', null, false);
        $nguoiDuyet = $this->db->get('dx_nguoi_duyet_de_xuat')->row_array();

        // 1. Trường hợp đặc biệt: Phòng TCHC xác nhận hoàn thành
        if ($isTCHC && $daDuyet == 1) {
            $this->db->trans_start();

            // Cập nhật trạng thái đề xuất thành 'da_duyet' (hoàn thành toàn bộ)
            $this->db->where('id_de_xuat', $idDeXuat);
            $this->db->update('dx_de_xuat', [
                'trang_thai' => 'da_duyet',
                'id_user_confirm_completed' => $qlNguoiDungId,
                'time_confirm_completed' => date('Y-m-d H:i:s'),
                'updated_user_id' => $qlNguoiDungId,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // Nếu user này cũng có trong danh sách người duyệt, cập nhật dòng của họ
            if ($nguoiDuyet) {
                $this->db->where('id_nguoi_duyet_de_xuat', $nguoiDuyet['id_nguoi_duyet_de_xuat']);
                $this->db->update('dx_nguoi_duyet_de_xuat', [
                    'da_duyet' => 1,
                    'ly_do' => $lyDo,
                    'thoi_gian_duyet' => date('Y-m-d H:i:s'),
                    'updated_user_id' => $qlNguoiDungId,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === TRUE) {
                $this->response([
                    'status' => true,
                    'message' => 'Phòng TCHC đã xác nhận hoàn thành đề xuất thành công',
                    'data' => [
                        'trang_thai_de_xuat' => 'da_duyet',
                        'should_send_email' => true,
                        'cap_duyet' => 999
                    ]
                ], REST_Controller::HTTP_OK);
                return;
            }
        }

        if (!$nguoiDuyet) {
            $this->response([
                'status' => false,
                'message' => 'Bạn không có quyền duyệt đề xuất này'
            ], REST_Controller::HTTP_FORBIDDEN);
            return;
        }

        // Kiểm tra các cấp nhỏ hơn đã duyệt chưa
        $capHienTai = $nguoiDuyet['cap_duyet'];

        if ($this->Dx_de_xuat_model->is_sequential && $capHienTai > 1 && !$isTCHC) {
            // Kiểm tra tất cả các cấp nhỏ hơn cấp hiện tại
            $this->db->select('cap_duyet, COUNT(*) as total, SUM(CASE WHEN da_duyet = 1 THEN 1 ELSE 0 END) as da_duyet_count');
            $this->db->where('id_de_xuat', $idDeXuat);
            $this->db->where('cap_duyet <', $capHienTai);
            // $this->db->where('deleted_at IS NULL', null, false);
            $this->db->group_by('cap_duyet');
            $cacCapTruoc = $this->db->get('dx_nguoi_duyet_de_xuat')->result_array();

            // Kiểm tra từng cấp phải có ít nhất 1 người đã duyệt
            foreach ($cacCapTruoc as $cap) {
                if ($cap['da_duyet_count'] == 0) {
                    $this->response([
                        'status' => false,
                        'message' => "Không thể duyệt. Cấp {$cap['cap_duyet']} chưa có người duyệt đề xuất này"
                    ], REST_Controller::HTTP_BAD_REQUEST);
                    return;
                }
            }

            // Kiểm tra xem có đủ số lượng cấp trước hay không
            $expectedCaps = range(1, $capHienTai - 1);
            $actualCaps = array_column($cacCapTruoc, 'cap_duyet');
            $missingCaps = array_diff($expectedCaps, $actualCaps);

            if (!empty($missingCaps)) {
                $this->response([
                    'status' => false,
                    'message' => 'Không thể duyệt. Các cấp trước chưa được thiết lập hoặc chưa duyệt: ' . implode(', ', $missingCaps)
                ], REST_Controller::HTTP_BAD_REQUEST);
                return;
            }
        }

        // Bắt đầu transaction
        $this->db->trans_start();

        // Cập nhật trạng thái duyệt
        $dataUpdate = [
            'da_duyet' => $daDuyet,
            'ly_do' => $lyDo,
            'thoi_gian_duyet' => date('Y-m-d H:i:s'),
            'updated_user_id' => $qlNguoiDungId,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->where('id_nguoi_duyet_de_xuat', $nguoiDuyet['id_nguoi_duyet_de_xuat']);
        $this->db->update('dx_nguoi_duyet_de_xuat', $dataUpdate);

        // Kiểm tra xem có cấp tiếp theo không
        $this->db->select('cap_duyet');
        $this->db->where('id_de_xuat', $idDeXuat);
        $this->db->where('cap_duyet >', $capHienTai);
        $this->db->group_by('cap_duyet');
        $this->db->order_by('cap_duyet', 'ASC');
        $this->db->limit(1);
        $capTiepTheo = $this->db->get('dx_nguoi_duyet_de_xuat')->row_array();

        // Nếu không có cấp tiếp theo (là cấp cuối) → cập nhật trạng thái đề xuất
        if (!$capTiepTheo) {
            $trangThaiDeXuat = $daDuyet == 1 ? 'da_duyet' : 'tu_choi';

            $this->db->where('id_de_xuat', $idDeXuat);
            $this->db->update('dx_de_xuat', [
                'trang_thai' => $trangThaiDeXuat,
                'updated_user_id' => $qlNguoiDungId,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        // Hoàn tất transaction
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->response([
                'status' => false,
                'message' => 'Có lỗi xảy ra trong quá trình cập nhật'
            ], REST_Controller::HTTP_INTERNAL_ERROR);
            return;
        }

        $message = $daDuyet == 1
            ? "Duyệt đề xuất thành công"
            : "Từ chối đề xuất thành công";

        // Thêm thông tin về trạng thái đề xuất nếu là cấp cuối
        if (!$capTiepTheo) {
            $message .= $daDuyet == 1
                ? ". Đề xuất đã được duyệt hoàn tất"
                : ". Đề xuất đã bị từ chối";
        }

        $this->response([
            'status' => true,
            'message' => $message,
            'data' => [
                'id_nguoi_duyet_de_xuat' => $nguoiDuyet['id_nguoi_duyet_de_xuat'],
                'cap_duyet' => $capHienTai,
                'da_duyet' => $daDuyet,
                'ngay_duyet' => date('Y-m-d H:i:s'),
                'la_cap_cuoi' => !$capTiepTheo ? true : false,
                'trang_thai_de_xuat' => !$capTiepTheo ? ($daDuyet == 1 ? 'da_duyet' : 'tu_choi') : 'dang_xu_ly',
                // Frontend dùng cap_duyet này để gọi send_email_post với cap_gui=cap_duyet
                // → send_email_post sẽ tự gửi tới cấp tiếp theo (cap_duyet+1)
                'should_send_email' => ($daDuyet == 1),
            ]
        ], REST_Controller::HTTP_OK);
    }

    /**
     * Thêm bình luận
     * POST /api/v2/admin/hrm/dexuat/binh-luan/:id
     */
    public function binh_luan_post($idDeXuat = null)
    {
        if (!$idDeXuat) {
            $this->response([
                'status' => false,
                'message' => 'Thiếu ID đề xuất'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $auth = $this->getUserLogin();
        $qlNguoiDungId = $auth['ql_nguoi_dung_id'];

        // Validate
        $this->validator->setRules([
            [
                'field' => 'noi_dung',
                'label' => 'Nội dung bình luận',
                'rules' => 'required|max_length[255]'
            ],
            [
                'field' => 'parent_id',
                'label' => 'ID bình luận cha',
                'rules' => 'trim|integer'
            ]
        ]);

        if ($this->validator->run() == false) {
            $this->response([
                'status' => false,
                'message' => $this->validator->getErrorsAsString()
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $noiDung = $this->input->post('noi_dung');
        $parentId = $this->input->post('parent_id') ?: null;

        // Kiểm tra quyền truy cập đề xuất
        $deXuat = $this->Dx_de_xuat_model->getChiTietDeXuat($idDeXuat, $qlNguoiDungId);

        if (!$deXuat) {
            $this->response([
                'status' => false,
                'message' => 'Không tìm thấy đề xuất hoặc bạn không có quyền bình luận'
            ], REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        // Nếu có parent_id, kiểm tra bình luận cha có tồn tại không
        if ($parentId) {
            $this->db->where('id_binh_luan', $parentId);
            $this->db->where('id_de_xuat', $idDeXuat);
            $this->db->where('deleted_at IS NULL');
            $parentComment = $this->db->get('dx_binh_luan')->row_array();

            if (!$parentComment) {
                $this->response([
                    'status' => false,
                    'message' => 'Bình luận cha không tồn tại'
                ], REST_Controller::HTTP_BAD_REQUEST);
                return;
            }
        }

        // Thêm bình luận
        $idBinhLuan = $this->Dx_binh_luan_model->themBinhLuan($idDeXuat, $noiDung, $qlNguoiDungId, $parentId);

        if ($idBinhLuan) {
            $this->response([
                'status' => true,
                'message' => $parentId ? 'Trả lời bình luận thành công' : 'Thêm bình luận thành công',
                'data' => ['id_binh_luan' => $idBinhLuan]
            ], REST_Controller::HTTP_CREATED);
        } else {
            $this->response([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi thêm bình luận'
            ], REST_Controller::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * Xóa đề xuất
     * DELETE /api/v2/admin/hrm/dexuat/delete/:id
     */
    public function delete_delete($idDeXuat = null)
    {
        if (!$idDeXuat) {
            $this->response([
                'status' => false,
                'message' => 'Thiếu ID đề xuất'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $auth = $this->getUserLogin();
        $qlNguoiDungId = $auth['ql_nguoi_dung_id'];

        $result = $this->Dx_de_xuat_model->xoaDeXuat($idDeXuat, $qlNguoiDungId);

        if ($result) {
            // Log xóa đề xuất
            $this->createLog(
                'delete',
                'Xóa đề xuất ID: ' . $idDeXuat,
                ['id_de_xuat' => $idDeXuat],
                null,
                'dx_de_xuat'
            );
            
            $this->response([
                'status' => true,
                'message' => 'Xóa đề xuất thành công'
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => false,
                'message' => 'Không tìm thấy đề xuất hoặc bạn không có quyền xóa'
            ], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function get_all_comment_get()
    {
        $auth = $this->getUserLogin();
        $comment = $this->Dx_binh_luan_model->get_all_comment($auth);
        resSuccess($comment['data'], 'Success', 200, true, [
            'sql' => $comment['sql']
        ]);
    }

    public function store_comment_post()
    {
        $auth = $this->getUserLogin();
        $idDeXuat = commonRequest('id_de_xuat');
        $noiDung = commonRequest('noi_dung');
        $parentId = commonRequest('parent_id');

        if (!$idDeXuat || !$noiDung) {
            resError('Thiếu thông tin đề xuất hoặc nội dung bình luận');
            return;
        }

        // Kiểm tra đề xuất tồn tại
        $this->db->where('id_de_xuat', $idDeXuat);
        $deXuat = $this->db->get('dx_de_xuat')->row_array();
        if (!$deXuat) {
            resError('Không tìm thấy đề xuất');
            return;
        }

        $insertData = [
            'id_de_xuat' => $idDeXuat,
            'noi_dung' => $noiDung,
            'created_user_id' => $auth['ql_nguoi_dung_id'],
            'created_at' => date('Y-m-d H:i:s')
        ];

        $targetUserIds = [];
        // Nếu là trả lời bình luận
        if ($parentId) {
            $this->db->where('id_binh_luan', $parentId);
            $this->db->where('id_de_xuat', $idDeXuat);
            $parentComment = $this->db->get('dx_binh_luan')->row_array();

            if (!$parentComment) {
                resError('Bình luận cha không tồn tại hoặc không thuộc đề xuất này');
                return;
            }

            $insertData['parent_id'] = $parentId;

            // Thông báo cho chủ bình luận cha (nếu không phải là mình)
            if ($parentComment['created_user_id'] != $auth['ql_nguoi_dung_id']) {
                $targetUserIds[] = $parentComment['created_user_id'];
            }
        }

        // Luôn thông báo cho chủ đề xuất (nếu không phải là mình)
        if ($deXuat['created_user_id'] != $auth['ql_nguoi_dung_id']) {
            if (!in_array($deXuat['created_user_id'], $targetUserIds)) {
                $targetUserIds[] = $deXuat['created_user_id'];
            }
        }

        $newCommentId = $this->Dx_binh_luan_model->create($insertData);

        if ($newCommentId) {
            // Gửi socket notify nếu có người cần nhận
            if (!empty($targetUserIds)) {
                $this->load->helper('socket');
                send_socket_notify(
                    $targetUserIds,
                    'propose_new_comment',
                    [
                        'id_de_xuat' => $idDeXuat,
                        'tieu_de' => $deXuat['tieu_de'],
                        'ho_ten_nguoi_binh_luan' => $auth['ql_nguoi_dung_ho_ten'],
                        'is_reply' => $parentId ? true : false,
                        'noi_dung' => $noiDung
                    ]
                );
            }

            resSuccess(['id_binh_luan' => $newCommentId], 'Success', 200, true);
        } else {
            resError('Có lỗi xảy ra khi lưu bình luận');
        }
    }

    public function update_comment_post($idBinhLuan)
    {
        $auth = $this->getUserLogin();
        $noiDung = commonRequest('noi_dung');

        $binhLuanCu = $this->Dx_binh_luan_model->where('id_binh_luan', $idBinhLuan)->first();
        $updateData = [
            'noi_dung' => $noiDung,
            'updated_user_id' => $auth['ql_nguoi_dung_id']
        ];

        $updatedComment = $this->Dx_binh_luan_model
            ->where('id_binh_luan', $idBinhLuan)
            ->update($updateData);

        $this->Dx_binh_luan_log_model->create([
            'id_binh_luan' => $idBinhLuan,
            'noi_dung_hien_tai' => $noiDung,
            'noi_dung_cu' => $binhLuanCu['noi_dung'],
            'created_user_id' => $auth['ql_nguoi_dung_id']
        ]);

        // Log cập nhật bình luận
        $this->createLog(
            'update',
            'Cập nhật bình luận đề xuất ID: ' . $idBinhLuan,
            $binhLuanCu,
            ['noi_dung' => $noiDung],
            'dx_binh_luan'
        );

        resSuccess($updatedComment, 'Success', 200, true);
    }

    public function delete_comment_post()
    {
        $auth = $this->getUserLogin();
        $idsBinhLuan = commonRequest('ids');

        $deletedComment = $this->Dx_binh_luan_model
            ->whereIn('id_binh_luan', $idsBinhLuan)
            ->update([
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_user_id' => $auth['ql_nguoi_dung_id']
            ]);

        // Log xóa bình luận
        $this->createLog(
            'delete',
            'Xóa bình luận đề xuất',
            ['ids' => $idsBinhLuan],
            null,
            'dx_binh_luan'
        );

        resSuccess($deletedComment, 'Success', 200, true);
    }

    /**
     * Lấy danh sách những người đã từng tạo đề xuất (để phục vụ bộ lọc)
     * GET /api/v2/admin/hrm/dexuat/get_created_by
     */
    public function get_created_by_get()
    {
        $this->db->select('dx.created_user_id, nv.ho_va_ten, nv.ma_nhan_vien');
        $this->db->from('dx_de_xuat dx');
        $this->db->join('hrm_nhan_vien nv', 'nv.ql_nguoi_dung_id = dx.created_user_id', 'inner');
        $this->db->where('dx.deleted_at IS NULL');
        $this->db->where('dx.nhap !=', 1);
        $this->db->group_by(['dx.created_user_id', 'nv.ho_va_ten', 'nv.ma_nhan_vien']);
        $this->db->order_by('nv.ho_va_ten', 'ASC');

        $creators = $this->db->get()->result_array();

        $this->response([
            'status' => true,
            'success' => true,
            'message' => 'Lấy danh sách người tạo thành công',
            'data' => $creators
        ], REST_Controller::HTTP_OK);
    }

    /**
     * Gắn sao cho nhiều đề xuất cùng lúc
     * POST /api/v2/admin/hrm/dexuat/gan_sao
     * Body: { "ids_de_xuat": [1, 2, 3] }
     */
    // public function gan_sao_post()
    // {
    //     $auth = $this->getUserLogin();
    //     $userId = $auth['ql_nguoi_dung_id'];
    //     $idsDeXuat = commonRequest('ids_de_xuat') ?? [];

    //     if (empty($idsDeXuat) || !is_array($idsDeXuat)) {
    //         resError('Danh sách đề xuất không hợp lệ');
    //         return;
    //     }

    //     $this->db->trans_start();

    //     $thanhCong = 0;
    //     $daTonTai = 0;
    //     $errors = [];

    //     foreach ($idsDeXuat as $idDeXuat) {
    //         // Kiểm tra đề xuất có tồn tại không
    //         $deXuat = $this->Dx_de_xuat_model->where('id_de_xuat', $idDeXuat)->get();
    //         if (!$deXuat) {
    //             $errors[] = "Đề xuất ID {$idDeXuat} không tồn tại";
    //             continue;
    //         }

    //         $result = $this->Dx_de_xuat_gan_sao_model->ganSao($idDeXuat, $userId);

    //         if ($result['status']) {
    //             $thanhCong++;
    //         } else {
    //             $daTonTai++;
    //         }
    //     }

    //     $this->db->trans_complete();

    //     if ($this->db->trans_status() === FALSE) {
    //         resError('Có lỗi xảy ra khi gắn sao');
    //         return;
    //     }

    //     $message = "Đã gắn sao {$thanhCong} đề xuất";
    //     if ($daTonTai > 0) {
    //         $message .= ", {$daTonTai} đề xuất đã được gắn sao trước đó";
    //     }
    //     if (!empty($errors)) {
    //         $message .= ". Lỗi: " . implode(', ', $errors);
    //     }

    //     resSuccess([
    //         'thanh_cong' => $thanhCong,
    //         'da_ton_tai' => $daTonTai,
    //         'errors' => $errors
    //     ], $message, 200, true);
    // }

    // /**
    //  * Bỏ gắn sao cho nhiều đề xuất cùng lúc
    //  * POST /api/v2/admin/hrm/dexuat/bo_gan_sao
    //  * Body: { "ids_de_xuat": [1, 2, 3] }
    //  */
    // public function bo_gan_sao_post()
    // {
    //     $auth = $this->getUserLogin();
    //     $userId = $auth['ql_nguoi_dung_id'];
    //     $idsDeXuat = commonRequest('ids_de_xuat') ?? [];

    //     if (empty($idsDeXuat) || !is_array($idsDeXuat)) {
    //         resError('Danh sách đề xuất không hợp lệ');
    //         return;
    //     }

    //     $this->db->trans_start();

    //     $thanhCong = 0;
    //     $errors = [];

    //     foreach ($idsDeXuat as $idDeXuat) {
    //         $result = $this->Dx_de_xuat_gan_sao_model->boGanSao($idDeXuat, $userId);

    //         if ($result['status']) {
    //             $thanhCong++;
    //         } else {
    //             $errors[] = "Đề xuất ID {$idDeXuat}: {$result['message']}";
    //         }
    //     }

    //     $this->db->trans_complete();

    //     if ($this->db->trans_status() === FALSE) {
    //         resError('Có lỗi xảy ra khi bỏ gắn sao');
    //         return;
    //     }

    //     $message = "Đã bỏ gắn sao {$thanhCong} đề xuất";
    //     if (!empty($errors)) {
    //         $message .= ". Lỗi: " . implode(', ', $errors);
    //     }

    //     resSuccess([
    //         'thanh_cong' => $thanhCong,
    //         'errors' => $errors
    //     ], $message, 200, true);
    // }

    // /**
    //  * Đánh dấu quan trọng cho nhiều đề xuất cùng lúc
    //  * POST /api/v2/admin/hrm/dexuat/danh_dau_quan_trong
    //  * Body: { "ids_de_xuat": [1, 2, 3] }
    //  */
    // public function danh_dau_quan_trong_post()
    // {
    //     $auth = $this->getUserLogin();
    //     $userId = $auth['ql_nguoi_dung_id'];
    //     $idsDeXuat = commonRequest('ids_de_xuat') ?? [];

    //     if (empty($idsDeXuat) || !is_array($idsDeXuat)) {
    //         resError('Danh sách đề xuất không hợp lệ');
    //         return;
    //     }

    //     $this->db->trans_start();

    //     $thanhCong = 0;
    //     $daTonTai = 0;
    //     $errors = [];

    //     foreach ($idsDeXuat as $idDeXuat) {
    //         // Kiểm tra đề xuất có tồn tại không
    //         $deXuat = $this->Dx_de_xuat_model->where('id_de_xuat', $idDeXuat)->get();
    //         if (!$deXuat) {
    //             $errors[] = "Đề xuất ID {$idDeXuat} không tồn tại";
    //             continue;
    //         }

    //         $result = $this->Dx_de_xuat_quan_trong_model->danhDauQuanTrong($idDeXuat, $userId);

    //         if ($result['status']) {
    //             $thanhCong++;
    //         } else {
    //             $daTonTai++;
    //         }
    //     }

    //     $this->db->trans_complete();

    //     if ($this->db->trans_status() === FALSE) {
    //         resError('Có lỗi xảy ra khi đánh dấu quan trọng');
    //         return;
    //     }

    //     $message = "Đã đánh dấu quan trọng {$thanhCong} đề xuất";
    //     if ($daTonTai > 0) {
    //         $message .= ", {$daTonTai} đề xuất đã được đánh dấu trước đó";
    //     }
    //     if (!empty($errors)) {
    //         $message .= ". Lỗi: " . implode(', ', $errors);
    //     }

    //     resSuccess([
    //         'thanh_cong' => $thanhCong,
    //         'da_ton_tai' => $daTonTai,
    //         'errors' => $errors
    //     ], $message, 200, true);
    // }

    // /**
    //  * Bỏ đánh dấu quan trọng cho nhiều đề xuất cùng lúc
    //  * POST /api/v2/admin/hrm/dexuat/bo_danh_dau_quan_trong
    //  * Body: { "ids_de_xuat": [1, 2, 3] }
    //  */
    // public function bo_danh_dau_quan_trong_post()
    // {
    //     $auth = $this->getUserLogin();
    //     $userId = $auth['ql_nguoi_dung_id'];
    //     $idsDeXuat = commonRequest('ids_de_xuat') ?? [];

    //     if (empty($idsDeXuat) || !is_array($idsDeXuat)) {
    //         resError('Danh sách đề xuất không hợp lệ');
    //         return;
    //     }

    //     $this->db->trans_start();

    //     $thanhCong = 0;
    //     $errors = [];

    //     foreach ($idsDeXuat as $idDeXuat) {
    //         $result = $this->Dx_de_xuat_quan_trong_model->boDanhDauQuanTrong($idDeXuat, $userId);

    //         if ($result['status']) {
    //             $thanhCong++;
    //         } else {
    //             $errors[] = "Đề xuất ID {$idDeXuat}: {$result['message']}";
    //         }
    //     }

    //     $this->db->trans_complete();

    //     if ($this->db->trans_status() === FALSE) {
    //         resError('Có lỗi xảy ra khi bỏ đánh dấu quan trọng');
    //         return;
    //     }

    //     $message = "Đã bỏ đánh dấu quan trọng {$thanhCong} đề xuất";
    //     if (!empty($errors)) {
    //         $message .= ". Lỗi: " . implode(', ', $errors);
    //     }

    //     resSuccess([
    //         'thanh_cong' => $thanhCong,
    //         'errors' => $errors
    //     ], $message, 200, true);
    // }



    /**
     * Gửi mã OTP xác thực duyệt đề xuất
     * GET /api/v2/admin/hrm/dexuat/send_otp_approval/:id
     */
    public function send_otp_approval_get($idDeXuat = null)
    {
        if (!$idDeXuat) {
            $this->response([
                'status' => false,
                'message' => 'Thiếu ID đề xuất'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $auth = $this->getUserLogin();
        $qlNguoiDungId = $auth['ql_nguoi_dung_id'];
        $email = $auth['ql_nguoi_dung_email'];

        if (empty($email)) {
            $this->response([
                'status' => false,
                'message' => 'Tài khoản chưa cập nhật email'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Create OTP Code
        $otpCode = rand(100000, 999999);

        // Save to DB
        $data = [
            'id_de_xuat' => $idDeXuat,
            'otp_code' => $otpCode,
            'created_at' => date('Y-m-d H:i:s'),
            'created_user_id' => $qlNguoiDungId
        ];

        $this->db->insert('dx_otp_code', $data);

        // Prep email data
        $deXuat = $this->Dx_de_xuat_model->where('id_de_xuat', $idDeXuat)->first();
        $subject = 'Mã OTP xác thực duyệt đề xuất ' . $deXuat['tieu_de'] . ' - MyOffice';
        $viewData = [
            'data' => $auth,
            'otp_code' => $otpCode,
            'id_de_xuat' => $idDeXuat
        ];

        // Load view
        $message = $this->load->view('email/otp_approval_verification.php', $viewData, true);

        // Send Email
        $result = send_email($email, $subject, $message, 'noreply@tchc.nctu.edu.vn', 'Trường Đại Học Nam Cần Thơ - DNC University');

        if ($result) {
            $this->response([
                'status' => true,
                'message' => 'Đã gửi mã OTP đến email ' . $email
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => false,
                'message' => 'Không thể gửi email xác thực'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Xác thực mã OTP duyệt đề xuất
     * POST /api/v2/admin/hrm/dexuat/verify_otp_approval
     */
    public function verify_otp_approval_post()
    {
        $idDeXuat = commonRequest('id_de_xuat');
        $otpCode = commonRequest('otp_code');

        if (!$idDeXuat || !$otpCode) {
            $this->response([
                'status' => false,
                'message' => 'Thiếu thông tin xác thực'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Kiểm tra OTP mới nhất của đề xuất này
        $otp = $this->db
            ->select('*')
            ->from('dx_otp_code')
            ->where('id_de_xuat', $idDeXuat)
            ->where('otp_code', $otpCode)
            ->order_by('created_at', 'DESC')
            ->get()
            ->row_array();

        if (!$otp || $otp['created_user_id'] != $this->getUserLogin()['ql_nguoi_dung_id']) {
            $this->response([
                'status' => false,
                'message' => 'Mã OTP không chính xác'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Kiểm tra hết hạn (ví dụ 5 phút)
        $createdAt = strtotime($otp['created_at']);
        $now = time();
        if ($now - $createdAt > 300) { // 300 giây = 5 phút
            $this->response([
                'status' => false,
                'message' => 'Mã OTP đã hết hạn'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Nếu thành công -> trả về status true ///
        $this->response([
            'status' => true,
            'message' => 'Xác thực thành công'
        ], REST_Controller::HTTP_OK);
    }

    /**
     * Đánh dấu đề xuất đã hoàn thành ở đơn vị/cấp nào
     * POST /api/v2/admin/hrm/dexuat/check_complete
     * 
     * Body: {
     *   "id_de_xuat": 123,
     *   "ids_nguoi_duyet": [1, 2, 3],  // Danh sách ID người duyệt cần đánh dấu hoàn thành
     *   "cap_duyet": 2,                // Hoặc đánh dấu hoàn thành cả cấp
     *   "id_don_vi": 5,                // Hoặc đánh dấu hoàn thành cả đơn vị
     *   "da_xong": 1                   // 1 = đã xong, 0 = chưa xong
     * }
     */
    public function check_complete_post()
    {
        $auth = $this->getUserLogin();
        $qlNguoiDungId = $auth['ql_nguoi_dung_id'];

        // Kiểm tra quyền: chỉ phòng TCHC (VĂN THƯ TCHC, LÃNH ĐẠO TCHC) hoặc SUPER_ADMIN
        $allowedRoleCodes = ['SUPER_ADMIN', 'VAN_THU_TO_CHUC_HANH_CHINH', 'LANH_DAO_TCHC'];

        $userRole = $this->db
            ->select('vt.ql_ma_vai_tro')
            ->from('ql_vai_tro_nguoi_dung vtn')
            ->join('ql_vai_tro vt', 'vtn.ql_vai_tro_id = vt.ql_vai_tro_id', 'inner')
            ->where('vtn.ql_nguoi_dung_id', $qlNguoiDungId)
            ->get()
            ->row_array();

        $userRoleCode = $userRole ? $userRole['ql_ma_vai_tro'] : null;

        if ($auth['ql_nguoi_dung_is_admin'] != 1 && !in_array($userRoleCode, $allowedRoleCodes)) {
            $this->response([
                'status' => false,
                'message' => 'Bạn không có quyền thực hiện chức năng này. Chỉ phòng Tổ chức và Quản trị viên mới được phép'
            ], REST_Controller::HTTP_FORBIDDEN);
            return;
        }

        $idDeXuat = commonRequest('id_de_xuat');
        $idsNguoiDuyet = commonRequest('ids_nguoi_duyet'); // Array IDs
        $capDuyet = commonRequest('cap_duyet'); // Đánh dấu cả cấp
        $idDonVi = commonRequest('id_don_vi'); // Đánh dấu cả đơn vị
        $daXong = commonRequest('da_xong') ?? 1; // Mặc định là đã xong

        if (!$idDeXuat) {
            $this->response([
                'status' => false,
                'message' => 'Thiếu ID đề xuất'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Kiểm tra đề xuất tồn tại
        $deXuat = $this->db->where('id_de_xuat', $idDeXuat)
            ->where('deleted_at IS NULL')
            ->get('dx_de_xuat')
            ->row_array();

        if (!$deXuat) {
            $this->response([
                'status' => false,
                'message' => 'Không tìm thấy đề xuất'
            ], REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        $this->db->trans_start();

        $updated = 0;
        $dataUpdate = [
            'da_xong' => $daXong,
            'updated_user_id' => $qlNguoiDungId,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Trường hợp 1: Đánh dấu theo danh sách ID người duyệt cụ thể
        if (!empty($idsNguoiDuyet) && is_array($idsNguoiDuyet)) {
            $this->db->where('id_de_xuat', $idDeXuat);
            $this->db->where_in('id_nguoi_duyet_de_xuat', $idsNguoiDuyet);
            $this->db->update('dx_nguoi_duyet_de_xuat', $dataUpdate);
            $updated = $this->db->affected_rows();
        }
        // Trường hợp 2: Đánh dấu cả cấp duyệt
        elseif ($capDuyet !== null && $capDuyet !== '') {
            $this->db->where('id_de_xuat', $idDeXuat);
            $this->db->where('cap_duyet', $capDuyet);
            $this->db->update('dx_nguoi_duyet_de_xuat', $dataUpdate);
            $updated = $this->db->affected_rows();
        }
        // Trường hợp 3: Đánh dấu cả đơn vị
        elseif ($idDonVi !== null && $idDonVi !== '') {
            $this->db->where('id_de_xuat', $idDeXuat);
            $this->db->where('id_don_vi', $idDonVi);
            $this->db->update('dx_nguoi_duyet_de_xuat', $dataUpdate);
            $updated = $this->db->affected_rows();
        }
        // Trường hợp 4: Đánh dấu tất cả người duyệt của đề xuất
        else {
            $this->db->where('id_de_xuat', $idDeXuat);
            $this->db->update('dx_nguoi_duyet_de_xuat', $dataUpdate);
            $updated = $this->db->affected_rows();
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->response([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật'
            ], REST_Controller::HTTP_INTERNAL_ERROR);
            return;
        }

        // Lấy thống kê tiến độ hoàn thành
        $tienDoHoanThanh = $this->db
            ->select('
                ndx.cap_duyet,
                ndx.id_don_vi,
                dv.ten_don_vi,
                COUNT(*) as tong_so,
                SUM(CASE WHEN da_xong = 1 THEN 1 ELSE 0 END) as da_hoan_thanh,
                SUM(CASE WHEN da_duyet = 1 THEN 1 ELSE 0 END) as da_duyet
            ')
            ->from('dx_nguoi_duyet_de_xuat ndx')
            ->join('e_don_vi dv', 'dv.id_don_vi = ndx.id_don_vi', 'left')
            ->where('ndx.id_de_xuat', $idDeXuat)
            ->group_by(['cap_duyet', 'ndx.id_don_vi', 'dv.ten_don_vi'])
            ->order_by('cap_duyet', 'ASC')
            ->get()
            ->result_array();

        $message = $daXong == 1
            ? "Đã đánh dấu hoàn thành {$updated} người duyệt/đơn vị"
            : "Đã đánh dấu chưa hoàn thành {$updated} người duyệt/đơn vị";

        $this->response([
            'status' => true,
            'message' => $message,
            'data' => [
                'so_luong_cap_nhat' => $updated,
                'tien_do_hoan_thanh' => $tienDoHoanThanh
            ]
        ], REST_Controller::HTTP_OK);
    }

    /**
     * Lấy danh sách bình luận của người đăng nhập trong các đề xuất
     * GET /api/v2/admin/hrm/dexuat/get_binh_luan
     * 
     * Trả về tất cả bình luận của user và các reply cho những bình luận đó
     * Nhóm theo từng đề xuất
     */
    public function get_binh_luan_get()
    {
        $auth = $this->getUserLogin();
        $qlNguoiDungId = $auth['ql_nguoi_dung_id'];

        // Lấy các bình luận của user (chỉ bình luận gốc, không lấy reply)
        $this->db->select('
            bl.id_binh_luan,
            bl.id_de_xuat,
            bl.noi_dung,
            bl.parent_id,
            bl.created_user_id,
            bl.created_at,
            bl.updated_at,
            bl.updated_user_id,
            dx.tieu_de as ten_de_xuat,
            nv.ho_va_ten,
            nv.avatar
        ');
        $this->db->from('dx_binh_luan bl');
        $this->db->join('dx_de_xuat dx', 'dx.id_de_xuat = bl.id_de_xuat', 'inner');
        $this->db->join('hrm_nhan_vien nv', 'nv.ql_nguoi_dung_id = bl.created_user_id', 'left');
        $this->db->where('bl.created_user_id', $qlNguoiDungId);
        $this->db->where('bl.parent_id IS NULL', null, false);
        $this->db->where('bl.deleted_at IS NULL', null, false);
        $this->db->where('dx.deleted_at IS NULL', null, false);
        $this->db->order_by('bl.created_at', 'DESC');

        $userComments = $this->db->get()->result_array();

        // Lấy IDs của các bình luận của user để tìm trả lời
        $commentIds = array_column($userComments, 'id_binh_luan');

        $traLoiBinhLuan = [];
        if (!empty($commentIds)) {
            $this->db->select('
                bl.id_binh_luan,
                bl.id_de_xuat,
                bl.noi_dung,
                bl.parent_id,
                bl.created_user_id,
                bl.created_at,
                bl.updated_at,
                bl.updated_user_id,
                nv.ho_va_ten,
                nv.avatar
            ');
            $this->db->from('dx_binh_luan bl');
            $this->db->join('hrm_nhan_vien nv', 'nv.ql_nguoi_dung_id = bl.created_user_id', 'left');
            $this->db->where_in('bl.parent_id', $commentIds);
            $this->db->where('bl.deleted_at IS NULL', null, false);
            $this->db->order_by('bl.created_at', 'ASC');

            $repliesResult = $this->db->get()->result_array();

            // Nhóm trả lời bình luận theo parent_id
            foreach ($repliesResult as $reply) {
                $traLoiBinhLuan[$reply['parent_id']][] = $reply;
            }
        }

        // Gộp comments với replies và nhóm theo đề xuất
        $result = [];
        foreach ($userComments as $comment) {
            $idDeXuat = $comment['id_de_xuat'];

            if (!isset($result[$idDeXuat])) {
                $result[$idDeXuat] = [
                    'id_de_xuat' => $idDeXuat,
                    'ten_de_xuat' => $comment['ten_de_xuat'],
                    'binh_luan' => []
                ];
            }

            $commentData = [
                'id_binh_luan' => $comment['id_binh_luan'],
                'noi_dung' => $comment['noi_dung'],
                'created_user_id' => $comment['created_user_id'],
                'ho_va_ten' => $comment['ho_va_ten'],
                'avatar' => $comment['avatar'],
                'created_at' => $comment['created_at'],
                'updated_user_id' => $comment['updated_user_id'],
                'updated_at' => $comment['updated_at'],
                'tra_loi_binh_luan' => $traLoiBinhLuan[$comment['id_binh_luan']] ?? []
            ];

            $result[$idDeXuat]['binh_luan'][] = $commentData;
        }

        // Convert to array values để loại bỏ keys
        $result = array_values($result);

        $this->response([
            'status' => true,
            'success' => true,
            'message' => 'Lấy danh sách bình luận thành công',
            'data' => $result
        ], REST_Controller::HTTP_OK);
    }

    /**
     * Lấy lịch sử chỉnh sửa đề xuất từ bảng ql_nhat_ky
     * GET /api/v2/admin/hrm/dexuat/view_log
     */
    public function view_log_get()
    {
        $searchValue = commonRequest('searchValue');
        $start = (int)(commonRequest('start') ?? 0);
        $length = (int)(commonRequest('length') ?? 100);

        $this->db
            ->select('ql_nhat_ky.ql_nhat_ky_id, ql_nhat_ky.ql_nhat_ky_hanh_dong, ql_nhat_ky.ql_nhat_ky_noi_dung, ql_nhat_ky.ql_nhat_ky_gia_tri_cu, ql_nhat_ky.ql_nhat_ky_gia_tri_moi, ql_nhat_ky.ql_nhat_ky_bang_du_lieu, ql_nhat_ky.ql_nhat_ky_controller, ql_nhat_ky.ql_nhat_ky_ngay_tao, ql_nguoi_dung.ql_nguoi_dung_id, ql_nguoi_dung.ql_nguoi_dung_ho_ten, ql_nguoi_dung.ql_nguoi_dung_email')
            ->from('ql_nhat_ky')
            ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = ql_nhat_ky.ql_nguoi_dung_id', 'left')
            ->where('ql_nhat_ky_bang_du_lieu', 'dx_de_xuat');

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
            'create' => 'Tạo mới',
            'update' => 'Cập nhật',
            'delete' => 'Xóa',
            'approve' => 'Duyệt',
            'reject' => 'Từ chối',
        ];

        $fieldLabels = [
            'tieu_de'             => 'Tiêu đề',
            'noi_dung'            => 'Nội dung',
            'trang_thai'          => 'Trạng thái',
            'id_dx_loai_de_xuat'  => 'Loại đề xuất (ID)',
            'nhap'                => 'Nháp',
            'da_duyet'            => 'Đã duyệt',
            'ly_do'               => 'Lý do',
            'id_de_xuat'          => 'ID đề xuất',
            'created_user_id'     => 'Người tạo',
            'updated_user_id'     => 'Người cập nhật',
        ];

        $skipFields = ['created_at', 'updated_at', 'deleted_at'];

        $data = array_map(function ($item) use ($hanhDongMap, $fieldLabels, $skipFields) {
            $giaTri_cu  = $item['ql_nhat_ky_gia_tri_cu']  ? json_decode($item['ql_nhat_ky_gia_tri_cu'], true)  : null;
            $giaTri_moi = $item['ql_nhat_ky_gia_tri_moi'] ? json_decode($item['ql_nhat_ky_gia_tri_moi'], true) : null;

            $chi_tiet = [];
            if (is_array($giaTri_cu) && is_array($giaTri_moi)) {
                foreach ($giaTri_moi as $key => $valMoi) {
                    if (in_array($key, $skipFields)) continue;
                    $valCu = isset($giaTri_cu[$key]) ? $giaTri_cu[$key] : null;
                    if ($valCu !== $valMoi) {
                        $label = isset($fieldLabels[$key]) ? $fieldLabels[$key] : $key;
                        $chi_tiet[$label] = ['cu' => $valCu, 'moi' => $valMoi];
                    }
                }
            }

            $hanhDongRaw   = strtolower(trim($item['ql_nhat_ky_hanh_dong'] ?? ''));
            $hanhDongLabel = isset($hanhDongMap[$hanhDongRaw])
                ? $hanhDongMap[$hanhDongRaw]
                : ucfirst($item['ql_nhat_ky_hanh_dong'] ?? '');

            return [
                'id'                    => $item['ql_nhat_ky_id'],
                'hanh_dong'             => $hanhDongLabel,
                'noi_dung'              => $item['ql_nhat_ky_noi_dung'],
                'bang_du_lieu'          => $item['ql_nhat_ky_bang_du_lieu'],
                'ten_nguoi_thuc_hien'   => $item['ql_nguoi_dung_ho_ten'],
                'email_nguoi_thuc_hien' => $item['ql_nguoi_dung_email'],
                'thoi_gian'             => $item['ql_nhat_ky_ngay_tao'],
                'chi_tiet'              => $chi_tiet,
            ];
        }, $log);

        resSuccess($data, 'Lấy lịch sử đề xuất thành công', REST_Controller::HTTP_OK, true, [
            'total' => $total,
        ]);
    }

    /**
     * Gửi email thông báo đề xuất
     *
     * @param array  $emailData  Dữ liệu truyền vào template (ho_va_ten, tieu_de, ten_loai_de_xuat,
     *                           trang_thai, email, file_dinh_kem, danh_sach_nguoi_duyet, link_duyet, cc, bcc, ...)
     * @return bool
     */
    private function sendDeXuatEmail($emailData)
    {
        try {
            // Kiểm tra domain - chỉ gửi email thật khi là production
            $currentDomain = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : (isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER'], PHP_URL_SCHEME) . '://' . parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) : '');
            $isProduction = ($currentDomain === 'https://myoffice.nctu.edu.vn' || $currentDomain === 'http://myoffice.nctu.edu.vn');

            // Nếu không phải production, return true mà không gửi email để tránh fail logic
            if (!$isProduction) {
                log_message('info', '[Dexuat] Bỏ qua gửi email do không ở domain production. Hiện tại: ' . $currentDomain);
                return true;
            }

            // Render template
            $viewData = ['data' => $emailData];
            $message = $this->load->view('email/dexuat_template.php', $viewData, true);

            // Địa chỉ email người nhận
            $email = isset($emailData['email']) ? $emailData['email'] : '';
            if (empty($email)) {
                return false;
            }

            // Xây dựng subject
            $hoVaTen = isset($emailData['ho_va_ten']) ? $emailData['ho_va_ten'] : '';
            $tenLoaiDeXuat = isset($emailData['ten_loai_de_xuat']) ? $emailData['ten_loai_de_xuat'] : '';
            $tieuDe = isset($emailData['tieu_de']) ? $emailData['tieu_de'] : '';

            $subject = "Đề xuất";
            if ($tieuDe)
                $subject .= " - {$tieuDe}";
            if ($hoVaTen)
                $subject .= " - {$hoVaTen}";

            // File đính kèm (nếu có — mảng đường dẫn file vật lý)
            $file_dinh_kem = isset($emailData['file_dinh_kem']) ? $emailData['file_dinh_kem'] : [];

            // CC / BCC
            $cc = isset($emailData['cc']) ? $emailData['cc'] : [];
            $bcc = isset($emailData['bcc']) ? $emailData['bcc'] : [];

            // Gửi email
            $result = send_email(
                $email,
                $subject,
                $message,
                'noreply@tchc.nctu.edu.vn',
                'Trường Đại Học Nam Cần Thơ - DNC University',
                $file_dinh_kem,
                $cc,
                $bcc
            );

            return $result;
        } catch (Exception $e) {
            log_message('error', 'Lỗi gửi email đề xuất: ' . $e->getMessage());
            return false;
        }
    }
    /**
     * Ghi log hành động vào hệ thống
     */
    public function createLog($hanh_dong, $noi_dung = '', $gia_tri_cu = [], $gia_tri_moi = [], $bang_du_lieu = '')
    {
        $this->load->model('Ql_nhat_ky_model');
        $controller = 'dexuat';
        
        $user_login = $this->getUserLogin();
        // Lấy ID người dùng từ payload
        $ql_nguoi_dung_id = isset($user_login['ql_nguoi_dung_id']) ? $user_login['ql_nguoi_dung_id'] : (isset($user_login['user_id']) ? $user_login['user_id'] : null);
        
        $data = [
            'ql_nguoi_dung_id' => $ql_nguoi_dung_id,
            'ql_nhat_ky_hanh_dong' => $hanh_dong,
            'ql_nhat_ky_noi_dung' => $noi_dung,
            'ql_nhat_ky_gia_tri_cu' => json_encode($gia_tri_cu),
            'ql_nhat_ky_gia_tri_moi' => json_encode($gia_tri_moi),
            'ql_nhat_ky_bang_du_lieu' => $bang_du_lieu,
            'ql_nhat_ky_controller' => $controller
        ];

        return $this->Ql_nhat_ky_model->insert($data);
    }
}
