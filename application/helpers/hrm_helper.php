<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Helper hỗ trợ xử lý các nghiệp vụ liên quan đến nhân sự (HRM)
 */

if (!function_exists('format_fullname_with_titles')) {
    /**
     * Định dạng họ tên nhân viên kèm theo học hàm và trình độ đào tạo (học vị)
     * 
     * @param string $fullname Họ và tên
     * @param string $hoc_ham Tên học hàm đầy đủ (ví dụ: Giáo sư)
     * @param string $trinh_do_dt Tên trình độ đào tạo đầy đủ (ví dụ: Tiến sĩ)
     * @return string Họ tên đã được định dạng (ví dụ: GS TS Nguyễn Văn A)
     */
    function format_fullname_with_titles($fullname, $hoc_ham = '', $trinh_do_dt = '')
    {
        $CI = &get_instance();
        $prefix = '';

        if (!empty($hoc_ham)) {
            $row = $CI->db->select('ten_viet_tat')
                ->from('hrm_hoc_ham_hoc_vi')
                ->like('ten_day_du', trim($hoc_ham))
                ->get()
                ->row_array();
            if ($row) {
                $prefix .= $row['ten_viet_tat'] . ' ';
            }
        }

        if (!empty($trinh_do_dt)) {
            $row = $CI->db->select('ten_viet_tat')
                ->from('hrm_hoc_ham_hoc_vi')
                ->like('ten_day_du', trim($trinh_do_dt))
                ->get()
                ->row_array();
            if ($row) {
                $prefix .= $row['ten_viet_tat'] . ' ';
            }
        }

        return trim($prefix . $fullname);
    }
}

if (!function_exists('append_hhhv_to_list')) {
    /**
     * Xử lý thêm tiền tố học hàm học vị cho một danh sách nhân viên 
     * Tối ưu hiệu năng bằng cách chỉ gọi 1 query duy nhất để lấy bản đồ học hàm học vị
     * 
     * @param array &$list Danh sách mảng nhân viên (pass by reference)
     * @param string $name_key Tên key chứa họ tên cần chèn tiền tố trong mảng (mặc định: ho_va_ten)
     */
    function append_hhhv_to_list(&$list, $name_key = 'ho_va_ten')
    {
        if (empty($list)) return;

        $CI = &get_instance();
        
        // Lấy toàn bộ danh mục học hàm học vị để cache
        $hhhv_data = $CI->db->get('hrm_hoc_ham_hoc_vi')->result_array();
        
        // Tạo map để tra cứu nhanh (case-insensitive)
        $hhhv_map = [];
        foreach ($hhhv_data as $row) {
            $hhhv_map[trim(mb_strtolower($row['ten_day_du']))] = $row['ten_viet_tat'];
        }

        foreach ($list as &$item) {
            $prefix = '';
            
            // Xử lý Học hàm
            if (!empty($item['hoc_ham'])) {
                $key_ham = trim(mb_strtolower($item['hoc_ham']));
                if (isset($hhhv_map[$key_ham])) {
                    $prefix .= $hhhv_map[$key_ham] . ' ';
                }
            }

            // Xử lý Trình độ đào tạo (Học vị)
            if (!empty($item['trinh_do_dt'])) {
                $key_vi = trim(mb_strtolower($item['trinh_do_dt']));
                if (isset($hhhv_map[$key_vi])) {
                    $prefix .= $hhhv_map[$key_vi] . ' ';
                }
            }

            // Gắn vào tên nếu có tiền tố
            if (!empty($prefix) && isset($item[$name_key])) {
                $item[$name_key] = trim($prefix . $item[$name_key]);
            }
        }
    }
}
