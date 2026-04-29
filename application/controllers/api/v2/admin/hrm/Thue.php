<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_nhan_vien_thue_model $Hrm_nhan_vien_thue_model
 * @property Fileupload $fileupload
 */



class Thue extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_nhan_vien_thue_model']);
        $this->load->library(['Validator', 'Fileupload']);
    }


    public function create_post()
    {
        $validator = new Validator();

        $thue_suat = commonRequest('thue_suat') ? commonRequest('thue_suat') : null;
        if (!$thue_suat) {
            $validator->addError('', 'thue_suat', 'Thuế suất bắt buộc nhập');
            resBadrequest($validator->errors());
        }

        $thue_thu_nhap = commonRequest('thue_thu_nhap') ? commonRequest('thue_thu_nhap') : null;
        if (!$thue_thu_nhap) {
            $validator->addError('', 'thue_thu_nhap', 'Thuế thu nhập bắt buộc nhập');
            resBadrequest($validator->errors());
        }

        $thu_nhap_chiu_thue = commonRequest('thu_nhap_chiu_thue') ? commonRequest('thu_nhap_chiu_thue') : null;
        if (!$thu_nhap_chiu_thue) {
            $validator->addError('', 'thu_nhap_chiu_thue', 'Thu nhập chịu thuế bắt buộc nhập');
            resBadrequest($validator->errors());
        }

        $thang_tinh_thue = commonRequest('thang_tinh_thue') ? commonRequest('thang_tinh_thue') : null;
        if (!$thang_tinh_thue) {
            $validator->addError('', 'thang_tinh_thue', 'Tháng tính thuế bắt buộc nhập');
            resBadrequest($validator->errors());
        }

        $data = [
            'id_nhan_vien' => commonRequest('id_nhan_vien') ? commonRequest('id_nhan_vien') : null,
            'thue_suat' => $thue_suat,
            'thue_thu_nhap' => $thue_thu_nhap,
            'thu_nhap_chiu_thue' => $thu_nhap_chiu_thue,
            'thang_tinh_thue' => $thang_tinh_thue,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $rules = [
            'id_nhan_vien' => 'required|integer',
            'thue_suat' => 'required|numeric',
            'thue_thu_nhap' => 'required|numeric',
            'thu_nhap_chiu_thue' => 'required|numeric',
            'thang_tinh_thue' => 'required|date',
        ];

        $customMessages = [
            'id_nhan_vien.required' => 'Vui lòng chọn nhân viên',
            'thue_suat.required' => 'Vui lòng nhập thuế suất',
            'thue_suat.numeric' => 'Thuế suất phải là số',
            'thu_nhap_chiu_thue.required' => 'Vui lòng nhập thu nhập chịu thuế',
            'thu_nhap_chiu_thue.numeric' => 'Thu nhập chịu thuế phải là số',
            'thue_thu_nhap.required' => 'Vui lòng nhập thuế thu nhập',
            'thue_thu_nhap.numeric' => 'Thuế thu nhập phải là số',
            'thang_tinh_thue.required' => 'Vui lòng nhập tháng tính thuế',
            'thang_tinh_thue.date' => 'Tháng tính thuế không đúng định dạng',
        ];

        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();

        $thue = $this->Hrm_nhan_vien_thue_model->create($data);

        //create log
        $this->createLog('create', 'Tạo mới thuế nhân viên', null, $thue, 'hrm_nhan_vien_thue');
        $this->db->trans_commit();
        resSuccess($thue, 'Thêm thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }
}
