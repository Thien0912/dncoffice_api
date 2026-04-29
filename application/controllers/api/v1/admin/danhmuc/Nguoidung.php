<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property E_bao_mat_model $E_bao_mat_model
 * @property Fileupload $fileupload
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 */



class Nguoidung extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Ql_nguoi_dung_model']);
    }

    public function index_get()
    {

        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;

        $data = $this->Ql_nguoi_dung_model->getAll($start, $length, $searchValue);
        $hocHamHocVi = $this->db
            ->select('*')
            ->from('hrm_hoc_ham_hoc_vi')
            ->get()
            ->result_array();

        resSuccess($data['data'], 'Success', 200, true, [
            'hoc_ham_hoc_vi' => $hocHamHocVi
        ]);
    }
}
