<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property E_van_ban_model $E_van_ban_model
 * @property E_file_dinh_kem_model $E_file_dinh_kem_model
 * @property E_but_phe_model $E_but_phe_model
 * @property E_xu_ly_model $E_xu_ly_model
 * @property E_don_vi_xu_ly_model $E_don_vi_xu_ly_model
 * @property E_don_vi_model $E_don_vi_model
 * @property E_bao_cao_model $E_bao_cao_model
 * @property Fileupload $fileupload
 * @property Common $common
 * @property Pxl $pxl
 * @property CI_Upload $upload
 * @property E_co_quan_model $E_co_quan_model
 * @property Hrm_hop_dong_model $Hrm_hop_dong_model
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 */



class Trashnhanvien extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_nhan_vien_model']);
        $this->load->library(['Validator', 'Fileupload', 'Common', 'Pxl', 'upload']);
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

        $data = $this->Hrm_nhan_vien_model->getTrash($start, $length, $searchValue, $orderBy, $searchKey);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function restore_post()
    {
        $ids = commonRequest('ids');
        if (empty($ids)) {
            resError('Vui lòng chọn dữ liệu muốn khôi phục', REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }
        $this->db->trans_start();
        $nhanvien = $this->Hrm_nhan_vien_model->whereIn('id_nhan_vien', $ids)->where('deleted_at IS NOT NULL')->get();
        if (count($ids) != count($nhanvien)) {
            resError('Dữ liệu không hợp lệ'); // truyền id không tương ứng với danh sách
        }

        $this->Hrm_nhan_vien_model->whereIn('id_nhan_vien', $ids)->update([
            'deleted_at' => null
        ]);
        $this->createLog('restore', 'Khôi phục nhân viên', $nhanvien, $nhanvien, 'hrm_nhan_vien');
        $this->db->trans_commit();
        resSuccess(null, 'Khôi phục thành công');
    }

    public function force_delete_post()
    {
        $ids = commonRequest('ids');
        $this->db->trans_start();

        $nhanvien = $this->Hrm_nhan_vien_model
            ->whereIn('id_nhan_vien', $ids)
            ->where('deleted_at IS NOT NULL')
            ->get();

        if (count($ids) != count($nhanvien)) {
            resError('Dữ liệu không hợp lệ'); //Lỗi chọn id không khớp với danh sách
        }

        $this->Hrm_nhan_vien_model->whereIn('id_van_ban', $ids)->delete();

        foreach ($nhanvien as $nv) {
            $deleteFile = $this->fileupload->delete($nv['anh_dai_dien']);
        }

        $this->createLog('delete', 'Xóa vĩnh viễn nhân viên', $nhanvien,  null, 'hrm_nhan_vien');
        $this->db->trans_commit();
        resSuccess(null, 'Xóa thành công');
    }
}
