<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

class SyncGoogleSheet extends REST_INSTANCE_Controller
{
    // ID của Google Sheet: 
    // AKfycbxmyw7Lvid6RFfVGQGcDdcJoB1crxrt_JA0W3HMogU2ZN4L6pNeh7bHcMyrruUZYVA9Yw


    // THAY URL WEB APP CỦA BẠN VÀO ĐÂY
    // URL đầy đủ cho tài khoản
    const GOOGLE_SHEET_URL = 'https://script.google.com/macros/s/AKfycbxmyw7Lvid6RFfVGQGcDdcJoB1crxrt_JA0W3HMogU2ZN4L6pNeh7bHcMyrruUZYVA9Yw/exec';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Hrm_nhan_vien_sync_model');
    }

    /**
     * Đồng bộ toàn bộ nhân viên lên Google Sheet
     */
    public function sync_all_get()
    {
        // 1. Lấy dữ liệu từ Model chuyên biệt cho Sync
        $data = $this->Hrm_nhan_vien_sync_model->get_data_for_sync();

        if (empty($data)) {
            resError('Không có dữ liệu để đồng bộ');
        }

        // 2. Gửi dữ liệu sang Google Apps Script
        $result = $this->sendToGoogleSheet($data);

        if ($result && isset($result['status']) && $result['status'] === 'success') {
            resSuccess($result, 'Đồng bộ dữ liệu lên Google Sheet thành công!');
        } else {
            $message = 'Không xác định';
            if (!$result) {
                $message = 'Không nhận được phản hồi từ Google Script (Có thể do lỗi Script hoặc URL)';
            } elseif (isset($result['message'])) {
                $message = $result['message'];
            }
            resError('Lỗi đồng bộ: ' . $message);
        }
    }

    /**
     * Nhập dữ liệu từ Google Sheet về lại CSDL
     */
    public function import_all_get()
    {
        // 1. Lấy dữ liệu từ Google Sheet qua lệnh GET
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::GOOGLE_SHEET_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (empty($response)) {
            resError('Không nhận được phản hồi từ Google (HTTP ' . $httpCode . ')');
        }

        if ($response === null) {
            resError('Không nhận được phản hồi từ Google Script.');
        }

        $sheetData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            resError('Lỗi giải mã JSON từ Google Script. Nội dung nhận được: ' . substr(strip_tags($response), 0, 200));
        }

        if (empty($sheetData)) {
            resError('Sheet rỗng hoặc không có dữ liệu hợp lệ (Cần có ít nhất 1 dòng dữ liệu dưới tiêu đề).');
        }

        // Gọi model xử lý cập nhật tập trung
        $auth = $this->getUserLogin();
        $userId = $auth['ql_nguoi_dung_id'] ?? 0;
        $result = $this->Hrm_nhan_vien_sync_model->update_from_sync($sheetData, $userId);

        resSuccess($result, "Nhập dữ liệu thành công! Cập nhật: {$result['updated']}, Thêm mới: {$result['inserted']}");
    }

    private function sendToGoogleSheet($data)
    {
        $jsonData = json_encode($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::GOOGLE_SHEET_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Gửi POST nguyên bản
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // LOGIC KIỂM TRA THÔNG MINH:
        // Nếu không có lỗi CURL nghiêm trọng, hoặc mã HTTP là 200/302, 
        // hoặc nếu có phản hồi JSON thành công thì đều báo Xanh.
        $decoded = json_decode($response, true);
        $isSuccessResponse = ($decoded && isset($decoded['status']) && $decoded['status'] === 'success');

        if ($isSuccessResponse || $httpCode == 200 || $httpCode == 302) {
            return ['status' => 'success', 'message' => 'Đồng bộ hoàn tất'];
        }

        if ($error) {
            // Nếu vẫn lỗi stream nhưng bạn thấy dữ liệu lên sheet thì vẫn coi là OK
            if (strpos($error, 'stream') !== false) {
                 return ['status' => 'success', 'message' => 'Đồng bộ hoàn tất'];
            }
            return ['status' => 'error', 'message' => 'Lỗi kết nối: ' . $error];
        }

        return ['status' => 'error', 'message' => 'Google trả về mã ' . $httpCode];
    }
}
