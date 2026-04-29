<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

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
        $this->load->model(['Hrm_chung_chi_model', 'Hrm_nhan_vien_model', 'Hrm_minh_chung_model']);
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
        $fileUpload = commonRequest('files_dinh_kem') ? commonRequest('files_dinh_kem') : [];
        if (isset($fileUpload) && !empty($fileUpload)) {
            $files = $fileUpload; // Tên input từ form                        

            foreach ($files['name'] as $key => $fileName) {
                // Chuẩn bị dữ liệu cho từng file
                $file = [
                    'name' => $files['name'][$key],
                    'type' => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error' => $files['error'][$key],
                    'size' => $files['size'][$key],
                ];
                $result = $this->fileupload->upload($file, $folderName);
                if ($result['success']) {
                    $uploadedFiles[] = $result;
                }
            }
        }

        // Lưu bản gốc cho sync (file_size còn bytes)
        $rawFilesForSync = $uploadedFiles;

        foreach ($uploadedFiles as $key => $file) {
            unset($uploadedFiles[$key]['success']);
            $uploadedFiles[$key]['file_size'] = exchangeFromKbToLargerCapacity($uploadedFiles[$key]['file_size']);
            $uploadedFiles[$key]['is_public'] = 1;
        }
        $data['files'] = json_encode($uploadedFiles);
        $this->db->trans_start();

        // Thêm chứng chỉ
        $result = $this->Hrm_chung_chi_model->create($data);
        $newFiles = $result['files'];
        if (!empty($newFiles)) {
            $newFiles = json_decode($newFiles, true);
            foreach ($newFiles as $key => &$file) {
                $file['file_path'] = encryptString($file['file_path']);
            }
            $result['files'] = $newFiles;
        }

        // Sync tất cả chứng chỉ vào bảng minh chứng
        $this->_syncAllChungChiToMinhChung($data['id_nhan_vien']);

        $changes = [
            [
                'field' => 'chung_chi',
                'field_name' => 'Chứng chỉ',
                'old_value' => null,
                'new_value' => [
                    'value' => 'Thêm mới',
                    'label' => 'Chứng chỉ: ' . $data['ten_chung_chi'] . ' - Nơi cấp: ' . $data['noi_cap']
                ]
            ]
        ];
        $this->logEmployeeHistory($data['id_nhan_vien'], 'Thêm chứng chỉ', $changes);

        $this->db->trans_commit();

        resSuccess($result, 'Thêm chứng chỉ thành công');
    }

    public function delete_post()
    {
        $ids = commonRequest('ids');

        $chungchi = $this->Hrm_chung_chi_model->whereIn('id_chung_chi', $ids)->get();

        $countIds = count($ids);
        $countChungChi = count($chungchi);
        if ($countIds != $countChungChi) {
            resError('Không tìm thấy tất cả chứng chỉ này', REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        // Bắt đầu transaction
        $this->db->trans_start();

        foreach ($chungchi as $cc) {
            $files = json_decode($cc['files'], true);
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

        // Cascade: soft-delete minh chứng liên kết qua ref_table + ref_id
        $userId = $this->getUserLogin()['ql_nguoi_dung_id'] ?? null;
        $this->Hrm_minh_chung_model->deleteByRef('hrm_chung_chi', $ids, $userId);

        // Xóa chứng chỉ trong database
        $this->Hrm_chung_chi_model->whereIn('id_chung_chi', $ids)->delete();

        // Sync: re-sync tất cả chứng chỉ còn lại vào minh chứng
        $employeeIds = array_unique(array_column($chungchi, 'id_nhan_vien'));
        foreach ($employeeIds as $empId) {
            $this->_syncAllChungChiToMinhChung($empId);
        }

        // Tạo log xóa
        $this->createLog('delete', 'Xóa chứng chỉ', $chungchi, null, 'hrm_chung_chi');

        $changes = [];
        $id_nhan_vien = $chungchi[0]['id_nhan_vien'] ?? null;
        if ($id_nhan_vien) {
            foreach ($chungchi as $item) {
                $changes[] = [
                    'field' => 'chung_chi',
                    'field_name' => 'Chứng chỉ',
                    'old_value' => [
                        'value' => 'Xoá',
                        'label' => 'Chứng chỉ: ' . $item['ten_chung_chi'] . ' - Nơi cấp: ' . $item['noi_cap']
                    ],
                    'new_value' => null
                ];
            }
            $this->logEmployeeHistory($id_nhan_vien, 'Xoá chứng chỉ', $changes);
        }

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
        if ($chungchi['files']) {
            $files = json_decode($chungchi['files'], true);
            if (!empty($files)) {
                foreach ($files as &$file) {
                    $file['file_path'] = encryptString($file['file_path']);
                }
            }
            unset($file);
            $chungchi['files'] = $files;
        }

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
        $is_public = 1;
        $data = $this->getValidationRules()['data'];

        $fileOld = commonRequest('old_files');

        if (is_string($fileOld)) {
            $fileOldDecoded = json_decode($fileOld, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $fileOld = $fileOldDecoded;
            } else {
                // json_decode lỗi → xử lý lỗi nếu cần
                $fileOld = [];
            }
        }

        // Normalize $fileOld: must be array (handles null, string, already-array)
        if (is_null($fileOld)) {
            $fileOld = [];
        }

        $fileOldPath = array_column($fileOld, 'file_path');
        $fileOldPath = array_map(function ($path) {
            return decryptString($path);
        }, $fileOldPath);
        $fileOldName = array_column($fileOld, 'file_name');


        $file = json_decode($chungchi['files'], true) ?? [];
        $filePath = array_column($file, 'file_path');

        $filePathDiff = array_diff($filePath, $fileOldPath);

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

        $this->Hrm_chung_chi_model->where('id_chung_chi', $id)->update([
            'files' => json_encode($tempFildOld)
        ]);

        // Upload nhiều files mới
        $folderName = 'certificate/' . date('Y') . '/' . date('m');
        $uploadedFiles = [];
        $files = commonRequest('files_dinh_kem') ? commonRequest('files_dinh_kem') : null;
        if (isset($files) && !empty($files)) {
            // resError('Test', 500, [
            //     'fileOld' => $fileOld,
            //     'files' => $files,
            // ]);

            foreach ($files['name'] as $key => $fileName) {
                if (in_array($fileName, $fileOldName)) {
                    continue;
                }

                // Chuẩn bị dữ liệu cho từng file
                $file = [
                    'name' => $files['name'][$key],
                    'type' => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error' => $files['error'][$key],
                    'size' => $files['size'][$key],
                ];
                $result = $this->fileupload->upload($file, $folderName);
                if ($result['success']) {
                    $uploadedFiles[] = $result;
                }
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

        // Cập nhật
        $this->db->trans_start();
        $this->Hrm_chung_chi_model->where('id_chung_chi', $id)->update($data);

        $chungchi = $this->Hrm_chung_chi_model->find($id);
        if ($chungchi['files']) {
            $files = json_decode($chungchi['files'], true);
            if (!empty($files)) {
                foreach ($files as &$file) {
                    $file['file_path'] = encryptString($file['file_path']);
                }
            }
            unset($file);
            $chungchi['files'] = $files;
        }
        // Sync tất cả chứng chỉ vào bảng minh chứng
        $this->_syncAllChungChiToMinhChung($data['id_nhan_vien']);

        $this->createLog('update', 'Cập nhật chứng chỉ', $chungchi, $data, 'hrm_chung_chi');

        $changes = [
            [
                'field' => 'chung_chi',
                'field_name' => 'Chứng chỉ',
                'old_value' => [
                    'value' => 'Cập nhật',
                    'label' => 'Chứng chỉ: ' . $chungchi['ten_chung_chi'] . ' - Nơi cấp: ' . $chungchi['noi_cap']
                ],
                'new_value' => [
                    'value' => 'Cập nhật',
                    'label' => 'Chứng chỉ: ' . $data['ten_chung_chi'] . ' - Nơi cấp: ' . $data['noi_cap']
                ]
            ]
        ];
        $this->logEmployeeHistory($chungchi['id_nhan_vien'], 'Cập nhật chứng chỉ', $changes);

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

    /**
     * Lấy ID loại minh chứng theo mã
     */
    private function _getLoaiMinhChungId($maLoai)
    {
        $loai = $this->db->where('ma_loai', $maLoai)->get('hrm_loai_minh_chung')->row_array();
        return $loai ? $loai['id_loai_minh_chung'] : null;
    }

    /**
     * Re-sync TẤT CẢ chứng chỉ của nhân viên vào bảng minh chứng
     * Xóa hết minh chứng CHUNG_CHI cũ → fetch tất cả chứng chỉ → insert lại
     */
    private function _syncAllChungChiToMinhChung($idNhanVien)
    {
        $loaiId = $this->_getLoaiMinhChungId('CHUNG_CHI');
        if (!$loaiId)
            return;

        $userId = $this->getUserLogin()['ql_nguoi_dung_id'];

        // 1. Xóa hết minh chứng CHUNG_CHI cũ của nhân viên
        $this->Hrm_minh_chung_model->deleteSyncedByNhanVienAndLoai($idNhanVien, $loaiId, $userId);

        // 2. Fetch tất cả chứng chỉ còn active của nhân viên
        $allChungChi = $this->Hrm_chung_chi_model
            ->where('id_nhan_vien', $idNhanVien)
            ->get();

        // 3. Insert lại tất cả files
        foreach ($allChungChi as $cc) {
            $files = json_decode($cc['files'], true);
            if (empty($files))
                continue;

            $this->Hrm_minh_chung_model->syncFromModule(
                $idNhanVien,
                $loaiId,
                $files,
                $userId
            );
        }
    }
}
