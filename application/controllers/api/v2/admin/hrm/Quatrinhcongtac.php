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

        $is_public = 1;
        $data = $this->getValidationRules()['data']; // Lấy dữ liệu đã validate
        
        // Tìm quá trình công tác hiện tại (chưa có ngày kết thúc) của nhân viên này
        if (isset($data['id_nhan_vien']) && isset($data['ngay_bat_dau'])) {
            $this->db->select('*');
            $this->db->from('hrm_qua_trinh_cong_tac');
            $this->db->where('id_nhan_vien', $data['id_nhan_vien']);
            $this->db->where('ngay_ket_thuc IS NULL', NULL, FALSE);
            $this->db->order_by('ngay_bat_dau', 'DESC');
            $this->db->limit(1);
            $current_quatrinh = $this->db->get()->row_array();
            
            // Nếu tìm thấy quá trình công tác hiện tại, cập nhật ngày kết thúc
            if ($current_quatrinh) {
                $ngay_bat_dau_moi = new DateTime($data['ngay_bat_dau']);
                $ngay_ket_thuc_cu = clone $ngay_bat_dau_moi;
                $ngay_ket_thuc_cu->modify('-1 day');
                
                $this->db->where('id_qua_trinh_cong_tac', $current_quatrinh['id_qua_trinh_cong_tac']);
                $this->db->update('hrm_qua_trinh_cong_tac', ['ngay_ket_thuc' => $ngay_ket_thuc_cu->format('Y-m-d')]);
                
                $this->createLog('update', 'Tự động cập nhật ngày kết thúc quá trình công tác cũ khi thêm mới', null, [
                    'id_qua_trinh_cong_tac' => $current_quatrinh['id_qua_trinh_cong_tac'],
                    'ngay_ket_thuc' => $ngay_ket_thuc_cu->format('Y-m-d')
                ], 'hrm_qua_trinh_cong_tac');
            }
        }

        $this->db->trans_start();
        // $data['files'] = json_decode(json_encode($data['files']), true);

        $folderName = 'qua-trinh-cong-tac/' . date('Y') . '/' . date('m');
        $uploadedFiles = [];
        $files = commonRequest('files_dinh_kem') ? commonRequest('files_dinh_kem') : null;
        if (isset($files) && !empty($files)) {
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
            $uploadedFiles[$key]['is_public'] = $is_public;
        }
        $data['files'] = json_encode($uploadedFiles);

        $quatrinhcongtac = $this->Hrm_qua_trinh_cong_tac_model->create($data);
        $quatrinhcongtac['ten_cong_viec'] = $this->Hrm_vi_tri_cong_viec_model->find($data['id_vi_tri_cong_viec'])['ten_cong_viec'];
        $quatrinhcongtac['ten_don_vi'] = $this->E_don_vi_model->find($data['id_don_vi'])['ten_don_vi'];

        $files = $quatrinhcongtac['files'] ? json_decode($quatrinhcongtac['files'], true) : [];
        if (!empty($files)) {
            foreach ($files as &$file) {
                if (!empty($file)) {
                    $file['file_path'] = encryptString($file['file_path']);
                }
            }
        }
        unset($file);
        $quatrinhcongtac['files'] = $files;

        $changes = [
            [
                'field' => 'quatrinhcongtac',
                'field_name' => 'Quá trình công tác',
                'old_value' => null,
                'new_value' => [
                    'value' => 'Thêm mới',
                    'label' => 'Quá trình công tác: ' . $quatrinhcongtac['ten_cong_viec'] . ' - ' . $quatrinhcongtac['ten_don_vi']
                ]
            ]
        ];
        $this->logEmployeeHistory($quatrinhcongtac['id_nhan_vien'], 'Thêm quá trình công tác', $changes);

        //create log
        $this->createLog('create', 'Tạo mới quá trình công tác', null, $quatrinhcongtac, 'hrm_qua_trinh_cong_tac');
        $this->db->trans_commit();
        resSuccess($quatrinhcongtac, 'Thêm thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }

    public function update_post($id)
    {
        $this->validate_data();

        // Lấy dữ liệu đã validate
        $is_public = 1;
        $data = $this->getValidationRules()['data'];

        // Tìm thông tin quá trình công tác theo ID
        $quatrinhcongtac = $this->Hrm_qua_trinh_cong_tac_model->find($id);
        if (!$quatrinhcongtac) {
            resError('Không tìm thấy quá trình công tác này.', REST_Controller::HTTP_NOT_FOUND);
        }

        // Xử lý file đính kèm cũ
        $fileOld = commonRequest('files_dinh_kem_old') ? json_decode(commonRequest('files_dinh_kem_old'), true) : [];
        $fileOldPath = array_column($fileOld, 'file_path');
        // Decrypt từng phần tử
        $fileOldPath = array_map(function ($path) {
            return decryptString($path);
        }, $fileOldPath);

        $fileOldName = array_column($fileOld, 'file_name');

        $file = json_decode($quatrinhcongtac['files'], true);
        $filePath = array_column($file, 'file_path');

        $filePathDiff = array_diff($filePath, $fileOldPath);
        // resError('Test lỗi', 500, [
        //     'files_dinh_kem_old' => $fileOld,
        //     'fileOldPath' => $fileOldPath,
        //     'fileHDPath' => $fileHDPath,
        //     'filePathDiff ' => $filePathDiff
        // ]);
        foreach ($filePathDiff as $fpd) {
            //Xóa file đính kèm đã bị xóa
            $delete = $this->fileupload->delete($fpd);
            @unlink(FCPATH . $fpd);
        }

        $tempFildOld = [];
        foreach ($fileOld as $key => $file) {
            if (isset($file['file_path'])) {
                $tempFildOld[$key]['file_name'] = $file['file_name'];
                $tempFildOld[$key]['file_path'] = decryptString($file['file_path']);
                $tempFildOld[$key]['file_extension'] = $file['file_extension'];
                $tempFildOld[$key]['file_size'] = $file['file_size'];
                $tempFildOld[$key]['is_public'] = $is_public;
            }
        }

        $this->Hrm_qua_trinh_cong_tac_model->where('id_qua_trinh_cong_tac', $id)->update([
            'files' => json_encode($tempFildOld)
        ]);

        // Upload nhiều files mới
        $folderName = 'qua-trinh-cong-tac/' . date('Y') . '/' . date('m');
        $uploadedFiles = [];
        $files = commonRequest('files_dinh_kem') ? commonRequest('files_dinh_kem') : null;
        if (isset($files) && !empty($files)) {
            // resError('Test', 500, $files);

            foreach ($files['name'] as $key => $fileName) {
                if (in_array($fileName, $fileOldName)) {
                    continue;
                }

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
        // else {
        //     resError('Vui lòng chọn file hợp đồng!');
        // }

        // resError('Test lỗi', 500, $uploadedFiles);


        foreach ($uploadedFiles as $key => $file) {
            unset($uploadedFiles[$key]['success']);
            $uploadedFiles[$key]['file_size'] = exchangeFromKbToLargerCapacity($uploadedFiles[$key]['file_size']);
            $uploadedFiles[$key]['is_public'] = $is_public;
        }
        $finalFiles = array_merge($tempFildOld, $uploadedFiles);
        $data['files'] = json_encode($finalFiles);


        // Bắt đầu transaction
        $this->db->trans_start();

        // Cập nhật thông tin quá trình công tác
        $this->Hrm_qua_trinh_cong_tac_model->where('id_qua_trinh_cong_tac', $id)->update($data);

        // Lấy dữ liệu sau khi cập nhật
        $updated_quatrinhcongtac = $this->Hrm_qua_trinh_cong_tac_model->find($id);

        // Lấy thêm thông tin vị trí công việc và đơn vị
        $updated_quatrinhcongtac['ten_cong_viec'] = $this->Hrm_vi_tri_cong_viec_model->find($updated_quatrinhcongtac['id_vi_tri_cong_viec'])['ten_cong_viec'] ?? null;
        $updated_quatrinhcongtac['ten_don_vi'] = $this->E_don_vi_model->find($updated_quatrinhcongtac['id_don_vi'])['ten_don_vi'] ?? null;

        $files = $updated_quatrinhcongtac['files'] ? json_decode($updated_quatrinhcongtac['files'], true) : [];
        if (!empty($files)) {
            foreach ($files as &$file) {
                if (!empty($file)) {
                    $file['file_path'] = encryptString($file['file_path']);
                }
            }
        }
        unset($file);
        $updated_quatrinhcongtac['files'] = $files;

        $quatrinhcongtac['ten_cong_viec'] = $this->Hrm_vi_tri_cong_viec_model->find($quatrinhcongtac['id_vi_tri_cong_viec'])['ten_cong_viec'] ?? null;
        $quatrinhcongtac['ten_don_vi'] = $this->E_don_vi_model->find($quatrinhcongtac['id_don_vi'])['ten_don_vi'] ?? null;

        $changes = [
            [
                'field' => 'quatrinhcongtac',
                'field_name' => 'Quá trình công tác',
                'old_value' => [
                    'value' => 'Cập nhật',
                    'label' => 'Quá trình công tác: ' . $quatrinhcongtac['ten_cong_viec'] . ' - ' . $quatrinhcongtac['ten_don_vi']
                ],
                'new_value' => [
                    'value' => 'Cập nhật',
                    'label' => 'Quá trình công tác: ' . $updated_quatrinhcongtac['ten_cong_viec'] . ' - ' . $updated_quatrinhcongtac['ten_don_vi']
                ]
            ]
        ];
        $this->logEmployeeHistory($updated_quatrinhcongtac['id_nhan_vien'], 'Cập nhật quá trình công tác', $changes);

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

        $files = $quatrinhcongtac['files'] ? json_decode($quatrinhcongtac['files'], true) : null;
        if (!empty($files)) {
            foreach ($files as $key => &$file) {
                $file['file_path'] = encryptString($file['file_path']);
            }
        }
        unset($file);

        $quatrinhcongtac['files'] = $files;

        // Trả về dữ liệu
        resSuccess($quatrinhcongtac, 'Lấy thông tin thành công');
    }

    public function delete_post()
    {
        $ids = commonRequest('ids');
        $quatrinhcongtac = $this->Hrm_qua_trinh_cong_tac_model->whereIn('id_qua_trinh_cong_tac', $ids)->get();
        if (!$quatrinhcongtac) {
            resError('Không tìm thấy quá trình công tác này');
        }
        $this->db->trans_start();

        $changesArray = [];
        foreach ($quatrinhcongtac as $qtct) {
            $ten_cong_viec = $this->Hrm_vi_tri_cong_viec_model->find($qtct['id_vi_tri_cong_viec'])['ten_cong_viec'] ?? null;
            $ten_don_vi = $this->E_don_vi_model->find($qtct['id_don_vi'])['ten_don_vi'] ?? null;

            $changesArray[$qtct['id_nhan_vien']][] = [
                'field' => 'quatrinhcongtac',
                'field_name' => 'Quá trình công tác',
                'old_value' => [
                    'value' => 'Xoá',
                    'label' => 'Quá trình công tác: ' . $ten_cong_viec . ' - ' . $ten_don_vi
                ],
                'new_value' => null
            ];

            $files = json_decode($qtct['files'], true);
            if (!empty($files)) {
                foreach ($files as $file) {
                    if (!empty($file)) {
                        $fullPath = FCPATH . $file['file_path'];
                        if (file_exists($fullPath)) {
                            unlink($fullPath);
                        }
                    }
                }
            }
        }

        $this->Hrm_qua_trinh_cong_tac_model->whereIn('id_qua_trinh_cong_tac', $ids)->delete();

        foreach ($changesArray as $id_nv => $changes) {
            $this->logEmployeeHistory($id_nv, 'Xoá quá trình công tác', $changes);
        }

        $this->createLog('delete', 'Xóa quá trình công tác', $quatrinhcongtac, null, 'hrm_qua_trinh_cong_tac');
        $this->db->trans_commit();

        resSuccess(null, 'Xóa thành công');
    }
}
