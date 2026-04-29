<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_vi_tri_cong_viec_model $Hrm_vi_tri_cong_viec_model
 * @property Dm_ton_giao_model $Dm_ton_giao_model
 * @property Fileupload $fileupload
 */



class Vitricongviec extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model(['Hrm_vi_tri_cong_viec_model']);
        $this->load->model(['Dm_ton_giao_model']);
    }

    public function index_get()
    {


        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;

        $data = $this->Hrm_vi_tri_cong_viec_model->getAll($start, $length, $searchValue);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function create_post()
    {
        $this->load->library(['Validator']);

        $data = [
            'ten_cong_viec' => commonRequest('ten_cong_viec') ? commonRequest('ten_cong_viec') : null,
        ];

        $rules = [
            'ten_cong_viec' => 'required',
        ];

        $customMessages = [
            'ten_cong_viec.required' => 'Tên công việc bắt buộc nhập',
        ];
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();

        //Lưu văn bản đến
        $result = $this->Hrm_vi_tri_cong_viec_model->create($data);

        $this->db->trans_commit();

        resSuccess($result);
    }
}
