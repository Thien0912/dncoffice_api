<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_vi_tri_cong_viec_model $Hrm_vi_tri_cong_viec_model
 */



class Vitricongviec extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model(['Hrm_vi_tri_cong_viec_model']);
    }

    public function index_get()
    {
        $data = [];
        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;

        $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
            'order' => commonRequest('order'),
            'columns' => commonRequest('columns')
        ] : [];

        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];

        $data = $this->Hrm_vi_tri_cong_viec_model->getAll($start, $length, $searchValue, $orderBy, $searchKey);
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

        $this->createLog(
            'create',
            'Tạo mới vị trí công việc',
            null,
            $result,
            'hrm_vi_tri_cong_viec'
        );


        $this->db->trans_commit();

        resSuccess($result, 'Thêm mới vị trí công việc thành công!');
    }

    public function update_post($id)
    {
        $this->load->library(['Validator']);

        $oldData = $this->Hrm_vi_tri_cong_viec_model->find($id);

        $data = [
            'ten_cong_viec' => commonRequest('ten_cong_viec_edit') ? commonRequest('ten_cong_viec_edit') : null,
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
        $result = $this->Hrm_vi_tri_cong_viec_model
            ->where('id_vi_tri_cong_viec', $id)
            ->update($data);

        $this->createLog(
            'create',
            'Chỉnh sửa vị trí công việc',
            $oldData,
            $result,
            'hrm_vi_tri_cong_viec'
        );


        $this->db->trans_commit();

        resSuccess($result, 'Chỉnh sửa vị trí công việc thành công!');
    }
}
