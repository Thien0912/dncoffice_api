<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');


class Hrm_ngoaigio_model extends MY_Model
{
    protected $table = 'hrm_ngoai_gio';
    protected $primaryKey = 'id_ngoai_gio';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Ql_vai_tro_model']);
    }

    /**
     * Build WHERE clause cho phân quyền (return SQL string)
     * Phân quyền từ cao đến thấp:
     * 1. xemtatca - Xem tất cả (Super Admin, TCHC)
     * 2. xemdonvi - Xem theo đơn vị (Lãnh đạo, Văn thư, Người duyệt)
     * 3. xemcanhan - Xem cá nhân (Nhân viên thường)
     */
    private function buildPermissionWhere($qlNguoiDungId, $idNhanVien, $idDonViCongTac)
    {
        $permissions = $this->Ql_vai_tro_model->getUserPermissionKeys($qlNguoiDungId);

        // Cấp 1: Xem tất cả
        if (in_array('ngoaigio.xemtatca', $permissions)) {
            return '1=1';
        }

        // Cấp 2: Xem theo đơn vị (gộp lãnh đạo, văn thư, người duyệt)
        if (in_array('ngoaigio.xemdonvi', $permissions)) {
            $conditions = [];

            // Xem đơn vị của mình (Văn thư)
            if ($idDonViCongTac) {
                $conditions[] = "nv.id_don_vi_cong_tac = " . $this->db->escape($idDonViCongTac);
            }

            // Xem đơn vị cấp dưới (Lãnh đạo)
            if ($qlNguoiDungId) {
                $conditions[] = "nv.id_don_vi_cong_tac IN (
                    SELECT id_don_vi FROM e_lanh_dao_don_vi 
                    WHERE ql_nguoi_dung_id = " . $this->db->escape($qlNguoiDungId) . " 
                    AND deleted_at IS NULL
                )";

                // Xem đơn được phân công duyệt (Người duyệt)
                $conditions[] = "EXISTS (
                    SELECT 1 FROM hrm_ngoai_gio_nguoi_duyet 
                    WHERE id_ngoai_gio = ng.id_ngoai_gio 
                    AND id_nguoi_duyet = " . $this->db->escape($qlNguoiDungId) . "
                )";
            }

            if (!empty($conditions)) {
                return '(' . implode(' OR ', $conditions) . ')';
            }
        }

        // Cấp 3: Xem cá nhân (mặc định)
        // Trường hợp đặc biệt: id_don_vi = -1 → export tất cả (controller đã kiểm tra quyền)
        if ($idDonViCongTac == -1) {
            return '1=1';
        }

        if ($idNhanVien == -1) {
            // Nếu chọn "Tất cả" nhân viên (-1) -> Lấy tất cả đơn vị
            return '1=1';
        } elseif (!empty($idNhanVien)) {
            // Nếu truyền một ID nhân viên hợp lệ -> Chỉ xem đơn của người đó
            return "ng.id_nhan_vien = " . $this->db->escape($idNhanVien);
        } elseif ($idDonViCongTac !== null && $idDonViCongTac !== '') {
            // Null nhân viên nhưng có đơn vị cụ thể → filter theo đơn vị đó
            return "nv.id_don_vi_cong_tac = " . $this->db->escape($idDonViCongTac);
        }

        // Null cả nhân viên và đơn vị → chỉ xem đơn của bản thân
        return "ng.id_nhan_vien = (SELECT id_nhan_vien FROM hrm_nhan_vien WHERE ql_nguoi_dung_id = " . $this->db->escape($qlNguoiDungId) . ")";
    }

    /**
     * Build WHERE clause cho search filters (return SQL string)
     */
    private function buildSearchWhere($searchValue, $searchKey)
    {
        $conditions = [];

        if ($searchValue) {
            $search = $this->db->escape_like_str($searchValue);
            $conditions[] = "(nv.ho_va_ten LIKE '%{$search}%' OR nv.ma_nhan_vien LIKE '%{$search}%')";
        }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                if ($value === '' || $value === null)
                    continue;

                if ($key === 'id_don_vi') {
                    // Nếu id_don_vi = -1 → lấy tất cả đơn vị (không filter)
                    if ($value != -1) {
                        $conditions[] = "nv.id_don_vi_cong_tac = " . $this->db->escape($value);
                    }
                } elseif ($key === 'trang_thai_tong' || $key === 'trang_thai') {
                    $conditions[] = "ng.trang_thai_tong = " . $this->db->escape($value);
                } elseif ($key === 'dateRange' && is_array($value)) {
                    if (!empty($value['from'])) {
                        $conditions[] = "ng.ngay_dang_ky >= " . $this->db->escape($value['from']);
                    }
                    if (!empty($value['to'])) {
                        $conditions[] = "ng.ngay_dang_ky <= " . $this->db->escape($value['to']);
                    }
                } elseif ($key === 'id_bang_cham_cong') {

                } else {
                    $conditions[] = "ng.{$key} = " . $this->db->escape($value);
                }
            }
        }

        return empty($conditions) ? '1=1' : implode(' AND ', $conditions);
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array(), $idNhanVien = null, $qlNguoiDungId = null, $idDonViCongTac = null, $maDonVi = null)
    {
        $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
        // Build WHERE conditions
        $permissionWhere = $this->buildPermissionWhere($qlNguoiDungId, $idNhanVien, $idDonViCongTac);
        $searchWhere = $this->buildSearchWhere($searchValue, $searchKey);

        // Base FROM/JOIN
        $baseFrom = "FROM hrm_ngoai_gio ng
            LEFT JOIN hrm_nhan_vien nv ON nv.id_nhan_vien = ng.id_nhan_vien
            LEFT JOIN e_don_vi dv ON dv.id_don_vi = nv.id_don_vi_cong_tac
            WHERE ng.deleted_at IS NULL AND {$permissionWhere}";

        // COUNT tổng (chỉ phân quyền)
        $countTotalSql = "SELECT COUNT(DISTINCT ng.id_ngoai_gio) as total {$baseFrom}";
        $recordsTotal = $this->db->query($countTotalSql)->row()->total;

        // COUNT filtered (phân quyền + search)
        $countFilteredSql = "SELECT COUNT(DISTINCT ng.id_ngoai_gio) as total {$baseFrom} AND {$searchWhere}";
        $recordsFiltered = $this->db->query($countFilteredSql)->row()->total;

        // Lấy data chính - dùng query builder
        $this->db->from($this->table . ' ng');
        $this->db->join('hrm_nhan_vien nv', 'nv.id_nhan_vien = ng.id_nhan_vien', 'left');
        $this->db->join('e_don_vi dv', 'dv.id_don_vi = nv.id_don_vi_cong_tac', 'left');
        $this->db->join('ql_nguoi_dung nd_tao', 'nd_tao.ql_nguoi_dung_id = ng.created_user_id', 'left');
        $this->db->join('hrm_ngoai_gio_nguoi_duyet hnd', 'hnd.id_ngoai_gio = ng.id_ngoai_gio', 'left');
        $this->db->join('ql_nguoi_dung nd_duyet', 'nd_duyet.ql_nguoi_dung_id = hnd.id_nguoi_duyet', 'left');
        $this->db->join('hrm_api_cham_cong cc', 'cc.emp_code = nv.ma_cham_cong AND DATE(cc.punch_time) = ng.ngay_dang_ky', 'left');

        $this->db->select(
            'ng.*, nv.ho_va_ten, nv.ma_nhan_vien, nv.avatar, nv.hoc_ham, nv.trinh_do_dt, 
             nd_tao.ql_nguoi_dung_ho_ten AS nguoi_tao_ho_ten,
             dv.ten_don_vi, dv.ma_don_vi,
             MIN(cc.punch_time) AS thoi_gian_bat_dau_cham_cong,
             MAX(cc.punch_time) AS thoi_gian_ket_thuc_cham_cong,
             IF(ng.created_user_id IS NOT NULL AND ng.created_user_id != nv.ql_nguoi_dung_id, 1, 0) AS tao_ho,
             GROUP_CONCAT(DISTINCT CONCAT_WS("^", hnd.cap_duyet, nd_duyet.ql_nguoi_dung_id, nd_duyet.ql_nguoi_dung_ho_ten, IFNULL(nd_duyet.ql_nguoi_dung_avatar, ""), hnd.trang_thai) SEPARATOR "|") AS string_nguoi_duyet',
            FALSE
        );

        $this->db->where('ng.deleted_at IS NULL');
        $this->db->where($permissionWhere, NULL, FALSE);
        $this->db->where($searchWhere, NULL, FALSE);
        $this->db->group_by('ng.id_ngoai_gio');

        if (!empty($orderBy)) {
            $orders = $orderBy['order'] ?? [];
            $columns = $orderBy['columns'] ?? [];

            $columnMapping = [
                'nhan_vien' => 'nv.ho_va_ten',
                'ngay_dang_ky' => 'ng.ngay_dang_ky',
                'ten_don_vi' => 'dv.ten_don_vi',
                'gio_bat_dau' => 'ng.gio_bat_dau',
                'gio_ket_thuc' => 'ng.gio_ket_thuc',
                'gio_ngoai_gio' => 'ng.gio_bat_dau',
                'so_gio' => 'ng.so_gio',
                'noi_dung' => 'ng.noi_dung',
                'trang_thai_tong' => 'ng.trang_thai_tong',
                'thoi_gian_cham_cong' => 'MIN(cc.punch_time)',
                'thoi_gian_bat_dau_cham_cong' => 'MIN(cc.punch_time)',
            ];

            foreach ($orders as $order) {
                $colIndex = $order['column'];
                $dir = $order['dir'] ?? 'asc';
                $colName = is_numeric($colIndex) ? ($columns[$colIndex]['data'] ?? null) : $colIndex;

                if ($colName) {
                    $dbField = $columnMapping[$colName] ?? 'ng.' . $colName;
                    $this->db->order_by($dbField, $dir);
                }
            }
        } else {
            $this->db->order_by('ng.id_ngoai_gio', 'DESC');
        }

        if ($length != '-1' && $length != null) {
            $this->db->limit($length, $start);
        }

        $query = $this->db->get();
        $data = $query->result_array();
        $sql = $this->db->last_query();

        // format dữ liệu
        foreach ($data as &$item) {
            if (isset($item['avatar']) && !empty($item['avatar'])) {
                $item['avatar'] = encryptString($item['avatar']);
            }

            // Xử lý mảng người duyệt từ string GROUP_CONCAT
            if (!empty($item['string_nguoi_duyet'])) {
                $rows = explode("|", $item['string_nguoi_duyet']);
                $approvers = [];
                foreach ($rows as $r) {
                    $parts = explode("^", $r);
                    if (count($parts) >= 5) {
                        $approvers[] = [
                            'cap_duyet' => $parts[0],
                            'ql_nguoi_dung_id' => $parts[1],
                            'ql_nguoi_dung_ho_ten' => $parts[2],
                            'ql_nguoi_dung_avatar' => !empty($parts[3]) ? encryptString($parts[3]) : null,
                            'trang_thai' => $parts[4]
                        ];
                    }
                }
                $item['danh_sach_nguoi_duyet'] = $approvers;
            } else {
                $item['danh_sach_nguoi_duyet'] = [];
            }

            unset($item['string_nguoi_duyet']);
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'sql' => $sql
        ];
    }

    /**
     * Tính tổng giờ theo loại ngày (NT/CN/Lễ) cho từng nhân viên
     * trong khoảng thời gian cụ thể
     * 
     * @param string $startDate Y-m-d
     * @param string $endDate Y-m-d
     * @param int|null $idNhanVien
     * @param int|null $idDonVi
     * @return array [id_nhan_vien => ['NT' => hours, 'CN' => hours, 'LE' => hours]]
     */
    public function getTotalHoursByDayType($startDate, $endDate, $idNhanVien = null, $idDonVi = null)
    {
        $sql = "
            SELECT 
                ng.id_nhan_vien,
                CASE 
                    WHEN le.ngay IS NOT NULL THEN 'LE'
                    WHEN DAYOFWEEK(ng.ngay_dang_ky) = 1 THEN 'CN'
                    ELSE 'NT'
                END AS loai_ngay,
                SUM(ng.so_gio) AS tong_gio
            FROM hrm_ngoai_gio ng
            LEFT JOIN hrm_nhan_vien nv ON nv.id_nhan_vien = ng.id_nhan_vien
            LEFT JOIN hrm_ngay_le_viet_nam le ON le.ngay = ng.ngay_dang_ky
            WHERE ng.deleted_at IS NULL
                AND ng.ngay_dang_ky BETWEEN ? AND ?
                AND ng.trang_thai_tong = 'Da_duyet'
        ";

        $params = [$startDate, $endDate];

        if ($idNhanVien) {
            $sql .= " AND ng.id_nhan_vien = ?";
            $params[] = $idNhanVien;
        }

        if ($idDonVi) {
            $sql .= " AND nv.id_don_vi_cong_tac = ?";
            $params[] = $idDonVi;
        }

        $sql .= " GROUP BY ng.id_nhan_vien, loai_ngay";

        $query = $this->db->query($sql, $params);
        $results = $query->result_array();

        // Format thành mảng dễ sử dụng
        $summary = [];
        foreach ($results as $row) {
            $empId = $row['id_nhan_vien'];
            $dayType = $row['loai_ngay'];
            $hours = (float) $row['tong_gio'];

            if (!isset($summary[$empId])) {
                $summary[$empId] = ['NT' => 0, 'CN' => 0, 'LE' => 0];
            }

            $summary[$empId][$dayType] = $hours;
        }

        return $summary;
    }

    /**
     * Lấy thống kê tổng quan ngoài giờ theo điều kiện filter
     * @return array [totalRegisteredHours, totalActualHours, totalDebtHours, totalRequests, statusStats]
     */
    public function getStatistics($searchValue = null, $searchKey = array(), $idNhanVien = null, $qlNguoiDungId = null, $idDonViCongTac = null, $maDonVi = null)
    {
        // Build WHERE conditions
        $permissionWhere = $this->buildPermissionWhere($qlNguoiDungId, $idNhanVien, $idDonViCongTac);
        $searchWhere = $this->buildSearchWhere($searchValue, $searchKey);

        // Query để lấy danh sách đơn với thông tin chấm công
        $sql = "
            SELECT 
                ng.id_ngoai_gio,
                ng.gio_bat_dau,
                ng.gio_ket_thuc,
                ng.so_gio,
                ng.ngay_dang_ky,
                ng.trang_thai_tong,
                nv.id_nhan_vien,
                nv.id_ca_lam_viec,
                MIN(cc.punch_time) AS thoi_gian_bat_dau_cham_cong,
                MAX(cc.punch_time) AS thoi_gian_ket_thuc_cham_cong,
                ca.check_in,
                ca.check_out,
                ca.bat_dau_check_in,
                ca.ket_thuc_check_out
            FROM hrm_ngoai_gio ng
            LEFT JOIN hrm_nhan_vien nv ON nv.id_nhan_vien = ng.id_nhan_vien
            LEFT JOIN e_don_vi dv ON dv.id_don_vi = nv.id_don_vi_cong_tac
            LEFT JOIN hrm_api_cham_cong cc ON cc.emp_code = nv.ma_cham_cong AND DATE(cc.punch_time) = ng.ngay_dang_ky
            LEFT JOIN hrm_ca_lam_viec ca ON ca.id = nv.id_ca_lam_viec
            WHERE ng.deleted_at IS NULL 
                AND {$permissionWhere}
                AND {$searchWhere}
            GROUP BY ng.id_ngoai_gio
        ";

        $query = $this->db->query($sql);
        $records = $query->result_array();

        // Tính toán thống kê
        $totalRegisteredMins = 0;
        $totalActualMins = 0;
        $totalDebtMins = 0;
        $totalRequests = count($records);

        // Status stats (cho employee mode)
        $statusStats = [
            'pending' => ['count' => 0, 'hours' => 0],
            'approved' => ['count' => 0, 'hours' => 0],
            'rejected' => ['count' => 0, 'hours' => 0],
            'cancelled' => ['count' => 0, 'hours' => 0]
        ];

        foreach ($records as $req) {
            // 1. Tính thời gian đăng ký (rDuration)
            if (empty($req['gio_bat_dau']) || empty($req['gio_ket_thuc'])) {
                continue;
            }

            $ngayDangKy = $req['ngay_dang_ky'];
            $rStart = strtotime($ngayDangKy . ' ' . $req['gio_bat_dau']);
            $rEnd = strtotime($ngayDangKy . ' ' . $req['gio_ket_thuc']);

            $rDuration = ($rEnd - $rStart) / 60; // phút
            if ($rDuration < 0) {
                // Qua đêm
                $rDuration += 24 * 60;
                $rEnd += 24 * 60 * 60; // Cộng thêm 1 ngày cho timestamp
            }

            // Trừ giờ nghỉ trưa nếu có
            $lunchBreakMins = 0;
            if (!empty($req['ket_thuc_check_in']) && !empty($req['bat_dau_check_out'])) {
                $lunchStart = strtotime($ngayDangKy . ' ' . $req['ket_thuc_check_in']);
                $lunchEnd = strtotime($ngayDangKy . ' ' . $req['bat_dau_check_out']);

                // Tính overlap giữa thời gian đăng ký và giờ nghỉ trưa
                $overlapStart = max($rStart, $lunchStart);
                $overlapEnd = min($rEnd, $lunchEnd);

                if ($overlapEnd > $overlapStart) {
                    $lunchBreakMins = ($overlapEnd - $overlapStart) / 60;
                }
            }

            $rDuration = max(0, $rDuration - $lunchBreakMins);
            $rHours = $rDuration / 60;
            $totalRegisteredMins += $rDuration;

            // Tính status stats
            if ($req['trang_thai_tong'] === 'Cho_duyet') {
                $statusStats['pending']['count']++;
                $statusStats['pending']['hours'] += $rHours;
            } elseif ($req['trang_thai_tong'] === 'Da_duyet') {
                $statusStats['approved']['count']++;
                $statusStats['approved']['hours'] += $rHours;
            } elseif ($req['trang_thai_tong'] === 'Tu_choi') {
                $statusStats['rejected']['count']++;
                $statusStats['rejected']['hours'] += $rHours;
            } elseif ($req['trang_thai_tong'] === 'Huy') {
                $statusStats['cancelled']['count']++;
                $statusStats['cancelled']['hours'] += $rHours;
            }

            // 2. Tính thời gian thực tế từ máy chấm công (overlap)
            $actualMins = 0;
            if (!empty($req['thoi_gian_bat_dau_cham_cong']) && !empty($req['thoi_gian_ket_thuc_cham_cong'])) {
                $aStart = strtotime($ngayDangKy . ' ' . date('H:i:s', strtotime($req['thoi_gian_bat_dau_cham_cong'])));
                $aEnd = strtotime($ngayDangKy . ' ' . date('H:i:s', strtotime($req['thoi_gian_ket_thuc_cham_cong'])));

                if ($aEnd < $aStart) {
                    $aEnd += 24 * 60 * 60;
                }

                // Tính phần giao nhau (overlap) giữa thời gian đăng ký và chấm công
                $overlapStart = max($rStart, $aStart);
                $overlapEnd = min($rEnd, $aEnd);

                $overlap = ($overlapEnd - $overlapStart) / 60;
                if ($overlap > 0) {
                    $actualMins = $overlap;

                    // Trừ giờ nghỉ trưa khỏi thời gian chấm công thực tế
                    if (!empty($req['ket_thuc_check_in']) && !empty($req['bat_dau_check_out'])) {
                        $lunchStart = strtotime($ngayDangKy . ' ' . $req['ket_thuc_check_in']);
                        $lunchEnd = strtotime($ngayDangKy . ' ' . $req['bat_dau_check_out']);

                        // Tính overlap giữa actual time và giờ nghỉ trưa
                        $lunchOverlapStart = max($overlapStart, $lunchStart);
                        $lunchOverlapEnd = min($overlapEnd, $lunchEnd);

                        if ($lunchOverlapEnd > $lunchOverlapStart) {
                            $lunchBreakMins = ($lunchOverlapEnd - $lunchOverlapStart) / 60;
                            $actualMins -= $lunchBreakMins;
                        }
                    }
                }
            }

            $totalActualMins += max(0, $actualMins);

            // 3. Tính nợ OT (đi trễ + về sớm so với ca làm việc)
            // LƯU Ý: KHÔNG tính giờ nghỉ trưa là nợ
            $debtMins = 0;
            if (
                !empty($req['thoi_gian_bat_dau_cham_cong']) && !empty($req['thoi_gian_ket_thuc_cham_cong'])
                && !empty($req['check_in']) && !empty($req['check_out'])
            ) {

                // Parse thời gian ca làm việc
                $caCheckIn = strtotime($ngayDangKy . ' ' . $req['check_in']);
                $caCheckOut = strtotime($ngayDangKy . ' ' . $req['check_out']);

                // Parse thời gian chấm công thực tế
                $actualCheckIn = strtotime($ngayDangKy . ' ' . date('H:i:s', strtotime($req['thoi_gian_bat_dau_cham_cong'])));
                $actualCheckOut = strtotime($ngayDangKy . ' ' . date('H:i:s', strtotime($req['thoi_gian_ket_thuc_cham_cong'])));

                // Xử lý trường hợp qua đêm
                if ($caCheckOut < $caCheckIn) {
                    $caCheckOut += 24 * 60 * 60;
                }
                if ($actualCheckOut < $actualCheckIn) {
                    $actualCheckOut += 24 * 60 * 60;
                }

                // Tính đi trễ (phút) - chỉ tính buổi sáng
                $lateMins = 0;
                if ($actualCheckIn > $caCheckIn) {
                    $lateMins = ($actualCheckIn - $caCheckIn) / 60;
                }

                // Tính về sớm (phút) - chỉ tính buổi chiều
                $earlyLeaveMins = 0;
                if ($actualCheckOut < $caCheckOut) {
                    $earlyLeaveMins = ($caCheckOut - $actualCheckOut) / 60;
                }

                $debtMins = $lateMins + $earlyLeaveMins;
            }

            $totalDebtMins += max(0, $debtMins);
        }

        return [
            'totalRegisteredHours' => round($totalRegisteredMins / 60, 1),
            'totalActualHours' => round($totalActualMins / 60, 1),
            'totalDebtHours' => round($totalDebtMins / 60, 1),
            'totalRequests' => $totalRequests,
            'statusStats' => [
                'pending' => [
                    'count' => $statusStats['pending']['count'],
                    'hours' => round($statusStats['pending']['hours'], 1)
                ],
                'approved' => [
                    'count' => $statusStats['approved']['count'],
                    'hours' => round($statusStats['approved']['hours'], 1)
                ],
                'rejected' => [
                    'count' => $statusStats['rejected']['count'],
                    'hours' => round($statusStats['rejected']['hours'], 1)
                ],
                'cancelled' => [
                    'count' => $statusStats['cancelled']['count'],
                    'hours' => round($statusStats['cancelled']['hours'], 1)
                ]
            ]
        ];
    }

    /**
     * Lấy danh sách đơn ngoài giờ đã xóa (Thùng rác)
     */
    public function getTrash($start = 0, $length = 10, $searchValue = null, $qlNguoiDungId = null)
    {
        $this->db->select('ng.*, nv.ho_va_ten, nv.ma_nhan_vien, dv.ten_don_vi, nd_xoa.ql_nguoi_dung_ho_ten as nguoi_xoa_ho_ten');
        $this->db->from($this->table . ' ng');
        $this->db->join('hrm_nhan_vien nv', 'nv.id_nhan_vien = ng.id_nhan_vien', 'left');
        $this->db->join('e_don_vi dv', 'dv.id_don_vi = nv.id_don_vi_cong_tac', 'left');
        $this->db->join('ql_nguoi_dung nd_xoa', 'nd_xoa.ql_nguoi_dung_id = ng.deleted_user_id', 'left');

        $this->db->where('ng.deleted_at IS NOT NULL');

        // Phân quyền: Người dùng chỉ thấy đơn mình đã xóa hoặc nếu là admin/tchc (theo logic hiện tại của hệ thống)
        // Ở đây tôi tạm thời để xem đơn mình đã xóa hoặc đơn của nhân viên nếu có quyền
        if ($qlNguoiDungId) {
            $this->db->group_start();
            $this->db->where('ng.deleted_user_id', $qlNguoiDungId);
            $this->db->or_where('ng.created_user_id', $qlNguoiDungId);
            $this->db->group_end();
        }

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('nv.ho_va_ten', $searchValue);
            $this->db->or_like('nv.ma_nhan_vien', $searchValue);
            $this->db->group_end();
        }

        $this->db->order_by('ng.deleted_at', 'DESC');

        // Records Filtered
        $tempDb = clone $this->db;
        $recordsFiltered = $tempDb->count_all_results('', false);

        if ($length != '-1' && $length != null) {
            $this->db->limit($length, $start);
        }

        $query = $this->db->get();
        $data = $query->result_array();

        return [
            'recordsTotal' => $recordsFiltered, // Đơn giản hóa cho trash
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ];
    }
}
