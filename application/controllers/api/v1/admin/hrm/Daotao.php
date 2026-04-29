<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_nhan_vien_dao_tao_model $Hrm_nhan_vien_dao_tao_model
 * @property Hrm_dao_tao_model $Hrm_dao_tao_model
 */
class Daotao extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model(['Hrm_dao_tao_model', 'Hrm_nhan_vien_dao_tao_model']);
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

        $fromDate = commonRequest('fromDate') ? commonRequest('fromDate') : null;
        $toDate = commonRequest('toDate') ? commonRequest('toDate') : null;

        $data = $this->Hrm_dao_tao_model->getAllDaotao($start, $length, $searchValue, $orderBy, $searchKey, $fromDate, $toDate);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
            'tren_5_ngay' => $data['tren5ngay'],
            'duoi_5_ngay' => $data['duoi5ngay'],
            'hom_nay' => $data['homnay'],
            'qua_han' => $data['quahan']
        ]);
    }

    public function khoadaotao_get()
    {
        $data = $this->Hrm_dao_tao_model->get_all_hrm_dao_tao();
        resSuccess(
            $data,
            'Success',
            REST_Controller::HTTP_OK,
            true,
        );
    }

    public function create_post()
    {
        $validator = new Validator();

        $ngay_bat_dau = commonRequest('ngay_bat_dau') ? commonRequest('ngay_bat_dau') : null;
        $ten_khoa_hoc = commonRequest('ten_khoa_hoc') ? commonRequest('ten_khoa_hoc') : null;
        $ngay_ket_thuc = commonRequest('ngay_ket_thuc') ? commonRequest('ngay_ket_thuc') : null;
        $trang_thai = commonRequest('trang_thai') ? commonRequest('trang_thai') : null;

        // if (!$ngay_bat_dau) {
        //     $validator->addError('', 'ngay_bat_dau', 'Ngày bắt đầu bắt buộc nhập');
        // }

        // if (!$ngay_ket_thuc) {
        //     $validator->addError('', 'ngay_ket_thuc', 'Ngày kết bắt buộc nhập');
        // }

        // if (!$ten_khoa_hoc) {
        //     $validator->addError('', 'ten_khoa_hoc', 'Tên khóa học bắt buộc nhập');
        // }

        // if (!$trang_thai) {
        //     $validator->addError('', 'trang_thai', 'Trạng thái bắt buộc nhập');
        // }


        $data = [
            'ten_khoa_hoc' => $ten_khoa_hoc,
            'noi_dung' => commonRequest('noi_dung') ? commonRequest('noi_dung') : null,
            'ngay_bat_dau' => $ngay_bat_dau,
            'ngay_ket_thuc' => commonRequest('ngay_ket_thuc') ? commonRequest('ngay_ket_thuc') : null,
            'trang_thai' => commonRequest('trang_thai') ? commonRequest('trang_thai') : null,
        ];

        $rules = [
            'ten_khoa_hoc' => 'required',
            // 'noi_dung' => 'required',
            'ngay_bat_dau' => 'date|required',
            // 'ngay_ket_thuc' => 'date|required',
            'trang_thai' => 'required',
        ];

        $customMessages = [
            'ten_khoa_hoc.required' => 'Vui lòng nhập tên khóa học',
            // 'noi_dung.required' => 'Vui lòng nhập nội dung',
            'ngay_bat_dau.date' => 'Ngày bắt đầu không đúng định dạng',
            'ngay_bat_dau.required' => 'Vui lòng nhập ngày bắt đầu',
            // 'ngay_ket_thuc.date' => 'Ngày kết thúc không đúng định dạng',
            // 'ngay_ket_thuc.required' => 'Vui lòng nhập ngày kết thúc',
            'trang_thai.required' => 'Vui lòng nhập trạng thái',
        ];

        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors(), 'Vui lòng nhập các trường bắt buộc!');
        }

        $this->db->trans_start();

        $khoadaotao = $this->Hrm_dao_tao_model->create($data);

        //create log
        $this->createLog('create', 'Tạo mới khóa đào tạo', null, $khoadaotao, 'hrm_dao_tao');
        $this->db->trans_commit();
        resSuccess($khoadaotao, 'Thêm thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }

    public function create_nhanvien_daotao_post()
    {
        $validator = new Validator();

        $id_dao_tao = commonRequest('id_dao_tao') ? commonRequest('id_dao_tao') : null;
        $ket_qua = commonRequest('ket_qua') ? commonRequest('ket_qua') : null;
        $ids_nhan_vien = commonRequest('ids_nhan_vien') ? commonRequest('ids_nhan_vien') : null;

        if (!$ids_nhan_vien) {
            $validator->addError('', 'id_nhan_vien', 'ID nhân viên bắt buộc nhập');
        }
        if (!$id_dao_tao) {
            $validator->addError('', 'id_dao_tao', 'ID đào tạo bắt buộc nhập');
        }
        if (!$ket_qua) {
            $validator->addError('', 'ket_qua', 'Kết quả bắt buộc nhập');
        }

        $data = [
            'id_dao_tao' => $id_dao_tao,
            'ket_qua' => $ket_qua,
        ];

        $rules = [
            'id_dao_tao' => 'required|integer',
            'ket_qua' => 'required',
        ];

        $customMessages = [
            'id_dao_tao.required' => 'Vui lòng nhập ID đào tạo',
            'id_dao_tao.integer' => 'ID đào tạo phải là số nguyên',
            'ket_qua.required' => 'Vui lòng nhập kết quả',
        ];

        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors(), 'Vui lòng nhập các trường bắt buộc!');
        }

        $this->db->trans_start();
        $ds_nhan_vien_dao_tao_id = [];
        $ids_nhan_vien = explode(",", $ids_nhan_vien);
        foreach ($ids_nhan_vien as $id) {
            $nvdt_id = $this->Hrm_nhan_vien_dao_tao_model->insert([
                'id_nhan_vien' => $id,
                'id_dao_tao' => $id_dao_tao,
                'ket_qua' => $ket_qua,
            ]);

            if ($nvdt_id) {
                $ds_nhan_vien_dao_tao_id[] = $nvdt_id;
            }
        }

        $nhanvien_daotao = $this->Hrm_nhan_vien_dao_tao_model->whereIn('id_nhan_vien_dao_tao', $ds_nhan_vien_dao_tao_id)->get();

        //create log
        $this->createLog('create', 'Tạo mới nhân viên đào tạo', null, $nhanvien_daotao, 'hrm_nhanvien_daotao');
        $this->db->trans_commit();
        resSuccess($nhanvien_daotao, 'Thêm thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }

    public function show_get($id)
    {
        $data = $this->Hrm_nhan_vien_dao_tao_model
            ->select('hrm_nhan_vien_dao_tao.*, hrm_dao_tao.ten_khoa_hoc, hrm_dao_tao.noi_dung, hrm_dao_tao.ngay_bat_dau, hrm_dao_tao.ngay_ket_thuc, hrm_dao_tao.trang_thai')
            ->leftJoin('hrm_dao_tao', 'hrm_nhan_vien_dao_tao.id_dao_tao = hrm_dao_tao.id_dao_tao')
            ->where('id_nhan_vien_dao_tao', $id)
            ->first();

        if (!$data) {
            resError('Không tìm thấy nhân viên đào tạo');
        }

        resSuccess($data, 'Lấy thông tin nhân viên đào tạo thành công');
    }

    public function edit_nhanvien_daotao_post($id)
    {
        $oldData = $this->Hrm_nhan_vien_dao_tao_model->find($id);
        if (!$oldData) {
            resError('Không tìm thấy quá trình đào tạo nhân viên');
        }

        $validator = new Validator();

        $id_dao_tao = commonRequest('id_dao_tao') ? commonRequest('id_dao_tao') : null;
        $ket_qua = commonRequest('ket_qua') ? commonRequest('ket_qua') : null;

        if (!$id_dao_tao) {
            $validator->addError('', 'id_dao_tao', 'ID đào tạo bắt buộc nhập');
        }
        if (!$ket_qua) {
            $validator->addError('', 'ket_qua', 'Kết quả bắt buộc nhập');
        }

        $data = [
            'id_dao_tao' => $id_dao_tao,
            'ket_qua' => $ket_qua,
        ];

        $rules = [
            'id_dao_tao' => 'required|integer',
            'ket_qua' => 'required',
        ];

        $customMessages = [
            'id_dao_tao.required' => 'Vui lòng nhập ID đào tạo',
            'id_dao_tao.integer' => 'ID đào tạo phải là số nguyên',
            'ket_qua.required' => 'Vui lòng nhập kết quả',
        ];

        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors(), 'Vui lòng nhập các trường bắt buộc!');
        }

        $this->db->trans_start();

        $nhanvien_daotao['data'] = $this->Hrm_nhan_vien_dao_tao_model
            ->where('id_nhan_vien_dao_tao', $id)
            ->update($data);

        $nhanvien_daotao['dsNVDT'] = $this->Hrm_nhan_vien_dao_tao_model
            ->select('hrm_nhan_vien_dao_tao.*, hrm_dao_tao.ten_khoa_hoc, hrm_dao_tao.noi_dung, hrm_dao_tao.ngay_bat_dau, hrm_dao_tao.ngay_ket_thuc, hrm_dao_tao.trang_thai')
            ->leftJoin('hrm_dao_tao', 'hrm_nhan_vien_dao_tao.id_dao_tao = hrm_dao_tao.id_dao_tao')
            ->where('hrm_nhan_vien_dao_tao.id_nhan_vien', $oldData['id_nhan_vien'])
            ->get();

        //create log
        $this->createLog('create', 'Chỉnh sửa nhân viên đào tạo', null, $nhanvien_daotao, 'hrm_nhanvien_daotao');
        $this->db->trans_commit();
        resSuccess($nhanvien_daotao, 'Sửa thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }

    public function delete_nhanvien_daotao_post($id)
    {
        $oldData = $this->Hrm_nhan_vien_dao_tao_model->find($id);
        if (!$oldData) {
            resError('Không tìm thấy quá trình đào tạo!');
        }

        $this->db->trans_start();
        $qtdt['data'] = $this->Hrm_nhan_vien_dao_tao_model->where('id_nhan_vien_dao_tao', $id)->delete();
        $qtdt['dsQTDT'] = $this->Hrm_nhan_vien_dao_tao_model
            ->select('hrm_nhan_vien_dao_tao.*, hrm_dao_tao.ten_khoa_hoc, hrm_dao_tao.noi_dung, hrm_dao_tao.ngay_bat_dau, hrm_dao_tao.ngay_ket_thuc, hrm_dao_tao.trang_thai')
            ->leftJoin('hrm_dao_tao', 'hrm_nhan_vien_dao_tao.id_dao_tao = hrm_dao_tao.id_dao_tao')
            ->where('hrm_nhan_vien_dao_tao.id_nhan_vien', $oldData['id_nhan_vien'])
            ->get();

        $this->createLog('delete', 'Xóa quá trình đào tạo', $qtdt,  null, 'hrm_nhan_vien_dao_tao');
        $this->db->trans_commit();
        resSuccess($qtdt, 'Xóa thành công');
    }
}
