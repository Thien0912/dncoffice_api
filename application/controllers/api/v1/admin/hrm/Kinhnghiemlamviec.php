<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Hrm_nhan_vien_kinh_nghiem_lam_viec_model $Hrm_nhan_vien_kinh_nghiem_lam_viec_model
 * @property Fileupload $fileupload
 * @property Validate $validate
 */

class Kinhnghiemlamviec extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_nhan_vien_model', 'Hrm_nhan_vien_kinh_nghiem_lam_viec_model']);
        $this->load->library(['Validator', 'Fileupload', 'Validate']);
    }

    public function index_get() {}

    public function create_post()
    {
        $data = [
            'id_nhan_vien' => commonRequest('id_nhan_vien') ? commonRequest('id_nhan_vien') : null,
            'ten_cong_ty' => commonRequest('ten_cong_ty') ? commonRequest('ten_cong_ty') : null,
            'ngay_bat_dau' => commonRequest('ngay_bat_dau') ? commonRequest('ngay_bat_dau') : null,
            'ngay_ket_thuc' => commonRequest('ngay_ket_thuc') ? commonRequest('ngay_ket_thuc') : null,
            'mo_ta' => commonRequest('mo_ta') ? commonRequest('mo_ta') : null,
            'la_kinh_nghiem_noi_bo' => commonRequest('la_kinh_nghiem_noi_bo') ? commonRequest('la_kinh_nghiem_noi_bo') : null,
            'chuc_danh' => commonRequest('chuc_danh') ? commonRequest('chuc_danh') : null,
        ];
        $rules = [
            'ten_cong_ty' => 'required',
            'ngay_bat_dau' => 'required|date',
            'ngay_ket_thuc' => 'required|date',
        ];
        $customMessages = [
            'ten_cong_ty.required' => 'Vui lòng nhập tên công ty',
            'ngay_bat_dau.date' => 'Vui lòng nhập ngày bắt đầu',
            'ngay_bat_dau.date' => 'Ngày bắt đầu không đúng định dạng',
            'ngay_ket_thuc.date' => 'Ngày kết thúc không đúng định dạng',
            'ngay_ket_thuc.date' => 'Ngày kết thúc không đúng định dạng',
        ];
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);
        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }
        $nhanvienId = $data['id_nhan_vien'];
        $nhanvien = $this->Hrm_nhan_vien_model->find($nhanvienId);
        if (!$nhanvien) {
            resError('Nhân viên không tồn tại', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }
        $kinhnghiemlamviec = $this->Hrm_nhan_vien_kinh_nghiem_lam_viec_model->create($data);
        resSuccess($kinhnghiemlamviec, 'Thêm thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }

    public function update_post()
    {
        $nhanvienId = commonRequest('id_nhan_vien');
        $nhanvien = $this->Hrm_nhan_vien_model->find($nhanvienId);
        if (!$nhanvien) {
            resError('Nhân viên không tồn tại', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }
        $data = [
            'id_kinh_nghiem' => commonRequest('id_kinh_nghiem') ? commonRequest('id_kinh_nghiem') : null,
            'id_nhan_vien' => commonRequest('id_nhan_vien') ? commonRequest('id_nhan_vien') : null,
            'ten_cong_ty' => commonRequest('ten_cong_ty') ? commonRequest('ten_cong_ty') : null,
            'ngay_bat_dau' => commonRequest('ngay_bat_dau') ? commonRequest('ngay_bat_dau') : null,
            'ngay_ket_thuc' => commonRequest('ngay_ket_thuc') ? commonRequest('ngay_ket_thuc') : null,
            'mo_ta' => commonRequest('mo_ta') ? commonRequest('mo_ta') : null,
            'la_kinh_nghiem_noi_bo' => commonRequest('la_kinh_nghiem_noi_bo') ? commonRequest('la_kinh_nghiem_noi_bo') : null,
            'chuc_danh' => commonRequest('chuc_danh') ? commonRequest('chuc_danh') : null,
        ];

        $result = $this->Hrm_nhan_vien_kinh_nghiem_lam_viec_model->where('id_kinh_nghiem', $data['id_kinh_nghiem'])->update($data);

        $kinhnghiemlamviec = $this->Hrm_nhan_vien_kinh_nghiem_lam_viec_model->find($data['id_kinh_nghiem']);
        resSuccess($kinhnghiemlamviec, 'Cập nhật thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }

    public function delete_post()
    {
        $id = commonRequest('id');
        $data = [
            'deleted_at' => date('Y-m-d H:i:s'),
        ];

        $result = $this->Hrm_nhan_vien_kinh_nghiem_lam_viec_model->where('id_kinh_nghiem', $id)->update($data);
        resSuccess($result, 'Xóa thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }
}
