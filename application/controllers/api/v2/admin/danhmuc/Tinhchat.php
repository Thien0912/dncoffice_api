<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property E_tinh_chat_model $E_tinh_chat_model
 * @property Fileupload $fileupload
 */



class Tinhchat extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['E_tinh_chat_model']);
    }

    public function index_get()
    {
        $id = commonRequest('id') ?? null;
        if ($id) {
            $result = $this->E_tinh_chat_model->find($id);
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

        $data = $this->E_tinh_chat_model->getAll($data['start'], $data['length'], $data['searchValue'], $data['order'], $data['columns']);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function create_post()
    {
        $this->load->library(['Validator']);

        $data = [
            'ten_tinh_chat' => commonRequest('ten_tinh_chat') ? commonRequest('ten_tinh_chat') : null,
            'class_color' => commonRequest('class_color') ? commonRequest('class_color') : null,
        ];

        $rules = [
            'ten_tinh_chat' => 'required',
        ];

        $customMessages = [
            'ten_tinh_chat.required' => 'Tên tính chất bắt buộc nhập',
        ];
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();
        $result = $this->E_tinh_chat_model->create($data);
        $this->db->trans_commit();

        resSuccess($result);
    }

    public function update_post($id)
    {
        $this->load->library(['Validator']);

        $tinhChat = $this->E_tinh_chat_model->find($id);
        if (!$tinhChat) {
            resError('Không tìm thấy dữ liệu', REST_Controller::HTTP_NOT_FOUND);
        }

        $data = [
            'ten_tinh_chat' => commonRequest('ten_tinh_chat') ? commonRequest('ten_tinh_chat') : null,
            'class_color' => commonRequest('class_color') ? commonRequest('class_color') : null,
        ];

        $rules = [
            'ten_tinh_chat' => 'required',
        ];

        $customMessages = [
            'ten_tinh_chat.required' => 'Tên tính chất bắt buộc nhập',
        ];
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();
        $this->E_tinh_chat_model->where('id_tinh_chat', $id)->update($data);
        $result = $this->E_tinh_chat_model->find($id);
        $this->db->trans_commit();

        resSuccess($result);
    }

    public function delete_post($id)
    {
        $tinhChat = $this->E_tinh_chat_model->find($id);
        if (!$tinhChat) {
            resError('Không tìm thấy dữ liệu', REST_Controller::HTTP_NOT_FOUND);
        }

        $this->db->trans_start();
        $this->E_tinh_chat_model->delete($id);
        $this->db->trans_commit();

        resSuccess([], 'Xóa thành công');
    }
}
