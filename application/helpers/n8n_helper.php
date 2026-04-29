<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('call_n8n_trich_xuat')) {
    /**
     * Gọi API n8n để trích xuất nội dung file
     *
     * @param int $id_van_ban
     * @return mixed|string Kết quả từ API hoặc false nếu lỗi
     */
    function call_n8n_trich_xuat($id_van_ban)
    {
        $url = "https://n8n.demoit.site/webhook/trich-xuat-noi-dung";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'id_van_ban' => $id_van_ban
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            log_message('error', 'N8N CURL error: ' . curl_error($ch));
            curl_close($ch);
            return false;
        }

        curl_close($ch);

        log_message('debug', 'N8N response: ' . $response);

        return $response;
    }
}

if (!function_exists('send_zalo_message')) {
    /**
     * Gọi webhook n8n không cần truyền dữ liệu
     *
     * @return array|null
     */
    function send_zalo_message()
    {
        $url = "https://n8n.demoit.site/webhook/workflow-send-zalo-message";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, []);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            log_message('error', 'N8N CURL error: ' . curl_error($ch));
            curl_close($ch);
            return false;
        }

        curl_close($ch);

        log_message('debug', 'N8N response: ' . $response);
        return $response;
    }
}

// Thông báo Zalo OA
//  Truyền id_van_ban
if (!function_exists('send_zalo_oa_message')) {
    /**
     * Gọi webhook n8n không cần truyền dữ liệu
     *
     * @param int $id_van_ban
     * @return array|null
     */
    function send_zalo_oa_message($id_van_ban)
    {

        if ($_SERVER['HTTP_HOST'] == 'myoffice.demoit.site' || $_SERVER['HTTP_HOST'] == 'localhost') {
            $url = "https://n8n.demoit.site/webhook/gui-thong-bao-van-ban-test"; // 30
        } else {
            $url = "https://n8n.demoit.site/webhook/gui-thong-bao-van-ban"; // 19 
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'id_van_ban' => $id_van_ban
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            log_message('error', 'N8N CURL error: ' . curl_error($ch));
            curl_close($ch);
            return false;
        }

        curl_close($ch);

        log_message('debug', 'N8N response: ' . $response);
        return $response;
    }
}
