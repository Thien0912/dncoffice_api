<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Hrm_quy_dinh_nghi_phep $Hrm_quy_dinh_nghi_phep
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 * @property Hrm_hop_dong_model $Hrm_hop_dong_model
 * @property Hrm_vi_tri_cong_viec_model $Hrm_vi_tri_cong_viec_model
 * @property Hrm_phu_cap_model $Hrm_phu_cap_model
 * @property E_don_vi_model $E_don_vi_model
 * @property Hrm_luong_can_ban_model $Hrm_luong_can_ban_model
 * @property Ql_nhat_ky_model $Ql_nhat_ky_model
 * @property Hrm_ty_le_bao_hiem_model $Hrm_ty_le_bao_hiem_model
 * @property Hrm_phu_luc_phu_cap_model $Hrm_phu_luc_phu_cap_model
 * @property Hrm_hop_dong_phu_luc_model $Hrm_hop_dong_phu_luc_model
 * @property Hrm_nhan_vien_cong_viec_model $Hrm_nhan_vien_cong_viec_model
 * @property Fileupload $fileupload
 * @property CI_Upload $upload
 * @property Pxl $pxl
 * @property Common $common
 */



class Hopdong extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model([
            'Hrm_nhan_vien_model',
            'Ql_nguoi_dung_model',
            'Hrm_hop_dong_model',
            'Hrm_vi_tri_cong_viec_model',
            'Hrm_phu_cap_model',
            'E_don_vi_model',
            'Hrm_luong_can_ban_model',
            'Ql_nhat_ky_model',
            'Hrm_ty_le_bao_hiem_model',
            'Hrm_phu_luc_phu_cap_model',
            'Hrm_hop_dong_phu_luc_model',
            'Hrm_nhan_vien_cong_viec_model',
        ]);
        $this->load->library(['Validator', 'Fileupload', 'Common', 'Pxl', 'upload']);
    }
    public function index_get()
    {
        $auth  = $this->getUserLogin();
        $data = [];

        $getDSNhanVien = commonRequest('getDSNhanVien') ? commonRequest('getDSNhanVien') : null;
        if ($getDSNhanVien) {
            $data['nhan_vien'] = $this->Hrm_nhan_vien_model
                ->select("*")
                ->get();

            $data['cong_viec'] = $this->Hrm_vi_tri_cong_viec_model
                ->select("*")
                ->get();

            $data['phu_cap'] = $this->Hrm_phu_cap_model
                ->select('*')
                ->where('deleted_at IS NULL')
                ->get();
            $data['don_vi'] = $this->E_don_vi_model->all();

            resSuccess($data, 'Lấy danh dữ liệu select2 thành công!');
        }

        $ma_nhan_vien_search = commonRequest('ma_nhan_vien_search') ? commonRequest('ma_nhan_vien_search') : null;
        if ($ma_nhan_vien_search) {
            $nhanvien = $this->Hrm_nhan_vien_model
                ->select("*")
                ->like('ma_nhan_vien', $ma_nhan_vien_search)
                ->orLike('ho_ten', $ma_nhan_vien_search)
                ->get();

            resSuccess($nhanvien, 'Lấy thông tin nhân viên thành công!');
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

        $data = $this->Hrm_hop_dong_model->getAll($data['start'], $data['length'], $data['searchValue'], $data['order'], $data['columns'], $data['searchKey'],  $data['fromDate'], $data['toDate'], $auth);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
            'sql' => $data['sql'],
        ]);
    }

    public function create_post()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Không tìm thấy người dùng!');
        }

        $them_thanh_phan = commonRequest('them_thanh_phan') ? commonRequest('them_thanh_phan') : null;
        if ($them_thanh_phan && ($them_thanh_phan == 'don_vi_cong_tac')) {
            $ten_don_vi = commonRequest('ten_don_vi_add') ? commonRequest('ten_don_vi_add') : null;
            $ma_don_vi = commonRequest('ma_don_vi_add') ? commonRequest('ma_don_vi_add') : null;
            $loai = commonRequest('loai_add') ? commonRequest('loai_add') : null;
            $email = commonRequest('email_add') ? commonRequest('email_add') : null;

            $result = $this->E_don_vi_model->create([
                'ten_don_vi' => $ten_don_vi,
                'ma_don_vi' => $ma_don_vi,
                'loai' => $loai,
                'email' => $email,
            ]);

            if ($result) {
                resSuccess($result, 'Thêm đơn vị công tác thành công!');
            } else {
                resError('Thêm lỗi đơn vị công tác!');
            }
        }

        $ten_cong_viec_add = commonRequest('ten_cong_viec_add') ? commonRequest('ten_cong_viec_add') : null;
        if ($ten_cong_viec_add) {
            $result = $this->Hrm_vi_tri_cong_viec_model->create([
                'ten_cong_viec' => $ten_cong_viec_add
            ]);

            if ($result) {
                resSuccess($result, 'Thêm vị trí công việc thành công!');
            } else {
                resError('Thêm lỗi vị trí công việc!');
            }
        }

        $validator = new Validator();

        $so_hop_dong = commonRequest('so_hop_dong') ? commonRequest('so_hop_dong') : null;
        if (!$so_hop_dong) {
            $validator->addError('', 'so_hop_dong', 'Số hợp đồng bắt buộc nhập');
            resBadrequest(resBadrequest($validator->errors()));
        }
        if ($this->Hrm_hop_dong_model->checkValueExists('so_hop_dong', $so_hop_dong)) {
            $validator->addError('', 'so_hop_dong', 'Số hợp đồng đã tồn tại');
            resBadrequest(resBadrequest($validator->errors()));
        }

        $loai_hop_dong = commonRequest('loai_hop_dong') ? commonRequest('loai_hop_dong') : null;
        if (!in_array($loai_hop_dong, array_keys(Common::LOAI_HOP_DONG))) {
            $validator->addError('', 'loai_hop_dong', 'Loại hợp đồng không hợp lệ');
            resBadrequest(resBadrequest($validator->errors()));
        }

        $auth = $this->getUserLogin();
        $is_public = 1;
        $luongFinal = 0;
        $data = [
            'id_nhan_vien' => commonRequest('id_nhan_vien') ? commonRequest('id_nhan_vien') : null,
            'ten_hop_dong' => commonRequest('ten_hop_dong') ? commonRequest('ten_hop_dong') : null,
            'so_hop_dong' => $so_hop_dong,
            'ngay_bat_dau' => commonRequest('ngay_bat_dau') ? commonRequest('ngay_bat_dau') : null,
            'ngay_ket_thuc' => commonRequest('ngay_ket_thuc') ? commonRequest('ngay_ket_thuc') : null,
            'muc_luong_bao_hiem' => commonRequest('muc_luong_bao_hiem') ? commonRequest('muc_luong_bao_hiem') : 0,
            'loai_hop_dong' => $loai_hop_dong,
            'ngay_tao' => date('Y-m-d H:i:s'),
            'ngay_sua' => null,
            'nguoi_tao' => $auth['ql_nguoi_dung_id'],
            'nguoi_sua' => $auth['ql_nguoi_dung_id'],
            'deleted_at' => null,
            'id_vi_tri_cong_viec' => commonRequest('id_vi_tri_cong_viec') ? commonRequest('id_vi_tri_cong_viec') : null,
            'thoi_han_hop_dong' => commonRequest('thoi_han_hop_dong') ? commonRequest('thoi_han_hop_dong') : null,
            'vi_tri_cong_viec' => '',
            'dang_hieu_luc' => 1,
            'muc_luong' => commonRequest('muc_luong') ? commonRequest('muc_luong') : null,
            'luong_co_ban' => commonRequest('luong_co_ban') ? commonRequest('luong_co_ban') : null,
            'ti_le_huong_luong' => commonRequest('ti_le_huong_luong') ? commonRequest('ti_le_huong_luong') : null,
            'id_don_vi_cong_tac' => commonRequest('id_don_vi_cong_tac') ? commonRequest('id_don_vi_cong_tac') : null,

            // Trường thêm mới
            'ngay_ky' => commonRequest('ngay_ky') ? commonRequest('ngay_ky') : null,
            'nguoi_dai_dien_ky' => commonRequest('nguoi_dai_dien_ky') ? commonRequest('nguoi_dai_dien_ky') : null,
            'trich_yeu' => commonRequest('trich_yeu') ? commonRequest('trich_yeu') : null,
            'ghi_chu' => commonRequest('ghi_chu') ? commonRequest('ghi_chu') : null,
            'chuc_danh' => commonRequest('chuc_danh') ? commonRequest('chuc_danh') : null,
            'id_ty_le_bao_hiem' => commonRequest('id_ty_le_bao_hiem') ? commonRequest('id_ty_le_bao_hiem') : null,
            'hinh_thuc_lam_viec' => commonRequest('hinh_thuc_lam_viec') ? commonRequest('hinh_thuc_lam_viec') : null,
        ];
        $rules = [
            'id_nhan_vien' => 'required|integer',
            'ngay_bat_dau' => 'date',
            'ngay_ket_thuc' => 'date',
        ];
        $customMessages = [
            'id_nhan_vien.required' => 'Vui lòng chọn nhân viên',
            'ngay_bat_dau.date' => 'Ngày bắt đầu hợp đồng không đúng định dạng',
            'ngay_ket_thuc.date' => 'Ngày kết thúc hợp đồng không đúng định dạng',
        ];

        if (($loai_hop_dong == Common::LOAI_HOP_DONG['Hoc_viec']['value']) || ($loai_hop_dong == Common::LOAI_HOP_DONG['Thu_viec']['value'])) {
            $rules['muc_luong'] = 'required';
            $customMessages['muc_luong.required'] = 'Mức lương là bắt buộc';

            $luongFinal = commonRequest('muc_luong');
        } else {
            $rules['luong_co_ban'] = 'required';
            $rules['muc_luong_bao_hiem'] = 'required';
            $customMessages['luong_co_ban.required'] = 'Vui lòng nhập lương cơ bản';
            $customMessages['muc_luong_bao_hiem.required'] = 'Vui lòng nhập lương bảo hiểm';

            $luongFinal = commonRequest('luong_co_ban');
        }

        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        // Upload nhiều files_hop_dong
        $folderName = 'hop-dong/' . date('Y') . '/' . date('m');
        $uploadedFiles = [];
        $files = commonRequest('files_hop_dong') ? commonRequest('files_hop_dong') : null;
        if (isset($files) && !empty($files)) {
            foreach ($files['name'] as $key => $fileName) {
                // Chuẩn bị dữ liệu cho từng file
                $file = [
                    'name'     => $files['name'][$key],
                    'type'     => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error'    => $files['error'][$key],
                    'size'     => $files['size'][$key],
                ];
                $result = $this->fileupload->upload($file, $folderName);
                $uploadedFiles[] = $result;
            }
        }

        foreach ($uploadedFiles as $key => $file) {
            unset($uploadedFiles[$key]['success']);
            $uploadedFiles[$key]['file_size'] = exchangeFromKbToLargerCapacity($uploadedFiles[$key]['file_size']);
            $uploadedFiles[$key]['is_public'] = $is_public;
        }
        $data['files_hop_dong'] = json_encode($uploadedFiles);

        $this->db->trans_start();
        $this->Hrm_hop_dong_model
            ->where('id_nhan_vien', commonRequest('id_nhan_vien'))
            ->update([
                'dang_hieu_luc' => 0
            ]);

        $hopdong = $this->Hrm_hop_dong_model->create($data);
        $this->Hrm_luong_can_ban_model
            ->where('nhan_vien_id', commonRequest('id_nhan_vien'))
            ->update([
                'dang_hieu_luc' => 0
            ]);

        $luongCanBan = $this->Hrm_luong_can_ban_model->create(
            [
                'nhan_vien_id' =>  commonRequest('id_nhan_vien'),
                'so_tien' => $luongFinal,
                'ngay_hieu_luc' => commonRequest('ngay_bat_dau'),
                'dang_hieu_luc' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'created_user_id' => $auth['ql_nguoi_dung_id']
            ]
        );
        $hopdong['luong_can_ban'] = $luongCanBan;

        $nhanvien = $this->Hrm_nhan_vien_model->find($data['id_nhan_vien']);
        $hopdong['nhan_vien'] = $nhanvien;
        $loaiHopDong = $this->common::LOAI_HOP_DONG;

        $hopdong['ten_loai_hop_dong'] = isset($loaiHopDong[$data['loai_hop_dong']]) ? $loaiHopDong[$data['loai_hop_dong']]['label'] : null;
        $viTriCongViec = $this->Hrm_vi_tri_cong_viec_model->find($hopdong['id_vi_tri_cong_viec']);
        $hopdong['ten_vi_tri_cong_viec'] = $viTriCongViec ? $viTriCongViec['ten_cong_viec'] : null;

        $hopdong['tong_phu_luc'] = $this->db
            ->from('hrm_hop_dong_phu_luc')
            ->where('id_hop_dong', $hopdong['id_hop_dong'])
            ->where('ngay_hieu_luc IS NOT NULL')
            ->count_all_results();

        $giaTriTuongDuong = [
            'Hoc_viec' => 'DANG_HOC_VIEC',
            'Thu_viec' => 'DANG_THU_VIEC',
            'Co_thoi_han' => 'DANG_LAM_VIEC',
            'Khong_thoi_han' => 'DANG_LAM_VIEC',
        ];

        $nhanVienCongViec = $this->Hrm_nhan_vien_cong_viec_model
            ->where('id_nhan_vien', $data['id_nhan_vien'])
            ->orderBy('id_nhan_vien_cong_viec', 'DESC')
            ->first();

        if ($nhanVienCongViec) {
            $this->Hrm_nhan_vien_cong_viec_model
                ->where('id_nhan_vien', $data['id_nhan_vien'])
                ->update([
                    'trang_thai' => Common::TRANG_THAI_CONG_VIEC[$giaTriTuongDuong[$loai_hop_dong]]['value']
                ]);
        }

        // $phuLucHopDong = $this->db
        //     ->select('*')
        //     ->from('hrm_hop_dong_phu_luc')
        //     ->where('id_hop_dong', $hopdong['id_hop_dong'])
        //     ->order_by('id_hop_dong_phu_luc', 'DESC')
        //     ->get()
        //     ->row_array();

        // if ($phuLucHopDong) {
        //     $hopdong['ngay_hieu_luc_phu_luc'] = $phuLucHopDong['ngay_hieu_luc'];
        // } else {
        //     $hopdong['ngay_hieu_luc_phu_luc'] = '';
        // }

        $hopdong['trang_thai_cong_viec_moi'] = Common::TRANG_THAI_CONG_VIEC[$giaTriTuongDuong[$loai_hop_dong]]['value'];

        $hopdong['trang_thai_cong_viec_moi'] = Common::TRANG_THAI_CONG_VIEC[$giaTriTuongDuong[$loai_hop_dong]]['value'];

        $files = $hopdong['files_hop_dong'] ? json_decode($hopdong['files_hop_dong'], true) : [];
        if (!empty($files)) {
            foreach ($files as &$file) {
                if (!empty($file)) {
                    $file['file_path'] = encryptString($file['file_path']);
                }
            }
        }
        unset($file);
        $hopdong['files_hop_dong'] = $files;

        $changes = [
            [
                'field' => 'hop_dong',
                'field_name' => 'Hợp đồng lao động',
                'old_value' => null,
                'new_value' => [
                    'value' => 'Thêm mới',
                    'label' => 'Hợp đồng: ' . $hopdong['ten_hop_dong'] . ' - Số: ' . $hopdong['so_hop_dong']
                ]
            ]
        ];
        $this->logEmployeeHistory($data['id_nhan_vien'], 'Thêm hợp đồng lao động', $changes);

        //create log
        $this->createLog('create', 'Tạo mới hợp đồng số: ' . $hopdong['so_hop_dong'], null, $hopdong, 'hrm_hop_dong');
        $this->db->trans_commit();
        resSuccess($hopdong, 'Thêm thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }

    public function update_post($id)
    {
        $hopdong = $this->Hrm_hop_dong_model->find($id);
        if (!$hopdong) {
            resError('Hợp đồng không tồn tại', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }

        $validator = new Validator();

        // Update by key-value
        $key = commonRequest('key') ? commonRequest('key') : null;
        $value = commonRequest('value') ? commonRequest('value') : null;
        if ($key) {
            $this->Hrm_hop_dong_model->where('id_hop_dong', $id)->update([
                $key => $value
            ]);

            resSuccess($this->Hrm_hop_dong_model->find($id), 'Cập nhật thành công', REST_INSTANCE_Controller::HTTP_OK);
        }



        $so_hop_dong = commonRequest('so_hop_dong') ? commonRequest('so_hop_dong') : null;
        if (!$so_hop_dong) {
            $validator->addError('', 'so_hop_dong', 'Số hợp đồng bắt buộc nhập');
            resBadrequest(resBadrequest($validator->errors()));
        }
        if ($this->Hrm_hop_dong_model->where('so_hop_dong', $so_hop_dong)->where('id_hop_dong !=', $id)->first()) {
            $validator->addError('', 'so_hop_dong', 'Số hợp đồng đã tồn tại');
            resBadrequest(resBadrequest($validator->errors()));
        }

        $loai_hop_dong = commonRequest('loai_hop_dong') ? commonRequest('loai_hop_dong') : null;
        if (!in_array($loai_hop_dong, array_keys(Common::LOAI_HOP_DONG))) {
            $validator->addError('', 'loai_hop_dong', 'Loại hợp đồng không hợp lệ');
            resBadrequest(resBadrequest($validator->errors()));
        }

        $is_public = 1;
        $auth = $this->getUserLogin();
        $data = [
            // 'id_nhan_vien' => commonRequest('id_nhan_vien') ? commonRequest('id_nhan_vien') : null,
            'ten_hop_dong' => commonRequest('ten_hop_dong') ? commonRequest('ten_hop_dong') : null,
            'so_hop_dong' => $so_hop_dong,
            'ngay_bat_dau' => commonRequest('ngay_bat_dau') ? commonRequest('ngay_bat_dau') : null,
            'ngay_ket_thuc' => commonRequest('ngay_ket_thuc') ? commonRequest('ngay_ket_thuc') : null,
            'muc_luong_bao_hiem' => commonRequest('muc_luong_bao_hiem') ? commonRequest('muc_luong_bao_hiem') : 0,
            'loai_hop_dong' => $loai_hop_dong,
            // 'ngay_tao' => date('Y-m-d H:i:s'),
            'ngay_sua' =>  date('Y-m-d H:i:s'),
            // 'nguoi_tao' => $auth['ql_nguoi_dung_id'],
            'nguoi_sua' => $auth['ql_nguoi_dung_id'],
            'deleted_at' => null,
            'id_vi_tri_cong_viec' => commonRequest('id_vi_tri_cong_viec') ? commonRequest('id_vi_tri_cong_viec') : null,
            'thoi_han_hop_dong' => commonRequest('thoi_han_hop_dong') ? commonRequest('thoi_han_hop_dong') : null,
            'vi_tri_cong_viec' => '',
            // 'dang_hieu_luc' => 1,
            'muc_luong' => commonRequest('muc_luong') ? commonRequest('muc_luong') : null,
            'luong_co_ban' => commonRequest('luong_co_ban') ? commonRequest('luong_co_ban') : null,
            'ti_le_huong_luong' => commonRequest('ti_le_huong_luong') ? commonRequest('ti_le_huong_luong') : null,
            'id_don_vi_cong_tac' => commonRequest('id_don_vi_cong_tac') ? commonRequest('id_don_vi_cong_tac') : null,

            // Trường thêm mới
            'ngay_ky' => commonRequest('ngay_ky') ? commonRequest('ngay_ky') : null,
            'nguoi_dai_dien_ky' => commonRequest('nguoi_dai_dien_ky') ? commonRequest('nguoi_dai_dien_ky') : null,
            'trich_yeu' => commonRequest('trich_yeu') ? commonRequest('trich_yeu') : null,
            'ghi_chu' => commonRequest('ghi_chu') ? commonRequest('ghi_chu') : null,
            'chuc_danh' => commonRequest('chuc_danh') ? commonRequest('chuc_danh') : null,
            'id_ty_le_bao_hiem' => commonRequest('id_ty_le_bao_hiem') ? commonRequest('id_ty_le_bao_hiem') : null,
            'hinh_thuc_lam_viec' => commonRequest('hinh_thuc_lam_viec') ? commonRequest('hinh_thuc_lam_viec') : null,

        ];

        $rules = [
            'ngay_bat_dau' => 'date',
            'ngay_ket_thuc' => 'date',
        ];
        $customMessages = [
            'ngay_bat_dau.date' => 'Ngày bắt đầu hợp đồng không đúng định dạng',
            'ngay_ket_thuc.date' => 'Ngày kết thúc hợp đồng không đúng định dạng',
        ];

        if (($loai_hop_dong == Common::LOAI_HOP_DONG['Hoc_viec']['value']) || ($loai_hop_dong == Common::LOAI_HOP_DONG['Thu_viec']['value'])) {
            $rules['muc_luong'] = 'required';
            $customMessages['muc_luong.required'] = 'Mức lương là bắt buộc';
        } else {
            $rules['luong_co_ban'] = 'required';
            $rules['muc_luong_bao_hiem'] = 'required';
            $customMessages['luong_co_ban.required'] = 'Vui lòng nhập lương cơ bản';
            $customMessages['muc_luong_bao_hiem.required'] = 'Vui lòng nhập lương bảo hiểm';
        }

        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();
        // Xử lý file đính kèm cũ
        $fileOld = commonRequest('files_dinh_kem_old') ? json_decode(commonRequest('files_dinh_kem_old'), true) : [];
        $fileOldPath = array_column($fileOld, 'file_path');
        // Decrypt từng phần tử
        $fileOldPath = array_map(function ($path) {
            return decryptString($path);
        }, $fileOldPath);

        $fileOldName = array_column($fileOld, 'file_name');

        $fileHD = $this->Hrm_hop_dong_model->find($id);
        $fileHD = json_decode($fileHD['files_hop_dong'], true);
        $fileHDPath = $fileHD ? array_column($fileHD, 'file_path') : [];

        $filePathDiff = array_diff($fileHDPath, $fileOldPath);
        // resError('Test lỗi', 500, [
        //     'files_dinh_kem_old' => $fileOld,
        //     'fileOldPath' => $fileOldPath,
        //     'fileHDPath' => $fileHDPath,
        //     'filePathDiff ' => $filePathDiff
        // ]);
        foreach ($filePathDiff as $fpd) {
            //Xóa file đính kèm đã bị xóa
            $delete = $this->fileupload->delete($fpd);
            @unlink(FCPATH . $fpd);
        }

        $tempFildOld = [];
        foreach ($fileOld as $key => $file) {
            if (isset($file['file_path'])) {
                $tempFildOld[$key]['file_name'] = $file['file_name'];
                $tempFildOld[$key]['file_path'] = decryptString($file['file_path']);
                $tempFildOld[$key]['file_extension'] = $file['file_extension'];
                $tempFildOld[$key]['file_size'] = $file['file_size'];
                $tempFildOld[$key]['is_public'] = $is_public;
            }
        }

        $this->Hrm_hop_dong_model->where('id_hop_dong', $id)->update([
            'files_hop_dong' => json_encode($tempFildOld)
        ]);

        // Upload nhiều files_hop_dong mới
        $folderName = 'hop-dong/' . date('Y') . '/' . date('m');
        $uploadedFiles = [];
        // $files = $_FILES['files_hop_dong'] ? $_FILES['files_hop_dong'] : null;
        $files = isset($_FILES['files_hop_dong']) ? $_FILES['files_hop_dong'] : null;
        if (isset($files) && !empty($files)) {
            // $files = $data['files_hop_dong']; // Tên input từ form  

            foreach ($files['name'] as $key => $fileName) {
                if (in_array($fileName, $fileOldName)) {
                    continue;
                }

                // Chuẩn bị dữ liệu cho từng file
                $file = [
                    'name'     => $files['name'][$key],
                    'type'     => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error'    => $files['error'][$key],
                    'size'     => $files['size'][$key],
                ];
                $result = $this->fileupload->upload($file, $folderName);
                $uploadedFiles[] = $result;
            }
        }
        // else {
        //     resError('Vui lòng chọn file hợp đồng!');
        // }

        // resError('Test lỗi', 500, $uploadedFiles);


        foreach ($uploadedFiles as $key => $file) {
            unset($uploadedFiles[$key]['success']);
            $uploadedFiles[$key]['file_size'] = exchangeFromKbToLargerCapacity($uploadedFiles[$key]['file_size']);
            $uploadedFiles[$key]['is_public'] = $is_public;
        }
        $finalFiles = array_merge($tempFildOld, $uploadedFiles);

        $data['files_hop_dong'] = json_encode($finalFiles);

        // End Upload nhiều files_hop_dong mới

        $this->Hrm_hop_dong_model->where('id_hop_dong', $id)->update($data);

        $nhanvienOld = $this->Hrm_nhan_vien_model->find($hopdong['id_nhan_vien']);
        $hopdong['nhan_vien'] = $nhanvienOld;

        $hopdongNew = $this->Hrm_hop_dong_model->find($id);
        $nhanvienNew = $this->Hrm_nhan_vien_model->find($hopdong['id_nhan_vien']);
        $hopdongNew['nhan_vien'] = $nhanvienNew;

        $files = $hopdongNew['files_hop_dong'] ? json_decode($hopdongNew['files_hop_dong'], true) : [];
        if (!empty($files)) {
            foreach ($files as &$file) {
                if (!empty($file)) {
                    $file['file_path'] = encryptString($file['file_path']);
                }
            }
        }
        unset($file);
        $hopdongNew['files_hop_dong'] = $files;

        $changes = [
            [
                'field' => 'hop_dong',
                'field_name' => 'Hợp đồng lao động',
                'old_value' => [
                    'value' => 'Cập nhật',
                    'label' => 'Hợp đồng: ' . $hopdong['ten_hop_dong'] . ' - Số: ' . $hopdong['so_hop_dong']
                ],
                'new_value' => [
                    'value' => 'Cập nhật',
                    'label' => 'Hợp đồng: ' . $hopdongNew['ten_hop_dong'] . ' - Số: ' . $hopdongNew['so_hop_dong']
                ]
            ]
        ];
        $this->logEmployeeHistory($hopdong['id_nhan_vien'], 'Cập nhật hợp đồng lao động', $changes);

        //create log
        $this->createLog('update', 'Cập nhật hợp đồng số: ' . $hopdongNew['so_hop_dong'], $hopdong, $hopdongNew, 'hrm_hop_dong');
        $this->db->trans_commit();
        resSuccess($hopdongNew, 'Cập nhật thành công', REST_INSTANCE_Controller::HTTP_OK);
    }

    public function updateHDLD_post($id)
    {
        $hopdong = $this->Hrm_hop_dong_model->find($id);
        if (!$hopdong) {
            resError('Hợp đồng không tồn tại', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }

        $validator = new Validator();

        $so_hop_dong = commonRequest('so_hop_dong') ? commonRequest('so_hop_dong') : null;
        if (!$so_hop_dong) {
            $validator->addError('', 'so_hop_dong', 'Số hợp đồng bắt buộc nhập');
            resBadrequest(resBadrequest($validator->errors()));
        }
        if ($this->Hrm_hop_dong_model->where('so_hop_dong', $so_hop_dong)->where('id_hop_dong !=', $id)->first()) {
            $validator->addError('', 'so_hop_dong', 'Số hợp đồng đã tồn tại');
            resBadrequest(resBadrequest($validator->errors()));
        }

        $loai_hop_dong = commonRequest('loai_hop_dong') ? commonRequest('loai_hop_dong') : null;
        if (!in_array($loai_hop_dong, array_keys(Common::LOAI_HOP_DONG))) {
            $validator->addError('', 'loai_hop_dong', 'Loại hợp đồng không hợp lệ');
            resBadrequest(resBadrequest($validator->errors()));
        }

        $auth = $this->getUserLogin();
        $data = [
            // 'id_nhan_vien' => commonRequest('id_nhan_vien') ? commonRequest('id_nhan_vien') : null,
            'ten_hop_dong' => commonRequest('ten_hop_dong') ? commonRequest('ten_hop_dong') : null,
            'so_hop_dong' => $so_hop_dong,
            'ngay_bat_dau' => commonRequest('ngay_bat_dau') ? commonRequest('ngay_bat_dau') : null,
            'ngay_ket_thuc' => commonRequest('ngay_ket_thuc') ? commonRequest('ngay_ket_thuc') : null,
            'muc_luong_bao_hiem' => commonRequest('muc_luong_bao_hiem') ? commonRequest('muc_luong_bao_hiem') : 0,
            'loai_hop_dong' => $loai_hop_dong,
            // 'ngay_tao' => date('Y-m-d H:i:s'),
            'ngay_sua' =>  date('Y-m-d H:i:s'),
            // 'nguoi_tao' => $auth['ql_nguoi_dung_id'],
            'nguoi_sua' => $auth['ql_nguoi_dung_id'],
            'deleted_at' => null,
            'id_vi_tri_cong_viec' => commonRequest('id_vi_tri_cong_viec') ? commonRequest('id_vi_tri_cong_viec') : null,
            'thoi_han_hop_dong' => commonRequest('thoi_han_hop_dong') ? commonRequest('thoi_han_hop_dong') : null,
            'vi_tri_cong_viec' => '',
            // 'dang_hieu_luc' => 1,
            'muc_luong' => commonRequest('muc_luong') ? commonRequest('muc_luong') : null,
            'luong_co_ban' => commonRequest('luong_co_ban') ? commonRequest('luong_co_ban') : null,
            'ti_le_huong_luong' => commonRequest('ti_le_huong_luong') ? commonRequest('ti_le_huong_luong') : null,
            'id_don_vi_cong_tac' => commonRequest('id_don_vi_cong_tac') ? commonRequest('id_don_vi_cong_tac') : null,

            // Trường thêm mới
            'ngay_ky' => commonRequest('ngay_ky') ? commonRequest('ngay_ky') : null,
            'nguoi_dai_dien_ky' => commonRequest('nguoi_dai_dien_ky') ? commonRequest('nguoi_dai_dien_ky') : null,
            'trich_yeu' => commonRequest('trich_yeu') ? commonRequest('trich_yeu') : null,
            'ghi_chu' => commonRequest('ghi_chu') ? commonRequest('ghi_chu') : null,
            'chuc_danh' => commonRequest('chuc_danh') ? commonRequest('chuc_danh') : null,
            'id_ty_le_bao_hiem' => commonRequest('id_ty_le_bao_hiem') ? commonRequest('id_ty_le_bao_hiem') : null,
            'hinh_thuc_lam_viec' => commonRequest('hinh_thuc_lam_viec') ? commonRequest('hinh_thuc_lam_viec') : null,

        ];

        $rules = [
            'ngay_bat_dau' => 'date',
            'ngay_ket_thuc' => 'date',
        ];
        $customMessages = [
            'ngay_bat_dau.date' => 'Ngày bắt đầu hợp đồng không đúng định dạng',
            'ngay_ket_thuc.date' => 'Ngày kết thúc hợp đồng không đúng định dạng',
        ];

        if (($loai_hop_dong == Common::LOAI_HOP_DONG['Hoc_viec']['value']) || ($loai_hop_dong == Common::LOAI_HOP_DONG['Thu_viec']['value'])) {
            $rules['muc_luong'] = 'required';
            $customMessages['muc_luong.required'] = 'Mức lương là bắt buộc';
        } else {
            $rules['luong_co_ban'] = 'required';
            $rules['muc_luong_bao_hiem'] = 'required';
            $customMessages['luong_co_ban.required'] = 'Vui lòng nhập lương cơ bản';
            $customMessages['muc_luong_bao_hiem.required'] = 'Vui lòng nhập lương bảo hiểm';
        }

        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        //Upload hợp đồng
        // $folderName = 'hop-dong/' . date('Y') . '/' . date('m');
        // if (isset($_FILES['file_hop_dong'])) {
        //     $uploadedFile = $this->fileupload->upload($_FILES['file_hop_dong'], $folderName);
        //     if (!$uploadedFile['success']) {
        //         resBadrequest([
        //             'file_hop_dong' => [
        //                 'Không thể tải lên file'
        //             ]
        //         ]);
        //     } else {
        //         $data['file_hop_dong_duong_dan'] = $uploadedFile['file_path'];
        //         $data['file_hop_dong_ten_file_goc'] = $uploadedFile['file_name'];
        //         $data['file_hop_dong_loai_file'] = $uploadedFile['file_extension'];
        //         //delete file
        //         $deletefile = $this->fileupload->delete($hopdong['file_hop_dong_duong_dan']);
        //     }
        // }

        // Xử lý file đính kèm cũ
        $fileOld = commonRequest('files_dinh_kem_old') ? json_decode(commonRequest('files_dinh_kem_old'), true) : [];
        $fileOldPath = array_column($fileOld, 'duong_dan');

        $fileHD = $this->Hrm_hop_dong_model->find($id);

        $fileHD = json_decode($fileHD['files_hop_dong'], true);

        $fileHDPath = array_column($fileHD, 'file_path');

        $filePathDiff = array_diff($fileHDPath, $fileOldPath);

        foreach ($filePathDiff as $fpd) {
            //Xóa file đính kèm đã bị xóa
            $delete = $this->fileupload->delete($fpd);
        }


        $tempFildOld = [];
        foreach ($fileOld as $key => $file) {
            if (isset($file['duong_dan'])) {
                $tempFildOld[$key]['file_name'] = $file['ten_file_goc'];
                $tempFildOld[$key]['file_path'] = $file['duong_dan'];
                $tempFildOld[$key]['file_extension'] = $file['loai_file'];
                $tempFildOld[$key]['file_size'] = $file['dung_luong'];
            }
        }

        $this->Hrm_hop_dong_model->where('id_hop_dong', $id)->update([
            'files_hop_dong' => json_encode($tempFildOld)
        ]);


        // Upload nhiều files_hop_dong mới
        $folderName = 'hop-dong/' . date('Y') . '/' . date('m');
        $uploadedFiles = [];
        $files = commonRequest('files_hop_dong') ? commonRequest('files_hop_dong') : null;
        if (isset($files) && !empty($files)) {
            // $files = $data['files_hop_dong']; // Tên input từ form  

            foreach ($files['name'] as $key => $fileName) {
                // Chuẩn bị dữ liệu cho từng file
                $file = [
                    'name'     => $files['name'][$key],
                    'type'     => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error'    => $files['error'][$key],
                    'size'     => $files['size'][$key],
                ];
                $result = $this->fileupload->upload($file, $folderName);
                $uploadedFiles[] = $result;
            }
        }
        // else {
        //     resError('Vui lòng chọn file hợp đồng!');
        // }

        foreach ($uploadedFiles as $key => $file) {
            unset($uploadedFiles[$key]['success']);
            $uploadedFiles[$key]['file_size'] = exchangeFromKbToLargerCapacity($uploadedFiles[$key]['file_size']);
        }
        $finalFiles = array_merge($tempFildOld, $uploadedFiles);

        $data['files_hop_dong'] = json_encode($finalFiles);

        // End Upload nhiều files_hop_dong mới


        $this->db->trans_start();

        // $ids_phu_cap = commonRequest('ids_phu_cap') ? commonRequest('ids_phu_cap') : null;
        // $ids_phu_cap_old = commonRequest('ids_phu_cap_old') ? commonRequest('ids_phu_cap_old') : null;
        // $ids_phu_cap_array = explode(',', $ids_phu_cap);
        // $ids_phu_cap_old_array = explode(',', $ids_phu_cap_old);


        // if (!empty($ids_phu_cap_array) || !empty($ids_phu_cap_old_array)) {
        //     $ids_deleted = array_diff($ids_phu_cap_old_array, $ids_phu_cap_array);
        //     foreach ($ids_deleted as $id) {
        //         $this->db->where('id_nhan_vien', $hopdong['id_nhan_vien']);
        //         $this->db->where('id_phu_cap', $id);
        //         $this->db->delete('hrm_phu_cap_nhan_vien');
        //     }

        //     $ids_added = array_diff($ids_phu_cap_array, $ids_phu_cap_old_array);
        //     foreach ($ids_added as $id_added) {
        //         $this->db->insert('hrm_phu_cap_nhan_vien', [
        //             'id_nhan_vien' => $hopdong['id_nhan_vien'],
        //             'id_phu_cap' => $id_added,
        //             'so_tien' => 100000,
        //         ]);
        //     }
        // }

        $this->Hrm_hop_dong_model->where('id_hop_dong', $id)->update($data);

        $nhanvienOld = $this->Hrm_nhan_vien_model->find($hopdong['id_nhan_vien']);
        $hopdong['nhan_vien'] = $nhanvienOld;

        $hopdongNew = $this->Hrm_hop_dong_model->find($id);
        $nhanvienNew = $this->Hrm_nhan_vien_model->find($hopdong['id_nhan_vien']);
        $hopdongNew['nhan_vien'] = $nhanvienNew;

        $dsHDLD = $this->Hrm_hop_dong_model
            ->where('id_nhan_vien', $hopdong['id_nhan_vien'])
            ->where('deleted_at IS NULL')
            ->join('hrm_vi_tri_cong_viec', 'hrm_vi_tri_cong_viec.id_vi_tri_cong_viec = hrm_hop_dong.id_vi_tri_cong_viec', 'left')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_hop_dong.id_don_vi_cong_tac', 'left')
            ->orderBy('ngay_bat_dau', 'DESC')
            ->get();

        foreach ($dsHDLD as &$hdld) {
            $hdld['tong_phu_luc'] = $this->db
                ->from('hrm_hop_dong_phu_luc')
                ->where('id_hop_dong', $hdld['id_hop_dong'])
                ->where('ngay_hieu_luc IS NOT NULL')
                ->count_all_results();

            $phuLucHopDong = $this->db
                ->select('*')
                ->from('hrm_hop_dong_phu_luc')
                ->where('id_hop_dong', $hdld['id_hop_dong'])
                ->order_by('id_hop_dong_phu_luc', 'DESC')
                ->get()
                ->row_array();

            if ($phuLucHopDong) {
                $hdld['ngay_hieu_luc_phu_luc'] = $phuLucHopDong['ngay_hieu_luc'];
            } else {
                $hdld['ngay_hieu_luc_phu_luc'] = '';
            }
        }

        $hopdongNew['dsHDLD'] = $dsHDLD;

        // $current = date('Y-m-d');
        // foreach ($hopdongNew['dsHDLD'] as &$hd) {
        //     if (is_null($hd["ngay_ket_thuc"])) {
        //         $hd["dang_hieu_luc"] = 1; // Hợp đồng không có ngày hết hạn thì luôn hiệu lực
        //     } else {
        //         $hd["dang_hieu_luc"] = ($hd["ngay_ket_thuc"] >= $current) ? 1 : 0;

        //         if ($hd["ngay_ket_thuc"] >= $current) {
        //         }
        //     }
        // }
        // unset($hd);

        $changes = [
            [
                'field' => 'hop_dong',
                'field_name' => 'Hợp đồng lao động',
                'old_value' => [
                    'value' => 'Cập nhật',
                    'label' => 'Hợp đồng: ' . $hopdong['ten_hop_dong'] . ' - Số: ' . $hopdong['so_hop_dong']
                ],
                'new_value' => [
                    'value' => 'Cập nhật',
                    'label' => 'Hợp đồng: ' . $hopdongNew['ten_hop_dong'] . ' - Số: ' . $hopdongNew['so_hop_dong']
                ]
            ]
        ];
        $this->logEmployeeHistory($hopdong['id_nhan_vien'], 'Cập nhật hợp đồng lao động', $changes);

        //create log
        $this->createLog('update', 'Cập nhật hợp đồng số: ' . $hopdongNew['so_hop_dong'], $hopdong, $hopdongNew, 'hrm_hop_dong');
        $this->db->trans_commit();
        resSuccess($hopdongNew, 'Cập nhật thành công', REST_INSTANCE_Controller::HTTP_OK);
    }

    public function themphuluc_post()
    {
        $id = commonRequest('id_hop_dong');
        $hopdong = $this->Hrm_hop_dong_model->find($id);
        if (!$hopdong) {
            resError('Không tìm thấy hợp đồng');
        }

        $validator = new Validator();
        $data = [
            'id_hop_dong' => $id,
            'ten_phu_luc' => commonRequest('ten_phu_luc') ? commonRequest('ten_phu_luc') : null,
            'ngay_ky_phu_luc' => commonRequest('ngay_ky_phu_luc') ? commonRequest('ngay_ky_phu_luc') : null,
            'ngay_hieu_luc' => commonRequest('ngay_hieu_luc') ? commonRequest('ngay_hieu_luc') : null,
            // 'ids_phu_cap' => commonRequest('ids_phu_cap') ? commonRequest('ids_phu_cap') : null,
            'trang_thai' => 1,
            'created_user_id' => $this->getUserLogin()['ql_nguoi_dung_id'],
        ];

        $rules = [
            'ngay_ky_phu_luc' => 'date|required',
            'ngay_hieu_luc' => 'date|required',
        ];
        $customMessages = [
            'ngay_ky_phu_luc.date' => 'Ngày ký phụ lục không đúng định dạng',
            'ngay_hieu_luc.date' => 'Ngày hiệu lực phụ lục không đúng định dạng',
            'ngay_ky_phu_luc.required' => 'Ngày ký phụ lục là bắt buộc',
            'ngay_hieu_luc.required' => 'Ngày hiệu lực phụ lục là bắt buộc',
        ];

        $validator->setCustomMessages($customMessages);
        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();
        $this->db
            ->where('id_hop_dong', $id)
            ->update('hrm_hop_dong_phu_luc', [
                'trang_thai' => false
            ]);

        // Upload nhiều file_phu_luc mới
        $folderName = 'hop-dong-phu-luc/' . date('Y') . '/' . date('m');
        $uploadedFiles = [];
        $files = commonRequest('file_phu_luc') ? commonRequest('file_phu_luc') : null;
        if (isset($files) && !empty($files)) {
            foreach ($files['name'] as $key => $fileName) {
                $file = [
                    'name'     => $files['name'][$key],
                    'type'     => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error'    => $files['error'][$key],
                    'size'     => $files['size'][$key],
                ];
                $result = $this->fileupload->upload($file, $folderName);
                $uploadedFiles[] = $result;
            }
        }

        foreach ($uploadedFiles as $key => $file) {
            unset($uploadedFiles[$key]['success']);
            $uploadedFiles[$key]['file_size'] = exchangeFromKbToLargerCapacity($uploadedFiles[$key]['file_size']);
        }
        $finalFiles = $uploadedFiles;

        $data['file_phu_luc'] = json_encode($finalFiles);

        $id_hop_dong_phu_luc = $this->Hrm_hop_dong_phu_luc_model->insert($data);

        // Thêm phụ cấp
        $ids_phu_cap = commonRequest('ids_phu_cap') ? json_decode(commonRequest('ids_phu_cap'), true) : null;
        if (!$ids_phu_cap) {
            resError('Bắt buộc phải có phụ cấp');
        }
        foreach ($ids_phu_cap as $id) {
            $phuCap = $this->Hrm_phu_cap_model->find($id);
            if ($phuCap) {
                $this->Hrm_phu_luc_phu_cap_model->insert([
                    'id_hop_dong_phu_luc' => $id_hop_dong_phu_luc,
                    'id_phu_cap' => $id,
                    'so_tien' => $phuCap['so_tien'],
                ]);
            }
        }

        $hop_dong_phu_cap_new = $this->Hrm_hop_dong_phu_luc_model->find($id_hop_dong_phu_luc);
        $hop_dong_phu_cap_new['tong_phu_luc'] = $this->Hrm_hop_dong_model
            ->leftJoin('hrm_hop_dong_phu_luc', 'hrm_hop_dong.id_hop_dong = hrm_hop_dong_phu_luc.id_hop_dong')
            ->where('hrm_hop_dong.id_hop_dong', $hop_dong_phu_cap_new['id_hop_dong'])
            ->count();

        $this->createLog('update', 'Thêm phụ lục hợp đồng số: ' . $hopdong['so_hop_dong'], $hop_dong_phu_cap_new, $hop_dong_phu_cap_new, 'hrm_hop_dong');
        $this->db->trans_commit();
        resSuccess($hop_dong_phu_cap_new, 'Thêm phụ lục thành công', REST_INSTANCE_Controller::HTTP_OK);
    }

    public function suaphuluc_post($id)
    {
        $data = [];
        $hop_dong_phu_cap = $this->db
            ->select('*')
            ->from('hrm_hop_dong_phu_luc')
            ->where('id_hop_dong_phu_luc', $id)
            ->get()
            ->row_array();

        if (!$hop_dong_phu_cap) {
            resError('Phụ lục không tồn tại', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        } else if (!$hop_dong_phu_cap['trang_thai']) {
            resError('Không chỉnh sửa được phụ lục hết hiệu lực');
        }

        $validator = new Validator();

        $data = [
            'ten_phu_luc' => commonRequest('ten_phu_luc') ? commonRequest('ten_phu_luc') : null,
            'ngay_ky_phu_luc' => commonRequest('ngay_ky_phu_luc') ? commonRequest('ngay_ky_phu_luc') : null,
            'ngay_hieu_luc' => commonRequest('ngay_hieu_luc') ? commonRequest('ngay_hieu_luc') : null,
            // 'ids_phu_cap' => commonRequest('ids_phu_cap') ? commonRequest('ids_phu_cap') : null,
        ];

        $rules = [
            'ngay_ky_phu_luc' => 'date',
            'ngay_hieu_luc' => 'date',
        ];
        $customMessages = [
            'ngay_ky_phu_luc.date' => 'Ngày ký hợp đồng không đúng định dạng',
            'ngay_hieu_luc.date' => 'Ngày hiệu lực hợp đồng không đúng định dạng',
        ];

        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();

        // Xử lý file đính kèm cũ
        $fileOld = commonRequest('files_dinh_kem_old') ? json_decode(commonRequest('files_dinh_kem_old'), true) : [];
        $fileOldPath = array_column($fileOld, 'duong_dan');

        $fileHD = $this->db->select('*')
            ->from('hrm_hop_dong_phu_luc')
            ->where('id_hop_dong_phu_luc', $id)
            ->get()
            ->row_array();

        $fileHD = json_decode($fileHD['file_phu_luc'], true);

        $fileHDPath = array_column($fileHD, 'file_path');

        $filePathDiff = array_diff($fileHDPath, $fileOldPath);

        foreach ($filePathDiff as $fpd) {
            $this->fileupload->delete($fpd);
        }

        $tempFildOld = [];
        foreach ($fileOld as $key => $file) {
            if (isset($file['duong_dan'])) {
                $tempFildOld[$key]['file_name'] = $file['ten_file_goc'];
                $tempFildOld[$key]['file_path'] = $file['duong_dan'];
                $tempFildOld[$key]['file_extension'] = $file['loai_file'];
                $tempFildOld[$key]['file_size'] = $file['dung_luong'];
            }
        }

        ///////////////////////////////////////////////////////////////////////////////
        $this->db->where('id_hop_dong_phu_luc', $id)->update('hrm_hop_dong_phu_luc', [
            'file_phu_luc' => json_encode($tempFildOld)
        ]);


        // Upload nhiều files_phu_luc mới
        $folderName = 'hop-dong-phu-luc/' . date('Y') . '/' . date('m');
        $uploadedFiles = [];
        $files = commonRequest('files_phu_luc') ? commonRequest('files_phu_luc') : null;
        if (isset($files) && !empty($files)) {
            foreach ($files['name'] as $key => $fileName) {
                $file = [
                    'name'     => $files['name'][$key],
                    'type'     => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error'    => $files['error'][$key],
                    'size'     => $files['size'][$key],
                ];
                $result = $this->fileupload->upload($file, $folderName);
                $uploadedFiles[] = $result;
            }
        }

        foreach ($uploadedFiles as $key => $file) {
            unset($uploadedFiles[$key]['success']);
            $uploadedFiles[$key]['file_size'] = exchangeFromKbToLargerCapacity($uploadedFiles[$key]['file_size']);
        }
        $finalFiles = array_merge($tempFildOld, $uploadedFiles);

        $data['file_phu_luc'] = json_encode($finalFiles);
        $this->db->update('hrm_hop_dong_phu_luc', $data, ['id_hop_dong_phu_luc' => $id]);

        // Cập nhật phụ cấp
        $phuCaps = commonRequest('phuCaps') ? json_decode(commonRequest('phuCaps'), true) : null;
        $phuCapOlds = commonRequest('phuCapOlds') ? json_decode(commonRequest('phuCapOlds'), true) : null;

        $themMoi = [];
        $capNhat = [];
        $xoa = [];

        if ($phuCaps && $phuCapOlds) {
            foreach ($phuCaps as $pc) {
                $idTemp = $pc['id_phu_cap'];
                $soTien = $pc['so_tien'];

                $old = null;
                foreach ($phuCapOlds as $pco) {
                    if ($pco['id_phu_cap'] == $idTemp) {
                        $old = $pco;
                        break;
                    }
                }

                if ($old) {
                    if ($old['so_tien'] != $soTien) {
                        $capNhat[] = $pc;
                    }
                } else {
                    $themMoi[] = $pc;
                }
            }

            foreach ($phuCapOlds as $pco) {
                $idTemp = $pco['id_phu_cap'];
                $found = false;
                foreach ($phuCaps as $pc) {
                    if ($pc['id_phu_cap'] == $idTemp) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $xoa[] = $pco;
                }
            }
        }

        if ($themMoi) {
            foreach ($themMoi as $tm) {
                $this->Hrm_phu_luc_phu_cap_model->insert([
                    'id_hop_dong_phu_luc' => $id,
                    'id_phu_cap' => $tm['id_phu_cap'],
                    'so_tien' => $tm['so_tien'],
                ]);
            }
        }

        if ($capNhat) {
            foreach ($capNhat as $cn) {
                $this->Hrm_phu_luc_phu_cap_model
                    ->where('id_hop_dong_phu_luc', $id)
                    ->where('id_phu_cap', $cn['id_phu_cap'])
                    ->update([
                        'so_tien' => $cn['so_tien']
                    ]);
            }
        }

        if ($xoa) {
            foreach ($xoa as $x) {
                $this->Hrm_phu_luc_phu_cap_model
                    ->where('id_hop_dong_phu_luc', $id)
                    ->where('id_phu_cap', $x['id_phu_cap'])
                    ->delete();
            }
        }

        $hop_dong_phu_cap_new = $this->db
            ->select('*')
            ->from('hrm_hop_dong_phu_luc')
            ->where('id_hop_dong_phu_luc', $id)
            ->get()
            ->row_array();

        $hopdong = $this->Hrm_hop_dong_model->where('id_hop_dong', $hop_dong_phu_cap_new['id_hop_dong'])->first();
        //create log
        $this->createLog('update', 'Cập nhật phụ lục hợp đồng số:' . $hopdong['so_hop_dong'], $hop_dong_phu_cap_new, $hop_dong_phu_cap_new, 'hrm_hop_dong');
        $this->db->trans_commit();
        resSuccess($hop_dong_phu_cap_new, 'Cập nhật thành công', REST_INSTANCE_Controller::HTTP_OK);
    }

    // public function delete_post($id)
    public function delete_post()
    {
        $ids = commonRequest('ids');
        $this->db->trans_start();
        $hopdong = $this->Hrm_hop_dong_model->whereIn('id_hop_dong', $ids)->get();
        if (count($ids) != count($hopdong)) {
            resError('Dữ liệu không hợp lệ');
        }

        $this->Hrm_hop_dong_model->whereIn('id_hop_dong', $ids)->update([
            'deleted_at' => date('Y-m-d H:i:s')
        ]);
        $changesArray = [];
        foreach ($hopdong as $hd) {
            $changesArray[$hd['id_nhan_vien']][] = [
                'field' => 'hop_dong',
                'field_name' => 'Hợp đồng lao động',
                'old_value' => [
                    'value' => 'Xoá',
                    'label' => 'Hợp đồng: ' . $hd['ten_hop_dong'] . ' - Số: ' . $hd['so_hop_dong']
                ],
                'new_value' => null
            ];
            $this->createLog('delete', 'Xóa tạm hợp đồng số: ' . $hd['so_hop_dong'], $hd,  null, 'hrm_hop_dong');
        }
        foreach ($changesArray as $id_nv => $changes) {
            $this->logEmployeeHistory($id_nv, 'Xoá hợp đồng lao động', $changes);
        }
        $this->db->trans_commit();
        resSuccess(null, 'Xóa thành công');
    }

    public function show_get($id)
    {
        $hd = $this->Hrm_hop_dong_model
            ->select('
                hrm_hop_dong.*,
                hrm_nhan_vien.ho_va_ten,
                hrm_nhan_vien.ma_nhan_vien,
                hrm_nhan_vien.cccd_so,
                hrm_nhan_vien.cccd_ngay_cap,
                hrm_nhan_vien.cccd_noi_cap,
                hrm_nhan_vien.mst_ca_nhan,
                hrm_nhan_vien_bao_hiem.ma_bhxh,
            ')
            ->leftJoin('hrm_nhan_vien', 'hrm_nhan_vien.id_nhan_vien = hrm_hop_dong.id_nhan_vien')
            ->leftJoin('hrm_nhan_vien_bao_hiem', 'hrm_nhan_vien_bao_hiem.id_nhan_vien = hrm_nhan_vien.id_nhan_vien')
            ->where('id_hop_dong', $id)
            ->first();

        if (!$hd) resError('Không tìm thấy văn bản', REST_Controller::HTTP_NOT_FOUND);

        $hopDongPhuCap = $this->db
            ->select('*')
            ->from('hrm_hop_dong_phu_luc')
            ->where('id_hop_dong', $id)
            ->where('trang_thai', 1)
            ->get()
            ->row_array();

        if ($hopDongPhuCap) {
            $hd['id_hop_dong_phu_luc'] = $hopDongPhuCap['id_hop_dong_phu_luc'];
            $hd['hdpc_ngay_ky'] = $hopDongPhuCap['ngay_ky_phu_luc'];
            $hd['hdpc_ngay_hieu_luc'] = $hopDongPhuCap['ngay_hieu_luc'];
            $hd['file_phu_luc'] = $hopDongPhuCap['file_phu_luc'];
            $hd['ten_phu_luc'] = $hopDongPhuCap['ten_phu_luc'];

            // $idsPhuCap = explode(",", $hopDongPhuCap['ids_phu_cap']);
            // $hd['phu_cap'] = $this->Hrm_phu_cap_model
            //     ->select('hrm_phu_cap.ten_phu_cap, hrm_phu_cap.id_phu_cap, hrm_phu_cap.so_tien')
            //     ->whereIn('id_phu_cap', $idsPhuCap)
            //     ->get();
            $hd['phu_cap'] = $this->Hrm_phu_luc_phu_cap_model
                // ->select('hrm_phu_cap.ten_phu_cap, hrm_phu_cap.id_phu_cap, hrm_phu_cap.so_tien')
                ->select('
                    hrm_phu_cap.ten_phu_cap, 
                    hrm_phu_cap.id_phu_cap, 
                    hrm_phu_cap.so_tien as so_tien_goc,
                    hrm_phu_luc_phu_cap.so_tien as so_tien,
                ')
                ->leftJoin('hrm_phu_cap', 'hrm_phu_cap.id_phu_cap = hrm_phu_luc_phu_cap.id_phu_cap')
                ->where('id_hop_dong_phu_luc', $hopDongPhuCap['id_hop_dong_phu_luc'])
                ->get();
        } else {
            $hd['id_hop_dong_phu_luc'] = '';
            $hd['hdpc_ngay_ky'] = '';
            $hd['hdpc_ngay_hieu_luc'] = '';
            $hd['file_phu_luc'] = '';
            $hd['phu_cap'] = [];
            $hd['ten_phu_luc'] = [];
        }

        $dsFile = json_decode($hd['files_hop_dong'], true);
        if ($dsFile) {
            foreach ($dsFile as &$file) {
                $file['file_path'] = encryptString($file['file_path']);
                $file['ten_file_goc'] = $file['file_name'];
                $file['duong_dan'] = ($file['file_path']);
                $file['dung_luong'] = $file['file_size'];
                $file['loai_file'] = $file['file_extension'];
            }
            unset($file);
        }

        $hd['files_hop_dong'] = $dsFile;

        resSuccess($hd);
    }

    public function showphuluc_get($id)
    {
        $hopDongPhuLuc = $this->db
            ->select('*')
            ->from('hrm_hop_dong_phu_luc')
            ->where('id_hop_dong_phu_luc', $id)
            ->get()
            ->row_array();

        if ($hopDongPhuLuc) {
            $hd = $this->Hrm_hop_dong_model
                ->select('
                hrm_hop_dong.*,
                hrm_nhan_vien.ho_va_ten,
                hrm_nhan_vien.ma_nhan_vien
            ')
                ->leftJoin('hrm_nhan_vien', 'hrm_nhan_vien.id_nhan_vien = hrm_hop_dong.id_nhan_vien')
                ->where('id_hop_dong', $hopDongPhuLuc['id_hop_dong'])
                ->first();

            $hd['id_hop_dong_phu_luc'] = $hopDongPhuLuc['id_hop_dong_phu_luc'];
            $hd['hdpc_ngay_ky'] = $hopDongPhuLuc['ngay_ky_phu_luc'];
            $hd['hdpc_ngay_hieu_luc'] = $hopDongPhuLuc['ngay_hieu_luc'];
            $hd['file_phu_luc'] = $hopDongPhuLuc['file_phu_luc'];
            $hd['ten_phu_luc'] = $hopDongPhuLuc['ten_phu_luc'];

            // $idsPhuCap = explode(",", $hopDongPhuLuc['ids_phu_cap']);
            // $hd['phu_cap'] = $this->Hrm_phu_cap_model
            //     ->select('hrm_phu_cap.ten_phu_cap, hrm_phu_cap.id_phu_cap, hrm_phu_cap.so_tien')
            //     ->whereIn('id_phu_cap', $idsPhuCap)
            //     ->get();
            $hd['phu_cap'] = $this->Hrm_phu_luc_phu_cap_model
                ->select('
                    hrm_phu_cap.ten_phu_cap, 
                    hrm_phu_cap.id_phu_cap, 
                    hrm_phu_cap.so_tien as so_tien_goc,
                    hrm_phu_luc_phu_cap.so_tien as so_tien,
                ')
                ->leftJoin('hrm_phu_cap', 'hrm_phu_cap.id_phu_cap = hrm_phu_luc_phu_cap.id_phu_cap')
                ->where('id_hop_dong_phu_luc', $hopDongPhuLuc['id_hop_dong_phu_luc'])
                ->get();
        } else {
            $hd['id_hop_dong_phu_luc'] = '';
            $hd['hdpc_ngay_ky'] = '';
            $hd['hdpc_ngay_hieu_luc'] = '';
            $hd['file_phu_luc'] = '';
            $hd['phu_cap'] = [];
            $hd['ten_phu_luc'] = [];
        }

        resSuccess($hd);
    }

    public function sohopdong_get()
    {
        $soHDCuoi = 0;
        $soHD = '';
        $namHD = '';
        $lastSoHopDong = '';

        // $hd = $this->Hrm_hop_dong_model
        //     ->select('so_hop_dong')
        //     ->like('so_hop_dong', '/HĐLĐ-ĐHNCT')
        //     ->orderBy('so_hop_dong', 'desc')
        //     ->first();

        // if (preg_match('/\//', $hd['so_hop_dong'])) {
        //     $hdArray = explode('/', $hd['so_hop_dong']);

        //     if (intval($hdArray[1]) != date("Y")) {
        //         $lastSoHopDong = '01/' . date("Y") . '/HĐLĐ-ĐHNCT';
        //     } else if (intval($hdArray[1]) == date("Y")) {
        //         $soHD = intval($hdArray[0]) + 1;
        //         $namHD = date("Y");
        //         $lastSoHopDong = (string)$soHD . '/' . (string)$namHD . '/HĐLĐ-ĐHNCT';
        //     }
        // }

        $dsHD = $this->Hrm_hop_dong_model
            ->select('so_hop_dong')
            ->like('so_hop_dong', '/HĐLĐ-ĐHNCT')
            ->get();

        if ($dsHD) {
            foreach ($dsHD as $hd) {
                $parts = explode('/', $hd['so_hop_dong']);
                if (count($parts) == 3) {
                    if (intval($parts[1]) != date("Y")) {
                        $soHD = '01';
                        $namHD = date("Y");
                    } else if (intval($parts[1]) == date("Y")) {
                        if (intval($parts[0]) > intval($soHDCuoi)) {
                            $soHDCuoi = $parts[0];

                            $soHD = intval($parts[0]) + 1;
                            $namHD = date("Y");
                        }
                    }
                }
            }

            $lastSoHopDong = $soHD . '/' . $namHD . '/HĐLĐ-ĐHNCT';
        } else {
            $lastSoHopDong = '01' . '/' . date('Y') . '/HĐLĐ-ĐHNCT';
        }

        resSuccess($lastSoHopDong, 'Lấy số cuối hợp đồng');
    }

    public function hopdongcuoi_get($id_nhan_vien)
    {
        $hopdong = $this->Hrm_hop_dong_model
            ->select('hrm_hop_dong.*, hrm_nhan_vien.ho_va_ten')
            ->join('hrm_nhan_vien', 'hrm_nhan_vien.id_nhan_vien = hrm_hop_dong.id_nhan_vien', 'left')
            ->where('hrm_hop_dong.id_nhan_vien', $id_nhan_vien)
            ->where('dang_hieu_luc', 1)
            ->get();

        // if (!$hopdong) {
        //     resError('Không có hợp đồng hiệu lực');
        // }

        resSuccess($hopdong, 'Lấy hợp đồng hiệu lực thành công!');
    }

    private function getExcelColumn()
    {
        $cols = [
            '' => 'STT',
            'so_hop_dong' => 'Số hợp đồng',
            'ma_nhan_vien' => 'Mã nhân viên',
            'ho_va_ten' => 'Họ và tên',
            'cccd_so' => 'CCCD',
            'cccd_ngay_cap' => 'Ngày cấp',
            'cccd_noi_cap' => 'Nơi cấp',
            'mst_ca_nhan' => 'Mã số thuế',
            'ma_bhxh' => 'Số BHXH',
            'gioi_tinh' => 'Giới tính',
            'ten_cong_viec' => 'Chức vụ',
            'ten_don_vi' => 'Vị trí công tác',
            'ngay_bat_dau' => 'Ngày bắt đầu',
            'ngay_ket_thuc' => 'Ngày kết thúc',
            'dang_hieu_luc' => 'Trạng thái',
            'loai_hop_dong' => 'Loại hợp đồng',
            'luong_co_ban' => 'Lương cơ bản',
            'muc_luong_bao_hiem' => 'Mức lương bảo hiểm',
            'ghi_chu' => 'Ghi chú'

        ];
        return $cols;
    }


    public function export_get()
    {
        $auth  = $this->getUserLogin();
        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;
        $fromDate = commonRequest('fromDate') ? commonRequest('fromDate') : null;
        $toDate = commonRequest('toDate') ? commonRequest('toDate') : null;

        $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
            'order' => commonRequest('order'),
            'columns' => commonRequest('columns')
        ] : [];

        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];

        $data = $this->Hrm_hop_dong_model->getListExport($start, $length, $searchValue, $orderBy, $searchKey, $fromDate, $toDate);

        $titles = $this->getExcelColumn();
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $sheet = $objPHPExcel->getActiveSheet();

        // Đặt tiêu đề cột vào hàng đầu tiên dựa trên mảng $titles
        $column = 'A';
        foreach ($titles as $key => $title) {
            $sheet->setCellValue($column . '2', $title);
            $sheet->getStyle($column . '2')->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN, // Kiểu viền (mỏng)
                        'color' => ['rgb' => '000000'], // Màu viền (đen)
                    ],
                ],
                'font' => [
                    'bold' => true,           // In đậm
                    // 'size' => 16,             // Tăng kích thước font
                ],
                'alignment' => [
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,  // Căn giữa nội dung
                ]
            ]);
            $column++;
        }
        $sheet->setCellValue('A1', 'Danh sách hợp đồng'); // Thêm cột thông báo

        // Hợp nhất các ô từ A1 đến S1
        $sheet->mergeCells('A1:S1');

        // Thiết lập chiều cao cho hàng 1
        $sheet->getRowDimension(1)->setRowHeight(30);

        // In đậm chữ và tăng kích thước font cho ô A1
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,           // In đậm
                'size' => 16,             // Tăng kích thước font
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,  // Căn giữa nội dung
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,  // Căn giữa nội dung
            ]
        ]);
        // Đặt dữ liệu vào các hàng tiếp theo
        $row = 3;
        $stt = 0;
        foreach ($data as $item) {
            $column = 'A';
            ++$stt;
            foreach ($titles as $key => $title) {
                $sheet->setCellValue('A' . $row, $stt);

                if ($key == 'gioi_tinh') {
                    if ($item[$key] == 1) {
                        $sheet->setCellValue($column . $row, 'Nam');
                    } else if ($item[$key] == 2) {
                        $sheet->setCellValue($column . $row, 'Nữ');
                    } else {
                        $sheet->setCellValue($column . $row, 'Khác');
                    }
                } else if ($key == 'ngay_bat_dau' || $key == 'ngay_ket_thuc' || $key == 'cccd_ngay_cap') {
                    if ($item[$key] != null) {
                        $sheet->setCellValue($column . $row, date('d/m/Y', strtotime($item[$key])));
                    }
                } else if ($key == 'dang_hieu_luc') {
                    if ($item[$key] == 1) {
                        $sheet->setCellValue($column . $row, 'Có hiệu lực');
                    } else {
                        $sheet->setCellValue($column . $row, 'Hết hiệu lực');
                    }
                } else if ($key == 'loai_hop_dong') {
                    $loaiHopDong = Common::LOAI_HOP_DONG;
                    $itemLoaiHopDong = array_filter($loaiHopDong, function ($lhd) use ($item, $key) {
                        return $lhd['value'] == $item[$key];
                    });

                    // resSuccess($itemLoaiHopDong, 'Lấy loại hợp đồng');

                    $sheet->setCellValue($column . $row, $itemLoaiHopDong[$item[$key]]['label']);
                } else {
                    $sheet->setCellValue($column . $row, isset($item[$key]) ? $item[$key] : '');
                }

                $sheet->getStyle($column . $row)->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THIN, // Kiểu viền (mỏng)
                            'color' => ['rgb' => '000000'], // Màu viền (đen)
                        ],
                    ],
                ]);
                // Bật wrap text cho ô 
                $sheet->getStyle($column . $row)->getAlignment()->setWrapText(true);

                // Đặt tự động điều chỉnh độ rộng cho cột 
                $sheet->getColumnDimension($column)->setAutoSize(true);

                // Đặt tự động điều chỉnh độ cao cho hàng 
                $sheet->getRowDimension($row)->setRowHeight(-1);
                $column++;
            }
            $row++;
        }

        // Đặt tiêu đề cho file Excel
        $directory = 'uploads/export/' . date('Y') . '/'; // Thư mục để lưu file
        $filename = 'hopdong_' . time() . '.xlsx'; // Tên file kèm timestamp để tránh trùng lặp
        $filePath = $directory . $filename;
        // Kiểm tra và tạo thư mục nếu chưa tồn tại
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);

        if (file_exists($filePath)) {
            $this->createLog('Export', 'Export danh sách hợp đồng', NULL, 'Export danh sách hợp đồng', 'hrm_hop_dong');
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Success',
                'success' => true,
                'data' => base_url($filePath)
            ], REST_INSTANCE_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                'message' => 'File not found',
                'success' => false,
                'data' => null
            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }
        // exit;
    }

    public function xemluongcoban_get($id)
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Không tìm thấy người dùng');
        }

        $hopdong = $this->Hrm_hop_dong_model->find($id);

        $this->createLog('Xem lương', 'Xem lương hợp đồng số ' . $hopdong['so_hop_dong'], null, null, 'hrm_hop_dong');
        resSuccess(null, 'Ghi log thành công!');
    }

    public function xemphuluc_get($id)
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Không tìm thấy người dùng');
        }

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

        $data['searchKey']['id_hop_dong'] = $id;

        $data = $this->Hrm_hop_dong_phu_luc_model->getAll($data['start'], $data['length'], $data['searchValue'], $data['order'], $data['columns'], $data['searchKey'],  $data['fromDate'], $data['toDate'], $auth);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
            // 'sql' => $data['sql'],
        ]);
    }

    public function view_log_get()
    {
        $searchValue = commonRequest('searchValue');
        $start = (int)(commonRequest('start') ?? 0);
        $length = (int)(commonRequest('length') ?? 50);

        $this->Ql_nhat_ky_model
            ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = ql_nhat_ky.ql_nguoi_dung_id', 'left')
            ->where('ql_nhat_ky_controller', 'hopdong');

        if ($searchValue) {
            $this->Ql_nhat_ky_model->group_start();
            $this->Ql_nhat_ky_model->like('ql_nhat_ky_hanh_dong', $searchValue);
            $this->Ql_nhat_ky_model->or_like('ql_nhat_ky_noi_dung', $searchValue);
            $this->Ql_nhat_ky_model->or_like('ql_nguoi_dung_ho_ten', $searchValue);
            $this->Ql_nhat_ky_model->or_like('ql_nguoi_dung_email', $searchValue);
            $this->Ql_nhat_ky_model->group_end();
        }

        $total = $this->Ql_nhat_ky_model->count();

        $log = $this->Ql_nhat_ky_model
            ->select('
                ql_nhat_ky.ql_nhat_ky_id,
                ql_nhat_ky.ql_nhat_ky_hanh_dong,
                ql_nhat_ky.ql_nhat_ky_noi_dung,
                ql_nhat_ky.ql_nhat_ky_gia_tri_cu,
                ql_nhat_ky.ql_nhat_ky_gia_tri_moi,
                ql_nhat_ky.ql_nhat_ky_bang_du_lieu,
                ql_nhat_ky.ql_nhat_ky_controller,
                ql_nhat_ky.ql_nhat_ky_ngay_tao,
                ql_nguoi_dung.ql_nguoi_dung_id,
                ql_nguoi_dung.ql_nguoi_dung_ho_ten,
                ql_nguoi_dung.ql_nguoi_dung_email
            ')
            ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = ql_nhat_ky.ql_nguoi_dung_id', 'left')
            ->where('ql_nhat_ky_controller', 'hopdong')
            ->orderBy('ql_nhat_ky_ngay_tao', 'desc')
            ->get($length, $start);

        $hanhDongMap = [
            'create' => 'Tạo mới',
            'update' => 'Cập nhật',
            'delete' => 'Xóa',
        ];

        $data = array_map(function ($item) use ($hanhDongMap) {
            $giaTri_cu  = $item['ql_nhat_ky_gia_tri_cu']  ? json_decode($item['ql_nhat_ky_gia_tri_cu'], true)  : null;
            $giaTri_moi = $item['ql_nhat_ky_gia_tri_moi'] ? json_decode($item['ql_nhat_ky_gia_tri_moi'], true) : null;

        $fieldLabels = [
            // Hợp đồng chính
            'so_hop_dong'           => 'Số hợp đồng',
            'ten_hop_dong'          => 'Tên hợp đồng',
            'loai_hop_dong'         => 'Loại hợp đồng',
            'hinh_thuc_lam_viec'    => 'Hình thức làm việc',
            'thoi_han_hop_dong'     => 'Thời hạn hợp đồng (tháng)',
            'ngay_ky'               => 'Ngày ký',
            'ngay_bat_dau'          => 'Ngày bắt đầu',
            'ngay_ket_thuc'         => 'Ngày kết thúc',
            'dang_hieu_luc'         => 'Trạng thái hiệu lực',
            'nguoi_dai_dien_ky'     => 'Người đại diện ký',
            'chuc_danh'             => 'Chức danh',
            'trich_yeu'             => 'Trích yếu',
            'ghi_chu'               => 'Ghi chú',
            // Lương
            'muc_luong'             => 'Mức lương',
            'luong_co_ban'          => 'Lương cơ bản',
            'muc_luong_bao_hiem'    => 'Mức lương bảo hiểm',
            'muc_luong_thu_viec'    => 'Mức lương thử việc',
            'ti_le_huong_luong'     => 'Tỉ lệ hưởng lương (%)',
            'so_tien'               => 'Số tiền',
            // Phụ lục
            'ten_phu_luc'           => 'Tên phụ lục',
            'ngay_ky_phu_luc'       => 'Ngày ký phụ lục',
            'ngay_hieu_luc'         => 'Ngày hiệu lực',
            'file_phu_luc'          => 'File phụ lục',
            'trang_thai'            => 'Trạng thái',
            // Liên kết
            'id_nhan_vien'          => 'Nhân viên (ID)',
            'id_don_vi_cong_tac'    => 'Đơn vị công tác (ID)',
            'id_vi_tri_cong_viec'   => 'Vị trí công việc (ID)',
            'id_ty_le_bao_hiem'     => 'Tỉ lệ bảo hiểm (ID)',
            'id_hop_dong'           => 'Hợp đồng (ID)',
            // Meta
            'nguoi_tao'             => 'Người tạo',
            'nguoi_sua'             => 'Người sửa',
            'ngay_tao'              => 'Ngày tạo',
            'ngay_sua'              => 'Ngày sửa',
            'deleted_at'            => 'Ngày xóa',
            'files_hop_dong'        => 'File hợp đồng',
            'thong_bao_het_han'     => 'Thông báo hết hạn',
        ];

        // Những field không cần hiển thị trong lịch sử
        $skipFields = [
            'ngay_tao', 'ngay_sua', 'nguoi_tao', 'nguoi_sua',
            'deleted_at', 'updated_at', 'created_at', 'files_hop_dong',
        ];

            $chi_tiet = [];
            if (is_array($giaTri_cu) && is_array($giaTri_moi)) {
                foreach ($giaTri_moi as $key => $valMoi) {
                    if (in_array($key, $skipFields)) continue;
                    $valCu = isset($giaTri_cu[$key]) ? $giaTri_cu[$key] : null;
                    if ($valCu !== $valMoi) {
                        $label = isset($fieldLabels[$key]) ? $fieldLabels[$key] : $key;
                        $chi_tiet[$label] = ['cu' => $valCu, 'moi' => $valMoi];
                    }
                }
            }

            $hanhDongRaw   = strtolower(trim($item['ql_nhat_ky_hanh_dong'] ?? ''));
            $hanhDongLabel = isset($hanhDongMap[$hanhDongRaw])
                ? $hanhDongMap[$hanhDongRaw]
                : ucfirst($item['ql_nhat_ky_hanh_dong'] ?? '');

            return [
                'id'                    => $item['ql_nhat_ky_id'],
                'hanh_dong'             => $hanhDongLabel,
                'noi_dung'              => $item['ql_nhat_ky_noi_dung'],
                'bang_du_lieu'          => $item['ql_nhat_ky_bang_du_lieu'],
                'ten_nguoi_thuc_hien'   => $item['ql_nguoi_dung_ho_ten'],
                'email_nguoi_thuc_hien' => $item['ql_nguoi_dung_email'],
                'thoi_gian'             => $item['ql_nhat_ky_ngay_tao'],
                'chi_tiet'              => $chi_tiet,
            ];
        }, $log);

        resSuccess($data, 'Lấy danh sách nhật ký thành công', REST_Controller::HTTP_OK, true, [
            'total' => $total,
        ]);
    }

    public function import_post()
    {
        try {

            $stop = false;
            $message = '';
            $dataList = [];
            $file_import = commonRequest('file_excel');

            if ($file_import) {

                $config['upload_path'] = 'uploads/excel/hopdong/'; // Thư mục để lưu file
                $config['allowed_types'] = 'xls|xlsx';

                if (!is_dir($config['upload_path'])) {
                    mkdir($config['upload_path'], 0755, true);
                }
                //Đổi tên file
                $newFileName = pathinfo($file_import['name'], PATHINFO_FILENAME) . '-' . date('Ymd') . '-' . time() . '.' . pathinfo($file_import['name'], PATHINFO_EXTENSION);
                $config['file_name'] = $newFileName;

                $this->upload->initialize($config);

                if (!$this->upload->do_upload('file_excel')) {
                    $stop = true;
                    $message = 'Thất bại: ' . $this->upload->display_errors();
                } else {
                    $uploadData = $this->upload->data(); // Lấy dữ liệu file đã upload 
                    $fileName = $uploadData['file_name']; // Tên file 
                    //gọi đến importExcel của Pxl để xuất dữ liệu mảng 
                    $path = $config['upload_path'] . $fileName;

                    $dataList = $this->pxl->importExcel(11, 12, $path);
                    $objPHPExcel = PHPExcel_IOFactory::load($path);
                    $sheet = $objPHPExcel->getActiveSheet();
                    $highestColumn = $sheet->getHighestColumn();
                    $highestRow = $sheet->getHighestRow();

                    $highestColumn = 'S';
                    $columnResult = $highestColumn . '10';
                    $sheet->setCellValue($columnResult, 'KẾT QUẢ');
                    $sheet->getColumnDimension($highestColumn)->setAutoSize(true);
                    $sheet->getStyle($columnResult)->applyFromArray(
                        array(
                            'font' => array(
                                'bold' => true,
                            ),
                            'alignment' => array(
                                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
                            ),
                        )
                    );

                    $range = $columnResult . ':' . $highestColumn . $highestRow;
                    $sheet->getStyle($range)->applyFromArray(
                        array(
                            'borders' => array(
                                'allborders' => array(
                                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                                    'color' => array('rgb' => '000000')
                                )
                            )
                        )
                    );

                    $requiredKeys = [
                        'ma_nhan_vien',
                        'ngay_ky',
                        'loai_hop_dong',
                        'hinh_thuc_lam_viec',
                        'ngay_bat_dau',
                    ];
                    //Kiểm tra có upload file rỗng không
                    foreach ($dataList as $index => $l) {
                        // Kiểm tra nếu tất cả các giá trị trong mảng đều rỗng thì loại bỏ
                        if (!array_filter($l)) {
                            unset($dataList[$index]);
                            continue;
                        }
                    }

                    if (!empty($dataList)) {
                        $firstDataList = reset($dataList); //reset chỉ lấy 1 mảng bên trong DataList
                        // Kiểm tra xem tất cả các khóa cần thiết có tồn tại trong phần tử không
                        $missingKeys = array_diff($requiredKeys, array_keys($firstDataList));
                        if (!empty($missingKeys)) {
                            unlink($path);
                            return $this->response([
                                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                                'message' => "File không đúng định dạng hoặc đã bị chỉnh sửa hàng mẫu, Vui lòng tải lại file mẫu và nhập lại dữ liệu",
                                'success' => false,
                                'data' => [],
                            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
                        }
                        $dataInsert = [];
                        $countError = $countSuccess = 0;
                        $totalRow = count($dataList);

                        $startRow_columnResult = 12;

                        foreach ($dataList as $key => $l) {
                            $nhanvien = null;
                            $errorMessages = [];
                            $field_PrimaryKey = [
                                'ma_nhan_vien' => 'Mã nhân viên đang rỗng.',
                                'ngay_ky' => 'Ngày ký đang rỗng.',
                                'loai_hop_dong' => 'Loại hợp đồng đang rỗng.',
                                'hinh_thuc_lam_viec' => 'Hình thức đang rỗng.',
                                'ngay_bat_dau' => 'Ngày bắt đầu đang rỗng.',
                            ];

                            $keys = [];
                            $keys = array_keys($l);
                            foreach ($field_PrimaryKey as $key => $errorMessage) {
                                if (($key == 'ma_nhan_vien') && !empty($l[$key])) {
                                    $nhanvien = $this->Hrm_nhan_vien_model->where('ma_nhan_vien', $l[$key])->first();
                                    if (!$nhanvien) {
                                        $errorMessages[] = 'Nhân viên không tồn tại.';

                                        $indexKey = array_search($key, $keys) + 1; // bỏ đi cột stt
                                        $column = PHPExcel_Cell::stringFromColumnIndex($indexKey);
                                        $sheet->getStyle($column . $startRow_columnResult)->applyFromArray(
                                            array(
                                                'fill' => array(
                                                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                                    'color' => array('rgb' => 'f9ca24')
                                                )
                                            )
                                        );
                                    }
                                }

                                if (($key == 'loai_hop_dong') && !empty($l[$key])) {
                                    if (!array_key_exists($l[$key], Common::LOAI_HOP_DONG)) {
                                        $errorMessages[] = 'Loại hợp đồng không hợp lệ.';

                                        $indexKey = array_search($key, $keys) + 1; // bỏ đi cột stt
                                        $column = PHPExcel_Cell::stringFromColumnIndex($indexKey);
                                        $sheet->getStyle($column . $startRow_columnResult)->applyFromArray(
                                            array(
                                                'fill' => array(
                                                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                                    'color' => array('rgb' => 'f9ca24')
                                                )
                                            )
                                        );
                                    }
                                }

                                if (($key == 'hinh_thuc_lam_viec') && !empty($l[$key])) {
                                    if (!array_key_exists($l[$key], Common::HINH_THUC_LAM_VIEC)) {
                                        $errorMessages[] = 'Hình thức làm việc không hợp lệ.';

                                        $indexKey = array_search($key, $keys) + 1; // bỏ đi cột stt
                                        $column = PHPExcel_Cell::stringFromColumnIndex($indexKey);
                                        $sheet->getStyle($column . $startRow_columnResult)->applyFromArray(
                                            array(
                                                'fill' => array(
                                                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                                    'color' => array('rgb' => 'f9ca24')
                                                )
                                            )
                                        );
                                    }
                                }

                                if (empty($l[$key])) {
                                    $errorMessages[] = $errorMessage;

                                    $indexKey = array_search($key, $keys) + 1; // bỏ đi cột stt
                                    $column = PHPExcel_Cell::stringFromColumnIndex($indexKey);
                                    $sheet->getStyle($column . $startRow_columnResult)->applyFromArray(
                                        array(
                                            'fill' => array(
                                                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                                'color' => array('rgb' => 'f9ca24')
                                            )
                                        )
                                    );
                                }
                            }

                            $l['ketqua'] = '';
                            if (!empty($errorMessages)) {
                                $l['ketqua'] = implode(' ', $errorMessages);
                                $countError++;
                            } else {
                                $invalidData = false;
                                if (!$invalidData) {
                                    $l['ketqua'] = 'Thành công';
                                    $countSuccess++;

                                    $dong_bao_hiem_upper = mb_strtoupper($l['dong_bao_hiem'], 'UTF-8');
                                    if ($dong_bao_hiem_upper == 'CÓ') {
                                        $id_ty_le_bao_hiem = $this->Hrm_ty_le_bao_hiem_model->orderBy('ngay_ap_dung', 'DESC')->first()['id_ty_le_bao_hiem'];
                                    } else {
                                        $id_ty_le_bao_hiem = null;
                                    }

                                    $this->Hrm_hop_dong_model->where('id_nhan_vien', $nhanvien['id_nhan_vien'])->update([
                                        'dang_hieu_luc' => 0
                                    ]);

                                    $dataInsert = [
                                        'id_nhan_vien' => $nhanvien['id_nhan_vien'],
                                        'ten_hop_dong' => $l['ten_hop_dong'],
                                        'so_hop_dong' => $l['so_hop_dong'],
                                        'ngay_bat_dau' => DateTime::createFromFormat('d/m/Y', $l['ngay_bat_dau'])->format('Y-m-d'),
                                        'ngay_ket_thuc' => DateTime::createFromFormat('d/m/Y', $l['ngay_ket_thuc'])->format('Y-m-d'),
                                        'muc_luong' => $l['muc_luong'],
                                        'luong_co_ban' => $l['luong_co_ban'],
                                        'muc_luong_bao_hiem' => $l['muc_luong_bao_hiem'],
                                        'id_ty_le_bao_hiem' => $id_ty_le_bao_hiem,
                                        'loai_hop_dong' => $l['loai_hop_dong'],
                                        'ngay_tao' => date('Y-m-d H:i:s'),
                                        'ngay_sua' => date('Y-m-d H:i:s'),
                                        'nguoi_tao' => $this->getUserLogin()['ql_nguoi_dung_id'],
                                        'nguoi_sua' => $this->getUserLogin()['ql_nguoi_dung_id'],
                                        'id_vi_tri_cong_viec' => $nhanvien['id_vi_tri_cong_viec'],
                                        'thoi_han_hop_dong' => Common::LOAI_HOP_DONG[$l['loai_hop_dong']]['time'],
                                        'vi_tri_cong_viec' => null,
                                        'dang_hieu_luc' => 1,
                                        'ti_le_huong_luong' => $l['ti_le_huong_luong'],
                                        'id_don_vi_cong_tac' => $nhanvien['id_don_vi_cong_tac'],
                                        'ngay_ky' => DateTime::createFromFormat('d/m/Y', $l['ngay_ky'])->format('Y-m-d'),
                                        'nguoi_dai_dien_ky' => $l['nguoi_dai_dien_ky'],
                                        'trich_yeu' => $l['trich_yeu'],
                                        'ghi_chu' => '',
                                        'chuc_danh' => $l['chuc_danh'],
                                        'hinh_thuc_lam_viec' => $l['hinh_thuc_lam_viec'],
                                    ];

                                    $dataInsertArray[] = $dataInsert;
                                    $id_hop_dong = $this->Hrm_hop_dong_model->insert($dataInsert);
                                }
                            }
                            $sheet->setCellValue($highestColumn . $startRow_columnResult, $l['ketqua']);
                            $color = strtolower($l['ketqua']) != strtolower('Thành công') ? 'f57878' : '77c884';
                            $sheet->getStyle($highestColumn . $startRow_columnResult)->applyFromArray(
                                array(
                                    'fill' => array(
                                        'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                        'color' => array('rgb' => $color)
                                    )
                                )
                            );

                            $sheet->getColumnDimension($highestColumn)->setAutoSize(true);
                            $startRow_columnResult++;
                        }
                        if (!empty($dataInsertArray)) {
                            $this->createLog('Import', 'Import danh sách hợp đồng (' . $countSuccess . '/' . $totalRow . ' dòng thành công)', NULL, $dataInsertArray, 'hrm_hop_dong');
                            if ($countError > 0) {
                                $message = 'Thêm thành công ' . count($dataInsert) . '/' . $totalRow . ' dòng </br>' .
                                    'Thêm thất bại ' . $countError . '/' . $totalRow . ' dòng';
                            } else {
                                $message = $countSuccess . "/" . $totalRow . " dòng được thêm thành công";
                            }
                        } else {
                            $message = "Không có dòng dữ liệu import hợp lệ";
                        }
                    } else {
                        $stop = $unlinkFile = true;
                        $message = "File import đang rỗng";
                    }
                    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
                    $objWriter->save($path); // Lưu đè file gốc
                }
                if ($stop) {
                    if (isset($unlinkFile) && $unlinkFile) unlink($path);

                    $this->response([
                        'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                        'message' => $message,
                        'success' => false,
                        'data' => [],
                    ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
                } else {
                    // Đọc nội dung file vào biến
                    $fileContent = file_get_contents($path);

                    // Mã hóa nội dung file dưới dạng base64
                    $encodedContent = base64_encode($fileContent);

                    // Xóa file 
                    if (file_exists($path)) unlink($path);

                    $this->response([
                        'status' => REST_INSTANCE_Controller::HTTP_CREATED,
                        'message' => $message,
                        'success' => true,
                        'data' => [
                            'file_content' => $encodedContent,
                            'file_name' => $fileName,
                            // 'path_file_excel' => base_url($path)
                            'countError' => $countError,
                            'dataInsert' => $dataInsert,
                        ],
                    ], REST_INSTANCE_Controller::HTTP_CREATED);
                }
            } else {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => 'Không tìm thấy file này',
                    'success' => false,
                    'data' => [],
                ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (Exception $e) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $e->getMessage(),
                'success' => false,
                'data' => null
            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $t) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $t->getMessage(),
                'success' => false,
                'data' => null
            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update_files_post($id)
    {
        // Lưu biến sau cùng để cập nhật db
        $tempFiles = [];

        $la_file_ban_hanh = commonRequest('la_file_ban_hanh') ?? null;
        // resError('', 500, $la_file_ban_hanh);
        if (!in_array($la_file_ban_hanh, [0, 1])) {
            resError('Lỗi', 'Dữ liệu không hợp lệ');
        }
        // $currentFiles = $this->E_file_dinh_kem_model->where('la_file_ban_hanh', $la_file_ban_hanh)->where('id_van_ban', $id)->get();

        $currentFilesJSON = $this->Hrm_hop_dong_model
            ->select('files_hop_dong')
            ->where('id_hop_dong', $id)->first();

        $currentFiles = json_decode($currentFilesJSON['files_hop_dong'], true) ?? [];
        $tempFiles = $currentFiles;

        // Lấy file cũ CÒN LẠI (ĐƯỢC GIỮ) đã nhận được
        $file_dinh_kem_old = json_decode(commonRequest('file_dinh_kem_old'), true) ?? [];
        $file_dinh_kem_old = array_map(function ($f_old) {
            // $f_old['file_path'] = decryptString($f_old['file_path']);



            ////////////////////// Tại sao vẫn còn là đường dẫn -> Check danhsachfile_json FE
            $f_old['file_path'] = decryptString($f_old['duong_dan']);
            return $f_old;
        }, $file_dinh_kem_old); // Mã hóa về đường dẫn gốc
        $this->db->trans_begin();
        try {
            foreach ($currentFiles as $key => $file) {
                if (!in_array($file['file_path'], array_column($file_dinh_kem_old, 'file_path'))) {
                    @unlink(FCPATH . $file['file_path']);

                    unset($tempFiles[$key]);
                }
            }
            $tempFiles = array_values($tempFiles);

            $folderName = 'hop-dong/' . date('Y') . '/' . date('m');
            $uploadedFiles = [];
            if (isset($_FILES['file_dinh_kem'])) {
                $files = $_FILES['file_dinh_kem']; // Tên input từ form    
                foreach ($files['name'] as $key => $fileName) {
                    // Chuẩn bị dữ liệu cho từng file
                    $file = [
                        'name'     => $files['name'][$key],
                        'type'     => $files['type'][$key],
                        'tmp_name' => $files['tmp_name'][$key],
                        'error'    => $files['error'][$key],
                        'size'     => $files['size'][$key],
                    ];

                    $result = $this->fileupload->upload($file, $folderName);
                    $uploadedFiles[] = $result;
                }
            }
            $uploadedFull = true;
            foreach ($uploadedFiles as $uploadedFile) {
                if ($uploadedFile['success']) {
                    unset($uploadedFiles[$key]['success']);
                    $uploadedFiles[$key]['file_size'] = exchangeFromKbToLargerCapacity($uploadedFiles[$key]['file_size']);
                    $uploadedFiles[$key]['is_public'] = 1;
                } else {
                    $uploadedFull = false;
                    break;
                }
            }
            if (!$uploadedFull) {
                foreach ($uploadedFiles as $f) {
                    if ($f['success']) {
                        $delete = $this->fileupload->delete($f['file_path']);
                    }
                }
                $this->db->trans_rollback();
                resError('Có lỗi xảy ra khi upload file đính kèm');
            }

            $mergeFiles = array_merge($tempFiles, $uploadedFiles);
            $mergeFiles = array_values($mergeFiles);
            $mergeFiles = json_encode($mergeFiles);

            $this->Hrm_hop_dong_model->where('id_hop_dong', $id)->update([
                'files_hop_dong' => $mergeFiles,
            ]);

            $dataNew = $this->Hrm_hop_dong_model
                ->select('files_hop_dong')
                ->where('id_hop_dong', $id)->first();

            $dataNew = json_decode($dataNew['files_hop_dong'], true);
            //Ghi log
            $this->createLog('update', 'Cập nhật file', json_encode($currentFiles), $dataNew, 'hrm_hop_dong');
            $this->db->trans_commit();

            resSuccess(null, 'Đã cập nhật file');
        } catch (\Throwable $th) {
            //throw $th;
            $this->db->trans_rollback();
            resError('Lỗi xử lý query', 500, $th->getMessage());
        }
    }

}
