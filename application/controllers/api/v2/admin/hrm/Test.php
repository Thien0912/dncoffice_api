<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Hrm_quy_dinh_nghi_phep $Hrm_quy_dinh_nghi_phep
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 * @property Fileupload $fileupload
 */



class Test extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_nhan_vien_model', 'Hrm_quy_dinh_nghi_phep', 'Ql_nguoi_dung_model']);
        $this->load->library(['Validator', 'Fileupload']);
    }

    public function index_get()
    {
        $ngay_vao_lam_chinh_thuc = commonRequest('ngay_vao_lam_chinh_thuc') ? commonRequest('ngay_vao_lam_chinh_thuc') : null;
        $ngayphep = 14; //14 ngày phép trong năm
        if ($ngay_vao_lam_chinh_thuc) {
            $currentYear = date('Y');
            $year = date('Y', strtotime($ngay_vao_lam_chinh_thuc));
            $dateEndYear = new DateTime($year . '-12-31');
            $dateChinhThuc = new DateTime($ngay_vao_lam_chinh_thuc);
            $diff = $dateChinhThuc->diff($dateEndYear);
            $monthFromChinhThucToEndYear = $diff->m; //số tháng từ lúc làm chính thức đến năm đầu làm chính thức
            //Tính các năm sau
            resSuccess($monthFromChinhThucToEndYear);
        }
    }
}
