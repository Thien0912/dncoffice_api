<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dx_de_xuat_model extends MY_Model
{
    protected $table = 'dx_de_xuat';
    protected $primaryKey = 'id_de_xuat';
    public $is_sequential = true; // TRUE: duyệt tuần tự, FALSE: duyệt song song

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Lấy danh sách đề xuất (bao gồm cả đề xuất gửi đi và nhận về)
     */
    public function getDanhSachDeXuat($userId, $start = 0, $length = 10, $searchValue = '', $searchKey = [], $orderBy = [])
    {
        $maVaiTro = $this->Ql_vai_tro_model->getMaVaiTro($userId);
        $maVaiTro = $maVaiTro['ql_ma_vai_tro'] ?? null;
        
        // Kiểm tra xem có phải Super Admin không (có quyền xem tất cả đơn vị)
        $isSuperAdmin = in_array($maVaiTro, Common::VAI_TRO_SUPER_ADMIN);
        
        // Lấy mã đơn vị của user hiện tại
        // Xác định tab: tat_ca (all), da_gui, nhap
        $tab = isset($searchKey['tab']) ? $searchKey['tab'] : 'all';

        $this->db->select("
            dx.id_de_xuat,
            dx.tieu_de,
            dx.noi_dung,
            dx.nhap,
            dx.trang_thai,
            dx.id_dx_loai_de_xuat,
            dx.created_at,
            dx.created_user_id,
            dx.updated_at,
            nv.ma_nhan_vien,
            COALESCE(NULLIF(nv.ho_va_ten, ''), creator.ql_nguoi_dung_ho_ten) as ho_va_ten,
            COALESCE(NULLIF(nv.email, ''), creator.ql_nguoi_dung_email) as email,
            COALESCE(NULLIF(nv.avatar, ''), creator.ql_nguoi_dung_avatar) as avatar,
            nv.gioi_tinh,
            loai.ten_loai as ten_loai_de_xuat,
            loai.mo_ta as mo_ta_loai_de_xuat,
            (SELECT COUNT(DISTINCT cap_duyet) FROM dx_nguoi_duyet_de_xuat WHERE id_de_xuat = dx.id_de_xuat AND da_duyet = 1) as count_approved,
            (SELECT COUNT(DISTINCT cap_duyet) FROM dx_nguoi_duyet_de_xuat WHERE id_de_xuat = dx.id_de_xuat) as count_total,
            MAX(bl.created_at) as thoi_gian_binh_luan,
            COALESCE(MAX(nd.created_at), '1970-01-01') as thoi_gian_nguoi_duyet,
            COALESCE(
                MAX(bl.created_at),
                MAX(nd.created_at),
                dx.created_at
            ) as thoi_gian_moi_nhat,
            completer.ql_nguoi_dung_ho_ten as nguoi_hoan_thanh
        ", FALSE);
        $this->db->from('dx_de_xuat dx');
        $this->db->join('ql_nguoi_dung creator', 'creator.ql_nguoi_dung_id = dx.created_user_id', 'left');
        $this->db->join('hrm_nhan_vien nv', 'nv.ql_nguoi_dung_id = creator.ql_nguoi_dung_id', 'left');
        $this->db->join('dx_loai_de_xuat loai', 'loai.id_dx_loai_de_xuat = dx.id_dx_loai_de_xuat', 'left');
        $this->db->join('dx_nguoi_duyet_de_xuat nd', 'nd.id_de_xuat = dx.id_de_xuat', 'left');
        $this->db->join('dx_binh_luan bl', 'bl.id_de_xuat = dx.id_de_xuat AND bl.deleted_at IS NULL', 'left');
        $this->db->join('ql_nguoi_dung completer', 'completer.ql_nguoi_dung_id = dx.id_user_confirm_completed', 'left');

        // Chưa bị xóa
        $this->db->where('dx.deleted_at IS NULL');

        // Lọc theo tab
        switch ($tab) {
            case 'nhap':
                // Tab nháp: chỉ lấy đề xuất nháp của chính mình
                $this->db->where('dx.nhap', 1);
                $this->db->where('dx.created_user_id', $userId);
                break;

            case 'da_gui':
                // Tab đã gửi: đề xuất đã gửi (không phải nháp) của chính mình
                $this->db->where('dx.nhap !=', 1);
                $this->db->where('dx.created_user_id', $userId);
                break;

            // case 'starred':
            //     // Tab starred: đề xuất đã gửi (không phải nháp) và đã đánh dấu sao
            //     $this->db->where('dx.nhap !=', 1);
            //     $this->db->where('dx.gan_sao =', 1);
            //     break;

            // case 'important':
            //     // Tab important: đề xuất đã gửi (không phải nháp) và quan trọng
            //     $this->db->where('dx.nhap !=', 1);
            //     $this->db->where('dx.quan_trong =', 1);
            //     break;

            case 'trash':
                // Tab trash: đề xuất đã gửi (không phải nháp) và đã bị xóa
                $this->db->where('dx.nhap !=', 1);
                $this->db->where('dx.deleted_at IS NOT NULL', null, false);
                break;

            default:
                // Tab tất cả (all): tất cả đề xuất liên quan (không phải nháp)
                $this->db->where('dx.nhap !=', 1);
                
                // Super Admin có thể xem tất cả đề xuất, không cần điều kiện phân quyền
                if (!$isSuperAdmin) {
                    $this->db->group_start();

                    // Đề xuất do mình tạo
                    $this->db->where('dx.created_user_id', $userId);

                    // Hoặc đề xuất mà mình là người duyệt (không cần kiểm tra cấp trước đã duyệt)
                    $this->db->or_where('nd.id_nguoi_duyet', $userId);

                    // Hoặc đề xuất mà người cùng đơn vị với mình là người duyệt
                    $this->db->or_where("
                        EXISTS (
                            SELECT 1
                            FROM dx_nguoi_duyet_de_xuat nd_same_unit
                            INNER JOIN hrm_nhan_vien nv_current ON nv_current.ql_nguoi_dung_id = {$userId}
                            WHERE nd_same_unit.id_de_xuat = dx.id_de_xuat
                            AND nd_same_unit.id_don_vi = nv_current.id_don_vi_cong_tac
                            AND nd_same_unit.deleted_at IS NULL
                        )
                    ", null, false);

                    // Hoặc đề xuất mà mình có bình luận
                    $this->db->or_where('bl.created_user_id', $userId);

                    $this->db->group_end();
                }
                break;
        }

        // Group by để tránh trùng lặp do join
        $this->db->group_by('dx.id_de_xuat');

        // Tìm kiếm
        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('dx.tieu_de', $searchValue);
            $this->db->or_like('dx.noi_dung', $searchValue);
            $this->db->group_end();
        }

        // Tìm kiếm nâng cao (bỏ qua key 'tab' vì đã xử lý)
        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                if ($value && $key !== 'tab') {
                    if ($key == 'da_duyet') {
                        $this->db->where('nd.da_duyet', $value);
                    } elseif ($key == 'tu_ngay') {
                        $this->db->where('DATE(dx.created_at) >=', $value);
                    } elseif ($key == 'den_ngay') {
                        $this->db->where('DATE(dx.created_at) <=', $value);
                    } elseif ($key == 'month') {
                        $this->db->where('MONTH(dx.created_at)', $value);
                    } elseif ($key == 'year') {
                        $this->db->where('YEAR(dx.created_at)', $value);
                    } elseif ($key == 'id_don_vi' && $isSuperAdmin) {
                        // Super Admin có thể filter theo đơn vị
                        $this->db->where('nv.id_don_vi_cong_tac', $value);
                    } else {
                        $this->db->where('dx.' . $key, $value);
                    }
                }
            }
        }

        // Đếm tổng số
        $totalRecords = $this->db->count_all_results('', false);

        // Sắp xếp theo ưu tiên: bình luận mới nhất -> thời gian người duyệt -> thời gian tạo
        if (!empty($orderBy) && isset($orderBy['order']) && isset($orderBy['columns'])) {
            $order = $orderBy['order'][0];
            $columnIndex = $order['column'];
            $columnName = $orderBy['columns'][$columnIndex]['data'];
            $dir = $order['dir'];

            if ($columnName && in_array($columnName, ['tieu_de', 'created_at', 'updated_at'])) {
                $this->db->order_by('dx.' . $columnName, $dir);
            } elseif ($columnName == 'thoi_gian_moi_nhat') {
                $this->db->order_by('thoi_gian_moi_nhat', $dir);
            }
        } else {
            // Mặc định sắp xếp theo bình luận mới nhất lên đầu (NULL xuống dưới)
            $this->db->order_by('nd.created_at', 'DESC');
            $this->db->order_by('thoi_gian_binh_luan', 'DESC');
            $this->db->order_by('dx.created_at', 'DESC');
        }

        // Phân trang
        $this->db->limit($length, $start);

        $data = $this->db->get()->result_array();
        $sql = $this->db->last_query();

        // Lấy danh sách bình luận cho tất cả đề xuất
        if (!empty($data)) {
            $deXuatIds = array_column($data, 'id_de_xuat');

            $this->db->select('
                bl.*,
                nv.ql_nguoi_dung_ho_ten as ten_nguoi_binh_luan,
                nv.ql_nguoi_dung_id as ma_nhan_vien
            ');
            $this->db->from('dx_binh_luan bl');
            $this->db->join('ql_nguoi_dung nv', 'nv.ql_nguoi_dung_id = bl.created_user_id', 'left');
            $this->db->where_in('bl.id_de_xuat', $deXuatIds);
            $this->db->where('bl.deleted_at IS NULL');
            $this->db->order_by('bl.created_at', 'ASC');
            $allBinhLuan = $this->db->get()->result_array();

            // Group bình luận theo id_de_xuat
            $binhLuanByDeXuat = [];
            foreach ($allBinhLuan as $bl) {
                $binhLuanByDeXuat[$bl['id_de_xuat']][] = $bl;
            }

            // Lấy thông tin TẤT CẢ người duyệt của các đề xuất trong danh sách để tính toán lượt
            $this->db->select('id_de_xuat, cap_duyet, id_don_vi, da_duyet, id_nguoi_duyet');
            $this->db->from('dx_nguoi_duyet_de_xuat');
            $this->db->where_in('id_de_xuat', $deXuatIds);
            $this->db->where('deleted_at IS NULL');
            $this->db->order_by('cap_duyet', 'ASC');
            $allSigners = $this->db->get()->result_array();

            $signersByDeXuat = [];
            foreach ($allSigners as $s) {
                $signersByDeXuat[$s['id_de_xuat']][] = $s;
            }

            // Lấy ID đơn vị của user hiện tại
            $this->db->select('id_don_vi_cong_tac');
            $this->db->from('hrm_nhan_vien');
            $this->db->where('ql_nguoi_dung_id', $userId);
            $userNhanVien = $this->db->get()->row_array();
            $userUnitId = $userNhanVien['id_don_vi_cong_tac'] ?? null;

            // Lấy danh sách file đính kèm cho tất cả đề xuất
            $this->db->select('*');
            $this->db->from('dx_file_dinh_kem');
            $this->db->where_in('id_de_xuat', $deXuatIds);
            $allFiles = $this->db->get()->result_array();

            $filesByDeXuat = [];
            foreach ($allFiles as $file) {
                $file['duong_dan'] = encryptString($file['duong_dan']);
                $link = $file['duong_dan'] . '?user_id=' . $userId . '&user_seen=' . $userId;
                $file['duong_dan'] = encryptString($link);
                $filesByDeXuat[$file['id_de_xuat']][] = $file;
            }

            // Gán dữ liệu vào từng đề xuất
            foreach ($data as &$deXuat) {
                $deXuat['binh_luan'] = $binhLuanByDeXuat[$deXuat['id_de_xuat']] ?? [];
                $deXuat['so_luong_binh_luan'] = count($deXuat['binh_luan']);
                $deXuat['file_dinh_kem'] = $filesByDeXuat[$deXuat['id_de_xuat']] ?? [];
                $deXuat['so_luong_file'] = count($deXuat['file_dinh_kem']);

                $propsSigners = $signersByDeXuat[$deXuat['id_de_xuat']] ?? [];

                // 1. Tìm cấp duyệt hiện tại (cấp nhỏ nhất chưa hoàn thành)
                $currentCap = 999;
                $stepsStatus = []; // cap_duyet => is_finished
                foreach ($propsSigners as $ps) {
                    if (!isset($stepsStatus[$ps['cap_duyet']])) {
                        $stepsStatus[$ps['cap_duyet']] = false;
                    }
                    if ($ps['da_duyet'] == 1) {
                        $stepsStatus[$ps['cap_duyet']] = true;
                    }
                }

                foreach ($stepsStatus as $cap => $isFin) {
                    if (!$isFin) {
                        $currentCap = min($currentCap, $cap);
                        break; // Tìm thấy cấp nhỏ nhất chưa xong thì dừng
                    }
                }

                // 2. Kiểm tra trạng thái của User hiện tại
                $isUserApproved = false;
                $isSigner = false;
                $userCap = null;
                $canApprove = false;

                foreach ($propsSigners as $ps) {
                    if ($ps['id_nguoi_duyet'] == $userId) {
                        $isSigner = true;
                        $userCap = $ps['cap_duyet'];
                        if ($ps['da_duyet'] == 1) {
                            $isUserApproved = true;
                        }

                        // Nếu cấp này là cấp hiện tại (hoặc duyệt song song) và chưa duyệt
                        $isTurn = $this->is_sequential ? ($ps['cap_duyet'] == $currentCap) : true;
                        if ($isTurn && $ps['da_duyet'] === null) {
                            $canApprove = true;
                        }
                    }
                }

                $deXuat['is_unit_approved'] = $isUserApproved ? 1 : 0;
                $deXuat['is_my_unit_turn'] = $canApprove ? 1 : 0;
                $deXuat['can_approve'] = $canApprove ? 1 : 0;
                $deXuat['da_duyet'] = $isUserApproved ? 1 : 0; // Để tương thích hiển thị Frontend

                // Mã hóa avatar
                if (!empty($deXuat['avatar']) && !filter_var($deXuat['avatar'], FILTER_VALIDATE_URL)) {
                    $deXuat['avatar'] = encryptString($deXuat['avatar']);
                }
            }
        }

        return [
            'data' => $data,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'sql' => $sql
        ];
    }

    /**
     * Lấy chi tiết đề xuất
     */
    public function getChiTietDeXuat($idDeXuat, $userId)
    {
        $this->db->select("
            dx.*,
            creator.ql_nguoi_dung_ho_ten as nguoi_tao,
            nv_creator.ma_nhan_vien as ma_nhan_vien_tao,
            nv_creator.email,
            COALESCE(NULLIF(nv_creator.avatar, ''), creator.ql_nguoi_dung_avatar) as avatar_nguoi_tao,
            creator.ql_nguoi_dung_email as email_nguoi_tao,

            proposer.ql_nguoi_dung_ho_ten as nguoi_de_xuat,
            nv_proposer.ma_nhan_vien as ma_nhan_vien_de_xuat,
            nv_proposer.email as email_de_xuat,
            dv_proposer.ten_don_vi as ten_don_vi_de_xuat,
            COALESCE(NULLIF(nv_proposer.avatar, ''), proposer.ql_nguoi_dung_avatar) as avatar_nguoi_de_xuat,

            updater.ql_nguoi_dung_ho_ten as nguoi_cap_nhat,
            loai.ten_loai as ten_loai_de_xuat,
            loai.mo_ta as mo_ta_loai_de_xuat,
            completer.ql_nguoi_dung_ho_ten as nguoi_hoan_thanh
        ", FALSE);
        $this->db->from('dx_de_xuat dx');
        $this->db->join('ql_nguoi_dung creator', 'creator.ql_nguoi_dung_id = dx.created_user_id', 'left');
        $this->db->join('hrm_nhan_vien nv_creator', 'nv_creator.ql_nguoi_dung_id = creator.ql_nguoi_dung_id', 'left');
        $this->db->join('ql_nguoi_dung proposer', 'proposer.ql_nguoi_dung_id = dx.id_nguoi_de_xuat', 'left');
        $this->db->join('hrm_nhan_vien nv_proposer', 'nv_proposer.ql_nguoi_dung_id = proposer.ql_nguoi_dung_id', 'left');
        $this->db->join('e_don_vi dv_proposer', 'dv_proposer.id_don_vi = nv_proposer.id_don_vi_cong_tac', 'left');
        $this->db->join('ql_nguoi_dung updater', 'updater.ql_nguoi_dung_id = dx.updated_user_id', 'left');
        $this->db->join('dx_loai_de_xuat loai', 'loai.id_dx_loai_de_xuat = dx.id_dx_loai_de_xuat', 'left');
        $this->db->join('ql_nguoi_dung completer', 'completer.ql_nguoi_dung_id = dx.id_user_confirm_completed', 'left');
        $this->db->where('dx.id_de_xuat', $idDeXuat);
        $this->db->where('dx.deleted_at IS NULL');

        $deXuat = $this->db->get()->row_array();

        if (!$deXuat) {
            return null;
        }

        // Lấy danh sách người duyệt
        $this->db->select("
            nd.*,
            nv.ql_nguoi_dung_ho_ten as ten_nguoi_duyet,
            hrm_nv.ma_nhan_vien,
            nv.ql_nguoi_dung_email as email,
            COALESCE(NULLIF(hrm_nv.avatar, ''), nv.ql_nguoi_dung_avatar) as avatar,
            dv.ten_don_vi,
            hrm_nv.hoc_ham,
            hrm_nv.trinh_do_dt
        ");
        $this->db->from('dx_nguoi_duyet_de_xuat nd');
        $this->db->join('ql_nguoi_dung nv', 'nv.ql_nguoi_dung_id = nd.id_nguoi_duyet', 'left');
        $this->db->join('hrm_nhan_vien hrm_nv', 'hrm_nv.ql_nguoi_dung_id = nv.ql_nguoi_dung_id', 'left');
        $this->db->join('e_don_vi dv', 'dv.id_don_vi = nd.id_don_vi', 'left');
        $this->db->where('nd.id_de_xuat', $idDeXuat);
        $this->db->order_by('nd.cap_duyet', 'ASC');
        // $this->db->order_by('hrm_nv.id_nhan_vien', 'DESC');
        // $this->db->group_by('nd.id_nguoi_duyet'); // thêm dòng này

        $nguoi_duyet = $this->db->get()->result_array();
        $CI = &get_instance();
        $CI->load->helper('hrm');
        foreach ($nguoi_duyet as &$ld) {
            if (!$ld['id_nguoi_duyet']) {
                $ld['hoc_ham_hoc_vi'] = '';
                $ld['ten_nguoi_duyet'] = '';
                $ld['avatar'] = '';
                continue;
            }

            $ld['hoc_ham_hoc_vi'] = ''; // Initialize
            $ld['ten_nguoi_duyet'] = format_fullname_with_titles($ld['ten_nguoi_duyet'], $ld['hoc_ham'], $ld['trinh_do_dt']);

            if (!empty($ld['avatar']) && !filter_var($ld['avatar'], FILTER_VALIDATE_URL)) {
                $ld['avatar'] = encryptString($ld['avatar']);
            }
        }

        // Lọc trùng ql_nguoi_dung join 1-n hrm_nhan_vien
        $unique = [];
        $seen = [];

        foreach ($nguoi_duyet as $item) {
            $key = $item['id_nguoi_duyet_de_xuat'];

            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $item;
            }
        }
        $nguoi_duyet = $unique;
        // END lọc trùng

        $deXuat['nguoi_duyet'] = $nguoi_duyet;

        // Mã hóa avatar người tạo
        if (!empty($deXuat['avatar_nguoi_tao']) && !filter_var($deXuat['avatar_nguoi_tao'], FILTER_VALIDATE_URL)) {
            $deXuat['avatar_nguoi_tao'] = encryptString($deXuat['avatar_nguoi_tao']);
        }

        // Mã hóa avatar người đề xuất
        if (!empty($deXuat['avatar_nguoi_de_xuat']) && !filter_var($deXuat['avatar_nguoi_de_xuat'], FILTER_VALIDATE_URL)) {
            $deXuat['avatar_nguoi_de_xuat'] = encryptString($deXuat['avatar_nguoi_de_xuat']);
        }
        $temp = [];

        foreach ($nguoi_duyet as $item) {
            $temp[$item['cap_duyet']] = [
                'cap_duyet' => $item['cap_duyet'],
                'ten_don_vi' => $item['ten_don_vi'],
                'id_don_vi' => $item['id_don_vi'],
                'nguoi_duyet' => array_values(
                    array_filter($nguoi_duyet, function ($nd) use ($item) {
                        return $nd['cap_duyet'] == $item['cap_duyet']
                            && $nd['id_don_vi'] == $item['id_don_vi']
                            && $nd['id_nguoi_duyet'];
                    })
                )
            ];
        }

        $quy_trinh = array_values($temp);

        // $quy_trinh = [];
        // foreach($result as $nd){
        //     $quy_trinh[] = [
        //         'cap_duyet' => $nd['cap_duyet'],
        //         'ten_don_vi' => $nd['ten_don_vi']
        //     ];

        // }
        $deXuat['quy_trinh'] = $quy_trinh;

        // Tính toán lượt duyệt cho user hiện tại (Để ẩn/hiện nút Duyệt ở Frontend)
        $currentCap = 999;
        $stepsStatus = []; // cap_duyet => is_finished (ít nhất 1 người duyệt)
        foreach ($nguoi_duyet as $nd) {
            if (!isset($stepsStatus[$nd['cap_duyet']])) {
                $stepsStatus[$nd['cap_duyet']] = false;
            }
            if ($nd['da_duyet'] == 1) {
                $stepsStatus[$nd['cap_duyet']] = true;
            }
        }

        ksort($stepsStatus);
        foreach ($stepsStatus as $cap => $isFin) {
            if (!$isFin) {
                $currentCap = $cap;
                break;
            }
        }

        $isMyTurn = false;
        $canApprove = false;
        foreach ($nguoi_duyet as $nd) {
            $isTurn = $this->is_sequential ? ($nd['cap_duyet'] == $currentCap) : true;
            if ($nd['id_nguoi_duyet'] == $userId && $isTurn) {
                $isMyTurn = true;
                if ($nd['da_duyet'] === null) {
                    $canApprove = true;
                }
            }
        }

        $deXuat['current_cap'] = $currentCap;
        $deXuat['is_my_turn'] = $isMyTurn ? 1 : 0;
        $deXuat['can_approve'] = $canApprove ? 1 : 0;

        // Lấy danh sách file đính kèm
        $this->db->select('*');
        $this->db->from('dx_file_dinh_kem');
        $this->db->where('id_de_xuat', $idDeXuat);
        $files = $this->db->get()->result_array();

        foreach ($files as &$file) {
            $file['duong_dan'] = encryptString($file['duong_dan']);
            $link = $file['duong_dan'] . '?user_id=' . $userId . '&user_seen=' . $userId;
            $file['duong_dan'] = encryptString($link);
        }
        $deXuat['file_dinh_kem'] = $files;

        // Lấy danh sách bình luận theo dạng cây (tree structure)
        $this->load->model('Dx_binh_luan_model');
        $deXuat['binh_luan'] = $this->Dx_binh_luan_model->getBinhLuanTree($idDeXuat);

        return $deXuat;
    }

    /**
     * Tạo đề xuất mới
     */
    public function taoDeXuat($data, $userId)
    {
        $insertData = [
            'tieu_de' => $data['tieu_de'],
            'noi_dung' => $data['noi_dung'] ?? null,
            'id_dx_loai_de_xuat' => $data['id_dx_loai_de_xuat'] ?? null,
            'id_nguoi_de_xuat' => $data['id_nguoi_de_xuat'] ?? null,
            'nhap' => isset($data['nhap']) ? $data['nhap'] : 0,
            'trang_thai' => $data['trang_thai'], // Mặc định 'nhap'
            'created_user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert('dx_de_xuat', $insertData);
        return $this->db->insert_id();
    }

    /**
     * Cập nhật đề xuất
     */
    public function capNhatDeXuat($idDeXuat, $data, $userId)
    {
        $updateData = [
            'updated_user_id' => $userId,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $updateData = array_merge($updateData, $data);

        $this->db->where('id_de_xuat', $idDeXuat);
        $this->db->where('created_user_id', $userId); // Chỉ người tạo mới được cập nhật
        return $this->db->update('dx_de_xuat', $updateData);
    }

    /**
     * Xóa mềm đề xuất
     */
    public function xoaDeXuat($idDeXuat, $userId)
    {
        $updateData = [
            'deleted_user_id' => $userId,
            'deleted_at' => date('Y-m-d H:i:s')
        ];

        $this->db->where('id_de_xuat', $idDeXuat);
        $this->db->where('created_user_id', $userId); // Chỉ người tạo mới được xóa
        return $this->db->update('dx_de_xuat', $updateData);
    }
}
