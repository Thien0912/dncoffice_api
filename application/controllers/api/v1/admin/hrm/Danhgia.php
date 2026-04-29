<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_danh_gia_nhan_su_model $Hrm_danh_gia_nhan_su_model
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Fileupload $fileupload
 */



class Danhgia extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_danh_gia_nhan_su_model', 'Hrm_nhan_vien_model']);
        $this->load->library(['Validator', 'Fileupload']);
    }

    public function create_post()
    {
        $this->load->library(['Validator']);

        $data = [
            'id_nhan_vien' => commonRequest('id_nhan_vien') ? commonRequest('id_nhan_vien') : null,
            'diem_so' => commonRequest('diem_so') ? commonRequest('diem_so') : null,
            'nhan_xet' => commonRequest('nhan_xet') ? commonRequest('nhan_xet') : null,
            'thang' => commonRequest('thang') ? commonRequest('thang') : null,
        ];

        // resSuccess($data);
        $nhanvien = $this->Hrm_nhan_vien_model->find($data['id_nhan_vien']);
        if (!$nhanvien) {
            resError('Không tìm thấy nhân viên này. Vui lòng tải lại trang');
        }
        $rules = [
            'diem_so' => 'required',
            'thang' => 'required',
        ];

        $customMessages = [
            'diem_so.required' => 'Điểm số bắt buộc nhập',
            'thang.required' => 'Tháng bắt buộc chọn',
        ];

        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors(), 'Vui lòng kiểm tra lại các trường cần nhập');
        }

        $this->db->trans_start();

        //Thêm đánh giá
        $result = $this->Hrm_danh_gia_nhan_su_model->create($data);
        $result['count'] = $this->Hrm_danh_gia_nhan_su_model->where('id_nhan_vien', commonRequest('id_nhan_vien'))->count();

        $this->db->trans_commit();

        resSuccess($result, 'Thêm đánh giá thành công', 200, true);
    }

    public function show_get($id)
    {
        $data = $this->Hrm_danh_gia_nhan_su_model->find($id);

        if (!$data) {
            resError('Không tìm thấy đánh giá');
        }

        resSuccess($data, 'Lấy thông tin đánh giá thành công');
    }

    public function edit_post($id)
    {
        $oldData = $this->Hrm_danh_gia_nhan_su_model->find($id);
        if (!$oldData) {
            resError('Không tìm thấy đánh giá!');
        }

        $this->load->library(['Validator']);
        $data = [
            'diem_so' => commonRequest('diem_so') ? commonRequest('diem_so') : null,
            'nhan_xet' => commonRequest('nhan_xet') ? commonRequest('nhan_xet') : null,
            'thang' => commonRequest('thang') ? commonRequest('thang') : null,
        ];

        $rules = [
            'diem_so' => 'required',
            'thang' => 'required',
        ];

        $customMessages = [
            'diem_so.required' => 'Điểm số bắt buộc nhập',
            'thang.required' => 'Tháng bắt buộc chọn',
        ];

        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors(), 'Vui lòng kiểm tra lại các trường cần nhập');
        }

        $this->db->trans_start();

        //Sửa đánh giá
        $this->Hrm_danh_gia_nhan_su_model
            ->where('id_danh_gia_nhan_su', $id)
            ->update($data);

        $newData = $this->Hrm_danh_gia_nhan_su_model->find($id);
        $newData['dsDanhGia'] = $this->Hrm_danh_gia_nhan_su_model->where('id_nhan_vien', $oldData['id_nhan_vien'])->get();

        $this->db->trans_commit();

        resSuccess($newData, 'Chỉnh sửa đánh giá thành công', 200, true);
    }

    public function delete_post($id)
    {
        $oldData = $this->Hrm_danh_gia_nhan_su_model->find($id);
        if (!$oldData) {
            resError('Không tìm thấy đánh giá!');
        }

        $this->db->trans_start();
        $ttgd['data'] = $this->Hrm_danh_gia_nhan_su_model->where('id_danh_gia_nhan_su', $id)->delete();
        $ttgd['dsDanhGia'] = $this->Hrm_danh_gia_nhan_su_model->where('id_nhan_vien', $oldData['id_nhan_vien'])->get();

        $this->createLog('delete', 'Xóa đánh giá nhân sự', $ttgd,  null, 'hrm_danh_gia_nhan_su');
        $this->db->trans_commit();
        resSuccess($ttgd, 'Xóa thành công');
    }
}
