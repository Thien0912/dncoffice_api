<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Hrm_quy_dinh_nghi_phep $Hrm_quy_dinh_nghi_phep
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 * @property Hrm_hop_dong_model $Hrm_hop_dong_model
 * @property Hrm_phu_cap_model $Hrm_phu_cap_model
 * @property Hrm_ca_lam_viec_model $Hrm_ca_lam_viec_model
 * @property Fileupload $fileupload
 * @property Common $common
 */



class Calamviec extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_nhan_vien_model', 'Ql_nguoi_dung_model', 'Hrm_hop_dong_model', 'Hrm_phu_cap_model', 'Hrm_ca_lam_viec_model']);
        $this->load->library(['Validator', 'Fileupload', 'Common']);
    }
    public function index_get()
    {
       
        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;

        $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
            'order' => commonRequest('order'),
            'columns' => commonRequest('columns')
        ] : [];

        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];

        $data = $this->Hrm_ca_lam_viec_model->getAll($start, $length, $searchValue, $orderBy, $searchKey);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function create_post()
    {
        $validator = new Validator();
        $auth = $this->getUserLogin();
        $data = [
            'ca_lam_viec' => commonRequest('ten_ca') ? commonRequest('ten_ca') : null,
            'check_in' => commonRequest('check_in') ? commonRequest('check_in') : null,
            'bat_dau_check_in' => commonRequest('gio_bat_dau_check_in') ? commonRequest('gio_bat_dau_check_in') : null,
            'ket_thuc_check_in' => commonRequest('gio_ket_thuc_check_in') ? commonRequest('gio_ket_thuc_check_in') : null,
            'check_out' => commonRequest('check_out') ? commonRequest('check_out') : null,
            'bat_dau_check_out' => commonRequest('gio_bat_dau_check_out') ? commonRequest('gio_bat_dau_check_out') : null,
            'ket_thuc_check_out' => commonRequest('gio_ket_thuc_check_out') ? commonRequest('gio_ket_thuc_check_out') : null,
        ];

        $rules = [
            'ca_lam_viec' => 'required',
            'check_in' => 'required',
            'bat_dau_check_in' => 'required',
            'ket_thuc_check_in' => 'required',
            'check_out' => 'required',
            'bat_dau_check_out' => 'required',
            'ket_thuc_check_out' => 'required',
        ];

        $customMessages = [
            'ca_lam_viec.required' => 'Ca làm việc bắt buộc nhập',
            'check_in.required' => 'Check in bắt buộc nhập',
            'bat_dau_check_in.required' => 'Giờ bắt đầu check in bắt buộc nhập',
            'ket_thuc_check_in.required' => 'Giờ kết thúc check in bắt buộc nhập',
            'check_out.required' => 'Check out bắt buộc nhập',
            'bat_dau_check_out.required' => 'Giờ bắt đầu check out bắt buộc nhập',
            'ket_thuc_check_out.required' => 'Giờ kết thúc check out bắt buộc nhập',
        ];

        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();

        $item = $this->Hrm_ca_lam_viec_model->create($data);

        //create log
        $this->createLog('create', 'Tạo mới ca làm việc', null, $item, 'hrm_ca_lam_viec');
        $this->db->trans_commit();
        resSuccess($item, 'Thêm thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }
}
