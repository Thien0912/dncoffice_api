<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_phuc_loi_model $Hrm_phuc_loi_model
 */



class Phucloi extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_phuc_loi_model']);
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

        $data = $this->Hrm_phuc_loi_model->getAll($start, $length, $searchValue, $orderBy, $searchKey);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }
    public function create_post()
    {
        $this->load->library(['Validator']);

        $data = [
            'ten_phuc_loi' => commonRequest('ten_phuc_loi') ? commonRequest('ten_phuc_loi') : null,
            'mo_ta' => commonRequest('mo_ta') ? commonRequest('mo_ta') : null,
        ];

        $rules = [
            'ten_phuc_loi' => 'required',
        ];
        $customMessages = [
            'ten_phuc_loi.required' => 'Tên phúc lợi bắt buộc nhập',
        ];
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }
        $this->db->trans_start();

        //Lưu phúc lợi
        $result = $this->Hrm_phuc_loi_model->create($data);

        $this->db->trans_commit();

        resSuccess($result, 'Tạo phúc lợi thành công');
    }
}
