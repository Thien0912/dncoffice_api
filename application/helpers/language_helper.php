<?php
if (!function_exists('l')) {
    /**
     * Hàm l: lấy dòng ngôn ngữ qua key 
     * Ví dụ: l('employee_fullname_required')
     * 
     * @param string $key
     * @param array  $replace Mảng thay thế chuỗi {key} trong ngôn ngữ
     * @return string
     */
    function l($key, $replace = [])
    {
        $CI = get_instance();
        $line = $CI->lang->line($key);
        if (empty($line)) {
            return $key;
        }

        // Nếu có tham số thay thế (dạng {key}), thì replace
        if (!empty($replace)) {
            foreach ($replace as $k => $v) {
                // {key} => $v
                $line = str_replace('{' . $k . '}', $v, $line);
            }
        }

        return $line ?? $key;
    }
}
