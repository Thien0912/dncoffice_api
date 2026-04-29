<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_ty_le_bao_hiem_model $Hrm_ty_le_bao_hiem_model
 */



class Tylebaohiem extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model(['Hrm_ty_le_bao_hiem_model']);
    }

    public function latest_get()
    {
        $data = $this->Hrm_ty_le_bao_hiem_model->orderBy('ngay_ap_dung', 'desc')->first();
        resSuccess($data);
    }

    public function index_get()
    {
        $data = [];
        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;

        $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
            'order' => commonRequest('order'),
            'columns' => commonRequest('columns')
        ] : [];

        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];

        $data = $this->Hrm_ty_le_bao_hiem_model->getAll($start, $length, $searchValue, $orderBy, $searchKey);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function create_post()
    {
        $this->load->library(['Validator']);

        $data = [
            'ngay_ap_dung' => commonRequest('ngay_ap_dung') ? commonRequest('ngay_ap_dung') : null,
            'bhxh_nv' => commonRequest('bhxh_nv') ? commonRequest('bhxh_nv') : null,
            'bhxh_dn' => commonRequest('bhxh_dn') ? commonRequest('bhxh_dn') : null,
            'bhyt_nv' => commonRequest('bhyt_nv') ? commonRequest('bhyt_nv') : null,
            'bhyt_dn' => commonRequest('bhyt_dn') ? commonRequest('bhyt_dn') : null,
            'bhtn_nv' => commonRequest('bhtn_nv') ? commonRequest('bhtn_nv') : null,
            'bhtn_dn' => commonRequest('bhtn_dn') ? commonRequest('bhtn_dn') : null,
        ];

        $rules = [
            'ngay_ap_dung' => 'required',
            'bhxh_nv' => 'required',
            'bhxh_dn' => 'required',
            'bhyt_nv' => 'required',
            'bhyt_dn' => 'required',
            'bhtn_nv' => 'required',
            'bhtn_dn' => 'required',
        ];

        $customMessages = [
            'ten_don_vi.required' => 'Tên đơn vị bắt buộc nhập',
            'bhxh_nv.required' => 'BHXH nhân viên bắt buộc nhập',
            'bhxh_dn.required' => 'BHXH doanh nghiệp bắt buộc nhập',
            'bhyt_nv.required' => 'BHYT nhân viên bắt buộc nhập',
            'bhyt_dn.required' => 'BHYT doanh nghiệp bắt buộc nhập',
            'bhtn_nv.required' => 'BHTN nhân viên bắt buộc nhập',
            'bhtn_dn.required' => 'BHTN doanh nghiệp bắt buộc nhập',
        ];
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();

        //Lưu văn bản đến
        $result = $this->Hrm_ty_le_bao_hiem_model->create($data);

        $this->createLog(
            'create',
            'Tạo mới tỷ lệ bảo hiểm',
            null,
            $result,
            'hrm_ty_le_bao_hiem'
        );


        $this->db->trans_commit();

        resSuccess($result, 'Thêm mới tỷ lệ bảo hiểm thành công!');
    }
}
