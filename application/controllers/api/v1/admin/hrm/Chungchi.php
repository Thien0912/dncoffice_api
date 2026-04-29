<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_chung_chi_model $Hrm_chung_chi_model
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Fileupload $fileupload
 */



class Chungchi extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_chung_chi_model', 'Hrm_nhan_vien_model']);
        $this->load->library(['Validator', 'Fileupload']);
    }

    public function index_get()
    {
        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;

        $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
            'order' => commonRequest('order'),
            'columns' => commonRequest('columns')
        ] : [];

        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];

        $data = $this->Hrm_chung_chi_model->getAll($start, $length, $searchValue, $orderBy, $searchKey);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }
    public function create_post()
    {
        $this->validate_data();

        $data = $this->getValidationRules()['data']; // Lấy dữ liệu đã validate

        $nhanvien = $this->Hrm_nhan_vien_model->find($data['id_nhan_vien']);
        if (!$nhanvien) {
            resError('Không tìm thấy nhân viên này. Vui lòng tải lại trang');
        }

        $folderName = 'certificate/' . date('Y') . '/' . date('m');
        $uploadedFiles = [];
        if (isset($data['files'])) {
            $files = $data['files']; // Tên input từ form                        

            foreach ($files['name'] as $key => $fileName) {
                // Chuẩn bị dữ liệu cho từng file
                $file = [
                    'name'     => $files['name'][$key],
                    'type'     => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error'    => $files['error'][$key],
                    'size'     => $files['size'][$key],
                ];
                $result = $this->fileupload->upload($file, $folderName);
                $uploadedFiles[] = $result;
            }
        }
        foreach ($uploadedFiles as $key => $file) {
            unset($uploadedFiles[$key]['success']);
            $uploadedFiles[$key]['file_size'] = exchangeFromKbToLargerCapacity($uploadedFiles[$key]['file_size']);
        }
        $data['files'] = json_encode($uploadedFiles);
        $this->db->trans_start();

        // Thêm chứng chỉ
        $result = $this->Hrm_chung_chi_model->create($data);

        $this->db->trans_commit();

        resSuccess($result, 'Thêm chứng chỉ thành công');
    }

    public function delete_post($id)
    {
        $chungchi = $this->Hrm_chung_chi_model->find($id);

        // Kiểm tra nếu không tìm thấy dữ liệu
        if (!$chungchi) {
            resError('Không tìm thấy chứng chỉ này', REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        // Lấy danh sách file từ cột `files`
        $files = json_decode($chungchi['files'], true);

        // Xóa các file vật lý
        if (!empty($files)) {
            foreach ($files as $file) {
                if (isset($file['file_path']) && file_exists($file['file_path'])) {
                    unlink($file['file_path']); // Xóa file
                }
            }
        }

        // Bắt đầu transaction
        $this->db->trans_start();

        // Xóa chứng chỉ trong database
        // $this->Hrm_chung_chi_model->where('id_chung_chi', $id)->delete();

        // Tạo log xóa
        // $this->createLog('delete', 'Xóa chứng chỉ', $chungchi, null, 'hrm_chung_chi');

        // Commit transaction
        $this->db->trans_commit();

        // Trả về kết quả thành công
        resSuccess(null, 'Xóa chứng chỉ thành công');
    }

    public function show_get($id)
    {
        // Tìm thông tin chứng chỉ theo ID
        $chungchi = $this->Hrm_chung_chi_model->find($id);

        // Kiểm tra nếu không tìm thấy dữ liệu
        if (!$chungchi) {
            resError('Không tìm thấy chứng chỉ này', REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        // Lấy danh sách file từ cột `files` (nếu có)
        $chungchi['files'] = !empty($chungchi['files']) ? json_decode($chungchi['files'], true) : [];

        // Trả về dữ liệu
        resSuccess($chungchi, 'Lấy thông tin chứng chỉ thành công');
    }

    public function update_post($id)
    {
        $chungchi = $this->Hrm_chung_chi_model->find($id);
        if (!$chungchi) {
            return resError('Không tìm thấy chứng chỉ này', REST_Controller::HTTP_NOT_FOUND);
        }

        $this->validate_data();
        $data = $this->getValidationRules()['data'];

        $old_files = json_decode(commonRequest('old_files'), true) ?? [];

        $field_files =  json_decode($chungchi['files'], true) ?? [];
        $files = $data['files'] ?? [];

        // Tạo mảng chứa tên file trong old_files (chuẩn hóa lowercase để so sánh)
        $old_file_names = array_map(function ($file) {
            return strtolower($file['file_name']);
        }, $old_files);

        // Unlink các file KHÔNG còn trong old_files
        array_filter($field_files, function ($file) use ($old_file_names) {
            if (!in_array(strtolower($file['file_name']), $old_file_names)) {
                $fullPath = FCPATH . $file['file_path'];
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
            return false; // Dù gì cũng không cần giữ lại, nên luôn return false
        });

        // Lọc lại field_files: chỉ giữ những file còn trong old_files
        $field_files = array_filter($field_files, function ($file) use ($old_file_names) {
            return in_array(strtolower($file['file_name']), $old_file_names);
        });

        // Nếu bạn cần reindex lại array:
        $field_files = array_values($field_files);

        $folderName = 'certificate/' . date('Y') . '/' . date('m');
        $uploadedFiles = [];
        if (isset($data['files'])) {
            $files = $data['files']; // Tên input từ form                        

            foreach ($files['name'] as $key => $fileName) {
                // Chuẩn bị dữ liệu cho từng file
                $file = [
                    'name'     => $files['name'][$key],
                    'type'     => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error'    => $files['error'][$key],
                    'size'     => $files['size'][$key],
                ];
                $result = $this->fileupload->upload($file, $folderName);
                $uploadedFiles[] = $result;
            }
        }
        foreach ($uploadedFiles as $key => $file) {
            unset($uploadedFiles[$key]['success']);
            $uploadedFiles[$key]['file_size'] = exchangeFromKbToLargerCapacity($uploadedFiles[$key]['file_size']);
        }
        $field_files = $field_files ?? []; // đảm bảo biến tồn tại
        $all_files = array_merge($uploadedFiles, $field_files);

        // Encode để lưu
        $data['files'] = json_encode($all_files);

        // Cập nhật
        $this->db->trans_start();
        $this->Hrm_chung_chi_model->where('id_chung_chi', $id)->update($data);
        $chungchi = $this->Hrm_chung_chi_model->find($id);
        $this->createLog('update', 'Cập nhật chứng chỉ', $chungchi, $data, 'hrm_chung_chi');
        $this->db->trans_commit();

        return resSuccess($chungchi, 'Cập nhật chứng chỉ thành công');
    }



    private function getValidationRules()
    {
        $data = [
            'id_nhan_vien' => commonRequest('id_nhan_vien') ?? null,
            'ten_chung_chi' => commonRequest('ten_chung_chi') ?? null,
            'noi_cap' => commonRequest('noi_cap') ?? null,
            'ngay_cap_chung_chi' => commonRequest('ngay_cap_chung_chi') ?? null,
            'files' => commonRequest('files') ?? null,
        ];

        $rules = [
            'id_nhan_vien' => 'required',
            'ten_chung_chi' => 'required',
            'noi_cap' => 'required',
            'ngay_cap_chung_chi' => 'required|date',
        ];

        $customMessages = [
            'id_nhan_vien.required' => 'Không có id nhân viên',
            'ten_chung_chi.required' => 'Tên chứng chỉ bắt buộc nhập',
            'noi_cap.required' => 'Nơi cấp chứng chỉ bắt buộc nhập',
            'ngay_cap_chung_chi.required' => 'Ngày cấp chứng chỉ bắt buộc nhập',
            'ngay_cap_chung_chi.date' => 'Ngày cấp chứng chỉ chưa đúng định dạng',
        ];

        return compact('data', 'rules', 'customMessages');
    }

    private function validate_data()
    {
        $validator = new Validator();
        $validation = $this->getValidationRules();

        $validator->setCustomMessages($validation['customMessages']);

        if (!$validator->validate($validation['data'], $validation['rules'])) {
            resBadrequest($validator->errors(), 'Vui lòng kiểm tra lại các trường cần nhập');
        }
    }
}
