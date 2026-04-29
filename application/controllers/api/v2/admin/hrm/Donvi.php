<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property E_don_vi_model $E_don_vi_model
 * @property Fileupload $fileupload
 */



class Donvi extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['E_don_vi_model']);
        $this->load->library(['Validator']);
    }

    public function index_get()
    {

        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;

        $loaidonvi = commonRequest('loaidonvi') ? commonRequest('loaidonvi') : null;

        // if (commonRequest('order')) {
        //     $order = json_decode(commonRequest('order'), true);
        // }

        $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
            'order' => commonRequest('order'),
            'columns' => commonRequest('columns')
        ] : [];

        $data = $this->E_don_vi_model->getAll($start, $length, $searchValue, $orderBy, $loaidonvi);
        // resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
        //     'recordsTotal' => $data['recordsTotal'],
        //     'recordsFiltered' => $data['recordsFiltered']
        // ]);

        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => $data
        ], REST_INSTANCE_Controller::HTTP_OK);
    }


    public function show_get($id_don_vi)
    {
        // $this->permissionMiddleware();
        $don_vi = $this->E_don_vi_model->find($id_don_vi);

        if (!$don_vi) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                'message' => 'Không tìm thấy đơn vị',
                'success' => false,
                'data' => null
            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }

        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_CREATED,
            'message' => 'Success',
            'success' => true,
            'data' => $don_vi,
        ], REST_INSTANCE_Controller::HTTP_CREATED);
    }

    public function store_post()
    {
        // $this->permissionMiddleware();
        try {
            $data = [
                'ten_don_vi' => commonRequest('ten_don_vi') ? commonRequest('ten_don_vi') : null,
                'ma_don_vi' => commonRequest('ma_don_vi') ? commonRequest('ma_don_vi') : null,
                'loai' => commonRequest('loai') ? commonRequest('loai') : null,
                'email' => commonRequest('email') ? commonRequest('email') : null,
            ];

            $rules = [
                'ten_don_vi' => 'required',
            ];
    
            $customMessages = [
                'ten_don_vi.required' => 'Tên đơn vị bắt buộc nhập',
            ];
            $validator = new Validator();
            $validator->setCustomMessages($customMessages);
    
            if (!$validator->validate($data, $rules)) {
                resBadrequest($validator->errors());
            }

            $this->db->trans_start();
            $don_vi = $this->E_don_vi_model->create($data);

            $this->createLog(
                'create',
                'Tạo mới đơn vị',
                null,
                $don_vi,
                'e_don_vi'
            );

            $this->db->trans_commit();

            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_CREATED,
                'message' => 'Tạo đơn vị thành công',
                'success' => true,
                'data' => $don_vi
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
            $don_vi = $this->E_don_vi_model->find($id);
            if (!$don_vi) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                    'message' => 'Không tìm thấy đơn vị',
                    'success' => false,
                    'data' => null
                ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
            }

            $data = [
                'ten_don_vi' => commonRequest('ten_don_vi') ? commonRequest('ten_don_vi') : null ,
                'ma_don_vi' => commonRequest('ma_don_vi') ? commonRequest('ma_don_vi') : null,
                'loai' => commonRequest('loai') ? commonRequest('loai') : null,
                'email' => commonRequest('email') ? commonRequest('email') : null,
            ];

            $result = $this->E_don_vi_model->where('id_don_vi', $id)->update($data);

            $this->createLog(
                'update',
                'Cập nhật đơn vị',
                $don_vi,
                $this->E_don_vi_model->find($id),
                'e_lich_cong_tac'
            );


            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Cập nhật đơn vị thành công',
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

    public function deletes_post()
    {
        $ids = commonRequest('ids');
        $this->db->trans_start();
        $donvi = $this->E_don_vi_model->whereIn('id_don_vi', $ids)->get();
        if (count($ids) != count($donvi)) {
            resError('Dữ liệu không hợp lệ');
        }

        $this->E_don_vi_model->whereIn('id_don_vi', $ids)->delete();

        $this->createLog('delete', 'Xóa đơn vị', $donvi,  null, 'e_don_vi');
        $this->db->trans_commit();
        resSuccess(null, 'Xóa thành công');
    }

}
