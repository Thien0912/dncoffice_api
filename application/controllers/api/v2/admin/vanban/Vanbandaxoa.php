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
 * @property Ql_thong_bao_model $Ql_thong_bao_model
 * @property E_don_vi_xu_ly_da_xem_model $E_don_vi_xu_ly_da_xem_model
 * @property E_vb_khoi_co_quan_model $E_vb_khoi_co_quan_model
 * @property E_vb_co_quan_model $E_vb_co_quan_model
 * @property E_tag_model $E_tag_model
 */



class Vanbandaxoa extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model([
            'E_van_ban_model',
            'E_file_dinh_kem_model',
            'E_but_phe_model',
            'E_xu_ly_model',
            'E_don_vi_xu_ly_model',
            'E_don_vi_model',
            'E_bao_cao_model',
            'E_don_vi_xu_ly_da_xem_model',
            'E_vb_khoi_co_quan_model',
            'E_vb_co_quan_model',
            'Ql_thong_bao_model',
            'E_tag_model',
        ]);
        $this->load->library(['Validator', 'Fileupload', 'Common', 'Pxl']);
    }

    public function index_get()
    {
        $auth = $this->getUserLogin();

        $searchKey = [];
        if (is_string(commonRequest('searchKey'))) {
            $searchKey = json_decode(commonRequest('searchKey'), true);
        }

        $data = [
            'start'         => commonRequest('start') ?? 0,
            'length'        => commonRequest('length') ?? 10,
            'searchValue'   => commonRequest('searchValue') ?? null,
            'order'         => commonRequest('order') ?? [],
            'columns'       => commonRequest('columns') ?? [],
            // 'searchKey'     => commonRequest('searchKey') ?? [],
            'searchKey'     => $searchKey,
            'fromDate'      => commonRequest('fromDate') ?? null,
            'toDate'        => commonRequest('toDate') ?? null
        ];

        $dataSource = [
            'order' => commonRequest('order') ?? [],
        ];

        $response = $this->E_van_ban_model->getVanbandaxoa($data['start'], $data['length'], $data['searchValue'], $data['order'], $data['columns'], $data['searchKey'],  $data['fromDate'], $data['toDate'], $auth, $dataSource);
        $tags = $this->E_tag_model->get_user_tags($auth['ql_nguoi_dung_id']);
        resSuccess($response['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $response['recordsTotal'],
            'recordsFiltered' => $response['recordsFiltered'],
            'tags' => $tags,
            'thoi_han' => $response['thoi_han'],
            'sql' => $response['sql'],
        ]);
    }
    public function show_get($id)
    {
        $auth = $this->getUserLogin();
        $vb = $this->E_van_ban_model->detail_vanbandendonvi($id, $auth);
        resSuccess($vb['data'], 'Lấy thông tin thành công');
    }

    public function files_v2_post()
    {
        $json = json_decode($this->input->raw_input_stream, true);

        $page     = isset($json['page']) ? (int)$json['page'] : 1;
        $limit    = isset($json['limit']) ? (int)$json['limit'] : 20;
        $search   = isset($json['search']) ? $json['search'] : '';
        $fileType = isset($json['fileType']) ? $json['fileType'] : '';
        $author   = isset($json['author']) ? $json['author'] : '';
        $date     = isset($json['date']) ? $json['date'] : null;

        $offset = ($page - 1) * $limit;

        $filters = [
            'search'   => $search,
            'fileType' => $fileType,
            'author'   => $author,
            'date'     => $date
        ];

        $files = $this->E_van_ban_model->getFiles_v2('trash', $offset, $limit, $filters);

        $response = [
            'status'   => true,
            'message'  => 'Tìm kiếm file đính kèm thành công',
            'page'     => $page,
            'limit'    => $limit,
            'offset'   => $offset,
            'nextPage' => $page + 1,
            'total'    => $files['total'],
            'data'     => $files['data']
        ];

        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode($response, JSON_UNESCAPED_UNICODE));
    }

    public function restore_post()
    {
        $auth = $this->getUserLogin();
        $id_van_ban = commonRequest('id_van_ban');

        if (!$id_van_ban) {
            resError('Vui lòng chọn văn bản cần khôi phục!');
        }

        $this->db->trans_start();

        // Xóa thông tin đã xóa trong e_van_ban_da_xoa
        $this->db->where('id_van_ban', $id_van_ban);
        $this->db->where('id_don_vi', $auth['id_don_vi']);
        $this->db->delete('e_van_ban_da_xoa');

        // Khôi phục văn bản trong bảng e_van_ban
        $this->db->where('id_van_ban', $id_van_ban);
        $this->db->update('e_van_ban', ['deleted_at' => null]);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            resError('Khôi phục văn bản thất bại!');
        }

        resSuccess([], 'Khôi phục văn bản thành công!');
    }

    public function delete_post()
    {
        $auth = $this->getUserLogin();
        $id_van_ban = commonRequest('id_van_ban');

        if (!$id_van_ban) {
            resError('Vui lòng chọn văn bản cần xóa vĩnh viễn!');
        }

        $this->db->trans_start();

        // 1. Cập nhật trạng thái xóa vĩnh viễn trong bảng e_van_ban_da_xoa
        $this->db->where('id_van_ban', $id_van_ban);
        $this->db->where('id_don_vi', $auth['id_don_vi']);
        $this->db->update('e_van_ban_da_xoa', ['da_xoa_vinh_vien' => 1]);


        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            resError('Xóa vĩnh viễn văn bản thất bại!');
        }

        resSuccess([], 'Xóa vĩnh viễn văn bản thành công!');
    }

}
