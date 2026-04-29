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
 * @property Ql_thong_bao_model $Ql_thong_bao_model
 * @property E_don_vi_xu_ly_da_xem_model $E_don_vi_xu_ly_da_xem_model
 * @property E_vb_khoi_co_quan_model $E_vb_khoi_co_quan_model
 * @property E_vb_co_quan_model $E_vb_co_quan_model
 * @property E_tag_model $E_tag_model
 */



class Vanbandendonvi extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        if (!$this->inSegment([
            'vanbandendonvi.xem_van_ban',
            'vanbandendonvi.phan_hoi_nhanh_vanban'
        ])) {
            $this->permissionMiddleware();
        }
        $this->load->helper('url');
        $this->load->model([
            'E_van_ban_model',
            'E_file_dinh_kem_model',
            'E_but_phe_model',
            'E_xu_ly_model',
            'E_don_vi_xu_ly_model',
            'E_don_vi_model',
            'E_bao_cao_model',
            'E_don_vi_xu_ly_da_xem_model',
            'E_vb_khoi_co_quan_model',
            'E_vb_co_quan_model',
            'Ql_thong_bao_model',
            'E_tag_model',
        ]);
        $this->load->library(['Validator', 'Fileupload', 'Common', 'Pxl']);
    }

    public function index_get()
    {
        $auth = $this->getUserLogin();

        $data = [
            'start'         => commonRequest('start') ?? 0,
            'length'        => commonRequest('length') ?? 10,
            'searchValue'   => commonRequest('searchValue') ?? null,
            'order'         => commonRequest('order') ?? [],
            'columns'       => commonRequest('columns') ?? [],
            'searchKey'     => commonRequest('searchKey') ? commonRequest('searchKey') : [],
            'fromDate'      => commonRequest('fromDate') ? commonRequest('fromDate') : null,
            'toDate'        => commonRequest('toDate') ? commonRequest('toDate') : null
        ];
        $response = $this->E_van_ban_model->getAllBaoCaoPhanHoiTheoDonvi($data['start'], $data['length'], $data['searchValue'], $data['order'], $data['columns'], $data['searchKey'],  $data['fromDate'], $data['toDate'], $auth);
        $tags = $this->E_tag_model->get_user_tags($auth['ql_nguoi_dung_id']);
        resSuccess($response['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $response['recordsTotal'],
            'recordsFiltered' => $response['recordsFiltered'],
            'tags' => $tags,
            'thoi_han' => $response['thoi_han'],
            'sql' => $response['sql'],
        ]);

        return;
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
        $tags = $this->E_tag_model->get_user_tags($auth['ql_nguoi_dung_id']);

        $filterData = [];
        foreach ($data['data'] as $dt) {
            if ((!$dt['id_nguoi_xu_ly']) || ($dt['id_nguoi_xu_ly'] == $auth['ql_nguoi_dung_id'])) {
                $filterData[] = $dt;
            }
        }

        // resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
        resSuccess($filterData, 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
            'tags' => $tags,
            'thoi_han' => $data['thoi_han'],
            'sql' => $data['sql'],
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
            // 'so_van_ban' => 'unique:e_van_ban,so_van_ban',
            'id_loai' => 'required|integer',
            'trich_yeu' => 'required',
            'ngay_nhan' => 'required|date',
            'ngay_ban_hanh' => 'required|date',
            // 'id_trang_thai' => 'required|integer',
            // 'trang_thai' => 'required',
            'thoi_gian_xu_ly' => 'date',
            'id_khoi_co_quan' => 'required|integer',
            'id_co_quan' => 'integer',
            'id_hinh_thuc' => 'required|integer',
            'id_tinh_chat' => 'required|integer',
            'id_bao_mat' => 'required|integer',
            'id_don_vi' => 'integer',
            'luu_tru_noi_bo' => 'integer',
            'nguoi_ky' => 'required',
            'ngay_ky' => 'required|date'
        ];

        $customMessages = [
            // 'ten_van_ban.required' => 'Tên văn bản bắt buộc nhập',
            // 'so_van_ban.unique' => 'Số văn bản đã tồn tại',
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
        $this->createLog('create', 'Tạo văn bản đi', null, $vb, 'e_van_ban');

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

    // public function show_get($id)
    // {
    //     $vb = $this->E_van_ban_model
    //         ->join('e_loai', 'e_loai.id_loai = e_van_ban.id_loai', 'left')
    //         ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
    //         ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
    //         ->join('e_hinh_thuc', 'e_hinh_thuc.id_hinh_thuc = e_van_ban.id_hinh_thuc', 'left')
    //         ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
    //         ->join('e_bao_mat', 'e_bao_mat.id_bao_mat = e_van_ban.id_bao_mat', 'left')
    //         ->where('e_van_ban.id_van_ban', $id)
    //         ->first();

    //     if (!$vb) resError('Không tìm thấy văn bản', REST_Controller::HTTP_NOT_FOUND);
    //     $vb['but_phe'] = $this->E_but_phe_model->where('id_van_ban', $id)->first();
    //     $vb['files'] = $this->E_file_dinh_kem_model->where('id_van_ban', $id)->get();
    //     $xuly = $this->E_xu_ly_model->where('id_van_ban', $id)->first();
    //     if ($xuly) {
    //         $xuly['don_vi_chinh'] = $this->E_don_vi_xu_ly_model->where('id_xu_ly', $xuly['id_xu_ly'])->where('don_vi_xu_ly_chinh IS NOT NULL')->get();
    //         $xuly['don_vi_phoi_hop'] = $this->E_don_vi_xu_ly_model->where('id_xu_ly', $xuly['id_xu_ly'])->where('don_vi_xu_ly_chinh IS NULL')->get();
    //     }
    //     $vb['xu_ly'] = $xuly;
    //     resSuccess($vb);
    // }

    public function show_get($id)
    {
        $auth = $this->getUserLogin();
        $vb = $this->E_van_ban_model->detail_vanbandendonvi($id, $auth);

        resSuccess($vb['data'], 'Lấy thông tin thành công');


        $auth = $this->getUserLogin();
        $vb = $this->E_van_ban_model
            ->select('
                e_van_ban.*, 
                DATE_FORMAT(e_van_ban.thoi_gian_xu_ly, "%Y-%m-%d")  AS thoi_gian_xu_ly,
                e_loai.id_loai,
                e_loai.ten_loai,
                e_khoi_co_quan.id_khoi_co_quan,
                e_khoi_co_quan.ten_khoi_co_quan,
                e_co_quan.id_co_quan,
                e_co_quan.ten_co_quan,
                e_hinh_thuc.id_hinh_thuc,
                e_hinh_thuc.ten_hinh_thuc,
                e_tinh_chat.id_tinh_chat,
                e_tinh_chat.ten_tinh_chat,
                e_tinh_chat.class_color AS color_tinh_chat,
                e_bao_mat.id_bao_mat,
                e_bao_mat.ten_bao_mat,
                e_bao_mat.class_color AS color_bao_mat,
                e_don_vi.id_don_vi,
                e_don_vi.ten_don_vi ho_so_don_vi,
                ql_nguoi_dung.ql_nguoi_dung_id,
                ql_nguoi_dung.ql_nguoi_dung_ho_ten AS nguoi_but_phe,
                e_but_phe.ngay_but_phe,
                e_but_phe.noi_dung_but_phe
            ')
            ->join('e_loai', 'e_loai.id_loai = e_van_ban.id_loai', 'left')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
            ->join('e_hinh_thuc', 'e_hinh_thuc.id_hinh_thuc = e_van_ban.id_hinh_thuc', 'left')
            ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
            ->join('e_bao_mat', 'e_bao_mat.id_bao_mat = e_van_ban.id_bao_mat', 'left')
            ->join('e_but_phe', 'e_but_phe.id_van_ban = e_van_ban.id_van_ban', 'left')
            ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_but_phe.id_nguoi_but_phe', 'left')
            ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'left')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = e_van_ban.id_don_vi', 'left')
            ->join('e_hinh_thuc', 'e_hinh_thuc.id_hinh_thuc = e_van_ban.id_hinh_thuc', 'left')
            ->where('e_van_ban.id_van_ban', $id)
            ->where('e_van_ban.deleted_at IS NULL')
            ->first();

        if (!$vb) resError('Không tìm thấy văn bản', REST_Controller::HTTP_NOT_FOUND);

        if ($vb['loai_van_ban'] == $this->common::VAN_BAN_DEN) {
            $vb['but_phe'] = $this->E_but_phe_model->where('id_van_ban', $id)->first();
            if ($vb['loai_van_ban'] == $this->common::VAN_BAN_DI) {
                $vb['files'] = $this->E_file_dinh_kem_model->where('id_van_ban', $id)->where('la_file_ban_hanh', 1)->get();
            } else {
                $vb['files'] = $this->E_file_dinh_kem_model->where('id_van_ban', $id)->get();
            }
            foreach ($vb['files'] as &$file) {
                $duongdan = encryptString($file['duong_dan']);
                $file['duong_dan'] = $duongdan;
            }

            $xuly = $this->E_xu_ly_model->where('id_van_ban', $id)->first();
            if ($xuly) {
                $xuly['don_vi_chinh'] = $this->E_don_vi_xu_ly_model->where('id_xu_ly', $xuly['id_xu_ly'])->where('don_vi_xu_ly_chinh IS NOT NULL')->get();
                $xuly['don_vi_phoi_hop'] = $this->E_don_vi_xu_ly_model->where('id_xu_ly', $xuly['id_xu_ly'])->where('don_vi_xu_ly_chinh IS NULL')->get();
            }
            $vb['xu_ly'] = $xuly;

            $baocao = $this->E_bao_cao_model->where('id_van_ban', $id)->where('id_don_vi_phan_hoi', $auth['id_don_vi'])->get();
            // foreach ($baocao as &$bc) {
            //     $bc['dinh_kem'] = encryptString($bc['dinh_kem']);
            // }

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

            $vb['bao_cao'] = $baocao;
            resSuccess($vb);
        } else {
            //nếu là văn bản đi
            $vb['files'] = $this->E_file_dinh_kem_model
                ->where('id_van_ban', $id)
                ->where('la_file_ban_hanh', 1)
                ->get();
            foreach ($vb['files'] as &$file) {
                $duongdan = encryptString($file['duong_dan']);
                $file['duong_dan'] = $duongdan;
            }
            $vb['files_tchc'] = $this->E_file_dinh_kem_model
                ->where('id_van_ban', $id)
                ->where('la_file_ban_hanh', 0)
                ->get();
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
                ->get();

            $baocao = $this->E_bao_cao_model->where('id_van_ban', $id)->where('id_don_vi_phan_hoi', $auth['id_don_vi'])->get();
            // foreach ($baocao as &$bc) {
            //     $bc['dinh_kem'] = encryptString($bc['dinh_kem']);
            // }
            $vb['bao_cao'] = $baocao;
            resSuccess($vb);
        }
    }

    public function update_post($id)
    {
        $vanban = $this->E_van_ban_model->find($id);
        if (!$vanban) resError('Không tìm thấy văn bản', REST_Controller::HTTP_NOT_FOUND);

        $trangThai = commonRequest('trang_thai') ? commonRequest('trang_thai') : null;

        if (!$trangThai || !in_array($trangThai, ['TIEP_NHAN', 'CHO_LANH_DAO_BUT_PHE', 'DA_BUT_PHE', 'CHO_XU_LY', 'DA_XU_LY', 'LUU_TRU', 'CHUA_PHAN_HOI', 'DA_PHAN_HOI'])) {
            resBadrequest([], 'Trạng thái không hợp lệ');
        }

        $data = [
            // 'ten_van_ban' => commonRequest('ten_van_ban') ? commonRequest('ten_van_ban') : null,
            'so_van_ban' => commonRequest('so_van_ban') ? commonRequest('so_van_ban') : null,
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
            // 'so_van_ban' => 'unique:e_van_ban,so_van_ban',
            'id_loai' => 'required|integer',
            'trich_yeu' => 'required',
            'ngay_nhan' => 'required|date',
            'ngay_ban_hanh' => 'required|date',
            // 'id_trang_thai' => 'required|integer',
            // 'trang_thai' => 'required',
            'thoi_gian_xu_ly' => 'date',
            'id_khoi_co_quan' => 'required|integer',
            'id_co_quan' => 'integer',
            'id_hinh_thuc' => 'required|integer',
            'id_tinh_chat' => 'required|integer',
            'id_bao_mat' => 'required|integer',
            'id_don_vi' => 'integer',
            'luu_tru_noi_bo' => 'integer',
            'nguoi_ky' => 'required',
            'ngay_ky' => 'required|date'
        ];

        $customMessages = [
            // 'ten_van_ban.required' => 'Tên văn bản bắt buộc nhập',
            // 'so_van_ban.unique' => 'Số văn bản đã tồn tại',
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

        //Cập nhật
        $this->E_van_ban_model->where('id_van_ban', $id)->where('loai_van_ban', $this->common::VAN_BAN_DEN)->update($data);

        //Upload file đính kèm
        // $folderName = 'documents';
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

        $filesOld = $this->E_file_dinh_kem_model->where('id_van_ban', $id)->get();

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
            if (!empty($filesOld)) {
                $fileIds = array_column($filesOld, 'id_file_dinh_kem');
                $this->E_file_dinh_kem_model->whereIn('id_file_dinh_kem', $fileIds)->delete();

                //Xóa file
                foreach ($filesOld as $fileOld) {
                    $deleteFile = $this->fileupload->delete($fileOld['duong_dan']);
                }
            }
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
        $vanban = $this->E_van_ban_model->whereIn('id_van_ban', $ids)->get();
        if (count($ids) != count($vanban)) {
            resError('Có văn bản không tồn tại');
        }
        $this->createLog('delete', 'Xóa văn bản đến', $vanban, null, 'e_van_ban');
        $files = $this->E_file_dinh_kem_model->whereIn('id_van_ban', $ids)->get();
        $this->E_file_dinh_kem_model->whereIn('id_van_ban', $ids)->delete();

        $this->E_bao_cao_model->whereIn('id_van_ban', $ids)->delete();


        $xuly = $this->E_xu_ly_model->whereIn('id_van_ban', $ids)->get();
        $xulyIds = array_column($xuly, 'id_xu_ly');

        if (!empty($xulyIds)) {
            $this->E_don_vi_xu_ly_model->whereIn('id_xu_ly', $xulyIds)->delete();
        }
        $this->E_xu_ly_model->whereIn('id_van_ban', $ids)->delete();

        $this->db->where_in('id_van_ban', $ids)->delete('e_vb_co_quan');
        $this->db->where_in('id_van_ban', $ids)->delete('e_vb_khoi_co_quan');

        $this->db->where_in('id_van_ban', $ids)->delete('e_ban_hanh');

        $this->E_van_ban_model->whereIn('id_van_ban', $ids)->delete();

        foreach ($files as $file) {
            $deleteFile = $this->fileupload->delete($file['duong_dan']);
        }
        $this->createLog('delete', 'Xóa văn bản đến', $vanban,  null, 'e_van_ban');
        $this->db->trans_commit();
        resSuccess(null, 'Xóa thành công');
    }

    public function tao_butphe_post($id)
    {
        $vb = $this->E_van_ban_model->find($id);
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

        $data = $this->E_van_ban_model->getAllButPhe($start, $length, $searchValue, $orderBy, $searchKey, $fromDate, $toDate);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function update_butphe_put($id)
    {
        $vb = $this->E_van_ban_model->find($id);
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
        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;

        $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
            'order' => commonRequest('order'),
            'columns' => commonRequest('columns')
        ] : [];

        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];

        $data = $this->E_van_ban_model->getAllXuLyVanBan($start, $length, $searchValue, $orderBy, $searchKey);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
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

        $data = $this->E_van_ban_model->getAllBaoCaoPhanHoi($start, $length, $searchValue, $orderBy, $searchKey, $fromDate, $toDate);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function xuly_vanban_post($id)
    {
        $vb = $this->E_van_ban_model->where('loai_van_ban', $this->common::VAN_BAN_DEN)->where('id_van_ban', $id)->first();
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

        $this->db->trans_commit();
        resSuccess(null, 'Xử lý thành công');
    }

    public function xuly_vanban_update_put($id)
    {
        $vb = $this->E_van_ban_model->where('loai_van_ban', $this->common::VAN_BAN_DEN)->where('id_van_ban', $id)->first();
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

    // public function baocaophanhoi_theodonvi_get()
    // {
    //     $start = commonRequest('start') ? commonRequest('start') : 0;
    //     $length = commonRequest('length') ? commonRequest('length') : 10;
    //     $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;

    //     $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
    //         'order' => commonRequest('order'),
    //         'columns' => commonRequest('columns')
    //     ] : [];

    //     $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];

    //     $fromDate = commonRequest('fromDate') ? commonRequest('fromDate') : null;
    //     $toDate = commonRequest('toDate') ? commonRequest('toDate') : null;

    //     $auth = $this->getUserLogin();

    //     $data = $this->E_van_ban_model->getAllBaoCaoPhanHoiTheoDonvi($start, $length, $searchValue, $orderBy, $searchKey, $fromDate, $toDate, $auth);

    //     resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
    //         'recordsTotal' => $data['recordsTotal'],
    //         'recordsFiltered' => $data['recordsFiltered']
    //     ]);
    // }

    public function bao_cao_phan_hoi_create_post($id)
    {
        $auth = $this->getUserLogin();

        $vanban = $this->E_van_ban_model->find($id);
        if (!$vanban) resError('Không tìm thấy văn bản', REST_Controller::HTTP_NOT_FOUND);

        $trangThai = commonRequest('trang_thai') ? commonRequest('trang_thai') : null;

        if (!$trangThai || !in_array($trangThai, ['DA_PHAN_HOI'])) {
            resBadrequest([], 'Trạng thái không hợp lệ');
        }

        $trangThaiChuan = '';
        if ($vanban['loai_van_ban'] == Common::VAN_BAN_DEN) {
            $trangThaiChuan = $this->common::STATUS_VAN_BAN_DEN['HOAN_THANH']['value'];
        } else if ($vanban['loai_van_ban'] == Common::VAN_BAN_DI) {
            $trangThaiChuan = $this->common::STATUS_VAN_BAN_DI['HOAN_THANH']['value'];
        }

        $data = [
            'noi_dung' => commonRequest('noi_dung') ? commonRequest('noi_dung') : null,
            'ngay_bao_cao' => date('Y-m-d'),
            'id_van_ban' => $id,
            'id_don_vi_phan_hoi' => $this->getUserLogin()['id_don_vi'],
            'nguoi_tao' => $this->getUserLogin()['ql_nguoi_dung_id'],
            'trang_thai' => $trangThaiChuan,
        ];

        $rules = [
            'noi_dung' => 'required',
            'id_van_ban' => 'required',
            'id_don_vi_phan_hoi' => 'required',
        ];

        $customMessages = [
            'noi_dung.required' => 'Nội dung phản hồi bắt buộc nhập',
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

        //Đổi trạng thái văn bản
        $xuly = $this->E_xu_ly_model->where('id_van_ban', $id)->first();
        if ($xuly) {
            //Tìm đơn vị được giao nhiệm vụ xử lý văn bản
            $donvixuly = $this->E_don_vi_xu_ly_model
                ->where('id_xu_ly', $xuly['id_xu_ly'])
                ->get();
            //Đếm đơn vị xử lý
            $countdonvixuly = count(array_unique(array_column($donvixuly, 'id_don_vi')));

            //Tìm đơn vị báo cáo phản hồi
            $baocao = $this->E_bao_cao_model->where('id_van_ban', $id)->get();
            //Đếm đơn vị báo cáo
            $countbaocao = count(array_unique(array_column($baocao, 'id_don_vi_phan_hoi')));

            //Nếu các đơn vị báo cáo đủ thì sẽ tự chuyển trạng thái
            if ($countbaocao >= $countdonvixuly) {
                $this->E_van_ban_model->where('id_van_ban', $id)->update([
                    'trang_thai' => $trangThaiChuan
                ]);
            }
        }

        $this->createLog('create', 'Phản hồi văn bản', $vanban, $baocaophanhoi, 'e_van_ban, e_bao_cao');

        $xuly = $this->E_xu_ly_model->where('id_van_ban', $id)->first();

        // Tạo đã xem
        if ($xuly) {
            $nguoiXuLy = $this->E_don_vi_xu_ly_model
                ->where('id_xu_ly', $xuly['id_xu_ly'])
                ->where('id_nguoi_xu_ly', $auth['ql_nguoi_dung_id'])
                ->first();

            if ($nguoiXuLy && !$nguoiXuLy['da_xem']) {
                $this->E_don_vi_xu_ly_model->where('id_don_vi_xu_ly', $nguoiXuLy['id_don_vi_xu_ly'])->update([
                    'da_xem' => 1,
                    'nguoi_sua' => $auth['ql_nguoi_dung_id'],
                ]);

                $nguoiXuLyDaXem = $this->E_don_vi_xu_ly_da_xem_model
                    ->where('id_don_vi_xu_ly', $nguoiXuLy['id_don_vi_xu_ly'])
                    ->where('ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])
                    ->first();

                if (!$nguoiXuLyDaXem) {
                    $this->E_don_vi_xu_ly_da_xem_model->create([
                        'id_don_vi_xu_ly' => $nguoiXuLy['id_don_vi_xu_ly'],
                        'ql_nguoi_dung_id' => $auth['ql_nguoi_dung_id'],
                        'ngay_xem' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }

        $this->db->trans_commit();
        resSuccess(null, 'Phản hồi văn bản thành công');
    }

    public function change_status_put($id)
    {
        $status = commonRequest('trang_thai') ? commonRequest('trang_thai') : null;
        if (!in_array($status, ['TIEP_NHAN', 'CHO_LANH_DAO_BUT_PHE', 'DA_BUT_PHE', 'CHO_XU_LY', 'DA_XU_LY', 'LUU_TRU', 'CHUA_PHAN_HOI', 'DA_PHAN_HOI'])) {
            resBadrequest(['trang_thai' => 'Trạng thái không hợp lệ'], 'Trạng thái không hợp lệ');
        }
        $this->E_van_ban_model->where('id_van_ban', $id)->update([
            'trang_thai' => $this->common::STATUS_VAN_BAN_DEN[$status]['value']
        ]);
        resSuccess('Cập nhật thành công');
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
        $this->createLog('Update', 'Cập nhật văn bản đến đơn vị', $vanban, $this->E_van_ban_model->find($id), 'e_van_ban');
        resSuccess(null, 'Cập nhật thành công', REST_INSTANCE_Controller::HTTP_OK);
    }

    public function xem_van_ban_post()
    {
        $ids = commonRequest('ids') ?? [];
        $auth = $this->getUserLogin();

        $dsThongbao = [];
        $thongBaoNguoiDung = [];
        $this->db->trans_begin();
        try {
            foreach ($ids as $key => $id) {
                $vanban = $this->E_van_ban_model->find($id);
                if (!$vanban)  throw new Exception("Có văn bản không tồn tại");

                $xuly = $this->E_xu_ly_model->where('id_van_ban', $id)->first();
                if (!$xuly) throw new Exception("Có lỗi xảy ra");

                $Listdvxl = $this->E_don_vi_xu_ly_model
                    ->where('id_xu_ly', $xuly['id_xu_ly'])
                    ->where('id_don_vi', $auth['id_don_vi'])
                    // ->where('id_nguoi_xu_ly', $auth['ql_nguoi_dung_id'])
                    ->get();
                foreach ($Listdvxl as $key => $dvxl) {
                    if (!$dvxl['id_nguoi_xu_ly']) {
                        $this->E_don_vi_xu_ly_model->where('id_don_vi_xu_ly', $dvxl['id_don_vi_xu_ly'])->update(['da_xem' => 1]);
                    }

                    if ($dvxl['id_nguoi_xu_ly'] && (!$dvxl['da_xem'] || is_null($dvxl['da_xem']))) {
                        $this->E_don_vi_xu_ly_model->where('id_don_vi_xu_ly', $dvxl['id_don_vi_xu_ly'])->update(['da_xem' => 1]);
                    }

                    $checkexits = $this->E_don_vi_xu_ly_da_xem_model->where('id_don_vi_xu_ly', $dvxl['id_don_vi_xu_ly'])->where('ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])->first();
                    if (!$checkexits) {
                        $this->E_don_vi_xu_ly_da_xem_model->create([
                            'id_don_vi_xu_ly' => $dvxl['id_don_vi_xu_ly'],
                            'ql_nguoi_dung_id' => $auth['ql_nguoi_dung_id'],
                            'ngay_xem' => date('Y-m-d H:i:s')
                        ]);
                    }
                }

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

                        if ($thongBaoNguoiDung  && $thongBaoNguoiDung['ql_thong_bao_da_doc'] == 0) {
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
            }
            $this->db->trans_commit();
            resSuccess([], 'Đã cập nhật trạng thái đã xem.', 205);
        } catch (\Throwable $th) {
            //throw $th;
            $this->db->trans_rollback();
            resError('Lỗi, ' . $th->getMessage());
        }
    }

    public function phan_hoi_nhanh_vanban_post()
    {
        $ids = commonRequest('ids') ?? [];
        $auth = $this->getUserLogin();
        $donvi = $this->E_don_vi_model->find($auth['id_don_vi']);

        $this->db->trans_begin();
        try {
            foreach ($ids as $key => $id) {
                $vanban = $this->E_van_ban_model->find($id);
                if (!$vanban)  throw new Exception("Có văn bản không tồn tại");

                $trangThaiChuan = '';
                if ($vanban['loai_van_ban'] == Common::VAN_BAN_DEN) {
                    $trangThaiChuan = $this->common::STATUS_VAN_BAN_DEN['HOAN_THANH']['value'];
                } else if ($vanban['loai_van_ban'] == Common::VAN_BAN_DI) {
                    $trangThaiChuan = $this->common::STATUS_VAN_BAN_DI['HOAN_THANH']['value'];
                }

                if ($vanban['van_ban_chi_doc'] == 0) {
                    $this->E_bao_cao_model->create([
                        'noi_dung' => $donvi['ten_don_vi'] . ' đã nhận văn bản.',
                        'ngay_bao_cao' => date('Y-m-d H:i:s'),
                        'dinh_kem' => '',
                        'files_dinh_kem' => '',
                        'id_van_ban' => $id,
                        'id_don_vi_phan_hoi' => $auth['id_don_vi'],
                        'ngay_tao' => date('Y-m-d H:i:s'),
                        'ngay_sua' => date('Y-m-d H:i:s'),
                        'nguoi_tao' => $auth['ql_nguoi_dung_id'],
                        'trang_thai' => $trangThaiChuan,
                    ]);
                }
            }
            $this->db->trans_commit();
            resSuccess(null, 'Đã cập nhật phản hồi nhanh.');
        } catch (\Throwable $th) {
            //throw $th;
            $this->db->trans_rollback();
            resError('Lỗi, ' . $th->getMessage());
        }
    }

    public function search_files_post()
    {
        $auth = $this->getUserLogin();
        $data = [
            'keyword' => commonRequest('keyword') ?? '',
            'page' => commonRequest('page') ?? 1,
            'per_page' => commonRequest('per_page') ?? 10,
            'range' => commonRequest('range') ?? 'today',
        ];
        $files = $this->E_van_ban_model->getFiles_byLoaiVanBan(4, $data, $auth);
        resSuccess($files, 'Tìm kiếm file đính kèm thành công');
    }
}
