<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property E_co_quan_model $E_co_quan_model
 * @property Fileupload $fileupload
 */



class Bophan extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['E_bo_phan_model']);
    }

    public function index_get()
    {

        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;

        $data = $this->E_bo_phan_model->getAll($start, $length, $searchValue);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }


    public function show_get($id_bo_phan)
    {
        // $this->permissionMiddleware();
        $lichcongtac = $this->E_bo_phan_model->find($id_bo_phan);
        if (!$lichcongtac) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                'message' => 'Không tìm thấy bộ phận',
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
                'ten_bo_phan' => commonRequest('ten_bo_phan'),
            ];

            $this->db->trans_start();
            $ten_bo_phan = $this->E_bo_phan_model->create($data);

            $this->createLog(
                'create',
                'Tạo mới Tên bộ phận',
                null,
                $ten_bo_phan,
                'e_bo_phan'
            );

            $this->db->trans_commit();

            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_CREATED,
                'message' => 'Tạo Tên bộ phận thành công',
                'success' => true,
                'data' => $ten_bo_phan
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
            $lichcongtac = $this->E_bo_phan_model->find($id);
            if (!$lichcongtac) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                    'message' => 'Không tìm thấy Tên bộ phận',
                    'success' => false,
                    'data' => null
                ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
            }

            $data = [
                'ten_bo_phan' => commonRequest('ten_bo_phan'),

            ];

            $result = $this->E_bo_phan_model->where('id_bo_phan', $id)->update($data);

            $this->createLog(
                'update',
                'Cập nhật bộ phận',
                $lichcongtac,
                $this->E_bo_phan_model->find($id),
                'e_bo_phan'
            );


            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Cập nhật bộ phận thành công',
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


    public function delete_delete($id_bo_phan)
    {
        // $this->permissionMiddleware();
        try {

            $lichcongtac = $this->E_bo_phan_model->find($id_bo_phan);
            if (!$lichcongtac) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                    'message' => 'Không tìm thấy bộ phận',
                    'success' => false,
                    'data' => null
                ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
            }


            $this->E_bo_phan_model->where('id_bo_phan', $id_bo_phan)->update(['deleted_at' => date('Y-m-d H:i:s')]);

            $this->createLog(
                'delete',
                'Xóa bộ phận',
                $lichcongtac,
                null,
                'e_bo_phan'
            );

            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,

                'message' => 'Xóa bộ phận thành công',

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
