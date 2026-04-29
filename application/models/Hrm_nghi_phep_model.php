<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');


class Hrm_nghi_phep_model extends MY_Model
{
    protected $table = 'hrm_nghi_phep';
    protected $primaryKey = 'id_nghi_phep';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Ql_vai_tro_model']);
    }

    public function formatNghiPhep($row)
    {
        if (empty($row))
            return $row;
        if (isset($row['avatar'])) {
            $row['avatar'] = !empty($row['avatar']) ? encryptString($row['avatar']) : null;
        }
        if (isset($row['minh_chung'])) {
            $row['minh_chung_ext'] = !empty($row['minh_chung']) ? pathinfo($row['minh_chung'], PATHINFO_EXTENSION) : null;
            $row['minh_chung'] = !empty($row['minh_chung']) ? encryptString(encryptString($row['minh_chung']) . '?no_login=1') : null;
        }

        $CI = &get_instance();
        $CI->load->helper('hrm');

        // Format if it's an employee row
        if (isset($row['ho_va_ten']) && isset($row['hoc_ham']) && isset($row['trinh_do_dt'])) {
            // $row['ho_va_ten'] = format_fullname_with_titles($row['ho_va_ten'], $row['hoc_ham'], $row['trinh_do_dt']);
        }

        return $row;
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array(), $idNhanVien = null, $qlNguoiDungId = null, $idDonViCongTac = null, $maDonVi = null)
    {
        $maVaiTro = $this->Ql_vai_tro_model->getMaVaiTro($qlNguoiDungId);
        $maVaiTro = $maVaiTro['ql_ma_vai_tro'] ?? null;

        // Kiểm tra xem có phải Super Admin không (có quyền xem tất cả đơn vị)
        $isSuperAdmin = in_array($maVaiTro, Common::VAI_TRO_SUPER_ADMIN);

        // Kiểm tra xem có phải Văn thư đơn vị không (quyền giống lãnh đạo đơn vị)
        $isVanThuDonVi = ($maVaiTro === 'VAN_THU_DON_VI');

        // Kiểm tra xem có phải lãnh đạo đơn vị không (kiểm tra trước để tránh JOIN phức tạp)
        $isLanhDaoDonVi = false;
        $donViLanhDao = [];
        if ($qlNguoiDungId && !$isSuperAdmin && !$isVanThuDonVi) {
            $donViLanhDao = $this->db
                ->select('id_don_vi')
                ->where('ql_nguoi_dung_id', $qlNguoiDungId)
                ->where('deleted_at IS NULL')
                ->get('e_lanh_dao_don_vi')
                ->result_array();
            $isLanhDaoDonVi = !empty($donViLanhDao);
        }

        $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
        $this->db->from($this->table . ' np');

        // Sử dụng subquery để tính SUM() tránh bị nhân đôi khi JOIN nhiều bảng
        $this->db->select(
            'np.*,lnp.ma_loai_phep, lnp.ten_loai_phep,lnp.co_tinh_luong, nv.ho_va_ten, nv.ma_nhan_vien, nv.avatar, nv.hoc_ham, nv.trinh_do_dt, nv.ql_nguoi_dung_id AS nhan_vien_ql_nguoi_dung_id, dv.ten_don_vi, dv.ma_don_vi, npt_sum.so_ngay_nghi, npt_sum.min_ngay_nghi, nd1.ql_nguoi_dung_ho_ten AS nguoi_duyet_cap_mot_ho_ten, nd2.ql_nguoi_dung_ho_ten AS nguoi_duyet_cap_hai_ho_ten,
        pd1.thoi_gian_duyet AS thoi_gian_duyet_cap_mot, pd1.ly_do AS ly_do_duyet_cap_mot,
        pd2.thoi_gian_duyet AS thoi_gian_duyet_cap_hai, pd2.ly_do AS ly_do_duyet_cap_hai,
        pd1.duyet_ho AS duyet_ho_cap_mot, pd1.id_duyet_ho AS id_duyet_ho_cap_mot, pd1.minh_chung_duyet_ho AS minh_chung_duyet_ho_cap_mot, nd_duyet_ho1.ql_nguoi_dung_ho_ten AS nguoi_duyet_ho_cap_mot_ho_ten,
        pd2.duyet_ho AS duyet_ho_cap_hai, pd2.id_duyet_ho AS id_duyet_ho_cap_hai, pd2.minh_chung_duyet_ho AS minh_chung_duyet_ho_cap_hai, nd_duyet_ho2.ql_nguoi_dung_ho_ten AS nguoi_duyet_ho_cap_hai_ho_ten,
        nd_tao.ql_nguoi_dung_ho_ten AS nguoi_tao_ho_ten,
        IF(np.created_user_id IS NOT NULL AND np.created_user_id != nv.ql_nguoi_dung_id, 1, 0) AS tao_ho'
        )
            ->join('hrm_danh_muc_loai_nghi_phep lnp', 'lnp.id_loai_phep = np.id_loai_phep', 'left')
            ->join('hrm_nhan_vien nv', 'nv.id_nhan_vien = np.id_nhan_vien', 'left')
            ->join('e_don_vi dv', 'dv.id_don_vi = np.id_don_vi', 'left')
            ->join('(SELECT id_nghi_phep, SUM(so_ngay_nghi) as so_ngay_nghi, MIN(ngay_nghi) as min_ngay_nghi FROM hrm_nghi_phep_chi_tiet GROUP BY id_nghi_phep) npt_sum', 'npt_sum.id_nghi_phep = np.id_nghi_phep', 'left')
            ->join('ql_nguoi_dung nd1', 'nd1.ql_nguoi_dung_id = np.nguoi_duyet_cap_mot_id', 'left')
            ->join('ql_nguoi_dung nd2', 'nd2.ql_nguoi_dung_id = np.nguoi_duyet_cap_hai_id', 'left')
            ->join('hrm_nghi_phep_nguoi_duyet pd1', 'pd1.id_nghi_phep = np.id_nghi_phep AND pd1.id_nguoi_duyet = np.nguoi_duyet_cap_mot_id AND pd1.cap_duyet = 1', 'left')
            ->join('hrm_nghi_phep_nguoi_duyet pd2', 'pd2.id_nghi_phep = np.id_nghi_phep AND pd2.id_nguoi_duyet = np.nguoi_duyet_cap_hai_id AND pd2.cap_duyet = 2', 'left')
            ->join('ql_nguoi_dung nd_duyet_ho1', 'nd_duyet_ho1.ql_nguoi_dung_id = pd1.id_duyet_ho', 'left')
            ->join('ql_nguoi_dung nd_duyet_ho2', 'nd_duyet_ho2.ql_nguoi_dung_id = pd2.id_duyet_ho', 'left')
            ->join('ql_nguoi_dung nd_tao', 'nd_tao.ql_nguoi_dung_id = np.created_user_id', 'left')
            ->where('np.deleted_at IS NULL')
            ->group_by('np.id_nghi_phep');

        // ========== PHÂN QUYỀN XEM DỮ LIỆU (Từ cao đến thấp) ==========

        // LEVEL 1: SUPER ADMIN - Thấy tất cả đơn của tất cả đơn vị (không cần điều kiện)
        if ($isSuperAdmin) {
            // Không thêm điều kiện WHERE nào, thấy tất cả
        }
        // LEVEL 2: TẤT CẢ NGƯỜI THUỘC PHONG_TCHC (cả lãnh đạo và nhân viên) - Thấy TẤT CẢ đơn đã duyệt cấp 1 + TẤT CẢ đơn của PHONG_TCHC
        // Mục đích: Để nhân viên TCHC có thể nhắc lãnh đạo duyệt cấp 2 cho mọi đơn vị
        else if ($qlNguoiDungId && $maDonVi == 'PHONG_TCHC') {
            $this->db->group_start();
            // Thấy TẤT CẢ đơn đã duyệt cấp 1 (từ MỌI đơn vị - để duyệt cấp 2)
            $this->db->where('np.trang_thai_cap_mot', 'Da_duyet');
            // HOẶC thấy TẤT CẢ đơn của PHONG_TCHC (bất kể trạng thái - để duyệt cấp 1 và 2)
            $this->db->or_where('dv.ma_don_vi', 'PHONG_TCHC');
            $this->db->group_end();
        }
        // LEVEL 3A: VĂN THƯ ĐƠN VỊ - Thấy tất cả đơn của đơn vị mình (giống lãnh đạo đơn vị)
        // Mục đích: Để văn thư có thể nhắc lãnh đạo duyệt cho nhân viên trong đơn vị
        else if ($qlNguoiDungId && $isVanThuDonVi && $idDonViCongTac) {
            $this->db->where('dv.id_don_vi', $idDonViCongTac);
        }
        // LEVEL 3B: LÃNH ĐẠO ĐƠN VỊ - Thấy tất cả đơn của đơn vị mình
        else if ($qlNguoiDungId && $isLanhDaoDonVi) {
            $donViIds = array_column($donViLanhDao, 'id_don_vi');
            if (!empty($donViIds)) {
                $this->db->where_in('dv.id_don_vi', $donViIds);
            } else {
                // Nếu không có đơn vị nào, return empty
                return [
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'thongke' => ['approved' => 0, 'rejected' => 0, 'pending' => 0],
                    'sql' => 'NO_PERMISSION - NOT_LEADER_OF_ANY_UNIT'
                ];
            }
        }
        // LEVEL 4: NHÂN VIÊN ĐƠN VỊ - Chỉ thấy đơn mình tạo
        else if ($idNhanVien) {
            $this->db->where('np.id_nhan_vien', $idNhanVien);
        }
        // LEVEL 5: Không có quyền gì
        else {
            return [
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'thongke' => ['approved' => 0, 'rejected' => 0, 'pending' => 0],
                'sql' => 'NO_PERMISSION'
            ];
        }


        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('nv.ho_va_ten', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                if ($value === '' || $value === null)
                    continue;

                if ($key === 'month') {
                    // Sử dụng subquery vì npt đã được thay bằng npt_sum
                    $this->db->where('EXISTS (SELECT 1 FROM hrm_nghi_phep_chi_tiet npt WHERE npt.id_nghi_phep = np.id_nghi_phep AND MONTH(npt.ngay_nghi) = ' . $this->db->escape($value) . ')');
                } elseif ($key === 'year') {
                    $this->db->where('EXISTS (SELECT 1 FROM hrm_nghi_phep_chi_tiet npt WHERE npt.id_nghi_phep = np.id_nghi_phep AND YEAR(npt.ngay_nghi) = ' . $this->db->escape($value) . ')');
                } elseif ($key === 'id_don_vi') {
                    $this->db->where('np.id_don_vi', $value);
                } elseif ($key === 'trang_thai') {
                    if ($maDonVi == 'PHONG_TCHC') {
                        $this->db->where('np.trang_thai_cap_hai', $value);
                    } else {
                        $this->db->where('np.trang_thai_cap_mot', $value);
                    }
                } elseif ($key === 'dateRange') {
                    if (is_array($value)) {
                        if (!empty($value['from'])) {
                            $this->db->where('EXISTS (SELECT 1 FROM hrm_nghi_phep_chi_tiet npt WHERE npt.id_nghi_phep = np.id_nghi_phep AND npt.ngay_nghi >= ' . $this->db->escape($value['from']) . ')');
                        }
                        if (!empty($value['to'])) {
                            $this->db->where('EXISTS (SELECT 1 FROM hrm_nghi_phep_chi_tiet npt WHERE npt.id_nghi_phep = np.id_nghi_phep AND npt.ngay_nghi <= ' . $this->db->escape($value['to']) . ')');
                        }
                    }
                } else {
                    $this->db->where('np.' . $key, $value);
                }
            }
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE);

        if (!empty($orderBy)) {
            $orders = $orderBy['order'] ?? [];
            $columns = $orderBy['columns'] ?? [];

            $columnMapping = [
                'nhan_vien' => 'nv.ho_va_ten',
                'chi_tiet_ngay_nghi' => 'min_ngay_nghi',
                'ngay_nop' => 'np.created_at',
                'ten_loai_phep' => 'lnp.ten_loai_phep',
                'ten_don_vi' => 'dv.ten_don_vi'
            ];

            foreach ($orders as $order) {
                $colIndex = $order['column'];
                $dir = $order['dir'] ?? 'asc';

                // Nếu colIndex là số, lấy tên cột từ columns. Nếu là string, dùng trực tiếp.
                $colName = is_numeric($colIndex) ? ($columns[$colIndex]['data'] ?? null) : $colIndex;

                if ($colName) {
                    $dbField = $columnMapping[$colName] ?? 'np.' . $colName;
                    $this->db->order_by($dbField, $dir);
                }
            }
        } else {
            $this->db->order_by('np.id_nghi_phep', 'DESC');
        }

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        $query = $this->db->get();
        $data = $query->result_array();
        $sql = $this->db->last_query();

        // Tính toán thống kê cho toàn bộ đơn (Chính xác theo phân quyền user và vai trò đơn vị)
        if ($maDonVi == 'PHONG_TCHC' || $isSuperAdmin) {
            $this->db->select('
                SUM(CASE WHEN np.trang_thai_cap_hai = "Da_duyet" THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN np.trang_thai_cap_hai = "Tu_choi" THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN np.trang_thai_cap_hai = "Cho_duyet" THEN 1 ELSE 0 END) as pending
            ', FALSE);
        } else {
            $this->db->select('
                SUM(CASE WHEN np.trang_thai_cap_mot = "Da_duyet" THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN np.trang_thai_cap_mot = "Tu_choi" THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN np.trang_thai_cap_mot = "Cho_duyet" THEN 1 ELSE 0 END) as pending
            ', FALSE);
        }

        $this->db->from($this->table . ' np');
        $this->db->join('hrm_nhan_vien nv', 'nv.id_nhan_vien = np.id_nhan_vien', 'left');
        $this->db->join('e_don_vi dv', 'dv.id_don_vi = np.id_don_vi', 'left');
        $this->db->where('np.deleted_at IS NULL');

        // ========== TÁI ÁP DỤNG PHÂN QUYỀN CHO PHẦN THỐNG KÊ ==========

        // LEVEL 1: SUPER ADMIN
        if ($isSuperAdmin) {
            // Không cần điều kiện WHERE
        }
        // LEVEL 2: TẤT CẢ NGƯỜI THUỘC PHONG_TCHC (cả lãnh đạo và nhân viên)
        else if ($qlNguoiDungId && $maDonVi == 'PHONG_TCHC') {
            $this->db->group_start();
            $this->db->where('np.trang_thai_cap_mot', 'Da_duyet');
            $this->db->or_where('dv.ma_don_vi', 'PHONG_TCHC');
            $this->db->group_end();
        }
        // LEVEL 3A: VĂN THƯ ĐƠN VỊ
        else if ($qlNguoiDungId && $isVanThuDonVi && $idDonViCongTac) {
            $this->db->where('dv.id_don_vi', $idDonViCongTac);
        }
        // LEVEL 3B: LÃNH ĐẠO ĐƠN VỊ
        else if ($qlNguoiDungId && $isLanhDaoDonVi) {
            $donViIds = array_column($donViLanhDao, 'id_don_vi');
            if (!empty($donViIds)) {
                $this->db->where_in('dv.id_don_vi', $donViIds);
            }
        }
        // LEVEL 4: NHÂN VIÊN
        else if ($idNhanVien) {
            $this->db->where('np.id_nhan_vien', $idNhanVien);
        }

        $thongke = $this->db->get()->row_array();

        // Sử dụng lại biến đã tính toán từ đầu hàm (tối ưu, không query lại)
        $isLanhDao = $isSuperAdmin || ($maDonVi == 'PHONG_TCHC') || $isLanhDaoDonVi || $isVanThuDonVi;

        foreach ($data as &$item) {
            $item = $this->formatNghiPhep($item);

            // Lấy chi tiết ngày nghỉ
            $chiTietNgayNghi = $this->db
                ->select('ngay_nghi, buoi_nghi, so_ngay_nghi')
                ->where('id_nghi_phep', $item['id_nghi_phep'])
                ->order_by('ngay_nghi', 'ASC')
                ->order_by('buoi_nghi', 'ASC')
                ->get('hrm_nghi_phep_chi_tiet')
                ->result_array();

            // Gộp các ngày nghỉ theo ngày
            $ngayNghiGroup = [];
            foreach ($chiTietNgayNghi as $ct) {
                $ngay = $ct['ngay_nghi'];
                if (!isset($ngayNghiGroup[$ngay])) {
                    $ngayNghiGroup[$ngay] = [
                        'ngay_nghi' => $ngay,
                        'buoi' => [],
                        'so_ngay_nghi' => 0
                    ];
                }
                $ngayNghiGroup[$ngay]['buoi'][] = $ct['buoi_nghi'];
                $ngayNghiGroup[$ngay]['so_ngay_nghi'] += floatval($ct['so_ngay_nghi']);
            }

            // Tạo chi tiết ngày nghỉ với buổi nghỉ
            $chiTietNgayNghiFormatted = [];

            foreach ($ngayNghiGroup as $ngay => $info) {
                if (count($info['buoi']) == 2) {
                    // Nghỉ cả 2 buổi -> Ca_ngay
                    $buoiNghi = 'Ca_ngay';
                } else {
                    // Nghỉ 1 buổi
                    $buoiNghi = $info['buoi'][0];
                }

                $chiTietNgayNghiFormatted[] = [
                    'ngay_nghi' => $ngay,
                    'buoi_nghi' => $buoiNghi,
                    'so_ngay_nghi' => $info['so_ngay_nghi']
                ];
            }

            $item['chi_tiet_ngay_nghi'] = $chiTietNgayNghiFormatted;

            // Chỉ lãnh đạo mới thấy cột trạng thái duyệt cấp 2
            // if (!$isLanhDao) {
            //     unset($item['trang_thai_cap_hai']);
            //     unset($item['nguoi_duyet_cap_hai_id']);
            // }

            $item['minh_chung_duyet_ho_cap_mot'] = !empty($item['minh_chung_duyet_ho_cap_mot']) ? encryptString(encryptString($item['minh_chung_duyet_ho_cap_mot']) . '?no_login=1') : null;
            $item['minh_chung_duyet_ho_cap_hai'] = !empty($item['minh_chung_duyet_ho_cap_hai']) ? encryptString(encryptString($item['minh_chung_duyet_ho_cap_hai']) . '?no_login=1') : null;
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'thongke' => $thongke,
            'sql' => $sql
        ];
    }
    public function getQuotaStats($idDonVi = null, $startDate = null, $endDate = null, $qlNguoiDungId = null)
    {
        $this->load->model('Hrm_nhan_vien_model');

        // LEVEL 1: Nếu có idDonVi truyền vào → ưu tiên dùng đơn vị đó luôn
        if ($idDonVi) {
            $nhanVienList = $this->Hrm_nhan_vien_model->getNhanVienCungDonVi($idDonVi);
        }
        // LEVEL 2: Không có idDonVi → fallback về danh sách đơn vị lãnh đạo của người dùng
        elseif ($qlNguoiDungId) {
            $donViLanhDao = $this->db
                ->select('id_don_vi')
                ->where('ql_nguoi_dung_id', $qlNguoiDungId)
                ->where('deleted_at IS NULL')
                ->get('e_lanh_dao_don_vi')
                ->result_array();

            if (empty($donViLanhDao)) {
                return [];
            }

            // Lấy nhân viên của TẤT CẢ các đơn vị mà người đó lãnh đạo
            $idDonViList = array_column($donViLanhDao, 'id_don_vi');
            $nhanVienList = $this->db
                ->select('nv.id_nhan_vien, nv.ho_va_ten, nv.ma_nhan_vien')
                ->from('hrm_nhan_vien nv')
                ->where_in('nv.id_don_vi_cong_tac', $idDonViList)
                ->where('nv.deleted_at IS NULL')
                ->group_by('nv.id_nhan_vien')
                ->get()
                ->result_array();
        }
        // LEVEL 3: Không có gì → trả về rỗng
        else {
            return [];
        }

        if (empty($nhanVienList)) {
            return [];
        }

        $ids = array_column($nhanVienList, 'id_nhan_vien');

        // Lấy thông tin công việc, avatar và gender
        $extraInfo = $this->db
            ->select('nv.id_nhan_vien, nv.avatar, nv.gioi_tinh, cv.ngay_lam_chinh_thuc, cv.so_ngay_phep')
            ->from('hrm_nhan_vien nv')
            ->join('hrm_nhan_vien_cong_viec cv', 'cv.id_nhan_vien = nv.id_nhan_vien', 'left')
            ->where_in('nv.id_nhan_vien', $ids)
            ->get()
            ->result_array();

        $extraMap = [];
        foreach ($extraInfo as $row) {
            // Tính số ngày phép dựa trên thâm niên
            $soNgayPhep = 12; // Mặc định

            if (!empty($row['ngay_lam_chinh_thuc'])) {
                $ngayLamChinhThuc = new DateTime($row['ngay_lam_chinh_thuc']);
                $ngayHienTai = new DateTime();
                $soNamLamViec = $ngayHienTai->diff($ngayLamChinhThuc)->y;

                // Cứ mỗi 5 năm làm việc thì cộng thêm 1 ngày phép
                $soNgayPhep = 12 + floor($soNamLamViec / 5);

                // Cập nhật lại cột so_ngay_phep trong bảng hrm_nhan_vien_cong_viec
                $this->db
                    ->where('id_nhan_vien', $row['id_nhan_vien'])
                    ->update('hrm_nhan_vien_cong_viec', ['so_ngay_phep' => $soNgayPhep]);
            } else if (!empty($row['so_ngay_phep'])) {
                // Nếu đã có so_ngay_phep sẵn thì dùng luôn
                $soNgayPhep = intval($row['so_ngay_phep']);
            }

            $extraMap[$row['id_nhan_vien']] = [
                'avatar' => $row['avatar'] ?? null,
                'gioi_tinh' => $row['gioi_tinh'] ?? null,
                'so_ngay_phep' => $soNgayPhep
            ];
        }

        // Tính tổng ngày nghỉ (chỉ tính phép có lương, đã duyệt cấp 1 và cấp 2)
        $this->db->select('np.id_nhan_vien, SUM(ct.so_ngay_nghi) as used');
        $this->db->from('hrm_nghi_phep np');
        $this->db->join('hrm_nghi_phep_chi_tiet ct', 'ct.id_nghi_phep = np.id_nghi_phep');
        $this->db->join('hrm_danh_muc_loai_nghi_phep lnp', 'lnp.id_loai_phep = np.id_loai_phep');

        $this->db->where_in('np.id_nhan_vien', $ids);

        // Lọc theo khoảng thời gian nếu có
        if ($startDate && $endDate) {
            $this->db->where('ct.ngay_nghi >=', $startDate);
            $this->db->where('ct.ngay_nghi <=', $endDate);
        }

        $this->db->where('np.trang_thai_cap_mot', 'Da_duyet');
        $this->db->where('np.trang_thai_cap_hai', 'Da_duyet');
        $this->db->where('np.deleted_at IS NULL');
        $this->db->where('lnp.co_tinh_luong', 1);

        $this->db->group_by('np.id_nhan_vien');
        $usageData = $this->db->get()->result_array();

        $sql = $this->db->last_query();

        $usageMap = [];
        foreach ($usageData as $u) {
            $usageMap[$u['id_nhan_vien']] = $u['used'];
        }

        $result = [];
        foreach ($nhanVienList as $nv) {
            $id = $nv['id_nhan_vien'];
            $extra = $extraMap[$id] ?? [];

            $row = $this->formatNghiPhep([
                'id' => $id,
                'name' => $nv['ho_va_ten'],
                'uid' => $nv['ma_nhan_vien'],
                'used' => floatval($usageMap[$id] ?? 0),
                'total' => $extra['so_ngay_phep'] ?? 12,
                'avatar' => $extra['avatar'] ?? null,
                'gender' => $extra['gioi_tinh'] ?? null
            ]);
            $result[] = $row;
        }

        return $result;
    }
}
