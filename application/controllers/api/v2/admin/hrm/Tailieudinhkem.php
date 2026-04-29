<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 * @property Hrm_nhan_vien_tai_lieu_dinh_kem_model $Hrm_nhan_vien_tai_lieu_dinh_kem_model
 * @property Fileupload $fileupload
 */



class Tailieudinhkem extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Ql_nguoi_dung_model', 'Hrm_nhan_vien_tai_lieu_dinh_kem_model']);
        $this->load->library(['Validator', 'Fileupload']);
    }
    public function index_get() {}

    public function create_post()
    {
        $validator = new Validator();

        $data = [
            'ghi_chu' => commonRequest('tldk_ghi_chu') ? commonRequest('tldk_ghi_chu') : null,
            'cho_phep_nv_tai' => commonRequest('tldk_cho_phep_nv_tai') ? commonRequest('tldk_cho_phep_nv_tai') : null,
            'id_nhan_vien' => commonRequest('id_nhan_vien') ? commonRequest('id_nhan_vien') : null
        ];
        $rules = [
            'id_nhan_vien' => 'required|integer',
        ];
        $customMessages = [
            'id_nhan_vien.required' => 'Vui lòng chọn nhân viên',
        ];

        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        // Upload file_hop_dong
        $folderName = 'tai-lieu-dinh-kem/' . date('Y') . '/' . date('m');
        if (isset($_FILES['file_tai_lieu_dinh_kem'])) {
            $uploadedFile = $this->fileupload->upload($_FILES['file_tai_lieu_dinh_kem'], $folderName);
            if (!$uploadedFile['success']) {
                resBadrequest([
                    'file_tai_lieu_dinh_kem' => [
                        'Không thể tải lên file'
                    ]
                ]);
            } else {
                $this->load->helper('file');
                $data['file_path'] = $uploadedFile['file_path'];
                $data['file_name'] = $uploadedFile['file_name'];
                $data['file_extension'] = get_mime_by_extension($_FILES['file_tai_lieu_dinh_kem']['name']);
            }
        } else {
            resError('Không tìm thấy file tải lên');
        }

        $this->db->trans_start();

        $tailieudinhkem = $this->Hrm_nhan_vien_tai_lieu_dinh_kem_model->create($data);
        $tailieudinhkem['count'] = $this->Hrm_nhan_vien_tai_lieu_dinh_kem_model
            ->where('id_nhan_vien', commonRequest('id_nhan_vien'))
            ->count();

        //create log
        $this->createLog('create', 'Tạo mới tài liệu đính kèm', null, $tailieudinhkem, 'hrm_nhan_vien_tai_lieu_dinh_kem');
        $this->db->trans_commit();
        resSuccess($tailieudinhkem, 'Thêm thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }

    public function show_get($id)
    {
        $item = $this->Hrm_nhan_vien_tai_lieu_dinh_kem_model->find($id);

        if (!$item) resError('Không tìm thấy tài liệu đính kèm', REST_Controller::HTTP_NOT_FOUND);

        resSuccess($item);
    }

    public function update_post($id)
    {
        $dataOld = $this->Hrm_nhan_vien_tai_lieu_dinh_kem_model->find($id);
        if (!$dataOld) {
            resError('Tài liệu đính kèm khồng tồn tại', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }

        $validator = new Validator();

        $data = [
            'ghi_chu' => commonRequest('tldk_ghi_chu') ? commonRequest('tldk_ghi_chu') : null,
            'cho_phep_nv_tai' => commonRequest('tldk_cho_phep_nv_tai') ? commonRequest('tldk_cho_phep_nv_tai') : null,
            'id_nhan_vien' => commonRequest('id_nhan_vien') ? commonRequest('id_nhan_vien') : null

        ];

        $rules = [
            'id_nhan_vien' => 'required|integer',
        ];
        $customMessages = [
            'id_nhan_vien.required' => 'Vui lòng chọn nhân viên',
        ];

        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        // Upload file_hop_dong
        $folderName = 'tai-lieu-dinh-kem/' . date('Y') . '/' . date('m');
        if (isset($_FILES['file_tai_lieu_dinh_kem'])) {
            $uploadedFile = $this->fileupload->upload($_FILES['file_tai_lieu_dinh_kem'], $folderName);
            if (!$uploadedFile['success']) {
                resBadrequest([
                    'file_tai_lieu_dinh_kem' => [
                        'Không thể tải lên file'
                    ]
                ]);
            } else {
                $this->load->helper('file');
                $data['file_path'] = $uploadedFile['file_path'];
                $data['file_name'] = $uploadedFile['file_name'];
                $data['file_extension'] = get_mime_by_extension($_FILES['file_tai_lieu_dinh_kem']['name']);

                $this->fileupload->delete($dataOld['file_path']);
            }
        }

        $this->db->trans_start();

        $this->Hrm_nhan_vien_tai_lieu_dinh_kem_model->where('id_tai_lieu_dinh_kem', $id)->update($data);
        $datanNew = $this->Hrm_nhan_vien_tai_lieu_dinh_kem_model->find($id);

        //create log
        $this->createLog('update', 'Cập nhật tài liệu đính kèm', $dataOld, $datanNew, 'hrm_nhan_vien_tai_lieu_dinh_kem');
        $this->db->trans_commit();
        resSuccess($datanNew, 'Cập nhật thành công', REST_INSTANCE_Controller::HTTP_OK);
    }
}
