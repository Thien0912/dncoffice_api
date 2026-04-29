<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property  E_lich_cong_tac_model $E_lich_cong_tac_model
 * @property  CI_DB_query_builder $db
 */

class Lichcongtac extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('E_lich_cong_tac_model');
        // $this->permissionMiddleware(); // check permissions access
    }
    //if parameter getRoles is null then it get information from token
    public function index_get()
    {
        // $this->permissionMiddleware();
        $lich_cong_tac = $this->E_lich_cong_tac_model->getSearch();

        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => $lich_cong_tac
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function show_get($id_lich_cong_tac)
    {
        // $this->permissionMiddleware();
        $lichcongtac = $this->E_lich_cong_tac_model->find($id_lich_cong_tac);
        if (!$lichcongtac) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                'message' => 'Không tìm thấy lịch công tác',
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
                'ngay' => commonRequest('ngay') ? commonRequest('ngay') : null,
                'gio' => commonRequest('gio') ? commonRequest('gio') : null,
                'noi_dung' => commonRequest('noidung') ? commonRequest('noidung') : null,
                'bo_phan' => commonRequest('id_bo_phan') ? commonRequest('id_bo_phan') : null,
                'trang_thai' => commonRequest('id_trang_thai') ? commonRequest('id_trang_thai') : null,
            ];

            $this->db->trans_start();
            $lich_cong_tac = $this->E_lich_cong_tac_model->create($data);

            $this->createLog(
                'create',
                'Tạo mới lịch công tác',
                null,
                $lich_cong_tac,
                'e_lich_cong_tac'
            );

            $this->db->trans_commit();

            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_CREATED,
                'message' => 'Tạo lịch công tác thành công',
                'success' => true,
                'data' => $lich_cong_tac
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
            $lichcongtac = $this->E_lich_cong_tac_model->find($id);
            if (!$lichcongtac) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                    'message' => 'Không tìm thấy lịch công tác',
                    'success' => false,
                    'data' => null
                ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
            }

            $data = [
                'ngay' => commonRequest('ngay'),
                'gio' => commonRequest('gio'),
                'noi_dung' => commonRequest('noi_dung'),
                'bo_phan' => commonRequest('id_bo_phan'),
                'trang_thai' => commonRequest('id_trang_thai'),

            ];

            $result = $this->E_lich_cong_tac_model->where('id_lich_cong_tac', $id)->update($data);

            $this->createLog(
                'update',
                'Cập nhật lịch công tác',
                $lichcongtac,
                $this->E_lich_cong_tac_model->find($id),
                'e_lich_cong_tac'
            );


            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Cập nhật lịch công tác thành công',
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


    public function delete_delete($id_lich_cong_tac)
    {
        // $this->permissionMiddleware();
        try {

            $lichcongtac = $this->E_lich_cong_tac_model->find($id_lich_cong_tac);
            if (!$lichcongtac) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                    'message' => 'Không tìm thấy lịch công tác',
                    'success' => false,
                    'data' => null
                ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
            }


            $this->E_lich_cong_tac_model->where('id_lich_cong_tac', $id_lich_cong_tac)->delete();

            $this->createLog(
                'delete',
                'Xóa lịch công tác',
                $lichcongtac,
                null,
                'e_lich_cong_tac'
            );

            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,

                'message' => 'Xóa lịch công tác thành công',

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

    public function list_get()
    {
        $today = new DateTime();

        // Lấy thứ Hai đầu tuần
        $monday = clone $today->modify(('Monday' == $today->format('l')) ? 'this monday' : 'last monday');
        $default_start_date = $monday->format('Y-m-d');

        // Lấy Chủ Nhật cuối tuần
        $sunday = clone $monday->modify('+6 days');
        $default_end_date = $sunday->format('Y-m-d');

        $start_date = commonRequest('start_date') ?: $default_start_date;
        $end_date = commonRequest('end_date') ?: $default_end_date;

        // $this->permissionMiddleware();
        $lich_cong_tac = $this->E_lich_cong_tac_model->getList($start_date, $end_date);

        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => $lich_cong_tac
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function deletes_post($ids) {
        
    }
}
