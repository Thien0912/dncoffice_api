<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property DB_query_builder $db 
 * @property Hrm_thu_tuc_thoi_viec_model $Hrm_thu_tuc_thoi_viec_model
 * @property Fileupload $fileupload
 */
class Thutucthoiviec extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_thu_tuc_thoi_viec_model']);
        $this->load->library(['Validator', 'Fileupload']);
    }

    private function getValidationRules()
    {
        $data = [
            'ten_thu_tuc' => commonRequest('ten_thu_tuc') ?? null,
            'nhom_thu_tuc' => commonRequest('nhom_thu_tuc') ?? null,
        ];

        $rules = [
            'ten_thu_tuc' => 'required',
            // 'nhom_thu_tuc' => 'required',
        ];

        $customMessages = [
            'ten_thu_tuc.required' => 'Vui lòng nhập tên thủ tục',
            // 'nhom_thu_tuc.required' => 'Vui lòng nhập nhóm thủ tục',
        ];

        return compact('data', 'rules', 'customMessages');
    }

    private function validate_data()
    {
        $validator = new Validator();
        $validation = $this->getValidationRules();

        $validator->setCustomMessages($validation['customMessages']);

        if (!$validator->validate($validation['data'], $validation['rules'])) {
            resBadrequest($validator->errors());
        }
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

        $data = $this->Hrm_thu_tuc_thoi_viec_model->getAll($start, $length, $searchValue, $orderBy, $searchKey);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function create_post()
    {
        // Validate dữ liệu
        $this->validate_data();

        // Lấy dữ liệu đã validate
        $data = $this->getValidationRules()['data'];

        // Bổ sung thông tin người tạo và thời gian tạo
        $auth = $this->getUserLogin();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['created_user_id'] = $auth['ql_nguoi_dung_id'];

        $this->db->trans_start();
        $tttv = $this->Hrm_thu_tuc_thoi_viec_model->create($data);
        //create log
        $this->createLog('create', 'Tạo mới thủ tục thôi việc', null, $tttv, 'hrm_thu_tuc_thoi_viec');
        $this->db->trans_commit();
        resSuccess($tttv, 'Thêm thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }
    public function delete_post($id)
    {
        $tttv = $this->Hrm_thu_tuc_thoi_viec_model->where('id_tttv', $id)->get();
        if (!$tttv) {
            resError('Không tìm thấy thủ tục này vui lòng tải lại trang để tiếp tục.');
        }

        $nhanVienThoiViec = $this->db
            ->from('hrm_nhan_vien_thoi_viec')
            ->select('*')
            ->where('id_tttv', $id)
            ->get()
            ->result_array();
        if (!empty($nhanVienThoiViec)) {
            resError('Thủ tục đang được sử dụng ở bảng khác!');
        }

        $this->db->trans_start();
        $this->Hrm_thu_tuc_thoi_viec_model->where('id_tttv', $id)->delete($id);
        //create log
        $this->createLog('delete', 'Xóa thủ tục thôi việc', null, $tttv, 'hrm_thu_tuc_thoi_viec');
        $this->db->trans_commit();
        resSuccess('Xóa thủ tục thành công');
    }
    public function update_post($id)
    {
        $tttv = $this->Hrm_thu_tuc_thoi_viec_model->where('id_tttv', $id)->first();
        if (!$tttv) {
            resError('Không tìm thấy thủ tục này vui lòng tải lại trang để tiếp tục.');
        }

        // Validate dữ liệu
        $this->validate_data();

        // Lấy dữ liệu đã validate
        $data = $this->getValidationRules()['data'];

        // Bổ sung thông tin người cập nhật và thời gian cập nhật
        $auth = $this->getUserLogin();
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['updated_user_id'] = $auth['ql_nguoi_dung_id'];

        $this->db->trans_start();
        $tttv_new = $this->Hrm_thu_tuc_thoi_viec_model->where('id_tttv', $id)->update($data);
        //create log
        $this->createLog('update', 'Cập nhật thông tin thủ tục thôi việc', $tttv, $tttv_new, 'hrm_thu_tuc_thoi_viec');
        $this->db->trans_commit();
        resSuccess($tttv, 'Cập nhật thành công');
    }
}
