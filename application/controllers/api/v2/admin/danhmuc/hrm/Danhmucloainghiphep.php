<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db  
 * @property Hrm_danh_muc_loai_nghi_phep_model $Hrm_danh_muc_loai_nghi_phep_model
 */



class Danhmucloainghiphep extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_danh_muc_loai_nghi_phep_model']);
    }

    public function index_get()
    {
        $id = commonRequest('id') ?? null;
        if($id){
            $result = $this->Hrm_danh_muc_loai_nghi_phep_model->find($id);
            if (!$result) {
                resError('Không tìm thấy dữ liệu', REST_Controller::HTTP_NOT_FOUND);
            }
            resSuccess($result);
        }

        $data = [
            'start' => commonRequest('start') ?? 0,
            'length' => commonRequest('length') ?? 10,
            'searchValue' => commonRequest('searchValue') ?? null,
            'order' => commonRequest('order') ?? [],
            'columns' => commonRequest('columns') ?? [],
        ];

        $data = $this->Hrm_danh_muc_loai_nghi_phep_model->getAll($data['start'], $data['length'], $data['searchValue'], $data['order'], $data['columns']);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function show_get($id) {
        $result = $this->Hrm_danh_muc_loai_nghi_phep_model->find($id);
        if (!$result) {
            resError('Không tìm thấy dữ liệu', REST_Controller::HTTP_NOT_FOUND);
        }
        resSuccess($result);
    }

    public function create_post()
    {
        $this->load->library(['Validator']);

        $data = [
            'ma_loai_phep' => commonRequest('ma_loai_phep') ? commonRequest('ma_loai_phep') : null,
            'ten_loai_phep' => commonRequest('ten_loai_phep') ? commonRequest('ten_loai_phep') : null,
            'ghi_chu' => commonRequest('ghi_chu') ? commonRequest('ghi_chu') : '',
            'so_ngay_mac_dinh' => commonRequest('so_ngay_mac_dinh') !== null ? (float)commonRequest('so_ngay_mac_dinh') : 0,
            'co_tinh_luong' => commonRequest('co_tinh_luong') !== null ? (int)commonRequest('co_tinh_luong') : 1,
        ];

        $rules = [
            'ma_loai_phep' => 'required',
            'ten_loai_phep' => 'required',
        ];

        $customMessages = [
            'ma_loai_phep.required' => 'Mã loại nghỉ phép bắt buộc nhập',
            'ten_loai_phep.required' => 'Tên loại nghỉ phép bắt buộc nhập',
        ];

        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();
        $result = $this->Hrm_danh_muc_loai_nghi_phep_model->create($data);
        $this->db->trans_commit();

        resSuccess($result, 'Thêm loại nghỉ phép thành công');
    }

    public function update_post($id)
    {
        $this->load->library(['Validator']);

        $itemToken = $this->Hrm_danh_muc_loai_nghi_phep_model->find($id);
        if (!$itemToken) {
            resError('Không tìm thấy dữ liệu', REST_Controller::HTTP_NOT_FOUND);
        }

        $data = [
            'ma_loai_phep' => commonRequest('ma_loai_phep') ? commonRequest('ma_loai_phep') : null,
            'ten_loai_phep' => commonRequest('ten_loai_phep') ? commonRequest('ten_loai_phep') : null,
            'ghi_chu' => commonRequest('ghi_chu') ? commonRequest('ghi_chu') : '',
            'so_ngay_mac_dinh' => commonRequest('so_ngay_mac_dinh') !== null ? (float)commonRequest('so_ngay_mac_dinh') : 0,
            'co_tinh_luong' => commonRequest('co_tinh_luong') !== null ? (int)commonRequest('co_tinh_luong') : 1,
        ];

        $rules = [
            'ma_loai_phep' => 'required',
            'ten_loai_phep' => 'required',
        ];

        $customMessages = [
            'ma_loai_phep.required' => 'Mã loại nghỉ phép bắt buộc nhập',
            'ten_loai_phep.required' => 'Tên loại nghỉ phép bắt buộc nhập',
        ];

        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();
        $this->Hrm_danh_muc_loai_nghi_phep_model->where('id_loai_phep', $id)->update($data);
        $result = $this->Hrm_danh_muc_loai_nghi_phep_model->find($id);
        $this->db->trans_commit();

        resSuccess($result, 'Cập nhật thành công');
    }

    public function delete_post($id)
    {
        $item = $this->Hrm_danh_muc_loai_nghi_phep_model->find($id);
        if (!$item) {
            resError('Không tìm thấy dữ liệu', REST_Controller::HTTP_NOT_FOUND);
        }

        $this->db->trans_start();
        $this->Hrm_danh_muc_loai_nghi_phep_model->delete($id);
        $this->db->trans_commit();

        resSuccess([], 'Xóa thành công');
    }
}
