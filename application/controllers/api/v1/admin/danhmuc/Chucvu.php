<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property E_chuc_vu_model $E_chuc_vu_model
 * @property Fileupload $fileupload
 */



class Chucvu extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_chuc_vu_model']);
    }

    public function index_get()
    {

        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;

        $data = $this->Hrm_chuc_vu_model->getAll($start, $length, $searchValue);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }


    public function show_get($id_chuc_vu)
    {
        // $this->permissionMiddleware();
        $lichcongtac = $this->Hrm_chuc_vu_model->find($id_chuc_vu);
        if (!$lichcongtac) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                'message' => 'Không tìm thấy chức vụ',
                'success' => false,
                'data' => null
            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }

        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_CREATED,
            'message' => 'Success',
            'success' => true,
            'data' => $lichcongtac,
        ], REST_INSTANCE_Controller::HTTP_CREATED);
    }


    public function create_post()
    {
        // $this->permissionMiddleware();
        try {
            $data = [
                'ten_chuc_vu' => commonRequest('ten_chuc_vu'),
            ];

            $this->db->trans_start();
            $ten_chuc_vu = $this->Hrm_chuc_vu_model->create($data);

            $this->createLog(
                'create',
                'Tạo mới tên chức vụ',
                null,
                $ten_chuc_vu,
                'hrm_chuc_vu'
            );

            $this->db->trans_commit();

            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_CREATED,
                'message' => 'Tạo tên chức vụ thành công',
                'success' => true,
                'data' => $ten_chuc_vu
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
            $lichcongtac = $this->Hrm_chuc_vu_model->find($id);
            if (!$lichcongtac) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                    'message' => 'Không tìm thấy tên chức vụ',
                    'success' => false,
                    'data' => null
                ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
            }

            $data = [
                'ten_chuc_vu' => commonRequest('ten_chuc_vu'),

            ];

            $result = $this->Hrm_chuc_vu_model->where('id_chuc_vu', $id)->update($data);

            $this->createLog(
                'update',
                'Cập nhật chức vụ',
                $lichcongtac,
                $this->Hrm_chuc_vu_model->find($id),
                'hrm_chuc_vu'
            );


            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Cập nhật chức vụ thành công',
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


    public function delete_delete($id_chuc_vu)
    {
        // $this->permissionMiddleware();
        try {

            $lichcongtac = $this->Hrm_chuc_vu_model->find($id_chuc_vu);
            if (!$lichcongtac) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                    'message' => 'Không tìm thấy chức vụ',
                    'success' => false,
                    'data' => null
                ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
            }


            $this->Hrm_chuc_vu_model->where('id_chuc_vu', $id_chuc_vu)->update(['deleted_at' => date('Y-m-d H:i:s')]);

            $this->createLog(
                'delete',
                'Xóa chức vụ',
                $lichcongtac,
                null,
                'hrm_chuc_vu'
            );

            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Xóa chức vụ thành công',
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
}
