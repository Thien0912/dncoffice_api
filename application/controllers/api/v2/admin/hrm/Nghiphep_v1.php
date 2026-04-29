<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_nghi_phep_model $Hrm_nghi_phep_model
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Fileupload $fileupload
 * @property Common $common
 */



class Nghiphep_v1 extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_nghi_phep_model', 'Hrm_nhan_vien_model']);
        $this->load->library(['Validator', 'Fileupload']);
    }

    public function index_get()
    {
        $idNhanVien = null;
        $auth = $this->getUserLogin();
        $getDSNhanVien = commonRequest('getDSNhanVien') ? commonRequest('getDSNhanVien') : null;
        if ($getDSNhanVien) {
            $dsNhanvien = $this->Hrm_nhan_vien_model
                ->select("*")
                ->get();

            resSuccess($dsNhanvien, 'Lấy danh sách nhân viên thành công!');
        }

        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;

        $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
            'order' => commonRequest('order'),
            'columns' => commonRequest('columns')
        ] : [];

        if ($auth['ql_nguoi_dung_is_admin'] == 1) {
            $idNhanVien = null;
        } else {
            $tempNhanVien = $this->Hrm_nhan_vien_model->where('ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])->first();
            if ($tempNhanVien) {
                $idNhanVien = $tempNhanVien['id_nhan_vien'];
            } else {
                $permisstions = $this->getPermissionKeys();
                foreach ($permisstions as $per) {
                    if ($per['ql_quyen_khoa'] == 'nghiphep.duyet_cap_mot') {
                        $idNhanVien = null;
                        break;
                    }
                }
            }
        }

        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];

        $permisstions = $this->getPermissionKeys();
        $duyetCapMot = array_filter($permisstions, function ($per) {
            return $per['ql_quyen_khoa'] === 'nghiphep.duyet_cap_mot';
        });
        $duyetCapHai = array_filter($permisstions, function ($per) {
            return $per['ql_quyen_khoa'] === 'nghiphep.duyet_cap_hai';
        });

        if ($duyetCapMot) {
            if (empty($searchKey)) {
                $searchKey['trang_thai_cap_mot'] = Common::STATUS_NGHI_PHEP['Cho_duyet']['value'];
            }
        } else if ($duyetCapHai) {
            if (empty($searchKey)) {
                $searchKey['trang_thai_cap_hai'] = Common::STATUS_NGHI_PHEP['Cho_duyet']['value'];
            }
            $searchKey['trang_thai_cap_mot'] = Common::STATUS_NGHI_PHEP['Da_duyet']['value'];
        }

        $data = $this->Hrm_nghi_phep_model->getAll($start, $length, $searchValue, $orderBy, $searchKey, $idNhanVien);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
            'sql' => $data['sql']
        ]);
    }

    public function create_post()
    {
        $idNhanVien = commonRequest('id_nhan_vien') ? commonRequest('id_nhan_vien') : null;
        if (!$idNhanVien) {
            resError('Không tìm thấy nhân viên!');
        }

        $dsNghiPhep = $this->Hrm_nghi_phep_model
            ->select('*')
            ->where('id_nhan_vien', $idNhanVien)
            ->get();

        foreach ($dsNghiPhep as $np) {
            if (($np['trang_thai_cap_mot'] == Common::STATUS_NGHI_PHEP['Cho_duyet']['value']) && ($np['deleted_at'] == null)) {
                resError('Đã có 1 đăng ký nghĩ phép chờ duyệt!');
                break;
            }
        }

        $validator = new Validator();
        $loai_phep = commonRequest('loai_phep') ? commonRequest('loai_phep') : null;
        if (!in_array($loai_phep, array_keys(Common::LOAI_PHEP))) {
            $validator->addError('', 'loai_phep', 'Loại phép không hợp lệ');
            resBadrequest(resBadrequest($validator->errors()));
        }

        $trang_thai = commonRequest('trang_thai_cap_mot') ? commonRequest('trang_thai_cap_mot') : null;
        if (!in_array($trang_thai, array_keys(Common::STATUS_NGHI_PHEP))) {
            $validator->addError('', 'trang_thai_cap_mot', 'Trạng thái không hợp lệ');
            resBadrequest(resBadrequest($validator->errors()));
        }

        $auth = $this->getUserLogin();
        $data = [
            'id_nhan_vien' => commonRequest('id_nhan_vien') ? commonRequest('id_nhan_vien') : null,
            'ngay_bat_dau' => commonRequest('ngay_bat_dau') ? commonRequest('ngay_bat_dau') : null,
            'ngay_ket_thuc' => commonRequest('ngay_ket_thuc') ? commonRequest('ngay_ket_thuc') : 0,
            'loai_phep' => $loai_phep,
            'trang_thai_cap_mot' => $trang_thai,
            'trang_thai_cap_hai' => Common::STATUS_NGHI_PHEP['Cho_duyet']['value'],
            // 'nguoi_tao' => $auth['ql_nguoi_dung_id'],
            // 'nguoi_sua' => $auth['ql_nguoi_dung_id'],
            // 'trang_thai_tchc' => Common::STATUS_DANG_KY_LAM_THEM['Cho_duyet']['value'],
            // 'lddv_duyet_id' => null,
            // 'tchc_duyet_id' => null
        ];
        $rules = [
            'id_nhan_vien' => 'required|integer',
            'ngay_bat_dau' => 'date',
            'ngay_ket_thuc' => 'date',
            'loai_phep' => 'required',
            'trang_thai_cap_mot' => 'required',

        ];
        $customMessages = [
            'id_nhan_vien.required' => 'Vui lòng chọn nhân viên',
            'ngay_bat_dau.date' => 'Ngày không đúng định dạng',
            'ngay_ket_thuc.date' => 'Ngày không đúng định dạng',
            'loai_phep.required' => 'Loại phép bắt buộc nhập',
            'trang_thai_cap_mot.required' => 'Trạng thái bắt buộc nhập',
        ];

        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();

        $nghi_phep = $this->Hrm_nghi_phep_model->create($data);

        //create log
        $this->createLog('create', 'Tạo mới nghĩ phép', null, $nghi_phep, 'hrm_nghi_phep');
        $this->db->trans_commit();
        resSuccess($nghi_phep, 'Thêm thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }

    function duyetnghiphep_post()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Không tìm thấy người dùng!');
        }

        $ids = commonRequest('ids');
        $status = commonRequest('trang_thai') ? commonRequest('trang_thai') : null;
        if (!array_key_exists($status, Common::STATUS_NGHI_PHEP)) {
            resBadrequest(['trang_thai' => 'Trạng thái không hợp lệ'], 'Trạng thái không hợp lệ');
        }

        $this->db->trans_start();
        $dklt = $this->Hrm_nghi_phep_model->whereIn('id_nghi_phep', $ids)->get();
        if (count($ids) != count($dklt)) {
            resError('Dữ liệu không hợp lệ');
        }

        $data = [];
        $permisstions = $this->getPermissionKeys();
        $duyetCapMot = array_filter($permisstions, function ($per) {
            return $per['ql_quyen_khoa'] === 'nghiphep.duyet_cap_mot';
        });
        $duyetCapHai = array_filter($permisstions, function ($per) {
            return $per['ql_quyen_khoa'] === 'nghiphep.duyet_cap_hai';
        });

        if ($duyetCapMot) {
            // Lãnh đạo đơn vị duyệt
            $data = [
                'trang_thai_cap_mot' => $status,
                'nguoi_duyet_cap_mot_id' => $auth['ql_nguoi_dung_id'],
                'updated_at' => date('Y-m-d H:i:s')
            ];
        } else if ($duyetCapHai) {
            // TC-HC duyệt
            $data = [
                'trang_thai_cap_hai' => $status,
                'nguoi_duyet_cap_hai_id' => $auth['ql_nguoi_dung_id'],
                'updated_at' => date('Y-m-d H:i:s')
            ];
        } else {
            resError('Không có quyền thao tác!');
        }

        foreach ($ids as $id) {
            $tempData = $this->Hrm_nghi_phep_model->find($id);
            if ($duyetCapMot && (($tempData['trang_thai_cap_mot'] == Common::STATUS_NGHI_PHEP['Da_duyet']['value']) || ($tempData['trang_thai_cap_mot'] == Common::STATUS_DANG_KY_LAM_THEM['Tu_choi']['value']))) {
                continue;
            } else if ($duyetCapHai && (($tempData['trang_thai_cap_hai'] == Common::STATUS_NGHI_PHEP['Da_duyet']['value']) || ($tempData['trang_thai_cap_hai'] == Common::STATUS_DANG_KY_LAM_THEM['Tu_choi']['value']))) {
                continue;
            }
            $this->Hrm_nghi_phep_model->where('id_nghi_phep', $id)->update($data);
        }

        $this->createLog('delete', 'Duyệt đăng ký làm thêm', $dklt,  null, 'hrm_nghi_phep');
        $this->db->trans_commit();
        resSuccess(null, 'Thao tác thành công');
    }

    public function delete_post()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Không tìm thấy người dùng!');
        }

        $ids = commonRequest('ids');
        $this->db->trans_start();
        $nghiPhep = $this->Hrm_nghi_phep_model->whereIn('id_nghi_phep', $ids)->get();
        if (count($ids) != count($nghiPhep)) {
            resError('Dữ liệu không hợp lệ');
        }

        foreach ($nghiPhep as $np) {
            if ($np['trang_thai_cap_mot'] == Common::STATUS_NGHI_PHEP['Da_duyet']['value']) {
                resError('Không thể xóa nghỉ phép đã duyệt', REST_Controller::HTTP_BAD_REQUEST);
            }
        }

        $this->Hrm_nghi_phep_model->whereIn('id_nghi_phep', $ids)->update([
            'deleted_at' => date('Y-m-d'),
            'deleted_user_id' => $auth['ql_nguoi_dung_id']
        ]);

        $this->createLog('delete', 'Xóa nghỉ phép', $nghiPhep,  null, 'hrm_nghi_phep');
        $this->db->trans_commit();
        resSuccess(null, 'Xóa thành công');
    }
}
