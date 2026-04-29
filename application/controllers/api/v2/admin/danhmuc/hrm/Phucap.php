<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Hrm_quy_dinh_nghi_phep $Hrm_quy_dinh_nghi_phep
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 * @property Hrm_hop_dong_model $Hrm_hop_dong_model
 * @property Hrm_phu_cap_model $Hrm_phu_cap_model
 * @property Fileupload $fileupload
 * @property Common $common
 */



class Phucap extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_nhan_vien_model', 'Ql_nguoi_dung_model', 'Hrm_hop_dong_model', 'Hrm_phu_cap_model']);
        $this->load->library(['Validator', 'Fileupload', 'Common']);
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

        $data = $this->Hrm_phu_cap_model->getAll($start, $length, $searchValue, $orderBy, $searchKey);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function create_post()
    {
        $validator = new Validator();
        $auth = $this->getUserLogin();
        $data = [
            'ma_phu_cap' => commonRequest('ma_phu_cap') ? commonRequest('ma_phu_cap') : null,
            'ten_phu_cap' => commonRequest('ten_phu_cap') ? commonRequest('ten_phu_cap') : null,
            'mo_ta' => commonRequest('mo_ta') ? commonRequest('mo_ta') : null,
            'so_tien' => commonRequest('so_tien') ? commonRequest('so_tien') : null,
            'chiu_thue' => commonRequest('chiu_thue') ? commonRequest('chiu_thue') : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'created_user_id' => $auth['ql_nguoi_dung_id']
        ];

        $rules = [
            'ma_phu_cap' => 'required',
            'ten_phu_cap' => 'required',
        ];

        $customMessages = [
            'ma_phu_cap.required' => 'Mã phụ cấp bắt buộc nhập',
            'ten_phu_cap.required' => 'Tên phụ cấp bắt buộc nhập',
        ];

        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();

        $item = $this->Hrm_phu_cap_model->create($data);

        //create log
        $this->createLog('create', 'Tạo mới phụ cấp', null, $item, 'hrm_phu_cap');
        $this->db->trans_commit();
        resSuccess($item, 'Thêm thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }

    public function update_post($id)
    {
        $validator = new Validator();
        $auth = $this->getUserLogin();

        $dataOld = $this->Hrm_phu_cap_model->find($id);
        if (!$dataOld) {
            resError('Phụ cấp khồng tồn tại', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }

        $data = [
            'ma_phu_cap' => commonRequest('ma_phu_cap') ? commonRequest('ma_phu_cap') : null,
            'ten_phu_cap' => commonRequest('ten_phu_cap') ? commonRequest('ten_phu_cap') : null,
            'mo_ta' => commonRequest('mo_ta') ? commonRequest('mo_ta') : null,
            'so_tien' => commonRequest('so_tien') ? commonRequest('so_tien') : null,
            'chiu_thue' => commonRequest('chiu_thue') ? commonRequest('chiu_thue') : 0,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_user_id' => $auth['ql_nguoi_dung_id']
        ];

        $rules = [
            'ma_phu_cap' => 'required',
            'ten_phu_cap' => 'required',
        ];

        $customMessages = [
            'ma_phu_cap.required' => 'Mã phụ cấp bắt buộc nhập',
            'ten_phu_cap.required' => 'Tên phụ cấp bắt buộc nhập',
        ];

        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();

        $this->Hrm_phu_cap_model->where('id_phu_cap', $id)->update($data);

        $dataNew = $this->Hrm_phu_cap_model->find($id);

        //create log
        $this->createLog('update', 'Cập nhật phụ cấp', $dataOld, $dataNew, 'hrm_phu_cap');
        $this->db->trans_commit();
        resSuccess($dataNew, 'Cập nhật thành công', REST_INSTANCE_Controller::HTTP_OK);
    }

    public function delete_post()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Không tìm thấy người dùng!');
        }

        $ids = commonRequest('ids');
        $this->db->trans_start();
        $items = $this->Hrm_phu_cap_model->whereIn('id_phu_cap', $ids)->get();
        if (count($ids) != count($items)) {
            resError('Dữ liệu không hợp lệ');
        }

        $dsHopDong = $this->db
            ->select('*')
            ->from('hrm_hop_dong_phu_luc')
            ->get()
            ->result_array();
        if ($dsHopDong) {
            foreach ($ids as $id) {
                foreach ($dsHopDong as $item) {
                    if ($item['ids_phu_cap']) {
                        $arrayId = explode(',', $item['ids_phu_cap']);
                        if (in_array($id, $arrayId)) {
                            resError('Phụ cấp đã được dùng trong hợp đồng khác!');
                            break;
                        }
                    }
                }
            }
        }

        $this->Hrm_phu_cap_model->whereIn('id_phu_cap', $ids)->update([
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_user_id' => $auth['ql_nguoi_dung_id']
        ]);

        $this->createLog('delete', 'Xóa phụ cấp', $items,  null, 'hrm_phu_cap');
        $this->db->trans_commit();
        resSuccess(null, 'Xóa thành công');
    }

    public function show_get($id)
    {
        $item = $this->Hrm_phu_cap_model
            ->where('id_phu_cap', $id)
            ->first();

        if (!$item) resError('Không tìm thấy phụ cấp', REST_Controller::HTTP_NOT_FOUND);

        resSuccess($item);
    }
}
