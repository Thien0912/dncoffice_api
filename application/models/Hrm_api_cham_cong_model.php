<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_api_cham_cong_model extends MY_Model
{
    protected $table = 'hrm_api_cham_cong';
    protected $primaryKey = 'id';

    protected $timestamps = false;


    public function __construct()
    {
        parent::__construct();
        $this->load->model('Ql_vai_tro_model');
    }

    private function buildPermissionWhere($qlNguoiDungId, $idNhanVien, $idDonViCongTac)
    {
        $permissions = $this->Ql_vai_tro_model->getUserPermissionKeys($qlNguoiDungId);

        // Cấp 1: Xem tất cả (superadmin)
        if (in_array('ngoaigio.xemtatca', $permissions)) {
            return '1=1';
        }

        // Cấp 2: Xem theo đơn vị (lãnh đạo, văn thư)
        if (in_array('ngoaigio.xemdonvi', $permissions)) {
            $conditions = [];

            // Xem đơn vị của mình (Văn thư)
            if ($idDonViCongTac) {
                $conditions[] = "hrm_nhan_vien.id_don_vi_cong_tac = " . $this->db->escape($idDonViCongTac);
            }

            // Xem đơn vị cấp dưới (Lãnh đạo)
            if ($qlNguoiDungId) {
                $conditions[] = "hrm_nhan_vien.id_don_vi_cong_tac IN (
                    SELECT id_don_vi FROM e_lanh_dao_don_vi 
                    WHERE ql_nguoi_dung_id = " . $this->db->escape($qlNguoiDungId) . " 
                    AND deleted_at IS NULL
                )";
            }

            if (!empty($conditions)) {
                return '(' . implode(' OR ', $conditions) . ')';
            }
        }

        // Cấp 3: Xem cá nhân (nhân viên thường)
        if (!empty($idNhanVien)) {
            return "hrm_nhan_vien.id_nhan_vien = " . $this->db->escape($idNhanVien);
        }

        // Fallback: không xem được gì
        return '1=0';
    }

    // public function getExistingIds($ids)
    // {
    //     if (empty($ids)) return [];

    //     $existing_ids = [];

    //     foreach (array_chunk($ids, 500) as $chunk) {
    //         $result = $this->db->select('id')
    //             ->from('hrm_api_cham_cong')
    //             ->where_in('id', $chunk)
    //             ->get()
    //             ->result_array();

    //         $existing_ids = array_merge($existing_ids, array_column($result, 'id'));
    //     }

    //     return $existing_ids;
    // }

    public function getExistingIds($ids)
    {
        if (empty($ids)) return [];

        $existing_ids = [];

        // Lấy từng khúc 500 để tránh quá tải
        foreach (array_chunk($ids, 500) as $chunk) {
            if (empty($chunk)) continue;

            // Tránh where_in khi mảng quá lớn
            $placeholders = implode(',', array_map('intval', $chunk));

            $sql = "SELECT id FROM hrm_api_cham_cong WHERE id IN ($placeholders)";
            $query = $this->db->query($sql);
            $result = $query->result_array();

            $existing_ids = array_merge($existing_ids, array_column($result, 'id'));
        }

        return $existing_ids;
    }



    // public function getAll($start_date, $end_date)
    // {
    //     $this->db->select('*');
    //     $this->db->from($this->table);
    //     $this->db->where('created_at >=', $start_date);
    //     $this->db->where('created_at <=', $end_date);
    //     $query = $this->db->get();

    //     return $query->result_array();
    // }

    public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array(), $tab = null, $idNhanVien = null, $qlNguoiDungId = null, $idDonViCongTac = null)
    {
        // Build permission where clause
        $permissionWhere = $this->buildPermissionWhere($qlNguoiDungId, $idNhanVien, $idDonViCongTac);

        // Build WHERE conditions for date filters in subquery
        $dateWhere = [];
        if (isset($searchKey['from_date']) && !empty($searchKey['from_date'])) {
            $dateWhere[] = "DATE(hrm_api_cham_cong.punch_time) >= " . $this->db->escape($searchKey['from_date']);
        }
        if (isset($searchKey['to_date']) && !empty($searchKey['to_date'])) {
            $dateWhere[] = "DATE(hrm_api_cham_cong.punch_time) <= " . $this->db->escape($searchKey['to_date']);
        }
        if (isset($searchKey['date']) && !empty($searchKey['date'])) {
            $dateWhere[] = "DATE(hrm_api_cham_cong.punch_time) = " . $this->db->escape($searchKey['date']);
        }
        $dateWhereClause = !empty($dateWhere) ? "WHERE " . implode(" AND ", $dateWhere) : "";

        // Build date filter cho phần UNION (ngày nghỉ phép không có chấm công)
        $leaveDateWhere = [];
        if (isset($searchKey['from_date']) && !empty($searchKey['from_date'])) {
            $leaveDateWhere[] = "npct_sub.ngay_nghi >= " . $this->db->escape($searchKey['from_date']);
        }
        if (isset($searchKey['to_date']) && !empty($searchKey['to_date'])) {
            $leaveDateWhere[] = "npct_sub.ngay_nghi <= " . $this->db->escape($searchKey['to_date']);
        }
        if (isset($searchKey['date']) && !empty($searchKey['date'])) {
            $leaveDateWhere[] = "npct_sub.ngay_nghi = " . $this->db->escape($searchKey['date']);
        }
        $leaveDateWhereClause = !empty($leaveDateWhere) ? "AND " . implode(" AND ", $leaveDateWhere) : "";

        // Build subquery manually (bao gồm cả ngày nghỉ phép được duyệt không có chấm công)
        $subquery = "
            SELECT 
                hrm_api_cham_cong.emp_code,
                DATE(hrm_api_cham_cong.punch_time) as ngay_cham_cong,
                MIN(hrm_api_cham_cong.punch_time) as first_punch,
                MAX(hrm_api_cham_cong.punch_time) as last_punch,
                COUNT(*) as so_lan_cham,
                GROUP_CONCAT(TIME_FORMAT(hrm_api_cham_cong.punch_time, '%H:%i') ORDER BY hrm_api_cham_cong.punch_time SEPARATOR ',') as all_times
            FROM hrm_api_cham_cong
            $dateWhereClause
            GROUP BY hrm_api_cham_cong.emp_code, DATE(hrm_api_cham_cong.punch_time)

            UNION ALL

            SELECT
                hnv_sub.ma_cham_cong AS emp_code,
                npct_sub.ngay_nghi AS ngay_cham_cong,
                NULL AS first_punch,
                NULL AS last_punch,
                0 AS so_lan_cham,
                '' AS all_times
            FROM hrm_nghi_phep_chi_tiet npct_sub
            JOIN hrm_nghi_phep np_sub ON np_sub.id_nghi_phep = npct_sub.id_nghi_phep
                AND np_sub.deleted_at IS NULL
                AND (np_sub.trang_thai_cap_mot = 'Da_duyet' OR np_sub.trang_thai_cap_hai = 'Da_duyet')
            JOIN hrm_nhan_vien hnv_sub ON hnv_sub.id_nhan_vien = np_sub.id_nhan_vien
                AND hnv_sub.ma_cham_cong IS NOT NULL
            WHERE npct_sub.ngay_nghi <= CURDATE()
            AND NOT EXISTS (
                SELECT 1 FROM hrm_api_cham_cong hac_check
                WHERE hac_check.emp_code = hnv_sub.ma_cham_cong
                AND DATE(hac_check.punch_time) = npct_sub.ngay_nghi
            )
            $leaveDateWhereClause
            GROUP BY hnv_sub.ma_cham_cong, npct_sub.ngay_nghi
        ";

        // Build main WHERE conditions
        $mainWhere = ["($permissionWhere)"];
        
        if (!empty($searchValue)) {
            $searchEscaped = $this->db->escape_like_str($searchValue);
            $mainWhere[] = "(
                hrm_nhan_vien.ho_va_ten LIKE '%{$searchEscaped}%' OR
                hrm_nhan_vien.ma_nhan_vien LIKE '%{$searchEscaped}%' OR
                grouped.emp_code LIKE '%{$searchEscaped}%' OR
                e_don_vi.ten_don_vi LIKE '%{$searchEscaped}%'
            )";
        }
        
        if (isset($searchKey['id_don_vi']) && !empty($searchKey['id_don_vi'])) {
            $donViIds = array_map('intval', explode(',', $searchKey['id_don_vi']));
            if (!empty($donViIds)) {
                $mainWhere[] = "hrm_nhan_vien.id_don_vi_cong_tac IN (" . implode(',', $donViIds) . ")";
            }
        }
        
        if (isset($searchKey['id_nhan_vien']) && !empty($searchKey['id_nhan_vien'])) {
            $nhanVienIds = array_map('intval', explode(',', $searchKey['id_nhan_vien']));
            if (!empty($nhanVienIds)) {
                $mainWhere[] = "hrm_nhan_vien.id_nhan_vien IN (" . implode(',', $nhanVienIds) . ")";
            }
        }
        
        $mainWhereClause = "WHERE " . implode(" AND ", $mainWhere);

        // Build ORDER BY clause
        $orderByClause = "ORDER BY grouped.ngay_cham_cong DESC, grouped.first_punch DESC";
        if (!empty($orderBy) && isset($orderBy['order'][0])) {
            $order = $orderBy['order'][0];
            $columns = $orderBy['columns'];
            $orderColumnIndex = $order['column'];
            $orderDir = strtoupper($order['dir']);
            
            if (isset($columns[$orderColumnIndex]['data'])) {
                $orderColumn = $columns[$orderColumnIndex]['data'];
                $orderByClause = "ORDER BY `{$orderColumn}` {$orderDir}";
            }
        }

        // Build LIMIT clause
        $limitClause = "";
        if ($length !== null && $length != -1) {
            $limitClause = "LIMIT " . intval($start) . ", " . intval($length);
        }

        // Build full query with ca làm việc, ngoai gio and nghi phep (calculations moved to PHP)
        $mainQuery = "
            SELECT 
                grouped.*,
                hrm_nhan_vien.ho_va_ten,
                hrm_nhan_vien.ma_nhan_vien,
                hrm_nhan_vien.id_nhan_vien,
                hrm_nhan_vien.id_don_vi_cong_tac,
                e_don_vi.ten_don_vi,
                e_don_vi.ma_don_vi,
                ca.ca_lam_viec,
                ca.check_in as ca_check_in,
                ca.bat_dau_check_in as ca_bat_dau_check_in,
                ca.ket_thuc_check_in as ca_ket_thuc_check_in,
                ca.check_out as ca_check_out,
                ca.bat_dau_check_out as ca_bat_dau_check_out,
                ca.ket_thuc_check_out as ca_ket_thuc_check_out,
                ng.id_ngoai_gio,
                ng.gio_bat_dau as ot_gio_bat_dau,
                ng.gio_ket_thuc as ot_gio_ket_thuc,
                ng.so_gio as ot_so_gio_dang_ky,
                ng.trang_thai_tong as ot_trang_thai,
                GROUP_CONCAT(DISTINCT npct.buoi_nghi ORDER BY npct.buoi_nghi SEPARATOR ',') as buoi_nghi_phep,
                MIN(cong_viec.ngay_lam_chinh_thuc) as ngay_lam_chinh_thuc
            FROM ($subquery) as grouped
            INNER JOIN hrm_nhan_vien ON hrm_nhan_vien.ma_cham_cong = grouped.emp_code
            LEFT JOIN e_don_vi ON e_don_vi.id_don_vi = hrm_nhan_vien.id_don_vi_cong_tac
            LEFT JOIN hrm_ca_lam_viec ca ON ca.id = hrm_nhan_vien.id_ca_lam_viec
            LEFT JOIN hrm_ngoai_gio ng ON ng.id_nhan_vien = hrm_nhan_vien.id_nhan_vien 
                AND ng.ngay_dang_ky = grouped.ngay_cham_cong
                AND ng.deleted_at IS NULL
            LEFT JOIN hrm_nghi_phep np ON np.id_nhan_vien = hrm_nhan_vien.id_nhan_vien
                AND np.deleted_at IS NULL
                AND (np.trang_thai_cap_mot = 'Da_duyet' OR np.trang_thai_cap_hai = 'Da_duyet')
            LEFT JOIN hrm_nghi_phep_chi_tiet npct ON npct.id_nghi_phep = np.id_nghi_phep
                AND npct.ngay_nghi = grouped.ngay_cham_cong
            LEFT JOIN hrm_nhan_vien_cong_viec cong_viec ON cong_viec.id_nhan_vien = hrm_nhan_vien.id_nhan_vien
                AND cong_viec.deleted_at IS NULL
            $mainWhereClause
            GROUP BY grouped.emp_code, grouped.ngay_cham_cong
            $orderByClause
            $limitClause
        ";

        // Count query
        $countQuery = "
            SELECT COUNT(DISTINCT CONCAT(grouped.emp_code, '-', grouped.ngay_cham_cong)) as total
            FROM ($subquery) as grouped
            INNER JOIN hrm_nhan_vien ON hrm_nhan_vien.ma_cham_cong = grouped.emp_code
            LEFT JOIN e_don_vi ON e_don_vi.id_don_vi = hrm_nhan_vien.id_don_vi_cong_tac
            $mainWhereClause
        ";

        // Execute queries
        $recordsTotal = $this->db->query($countQuery)->row()->total;
        $data = $this->db->query($mainQuery)->result_array();
        
        // Calculate metrics for each record based on punch times and shift
        foreach ($data as &$record) {
            // Parse punch times
            $punchTimes = !empty($record['all_times']) ? explode(',', $record['all_times']) : [];
            $numPunches = count($punchTimes);
            
            // Parse buổi nghỉ phép
            $buoiNghiPhep = !empty($record['buoi_nghi_phep']) ? explode(',', $record['buoi_nghi_phep']) : [];
            $nghiSang = in_array('Sang', $buoiNghiPhep);
            $nghiChieu = in_array('Chieu', $buoiNghiPhep);
            
            // Thêm thông tin nghỉ phép vào response
            $record['nghi_phep_sang'] = $nghiSang;
            $record['nghi_phep_chieu'] = $nghiChieu;
            
            // Initialize values
            $record['gio_vao'] = $numPunches > 0 ? $punchTimes[0] : '';
            $record['gio_ra'] = $numPunches > 0 ? end($punchTimes) : '';
            $record['gio_di_tre'] = 0;
            $record['gio_ve_som'] = 0;
            $record['gio_di_tre_sang'] = 0;
            $record['gio_di_tre_chieu'] = 0;
            $record['gio_ve_som_sang'] = 0;
            $record['gio_ve_som_chieu'] = 0;
            $record['tong_gio_lam'] = 0;
            $record['gio_lam_sang'] = 0;
            $record['gio_lam_chieu'] = 0;
            $record['gio_ot'] = 0;
            
            // Get shift times
            $ca_check_in = $record['ca_check_in'] ?? null;
            $ca_ket_thuc_check_in = $record['ca_ket_thuc_check_in'] ?? null;
            $ca_bat_dau_check_out = $record['ca_bat_dau_check_out'] ?? null;
            $ca_check_out = $record['ca_check_out'] ?? null;

            // Tách các lượt chấm công thông minh dựa trên ca làm việc
            $keyPunches = $this->extractKeyPunches($punchTimes, $ca_ket_thuc_check_in, $ca_bat_dau_check_out);
            $record['punch_1'] = $keyPunches['punch_1'];
            $record['punch_2'] = $keyPunches['punch_2'];
            $record['punch_3'] = $keyPunches['punch_3'];
            $record['punch_4'] = $keyPunches['punch_4'];

            $isCrossShift = false; // Flag cho trường hợp làm xuyên ca (chỉ 2 punch nhưng bao trùm cả ngày)
            
            // Kiểm tra xem có phải ngày Chủ nhật / Thứ 7 không
            $ngayChamCong = $record['ngay_cham_cong'] ?? null;
            $isSunday = false;
            $isSaturday = false;
            $dayOfWeek = null;
            if ($ngayChamCong) {
                $dayOfWeek = (int) date('N', strtotime($ngayChamCong));
                $isSunday   = ($dayOfWeek === 7);
                $isSaturday = ($dayOfWeek === 6);
            }

            // Kiểm tra thâm niên >= 2 năm dựa trên ngay_lam_chinh_thuc
            $ngayLamChinhThuc = $record['ngay_lam_chinh_thuc'] ?? null;
            $isHighSeniority = false;
            if ($ngayLamChinhThuc) {
                try {
                    $startDate = new DateTime($ngayLamChinhThuc);
                    $now       = new DateTime();
                    $isHighSeniority = ($startDate->diff($now)->y >= 2);
                } catch (Exception $e) {
                    $isHighSeniority = false;
                }
            }

            // Thứ 7 + thâm niên >= 2 năm → chỉ cần chấm sáng (2 lần)
            $isSaturdaySeniority = $isSaturday && $isHighSeniority;
            $record['is_saturday_seniority'] = $isSaturdaySeniority;
            $record['is_high_seniority']     = $isHighSeniority;

            // Nếu là Chủ nhật: tính đơn giản từ In đầu đến Out cuối, không chia ca
            if ($isSunday && $numPunches >= 2) {
                $firstTime = $punchTimes[0];
                $lastTime = end($punchTimes);
                
                // Tính tổng giờ làm thực tế (không trừ giờ nghỉ trưa, làm nhiêu tính nhiêu)
                $totalMinutes = $this->calculateMinuteDiff($firstTime, $lastTime);
                $hours = $totalMinutes / 60.0;
                
                $record['tong_gio_lam'] = $hours;
                $record['gio_lam_sang'] = 0;  // Không chia ca sáng/chiều
                $record['gio_lam_chieu'] = 0;
                $record['gio_vao_sang_hieu_luc'] = $firstTime;
                $record['gio_ra_sang_hieu_luc'] = null;
                $record['gio_vao_chieu_hieu_luc'] = null;
                $record['gio_ra_chieu_hieu_luc'] = $lastTime;
                $record['gio_di_tre'] = 0;  // Chủ nhật không tính đi trễ/về sớm
                $record['gio_ve_som'] = 0;
                $record['gio_ot'] = 0;
                $record['is_sunday'] = true;  // Đánh dấu để FE biết
            }
            // Thứ 7 + thâm niên >= 2 năm: chỉ tính buổi sáng với 2 lần chấm
            elseif ($isSaturdaySeniority && $numPunches >= 2) {
                $punch1 = $punchTimes[0];   // Vào sáng (lần đầu tiên)
                $punch2 = end($punchTimes); // Ra sáng (lần cuối - T7 chỉ làm sáng)

                // Tính đi trễ / về sớm sáng so với ca (nếu có ca)
                if ($ca_check_in && $punch1 > $ca_check_in) {
                    $late = $this->calculateMinuteDiff($ca_check_in, $punch1);
                    $record['gio_di_tre']      += $late;
                    $record['gio_di_tre_sang'] = $late;
                }
                if ($ca_ket_thuc_check_in && $punch2 < $ca_ket_thuc_check_in) {
                    $early = $this->calculateMinuteDiff($punch2, $ca_ket_thuc_check_in);
                    $record['gio_ve_som']      += $early;
                    $record['gio_ve_som_sang'] = $early;
                }

                // Giờ làm sáng
                $sangRa = ($ca_ket_thuc_check_in && $punch2 > $ca_ket_thuc_check_in)
                    ? $ca_ket_thuc_check_in
                    : $punch2;
                $record['gio_lam_sang']          = max(0, $this->calculateMinuteDiff($punch1, $sangRa) / 60.0);
                $record['gio_lam_chieu']         = 0; // Không làm chiều
                $record['tong_gio_lam']          = $record['gio_lam_sang'];
                $record['gio_vao_sang_hieu_luc'] = $punch1;
                $record['gio_ra_sang_hieu_luc']  = $sangRa;
                $record['gio_vao_chieu_hieu_luc'] = null;
                $record['gio_ra_chieu_hieu_luc']  = null;
                $record['gio_ot']    = 0;
                $record['is_sunday'] = false;
            }
            // Process 4 punches per day (không phải Chủ nhật, không phải T7 thâm niên)
            elseif ($numPunches >= 4 && $ca_check_in && $ca_ket_thuc_check_in && $ca_bat_dau_check_out && $ca_check_out) {
                $punch1 = $record['punch_1']; // Vào sáng
                $punch2 = $record['punch_2']; // Ra sáng (điểm chia ca, gần ca_ket_thuc_check_in)
                $punch3 = $record['punch_3']; // Vào chiều
                $punch4 = $record['punch_4']; // Ra chiều (lần cuối)
                
                // Lần 1: Vào sáng - so sánh với check_in (KHÔNG tính nếu nghỉ sáng)
                if (!$nghiSang && $punch1 > $ca_check_in) {
                    $late = $this->calculateMinuteDiff($ca_check_in, $punch1);
                    $record['gio_di_tre'] += $late;
                    $record['gio_di_tre_sang'] = $late;
                }
                // Lần 2: Ra trưa - so sánh với ket_thuc_check_in (KHÔNG tính nếu nghỉ sáng)
                if (!$nghiSang && $punch2 < $ca_ket_thuc_check_in) {
                    $early = $this->calculateMinuteDiff($punch2, $ca_ket_thuc_check_in);
                    $record['gio_ve_som'] += $early;
                    $record['gio_ve_som_sang'] = $early;
                }
                // Lần 3: Vào chiều - so sánh với bat_dau_check_out (KHÔNG tính nếu nghỉ chiều)
                if (!$nghiChieu && $punch3 > $ca_bat_dau_check_out) {
                    $late = $this->calculateMinuteDiff($ca_bat_dau_check_out, $punch3);
                    $record['gio_di_tre'] += $late;
                    $record['gio_di_tre_chieu'] = $late;
                }
                // Lần 4: Ra chiều - so sánh với check_out (KHÔNG tính nếu nghỉ chiều)
                if (!$nghiChieu) {
                    if ($punch4 < $ca_check_out) {
                        $early = $this->calculateMinuteDiff($punch4, $ca_check_out);
                        $record['gio_ve_som'] += $early;
                        $record['gio_ve_som_chieu'] = $early;
                    } elseif ($punch4 > $ca_check_out) {
                        // OT nếu chấm sau giờ tan làm
                        $record['gio_ot'] = $this->calculateMinuteDiff($ca_check_out, $punch4);
                    }
                }
                
                // Tính giờ làm buổi sáng (từ punch1 đến punch2) - KHÔNG tính nếu nghỉ sáng
                if (!$nghiSang) {
                    // Nếu chấm sau ket_thuc_check_in (11:30), vẫn chỉ tính đến ket_thuc_check_in (đúng quy định)
                    // Nếu chấm trước, tính từ thời gian thực tế (về sớm)
                    $sangRa = ($punch2 > $ca_ket_thuc_check_in) ? $ca_ket_thuc_check_in : $punch2;
                    $record['gio_lam_sang'] = max(0, $this->calculateMinuteDiff($punch1, $sangRa) / 60.0);
                    $record['gio_vao_sang_hieu_luc'] = $punch1;
                    $record['gio_ra_sang_hieu_luc'] = $sangRa;
                } else {
                    $record['gio_vao_sang_hieu_luc'] = null;
                    $record['gio_ra_sang_hieu_luc'] = null;
                }
                
                // Tính giờ làm buổi chiều (từ punch3 đến punch4) - KHÔNG tính nếu nghỉ chiều
                if (!$nghiChieu) {
                    // Nếu chấm sớm hơn bat_dau_check_out (11:30-13:00), vẫn lấy bat_dau_check_out (đúng quy định)
                    // Nếu chấm sau bat_dau_check_out, tính từ thời gian thực tế (đã bị tính đi trễ)
                    $chieuVao = ($punch3 < $ca_bat_dau_check_out) ? $ca_bat_dau_check_out : $punch3;
                    $record['gio_lam_chieu'] = max(0, $this->calculateMinuteDiff($chieuVao, $punch4) / 60.0);
                    $record['gio_vao_chieu_hieu_luc'] = $chieuVao;
                    $record['gio_ra_chieu_hieu_luc'] = $punch4;
                } else {
                    $record['gio_vao_chieu_hieu_luc'] = null;
                    $record['gio_ra_chieu_hieu_luc'] = null;
                }
                
                // Tổng giờ làm
                $record['tong_gio_lam'] = $record['gio_lam_sang'] + $record['gio_lam_chieu'];
                $record['is_sunday'] = false;
            }
            // Fallback: nếu không đủ 4 lần chấm, tính đơn giản
            elseif ($numPunches >= 1) {
                $firstTime = $punchTimes[0];
                $lastTime = end($punchTimes);
                
                // Tính tổng giờ làm, trừ 1.5h nghỉ trưa nếu làm qua trưa
                $totalMinutes = $this->calculateMinuteDiff($firstTime, $lastTime);
                $hours = $totalMinutes / 60.0;
                
                // Ước tính giờ làm từng ca
                $record['gio_vao_sang_hieu_luc'] = $nghiSang ? null : $firstTime;
                $record['gio_vao_chieu_hieu_luc'] = null;
                $record['gio_ra_sang_hieu_luc'] = null;
                $record['gio_ra_chieu_hieu_luc'] = $nghiChieu ? null : $lastTime;
                
                // Kiểm tra nghỉ phép trước
                if ($nghiSang && !$nghiChieu) {
                    // Nghỉ sáng, chỉ làm chiều
                    $record['gio_lam_sang'] = 0;
                    $record['gio_lam_chieu'] = $numPunches >= 2 ? $hours : 0;  // Chỉ tính nếu có checkout
                    $record['gio_vao_chieu_hieu_luc'] = $firstTime;
                    $record['gio_ra_chieu_hieu_luc'] = $numPunches >= 2 ? $lastTime : null;  // null nếu chưa checkout
                } elseif ($nghiChieu && !$nghiSang) {
                    // Nghỉ chiều, chỉ làm sáng
                    $record['gio_lam_sang'] = $numPunches >= 2 ? $hours : 0;
                    $record['gio_lam_chieu'] = 0;
                    $record['gio_vao_sang_hieu_luc'] = $firstTime;
                    $record['gio_ra_sang_hieu_luc'] = $numPunches >= 2 ? $lastTime : null;
                } elseif ($nghiSang && $nghiChieu) {
                    // Nghỉ cả ngày (lý thuyết không nên có chấm công)
                    $record['gio_lam_sang'] = 0;
                    $record['gio_lam_chieu'] = 0;
                    $record['gio_vao_sang_hieu_luc'] = null;
                    $record['gio_ra_sang_hieu_luc'] = null;
                    $record['gio_vao_chieu_hieu_luc'] = null;
                    $record['gio_ra_chieu_hieu_luc'] = null;
                } else {
                    // Không nghỉ phép: Kiểm tra có qua giờ nghỉ trưa không (chỉ nếu có đủ 2 punch)
                    if ($numPunches >= 2) {
                        // Dùng giờ ca thực tế (fallback về 11:30 / 13:00 nếu chưa cấu hình ca)
                        $sangKetThuc  = !empty($ca_ket_thuc_check_in)  ? $ca_ket_thuc_check_in  : '11:30:00';
                        $chieuBatDau  = !empty($ca_bat_dau_check_out)  ? $ca_bat_dau_check_out  : '13:00:00';
                        $morningCutoff = !empty($ca_ket_thuc_check_in) ? $ca_ket_thuc_check_in  : '12:00:00';

                        // "Làm xuyên ca": vào trước kết thúc sáng & ra sau bắt đầu chiều
                        if ($firstTime < $sangKetThuc && $lastTime > $chieuBatDau) {
                            $isCrossShift = true; // Đánh dấu là làm xuyên ca
                            // Quên chấm giữa ngày → áp ca mặc định
                            // Sáng: firstTime → ca_ket_thuc_check_in
                            // Chiều: ca_bat_dau_check_out → lastTime
                            $record['gio_ra_sang_hieu_luc']   = $sangKetThuc;
                            $record['gio_vao_chieu_hieu_luc'] = $chieuBatDau;

                            $record['gio_lam_sang']  = max(0, $this->calculateMinuteDiff($firstTime, $sangKetThuc) / 60.0);
                            $record['gio_lam_chieu'] = max(0, $this->calculateMinuteDiff($chieuBatDau, $lastTime)  / 60.0);

                            // Tính đi trễ / về sớm nếu có ca đầy đủ
                            if ($ca_check_in && $firstTime > $ca_check_in) {
                                $late = $this->calculateMinuteDiff($ca_check_in, $firstTime);
                                $record['gio_di_tre']      += $late;
                                $record['gio_di_tre_sang'] = $late;
                            }
                            if ($ca_check_out && $lastTime < $ca_check_out) {
                                $early = $this->calculateMinuteDiff($lastTime, $ca_check_out);
                                $record['gio_ve_som']       += $early;
                                $record['gio_ve_som_chieu'] = $early;
                            }

                            // Tổng giờ = sáng + chiều (không trừ 1.5h nghỉ trưa, đã chia ca rõ ràng)
                            $hours = $record['gio_lam_sang'] + $record['gio_lam_chieu'];
                        } else {
                            // Làm 1 ca duy nhất
                            if ($firstTime <= $morningCutoff) {
                                // Ca sáng
                                $record['gio_lam_sang']       = $hours;
                                $record['gio_lam_chieu']      = 0;
                                $record['gio_ra_sang_hieu_luc'] = $lastTime;

                                if ($ca_check_in && $firstTime > $ca_check_in) {
                                    $late = $this->calculateMinuteDiff($ca_check_in, $firstTime);
                                    $record['gio_di_tre']      += $late;
                                    $record['gio_di_tre_sang'] = $late;
                                }
                            } else {
                                // Ca chiều
                                $record['gio_lam_sang'] = 0;
                                $record['gio_lam_chieu'] = $hours;
                                $record['gio_vao_chieu_hieu_luc'] = $firstTime;
                            }
                        }
                    } else {
                        // Chỉ có 1 lần chấm (chưa checkout) → không tính giờ làm
                        $record['gio_lam_sang'] = 0;
                        $record['gio_lam_chieu'] = 0;
                        $hours = 0;
                    }
                }
                
                $record['tong_gio_lam'] = max(0, $hours);
                $record['is_sunday'] = false;
            }
            else {
                // Không đủ dữ liệu để tính giờ làm
                $record['is_sunday'] = false;
            }
            
            // Kiểm tra trạng thái chấm công
            if ($numPunches == 0) {
                // Ngày không có dữ liệu chấm công - kiểm tra đơn nghỉ phép hợp lệ
                if (!empty($buoiNghiPhep)) {
                    // Có đơn phép được duyệt đúng ngày đúng buổi → nghỉ phép
                    $record['trang_thai_cham_cong'] = 'nghi_phep';
                } else {
                    // Không chấm công, không có phép → vắng mặt
                    $record['trang_thai_cham_cong'] = 'vang_mat';
                }
            } else {
                $record['trang_thai_cham_cong'] = 'day_du'; // Mặc định đầy đủ

                // Kiểm tra thiếu chấm công (bỏ qua Chủ nhật và Thứ 7 thâm niên đã đủ punch)
                if (!$isSunday) {
                    $p1 = $record['punch_1'];
                    $p2 = $record['punch_2'];
                    $p3 = $record['punch_3'];
                    $p4 = $record['punch_4'];

                    // Xác định đây có phải ngày hôm nay không và giờ hiện tại
                    $isToday     = ($ngayChamCong === date('Y-m-d'));
                    $currentTime = date('H:i:s');

                    // Chỉ flag "thiếu" khi:
                    //   - Là ngày cũ (luôn hiện), HOẶC
                    //   - Là hôm nay VÀ đã qua giờ kết thúc ca tương ứng

                    // Hàm kiểm tra: đã qua mốc thời gian ca chưa?
                    // Ngày cũ → luôn "đã qua". Hôm nay → so sánh với giờ hiện tại.
                    $passedCaTime = function($caTime) use ($isToday, $currentTime) {
                        if (!$isToday) return true;          // Ngày cũ → luôn tính là đã qua
                        if (empty($caTime)) return true;     // Không có ca → luôn tính
                        return $currentTime > $caTime;
                    };

                    if ($isSaturdaySeniority) {
                        // Thứ 7 thâm niên: chỉ cần sáng
                        // Thiếu Ra sáng → chỉ flag sau ca_ket_thuc_check_in
                        if (!empty($p1) && empty($p2) && $passedCaTime($ca_ket_thuc_check_in)) {
                            $record['trang_thai_cham_cong'] = 'thieu_cham_cong_sang';
                        }
                    } else {
                        // Phát hiện: p1 có phải giờ chiều không?
                        // Nếu punch đầu tiên > ca_ket_thuc_check_in → người này chỉ làm chiều
                        $morningBoundary = !empty($ca_ket_thuc_check_in) ? $ca_ket_thuc_check_in : '12:00:00';
                        $firstPunchIsAfternoon = !empty($p1) && ($p1 > $morningBoundary);

                        // Có p3 nhưng thiếu p4 (Ra chiều) → chỉ flag sau ca_check_out
                        // Ngoại trừ trường hợp xuyên ca: p3 thực ra là "Ra chiều" (không phải "Vào chiều")
                        if (!empty($p3) && empty($p4) && !$isCrossShift && $passedCaTime($ca_check_out)) {
                            $record['trang_thai_cham_cong'] = 'thieu_cham_cong_chieu';
                        }
                        // Có p1 nhưng thiếu p2 (Ra sáng) và chưa có p3
                        elseif (!empty($p1) && empty($p2) && empty($p3)) {
                            if ($firstPunchIsAfternoon) {
                                // p1 là giờ chiều → người này vào chiều, thiếu buổi sáng
                                if (!$nghiSang && $passedCaTime($ca_ket_thuc_check_in)) {
                                    $record['trang_thai_cham_cong'] = 'thieu_cham_cong_sang';
                                }
                            } elseif ($passedCaTime($ca_ket_thuc_check_in)) {
                                // p1 là giờ sáng, chưa có ra sáng
                                $record['trang_thai_cham_cong'] = 'thieu_cham_cong_sang';
                            }
                        }
                        // Có p1+p2 nhưng chưa có p3
                        elseif (!empty($p1) && !empty($p2) && empty($p3)) {
                            if ($isCrossShift) {
                                // Nếu là xuyên ca (p1 sáng, p2 chiều) -> coi như đầy đủ
                                $record['trang_thai_cham_cong'] = 'day_du';
                            } elseif ($firstPunchIsAfternoon) {
                                // p1 là giờ chiều → cả p1+p2 đều là chiều → thiếu buổi sáng
                                if (!$nghiSang && $passedCaTime($ca_ket_thuc_check_in)) {
                                    $record['trang_thai_cham_cong'] = 'thieu_cham_cong_sang';
                                }
                            } elseif (!$nghiChieu && $passedCaTime($ca_bat_dau_check_out)) {
                                // p1 là giờ sáng, có đủ sáng nhưng thiếu chiều
                                $record['trang_thai_cham_cong'] = 'thieu_cham_cong_chieu';
                            }
                        }
                    }
                }
            }
            
            // Tính nợ OT (giờ)
            $record['no_ot'] = ($record['gio_di_tre'] + $record['gio_ve_som']) / 60.0;
        }

        return [
            'data' => $data,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal
        ];
    }
    
    /**
     * Tìm 4 thời điểm chấm công "then chốt" từ N lần chấm.
     *
     * Khi có > 4 lần chấm trong ngày (ví dụ bấm máy nhiều lần buổi sáng),
     * thuật toán tìm điểm chia sáng/chiều dựa trên ca_ket_thuc_check_in:
     *   - "Ra sáng" (punch_2) = lần chấm GẦN NHẤT với ca_ket_thuc_check_in
     *     trong khoảng trước ca_ket_thuc_check_in + grace (15 phút).
     *   - Mọi lần chấm từ đầu đến punch_2 = buổi sáng.
     *   - Mọi lần chấm sau punch_2 = buổi chiều.
     *
     * Ví dụ: 7:29, 9:15, 11:31, 11:33, 17:04 với ca_ket_thuc_check_in=11:30:
     *   Khoảng cách: 241, 135, 1, 3 phút (17:04 bị loại vì > 11:45)
     *   → punch_2 = 11:31 (gần nhất), punch_3 = 11:33, punch_4 = 17:04
     */
    private function extractKeyPunches(array $punchTimes, $caKetThucCheckIn = null, $caBatDauCheckOut = null)
    {
        $n = count($punchTimes);
        if ($n === 0) {
            return ['punch_1' => null, 'punch_2' => null, 'punch_3' => null, 'punch_4' => null];
        }

        // Với <= 3 lần chấm: giữ nguyên thứ tự index
        if ($n <= 3) {
            return [
                'punch_1' => $punchTimes[0] ?? null,
                'punch_2' => $punchTimes[1] ?? null,
                'punch_3' => $punchTimes[2] ?? null,
                'punch_4' => null,
            ];
        }

        // Đúng 4 lần chấm: map tuần tự 1-1 (không cần thuật toán "gần nhất")
        // Ví dụ: 07:22(vào sáng), 11:26(ra trưa), 11:30(vào chiều), 17:01(ra chiều)
        // Không thể dùng "tìm gần ca_ket_thuc_check_in" vì 11:30 sẽ bị gộp vào sáng
        if ($n === 4) {
            return [
                'punch_1' => $punchTimes[0],
                'punch_2' => $punchTimes[1],
                'punch_3' => $punchTimes[2],
                'punch_4' => $punchTimes[3],
            ];
        }

        // Với > 4 lần chấm: tìm "Ra sáng" là lần chấm gần ca_ket_thuc_check_in nhất
        // (xử lý trường hợp bấm máy nhiều lần ở 1 buổi)
        $splitTarget   = !empty($caKetThucCheckIn) ? $caKetThucCheckIn : '11:30:00';
        $splitLimit    = !empty($caBatDauCheckOut) ? $caBatDauCheckOut : '13:00:00';

        $splitTargetTs = strtotime('1970-01-01 ' . $splitTarget);
        $splitLimitTs  = strtotime('1970-01-01 ' . $splitLimit);

        $splitIdx = 0;
        $minDist  = PHP_INT_MAX;

        // Duyệt đến n-2 để luôn còn ít nhất 1 lần cho buổi chiều
        for ($i = 0; $i < $n - 1; $i++) {
            $ts = strtotime('1970-01-01 ' . $punchTimes[$i]);

            // Nếu lần chấm này đã thuộc hẳn về giờ bắt đầu ca chiều → dừng tìm "Ra sáng"
            if ($ts >= $splitLimitTs) {
                break;
            }

            $dist = abs($ts - $splitTargetTs);
            if ($dist < $minDist) {
                $minDist  = $dist;
                $splitIdx = $i;
            }
        }

        // morning = [0 .. splitIdx], afternoon = [splitIdx+1 .. n-1]
        $morningPunches   = array_slice($punchTimes, 0, $splitIdx + 1);
        $afternoonPunches = array_slice($punchTimes, $splitIdx + 1);

        return [
            'punch_1' => $morningPunches[0]                                    ?? null,
            'punch_2' => end($morningPunches) ?: null,
            'punch_3' => $afternoonPunches[0]                                  ?? null,
            'punch_4' => count($afternoonPunches) > 0 ? end($afternoonPunches) : null,
        ];
    }

    /**
     * Calculate minute difference between two time strings (HH:MM or HH:MM:SS)
     */
    private function calculateMinuteDiff($time1, $time2)
    {
        $t1 = strtotime("1970-01-01 " . $time1);
        $t2 = strtotime("1970-01-01 " . $time2);
        return abs(($t2 - $t1) / 60);
    }

}
