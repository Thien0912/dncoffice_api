<?php
defined('BASEPATH') or exit('No direct script access allowed');
require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property CI_Upload $upload
 */

class UploadController extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('upload');
        $this->load->helper('url');
    }
    public function upload_image_post()
    {
        $file = commonRequest('file');
        if ($file) {
            $config['upload_path'] = 'uploads/file-manager/'; // Thư mục để lưu file
            $config['allowed_types'] = '*';

            if (!is_dir($config['upload_path'])) {
                mkdir($config['upload_path'], 0755, true);
            }
            $newFileName = 'fileuploads-' . date('Ymd') . '-' . time() . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $config['file_name'] = $newFileName;

            $this->upload->initialize($config);

            if (!$this->upload->do_upload('file')) {
                return $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => $this->upload->display_errors(),
                    'success' => true,
                    'data' => null,
                ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
            } else {
                $uploadData = $this->upload->data(); // Lấy dữ liệu file đã upload 
                $fileName = $uploadData['file_name']; // Tên file 
                //gọi đến importExcel của Pxl để xuất dữ liệu mảng 
                $path = $config['upload_path'] . $fileName;

                return $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_OK,
                    'message' => "Success",
                    'success' => true,
                    'data' => base_url($path),
                ], REST_INSTANCE_Controller::HTTP_OK);
            }
        }
    }

    public function delete_image_post()
    {
        $image = commonRequest('image');
        $filePath = str_replace(base_url(), '', $image);

        // Kiểm tra và xóa file nếu tồn tại
        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                return $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_OK,
                    'message' => "Image deleted successfully.",
                    'success' => true
                ], REST_INSTANCE_Controller::HTTP_OK);
            } else {
                return $this->response([    
                    'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => "Failed to delete the image.",
                    'success' => false
                ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else {
            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => "File does not exist.",
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
