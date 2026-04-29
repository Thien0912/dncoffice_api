<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 * @property Ql_nhat_ky_model $Ql_nhat_ky_model
 */
class Diaries extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Ql_nhat_ky_model');
    }
    public function index_get()
    {
        $actions = $this->Ql_nhat_ky_model->getSearch();
        $this->response([
            'status' => REST_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => $actions
        ], REST_Controller::HTTP_OK);
    }
    public function show_get($id)
    {
        $diary = $this->Ql_nhat_ky_model->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = ql_nhat_ky.ql_nguoi_dung_id')->find($id);
        if (!$diary) {
            $this->response([
                'status' => REST_Controller::HTTP_NOT_FOUND,
                'message' => 'Diary not found',
                'success' => false,
                'data' => null
            ], REST_Controller::HTTP_NOT_FOUND);
        }
        if (isset($diary['ql_nguoi_dung_mat_khau'])) {
            unset($diary['ql_nguoi_dung_mat_khau']);
        }
        $this->response([
            'status' => REST_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => $diary
        ], REST_Controller::HTTP_OK);
    }
}
