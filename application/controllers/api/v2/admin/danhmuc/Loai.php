<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property E_loai_model $E_loai_model
 * @property E_don_vi_model $E_don_vi_model
 * @property Fileupload $fileupload
 */



class Loai extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['E_loai_model']);
    }

    public function index_get()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Tài khoản không hợp lệ', REST_Controller::HTTP_UNAUTHORIZED);
        }

        // Lấy theo phòng ban
        if(commonRequest('theo_phong_ban') && commonRequest('id_don_vi_nguoi_dung')){
            $this->db->from('e_loai');
            $this->db->where('is_disabled', 0);
            $this->db->group_start();
            $this->db->where('id_don_vi', commonRequest('id_don_vi_nguoi_dung'));
            $this->db->or_where('id_don_vi', null);
            $this->db->group_end();

            $query = $this->db->get();
            $data = $query->result_array();

            resSuccess($data, 'Success', REST_Controller::HTTP_OK, true);
        }

        $searchKey = [];
        if (is_string(commonRequest('searchKey'))) {
            $searchKey = json_decode(commonRequest('searchKey'), true);
        }

        $data = [
            'start' => commonRequest('start') ?? 0,
            'length' => commonRequest('length') ?? 10,
            'searchValue' => commonRequest('searchValue') ?? null,
            'order' => commonRequest('order') ?? [],
            'columns' => commonRequest('columns') ?? [],
            // 'columnControl' => commonRequest('columnControl') ?? [],
            'searchKey' => $searchKey,
            'fromDate' => commonRequest('fromDate') ? commonRequest('fromDate') : null,
            'toDate' => commonRequest('toDate') ? commonRequest('toDate') : null
        ];
        $dataSource = [
            'order' => commonRequest('order') ?? [],
        ];

        $data = $this->E_loai_model->getAll($data['start'], $data['length'], $data['searchValue'], $data['order'], $data['columns'], $data['searchKey']);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function create_post()
    {
        $this->load->library(['Validator']);

        $data = [
            'ten_loai' => commonRequest('ten_loai') ? commonRequest('ten_loai') : null,
            'tien_to' => commonRequest('tien_to') ? commonRequest('tien_to') : null,
            'hau_to' => commonRequest('hau_to') ? commonRequest('hau_to') : null,
            'id_don_vi' => commonRequest('id_don_vi') ? commonRequest('id_don_vi') : null,
            'thuoc_nhom' => commonRequest('thuoc_nhom') ? commonRequest('thuoc_nhom') : null,
        ];

        $rules = [
            'ten_loai' => 'required',
            'hau_to' => 'required',
            'thuoc_nhom' => 'required',
        ];

        $customMessages = [
            'ten_loai.required' => 'Tên loại bắt buộc nhập',
            'hau_to.required' => 'Hậu tố loại bắt buộc nhập',
            'thuoc_nhom.required' => 'Thuộc nhóm là dữ liệu bắt buộc',
        ];
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();

        //Lưu văn bản đến
        $result = $this->E_loai_model->create($data);
        $this->createLog('Create', 'Thêm loại văn bản: ' . $result['ten_loai'], NULL, $result, 'e_loai');

        $this->db->trans_commit();

        resSuccess($result);
    }

    public function show_get($id){
        $loaiVanBan = $this->E_loai_model->find($id);
        if($loaiVanBan['id_don_vi']){
            $donvi = $this->E_don_vi_model->find($loaiVanBan['id_don_vi']);
            $loaiVanBan['ten_don_vi'] = $donvi['ten_don_vi'];
        }
        resSuccess($loaiVanBan);
    }

    public function update_post($id)
    {
        $loaiVanBan = $this->E_loai_model->find($id);
        if(!$loaiVanBan){
            resError('Không tìm thấy loại văn bản', REST_Controller::HTTP_NOT_FOUND);
        }

        // Tích hợp inline edit //
        if (commonRequest('inline_edit')) {
            $this->db->trans_start();
            $this->E_loai_model
                ->where('id_loai', $id)
                ->update([
                    commonRequest('column') => commonRequest('value')
                ]);
            $this->db->trans_commit();
            resSuccess($this->E_loai_model->find($id));
        }

        $data = [
            'ten_loai' => commonRequest('ten_loai') ? commonRequest('ten_loai') : null,
            'tien_to' => commonRequest('tien_to') ? commonRequest('tien_to') : null,
            'hau_to' => commonRequest('hau_to') ? commonRequest('hau_to') : null,
            'id_don_vi' => commonRequest('id_don_vi') ? commonRequest('id_don_vi') : null,
            'thuoc_nhom' => commonRequest('thuoc_nhom') ? commonRequest('thuoc_nhom') : null,
        ];

        $rules = [
            'ten_loai' => 'required',
            'hau_to' => 'required',
            'thuoc_nhom' => 'required',
        ];

        $customMessages = [
            'ten_loai.required' => 'Tên loại bắt buộc nhập',
            'hau_to.required' => 'Hậu tố loại bắt buộc nhập',
            'thuoc_nhom.required' => 'Thuộc nhóm là dữ liệu bắt buộc',
        ];
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();

        //Lưu văn bản đến
        $result = $this->E_loai_model
                ->where('id_loai', $id)
                ->update($data);

        $newLoaiVanBan = $this->E_loai_model->find($id);
        $this->createLog('Create', 'Sửa loại văn bản: ' . $newLoaiVanBan['ten_loai'], $loaiVanBan, $newLoaiVanBan, 'e_loai');

        $this->db->trans_commit();

        resSuccess($result);
    }
}
