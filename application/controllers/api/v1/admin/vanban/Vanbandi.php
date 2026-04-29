<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property E_van_ban_model $E_van_ban_model
 * @property E_vb_co_quan_model $E_vb_co_quan_model
 * @property E_vb_khoi_co_quan_model $E_vb_khoi_co_quan_model
 * @property E_vb_nguoi_dung_model $E_vb_nguoi_dung_model
 * @property E_ban_hanh_model $E_ban_hanh_model
 * @property E_file_dinh_kem_model $E_file_dinh_kem_model
 * @property E_loai_model $E_loai_model
 * @property E_don_vi_model $E_don_vi_model
 * @property E_hinh_thuc_model $E_hinh_thuc_model
 * @property E_vb_hinh_thuc_model $E_vb_hinh_thuc_model
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 * @property E_tinh_chat_model $E_tinh_chat_model
 * @property E_bao_mat_model $E_bao_mat_model
 * @property E_don_vi_xu_ly_model $E_don_vi_xu_ly_model
 * @property E_xu_ly_model $E_xu_ly_model
 * @property E_co_quan_model $E_co_quan_model
 * @property E_khoi_co_quan_model $E_khoi_co_quan_model
 * @property Ql_thong_bao_model $Ql_thong_bao_model
 * @property E_bao_cao_model $E_bao_cao_model
 * @property E_don_vi_xu_ly_da_xem_model $E_don_vi_xu_ly_da_xem_model
 * @property Ql_nhat_ky_model $Ql_nhat_ky_model
 * @property Fileupload $fileupload
 * @property Common $common
 * @property Pxl $pxl
 * @property CI_Upload $upload
 * @property E_tag_model $E_tag_model
 * @property email $email
 */



