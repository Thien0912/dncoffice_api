<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';
require APPPATH . 'libraries/pxl/PHPExcel.php';
class Test extends REST_INSTANCE_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function cap_nhat_ma_cham_cong_post() {

        $file = './uploads/danh_sach_nhan_vien.xlsx';

        // Load file Excel
        $objPHPExcel = PHPExcel_IOFactory::load($file);
        $sheet = $objPHPExcel->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            $ma_cham_cong = trim($sheet->getCell("A{$row}")->getValue());
            $ho_va_ten = trim($sheet->getCell("B{$row}")->getValue());

            if ($ho_va_ten == '') continue;

            // Chuẩn hóa tên
            $ho_va_ten = preg_replace('/\s+/', ' ', $ho_va_ten);

            // Cập nhật mã chấm công
            $this->db->where('ho_va_ten', $ho_va_ten);
            $this->db->update('hrm_nhan_vien', ['ma_cham_cong' => $ma_cham_cong]);

            if ($this->db->affected_rows() === 0) {
                log_message('error', "Không cập nhật được cho {$ho_va_ten}");
            }

            if($this->db->affected_rows() > 0) {
                
            }
        }

        echo "Đã cập nhật xong mã chấm công.";
    }
}
