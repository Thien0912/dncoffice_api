<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property  Hrm_quy_dinh_nghi_phep_model $Hrm_quy_dinh_nghi_phep_model
 * @property  CI_DB_query_builder $db
 */

class Quydinhnghiphep extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Hrm_quy_dinh_nghi_phep_model');
        // $this->permissionMiddleware(); // check permissions access
    }
    //if parameter getRoles is null then it get information from token
    public function index_get()
    {
        $this->permissionMiddleware();
        $quydinhnghiphep = $this->Hrm_quy_dinh_nghi_phep_model->getSearch();

        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => $quydinhnghiphep
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function show_get($id)
    {
        // $this->permissionMiddleware();
        $qdnghiphep = $this->Hrm_quy_dinh_nghi_phep_model->find($id);
        if (!$qdnghiphep) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                'message' => 'Không tìm thấy quy định nghĩ phép',
                'success' => false,
                'data' => null
            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }

        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_CREATED,
            'message' => 'Success',
            'success' => true,
            'data' => $qdnghiphep,
        ], REST_INSTANCE_Controller::HTTP_CREATED);
    }



    public function create_post()
    {
        // $this->permissionMiddleware();
        try {
            $data = [
                'loai_cong_viec' => commonRequest('loai_cong_viec'),
                'ngay_phep_co_ban' => commonRequest('ngay_phep_co_ban'),
            ];

            $this->db->trans_start();
            $qdnghiphep = $this->Hrm_quy_dinh_nghi_phep_model->create($data);

            $this->createLog(
                'create',
                'Tạo mới quy định nghĩ phép',
                null,
                $qdnghiphep,
                'hrm_quy_dinh_nghi_phep'
            );

            $this->db->trans_commit();

            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_CREATED,
                'message' => 'Tạo mới quy định nghĩ phép thành công',
                'success' => true,
                'data' => $qdnghiphep
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

    public function update_put($id)
    {
        // $this->permissionMiddleware();
        try {
            $qdnghiphep = $this->Hrm_quy_dinh_nghi_phep_model->find($id);
            if (!$qdnghiphep) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                    'message' => 'Không tìm thấy quy định nghĩ phép',
                    'success' => false,
                    'data' => null
                ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
            }

            $data = [
                'ngay' => commonRequest('ngay'),
                'gio' => commonRequest('gio'),

            ];

            $result = $this->Hrm_quy_dinh_nghi_phep_model->where('$id', $id)->update($data);

            $this->createLog(
                'update',
                'Cập nhật quy định nghĩ phép',
                $qdnghiphep,
                $this->Hrm_quy_dinh_nghi_phep_model->find($id),
                'hrm_quy_dinh_nghi_phep'
            );


            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Cập nhật quy định nghĩ phép thành công',
                'success' => true,
                'data' => $result
            ], REST_INSTANCE_Controller::HTTP_OK);
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


    public function delete_delete($id)
    {
        // $this->permissionMiddleware();
        try {

            $qdnghiphep = $this->Hrm_quy_dinh_nghi_phep_model->find($id);
            if (!$qdnghiphep) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                    'message' => 'Không tìm thấy quy định nghĩ phép',
                    'success' => false,
                    'data' => null
                ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
            }

            $this->Hrm_quy_dinh_nghi_phep_model->where('$id', $id)->delete();

            $this->createLog(
                'delete',
                'Xóa quy định nghĩ phép',
                $qdnghiphep,
                null,
                'hrm_quy_dinh_nghi_phep'
            );

            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Xóa quy định nghĩ phép thành công',
                'success' => true,
                'data' => null
            ], REST_INSTANCE_Controller::HTTP_OK);
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

    // public function list_get()
    // {
    //     $today = new DateTime();

    //     // Lấy thứ Hai đầu tuần
    //     $monday = clone $today->modify(('Monday' == $today->format('l')) ? 'this monday' : 'last monday');
    //     $default_start_date = $monday->format('Y-m-d');

    //     // Lấy Chủ Nhật cuối tuần
    //     $sunday = clone $monday->modify('+6 days');
    //     $default_end_date = $sunday->format('Y-m-d');

    //     $start_date = commonRequest('start_date') ?: $default_start_date;
    //     $end_date = commonRequest('end_date') ?: $default_end_date;

    //     // $this->permissionMiddleware();
    //     $lich_cong_tac = $this->Hrm_quy_dinh_nghi_phep_model->getList($start_date, $end_date);

    //     $this->response([
    //         'status' => REST_INSTANCE_Controller::HTTP_OK,
    //         'message' => 'Success',
    //         'success' => true,
    //         'data' => $lich_cong_tac
    //     ], REST_INSTANCE_Controller::HTTP_OK);
    // }
}
