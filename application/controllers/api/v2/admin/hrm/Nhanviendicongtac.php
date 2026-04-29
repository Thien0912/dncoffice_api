<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property DB_query_builder $db
 * @property Hrm_nhan_vien_di_cong_tac_model $Hrm_nhan_vien_di_cong_tac_model
 * @property Fileupload $fileupload
 */

class Nhanviendicongtac extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_nhan_vien_di_cong_tac_model']);
        $this->load->library(['Validator', 'Fileupload']);
    }

    public function create_post()
    {
        $validator = new Validator();

        $ngay_bat_dau = commonRequest('ngay_bat_dau') ? commonRequest('ngay_bat_dau') : null;
        if (!$ngay_bat_dau) {
            $validator->addError('', 'ngay_bat_dau', 'Ngày bắt đầu bắt buộc nhập');
            resBadrequest($validator->errors());
        }

        $auth = $this->getUserLogin();
        $data = [
            'id_nhan_vien' => commonRequest('id_nhan_vien') ? commonRequest('id_nhan_vien') : null,
            'dia_diem' => commonRequest('dia_diem') ? commonRequest('dia_diem') : null,
            'ngay_bat_dau' => $ngay_bat_dau,
            'ngay_ket_thuc' => commonRequest('ngay_ket_thuc') ? commonRequest('ngay_ket_thuc') : null,
            'ghi_chu' => commonRequest('ghi_chu') ? commonRequest('ghi_chu') : null,
        ];

        $rules = [
            'id_nhan_vien' => 'required|integer',
            'dia_diem' => 'required',
            'ngay_bat_dau' => 'date',
            'ngay_ket_thuc' => 'date',
            'ghi_chu' => 'required',
        ];

        $customMessages = [
            'id_nhan_vien.required' => 'Vui lòng chọn nhân viên',
            'dia_diem.required' => 'Vui lòng nhập địa điểm',
            'ngay_bat_dau.date' => 'Ngày bắt đầu không đúng định dạng',
            'ngay_ket_thuc.date' => 'Ngày kết thúc không đúng định dạng',
            'ghi_chu.required' => 'Vui lòng nhập ghi chú',
        ];

        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();

        $dicongtac = $this->Hrm_nhan_vien_di_cong_tac_model->create($data);

        //create log
        $this->createLog('create', 'Tạo mới đi công tác', null, $dicongtac, 'hrm_nhan_vien_di_cong_tac');
        $this->db->trans_commit();
        resSuccess($dicongtac, 'Thêm thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }
}