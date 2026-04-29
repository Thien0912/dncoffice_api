<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Hrm_nhan_vien_sync_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Xuất dữ liệu phục vụ đồng bộ Google Sheet
     * Chỉ lấy các trường: Mã NV, Mã chấm công, Họ tên, Email, Giới tính, CCCD, Ngày làm chính thức
     */
    public function get_data_for_sync()
    {
        $this->db->select('
            nv.ma_nhan_vien,
            nv.ma_cham_cong,
            nv.ho_va_ten,
            nv.email,
            nv.email_ca_nhan,
            IFNULL(e.ten_don_vi, "") AS don_vi,
            CASE 
                WHEN nv.gioi_tinh = "1" THEN "Nam"
                WHEN nv.gioi_tinh = "2" THEN "Nữ"
                ELSE ""
            END AS gioi_tinh,
            nv.so_dien_thoai,
            DATE_FORMAT(nvcv.ngay_lam_chinh_thuc, "%d/%m/%Y") as ngay_lam_chinh_thuc,
            nv.cccd_so,
            DATE_FORMAT(nv.cccd_ngay_cap, "%d/%m/%Y") as cccd_ngay_cap,
            DATE_FORMAT(nv.cccd_ngay_het_han, "%d/%m/%Y") as cccd_ngay_het_han,
            nv.cccd_noi_cap,
            nv.nganh_dt,
            nv.trinh_do_vh,
            nv.noi_dt,
            nv.khoa_dt,
            nv.nam_tn,
            nv.xep_loai_tn
        ', false);
        $this->db->from('hrm_nhan_vien nv');
        $this->db->join('hrm_nhan_vien_cong_viec nvcv', 'nvcv.id_nhan_vien = nv.id_nhan_vien', 'left');
        $this->db->join('e_don_vi e', 'e.id_don_vi = nv.id_don_vi_cong_tac', 'left');
        $this->db->where('nv.deleted_at IS NULL');
        $this->db->order_by('e.ten_don_vi', 'ASC');
        $this->db->order_by('nvcv.ngay_lam_chinh_thuc', 'ASC');
        $this->db->order_by('nv.ma_nhan_vien', 'ASC');

        $result = $this->db->get()->result_array();

        // Xử lý tiền tố 0 cho CCCD và Mã chấm công nếu cần (đảm bảo là chuỗi)
        foreach ($result as &$row) {
            if (!empty($row['cccd_so'])) {
                $row['cccd_so'] = "'" . ltrim($row['cccd_so'], "'"); // Đảm bảo luôn có 1 dấu nháy đơn
            }
            if (!empty($row['ma_cham_cong'])) {
                $row['ma_cham_cong'] = "'" . ltrim($row['ma_cham_cong'], "'");
            }
            if (!empty($row['ma_nhan_vien'])) {
                $row['ma_nhan_vien'] = "'" . ltrim($row['ma_nhan_vien'], "'");
            }
            if (!empty($row['so_dien_thoai'])) {
                $row['so_dien_thoai'] = "'" . ltrim($row['so_dien_thoai'], "'");
            }
        }

        return $result;
    }

    /**
     * Cập nhật dữ liệu từ Sheet về DB
     */
    public function update_from_sync($data, $userId = 0)
    {
        $countUpdate = 0;
        $countInsert = 0;
        $now = date('Y-m-d H:i:s');

        // Load mappings để tối ưu
        $units = $this->db->select('id_don_vi, ten_don_vi')->get('e_don_vi')->result_array();
        $unitMap = array_column($units, 'id_don_vi', 'ten_don_vi');

        foreach ($data as $row) {
            // Chuẩn hóa về chữ thường để map key
            $row = array_change_key_case($row, CASE_LOWER);
            
            $ma_nhan_vien = isset($row['ma_nhan_vien']) ? ltrim($row['ma_nhan_vien'], "'") : '';
            if (empty($ma_nhan_vien)) continue;

            // Thu thập các trường chung
            $ma_cham_cong = isset($row['ma_cham_cong']) ? ltrim($row['ma_cham_cong'], "'") : '';
            $ho_va_ten = isset($row['ho_va_ten']) ? trim($row['ho_va_ten']) : '';
            $email = isset($row['email']) ? trim($row['email']) : '';
            $email_ca_nhan = isset($row['email_ca_nhan']) ? trim($row['email_ca_nhan']) : '';
            $so_dien_thoai = isset($row['so_dien_thoai']) ? ltrim(trim($row['so_dien_thoai']), "'") : '';
            
            $gioi_tinh = '';
            if (isset($row['gioi_tinh'])) {
                $gtText = mb_strtolower(trim($row['gioi_tinh']), 'UTF-8');
                if ($gtText == 'nam') $gioi_tinh = '1';
                else if ($gtText == 'nữ') $gioi_tinh = '2';
            }

            $id_don_vi = null;
            if (isset($row['don_vi']) && isset($unitMap[trim($row['don_vi'])])) {
                $id_don_vi = $unitMap[trim($row['don_vi'])];
            }

            // Dữ liệu CCCD
            $cccd_so = isset($row['cccd_so']) ? ltrim($row['cccd_so'], "'") : '';
            $cccd_ngay_cap = isset($row['cccd_ngay_cap']) ? $this->format_date_db($row['cccd_ngay_cap']) : null;
            $cccd_ngay_het_han = isset($row['cccd_ngay_het_han']) ? $this->format_date_db($row['cccd_ngay_het_han']) : null;
            $cccd_noi_cap = isset($row['cccd_noi_cap']) ? trim($row['cccd_noi_cap']) : '';

            // Dữ liệu Đào tạo
            $nganh_dt = isset($row['nganh_dt']) ? trim($row['nganh_dt']) : '';
            $trinh_do_vh = isset($row['trinh_do_vh']) ? trim($row['trinh_do_vh']) : '';
            $noi_dt = isset($row['noi_dt']) ? trim($row['noi_dt']) : '';
            $khoa_dt = isset($row['khoa_dt']) ? trim($row['khoa_dt']) : '';
            $nam_tn = isset($row['nam_tn']) ? trim($row['nam_tn']) : null;
            $xep_loai_tn = isset($row['xep_loai_tn']) ? trim($row['xep_loai_tn']) : '';

            $ngay_lam_chinh_thuc = isset($row['ngay_lam_chinh_thuc']) ? $this->format_date_db($row['ngay_lam_chinh_thuc']) : null;

            // Kiểm tra tồn tại
            $this->db->where('ma_nhan_vien', $ma_nhan_vien);
            $current = $this->db->get('hrm_nhan_vien')->row_array();

            if ($current) {
                // UPDATE
                $updateData = [];
                if (!empty($ma_cham_cong) && $current['ma_cham_cong'] != $ma_cham_cong) $updateData['ma_cham_cong'] = $ma_cham_cong;
                if (!empty($ho_va_ten) && $current['ho_va_ten'] != $ho_va_ten) $updateData['ho_va_ten'] = $ho_va_ten;
                if (!empty($email) && $current['email'] != $email) $updateData['email'] = $email;
                if (!empty($email_ca_nhan) && $current['email_ca_nhan'] != $email_ca_nhan) $updateData['email_ca_nhan'] = $email_ca_nhan;
                if (!empty($so_dien_thoai) && $current['so_dien_thoai'] != $so_dien_thoai) $updateData['so_dien_thoai'] = $so_dien_thoai;
                if (!empty($gioi_tinh) && $current['gioi_tinh'] != $gioi_tinh) $updateData['gioi_tinh'] = $gioi_tinh;
                if (!empty($id_don_vi) && $current['id_don_vi_cong_tac'] != $id_don_vi) $updateData['id_don_vi_cong_tac'] = $id_don_vi;
                
                if (!empty($cccd_so) && $current['cccd_so'] != $cccd_so) $updateData['cccd_so'] = $cccd_so;
                if ($cccd_ngay_cap && $current['cccd_ngay_cap'] != $cccd_ngay_cap) $updateData['cccd_ngay_cap'] = $cccd_ngay_cap;
                if ($cccd_ngay_het_han && $current['cccd_ngay_het_han'] != $cccd_ngay_het_han) $updateData['cccd_ngay_het_han'] = $cccd_ngay_het_han;
                if (!empty($cccd_noi_cap) && $current['cccd_noi_cap'] != $cccd_noi_cap) $updateData['cccd_noi_cap'] = $cccd_noi_cap;

                if (!empty($nganh_dt) && $current['nganh_dt'] != $nganh_dt) $updateData['nganh_dt'] = $nganh_dt;
                if (!empty($trinh_do_vh) && $current['trinh_do_vh'] != $trinh_do_vh) $updateData['trinh_do_vh'] = $trinh_do_vh;
                if (!empty($noi_dt) && $current['noi_dt'] != $noi_dt) $updateData['noi_dt'] = $noi_dt;
                if (!empty($khoa_dt) && $current['khoa_dt'] != $khoa_dt) $updateData['khoa_dt'] = $khoa_dt;
                if (!empty($nam_tn) && $current['nam_tn'] != $nam_tn) $updateData['nam_tn'] = $nam_tn;
                if (!empty($xep_loai_tn) && $current['xep_loai_tn'] != $xep_loai_tn) $updateData['xep_loai_tn'] = $xep_loai_tn;

                if (!empty($updateData)) {
                    $updateData['updated_at'] = $now;
                    $updateData['updated_user_id'] = $userId;
                    $this->db->where('ma_nhan_vien', $ma_nhan_vien);
                    $this->db->update('hrm_nhan_vien', $updateData);
                    $countUpdate++;

                    // Đồng bộ tài khoản người dùng
                    if (!empty($current['ql_nguoi_dung_id'])) {
                        $syncUser = [];
                        if (isset($updateData['email'])) $syncUser['ql_nguoi_dung_email'] = $updateData['email'];
                        if (isset($updateData['ho_va_ten'])) $syncUser['ql_nguoi_dung_ho_ten'] = $updateData['ho_va_ten'];
                        if (isset($updateData['id_don_vi_cong_tac'])) $syncUser['id_don_vi'] = $updateData['id_don_vi_cong_tac'];
                        
                        if (!empty($syncUser)) {
                            $this->db->where('ql_nguoi_dung_id', $current['ql_nguoi_dung_id']);
                            $this->db->update('ql_nguoi_dung', $syncUser);
                        }
                    }
                }

                // Cập nhật bảng công việc nếu có ngày làm chính thức
                if ($ngay_lam_chinh_thuc) {
                    $this->db->where('id_nhan_vien', $current['id_nhan_vien']);
                    $this->db->update('hrm_nhan_vien_cong_viec', [
                        'ngay_lam_chinh_thuc' => $ngay_lam_chinh_thuc,
                        'updated_at' => $now,
                        'updated_user_id' => $userId
                    ]);
                }
            } else {
                // INSERT
                $this->db->trans_start();

                // 1. Tạo bản ghi hrm_nhan_vien
                $insertData = [
                    'ma_nhan_vien' => $ma_nhan_vien,
                    'ma_cham_cong' => $ma_cham_cong,
                    'ho_va_ten' => $ho_va_ten,
                    'email' => $email,
                    'email_ca_nhan' => $email_ca_nhan,
                    'so_dien_thoai' => $so_dien_thoai,
                    'gioi_tinh' => $gioi_tinh,
                    'id_don_vi_cong_tac' => $id_don_vi,
                    'cccd_so' => $cccd_so,
                    'cccd_ngay_cap' => $cccd_ngay_cap,
                    'cccd_ngay_het_han' => $cccd_ngay_het_han,
                    'cccd_noi_cap' => $cccd_noi_cap,
                    'nganh_dt' => $nganh_dt,
                    'trinh_do_vh' => $trinh_do_vh,
                    'noi_dt' => $noi_dt,
                    'khoa_dt' => $khoa_dt,
                    'nam_tn' => $nam_tn,
                    'xep_loai_tn' => $xep_loai_tn,
                    'created_at' => $now,
                    'created_user_id' => $userId,
                    'updated_at' => $now,
                    'updated_user_id' => $userId
                ];
                $this->db->insert('hrm_nhan_vien', $insertData);
                $newId = $this->db->insert_id();

                // 2. Tạo bản ghi hrm_nhan_vien_cong_viec
                $this->db->insert('hrm_nhan_vien_cong_viec', [
                    'id_nhan_vien' => $newId,
                    'trang_thai' => 'DANG_LAM_VIEC',
                    'ngay_lam_chinh_thuc' => $ngay_lam_chinh_thuc,
                    'created_at' => $now,
                    'created_user_id' => $userId,
                    'updated_at' => $now,
                    'updated_user_id' => $userId
                ]);

                // 3. Tạo tài khoản ql_nguoi_dung
                $userData = [
                    'ql_nguoi_dung_ho_ten' => $ho_va_ten,
                    'ql_nguoi_dung_email' => $email ?: null,
                    'ql_nguoi_dung_mat_khau' => password_hash('123456', PASSWORD_BCRYPT, ['cost' => 12]),
                    'ql_nguoi_dung_loai' => 2, // Nhân viên
                    'ql_nguoi_dung_ngay_tao' => $now,
                    'ql_nguoi_dung_ngay_cap_nhat' => $now,
                    'active_flag' => 1,
                    'ql_nguoi_dung_is_admin' => 0,
                    'ql_nguoi_dung_la_lanh_dao' => 0,
                    'id_don_vi' => $id_don_vi
                ];
                $this->db->insert('ql_nguoi_dung', $userData);
                $userIdNew = $this->db->insert_id();

                // 4. Link lại id_nguoi_dung vào hrm_nhan_vien
                $this->db->where('id_nhan_vien', $newId);
                $this->db->update('hrm_nhan_vien', ['ql_nguoi_dung_id' => $userIdNew]);

                $this->db->trans_complete();

                if ($this->db->trans_status() !== FALSE) {
                    $countInsert++;
                }
            }
        }

        return [
            'updated' => $countUpdate,
            'inserted' => $countInsert
        ];
    }

    private function format_date_db($dateStr)
    {
        if (empty($dateStr)) return null;
        $dateStr = trim($dateStr);

        // 1. Hỗ trợ format d/m/Y (thường gặp khi user nhập tay trên Sheet)
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateStr, $matches)) {
            return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
        }

        // 2. Hỗ trợ format JS Date string (thường gặp khi Google Script trả về object Date)
        // Ví dụ: Fri Jan 01 2021 00:00:00 GMT+0700 (Giờ Đông Dương)
        // Loại bỏ phần trong ngoặc (mô tả múi giờ) trước khi dùng strtotime
        $cleanDate = preg_replace('/\s\(.*\)$/', '', $dateStr);
        $timestamp = strtotime($cleanDate);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return $dateStr;
    }
}
