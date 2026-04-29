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
 */



class Trashhopdong extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_hop_dong_model']);
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

        $fromDate = commonRequest('fromDate') ? commonRequest('fromDate') : null;
        $toDate = commonRequest('toDate') ? commonRequest('toDate') : null;

        $data = $this->Hrm_hop_dong_model->getAllTrash($start, $length, $searchValue, $orderBy, $searchKey);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function restore_post()
    {
        $ids = commonRequest('ids');
        if (empty($ids)) {
            resError('Vui lòng chọn hợp đồng muốn khôi phục', REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }
        $this->db->trans_start();
        $items = $this->Hrm_hop_dong_model->whereIn('id_hop_dong', $ids)->where('deleted_at IS NOT NULL')->get();
        if (count($ids) != count($items)) {
            resError('Có lỗi xảy ra'); // truyền id không tương ứng với danh sách
        }

        $this->Hrm_hop_dong_model->whereIn('id_hop_dong', $ids)->update([
            'deleted_at' => null
        ]);
        $this->createLog('restore', 'Khôi phục hợp đồng', $items, null, 'hrm_hop_dong');
        $this->db->trans_commit();
        resSuccess(null, 'Khôi phục thành công');
    }

    public function force_delete_post()
    {

        $ids = commonRequest('ids');
        $this->db->trans_start();

        $items = $this->Hrm_hop_dong_model
            ->whereIn('id_hop_dong', $ids)
            ->where('deleted_at IS NOT NULL')
            ->get();

        if (count($ids) != count($items)) {
            resError('Có lỗi xảy ra'); //Lỗi chọn id không khớp với danh sách
        }

        foreach ($items as $hd) {
            // Xóa các phụ lục liên quan
            $dsPhuLucHD = $this->db->select('*')
                ->from('hrm_hop_dong_phu_luc')
                ->where('id_hop_dong', $hd['id_hop_dong'])
                ->get()
                ->result_array();

            if (!empty($dsPhuLucHD)) {
                foreach ($dsPhuLucHD as $pl) {
                    $filesPhuLuc = json_decode($pl['file_phu_luc'], true);
                    foreach ($filesPhuLuc as $filePL) {
                        $this->fileupload->delete($filePL['file_path']);
                    }
                }
            }

            $this->db->where('id_hop_dong', $hd['id_hop_dong'])
                ->delete('hrm_hop_dong_phu_luc');

            // Xóa các file hợp đồng
            $filesHD = json_decode($hd['files_hop_dong'], true);
            foreach ($filesHD as $fildHD) {
                $this->fileupload->delete($fildHD['file_path']);
            }
        }
        $this->Hrm_hop_dong_model->whereIn('id_hop_dong', $ids)->delete();

        $this->createLog('delete', 'Xóa vĩnh viễn hợp đồng', $items,  null, 'hrm_hop_dong');
        $this->db->trans_commit();
        resSuccess(null, 'Xóa thành công');
    }
}
