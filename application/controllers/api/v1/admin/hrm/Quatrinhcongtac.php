<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_qua_trinh_cong_tac_model $Hrm_qua_trinh_cong_tac_model
 * @property Hrm_vi_tri_cong_viec_model $Hrm_vi_tri_cong_viec_model
 * @property E_don_vi_model $E_don_vi_model
 * @property Fileupload $fileupload
 */



class Quatrinhcongtac extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_qua_trinh_cong_tac_model', 'Hrm_vi_tri_cong_viec_model', 'E_don_vi_model']);
        $this->load->library(['Validator', 'Fileupload']);
    }
    public function index_get()
    {
        show_404();
    }

    private function getValidationRules()
    {
        $data = [
            'id_nhan_vien' => commonRequest('id_nhan_vien') ?? null,
            'ngay_bat_dau' => commonRequest('ngay_bat_dau') ?? null,
            'ngay_ket_thuc' => commonRequest('ngay_ket_thuc') ?: null,
            'id_vi_tri_cong_viec' => commonRequest('id_vi_tri_cong_viec') ?? null,
            'id_don_vi' => commonRequest('id_don_vi') ?? null,
            'cap' => commonRequest('cap') ?? null,
            'bac' => commonRequest('bac') ?? null,
            'ghi_chu' => commonRequest('ghi_chu') ?? null,
            'files' => commonRequest('files') ?? null,
        ];

        $rules = [
            'id_nhan_vien' => 'required|integer',
            'ngay_bat_dau' => 'required|date',
            'id_vi_tri_cong_viec' => 'required',
            'id_don_vi' => 'required',
        ];

        $customMessages = [
            'id_nhan_vien.required' => 'Vui lòng chọn nhân viên',
            'ngay_bat_dau.required' => 'Vui lòng nhập ngày bắt đầu',
            'ngay_bat_dau.date' => 'Ngày bắt đầu không đúng định dạng',
            'id_vi_tri_cong_viec.required' => 'Vui lòng nhập vị trí công việc',
            'id_don_vi.required' => 'Vui lòng nhập đơn vị công tác',
        ];

        return compact('data', 'rules', 'customMessages');
    }
    private function validate_data()
    {
        $validator = new Validator();
        $validation = $this->getValidationRules();

        $validator->setCustomMessages($validation['customMessages']);

        if (!$validator->validate($validation['data'], $validation['rules'])) {
            resBadrequest($validator->errors());
        }
    }
    public function create_post()
    {
        $this->validate_data();

        $this->db->trans_start();

        $data = $this->getValidationRules()['data']; // Lấy dữ liệu đã validate
        $data['files'] = json_decode(json_encode($data['files']), true);

        $folderName = 'qua-trinh-cong-tac/' . date('Y') . '/' . date('m');
        if ($data['files']) {

            $files = $data['files'];
            // Chuẩn bị dữ liệu cho từng file
            $file = [
                'name'     => $files['name'],
                'type'     => $files['type'],
                'tmp_name' => $files['tmp_name'],
                'error'    => $files['error'],
                'size'     => $files['size'],
            ];
            $uploadedFile = $this->fileupload->upload($file, $folderName);
            $uploadedFile['file_size'] = exchangeFromKbToLargerCapacity($uploadedFile['file_size']);
            unset($uploadedFile['success']);
        }
        $files = isset($uploadedFile) ? json_encode($uploadedFile) : null;
        $data['files'] = $files;


        $quatrinhcongtac = $this->Hrm_qua_trinh_cong_tac_model->create($data);
        $quatrinhcongtac['ten_cong_viec'] = $this->Hrm_vi_tri_cong_viec_model->find($data['id_vi_tri_cong_viec'])['ten_cong_viec'];
        $quatrinhcongtac['ten_don_vi'] = $this->E_don_vi_model->find($data['id_don_vi'])['ten_don_vi'];

        //create log
        $this->createLog('create', 'Tạo mới quá trình công tác', null, $quatrinhcongtac, 'hrm_qua_trinh_cong_tac');
        $this->db->trans_commit();
        resSuccess($quatrinhcongtac, 'Thêm thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }

    public function update_post($id)
    {
        $this->validate_data();

        // Lấy dữ liệu đã validate
        $data = $this->getValidationRules()['data'];

        // Tìm thông tin quá trình công tác theo ID
        $quatrinhcongtac = $this->Hrm_qua_trinh_cong_tac_model->find($id);
        if (!$quatrinhcongtac) {
            resError('Không tìm thấy quá trình công tác này.', REST_Controller::HTTP_NOT_FOUND);
        }

        $folderName = 'qua-trinh-cong-tac/' . date('Y') . '/' . date('m');

        // Xử lý upload file nếu có file mới
        // Xử lý upload file nếu có file mới
        if (!empty($data['files']['name'])) { // kiểm tra có file thực sự upload không
            $files = $data['files'];
            $file = [
                'name'     => $files['name'],
                'type'     => $files['type'],
                'tmp_name' => $files['tmp_name'],
                'error'    => $files['error'],
                'size'     => $files['size'],
            ];

            // Upload file
            $uploadedFile = $this->fileupload->upload($file, $folderName);
            $uploadedFile['file_size'] = exchangeFromKbToLargerCapacity($uploadedFile['file_size']);

            // Xóa file cũ nếu có
            if (!empty($quatrinhcongtac['files'])) {
                $oldFilePath = json_decode($quatrinhcongtac['files'], true)['file_path'] ?? null;
                if ($oldFilePath && file_exists(FCPATH . $oldFilePath)) {
                    unlink(FCPATH . $oldFilePath);
                }
            }

            unset($uploadedFile['success']);
            $data['files'] = json_encode($uploadedFile);
        } else {
            // Không có file mới
            if (!empty($quatrinhcongtac['files'])) {
                // Giữ nguyên file cũ
                // unset($data['files']);
                $file = json_decode($quatrinhcongtac['files'], true);
                if (!empty($file)) {
                    $fullPath = FCPATH . $file['file_path'];
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }
                $data['files'] = null;
            } else {
                // Không có file cũ luôn => set NULL
                $data['files'] = null;
            }
        }


        // Bắt đầu transaction
        $this->db->trans_start();

        // Cập nhật thông tin quá trình công tác
        $this->Hrm_qua_trinh_cong_tac_model->where('id_qua_trinh_cong_tac', $id)->update($data);

        // Lấy dữ liệu sau khi cập nhật
        $updated_quatrinhcongtac = $this->Hrm_qua_trinh_cong_tac_model->find($id);

        // Lấy thêm thông tin vị trí công việc và đơn vị
        $updated_quatrinhcongtac['ten_cong_viec'] = $this->Hrm_vi_tri_cong_viec_model->find($updated_quatrinhcongtac['id_vi_tri_cong_viec'])['ten_cong_viec'] ?? null;
        $updated_quatrinhcongtac['ten_don_vi'] = $this->E_don_vi_model->find($updated_quatrinhcongtac['id_don_vi'])['ten_don_vi'] ?? null;

        // Ghi log
        $this->createLog('update', 'Cập nhật quá trình công tác', $quatrinhcongtac, $updated_quatrinhcongtac, 'hrm_qua_trinh_cong_tac');

        // Kết thúc transaction
        $this->db->trans_commit();

        resSuccess($updated_quatrinhcongtac, 'Cập nhật thành công');
    }

    public function show_get($id)
    {
        // Lấy thông tin quá trình công tác từ model
        $quatrinhcongtac = $this->Hrm_qua_trinh_cong_tac_model->find($id);

        // Kiểm tra nếu không tìm thấy dữ liệu
        if (!$quatrinhcongtac) {
            resError(['message' => 'Không tìm thấy thông tin quá trình công tác']);
            return;
        }

        // Lấy thêm thông tin vị trí công việc và đơn vị
        $quatrinhcongtac['ten_cong_viec'] = $this->Hrm_vi_tri_cong_viec_model->find($quatrinhcongtac['id_vi_tri_cong_viec'])['ten_cong_viec'] ?? null;
        $quatrinhcongtac['ten_don_vi'] = $this->E_don_vi_model->find($quatrinhcongtac['id_don_vi'])['ten_don_vi'] ?? null;
        $quatrinhcongtac['files'] = $quatrinhcongtac['files'] ? json_decode($quatrinhcongtac['files'], true) : null;

        // Trả về dữ liệu
        resSuccess($quatrinhcongtac, 'Lấy thông tin thành công');
    }

    public function delete_post($id)
    {
        $quatrinhcongtac = $this->Hrm_qua_trinh_cong_tac_model->find($id);
        if (!$quatrinhcongtac) {
            resError('Không tìm thấy quá trình công tác này');
        }
        $this->db->trans_start();

        $file = json_decode($quatrinhcongtac['files'], true);
        if (!empty($file)) {
            $fullPath = FCPATH . $file['file_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        $this->Hrm_qua_trinh_cong_tac_model->where('id_qua_trinh_cong_tac', $id)->delete();

        $this->createLog('delete', 'Xóa quá trình công tác', $quatrinhcongtac, null, 'hrm_qua_trinh_cong_tac');
        $this->db->trans_commit();

        resSuccess(null, 'Xóa thành công');
    }
}
