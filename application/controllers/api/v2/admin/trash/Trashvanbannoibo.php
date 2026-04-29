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
 */



class Trashvanbannoibo extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['E_van_ban_model', 'E_file_dinh_kem_model', 'E_but_phe_model', 'E_xu_ly_model', 'E_don_vi_xu_ly_model', 'E_don_vi_model', 'E_bao_cao_model', 'E_co_quan_model']);
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

        $auth = $this->getUserLogin();

        $data = $this->E_van_ban_model->getTrashVanbannoibo($start, $length, $searchValue, $orderBy, $searchKey, $fromDate, $toDate, $auth);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function restore_post()
    {
        $ids = commonRequest('ids');
        if (empty($ids)) {
            resError('Vui lòng chọn văn bản muốn khôi phục', REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }
        $donviId = $this->getUserLogin()['id_don_vi'];
        $this->db->trans_start();
        $vanban = $this->E_van_ban_model
            ->where('loai_van_ban', $this->common::VAN_BAN_NOI_BO)
            ->whereIn('id_van_ban', $ids)
            ->where('e_van_ban.id_don_vi_soan', $donviId)
            ->where('deleted_at IS NOT NULL')
            ->get();

        if (count($ids) != count($vanban)) {
            resError('Có lỗi xảy ra'); // truyền id không tương ứng với danh sách
        }

        $this->E_van_ban_model->whereIn('id_van_ban', $ids)->update([
            'deleted_at' => null
        ]);
        $this->createLog('restore', 'Khôi phục văn bản nội bộ', $vanban, null, 'e_van_ban');
        $this->db->trans_commit();
        resSuccess(null, 'Khôi phục thành công');
    }

    public function force_delete_post()
    {

        $ids = commonRequest('ids');
        $donviId = $this->getUserLogin()['id_don_vi'];
        $this->db->trans_start();
        $vanban = $this->E_van_ban_model
            ->whereIn('id_van_ban', $ids)
            ->where('loai_van_ban', $this->common::VAN_BAN_NOI_BO)
            ->where('id_don_vi_soan', $donviId)
            ->where('deleted_at IS NOT NULL')
            ->get();
        if (count($ids) != count($vanban)) {
            resError('Có lỗi xảy ra'); //Lỗi chọn id không khớp với danh sách
        }

        $files = $this->E_file_dinh_kem_model->whereIn('id_van_ban', $ids)->get();
        $this->E_file_dinh_kem_model->whereIn('id_van_ban', $ids)->delete();

        $this->E_bao_cao_model->whereIn('id_van_ban', $ids)->delete();


        $xuly = $this->E_xu_ly_model->whereIn('id_van_ban', $ids)->get();
        $xulyIds = array_column($xuly, 'id_xu_ly');

        if (!empty($xulyIds)) {
            $this->E_don_vi_xu_ly_model->whereIn('id_xu_ly', $xulyIds)->delete();
        }
        $this->E_xu_ly_model->whereIn('id_van_ban', $ids)->delete();

        $this->db->where_in('id_van_ban', $ids)->delete('e_vb_co_quan');
        $this->db->where_in('id_van_ban', $ids)->delete('e_vb_khoi_co_quan');

        $this->db->where_in('id_van_ban', $ids)->delete('e_ban_hanh');

        $this->E_van_ban_model->whereIn('id_van_ban', $ids)->delete();

        foreach ($files as $file) {
            $deleteFile = $this->fileupload->delete($file['duong_dan']);
        }

        $this->createLog('delete', 'Xóa vĩnh viễn văn bản nội bộ', $vanban,  null, 'e_van_ban');
        $this->db->trans_commit();
        resSuccess(null, 'Xóa thành công');
    }
}
