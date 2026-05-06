<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property E_hinh_thuc_model $E_hinh_thuc_model
 * @property Fileupload $fileupload
 */



class Hinhthuc extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['E_hinh_thuc_model']);
    }

    public function index_get()
    {
        $id = commonRequest('id') ?? null;
        if($id){
            $result = $this->E_hinh_thuc_model->find($id);
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

        $data = $this->E_hinh_thuc_model->getAll($data['start'], $data['length'], $data['searchValue'], $data['order'], $data['columns']);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function show_get($id) {
        $result = $this->E_hinh_thuc_model->find($id);
        if (!$result) {
            resError('Không tìm thấy dữ liệu', REST_Controller::HTTP_NOT_FOUND);
        }
        resSuccess($result);
    }

    public function create_post()
    {
        $this->load->library(['Validator']);

        $data = [
            'ten_hinh_thuc' => commonRequest('ten_hinh_thuc') ? commonRequest('ten_hinh_thuc') : null,
            'ma_hinh_thuc' => commonRequest('ma_hinh_thuc') ? commonRequest('ma_hinh_thuc') : null,
        ];

        $rules = [
            'ten_hinh_thuc' => 'required',
        ];

        $customMessages = [
            'ten_hinh_thuc.required' => 'Tên hình thức bắt buộc nhập',
        ];
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();
        $result = $this->E_hinh_thuc_model->create($data);
        $this->db->trans_commit();

        resSuccess($result);
    }

    public function update_post($id)
    {
        $this->load->library(['Validator']);

        $hinhThuc = $this->E_hinh_thuc_model->find($id);
        if (!$hinhThuc) {
            resError('Không tìm thấy dữ liệu', REST_Controller::HTTP_NOT_FOUND);
        }

        $data = [
            'ten_hinh_thuc' => commonRequest('ten_hinh_thuc') ? commonRequest('ten_hinh_thuc') : null,
            'ma_hinh_thuc' => commonRequest('ma_hinh_thuc') ? commonRequest('ma_hinh_thuc') : null,
        ];

        // Only update provided fields if needed, but usually we send all editable fields
        // Since input might be null if not provided, we should check what's being sent or just use the ternary above which keeps null.
        // If the FE sends undefined for unchanged fields, they might be missed. But usually standard is full submit.
        // Let's stick to standard full submit for simplicity unless it's PATCH.

        $rules = [
            'ten_hinh_thuc' => 'required',
        ];

        $customMessages = [
            'ten_hinh_thuc.required' => 'Tên hình thức bắt buộc nhập',
        ];
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();
        $this->E_hinh_thuc_model->where('id_hinh_thuc', $id)->update($data);
        $result = $this->E_hinh_thuc_model->find($id);
        $this->db->trans_commit();

        resSuccess($result);
    }

    public function delete_post($id)
    {
        $hinhThuc = $this->E_hinh_thuc_model->find($id);
        if (!$hinhThuc) {
            resError('Không tìm thấy dữ liệu', REST_Controller::HTTP_NOT_FOUND);
        }

        $this->db->trans_start();
        $this->E_hinh_thuc_model->where('id_hinh_thuc', $id)->update(['deleted_at' => date('Y-m-d H:i:s')]);
        $this->db->trans_commit();

        resSuccess([], 'Xóa thành công');
    }
}
