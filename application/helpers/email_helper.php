<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('send_email')) {
    function send_email($to, $subject, $message, $from_email = 'noreply@tchc.nctu.edu.vn', $from_name = 'Trường Đại Học Nam Cần Thơ - DNC University', $file_dinh_kem = [], $cc = [], $bcc = [])
    {
        $CI = &get_instance();

        $CI->email->from($from_email, $from_name);
        $CI->email->to($to);
        $CI->email->subject($subject);
        $CI->email->message($message);

        // $file_path = FCPATH . 'uploads/documents/67a9ca3c128c6_1739180604.xlsx'; // ví dụ file trong thư mục uploads

        // Gán file
        if (!empty($file_dinh_kem)) {
            foreach ($file_dinh_kem as $file) {
                if (is_array($file)) {
                    $custom_file_path = FCPATH . $file['duong_dan'];
                    $file_name = isset($file['ten_file_goc']) ? $file['ten_file_goc'] : basename($custom_file_path);
                    $CI->email->attach(
                        $custom_file_path,
                        'attachment',
                        $file_name
                    );
                } else if (is_string($file)) {
                    $custom_file_path = FCPATH . $file;
                    $CI->email->attach(
                        $custom_file_path,
                        'attachment',
                        basename($custom_file_path)
                    );
                }
            }
        }

        if (!empty($cc)) {
            $CI->email->cc($cc);
        }

        if (!empty($bcc)) {
            $CI->email->bcc($bcc);
        }

        if ($CI->email->send()) {

            return true;
        } else {
            // log_message('error', $CI->email->print_debugger());
            // log_message('error', "TO: " . $CI->email->to($to) . " - SUBJECT: " . $CI->email->subject($subject));
            return false;
        }
    }
}
