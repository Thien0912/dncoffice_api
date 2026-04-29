<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property E_loai_model $E_loai_model
 * @property Fileupload $fileupload
 */



class Loai extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['E_loai_model']);
    }

    public function index_get()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Tài khoản không hợp lệ', REST_Controller::HTTP_UNAUTHORIZED);
        }

        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;

        $data = $this->E_loai_model->getAll($start, $length, $searchValue, [], [], $auth);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function create_post()
    {
        $this->load->library(['Validator']);

        $data = [
            'ten_loai' => commonRequest('ten_loai') ? commonRequest('ten_loai') : null,
            'tien_to' => commonRequest('tien_to') ? commonRequest('tien_to') : null,
            'hau_to' => commonRequest('hau_to') ? commonRequest('hau_to') : null,
        ];

        $rules = [
            'ten_loai' => 'required',
            'tien_to' => 'required',
            'hau_to' => 'required',
        ];

        $customMessages = [
            'ten_loai.required' => 'Tên loại bắt buộc nhập',
            'tien_to.required' => 'Tiền tố loại bắt buộc nhập',
            'hau_to.required' => 'Hậu tố loại bắt buộc nhập',
        ];
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();

        //Lưu văn bản đến
        $result = $this->E_loai_model->create($data);

        $this->db->trans_commit();

        resSuccess($result);
    }
}
