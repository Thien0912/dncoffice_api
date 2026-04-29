<?php

/**
 * @var CI_Controller $CI
 * @property CI_Upload $upload
 */

class Fileupload
{
    function upload($file, $folderName, $allowedTypes =  'jpg|jpeg|png|pdf|doc|docx|xls|xlsx|zip|rar')
    {
        $CI = &get_instance();
        $CI->load->library('upload');

        // Định nghĩa đường dẫn lưu file
        $uploadPath = './uploads/' . $folderName;
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);

            // Nội dung của file index.html
            $indexContent = <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <title>Forbidden</title>
    </head>
    <body>
        <h1>Forbidden</h1>
    </body>
    </html>
    HTML;

            // Ghi nội dung vào file index.html
            file_put_contents($uploadPath . '/index.html', $indexContent);
        }

        // Tạo tên file ngẫu nhiên
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileNameHashed = uniqid() . '_' . time() . '.' . $fileExtension;

        // Cấu hình upload
        $config['upload_path']   = $uploadPath;
        $config['allowed_types'] = $allowedTypes; // Định dạng file được phép
        // $config['allowed_types'] = '*'; // Định dạng file được phép
        $config['file_name']     = $fileNameHashed;
        $config['overwrite']     = false;
        $config['max_size']      = 102400;    // Giới hạn kích thước file: 100MB

        $CI->upload->initialize($config);

        // Gán dữ liệu file vào biến $_FILES
        $_FILES['file']['name']     = $file['name'];
        $_FILES['file']['type']     = $file['type'];
        $_FILES['file']['tmp_name'] = $file['tmp_name'];
        $_FILES['file']['error']    = $file['error'];
        $_FILES['file']['size']     = $file['size'];

        // Upload file
        if ($CI->upload->do_upload('file')) {
            $uploadData = $CI->upload->data();
            return [
                'success' => true,
                'file_name' => $uploadData['client_name'],
                'file_path' => 'uploads/' . $folderName . '/' . $uploadData['file_name'],
                'file_extension' => $fileExtension,
                'file_size' => $uploadData['file_size'], // Dung lượng file (KB)
            ];
        } else {
            // Trả lỗi nếu không upload được
            return [
                'success' => false,
                'file_name' => $file['name'],
                'error'     => $CI->upload->display_errors('', ''),
            ];
        }
    }

    function delete($filePath)
    {
        // Kiểm tra nếu file tồn tại
        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                return true;
            } else {
                return false;
                // return [
                //     'success' => false,
                //     'message' => 'Không thể xóa file. Có thể do quyền truy cập.',
                // ];
            }
        } else {
            return false;
            // return [
            //     'success' => false,
            //     'message' => 'File không tồn tại.',
            // ];
        }
    }
}
