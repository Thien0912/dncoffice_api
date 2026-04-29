<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property Hrm_ngoaigio_model $Hrm_ngoaigio_model
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property CI_DB_query_builder $db
 */
class Ngoaigio extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');

        $this->load->model(['Hrm_ngoaigio_model', 'Hrm_nhan_vien_model']);
        $this->load->library(['Validator', 'Fileupload', 'Common']);

        // Phân quyền: Bỏ qua bắt quyền đối với các chức năng công khai
        $publicSegments = [
            'ngoaigio.index',
            'ngoaigio.create',
            'ngoaigio.update',
            'ngoaigio.delete',
            'ngoaigio.detail',
            'ngoaigio.approve',
            'ngoaigio.tong_gio_theo_loai_ngay',
            'ngoaigio.ngay_le',
            'ngoaigio.bang_cham_cong',
            'ngoaigio.export_excel',
            'ngoaigio.statistics',
            'ngoaigio.cancel',
            'ngoaigio.change_status',
            'ngoaigio.logs',
            'ngoaigio.bang_cham_cong_khoa',
            'ngoaigio.bang_cham_cong_duyet',
            'ngoaigio.bang_cham_cong_delete',
            'ngoaigio.bang_cham_cong_lock_dates',
            'ngoaigio.bang_cham_cong_unlock_dates',

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
        $nhanVienRaw = $this->Hrm_nhan_vien_model
            ->select('hrm_nhan_vien.id_nhan_vien, hrm_nhan_vien.id_don_vi_cong_tac, e_don_vi.ma_don_vi')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_nhan_vien.id_don_vi_cong_tac', 'left')
            ->where('hrm_nhan_vien.ql_nguoi_dung_id', $qlNguoiDungId)
            ->first();
        $nhanVien = is_array($nhanVienRaw) || is_object($nhanVienRaw) ? (array) $nhanVienRaw : [];

        $idNhanVien = !empty($nhanVien['id_nhan_vien']) ? $nhanVien['id_nhan_vien'] : null;
        $idDonViCongTac = !empty($nhanVien['id_don_vi_cong_tac']) ? $nhanVien['id_don_vi_cong_tac'] : $auth['id_don_vi'];
        $maDonVi = !empty($nhanVien['ma_don_vi']) ? $nhanVien['ma_don_vi'] : null;

        // dd($idDonViCongTac);

        $data = $this->Hrm_ngoaigio_model->getAll(
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

        // dd($data);
        resSuccess($data, 'Lấy danh sách ngoài giờ thành công', REST_Controller::HTTP_OK);
    }

    public function create_post()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Vui lòng đăng nhập', REST_Controller::HTTP_UNAUTHORIZED);
        }

        $idNhanVienInput = commonRequest('id_nhan_vien');
        $danhSachDangKy = commonRequest('data');
        $isDotXuat = commonRequest('is_dot_xuat');

        // Chuẩn hóa id_nhan_vien thành mảng
        if (is_string($idNhanVienInput)) {
            $decodedIds = json_decode($idNhanVienInput, true);
            $idsNhanVien = is_array($decodedIds) ? $decodedIds : [$idNhanVienInput];
        } elseif (is_array($idNhanVienInput)) {
            $idsNhanVien = $idNhanVienInput;
        } else {
            $idsNhanVien = $idNhanVienInput ? [$idNhanVienInput] : [];
        }

        // Chuẩn hóa danhSachDangKy
        if (is_string($danhSachDangKy)) {
            $decoded = json_decode($danhSachDangKy, true);
            $danhSachDangKy = $decoded ? $decoded : [];
        }

        if (empty($idsNhanVien) || empty($danhSachDangKy) || !is_array($danhSachDangKy)) {
            resError('Vui lòng nhập đầy đủ thông tin (Nhân viên và Danh sách ngày tăng ca)', REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->db->trans_start();

        try {
            $allIdsCreated = []; // Tất cả các đơn đã tạo cho tất cả nhân viên

            // Loop qua từng nhân viên
            foreach ($idsNhanVien as $idNhanVien) {
                $nhanVienRaw = $this->Hrm_nhan_vien_model
                    ->select('id_don_vi_cong_tac')
                    ->where('id_nhan_vien', $idNhanVien)
                    ->first();
                $nhanVien = is_array($nhanVienRaw) || is_object($nhanVienRaw) ? (array) $nhanVienRaw : [];

                if (empty($nhanVien)) {
                    throw new Exception("Không tìm thấy thông tin nhân viên ID: $idNhanVien");
                }

                $idsCreated = []; // Các đơn đã tạo cho nhân viên này

                foreach ($danhSachDangKy as $item) {
                    // Đảm bảo item là mảng
                    if (is_object($item)) {
                        $item = (array) $item;
                    }

                    $ngay = isset($item['ngay_dang_ky']) ? $item['ngay_dang_ky'] : null;
                    $soGio = isset($item['so_gio']) ? $item['so_gio'] : 0;
                    $gioBatDau = isset($item['gio_bat_dau']) ? $item['gio_bat_dau'] : null;
                    $gioKetThuc = isset($item['gio_ket_thuc']) ? $item['gio_ket_thuc'] : null;
                    $noiDung = isset($item['noi_dung']) ? $item['noi_dung'] : null;
                    $chiTiet = isset($item['chi_tiet']) ? $item['chi_tiet'] : null;

                    if (empty($ngay))
                        continue;

                    // === VALIDATION MỚI: Kiểm tra bảng chấm công ===

                    // 1. Tìm bảng chấm công chứa ngày đăng ký này
                    $bangChamCong = $this->db
                        ->select('id, trang_thai, ten_bang, ngay_bat_dau, ngay_ket_thuc, locked_dates')
                        ->from('hrm_bang_cham_cong_thang')
                        ->where('deleted_at IS NULL')
                        ->where('ngay_bat_dau <=', $ngay)
                        ->where('ngay_ket_thuc >=', $ngay)
                        ->get()
                        ->row_array();

                    if (!$bangChamCong) {
                        throw new Exception("Ngày $ngay không thuộc bất kỳ kỳ chấm công nào. Vui lòng liên hệ phòng TCHC để tạo bảng chấm công.");
                    }

                    // 2. Kiểm tra trạng thái bảng chấm công
                    if ($bangChamCong['trang_thai'] === 'KHOA') {
                        throw new Exception("Ngày $ngay thuộc bảng chấm công '{$bangChamCong['ten_bang']}' đã bị khóa. Không thể đăng ký ngoài giờ.");
                    }

                    if ($bangChamCong['trang_thai'] === 'BI_TU_CHOI') {
                        throw new Exception("Ngày $ngay thuộc bảng chấm công '{$bangChamCong['ten_bang']}' đã bị từ chối. Không thể đăng ký ngoài giờ.");
                    }

                    // 3. Kiểm tra ngày có bị khóa trong locked_dates không
                    if (!empty($bangChamCong['locked_dates'])) {
                        $lockedDatesArr = json_decode($bangChamCong['locked_dates'], true);
                        if (is_array($lockedDatesArr)) {
                            foreach ($lockedDatesArr as $lockedRange) {
                                $startLocked = $lockedRange['start'] ?? null;
                                $endLocked = $lockedRange['end'] ?? null;
                                if ($startLocked && $endLocked && $ngay >= $startLocked && $ngay <= $endLocked) {
                                    $startFormatted = date('d/m/Y', strtotime($startLocked));
                                    $endFormatted = date('d/m/Y', strtotime($endLocked));
                                    throw new Exception("Ngày $ngay đã bị khóa trong khoảng từ $startFormatted đến $endFormatted. Không thể đăng ký ngoài giờ.");
                                }
                            }
                        }
                    }

                    $idBangChamCong = $bangChamCong['id'];

                    // === KẾT THÚC VALIDATION MỚI ===

                    $checkDuplicate = $this->db
                        ->from('hrm_ngoai_gio')
                        ->where('id_nhan_vien', $idNhanVien)
                        ->where('ngay_dang_ky', $ngay)
                        ->where('deleted_at IS NULL')
                        ->where('trang_thai_tong !=', 'Tu_choi')
                        ->group_start()
                        ->where("('$gioBatDau' < gio_ket_thuc AND '$gioKetThuc' > gio_bat_dau)")
                        ->group_end()
                        ->count_all_results();

                    if ($checkDuplicate > 0) {
                        throw new Exception("Ngày $ngay khung giờ $gioBatDau - $gioKetThuc đã có đăng ký ngoài giờ hơặc bị chồng lấn thời gian.");
                    }

                    // 1. Insert đơn ngoài giờ
                    $dataInsert = [
                        'id_nhan_vien' => $idNhanVien,
                        'id_bang_cham_cong' => $idBangChamCong,
                        'ngay_dang_ky' => $ngay,
                        'gio_bat_dau' => $gioBatDau,
                        'gio_ket_thuc' => $gioKetThuc,
                        'noi_dung' => $noiDung,
                        'chi_tiet' => $chiTiet,
                        'so_gio' => $soGio,
                        'trang_thai_tong' => 'Cho_duyet',
                        'cap_duyet_hien_tai' => 1,
                        'created_user_id' => $auth['ql_nguoi_dung_id']
                    ];

                    if ($isDotXuat == true || $isDotXuat == '1' || $isDotXuat === 1) {
                        $dataInsert['is_dotxuat'] = 1;
                    }

                    $this->db->insert('hrm_ngoai_gio', $dataInsert);
                    $idNgoaiGio = $this->db->insert_id();
                    $idsCreated[] = $idNgoaiGio;

                    // 6. Tạo 2 record placeholder trong hrm_ngoai_gio_nguoi_duyet
                    //    id_nguoi_duyet = NULL — sẽ được điền khi người có quyền bấm Duyệt
                    $this->db->insert('hrm_ngoai_gio_nguoi_duyet', [
                        'id_ngoai_gio' => $idNgoaiGio,
                        'cap_duyet' => 1,
                        'trang_thai' => 'Cho_duyet'
                    ]);
                    $this->db->insert('hrm_ngoai_gio_nguoi_duyet', [
                        'id_ngoai_gio' => $idNgoaiGio,
                        'cap_duyet' => 2,
                        'trang_thai' => 'Cho_duyet'
                    ]);
                } // end foreach danhSachDangKy

                // Thêm các đơn của nhân viên này vào danh sách tổng
                $allIdsCreated = array_merge($allIdsCreated, $idsCreated);
            } // Kết thúc loop nhân viên

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                resError('Có lỗi xảy ra khi lưu dữ liệu');
            }

            // Log tạo đơn ngoài giờ
            $this->createLog(
                'create',
                'Đăng ký ngoài giờ cho ' . count($idsNhanVien) . ' nhân viên',
                null,
                ['ids' => $allIdsCreated, 'id_nhan_vien' => $idsNhanVien, 'so_don' => count($allIdsCreated)],
                'hrm_ngoai_gio'
            );

            resSuccess([
                'ids' => $allIdsCreated,
                'total' => count($allIdsCreated),
                'employees_count' => count($idsNhanVien)
            ], 'Đăng ký ngoài giờ thành công cho ' . count($idsNhanVien) . ' nhân viên, tổng ' . count($allIdsCreated) . ' đơn');

        } catch (Exception $e) {
            $this->db->trans_rollback();
            resError($e->getMessage());
        }
    }

    public function approve_post()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Vui lòng đăng nhập', REST_Controller::HTTP_UNAUTHORIZED);
        }
        $idNguoiDuyet = $auth['ql_nguoi_dung_id'];

        // Nhận dữ liệu
        $ids = commonRequest('ids_ngoai_gio') ?? commonRequest('id_ngoai_gio');
        $hanhDong = commonRequest('hanh_dong'); // 'duyet' hoặc 'tu_choi'
        $lyDo = commonRequest('ly_do') ?? commonRequest('ly_do_duyet');

        // Chuẩn hóa $ids thành mảng
        if (is_string($ids)) {
            $decoded = json_decode($ids, true);
            $ids = is_array($decoded) ? $decoded : [$ids];
        } elseif (!is_array($ids)) {
            $ids = $ids ? [$ids] : [];
        }

        if (empty($ids) || !in_array($hanhDong, ['duyet', 'tu_choi'])) {
            resError('Dữ liệu không hợp lệ (Thiếu ID đơn hoặc hành động)');
        }

        $this->db->trans_start();
        $ketQua = [
            'success_count' => 0,
            'fail_count' => 0,
            'details' => []
        ];

        try {
            foreach ($ids as $id) {
                // 1. Kiểm tra đơn tồn tại
                $don = $this->db
                    ->where('id_ngoai_gio', $id)
                    ->where('deleted_at IS NULL')
                    ->get('hrm_ngoai_gio')
                    ->row_array();

                if (!$don) {
                    $ketQua['fail_count']++;
                    $ketQua['details'][] = ['id' => $id, 'success' => false, 'message' => 'Đơn không tồn tại'];
                    continue;
                }

                // 2. Kiểm tra trạng thái đơn
                if ($don['trang_thai_tong'] != 'Cho_duyet') {
                    $ketQua['fail_count']++;
                    $ketQua['details'][] = ['id' => $id, 'success' => false, 'message' => 'Đơn đã được xử lý'];
                    continue;
                }

                $capHienTai = $don['cap_duyet_hien_tai'];

                // 3. Lấy đơn vị của nhân viên
                $idDonVi = null;
                $nhanVienInfo = $this->db
                    ->select('id_don_vi_cong_tac')
                    ->where('id_nhan_vien', $don['id_nhan_vien'])
                    ->where('deleted_at IS NULL')
                    ->get('hrm_nhan_vien')
                    ->row_array();
                if ($nhanVienInfo) {
                    $idDonVi = $nhanVienInfo['id_don_vi_cong_tac'];
                }

                // 4. Kiểm tra quyền theo cap duyệt hiện tại
                $coQuyen = false;

                if ($capHienTai == 1) {
                    // Cấp 1: người duyệt phải là lãnh đạo đơn vị của nhân viên
                    if ($idDonVi) {
                        $coQuyen = $this->db
                            ->where('id_don_vi', $idDonVi)
                            ->where('ql_nguoi_dung_id', $idNguoiDuyet)
                            ->where('deleted_at IS NULL')
                            ->count_all_results('e_lanh_dao_don_vi') > 0;
                    }
                } elseif ($capHienTai == 2) {
                    // Cấp 2: người duyệt phải có quyền ngoaigio.duyetbytochuc
                    $coQuyen = $this->db
                        ->from('ql_nguoi_dung')
                        ->join('ql_vai_tro_nguoi_dung', 'ql_vai_tro_nguoi_dung.ql_nguoi_dung_id = ql_nguoi_dung.ql_nguoi_dung_id', 'inner')
                        ->join('ql_vai_tro_quyen', 'ql_vai_tro_quyen.ql_vai_tro_id = ql_vai_tro_nguoi_dung.ql_vai_tro_id', 'inner')
                        ->join('ql_quyen', 'ql_quyen.ql_quyen_id = ql_vai_tro_quyen.ql_quyen_id', 'inner')
                        ->where('ql_quyen.ql_quyen_khoa', 'ngoaigio.duyetbytochuc')
                        ->where('ql_nguoi_dung.ql_nguoi_dung_id', $idNguoiDuyet)
                        ->where('ql_nguoi_dung.active_flag', 1)
                        ->count_all_results() > 0;
                }

                if (!$coQuyen) {
                    $ketQua['fail_count']++;
                    // Kiểm tra xem người dùng có phải là người đã duyệt cấp 1 không
                    // → Nếu có, thông báo rõ hơn thay vì "không có quyền"
                    if ($capHienTai == 2) {
                        $daDuyetCap1 = $this->db
                            ->where('id_ngoai_gio', $id)
                            ->where('cap_duyet', 1)
                            ->where('id_nguoi_duyet', $idNguoiDuyet)
                            ->where('trang_thai', 'Da_duyet')
                            ->count_all_results('hrm_ngoai_gio_nguoi_duyet') > 0;

                        if ($daDuyetCap1) {
                            $ketQua['details'][] = ['id' => $id, 'success' => false, 'message' => 'Bạn đã duyệt cấp 1 rồi. Đơn đang chờ TCHC duyệt cấp 2.'];
                        } else {
                            $ketQua['details'][] = ['id' => $id, 'success' => false, 'message' => 'Đơn đã qua cấp 1, đang chờ TCHC duyệt cấp 2. Bạn không có quyền duyệt ở cấp này.'];
                        }
                    } else {
                        $ketQua['details'][] = ['id' => $id, 'success' => false, 'message' => 'Bạn không có quyền duyệt đơn này (cấp ' . $capHienTai . ')'];
                    }
                    continue;
                }

                // 5. Tìm và cập nhật record placeholder (Cho_duyet, chưa có người duyệt)
                $statusNguoiDuyet = ($hanhDong == 'duyet') ? 'Da_duyet' : 'Tu_choi';

                // Placeholder có thể có id_nguoi_duyet = NULL hoặc = 0 (column default)
                $placeholder = $this->db
                    ->where('id_ngoai_gio', $id)
                    ->where('cap_duyet', $capHienTai)
                    ->where('trang_thai', 'Cho_duyet')
                    ->group_start()
                    ->where('id_nguoi_duyet IS NULL')
                    ->or_where('id_nguoi_duyet', 0)
                    ->group_end()
                    ->get('hrm_ngoai_gio_nguoi_duyet')
                    ->row_array();

                if ($placeholder) {
                    // Cập nhật placeholder: điền id_nguoi_duyet và đổi trạng thái
                    $this->db
                        ->where('id_ngoai_gio_nguoi_duyet', $placeholder['id_ngoai_gio_nguoi_duyet'])
                        ->update('hrm_ngoai_gio_nguoi_duyet', [
                            'id_nguoi_duyet' => $idNguoiDuyet,
                            'trang_thai' => $statusNguoiDuyet,
                            'thoi_gian_duyet' => date('Y-m-d H:i:s'),
                            'ly_do_duyet' => $lyDo
                        ]);
                } else {
                    // Không tìm thấy placeholder chờ duyệt → kiểm tra nguyên nhân
                    $existingRecord = $this->db
                        ->where('id_ngoai_gio', $id)
                        ->where('cap_duyet', $capHienTai)
                        ->where('deleted_at IS NULL')
                        ->get('hrm_ngoai_gio_nguoi_duyet')
                        ->row_array();

                    if ($existingRecord && $existingRecord['trang_thai'] !== 'Cho_duyet') {
                        // Record đã tồn tại và đã được xử lý thực sự (Da_duyet / Tu_choi)
                        $ketQua['fail_count']++;
                        $ketQua['details'][] = ['id' => $id, 'success' => false, 'message' => 'Đơn cấp ' . $capHienTai . ' đã được duyệt trước đó'];
                        continue;
                    }

                    if ($existingRecord) {
                        // Record cấp 1 tồn tại nhưng chưa có người duyệt → UPDATE record đó
                        $this->db
                            ->where('id_ngoai_gio_nguoi_duyet', $existingRecord['id_ngoai_gio_nguoi_duyet'])
                            ->update('hrm_ngoai_gio_nguoi_duyet', [
                                'id_nguoi_duyet' => $idNguoiDuyet,
                                'trang_thai' => $statusNguoiDuyet,
                                'thoi_gian_duyet' => date('Y-m-d H:i:s'),
                                'ly_do_duyet' => $lyDo
                            ]);
                    } else {
                        // Hoàn toàn không có record nào (đơn rất cũ) → INSERT record cấp 1
                        $this->db->insert('hrm_ngoai_gio_nguoi_duyet', [
                            'id_ngoai_gio' => $id,
                            'cap_duyet' => $capHienTai,
                            'id_nguoi_duyet' => $idNguoiDuyet,
                            'trang_thai' => $statusNguoiDuyet,
                            'thoi_gian_duyet' => date('Y-m-d H:i:s'),
                            'ly_do_duyet' => $lyDo
                        ]);
                    }
                }

                // 6. Cập nhật trạng thái tổng đơn
                if ($hanhDong == 'tu_choi') {
                    $this->db->where('id_ngoai_gio', $id)->update('hrm_ngoai_gio', [
                        'trang_thai_tong' => 'Tu_choi',
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                } else {
                    // Duyệt: kiểm tra còn placeholder chưa duyệt cùng cap không
                    $chuaDuyetCungCap = $this->db
                        ->where('id_ngoai_gio', $id)
                        ->where('cap_duyet', $capHienTai)
                        ->where('trang_thai', 'Cho_duyet')
                        ->count_all_results('hrm_ngoai_gio_nguoi_duyet');

                    if ($chuaDuyetCungCap == 0) {
                        // Cap này xong → tìm cap tiếp theo
                        $capTiepTheo = $this->db
                            ->select('cap_duyet')
                            ->where('id_ngoai_gio', $id)
                            ->where('cap_duyet >', $capHienTai)
                            ->where('trang_thai', 'Cho_duyet')
                            ->order_by('cap_duyet', 'ASC')
                            ->limit(1)
                            ->get('hrm_ngoai_gio_nguoi_duyet')
                            ->row_array();

                        if ($capTiepTheo) {
                            $this->db->where('id_ngoai_gio', $id)->update('hrm_ngoai_gio', [
                                'cap_duyet_hien_tai' => $capTiepTheo['cap_duyet'],
                                'updated_at' => date('Y-m-d H:i:s')
                            ]);
                        } else {
                            // Không thấy cap tiếp theo có placeholder 'Cho_duyet'
                            // Nếu đơn cũ (cap 1) chưa có bất kỳ record cap 2 nào → tạo placeholder cap 2
                            $coRecordCap2 = $this->db
                                ->where('id_ngoai_gio', $id)
                                ->where('cap_duyet', 2)
                                ->where('deleted_at IS NULL')
                                ->count_all_results('hrm_ngoai_gio_nguoi_duyet');

                            if ($capHienTai == 1 && $coRecordCap2 == 0) {
                                // Đơn tạo trước khi có logic cấp 2 → tạo placeholder cấp 2 và chờ duyệt
                                $this->db->insert('hrm_ngoai_gio_nguoi_duyet', [
                                    'id_ngoai_gio' => $id,
                                    'cap_duyet' => 2,
                                    'trang_thai' => 'Cho_duyet'
                                ]);
                                $this->db->where('id_ngoai_gio', $id)->update('hrm_ngoai_gio', [
                                    'cap_duyet_hien_tai' => 2,
                                    'updated_at' => date('Y-m-d H:i:s')
                                ]);
                            } else {
                                // Hết tất cả các cap → hoàn tất duyệt
                                $this->db->where('id_ngoai_gio', $id)->update('hrm_ngoai_gio', [
                                    'trang_thai_tong' => 'Da_duyet',
                                    'updated_at' => date('Y-m-d H:i:s')
                                ]);
                            }
                        }
                    }
                    // Còn placeholder chưa duyệt cùng cap → giữ nguyên
                }

                $ketQua['success_count']++;
                $ketQua['details'][] = ['id' => $id, 'success' => true, 'message' => 'Xử lý thành công'];
            }

            $this->db->trans_complete();

            // Log approve/reject
            $this->createLog(
                $hanhDong == 'duyet' ? 'approve' : 'reject',
                ($hanhDong == 'duyet' ? 'Duyệt' : 'Từ chối') . ' đơn ngoài giờ',
                $ids,
                $ketQua,
                'hrm_ngoai_gio'
            );

            resSuccess($ketQua, 'Hoàn tất quá trình phê duyệt');

        } catch (Exception $e) {
            $this->db->trans_rollback();
            resError($e->getMessage());
        }
    }


    /**
     * Cập nhật đơn ngoài giờ
     * POST /api/v2/admin/hrm/ngoaigio/update/:id
     * Chỉ cho phép khi đơn ở trạng thái 'Cho_duyet' và người gọi là người tạo đơn
     */
    public function update_post()
    {
        $auth = $this->getUserLogin();
        $id = commonRequest('id_ngoai_gio');
        // Kiểm tra đơn tồn tại
        $don = $this->db
            ->where('id_ngoai_gio', $id)
            ->where('deleted_at IS NULL')
            ->get('hrm_ngoai_gio')
            ->row_array();
        if (!$auth) {
            resError('Vui lòng đăng nhập', REST_Controller::HTTP_UNAUTHORIZED);
        }


        if (!$don) {
            resError('Không tìm thấy đơn ngoài giờ', REST_Controller::HTTP_NOT_FOUND);
        }

        // Chỉ người tạo mới được sửa
        if ($don['created_user_id'] != $auth['ql_nguoi_dung_id']) {
            resError('Bạn không có quyền cập nhật đơn này', REST_Controller::HTTP_FORBIDDEN);
        }

        // Chỉ sửa khi đang chờ duyệt
        if ($don['trang_thai_tong'] != 'Cho_duyet') {
            resError('Chỉ có thể cập nhật đơn đang ở trạng thái Chờ duyệt', REST_Controller::HTTP_BAD_REQUEST);
        }

        $ngay = commonRequest('ngay_dang_ky');
        $gioBatDau = commonRequest('gio_bat_dau');
        $gioKetThuc = commonRequest('gio_ket_thuc');
        $soGio = commonRequest('so_gio');
        $noiDung = commonRequest('noi_dung');
        $chiTiet = commonRequest('chi_tiet');


        // Kiểm tra trùng giờ (bỏ qua chính đơn đang sửa)
        if ($ngay || $gioBatDau || $gioKetThuc) {
            $ngayCheck = $ngay ?? $don['ngay_dang_ky'];
            $gioBatDauCheck = $gioBatDau ?? $don['gio_bat_dau'];
            $gioKetThucCheck = $gioKetThuc ?? $don['gio_ket_thuc'];

            $checkDuplicate = $this->db
                ->from('hrm_ngoai_gio')
                ->where('id_nhan_vien', $don['id_nhan_vien'])
                ->where('ngay_dang_ky', $ngayCheck)
                ->where('deleted_at IS NULL')
                ->where('trang_thai_tong !=', 'Tu_choi')
                ->where('id_ngoai_gio !=', $id)
                ->group_start()
                ->where("('$gioBatDauCheck' < gio_ket_thuc AND '$gioKetThucCheck' > gio_bat_dau)")
                ->group_end()
                ->count_all_results();

            if ($checkDuplicate > 0) {
                resError("Khung giờ $gioBatDauCheck - $gioKetThucCheck ngày $ngayCheck đã bị chồng lấn với đăng ký khác", REST_Controller::HTTP_CONFLICT);
            }
        }

        $dataUpdate = [];
        if ($ngay !== null)
            $dataUpdate['ngay_dang_ky'] = $ngay;
        if ($gioBatDau !== null)
            $dataUpdate['gio_bat_dau'] = $gioBatDau;
        if ($gioKetThuc !== null)
            $dataUpdate['gio_ket_thuc'] = $gioKetThuc;
        if ($soGio !== null)
            $dataUpdate['so_gio'] = $soGio;
        if ($noiDung !== null)
            $dataUpdate['noi_dung'] = $noiDung;
        if ($chiTiet !== null)
            $dataUpdate['chi_tiet'] = $chiTiet;
        $dataUpdate['updated_at'] = date('Y-m-d H:i:s');

        if (empty($dataUpdate)) {
            resError('Không có dữ liệu cần cập nhật', REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->db->where('id_ngoai_gio', $id)->update('hrm_ngoai_gio', $dataUpdate);

        // Log cập nhật
        $this->createLog(
            'update',
            'Cập nhật đơn ngoài giờ ID: ' . $id,
            $don,
            array_merge($don, $dataUpdate),
            'hrm_ngoai_gio'
        );

        resSuccess(['id_ngoai_gio' => $id], 'Cập nhật đơn ngoài giờ thành công');
    }

    /**
     * Xóa đơn ngoài giờ (soft delete)
     * POST /api/v2/admin/hrm/ngoaigio/delete/:id
     * Chỉ cho phép khi đơn ở trạng thái 'Cho_duyet' và người gọi là người tạo đơn
     */
    /**
     * Xóa tạm đơn ngoài giờ (Bulk hỗ trợ)
     */
    public function delete_post($id = null)
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Vui lòng đăng nhập', REST_Controller::HTTP_UNAUTHORIZED);
        }

        // Lấy danh sách IDs từ request (Hỗ trợ cả đơn lẻ và mảng)
        $ids = commonRequest('ids') ?? commonRequest('ids_ngoai_gio') ?? $id;
        if (is_string($ids)) {
            $decoded = json_decode($ids, true);
            $ids = is_array($decoded) ? $decoded : [$ids];
        } elseif (!is_array($ids)) {
            $ids = $ids ? [$ids] : [];
        }

        if (empty($ids)) {
            resError('Thiếu ID đơn ngoài giờ', REST_Controller::HTTP_BAD_REQUEST);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->trans_start();

        foreach ($ids as $idNgoaiGio) {
            // Kiểm tra quyền (chỉ người tạo mới được xóa đơn đang chờ duyệt)
            $don = $this->db->where(['id_ngoai_gio' => $idNgoaiGio, 'deleted_at' => null])->get('hrm_ngoai_gio')->row_array();
            if (!$don)
                continue;

            if ($don['created_user_id'] != $auth['ql_nguoi_dung_id'] && !in_array('ngoaigio.xemtatca', $auth['permissions'] ?? [])) {
                // Nếu không phải người tạo và không phải admin thì bỏ qua
                continue;
            }

            // Soft delete đơn chính
            $this->db->where('id_ngoai_gio', $idNgoaiGio)
                ->update('hrm_ngoai_gio', ['deleted_at' => $now, 'deleted_user_id' => $auth['ql_nguoi_dung_id']]);

            // Soft delete người duyệt
            $this->db->where('id_ngoai_gio', $idNgoaiGio)
                ->update('hrm_ngoai_gio_nguoi_duyet', ['deleted_at' => $now]);

            // Log
            $this->createLog('delete', 'Xóa tạm đơn ngoài giờ ID: ' . $idNgoaiGio, $don, null, 'hrm_ngoai_gio');
        }

        $this->db->trans_complete();
        resSuccess(['ids' => $ids], 'Đã chuyển ' . count($ids) . ' đơn vào thùng rác');
    }

    /**
     * Lấy danh sách đơn ngoài giờ đã xóa (Thùng rác)
     * GET /api/v2/admin/hrm/ngoaigio/trash
     */
    public function trash_get()
    {
        $start = commonRequest('start') ?: 0;
        $length = commonRequest('length') ?: 10;
        $searchValue = commonRequest('searchValue');

        $auth = $this->getUserLogin();
        $qlNguoiDungId = $auth['ql_nguoi_dung_id'];

        $data = $this->Hrm_ngoaigio_model->getTrash(
            $start,
            $length,
            $searchValue,
            $qlNguoiDungId
        );

        resSuccess($data, 'Lấy danh sách thùng rác thành công');
    }

    /**
     * Khôi phục đơn ngoài giờ (Bulk hỗ trợ)
     */
    public function restore_post($id = null)
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Vui lòng đăng nhập', REST_Controller::HTTP_UNAUTHORIZED);
        }

        $ids = commonRequest('ids') ?? commonRequest('ids_ngoai_gio') ?? $id;
        if (is_string($ids)) {
            $decoded = json_decode($ids, true);
            $ids = is_array($decoded) ? $decoded : [$ids];
        } elseif (!is_array($ids)) {
            $ids = $ids ? [$ids] : [];
        }

        if (empty($ids)) {
            resError('Thiếu ID đơn ngoài giờ', REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->db->trans_start();
        foreach ($ids as $idNgoaiGio) {
            $this->db->where('id_ngoai_gio', $idNgoaiGio)->update('hrm_ngoai_gio', [
                'deleted_at' => null,
                'deleted_user_id' => null,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $this->db->where('id_ngoai_gio', $idNgoaiGio)->update('hrm_ngoai_gio_nguoi_duyet', ['deleted_at' => null]);
            $this->createLog('restore', 'Khôi phục đơn ngoài giờ ID: ' . $idNgoaiGio, null, null, 'hrm_ngoai_gio');
        }
        $this->db->trans_complete();

        resSuccess(['ids' => $ids], 'Đã khôi phục ' . count($ids) . ' đơn ngoài giờ');
    }

    /**
     * Xóa vĩnh viễn đơn ngoài giờ (Bulk hỗ trợ)
     * POST /api/v2/admin/hrm/ngoaigio/destroy/:id
     */
    public function destroy_post($id = null)
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Vui lòng đăng nhập', REST_Controller::HTTP_UNAUTHORIZED);
        }

        $ids = commonRequest('ids') ?? commonRequest('ids_ngoai_gio') ?? $id;
        if (is_string($ids)) {
            $decoded = json_decode($ids, true);
            $ids = is_array($decoded) ? $decoded : [$ids];
        } elseif (!is_array($ids)) {
            $ids = $ids ? [$ids] : [];
        }

        if (empty($ids)) {
            resError('Thiếu ID đơn ngoài giờ', REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->db->trans_start();
        foreach ($ids as $idNgoaiGio) {
            // Log trước khi xóa vĩnh viễn
            $don = $this->db->where('id_ngoai_gio', $idNgoaiGio)->get('hrm_ngoai_gio')->row_array();
            if (!$don)
                continue;

            $this->db->where('id_ngoai_gio', $idNgoaiGio)->delete('hrm_ngoai_gio');
            $this->db->where('id_ngoai_gio', $idNgoaiGio)->delete('hrm_ngoai_gio_nguoi_duyet');

            $this->createLog('destroy', 'Xóa vĩnh viễn đơn ngoài giờ ID: ' . $idNgoaiGio, $don, null, 'hrm_ngoai_gio');
        }
        $this->db->trans_complete();

        resSuccess(['ids' => $ids], 'Đã xóa vĩnh viễn ' . count($ids) . ' đơn ngoài giờ');
    }

    /**
     * Lấy chi tiết đơn ngoài giờ
     * GET /api/v2/admin/hrm/ngoaigio/detail/:id
     */
    public function detail_get($id = null)
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Vui lòng đăng nhập', REST_Controller::HTTP_UNAUTHORIZED);
        }

        if (!$id) {
            resError('Thiếu ID đơn ngoài giờ', REST_Controller::HTTP_BAD_REQUEST);
        }

        // Lấy thông tin đơn + nhân viên + đơn vị + chấm công thực tế
        $don = $this->db
            ->select('
                ng.*,
                nv.ho_va_ten, nv.ma_nhan_vien, nv.avatar, nv.ql_nguoi_dung_id AS nhan_vien_ql_nguoi_dung_id,
                dv.ten_don_vi, dv.ma_don_vi,
                nd_tao.ql_nguoi_dung_ho_ten AS nguoi_tao_ho_ten,
                IF(ng.created_user_id IS NOT NULL AND ng.created_user_id != nv.ql_nguoi_dung_id, 1, 0) AS tao_ho,
                MIN(cc.punch_time) AS thoi_gian_bat_dau_cham_cong,
                MAX(cc.punch_time) AS thoi_gian_ket_thuc_cham_cong
            ', FALSE)
            ->from('hrm_ngoai_gio ng')
            ->join('hrm_nhan_vien nv', 'nv.id_nhan_vien = ng.id_nhan_vien', 'left')
            ->join('e_don_vi dv', 'dv.id_don_vi = nv.id_don_vi_cong_tac', 'left')
            ->join('ql_nguoi_dung nd_tao', 'nd_tao.ql_nguoi_dung_id = ng.created_user_id', 'left')
            ->join('hrm_api_cham_cong cc', 'cc.emp_code = nv.ma_cham_cong AND DATE(cc.punch_time) = ng.ngay_dang_ky', 'left')
            ->where('ng.id_ngoai_gio', $id)
            ->where('ng.deleted_at IS NULL')
            ->group_by('ng.id_ngoai_gio')
            ->get()
            ->row_array();

        if (!$don) {
            resError('Không tìm thấy đơn ngoài giờ', REST_Controller::HTTP_NOT_FOUND);
        }

        // Lấy danh sách người duyệt
        $nguoiDuyet = $this->db
            ->select('
                nd.id_ngoai_gio_nguoi_duyet,
                nd.cap_duyet,
                nd.trang_thai,
                nd.thoi_gian_duyet,
                nd.ly_do_duyet,
                nd.duyet_ho,
                nd.id_duyet_ho,
                qnd.ql_nguoi_dung_id,
                qnd.ql_nguoi_dung_ho_ten,
                nv.avatar AS ql_nguoi_dung_avatar,
                dv.ten_don_vi,
                nd_duyet_ho.ql_nguoi_dung_ho_ten AS nguoi_duyet_ho_ten
            ')
            ->from('hrm_ngoai_gio_nguoi_duyet nd')
            ->join('ql_nguoi_dung qnd', 'qnd.ql_nguoi_dung_id = nd.id_nguoi_duyet', 'left')
            ->join('hrm_nhan_vien nv', 'nv.ql_nguoi_dung_id = qnd.ql_nguoi_dung_id', 'left')
            ->join('e_don_vi dv', 'dv.id_don_vi = nv.id_don_vi_cong_tac', 'left')
            ->join('ql_nguoi_dung nd_duyet_ho', 'nd_duyet_ho.ql_nguoi_dung_id = nd.id_duyet_ho', 'left')
            ->where('nd.id_ngoai_gio', $id)
            ->order_by('nd.cap_duyet', 'ASC')
            ->get()
            ->result_array();

        if (!empty($don['avatar'])) {
            $don['avatar'] = encryptString($don['avatar']);
        }

        foreach ($nguoiDuyet as &$nd) {
            if (!empty($nd['ql_nguoi_dung_avatar'])) {
                $nd['ql_nguoi_dung_avatar'] = encryptString($nd['ql_nguoi_dung_avatar']);
            }
        }

        $don['danh_sach_nguoi_duyet'] = $nguoiDuyet;

        resSuccess($don, 'Lấy chi tiết đơn ngoài giờ thành công');
    }

    public function import_post()
    {

    }

    /**
     * API: Lấy danh sách bảng chấm công tháng
     * GET /api/v2/admin/hrm/ngoaigio/bang_cham_cong
     */
    public function bang_cham_cong_get()
    {
        $start = commonRequest('start') ?: 0;
        $length = commonRequest('length') ?: 10;
        $searchValue = commonRequest('searchValue') ?: '';
        $trangThai = commonRequest('trang_thai');
        $thang = commonRequest('thang');

        $this->db->select('bcc.*, 
            nd.ql_nguoi_dung_ho_ten as nguoi_duyet_ho_ten,
            creator.ql_nguoi_dung_ho_ten as nguoi_tao_ho_ten')
            ->from('hrm_bang_cham_cong_thang bcc')
            ->join('ql_nguoi_dung nd', 'nd.ql_nguoi_dung_id = bcc.nguoi_duyet', 'left')
            ->join('ql_nguoi_dung creator', 'creator.ql_nguoi_dung_id = bcc.created_user_id', 'left')
            ->where('bcc.deleted_at IS NULL');

        if (!empty($searchValue)) {
            $this->db->group_start()
                ->like('bcc.ten_bang', $searchValue)
                ->or_like('bcc.thang', $searchValue)
                ->group_end();
        }

        if (!empty($trangThai)) {
            $this->db->where('bcc.trang_thai', $trangThai);
        }

        if (!empty($thang)) {
            $this->db->where('bcc.thang', $thang);
        }

        $total = $this->db->count_all_results('', false);

        $data = $this->db->order_by('bcc.ngay_bat_dau', 'DESC')
            ->limit($length, $start)
            ->get()
            ->result_array();

        resSuccess([
            'data' => $data,
            'recordsTotal' => $total,
            'recordsFiltered' => $total
        ], 'Lấy danh sách bảng chấm công thành công');
    }

    /**
     * API: Tạo bảng chấm công tháng mới
     * POST /api/v2/admin/hrm/ngoaigio/bang_cham_cong_create
     */
    public function bang_cham_cong_create_post()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Vui lòng đăng nhập', REST_Controller::HTTP_UNAUTHORIZED);
        }

        $thang = commonRequest('thang');
        $ngayBatDau = commonRequest('ngay_bat_dau');
        $ngayKetThuc = commonRequest('ngay_ket_thuc');
        $tenBang = commonRequest('ten_bang');
        $ghiChu = commonRequest('ghi_chu');

        if (empty($thang) || empty($ngayBatDau) || empty($ngayKetThuc)) {
            resError('Vui lòng nhập đầy đủ thông tin (Tháng, Ngày bắt đầu, Ngày kết thúc)', REST_Controller::HTTP_BAD_REQUEST);
        }

        // Kiểm tra trùng kỳ
        $exists = $this->db->where('thang', $thang)
            ->where('deleted_at IS NULL')
            ->where('(ngay_bat_dau BETWEEN "' . $ngayBatDau . '" AND "' . $ngayKetThuc . '" OR ngay_ket_thuc BETWEEN "' . $ngayBatDau . '" AND "' . $ngayKetThuc . '")')
            ->count_all_results('hrm_bang_cham_cong_thang');

        if ($exists > 0) {
            resError('Đã tồn tại bảng chấm công trong khoảng thời gian này', REST_Controller::HTTP_BAD_REQUEST);
        }

        $dataInsert = [
            'thang' => $thang,
            'ngay_bat_dau' => $ngayBatDau,
            'ngay_ket_thuc' => $ngayKetThuc,
            'ten_bang' => $tenBang ?: "Bảng chấm công tháng $thang",
            'trang_thai' => 'MO',
            'ghi_chu' => $ghiChu,
            'created_user_id' => $auth['ql_nguoi_dung_id']
        ];

        $this->db->insert('hrm_bang_cham_cong_thang', $dataInsert);
        $id = $this->db->insert_id();

        $this->createLog(
            'create',
            "Tạo bảng chấm công tháng {$thang}: {$tenBang}",
            null,
            json_encode($dataInsert),
            'hrm_bang_cham_cong_thang'
        );

        resSuccess(['id' => $id], 'Tạo bảng chấm công thành công');
    }

    /**
     * API: Cập nhật bảng chấm công
     * POST /api/v2/admin/hrm/ngoaigio/bang_cham_cong_update
     */
    public function bang_cham_cong_update_post()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Vui lòng đăng nhập', REST_Controller::HTTP_UNAUTHORIZED);
        }

        $id = commonRequest('id');
        $thang = commonRequest('thang');
        $ngayBatDau = commonRequest('ngay_bat_dau');
        $ngayKetThuc = commonRequest('ngay_ket_thuc');
        $tenBang = commonRequest('ten_bang');
        $ghiChu = commonRequest('ghi_chu');

        if (empty($id)) {
            resError('Vui lòng cung cấp ID bảng chấm công', REST_Controller::HTTP_BAD_REQUEST);
        }

        $bang = $this->db->where('id', $id)
            ->where('deleted_at IS NULL')
            ->get('hrm_bang_cham_cong_thang')
            ->row_array();

        if (!$bang) {
            resError('Không tìm thấy bảng chấm công', REST_Controller::HTTP_NOT_FOUND);
        }

        // Không cho sửa nếu đã duyệt
        if ($bang['trang_thai'] === 'DA_DUYET') {
            resError('Không thể sửa bảng chấm công đã được duyệt', REST_Controller::HTTP_BAD_REQUEST);
        }

        $dataUpdate = [
            'updated_user_id' => $auth['ql_nguoi_dung_id']
        ];

        if (!empty($thang))
            $dataUpdate['thang'] = $thang;
        if (!empty($ngayBatDau))
            $dataUpdate['ngay_bat_dau'] = $ngayBatDau;
        if (!empty($ngayKetThuc))
            $dataUpdate['ngay_ket_thuc'] = $ngayKetThuc;
        if (!empty($tenBang))
            $dataUpdate['ten_bang'] = $tenBang;
        if (isset($ghiChu))
            $dataUpdate['ghi_chu'] = $ghiChu;

        $this->db->where('id', $id)->update('hrm_bang_cham_cong_thang', $dataUpdate);

        $this->createLog(
            'update',
            "Cập nhật bảng chấm công ID: {$id}",
            json_encode($bang),
            json_encode($dataUpdate),
            'hrm_bang_cham_cong_thang'
        );

        resSuccess(['id' => $id], 'Cập nhật bảng chấm công thành công');
    }

    /**
     * API: Khóa/Mở khóa bảng chấm công
     * POST /api/v2/admin/hrm/ngoaigio/bang_cham_cong_khoa
     */
    public function bang_cham_cong_khoa_post()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Vui lòng đăng nhập', REST_Controller::HTTP_UNAUTHORIZED);
        }

        $id = commonRequest('id');
        $trangThai = commonRequest('trang_thai'); // 'KHOA' hoặc 'MO'

        if (empty($id) || !in_array($trangThai, ['KHOA', 'MO'])) {
            resError('Dữ liệu không hợp lệ', REST_Controller::HTTP_BAD_REQUEST);
        }

        $bang = $this->db->where('id', $id)
            ->where('deleted_at IS NULL')
            ->get('hrm_bang_cham_cong_thang')
            ->row_array();

        if (!$bang) {
            resError('Không tìm thấy bảng chấm công', REST_Controller::HTTP_NOT_FOUND);
        }

        $this->db->where('id', $id)->update('hrm_bang_cham_cong_thang', [
            'trang_thai' => $trangThai,
            'updated_user_id' => $auth['ql_nguoi_dung_id']
        ]);

        $action = $trangThai === 'KHOA' ? 'Khóa' : 'Mở khóa';
        $this->createLog(
            'update',
            "{$action} bảng chấm công ID: {$id}",
            json_encode(['trang_thai' => $bang['trang_thai']]),
            json_encode(['trang_thai' => $trangThai]),
            'hrm_bang_cham_cong_thang'
        );

        $message = $trangThai === 'KHOA' ? 'Khóa bảng chấm công thành công' : 'Mở khóa bảng chấm công thành công';
        resSuccess(['id' => $id, 'trang_thai' => $trangThai], $message);
    }

    /**
     * API: Duyệt bảng chấm công
     * POST /api/v2/admin/hrm/ngoaigio/bang_cham_cong_duyet
     */
    public function bang_cham_cong_duyet_post()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Vui lòng đăng nhập', REST_Controller::HTTP_UNAUTHORIZED);
        }

        $id = commonRequest('id');
        $hanhDong = commonRequest('hanh_dong'); // 'duyet' hoặc 'tu_choi'
        $lyDo = commonRequest('ly_do');

        if (empty($id) || !in_array($hanhDong, ['duyet', 'tu_choi'])) {
            resError('Dữ liệu không hợp lệ', REST_Controller::HTTP_BAD_REQUEST);
        }

        $bang = $this->db->where('id', $id)
            ->where('deleted_at IS NULL')
            ->get('hrm_bang_cham_cong_thang')
            ->row_array();

        if (!$bang) {
            resError('Không tìm thấy bảng chấm công', REST_Controller::HTTP_NOT_FOUND);
        }

        $trangThaiMoi = $hanhDong === 'duyet' ? 'DA_DUYET' : 'BI_TU_CHOI';

        $this->db->where('id', $id)->update('hrm_bang_cham_cong_thang', [
            'trang_thai' => $trangThaiMoi,
            'nguoi_duyet' => $auth['ql_nguoi_dung_id'],
            'ngay_duyet' => date('Y-m-d H:i:s'),
            'ly_do_tu_choi' => $hanhDong === 'tu_choi' ? $lyDo : null,
            'updated_user_id' => $auth['ql_nguoi_dung_id']
        ]);

        $action = $hanhDong === 'duyet' ? 'Duyệt' : 'Từ chối';
        $logDesc = $hanhDong === 'tu_choi' && $lyDo ? "{$action} bảng chấm công ID: {$id} - Lý do: {$lyDo}" : "{$action} bảng chấm công ID: {$id}";
        $this->createLog(
            'approve',
            $logDesc,
            json_encode(['trang_thai' => $bang['trang_thai']]),
            json_encode(['trang_thai' => $trangThaiMoi]),
            'hrm_bang_cham_cong_thang'
        );

        $message = $hanhDong === 'duyet' ? 'Duyệt bảng chấm công thành công' : 'Từ chối bảng chấm công';
        resSuccess(['id' => $id, 'trang_thai' => $trangThaiMoi], $message);
    }

    /**
     * API: Xóa bảng chấm công
     * POST /api/v2/admin/hrm/ngoaigio/bang_cham_cong_delete
     */
    public function bang_cham_cong_delete_post()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Vui lòng đăng nhập', REST_Controller::HTTP_UNAUTHORIZED);
        }

        $id = commonRequest('id');

        if (empty($id)) {
            resError('Vui lòng cung cấp ID bảng chấm công', REST_Controller::HTTP_BAD_REQUEST);
        }

        $bang = $this->db->where('id', $id)
            ->where('deleted_at IS NULL')
            ->get('hrm_bang_cham_cong_thang')
            ->row_array();

        if (!$bang) {
            resError('Không tìm thấy bảng chấm công', REST_Controller::HTTP_NOT_FOUND);
        }

        // Kiểm tra xem có đăng ký nào liên kết không
        $countDangKy = $this->db->where('id_bang_cham_cong', $id)
            ->where('deleted_at IS NULL')
            ->count_all_results('hrm_ngoai_gio');

        if ($countDangKy > 0) {
            resError("Không thể xóa bảng chấm công đã có $countDangKy đăng ký liên kết", REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->db->where('id', $id)->update('hrm_bang_cham_cong_thang', [
            'deleted_at' => date('Y-m-d H:i:s')
        ]);

        $this->createLog(
            'delete',
            "Xóa bảng chấm công ID: {$id} - Tháng: {$bang['thang']}",
            json_encode($bang),
            null,
            'hrm_bang_cham_cong_thang'
        );

        resSuccess(['id' => $id], 'Xóa bảng chấm công thành công');
    }

    /**
     * API: Thêm khoảng thời gian khóa cho bảng chấm công
     * POST /api/v2/admin/hrm/ngoaigio/bang_cham_cong_lock_dates
     */
    public function bang_cham_cong_lock_dates_post()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Vui lòng đăng nhập', REST_Controller::HTTP_UNAUTHORIZED);
        }

        $id = commonRequest('id');
        $startDate = commonRequest('start_date');
        $endDate = commonRequest('end_date');

        if (empty($id) || empty($startDate) || empty($endDate)) {
            resError('Vui lòng cung cấp đầy đủ thông tin (ID, Ngày bắt đầu, Ngày kết thúc)', REST_Controller::HTTP_BAD_REQUEST);
        }

        $bang = $this->db->where('id', $id)
            ->where('deleted_at IS NULL')
            ->get('hrm_bang_cham_cong_thang')
            ->row_array();

        if (!$bang) {
            resError('Không tìm thấy bảng chấm công', REST_Controller::HTTP_NOT_FOUND);
        }

        // Lấy danh sách locked_dates hiện tại
        $lockedDates = [];
        if (!empty($bang['locked_dates'])) {
            $lockedDates = json_decode($bang['locked_dates'], true) ?: [];
        }

        // Thêm khoảng thời gian mới
        $lockedDates[] = [
            'start' => $startDate,
            'end' => $endDate,
            'locked_by' => $auth['ql_nguoi_dung_id'],
            'locked_at' => date('Y-m-d H:i:s')
        ];

        // Cập nhật vào database
        $this->db->where('id', $id)->update('hrm_bang_cham_cong_thang', [
            'locked_dates' => json_encode($lockedDates),
            'updated_user_id' => $auth['ql_nguoi_dung_id']
        ]);

        $this->createLog(
            'update',
            "Khóa khoảng thời gian {$startDate} - {$endDate} cho bảng chấm công ID: {$id}",
            $bang['locked_dates'],
            json_encode($lockedDates),
            'hrm_bang_cham_cong_thang'
        );

        resSuccess([
            'id' => $id,
            'locked_dates' => $lockedDates
        ], 'Khóa khoảng thời gian thành công');
    }

    /**
     * API: Xóa khoảng thời gian khóa
     * POST /api/v2/admin/hrm/ngoaigio/bang_cham_cong_unlock_dates
     */
    public function bang_cham_cong_unlock_dates_post()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Vui lòng đăng nhập', REST_Controller::HTTP_UNAUTHORIZED);
        }

        $id = commonRequest('id');
        $index = commonRequest('index'); // Index của khoảng thời gian cần xóa

        if (empty($id) || !isset($index)) {
            resError('Vui lòng cung cấp đầy đủ thông tin', REST_Controller::HTTP_BAD_REQUEST);
        }

        $bang = $this->db->where('id', $id)
            ->where('deleted_at IS NULL')
            ->get('hrm_bang_cham_cong_thang')
            ->row_array();

        if (!$bang) {
            resError('Không tìm thấy bảng chấm công', REST_Controller::HTTP_NOT_FOUND);
        }

        // Lấy danh sách locked_dates hiện tại
        $lockedDates = [];
        if (!empty($bang['locked_dates'])) {
            $lockedDates = json_decode($bang['locked_dates'], true) ?: [];
        }

        // Xóa item tại index
        if (isset($lockedDates[$index])) {
            array_splice($lockedDates, $index, 1);
        }

        // Cập nhật vào database
        $this->db->where('id', $id)->update('hrm_bang_cham_cong_thang', [
            'locked_dates' => json_encode($lockedDates),
            'updated_user_id' => $auth['ql_nguoi_dung_id']
        ]);

        $this->createLog(
            'update',
            "Mở khóa khoảng thời gian index {$index} cho bảng chấm công ID: {$id}",
            $bang['locked_dates'],
            json_encode($lockedDates),
            'hrm_bang_cham_cong_thang'
        );

        resSuccess([
            'id' => $id,
            'locked_dates' => $lockedDates
        ], 'Mở khóa khoảng thời gian thành công');
    }

    /**
     * API: Lấy danh sách ngày lễ Việt Nam
     * GET /api/v2/admin/hrm/ngoaigio/ngay_le
     */
    public function ngay_le_get()
    {
        $year = commonRequest('year') ?: date('Y');

        // Tên cột thực tế trong bảng hrm_ngay_le_viet_nam:
        // ngay (date), ten_ngay_le, duoc_nghi (bool nghỉ hẳn)
        $this->db->select('ngay, ten_ngay_le, duoc_nghi')
            ->from('hrm_ngay_le_viet_nam')
            ->where('YEAR(ngay)', $year);

        $this->db->order_by('ngay', 'ASC');
        $data = $this->db->get()->result_array();

        // Format thành object với key là ngày (YYYY-MM-DD)
        $holidays = [];
        foreach ($data as $item) {
            $holidays[$item['ngay']] = [
                'ten' => $item['ten_ngay_le'],
                'duoc_nghi' => (bool) $item['duoc_nghi']
            ];
        }

        resSuccess($holidays, 'Lấy danh sách ngày lễ thành công');
    }

    /**
     * API: Lấy tổng giờ theo loại ngày (NT/CN/Lễ) cho từng nhân viên
     * GET /api/v2/admin/hrm/ngoaigio/tong_gio_theo_loai_ngay
     * Params: start_date, end_date, id_nhan_vien (optional), id_don_vi (optional)
     */
    public function tong_gio_theo_loai_ngay_get()
    {
        $startDate = commonRequest('start_date');
        $endDate = commonRequest('end_date');
        $idNhanVien = commonRequest('id_nhan_vien');
        $idDonVi = commonRequest('id_don_vi');

        if (!$startDate || !$endDate) {
            resError('Vui lòng cung cấp start_date và end_date', REST_Controller::HTTP_BAD_REQUEST);
        }

        $summary = $this->Hrm_ngoaigio_model->getTotalHoursByDayType(
            $startDate,
            $endDate,
            $idNhanVien,
            $idDonVi
        );

        resSuccess($summary, 'Lấy tổng giờ theo loại ngày thành công');
    }

    public function export_excel_post()
    {
        $this->load->library('Pxl');

        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Vui lòng đăng nhập', REST_Controller::HTTP_UNAUTHORIZED);
        }

        $qlNguoiDungId = $auth['ql_nguoi_dung_id'];
        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];
        if (is_object($searchKey)) {
            $searchKey = json_decode(json_encode($searchKey), true);
        }

        $idBangChamCongs = isset($searchKey['id_bang_cham_cong']) ? $searchKey['id_bang_cham_cong'] : [];

        if (empty($idBangChamCongs)) {
            resError('Vui lòng chọn ít nhất một bảng chấm công để xuất báo cáo', REST_Controller::HTTP_BAD_REQUEST);
        }

        if (!is_array($idBangChamCongs)) {
            $idBangChamCongs = [$idBangChamCongs];
        }

        // Xử lý filter theo đơn vị
        $filterIdDonVi = null;
        $filterMaDonVi = null;

        if (isset($searchKey['id_don_vi'])) {
            if ($searchKey['id_don_vi'] == -1) {
                // -1 = Xem tất cả đơn vị (cần có quyền xemtatca)
                $filterIdDonVi = null;
                $filterMaDonVi = null;
            } elseif (!empty($searchKey['id_don_vi'])) {
                // Lấy theo đơn vị cụ thể
                $filterIdDonVi = $searchKey['id_don_vi'];

                // Lấy mã đơn vị
                $donVi = $this->db->select('ma_don_vi')
                    ->from('e_don_vi')
                    ->where('id_don_vi', $filterIdDonVi)
                    ->get()
                    ->row_array();
                $filterMaDonVi = !empty($donVi['ma_don_vi']) ? $donVi['ma_don_vi'] : null;
            }
        } else {
            // Không truyền id_don_vi → mặc định null (lấy tất cả nếu có quyền)
            $filterIdDonVi = null;
            $filterMaDonVi = null;
        }

        try {
            require_once APPPATH . '/libraries/pxl/PHPExcel.php';
            require_once APPPATH . '/libraries/pxl/PHPExcel/IOFactory.php';
            $objPHPExcel = new PHPExcel();
            $objPHPExcel->removeSheetByIndex(0);

            $sheetCount = 0;

            foreach ($idBangChamCongs as $index => $idBangChamCong) {
                $bangChamCong = $this->db
                    ->select('id, ten_bang, ngay_bat_dau, ngay_ket_thuc')
                    ->from('hrm_bang_cham_cong_thang')
                    ->where('id', $idBangChamCong)
                    ->where('deleted_at IS NULL')
                    ->get()
                    ->row_array();

                if (!$bangChamCong)
                    continue;

                $sheetName = preg_replace('/[:\\\\\/?*\[\]]/', '-', $bangChamCong['ten_bang']);
                $sheetName = mb_substr($sheetName, 0, 31);

                $sheet = new PHPExcel_Worksheet($objPHPExcel, $sheetName);
                $objPHPExcel->addSheet($sheet, $index);
                $objPHPExcel->setActiveSheetIndex($index);

                $searchKeyWithBang = $searchKey;
                $searchKeyWithBang['id_bang_cham_cong'] = $idBangChamCong;
                $searchKeyWithBang['dateRange'] = [
                    'from' => $bangChamCong['ngay_bat_dau'],
                    'to' => $bangChamCong['ngay_ket_thuc']
                ];
                // Chỉ lấy đơn đã duyệt khi xuất excel
                $searchKeyWithBang['trang_thai_tong'] = 'Da_duyet';

                // Lấy data theo đơn vị, không filter theo nhân viên
                $data = $this->Hrm_ngoaigio_model->getAll(
                    0,
                    9999,
                    '',
                    [],
                    $searchKeyWithBang,
                    -1,
                    $qlNguoiDungId,
                    $filterIdDonVi,
                    $filterMaDonVi
                );

                $this->fillSheetData($sheet, $data['data'] ?? [], $bangChamCong, $filterIdDonVi);
                $sheetCount++;
            }

            if ($sheetCount === 0) {
                resError('Không tìm thấy bảng chấm công hợp lệ hoặc không có dữ liệu', REST_Controller::HTTP_BAD_REQUEST);
            }

            $objPHPExcel->setActiveSheetIndex(0);

            $directory = FCPATH . 'uploads/exports/';
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $filename = 'BaoCaoNgoaiGio_' . date('YmdHis') . '.xlsx';
            $filePath = $directory . $filename;

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save($filePath);

            resSuccess([
                'file_path' => base_url('uploads/exports/' . $filename),
                'filename' => $filename
            ], 'Xuất báo cáo thành công');

        } catch (Exception $e) {
            resError('Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Fill dữ liệu vào sheet Excel đơn giản
     */
    private function fillSheetData($sheet, $data, $bangChamCong, $filterIdDonVi = null)
    {
        // Tạo danh sách ngày
        $startDate = new DateTime($bangChamCong['ngay_bat_dau']);
        $endDate = new DateTime($bangChamCong['ngay_ket_thuc']);
        $dates = [];

        while ($startDate <= $endDate) {
            $dates[] = [
                'date' => $startDate->format('Y-m-d'),
                'display' => $startDate->format('d/m'),
                'day_of_week' => $startDate->format('N'),
                'day_name' => $this->getDayName($startDate->format('N'))
            ];
            $startDate->modify('+1 day');
        }

        // Nhóm dữ liệu
        $groupedData = $this->groupDataByDepartmentAndEmployee($data, $dates);

        // Nếu không có dữ liệu hoặc cần bổ sung nhân viên, lấy danh sách nhân viên trong đơn vị
        $allEmployees = $this->getAllEmployeesInDepartment($filterIdDonVi);

        // Merge nhân viên không có data vào groupedData
        foreach ($allEmployees as $dept => $employees) {
            if (!isset($groupedData[$dept])) {
                $groupedData[$dept] = [];
            }

            foreach ($employees as $empId => $empInfo) {
                if (!isset($groupedData[$dept][$empId])) {
                    $groupedData[$dept][$empId] = [
                        'id_nhan_vien' => $empId,
                        'ho_va_ten' => $empInfo['ho_va_ten'],
                        'ma_nhan_vien' => $empInfo['ma_nhan_vien'],
                        'dates' => [],
                        'total_NT' => 0,
                        'total_CN' => 0,
                        'total_LE' => 0
                    ];
                }
            }
        }

        // Set font Times New Roman cho toàn bộ sheet
        $sheet->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(12);

        $row = 1;
        $currentColIndex = 0; // Track column index để đảm bảo không gap

        // Header Row 1-3: HỌ VÀ TÊN, ĐƠN VỊ (merge 3 rows)
        $colA = PHPExcel_Cell::stringFromColumnIndex($currentColIndex);
        $sheet->setCellValue($colA . $row, 'HỌ VÀ TÊN');
        $sheet->mergeCells($colA . $row . ':' . $colA . ($row + 2));
        $currentColIndex++;

        $colB = PHPExcel_Cell::stringFromColumnIndex($currentColIndex);
        $sheet->setCellValue($colB . $row, 'ĐƠN VỊ');
        $sheet->mergeCells($colB . $row . ':' . $colB . ($row + 2));
        $currentColIndex++;

        // Các cột ngày - mỗi ngày có 4 sub-columns (Từ, Đến, Nội dung, Tổng giờ)
        $dateColumns = [];
        foreach ($dates as $dateInfo) {
            $col1 = PHPExcel_Cell::stringFromColumnIndex($currentColIndex);
            $col2 = PHPExcel_Cell::stringFromColumnIndex($currentColIndex + 1);
            $col3 = PHPExcel_Cell::stringFromColumnIndex($currentColIndex + 2);
            $col4 = PHPExcel_Cell::stringFromColumnIndex($currentColIndex + 3);

            // Row 1: Ngày (merge 4 columns)
            $sheet->setCellValue($col1 . $row, $dateInfo['display']);
            $sheet->mergeCells($col1 . $row . ':' . $col4 . $row);

            // Row 2: Thứ (merge 4 columns)
            $sheet->setCellValue($col1 . ($row + 1), $dateInfo['day_name']);
            $sheet->mergeCells($col1 . ($row + 1) . ':' . $col4 . ($row + 1));

            // Row 3: Sub headers
            $sheet->setCellValue($col1 . ($row + 2), 'Từ');
            $sheet->setCellValue($col2 . ($row + 2), 'Đến');
            $sheet->setCellValue($col3 . ($row + 2), 'Nội dung');
            $sheet->setCellValue($col4 . ($row + 2), 'Tổng giờ');

            $dateColumns[] = [
                'date' => $dateInfo['date'],
                'day_of_week' => $dateInfo['day_of_week'],
                'col_from' => $col1,
                'col_to' => $col2,
                'col_content' => $col3,
                'col_total' => $col4
            ];

            $currentColIndex += 4; // Move 4 columns ahead
        }

        // Cột TỔNG GIỜ (3 columns: NT, CN, LÊ)
        $colNT = PHPExcel_Cell::stringFromColumnIndex($currentColIndex);
        $colCN = PHPExcel_Cell::stringFromColumnIndex($currentColIndex + 1);
        $colLE = PHPExcel_Cell::stringFromColumnIndex($currentColIndex + 2);

        // Row 1: TỔNG GIỜ (merge 3 columns)
        $sheet->setCellValue($colNT . $row, 'TỔNG GIỜ (Số thập phân)');
        $sheet->mergeCells($colNT . $row . ':' . $colLE . $row);

        // Row 2: merge 3 columns
        $sheet->mergeCells($colNT . ($row + 1) . ':' . $colLE . ($row + 1));

        // Row 3: NT | CN | LÊ
        $sheet->setCellValue($colNT . ($row + 2), 'NT');
        $sheet->setCellValue($colCN . ($row + 2), 'CN');
        $sheet->setCellValue($colLE . ($row + 2), 'LÊ');

        // Lưu các cột tổng
        $totalColumns = [
            'col_nt' => $colNT,
            'col_cn' => $colCN,
            'col_le' => $colLE
        ];
        $lastCol = $colLE;

        // Style header
        $sheet->getStyle('A' . $row . ':' . $lastCol . ($row + 2))->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allborders' => [
                    'style' => PHPExcel_Style_Border::BORDER_THIN
                ]
            ]
        ]);

        // Fill data
        $row = 4;
        foreach ($groupedData as $deptName => $employees) {
            foreach ($employees as $employee) {
                $sheet->setCellValue('A' . $row, $employee['ho_va_ten']);
                $sheet->setCellValue('B' . $row, $deptName);

                // Dữ liệu từng ngày
                foreach ($dateColumns as $dateCol) {
                    $dateStr = $dateCol['date'];
                    $requests = isset($employee['dates'][$dateStr]) ? $employee['dates'][$dateStr] : [];

                    // Từ
                    $sheet->setCellValue($dateCol['col_from'] . $row, !empty($requests) ? substr($requests[0]['gio_bat_dau'], 0, 5) : '');

                    // Đến
                    $sheet->setCellValue($dateCol['col_to'] . $row, !empty($requests) ? substr($requests[0]['gio_ket_thuc'], 0, 5) : '');

                    // Nội dung
                    $contents = [];
                    foreach ($requests as $req) {
                        $contents[] = $req['noi_dung'];
                    }
                    $sheet->setCellValue($dateCol['col_content'] . $row, implode("; ", $contents));

                    // Tổng giờ
                    $totalHours = 0;
                    foreach ($requests as $req) {
                        $totalHours += floatval($req['so_gio']);
                    }
                    $sheet->setCellValue($dateCol['col_total'] . $row, $totalHours > 0 ? number_format($totalHours, 2) : '');
                }

                // Tổng giờ NT, CN, LÊ - Fill đúng vào các cột đã định sẵn
                $sheet->setCellValue($totalColumns['col_nt'] . $row, number_format($employee['total_NT'], 2));
                $sheet->setCellValue($totalColumns['col_cn'] . $row, number_format($employee['total_CN'], 2));
                $sheet->setCellValue($totalColumns['col_le'] . $row, number_format($employee['total_LE'], 2));

                $row++;
            }
        }

        // Border cho data
        $sheet->getStyle('A1:' . $lastCol . ($row - 1))->applyFromArray([
            'borders' => [
                'allborders' => [
                    'style' => PHPExcel_Style_Border::BORDER_THIN
                ]
            ]
        ]);

        // Set vertical alignment cho tất cả data rows
        $sheet->getStyle('A4:' . $lastCol . ($row - 1))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

        // Bật wrap text cho cột ĐƠN VỊ sau khi apply border
        $sheet->getStyle('B4:B' . ($row - 1))->getAlignment()->setWrapText(true);

        // Set width cho các cột
        $sheet->getColumnDimension('A')->setWidth(30);  // HỌ VÀ TÊN
        $sheet->getColumnDimension('B')->setWidth(25);  // ĐƠN VỊ - wrap text nếu dài

        foreach ($dateColumns as $dateCol) {
            $sheet->getColumnDimension($dateCol['col_from'])->setWidth(8);      // Từ
            $sheet->getColumnDimension($dateCol['col_to'])->setWidth(8);        // Đến
            $sheet->getColumnDimension($dateCol['col_content'])->setWidth(30);  // Nội dung
            $sheet->getColumnDimension($dateCol['col_total'])->setWidth(10);    // Tổng giờ

            // Bật wrap text cho cột Nội dung
            $sheet->getStyle($dateCol['col_content'] . '4:' . $dateCol['col_content'] . ($row - 1))->getAlignment()->setWrapText(true);
        }

        // Set width cho cột TỔNG GIỜ
        $sheet->getColumnDimension($totalColumns['col_nt'])->setWidth(12);  // NT
        $sheet->getColumnDimension($totalColumns['col_cn'])->setWidth(12);  // CN
        $sheet->getColumnDimension($totalColumns['col_le'])->setWidth(12);  // LÊ

        // Set row height
        // Header rows (1-3): chiều cao cố định
        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->getRowDimension(2)->setRowHeight(25);
        $sheet->getRowDimension(3)->setRowHeight(25);

        // Data rows: chiều cao tự động để fit content với wrap text
        for ($i = 4; $i < $row; $i++) {
            $sheet->getRowDimension($i)->setRowHeight(-1); // -1 = auto height
        }
    }

    /**
     * Nhóm dữ liệu theo đơn vị và nhân viên
     */
    private function groupDataByDepartmentAndEmployee($data, $dates)
    {
        $grouped = [];

        foreach ($data as $item) {
            // Convert object to array nếu cần
            if (is_object($item)) {
                $item = (array) $item;
            }

            $deptName = $item['ten_don_vi'] ?? 'Chưa xác định';
            $empId = $item['id_nhan_vien'];
            $empName = $item['ho_va_ten'];

            if (!isset($grouped[$deptName])) {
                $grouped[$deptName] = [];
            }

            if (!isset($grouped[$deptName][$empId])) {
                $grouped[$deptName][$empId] = [
                    'id_nhan_vien' => $empId,
                    'ho_va_ten' => $empName,
                    'ma_nhan_vien' => $item['ma_nhan_vien'],
                    'dates' => [],
                    'total_NT' => 0,
                    'total_CN' => 0,
                    'total_LE' => 0
                ];
            }

            $dateStr = $item['ngay_dang_ky'];
            if (!isset($grouped[$deptName][$empId]['dates'][$dateStr])) {
                $grouped[$deptName][$empId]['dates'][$dateStr] = [];
            }

            $grouped[$deptName][$empId]['dates'][$dateStr][] = $item;

            // Tính tổng giờ theo loại ngày
            // Tìm ngày trong $dates để biết là thứ mấy
            foreach ($dates as $dateInfo) {
                if ($dateInfo['date'] === $dateStr) {
                    $dayOfWeek = intval($dateInfo['day_of_week']);
                    $soGio = floatval($item['so_gio']);

                    // Chỉ tính các đơn đã duyệt
                    if ($item['trang_thai_tong'] === 'Da_duyet') {
                        if ($dayOfWeek == 7) { // Chủ nhật
                            $grouped[$deptName][$empId]['total_CN'] += $soGio;
                        } else if ($this->isHoliday($dateStr)) { // Ngày lễ
                            $grouped[$deptName][$empId]['total_LE'] += $soGio;
                        } else { // Ngày thường
                            $grouped[$deptName][$empId]['total_NT'] += $soGio;
                        }
                    }
                    break;
                }
            }
        }

        return $grouped;
    }

    /**
     * Lấy danh sách tất cả nhân viên trong đơn vị
     */
    private function getAllEmployeesInDepartment($filterIdDonVi = null)
    {
        $this->db->select('nv.id_nhan_vien, nv.ho_va_ten, nv.ma_nhan_vien, dv.ten_don_vi, dv.id_don_vi');
        $this->db->from('hrm_nhan_vien nv');
        $this->db->join('e_don_vi dv', 'dv.id_don_vi = nv.id_don_vi_cong_tac', 'left');
        $this->db->where('nv.deleted_at IS NULL');

        if ($filterIdDonVi !== null) {
            $this->db->where('nv.id_don_vi_cong_tac', $filterIdDonVi);
        }

        $this->db->order_by('dv.ten_don_vi, nv.ho_va_ten');
        $result = $this->db->get()->result_array();

        $grouped = [];
        foreach ($result as $emp) {
            $deptName = $emp['ten_don_vi'] ?? 'Chưa xác định';
            $empId = $emp['id_nhan_vien'];

            if (!isset($grouped[$deptName])) {
                $grouped[$deptName] = [];
            }

            $grouped[$deptName][$empId] = [
                'id_nhan_vien' => $empId,
                'ho_va_ten' => $emp['ho_va_ten'],
                'ma_nhan_vien' => $emp['ma_nhan_vien']
            ];
        }

        return $grouped;
    }

    /**
     * Lấy tên thứ trong tuần
     */
    private function getDayName($dayOfWeek)
    {
        $days = [
            1 => 'THỨ HAI',
            2 => 'THỨ BA',
            3 => 'THỨ TƯ',
            4 => 'THỨ NĂM',
            5 => 'THỨ SÁU',
            6 => 'THỨ BẢY',
            7 => 'CHỦ NHẬT'
        ];
        return $days[$dayOfWeek] ?? '';
    }

    /**
     * Kiểm tra ngày có phải ngày lễ không
     */
    private function isHoliday($date)
    {
        $holidays = $this->db
            ->select('ngay')
            ->from('hrm_ngay_le_viet_nam')
            ->where('YEAR(ngay)', date('Y', strtotime($date)))
            ->get()
            ->result_array();

        foreach ($holidays as $holiday) {
            if (date('Y-m-d', strtotime($holiday['ngay'])) === $date) {
                return true;
            }
        }

        return false;
    }

    /**
     * Thống kê tổng quan theo điều kiện filter
     * GET /api/v2/admin/hrm/ngoaigio/statistics
     */
    public function statistics_get()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Vui lòng đăng nhập', REST_Controller::HTTP_UNAUTHORIZED);
        }

        $searchValue = commonRequest('searchValue');
        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];

        // Lấy thông tin nhân viên của user hiện tại
        $nhanVienRaw = $this->Hrm_nhan_vien_model
            ->select('hrm_nhan_vien.id_nhan_vien, hrm_nhan_vien.id_don_vi_cong_tac, e_don_vi.ma_don_vi')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_nhan_vien.id_don_vi_cong_tac', 'left')
            ->where('hrm_nhan_vien.ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])
            ->first();
        $nhanVien = is_array($nhanVienRaw) || is_object($nhanVienRaw) ? (array) $nhanVienRaw : [];

        $idNhanVien = !empty($nhanVien['id_nhan_vien']) ? $nhanVien['id_nhan_vien'] : null;
        $idDonViCongTac = !empty($nhanVien['id_don_vi_cong_tac']) ? $nhanVien['id_don_vi_cong_tac'] : $auth['id_don_vi'];
        $maDonVi = !empty($nhanVien['ma_don_vi']) ? $nhanVien['ma_don_vi'] : null;

        // Gọi model để lấy thống kê
        $stats = $this->Hrm_ngoaigio_model->getStatistics(
            $searchValue,
            $searchKey,
            $idNhanVien,
            $auth['ql_nguoi_dung_id'],
            $idDonViCongTac,
            $maDonVi
        );

        resSuccess($stats, 'Lấy thống kê ngoài giờ thành công');
    }


    public function change_status_post()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Vui lòng đăng nhập', REST_Controller::HTTP_UNAUTHORIZED);
        }

        $id = commonRequest('id');
        $ly_do_huy = commonRequest('ly_do_huy');
        $trang_thai_moi = commonRequest('trang_thai_moi');
        $data = commonRequest('data');

        if (empty($id)) {
            resError('Vui lòng cung cấp ID đăng ký', REST_Controller::HTTP_BAD_REQUEST);
        }

        $dangKy = $this->db->where('id_ngoai_gio', $id)
            ->where('deleted_at IS NULL')
            ->get('hrm_ngoai_gio')
            ->row_array();

        if (!$dangKy) {
            resError('Không tìm thấy đăng ký', REST_Controller::HTTP_NOT_FOUND);
        }

        // Xử lý theo trạng thái mới
        if ($trang_thai_moi === 'Huy') {
            // Trường hợp HỦY: Kiểm tra không cho hủy đơn đã duyệt
            if ($dangKy['trang_thai_tong'] === 'Da_duyet') {
                resError('Không thể hủy đăng ký đã được duyệt', REST_Controller::HTTP_BAD_REQUEST);
            }

            // Lấy số lần hủy hiện tại
            $soLanHuyHienTai = isset($dangKy['so_lan_huy']) ? (int) $dangKy['so_lan_huy'] : 0;
            $soLanHuyMoi = $soLanHuyHienTai + 1;

            $this->db->where('id_ngoai_gio', $id)->update('hrm_ngoai_gio', [
                'trang_thai_tong' => 'Huy',
                'ly_do_huy' => $ly_do_huy,
                'so_lan_huy' => $soLanHuyMoi,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_user_id' => $auth['ql_nguoi_dung_id']
            ]);

            // Log hủy đăng ký
            $this->createLog(
                'cancel',
                'Hủy đăng ký ngoài giờ ID: ' . $id . ' (Lần ' . $soLanHuyMoi . ')',
                $dangKy,
                ['ly_do_huy' => $ly_do_huy, 'trang_thai_tong' => 'Huy', 'so_lan_huy' => $soLanHuyMoi],
                'hrm_ngoai_gio'
            );

            // Thông báo cho người dùng
            $message = 'Hủy đăng ký thành công';
            if ($soLanHuyMoi >= 3) {
                $message .= ' (Đã hủy ' . $soLanHuyMoi . ' lần - Đây là lần cuối được phép hủy. Không thể mở lại đăng ký này nữa)';
            } else {
                $conLai = 3 - $soLanHuyMoi;
                $message .= ' (Đã hủy ' . $soLanHuyMoi . '/3 lần - Còn ' . $conLai . ' lần được phép mở lại)';
            }

            resSuccess(['id' => $id, 'so_lan_huy' => $soLanHuyMoi], $message);

        } elseif ($trang_thai_moi === 'Cho_duyet') {
            // Trường hợp MỞ LẠI: Kiểm tra số lần hủy
            $soLanHuyHienTai = isset($dangKy['so_lan_huy']) ? (int) $dangKy['so_lan_huy'] : 0;

            if ($soLanHuyHienTai >= 3) {
                resError('Đã hết số lần được phép mở lại. Đăng ký này đã bị hủy ' . $soLanHuyHienTai . ' lần (tối đa 3 lần). Vui lòng tạo đăng ký mới.', REST_Controller::HTTP_BAD_REQUEST);
            }

            // Cập nhật về trạng thái Chờ duyệt
            $dataUpdate = [
                'trang_thai_tong' => 'Cho_duyet',
                'ly_do_huy' => null, // Xóa lý do hủy cũ
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_user_id' => $auth['ql_nguoi_dung_id']
            ];

            // Nếu có data mới thì cập nhật
            if (!empty($data)) {
                // Chuẩn hóa data
                if (is_string($data)) {
                    $data = json_decode($data, true);
                } elseif (is_object($data)) {
                    $data = json_decode(json_encode($data), true);
                }

                if (!empty($data['ngay_dang_ky'])) {
                    $dataUpdate['ngay_dang_ky'] = $data['ngay_dang_ky'];
                }
                if (!empty($data['gio_bat_dau'])) {
                    $dataUpdate['gio_bat_dau'] = $data['gio_bat_dau'];
                }
                if (!empty($data['gio_ket_thuc'])) {
                    $dataUpdate['gio_ket_thuc'] = $data['gio_ket_thuc'];
                }
                if (isset($data['so_gio'])) {
                    $dataUpdate['so_gio'] = $data['so_gio'];
                }
                if (isset($data['noi_dung'])) {
                    $dataUpdate['noi_dung'] = $data['noi_dung'];
                }
                if (isset($data['chi_tiet'])) {
                    $dataUpdate['chi_tiet'] = $data['chi_tiet'];
                }
            }

            $this->db->where('id_ngoai_gio', $id)->update('hrm_ngoai_gio', $dataUpdate);

            // Log mở lại đăng ký
            $this->createLog(
                'reopen',
                'Mở lại đăng ký ngoài giờ ID: ' . $id . ' (Đã hủy ' . $soLanHuyHienTai . '/3 lần)',
                $dangKy,
                $dataUpdate,
                'hrm_ngoai_gio'
            );

            resSuccess([
                'id' => $id,
                'so_lan_huy' => $soLanHuyHienTai,
                'con_lai' => 3 - $soLanHuyHienTai
            ], 'Mở lại đăng ký thành công (Còn ' . (3 - $soLanHuyHienTai) . ' lần được phép hủy)');

        } else {
            resError('Trạng thái không hợp lệ. Chỉ chấp nhận: Huy hoặc Cho_duyet', REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    /**
     * API: Lấy lịch sử thao tác (logs) của HRM Ngoài Giờ
     * GET /api/v2/admin/hrm/ngoaigio/logs
     */
    public function logs_get()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Vui lòng đăng nhập', REST_Controller::HTTP_UNAUTHORIZED);
        }

        $limit = commonRequest('limit') ? (int) commonRequest('limit') : 20;
        $page = commonRequest('page') ? (int) commonRequest('page') : 1;
        $start = ($page - 1) * $limit;

        $search = commonRequest('search'); // filter by actor, action name
        $actionFilter = commonRequest('actionOptions'); // filter by create/update/etc
        $dateFilter = commonRequest('dateFilter'); // filter by date constraints

        $this->db->select('nhat_ky.*, nd.ql_nguoi_dung_ho_ten as actor_name')
            ->from('ql_nhat_ky nhat_ky')
            ->join('ql_nguoi_dung nd', 'nhat_ky.ql_nguoi_dung_id = nd.ql_nguoi_dung_id', 'left')
            ->where('nhat_ky.ql_nhat_ky_bang_du_lieu', 'hrm_ngoai_gio');

        // Apply filters
        if (!empty($search)) {
            $this->db->group_start()
                ->like('nhat_ky.ql_nhat_ky_noi_dung', $search)
                ->or_like('nd.ql_nguoi_dung_ho_ten', $search)
                ->group_end();
        }

        // actionFilter có định dạng array từ client
        if (!empty($actionFilter)) {
            if (is_string($actionFilter)) {
                $actionFilter = json_decode($actionFilter, true) ?? explode(',', $actionFilter);
            }
            if (is_array($actionFilter) && count($actionFilter) > 0) {
                $this->db->where_in('nhat_ky.ql_nhat_ky_hanh_dong', $actionFilter);
            }
        }

        if (!empty($dateFilter) && $dateFilter !== 'all') {
            if ($dateFilter === '7d') {
                $this->db->where('nhat_ky.ql_nhat_ky_ngay_tao >=', date('Y-m-d 00:00:00', strtotime('-7 days')));
            } elseif ($dateFilter === '30d') {
                $this->db->where('nhat_ky.ql_nhat_ky_ngay_tao >=', date('Y-m-d 00:00:00', strtotime('-30 days')));
            } else {
                // assume specific date Y-m-d
                $this->db->like('nhat_ky.ql_nhat_ky_ngay_tao', $dateFilter, 'after');
            }
        }

        // Count total
        $totalQuery = clone $this->db;
        $total = $totalQuery->count_all_results();

        // Get paginated data
        $this->db->order_by('nhat_ky.ql_nhat_ky_id', 'DESC')
            ->limit($limit, $start);

        $logs = $this->db->get()->result_array();

        resSuccess([
            'data' => $logs,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'has_more' => ($start + count($logs)) < $total
        ], 'Lấy lịch sử thao tác thành công');
    }
}
