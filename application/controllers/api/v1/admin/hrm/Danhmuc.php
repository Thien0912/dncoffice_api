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
 * @property Fileupload $fileupload
 */



class Danhmuc extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_nhan_vien_model', 'Ql_nguoi_dung_model', 'Hrm_hop_dong_model', 'Hrm_vi_tri_cong_viec_model', 'Hrm_phu_cap_model', 'E_don_vi_model']);
        $this->load->library(['Validator', 'Fileupload', 'Common']);
    }
    public function index_get()
    {
        $data = [];

        $getDSNhanVien = commonRequest('getDSNhanVien') ? commonRequest('getDSNhanVien') : null;
        if ($getDSNhanVien) {
            $data['nhan_vien'] = $this->Hrm_nhan_vien_model
                ->select("*")
                ->get();

            $data['cong_viec'] = $this->Hrm_vi_tri_cong_viec_model
                ->select("*")
                ->get();

            $data['phu_cap'] = $this->Hrm_phu_cap_model->all();
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

        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;

        $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
            'order' => commonRequest('order'),
            'columns' => commonRequest('columns')
        ] : [];

        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];

        $data = $this->Hrm_hop_dong_model->getAll($start, $length, $searchValue, $orderBy, $searchKey);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function create_post()
    {
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
        if (!in_array($loai_hop_dong, ['Thu_viec', 'Co_thoi_han', 'Khong_thoi_han'])) {
            $validator->addError('', 'loai_hop_dong', 'Loại hợp đồng không hợp lệ');
            resBadrequest(resBadrequest($validator->errors()));
        }

        $auth = $this->getUserLogin();
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
        ];
        $rules = [
            'id_nhan_vien' => 'required|integer',
            'ngay_bat_dau' => 'date',
            'ngay_ket_thuc' => 'date',
            'luong_co_ban' => 'required',
            'muc_luong_bao_hiem' => 'required',

        ];
        $customMessages = [
            'id_nhan_vien.required' => 'Vui lòng chọn nhân viên',
            'ngay_bat_dau.date' => 'Ngày bắt đầu hợp đồng không đúng định dạng',
            'ngay_ket_thuc.date' => 'Ngày kết thúc hợp đồng không đúng định dạng',
            'luong_co_ban.required' => 'Vui lòng nhập lương cơ bản',
            'muc_luong_bao_hiem.required' => 'Vui lòng nhập lương bảo hiểm',
        ];

        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        // Upload file_hop_dong
        $folderName = 'hop-dong/' . date('Y') . '/' . date('m');
        if (isset($_FILES['file_hop_dong'])) {
            $uploadedFile = $this->fileupload->upload($_FILES['file_hop_dong'], $folderName);
            if (!$uploadedFile['success']) {
                resBadrequest([
                    'file_hop_dong' => [
                        'Không thể tải lên file'
                    ]
                ]);
            } else {
                $this->load->helper('file');
                $data['file_hop_dong_duong_dan'] = $uploadedFile['file_path'];
                $data['file_hop_dong_ten_file_goc'] = $uploadedFile['file_name'];
                $data['file_hop_dong_loai_file'] = get_mime_by_extension($_FILES['file_hop_dong']['name']);
            }
        }

        $this->db->trans_start();

        $ids_phu_cap = commonRequest('ids_phu_cap') ? commonRequest('ids_phu_cap') : null;
        $ids_phu_cap_array = explode(',', $ids_phu_cap);
        if (!empty($ids_phu_cap_array)) {
            foreach ($ids_phu_cap_array as $id) {
                // $phuCap = $this->Hrm_phu_cap_model->find($id);

                $this->db->insert('hrm_phu_cap_nhan_vien', [
                    'id_nhan_vien' => commonRequest('id_nhan_vien'),
                    'id_phu_cap' => $id,
                    'so_tien' => 100000,
                ]);
            }
        }

        $hopdong = $this->Hrm_hop_dong_model->create($data);

        $nhanvien = $this->Hrm_nhan_vien_model->find($data['id_nhan_vien']);
        $hopdong['nhan_vien'] = $nhanvien;


        $loaiHopDong = Common::LOAI_HOP_DONG;

        $hopdong['ten_loai_hop_dong'] = isset($loaiHopDong[$data['loai_hop_dong']]) ? $loaiHopDong[$data['loai_hop_dong']]['label'] : null;
        $viTriCongViec = $this->Hrm_vi_tri_cong_viec_model->find($hopdong['id_vi_tri_cong_viec']);
        $hopdong['ten_vi_tri_cong_viec'] = $viTriCongViec ? $viTriCongViec['ten_cong_viec'] : null;

        //create log
        $this->createLog('create', 'Tạo mới hợp đồng', null, $hopdong, 'hrm_hop_dong');
        $this->db->trans_commit();
        resSuccess($hopdong, 'Thêm thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }

    public function update_post($id)
    {
        $hopdong = $this->Hrm_hop_dong_model->find($id);
        if (!$hopdong) {
            resError('Hợp đồng khồng tồn tại', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
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
        if (!in_array($loai_hop_dong, ['Thu_viec', 'Co_thoi_han', 'Khong_thoi_han'])) {
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
            'dang_hieu_luc' => 1,
            'luong_co_ban' => commonRequest('luong_co_ban') ? commonRequest('luong_co_ban') : null,
            'ti_le_huong_luong' => commonRequest('ti_le_huong_luong') ? commonRequest('ti_le_huong_luong') : null,
            'id_don_vi_cong_tac' => commonRequest('id_don_vi_cong_tac') ? commonRequest('id_don_vi_cong_tac') : null,

            // Trường thêm mới
            'ngay_ky' => commonRequest('ngay_ky') ? commonRequest('ngay_ky') : null,
            'nguoi_dai_dien_ky' => commonRequest('nguoi_dai_dien_ky') ? commonRequest('nguoi_dai_dien_ky') : null,
            'trich_yeu' => commonRequest('trich_yeu') ? commonRequest('trich_yeu') : null,
            'ghi_chu' => commonRequest('ghi_chu') ? commonRequest('ghi_chu') : null,
            'chuc_danh' => commonRequest('chuc_danh') ? commonRequest('chuc_danh') : null,

        ];
        $rules = [
            // 'id_nhan_vien' => 'required|integer',
            'ngay_bat_dau' => 'date',
            'ngay_ket_thuc' => 'date',
            'luong_co_ban' => 'required',
            'muc_luong_bao_hiem' => 'required',
        ];
        $customMessages = [
            // 'id_nhan_vien.required' => 'Vui lòng chọn nhân viên',
            'ngay_bat_dau.date' => 'Ngày bắt đầu hợp đồng không đúng định dạng',
            'ngay_ket_thuc.date' => 'Ngày kết thúc hợp đồng không đúng định dạng',
            'luong_co_ban.required' => 'Vui lòng nhập lương cơ bản',
            'muc_luong_bao_hiem.required' => 'Vui lòng nhập lương bảo hiểm',
        ];

        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        //Upload avatar
        $folderName = 'hop-dong/' . date('Y') . '/' . date('m');
        if (isset($_FILES['file_hop_dong'])) {
            $uploadedFile = $this->fileupload->upload($_FILES['file_hop_dong'], $folderName);
            if (!$uploadedFile['success']) {
                resBadrequest([
                    'file_hop_dong' => [
                        'Không thể tải lên file'
                    ]
                ]);
            } else {
                $data['file_hop_dong_duong_dan'] = $uploadedFile['file_path'];
                $data['file_hop_dong_ten_file_goc'] = $uploadedFile['file_name'];
                $data['file_hop_dong_loai_file'] = $uploadedFile['file_extension'];
                //delete file
                $deletefile = $this->fileupload->delete($hopdong['file_hop_dong_duong_dan']);
            }
        }

        $this->db->trans_start();

        $ids_phu_cap = commonRequest('ids_phu_cap') ? commonRequest('ids_phu_cap') : null;
        $ids_phu_cap_old = commonRequest('ids_phu_cap_old') ? commonRequest('ids_phu_cap_old') : null;
        $ids_phu_cap_array = explode(',', $ids_phu_cap);
        $ids_phu_cap_old_array = explode(',', $ids_phu_cap_old);


        if (!empty($ids_phu_cap_array) || !empty($ids_phu_cap_old_array)) {
            $ids_deleted = array_diff($ids_phu_cap_old_array, $ids_phu_cap_array);
            foreach ($ids_deleted as $id) {
                $this->db->where('id_nhan_vien', $hopdong['id_nhan_vien']);
                $this->db->where('id_phu_cap', $id);
                $this->db->delete('hrm_phu_cap_nhan_vien');
            }

            $ids_added = array_diff($ids_phu_cap_array, $ids_phu_cap_old_array);
            foreach ($ids_added as $id_added) {
                $this->db->insert('hrm_phu_cap_nhan_vien', [
                    'id_nhan_vien' => $hopdong['id_nhan_vien'],
                    'id_phu_cap' => $id_added,
                    'so_tien' => 100000,
                ]);
            }
        }

        $this->Hrm_hop_dong_model->where('id_hop_dong', $id)->update($data);

        $nhanvienOld = $this->Hrm_nhan_vien_model->find($hopdong['id_nhan_vien']);
        $hopdong['nhan_vien'] = $nhanvienOld;

        $hopdongNew = $this->Hrm_hop_dong_model->find($id);
        $nhanvienNew = $this->Hrm_nhan_vien_model->find($hopdong['id_nhan_vien']);
        $hopdongNew['nhan_vien'] = $nhanvienNew;

        //create log
        $this->createLog('update', 'Cập nhật hợp đồng', $hopdong, $hopdongNew, 'hrm_hop_dong');
        $this->db->trans_commit();
        resSuccess($hopdong, 'Cập nhật thành công', REST_INSTANCE_Controller::HTTP_OK);
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
        // foreach ($hopdong as $hd) {
        //     $this->fileupload->delete($hd['file_hop_dong_duong_dan']);
        // }
        // $this->Hrm_hop_dong_model->whereIn('id_hop_dong', $ids)->delete(); // code cũ
        $this->createLog('delete', 'Xóa hợp đồng', $hopdong,  null, 'hrm_hop_dong');
        $this->db->trans_commit();
        resSuccess(null, 'Xóa thành công');
    }

    public function show_get($id)
    {
        $vb = $this->Hrm_hop_dong_model
            ->select('
                hrm_hop_dong.*,
                hrm_nhan_vien.ho_va_ten,
                hrm_nhan_vien.ma_nhan_vien,
            ')
            ->join('hrm_nhan_vien', 'hrm_nhan_vien.id_nhan_vien = hrm_hop_dong.id_nhan_vien')
            ->where('id_hop_dong', $id)
            ->first();

        if (!$vb) resError('Không tìm thấy văn bản', REST_Controller::HTTP_NOT_FOUND);

        $vb['phu_cap'] = $this->Hrm_hop_dong_model
            ->select('hrm_phu_cap.ten_phu_cap, hrm_phu_cap.id_phu_cap')
            ->leftJoin('hrm_phu_cap_nhan_vien', 'hrm_phu_cap_nhan_vien.id_nhan_vien = hrm_hop_dong.id_nhan_vien')
            ->leftJoin('hrm_phu_cap', 'hrm_phu_cap.id_phu_cap = hrm_phu_cap_nhan_vien.id_phu_cap')
            ->where('id_hop_dong', $id)
            ->get();

        resSuccess($vb);
    }
}
