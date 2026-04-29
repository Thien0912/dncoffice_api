<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property E_van_ban_model $E_van_ban_model
 * @property E_vb_co_quan_model $E_vb_co_quan_model
 * @property E_vb_khoi_co_quan_model $E_vb_khoi_co_quan_model
 * @property E_ban_hanh_model $E_ban_hanh_model
 * @property E_file_dinh_kem_model $E_file_dinh_kem_model
 * @property Fileupload $fileupload
 * @property Common $common
 * @property Pxl $pxl
 * @property E_xu_ly_model $E_xu_ly_model
 * @property E_don_vi_xu_ly_model $E_don_vi_xu_ly_model
 * @property E_co_quan_model $E_co_quan_model
 * @property E_loai_model $E_loai_model
 * @property E_tag_model $E_tag_model
 * @property CI_Upload $upload
 */



class Vanbannoibo extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['E_van_ban_model', 'E_file_dinh_kem_model', 'E_vb_co_quan_model', 'E_vb_khoi_co_quan_model', 'E_ban_hanh_model', 'E_xu_ly_model', 'E_don_vi_xu_ly_model', 'E_loai_model', 'E_tag_model']);
        $this->load->library(['Validator', 'Fileupload', 'Common', 'Pxl', 'upload']);
    }

    public function index_get()
    {
        $auth = $this->getUserLogin();

        $id_loai_selected = commonRequest('id_loai_selected') ? commonRequest('id_loai_selected') : null;
        $current_year_vbnb = commonRequest('current_year_vbnb') ? commonRequest('current_year_vbnb') : null;
        if ($id_loai_selected) {
            $result = $this->soHieuVanBan($auth, 3, $id_loai_selected, $current_year_vbnb);
            resSuccess($result['sohieuvanban_last'], 'Lấy số hiệu văn bản mới nhất', 200, true, $result['options']);
        }

        $auth = $this->getUserLogin();

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

        $data = $this->E_van_ban_model->getAllVanbannoibo($data['start'], $data['length'], $data['searchValue'], $data['order'], $data['columns'], $data['searchKey'], $data['fromDate'], $data['toDate'], $auth);
        $tags = $this->E_tag_model->get_user_tags($auth['ql_nguoi_dung_id']);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
            'tags' => $tags,
            'sql' => $data['sql'],
        ]);
    }

    public function create_post()
    {
        // resError('Chức năng tạm thời không khả dụng', REST_Controller::HTTP_FORBIDDEN, commonRequest('nguoi_don_vi'));
        $ten_loai_add = commonRequest('ten_loai_add') ? commonRequest('ten_loai_add') : null;
        $hau_to_loai_add = commonRequest('hau_to_loai_add') ? commonRequest('hau_to_loai_add') : null;
        if ($ten_loai_add && $hau_to_loai_add) {
            $loai = $this->E_loai_model->create([
                'ten_loai' => $ten_loai_add,
                'hau_to' => $hau_to_loai_add
            ]);
            resSuccess($loai, 'Thêm thành công');
        }

        $this->load->library(['Validator', 'Fileupload']);

        // $trangThai = commonRequest('trang_thai') ? commonRequest('trang_thai') : null;

        // if (!$trangThai || !in_array($trangThai, ['TAO_MOI'])) {
        //     resBadrequest([], 'Trạng thái không hợp lệ');
        // }

        $ids_khoi_co_quan = commonRequest('id_khoi_co_quan') ? commonRequest('id_khoi_co_quan') : null;
        $ids_co_quan = commonRequest('id_co_quan') ? commonRequest('id_co_quan') : null;
        $nguoi_don_vi = commonRequest('nguoi_don_vi') ? commonRequest('nguoi_don_vi') : null;
        $nguoi_dong_so_huu = commonRequest('nguoi_dong_so_huu') ? commonRequest('nguoi_dong_so_huu') : null;
        if (!$nguoi_don_vi) {
            resBadrequest([], 'Vui lòng chọn người có thể xem văn bản');
        }
        // resError('Chức năng tạm thời không khả dụng', REST_Controller::HTTP_FORBIDDEN, gettype($nguoi_don_vi));


        $auth = $this->getUserLogin();

        $data = [
            'loai_van_ban' => $this->common::VAN_BAN_NOI_BO,
            // 'so_di' => nhảy tự động tính theo năm
            // 'trang_thai' => $this->common::STATUS_VAN_BAN_NOI_BO[$trangThai]['value'],
            'trang_thai' => 1,
            'id_loai' => commonRequest('id_loai') ? commonRequest('id_loai') : null,
            'trich_yeu' => commonRequest('trich_yeu') ? commonRequest('trich_yeu') : null,
            'nguoi_ky' => commonRequest('nguoi_ky') ? commonRequest('nguoi_ky') : null,
            'ngay_ky' => commonRequest('ngay_ky') ? commonRequest('ngay_ky') : null,
            'tra_loi_cv_den' => commonRequest('tra_loi_cv_den') ? commonRequest('tra_loi_cv_den') : null,
            'so_van_ban' => commonRequest('so_van_ban') ? commonRequest('so_van_ban') : null,
            'so_hieu_van_ban' => commonRequest('so_hieu_van_ban') ? commonRequest('so_hieu_van_ban') : null,

            // Đơn vị soạn
            'id_don_vi_soan' => $auth['id_don_vi'],
            'id_hinh_thuc' => commonRequest('id_hinh_thuc') ? commonRequest('id_hinh_thuc') : null,
            'id_nguoi_tao' => $this->getUserLogin()['ql_nguoi_dung_id'],
            // 'nguoi_soan_vb_di' => $this->getUserLogin()['ql_nguoi_dung_id'],
            'nguoi_soan_vb_di' => commonRequest('nguoi_soan_vb_di') ? commonRequest('nguoi_soan_vb_di') : null,
            'linh_vuc' => commonRequest('linh_vuc') ? commonRequest('linh_vuc') : null,
            'id_don_vi' => commonRequest('id_don_vi') ? commonRequest('id_don_vi') : null,
            'noi_luu_tru' => commonRequest('noi_luu_tru') ? commonRequest('noi_luu_tru') : null,
            'id_tinh_chat' => commonRequest('id_tinh_chat') ? commonRequest('id_tinh_chat') : null,
            'id_bao_mat' => commonRequest('id_bao_mat') ? commonRequest('id_bao_mat') : null,



            'ten_van_ban' => commonRequest('ten_van_ban') ? commonRequest('ten_van_ban') : null,
            'ngay_nhan' => commonRequest('ngay_nhan') ? commonRequest('ngay_nhan') : null,
            // 'ngay_ban_hanh' => commonRequest('ngay_ban_hanh') ? commonRequest('ngay_ban_hanh') : null,
            'ngay_ban_hanh' => commonRequest('ngay_ky') ? commonRequest('ngay_ky') : null,
            'thoi_gian_xu_ly' => commonRequest('thoi_gian_xu_ly') ? commonRequest('thoi_gian_xu_ly') : null,
            'id_khoi_co_quan' => null,
            'id_co_quan' => null,
            // 'ngay_tao' => MY_Model
            'id_nguoi_sua' => $auth['ql_nguoi_dung_id'],
            // 'ngay_sua' => MY_Model
            'trang_thai_huy_vb' => 0, //0: không xóa
            'luu_tru_noi_bo' => commonRequest('luu_tru_noi_bo') ? commonRequest('luu_tru_noi_bo') : null,
            'van_ban_chi_doc' => commonRequest('van_ban_chi_doc') ? commonRequest('van_ban_chi_doc') : 0,
        ];

        $rules = [
            // 'ten_van_ban' => 'required',
            // 'so_van_ban' => 'unique:e_van_ban,so_van_ban',
            'id_loai' => 'required|integer',
            'trich_yeu' => 'required',
            // 'ngay_nhan' => 'required|date',
            // 'ngay_ban_hanh' => 'required|date',
            // 'id_trang_thai' => 'required|integer',
            // 'trang_thai' => 'required',
            'thoi_gian_xu_ly' => 'date',
            // 'id_khoi_co_quan' => 'required',
            'id_co_quan' => 'integer',
            'id_hinh_thuc' => 'required|integer',
            'id_tinh_chat' => 'required|integer',
            'id_bao_mat' => 'required|integer',
            'id_don_vi' => 'integer',
            'luu_tru_noi_bo' => 'integer',
            'ngay_ky' => 'required|date',
            'nguoi_ky' => 'required',
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
            'id_hinh_thuc.required' => 'Hình thức bắt buộc',
            'id_tinh_chat.required' => 'Mức độ tính chất bắt buộc',
            'id_bao_mat.required' => 'Mức độ bảo mật bắt buộc',
            'ngay_ky.date' => 'Ngày ký phải đúng định dạng ngày',
            'ngay_ky.required' => 'Ngày ký bắt buộc',
            'nguoi_ky.required' => 'Người ký bắt buộc',

        ];
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();

        //Lưu văn bản đến
        $vb = $this->E_van_ban_model->create($data);

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

        if ($nguoi_don_vi) {
            $ids_nguoi_don_vi_array = explode(',', $nguoi_don_vi);
            $ids_nguoi_dong_so_huu_array = explode(',', $nguoi_dong_so_huu);

            foreach ($ids_nguoi_don_vi_array as $id) {
                $nguoi_dong_so_huu_id = null;
                if (in_array($id, $ids_nguoi_dong_so_huu_array)) {
                    $nguoi_dong_so_huu_id = $id;
                }
                $this->db->insert('e_nguoi_xem_vbnoibo', [
                    'id_van_ban' => $vb['id_van_ban'],
                    'ql_nguoi_dung_id' => $id,
                    'nguoi_dong_so_huu_id' => $nguoi_dong_so_huu_id,
                ]);
            }
        }

        //Upload file đính kèm
        $folderName = 'documents/' . date('Y') . '/' . date('m');
        $uploadedFiles = [];
        if (isset($_FILES['file_dinh_kem_vbnoibo'])) {
            $files = $_FILES['file_dinh_kem_vbnoibo']; // Tên input từ form                        

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
                    'id_van_ban' => $vb['id_van_ban'],
                    'ten_file_goc' => $uploadedFile['file_name'],
                    'dung_luong' => exchangeFromKbToLargerCapacity($uploadedFile['file_size']), //đổi dung lượng lớn hơn MB từ KB
                    'duong_dan' => $uploadedFile['file_path'],
                    'loai_file' => $uploadedFile['file_extension']
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

        $this->createLog('Create', 'Thêm văn bản nội bộ', NULL, $vb, 'e_van_ban');

        $this->db->trans_commit();
        // Trích xuất dữ liệu trong file đính kèm
        call_n8n_trich_xuat($vb['id_van_ban']);

        resSuccess($vb);
    }

    public function show_get($id)
    {
        $auth = $this->getUserLogin();
        $vb = $this->E_van_ban_model
            ->select('e_van_ban.*, e_loai.*, e_hinh_thuc.*, e_tinh_chat.*')
            ->join('e_loai', 'e_loai.id_loai = e_van_ban.id_loai', 'left')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
            ->join('e_hinh_thuc', 'e_hinh_thuc.id_hinh_thuc = e_van_ban.id_hinh_thuc', 'left')
            ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
            ->join('e_bao_mat', 'e_bao_mat.id_bao_mat = e_van_ban.id_bao_mat', 'left')
            ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_van_ban.id_nguoi_tao', 'left')
            ->join('e_don_vi', 'ql_nguoi_dung.id_don_vi = e_don_vi.id_don_vi', 'left')
            ->where('e_van_ban.id_van_ban', $id)
            ->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_NOI_BO)
            ->where('e_don_vi.id_don_vi', $auth['id_don_vi'])
            // ->where('e_van_ban.id_don_vi_soan', $auth['id_don_vi'])
            // ->where('e_van_ban.deleted_at IS NULL')
            ->first();

        if (!$vb) resError('Không tìm thấy văn bản', REST_Controller::HTTP_NOT_FOUND);
        $vb['files'] = $this->E_file_dinh_kem_model->where('id_van_ban', $id)->get();
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
        $vb['nguoi_xem'] = $this->db->select('*')->from('e_nguoi_xem_vbnoibo')->where('id_van_ban', $id)->get()->result_array();

        $gioiHanDoc = $this->db
            ->select('*')
            ->from('e_nguoi_xem_vbnoibo')
            ->where('id_van_ban', $id)
            ->where('nguoi_dong_so_huu_id', $auth['ql_nguoi_dung_id'])
            ->get()
            ->row_array();

        $vb['co_quyen_dong_so_huu'] = $gioiHanDoc ? true : false;
        resSuccess($vb);
    }

    public function checkExitsUpdate($id, $field, $value, $table = 'e_van_ban')
    {
        $vbdi = $this->E_van_ban_model->all();
        foreach ($vbdi as $item) {
            if ($item['id_van_ban'] == $id) {
                continue;
            } else if (($item['id_van_ban'] != $id) && ($item['loai_van_ban'] == 3) && ($item[$field] == $value)) {
                return true;
            }
        }
        return false;
    }

    public function update_post($id)
    {
        $vb = $this->E_van_ban_model
            ->where('id_van_ban', $id)
            // ->where('deleted_at IS NULL')
            ->first();
        $this->load->library(['Validator', 'Fileupload']);

        $trangThai = commonRequest('trang_thai') ? commonRequest('trang_thai') : null;

        // if (!$trangThai || !in_array($trangThai, ['TAO_MOI', 'DE_XUAT_DUYET'])) {
        //     resBadrequest([], 'Trạng thái không hợp lệ');
        // }

        $ids_khoi_co_quan = commonRequest('id_khoi_co_quan') ? commonRequest('id_khoi_co_quan') : null;
        $ids_co_quan = commonRequest('id_co_quan') ? commonRequest('id_co_quan') : null;
        $nguoi_don_vi = commonRequest('nguoi_don_vi') ? commonRequest('nguoi_don_vi') : null;
        $nguoi_dong_so_huu = commonRequest('nguoi_dong_so_huu') ? commonRequest('nguoi_dong_so_huu') : null;
        if (!$nguoi_don_vi) {
            resBadrequest([], 'Vui lòng chọn người có thể xem văn bản');
        }

        // Lấy danh sách người xem hiện tại
        $currentViewers = $this->db->select('ql_nguoi_dung_id')
            ->from('e_nguoi_xem_vbnoibo')
            ->where('id_van_ban', $id)
            ->get()
            ->result_array();

        $currentViewers = array_column($currentViewers, 'ql_nguoi_dung_id'); // [633, 1750, 1936, 624]
        $newViewers = explode(',', $nguoi_don_vi); // danh sách người mới từ form
        $nguoi_dong_so_huu_arr = explode(',', $nguoi_dong_so_huu);

        // 1️⃣ Xóa những người KHÔNG còn trong danh sách mới
        $this->db->where('id_van_ban', $id)
            ->where_not_in('ql_nguoi_dung_id', $newViewers)
            ->delete('e_nguoi_xem_vbnoibo');

        // 2️⃣ Thêm những người MỚI chưa có
        foreach ($newViewers as $userId) {
            if (!in_array($userId, $currentViewers)) {
                $nguoi_dong_so_huu_id = null;
                if (in_array($userId, $nguoi_dong_so_huu_arr)) {
                    $nguoi_dong_so_huu_id = $userId;
                }

                $this->db->insert('e_nguoi_xem_vbnoibo', [
                    'id_van_ban' => $id,
                    'ql_nguoi_dung_id' => $userId,
                    'nguoi_dong_so_huu_id' => $nguoi_dong_so_huu_id,
                ]);
            }
        }

        $currentViewersAfterUpdate = $this->db->select('ql_nguoi_dung_id')
            ->from('e_nguoi_xem_vbnoibo')
            ->where('id_van_ban', $id)
            ->get()
            ->result_array();

        foreach ($currentViewersAfterUpdate as $cv) {
            if (in_array($cv['ql_nguoi_dung_id'], $nguoi_dong_so_huu_arr)) {
                $this->db->where('id_van_ban', $id)
                    ->where('ql_nguoi_dung_id', $cv['ql_nguoi_dung_id'])
                    ->update('e_nguoi_xem_vbnoibo', [
                        'nguoi_dong_so_huu_id' => $cv['ql_nguoi_dung_id']
                    ]);
            }
        }

        $data = [
            'id_loai' => commonRequest('id_loai') ? commonRequest('id_loai') : null,
            'trich_yeu' => commonRequest('trich_yeu') ? commonRequest('trich_yeu') : null,
            'nguoi_ky' => commonRequest('nguoi_ky') ? commonRequest('nguoi_ky') : null,
            'ngay_ky' => commonRequest('ngay_ky') ? commonRequest('ngay_ky') : null,
            'so_van_ban' => commonRequest('so_van_ban') ? commonRequest('so_van_ban') : null,
            'so_hieu_van_ban' => commonRequest('so_hieu_van_ban') ? commonRequest('so_hieu_van_ban') : null,
            'id_hinh_thuc' => commonRequest('id_hinh_thuc') ? commonRequest('id_hinh_thuc') : null,
            // 'id_nguoi_tao' => $this->getUserLogin()['ql_nguoi_dung_id'],
            'linh_vuc' => commonRequest('linh_vuc') ? commonRequest('linh_vuc') : null,
            'id_don_vi' => commonRequest('id_don_vi') ? commonRequest('id_don_vi') : null,
            'noi_luu_tru' => commonRequest('noi_luu_tru') ? commonRequest('noi_luu_tru') : null,
            'id_tinh_chat' => commonRequest('id_tinh_chat') ? commonRequest('id_tinh_chat') : null,
            'id_bao_mat' => commonRequest('id_bao_mat') ? commonRequest('id_bao_mat') : null,
            'ten_van_ban' => commonRequest('ten_van_ban') ? commonRequest('ten_van_ban') : null,
            'ngay_nhan' => commonRequest('ngay_nhan') ? commonRequest('ngay_nhan') : null,
            'ngay_ban_hanh' => commonRequest('ngay_ban_hanh') ? commonRequest('ngay_ban_hanh') : null,
            'thoi_gian_xu_ly' => commonRequest('thoi_gian_xu_ly') ? commonRequest('thoi_gian_xu_ly') : null,
            'id_khoi_co_quan' => null,
            'id_co_quan' => null,
            'id_nguoi_sua' => $this->getUserLogin()['ql_nguoi_dung_id'],
            'trang_thai_huy_vb' => 0, //0: không xóa
            'luu_tru_noi_bo' => commonRequest('luu_tru_noi_bo') ? commonRequest('luu_tru_noi_bo') : null,
            'van_ban_chi_doc' => commonRequest('van_ban_chi_doc') ? commonRequest('van_ban_chi_doc') : 0,
        ];

        $rules = [
            'id_loai' => 'required|integer',
            'trich_yeu' => 'required',
            'thoi_gian_xu_ly' => 'date',
            'id_co_quan' => 'integer',
            'id_hinh_thuc' => 'required|integer',
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
            'id_hinh_thuc.required' => 'Hình thức bắt buộc',
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

        if ($this->checkExitsUpdate($id, 'so_hieu_van_ban', $data['so_hieu_van_ban'])) {
            resError('Trùng lặp số hiệu văn bản');
        }

        $this->db->trans_start();

        //Lưu văn bản đi
        $this->E_van_ban_model->where('id_van_ban', $id)->where('loai_van_ban', $this->common::VAN_BAN_NOI_BO)->update($data);

        // Xử lý file đính kèm cũ
        $fileOld = commonRequest('file_dinh_kem_old') ? json_decode(commonRequest('file_dinh_kem_old'), true) : [];
        $fileOldPath = array_column($fileOld, 'duong_dan');
        $fileVb = $this->E_file_dinh_kem_model
            ->where('id_van_ban', $id)
            // ->where('la_file_ban_hanh', 1)
            ->get();

        $fileVbPath = array_column($fileVb, 'duong_dan');

        $filePathDiff = array_diff($fileVbPath, $fileOldPath);

        foreach ($filePathDiff as $fpd) {
            //Xóa file trong db
            $this->E_file_dinh_kem_model->where('id_van_ban', $id)->where('duong_dan', $fpd)->delete();
            $delete = $this->fileupload->delete($fpd);
        }

        //Upload file đính kèm
        $folderName = 'documents/' . date('Y') . '/' . date('m');
        $uploadedFiles = [];
        if (isset($_FILES['file_dinh_kem_vbnoibo'])) {
            $files = $_FILES['file_dinh_kem_vbnoibo']; // Tên input từ form                        

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

            $filesOld = $this->E_file_dinh_kem_model->where('id_van_ban', $id)->get();

            $uploadedFull = true;
            foreach ($uploadedFiles as $uploadedFile) {
                if ($uploadedFile['success']) {
                    $this->E_file_dinh_kem_model->create([
                        'id_van_ban' => $id,
                        'ten_file_goc' => $uploadedFile['file_name'],
                        'dung_luong' => exchangeFromKbToLargerCapacity($uploadedFile['file_size']), //đổi dung lượng lớn hơn MB từ KB
                        'duong_dan' => $uploadedFile['file_path'],
                        'loai_file' => $uploadedFile['file_extension'],
                        'is_public' => 1
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

        $newVb = $this->E_van_ban_model->find($id);
        //Ghi log
        $this->createLog('update', 'Cập nhật văn bản nội bộ', $vb, $newVb, 'e_van_ban');

        $this->db->trans_commit();
        call_n8n_trich_xuat($vb['id_van_ban']);

        resSuccess($newVb);
    }

    public function delete_post()
    {
        $ids = commonRequest('ids');
        $this->db->trans_start();
        $vanban = $this->E_van_ban_model
            ->whereIn('id_van_ban', $ids)->where('deleted_at IS NULL')->get();
        if (count($ids) != count($vanban)) {
            resError('Có văn bản không tồn tại');
        }
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

        // $this->E_van_ban_model->where('id_don_vi_soan', $this->getUserLogin()['id_don_vi'])->whereIn('id_van_ban', $ids)->delete();
        // foreach ($files as $file) {
        //     $this->fileupload->delete($file['duong_dan']);
        // }

        //Xóa mềm
        $this->E_van_ban_model->whereIn('id_van_ban', $ids)->update([
            'deleted_at' => date('Y-m-d H:i:s')
        ]);

        $this->createLog('delete', 'Xóa văn bản nội bộ', $vanban,  null, 'e_van_ban');
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
            // 'noi_nhan' => 'Nơi nhận',
            'nguoi_ky' => 'Người ký'

        ];
        return $cols;
    }


    public function export_get()
    {
        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;
        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];
        $fromDate = commonRequest('fromDate') ? commonRequest('fromDate') : null;
        $toDate = commonRequest('toDate') ? commonRequest('toDate') : null;

        $data = $this->E_van_ban_model->getListExportVanbannoibo($start, $length, $searchValue, $searchKey, $fromDate, $toDate, $this->getUserLogin());

        $titles = $this->getExcelColumn();
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $sheet = $objPHPExcel->getActiveSheet();

        // Đặt tiêu đề cột vào hàng đầu tiên dựa trên mảng  $titles
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
        $sheet->setCellValue('A1', 'Công văn nội bộ'); // Thêm cột thông báo

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
                if (in_array($key, ['noi_dung', 'nguoi_ky'])) {
                    // Định dạng chuỗi cho các cột có khả năng là số dài
                    $sheet->setCellValueExplicit($column . $row, isset($item[$key]) ? $item[$key] : '', PHPExcel_Cell_DataType::TYPE_STRING);
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
        $filename = 'vanbannoibo_' . time() . '.xlsx'; // Tên file kèm timestamp để tránh trùng lặp
        $filePath = $directory . $filename;
        // Kiểm tra và tạo thư mục nếu chưa tồn tại
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);

        if (file_exists($filePath)) {
            $this->createLog('Export', 'Export danh sách công văn nội bộ', NULL, 'Export danh sách công văn nội bộ', 'e_van_ban');
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

    public function update_by_key_post($id)
    {
        $vanban = $this->E_van_ban_model->where('id_van_ban', $id)->where('loai_van_ban', $this->common::VAN_BAN_NOI_BO)->where('deleted_at IS NULL')->first();
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
        $this->createLog('Update', 'Cập nhật văn bản nội bộ', $vanban, $this->E_van_ban_model->find($id), 'e_van_ban');
        resSuccess(null, 'Cập nhật thành công', REST_INSTANCE_Controller::HTTP_OK);
    }

    public function import_post()
    {
        try {
            $auth = $this->getUserLogin();
            $stop = false;
            $message = '';
            $dataList = [];
            $file_import = commonRequest('file_excel');

            if ($file_import) {

                $config['upload_path'] = 'uploads/excel/vanbannoibo/'; // Thư mục để lưu file
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

                    $highestColumn = 'I';
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
                        // 'ten_co_quan',
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

                                    $ngaybanhanh = $l['ngay_ban_hanh'];

                                    $ngaybanhanhFormat = DateTime::createFromFormat('d/m/Y', $ngaybanhanh)->format('Y-m-d');

                                    $dataInsert = [
                                        'so_hieu_van_ban' => $l['so_hieu_van_ban'],
                                        'loai_van_ban' => $this->common::VAN_BAN_NOI_BO,
                                        'trich_yeu' => $l['trich_yeu'],
                                        // 'ngay_nhan' => $ngaynhanFormat,
                                        'ngay_ban_hanh' => $ngaybanhanhFormat,
                                        // Đơn vị soạn
                                        'id_don_vi_soan' => $auth['id_don_vi'],
                                        // 'ngay_ky' => $ngaybanhanhFormat,
                                        'nguoi_ky' => $l['nguoi_ky'],
                                        'ghi_chu' => $l['ghi_chu'],
                                        // 'id_co_quan' => $coquan['id_co_quan'],
                                        'trang_thai' => $this->common::STATUS_VAN_BAN_NOI_BO['TAO_MOI']['value'],

                                        'id_nguoi_tao' => $this->getUserLogin()['ql_nguoi_dung_id'],
                                        'id_nguoi_sua' => $this->getUserLogin()['ql_nguoi_dung_id'],
                                        'ngay_tao' => date('Y-m-d H:i:s'),
                                        'ngay_sua' => date('Y-m-d H:i:s'),
                                    ];

                                    $dataInsertArray[] = $dataInsert;

                                    $id_van_ban = $this->E_van_ban_model->insert($dataInsert);

                                    // if (trim($l['ten_co_quan']) != "") {
                                    //     $arrayCoQuan = explode(",", $l['ten_co_quan']);
                                    //     if (!empty($arrayCoQuan)) {
                                    //         foreach ($arrayCoQuan as $item) {
                                    //             if (trim($item) != "") {
                                    //                 $coquan = $this->E_co_quan_model->where('ten_co_quan', trim($item))->first();

                                    //                 if (!$coquan) {
                                    //                     $id_coquan = $this->E_co_quan_model->insert([
                                    //                         'ten_co_quan' => trim($item)
                                    //                     ]);

                                    //                     $this->E_vb_co_quan_model->create([
                                    //                         'id_van_ban' => $id_van_ban,
                                    //                         'id_co_quan' => $id_coquan
                                    //                     ]);
                                    //                 } else {
                                    //                     $this->E_vb_co_quan_model->create([
                                    //                         'id_van_ban' => $id_van_ban,
                                    //                         'id_co_quan' => $coquan['id_co_quan']
                                    //                     ]);
                                    //                 }
                                    //             }
                                    //         }

                                    //         $this->E_van_ban_model->where('id_van_ban', $id_van_ban)->update([
                                    //             'trang_thai' => $this->common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value']
                                    //         ]);
                                    //     }
                                    // }
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
                            $this->createLog('Import', 'Import văn bản nội bộ', NULL, $dataInsert, 'e_van_ban');
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

    public function update_files_post($id)
    {
        $currentFiles = $this->E_file_dinh_kem_model->where('id_van_ban', $id)->get();
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
                        'is_public' => 1
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

    public function search_files_post()
    {
        $data = [
            'keyword' => commonRequest('keyword') ?? '',
            'page' => commonRequest('page') ?? 1,
            'per_page' => commonRequest('per_page') ?? 10,
            'range' => commonRequest('range') ?? 'today',
        ];
        $files = $this->E_van_ban_model->getFiles_byLoaiVanBan(3, $data);
        resSuccess($files, 'Tìm kiếm file đính kèm thành công');
    }
}
