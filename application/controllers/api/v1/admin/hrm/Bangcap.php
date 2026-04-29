<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_bang_cap_model $Hrm_bang_cap_model
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Fileupload $fileupload
 */



class Bangcap extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_bang_cap_model', 'Hrm_nhan_vien_model']);
        $this->load->library(['Validator', 'Fileupload']);
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

        $data = $this->Hrm_bang_cap_model->getAll($start, $length, $searchValue, $orderBy, $searchKey);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }
    public function create_post()
    {
        $this->load->library(['Validator']);

        $data = [
            'id_nhan_vien' => commonRequest('id_nhan_vien') ? commonRequest('id_nhan_vien') : null,
            'tu_thang' => commonRequest('tu_thang') ? commonRequest('tu_thang') : null,
            'den_thang' => commonRequest('den_thang') ? commonRequest('den_thang') : null,
            'noi_dao_tao' => commonRequest('noi_dao_tao') ? commonRequest('noi_dao_tao') : null,
            'chuyen_nganh' => commonRequest('chuyen_nganh') ? commonRequest('chuyen_nganh') : null,
            'trinh_do_dt' => commonRequest('trinh_do_dt') ? commonRequest('trinh_do_dt') : null,
            'xep_loai_dt' => commonRequest('xep_loai_dt') ? commonRequest('xep_loai_dt') : null,
        ];
        // resSuccess($data);
        $nhanvien = $this->Hrm_nhan_vien_model->find($data['id_nhan_vien']);
        if (!$nhanvien) {
            resError('Không tìm thấy nhân viên này. Vui lòng tải lại trang');
        }
        $rules = [
            'tu_thang' => 'required|date',
            // 'den_thang' => 'required|date',
            'noi_dao_tao' => 'required',
            'chuyen_nganh' => 'required',
            'trinh_do_dt' => 'required',
            // 'xep_loai_dt' => 'required',
        ];

        $customMessages = [
            'tu_ngay.required' => 'Ngày thời gian bắt đầu học bắt buộc nhập',
            'tu_ngay.date' => 'Ngày thời gian bắt đầu học chưa đúng định dạng',
            // 'den_ngay.required' => 'Ngày thời gian kết thúc học bắt buộc nhập',
            // 'den_ngay.date' => 'Ngày thời gian kết thúc học chưa đúng định dạng',
            'noi_dao_tao.required' => 'Nơi đào tạo bắt buộc nhập',
            'chuyen_nganh.required' => 'Chuyên ngành bắt buộc nhập',
            'trinh_do_dt.required' => 'Trình độ đào tạo bắt buộc nhập',
            // 'xep_loai_dt.required' => 'Xếp loại bắt buộc chọn',
        ];

        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors(), 'Vui lòng kiểm tra lại các trường cần nhập');
        }

        $this->db->trans_start();

        //Thêm bằng cấp
        $result = $this->Hrm_bang_cap_model->create($data);

        $this->db->trans_commit();

        resSuccess($result, 'Thêm bằng cấp thành công');
    }

    public function show_get($id)
    {
        $data = $this->Hrm_bang_cap_model->find($id);
        if (!$data) {
            resError('Không tìm thấy thông tin bằng cấp này', REST_Controller::HTTP_NOT_FOUND);
        }
        resSuccess($data, 'Success', REST_Controller::HTTP_OK, true);
    }
    public function update_post($id)
    {
        $this->load->library(['Validator']);

        $data = [
            'id_nhan_vien' => commonRequest('id_nhan_vien') ? commonRequest('id_nhan_vien') : null,
            'tu_thang' => commonRequest('tu_thang') ? commonRequest('tu_thang') : null,
            'den_thang' => commonRequest('den_thang') ? commonRequest('den_thang') : null,
            'noi_dao_tao' => commonRequest('noi_dao_tao') ? commonRequest('noi_dao_tao') : null,
            'chuyen_nganh' => commonRequest('chuyen_nganh') ? commonRequest('chuyen_nganh') : null,
            'trinh_do_dt' => commonRequest('trinh_do_dt') ? commonRequest('trinh_do_dt') : null,
            'xep_loai_dt' => commonRequest('xep_loai_dt') ? commonRequest('xep_loai_dt') : null,
        ];
        // resError($data);
        $nhanvien = $this->Hrm_nhan_vien_model->find($data['id_nhan_vien']);
        if (!$nhanvien) {
            resError('Không tìm thấy nhân viên này. Vui lòng tải lại trang');
        }
        $rules = [
            'tu_thang' => 'required|date',
            // 'den_thang' => 'required|date',
            'noi_dao_tao' => 'required',
            'chuyen_nganh' => 'required',
            'trinh_do_dt' => 'required',
            // 'xep_loai_dt' => 'required',
        ];

        $customMessages = [
            'tu_ngay.required' => 'Ngày thời gian bắt đầu học bắt buộc nhập',
            'tu_ngay.date' => 'Ngày thời gian bắt đầu học chưa đúng định dạng',
            // 'den_ngay.required' => 'Ngày thời gian kết thúc học bắt buộc nhập',
            // 'den_ngay.date' => 'Ngày thời gian kết thúc học chưa đúng định dạng',
            'noi_dao_tao.required' => 'Nơi đào tạo bắt buộc nhập',
            'chuyen_nganh.required' => 'Chuyên ngành bắt buộc nhập',
            'trinh_do_dt.required' => 'Trình độ đào tạo bắt buộc nhập',
            // 'xep_loai_dt.required' => 'Xếp loại bắt buộc chọn',
        ];
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);
        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors(), 'Vui lòng kiểm tra lại các trường cần nhập');
        }
        $this->db->trans_start();

        // resSuccess($data);
        //Thêm bằng cấp
        $this->Hrm_bang_cap_model->where('id_bang_cap', $id)->update($data);
        $result = $this->Hrm_bang_cap_model->where('id_bang_cap', $id)->first();

        $this->db->trans_commit();

        resSuccess($result, 'Cập nhật bằng cấp thành công');
    }

    public function delete_post($id)
    {
        // Tìm thông tin bằng cấp theo ID
        $bangcap = $this->Hrm_bang_cap_model->find($id);
        if (!$bangcap) {
            resError('Không tìm thấy thông tin bằng cấp này.', REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        // Bắt đầu transaction
        $this->db->trans_start();

        // Xóa thông tin bằng cấp (cập nhật trường `deleted_at` và `deleted_user_id`)
        $auth = $this->getUserLogin();
        $this->Hrm_bang_cap_model->where('id_bang_cap', $id)->delete();

        // Tạo log xóa
        $this->createLog('delete', 'Xóa thông tin bằng cấp', $bangcap, null, 'hrm_bang_cap');

        // Commit transaction
        $this->db->trans_commit();

        // Trả về kết quả thành công
        resSuccess(null, 'Xóa thông tin bằng cấp thành công');
    }
}
