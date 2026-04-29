<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property E_van_ban_model $E_van_ban_model
 * @property E_file_dinh_kem_model $E_file_dinh_kem_model
 * @property E_but_phe_model $E_but_phe_model
 * @property E_xu_ly_model $E_xu_ly_model
 * @property E_don_vi_xu_ly_model $E_don_vi_xu_ly_model
 * @property E_don_vi_model $E_don_vi_model
 * @property E_bao_cao_model $E_bao_cao_model
 * @property Fileupload $fileupload
 * @property Common $common
 * @property Pxl $pxl
 * @property CI_Upload $upload
 * @property E_co_quan_model $E_co_quan_model
 * @property Ql_thong_bao_model $Ql_thong_bao_model
 * @property E_tag_model $E_tag_model
 */



class Vanbanden extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->inSegment([
            'vanbanden.tochuchanhchinhxembaocaophanhoi'
        ])) {
            $this->permissionMiddleware();
        }

        $this->load->helper('url');
        $this->load->model(['E_van_ban_model', 'E_file_dinh_kem_model', 'E_but_phe_model', 'E_xu_ly_model', 'E_don_vi_xu_ly_model', 'E_don_vi_model', 'E_bao_cao_model', 'E_co_quan_model', 'Ql_thong_bao_model', 'E_tag_model']);
        $this->load->library(['Validator', 'Fileupload', 'Common', 'Pxl', 'upload']);
    }

    public function index_get()
    {
        $auth = $this->getUserLogin();

        $data = [
            'start' => commonRequest('start') ?? 0,
            'length' => commonRequest('length') ?? 10,
            'searchValue' => commonRequest('searchValue') ?? null,
            'order' => commonRequest('order') ?? [],
            'columns' => commonRequest('columns') ?? [],
            'searchKey' => commonRequest('searchKey') ? commonRequest('searchKey') : [],
            'fromDate' => commonRequest('fromDate') ? commonRequest('fromDate') : null,
            'toDate' => commonRequest('toDate') ? commonRequest('toDate') : null
        ];
        $response = $this->E_van_ban_model->getAllVanbanden($data['start'], $data['length'], $data['searchValue'], $data['order'], $data['columns'], $data['searchKey'],  $data['fromDate'], $data['toDate'], $auth);
        $tags = $this->E_tag_model->get_user_tags($auth['ql_nguoi_dung_id']);
        resSuccess($response['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $response['recordsTotal'],
            'recordsFiltered' => $response['recordsFiltered'],
            'tags' => $tags,
            'tren_5_ngay' => $response['tren5ngay'],
            'duoi_5_ngay' => $response['duoi5ngay'],
            'hom_nay' => $response['homnay'],
            'qua_han' => $response['quahan'],
            // 'sql' => $response['sql'],
        ]);
    }

    public function create_post()
    {
        $trangThai = commonRequest('trang_thai') ? commonRequest('trang_thai') : null;

        if (!$trangThai || !in_array($trangThai, ['TIEP_NHAN', 'CHO_LANH_DAO_BUT_PHE'])) {
            resBadrequest([], 'Trạng thái không hợp lệ');
        }

        $data = [
            // 'ten_van_ban' => commonRequest('ten_van_ban') ? commonRequest('ten_van_ban') : null,
            'so_van_ban' => commonRequest('so_van_ban') ? commonRequest('so_van_ban') : null,
            'so_van_ban_hau_to' => commonRequest('so_van_ban_hau_to') ? strtoupper(trim(commonRequest('so_van_ban_hau_to'))) : null,
            'so_hieu_van_ban' => commonRequest('so_hieu_van_ban') ? commonRequest('so_hieu_van_ban') : null,
            'loai_van_ban' => 1,
            'id_loai' => commonRequest('id_loai') ? commonRequest('id_loai') : null,
            'trich_yeu' => commonRequest('trich_yeu') ? commonRequest('trich_yeu') : null,
            'ngay_nhan' => commonRequest('ngay_nhan') ? commonRequest('ngay_nhan') : null,
            'ngay_ban_hanh' => commonRequest('ngay_ban_hanh') ? commonRequest('ngay_ban_hanh') : null,
            // 'id_trang_thai' => commonRequest('id_trang_thai') ? commonRequest('id_trang_thai') : null,
            'trang_thai' => $this->common::STATUS_VAN_BAN_DEN[$trangThai]['value'],
            'thoi_gian_xu_ly' => commonRequest('thoi_gian_xu_ly') ? commonRequest('thoi_gian_xu_ly') : null,
            'id_khoi_co_quan' => commonRequest('id_khoi_co_quan') ? commonRequest('id_khoi_co_quan') : null,
            'id_co_quan' =>  commonRequest('id_co_quan') ? commonRequest('id_co_quan') : null,
            'id_hinh_thuc' => commonRequest('id_hinh_thuc') ? commonRequest('id_hinh_thuc') : null,
            'linh_vuc' => commonRequest('linh_vuc') ? commonRequest('linh_vuc') : null,
            'id_tinh_chat' => commonRequest('id_tinh_chat') ? commonRequest('id_tinh_chat') : null,
            'id_bao_mat' => commonRequest('id_bao_mat') ? commonRequest('id_bao_mat') : null,
            'id_don_vi' => commonRequest('id_don_vi') ? commonRequest('id_don_vi') : null,
            'noi_luu_tru' => commonRequest('noi_luu_tru') ? commonRequest('noi_luu_tru') : null,
            'id_nguoi_tao' => $this->getUserLogin()['ql_nguoi_dung_id'],
            // 'ngay_tao' => MY_Model
            'id_nguoi_sua' => $this->getUserLogin()['ql_nguoi_dung_id'],
            // 'ngay_sua' => MY_Model
            'trang_thai_huy_vb' => 0, //0: không xóa
            'luu_tru_noi_bo' => commonRequest('luu_tru_noi_bo') ? commonRequest('luu_tru_noi_bo') : null,
            'nguoi_ky' => commonRequest('nguoi_ky') ? commonRequest('nguoi_ky') : null,
            'ngay_ky' => commonRequest('ngay_ky') ? commonRequest('ngay_ky') : null,
            'van_ban_chi_doc' => commonRequest('van_ban_chi_doc') ? commonRequest('van_ban_chi_doc') : 0,
            'ghi_chu' => commonRequest('ghi_chu') ? commonRequest('ghi_chu') : null
        ];

        $rules = [
            // 'ten_van_ban' => 'required',
            'so_van_ban' => 'required|integer',
            'id_loai' => 'required|integer',
            'trich_yeu' => 'required',
            // 'ngay_nhan' => 'required|date',
            'ngay_nhan' => 'date',
            // 'ngay_ban_hanh' => 'required|date',
            'ngay_ban_hanh' => 'date',
            // 'id_trang_thai' => 'required|integer',
            // 'trang_thai' => 'required',
            'thoi_gian_xu_ly' => 'date',
            // 'id_khoi_co_quan' => 'required|integer',
            'id_co_quan' => 'integer',
            // 'id_hinh_thuc' => 'required|integer',
            // 'id_tinh_chat' => 'required|integer',
            // 'id_bao_mat' => 'required|integer',
            // 'id_don_vi' => 'integer',
            'luu_tru_noi_bo' => 'integer',
            // 'nguoi_ky' => 'required',
            'ngay_ky' => 'date'
        ];

        $customMessages = [
            // 'ten_van_ban.required' => 'Tên văn bản bắt buộc nhập',
            'so_van_ban.required' => 'Số đến bắt buộc nhập',
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
            'nguoi_ky.required' => 'Người ký bắt buộc nhập',
            'ngay_ky.required' => 'Ngày ký bắt buộc nhập',
            'ngay_ky.date' => 'Ngày ký phải đúng định dạng ngày'
        ];
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();

        //Lưu văn bản đến
        $vb = $this->E_van_ban_model->create($data);

        //ghi log
        $this->createLog('create', 'Tạo văn bản đến', null, $vb, 'e_van_ban');

        //Upload file đính kèm
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

        $this->db->trans_commit();

        resSuccess($vb);
    }

    public function show_get($id)
    {
        $vb = $this->E_van_ban_model
            ->select('e_van_ban.*, DATE_FORMAT(e_van_ban.thoi_gian_xu_ly, "%Y-%m-%d")  AS thoi_gian_xu_ly')
            ->join('e_loai', 'e_loai.id_loai = e_van_ban.id_loai', 'left')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
            ->join('e_hinh_thuc', 'e_hinh_thuc.id_hinh_thuc = e_van_ban.id_hinh_thuc', 'left')
            ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
            ->join('e_bao_mat', 'e_bao_mat.id_bao_mat = e_van_ban.id_bao_mat', 'left')
            ->where('e_van_ban.id_van_ban', $id)
            ->where('e_van_ban.deleted_at IS NULL')
            ->first();
        if (!$vb) resError('Không tìm thấy văn bản', REST_Controller::HTTP_NOT_FOUND);
        $vb['but_phe'] = $this->E_but_phe_model->where('id_van_ban', $id)->first();
        $vb['files'] = $this->E_file_dinh_kem_model->where('id_van_ban', $id)->get();
        $xuly = $this->E_xu_ly_model->where('id_van_ban', $id)->first();
        if ($xuly) {
            $xuly['don_vi_chinh'] = $this->E_don_vi_xu_ly_model->where('id_xu_ly', $xuly['id_xu_ly'])->where('don_vi_xu_ly_chinh IS NOT NULL')->get();
            $xuly['don_vi_phoi_hop'] = $this->E_don_vi_xu_ly_model->where('id_xu_ly', $xuly['id_xu_ly'])->where('don_vi_xu_ly_chinh IS NULL')->get();
        }
        $vb['xu_ly'] = $xuly;
        resSuccess($vb);
    }

    public function update_post($id)
    {
        $vanban = $this->E_van_ban_model->where('id_van_ban', $id)->where('deleted_at IS NULL')->first();
        if (!$vanban) resError('Không tìm thấy văn bản', REST_Controller::HTTP_NOT_FOUND);

        $trangThai = commonRequest('trang_thai') ? commonRequest('trang_thai') : null;

        if (!$trangThai || !in_array($trangThai, ['TIEP_NHAN', 'CHO_LANH_DAO_BUT_PHE', 'DA_BUT_PHE', 'CHO_XU_LY', 'DA_XU_LY', 'LUU_TRU', 'CHUA_PHAN_HOI', 'DA_PHAN_HOI'])) {
            resBadrequest([], 'Trạng thái không hợp lệ');
        }

        $data = [
            // 'ten_van_ban' => commonRequest('ten_van_ban') ? commonRequest('ten_van_ban') : null,
            'so_van_ban' => commonRequest('so_van_ban') ? commonRequest('so_van_ban') : null,
            'so_van_ban_hau_to' => commonRequest('so_van_ban_hau_to') ? strtoupper(trim(commonRequest('so_van_ban_hau_to'))) : null,
            'so_hieu_van_ban' => commonRequest('so_hieu_van_ban') ? commonRequest('so_hieu_van_ban') : null,
            'loai_van_ban' => 1,
            'id_loai' => commonRequest('id_loai') ? commonRequest('id_loai') : null,
            'trich_yeu' => commonRequest('trich_yeu') ? commonRequest('trich_yeu') : null,
            'ngay_nhan' => commonRequest('ngay_nhan') ? commonRequest('ngay_nhan') : null,
            'ngay_ban_hanh' => commonRequest('ngay_ban_hanh') ? commonRequest('ngay_ban_hanh') : null,
            // 'id_trang_thai' => commonRequest('id_trang_thai') ? commonRequest('id_trang_thai') : null,
            'trang_thai' => $this->common::STATUS_VAN_BAN_DEN[$trangThai]['value'],
            'thoi_gian_xu_ly' => commonRequest('thoi_gian_xu_ly') ? commonRequest('thoi_gian_xu_ly') : null,
            'id_khoi_co_quan' => commonRequest('id_khoi_co_quan') ? commonRequest('id_khoi_co_quan') : null,
            'id_co_quan' =>  commonRequest('id_co_quan') ? commonRequest('id_co_quan') : null,
            'id_hinh_thuc' => commonRequest('id_hinh_thuc') ? commonRequest('id_hinh_thuc') : null,
            'linh_vuc' => commonRequest('linh_vuc') ? commonRequest('linh_vuc') : null,
            'id_tinh_chat' => commonRequest('id_tinh_chat') ? commonRequest('id_tinh_chat') : null,
            'id_bao_mat' => commonRequest('id_bao_mat') ? commonRequest('id_bao_mat') : null,
            'id_don_vi' => commonRequest('id_don_vi') ? commonRequest('id_don_vi') : null,
            'noi_luu_tru' => commonRequest('noi_luu_tru') ? commonRequest('noi_luu_tru') : null,
            // 'id_nguoi_tao' => $this->getUserLogin()['ql_nguoi_dung_id'],
            // 'ngay_tao' => MY_Model
            'id_nguoi_sua' => $this->getUserLogin()['ql_nguoi_dung_id'],
            // 'ngay_sua' => MY_Model
            'trang_thai_huy_vb' => 0, //0: không xóa
            'luu_tru_noi_bo' => commonRequest('luu_tru_noi_bo') ? commonRequest('luu_tru_noi_bo') : null,
            'nguoi_ky' => commonRequest('nguoi_ky') ? commonRequest('nguoi_ky') : null,
            'ngay_ky' => commonRequest('ngay_ky') ? commonRequest('ngay_ky') : null,
            'van_ban_chi_doc' => commonRequest('van_ban_chi_doc') ? commonRequest('van_ban_chi_doc') : 0,
            'ghi_chu' => commonRequest('ghi_chu') ? commonRequest('ghi_chu') : null
        ];

        $rules = [
            // 'ten_van_ban' => 'required',
            'so_van_ban' => 'required|integer',
            'id_loai' => 'required|integer',
            'trich_yeu' => 'required',
            'ngay_nhan' => 'required|date',
            'ngay_ban_hanh' => 'required|date',
            // 'id_trang_thai' => 'required|integer', 
            // 'trang_thai' => 'required',
            'thoi_gian_xu_ly' => 'date',
            // 'id_khoi_co_quan' => 'required|integer',
            'id_co_quan' => 'integer',
            'id_hinh_thuc' => 'required|integer',
            // 'id_tinh_chat' => 'required|integer',
            // 'id_bao_mat' => 'required|integer',
            'id_don_vi' => 'integer',
            'luu_tru_noi_bo' => 'integer',
            // 'nguoi_ky' => 'required',
            // 'ngay_ky' => 'required|date'
        ];

        $customMessages = [
            // 'ten_van_ban.required' => 'Tên văn bản bắt buộc nhập',
            'so_van_ban.required' => 'Số đến bắt buộc nhập',
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
            // 'nguoi_ky.required' => 'Người ký bắt buộc nhập',
            // 'ngay_ky.required' => 'Ngày ký bắt buộc nhập',
            // 'ngay_ky.date' => 'Ngày ký phải đúng định dạng ngày'
        ];
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();

        //Cập nhật
        $this->E_van_ban_model->where('id_van_ban', $id)->where('loai_van_ban', $this->common::VAN_BAN_DEN)->update($data);

        // dd($_FILES);
        //Xử lý file cũ
        $fileOld = commonRequest('file_dinh_kem_old') ? json_decode(commonRequest('file_dinh_kem_old'), true) : [];
        $fileOldPath = array_column($fileOld, 'duong_dan');
        $fileVb = $this->E_file_dinh_kem_model->where('id_van_ban', $id)->get();

        $fileVbPath = array_column($fileVb, 'duong_dan');

        $filePathDiff = array_diff($fileVbPath, $fileOldPath);

        foreach ($filePathDiff as $fpd) {
            //Xóa file trong db
            $this->E_file_dinh_kem_model->where('id_van_ban', $id)->where('duong_dan', $fpd)->delete();
            $delete = $this->fileupload->delete($fpd);
        }
        //Upload file đính kèm
        // $folderName = 'documents/';
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

        // $filesOld = $this->E_file_dinh_kem_model->where('id_van_ban', $id)->get();

        $uploadedFull = true;
        foreach ($uploadedFiles as $uploadedFile) {
            if ($uploadedFile['success']) {
                $this->E_file_dinh_kem_model->create([
                    'id_van_ban' => $vanban['id_van_ban'],
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



        $newVb = $this->E_van_ban_model->find($id);
        //Ghi log
        $this->createLog('update', 'Cập nhật văn bản đến', $vanban, $newVb, 'e_van_ban');

        $this->db->trans_commit();

        resSuccess($newVb);
    }

    public function delete_post()
    {
        $ids = commonRequest('ids');
        $this->db->trans_start();
        $vanban = $this->E_van_ban_model->whereIn('id_van_ban', $ids)->where('deleted_at IS NULL')->get();
        if (count($ids) != count($vanban)) {
            resError('Có văn bản không tồn tại');
        }

        // $files = $this->E_file_dinh_kem_model->whereIn('id_van_ban', $ids)->get();
        // $this->E_file_dinh_kem_model->whereIn('id_van_ban', $ids)->delete();

        // $this->E_bao_cao_model->whereIn('id_van_ban', $ids)->delete();


        // $xuly = $this->E_xu_ly_model->whereIn('id_van_ban', $ids)->get();
        // $xulyIds = array_column($xuly, 'id_xu_ly');

        // if (!empty($xulyIds)) {
        //     $this->E_don_vi_xu_ly_model->whereIn('id_xu_ly', $xulyIds)->delete();
        // }
        // $this->E_xu_ly_model->whereIn('id_van_ban', $ids)->delete();

        // $this->db->where_in('id_van_ban', $ids)->delete('e_vb_co_quan');
        // $this->db->where_in('id_van_ban', $ids)->delete('e_vb_khoi_co_quan');

        // $this->db->where_in('id_van_ban', $ids)->delete('e_ban_hanh');

        // $this->E_van_ban_model->whereIn('id_van_ban', $ids)->delete();

        // foreach ($files as $file) {
        //     $deleteFile = $this->fileupload->delete($file['duong_dan']);
        // }
        //Xóa mềm

        $this->E_van_ban_model->whereIn('id_van_ban', $ids)->update([
            'deleted_at' => date('Y-m-d H:i:s')
        ]);

        $this->createLog('delete', 'Xóa văn bản đến', $vanban,  null, 'e_van_ban');
        $this->db->trans_commit();
        resSuccess(null, 'Xóa thành công');
    }

    public function tao_butphe_post($id)
    {
        $vb = $this->E_van_ban_model->where('id_van_ban', $id)->where('deleted_at IS NULL')->first();
        if (!$vb) resError('Không tìm thấy văn bản', REST_Controller::HTTP_NOT_FOUND);

        $trangThai = commonRequest('trang_thai') ? commonRequest('trang_thai') : null;
        if (!in_array($trangThai, ['DA_BUT_PHE', 'LUU_TRU'])) {
            resError('Trạng thái bút phê không hợp lệ');
        }

        $butphe = $this->E_but_phe_model->where('id_van_ban', $id)->first();
        if ($butphe) {
            resError('Văn bản đã được bút phê');
        }
        $data = [
            'id_van_ban' => $id,
            // 'id_nguoi_but_phe' => $this->getUserLogin()['ql_nguoi_dung_id'],
            'id_nguoi_but_phe' => commonRequest('id_nguoi_but_phe') ? commonRequest('id_nguoi_but_phe') : null,
            'ngay_but_phe' => commonRequest('ngay_but_phe') ? commonRequest('ngay_but_phe') : null,
            'noi_dung_but_phe' =>  commonRequest('noi_dung_but_phe') ? commonRequest('noi_dung_but_phe') : null,
            'id_nguoi_tao' => $this->getUserLogin()['ql_nguoi_dung_id'],
            'id_nguoi_sua' => $this->getUserLogin()['ql_nguoi_dung_id'],
        ];
        $rules = [
            'id_nguoi_but_phe' => 'required|integer',
            'ngay_but_phe' => 'required|date',
            'noi_dung_but_phe' => 'required'
        ];

        $customMessages = [
            'id_nguoi_but_phe.required' => 'Vui lòng chọn người bút phê',
            'ngay_but_phe.required' => 'Vui lòng chọn ngày bút phê',
            'ngay_but_phe.date' => 'Ngày bút phê không đúng định dạng',
            'noi_dung_but_phe.required' => 'Vui lòng nhập nội dung bút phê'
        ];

        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }
        // $folderName = 'documents';
        $folderName = 'file-butphe/' . date('Y') . '/' . date('m');
        if (isset($_FILES['file_but_phe'])) {
            $files = $_FILES['file_but_phe']; // Tên input từ form                        
            $file = [
                'name'     => $files['name'],
                'type'     => $files['type'],
                'tmp_name' => $files['tmp_name'],
                'error'    => $files['error'],
                'size'     => $files['size'],
            ];
            $result = $this->fileupload->upload($file, $folderName);
            $data['file_but_phe'] = $result['file_path'];
        }
        $this->db->trans_start();
        $butphe = $this->E_but_phe_model->create($data);

        //Đổi trạng thái văn bản
        $this->E_van_ban_model->where('id_van_ban', $id)->update([
            'trang_thai' => $this->common::STATUS_VAN_BAN_DEN[$trangThai]['value']
        ]);
        $newVb = $this->E_van_ban_model->find($id);
        $newVb['but_phe'] = $butphe;
        $this->createLog('but_phe', 'Bút phê văn bản đến', $vb, $newVb, 'e_van_ban, e_but_phe');

        $this->db->trans_commit();
        resSuccess(null, 'Bút phê thành công');
    }

    public function butphe_get()
    {
        $data = [
            'start' => commonRequest('start') ?? 0,
            'length' => commonRequest('length') ?? 10,
            'searchValue' => commonRequest('searchValue') ?? null,
            'order' => commonRequest('order') ?? [],
            'columns' => commonRequest('columns') ?? [],
            'searchKey' => commonRequest('searchKey') ? commonRequest('searchKey') : [],
            'fromDate' => commonRequest('fromDate') ? commonRequest('fromDate') : null,
            'toDate' => commonRequest('toDate') ? commonRequest('toDate') : null
        ];
        $response = $this->E_van_ban_model->getAllButPhe($data['start'], $data['length'], $data['searchValue'], $data['order'], $data['columns'], $data['searchKey'],  $data['fromDate'], $data['toDate']);

        resSuccess($response['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $response['recordsTotal'],
            'recordsFiltered' => $response['recordsFiltered'],
            'tren_5_ngay' => $response['tren5ngay'],
            'duoi_5_ngay' => $response['duoi5ngay'],
            'hom_nay' => $response['homnay'],
            'qua_han' => $response['quahan'],
            // 'sql' => $response['sql'],
        ]);
    }

    public function update_butphe_post($id)
    {
        $vb = $this->E_van_ban_model->where('id_van_ban', $id)->where('deleted_at IS NULL')->first();
        if (!$vb) resError('Không tìm thấy văn bản', REST_Controller::HTTP_NOT_FOUND);

        $trangThai = commonRequest('trang_thai') ? commonRequest('trang_thai') : null;
        if (!in_array($trangThai, ['DA_BUT_PHE', 'LUU_TRU'])) {
            resError('Trạng thái bút phê không hợp lệ');
        }
        $butphe = $this->E_but_phe_model->where('id_van_ban', $id)->first();
        if (!$butphe) {
            resError('Văn bản chưa được bút phê');
        }
        $vb['but_phe'] = $butphe;
        $data = [
            'id_van_ban' => $id,
            'id_nguoi_but_phe' => commonRequest('id_nguoi_but_phe') ? commonRequest('id_nguoi_but_phe') : null,
            'ngay_but_phe' => commonRequest('ngay_but_phe') ? commonRequest('ngay_but_phe') : null,
            'noi_dung_but_phe' =>  commonRequest('noi_dung_but_phe') ? commonRequest('noi_dung_but_phe') : null,
            'id_nguoi_sua' => $this->getUserLogin()['ql_nguoi_dung_id'],
        ];
        $rules = [
            'id_nguoi_but_phe' => 'required|integer',
            'ngay_but_phe' => 'required|date',
            'noi_dung_but_phe' => 'required'
        ];

        $customMessages = [
            'id_nguoi_but_phe.required' => 'Vui lòng chọn người bút phê',
            'ngay_but_phe.required' => 'Vui lòng chọn ngày bút phê',
            'ngay_but_phe.date' => 'Ngày bút phê không đúng định dạng',
            'noi_dung_but_phe.required' => 'Vui lòng nhập nội dung bút phê'
        ];

        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }
        // $folderName = 'documents';
        $folderName = 'file-butphe/' . date('Y') . '/' . date('m');
        if (isset($_FILES['file_but_phe'])) {
            $uploadedFile = $this->fileupload->upload($_FILES['file_but_phe'], $folderName);
            if (!$uploadedFile['success']) {
                resBadrequest([
                    'file_but_phe' => [
                        'Không thể tải lên file'
                    ]
                ]);
            } else {
                $data['file_but_phe'] = $uploadedFile['file_path'];
                $filepathOld = $butphe['file_but_phe'];
                $this->fileupload->delete($filepathOld);
            }
        }
        $this->db->trans_start();
        // $this->E_but_phe_model->create($data);
        $this->E_but_phe_model->where('id_van_ban', $id)->update($data);

        //Đổi trạng thái văn bản
        $this->E_van_ban_model->where('id_van_ban', $id)->update([
            'trang_thai' => $this->common::STATUS_VAN_BAN_DEN[$trangThai]['value']
        ]);

        $newVb = $this->E_van_ban_model->find($id);
        $newVb['but_phe'] = $this->E_but_phe_model->where('id_van_ban', $id)->first();

        $this->createLog('but_phe', 'Bút phê văn bản đến', $vb, $newVb, 'e_van_ban, e_but_phe');

        $this->db->trans_commit();
        resSuccess(null, 'Cập nhật thành công');
    }

    public function xulyvanban_get()
    {
        $data = [
            'start' => commonRequest('start') ?? 0,
            'length' => commonRequest('length') ?? 10,
            'searchValue' => commonRequest('searchValue') ?? null,
            'order' => commonRequest('order') ?? [],
            'columns' => commonRequest('columns') ?? [],
            'searchKey' => commonRequest('searchKey') ? commonRequest('searchKey') : [],
            'fromDate' => commonRequest('fromDate') ? commonRequest('fromDate') : null,
            'toDate' => commonRequest('toDate') ? commonRequest('toDate') : null
        ];
        $response = $this->E_van_ban_model->getAllXuLyVanBan($data['start'], $data['length'], $data['searchValue'], $data['order'], $data['columns'], $data['searchKey'],  $data['fromDate'], $data['toDate']);

        resSuccess($response['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $response['recordsTotal'],
            'recordsFiltered' => $response['recordsFiltered'],
            'tren_5_ngay' => $response['tren5ngay'],
            'duoi_5_ngay' => $response['duoi5ngay'],
            'hom_nay' => $response['homnay'],
            'qua_han' => $response['quahan'],
            // 'sql' => $response['sql'],
        ]);
    }

    public function baocaophanhoi_get()
    { 
        $data = [
            'start' => commonRequest('start') ?? 0,
            'length' => commonRequest('length') ?? 10,
            'searchValue' => commonRequest('searchValue') ?? null,
            'order' => commonRequest('order') ?? [],
            'columns' => commonRequest('columns') ?? [],
            'searchKey' => commonRequest('searchKey') ? commonRequest('searchKey') : [],
            'fromDate' => commonRequest('fromDate') ? commonRequest('fromDate') : null,
            'toDate' => commonRequest('toDate') ? commonRequest('toDate') : null
        ];
        $response = $this->E_van_ban_model->getAllBaoCaoPhanHoi($data['start'], $data['length'], $data['searchValue'], $data['order'], $data['columns'], $data['searchKey'],  $data['fromDate'], $data['toDate']);

        resSuccess($response['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $response['recordsTotal'],
            'recordsFiltered' => $response['recordsFiltered'],
            'tren_5_ngay' => $response['tren5ngay'],
            'duoi_5_ngay' => $response['duoi5ngay'],
            'hom_nay' => $response['homnay'],
            'qua_han' => $response['quahan'],
            // 'sql' => $response['sql'],
        ]);
    }

    public function tochuchanhchinhxembaocaophanhoi_post($baocaoId)
    {
        $this->E_van_ban_model->toChucHanhChinhXemBaoCaoPhanHoi($baocaoId, $this->getUserLogin());
        resSuccess();
    }

    public function xuly_vanban_post($id)
    {
        $vb = $this->E_van_ban_model->where('loai_van_ban', $this->common::VAN_BAN_DEN)->where('id_van_ban', $id)->where('deleted_at IS NULL')->first();
        if (!$vb) resError('Không tìm thấy văn bản', REST_Controller::HTTP_NOT_FOUND);
        $donviChinhIds = commonRequest('main_unit_ids') ? commonRequest('main_unit_ids') : [];
        $donviPhoihopIds = commonRequest('combination_unit_ids') ? commonRequest('combination_unit_ids') : [];

        $nguoi_phu_trach = commonRequest('nguoi_phu_trach') ? commonRequest('nguoi_phu_trach') : null;
        $nguoi_chu_tri = commonRequest('nguoi_chu_tri') ? commonRequest('nguoi_chu_tri') : null;
        $nguoi_phoi_hop = commonRequest('nguoi_phoi_hop') ? commonRequest('nguoi_phoi_hop') : null;
        $nguoi_duyet = commonRequest('nguoi_duyet') ? commonRequest('nguoi_duyet') : null;
        $ghi_chu_duyet = commonRequest('ghi_chu_duyet') ? commonRequest('ghi_chu_duyet') : null;
        $ngay_duyet = commonRequest('ngay_duyet') ? commonRequest('ngay_duyet') : null;
        $nguoi_xem = commonRequest('nguoi_xem') ? commonRequest('nguoi_xem') : null;
        $trang_thai_xu_ly = commonRequest('trang_thai_xu_ly') ? commonRequest('trang_thai_xu_ly') : null;

        $auth  = $this->getUserLogin();

        $data = [
            'id_van_ban' => $id,
            'nguoi_phu_trach' => $nguoi_phu_trach,
            'nguoi_chu_tri' => $nguoi_chu_tri,
            'nguoi_phoi_hop' => $nguoi_phoi_hop,
            'nguoi_duyet' => $nguoi_duyet,
            'ngay_duyet' => $ngay_duyet,
            'ghi_chu_duyet' => $ghi_chu_duyet,
            'nguoi_xem' => $nguoi_xem,
            'trang_thai_xu_ly' => $trang_thai_xu_ly,
            'nguoi_tao' => $auth['ql_nguoi_dung_id'],
            'nguoi_sua' => $auth['ql_nguoi_dung_id']
        ];

        $rules = [
            'ngay_duyet' => 'date',
            'trang_thai_xu_ly' => 'integer'
        ];

        $customMessages = [
            'ngay_duyet.date' => 'Ngày duyệt không đúng định dạng',
        ];

        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }
        $this->db->trans_start();
        $xuly = $this->E_xu_ly_model->create($data);

        foreach ($donviChinhIds as $donviChinhId) {
            $dvXulyId = $this->E_don_vi_xu_ly_model->insert([
                'id_don_vi' => $donviChinhId,
                'id_xu_ly' => $xuly['id_xu_ly'],
                'nguoi_tao' => $auth['ql_nguoi_dung_id'],
                'nguoi_sua' => $auth['ql_nguoi_dung_id'],
                'don_vi_xu_ly_chinh' => 1
            ]);
        }
        foreach ($donviPhoihopIds as $donviPhoihopId) {
            $dvXulyId = $this->E_don_vi_xu_ly_model->insert([
                'id_don_vi' => $donviPhoihopId,
                'id_xu_ly' => $xuly['id_xu_ly'],
                'nguoi_tao' => $auth['ql_nguoi_dung_id'],
                'nguoi_sua' => $auth['ql_nguoi_dung_id'],
                'don_vi_xu_ly_chinh' => null
            ]);
        }

        //Cập nhật lại trạng thái
        $this->E_van_ban_model->where('id_van_ban', $id)->update([
            'trang_thai' => $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value']
        ]);

        $noidungthongbao = 'Đơn vị có văn bản đến: ' . $vb['trich_yeu'] . '. Click vào đây để <a href="' . $this->config->item('frontend_url') . 'vanban/vanbandendonvi">xem chi tiết</a>.';
        $donvithongbaoIds = array_unique(array_merge($donviChinhIds, $donviPhoihopIds));

        $notification = $this->Ql_thong_bao_model->create([
            'ql_thong_bao_tieu_de' => $vb['trich_yeu'],
            'ql_thong_bao_tieu_de_tieng_anh' => $vb['trich_yeu'],
            'ql_thong_bao_noi_dung' => $noidungthongbao,
            'ql_thong_bao_noi_dung_tieng_anh' => $noidungthongbao,
            'ql_thong_bao_ngay_gui' => date('Y-m-d H:i:s'),
            'ql_thong_bao_loai' => 1, //1: thông báo, 2: nhắc nhở
            'ql_thong_bao_doi_tuong' => 2,
            'ql_thong_bao_da_gui' => 1,
            'ql_thong_bao_tu_dong_gui' => 1,
            'ql_thong_bao_ds_don_vi_id' => !empty($donvithongbaoIds) ? json_encode($donvithongbaoIds) : null,
            'ql_thong_bao_cong_khai' => 0, //Nội bộ
            'created_user_id' => $auth['ql_nguoi_dung_id'],
            'updated_user_id' => $auth['ql_nguoi_dung_id']
        ]);

        $users = $this->Ql_nguoi_dung_model->whereIn('id_don_vi', $donvithongbaoIds)->get();
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

        $this->db->trans_commit();
        resSuccess(null, 'Xử lý thành công');
    }

    public function xuly_vanban_update_put($id)
    {
        $vb = $this->E_van_ban_model->where('loai_van_ban', $this->common::VAN_BAN_DEN)->where('id_van_ban', $id)->where('deleted_at IS NULL')->first();
        if (!$vb) resError('Không tìm thấy văn bản', REST_Controller::HTTP_NOT_FOUND);
        $cloneVb = $vb;
        $donviChinhIds = commonRequest('main_unit_ids') ? commonRequest('main_unit_ids') : [];
        $donviPhoihopIds = commonRequest('combination_unit_ids') ? commonRequest('combination_unit_ids') : [];

        $nguoi_phu_trach = commonRequest('nguoi_phu_trach') ? commonRequest('nguoi_phu_trach') : null;
        $nguoi_chu_tri = commonRequest('nguoi_chu_tri') ? commonRequest('nguoi_chu_tri') : null;
        $nguoi_phoi_hop = commonRequest('nguoi_phoi_hop') ? commonRequest('nguoi_phoi_hop') : null;
        $nguoi_duyet = commonRequest('nguoi_duyet') ? commonRequest('nguoi_duyet') : null;
        $ghi_chu_duyet = commonRequest('ghi_chu_duyet') ? commonRequest('ghi_chu_duyet') : null;
        $ngay_duyet = commonRequest('ngay_duyet') ? commonRequest('ngay_duyet') : null;
        $nguoi_xem = commonRequest('nguoi_xem') ? commonRequest('nguoi_xem') : null;
        $trang_thai_xu_ly = commonRequest('trang_thai_xu_ly') ? commonRequest('trang_thai_xu_ly') : null;

        $auth  = $this->getUserLogin();

        $data = [
            // 'id_van_ban' => $id,
            'nguoi_phu_trach' => $nguoi_phu_trach,
            'nguoi_chu_tri' => $nguoi_chu_tri,
            'nguoi_phoi_hop' => $nguoi_phoi_hop,
            'nguoi_duyet' => $nguoi_duyet,
            'ngay_duyet' => $ngay_duyet,
            'ghi_chu_duyet' => $ghi_chu_duyet,
            'nguoi_xem' => $nguoi_xem,
            'trang_thai_xu_ly' => $trang_thai_xu_ly,
            'nguoi_tao' => $auth['ql_nguoi_dung_id'],
            'nguoi_sua' => $auth['ql_nguoi_dung_id']
        ];

        $rules = [
            'ngay_duyet' => 'date',
            'trang_thai_xu_ly' => 'integer'
        ];

        $customMessages = [
            'ngay_duyet.date' => 'Ngày duyệt không đúng định dạng',
        ];

        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }
        $this->db->trans_start();

        $xulyOld = $this->E_xu_ly_model->where('id_van_ban', $id)->first();
        $donvixulyChinhOld = $this->E_don_vi_xu_ly_model->where('id_xu_ly', $xulyOld['id_xu_ly'])->where('don_vi_xu_ly_chinh IS NOT NULL')->get();
        $donvixulyPhoihopOld = $this->E_don_vi_xu_ly_model->where('id_xu_ly', $xulyOld['id_xu_ly'])->where('don_vi_xu_ly_chinh IS NULL')->get();

        $donviChinhOldIds = array_column($donvixulyChinhOld, 'id_don_vi');
        $donviPhoihopOldIds = array_column($donvixulyPhoihopOld, 'id_don_vi');


        $donviChinhOld = !empty($donviChinhOldIds) ? $this->E_don_vi_model->whereIn('id_don_vi', $donviChinhOldIds)->get() : [];
        $donviPhoihopOld = !empty($donviPhoihopOldIds) ? $this->E_don_vi_model->whereIn('id_don_vi', $donviPhoihopOldIds)->get() : [];

        $xulyOld['don_vi_chinh'] = $donviChinhOld;
        $xulyOld['don_vi_phoi_hop'] = $donviPhoihopOld;
        $vb['xu_ly'] = $xulyOld;




        //Cập nhật xử lý
        $this->E_xu_ly_model->where('id_van_ban', $id)->update($data);
        $xuly = $this->E_xu_ly_model->where('id_van_ban', $id)->first();

        //Xóa các đơn vị cũ
        $this->E_don_vi_xu_ly_model->where('id_xu_ly', $xuly['id_xu_ly'])->delete();

        //Tạo lại các đơn vị mới
        foreach ($donviChinhIds as $donviChinhId) {
            $dvXulyId = $this->E_don_vi_xu_ly_model->insert([
                'id_don_vi' => $donviChinhId,
                'id_xu_ly' => $xuly['id_xu_ly'],
                'nguoi_tao' => $auth['ql_nguoi_dung_id'],
                'nguoi_sua' => $auth['ql_nguoi_dung_id'],
                'don_vi_xu_ly_chinh' => 1
            ]);
        }
        foreach ($donviPhoihopIds as $donviPhoihopId) {
            $dvXulyId = $this->E_don_vi_xu_ly_model->insert([
                'id_don_vi' => $donviPhoihopId,
                'id_xu_ly' => $xuly['id_xu_ly'],
                'nguoi_tao' => $auth['ql_nguoi_dung_id'],
                'nguoi_sua' => $auth['ql_nguoi_dung_id'],
                'don_vi_xu_ly_chinh' => null
            ]);
        }

        //Gửi thông báo cho đơn vị được giao
        $noidungthongbao = 'Đơn vị có văn bản đến: ' . $vb['trich_yeu'] . '. Click vào đây để <a href="' . $this->config->item('frontend_url') . 'vanban/vanbandendonvi">xem chi tiết</a>.';
        $donvithongbaoIds = array_unique(array_merge($donviChinhIds, $donviPhoihopIds));

        $notification = $this->Ql_thong_bao_model->create([
            'ql_thong_bao_tieu_de' => $vb['trich_yeu'],
            'ql_thong_bao_tieu_de_tieng_anh' => $vb['trich_yeu'],
            'ql_thong_bao_noi_dung' => $noidungthongbao,
            'ql_thong_bao_noi_dung_tieng_anh' => $noidungthongbao,
            'ql_thong_bao_ngay_gui' => date('Y-m-d H:i:s'),
            'ql_thong_bao_loai' => 1, //1: thông báo, 2: nhắc nhở
            'ql_thong_bao_doi_tuong' => 2,
            'ql_thong_bao_da_gui' => 1,
            'ql_thong_bao_tu_dong_gui' => 1,
            'ql_thong_bao_ds_don_vi_id' => !empty($donvithongbaoIds) ? json_encode($donvithongbaoIds) : null,
            'ql_thong_bao_cong_khai' => 0, //Nội bộ
            'created_user_id' => $auth['ql_nguoi_dung_id'],
            'updated_user_id' => $auth['ql_nguoi_dung_id']
        ]);

        $users = $this->Ql_nguoi_dung_model->whereIn('id_don_vi', $donvithongbaoIds)->get();
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


        $donviChinhNew = !empty($donviChinhIds) ? $this->E_don_vi_model->whereIn('id_don_vi', $donviChinhIds)->get() : [];
        $donviPhoihopNew = !empty($donviPhoihopIds) ? $this->E_don_vi_model->whereIn('id_don_vi', $donviPhoihopIds)->get() : [];

        $xuly['don_vi_chinh'] = $donviChinhNew;
        $xuly['don_vi_phoi_hop'] = $donviPhoihopNew;
        $cloneVb['xu_ly'] = $xuly;

        //Tạo log
        $this->createLog('update', 'Cập nhật xử lý văn bản', $vb, $cloneVb, 'e_xu_ly, e_don_vi_xu_ly');

        //Cập nhật lại trạng thái
        // $this->E_van_ban_model->where('id_van_ban', $id)->update([
        //     'trang_thai' => $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value']
        // ]);

        $this->db->trans_commit();
        resSuccess(null, 'Cập nhật thành công');
    }

    private function getExcelColumn()
    {
        $cols = [
            '' => 'STT',
            'nam' => 'Năm',
            'ngay_nhan' => 'Ngày CV',
            'so_hieu_van_ban' => 'Số trên CV',
            'noi_gui' => 'Nơi gửi',
            'ngay_ban_hanh' => 'Ngày trên CV',
            'noi_dung' => 'Nội dung',
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

        $data = $this->E_van_ban_model->getListExportVanbanden($start, $length, $searchValue, $searchKey, $fromDate, $toDate);

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
        $sheet->setCellValue('A1', 'Công văn đến'); // Thêm cột thông báo

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
                $sheet->setCellValue($column . $row, isset($item[$key]) ? $item[$key] : '');
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
        $filename = 'vanbanden_' . time() . '.xls'; // Tên file kèm timestamp để tránh trùng lặp
        $filePath = $directory . $filename;
        // Kiểm tra và tạo thư mục nếu chưa tồn tại
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);

        if (file_exists($filePath)) {
            $this->createLog('Export', 'Export danh sách công văn đến', NULL, 'Export danh sách công văn đến', 'e_van_ban');
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

    public function baocaophanhoi_theodonvi_get()
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

        $auth = $this->getUserLogin();

        $data = $this->E_van_ban_model->getAllBaoCaoPhanHoiTheoDonvi($start, $length, $searchValue, $orderBy, $searchKey, $fromDate, $toDate, $auth);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
            'tren_5_ngay' => $data['tren5ngay'],
            'duoi_5_ngay' => $data['duoi5ngay'],
            'hom_nay' => $data['homnay'],
            'qua_han' => $data['quahan']
        ]);
    }

    public function bao_cao_phan_hoi_create_post($id)
    {
        $vanban = $this->E_van_ban_model->where('id_van_ban', $id)->where('deleted_at IS NULL')->first();
        if (!$vanban) resError('Không tìm thấy văn bản', REST_Controller::HTTP_NOT_FOUND);

        $trangThai = commonRequest('trang_thai') ? commonRequest('trang_thai') : null;

        if (!$trangThai || !in_array($trangThai, ['DA_PHAN_HOI'])) {
            resBadrequest([], 'Trạng thái không hợp lệ');
        }

        $data = [
            'noi_dung' => commonRequest('noi_dung') ? commonRequest('noi_dung') : null,
            'ngay_bao_cao' => commonRequest('ngay_bao_cao') ? commonRequest('ngay_bao_cao') : null,
            'id_van_ban' => $id,
            'id_don_vi_phan_hoi' => $this->getUserLogin()['id_don_vi'],
            'nguoi_tao' => $this->getUserLogin()['ql_nguoi_dung_id'],
            'trang_thai' => $this->common::STATUS_VAN_BAN_DEN[$trangThai]['value'],
        ];

        $rules = [
            'noi_dung' => 'required',
            'ngay_bao_cao' => 'required|date',
            'id_van_ban' => 'required',
            'id_don_vi_phan_hoi' => 'required',
        ];

        $customMessages = [
            'noi_dung.required' => 'Nội dung phản hồi bắt buộc nhập',
            'ngay_bao_cao.required' => 'Ngày báo cáo bắt buộc nhập',
            'ngay_bao_cao.date' => 'Ngày báo cáo bắt phải đúng địng dạng',
        ];

        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $folderName = 'documents/' . date('Y') . '/' . date('m');
        if (isset($_FILES['dinh_kem'])) {
            $uploadedFile = $this->fileupload->upload($_FILES['dinh_kem'], $folderName);
            if (!$uploadedFile['success']) {
                resBadrequest([], 'Không thể tải file lên');
            } else {
                $data['dinh_kem'] = $uploadedFile['file_path'];
            }
        }

        $this->db->trans_start();
        $baocaophanhoi = $this->E_bao_cao_model->create($data);

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
                    'trang_thai' => $this->common::STATUS_VAN_BAN_DEN['DA_PHAN_HOI']['value']
                ]);
            }
        }

        //Đổi trạng thái văn bản
        // $this->E_van_ban_model->where('id_van_ban', $id)->update([
        //     'trang_thai' => $this->common::STATUS_VAN_BAN_DEN[$trangThai]['value']
        // ]);
        // $newVb = $this->E_van_ban_model->find($id);
        // $newVb['phanhoi'] = $baocaophanhoi;

        $this->createLog('create', 'Báo cáo phản hồi văn bản', $vanban, $baocaophanhoi, 'e_van_ban, e_bao_cao');

        //Tạo thông báo về cho tổ chức hành chính
        $donvitochuchanhchinh = $this->E_don_vi_model->where('ma_don_vi', 'PHONG_TCHC')->first();
        if ($donvitochuchanhchinh) {
            $auth = $this->getUserLogin();
            $donvi = $this->E_don_vi_model->where('id_don_vi', $auth['id_don_vi'])->first();

            $noidungthongbao = 'Đơn vị ' . $donvi['ten_don_vi'] . ' đã phản hồi văn bản ' .
                $vanban['trich_yeu'] . '. Click vào đây để <a href="' . $this->config->item('frontend_url') . 'vanban/vanbanden?searchValue=' . $vanban['so_van_ban'] . '&activeTabId=nav-xemBaoCaoPhanHoi">xem chi tiết</a>.';
            // $noidungthongbao = 'Đơn vị có văn bản đến: ' . $vb['trich_yeu'] . '. Click vào đây để <a href="' . $this->config->item('frontend_url') . 'vanban/vanbandendonvi">xem chi tiết</a>.';
            $donvithongbaoIds = [$donvitochuchanhchinh['id_don_vi']];

            $notification = $this->Ql_thong_bao_model->create([
                'ql_thong_bao_tieu_de' => $vanban['trich_yeu'],
                'ql_thong_bao_tieu_de_tieng_anh' => $vanban['trich_yeu'],
                'ql_thong_bao_noi_dung' => $noidungthongbao,
                'ql_thong_bao_noi_dung_tieng_anh' => $noidungthongbao,
                'ql_thong_bao_ngay_gui' => date('Y-m-d H:i:s'),
                'ql_thong_bao_loai' => 1, //1: thông báo, 2: nhắc nhở
                'ql_thong_bao_doi_tuong' => 2,
                'ql_thong_bao_da_gui' => 1,
                'ql_thong_bao_tu_dong_gui' => 1,
                'ql_thong_bao_ds_don_vi_id' => !empty($donvithongbaoIds) ? json_encode($donvithongbaoIds) : null,
                'ql_thong_bao_cong_khai' => 0, //Nội bộ
                'created_user_id' => $auth['ql_nguoi_dung_id'],
                'updated_user_id' => $auth['ql_nguoi_dung_id']
            ]);

            $users = $this->Ql_nguoi_dung_model->whereIn('id_don_vi', $donvithongbaoIds)->get();
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
        }

        $this->db->trans_commit();
        resSuccess(null, 'Phản hồi văn bản thành công');
    }

    public function change_status_put($id)
    {
        $vanban = $this->E_van_ban_model->where('id_van_ban', $id)->where('deleted_at IS NULL')->first();
        if (!$vanban) resError('Không tìm thấy văn bản', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        $status = commonRequest('trang_thai') ? commonRequest('trang_thai') : null;
        if (!in_array($status, ['TIEP_NHAN', 'CHO_LANH_DAO_BUT_PHE', 'DA_BUT_PHE', 'CHO_XU_LY', 'DA_XU_LY', 'LUU_TRU', 'CHUA_PHAN_HOI', 'DA_PHAN_HOI', 'HOAN_THANH'])) {
            resBadrequest(['trang_thai' => 'Trạng thái không hợp lệ'], 'Trạng thái không hợp lệ');
        }
        $this->E_van_ban_model->where('id_van_ban', $id)->update([
            'trang_thai' => $this->common::STATUS_VAN_BAN_DEN[$status]['value']
        ]);
        $this->createLog('Update', 'Cập nhật trạng thái văn bản', $vanban, $this->E_van_ban_model->find($id), 'e_van_ban');
        resSuccess(null, 'Cập nhật thành công');
    }

    public function import_post()
    {
        try {

            $stop = false;
            $message = '';
            $dataList = [];
            $file_import = commonRequest('file_excel');

            if ($file_import) {

                $config['upload_path'] = 'uploads/excel/vanbanden/'; // Thư mục để lưu file
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
                        'ngay_nhan',
                        'so_hieu_van_ban',
                        'co_quan',
                        'ngay_ban_hanh',
                        'trich_yeu',
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
                                'ngay_nhan' => 'Ngày CV đang rỗng.',
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

                                $coquan = $this->E_co_quan_model->where('ten_co_quan', $l['co_quan'])->first();
                                if (!$coquan) {
                                    // $l['ketqua'] .= "Nơi gửi không tồn tại. ";
                                    // $invalidData = true;

                                    // $indexKey = array_search('co_quan', $keys) + 1; // bỏ đi cột stt
                                    // $column = PHPExcel_Cell::stringFromColumnIndex($indexKey);
                                    // $sheet->getStyle($column . $startRow_columnResult)->applyFromArray(
                                    //     array(
                                    //         'fill' => array(
                                    //             'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                    //             'color' => array('rgb' => 'f9ca24')
                                    //         )
                                    //     )
                                    // );
                                    $id_coquan = $this->E_co_quan_model->insert([
                                        'ten_co_quan' => $l['co_quan']
                                    ]);

                                    $coquan = $this->E_co_quan_model->find($id_coquan);
                                }

                                if (!$invalidData) {
                                    $l['ketqua'] = 'Thành công';
                                    $countSuccess++;

                                    $ngaynhan = $l['ngay_nhan'];
                                    $ngaynhanFormat = DateTime::createFromFormat('d/m/Y', $ngaynhan)->format('Y-m-d');


                                    $ngaybanhanh = $l['ngay_ban_hanh'];

                                    $ngaybanhanhFormat = DateTime::createFromFormat('d/m/Y', $ngaybanhanh)->format('Y-m-d');

                                    $dataInsert[] = [
                                        'so_hieu_van_ban' => $l['so_hieu_van_ban'],
                                        'loai_van_ban' => $this->common::VAN_BAN_DEN,
                                        'trich_yeu' => $l['trich_yeu'],
                                        'ngay_nhan' => $ngaynhanFormat,
                                        'ngay_ban_hanh' => $ngaybanhanhFormat,
                                        'ngay_ky' => $ngaybanhanhFormat,
                                        'nguoi_ky' => $l['nguoi_ky'],
                                        'ghi_chu' => $l['ghi_chu'],
                                        'id_co_quan' => $coquan['id_co_quan'],
                                        'trang_thai' => $this->common::STATUS_VAN_BAN_DEN['TIEP_NHAN']['value'],
                                        'id_nguoi_tao' => $this->getUserLogin()['ql_nguoi_dung_id'],
                                        'id_nguoi_sua' => $this->getUserLogin()['ql_nguoi_dung_id'],
                                        'ngay_tao' => date('Y-m-d H:i:s'),
                                        'ngay_sua' => date('Y-m-d H:i:s'),
                                    ];
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
                        if (!empty($dataInsert)) {
                            // dd($dataInsert);
                            // $this->db->trans_start();
                            $this->E_van_ban_model->insertBatch($dataInsert);
                            $this->createLog('Import', 'Import văn bản đến', NULL, $dataInsert, 'e_van_ban');
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

    public function update_by_key_post($id)
    {
        $vanban = $this->E_van_ban_model->where('id_van_ban', $id)->where('loai_van_ban', $this->common::VAN_BAN_DEN)->where('deleted_at IS NULL')->first();
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
        $this->createLog('Update', 'Cập nhật văn bản đến', $vanban, $this->E_van_ban_model->find($id), 'e_van_ban');
        resSuccess(null, 'Cập nhật thành công', REST_INSTANCE_Controller::HTTP_OK);
    }
}
