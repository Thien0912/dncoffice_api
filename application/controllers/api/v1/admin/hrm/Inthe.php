<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model 
 * @property E_don_vi_model $E_don_vi_model
 * @property Hrm_vi_tri_cong_viec_model $Hrm_vi_tri_cong_viec_model 
 * @property Pxl $pxl
 * @property Common $common 
 */



class Inthe extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model([
            'Hrm_nhan_vien_model',
            'E_don_vi_model',
            'Hrm_vi_tri_cong_viec_model'
        ]);
        $this->load->library(['Validator', 'Fileupload', 'Validate', 'Pxl', 'Common']);
    }

    public function index_get()
    {
        $data = [
            'start' => commonRequest('start') ?? 0,
            'length' => commonRequest('length') ?? 10,
            'searchValue' => commonRequest('searchValue') ?? null,
            'order' => commonRequest('order') ?? [],
            'columns' => commonRequest('columns') ?? [],
            'searchKey' => commonRequest('searchKey') ? commonRequest('searchKey') : [],
            'fromDate' => commonRequest('fromDate') ? commonRequest('fromDate') : null,
            'toDate' => commonRequest('toDate') ? commonRequest('toDate') : null
        ];

        $response = $this->Hrm_nhan_vien_model->getDanhSach_forInthe($data['start'], $data['length'], $data['searchValue'], $data['order'], $data['columns'], $data['searchKey'],  $data['fromDate'], $data['toDate']);

        resSuccess($response['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $response['recordsTotal'],
            'recordsFiltered' => $response['recordsFiltered'],
            'sql' => $response['sql'],
        ]);
    }

    public function show_get($id)
    {
        $nhanvien = $this->Hrm_nhan_vien_model
            ->select('
                    hrm_nhan_vien.id_nhan_vien,
                    hrm_nhan_vien.ma_nhan_vien,
                    hrm_nhan_vien.ho_va_ten,
                    hrm_nhan_vien.avatar,
                    hrm_nhan_vien.gioi_tinh,
                    hrm_nhan_vien.ngay_sinh,
                    hrm_nhan_vien.hoc_ham,
                    e_don_vi.id_don_vi,
                    e_don_vi.ten_don_vi,
                    e_don_vi.ten_don_vi_en,
                    hrm_vi_tri_cong_viec.id_vi_tri_cong_viec,
                    hrm_vi_tri_cong_viec.ten_cong_viec,
                    hrm_vi_tri_cong_viec.ten_cong_viec_en
            ')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_nhan_vien.id_don_vi_cong_tac', 'left')
            ->join('hrm_vi_tri_cong_viec', 'hrm_vi_tri_cong_viec.id_vi_tri_cong_viec = hrm_nhan_vien.id_vi_tri_cong_viec', 'left')
            ->where('hrm_nhan_vien.deleted_at IS NULL')
            ->find($id);
        if (!$nhanvien) {
            resError('Không tìm thấy nhân viên', REST_Controller::HTTP_NOT_FOUND);
        }
        $nhanvien['ngay_sinh'] = date('d/m/Y', strtotime($nhanvien['ngay_sinh']));
        $nhanvien['avatar'] = $nhanvien['avatar'] ? encryptString($nhanvien['avatar']) : encryptString('assets/avatar-users/default.jpg');
        resSuccess($nhanvien, 'success', REST_Controller::HTTP_OK);
    }
}
