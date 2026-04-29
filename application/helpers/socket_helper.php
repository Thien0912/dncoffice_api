<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('send_socket_notify')) {
    /**
     * Gửi thông báo realtime qua Socket Server (Node.js)
     * 
     * @param array $user_ids Mảng chứa ID người nhận (VD: [1, 2, 3])
     * @param string $type Loại thông báo (VD: 'new_task', 'approve_doc')
     * @param array $payload Dữ liệu chi tiết đính kèm
     * @return bool
     */
    function send_socket_notify($user_ids, $type, $payload = [])
    {
        // URL của Node.js Socket Server (Lấy từ config/config.php)
        $socket_url = config_item('socket_server_url') . '/notify';

        // Chuẩn bị dữ liệu
        $data = [
            'user_ids' => $user_ids,
            'type' => $type,
            'payload' => $payload
        ];

        // Khởi tạo cURL
        $ch = curl_init($socket_url);
        
        // Cấu hình cURL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data))
        ]);
        
        // Timeout cực ngắn (100ms) để PHP ko bị treo chờ Nodejs
        // Nếu quan trọng việc nhận kết quả trả về thì tăng lên 1-2s
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 200); 

        // Thực thi
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        
        curl_close($ch);

        if ($http_code != 200) {
            log_message('error', "Socket Error: URL: $socket_url | Code: $http_code | Error: $curl_error | Result: $result");
            return [
                'success' => false,
                'debug' => [
                    'url' => $socket_url,
                    'code' => $http_code,
                    'curl_error' => $curl_error,
                    'result' => $result
                ]
            ];
        }

        return true;
    }
}

if (!function_exists('send_socket_pin_verified')) {
    /**
     * Gửi sự kiện xác thực mã PIN thành công qua Socket Server
     * 
     * @param string|int $user_id ID người dùng
     * @return bool
     */
    function send_socket_pin_verified($user_id)
    {
        $socket_url = config_item('socket_server_url') . '/pin-verified';

        $data = ['user_id' => $user_id];

        $ch = curl_init($socket_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data))
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 200); 

        curl_exec($ch);
        curl_close($ch);

        return true;
    }
}

if (!function_exists('send_socket_pin_registered')) {
    /**
     * Gửi sự kiện đăng ký mã PIN thành công qua Socket Server
     * 
     * @param string|int $user_id ID người dùng
     * @return bool
     */
    function send_socket_pin_registered($user_id)
    {
        $socket_url = config_item('socket_server_url') . '/pin-registered';

        $data = ['user_id' => $user_id];

        $ch = curl_init($socket_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data))
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 200); 

        curl_exec($ch);
        curl_close($ch);

        return true;
    }
}

if (!function_exists('send_socket_pin_disabled')) {
    /**
     * Gửi sự kiện mã PIN bị vô hiệu hóa qua Socket Server
     * 
     * @param string|int $user_id ID người dùng
     * @return bool
     */
    function send_socket_pin_disabled($user_id)
    {
        $socket_url = config_item('socket_server_url') . '/pin-disabled';

        $data = ['user_id' => $user_id];

        $ch = curl_init($socket_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data))
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 200); 

        curl_exec($ch);
        curl_close($ch);

        return true;
    }
}

if (!function_exists('send_socket_unread_count')) {
    /**
     * Gửi số lượng thông báo chưa đọc qua Socket Server
     * 
     * @param string|int $user_id ID người dùng
     * @param int $count Số lượng chưa đọc
     * @return bool
     */
    function send_socket_unread_count($user_id, $count)
    {
        $socket_url = config_item('socket_server_url') . '/unread-count';

        $data = [
            'user_id' => $user_id,
            'count' => (int)$count
        ];

        $ch = curl_init($socket_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data))
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 200); 

        curl_exec($ch);
        curl_close($ch);

        return true;
    }
}
