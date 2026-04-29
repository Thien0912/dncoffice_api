<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db

 * @property Fileupload $fileupload
 */



class Calamviec extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_ca_lam_viec_model', 'Hrm_nhan_vien_model']);
        $this->load->library(['Validator', 'Fileupload', 'Common', 'Pxl', 'upload']);
    }

    public function index_get()
    {
        $id = commonRequest('id') ? commonRequest('id') : null;
        if($id){
            $data = $this->Hrm_ca_lam_viec_model->find($id);
            resSuccess($data, 'Success', REST_Controller::HTTP_OK, true);
            return;
        }

        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : -1;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;
        $fromDate = commonRequest('fromDate') ? commonRequest('fromDate') : null;
        $toDate = commonRequest('toDate') ? commonRequest('toDate') : null;

        $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
            'order' => commonRequest('order'),
            'columns' => commonRequest('columns')
        ] : [];

        $nhanvien = commonRequest('nhanvien') ? commonRequest('nhanvien') : null;
        $ca = commonRequest('ca') ? commonRequest('ca') : null;

        $data = $this->Hrm_ca_lam_viec_model->getAll($start, $length, $searchValue, $orderBy, null, $nhanvien , $fromDate, $toDate, $ca);
   

        // $this->response([
        //     'status' => REST_INSTANCE_Controller::HTTP_OK,
        //     'message' => 'Success',
        //     'success' => true,
        //     'data' => $data,
        //     'sql' => $data['sql']
        // ], REST_INSTANCE_Controller::HTTP_OK);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
            'sql' => $data['sql'] ?? null,
        ]);
    }

    public function them_ca_post()
    {
        $data = [
            'ca_lam_viec' => commonRequest('ca_lam_viec'),
            'check_in' => commonRequest('check_in'),
            'bat_dau_check_in' => commonRequest('bat_dau_check_in'),
            'ket_thuc_check_in' => commonRequest('ket_thuc_check_in'),
            'check_out' => commonRequest('check_out'),
            'bat_dau_check_out' => commonRequest('bat_dau_check_out'),
            'ket_thuc_check_out' => commonRequest('ket_thuc_check_out'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        
        $this->Hrm_ca_lam_viec_model->insert($data);
        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Thêm ca làm việc thành công',
            'success' => true,
        ], REST_INSTANCE_Controller::HTTP_OK);
    }
    public function sua_ca_post()
    {
        $id = commonRequest('id');
        $data = [
            'ca_lam_viec' => commonRequest('ca_lam_viec'),
            'check_in' => commonRequest('check_in'),
            'bat_dau_check_in' => commonRequest('bat_dau_check_in'),
            'ket_thuc_check_in' => commonRequest('ket_thuc_check_in'),
            'check_out' => commonRequest('check_out'),
            'bat_dau_check_out' => commonRequest('bat_dau_check_out'),
            'ket_thuc_check_out' => commonRequest('ket_thuc_check_out'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->Hrm_ca_lam_viec_model->where('id', $id)->update($data);
        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Cập nhật ca làm việc thành công',
            'success' => true,
        ], REST_INSTANCE_Controller::HTTP_OK);
    }
    public function xoa_ca_post()
    {
        $id = commonRequest('id');
        $data = [
            'deleted_at' => date('Y-m-d H:i:s'),
        ];
        $this->Hrm_ca_lam_viec_model->where('id', $id)->update($data);
        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Xóa ca làm việc thành công',
            'success' => true,
        ], REST_INSTANCE_Controller::HTTP_OK);

    }

    public function them_ca_nhan_vien_post()
    {
        $id_nhan_vien = commonRequest('id_nhan_vien');
        $id_ca = commonRequest('id_ca');

        $result = $this->Hrm_nhan_vien_model->where('id_nhan_vien', $id_nhan_vien)->update(['id_ca_lam_viec' => $id_ca]);

        resSuccess($result, 'Thêm ca làm việc thành công!');
    }

}