class Vanbandi extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model([
            'E_van_ban_model',
            'E_file_dinh_kem_model',
            'E_vb_co_quan_model',
            'E_vb_khoi_co_quan_model',
            'E_ban_hanh_model',
            'E_loai_model',
            'E_don_vi_model',
            'E_hinh_thuc_model',
            'Ql_nguoi_dung_model',
            'E_tinh_chat_model',
            'E_bao_mat_model',
            'E_don_vi_xu_ly_model',
            'E_xu_ly_model',
            'E_co_quan_model',
            'Ql_thong_bao_model',
            'E_tag_model',
            'E_khoi_co_quan_model',
            'E_vb_hinh_thuc_model',
            'E_vb_nguoi_dung_model',
            'E_bao_cao_model',
            'E_don_vi_xu_ly_da_xem_model',
            'Ql_nhat_ky_model'
        ]);
        $this->load->library(['Validator', 'Fileupload', 'Common', 'Pxl', 'upload']);
    }

    public function index_get()
    {
        $auth  = $this->getUserLogin();

        $id_loai_selected = commonRequest('id_loai_selected') ? commonRequest('id_loai_selected') : null;
        $current_year_vbdi = commonRequest('current_year_vbdi') ? commonRequest('current_year_vbdi') : null;
        if ($id_loai_selected) {
            $result = $this->soHieuVanBan($auth, 2, $id_loai_selected, $current_year_vbdi);
            resSuccess($result['sohieuvanban_last'], 'Lấy số hiệu văn bản mới nhất', 200, true, $result['options']);
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

        $data = $this->E_van_ban_model->getAllVanbandi($data['start'], $data['length'], $data['searchValue'], $data['order'], $data['columns'], $data['searchKey'],  $data['fromDate'], $data['toDate'], $auth);
        $tags = $this->E_tag_model->get_user_tags($auth['ql_nguoi_dung_id']);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
            'tags' => $tags,
            // 'sql' => $data['sql'],
        ]);
    }

    public function create_post()
    {
        $auth  = $this->getUserLogin();
        $this->load->library(['Validator', 'Fileupload']);

        $ten_don_vi_soan_add = commonRequest('ten_don_vi_soan_add') ? commonRequest('ten_don_vi_soan_add') : null;
        if ($ten_don_vi_soan_add) {
            $donvi = $this->E_don_vi_model->create([
                'ten_don_vi' => $ten_don_vi_soan_add
            ]);
            resSuccess($donvi, 'Thêm thành công');
        }

        $ten_loai_add = commonRequest('ten_loai_add') ? commonRequest('ten_loai_add') : null;
        $hau_to_loai_add = commonRequest('hau_to_loai_add') ? commonRequest('hau_to_loai_add') : null;
        if ($ten_loai_add && $hau_to_loai_add) {
            $loai = $this->E_loai_model->create([
                'ten_loai' => $ten_loai_add,
                'hau_to' => $hau_to_loai_add
            ]);
            resSuccess($loai, 'Thêm thành công');
        }

        $soHieuVB = commonRequest('so_hieu_van_ban') ? commonRequest('so_hieu_van_ban') : null;
        if ($soHieuVB) {
            $current_year_vbdi = commonRequest('current_year_vbdi') ? commonRequest('current_year_vbdi') : null;

            $existSoHieu = $this->E_van_ban_model
                ->where('loai_van_ban', 2)
                ->where('so_hieu_van_ban', $soHieuVB)
                ->where('YEAR(ngay_ky)', $current_year_vbdi)
                ->where('id_don_vi', $auth['id_don_vi'])
                ->get();
            if ($existSoHieu) {
                resError('Trùng lập số hiệu văn bản!');
            }
        }

        $ids_khoi_co_quan = commonRequest('ids_khoi_co_quan') ? commonRequest('ids_khoi_co_quan') : null;
        $ids_co_quan = commonRequest('ids_co_quan') ? commonRequest('ids_co_quan') : null;
        $ids_don_vi_xu_ly = commonRequest('ids_don_vi_xu_ly') ? commonRequest('ids_don_vi_xu_ly') : null;
        $ids_ql_nguoi_dung = commonRequest('ids_ql_nguoi_dung') ? commonRequest('ids_ql_nguoi_dung') : null;
        $ids_hinh_thuc = commonRequest('ids_hinh_thuc') ? implode(',', commonRequest('ids_hinh_thuc')) : null;
        $trang_thai_luu_tru = commonRequest('trang_thai') ? commonRequest('trang_thai') : null;
        $is_public = commonRequest('is_public');

        $data = [
            'loai_van_ban' => 2,
            'trang_thai' => $this->common::STATUS_VAN_BAN_DI['DA_BAN_HANH']['value'],
            'id_loai' => commonRequest('id_loai') ? commonRequest('id_loai') : null,
            'trich_yeu' => commonRequest('trich_yeu') ? commonRequest('trich_yeu') : null,
            'nguoi_ky' => commonRequest('nguoi_ky') ? commonRequest('nguoi_ky') : null,
            'ngay_ky' => commonRequest('ngay_ky') ? commonRequest('ngay_ky') : null,
            'tra_loi_cv_den' => commonRequest('tra_loi_cv_den') ? commonRequest('tra_loi_cv_den') : null,
            'ngay_tra_loi_cv_den' => commonRequest('ngay_tra_loi_cv_den') ? commonRequest('ngay_tra_loi_cv_den') : null,
            'so_van_ban' => commonRequest('so_van_ban') ? commonRequest('so_van_ban') : null,
            'so_hieu_van_ban' => commonRequest('so_hieu_van_ban') ? commonRequest('so_hieu_van_ban') : null,

            'id_don_vi_soan' => commonRequest('id_don_vi_soan') ? commonRequest('id_don_vi_soan') : null,
            // 'id_hinh_thuc' => commonRequest('id_hinh_thuc') ? commonRequest('id_hinh_thuc') : null,
            'id_nguoi_tao' => $this->getUserLogin()['ql_nguoi_dung_id'],
            'nguoi_soan_vb_di' => commonRequest('nguoi_soan_vb_di') ? commonRequest('nguoi_soan_vb_di') : null,
            'linh_vuc' => commonRequest('linh_vuc') ? commonRequest('linh_vuc') : null,
            'id_don_vi' => commonRequest('id_don_vi') ? commonRequest('id_don_vi') : null,
            'noi_luu_tru' => commonRequest('noi_luu_tru') ? commonRequest('noi_luu_tru') : null,
            'id_tinh_chat' => commonRequest('id_tinh_chat') ? commonRequest('id_tinh_chat') : null,
            'id_bao_mat' => commonRequest('id_bao_mat') ? commonRequest('id_bao_mat') : null,
            'ten_van_ban' => commonRequest('ten_van_ban') ? commonRequest('ten_van_ban') : null,
            'ngay_nhan' => commonRequest('ngay_ky') ? commonRequest('ngay_ky') : null,
            'ngay_ban_hanh' => commonRequest('ngay_ky') ? commonRequest('ngay_ky') : null,
            'thoi_gian_xu_ly' => commonRequest('thoi_gian_xu_ly') ? commonRequest('thoi_gian_xu_ly') : null,
            'id_khoi_co_quan' => null,
            'id_co_quan' => null,
            'id_nguoi_sua' => $this->getUserLogin()['ql_nguoi_dung_id'],
            'trang_thai_huy_vb' => 0, //0: không xóa
            'luu_tru_noi_bo' => commonRequest('luu_tru_noi_bo') ? commonRequest('luu_tru_noi_bo') : null,
            'van_ban_chi_doc' => commonRequest('van_ban_chi_doc') ? 1 : 0,
            'ghi_chu' => commonRequest('ghi_chu') ? commonRequest('ghi_chu') : null,
            'ids_hinh_thuc[]' => commonRequest('ids_hinh_thuc') ? implode(',', commonRequest('ids_hinh_thuc')) : null
        ];

        $rules = [
            'id_loai' => 'required|integer',
            'trich_yeu' => 'required',
            'thoi_gian_xu_ly' => 'date',
            'id_co_quan' => 'integer',
            // 'id_hinh_thuc' => 'required|integer',
            'id_tinh_chat' => 'required|integer',
            'id_bao_mat' => 'required|integer',
            'id_don_vi' => 'integer',
            'luu_tru_noi_bo' => 'integer',
            'ngay_ky' => 'required|date',
            'nguoi_ky' => 'required',
            'ids_hinh_thuc[]' => 'required',
        ];

        $customMessages = [
            'ten_van_ban.required' => 'Tên văn bản bắt buộc nhập',
            'so_van_ban.unique' => 'Số văn bản đã tồn tại',
            'id_loai.required' => 'Loại văn bản bắt buộc',
            'trich_yeu.required' => 'Trích yếu văn bản bắt buộc nhập',
            'ngay_nhan.required' => 'Ngày nhận bắt buộc nhập',
            'ngay_nhan.date' => 'Ngày nhận phải đúng định dạng ngày',
            'ngay_ban_hanh.required' => 'Ngày ban hành bắt buộc nhập',
            'ngay_ban_hanh.date' => 'Ngày ban hành phải đúng định dạng ngày',
            'trang_thai.required' => 'Trạng thái văn bản bắt buộc',
            'thoi_gian_xu_ly.date' => 'Thời gian xử lý phải đúng định dạng ngày',
            'id_khoi_co_quan.required' => 'Khoi cơ quan văn bản bắt buộc',
            // 'id_hinh_thuc.required' => 'Hình thức gửi bắt buộc',
            'id_tinh_chat.required' => 'Mức độ tính chất bắt buộc',
            'id_bao_mat.required' => 'Mức độ bảo mật bắt buộc',
            'ngay_ky.date' => 'Ngày ký phải đúng định dạng ngày',
            'ngay_ky.required' => 'Ngày ký bắt buộc',
            'nguoi_ky.required' => 'Người ký bắt buộc',
            'ids_hinh_thuc[].required' => 'Hình thức bắt buộc',
        ];

        if ($trang_thai_luu_tru == 'LUU_TRU') {
            $rules = [];
        }

        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();
        unset($data['ids_hinh_thuc[]']);
        //Lưu văn bản đến
        $vb = $this->E_van_ban_model->create($data);

        $sendMail = false;
        $sendZalo = false;
        $textHinhThuc = '';

        if ($ids_hinh_thuc) {
            $ids_hinh_thuc_array = explode(',', $ids_hinh_thuc);
            foreach ($ids_hinh_thuc_array as $id_hinh_thuc) {
                $hinhthuc = $this->E_hinh_thuc_model->find($id_hinh_thuc);

                // $textHinhThuc .= $hinhthuc['ten_hinh_thuc'];
                // if ($id_hinh_thuc !== end($ids_hinh_thuc_array)) {
                //     $textHinhThuc .= '/';
                // }

                if ($hinhthuc['ma_hinh_thuc'] == 'ZALO') $sendZalo = true;
                if ($hinhthuc['ma_hinh_thuc'] == 'EMAIL') {
                    $textHinhThuc = '/' . $hinhthuc['ten_hinh_thuc'];
                    $sendMail = true;
                }

                $this->E_vb_hinh_thuc_model->create([
                    'id_van_ban' => $vb['id_van_ban'],
                    'id_hinh_thuc' => $id_hinh_thuc,
                ]);
            }
        }

        if ($ids_khoi_co_quan) {
            $ids_khoi_co_quan_array = explode(',', $ids_khoi_co_quan);

            foreach ($ids_khoi_co_quan_array as $id) {
                $this->E_vb_khoi_co_quan_model->create([
                    'id_van_ban' => $vb['id_van_ban'],
                    'id_khoi_co_quan' => $id,
                ]);
            }
        }

        if ($ids_co_quan) {
            $ids_co_quan_array = explode(',', $ids_co_quan);

            foreach ($ids_co_quan_array as $id) {
                $this->E_vb_co_quan_model->create([
                    'id_van_ban' => $vb['id_van_ban'],
                    'id_co_quan' => $id,
                ]);
            }
        }

        $ids_don_vi_xu_ly_array = [];
        if ($ids_don_vi_xu_ly) {
            $this->E_van_ban_model
                ->where('id_van_ban', $vb['id_van_ban'])
                ->update([
                    'trang_thai' => Common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value']
                ]);

            $xuly = $this->E_xu_ly_model->insert([
                'id_van_ban' => $vb['id_van_ban'],
                'nguoi_tao' => $this->getUserLogin()['ql_nguoi_dung_id'],
            ]);

            // dùng cho createNotification
            $ids_don_vi_xu_ly_array = explode(',', $ids_don_vi_xu_ly);
            $ids_don_vi_xu_ly_array = array_unique($ids_don_vi_xu_ly_array);

            // foreach ($ids_don_vi_xu_ly_array as $id) {
            //     $this->E_don_vi_xu_ly_model->insert([
            //         'id_don_vi' => $id,
            //         'id_xu_ly' => $xuly,
            //         'nguoi_tao' => $this->getUserLogin()['ql_nguoi_dung_id'],
            //     ]);
            // }
        }

        $ids_ql_nguoi_dung_array = [];
        if ($ids_ql_nguoi_dung) {
            $this->E_van_ban_model
                ->where('id_van_ban', $vb['id_van_ban'])
                ->update([
                    'trang_thai' => Common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value']
                ]);

            $ids_ql_nguoi_dung_array = explode(',', $ids_ql_nguoi_dung);
            $ids_ql_nguoi_dung_array = array_unique($ids_ql_nguoi_dung_array);

            $xulyId = "";
            $xuly = $this->E_xu_ly_model
                ->where('id_van_ban', $vb['id_van_ban'])
                ->first();
            if ($xuly) {
                $xulyId = $xuly['id_xu_ly'];
            } else {
                $xulyId = $this->E_xu_ly_model->insert([
                    'id_van_ban' => $vb['id_van_ban'],
                    'nguoi_tao' => $this->getUserLogin()['ql_nguoi_dung_id'],
                ]);
            }

            foreach ($ids_ql_nguoi_dung_array as $id) {
                $nguoidung = $this->Ql_nguoi_dung_model->find($id);

                $this->E_don_vi_xu_ly_model->insert([
                    'id_don_vi' => $nguoidung['id_don_vi'],
                    'id_xu_ly' => $xulyId,
                    'nguoi_tao' => $this->getUserLogin()['ql_nguoi_dung_id'],
                    'id_nguoi_xu_ly' => $id,
                ]);
            }
        }

        //Upload file đính kèm
        $folderName = 'documents';
        $uploadedFiles = [];
        if (isset($_FILES['file_dinh_kem_vbdi'])) {
            $files = $_FILES['file_dinh_kem_vbdi']; // Tên input từ form                        

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

            $uploadedFull = true;
            foreach ($uploadedFiles as $uploadedFile) {
                if ($uploadedFile['success']) {
                    $this->E_file_dinh_kem_model->create([
                        'id_van_ban' => $vb['id_van_ban'],
                        'ten_file_goc' => $uploadedFile['file_name'],
                        'dung_luong' => exchangeFromKbToLargerCapacity($uploadedFile['file_size']), //đổi dung lượng lớn hơn MB từ KB
                        'duong_dan' => $uploadedFile['file_path'],
                        'loai_file' => $uploadedFile['file_extension'],
                        'la_file_ban_hanh' => 1,
                        'is_public' => $is_public
                    ]);
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
                resError('Có lỗi xảy ra khi upload file ban hành');
            }
        }

        // File nội bộ
        $uploadedFilesTCHC = [];
        if (isset($_FILES['file_dinh_kem_vbdi_tchc_noibo'])) {
            $files = $_FILES['file_dinh_kem_vbdi_tchc_noibo']; // Tên input từ form                        

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
                $uploadedFilesTCHC[] = $result;
            }

            $uploadedFullTCHC = true;
            foreach ($uploadedFilesTCHC as $item) {
                if ($item['success']) {
                    $e_file_dinh_kem = $this->E_file_dinh_kem_model->create([
                        'id_van_ban' => $vb['id_van_ban'],
                        'ten_file_goc' => $item['file_name'],
                        'dung_luong' => exchangeFromKbToLargerCapacity($item['file_size']), //đổi dung lượng lớn hơn MB từ KB
                        'duong_dan' => $item['file_path'],
                        'loai_file' => $item['file_extension'],
                        'la_file_ban_hanh' => 0,
                        'is_public' => $is_public
                    ]);
                } else {
                    $uploadedFullTCHC = false;
                    break;
                }
            }

            if (!$uploadedFullTCHC) {
                foreach ($uploadedFilesTCHC as $up) {
                    if ($up['success']) {
                        $delete = $this->fileupload->delete($up['file_path']);
                    }
                }
                $this->db->trans_rollback();
                resError('Có lỗi xảy ra khi upload file nội bộ');
            }
        }

        $textZalo = '';
        if ($trang_thai_luu_tru == 'LUU_TRU') {
            $this->E_van_ban_model
                ->where('id_van_ban', $vb['id_van_ban'])
                ->update([
                    'trang_thai' => Common::STATUS_VAN_BAN_DI['LUU_TRU']['value']
                ]);
        } else {
            $this->createNotification($auth, $vb['id_van_ban'], $ids_don_vi_xu_ly_array, $ids_ql_nguoi_dung_array, false, $sendZalo);
            send_zalo_oa_message($vb['id_van_ban']);

            $textZalo = $this->copyZaloMessage($vb['id_van_ban'], $textHinhThuc);
        }

        $this->createLog('Create', 'Thêm văn bản đi: ' . $vb['so_hieu_van_ban'], NULL, $vb, 'e_van_ban');

        $this->db->trans_commit();

        // Trích xuất nội dung file
        call_n8n_trich_xuat($vb['id_van_ban']);

        resSuccess($vb, 'Thêm văn bản đi thành công', 200, true, [
            'noi_dung_tin_nhan_zalo' => $textZalo,
            'send_mail' => $sendMail
        ]);
    }

    public function sendMailVBDi_get($id)
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Không tìm thấy người dùng');
        }

        $vanban = $this->E_van_ban_model->find($id);
        if (!$vanban) {
            resError('Không tìm thấy văn bản!');
        }

        $sendMail = false;
        $dsHinhThuc = $this->E_vb_hinh_thuc_model
            ->select('ma_hinh_thuc')
            ->leftJoin('e_hinh_thuc', 'e_vb_hinh_thuc.id_hinh_thuc = e_hinh_thuc.id_hinh_thuc')
            ->where('e_vb_hinh_thuc.id_van_ban', $id)
            ->get();
        foreach ($dsHinhThuc as $ht) {
            if ($ht['ma_hinh_thuc'] == 'EMAIL') {
                $sendMail = true;
                break;
            }
        }

        if ($sendMail) {
            $idsNguoiNhanArray = $this->db
                ->select('id_nguoi_xu_ly')
                ->from('e_xu_ly')
                ->join('e_don_vi_xu_ly', 'e_xu_ly.id_xu_ly = e_don_vi_xu_ly.id_xu_ly')
                ->where('e_xu_ly.id_van_ban', $id)
                ->get()
                ->result_array();

            $idsNguoiNhan = array_column($idsNguoiNhanArray, 'id_nguoi_xu_ly');

            if (empty($idsNguoiNhan)) {
                resError('Không tìm thấy người nhận mail');
            }

            $file_dinh_kem = $this->E_file_dinh_kem_model
                ->where('id_van_ban', $id)
                ->get();

            $loaiVanBan = '';
            $loaiVB = $this->E_loai_model->find($vanban['id_loai']);
            if ($loaiVB['hau_to'] == 'QĐ-CTHĐT-ĐHNCT') {
                $loaiVanBan = 'Quyết định';
            } else {
                $loaiVanBan = $loaiVB['ten_loai'];
            }

            $soHieuVBTuyChinh = '';
            if ($vanban['loai_van_ban'] == 1) {
                $soHieuVBTuyChinh = $vanban['so_hieu_van_ban'] . ' ';
            } else if ($vanban['loai_van_ban'] == 2) {
                $soHieuVBTuyChinh = '';
            }

            $idsNguoiNhan = array_unique($idsNguoiNhan);
            $dsNguoiDung = $this->Ql_nguoi_dung_model->whereIn('ql_nguoi_dung_id', $idsNguoiNhan)->get();
            $ql_nguoi_dung_emails = array_column($dsNguoiDung, 'ql_nguoi_dung_email');
            $first_email = $ql_nguoi_dung_emails[0];
            $cc = array_column($dsNguoiDung, 'ql_nguoi_dung_email');
            $bcc = array_column($dsNguoiDung, 'ql_nguoi_dung_email');

            $this->sendVBDi_post([
                // 'ql_nguoi_dung_ho_ten' => $nguoidung['ql_nguoi_dung_ho_ten'],
                'so_hieu_van_ban'      => $vanban['so_hieu_van_ban'],
                'ngay_ky'              => date('d/m/Y', strtotime($vanban['ngay_ky'])),
                'trich_yeu'            => $loaiVanBan . " số " . $soHieuVBTuyChinh . $vanban['trich_yeu'],
                // 'ten_don_vi_ban_hanh'  => $donvibanhanh['ten_don_vi'],
                'duong_dan'            => $this->config->item('frontend_url') . 'vanban/vanbandendonvi/xemchitiet/' . $vanban['id_van_ban'],
                // 'email'                => $ql_nguoi_dung_emails,
                'email'                => $first_email,
                'file_dinh_kem'        => $file_dinh_kem,
                'cc'                   => $cc,
                // 'bcc'                  => $bcc,
                'bcc'                  => [],
            ]);
            // foreach ($idsNguoiNhan as $uId) {
            //     $nguoidung = $this->Ql_nguoi_dung_model->find($uId);
            //     $donvibanhanh = $this->E_don_vi_model->find($vanban['id_don_vi_soan']);

            //     $this->sendVBDi_post([
            //         // 'ql_nguoi_dung_ho_ten' => $nguoidung['ql_nguoi_dung_ho_ten'],
            //         'so_hieu_van_ban'      => $vanban['so_hieu_van_ban'],
            //         'ngay_ky'              => date('d/m/Y', strtotime($vanban['ngay_ky'])),
            //         'trich_yeu'            => $loaiVanBan . " số " . $soHieuVBTuyChinh . $vanban['trich_yeu'],
            //         // 'ten_don_vi_ban_hanh'  => $donvibanhanh['ten_don_vi'],
            //         'duong_dan'            => $this->config->item('frontend_url') . 'vanban/vanbandendonvi/xemchitiet/' . $vanban['id_van_ban'],
            //         'email'                => $nguoidung['ql_nguoi_dung_email'],
            //         'file_dinh_kem'        => $file_dinh_kem,
            //         'cc'                   => $cc,
            //         'bcc'                  => $bcc,
            //     ]);
            // }
        }
        resSuccess([
            'sendMail' => $sendMail,
            'so_hieu_van_ban' => $vanban['so_hieu_van_ban'],
        ], $sendMail ? 'Gửi mail văn bản ' . $vanban['so_hieu_van_ban'] . ' thành công' : 'Văn bản không gửi mail');
    }

    public function show_get($id)
    {
        $auth = $this->getUserLogin();

        // Check đã xem các thông báo liên quan tới văn bản
        $linkPartern = 'xemchitiet/' . $id;
        $dsThongbao = $this->db
            ->select('*')
            ->from('ql_thong_bao')
            ->like('ql_thong_bao_link', $linkPartern, 'before')
            ->get()
            ->result_array();

        if (!empty($dsThongbao)) {
            foreach ($dsThongbao as $item) {
                $thongBaoNguoiDung = $this->db
                    ->select("*")
                    ->from('ql_thong_bao_nguoi_dung')
                    ->where('ql_thong_bao_id', $item['ql_thong_bao_id'])
                    ->where('ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])
                    ->get()
                    ->row_array();

                if ($thongBaoNguoiDung && $thongBaoNguoiDung['ql_thong_bao_da_doc'] == 0) {
                    $this->db
                        ->where('ql_thong_bao_nguoi_dung_id', $thongBaoNguoiDung['ql_thong_bao_nguoi_dung_id'])
                        ->update('ql_thong_bao_nguoi_dung', [
                            'ql_thong_bao_da_doc' => 1,
                            'updated_at' => date('Y-m-d H:i:s'),
                            'updated_user_id' => $auth['ql_nguoi_dung_id']
                        ]);
                }
            }
        }

        $vb = $this->E_van_ban_model->detail_vanbandi($id, $auth);

        resSuccess($vb['data'], 'Lấy thông tin thành công');

        $vb = $this->E_van_ban_model
            ->join('e_loai', 'e_loai.id_loai = e_van_ban.id_loai', 'left')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
            ->join('e_hinh_thuc', 'e_hinh_thuc.id_hinh_thuc = e_van_ban.id_hinh_thuc', 'left')
            ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
            ->join('e_bao_mat', 'e_bao_mat.id_bao_mat = e_van_ban.id_bao_mat', 'left')
            ->where('e_van_ban.id_van_ban', $id)
            ->first();

        if (!$vb) resError('Không tìm thấy văn bản', REST_Controller::HTTP_NOT_FOUND);
        $vb['files'] = $this->E_file_dinh_kem_model
            ->where('id_van_ban', $id)
            ->where('la_file_ban_hanh', 1)
            ->get();
        if ($vb['files']) {
            foreach ($vb['files'] as &$file) {
                $file['duong_dan'] = encryptString($file['duong_dan']);
            }
            unset($file);
        }

        $vb['files_tchc'] = $this->E_file_dinh_kem_model
            ->where('id_van_ban', $id)
            ->where('la_file_ban_hanh', 0)
            ->get();
        if ($vb['files_tchc']) {
            foreach ($vb['files_tchc'] as &$file) {
                $file['duong_dan'] = encryptString($file['duong_dan']);
            }
            unset($file);
        }

        $vb['e_vb_khoi_co_quan'] = $this->E_vb_khoi_co_quan_model
            ->select('e_vb_khoi_co_quan.*, e_khoi_co_quan.ten_khoi_co_quan')
            ->leftJoin('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_vb_khoi_co_quan.id_khoi_co_quan')
            ->where('id_van_ban', $id)
            ->get();

        $vb['e_vb_co_quan'] = $this->E_vb_co_quan_model
            ->select('e_vb_co_quan.*, e_co_quan.ten_co_quan')
            ->leftJoin('e_co_quan', 'e_co_quan.id_co_quan = e_vb_co_quan.id_co_quan')
            ->where('id_van_ban', $id)
            ->get();

        // $vb['e_ban_hanh'] = $this->E_ban_hanh_model
        //     ->select('e_ban_hanh.*, e_don_vi.ten_don_vi')
        //     ->leftJoin('e_don_vi', 'e_don_vi.id_don_vi = e_ban_hanh.id_don_vi')
        //     ->where('id_van_ban', $id)
        //     ->get();

        $vb['e_don_vi_xu_ly'] = $this->E_xu_ly_model
            ->select('e_don_vi_xu_ly.*, e_don_vi.ten_don_vi')
            ->leftJoin('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly')
            ->leftJoin('e_don_vi', 'e_don_vi.id_don_vi = e_don_vi_xu_ly.id_don_vi')
            ->where('e_xu_ly.id_van_ban', $id)
            ->where('e_don_vi_xu_ly.id_nguoi_xu_ly IS NULL')
            ->get();

        $e_nguoi_xu_ly = $this->E_xu_ly_model
            ->select('e_don_vi_xu_ly.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten, e_don_vi.ten_don_vi')
            ->leftJoin('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly')
            ->leftJoin('e_don_vi', 'e_don_vi.id_don_vi = e_don_vi_xu_ly.id_don_vi')
            ->leftJoin('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_don_vi_xu_ly.id_nguoi_xu_ly')
            ->where('e_xu_ly.id_van_ban', $id)
            ->where('e_don_vi_xu_ly.id_nguoi_xu_ly IS NOT NULL')
            ->get();
        $vb['e_nguoi_xu_ly'] = $e_nguoi_xu_ly ?? [];


        $vb['e_vb_hinh_thuc'] = $this->E_vb_hinh_thuc_model
            ->select('e_vb_hinh_thuc.*, e_hinh_thuc.ten_hinh_thuc')
            ->leftJoin('e_hinh_thuc', 'e_hinh_thuc.id_hinh_thuc = e_vb_hinh_thuc.id_hinh_thuc')
            ->where('id_van_ban', $id)
            ->get();

        $vb['color_bao_mat'] = ($this->E_bao_mat_model->find($vb['id_bao_mat']))['class_color'];
        $vb['color_tinh_chat'] = ($this->E_tinh_chat_model->find($vb['id_tinh_chat']))['class_color'];
        $vb['ten_don_vi_soan'] = $this->E_don_vi_model->find($vb['id_don_vi_soan'])['ten_don_vi'];
        $vb['ho_so_don_vi'] = $this->E_don_vi_model->find($vb['id_don_vi'])['ten_don_vi'];
        $vb['hinh_thuc_gui_vb'] = '';

        $ds_hinh_thuc = $this->E_vb_hinh_thuc_model
            ->select('ten_hinh_thuc')
            ->leftJoin('e_hinh_thuc', 'e_vb_hinh_thuc.id_hinh_thuc = e_hinh_thuc.id_hinh_thuc')
            ->where('e_vb_hinh_thuc.id_van_ban', $id)
            ->get();
        if ($ds_hinh_thuc) {
            $arr_hinh_thuc = [];
            foreach ($ds_hinh_thuc as $ht) {
                $arr_hinh_thuc[] = $ht['ten_hinh_thuc'];
            }

            $vb['hinh_thuc_gui_vb'] = implode(', ', $arr_hinh_thuc);
        }

        $phanhoivanban = $this->db
            ->select('
                e_bao_cao.*,
                DATE_FORMAT(ngay_tao, "%H:%i:%s - %d/%m/%Y") AS ngay_tao,
                e_don_vi.ten_don_vi
            ')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = e_bao_cao.id_don_vi_phan_hoi', 'left')
            ->where('id_van_ban', $id)
            ->order_by('e_bao_cao.ngay_tao', 'ASC')
            ->get('e_bao_cao')->result_array();

        foreach ($phanhoivanban as $key => &$phanhoi) {
            $files_phanhoi  = json_decode($phanhoi['files_dinh_kem'], true);
            if (json_last_error() === JSON_ERROR_NONE && !empty($files_phanhoi)) {
                foreach ($files_phanhoi as &$fph) {
                    $fph['file_path'] = encryptString($fph['file_path']);
                }
                $phanhoi['files_dinh_kem'] = $files_phanhoi;
            } else {
                $phanhoi['files_dinh_kem'] = [];
            }

            $phanhoi['send'] = false;
            if ($auth['id_don_vi'] == $phanhoi['id_don_vi_phan_hoi']) {
                $phanhoi['send'] = true;
            }
            $phanhoi['files_dinh_kem'] = $files_phanhoi;
        }

        $vb['phan_hoi'] = $phanhoivanban ?? [];

        resSuccess($vb);
    }

    public function checkExitsUpdate($id, $field, $value, $table = 'e_van_ban', $loai, $auth)
    {
        $tempVB = $this->E_van_ban_model->find($id);
        list($year, $month, $date) = explode("-", $tempVB['ngay_ky']);

        $vbdi = $this->E_van_ban_model
            ->where('loai_van_ban', $loai)
            ->where('YEAR(ngay_ky)', $year)
            ->where('id_don_vi', $auth['id_don_vi'])
            ->get();

        foreach ($vbdi as $item) {
            if ($item['id_van_ban'] == $id) {
                continue;
            } else if (($item['id_van_ban'] != $id) && ($item[$field] == $value)) {
                return true;
            }
        }
        return false;
    }

    public function update_post($id)
    {
        $auth  = $this->getUserLogin();

        $vb = $this->E_van_ban_model->find($id);
        $this->load->library(['Validator', 'Fileupload']);

        $trangThai = commonRequest('trang_thai') ? commonRequest('trang_thai') : null;

        $ids_khoi_co_quan = commonRequest('ids_khoi_co_quan') ? commonRequest('ids_khoi_co_quan') : null;
        $ids_co_quan = commonRequest('ids_co_quan') ? commonRequest('ids_co_quan') : null;
        $ids_don_vi_xu_ly = commonRequest('ids_don_vi_xu_ly') ? commonRequest('ids_don_vi_xu_ly') : null;
        $ids_hinh_thuc = commonRequest('ids_hinh_thuc') ? commonRequest('ids_hinh_thuc') : null;
        $ids_ql_nguoi_dung = commonRequest('ids_ql_nguoi_dung') ? commonRequest('ids_ql_nguoi_dung') : null;
        $is_public = commonRequest('is_public') ?? 1;

        $data = [
            'loai_van_ban' => 2,
            'id_loai' => commonRequest('id_loai') ? commonRequest('id_loai') : null,
            'trich_yeu' => commonRequest('trich_yeu') ? commonRequest('trich_yeu') : null,
            'nguoi_ky' => commonRequest('nguoi_ky') ? commonRequest('nguoi_ky') : null,
            'ngay_ky' => commonRequest('ngay_ky') ? commonRequest('ngay_ky') : null,
            'tra_loi_cv_den' => commonRequest('tra_loi_cv_den') ? commonRequest('tra_loi_cv_den') : null,
            'ngay_tra_loi_cv_den' => commonRequest('ngay_tra_loi_cv_den') ? commonRequest('ngay_tra_loi_cv_den') : null,
            'so_van_ban' => commonRequest('so_van_ban') ? commonRequest('so_van_ban') : null,
            'so_hieu_van_ban' => commonRequest('so_hieu_van_ban') ? commonRequest('so_hieu_van_ban') : null,
            'id_don_vi_soan' => commonRequest('id_don_vi_soan') ? commonRequest('id_don_vi_soan') : null,
            'id_hinh_thuc' => commonRequest('id_hinh_thuc') ? commonRequest('id_hinh_thuc') : null,
            // 'id_nguoi_tao' => $this->getUserLogin()['ql_nguoi_dung_id'],
            'nguoi_soan_vb_di' => commonRequest('nguoi_soan_vb_di') ? commonRequest('nguoi_soan_vb_di') : null,
            'linh_vuc' => commonRequest('linh_vuc') ? commonRequest('linh_vuc') : null,
            'id_don_vi' => commonRequest('id_don_vi') ? commonRequest('id_don_vi') : null,
            'noi_luu_tru' => commonRequest('noi_luu_tru') ? commonRequest('noi_luu_tru') : null,
            'id_tinh_chat' => commonRequest('id_tinh_chat') ? commonRequest('id_tinh_chat') : null,
            'id_bao_mat' => commonRequest('id_bao_mat') ? commonRequest('id_bao_mat') : null,
            'ten_van_ban' => commonRequest('ten_van_ban') ? commonRequest('ten_van_ban') : null,
            'ngay_nhan' => commonRequest('ngay_nhan') ? commonRequest('ngay_nhan') : null,
            'ngay_ban_hanh' => commonRequest('ngay_ky') ? commonRequest('ngay_ky') : null,
            'thoi_gian_xu_ly' => commonRequest('thoi_gian_xu_ly') ? commonRequest('thoi_gian_xu_ly') : null,
            'id_khoi_co_quan' => null,
            'id_co_quan' => null,
            'id_nguoi_sua' => $this->getUserLogin()['ql_nguoi_dung_id'],
            'trang_thai_huy_vb' => 0, //0: không xóa
            'luu_tru_noi_bo' => commonRequest('luu_tru_noi_bo') ? commonRequest('luu_tru_noi_bo') : null,
            'van_ban_chi_doc' => commonRequest('van_ban_chi_doc') ? 1 : 0,
            'ghi_chu' => commonRequest('ghi_chu') ? commonRequest('ghi_chu') : null,
        ];

        $rules = [
            'id_loai' => 'required|integer',
            'trich_yeu' => 'required',
            'thoi_gian_xu_ly' => 'date',
            'id_co_quan' => 'integer',
            // 'id_hinh_thuc' => 'required|integer',
            'id_tinh_chat' => 'required|integer',
            'id_bao_mat' => 'required|integer',
            'id_don_vi' => 'integer',
            'luu_tru_noi_bo' => 'integer',
            'ngay_ky' => 'required|date',
            'nguoi_ky' => 'required'
        ];

        $customMessages = [
            'ten_van_ban.required' => 'Tên văn bản bắt buộc nhập',
            'id_loai.required' => 'Loại văn bản bắt buộc',
            'trich_yeu.required' => 'Trích yếu văn bản bắt buộc nhập',
            'ngay_nhan.required' => 'Ngày nhận bắt buộc nhập',
            'ngay_nhan.date' => 'Ngày nhận phải đúng định dạng ngày',
            'ngay_ban_hanh.required' => 'Ngày ban hành bắt buộc nhập',
            'ngay_ban_hanh.date' => 'Ngày ban hành phải đúng định dạng ngày',
            'trang_thai.required' => 'Trạng thái văn bản bắt buộc',
            'thoi_gian_xu_ly.date' => 'Thời gian xử lý phải đúng định dạng ngày',
            'id_khoi_co_quan.required' => 'Khoi cơ quan văn bản bắt buộc',
            // 'id_hinh_thuc.required' => 'Hình thức bắt buộc',
            'id_tinh_chat.required' => 'Mức độ tính chất bắt buộc',
            'id_bao_mat.required' => 'Mức độ bảo mật bắt buộc',
            'ngay_ky.date' => 'Ngày ký phải đúng định dạng ngày',
            'ngay_ky.required' => 'Ngày ký bắt buộc',
            'nguoi_ky.required' => 'Người ký là bắt buộc',
        ];
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        if ($this->checkExitsUpdate($id, 'so_hieu_van_ban', $data['so_hieu_van_ban'], 'e_van_ban', 2, $auth)) {
            resError('Trùng lặp số hiệu văn bản');
        }

        $this->db->trans_start();

        $this->E_van_ban_model->where('id_van_ban', $id)->where('loai_van_ban', $this->common::VAN_BAN_DI)->update($data);

        // Cập nhật thông tin ban hành
        $ids_khoi_co_quan_old = commonRequest('ids_khoi_co_quan_old') ? commonRequest('ids_khoi_co_quan_old') : null;
        $ids_co_quan_old = commonRequest('ids_co_quan_old') ? commonRequest('ids_co_quan_old') : null;
        $ids_don_vi_xu_ly_old = commonRequest('ids_don_vi_xu_ly_old') ? commonRequest('ids_don_vi_xu_ly_old') : null;
        $ids_hinh_thuc_old = commonRequest('ids_hinh_thuc_old') ? commonRequest('ids_hinh_thuc_old') : null;
        $ids_ql_nguoi_dung_old = commonRequest('ids_ql_nguoi_dung_old') ? commonRequest('ids_ql_nguoi_dung_old') : null;

        // Xử lý hình thức
        $ids_hinh_thuc_array = explode(',', $ids_hinh_thuc);
        $ids_hinh_thuc_old_array = explode(',', $ids_hinh_thuc_old);

        $arrayDeleted = array_diff($ids_hinh_thuc_old_array, $ids_hinh_thuc_array);
        if (!empty($arrayDeleted)) {
            $this->E_vb_hinh_thuc_model
                ->where('id_van_ban', $id)
                ->whereIn('id_hinh_thuc', $arrayDeleted)
                ->delete();
        }

        $arrayAdded = array_diff($ids_hinh_thuc_array, $ids_hinh_thuc_old_array);
        if (!empty($arrayAdded)) {
            foreach ($arrayAdded as $id_hinh_thuc) {
                if ($id_hinh_thuc && ($this->E_hinh_thuc_model->find($id_hinh_thuc)))
                    $this->E_vb_hinh_thuc_model->create([
                        'id_van_ban' => $id,
                        'id_hinh_thuc' => $id_hinh_thuc,
                    ]);
            }
        }
        // END Xử lý hình thức

        // Xử lý khối cơ quan
        $ids_khoi_co_quan_array = explode(',', $ids_khoi_co_quan);
        $ids_khoi_co_quan_old_array = explode(',', $ids_khoi_co_quan_old);

        $arrayDeleted = array_diff($ids_khoi_co_quan_old_array, $ids_khoi_co_quan_array);
        if (!empty($arrayDeleted)) {
            $this->E_vb_khoi_co_quan_model
                ->where('id_van_ban', $id)
                ->whereIn('id_khoi_co_quan', $arrayDeleted)
                ->delete();
        }

        $arrayAdded = array_diff($ids_khoi_co_quan_array, $ids_khoi_co_quan_old_array);
        if (!empty($arrayAdded)) {
            foreach ($arrayAdded as $id_khoi_co_quan) {
                if ($id_khoi_co_quan && ($this->E_khoi_co_quan_model->find($id_khoi_co_quan)))
                    $this->E_vb_khoi_co_quan_model->create([
                        'id_van_ban' => $id,
                        'id_khoi_co_quan' => $id_khoi_co_quan,
                    ]);
            }
        }
        // END Xử lý khối cơ quan

        // Xử lý cơ quan
        $ids_co_quan_array = explode(',', $ids_co_quan);
        $ids_co_quan_old_array = explode(',', $ids_co_quan_old);

        $arrayCoQuanDeleted = array_diff($ids_co_quan_old_array, $ids_co_quan_array);
        if (!empty($arrayCoQuanDeleted)) {
            $this->E_vb_co_quan_model
                ->where('id_van_ban', $id)
                ->whereIn('id_co_quan', $arrayCoQuanDeleted)
                ->delete();
        }

        $arrayCoQuanAdded = array_diff($ids_co_quan_array, $ids_co_quan_old_array);
        if (!empty($arrayCoQuanAdded)) {
            foreach ($arrayCoQuanAdded as $id_co_quan) {
                if ($id_co_quan && ($this->E_co_quan_model->find($id_co_quan))) {
                    $this->E_vb_co_quan_model->create([
                        'id_van_ban' => $id,
                        'id_co_quan' => $id_co_quan,
                    ]);
                }
            }
        }
        // END Xử lý cơ quan

        // Xử lý đơn vị
        $xuly = $this->E_xu_ly_model->where('id_van_ban', $id)->first();
        if (!$xuly) {
            $xuly = $this->E_xu_ly_model->create([
                'id_van_ban' => $vb['id_van_ban'],
                'nguoi_tao' => $this->getUserLogin()['ql_nguoi_dung_id'],
            ]);
        }

        $ids_don_vi_xu_ly_array = explode(',', $ids_don_vi_xu_ly);
        $ids_don_vi_xu_ly_old_array = explode(',', $ids_don_vi_xu_ly_old);

        $arrayDonViDeleted = array_diff($ids_don_vi_xu_ly_old_array, $ids_don_vi_xu_ly_array);
        if (!empty($arrayDonViDeleted)) {
            $this->E_don_vi_xu_ly_model
                ->where('id_xu_ly', $xuly['id_xu_ly'])
                ->whereIn('id_don_vi', $arrayDonViDeleted)
                ->delete();
        }

        $arrayDonViAdded = array_diff($ids_don_vi_xu_ly_array, $ids_don_vi_xu_ly_old_array);
        if (!empty($arrayDonViAdded)) {
            $this->E_van_ban_model
                ->where('id_van_ban', $id)
                ->update([
                    'trang_thai' => Common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value']
                ]);

            // foreach ($arrayDonViAdded as $id_don_vi_xu_ly) {
            //     if ($id_don_vi_xu_ly && ($this->E_don_vi_model->find($id_don_vi_xu_ly))) {
            //         $this->E_don_vi_xu_ly_model->create([
            //             'id_don_vi' => $id_don_vi_xu_ly,
            //             'id_xu_ly' => $xuly['id_xu_ly'],
            //             'nguoi_tao' => $this->getUserLogin()['ql_nguoi_dung_id'],
            //         ]);
            //     }
            // }
        }


        $ids_ql_nguoi_dung_array = explode(',', $ids_ql_nguoi_dung);
        $ids_ql_nguoi_dung_old_array = explode(',', $ids_ql_nguoi_dung_old);

        $arrayNguoiXuLyDeleted = array_diff($ids_ql_nguoi_dung_old_array, $ids_ql_nguoi_dung_array);
        if (!empty($arrayNguoiXuLyDeleted)) {
            $this->E_don_vi_xu_ly_model
                ->where('id_xu_ly', $xuly['id_xu_ly'])
                ->whereIn('id_nguoi_xu_ly', $arrayNguoiXuLyDeleted)
                ->delete();
        }

        $arrayNguoiXuLyAdded = array_diff($ids_ql_nguoi_dung_array, $ids_ql_nguoi_dung_old_array);
        if (!empty($arrayNguoiXuLyAdded)) {
            $this->E_van_ban_model
                ->where('id_van_ban', $id)
                ->update([
                    'trang_thai' => Common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value']
                ]);

            foreach ($arrayNguoiXuLyAdded as $id_nguoi_xu_ly) {
                $nguoiDung = $this->Ql_nguoi_dung_model->find($id_nguoi_xu_ly);
                if ($id_nguoi_xu_ly && $nguoiDung) {
                    $this->E_don_vi_xu_ly_model->create([
                        'id_don_vi' => $nguoiDung['id_don_vi'],
                        'id_xu_ly' => $xuly['id_xu_ly'],
                        'id_nguoi_xu_ly' => $nguoiDung['ql_nguoi_dung_id'],
                        'nguoi_tao' => $this->getUserLogin()['ql_nguoi_dung_id'],
                    ]);
                }
            }
        }
        // END Xử lý đơn vị

        // Gửi thông báo
        $textZalo = '';
        $sendMail = false;
        $sendZalo = false;
        $textHinhThuc = '';

        $ds_hinh_thuc = $this->E_vb_hinh_thuc_model->where('id_van_ban', $id)->get();
        $ids_hinh_thuc_array = array_column($ds_hinh_thuc, 'id_hinh_thuc');
        foreach ($ids_hinh_thuc_array as $id_hinh_thuc) {
            $hinhthuc = $this->E_hinh_thuc_model->find($id_hinh_thuc);

            if ($hinhthuc['ma_hinh_thuc'] == 'ZALO') $sendZalo = true;
            if ($hinhthuc['ma_hinh_thuc'] == 'EMAIL') {
                $textHinhThuc = '/' . $hinhthuc['ten_hinh_thuc'];
                $sendMail = true;
            }

            $this->E_vb_hinh_thuc_model->create([
                'id_van_ban' => $vb['id_van_ban'],
                'id_hinh_thuc' => $id_hinh_thuc,
            ]);
        }

        $vb = $this->E_van_ban_model->find($id);
        $ban_hanh_lai = commonRequest('ban_hanh_lai') ? commonRequest('ban_hanh_lai') : false;
        if (($ids_don_vi_xu_ly_array || $ids_ql_nguoi_dung_array) && ($ban_hanh_lai == "true")) {
            $this->createNotification($auth, $vb['id_van_ban'], $ids_don_vi_xu_ly_array, $ids_ql_nguoi_dung_array, false, $sendZalo);
            send_zalo_oa_message($vb['id_van_ban']);

            $textZalo = $this->copyZaloMessage($vb['id_van_ban'], $textHinhThuc);
        }

        // Xử lý file đính kèm cũ
        $fileOld = commonRequest('file_dinh_kem_old') ? json_decode(commonRequest('file_dinh_kem_old'), true) : [];
        foreach ($fileOld as &$fo) {
            $fo['duong_dan'] = decryptString($fo['duong_dan']);
        }
        unset($fo);

        $fileOldPath = array_column($fileOld, 'duong_dan');
        $fileVb = $this->E_file_dinh_kem_model
            ->where('id_van_ban', $id)
            ->where('la_file_ban_hanh', 1)
            ->get();

        $fileVbPath = array_column($fileVb, 'duong_dan');

        $filePathDiff = array_diff($fileVbPath, $fileOldPath);

        foreach ($filePathDiff as $fpd) {
            //Xóa file trong db
            $this->E_file_dinh_kem_model->where('id_van_ban', $id)->where('duong_dan', $fpd)->delete();
            $delete = $this->fileupload->delete($fpd);
        }

        //Upload file đính kèm
        $folderName = 'documents';
        $uploadedFiles = [];
        if (isset($_FILES['file_dinh_kem_vbdi'])) {
            $files = $_FILES['file_dinh_kem_vbdi']; // Tên input từ form                        

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

            $filesOld = $this->E_file_dinh_kem_model->where('id_van_ban', $id)
                ->where('la_file_ban_hanh', 1)
                ->get();

            $uploadedFull = true;
            foreach ($uploadedFiles as $uploadedFile) {
                if ($uploadedFile['success']) {
                    $this->E_file_dinh_kem_model->create([
                        'id_van_ban' => $id,
                        'ten_file_goc' => $uploadedFile['file_name'],
                        'dung_luong' => exchangeFromKbToLargerCapacity($uploadedFile['file_size']), //đổi dung lượng lớn hơn MB từ KB
                        'duong_dan' => $uploadedFile['file_path'],
                        'loai_file' => $uploadedFile['file_extension'],
                        'la_file_ban_hanh' => 1,
                        'is_public' => $is_public
                    ]);
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
            } else {
                // upload mới thành công, xóa các file cũ
                // if (!empty($filesOld)) {
                //     $fileIds = array_column($filesOld, 'id_file_dinh_kem');
                //     $this->E_file_dinh_kem_model->whereIn('id_file_dinh_kem', $fileIds)->delete();

                //     //Xóa file
                //     foreach ($filesOld as $fileOld) {
                //         $deleteFile = $this->fileupload->delete($fileOld['duong_dan']);
                //     }
                // }
            }
        }

        // Xử lý file đính kèm cũ
        $fileOld = commonRequest('file_noi_bo_old') ? json_decode(commonRequest('file_noi_bo_old'), true) : [];
        foreach ($fileOld as &$fo) {
            $fo['duong_dan'] = decryptString($fo['duong_dan']);
        }
        unset($fo);

        $fileOldPath = array_column($fileOld, 'duong_dan');
        $fileVb = $this->E_file_dinh_kem_model
            ->where('id_van_ban', $id)
            ->where('la_file_ban_hanh', 0)
            ->get();

        $fileVbPath = array_column($fileVb, 'duong_dan');

        $filePathDiff = array_diff($fileVbPath, $fileOldPath);

        foreach ($filePathDiff as $fpd) {
            //Xóa file trong db
            $this->E_file_dinh_kem_model->where('id_van_ban', $id)->where('duong_dan', $fpd)->delete();
            $delete = $this->fileupload->delete($fpd);
        }

        // File nội bộ
        $uploadedFilesTCHC = [];
        if (isset($_FILES['file_dinh_kem_vbdi_tchc_noibo'])) {

            // resError(($_FILES['file_dinh_kem_vbdi_tchc_noibo']['name']));
            $files = $_FILES['file_dinh_kem_vbdi_tchc_noibo']; // Tên input từ form                        

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
                $uploadedFilesTCHC[] = $result;
            }

            $filesOld = $this->E_file_dinh_kem_model->where('id_van_ban', $id)
                ->where('la_file_ban_hanh', 0)
                ->get();

            $uploadedFullTCHC = true;
            foreach ($uploadedFilesTCHC as $item) {
                if ($item['success']) {
                    $this->E_file_dinh_kem_model->create([
                        'id_van_ban' => $vb['id_van_ban'],
                        'ten_file_goc' => $item['file_name'],
                        'dung_luong' => exchangeFromKbToLargerCapacity($item['file_size']), //đổi dung lượng lớn hơn MB từ KB
                        'duong_dan' => $item['file_path'],
                        'loai_file' => $item['file_extension'],
                        'la_file_ban_hanh' => 0,
                        'is_public' => $is_public
                    ]);
                } else {
                    $uploadedFullTCHC = false;
                    break;
                }
            }

            if (!$uploadedFullTCHC) {
                foreach ($uploadedFilesTCHC as $up) {
                    if ($up['success']) {
                        $delete = $this->fileupload->delete($up['file_path']);
                    }
                }
                $this->db->trans_rollback();
                resError('Có lỗi xảy ra khi upload file nội bộ');
            } else {
                // upload mới thành công, xóa các file cũ
                // if (!empty($filesOld)) {
                //     $fileIds = array_column($filesOld, 'id_file_dinh_kem');
                //     $this->E_file_dinh_kem_model->whereIn('id_file_dinh_kem', $fileIds)->delete();

                //     //Xóa file
                //     foreach ($filesOld as $fileOld) {
                //         $deleteFile = $this->fileupload->delete($fileOld['duong_dan']);
                //     }
                // }
            }
        }

        $this->E_file_dinh_kem_model->where('id_van_ban', $id)
            ->update([
                'is_public' => $is_public,
            ]);

        $newVb = $this->E_van_ban_model->find($id);
        //Ghi log
        $this->createLog('update', 'Cập nhật văn bản đi: ' . $vb['so_hieu_van_ban'], $vb, $newVb, 'e_van_ban');

        $this->db->trans_commit();
        // Trích xuất nội dung file
        call_n8n_trich_xuat($vb['id_van_ban']);


        resSuccess($newVb, 'Cập nhật văn bản đi thành công', 200, true, [
            'noi_dung_tin_nhan_zalo' => $textZalo,
        ]);
    }

    public function delete_post()
    {
        $ids = commonRequest('ids');
        $this->db->trans_start();
        $vanban = $this->E_van_ban_model->where('loai_van_ban', 2)->whereIn('id_van_ban', $ids)->get();
        if (count($ids) != count($vanban)) {
            resError('Có văn bản không tồn tại');
        }

        $this->E_van_ban_model->whereIn('id_van_ban', $ids)->update([
            'deleted_at' => date('Y-m-d H:i:s')
        ]);

        // $files = $this->E_file_dinh_kem_model->whereIn('id_van_ban', $ids)->get();
        // $this->E_ban_hanh_model->whereIn('id_van_ban', $ids)->delete();
        // $this->E_file_dinh_kem_model->whereIn('id_van_ban', $ids)->delete();

        // $xuly = $this->E_xu_ly_model->whereIn('id_van_ban', $ids)->get();
        // $xulyIds = array_column($xuly, 'id_xu_ly');

        // if (!empty($xulyIds)) {
        //     $this->E_don_vi_xu_ly_model->whereIn('id_xu_ly', $xulyIds)->delete();
        // }
        // $this->E_xu_ly_model->whereIn('id_van_ban', $ids)->delete();

        // $this->db->where_in('id_van_ban', $ids)->delete('e_vb_co_quan');
        // $this->db->where_in('id_van_ban', $ids)->delete('e_vb_khoi_co_quan');


        // $this->E_van_ban_model->whereIn('id_van_ban', $ids)->delete();
        // foreach ($files as $file) {
        //     $this->fileupload->delete($file['duong_dan']);
        // }

        $this->createLog('delete', 'Xóa văn bản đi', $vanban,  null, 'e_van_ban');
        $this->db->trans_commit();
        resSuccess(null, 'Xóa thành công');
    }


    private function getExcelColumn()
    {
        $cols = [
            '' => 'STT',
            'nam' => 'Năm',
            // 'ngay_nhan' => 'Ngày CV',
            'ngay_ky' => 'Ngày ký',
            'so_hieu_van_ban' => 'Số trên CV',
            'ngay_ban_hanh' => 'Ngày trên CV',
            'noi_dung' => 'Nội dung',
            'noi_nhan' => 'Nơi nhận',
            'nguoi_ky' => 'Người ký'

        ];
        return $cols;
    }


    public function export_get()
    {
        $auth  = $this->getUserLogin();
        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;
        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];
        $fromDate = commonRequest('fromDate') ? commonRequest('fromDate') : null;
        $toDate = commonRequest('toDate') ? commonRequest('toDate') : null;

        // $searchKey['id_don_vi_soan'] = $auth['id_don_vi'];

        $data = $this->E_van_ban_model->getListExportVanbandi($start, $length, $searchValue, $searchKey, $fromDate, $toDate);

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
        $sheet->setCellValue('A1', 'Công văn đi'); // Thêm cột thông báo

        // Hợp nhất các ô từ A1 đến H1
        $sheet->mergeCells('A1:H1');

        // In đậm chữ và tăng kích thước font cho ô A1
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,           // In đậm
                'size' => 16,             // Tăng kích thước font
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,  // Căn giữa nội dung
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

                if ($key == 'noi_nhan') {
                    $text_noi_nhan = '';

                    $e_vb_khoi_co_quan = $this->E_vb_khoi_co_quan_model
                        ->select('e_khoi_co_quan.ten_khoi_co_quan')
                        ->leftJoin('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_vb_khoi_co_quan.id_khoi_co_quan')
                        ->where('e_vb_khoi_co_quan.id_van_ban', $item['id_van_ban'])
                        ->get();
                    if ($e_vb_khoi_co_quan) {
                        foreach ($e_vb_khoi_co_quan as $key => $itemkcq) {
                            $text_noi_nhan .= $itemkcq['ten_khoi_co_quan'] . ', ';
                        }
                    }

                    $e_vb_co_quan = $this->E_vb_co_quan_model
                        ->select('e_co_quan.ten_co_quan')
                        ->leftJoin('e_co_quan', 'e_co_quan.id_co_quan = e_vb_co_quan.id_co_quan')
                        ->where('e_vb_co_quan.id_van_ban', $item['id_van_ban'])
                        ->get();
                    if ($e_vb_co_quan) {
                        foreach ($e_vb_co_quan as $key => $itemcq) {
                            $text_noi_nhan .= $itemcq['ten_co_quan'] . ', ';
                        }
                    }

                    $e_don_vi_xu_ly = $this->E_xu_ly_model
                        ->select('e_don_vi.ten_don_vi')
                        ->leftJoin('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly')
                        ->leftJoin('e_don_vi', 'e_don_vi.id_don_vi = e_don_vi_xu_ly.id_don_vi')
                        ->where('e_xu_ly.id_van_ban', $item['id_van_ban'])
                        ->get();
                    if ($e_don_vi_xu_ly) {
                        foreach ($e_don_vi_xu_ly as $key => $itemdvxl) {
                            $text_noi_nhan .= $itemdvxl['ten_don_vi'] . ', ';
                        }
                    }

                    $text_noi_nhan = rtrim($text_noi_nhan, ", ");

                    $sheet->setCellValue($column . $row, $text_noi_nhan);
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
        $filename = 'vanbandi_' . time() . '.xlsx'; // Tên file kèm timestamp để tránh trùng lặp
        $filePath = $directory . $filename;
        // Kiểm tra và tạo thư mục nếu chưa tồn tại
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);

        if (file_exists($filePath)) {
            $this->createLog('Export', 'Export danh sách công văn đi', NULL, 'Export danh sách công văn đi', 'e_van_ban');
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
    }

    public function import_post()
    {
        try {

            $stop = false;
            $message = '';
            $dataList = [];
            $file_import = commonRequest('file_excel');

            if ($file_import) {

                $config['upload_path'] = 'uploads/excel/vanbandi/'; // Thư mục để lưu file
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

                    $dataList = $this->pxl->importExcel(3, 4, $path);
                    $objPHPExcel = PHPExcel_IOFactory::load($path);
                    $sheet = $objPHPExcel->getActiveSheet();
                    $highestColumn = $sheet->getHighestColumn();
                    $highestRow = $sheet->getHighestRow();

                    $highestColumn = 'J';
                    $columnResult = $highestColumn . '2';
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
                        'nam',
                        'so_hieu_van_ban',
                        'ngay_ban_hanh',
                        'trich_yeu',
                        'ten_co_quan',
                        'nguoi_ky',
                        'ghi_chu'
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

                        $startRow_columnResult = 4;

                        foreach ($dataList as $key => $l) {
                            $errorMessages = [];
                            $field_PrimaryKey = [
                                'so_hieu_van_ban' => 'Số trên CV đang rỗng.',
                                'ngay_ban_hanh' => 'Ngày trên CV đang rỗng.'
                            ];

                            $keys = [];
                            $keys = array_keys($l);
                            foreach ($field_PrimaryKey as $key => $errorMessage) {
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

                                    $ngaybanhanh = $l['ngay_ban_hanh'] ?? '';

                                    $ngaybanhanhFormat = null;
                                    if ($ngaybanhanh) {
                                        $dateObj = DateTime::createFromFormat('d/m/Y', trim($ngaybanhanh));
                                        if ($dateObj !== false) {
                                            $ngaybanhanhFormat = $dateObj->format('Y-m-d');
                                        } else {
                                            // Có thể log ra để debug
                                            $ngaybanhanhFormat = null;
                                        }
                                    }

                                    $dataInsert = [
                                        'so_hieu_van_ban' => $l['so_hieu_van_ban'],
                                        'loai_van_ban' => $this->common::VAN_BAN_DI,
                                        'trich_yeu' => $l['trich_yeu'],
                                        // 'ngay_nhan' => $ngaynhanFormat,
                                        'ngay_ban_hanh' => $ngaybanhanhFormat,
                                        'ngay_ky' => $ngaybanhanhFormat,
                                        'nguoi_ky' => $l['nguoi_ky'],
                                        'ghi_chu' => $l['ghi_chu'],
                                        // 'id_co_quan' => $coquan['id_co_quan'],
                                        'trang_thai' => $this->common::STATUS_VAN_BAN_DI['DA_BAN_HANH']['value'],

                                        'id_nguoi_tao' => $this->getUserLogin()['ql_nguoi_dung_id'],
                                        'id_nguoi_sua' => $this->getUserLogin()['ql_nguoi_dung_id'],
                                        'ngay_tao' => date('Y-m-d H:i:s'),
                                        'ngay_sua' => date('Y-m-d H:i:s'),
                                    ];

                                    $dataInsertArray[] = $dataInsert;

                                    $id_van_ban = $this->E_van_ban_model->insert($dataInsert);

                                    if (trim($l['ten_co_quan']) != "") {
                                        $arrayCoQuan = explode(",", $l['ten_co_quan']);
                                        if (!empty($arrayCoQuan)) {
                                            foreach ($arrayCoQuan as $item) {
                                                if (trim($item) != "") {
                                                    $coquan = $this->E_co_quan_model->where('ten_co_quan', trim($item))->first();

                                                    if (!$coquan) {
                                                        $id_coquan = $this->E_co_quan_model->insert([
                                                            'ten_co_quan' => trim($item)
                                                        ]);

                                                        $this->E_vb_co_quan_model->create([
                                                            'id_van_ban' => $id_van_ban,
                                                            'id_co_quan' => $id_coquan
                                                        ]);
                                                    } else {
                                                        $this->E_vb_co_quan_model->create([
                                                            'id_van_ban' => $id_van_ban,
                                                            'id_co_quan' => $coquan['id_co_quan']
                                                        ]);
                                                    }
                                                }
                                            }

                                            $this->E_van_ban_model->where('id_van_ban', $id_van_ban)->update([
                                                'trang_thai' => $this->common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value']
                                            ]);
                                        }
                                    }
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
                            // dd($dataInsert);
                            // $this->db->trans_start();
                            $this->createLog('Import', 'Import văn bản đi', NULL, $dataInsert, 'e_van_ban');
                            // $this->db->trans_commit();
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

    public function baocaophanhoi_get()
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

        $data = $this->E_van_ban_model->getAllBaoCaoPhanHoiVanBanDi($start, $length, $searchValue, $orderBy, $searchKey, $fromDate, $toDate);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function change_status_put($id)
    {
        $vanban = $this->E_van_ban_model->find($id);
        if (!$vanban) resError('Không tìm thấy văn bản', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        $status = commonRequest('trang_thai') ? commonRequest('trang_thai') : null;
        if (!in_array($status, ['TAO_MOI', 'DA_BAN_HANH', 'CHO_XU_LY', 'DA_PHAN_HOI', 'CHUA_PHAN_HOI', 'HOAN_THANH'])) {
            resBadrequest(['trang_thai' => 'Trạng thái không hợp lệ'], 'Trạng thái không hợp lệ');
        }
        $this->E_van_ban_model->where('id_van_ban', $id)->update([
            'trang_thai' => $this->common::STATUS_VAN_BAN_DI[$status]['value']
        ]);
        $this->createLog('Update', 'Cập nhật trạng thái văn bản', $vanban, $this->E_van_ban_model->find($id), 'e_van_ban');
        resSuccess(null, 'Cập nhật thành công');
    }

    public function update_by_key_post($id)
    {
        $vanban = $this->E_van_ban_model->where('id_van_ban', $id)->where('loai_van_ban', $this->common::VAN_BAN_DI)->where('deleted_at IS NULL')->first();
        if (!$vanban) {
            resError('Không tìm thấy văn bản', REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }
        $key = commonRequest('key') ? commonRequest('key') : null;
        $value = commonRequest('value') ? commonRequest('value') : null;
        if (!$key) {
            resError('Lỗi', REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }
        $this->E_van_ban_model->where('id_van_ban', $id)->update([
            $key => $value
        ]);
        $this->createLog('Update', 'Cập nhật văn bản đi', $vanban, $this->E_van_ban_model->find($id), 'e_van_ban');
        resSuccess(null, 'Cập nhật thành công', REST_INSTANCE_Controller::HTTP_OK);
    }

    public function bao_cao_phan_hoi_create_post($id)
    {
        $auth = $this->getUserLogin();

        $vanban = $this->E_van_ban_model->find($id);
        if (!$vanban) resError('Không tìm thấy văn bản', REST_Controller::HTTP_NOT_FOUND);

        $trangThai = commonRequest('trang_thai') ? commonRequest('trang_thai') : null;

        if (!$trangThai || !in_array($trangThai, ['DA_PHAN_HOI'])) {
            resBadrequest([], 'Trạng thái không hợp lệ');
        }

        $data = [
            'noi_dung' => commonRequest('noi_dung') ? commonRequest('noi_dung') : null,
            // 'ngay_bao_cao' => commonRequest('ngay_bao_cao') ? commonRequest('ngay_bao_cao') : null,
            'ngay_bao_cao' => date('Y-m-d'),
            'id_van_ban' => $id,
            'id_don_vi_phan_hoi' => $this->getUserLogin()['id_don_vi'],
            'nguoi_tao' => $this->getUserLogin()['ql_nguoi_dung_id'],
            'trang_thai' => $this->common::STATUS_VAN_BAN_DI[$trangThai]['value'],
        ];

        $rules = [
            'noi_dung' => 'required',
            // 'ngay_bao_cao' => 'required|date',
            'id_van_ban' => 'required',
            'id_don_vi_phan_hoi' => 'required',
        ];

        $customMessages = [
            'noi_dung.required' => 'Nội dung phản hồi bắt buộc nhập',
            // 'ngay_bao_cao.required' => 'Ngày báo cáo bắt buộc nhập',
            // 'ngay_bao_cao.date' => 'Ngày báo cáo bắt phải đúng địng dạng',
        ];

        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        // Upload nhiều files_dinh_kem
        $folderName = 'documents/bao_cao_phan_hoi/' . date('Y') . '/' . date('m');
        $uploadedFiles = [];
        $files = commonRequest('files_dinh_kem') ? commonRequest('files_dinh_kem') : null;
        if (isset($files) && !empty($files)) {
            // $files = $data['files_dinh_kem']; // Tên input từ form  
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
        }
        $data['files_dinh_kem'] = json_encode($uploadedFiles);

        $this->db->trans_start();
        $baocaophanhoi = $this->E_bao_cao_model->create($data);

        // Cập nhật đã xem cho báo cáo vừa tạo
        $nguoitao = $this->Ql_nguoi_dung_model->find($vanban['id_nguoi_tao']);
        if ($nguoitao['id_don_vi'] == $auth['id_don_vi']) {
            $this->db->insert('e_bao_cao_da_xem', [
                'id_bao_cao' => $baocaophanhoi['id_bao_cao'],
                'nguoi_xem' => $auth['ql_nguoi_dung_id'],
                'ngay_xem' => date('Y-m-d H:i:s'),
                'id_van_ban' => $vanban['id_van_ban']
            ]);
        }

        //Đổi trạng thái văn bản
        $xuly = $this->E_xu_ly_model->where('id_van_ban', $id)->first();
        if ($xuly) {
            //Tìm đơn vị được giao nhiệm vụ xử lý văn bản
            $donvixuly = $this->E_don_vi_xu_ly_model->where('id_xu_ly', $xuly['id_xu_ly'])->get();
            //Đếm đơn vị xử lý
            $countdonvixuly = count(array_unique(array_column($donvixuly, 'id_don_vi')));

            //Tìm đơn vị báo cáo phản hồi
            $baocao = $this->E_bao_cao_model->where('id_van_ban', $id)->get();
            //Đếm đơn vị báo cáo
            $countbaocao = count(array_unique(array_column($baocao, 'id_don_vi_phan_hoi')));

            //Nếu các đơn vị báo cáo đủ thì sẽ tự chuyển trạng thái
            if ($countbaocao >= $countdonvixuly) {
                $this->E_van_ban_model->where('id_van_ban', $id)->update([
                    'trang_thai' => $this->common::STATUS_VAN_BAN_DI['DA_PHAN_HOI']['value']
                ]);
            }
        }

        $this->createLog('create', 'Báo cáo phản hồi văn bản: ' . $vanban['so_hieu_van_ban'], $vanban, $baocaophanhoi, 'e_van_ban, e_bao_cao');

        $xuly = $this->E_xu_ly_model->where('id_van_ban', $id)->first();

        if ($xuly) {
            $donvixuly = $this->E_don_vi_xu_ly_model->where('id_xu_ly', $xuly['id_xu_ly'])->where('id_don_vi', $auth['id_don_vi'])->get();

            foreach ($donvixuly as $dvxl) {
                if (!$dvxl['da_xem']) {
                    $this->E_don_vi_xu_ly_model->where('id_don_vi_xu_ly', $dvxl['id_don_vi_xu_ly'])->update([
                        'da_xem' => 1
                    ]);
                }

                $donvixulydaxem = $this->E_don_vi_xu_ly_da_xem_model
                    ->where('id_don_vi_xu_ly', $dvxl['id_don_vi_xu_ly'])
                    ->where('ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])
                    ->first();
                if (!$donvixulydaxem) {
                    $this->E_don_vi_xu_ly_da_xem_model->create([
                        'id_don_vi_xu_ly' => $dvxl['id_don_vi_xu_ly'],
                        'ql_nguoi_dung_id' => $auth['ql_nguoi_dung_id'],
                        'ngay_xem' => date('Y-m-d H:i:s')
                    ]);
                }
            }

            // HOẠT ĐỘNG NHƯNG KHÔNG DÙNG |||| Tạo thông báo về cho các đơn vị được ban hành
            // $donviduocbanhanh = $this->E_don_vi_xu_ly_model->where('id_xu_ly', $xuly['id_xu_ly'])->get();
            // $tatcadonvi = $this->E_don_vi_model->all();
            // $donViNhan = '';
            // if ($donviduocbanhanh) {
            //     $donvithongbaoIds = array_unique(array_column($donviduocbanhanh, 'id_don_vi'));
            //     $tatcadonviIds = array_column($tatcadonvi, 'id_don_vi');

            //     sort($donvithongbaoIds);
            //     sort($tatcadonviIds);

            //     if ($donvithongbaoIds == $tatcadonviIds) {
            //         $donViNhan = 'Tất cả các đơn vị thuộc trường';
            //     } else {
            //         $dsDonViNhan = $this->E_don_vi_model->whereIn('id_don_vi', $donvithongbaoIds)->get();
            //         $tenDonVis = array_column($dsDonViNhan, 'ten_don_vi');
            //         // $donViNhan = implode(', ', $tenDonVis);
            //         foreach ($tenDonVis as $ten) {
            //             $donViNhan = $donViNhan . "\n +" . $ten;
            //         }
            //     }

            //     $noidungthongbao = $vanban['trich_yeu'] .
            //         ".\nClick vào đây để xem chi tiết: " . $this->config->item('frontend_url') . 'vanban/vanbandendonvi/xemchitiet/' . $vanban['id_van_ban'] .
            //         ".\nĐơn vị nhận: " . $donViNhan;

            //     $notification = $this->Ql_thong_bao_model->create([
            //         'ql_thong_bao_tieu_de' => $vanban['trich_yeu'],
            //         'ql_thong_bao_tieu_de_tieng_anh' => $vanban['trich_yeu'],
            //         'ql_thong_bao_noi_dung' => $noidungthongbao,
            //         'ql_thong_bao_noi_dung_tieng_anh' => $noidungthongbao,
            //         'ql_thong_bao_ngay_gui' => date('Y-m-d H:i:s'),
            //         'ql_thong_bao_loai' => 1, //1: thông báo, 2: nhắc nhở
            //         'ql_thong_bao_doi_tuong' => 2,
            //         'ql_thong_bao_da_gui' => 1,
            //         'ql_thong_bao_gui_zalo' => 1, // Tạm gán
            //         'ql_thong_bao_tu_dong_gui' => 1,
            //         'ql_thong_bao_ds_don_vi_id' => !empty($donvithongbaoIds) ? json_encode($donvithongbaoIds) : null,
            //         'ql_thong_bao_cong_khai' => 0, //Nội bộ
            //         'created_user_id' => $auth['ql_nguoi_dung_id'],
            //         'updated_user_id' => $auth['ql_nguoi_dung_id'],
            //         'ql_thong_bao_link' => $this->config->item('frontend_url') . 'vanban/vanbandendonvi/xemchitiet/' . $vanban['id_van_ban']
            //     ]);

            //     $users = $this->Ql_nguoi_dung_model->whereIn('id_don_vi', $donvithongbaoIds)->get();
            //     $userIds = array_column($users, 'ql_nguoi_dung_id');

            //     $dataInsertNotiUser = [];
            //     foreach ($userIds as $uId) {
            //         $dataInsertNotiUser[] = [
            //             'ql_thong_bao_id' => $notification['ql_thong_bao_id'],
            //             'ql_nguoi_dung_id' => $uId,
            //             'created_at' => date('Y-m-d H:i:s'),
            //             'updated_at' => date('Y-m-d H:i:s'),
            //             'created_user_id' => $auth['ql_nguoi_dung_id'],
            //             'updated_user_id' => $auth['ql_nguoi_dung_id'],
            //         ];
            //     }
            //     if (!empty($dataInsertNotiUser)) {
            //         $this->Ql_thong_bao_model->insertThongBaoNguoiDung($dataInsertNotiUser);
            //     }
            // }
        }

        $this->db->trans_commit();
        resSuccess(null, 'Phản hồi văn bản thành công');
    }

    public function xembaocao_post($id)
    {
        $auth = $this->getUserLogin();
        $ids_bao_cao = commonRequest('ids_bao_cao') ? commonRequest('ids_bao_cao') : null;

        $bao_cao_da_xem = $this->db
            ->select('*')
            ->from('e_bao_cao_da_xem')
            ->where('id_van_ban', $id)
            ->get()
            ->result_array();

        $ids_bao_cao_chua_xem = array_diff($ids_bao_cao, array_column($bao_cao_da_xem, 'id_bao_cao'));
        if (!empty($ids_bao_cao_chua_xem)) {
            foreach ($ids_bao_cao_chua_xem as $id_bao_cao) {
                $this->db->insert('e_bao_cao_da_xem', [
                    'id_bao_cao' => $id_bao_cao,
                    'id_van_ban' => $id,
                    'nguoi_xem' => $auth['ql_nguoi_dung_id'],
                    'ngay_xem' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        resSuccess(null, 'Đánh dấu đã xem thành công!');
    }

    public function update_files_post($id)
    {
        $la_file_ban_hanh = commonRequest('la_file_ban_hanh') ?? null;
        // resError('', 500, $la_file_ban_hanh);
        if (!in_array($la_file_ban_hanh, [0, 1])) {
            resError('Lỗi', 'Dữ liệu không hợp lệ');
        }
        $currentFiles = $this->E_file_dinh_kem_model->where('la_file_ban_hanh', $la_file_ban_hanh)->where('id_van_ban', $id)->get();
        // Lấy file cũ CÒN LẠI (ĐƯỢC GIỮ) đã nhận được
        $file_dinh_kem_old = json_decode(commonRequest('file_dinh_kem_old'), true) ?? [];
        $file_dinh_kem_old = array_map(function ($f_old) {
            $f_old['duong_dan'] = decryptString($f_old['duong_dan']);
            return $f_old;
        }, $file_dinh_kem_old); // Mã hóa về đường dẫn gốc
        $this->db->trans_begin();
        try {
            foreach ($currentFiles as $key => $file) {
                if (!in_array($file['duong_dan'], array_column($file_dinh_kem_old, 'duong_dan'))) {
                    @unlink(FCPATH . $file['duong_dan']);
                    $this->E_file_dinh_kem_model->where('id_file_dinh_kem', $file['id_file_dinh_kem'])->delete();
                }
            }
            $folderName = 'documents/' . date('Y') . '/' . date('m');
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
                    $this->E_file_dinh_kem_model->create([
                        'id_van_ban' => $id,
                        'ten_file_goc' => $uploadedFile['file_name'],
                        'dung_luong' => exchangeFromKbToLargerCapacity($uploadedFile['file_size']), //đổi dung lượng lớn hơn MB từ KB
                        'duong_dan' => $uploadedFile['file_path'],
                        'loai_file' => $uploadedFile['file_extension'],
                        'is_public' => 1,
                        'la_file_ban_hanh' => $la_file_ban_hanh,
                    ]);
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

            $dataNew = $this->E_file_dinh_kem_model->where('id_van_ban', $id)->get();
            //Ghi log
            $this->createLog('update', 'Cập nhật file', json_encode($currentFiles), $dataNew, 'e_van_ban');
            $this->db->trans_commit();

            resSuccess(null, 'Đã cập nhật file');
        } catch (\Throwable $th) {
            //throw $th;
            $this->db->trans_rollback();
            resError('Lỗi xử lý query', 500, $th->getMessage());
        }
    }

    public function view_log_get()
    {
        $log = $this->Ql_nhat_ky_model
            ->select("
                ql_nhat_ky.*,
                ql_nguoi_dung.ql_nguoi_dung_ho_ten,
                JSON_UNQUOTE(JSON_EXTRACT(ql_nhat_ky_gia_tri_moi, '$.id_van_ban')) AS id_van_ban
            ")
            ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = ql_nhat_ky.ql_nguoi_dung_id')
            ->where('ql_nhat_ky_controller', 'vanbandi')
            ->orderBy('ql_nhat_ky_ngay_tao', 'desc')
            ->get(20);

        resSuccess($log, 'Lấy danh sách nhật ký thành công');
    }
    public function remind_post()
    {
        // Nhắc nhở đơn vị thực hiện theo văn bản
        $id_van_ban = commonRequest('id_van_ban') ?? null;
        $id_don_vi = commonRequest('id_don_vi') ?? null;

        if (!$id_van_ban || !$id_don_vi) {
            resError('Thiếu thông tin id_van_ban hoặc id_don_vi', REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        $vanban = $this->E_van_ban_model->find($id_van_ban);
        if (!$vanban) {
            resError('Không tìm thấy văn bản', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }

        $donvi = $this->E_don_vi_model->find($id_don_vi);
        if (!$donvi) {
            resError('Không tìm thấy đơn vị', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }

        $xuly = $this->E_xu_ly_model->where('id_van_ban', $id_van_ban)->first();
        if (!$xuly) {
            resError('Không tìm thấy thông tin xử lý của văn bản', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }

        $don_vi_xu_ly = $this->E_don_vi_xu_ly_model->where('id_xu_ly', $xuly['id_xu_ly'])->where('id_don_vi', $id_don_vi)->first();
        if (!$don_vi_xu_ly) {
            resError('Không tìm thấy thông tin đơn vị xử lý của văn bản', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }

        // Gửi thông báo nhắc nhở
        $auth = $this->getUserLogin();
        // $noidungthongbao = 'Kính nhờ Đơn vị ' . $donvi['ten_don_vi'] . ' thực hiện văn bản: [' . $vanban['so_hieu_van_ban'] . '] \n ' . $vanban['trich_yeu'] .
        //     '. Click vào đây để <a href="' . $this->config->item('frontend_url') . 'vanban/vanbandendonvi/xemchitiet/' . $vanban['id_van_ban'] . '">xem chi tiết</a>.';
        $tieuDe = 'Nhắc nhở thực hiện văn bản [' . $vanban['so_hieu_van_ban'] . ']';
        $noidungthongbao = "Kính nhờ quý đơn vị {$donvi['ten_don_vi']} thực hiện văn bản: [ {$vanban['so_hieu_van_ban']} ] \n\n{$vanban['trich_yeu']}.\n\n" .
            "Xem chi tiết tại: " . $this->config->item('frontend_url') . 'vanban/vanbandendonvi/xemchitiet/' . $vanban['id_van_ban'];


        $notification = $this->Ql_thong_bao_model->create([
            'ql_thong_bao_tieu_de' => $tieuDe,
            'ql_thong_bao_tieu_de_tieng_anh' => 'Reminder to process document',
            'ql_thong_bao_noi_dung' => $noidungthongbao,
            'ql_thong_bao_noi_dung_tieng_anh' => $noidungthongbao,
            'ql_thong_bao_ngay_gui' => date('Y-m-d H:i:s'),
            'ql_thong_bao_loai' => 2, // 2: nhắc nhở
            'ql_thong_bao_doi_tuong' => 2,
            'ql_thong_bao_da_gui' => 1,
            'ql_thong_bao_gui_zalo' => 1, // Tạm gán
            'ql_thong_bao_tu_dong_gui' => 1,
            'ql_thong_bao_ds_don_vi_id' => json_encode([$id_don_vi]),
            'ql_thong_bao_cong_khai' => 0,
            'created_user_id' => $auth['ql_nguoi_dung_id'],
            'updated_user_id' => $auth['ql_nguoi_dung_id'],
            'ql_thong_bao_link' => $this->config->item('frontend_url') . 'vanban/vanbandendonvi/xemchitiet/' . $vanban['id_van_ban']
        ]);

        // Gửi thông báo cho tất cả người dùng thuộc đơn vị
        $users = $this->Ql_nguoi_dung_model->where('id_don_vi', $id_don_vi)->get();
        $userIds = array_column($users, 'ql_nguoi_dung_id');
        $dataInsertNotiUser = [];
        foreach ($userIds as $uId) {
            $dataInsertNotiUser[] = [
                'ql_thong_bao_id' => $notification['ql_thong_bao_id'],
                'ql_nguoi_dung_id' => $uId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'created_user_id' => $auth['ql_nguoi_dung_id'],
                'updated_user_id' => $auth['ql_nguoi_dung_id'],
            ];
        }
        if (!empty($dataInsertNotiUser)) {
            $this->Ql_thong_bao_model->insertThongBaoNguoiDung($dataInsertNotiUser);
        }

        // Ghi log nhắc nhở
        $this->createLog('remind', 'Nhắc nhở đơn vị ' . $donvi['ten_don_vi'] . ' thực hiện văn bản', $vanban, $donvi, 'e_van_ban, e_don_vi');

        resSuccess(null, 'Đã gửi nhắc nhở thành công');
    }

    public function search_files_post()
    {
        $data = [
            'keyword' => commonRequest('keyword') ?? '',
            'page' => commonRequest('page') ?? 1,
            'per_page' => commonRequest('per_page') ?? 10,
            'range' => commonRequest('range') ?? 'today',
        ];
        $files = $this->E_van_ban_model->getFiles_byLoaiVanBan(2, $data);
        resSuccess($files, 'Tìm kiếm file đính kèm thành công');
    }

    public function cloneDocument_get($id_van_ban)
    {
        $vanBanGoc = $this->E_van_ban_model->find($id_van_ban);
        if (!$vanBanGoc) {
            resError('Không tìm thấy văn bản gốc', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }

        $vanBanNhanBan = $vanBanGoc;
        unset($vanBanNhanBan['id_van_ban']);

        $ds_van_ban_phu_hop = $this->E_van_ban_model
            ->select('so_hieu_van_ban, ngay_ky')
            ->where('loai_van_ban', 2)
            ->where('id_loai', $vanBanGoc['id_loai'])
            ->where('YEAR(ngay_ky)', date('Y', strtotime($vanBanGoc['ngay_ky'])))
            ->get();

        $sohieuvanban_next = 1;
        if (!empty($ds_van_ban_phu_hop)) {
            $soHieuMNax = 1;
            foreach ($ds_van_ban_phu_hop as $item) {
                $so_hieu = explode('/', $item['so_hieu_van_ban'])[0];
                if (intval($so_hieu) > $soHieuMNax) {
                    $soHieuMNax = intval($so_hieu);
                }
            }

            $sohieuvanban_next =  intval($soHieuMNax) + 1;
        }

        $hautoVBDi = $this->E_loai_model->find($vanBanGoc['id_loai']);
        $sohieuvanban_last = (string)$sohieuvanban_next . '/' . $hautoVBDi['hau_to'];
        $vanBanNhanBan['so_hieu_van_ban'] = $sohieuvanban_last;

        $insert_id = $this->E_van_ban_model->insert($vanBanNhanBan);

        // Insert hình thức
        if ($vanBanGoc['loai_van_ban'] == 2) {
            $dsHinhThucCu = $this->E_vb_hinh_thuc_model
                ->where('id_van_ban', $id_van_ban)
                ->get();

            foreach ($dsHinhThucCu as $ht) {
                $this->E_vb_hinh_thuc_model->insert([
                    'id_van_ban' => $insert_id,
                    'id_hinh_thuc' => $ht['id_hinh_thuc'],
                ]);
            }
        }

        // Insert đơn vị xử lý
        $xuly_id = $this->E_xu_ly_model->insert([
            'id_van_ban' => $insert_id,
            'nguoi_tao' => $this->getUserLogin()['ql_nguoi_dung_id'],
        ]);

        $xuLyCu = $this->E_xu_ly_model->where('id_van_ban', $id_van_ban)->first();
        $donViXuLyCu = $this->E_don_vi_xu_ly_model->where('id_xu_ly', $xuLyCu['id_xu_ly'])->get();
        $donViXuLy = [];
        foreach ($donViXuLyCu as $dv) {
            $donViXuLy[] = [
                'id_don_vi' => $dv['id_don_vi'],
                'id_xu_ly' => $xuly_id,
                'nguoi_tao' => $this->getUserLogin()['ql_nguoi_dung_id'],
                'id_nguoi_xu_ly' => $dv['id_nguoi_xu_ly'],
            ];
        }

        $this->E_don_vi_xu_ly_model->insertBatch($donViXuLy);

        // Insert cơ quan
        $dsCoQuanCu = $this->E_vb_co_quan_model
            ->where('id_van_ban', $id_van_ban)
            ->get();

        foreach ($dsCoQuanCu as $ht) {
            $this->E_vb_co_quan_model->insert([
                'id_van_ban' => $insert_id,
                'id_co_quan' => $ht['id_co_quan'],
            ]);
        }

        // Clone file
        $dsFileDinhKemCu = $this->E_file_dinh_kem_model
            ->where('id_van_ban', $id_van_ban)
            ->get();

        foreach ($dsFileDinhKemCu as $file) {
            $duongDanMoi = '';
            $source = $file['duong_dan'];

            $duongDanMoi = 'uploads/documents/' . $file['ten_file_goc'] . '_copy_' . time() . '.' . $file['loai_file'];
            $destination = FCPATH . $duongDanMoi;

            if (file_exists($source)) {
                copy($source, $destination);
            }

            $this->E_file_dinh_kem_model->insert([
                'id_van_ban' => $insert_id,
                'ten_file_goc' => $file['ten_file_goc'],
                'dung_luong' => $file['dung_luong'],
                'duong_dan' => $duongDanMoi, ////////////////////// Custom lại 
                'loai_file' => $file['loai_file'],
                'ngay_tao' => Date('Y-m-d H:i:s'),
                'ngay_sua' => Date('Y-m-d H:i:s'),
                'la_file_ban_hanh' => $file['la_file_ban_hanh'],
                'is_public' => $file['is_public'],
                'noi_dung_trich_xuat' => $file['noi_dung_trich_xuat'],
            ]);
        }


        $vanban = $this->E_van_ban_model->find($insert_id);
        resSuccess($vanban, 'Nhân bản thành công');
    }
}
