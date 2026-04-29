<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property E_khoi_co_quan_model $E_khoi_co_quan_model
 * @property Fileupload $fileupload
 */



class Khoicoquan extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['E_khoi_co_quan_model']);
    }

    public function index_get()
    {

        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;

        $data = $this->E_khoi_co_quan_model->getAll($start, $length, $searchValue);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function create_post()
    {
        $this->load->library(['Validator']);

        $data = [
            'ten_khoi_co_quan' => commonRequest('ten_khoi_co_quan') ? commonRequest('ten_khoi_co_quan') : null,
        ];

        $rules = [
            'ten_khoi_co_quan' => 'required',
        ];

        $customMessages = [
            'ten_khoi_co_quan.required' => 'Tên khối cơ quan bắt buộc nhập',
        ];
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();

        //Lưu văn bản đến
        $result = $this->E_khoi_co_quan_model->create($data);

        $this->db->trans_commit();

        resSuccess($result, 'Thêm khối cơ quan thành công');
    }
}
