<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 * @property Ql_thong_bao_model $Ql_thong_bao_model
 */

class Notifications extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Ql_thong_bao_model');
    }
    public function index_get()
    {
        $user = $this->getUserLogin();

        $result = $this->Ql_thong_bao_model->getNotificationsByUserId($user['ql_nguoi_dung_id']);
        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => [
                'notifications' => $result['data'],
                'countAllRecord' => $result['recordsFiltered']
            ]
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function show_get($id)
    {
        $user = $this->getUserLogin();
        $notification = $this->Ql_thong_bao_model
            ->join('ql_thong_bao_nguoi_dung', 'ql_thong_bao_nguoi_dung.ql_thong_bao_id = ql_thong_bao.ql_thong_bao_id')
            ->where('ql_thong_bao_nguoi_dung.ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])
            ->where('ql_thong_bao.ql_thong_bao_da_gui', 1)
            ->where('ql_thong_bao.ql_thong_bao_id', $id)
            ->first();

        if (!$notification) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                'message' => 'Notification not found',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }
        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => $notification
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function new_notifications_get()
    {
        $user = $this->getUserLogin();
        $newNoti = $this->Ql_thong_bao_model
            ->join('ql_thong_bao_nguoi_dung', 'ql_thong_bao_nguoi_dung.ql_thong_bao_id = ql_thong_bao.ql_thong_bao_id')
            ->where('ql_thong_bao_nguoi_dung.ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])
            ->where('ql_thong_bao.ql_thong_bao_da_gui', 1)
            ->where('ql_thong_bao_nguoi_dung.ql_thong_bao_da_doc !=', 1)
            ->count();

        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'new_noti' => $newNoti
        ], REST_INSTANCE_Controller::HTTP_OK);
    }
}
