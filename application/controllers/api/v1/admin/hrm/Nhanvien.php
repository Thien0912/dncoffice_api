<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Hrm_quy_dinh_nghi_phep $Hrm_quy_dinh_nghi_phep
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 * @property Fileupload $fileupload
 * @property Hrm_nghi_phep_cong_don_model $Hrm_nghi_phep_cong_don_model
 * @property Hrm_danh_muc_nghi_phep_cong_don_model $Hrm_danh_muc_nghi_phep_cong_don_model
 * @property Validate $validate
 * @property Hrm_hop_dong_model $Hrm_hop_dong_model
 * @property Hrm_qua_trinh_cong_tac_model $Hrm_qua_trinh_cong_tac_model
 * @property Hrm_nhan_vien_bao_hiem_model $Hrm_nhan_vien_bao_hiem_model
 * @property Hrm_nhan_vien_khen_thuong_model $Hrm_nhan_vien_khen_thuong_model
 * @property Hrm_nhan_vien_dao_tao_model $Hrm_nhan_vien_dao_tao_model
 * @property Hrm_danh_gia_nhan_su_model $Hrm_danh_gia_nhan_su_model
 * @property Hrm_nhan_vien_bang_cap_model $Hrm_nhan_vien_bang_cap_model
 * @property Hrm_nhan_vien_kinh_nghiem_lam_viec_model $Hrm_nhan_vien_kinh_nghiem_lam_viec_model
 * @property Hrm_nhan_vien_cong_viec_model $Hrm_nhan_vien_cong_viec_model
 * @property E_don_vi_model $E_don_vi_model
 * @property Hrm_vi_tri_cong_viec_model $Hrm_vi_tri_cong_viec_model
 * @property Hrm_nhan_vien_tai_lieu_dinh_kem_model $Hrm_nhan_vien_tai_lieu_dinh_kem_model
 * @property Hrm_nhan_vien_di_cong_tac_model $Hrm_nhan_vien_di_cong_tac_model
 * @property Hrm_nhan_vien_thoi_viec_model $Hrm_nhan_vien_thoi_viec_model
 * @property Pxl $pxl
 * @property Common $common
 * @property Hrm_chung_chi_model $Hrm_chung_chi_model
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 * @property Hrm_nhan_vien_chinhtri_yte_quansu_model $Hrm_nhan_vien_chinhtri_yte_quansu_model
 * @property Hrm_nhan_vien_thong_tin_gia_dinh $Hrm_nhan_vien_thong_tin_gia_dinh 
 * @property Hrm_thu_tuc_thoi_viec_model $Hrm_thu_tuc_thoi_viec_model
 * @property Hrm_yeu_cau_cap_nhat_model $Hrm_yeu_cau_cap_nhat_model
 * @property Hrm_bang_luong_model $Hrm_bang_luong_model 
 */



