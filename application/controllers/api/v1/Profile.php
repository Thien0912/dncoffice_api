<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';
/**
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Hrm_yeu_cau_cap_nhat_model $Hrm_yeu_cau_cap_nhat_model
 * @property Fileupload $fileupload
 */

class Profile extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model(['Hrm_nhan_vien_model', 'Hrm_yeu_cau_cap_nhat_model']);
        $this->load->library(['Common', 'fileupload']);
    }

    public function index_get()
    {
        $auth = $this->getUserLogin();
        $data = $this->Hrm_nhan_vien_model
            ->where('ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])
            ->first();
        resSuccess($data);
    }

    public function yeucaucapnhat_post()
    {
        $auth = $this->getUserLogin();
        $data = commonRequest('payload');

        $nhanvien = $this->Hrm_nhan_vien_model
            ->where('hrm_nhan_vien.ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])
            ->first();

        if (!$nhanvien) {
            resError("Không tìm thấy nhân viên");
        }

        $result = $this->Hrm_yeu_cau_cap_nhat_model->insert([
            'id_nhan_vien' => $nhanvien['id_nhan_vien'],
            'du_lieu' => $data,
        ]);

        if ($result) {
            $id_yeu_cau_cap_nhat = $this->db->insert_id();
            resSuccess(['id_yeu_cau_cap_nhat' => $id_yeu_cau_cap_nhat], 'Yêu cầu cập nhật thông tin của bạn đã được gửi.', REST_Controller::HTTP_OK, true);
        } else {
            resError("Đã có lỗi xảy ra khi gửi yêu cầu");
        }
    }

    public function uploadAvatar_post()
    {
        $avatar = commonRequest('avatar') ? commonRequest('avatar') : null;
        $id_yeu_cau_cap_nhat = commonRequest('id_yeu_cau_cap_nhat') ? commonRequest('id_yeu_cau_cap_nhat') : null;

        // dd($avatar);
        $errors = [];
        $folderName = 'employees/avatars';

        if (isset($avatar)) {

            $uploadedFile = $this->fileupload->upload($avatar, $folderName);

            if (!$uploadedFile['success']) {
                $errors = array_merge($errors, ['avatar' => 'Không thể tải lên ảnh đại diện']);
            } else {

                if ($id_yeu_cau_cap_nhat) {

                    $yeu_cau = $this->Hrm_yeu_cau_cap_nhat_model->find($id_yeu_cau_cap_nhat);
                    $du_lieu = json_decode($yeu_cau['du_lieu'], true);
                    $du_lieu['avatar'] = $uploadedFile['file_path'];

                    $result = $this->Hrm_yeu_cau_cap_nhat_model->where('id_yeu_cau_cap_nhat', $id_yeu_cau_cap_nhat)
                        ->update(['du_lieu' => json_encode($du_lieu)]);

                    resSuccess($result, 'Ảnh đại diện đã được tải lên, vui lòng đợi duyệt.', REST_Controller::HTTP_OK, true);
                } else {

                    $auth = $this->getUserLogin();
                    $du_lieu['avatar'] = $uploadedFile['file_path'];

                    $nhanvien = $this->Hrm_nhan_vien_model
                        ->where('ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])
                        ->first();

                    $result = $this->Hrm_yeu_cau_cap_nhat_model->insert([
                        'id_nhan_vien' => $nhanvien['id_nhan_vien'],
                        'du_lieu' => json_encode($du_lieu),
                    ]);

                    resSuccess($result, 'Yêu cầu cập nhật thông tin của bạn đã được gửi.', REST_Controller::HTTP_OK, true);
                }
            }
        }

        $errors = array_merge($errors, ['avatar' => ['Không thể tải lên ảnh đại diện']]);

        resError("Đã có lỗi xảy ra", 500, $errors);
    }

    public function code_get($code)
    {
        $data = $this->Hrm_nhan_vien_model->get_employee_by_code($code);
        if (!$data) {
            resError("Không tìm thấy nhân viên");
        }
        $data['avatar'] = encryptString($data['avatar']);
        resSuccess($data, 'Success');
    }
}
