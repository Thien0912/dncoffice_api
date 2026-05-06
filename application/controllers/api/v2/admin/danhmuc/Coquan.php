<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property E_co_quan_model $E_co_quan_model
 * @property Fileupload $fileupload
 */



class Coquan extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();

        // $this->permissionMiddleware();

        $this->load->helper('url');
        $this->load->model(['E_co_quan_model']);
    }

    public function index_get()
    {
        $id = commonRequest('id') ?? null;
        if($id){
            $result = $this->E_co_quan_model->find($id);
            if (!$result) {
                resError('Không tìm thấy dữ liệu', REST_Controller::HTTP_NOT_FOUND);
            }
            resSuccess($result);
        }

        $data = [
            'start' => commonRequest('start') ?? 0,
            'length' => commonRequest('length') ?? 10,
            'searchValue' => commonRequest('searchValue') ?? null,
            'order' => commonRequest('order') ?? [],
            'columns' => commonRequest('columns') ?? [],
        ];

        $data = $this->E_co_quan_model->getAll($data['start'], $data['length'], $data['searchValue'], $data['order'], $data['columns']);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function show_get($id) {
        $result = $this->E_co_quan_model->find($id);
        if (!$result) {
            resError('Không tìm thấy dữ liệu', REST_Controller::HTTP_NOT_FOUND);
        }
        resSuccess($result);
    }

    public function create_post()
    {
        $this->load->library(['Validator']);

        $data = [
            'ten_co_quan' => commonRequest('ten_co_quan') ? commonRequest('ten_co_quan') : null,
        ];

        $rules = [
            'ten_co_quan' => 'required',
        ];

        $customMessages = [
            'ten_co_quan.required' => 'Tên cơ quan bắt buộc nhập',
        ];
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();

        //Lưu văn bản đến
        $result = $this->E_co_quan_model->create($data);

        $this->db->trans_commit();

        resSuccess($result, 'Thêm cơ quan thành công');
    }

    public function update_post($id)
    {
        $this->load->library(['Validator']);

        $coQuan = $this->E_co_quan_model->find($id);
        if (!$coQuan) {
            resError('Không tìm thấy dữ liệu', REST_Controller::HTTP_NOT_FOUND);
        }

        $data = [
            'ten_co_quan' => commonRequest('ten_co_quan') ? commonRequest('ten_co_quan') : null,
        ];

        $rules = [
            'ten_co_quan' => 'required',
        ];

        $customMessages = [
            'ten_co_quan.required' => 'Tên cơ quan bắt buộc nhập',
        ];
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();
        $this->E_co_quan_model->where('id_co_quan', $id)->update($data);
        $result = $this->E_co_quan_model->find($id);
        $this->db->trans_commit();

        resSuccess($result, 'Cập nhật cơ quan thành công');
    }

    public function delete_post($id)
    {
        $coQuan = $this->E_co_quan_model->find($id);
        if (!$coQuan) {
            resError('Không tìm thấy dữ liệu', REST_Controller::HTTP_NOT_FOUND);
        }

        $isUsed = $this->db->select('*')
            ->from('e_vb_co_quan')
            ->where('id_co_quan', $id)
            ->get()
            ->result_array();

        if(!empty($isUsed)){
            resError('Cơ quan đã được sử dụng', REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->db->trans_start();
        $this->E_co_quan_model
            ->where('id_co_quan', $id)
            ->update(['deleted_at' => date('Y-m-d H:i:s')]);
        $this->db->trans_commit();

        resSuccess([], 'Xóa cơ quan thành công');
    }
}
