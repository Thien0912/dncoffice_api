<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

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
        $publicSegments = [
            'vitricongviec.index'
        ];

        if (!$this->inSegment($publicSegments)) {
            $this->permissionMiddleware();
        }
        $this->load->helper('url');
        $this->load->model(['Hrm_vi_tri_cong_viec_model']);
    }

    public function index_get()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Tài khoản không hợp lệ', REST_Controller::HTTP_UNAUTHORIZED);
        }

        // Tích hợp show theo id
        if (commonRequest('show') && commonRequest('id_vi_tri_cong_viec')) {
            $vitri = $this->Hrm_vi_tri_cong_viec_model->find(commonRequest('id_vi_tri_cong_viec'));
            if (!$vitri) {
                resError('Không tìm thấy vị trí công việc', REST_Controller::HTTP_NOT_FOUND);
            }
            resSuccess($vitri);
        }
        // END Tích hợp show theo id

        $searchKey = [];
        if (is_string(commonRequest('searchKey'))) {
            $searchKey = json_decode(commonRequest('searchKey'), true);
        }

        $data = [
            'start' => commonRequest('start') ?? 0,
            'length' => commonRequest('length') ?? 10,
            'searchValue' => commonRequest('searchValue') ?? null,
            'order' => commonRequest('order') ?? [],
            'columns' => commonRequest('columns') ?? [],
            'searchKey' => $searchKey,
        ];

        $data = $this->Hrm_vi_tri_cong_viec_model->getAll($data['start'], $data['length'], $data['searchValue'], $data['order']);

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
            'ten_cong_viec_en' => commonRequest('ten_cong_viec_en') ? commonRequest('ten_cong_viec_en') : null,
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

        $result = $this->Hrm_vi_tri_cong_viec_model->create($data);
        $this->createLog('Create', 'Thêm vị trí công việc: ' . $result['ten_cong_viec'], NULL, $result, 'hrm_vi_tri_cong_viec');

        $this->db->trans_commit();

        resSuccess($result);
    }

    public function update_post($id)
    {
        $vitri = $this->Hrm_vi_tri_cong_viec_model->find($id);
        if (!$vitri) {
            resError('Không tìm thấy vị trí công việc', REST_Controller::HTTP_NOT_FOUND);
        }

        // Tích hợp inline edit
        if (commonRequest('inline_edit')) {
            $this->db->trans_start();
            $this->Hrm_vi_tri_cong_viec_model
                ->where('id_vi_tri_cong_viec', $id)
                ->update([
                    commonRequest('column') => commonRequest('value')
                ]);
            $this->db->trans_commit();
            resSuccess($this->Hrm_vi_tri_cong_viec_model->find($id));
        }
        // END Tích hợp inline edit

        $data = [
            'ten_cong_viec' => commonRequest('ten_cong_viec') ? commonRequest('ten_cong_viec') : null,
            'ten_cong_viec_en' => commonRequest('ten_cong_viec_en') ? commonRequest('ten_cong_viec_en') : null,
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

        $this->Hrm_vi_tri_cong_viec_model
            ->where('id_vi_tri_cong_viec', $id)
            ->update($data);

        $newViTri = $this->Hrm_vi_tri_cong_viec_model->find($id);
        $this->createLog('Update', 'Sửa vị trí công việc: ' . $newViTri['ten_cong_viec'], $vitri, $newViTri, 'hrm_vi_tri_cong_viec');

        $this->db->trans_commit();

        resSuccess($newViTri);
    }

    public function delete_delete($id)
    {
        $vitri = $this->Hrm_vi_tri_cong_viec_model->find($id);
        if (!$vitri) {
            resError('Không tìm thấy vị trí công việc', REST_Controller::HTTP_NOT_FOUND);
        }

        $this->db->trans_start();
        $this->Hrm_vi_tri_cong_viec_model->delete($id);
        $this->createLog('Delete', 'Xóa vị trí công việc: ' . $vitri['ten_cong_viec'], $vitri, NULL, 'hrm_vi_tri_cong_viec');
        $this->db->trans_commit();

        resSuccess(null, 'Xóa thành công');
    }
}
