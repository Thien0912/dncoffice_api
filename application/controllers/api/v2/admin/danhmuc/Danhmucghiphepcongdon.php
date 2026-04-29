<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property DB_query_builder $db
 * @property Hrm_danh_muc_nghi_phep_cong_don_model $Hrm_danh_muc_nghi_phep_cong_don_model
 */

class Danhmucghiphepcongdon extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_danh_muc_nghi_phep_cong_don_model']);
    }

    public function index_get()
    {
        // $this->permissionMiddleware();
        $data = $this->Hrm_danh_muc_nghi_phep_cong_don_model->getSearch();

        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => $data
        ], REST_INSTANCE_Controller::HTTP_OK);
    }
    public function show_get($id_danh_muc_nghi_phep)
    {
        // $this->permissionMiddleware();
        $danh_muc_nghi_phep = $this->Hrm_danh_muc_nghi_phep_cong_don_model->find($id_danh_muc_nghi_phep);
        if (!$danh_muc_nghi_phep) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                'message' => 'Không tìm thấy danh mục nghỉ phép',
                'success' => false,
                'data' => null
            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }

        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_CREATED,
            'message' => 'Success',
            'success' => true,
            'data' => $danh_muc_nghi_phep,
        ], REST_INSTANCE_Controller::HTTP_CREATED);
    }

    public function create_post()
    {
        // $this->permissionMiddleware();
        try {
            $data = [
                'so_nam_lam_viec' => commonRequest('so_nam_lam_viec'),
                'so_ngay_phep' => commonRequest('so_ngay_phep'),
            ];

            $this->db->trans_start();
            $danh_muc_nghi_phep = $this->Hrm_danh_muc_nghi_phep_cong_don_model->create($data);

            $this->createLog(
                'create',
                'Tạo mới danh mục nghỉ phép cộng dồ',
                null,
                $danh_muc_nghi_phep,
                'hrm_danh_muc_lich_nghi_phep_cong_don'
            );

            $this->db->trans_commit();

            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_CREATED,
                'message' => 'Tạo danh mục nghỉ phép cộng dồn thành công',
                'success' => true,
                'data' => $danh_muc_nghi_phep
            ], REST_INSTANCE_Controller::HTTP_CREATED);
        } catch (Exception $e) {
            $this->response([

                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,

                'message' => $e->getMessage(),

                'success' => false,

                'data' => null

            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $t) {
            $this->response([

                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,

                'message' => $t->getMessage(),

                'success' => false,

                'data' => null

            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
