<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_bang_cap_model $Hrm_bang_cap_model
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Fileupload $fileupload
 */



class Bangcap extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_bang_cap_model', 'Hrm_nhan_vien_model']);
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

        $data = $this->Hrm_bang_cap_model->getAll($start, $length, $searchValue, $orderBy, $searchKey);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }
    public function create_post()
    {
        $this->load->library(['Validator']);

        $data = [
            'id_nhan_vien' => commonRequest('id_nhan_vien') ? commonRequest('id_nhan_vien') : null,
            'tu_thang'     => commonRequest('tu_thang') ? commonRequest('tu_thang') : null,
            'den_thang'    => commonRequest('den_thang') ? commonRequest('den_thang') : null,
            'noi_dao_tao'  => commonRequest('noi_dao_tao') ? commonRequest('noi_dao_tao') : null,
            'chuyen_nganh' => commonRequest('chuyen_nganh') ? commonRequest('chuyen_nganh') : null,
            'trinh_do_dt'  => commonRequest('trinh_do_dt') ? commonRequest('trinh_do_dt') : null,
            'xep_loai_dt'  => commonRequest('xep_loai_dt') ? commonRequest('xep_loai_dt') : null,
            'file_path'    => null,
        ];

        // Optional file upload
        $file = commonRequest('file');
        if ($file && !empty($file['name'])) {
            $uploaded = $this->fileupload->upload($file, 'employees/bang_cap');
            if ($uploaded['success']) {
                $data['file_path']      = $uploaded['file_path'];
                $data['file_name']      = $uploaded['file_name'];
                $data['file_extension'] = $uploaded['file_extension'];
                $data['file_size']      = $uploaded['file_size'];
            }
        }
        // resSuccess($data);
        $nhanvien = $this->Hrm_nhan_vien_model->find($data['id_nhan_vien']);
        if (!$nhanvien) {
            resError('Không tìm thấy nhân viên này. Vui lòng tải lại trang');
        }
        $rules = [
            'tu_thang' => 'required|date',
            // 'den_thang' => 'required|date',
            'noi_dao_tao' => 'required',
            'chuyen_nganh' => 'required',
            'trinh_do_dt' => 'required',
            // 'xep_loai_dt' => 'required',
        ];

        $customMessages = [
            'tu_ngay.required' => 'Ngày thời gian bắt đầu học bắt buộc nhập',
            'tu_ngay.date' => 'Ngày thời gian bắt đầu học chưa đúng định dạng',
            // 'den_ngay.required' => 'Ngày thời gian kết thúc học bắt buộc nhập',
            // 'den_ngay.date' => 'Ngày thời gian kết thúc học chưa đúng định dạng',
            'noi_dao_tao.required' => 'Nơi đào tạo bắt buộc nhập',
            'chuyen_nganh.required' => 'Chuyên ngành bắt buộc nhập',
            'trinh_do_dt.required' => 'Trình độ đào tạo bắt buộc nhập',
            // 'xep_loai_dt.required' => 'Xếp loại bắt buộc chọn',
        ];

        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors(), 'Vui lòng kiểm tra lại các trường cần nhập');
        }

        $this->db->trans_start();

        // Thêm bằng cấp
        $result = $this->Hrm_bang_cap_model->create($data);
        $newId = $result['id_bang_cap'] ?? null;

        // Sync minh chứng nếu có file
        if ($newId && !empty($data['file_path'])) {
            $this->load->model('Hrm_minh_chung_model');
            $userId = $this->getUserLogin()['ql_nguoi_dung_id'] ?? null;
            $loai = $this->db->where('ma_loai', 'BANG_TN')->get('hrm_loai_minh_chung')->row_array();
            if ($loai) {
                $this->Hrm_minh_chung_model->syncFromModule(
                    $data['id_nhan_vien'],
                    $loai['id_loai_minh_chung'],
                    [[
                        'file_path'      => $data['file_path'],
                        'file_name'      => $data['file_name'] ?? basename($data['file_path']),
                        'file_extension' => $data['file_extension'] ?? null,
                        'file_size'      => $data['file_size'] ?? null,
                    ]],
                    $userId,
                    'hrm_nhan_vien_bang_cap',
                    $newId
                );
            }
        }

        $changes = [
            [
                'field' => 'bang_cap',
                'field_name' => 'Bằng cấp',
                'old_value' => null,
                'new_value' => [
                    'value' => 'Thêm mới',
                    'label' => 'Bằng/chuyên ngành: ' . $data['chuyen_nganh'] . ' - Nơi đào tạo: ' . $data['noi_dao_tao']
                ]
            ]
        ];
        $this->logEmployeeHistory($data['id_nhan_vien'], 'Thêm bằng cấp', $changes);

        $this->db->trans_commit();

        resSuccess($result, 'Thêm bằng cấp thành công');
    }

    public function show_get($id)
    {
        $data = $this->Hrm_bang_cap_model->find($id);
        if (!$data) {
            resError('Không tìm thấy thông tin bằng cấp này', REST_Controller::HTTP_NOT_FOUND);
        }
        resSuccess($data, 'Success', REST_Controller::HTTP_OK, true);
    }
    public function update_post($id)
    {
        $this->load->library(['Validator']);

        $data = [
            'id_nhan_vien' => commonRequest('id_nhan_vien') ? commonRequest('id_nhan_vien') : null,
            'tu_thang'     => commonRequest('tu_thang') ? commonRequest('tu_thang') : null,
            'den_thang'    => commonRequest('den_thang') ? commonRequest('den_thang') : null,
            'noi_dao_tao'  => commonRequest('noi_dao_tao') ? commonRequest('noi_dao_tao') : null,
            'chuyen_nganh' => commonRequest('chuyen_nganh') ? commonRequest('chuyen_nganh') : null,
            'trinh_do_dt'  => commonRequest('trinh_do_dt') ? commonRequest('trinh_do_dt') : null,
            'xep_loai_dt'  => commonRequest('xep_loai_dt') ? commonRequest('xep_loai_dt') : null,
        ];

        // Lấy dữ liệu cũ trước để có thể xóa file cũ nếu cần
        $oldData = $this->Hrm_bang_cap_model->where('id_bang_cap', $id)->first();
        if (!$oldData) {
            resError('Không tìm thấy thông tin bằng cấp này');
        }

        // Optional file upload (only update if new file provided)
        $file = commonRequest('file');
        if ($file && !empty($file['name'])) {
            $uploaded = $this->fileupload->upload($file, 'employees/bang_cap');
            if ($uploaded['success']) {
                // Xóa file cũ trên disk nếu có
                if (!empty($oldData['file_path'])) {
                    @unlink(FCPATH . $oldData['file_path']);
                }
                $data['file_path']      = $uploaded['file_path'];
                $data['file_name']      = $uploaded['file_name'];
                $data['file_extension'] = $uploaded['file_extension'];
                $data['file_size']      = $uploaded['file_size'];
            }
        } elseif (commonRequest('file_path') === null || commonRequest('file_path') === '') {
            // User đã xóa file đính kèm
            if (!empty($oldData['file_path'])) {
                @unlink(FCPATH . $oldData['file_path']);
            }
            $data['file_path']      = null;
            $data['file_name']      = null;
            $data['file_extension'] = null;
            $data['file_size']      = null;
        }
        // resError($data);
        $nhanvien = $this->Hrm_nhan_vien_model->find($data['id_nhan_vien']);
        if (!$nhanvien) {
            resError('Không tìm thấy nhân viên này. Vui lòng tải lại trang');
        }
        $rules = [
            'tu_thang' => 'required|date',
            // 'den_thang' => 'required|date',
            'noi_dao_tao' => 'required',
            'chuyen_nganh' => 'required',
            'trinh_do_dt' => 'required',
            // 'xep_loai_dt' => 'required',
        ];

        $customMessages = [
            'tu_ngay.required' => 'Ngày thời gian bắt đầu học bắt buộc nhập',
            'tu_ngay.date' => 'Ngày thời gian bắt đầu học chưa đúng định dạng',
            // 'den_ngay.required' => 'Ngày thời gian kết thúc học bắt buộc nhập',
            // 'den_ngay.date' => 'Ngày thời gian kết thúc học chưa đúng định dạng',
            'noi_dao_tao.required' => 'Nơi đào tạo bắt buộc nhập',
            'chuyen_nganh.required' => 'Chuyên ngành bắt buộc nhập',
            'trinh_do_dt.required' => 'Trình độ đào tạo bắt buộc nhập',
            // 'xep_loai_dt.required' => 'Xếp loại bắt buộc chọn',
        ];
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);
        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors(), 'Vui lòng kiểm tra lại các trường cần nhập');
        }

        $this->db->trans_start();

        // resSuccess($data);
        //Thêm bằng cấp
        $this->Hrm_bang_cap_model->where('id_bang_cap', $id)->update($data);
        $result = $this->Hrm_bang_cap_model->where('id_bang_cap', $id)->first();

        // Re-sync minh chứng nếu file thay đổi
        if (array_key_exists('file_path', $data)) {
            $this->load->model('Hrm_minh_chung_model');
            $userId = $this->getUserLogin()['ql_nguoi_dung_id'] ?? null;
            // Soft-delete minh chứng cũ của bản ghi này
            $this->Hrm_minh_chung_model->deleteByRef('hrm_nhan_vien_bang_cap', [$id], $userId);
            // Insert minh chứng mới nếu có file
            if (!empty($result['file_path'])) {
                $loai = $this->db->where('ma_loai', 'BANG_TN')->get('hrm_loai_minh_chung')->row_array();
                if ($loai) {
                    $this->Hrm_minh_chung_model->syncFromModule(
                        $result['id_nhan_vien'],
                        $loai['id_loai_minh_chung'],
                        [[
                            'file_path'      => $result['file_path'],
                            'file_name'      => $result['file_name'],
                            'file_extension' => $result['file_extension'],
                            'file_size'      => $result['file_size'],
                        ]],
                        $userId,
                        'hrm_nhan_vien_bang_cap',
                        $id
                    );
                }
            }
        }

        $changes = [
            [
                'field' => 'bang_cap',
                'field_name' => 'Bằng cấp',
                'old_value' => [
                    'value' => 'Cập nhật',
                    'label' => 'Bằng/chuyên ngành: ' . $oldData['chuyen_nganh'] . ' - Nơi đào tạo: ' . $oldData['noi_dao_tao']
                ],
                'new_value' => [
                    'value' => 'Cập nhật',
                    'label' => 'Bằng/chuyên ngành: ' . $data['chuyen_nganh'] . ' - Nơi đào tạo: ' . $data['noi_dao_tao']
                ]
            ]
        ];
        $this->logEmployeeHistory($oldData['id_nhan_vien'], 'Cập nhật bằng cấp', $changes);

        $this->db->trans_commit();

        resSuccess($result, 'Cập nhật bằng cấp thành công');
    }

    public function delete_post()
    {
        // Tìm thông tin bằng cấp theo ID
        $ids = commonRequest('ids');
        $bangcap = $this->Hrm_bang_cap_model->whereIn('id_bang_cap', $ids)->get();
        if (count($ids) != count($bangcap)) {
            resError('Không tìm thấy thông tin bằng cấp này.', REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        // Bắt đầu transaction
        $this->db->trans_start();

        // Xóa file vật lý đính kèm (nếu có)
        foreach ($bangcap as $item) {
            if (!empty($item['file_path'])) {
                @unlink(FCPATH . $item['file_path']);
            }
        }

        // Cascade: soft-delete minh chứng liên kết qua ref_table + ref_id
        $this->load->model('Hrm_minh_chung_model');
        $userId = $this->getUserLogin()['ql_nguoi_dung_id'] ?? null;
        $minhChungFiles = $this->Hrm_minh_chung_model->getActiveByRef('hrm_nhan_vien_bang_cap', $ids);
        foreach ($minhChungFiles as $mc) {
            // Xóa file vật lý của minh chứng nếu chưa bị xóa ở trên
            if (!empty($mc['file_path'])) {
                @unlink(FCPATH . $mc['file_path']);
            }
        }
        $this->Hrm_minh_chung_model->deleteByRef('hrm_nhan_vien_bang_cap', $ids, $userId);

        // Xóa thông tin bằng cấp (soft-delete)
        $this->Hrm_bang_cap_model->whereIn('id_bang_cap', $ids)->delete();

        // Tạo log xóa
        $this->createLog('delete', 'Xóa thông tin bằng cấp', $bangcap, null, 'hrm_bang_cap');

        $changes = [];
        $id_nhan_vien = $bangcap[0]['id_nhan_vien'] ?? null;
        if ($id_nhan_vien) {
            foreach($bangcap as $item) {
                $changes[] = [
                    'field' => 'bang_cap',
                    'field_name' => 'Bằng cấp',
                    'old_value' => [
                        'value' => 'Xoá',
                        'label' => 'Bằng/chuyên ngành: ' . $item['chuyen_nganh'] . ' - Nơi đào tạo: ' . $item['noi_dao_tao']
                    ],
                    'new_value' => null
                ];
            }
            $this->logEmployeeHistory($id_nhan_vien, 'Xoá bằng cấp', $changes);
        }

        // Commit transaction
        $this->db->trans_commit();

        // Trả về kết quả thành công
        resSuccess(null, 'Xóa thông tin bằng cấp thành công');
    }
}
