<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 * @property CI_Input $input
 * @property CI_Upload $upload
 */
class Permissions extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Ql_quyen_model');
    }
    //get all permissions
    public function index_get()
    {
        $permissions = $this->Ql_quyen_model->getSearch();
        $this->response([
            'status' => REST_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => $permissions
        ], REST_Controller::HTTP_OK);
    }

    public function create_post()
    {
        $checkKey = $this->Ql_quyen_model
            ->where('ql_quyen_khoa', commonRequest('ql_quyen_khoa'))
            ->first() ? true : false;
        if ($checkKey) {
            $this->response([
                'status' => REST_Controller::HTTP_BAD_REQUEST,
                'message' => 'Permission key already exists',
                'success' => false
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        $permission = $this->Ql_quyen_model->create([
            'ql_quyen_ten' => commonRequest('ql_quyen_ten'),
            'ql_quyen_mo_ta' => commonRequest('ql_quyen_mo_ta'),
            'ql_quyen_khoa' => commonRequest('ql_quyen_khoa'),
            'ql_quyen_parent_id' => commonRequest('ql_quyen_parent_id'),
            'ql_quyen_url' => commonRequest('ql_quyen_url')
        ]);
        if ($permission) {
            $this->response([
                'status' => REST_Controller::HTTP_OK,
                'message' => 'Create successfully',
                'success' => true,
                'data' => $permission
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => REST_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Create failed',
                'success' => false
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show_get($id)
    {
        $permission = $this->Ql_quyen_model->find($id);
        if (!$permission) {
            $this->response([
                'status' => REST_Controller::HTTP_NOT_FOUND,
                'message' => 'Permission not found',
                'success' => false
            ], REST_Controller::HTTP_NOT_FOUND);
        }
        $this->response([
            'status' => REST_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => $permission
        ], REST_Controller::HTTP_OK);
    }

    public function update_put($id) {}
}
