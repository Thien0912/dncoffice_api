<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_nhan_vien_khen_thuong_model $Hrm_nhan_vien_khen_thuong_model
 * @property Hrm_khen_thuong_model $Hrm_khen_thuong_model
 * @property Fileupload $fileupload
 */



class Khenthuong extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model([
            'Hrm_nhan_vien_khen_thuong_model',
            'Hrm_khen_thuong_model',
        ]);
        $this->load->library(['Validator', 'Fileupload']);
    }

    public function index_get()
    {
        $auth  = $this->getUserLogin();

        $data = [
            'start' => commonRequest('start') ?? 0,
            'length' => commonRequest('length') ?? 10,
            'searchValue' => commonRequest('searchValue') ?? null,
            'order' => commonRequest('order') ?? [],
            'columns' => commonRequest('columns') ?? [],
            // 'columnControl' => commonRequest('columnControl') ?? [],
            'searchKey' => commonRequest('searchKey') ? commonRequest('searchKey') : [],
            'fromDate' => commonRequest('fromDate') ? commonRequest('fromDate') : null,
            'toDate' => commonRequest('toDate') ? commonRequest('toDate') : null
        ];

        $data = $this->Hrm_khen_thuong_model->getAll_KhenThuong($data['start'], $data['length'], $data['searchValue'], $data['order'], $data['columns'], $data['searchKey'],  $data['fromDate'], $data['toDate'], $auth);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
            // 'sql' => $data['sql'],
        ]);
    }

    public function create_post()
    {
        $validator = new Validator();

        $auth = $this->getUserLogin();
        $data = [
            'id_nhan_vien' => commonRequest('id_nhan_vien') ? commonRequest('id_nhan_vien') : null,
            'loai_khen_thuong' => commonRequest('loai_khen_thuong') ? commonRequest('loai_khen_thuong') : null,
            'ly_do_khen_thuong' => commonRequest('ly_do_khen_thuong') ? commonRequest('ly_do_khen_thuong') : null,
            'ngay_khen_thuong' => commonRequest('ngay_khen_thuong') ? commonRequest('ngay_khen_thuong') : null,
            'cap_khen_thuong' => commonRequest('cap_khen_thuong') ? commonRequest('cap_khen_thuong') : null,
            'ghi_chu_khen_thuong' => commonRequest('ghi_chu_khen_thuong') ? commonRequest('ghi_chu_khen_thuong') : null,
        ];

        $rules = [
            'id_nhan_vien' => 'required|integer',
            'ngay_khen_thuong' => 'required|date',
            'loai_khen_thuong' => 'required',
            'ly_do_khen_thuong' => 'required',
            'cap_khen_thuong' => 'required',
        ];

        $customMessages = [
            'id_nhan_vien.required' => 'Vui lòng chọn nhân viên',
            'ngay_khen_thuong.required' => 'Vui lòng nhập ngày khen thưởng',
            'ngay_khen_thuong.date' => 'Ngày khen thưởng không đúng định dạng',
            'loai_khen_thuong.required' => 'Vui lòng nhập loại khen thưởng',
            'ly_do_khen_thuong.required' => 'Vui lòng nhập lý do khen thưởng',
            'cap_khen_thuong.required' => 'Vui lòng nhập cấp khen thưởng',
        ];

        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();

        $khenthuong = $this->Hrm_nhan_vien_khen_thuong_model->create($data);

        //create log
        $this->createLog('create', 'Tạo mới khen thưởng', null, $khenthuong, 'hrm_nhan_vien_khen_thuong');
        $this->db->trans_commit();
        resSuccess($khenthuong, 'Thêm thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }

    public function show_get($id)
    {
        $khenthuong = $this->Hrm_nhan_vien_khen_thuong_model->find($id);
        if (!$khenthuong) {
            resError('Không tìm thấy khen thưởng này');
        }
        resSuccess($khenthuong, 'Lấy thông tin thành công ');
    }
    public function update_post($id)
    {
        $data = [
            'id_nhan_vien' => commonRequest('id_nhan_vien') ? commonRequest('id_nhan_vien') : null,
            'loai_khen_thuong' => commonRequest('loai_khen_thuong') ? commonRequest('loai_khen_thuong') : null,
            'ly_do_khen_thuong' => commonRequest('ly_do_khen_thuong') ? commonRequest('ly_do_khen_thuong') : null,
            'ngay_khen_thuong' => commonRequest('ngay_khen_thuong') ? commonRequest('ngay_khen_thuong') : null,
            'cap_khen_thuong' => commonRequest('cap_khen_thuong') ? commonRequest('cap_khen_thuong') : null,
            'ghi_chu_khen_thuong' => commonRequest('ghi_chu_khen_thuong') ? commonRequest('ghi_chu_khen_thuong') : null,
        ];
        $rules = [
            'ngay_khen_thuong' => 'required|date',
            'loai_khen_thuong' => 'required',
            'ly_do_khen_thuong' => 'required',
            'cap_khen_thuong' => 'required',
        ];
        $customMessages = [
            'ngay_khen_thuong.required' => 'Vui lòng nhập ngày khen thưởng',
            'ngay_khen_thuong.date' => 'Ngày khen thưởng không đúng định dạng',
            'loai_khen_thuong.required' => 'Vui lòng nhập loại khen thưởng',
            'ly_do_khen_thuong.required' => 'Vui lòng nhập lý do khen thưởng',
            'cap_khen_thuong.required' => 'Vui lòng nhập cấp khen thưởng',
        ];
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);
        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();
        $this->Hrm_nhan_vien_khen_thuong_model->where('id_nhan_vien_khen_thuong', $id)->update($data);
        //create log
        $khenthuong = $this->Hrm_nhan_vien_khen_thuong_model->find($id);
        $this->createLog('update', 'Cập nhật khen thưởng', null, $khenthuong, 'hrm_nhan_vien_khen_thuong');
        $this->db->trans_commit();
        resSuccess($khenthuong, 'Cập nhật thành công');
    }
    public function delete_post($id)
    {
        $khenthuong = $this->Hrm_nhan_vien_khen_thuong_model->find($id);
        if (!$this->Hrm_nhan_vien_khen_thuong_model) {
            resError('Không tìm thấy khen thưởng này');
        }
        $this->db->trans_start();
        $this->Hrm_nhan_vien_khen_thuong_model->where('id_nhan_vien_khen_thuong', $id)->delete();
        //create log
        $this->createLog('delete', 'Xóa khen thưởng', $khenthuong, null, 'hrm_nhan_vien_khen_thuong');
        $this->db->trans_commit();

        resSuccess($khenthuong, 'Xóa thành công');
    }
}
