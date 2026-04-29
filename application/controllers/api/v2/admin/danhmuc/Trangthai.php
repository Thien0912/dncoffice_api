<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property E_trang_thai_model $E_trang_thai_model
 * @property Fileupload $fileupload
 */



class Trangthai extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['E_trang_thai_model']);
    }

    public function index_get()
    {

        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;

        $data = $this->E_trang_thai_model->getAll($start, $length, $searchValue);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function create_post()
    {
        $this->load->library(['Validator']);

        $data = [
            'ten_trang_thai' => commonRequest('ten_trang_thai') ? commonRequest('ten_trang_thai') : null,
            'loai' => commonRequest('loai') ? commonRequest('loai') : null,
            'ma_trang_thai' => commonRequest('ma_trang_thai') ? mb_strtoupper(commonRequest('ma_trang_thai'), 'UTF-8')  : null,
        ];

        $rules = [
            'ten_trang_thai' => 'required',
            'loai' => 'required|integer',
            'ma_trang_thai' => 'required',
        ];

        $customMessages = [
            'ten_trang_thai.required' => 'Tên trạng thái bắt buộc nhập',
            'loai.required' => 'Loại trạng thái bắt buộc nhập',
            'ma_trang_thai.required' => 'Mã trạng thái bắt buộc nhập',
        ];
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();

        //Lưu văn bản đến
        $result = $this->E_trang_thai_model->create($data);

        $this->db->trans_commit();

        resSuccess($result);
    }
}