class Nhanvien extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model([
            'Hrm_nhan_vien_model',
            'Hrm_quy_dinh_nghi_phep',
            'Ql_nguoi_dung_model',
            'Hrm_nghi_phep_cong_don_model',
            'Hrm_danh_muc_nghi_phep_cong_don_model',
            'Hrm_hop_dong_model',
            'Hrm_qua_trinh_cong_tac_model',
            'Hrm_nhan_vien_bao_hiem_model',
            'Hrm_nhan_vien_khen_thuong_model',
            'Hrm_nhan_vien_dao_tao_model',
            'Hrm_danh_gia_nhan_su_model',
            'Hrm_nhan_vien_bang_cap_model',
            'Hrm_nhan_vien_kinh_nghiem_lam_viec_model',
            'Hrm_nhan_vien_cong_viec_model',
            'E_don_vi_model',
            'Hrm_vi_tri_cong_viec_model',
            'Hrm_nhan_vien_tai_lieu_dinh_kem_model',
            'Hrm_nhan_vien_di_cong_tac_model',
            'Hrm_nhan_vien_thoi_viec_model',
            'Hrm_chung_chi_model',
            'Ql_nguoi_dung_model',
            'Hrm_nhan_vien_chinhtri_yte_quansu_model',
            'Hrm_nhan_vien_thong_tin_gia_dinh',
            'Hrm_thu_tuc_thoi_viec_model',
            'Hrm_bang_luong_model',
            'Hrm_yeu_cau_cap_nhat_model',
        ]);
        $this->load->library(['Validator', 'Fileupload', 'Validate', 'Pxl', 'Common']);
        $this->load->helper(['hrm']);
    }

    public function index_post()
    {
        $postData = $this->post();
        $response = $this->Hrm_nhan_vien_model->getAllNhanvien($postData);

        if (!empty($response['data'])) {
            // append_hhhv_to_list($response['data'], 'ho_va_ten');
        }

        resSuccess([
            'draw' => isset($postData['draw']) ? intval($postData['draw']) : 1,
            'data' => $response['data'] ?? [],
            'recordsTotal' => $response['recordsTotal'] ?? count($response['data']),
            'recordsFiltered' => $response['recordsFiltered'] ?? count($response['data']),
            'sql' => $response['sql'] ?? '',
            'filters' => $response['filters'] ?? [],
        ], 'Successful');
    }

    public function create_post()
    {

        // $data = commonRequestAll();

        // $rules = [
        //     'ma_nhan_vien' => 'required|unique:nhan_vien,ma_nhan_vien',
        //     'ho_va_ten' => 'required|string|max:255',
        //     'gioi_tinh' => 'required|in:1,2,3',
        //     'ngay_sinh' => 'required|date',
        //     'mst_ca_nhan' => 'nullable|regex:/^\d{10,13}$/',
        //     'id_don_vi_cong_tac' => 'required|integer',
        //     'id_vi_tri_cong_viec' => 'required|integer',
        //     'id_dan_toc' => 'required|integer',
        //     'id_ton_giao' => 'required|integer',
        //     'id_quoc_tich' => 'required|integer',
        //     'cccd_so' => 'required|regex:/^\d{12}$/|unique:nhan_vien,cccd_so',
        //     'cccd_ngay_cap' => 'required|date',
        //     'cccd_noi_cap' => 'required|string|max:255',
        //     'cccd_ngay_het_han' => 'nullable|date',
        //     'ho_chieu_so' => 'nullable|string|max:50|unique:nhan_vien,ho_chieu_so',
        //     'ho_chieu_ngay_cap' => 'nullable|date',
        //     'ho_chieu_noi_cap' => 'nullable|string|max:255',
        //     'ho_chieu_ngay_het_han' => 'nullable|date',
        //     'trinh_do_vh' => 'required|string|max:255',
        //     'trinh_do_dt' => 'required|string|max:255',
        //     'noi_dt' => 'nullable|string|max:255',
        //     'khoa_dt' => 'nullable|string|max:255',
        //     'nganh_dt' => 'nullable|string|max:255',
        //     'nam_tn' => 'nullable|integer|digits:4',
        //     'xep_loai_tn' => 'nullable|string|max:50',
        //     'hktt_id_quoc_gia' => 'required|integer',
        //     'hktt_id_tinh_tp' => 'required|integer',
        //     'hktt_id_quan_huyen' => 'required|integer',
        //     'hktt_id_xa_phuong' => 'required|integer',
        //     'hktt_so_nha' => 'nullable|string|max:255',
        //     'hktt_dia_chi' => 'required|string|max:255',
        //     'hktt_so_ho_khau' => 'nullable|string|max:50',
        //     'hktt_ma_so_ho_gd' => 'nullable|string|max:50',
        //     'hktt_la_chu_ho' => 'nullable|boolean',
        //     'so_dien_thoai' => 'required|regex:/^0[0-9]{9}$/',
        //     'email' => 'required|email|unique:nhan_vien,email',
        //     'que_quan' => 'nullable|string|max:255',
        //     'cohn_giong_hktt' => 'nullable|boolean',
        //     'cohn_id_quoc_gia' => 'nullable|integer',
        //     'cohn_id_tinh_tp' => 'nullable|integer',
        //     'cohn_id_quan_huyen' => 'nullable|integer',
        //     'cohn_id_xa_phuong' => 'nullable|integer',
        //     'cohn_so_nha' => 'nullable|string|max:255',
        //     'cohn_dia_chi' => 'nullable|string|max:255',
        //     'avatar' => 'nullable|mimes:jpeg,png,jpg,gif|max:2048',
        //     'created_user_id' => 'required|integer',
        //     'updated_user_id' => 'nullable|integer',
        //     'ql_nguoi_dung_id' => 'nullable|integer',
        // ];

        // $customMessages = [
        //     'ma_nhan_vien.required' => 'Mã nhân viên là bắt buộc.',
        //     'ma_nhan_vien.unique' => 'Mã nhân viên đã tồn tại.',
        //     'ho_va_ten.required' => 'Họ và tên là bắt buộc.',
        //     'ho_va_ten.string' => 'Họ và tên phải là chuỗi.',
        //     'ho_va_ten.max' => 'Họ và tên không được vượt quá 255 ký tự.',
        //     'gioi_tinh.required' => 'Giới tính là bắt buộc.',
        //     'gioi_tinh.in' => 'Giới tính phải là Nam, Nữ hoặc Khác.',
        //     'ngay_sinh.required' => 'Ngày sinh là bắt buộc.',
        //     'ngay_sinh.date' => 'Ngày sinh phải có định dạng ngày hợp lệ.',
        //     'mst_ca_nhan.regex' => 'Mã số thuế cá nhân phải có từ 10 đến 13 chữ số.',
        //     'id_don_vi_cong_tac.required' => 'Đơn vị công tác là bắt buộc.',
        //     'id_don_vi_cong_tac.integer' => 'Đơn vị công tác không hợp lệ.',
        //     'id_vi_tri_cong_viec.required' => 'Vị trí công việc là bắt buộc.',
        //     'id_vi_tri_cong_viec.integer' => 'Vị trí công việc không hợp lệ.',
        //     'id_dan_toc.required' => 'Dân tộc là bắt buộc.',
        //     'id_dan_toc.integer' => 'Dân tộc không hợp lệ.',
        //     'id_ton_giao.required' => 'Tôn giáo là bắt buộc.',
        //     'id_ton_giao.integer' => 'Tôn giáo không hợp lệ.',
        //     'id_quoc_tich.required' => 'Quốc tịch là bắt buộc.',
        //     'id_quoc_tich.integer' => 'Quốc tịch không hợp lệ.',
        //     'cccd_so.required' => 'Số CCCD là bắt buộc.',
        //     'cccd_so.regex' => 'Số CCCD phải có đúng 12 chữ số.',
        //     'cccd_so.unique' => 'Số CCCD đã tồn tại.',
        //     'cccd_ngay_cap.required' => 'Ngày cấp CCCD là bắt buộc.',
        //     'cccd_ngay_cap.date' => 'Ngày cấp CCCD phải có định dạng ngày hợp lệ.',
        //     'cccd_noi_cap.required' => 'Nơi cấp CCCD là bắt buộc.',
        //     'cccd_noi_cap.string' => 'Nơi cấp CCCD phải là chuỗi.',
        //     'cccd_noi_cap.max' => 'Nơi cấp CCCD không được vượt quá 255 ký tự.',
        //     'cccd_ngay_het_han.date' => 'Ngày hết hạn CCCD phải có định dạng ngày hợp lệ.',
        //     'ho_chieu_so.string' => 'Số hộ chiếu phải là chuỗi.',
        //     'ho_chieu_so.max' => 'Số hộ chiếu không được vượt quá 50 ký tự.',
        //     'ho_chieu_so.unique' => 'Số hộ chiếu đã tồn tại.',
        //     'ho_chieu_ngay_cap.date' => 'Ngày cấp hộ chiếu phải có định dạng ngày hợp lệ.',
        //     'ho_chieu_noi_cap.string' => 'Nơi cấp hộ chiếu phải là chuỗi.',
        //     'ho_chieu_noi_cap.max' => 'Nơi cấp hộ chiếu không được vượt quá 255 ký tự.',
        //     'ho_chieu_ngay_het_han.date' => 'Ngày hết hạn hộ chiếu phải có định dạng ngày hợp lệ.',
        //     'trinh_do_vh.required' => 'Trình độ văn hóa là bắt buộc.',
        //     'trinh_do_vh.string' => 'Trình độ văn hóa phải là chuỗi.',
        //     'trinh_do_vh.max' => 'Trình độ văn hóa không được vượt quá 255 ký tự.',
        //     'trinh_do_dt.required' => 'Trình độ đào tạo là bắt buộc.',
        //     'trinh_do_dt.string' => 'Trình độ đào tạo phải là chuỗi.',
        //     'trinh_do_dt.max' => 'Trình độ đào tạo không được vượt quá 255 ký tự.',
        //     'noi_dt.string' => 'Nơi đào tạo phải là chuỗi.',
        //     'noi_dt.max' => 'Nơi đào tạo không được vượt quá 255 ký tự.',
        //     'khoa_dt.string' => 'Khóa đào tạo phải là chuỗi.',
        //     'khoa_dt.max' => 'Khóa đào tạo không được vượt quá 255 ký tự.',
        //     'nganh_dt.string' => 'Ngành đào tạo phải là chuỗi.',
        //     'nganh_dt.max' => 'Ngành đào tạo không được vượt quá 255 ký tự.',
        //     'nam_tn.integer' => 'Năm tốt nghiệp không hợp lệ.',
        //     'nam_tn.digits' => 'Năm tốt nghiệp không hợp lệ.',
        //     'xep_loai_tn.string' => 'Xếp loại tốt nghiệp không hợp lệ.',
        //     'xep_loai_tn.max' => 'Xếp loại tốt nghiệp không được vượt quá 50 ký tự.',
        //     'hktt_id_quoc_gia.required' => 'Quốc gia hộ khẩu thường trú là bắt buộc.',
        //     'hktt_id_quoc_gia.integer' => 'Quốc gia hộ khẩu thường trú không hợp lệ.',
        //     'hktt_id_tinh_tp.required' => 'Tỉnh/Thành phố hộ khẩu thường trú là bắt buộc.',
        //     'hktt_id_tinh_tp.integer' => 'Tỉnh/Thành phố hộ khẩu thường trú không hợp lệ.',
        //     'hktt_id_quan_huyen.required' => 'Quận/Huyện hộ khẩu thường trú là bắt buộc.',
        //     'hktt_id_quan_huyen.integer' => 'Quận/Huyện hộ khẩu thường trú không hợp lệ.',
        //     'hktt_id_xa_phuong.required' => 'Xã/Phường hộ khẩu thường trú là bắt buộc.',
        //     'hktt_id_xa_phuong.integer' => 'Xã/Phường hộ khẩu thường trú không hợp lệ.',
        //     'hktt_so_nha.string' => 'Số nhà hộ khẩu thường trú không hợp lệ.',
        //     'hktt_dia_chi.required' => 'Địa chỉ hộ khẩu thường trú là bắt buộc.',
        //     'hktt_dia_chi.string' => 'Địa chỉ hộ khẩu thường trú không hợp lệ.',
        //     'hktt_so_ho_khau.string' => 'Số hộ khẩu không hợp lệ.',
        //     'hktt_ma_so_ho_gd.string' => 'Mã số hộ gia đình không hợp lệ.',
        //     'hktt_la_chu_ho.boolean' => 'Chủ hộ không hợp lệ.',
        //     'so_dien_thoai.required' => 'Số điện thoại là bắt buộc.',
        //     'so_dien_thoai.regex' => 'Số điện thoại phải có 10 chữ số và bắt đầu bằng số 0.',
        //     'email.required' => 'Email là bắt buộc.',
        //     'email.email' => 'Email không hợp lệ.',
        //     'email.unique' => 'Email đã tồn tại.',
        //     'que_quan.string' => 'Quê quán không hợp lệ.',
        //     'cohn_giong_hktt.boolean' => 'Trường có hộ khẩu giống hộ khẩu thường trú phải không hợp lệ.',
        //     'cohn_id_quoc_gia.integer' => 'Quốc gia cư trú hiện nay không hợp lệ.',
        //     'cohn_id_tinh_tp.integer' => 'Tỉnh/Thành phố cư trú hiện nay không hợp lệ.',
        //     'cohn_id_quan_huyen.integer' => 'Quận/Huyện cư trú hiện nay không hợp lệ.',
        //     'cohn_id_xa_phuong.integer' => 'Xã/Phường cư trú hiện nay không hợp lệ.',
        //     'cohn_so_nha.string' => 'Số nhà cư trú hiện nay không hợp lệ.',
        //     'cohn_dia_chi.string' => 'Địa chỉ cư trú hiện nay không hợp lệ.',
        //     'avatar.mimes' => 'Ảnh đại diện phải có định dạng jpeg, png, jpg hoặc gif.',
        //     'avatar.max' => 'Ảnh đại diện không được vượt quá 2MB.',
        //     'created_user_id.required' => 'Người tạo là bắt buộc.',
        //     'created_user_id.integer' => 'Người tạo không hợp lệ.',
        //     'updated_user_id.integer' => 'Người cập nhật không hợp lệ.',
        //     'ql_nguoi_dung_id.integer' => 'Người dùng quản lý không hợp lệ.',
        // ];

        // $validator = new Validator();
        // $validator->setCustomMessages($customMessages);

        // if (!$validator->validate($data, $rules)) {
        //     resBadrequest($validator->errors());
        // }

        // resSuccess($data);
        // die();

        $payload = commonRequest('payload') ? json_decode(commonRequest('payload'), false) : null;

        if (!$payload) {
            resBadrequest(['message' => 'Invalid payload']);
        }
        // dd(json_decode(commonRequest('payload'), true));
        // $errors = [];
        $errorsHrmcongviec = [];
        $errorsHrmnhanvien = [];
        $errorsBaohiem = [];

        //Thông tin chung
        $dataNhanvien = [];
        $hrm_nhan_vien = $payload->hrm_nhan_vien ?? null;
        if ($hrm_nhan_vien) {
            foreach ($hrm_nhan_vien as $key => $item) {
                if ($item == '') {
                    $dataNhanvien[$key] = null;
                } else {
                    $dataNhanvien[$key] = $this->format_date_ddmmyyyy($item);
                }
            }
        }
        if (!$dataNhanvien['id_vi_tri_cong_viec'] || !$dataNhanvien['id_don_vi_cong_tac']) {
            resBadrequest([
                'hrm_nhan_vien' => [
                    'id_vi_tri_cong_viec' => 'Vị trí công việc hoặc Đơn vị công tác không hợp lệ',
                    'id_don_vi_cong_tac' => 'Vị trí công việc hoặc Đơn vị công tác không hợp lệ',
                ]
            ], 'Vị trí công việc hoặc Đơn vị công tác không hợp lệ', 500);
        }
        // dd($dataNhanvien);
        // resError('Error', 500, $dataNhanvien);

        //Thông tin công việc
        $dataCongviec = [];
        $hrm_cong_viec = $payload->hrm_nhan_vien_cong_viec ?? null;
        if ($hrm_cong_viec) {
            foreach ($hrm_cong_viec as $key => $item) {
                if ($item == '') {
                    $dataCongviec[$key] = null;
                } else {
                    $dataCongviec[$key] = $item;
                }
            }
        }
        // resError('Error', 500, $dataCongviec);

        $dataBaohiem = [];
        $hrm_bao_hiem = $payload->hrm_nhan_vien_bao_hiem ?? null;
        if ($hrm_bao_hiem) {
            foreach ($hrm_bao_hiem as $key => $item) {
                if ($item == '') {
                    $dataBaohiem[$key] = null;
                } else {
                    $dataBaohiem[$key] = $item;
                }
            }
        }

        // $this->moveArrayToArray(['ma_cham_cong', 'id_ca_lam_viec'], $dataNhanvien, $dataCongviec);
        $this->moveArrayToArray(['ma_cham_cong', 'id_ca_lam_viec'], $dataCongviec, $dataNhanvien);
        // dd([$dataNhanvien, $dataCongviec, $dataBaohiem]);

        //Thông tin chính trị, y tế, quân sự
        // $dataChinhtriytequansu = [];
        // $hrm_nhan_vien_chinhtri_yte_quansu = $payload->hrm_nhan_vien_chinhtri_yte_quansu ?? null;
        // if ($hrm_nhan_vien_chinhtri_yte_quansu) {
        //     foreach ($hrm_nhan_vien_chinhtri_yte_quansu as $key => $item) {
        //         $dataChinhtriytequansu[$key] = $item;
        //     }
        // }

        /**
         * ==============Validate===============
         */
        //Thông tin chung
        $tempData = array_merge($dataNhanvien, $dataCongviec, $dataBaohiem);
        $this->validate->setData($tempData);
        if (!$this->validate->setRulesAndRun('nhanvien', 'create')) {
            foreach ($this->validate->errors() as $key => $item) {
                if (array_key_exists($key, $dataNhanvien)) {
                    $errorsHrmnhanvien[$key] = $item[0];
                }

                if (array_key_exists($key, $dataCongviec)) {
                    $errorsHrmcongviec[$key] = $item[0];
                }

                if (array_key_exists($key, $dataBaohiem)) {
                    $errorsBaohiem[$key] = $item[0];
                }
            }
            // $errorsHrmnhanvien = array_merge($errorsHrmnhanvien, $this->validate->errors());
        }
        //Thông tin công việc
        // $this->validate->setData($dataCongviec);
        // if (!$this->validate->setRulesAndRun('nhanvien', 'create_cong_viec')) {
        //     $errorsHrmcongviec = array_merge($errorsHrmcongviec, $this->validate->errors());
        // } 

        //Thông tin quá trình công tác
        $data_quatrinhcongtac = [
            'ngay_bat_dau' => date('Y-m-d'),
            'ngay_ket_thuc' => null,
            'id_don_vi' => $payload->hrm_nhan_vien->id_don_vi_cong_tac ?? null,
            'id_vi_tri_cong_viec' => $payload->hrm_nhan_vien->id_vi_tri_cong_viec ?? null,
            'ghi_chu' => ''
        ];

        // Thông tin bằng cấp 
        $dataBangcap = [
            'tu_thang' => null,
            'den_thang' => null,
            'noi_dao_tao' => $payload->hrm_nhan_vien->noi_dt ?? null,
            'chuyen_nganh' => $payload->hrm_nhan_vien->nganh_dt ?? null,
            'trinh_do_dt' => $payload->hrm_nhan_vien->trinh_do_dt ?? null,
            'xep_loai_dt' => $payload->hrm_nhan_vien->xep_loai_dt ?? null,
        ];
        // dd($dataBangcap);

        //Upload avatar
        $folderName = 'employees/avatars';
        if (isset($_FILES['avatar'])) {
            if ($_FILES['avatar']) {
                $uploadedFile = $this->fileupload->upload($_FILES['avatar'], $folderName);
                if (!$uploadedFile['success']) {
                    $errorsHrmnhanvien = array_merge($errorsHrmnhanvien, ['avatar' => 'Không thể tải lên ảnh đại diện']);
                } else {
                    $dataNhanvien['avatar'] = $uploadedFile['file_path'];
                }
            }
        }

        if (!empty($errorsHrmnhanvien) || !empty($errorsHrmcongviec) || !empty($errorsBaohiem)) {
            resBadrequest([
                'hrm_nhan_vien' => $errorsHrmnhanvien,
                'hrm_nhan_vien_cong_viec' => $errorsHrmcongviec,
                'hrm_nhan_vien_bao_hiem' => $errorsBaohiem,
            ]);
        }
        $auth = $this->getUserLogin();
        $now = date('Y-m-d H:i:s');
        $userId = $auth['ql_nguoi_dung_id'];

        $this->db->trans_start();
        if (!empty($dataNhanvien)) {
            $dataNhanvien += [
                'created_at' => $now,
                'created_user_id' => $userId,
                'updated_at' => $now,
                'updated_user_id' => $userId,
            ];
            $nhanvien = $this->Hrm_nhan_vien_model->create($dataNhanvien);
        }
        //Công việc
        if (!empty($dataCongviec) && $nhanvien['id_nhan_vien']) {
            $dataCongviec += [
                'id_nhan_vien' => $nhanvien['id_nhan_vien'],
                'created_at' => $now,
                'created_user_id' => $userId,
                'updated_at' => $now,
                'updated_user_id' => $userId,
            ];
            $congviec = $this->Hrm_nhan_vien_cong_viec_model->create($dataCongviec);
        }

        //Bảo hiểm
        if (!empty($dataBaohiem) &&  $nhanvien['id_nhan_vien']) {
            $dataBaohiem['id_nhan_vien'] = $nhanvien['id_nhan_vien'];
            $baohiem = $this->Hrm_nhan_vien_bao_hiem_model->create($dataBaohiem);
        }

        //Quá trình công tác
        if (!empty($data_quatrinhcongtac) && $nhanvien['id_nhan_vien']) {
            $data_quatrinhcongtac['id_nhan_vien'] = $nhanvien['id_nhan_vien'];
            $this->Hrm_qua_trinh_cong_tac_model->create($data_quatrinhcongtac);
        }

        //Bằng cấp
        if (!empty($dataBangcap) && $nhanvien['id_nhan_vien']) {
            $dataBangcap['id_nhan_vien'] = $nhanvien['id_nhan_vien'];
            $this->Hrm_nhan_vien_bang_cap_model->create($dataBangcap);
        }


        //Thêm người dùng
        $dataNguoidung = [
            'ql_nguoi_dung_ho_ten' => $nhanvien['ho_va_ten'],
            'ql_nguoi_dung_email' => $nhanvien['email'] ?? NULL,
            'ql_nguoi_dung_mat_khau' => password_hash(substr(bin2hex(random_bytes(8)), 0, 8), PASSWORD_BCRYPT, ['cost' => 12]), //hash ngẫu nhiên
            'ql_nguoi_dung_avatar' => $nhanvien['avatar'],
            'ql_nguoi_dung_loai' => 2,
            'ql_nguoi_dung_ngay_tao' => date('Y-m-d H:i:s'),
            'ql_nguoi_dung_ngay_cap_nhat' => date('Y-m-d H:i:s'),
            'active_flag' => 1,
            'ql_nguoi_dung_is_admin' => 0,
            'ql_nguoi_dung_la_lanh_dao' => 0,
            'id_don_vi' => $nhanvien['id_don_vi_cong_tac']
        ];
        $nguoidungId = $this->Ql_nguoi_dung_model->insert($dataNguoidung);

        $this->Hrm_nhan_vien_model->where('id_nhan_vien', $nhanvien['id_nhan_vien'])->update(['ql_nguoi_dung_id' => $nguoidungId]);

        $this->db->trans_commit();

        resSuccess($nhanvien, 'Thêm thành công');
    }

    public function show_get($id)
    {
        $nhanvien = $this->Hrm_nhan_vien_model
            ->select('hrm_vi_tri_cong_viec.*, 
                        e_don_vi.*, 
                        hrm_nhan_vien_cong_viec.*,
                        provinces.name as province_name, 
                        wards.name as ward_name, 
                        dm_quoc_gia.ten as ten_quoc_gia, 
                        hrm_nhan_vien.*')
            ->join('hrm_vi_tri_cong_viec', 'hrm_nhan_vien.id_vi_tri_cong_viec = hrm_vi_tri_cong_viec.id_vi_tri_cong_viec', 'left')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_nhan_vien.id_don_vi_cong_tac', 'left')
            ->join('hrm_nhan_vien_cong_viec', 'hrm_nhan_vien_cong_viec.id_nhan_vien = hrm_nhan_vien.id_nhan_vien', 'left')
            ->join('dm_quoc_gia', 'dm_quoc_gia.id_quoc_gia = hrm_nhan_vien.cohn_id_quoc_gia', 'left')
            // ->join('province', 'province.id = hrm_nhan_vien.cohn_id_tinh_tp', 'left')
            // ->join('district', 'district.id = hrm_nhan_vien.cohn_id_quan_huyen', 'left')
            // ->join('wards', 'wards.id = hrm_nhan_vien.cohn_id_xa_phuong', 'left')
            ->join('provinces', 'provinces.id = hrm_nhan_vien.cohn_id_tinh_tp', 'left')
            ->join('wards', 'wards.id = hrm_nhan_vien.cohn_id_xa_phuong', 'left')
            ->where('hrm_nhan_vien.id_nhan_vien', $id)
            ->where('hrm_nhan_vien.deleted_at IS NULL')
            ->first();

        if (!$nhanvien) {
            resError('Employee not found', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }

        $avatar = file_exists($nhanvien['avatar']) ? $nhanvien['avatar'] : 'assets/avatar-users/default.jpg';
        //Thông tin chung
        $nhanvien['thong_tin_chung'] = [
            'ma_nhan_vien' => $nhanvien['ma_nhan_vien'],
            'ho_va_ten' => $nhanvien['ho_va_ten'],
            'gioi_tinh' => $nhanvien['gioi_tinh'],
            'ngay_sinh' => $nhanvien['ngay_sinh'],
            'cccd_so' => $nhanvien['cccd_so'],
            'cccd_ngay_cap' => $nhanvien['cccd_ngay_cap'],
            'cccd_noi_cap' => $nhanvien['cccd_noi_cap'],
            'cccd_ngay_het_han' => $nhanvien['cccd_ngay_het_han'],
            'ho_chieu_so' => $nhanvien['ho_chieu_so'],
            'ten_cong_viec' => $nhanvien['ten_cong_viec'],
            'ten_don_vi' => $nhanvien['ten_don_vi'],
            'ngay_thu_viec' => $nhanvien['ngay_thu_viec'],
            'ngay_lam_chinh_thuc' => $nhanvien['ngay_lam_chinh_thuc'],
            'avatar' => $avatar,
        ];
        // foreach (array_keys($nhanvien['thong_tin_chung']) as $key) {
        //     unset($nhanvien[$key]);
        // }


        //Thông tin liên hệ
        $nhanvien['thong_tin_lien_he'] = [
            'so_dien_thoai' => $nhanvien['so_dien_thoai'],
            'email' => $nhanvien['email'],
            'que_quan' => $nhanvien['que_quan'],
            'cohn_giong_hktt' => $nhanvien['cohn_giong_hktt'],
            'cohn_id_quoc_gia' => $nhanvien['cohn_id_quoc_gia'],
            'cohn_id_tinh_tp' => $nhanvien['cohn_id_tinh_tp'],
            'cohn_id_quan_huyen' => $nhanvien['cohn_id_quan_huyen'],
            'cohn_id_xa_phuong' => $nhanvien['cohn_id_xa_phuong'],
            'cohn_quoc_gia' => $nhanvien['ten_quoc_gia'],
            'cohn_tinh_tp' => $nhanvien['province_name'],
            // 'cohn_quan_huyen' => $nhanvien['district_name'],
            'cohn_xa_phuong' => $nhanvien['ward_name'],
            'cohn_so_nha' => $nhanvien['cohn_so_nha'],
            'cohn_dia_chi' => $nhanvien['cohn_dia_chi'],
            'avatar' => $avatar
        ];

        // foreach (array_keys($nhanvien['thong_tin_lien_he']) as $key) {
        //     unset($nhanvien[$key]);
        // }

        //Thông tin bảo hiểm
        $mucDongBaoHiem = '';

        $nhanvien['thong_tin_bao_hiem'] = $this->Hrm_nhan_vien_bao_hiem_model->where('id_nhan_vien', $id)->first();

        //Hợp đồng lao động
        $hopdong = $this->Hrm_hop_dong_model
            ->where('id_nhan_vien', $id)
            ->where('deleted_at IS NULL')
            ->join('hrm_vi_tri_cong_viec', 'hrm_vi_tri_cong_viec.id_vi_tri_cong_viec = hrm_hop_dong.id_vi_tri_cong_viec', 'left')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_hop_dong.id_don_vi_cong_tac', 'left')
            ->orderBy('ngay_bat_dau', 'DESC')
            ->get();

        $current = date('Y-m-d');
        foreach ($hopdong as &$hd) {
            if (is_null($hd["ngay_ket_thuc"])) {
                // $hd["dang_hieu_luc"] = 1; // Hợp đồng không có ngày hết hạn thì luôn hiệu lực
                $mucDongBaoHiem = $hd['muc_luong_bao_hiem'];
            } else {
                // $hd["dang_hieu_luc"] = ($hd["ngay_ket_thuc"] >= $current) ? 1 : 0;

                if ($hd["ngay_ket_thuc"] >= $current) {
                    $mucDongBaoHiem = $hd['muc_luong_bao_hiem'];
                }
            }

            $hd['tong_phu_luc'] = $this->db
                ->from('hrm_hop_dong_phu_luc')
                ->where('id_hop_dong', $hd['id_hop_dong'])
                ->where('ngay_hieu_luc IS NOT NULL')
                ->count_all_results();

            $phuLucHopDong = $this->db
                ->select('*')
                ->from('hrm_hop_dong_phu_luc')
                ->where('id_hop_dong', $hd['id_hop_dong'])
                ->order_by('id_hop_dong_phu_luc', 'DESC')
                ->get()
                ->row_array();

            if ($phuLucHopDong) {
                $hd['ngay_hieu_luc_phu_luc'] = $phuLucHopDong['ngay_hieu_luc'];
            } else {
                $hd['ngay_hieu_luc_phu_luc'] = '';
            }
        }
        unset($hd);

        $nhanvien['hop_dong'] = $hopdong;

        $nhanvien['thong_tin_bao_hiem']['muc_luong_bao_hiem'] = number_format((float) $mucDongBaoHiem, 0, ',', '.');

        //Quá trình công tác
        $nhanvien['qua_trinh_cong_tac'] = $this->Hrm_qua_trinh_cong_tac_model
            ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_qua_trinh_cong_tac.id_don_vi', 'left')
            ->join('hrm_vi_tri_cong_viec', 'hrm_vi_tri_cong_viec.id_vi_tri_cong_viec = hrm_qua_trinh_cong_tac.id_vi_tri_cong_viec', 'left')
            ->where('hrm_qua_trinh_cong_tac.id_nhan_vien', $id)
            ->orderBy('hrm_qua_trinh_cong_tac.ngay_bat_dau', 'DESC')
            ->select('hrm_qua_trinh_cong_tac.*, e_don_vi.ten_don_vi, hrm_vi_tri_cong_viec.*')
            ->get();

        $dantoc = $this->db->where('id_dan_toc', $nhanvien['id_dan_toc'])->get('dm_dan_toc')->row_array();
        $ton_giao = $this->db->where('id_ton_giao', $nhanvien['id_ton_giao'])->get('dm_ton_giao')->row_array();
        $quoc_tich = $this->db->where('id_quoc_gia', $nhanvien['id_quoc_tich'])->get('dm_quoc_gia')->row_array();

        $nhanvien['ten_ton_giao'] = $ton_giao['ten'] ?? null;
        $nhanvien['ten_dan_toc'] = $dantoc['ten'] ?? null;
        $nhanvien['ten_quoc_tich'] = $quoc_tich['ten'] ?? null;

        //Thu nhập thường xuyên
        $nhanvien['thu_nhap_thuong_xuyen'] = [];

        //Khấu trừ thường xuyên
        $nhanvien['khau_tru_thuong_xuyen'] = [];

        //Lịch sử lương
        $nhanvien['lich_su_luong'] = [];

        // Lương
        $nhanvien['luong'] = [];

        //Thuế
        $nhanvien['thue'] = $this->db->where('id_nhan_vien', $id)->order_by('thang_tinh_thue', 'DESC')->get('hrm_nhan_vien_thue')->result_array();

        //Quá trình đóng bảo hiểm
        $nhanvien['qua_trinh_dong_bao_hiem'] = $this->db->where('id_nhan_vien', $id)->order_by('thang', 'DESC')->get('hrm_bao_hiem_dong')->result_array();

        //Phúc lợi
        $nhanvien['phuc_loi'] = $this->db->where('id_nhan_vien', $id)
            ->join('hrm_phuc_loi', 'hrm_phuc_loi.id_phuc_loi = hrm_nhan_vien_phuc_loi.id_phuc_loi', 'left')
            ->order_by('hrm_nhan_vien_phuc_loi.ngay_cap', 'desc')
            ->get('hrm_nhan_vien_phuc_loi')
            ->result_array();

        //Khen thưởng
        $nhanvien['khen_thuong'] = $this->Hrm_nhan_vien_khen_thuong_model->where('id_nhan_vien', $id)->orderBy('ngay_khen_thuong', 'DESC')->get();
        $nhanvien['thuong_danh_sach'] = $this->db
            ->select('hrm_thuong_danh_sach.*, hrm_thuong.ten_thuong, hrm_thuong.loai_thuong, hrm_thuong.trang_thai')
            ->where('id_nhan_vien', $id)
            ->from('hrm_thuong_danh_sach')
            ->join('hrm_thuong', 'hrm_thuong.id = hrm_thuong_danh_sach.id_thuong', 'left')
            ->get()
            ->result();

        //Quá trình đào tạo
        $nhanvien['qua_trinh_dao_tao'] = $this->Hrm_nhan_vien_dao_tao_model
            ->join('hrm_dao_tao', 'hrm_dao_tao.id_dao_tao = hrm_nhan_vien_dao_tao.id_dao_tao', 'left')
            ->where('hrm_nhan_vien_dao_tao.id_nhan_vien', $id)
            ->get();

        //Đánh giá
        $nhanvien['danh_gia'] = $this->Hrm_danh_gia_nhan_su_model->where('id_nhan_vien', $id)->orderBy('thang', 'DESC')->get();

        //Bằng cấp
        $nhanvien['bang_cap'] = $this->Hrm_nhan_vien_bang_cap_model->where('id_nhan_vien', $id)->get();

        //Chứng chỉ
        $nhanvien['chung_chi'] = $this->Hrm_chung_chi_model->where('id_nhan_vien', $id)->get();
        //Kinh nghiệm làm việc
        $nhanvien['kinh_nghiem_lam_viec'] = $this->Hrm_nhan_vien_kinh_nghiem_lam_viec_model
            ->where('id_nhan_vien', $id)
            ->where('deleted_at IS NULL')
            ->orderBy('ngay_bat_dau', 'DESC')
            ->get();

        //Nhân viên đi công tác
        $nhanvien['nhan_vien_di_cong_tac'] = $this->Hrm_nhan_vien_di_cong_tac_model->where('id_nhan_vien', $id)->get();


        //Thủ tục thôi việc
        $nhanvien['thu_tuc_thoi_viec'] = $this->Hrm_thu_tuc_thoi_viec_model
            // ->where("id_tttv NOT IN (SELECT id_tttv FROM hrm_nhan_vien_thoi_viec where id_nhan_vien = '$id')", null, false)
            ->get();

        //Thôi việc
        $nhanvien['thoi_viec'] = $this->Hrm_nhan_vien_thoi_viec_model
            ->join('hrm_thu_tuc_thoi_viec', 'hrm_thu_tuc_thoi_viec.id_tttv = hrm_nhan_vien_thoi_viec.id_tttv', 'left')
            ->where('hrm_nhan_vien_thoi_viec.id_nhan_vien', $id)
            ->where('hrm_nhan_vien_thoi_viec.deleted_at IS NULL')
            ->get();

        //Tài liệu đính kèm
        $nhanvien['tai_lieu_dinh_kem'] = $this->Hrm_nhan_vien_tai_lieu_dinh_kem_model->where('id_nhan_vien', $id)->get();

        //Thông tin gia đình
        $nhanvien['thong_tin_gia_dinh'] = $this->Hrm_nhan_vien_thong_tin_gia_dinh->where('id_nhan_vien', $id)->get();

        // Thông tin yêu cầu cập nhật
        $id_yeu_cau_cap_nhat = commonRequest('id_yeu_cau_cap_nhat') ?? null;
        if ($id_yeu_cau_cap_nhat) {
            $nhanvien['yeu_cau_cap_nhat'] = $this->Hrm_yeu_cau_cap_nhat_model->find($id_yeu_cau_cap_nhat);
        }

        resSuccess($nhanvien, 'success', REST_Controller::HTTP_OK);
    }

    public function update_post($id)
    {
        $nhanvien = $this->Hrm_nhan_vien_model->where('id_nhan_vien', $id)->where('deleted_at IS NULL')->first();
        if (!$nhanvien) {
            resError('Employee not found', REST_Controller::HTTP_NOT_FOUND);
        }

        $payload = commonRequest('payload') ? json_decode(commonRequest('payload')) : null;
        // dd(json_decode(commonRequest('payload'), true));
        if (!$payload) {
            resBadrequest(['message' => 'Invalid payload']);
        }

        $errorsHrmcongviec = [];
        $errorsHrmnhanvien = [];
        $errorsBaohiem = [];
        $errorsHrmchinhtriytequansu = [];

        //Thông tin chung
        $dataNhanvien = [];
        $hrm_nhan_vien = $payload->hrm_nhan_vien ?? null;
        if ($hrm_nhan_vien) {
            foreach ($hrm_nhan_vien as $key => $item) {
                if ($item == '') {
                    $dataNhanvien[$key] = null;
                } else {
                    $dataNhanvien[$key] = $item;
                }
            }
        }

        //Thông tin công việc
        $dataCongviec = [];
        $hrm_cong_viec = $payload->hrm_nhan_vien_cong_viec ?? null;
        if ($hrm_cong_viec) {
            foreach ($hrm_cong_viec as $key => $item) {
                if ($item == '') {
                    $dataCongviec[$key] = null;
                } else {
                    $dataCongviec[$key] = $item;
                }
            }
        }

        $dataBaohiem = [];
        $hrm_bao_hiem = $payload->hrm_nhan_vien_bao_hiem ?? null;
        if ($hrm_bao_hiem) {
            foreach ($hrm_bao_hiem as $key => $item) {
                if ($item == '') {
                    $dataBaohiem[$key] = null;
                } else {
                    $dataBaohiem[$key] = $item;
                }
            }
        }

        // Thông tin chính trị, y tế, quân sự
        $dataChinhtriytequansu = [];
        $hrm_nhan_vien_chinhtri_yte_quansu = $payload->hrm_nhan_vien_chinhtri_yte_quansu ?? null;
        if ($hrm_nhan_vien_chinhtri_yte_quansu) {
            foreach ($hrm_nhan_vien_chinhtri_yte_quansu as $key => $item) {
                if ($item == '') {
                    $dataChinhtriytequansu[$key] = null;
                } else {
                    $dataChinhtriytequansu[$key] = $item;
                }
            }
        }

        //Thông tin quá trình công tác
        $data_quatrinhcongtac = [
            'ngay_bat_dau' => date('Y-m-d'),
            'ngay_ket_thuc' => null,
            'id_don_vi' => $payload->hrm_nhan_vien->id_don_vi_cong_tac ?? null,
            'id_vi_tri_cong_viec' => $payload->hrm_nhan_vien->id_vi_tri_cong_viec ?? null,
            'ghi_chu' => null
        ];

        $data_bangcap = [
            'tu_thang' => date('Y-m-01'),
            'den_thang' => null,
            'noi_dao_tao' => $payload->hrm_nhan_vien->noi_dt ?? null,
            'chuyen_nganh' => $payload->hrm_nhan_vien->nganh_dt ?? null,
            'trinh_do_dt' => $payload->hrm_nhan_vien->trinh_do_dt ?? null,
            'xep_loai_dt' => null,
        ];

        $this->moveArrayToArray(['ma_cham_cong', 'id_ca_lam_viec'], $dataCongviec, $dataNhanvien);
        // dd([$dataNhanvien, $dataCongviec, $dataBaohiem]);
        /**
         * ==============Validate===============
         */
        //Thông tin chung
        $this->validate->setData($dataNhanvien);
        if (!$this->validate->setRulesAndRun('nhanvien', 'update')) {
            $errorsHrmnhanvien = array_merge($errorsHrmnhanvien, $this->validate->errors());
        }
        $validateEmail = [];
        // if (!$dataNhanvien['email']) {
        //     $validateEmail[] = l('Email is required');
        // }
        // if (
        //     $this->Hrm_nhan_vien_model->where('email', $dataNhanvien['email'])->where('id_nhan_vien !=', $id)->first() ||
        //     $this->Ql_nguoi_dung_model->where('ql_nguoi_dung_email', $dataNhanvien['email'])->where('ql_nguoi_dung_id !=', $nhanvien['ql_nguoi_dung_id'])->first()
        // ) {
        //     $validateEmail[] = l('Email already exists');
        // }
        if (!empty($validateEmail)) {
            $errorsHrmnhanvien = array_merge($errorsHrmnhanvien, ['email' => $validateEmail]);
        }

        //Thông tin công việc
        // $this->validate->setData($dataCongviec);
        // if (!$this->validate->setRulesAndRun('nhanvien', 'update_cong_viec')) {
        //     $errorsHrmcongviec = array_merge($errorsHrmcongviec, $this->validate->errors());
        // }

        //Upload avatar
        $folderName = 'employees/avatars';
        if (isset($_FILES['avatar'])) {
            if ($_FILES['avatar']) {
                $uploadedFile = $this->fileupload->upload($_FILES['avatar'], $folderName);
                if (!$uploadedFile['success']) {
                    $errorsHrmnhanvien = array_merge($errorsHrmnhanvien, ['avatar' => 'Không thể tải lên ảnh đại diện']);
                } else {
                    $dataNhanvien['avatar'] = $uploadedFile['file_path'];
                }
            }
        }

        if (!empty($errorsHrmnhanvien) || !empty($errorsHrmcongviec) || !empty($errorsBaohiem) || !empty($errorsHrmchinhtriytequansu)) {
            resBadrequest([
                'hrm_nhan_vien' => $errorsHrmnhanvien,
                'hrm_nhan_vien_cong_viec' => $errorsHrmcongviec,
                'hrm_nhan_vien_bao_hiem' => $errorsBaohiem,
                'hrm_nhan_vien_chinhtri_yte_quansu' => $errorsHrmchinhtriytequansu
            ]);
        }
        // dd([$id, $dataNhanvien, $dataCongviec, $dataBaohiem, $dataChinhtriytequansu]);
        $this->db->trans_start();
        if (!empty($dataNhanvien)) {
            $this->Hrm_nhan_vien_model->where('id_nhan_vien', $id)->update($dataNhanvien);
            $this->Ql_nguoi_dung_model->where('ql_nguoi_dung_id', $nhanvien['ql_nguoi_dung_id'])
                ->update([
                    'ql_nguoi_dung_ho_ten' => $dataNhanvien['ho_va_ten'],
                    'ql_nguoi_dung_email' => $dataNhanvien['email'],
                    'id_don_vi' => $dataNhanvien['id_don_vi_cong_tac'],
                ]);
        } else {
            log_message('debug', 'Bỏ qua update/insert dataNhanvien vì không có dữ liệu.');
        }
        //Công việc
        if (!empty($dataCongviec)) {
            $congviec = $this->Hrm_nhan_vien_cong_viec_model->where('id_nhan_vien', $id)->first();
            if ($congviec) {
                $this->Hrm_nhan_vien_cong_viec_model->where('id_nhan_vien', $id)->update($dataCongviec);
            } else {
                $dataCongviec['id_nhan_vien'] = $id;
                $this->Hrm_nhan_vien_cong_viec_model->insert($dataCongviec);
            }
        } else {
            log_message('debug', 'Bỏ qua update/insert dataCongViec vì không có dữ liệu.');
        }

        //Bảo hiểm
        if (!empty($dataBaohiem)) {
            $baohiem = $this->Hrm_nhan_vien_bao_hiem_model->where('id_nhan_vien', $id)->first();
            if ($baohiem) {
                $this->Hrm_nhan_vien_bao_hiem_model->where('id_nhan_vien', $id)->update($dataBaohiem);
            } else {
                $dataBaohiem['id_nhan_vien'] = $id;
                $this->Hrm_nhan_vien_bao_hiem_model->insert($dataBaohiem);
            }
        } else {
            log_message('debug', 'Bỏ qua update/insert baohiem vì không có dữ liệu.');
        }
        //Chính trị, y tế, quân sự
        if (!empty($dataChinhtriytequansu)) {

            $chinhtriytequansu = $this->Hrm_nhan_vien_chinhtri_yte_quansu_model->where('id_nhan_vien', $id)->first();
            if ($chinhtriytequansu) {
                $this->Hrm_nhan_vien_chinhtri_yte_quansu_model->where('id_nhan_vien', $id)->update($dataChinhtriytequansu);
            } else {
                $dataChinhtriytequansu['id_nhan_vien'] = $id;
                $this->Hrm_nhan_vien_chinhtri_yte_quansu_model->insert($dataChinhtriytequansu);
            }
        } else {
            log_message('debug', 'Bỏ qua update/insert chinhtriytequansu vì không có dữ liệu.');
        }

        //Quá trình công tác 
        if (($data_quatrinhcongtac['id_vi_tri_cong_viec'] != $nhanvien['id_vi_tri_cong_viec'] || $data_quatrinhcongtac['id_don_vi'] != $nhanvien['id_don_vi_cong_tac']) && $id) {

            $this->Hrm_qua_trinh_cong_tac_model->where('id_nhan_vien', $id)->where('ngay_ket_thuc IS NULL')->update(['ngay_ket_thuc' => date('Y-m-d')]);
            // Thêm quá trình công tác mới
            $this->Hrm_qua_trinh_cong_tac_model->create($data_quatrinhcongtac);
        }

        if (
            (strtolower($data_bangcap['noi_dao_tao']) != strtolower($nhanvien['noi_dt']) ||
                strtolower($data_bangcap['chuyen_nganh']) != strtolower($nhanvien['nganh_dt']) ||
                strtolower($data_bangcap['trinh_do_dt']) != strtolower($nhanvien['trinh_do_dt'])) && $id
        ) {
            $data_bangcap['id_nhan_vien'] = $nhanvien['id_nhan_vien'];
            $this->Hrm_nhan_vien_bang_cap_model->create($data_bangcap);
        }


        $this->db->trans_commit();
        //cập nhật thành công xóa avatar cũ
        $this->fileupload->delete($nhanvien['avatar']);

        resSuccess($this->Hrm_nhan_vien_model->find($id), 'Cập nhật thành công');
    }

    /*
        Lấy dữ liệu của arr1 gán vào arr2 và xóa dữ liệu đó ở arr1
    */
    private function moveArrayToArray(array $keys, array &$from, array &$to)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $from)) {
                $to[$key] = $from[$key];
                unset($from[$key]);
            }
        }
    }


    public function delete_post()
    {
        $auth = $this->getUserLogin();
        $ids = commonRequest('ids') ? commonRequest('ids') : null;
        if (!$ids) {
            resError('Không tìm thấy dữ liệu để xóa');
        }
        $nhanvien = $this->Hrm_nhan_vien_model->whereIn('id_nhan_vien', $ids)->get();

        $id_nds = array_column($nhanvien, 'ql_nguoi_dung_id');

        if (!$nhanvien) {
            resError('Không tìm thấy nhân viên để xóa');
        }

        $this->db->trans_start();
        $this->Hrm_nhan_vien_model->whereIn('id_nhan_vien', $ids)->update(['deleted_at' => date('Y-m-d H:i:s'), 'deleted_user_id' => $auth['ql_nguoi_dung_id']]);
        $this->Hrm_bang_luong_model->whereIn('id_nhan_vien', $ids)->update(['deleted_at' => date('Y-m-d H:i:s'), 'deleted_user_id' => $auth['ql_nguoi_dung_id']]);
        $this->Hrm_nhan_vien_cong_viec_model->whereIn('id_nhan_vien', $ids)->update(['deleted_at' => date('Y-m-d H:i:s'), 'deleted_user_id' => $auth['ql_nguoi_dung_id']]);

        $this->Ql_nguoi_dung_model->whereIn('ql_nguoi_dung_id', $id_nds)->update(['active_flag' => 0]);
        $this->db->trans_commit();

        resSuccess('Xóa nhân viên thành công');
    }

    public function import_post()
    {
        $file_excel_nhanvien =  $_FILES['file_excel_nhanvien'] ? $_FILES['file_excel_nhanvien'] : null;
        if (!$file_excel_nhanvien) {
            resError('Không tìm thấy file import');
        }

        // Upload file
        $folderName = 'employees/import/' . date('Y') . '/' . date('m');
        $result = $this->fileupload->upload($file_excel_nhanvien, $folderName);
        $path = $result['file_path'];

        $rowKey = 10;
        $rowData = 11;

        $data = $this->pxl->importExcel($rowKey, $rowData, $path);
        $auth = $this->getUserLogin();
        $userId = $auth['ql_nguoi_dung_id'];
        $currentTime = date('Y-m-d H:i:s');
        // resSuccess($data);

        // $data = array_filter($data, function ($nhanvien) {
        //     return array_filter($nhanvien); // Nếu có ít nhất 1 giá trị khác rỗng thì giữ lại
        // });
        $isEmpty = array_reduce($data, function ($carry, $item) {
            return $carry && empty(array_filter($item));
        }, true);

        if ($isEmpty) {
            resError('File đang không có dữ liệu , Vui lòng kiểm tra lại !');
        }

        $objPHPExcel = PHPExcel_IOFactory::load($path);
        $sheet = $objPHPExcel->getActiveSheet();
        $highestColumn = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();

        $columnResult = $highestColumn . '9';
        $sheet->setCellValue($columnResult, 'KẾT QUẢ IMPORT');
        $requiredKeys = [
            'ma_nhan_vien',
            'ho_va_ten',
            'gioi_tinh',
            'ngay_sinh',
            'so_dien_thoai',
            'email',
            'que_quan',
            'mst_ca_nhan',
            'id_don_vi_cong_tac',
            'id_vi_tri_cong_viec',
            'id_dan_toc',
            'id_ton_giao',
            'id_quoc_tich',
            'cccd_so',
            'cccd_ngay_cap',
            'cccd_noi_cap',
            'cccd_ngay_het_han',
            'ho_chieu_so',
            'ho_chieu_ngay_cap',
            'ho_chieu_noi_cap',
            'ho_chieu_ngay_het_han',
            'trinh_do_vh',
            'trinh_do_dt',
            'noi_dt',
            'khoa_dt',
            'nganh_dt',
            'nam_tn',
            'xep_loai_tn',
            'ma_cham_cong',
            'chuc_danh',
            // 'cap',
            // 'bac',
            'trang_thai',
            'loai_hop_dong',
            'ngay_tap_su',
            'ngay_thu_viec',
            'ngay_lam_chinh_thuc',
            'so_ngay_phep'
        ];
        $hrmNhanVienKeys = [
            'id_nhan_vien',
            'ma_nhan_vien',
            'ho_va_ten',
            'gioi_tinh',
            'ngay_sinh',
            'mst_ca_nhan',
            'id_don_vi_cong_tac',
            'id_vi_tri_cong_viec',
            'id_dan_toc',
            'id_ton_giao',
            'id_quoc_tich',
            'cccd_so',
            'cccd_ngay_cap',
            'cccd_noi_cap',
            'cccd_ngay_het_han',
            'ho_chieu_so',
            'ho_chieu_ngay_cap',
            'ho_chieu_noi_cap',
            'ho_chieu_ngay_het_han',
            'trinh_do_vh',
            'trinh_do_dt',
            'noi_dt',
            'khoa_dt',
            'nganh_dt',
            'nam_tn',
            'xep_loai_tn',
            'hktt_id_quoc_gia',
            'hktt_id_tinh_tp',
            'hktt_id_quan_huyen',
            'hktt_id_xa_phuong',
            'hktt_so_nha',
            'hktt_dia_chi',
            'hktt_so_ho_khau',
            'hktt_ma_so_ho_gd',
            'hktt_la_chu_ho',
            'so_dien_thoai',
            'email',
            'que_quan',
            'cohn_giong_hktt',
            'cohn_id_quoc_gia',
            'cohn_id_tinh_tp',
            'cohn_id_quan_huyen',
            'cohn_id_xa_phuong',
            'cohn_so_nha',
            'cohn_dia_chi',
            'avatar',
            'created_at',
            'updated_at',
            'deleted_at',
            'created_user_id',
            'updated_user_id',
            'ql_nguoi_dung_id'
        ];
        if (!empty($data)) {
            $firstRow = $data[0];
            $missingKeys = array_diff($requiredKeys, array_keys($firstRow));
            if (!empty($missingKeys)) {
                unlink($path);
                resError('Không tìm thấy trường thông tin: ' . implode(', ', $missingKeys) . ', Vui lòng kiểm tra lại !');
            }
        }
        $donvi = $this->E_don_vi_model->select('id_don_vi, ten_don_vi')->get();
        $vitricongviec = $this->Hrm_vi_tri_cong_viec_model->get();
        $dan_toc = $this->db->get('dm_dan_toc')->result_array();
        $ton_giao = $this->db->get('dm_ton_giao')->result_array();
        $quoc_gia = $this->db->get('dm_quoc_gia')->result_array();

        function findValueByName(array $haystack, string $searchKey, $needleValue, string $returnKey)
        {
            $needle = strtolower(trim($needleValue));
            foreach ($haystack as $item) {
                if (isset($item[$searchKey]) && strtolower(trim($item[$searchKey])) === $needle) {
                    return $item[$returnKey] ?? null;
                }
            }
            return null;
        }



        // resSuccess($idDonVi);
        foreach ($data as $key => $d) {
            $rowErrorsNhanvien = [];
            $rowErrorsCongviec = [];

            $idDonVi = !empty($d['id_don_vi_cong_tac'])
                ? findValueByName($donvi, 'ten_don_vi', $d['id_don_vi_cong_tac'], 'id_don_vi')
                : null;

            $id_vi_tri_cong_viec = !empty($d['id_vi_tri_cong_viec'])
                ? findValueByName($vitricongviec, 'ten_cong_viec', $d['id_vi_tri_cong_viec'], 'id_vi_tri_cong_viec')
                : null;

            $id_dan_toc = !empty($d['id_dan_toc'])
                ? findValueByName($dan_toc, 'ten', $d['id_dan_toc'], 'id_dan_toc')
                : null;

            $id_ton_giao = !empty($d['id_ton_giao'])
                ? findValueByName($ton_giao, 'ten', $d['id_ton_giao'], 'id_ton_giao')
                : null;

            $id_quoc_gia = !empty($d['id_quoc_gia'])
                ? findValueByName($quoc_gia, 'ten', $d['id_quoc_gia'], 'id_quoc_gia')
                : null;


            $currentNhanvien = [
                'ma_nhan_vien'          => $d['ma_nhan_vien'] ?? null,
                'ho_va_ten'             => $d['ho_va_ten'] ?? null,
                'gioi_tinh'             => $d['gioi_tinh'] ?? null,
                'ngay_sinh'             => convertDateToISO($d['ngay_sinh']) ?? null,
                'so_dien_thoai'         => $d['so_dien_thoai'] ?? null,
                'email'                 => $d['email'] ?? null,
                'que_quan'              => $d['que_quan'] ?? null,
                'mst_ca_nhan'           => $d['mst_ca_nhan'] ?? null,
                'id_don_vi_cong_tac'    => $idDonVi ?? null,
                'id_vi_tri_cong_viec'   => $id_vi_tri_cong_viec ?? null,
                'id_dan_toc'            => $id_dan_toc ?? null,
                'id_ton_giao'           => $id_ton_giao ?? null,
                'id_quoc_tich'          => $id_quoc_gia ?? null,
                'cccd_so'               => $d['cccd_so'] ?? null,
                'cccd_ngay_cap'         => convertDateToISO($d['cccd_ngay_cap']) ?? null,
                'cccd_noi_cap'          => $d['cccd_noi_cap'] ?? null,
                'cccd_ngay_het_han'     => convertDateToISO($d['cccd_ngay_het_han']) ?? null,
                'ho_chieu_so'           => $d['ho_chieu_so'] ?? null,
                'ho_chieu_ngay_cap'     => convertDateToISO($d['ho_chieu_ngay_cap']) ?? null,
                'ho_chieu_noi_cap'      => $d['ho_chieu_noi_cap'] ?? null,
                'ho_chieu_ngay_het_han' => convertDateToISO($d['ho_chieu_ngay_het_han']) ?? null,
                'trinh_do_vh'           => $d['trinh_do_vh'] ?? null,
                'trinh_do_dt'           => $d['trinh_do_dt'] ?? null,
                'noi_dt'                => $d['noi_dt'] ?? null,
                'khoa_dt'               => $d['khoa_dt'] ?? null,
                'nganh_dt'              => $d['nganh_dt'] ?? null,
                'nam_tn'                => $d['nam_tn'] ?? null,
                'xep_loai_tn'           => $d['xep_loai_tn'] ?? null,
                'created_user_id'       => $userId,
                'created_at'            => $currentTime,
                'updated_user_id'       => $userId,
                'updated_at'            => $currentTime,
                // 'availability' => 1,
            ];
            $this->check_currentNhanvien($currentNhanvien, $rowErrorsNhanvien);
            if (!empty($rowErrorsNhanvien)) {
                $currentNhanvien['errors'] = $rowErrorsNhanvien;
            }
            $dataNhanvien[] = $currentNhanvien;


            // Lấy key của common loai_hop_dong
            foreach ($this->common::LOAI_HOP_DONG as $key_lhd => $lhd) {
                if (strtolower($d['loai_hop_dong']) == strtolower($lhd['label'])) {
                    $loai_hop_dong = $lhd['value'];
                    break;
                }
            }
            foreach ($this->common::TRANG_THAI_CONG_VIEC as $key_ttlv => $ttlv) {
                if (strtolower($d['trang_thai']) == strtolower($ttlv['label'])) {
                    $trang_thai = $ttlv['value'];
                    break;
                }
            }

            $currentCongviec = [
                'ma_cham_cong'          => $d['ma_cham_cong'] ?? null,
                'chuc_danh'             => $d['chuc_danh'] ?? null,
                'cap'                   => $d['cap'] ?? null,
                'bac'                   => $d['bac'] ?? null,
                'trang_thai'            => $trang_thai ?? null,
                'loai_hop_dong'         => $loai_hop_dong ?? null,
                'ngay_tap_su'           => convertDateToISO($d['ngay_tap_su']) ?? null,
                'ngay_thu_viec'         => convertDateToISO($d['ngay_thu_viec']) ?? null,
                'ngay_lam_chinh_thuc'   => convertDateToISO($d['ngay_lam_chinh_thuc']) ?? null,
                'so_ngay_phep'          => $d['so_ngay_phep'] ?? null,
                'created_user_id'       => $userId,
                'created_at'            => $currentTime,
                'updated_user_id'       => $userId,
                'updated_at'            => $currentTime,
            ];

            $this->check_currentCongviec($currentCongviec, $rowErrorsCongviec);
            if (!empty($rowErrorsCongviec)) {
                $currentCongviec['errors'] = $rowErrorsCongviec;
            }
            $dataCongviec[] = $currentCongviec;

            $rowErrors = array_merge_recursive($rowErrorsNhanvien, $rowErrorsCongviec);
            $errorCell = $highestColumn . $rowData;
            if (!empty($rowErrors)) {
                $message = implode("\n", $rowErrors);
                $color = 'FF3300'; // đỏ
            } else {
                $message = 'Import thành công';
                $color = '006400'; // xanh đậm
            }

            $sheet->setCellValue($errorCell, $message);
            $sheet->getStyle($errorCell)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => $color],
                ],
            ]);

            if (empty($rowErrors)) {
                $this->db->trans_start();
                unset($currentNhanvien['errors']);
                unset($currentCongviec['errors']);

                $last_insertNhanvien = $this->Hrm_nhan_vien_model->insert($currentNhanvien);
                $currentCongviec['id_nhan_vien'] = $last_insertNhanvien;
                $last_insertCongviec = $this->Hrm_nhan_vien_cong_viec_model->insert($currentCongviec);


                $dataNguoidung = [
                    'ql_nguoi_dung_ho_ten' => $currentNhanvien['ho_va_ten'],
                    'ql_nguoi_dung_email' => $currentNhanvien['email'] ?? NULL,
                    'ql_nguoi_dung_mat_khau' => password_hash(substr(bin2hex(random_bytes(8)), 0, 8), PASSWORD_BCRYPT, ['cost' => 12]), //hash ngẫu nhiên
                    // 'ql_nguoi_dung_avatar' => $d['avatar'] ?? null,
                    'ql_nguoi_dung_loai' => 2,
                    'ql_nguoi_dung_ngay_tao' => date('Y-m-d H:i:s'),
                    'ql_nguoi_dung_ngay_cap_nhat' => date('Y-m-d H:i:s'),
                    'active_flag' => 1,
                    'ql_nguoi_dung_is_admin' => 0,
                    'ql_nguoi_dung_la_lanh_dao' => 0,
                    'id_don_vi' => $currentNhanvien['id_don_vi_cong_tac']
                ];
                $ql_nguoi_dung = $this->Ql_nguoi_dung_model->insert($dataNguoidung);
                $this->Hrm_nhan_vien_model->where('id_nhan_vien', $last_insertNhanvien)
                    ->update([
                        'ql_nguoi_dung_id' => $ql_nguoi_dung
                    ]);

                $this->db->trans_complete();
            }
            $rowData++; // Chỉ tăng dòng nếu có lỗi (nếu bạn muốn vậy)
        }

        $sheet->getColumnDimension($highestColumn)->setAutoSize(true);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($path); // Lưu đè file gốc

        $filename = pathinfo($file_excel_nhanvien['name'], PATHINFO_FILENAME);
        $fileNameEncrypt = pathinfo($path, PATHINFO_FILENAME);
        $fileextension = pathinfo($file_excel_nhanvien['name'], PATHINFO_EXTENSION);

        $newFileName = $filename . '_' . date('Y-m-d') . '_' . $fileNameEncrypt . '.' . $fileextension;

        $newPath = dirname($path) . '/' . $newFileName;
        rename($path, $newPath); // Đổi tên file

        $fileContent = file_get_contents($newPath);
        $encodedContent = base64_encode($fileContent);
        // dd([$encodedContent, $path, $file_excel_nhanvien, $newPath]);
        resSuccess([
            'file_base64' => $encodedContent,
            'file_name' => $newFileName
        ], 'Import thành công');
    }

    private function check_currentNhanvien($data, &$rowErrors)
    {
        if (empty($data['ho_va_ten'])) {
            $rowErrors[] = 'Họ và tên không được để trống.';
        }

        if (empty($data['cccd_so'])) {
            $rowErrors[] = 'Số căn cước không được để trống.';
        } elseif (!preg_match('/^\d{9,12}$/', $data['cccd_so'])) {
            $rowErrors[] = 'Số căn cước phải từ 9-12 chữ số.';
        } else if (!empty($data['cccd_so'])) {
            $issetCCCD = $this->Hrm_nhan_vien_model->where('cccd_so', $data['cccd_so'])->first();
            if ($issetCCCD)  $rowErrors[] = 'Số căn cước đã trùng với ' . $issetCCCD['ho_va_ten'] . '.';
        }
        // Bạn muốn validate gì thêm nữa thì thả vào đây nha

    }

    private function check_currentCongviec($data, &$rowErrors)
    {
        $loaiHopDong = $data['loai_hop_dong'] ?? null;

        if (!$loaiHopDong || !isset(Common::LOAI_HOP_DONG[$loaiHopDong])) {
            $rowErrors[] = "Loại hợp đồng không hợp lệ hoặc bị thiếu!";
        }
    }


    public function download_post($file_name)
    {
        $path = 'assets/download/excel/excel_sample/nhanvien/' . $file_name . '.xlsx';
        resSuccess(['path' => $path], 'Download file import');
    }

    public function print_get($id = 122)
    {
        $this->show_get($id);
    }

    public function resignation_post()
    {
        $auth = $this->getUserLogin();
        $data = $this->post();
        $data['id_nhan_vien'] = (array) $data['id_nhan_vien'];

        if (empty($data['ngay_lam_chinh_thuc_ket_thuc'])) {
            resError('Ngày làm chính thức kết thúc không được để trống');
        }


        $nhanvienList = $this->Hrm_nhan_vien_model->whereIn('id_nhan_vien', $data['id_nhan_vien'])->get();

        if (empty($nhanvienList)) {
            return resBadrequest(null, 'Không tìm thấy nhân viên nào');
        }
        $arrayCheckExist = array_column($this->Hrm_nhan_vien_cong_viec_model->whereIn('id_nhan_vien', $data['id_nhan_vien'])->get(), 'trang_thai');

        if (in_array(Common::TRANG_THAI_CONG_VIEC['DANG_LAM_THU_TUC_THOI_VIEC']['value'], $arrayCheckExist) || in_array(Common::TRANG_THAI_CONG_VIEC['NGHI_VIEC']['value'], $arrayCheckExist)) {
            return resError('Có nhân viên đang làm thủ tục hoặc nghỉ việc, Vui lòng kiểm tra lại');
        }

        foreach ($nhanvienList as $nv) {
            $this->createLog(
                'update',
                'Làm thôi việc',
                json_encode($nv),
                json_encode([
                    'trang_thai' => Common::TRANG_THAI_CONG_VIEC['DANG_LAM_THU_TUC_THOI_VIEC']['value'],
                    'ngay_lam_chinh_thuc_ket_thuc' => $data['ngay_lam_chinh_thuc_ket_thuc'],
                    'ly_do_thoi_viec' => $data['ly_do_thoi_viec'] ?? null,
                ])
            );
        }

        $this->Hrm_nhan_vien_cong_viec_model->whereIn('id_nhan_vien', $data['id_nhan_vien'])
            ->update([
                'trang_thai' => Common::TRANG_THAI_CONG_VIEC['DANG_LAM_THU_TUC_THOI_VIEC']['value'],
                'ngay_lam_chinh_thuc_ket_thuc' => $data['ngay_lam_chinh_thuc_ket_thuc'],
                'ly_do_thoi_viec' => $data['ly_do_thoi_viec'] ?? null,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_user_id' => $auth['ql_nguoi_dung_id']
            ]);
        $names = array_column($nhanvienList, 'ho_va_ten');
        $message = count($names) > 1
            ? 'Làm thủ tục cho các nhân viên: ' . implode(', ', $names)
            : 'Làm thủ tục cho nhân viên: ' . $names[0];
        return resSuccess(null, $message);
    }

    public function changeStatus_post($id)
    {
        $trangthai = commonRequest('trang_thai') ? Common::TRANG_THAI_CONG_VIEC[commonRequest('trang_thai')]['value'] : null;

        $nhanvien = $this->Hrm_nhan_vien_model->where('id_nhan_vien', $id)->get();
        if (!$nhanvien) {
            resBadrequest(null, 'Không tìm thấy nhân viên này');
        }
        if (!$trangthai) {
            resError('Trạng thái làm việc không hợp lệ.');
        }
        $this->Hrm_nhan_vien_cong_viec_model->where('id_nhan_vien', $id)->update(['trang_thai' => $trangthai]);

        resSuccess(null, 'Cập nhật trạng thái thành công');
    }

    public function getList_nhanvien_get()
    {
        $nhanvien = $this->Hrm_nhan_vien_model
            ->join('hrm_nhan_vien_cong_viec', 'hrm_nhan_vien_cong_viec.id_nhan_vien = hrm_nhan_vien.id_nhan_vien', 'left')
            ->whereNotIn('hrm_nhan_vien_cong_viec.trang_thai', [
                Common::TRANG_THAI_CONG_VIEC['DANG_LAM_THU_TUC_THOI_VIEC']['value'],
                Common::TRANG_THAI_CONG_VIEC['NGHI_VIEC']['value'],
            ])
            ->where('hrm_nhan_vien.deleted_at IS NULL')
            ->get();
        resSuccess($nhanvien, 'Lấy danh sách nhân viên thành công');
    }

    public function importThongtinNganhang_post()
    {
        $file_thongtinnganhang = commonRequest('file_thongtinnganhang') ? commonRequest('file_thongtinnganhang') : null;
        if (!$file_thongtinnganhang) {
            resError('Không tìm thấy file import');
        }
        // Upload file
        $folderName = 'employees/import/' . date('Y') . '/' . date('m');
        $result = $this->fileupload->upload($file_thongtinnganhang, $folderName);
        $path = $result['file_path'];

        $rowKey = 10;
        $rowData = 11;
        $countSuccess = $countError = 0;

        $data = $this->pxl->importExcel($rowKey, $rowData, $path);
        // Kiểm tra có phải người dùng đang upload file rỗng hay không.
        $this->emptyData($data);

        // Đọc lại file vừa được upload
        $objPHPExcel = PHPExcel_IOFactory::load($path);
        $sheet = $objPHPExcel->getActiveSheet();
        $highestColumn = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();

        $columnResult = $highestColumn . '9';
        $sheet->setCellValue($columnResult, 'KẾT QUẢ IMPORT');

        $requiredKeys = [
            'cccd_so',
            'tk_ngan_hang',
            'ngan_hang',
            'ten_chu_tai_khoan',
        ];
        $thongtinnganhangKeys = [
            'ma_nhan_vien',
            'cccd_so',
            'tk_ngan_hang',
            'ngan_hang',
            'ten_chi_nhanh',
            'ten_chu_tai_khoan',
        ];

        // Kiểm tra xem các trường bắt buộc có bị thiếu hay không.
        $this->checkMissingFields($data, $requiredKeys, $path);

        foreach ($data as $key => $d) {

            $errors = [];
            if (empty($d['cccd_so'])) {
                $errors[] = 'Số CCCD/Thẻ Căn Cước không được để trống';
            } else {
                $issetCCCD = $this->Hrm_nhan_vien_model->where('cccd_so', $d['cccd_so'])->first();
                if (!$issetCCCD) {
                    $errors[] = 'Số CCCD/Thẻ Căn Cước không đúng';
                    $id_nhan_vien = null;
                } else {
                    $id_nhan_vien = $issetCCCD['id_nhan_vien'];
                }
            }
            if (empty($d['tk_ngan_hang'])) {
                $errors[] = 'Số tài khoản không được để trống';
            }
            if (empty($d['ngan_hang'])) {
                $errors[] = 'Tên ngân hàng không được để trống';
            }
            if (empty($d['ten_chu_tai_khoan'])) {
                $errors[] = 'Chủ tài khoản không không được để trống';
            }
            // Nếu có lỗi, ghi vào Excel và tiếp tục vòng lặp
            $errorCell = $highestColumn . $rowData;   // Xác định ô ghi kết quả
            if (!empty($errors)) {
                $errorMessage = implode("\n", $errors); // Ghép nhiều lỗi thành chuỗi xuống dòng
                $sheet->setCellValue($errorCell, $errorMessage);
                $sheet->getStyle($errorCell)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FF3300'] // Màu chữ đỏ
                    ],
                ]);
                $countError++;
                $rowData++; // Tăng dòng
                continue;
            } else {
                $sheet->setCellValue($errorCell, 'Thành công');
                $sheet->getStyle($errorCell)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '006400'] // Màu chữ xanh đậm
                    ],
                ]);

                $dataUpdate = [
                    'tk_ngan_hang' => $d['tk_ngan_hang'],
                    'tk_ngan_hang' => $d['tk_ngan_hang'],
                    'ngan_hang' => $d['ngan_hang'],
                    'ten_chu_tai_khoan' => $d['ten_chu_tai_khoan'],
                ];
                $dataInsert = [
                    'id_nhan_vien' => $id_nhan_vien,
                    'tk_ngan_hang' => $d['tk_ngan_hang'],
                    'tk_ngan_hang' => $d['tk_ngan_hang'],
                    'ngan_hang' => $d['ngan_hang'],
                    'ten_chu_tai_khoan' => $d['ten_chu_tai_khoan'],
                ];
                $this->db->trans_start();

                $this->db->where('id_nhan_vien', $id_nhan_vien);
                $exists = $this->db->count_all_results('hrm_nhan_vien_luong') > 0;

                $exists
                    ? $this->db->where('id_nhan_vien', $id_nhan_vien)->update('hrm_nhan_vien_luong', $dataUpdate)
                    : $this->db->insert('hrm_nhan_vien_luong', $dataInsert);

                $this->db->trans_complete();


                $dataUpdate['id_nhan_vien'] = $id_nhan_vien;
                $this->createLog('Import', 'Import thông tin ngân hàng cho nhân viên', NULL, $dataUpdate, 'hrm_nhan_vien_luong');

                $countSuccess++;
                $rowData++;
            }
        }

        $sheet->getColumnDimension($highestColumn)->setAutoSize(true);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($path); // Lưu đè file gốc

        $filename = pathinfo($file_thongtinnganhang['name'], PATHINFO_FILENAME);
        $fileNameEncrypt = pathinfo($path, PATHINFO_FILENAME);
        $fileextension = pathinfo($file_thongtinnganhang['name'], PATHINFO_EXTENSION);

        $newFileName = $filename . '_' . date('Y-m-d') . '_' . $fileNameEncrypt . '.' . $fileextension;

        $newPath = dirname($path) . '/' . $newFileName;
        rename($path, $newPath); // Đổi tên file

        $fileContent = file_get_contents($newPath);
        $encodedContent = base64_encode($fileContent);
        // dd([$encodedContent, $path, $file_thongtinnganhang, $newPath]);
        resSuccess([
            'count' => [
                'countSuccess' => $countSuccess,
                'countError' => $countError,
            ],
            'file_base64' => $encodedContent,
            'file_name' => $newFileName
        ], 'Import thành công');
    }

    private function emptyData(array $data): void
    {
        $isEmpty = array_reduce($data, fn($carry, $item) => $carry && empty(array_filter($item)), true);

        if ($isEmpty) {
            resError('File đang không có dữ liệu, vui lòng kiểm tra lại!');
        }
    }

    private function checkMissingFields(array $data, array $requiredKeys, string $path = ''): void
    {
        if (!empty($data)) {
            $missing = array_diff($requiredKeys, array_keys($data[0]));

            if (!empty($missing)) {
                if ($path) unlink($path);
                resError('Thiếu trường: ' . implode(', ', $missing) . '. Vui lòng kiểm tra lại!');
            }
        }
    }

    public function findByKey_post()
    {
        $inputJSON = file_get_contents('php://input');
        $post = json_decode($inputJSON, true);

        $query = $this->Hrm_nhan_vien_model;
        foreach ($post as $key => $value) {
            $query = $query->where($key, $value);
        }
        $query = $query->where('deleted_at IS NULL', null, false);
        $nhanvien = $query->first();
        // $sql = $this->db->last_query();
        // dd($sql); 
        if (!$nhanvien) {
            return resError('Không tìm thấy nhân viên', REST_Controller::HTTP_NOT_FOUND);
        }

        return resSuccess($nhanvien, 'Lấy thông tin nhân viên thành công');
    }

    public function last_code_get()
    {
        $lastCode = $this->Hrm_nhan_vien_model->last_code();
        // $sql = $this->db->last_query();
        // dd($sql);
        // $newCode =  $lastCode ? ((int) $lastCode[0] + 1) : '0001';
        resSuccess($lastCode, 'Mã nhân viên mới');
    }

    public function export_post()
    {
        $postData =  json_decode($this->input->raw_input_stream, true);
        $file_name = $postData['file_name'] ?? 'Danh_sach_nhan_su';

        // nếu không có đuôi .xlsx thì thêm vào
        if (pathinfo($file_name, PATHINFO_EXTENSION) !== 'xlsx') {
            $file_name .= '.xlsx';
        }

        $data = $this->Hrm_nhan_vien_model->Export($postData);
        if (empty($data)) {
            resError('Danh sách nhân viên trống');
        }

        $file_path = $this->setupExcel($file_name, $data);
        if (!file_exists($file_path)) {
            resError('Không tạo được file Excel');
        }

        $file_content = file_get_contents($file_path);
        $file_base64 = base64_encode($file_content);

        unlink($file_path);

        resSuccess([
            'file_name'   => $file_name,
            'file_base64' => $file_base64,
        ]);
    }


    private function getEmployeeExportHeaders()
    {
        return [
            // 'id_nhan_vien' => 'ID Nhân viên',
            'ma_nhan_vien' => 'Mã nhân viên',
            'ma_cham_cong' => 'Mã chấm công',
            'ho_va_ten' => 'Họ và tên',
            'email' => 'Email',
            'gioi_tinh' => 'Giới tính',
            'ngay_sinh' => 'Ngày sinh',
            'ten_don_vi' => 'Đơn vị',
            'trang_thai' => 'Trạng thái',
            'ngay_lam_chinh_thuc' => 'Ngày làm chính thức',
            'ngay_lam_chinh_thuc_ket_thuc' => 'Ngày kết thúc',
            'ten_cong_viec' => 'Chức vụ',
            'ca_lam_viec' => 'Ca làm việc',
            'cccd_so' => 'Số CCCD',
            'cccd_noi_cap' => 'Nơi cấp CCCD',
            'cccd_ngay_cap' => 'Ngày cấp CCCD',
            'cccd_ngay_het_han' => 'Ngày hết hạn CCCD',
            'ten_quoc_gia' => 'Quốc tịch',
            'ten_ton_giao' => 'Tôn giáo',
            'ten_dan_toc' => 'Dân tộc',
        ];
    }


    private function setupExcel($file_name, $data)
    {
        $objPHPExcel = new PHPExcel;
        $sheet = $objPHPExcel->setActiveSheetIndex(0);
        $sheet->setTitle('Thông tin nhân viên');

        // Lấy headers từ function
        $headers = $this->getEmployeeExportHeaders();

        // Ghi header
        $col = 0;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($col, 1, $header);

            // In đậm header
            $sheet->getStyleByColumnAndRow($col, 1)->getFont()->setBold(true);

            // Auto-size column
            $columnLetter = PHPExcel_Cell::stringFromColumnIndex($col);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);

            $col++;
        }

        // Ghi data
        $row = 2;
        foreach ($data as $item) {
            $col = 0;
            foreach ($headers as $key => $header) {
                $value = $item[$key] ?? '';

                if (in_array($key, ['cccd_so', 'ma_nhan_vien', 'ma_cham_cong'])) {
                    $sheet->setCellValueExplicitByColumnAndRow(
                        $col,
                        $row,
                        $value,
                        PHPExcel_Cell_DataType::TYPE_STRING
                    );
                } else {
                    $sheet->setCellValueByColumnAndRow($col, $row, $value);
                }

                $col++;
            }
            $row++;
        }

        // Đường dẫn vật lý trên server
        $dir = FCPATH . 'assets/download/excel/excel_export/nhanvien/';
        $file_path = $dir . $file_name;

        // ✅ Đảm bảo thư mục tồn tại & có quyền ghi
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (!is_writable($dir)) {
            chmod($dir, 0777);
        }

        // Lưu file Excel
        $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $writer->save($file_path);

        return $file_path;
    }


    private function sheetExport()
    {
        return [
            'cong_viec' => 'Công việc',
            'bao_hiem' => 'Bảo hiểm',
            'thong_tin_gia_dinh' => 'Thông tin gia đình',
            'hop_dong' => 'Hợp đồng',
            'qua_trinh_cong_tac' => 'Quá trình công tác',
            'khen_thuong' => 'Khen thưởng',
            'qua_trinh_dao_tao' => 'Quá trình đào tạo',
            'danh_gia' => 'Đánh giá nhân sự',
            'bang_cap' => 'Bằng cấp',
            'chung_chi' => 'Chứng chỉ',
            'kinh_nghiem_lam_viec' => 'Kinh nghiệm làm việc',
            'thoi_viec' => 'Thôi việc',
        ];
    }

    private function columnExportNhanvien()
    {
        return [
            'ma_nhan_vien' => 'Mã nhân sự',
            'ma_cham_cong' => 'Mã chấm công',
            'ho_va_ten' => 'Họ và tên',
            'email' => 'Email',
            'gioi_tinh' => 'Giới tính',
            'ngay_sinh' => 'Ngày sinh',
            'ten_don_vi' => 'Tên đơn vị',
            'ten_cong_viec' => 'Tên công việc',
            'ca_lam_viec' => 'Ca làm việc',
            'cccd_so' => 'Số CCCD',
            'cccd_noi_cap' => 'Nơi cấp CCCD',
            'cccd_ngay_cap' => 'Ngày cấp CCCD',
            'cccd_ngay_het_han' => 'Ngày hết hạn CCCD',
            'ten_quoc_gia' => 'Quốc gia',
            'ten_ton_giao' => 'Tôn giáo',
            'ten_dan_toc' => 'Dân tộc',
        ];
    }

    public function statistical_get()
    {
        $result = $this->Hrm_nhan_vien_model->statistical();
        if (!$result) {
            resError('Không có dữ liệu thống kê');
        }
        resSuccess($result, 'Thống kê hồ sơ');
    }

    // public function save_image_url_post()
    // {
    //     // $imageUrl = $this->input->post('image_url');
    //     // $cccd_so = $this->input->post('cccd_so');
    //     // $ho_va_ten = $this->input->post('ho_va_ten');
    //     // $ngay_sinh = $this->input->post('ngay_sinh');

    //     $rawData = file_get_contents("php://input");
    //     $data = json_decode($rawData, true);

    //     $imageUrl = $data['image_url'] ?? null;
    //     $cccd_so = $data['cccd_so'] ?? null;
    //     $ho_va_ten = $data['ho_va_ten'] ?? null;
    //     $ngay_sinh = $data['ngay_sinh'] ?? null;

    //     $nhanvien = $this->Hrm_nhan_vien_model
    //         ->where('cccd_so', $cccd_so)
    //         ->first();

    //     if (!$nhanvien) {
    //         resError('Không có nhân viên');
    //     }

    //     if (!$imageUrl) {
    //         resError('No image URL provided');
    //     }

    //     // Lấy nội dung ảnh
    //     $imageContent = file_get_contents($imageUrl);
    //     if (!$imageContent) {
    //         resError('Cannot download image');
    //     }

    //     // Tạo tên file lưu
    //     $filename = '5tan_img_' . time() . '_' . rand(1000, 9999) . '.' . pathinfo($imageUrl, PATHINFO_EXTENSION);
    //     $uploadPath = 'uploads/employees/avatars/';
    //     if (!file_exists($uploadPath)) {
    //         mkdir($uploadPath, 0777, true);
    //     }

    //     // Lưu ảnh vào thư mục uploads/images/
    //     $saved = file_put_contents($uploadPath . $filename, $imageContent);
    //     if (!$saved) {
    //         resError('Failed to save image');
    //     }

    //     // Ghi vào DB (tuỳ anh cấu hình table)
    //     $this->Hrm_nhan_vien_model->where('ql_nguoi_dung_id', $nhanvien['ql_nguoi_dung_id'])->update([
    //         'avatar' => 'uploads/employees/avatars/' . $filename
    //     ]);

    //     resSuccess('Lưu ảnh thành công');
    // }

    public function save_image_upload_post()
    {
        $id_nhan_vien = $_POST['id_nhan_vien'] ?? null;
        $ho_va_ten    = $_POST['ho_va_ten'] ?? null;
        $ngay_sinh    = $_POST['ngay_sinh'] ?? null;
        $cccd_so      = $_POST['so_cccd'] ?? null;
        $cccd_ngay_cap = $_POST['ngay_cap'] ?? null;
        $cccd_noi_cap  = $_POST['noi_cap'] ?? null;
        $lhkc_sdt_di_dong = $_POST['sdt'] ?? null;
        $hktt_dia_chi = $_POST['dc_thuong_tru'] ?? null;

        if (!$id_nhan_vien) {
            resError('Thiếu ID nhân viên');
        }

        if (!$ho_va_ten) {
            resError('Thiếu họ và tên');
        }

        if (!$ngay_sinh) {
            resError('Thiếu ngày sinh');
        }

        if (!$cccd_so) {
            resError('Thiếu số CCCD');
        }


        $file = $_FILES['image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE   => 'Dung lượng ảnh vượt quá giới hạn',
                UPLOAD_ERR_FORM_SIZE  => 'Dung lượng ảnh vượt quá giới hạn',
                UPLOAD_ERR_PARTIAL    => 'Tập tin chỉ được tải lên một phần',
                UPLOAD_ERR_NO_FILE    => 'Không có tập tin nào được tải lên',
                UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm',
                UPLOAD_ERR_CANT_WRITE => 'Không ghi được tập tin lên đĩa',
                UPLOAD_ERR_EXTENSION  => 'Một extension của PHP đã chặn quá trình tải tập tin',
            ];

            $error_code = $file['error'];
            $message = $error_messages[$error_code] ?? 'Lỗi không xác định khi tải ảnh lên (Mã: ' . $error_code . ')';

            resError($message);
        }


        $imageContent = file_get_contents($file['tmp_name']);

        // Kiểm tra định dạng MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        switch ($mimeType) {
            case 'image/jpeg':
                $ext = 'jpg';
                break;
            case 'image/png':
                $ext = 'png';
                break;
            case 'image/gif':
                $ext = 'gif';
                break;
            case 'image/webp':
                $ext = 'webp';
                break;
            case 'image/bmp':
            case 'image/x-ms-bmp':
                $ext = 'bmp';
                break;
            case 'image/x-icon':
                $ext = 'ico';
                break;
            case 'image/tiff':
                $ext = 'tiff';
                break;
            case 'image/svg+xml':
                $ext = 'svg';
                break;
            case 'image/heif':
                $ext = 'heif';
                break;
            case 'image/heic':
                $ext = 'heic';
                break;
            default:
                resError('Định dạng ảnh không hợp lệ');
        }

        $ngay_sinh = str_replace(' ', '', $ngay_sinh);

        if (!$this->isValidDateYMD($ngay_sinh)) {
            resError('Ngày sinh không hợp lệ');
        }

        $lhkc_sdt_di_dong = str_replace(' ', '', $lhkc_sdt_di_dong);
        if (!$this->isValidPhoneNumber($lhkc_sdt_di_dong)) {
            resError("Số điện thoại không hợp lệ");
        }

        // Kiểm tra nhân viên
        $nhanvien = $this->Hrm_nhan_vien_model->find($id_nhan_vien);
        if (!$nhanvien) {
            resError('Không có nhân viên tương ứng');
        }

        $ho_va_ten_format = $this->format_string($ho_va_ten);
        $ngay_sinh_format = $this->format_date($ngay_sinh);
        $filename = $ho_va_ten_format . '_' . $cccd_so . '_' . $ngay_sinh_format . '_' . time() . '.' . $ext;
        $uploadPath = 'uploads/employees/avatars/';
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $saved = file_put_contents($uploadPath . $filename, $imageContent);
        if (!$saved) {
            resError('Không thể lưu ảnh');
        }
        $this->Hrm_nhan_vien_model
            ->where('ql_nguoi_dung_id', $nhanvien['ql_nguoi_dung_id'])
            ->update([
                'avatar' => $uploadPath . $filename,
                'ngay_sinh' => $ngay_sinh,
                'cccd_so' => $cccd_so,
                'cccd_ngay_cap' => $cccd_ngay_cap,
                'cccd_noi_cap' => $cccd_noi_cap,
                'lhkc_sdt_di_dong' => $lhkc_sdt_di_dong,
                'hktt_dia_chi' => $hktt_dia_chi,
            ]);

        resSuccess(['avatar' => $uploadPath . $filename], 'Lưu thông tin thành công');
    }

    // public function save_image_upload_post()
    // {
    //     $id_nhan_vien = $_POST['id_nhan_vien'] ?? null;
    //     $ho_va_ten    = $_POST['ho_va_ten'] ?? null;
    //     $ngay_sinh    = $_POST['ngay_sinh'] ?? null;
    //     $cccd_so      = $_POST['so_cccd'] ?? null;
    //     $cccd_ngay_cap = $_POST['ngay_cap'] ?? null;
    //     $cccd_noi_cap  = $_POST['noi_cap'] ?? null;
    //     $lhkc_sdt_di_dong = $_POST['sdt'] ?? null;
    //     $hktt_dia_chi = $_POST['dc_thuong_tru'] ?? null;

    //     if (!$id_nhan_vien) resError('Thiếu ID nhân viên');
    //     if (!$ho_va_ten) resError('Thiếu họ và tên');
    //     if (!$ngay_sinh) resError('Thiếu ngày sinh');
    //     if (!$cccd_so) resError('Thiếu số CCCD');

    //     $file = $_FILES['image'] ?? null;
    //     if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    //         $error_messages = [
    //             UPLOAD_ERR_INI_SIZE   => 'Dung lượng ảnh vượt quá giới hạn',
    //             UPLOAD_ERR_FORM_SIZE  => 'Dung lượng ảnh vượt quá giới hạn',
    //             UPLOAD_ERR_PARTIAL    => 'Tập tin chỉ được tải lên một phần',
    //             UPLOAD_ERR_NO_FILE    => 'Không có tập tin nào được tải lên',
    //             UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm',
    //             UPLOAD_ERR_CANT_WRITE => 'Không ghi được tập tin lên đĩa',
    //             UPLOAD_ERR_EXTENSION  => 'Một extension của PHP đã chặn quá trình tải tập tin',
    //         ];
    //         $message = $error_messages[$file['error']] ?? 'Lỗi không xác định khi tải ảnh';
    //         resError($message);
    //     }

    //     // Lấy định dạng ảnh
    //     $finfo = finfo_open(FILEINFO_MIME_TYPE);
    //     $mimeType = finfo_file($finfo, $file['tmp_name']);
    //     finfo_close($finfo);

    //     switch ($mimeType) {
    //         case 'image/jpeg':
    //             $ext = 'jpg';
    //             break;
    //         case 'image/png':
    //             $ext = 'png';
    //             break;
    //         case 'image/webp':
    //             $ext = 'webp';
    //             break;
    //         default:
    //             resError('Chỉ hỗ trợ ảnh JPG, PNG, WEBP');
    //     }

    //     if (!$this->isValidDateYMD($ngay_sinh)) {
    //         resError('Ngày sinh không hợp lệ');
    //     }

    //     $lhkc_sdt_di_dong = str_replace(' ', '', $lhkc_sdt_di_dong);
    //     if (!$this->isValidPhoneNumber($lhkc_sdt_di_dong)) {
    //         resError("Số điện thoại không hợp lệ");
    //     }

    //     $nhanvien = $this->Hrm_nhan_vien_model->find($id_nhan_vien);
    //     if (!$nhanvien) {
    //         resError('Không có nhân viên tương ứng');
    //     }

    //     $ho_va_ten_format = $this->format_string($ho_va_ten);
    //     $ngay_sinh_clean = str_replace(' ', '', $ngay_sinh);
    //     $ngay_sinh_format = $this->format_date($ngay_sinh_clean);
    //     $filename = $ho_va_ten_format . '_' . $cccd_so . '_' . $ngay_sinh_format . '.' . $ext;
    //     $uploadPath = 'uploads/employees/avatars/';
    //     if (!file_exists($uploadPath)) {
    //         mkdir($uploadPath, 0777, true);
    //     }

    //     $savePath = $uploadPath . $filename;

    //     // Resize ảnh xuống < 3MB
    //     $maxSizeBytes = 3 * 1024 * 1024; // 3MB
    //     switch ($ext) {
    //         case 'jpg':
    //             $src = imagecreatefromjpeg($file['tmp_name']);
    //             imagejpeg($src, $savePath, 75); // Giảm chất lượng còn 75%
    //             break;
    //         case 'png':
    //             $src = imagecreatefrompng($file['tmp_name']);
    //             imagepng($src, $savePath, 6); // 0-9 (nén cao hơn thì chậm hơn)
    //             break;
    //         case 'webp':
    //             $src = imagecreatefromwebp($file['tmp_name']);
    //             imagewebp($src, $savePath, 75);
    //             break;
    //     }

    //     clearstatcache();
    //     if (filesize($savePath) > $maxSizeBytes) {
    //         unlink($savePath);
    //         resError('Ảnh sau khi nén vẫn vượt quá 3MB');
    //     }

    //     // Cập nhật DB
    //     $this->Hrm_nhan_vien_model
    //         ->where('ql_nguoi_dung_id', $nhanvien['ql_nguoi_dung_id'])
    //         ->update([
    //             'avatar' => $savePath,
    //             'ngay_sinh' => $ngay_sinh,
    //             'cccd_so' => $cccd_so,
    //             'cccd_ngay_cap' => $cccd_ngay_cap,
    //             'cccd_noi_cap' => $cccd_noi_cap,
    //             'lhkc_sdt_di_dong' => $lhkc_sdt_di_dong,
    //             'hktt_dia_chi' => $hktt_dia_chi,
    //         ]);

    //     resSuccess(['avatar' => $savePath], 'Lưu thông tin thành công');
    // }



    public function save_image_url_post()
    {
        $rawData = file_get_contents("php://input");
        $data = json_decode($rawData, true);

        $imageUrl   = $data['image_url'] ?? null;
        $id_nhan_vien  = $data['id_nhan_vien'] ?? null;

        $ho_va_ten  = $data['ho_va_ten'] ?? null;
        $ngay_sinh  = $data['ngay_sinh'] ?? null;
        $cccd_so    = $data['so_cccd'] ?? null;
        $cccd_ngay_cap  = $data['ngay_cap'] ?? null;
        $cccd_noi_cap  = $data['noi_cap'] ?? null;
        $lhkc_sdt_di_dong  = $data['sdt'] ?? null;
        $hktt_dia_chi = $data['dc_thuong_tru'] ?? null;

        $imageUrl = $this->convertOpenLinkToView($imageUrl);

        if (!$imageUrl || !$id_nhan_vien) {
            resError('Thiếu dữ liệu cần thiết (image_url hoặc id_nhan_vien)');
        }

        if (!$this->isValidDateYMD($ngay_sinh)) {
            resError('Ngày sinh không hợp lệ');
        }

        if (!$this->isValidPhoneNumber($lhkc_sdt_di_dong)) {
            resError("Số điện thoại không hợp lệ");
        }

        // Kiểm tra nhân viên
        $nhanvien = $this->Hrm_nhan_vien_model->find($id_nhan_vien);
        if (!$nhanvien) {
            resError('Không có nhân viên tương ứng');
        }

        // Nếu là link Google Drive dạng "view", convert sang download
        if (preg_match('/drive\.google\.com\/file\/d\/(.*?)\//', $imageUrl, $matches)) {
            $fileId = $matches[1];
            $imageUrl = 'https://drive.google.com/uc?export=download&id=' . $fileId;
        }

        // Tải ảnh bằng cURL
        $ch = curl_init($imageUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_HEADER, true); // Lấy cả header
        curl_setopt($ch, CURLOPT_NOBODY, false); // Để lấy body ảnh
        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        $imageContent = substr($response, $headerSize);
        curl_close($ch);

        if (!$imageContent) {
            resError('Không thể tải ảnh');
        }

        // Lấy đuôi từ Content-Type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $imageContent);
        finfo_close($finfo);

        switch ($mimeType) {
            case 'image/jpeg':
                $ext = 'jpg';
                break;
            case 'image/png':
                $ext = 'png';
                break;
            case 'image/gif':
                $ext = 'gif';
                break;
            case 'image/webp':
                $ext = 'webp';
                break;
            default:
                $ext = 'jpg';
                break;
        }

        $ho_va_ten_format = $this->format_string($ho_va_ten);
        $ngay_sinh_format = $this->format_date($ngay_sinh);
        $filename = $ho_va_ten_format . '_' . $cccd_so . '_' . $ngay_sinh_format . '.' . $ext;
        $uploadPath = 'uploads/employees/avatars/';
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $saved = file_put_contents($uploadPath . $filename, $imageContent);
        if (!$saved) {
            resError('Không thể lưu ảnh');
        }

        // Cập nhật DB
        $this->Hrm_nhan_vien_model
            ->where('ql_nguoi_dung_id', $nhanvien['ql_nguoi_dung_id'])
            ->update([
                'avatar' => $uploadPath . $filename,
                'ngay_sinh' => $ngay_sinh,
                'cccd_so' => $cccd_so,
                'cccd_ngay_cap' => $cccd_ngay_cap,
                'cccd_noi_cap' => $cccd_noi_cap,
                'lhkc_sdt_di_dong' => $lhkc_sdt_di_dong,
                'hktt_dia_chi' => $hktt_dia_chi,
            ]);

        resSuccess(['avatar' => $uploadPath . $filename], 'Lưu thông tin thành công');
    }

    function convertOpenLinkToView($link)
    {
        // Nếu là dạng open?id=
        if (preg_match('/open\?id=([a-zA-Z0-9_-]+)/', $link, $matches)) {
            $fileId = $matches[1];
            return "https://drive.google.com/file/d/{$fileId}/view";
        }

        // Nếu đã là link view thì giữ nguyên
        return $link;
    }

    function isValidDateYMD($date)
    {
        // Kiểm tra định dạng ban đầu: yyyy-mm-dd
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        // Tách các thành phần năm, tháng, ngày
        list($year, $month, $day) = explode('-', $date);

        // Kiểm tra ngày tháng hợp lệ
        return checkdate((int)$month, (int)$day, (int)$year);
    }

    function isValidPhoneNumber($phone)
    {
        // Kiểm tra: bắt đầu bằng số 0, theo sau là 9 chữ số (tổng cộng 10 số)
        return preg_match('/^0\d{9}$/', $phone) === 1;
    }

    function format_string($str)
    {
        // Bỏ dấu tiếng Việt
        $str = preg_replace([
            "/[àáạảãâầấậẩẫăằắặẳẵ]/u",
            "/[èéẹẻẽêềếệểễ]/u",
            "/[ìíịỉĩ]/u",
            "/[òóọỏõôồốộổỗơờớợởỡ]/u",
            "/[ùúụủũưừứựửữ]/u",
            "/[ỳýỵỷỹ]/u",
            "/[đ]/u",
            "/[ÀÁẠẢÃÂẦẤẬẨẪĂẰẮẶẲẴ]/u",
            "/[ÈÉẸẺẼÊỀẾỆỂỄ]/u",
            "/[ÌÍỊỈĨ]/u",
            "/[ÒÓỌỎÕÔỒỐỘỔỖƠỜỚỢỞỠ]/u",
            "/[ÙÚỤỦŨƯỪỨỰỬỮ]/u",
            "/[ỲÝỴỶỸ]/u",
            "/[Đ]/u"
        ], [
            "a",
            "e",
            "i",
            "o",
            "u",
            "y",
            "d",
            "a",
            "e",
            "i",
            "o",
            "u",
            "y",
            "d"
        ], $str);

        // Chuyển về chữ thường
        $str = mb_strtolower($str, 'UTF-8');

        // Thay khoảng trắng hoặc nhiều khoảng trắng liên tiếp bằng dấu _
        $str = preg_replace('/\s+/', '_', $str);

        // Loại bỏ các ký tự không phải chữ cái, số hoặc dấu _
        $str = preg_replace('/[^a-z0-9_]/', '', $str);

        // Loại bỏ dấu _ ở đầu hoặc cuối (nếu có)
        $str = trim($str, '_');

        return $str;
    }

    function format_date($date)
    {
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        return $dt ? $dt->format('d.m.Y') : '';
    }

    public function delete_avatar_post()
    {
        $id = commonRequest('id_nhan_vien') ?? null;
        if (empty($id)) {
            return resError('Thiếu ID nhân viên');
        }

        $nhanvien = $this->Hrm_nhan_vien_model->find($id);
        if (!$nhanvien) {
            return resError('Nhân viên không tồn tại');
        }

        if (empty($nhanvien['avatar'])) {
            return resError('Nhân viên chưa có ảnh hồ sơ');
        }

        // Xóa file vật lý
        $this->fileupload->delete($nhanvien['avatar']);

        // Cập nhật DB
        $updated = $this->Hrm_nhan_vien_model->update_by([
            'id_nhan_vien' => $id,
        ], ['avatar' => null]);
        if (!$updated) {
            return resError('Không thể cập nhật dữ liệu');
        }

        return resSuccess(null, 'Xóa ảnh thành công');
    }

    public function getLoggedInUser_get()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError("Người dùng chưa đăng nhập");
        }

        $auth['ten_cong_viec'] = 'Đang cập nhật';
        $donvi = $this->E_don_vi_model->find($auth['id_don_vi']);
        $auth['ten_don_vi'] = $donvi ? $donvi['ten_don_vi'] : 'Đang cập nhật';

        $nhanvien = $this->Hrm_nhan_vien_model->where('ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])->first();
        if ($nhanvien) {
            $vitricongviec = $this->Hrm_vi_tri_cong_viec_model->find($nhanvien['id_vi_tri_cong_viec']);
            $auth['ten_cong_viec'] = $vitricongviec ? $vitricongviec['ten_cong_viec'] : 'Đang cập nhật';
        }

        resSuccess($auth, "Thông tin người dùng đăng nhập thành công");
    }
    function format_date_ddmmyyyy($date)
    {
        $p = explode('/', $date);
        if (count($p) !== 3 || !checkdate((int)$p[1], (int)$p[0], (int)$p[2])) return $date;

        return str_pad($p[0], 2, '0', STR_PAD_LEFT) . '/' .
            str_pad($p[1], 2, '0', STR_PAD_LEFT) . '/' .
            $p[2];
    }
}
