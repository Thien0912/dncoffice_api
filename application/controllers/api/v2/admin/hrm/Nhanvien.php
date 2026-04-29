<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

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

        $publicSegments = [
            'nhanvien.index',
            'nhanvien.create',
            'nhanvien.update',
            'nhanvien.show',
            'nhanvien.delete',
            'nhanvien.get_by_unit',
            'nhanvien.last_code',
            'nhanvien.statistical'
        ];

        if (!$this->inSegment($publicSegments)) {
            $this->permissionMiddleware();
        }
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
            'Ql_nhat_ky_model',
        ]);
        $this->load->library(['Validator', 'Fileupload', 'Validate', 'Pxl', 'Common']);
        $this->load->helper(['hrm']);
    }

    public function get_by_unit_get()
    {
        $id_don_vi = commonRequest('id_don_vi');
        $exclude_positions = commonRequest('exclude_positions'); // Expecting json string or array? Usually query params come as strings or arrays depending on framework. `commonRequest` seems to wrap input. 
        // Let's assume it might come as a JSON string if sent complex or just check how it's sent.
        // If sent as ?exclude_positions[]=Bảo vệ&exclude_positions[]=Tài xế it might be array.
        // If sent as ?exclude_positions=["Bảo vệ", "Tài xế"] it might be string.

        // Handling both cases robustly
        $excludes = [];
        if ($exclude_positions) {
            if (is_array($exclude_positions)) {
                $excludes = $exclude_positions;
            } else if (is_string($exclude_positions)) {
                $decoded = json_decode($exclude_positions, true);
                if (is_array($decoded)) {
                    $excludes = $decoded;
                } else {
                    $excludes = [$exclude_positions];
                }
            }
        }

        if (!$id_don_vi) {
            resBadrequest(['message' => 'Đơn vị là bắt buộc']);
        }

        $data = $this->Hrm_nhan_vien_model->getEmployeesByUnit($id_don_vi, $excludes);

        if (!empty($data)) {
            foreach ($data as &$item) {
                if (!empty($item['ql_nguoi_dung_avatar']) && !filter_var($item['ql_nguoi_dung_avatar'], FILTER_VALIDATE_URL)) {
                    $item['ql_nguoi_dung_avatar'] = encryptString($item['ql_nguoi_dung_avatar']);
                }
            }
            // append_hhhv_to_list($data, 'ql_nguoi_dung_ho_ten'); // Không sử dụng cho danh sách nhân sự
        }

        resSuccess($data);
    }

    public function index_post()
    {
        if (commonRequest('action') == 'get_category_data') {
            $table = commonRequest('table');
            $fieldName = commonRequest('fieldName') ?? null;
            $fieldValue = commonRequest('fieldValue') ?? null;
            $start = commonRequest('start') ?? 0;
            $length = commonRequest('length') ?? null;
            $orderBy = commonRequest('orderBy') ?? null;
            $data = $this->getCategoryData($table, $fieldName, $fieldValue, $start, $length, $orderBy);

            if (isset($data['options'])) {
                resSuccess($data['data'], 'Lấy dữ liệu danh mục thành công', 200, true, $data['options']);
            }

            resSuccess($data['data'], 'Lấy dữ liệu danh mục thành công', 200, true);
            return;
        }

        $postData = $this->post();
        $response = $this->Hrm_nhan_vien_model->getAllNhanvien($postData);

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
                if ($item === null || (is_string($item) && trim($item) === '')) {
                    $dataNhanvien[$key] = null;
                } else {
                    $dataNhanvien[$key] = $item;
                }
            }

        if (isset($dataNhanvien['dang_yeu_cau_cap_nhat']))
            unset($dataNhanvien['dang_yeu_cau_cap_nhat']);
        if (isset($dataNhanvien['tu_dong_tang_phep']))
            unset($dataNhanvien['tu_dong_tang_phep']);
        }

        if (isset($dataNhanvien['dang_yeu_cau_cap_nhat']))
            unset($dataNhanvien['dang_yeu_cau_cap_nhat']);
        if (isset($dataNhanvien['tu_dong_tang_phep']))
            unset($dataNhanvien['tu_dong_tang_phep']);
        // dd($dataNhanvien);
        // resError('Error', 500, $dataNhanvien);

        //Thông tin công việc
        $dataCongviec = [];
        $hrm_cong_viec = $payload->hrm_nhan_vien_cong_viec ?? null;
        if ($hrm_cong_viec) {
            foreach ($hrm_cong_viec as $key => $item) {
                if ($item === null || (is_string($item) && trim($item) === '')) {
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
                if ($item === null || (is_string($item) && trim($item) === '')) {
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
        if (!empty($dataBaohiem) && $nhanvien['id_nhan_vien']) {
            $dataBaohiem['id_nhan_vien'] = $nhanvien['id_nhan_vien'];
            $baohiem = $this->Hrm_nhan_vien_bao_hiem_model->create($dataBaohiem);
        }

        //Quá trình công tác
        if (!empty($data_quatrinhcongtac) && $nhanvien['id_nhan_vien']) {
            $data_quatrinhcongtac['id_nhan_vien'] = $nhanvien['id_nhan_vien'];
            $this->Hrm_qua_trinh_cong_tac_model->create($data_quatrinhcongtac);
        }

        //Bằng cấp
        // if (!empty($dataBangcap) && $nhanvien['id_nhan_vien']) {
        //     $dataBangcap['id_nhan_vien'] = $nhanvien['id_nhan_vien'];
        //     $this->Hrm_nhan_vien_bang_cap_model->create($dataBangcap);
        // }

        // Đơn vị kiêm nhiệm
        $don_vi_kiem_nhiem = $payload->don_vi_kiem_nhiem ?? null;
        if (!empty($don_vi_kiem_nhiem) && is_array($don_vi_kiem_nhiem) && $nhanvien['id_nhan_vien']) {
            // Validate trùng đơn vị - quét hết, gịm tất cả lỗi
            $seenDonVi = []; // id_don_vi => index lần đầu
            $dupErrors = []; // danh sách các lỗi trùng
            foreach ($don_vi_kiem_nhiem as $idx => $kn) {
                $knData = (array) $kn;
                if (empty($knData['id_don_vi_cong_tac']))
                    continue;
                $idDv = $knData['id_don_vi_cong_tac'];
                if (isset($seenDonVi[$idDv])) {
                    // Lấy tên đơn vị bị trùng
                    $tenDv = $this->db->select('ten_don_vi')->where('id_don_vi', $idDv)->get('e_don_vi')->row_array();
                    $dupErrors[] = [
                        'index' => (int) $idx,
                        'message' => 'Trùng đơn vị kiêm nhiệm: "' . ($tenDv['ten_don_vi'] ?? 'ID ' . $idDv) . '". Mỗi đơn vị chỉ được chọn một lần.',
                    ];
                } else {
                    $seenDonVi[$idDv] = $idx;
                }
            }
            if (!empty($dupErrors)) {
                $this->db->trans_rollback();
                resBadrequest([
                    'don_vi_kiem_nhiem_errors' => $dupErrors,
                ]);
            }
            foreach ($don_vi_kiem_nhiem as $kn) {
                $knData = (array) $kn;
                if (empty($knData['id_don_vi_cong_tac']))
                    continue;
                $laLanhDao = isset($knData['la_lanh_dao']) && ($knData['la_lanh_dao'] == '1' || $knData['la_lanh_dao'] === true) ? 1 : 0;
                $this->db->insert('hrm_nhan_vien_don_vi', [
                    'id_nhan_vien' => $nhanvien['id_nhan_vien'],
                    'id_don_vi_cong_tac' => $knData['id_don_vi_cong_tac'],
                    'id_vi_tri_cong_viec' => $knData['id_vi_tri_cong_viec'] ?: null,
                    'la_lanh_dao' => $laLanhDao,
                    'ghi_chu' => $knData['ghi_chu'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                // Nếu là lãnh đạo: thêm vào e_lanh_dao_don_vi và đánh dấu user là lãnh đạo
                if ($laLanhDao) {
                    $qlNguoiDungId = $nhanvien['ql_nguoi_dung_id'] ?? null;
                    if ($qlNguoiDungId) {
                        // Kiểm tra tránh insert trùng
                        $exists = $this->db
                            ->where('id_don_vi', $knData['id_don_vi_cong_tac'])
                            ->where('ql_nguoi_dung_id', $qlNguoiDungId)
                            ->count_all_results('e_lanh_dao_don_vi');
                        if (!$exists) {
                            $this->db->insert('e_lanh_dao_don_vi', [
                                'id_don_vi' => $knData['id_don_vi_cong_tac'],
                                'ql_nguoi_dung_id' => $qlNguoiDungId,
                            ]);
                        }
                        $this->db->where('ql_nguoi_dung_id', $qlNguoiDungId)
                            ->update('ql_nguoi_dung', ['ql_nguoi_dung_la_lanh_dao' => 1]);
                    }
                }
            }
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

        // --- SAVE HISTORY for create ---
        $labels = [
            'ma_nhan_vien' => 'Mã nhân viên',
            'ho_va_ten' => 'Họ và tên',
            'gioi_tinh' => 'Giới tính',
            'ngay_sinh' => 'Ngày sinh',
            'mst_ca_nhan' => 'MST cá nhân',
            'id_don_vi_cong_tac' => 'Đơn vị công tác',
            'id_vi_tri_cong_viec' => 'Vị trí công việc',
            'id_quoc_tich' => 'Quốc tịch',
            'id_dan_toc' => 'Dân tộc',
            'id_ton_giao' => 'Tôn giáo',
            'hktt_id_quoc_gia' => 'Quốc gia (HKTT)',
            'hktt_id_tinh_tp' => 'Tỉnh/TP (HKTT)',
            'hktt_id_quan_huyen' => 'Quận/Huyện (HKTT)',
            'hktt_id_xa_phuong' => 'Phường/Xã (HKTT)',
            'hktt_so_nha' => 'Số nhà (HKTT)',
            'hktt_dia_chi' => 'Địa chỉ (HKTT)',
            'so_dien_thoai' => 'Số điện thoại',
            'email' => 'Email',
            'que_quan' => 'Quê quán',
            'avatar' => 'Ảnh đại diện',
            'ma_cham_cong' => 'Mã chấm công',
            'chuc_danh' => 'Chức danh',
            'cap' => 'Cấp',
            'bac' => 'Bậc',
            'trang_thai' => 'Trạng thái',
            'loai_hop_dong' => 'Loại hợp đồng',
            'ngay_tap_su' => 'Ngày tập sự',
            'ngay_thu_viec' => 'Ngày thử việc',
            'ngay_lam_chinh_thuc' => 'Ngày làm chính thức',
            'so_ngay_phep' => 'Số ngày phép',
            'cccd_so' => 'Số CCCD',
            'cccd_ngay_cap' => 'Ngày cấp CCCD',
            'cccd_noi_cap' => 'Nơi cấp CCCD',
            'cccd_ngay_het_han' => 'Ngày hết hạn CCCD',
        ];

        // Skip internal/system fields
        $skipFields = ['created_at', 'created_user_id', 'updated_at', 'updated_user_id', 'id_nhan_vien', 'ql_nguoi_dung_id'];

        // --- GET LABEL HELPER ---
        $resolveLabel = function ($field, $val) {
            if ($val === null || $val === '')
                return null;
            if ($field === 'id_ca_lam_viec') {
                $row = $this->db->select('ca_lam_viec')->where('id', $val)->get('hrm_ca_lam_viec')->row_array();
                return $row ? $row['ca_lam_viec'] : $val;
            }
            if ($field === 'id_don_vi_cong_tac') {
                $row = $this->db->select('ten_don_vi')->where('id_don_vi', $val)->get('e_don_vi')->row_array();
                return $row ? $row['ten_don_vi'] : $val;
            }
            if ($field === 'id_vi_tri_cong_viec') {
                $row = $this->db->select('ten_cong_viec')->where('id_vi_tri_cong_viec', $val)->get('hrm_vi_tri_cong_viec')->row_array();
                return $row ? $row['ten_cong_viec'] : $val;
            }
            if ($field === 'id_dan_toc') {
                $row = $this->db->select('ten')->where('id_dan_toc', $val)->get('dm_dan_toc')->row_array();
                return $row ? $row['ten'] : $val;
            }
            if ($field === 'id_ton_giao') {
                $row = $this->db->select('ten')->where('id_ton_giao', $val)->get('dm_ton_giao')->row_array();
                return $row ? $row['ten'] : $val;
            }
            if (in_array($field, ['id_quoc_tich', 'hktt_id_quoc_gia', 'cohn_id_quoc_gia'])) {
                $row = $this->db->select('ten')->where('id_quoc_gia', $val)->get('dm_quoc_gia')->row_array();
                return $row ? $row['ten'] : $val;
            }
            if (in_array($field, ['hktt_id_tinh_tp', 'cohn_id_tinh_tp'])) {
                $row = $this->db->select('name')->where('id', $val)->get('provinces')->row_array();
                return $row ? $row['name'] : $val;
            }
            if (in_array($field, ['hktt_id_quan_huyen', 'cohn_id_quan_huyen'])) {
                $row = $this->db->select('name')->where('id', $val)->get('districts')->row_array();
                return $row ? $row['name'] : $val;
            }
            if (in_array($field, ['hktt_id_xa_phuong', 'cohn_id_xa_phuong'])) {
                $row = $this->db->select('name')->where('id', $val)->get('wards')->row_array();
                return $row ? $row['name'] : $val;
            }
            return $val;
        };

        $formatChangeValue = function ($field, $val) use ($resolveLabel) {
            if ($val === null || $val === '')
                return null;
            if ($field === 'avatar') {
                return [
                    'value' => encryptString($val),
                    'label' => 'Ảnh đại diện'
                ];
            }
            if (in_array($field, ['id_ca_lam_viec', 'id_don_vi_cong_tac', 'id_vi_tri_cong_viec', 'id_dan_toc', 'id_ton_giao', 'id_quoc_tich', 'hktt_id_quoc_gia', 'cohn_id_quoc_gia', 'hktt_id_tinh_tp', 'cohn_id_tinh_tp', 'hktt_id_quan_huyen', 'cohn_id_quan_huyen', 'hktt_id_xa_phuong', 'cohn_id_xa_phuong'])) {
                return [
                    'value' => $val,
                    'label' => $resolveLabel($field, $val)
                ];
            }
            return [
                'value' => $val,
                'label' => $val
            ];
        };

        $history_changes = [];
        $allData = array_merge($dataNhanvien, $dataCongviec, $dataBaohiem);

        foreach ($allData as $field => $value) {
            if (in_array($field, $skipFields))
                continue;
            if ($value === null || $value === '')
                continue;

            $history_changes[] = [
                'field' => $field,
                'field_name' => $labels[$field] ?? $field,
                'old_value' => null,
                'new_value' => $formatChangeValue($field, $value)
            ];
        }

        if (!empty($history_changes)) {
            $this->db->insert('hrm_nhan_vien_lich_su', [
                'id_nhan_vien' => $nhanvien['id_nhan_vien'],
                'action' => 'Tạo mới hồ sơ',
                'changes' => json_encode($history_changes, JSON_UNESCAPED_UNICODE),
                'created_user_id' => $userId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        // --- END SAVE HISTORY ---

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
            'avatar' => $avatar ? encryptString($avatar) : null,
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
            'avatar' => $avatar ? encryptString($avatar) : null,
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

            $dsFile = [];
            $dsFile = json_decode($hd['files_hop_dong'], true);

            if (!empty($dsFile)) {
                foreach ($dsFile as $key => &$file) {
                    if (!isset($file['file_path'])) {
                        unset($dsFile[$key]);
                        continue;
                    }

                    $file['file_path'] = encryptString($file['file_path']);
                }
            }
            unset($file);

            $hd['files_hop_dong'] = $dsFile;
        }
        unset($hd);

        $nhanvien['hop_dong'] = $hopdong;

        $nhanvien['thong_tin_bao_hiem']['muc_luong_bao_hiem'] = number_format((float) $mucDongBaoHiem, 0, ',', '.');

        //Quá trình công tác        
        $qtct = $this->Hrm_qua_trinh_cong_tac_model
            ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_qua_trinh_cong_tac.id_don_vi', 'left')
            ->join('hrm_vi_tri_cong_viec', 'hrm_vi_tri_cong_viec.id_vi_tri_cong_viec = hrm_qua_trinh_cong_tac.id_vi_tri_cong_viec', 'left')
            ->where('hrm_qua_trinh_cong_tac.id_nhan_vien', $id)
            ->orderBy('hrm_qua_trinh_cong_tac.ngay_bat_dau', 'DESC')
            ->select('hrm_qua_trinh_cong_tac.*, e_don_vi.ten_don_vi, hrm_vi_tri_cong_viec.*')
            ->get();

        if (!empty($qtct)) {
            foreach ($qtct as &$hd) {
                $dsFile = [];
                $dsFile = json_decode($hd['files'], true);

                if (!empty($dsFile)) {
                    foreach ($dsFile as &$file) {
                        $file['file_path'] = encryptString($file['file_path']);
                    }
                }
                unset($file);

                $hd['files'] = $dsFile;
            }
            unset($hd);
        }
        $nhanvien['qua_trinh_cong_tac'] = $qtct;
        ;

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
            ->where('hrm_thuong_danh_sach.id_nhan_vien', $id)
            ->where('hrm_thuong_danh_sach.deleted_at IS NULL')
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
        $nhanvien['danh_gia'] = $this->Hrm_danh_gia_nhan_su_model->where('id_nhan_vien', $id)->orderBy('thang', 'ASC')->get();

        //Bằng cấp
        $nhanvien['bang_cap'] = $this->Hrm_nhan_vien_bang_cap_model->where('id_nhan_vien', $id)->get();

        //Chứng chỉ
        $chungChiDS = $this->Hrm_chung_chi_model->where('id_nhan_vien', $id)->get();
        if (!empty($chungChiDS)) {
            foreach ($chungChiDS as &$cc) {
                if ($cc['files']) {
                    $files = json_decode($cc['files'], true);
                    if (!empty($files)) {
                        foreach ($files as &$file) {
                            $file['file_path'] = encryptString($file['file_path']);
                        }
                    }
                    unset($file);
                    $cc['files'] = $files;
                }
            }
            unset($cc);
        }
        $nhanvien['chung_chi'] = $chungChiDS;

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

        // Đơn vị kiêm nhiệm
        $donViKiemNhiemRaw = $this->db
            ->select('hrm_nhan_vien_don_vi.*, e_don_vi.ten_don_vi, hrm_vi_tri_cong_viec.ten_cong_viec')
            ->from('hrm_nhan_vien_don_vi')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_nhan_vien_don_vi.id_don_vi_cong_tac', 'left')
            ->join('hrm_vi_tri_cong_viec', 'hrm_vi_tri_cong_viec.id_vi_tri_cong_viec = hrm_nhan_vien_don_vi.id_vi_tri_cong_viec', 'left')
            ->where('hrm_nhan_vien_don_vi.id_nhan_vien', $id)
            ->get()
            ->result_array();
        // Map la_lanh_dao -> boolean cho frontend
        foreach ($donViKiemNhiemRaw as &$kn) {
            $kn['la_lanh_dao'] = ($kn['la_lanh_dao'] == '1' || $kn['la_lanh_dao'] === 1) ? true : false;
        }
        unset($kn);
        $nhanvien['don_vi_kiem_nhiem'] = $donViKiemNhiemRaw;

        // Thông tin yêu cầu cập nhật
        $id_yeu_cau_cap_nhat = commonRequest('id_yeu_cau_cap_nhat') ?? null;
        if ($id_yeu_cau_cap_nhat) {
            $yeu_cau = $this->Hrm_yeu_cau_cap_nhat_model->find($id_yeu_cau_cap_nhat);
            if ($yeu_cau && !empty($yeu_cau['du_lieu'])) {
                $du_lieu = json_decode($yeu_cau['du_lieu'], true);
                if (!empty($du_lieu['avatar'])) {
                    $du_lieu['avatar'] = encryptString($du_lieu['avatar']);
                }

                // Mã hoá mảng link minh chứng đính kèm
                if (!empty($du_lieu['minh_chung']) && is_array($du_lieu['minh_chung'])) {
                    foreach ($du_lieu['minh_chung'] as &$mc) {
                        if (!empty($mc['file_path'])) {
                            $mc['file_path_old'] = $mc['file_path'];
                            $mc['file_path'] = encryptString($mc['file_path']);
                        }
                    }
                    unset($mc);
                }

                // Mã hoá file_path trong bang_cap
                if (!empty($du_lieu['bang_cap']) && is_array($du_lieu['bang_cap'])) {
                    foreach ($du_lieu['bang_cap'] as &$bc) {
                        if (!empty($bc['file_path'])) {
                            $bc['file_path'] = encryptString($bc['file_path']);
                        }
                    }
                    unset($bc);
                }

                // Mã hoá file_path trong chung_chi.files (JSON array)
                if (!empty($du_lieu['chung_chi']) && is_array($du_lieu['chung_chi'])) {
                    foreach ($du_lieu['chung_chi'] as &$cc) {
                        if (!empty($cc['files'])) {
                            $files = is_string($cc['files']) ? json_decode($cc['files'], true) : $cc['files'];
                            if (is_array($files)) {
                                foreach ($files as &$f) {
                                    if (!empty($f['file_path'])) {
                                        $f['file_path'] = encryptString($f['file_path']);
                                    }
                                }
                                unset($f);
                                $cc['files'] = $files;
                            }
                        }
                    }
                    unset($cc);
                }

                $yeu_cau['du_lieu'] = json_encode($du_lieu);
            }
            $nhanvien['yeu_cau_cap_nhat'] = $yeu_cau;
        }
        $nhanvien['avatar'] = $avatar ? encryptString($avatar) : null;
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
                if ($item === null || (is_string($item) && trim($item) === '')) {
                    $dataNhanvien[$key] = null;
                } else {
                    $dataNhanvien[$key] = $item;
                }
            }

        if (isset($dataNhanvien['dang_yeu_cau_cap_nhat']))
            unset($dataNhanvien['dang_yeu_cau_cap_nhat']);
        if (isset($dataNhanvien['tu_dong_tang_phep']))
            unset($dataNhanvien['tu_dong_tang_phep']);
        }

        //Thông tin công việc
        $dataCongviec = [];
        $hrm_cong_viec = $payload->hrm_nhan_vien_cong_viec ?? null;
        if ($hrm_cong_viec) {
            foreach ($hrm_cong_viec as $key => $item) {
                if ($item === null || (is_string($item) && trim($item) === '')) {
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
                if ($item === null || (is_string($item) && trim($item) === '')) {
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
                if ($item === null || (is_string($item) && trim($item) === '')) {
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

        // Check unique ma_nhan_vien
        if (!empty($dataNhanvien['ma_nhan_vien'])) {
            $exist = $this->Hrm_nhan_vien_model
                ->where('ma_nhan_vien', $dataNhanvien['ma_nhan_vien'])
                ->where('id_nhan_vien !=', $id)
                ->where('deleted_at IS NULL')
                ->first();
            if ($exist) {
                // $errorsHrmnhanvien = array_merge($errorsHrmnhanvien, ['ma_nhan_vien' => 'Mã nhân viên đã tồn tại (' . $exist['ho_va_ten'] . ')']);
                // Gán lỗi trực tiếp để resBadrequest trả về đúng cấu trúc
                $errorsHrmnhanvien['ma_nhan_vien'] = 'Mã nhân viên đã tồn tại (' . $exist['ho_va_ten'] . ')';
            }
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
        $shouldDeleteOldAvatar = false;
        if (isset($_FILES['avatar'])) {
            if ($_FILES['avatar']) {
                $uploadedFile = $this->fileupload->upload($_FILES['avatar'], $folderName);
                if (!$uploadedFile['success']) {
                    $errorsHrmnhanvien = array_merge($errorsHrmnhanvien, ['avatar' => 'Không thể tải lên ảnh đại diện']);
                } else {
                    $dataNhanvien['avatar'] = $uploadedFile['file_path'];
                    $shouldDeleteOldAvatar = true;
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

        // --- PREPARE DATA FOR HISTORY TRACKING ---
        $congviec = $this->Hrm_nhan_vien_cong_viec_model->where('id_nhan_vien', $id)->first();
        $baohiem = $this->Hrm_nhan_vien_bao_hiem_model->where('id_nhan_vien', $id)->first();
        $chinhtriytequansu = $this->Hrm_nhan_vien_chinhtri_yte_quansu_model->where('id_nhan_vien', $id)->first();

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

            $this->Hrm_qua_trinh_cong_tac_model->where('id_nhan_vien', $id)
                ->where('ngay_ket_thuc IS NULL')
                ->update(['ngay_ket_thuc' => date('Y-m-d')]);
            // Thêm quá trình công tác mới
            $data_quatrinhcongtac['id_nhan_vien'] = $id;
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

        // Đơn vị kiêm nhiệm: xóa cũ rồi chèn lại
        $don_vi_kiem_nhiem = $payload->don_vi_kiem_nhiem ?? null;
        // Xóa kiêm nhiệm cũ của nhân viên này khỏi bảng hrm_nhan_vien_don_vi
        $this->db->where('id_nhan_vien', $id)->delete('hrm_nhan_vien_don_vi');
        // Xóa lãnh đạo kiêm nhiệm cũ khỏi e_lanh_dao_don_vi (chỉ xóa các đơn vị kiêm nhiệm, không ảnh hưởng đơn vị chính)
        $qlNguoiDungId = $nhanvien['ql_nguoi_dung_id'] ?? null;
        $idDonViChinh = $dataNhanvien['id_don_vi_cong_tac'] ?? ($nhanvien['id_don_vi_cong_tac'] ?? null);
        if ($qlNguoiDungId) {
            // Xóa khỏi e_lanh_dao_don_vi những đơn vị khác đơn vị chính
            $this->db
                ->where('ql_nguoi_dung_id', $qlNguoiDungId)
                ->where('id_don_vi !=', $idDonViChinh)
                ->delete('e_lanh_dao_don_vi');
        }
        if (!empty($don_vi_kiem_nhiem) && is_array($don_vi_kiem_nhiem)) {
            // Validate trùng đơn vị - quét hết, gịm tất cả lỗi
            $seenDonVi = [];
            $dupErrors = [];
            foreach ($don_vi_kiem_nhiem as $idx => $kn) {
                $knData = (array) $kn;
                if (empty($knData['id_don_vi_cong_tac']))
                    continue;
                $idDv = $knData['id_don_vi_cong_tac'];
                if (isset($seenDonVi[$idDv])) {
                    $tenDv = $this->db->select('ten_don_vi')->where('id_don_vi', $idDv)->get('e_don_vi')->row_array();
                    $dupErrors[] = [
                        'index' => (int) $idx,
                        'message' => 'Trùng đơn vị kiêm nhiệm: "' . ($tenDv['ten_don_vi'] ?? 'ID ' . $idDv) . '". Mỗi đơn vị chỉ được chọn một lần.',
                    ];
                } else {
                    $seenDonVi[$idDv] = $idx;
                }
            }
            if (!empty($dupErrors)) {
                $this->db->trans_rollback();
                resBadrequest([
                    'don_vi_kiem_nhiem_errors' => $dupErrors,
                ]);
            }
            $nowUpdate = date('Y-m-d H:i:s');
            foreach ($don_vi_kiem_nhiem as $kn) {
                $knData = (array) $kn;
                if (empty($knData['id_don_vi_cong_tac']))
                    continue;
                $laLanhDao = isset($knData['la_lanh_dao']) && ($knData['la_lanh_dao'] == '1' || $knData['la_lanh_dao'] === true) ? 1 : 0;
                $this->db->insert('hrm_nhan_vien_don_vi', [
                    'id_nhan_vien' => $id,
                    'id_don_vi_cong_tac' => $knData['id_don_vi_cong_tac'],
                    'id_vi_tri_cong_viec' => $knData['id_vi_tri_cong_viec'] ?: null,
                    'la_lanh_dao' => $laLanhDao,
                    'ghi_chu' => $knData['ghi_chu'] ?? null,
                    'created_at' => $nowUpdate,
                    'updated_at' => $nowUpdate,
                ]);
                // Nếu là lãnh đạo: thêm vào e_lanh_dao_don_vi
                if ($laLanhDao && $qlNguoiDungId) {
                    $exists = $this->db
                        ->where('id_don_vi', $knData['id_don_vi_cong_tac'])
                        ->where('ql_nguoi_dung_id', $qlNguoiDungId)
                        ->count_all_results('e_lanh_dao_don_vi');
                    if (!$exists) {
                        $this->db->insert('e_lanh_dao_don_vi', [
                            'id_don_vi' => $knData['id_don_vi_cong_tac'],
                            'ql_nguoi_dung_id' => $qlNguoiDungId,
                        ]);
                    }
                    $this->db->where('ql_nguoi_dung_id', $qlNguoiDungId)
                        ->update('ql_nguoi_dung', ['ql_nguoi_dung_la_lanh_dao' => 1]);
                }
            }
        }

        if ($qlNguoiDungId) {
            $hasLanhDaoDonVi = $this->db
                ->where('ql_nguoi_dung_id', $qlNguoiDungId)
                ->where('deleted_at IS NULL')
                ->count_all_results('e_lanh_dao_don_vi') > 0 ? 1 : 0;

            $this->db->where('ql_nguoi_dung_id', $qlNguoiDungId)
                ->update('ql_nguoi_dung', ['ql_nguoi_dung_la_lanh_dao' => $hasLanhDaoDonVi]);
        }

        // --- GET LABEL HELPER ---
        $resolveLabel = function ($field, $val) {
            if ($val === null || $val === '')
                return null;
            if ($field === 'id_ca_lam_viec') {
                $row = $this->db->select('ca_lam_viec')->where('id', $val)->get('hrm_ca_lam_viec')->row_array();
                return $row ? $row['ca_lam_viec'] : $val;
            }
            if ($field === 'id_don_vi_cong_tac') {
                $row = $this->db->select('ten_don_vi')->where('id_don_vi', $val)->get('e_don_vi')->row_array();
                return $row ? $row['ten_don_vi'] : $val;
            }
            if ($field === 'id_vi_tri_cong_viec') {
                $row = $this->db->select('ten_cong_viec')->where('id_vi_tri_cong_viec', $val)->get('hrm_vi_tri_cong_viec')->row_array();
                return $row ? $row['ten_cong_viec'] : $val;
            }
            if ($field === 'id_dan_toc') {
                $row = $this->db->select('ten')->where('id_dan_toc', $val)->get('dm_dan_toc')->row_array();
                return $row ? $row['ten'] : $val;
            }
            if ($field === 'id_ton_giao') {
                $row = $this->db->select('ten')->where('id_ton_giao', $val)->get('dm_ton_giao')->row_array();
                return $row ? $row['ten'] : $val;
            }
            if (in_array($field, ['id_quoc_tich', 'hktt_id_quoc_gia', 'cohn_id_quoc_gia'])) {
                $row = $this->db->select('ten')->where('id_quoc_gia', $val)->get('dm_quoc_gia')->row_array();
                return $row ? $row['ten'] : $val;
            }
            if (in_array($field, ['hktt_id_tinh_tp', 'cohn_id_tinh_tp'])) {
                $row = $this->db->select('name')->where('id', $val)->get('provinces')->row_array();
                return $row ? $row['name'] : $val;
            }
            if (in_array($field, ['hktt_id_quan_huyen', 'cohn_id_quan_huyen'])) {
                $row = $this->db->select('name')->where('id', $val)->get('districts')->row_array();
                return $row ? $row['name'] : $val;
            }
            if (in_array($field, ['hktt_id_xa_phuong', 'cohn_id_xa_phuong'])) {
                $row = $this->db->select('name')->where('id', $val)->get('wards')->row_array();
                return $row ? $row['name'] : $val;
            }
            return $val;
        };

        // Hàm helper chuyển đổi giá trị sang định dạng { value, label } dùng cho lịch sử mới
        $formatChangeValue = function ($field, $val) use ($resolveLabel) {
            if ($val === null || $val === '')
                return null;
            // Nếu là avatar thì mã hoá
            if ($field === 'avatar') {
                return [
                    'value' => encryptString($val),
                    'label' => 'Ảnh đại diện'
                ];
            }
            // Nếu là các trường ID cần dịch
            if (in_array($field, ['id_ca_lam_viec', 'id_don_vi_cong_tac', 'id_vi_tri_cong_viec', 'id_dan_toc', 'id_ton_giao', 'id_quoc_tich', 'hktt_id_quoc_gia', 'cohn_id_quoc_gia', 'hktt_id_tinh_tp', 'cohn_id_tinh_tp', 'hktt_id_quan_huyen', 'cohn_id_quan_huyen', 'hktt_id_xa_phuong', 'cohn_id_xa_phuong'])) {
                return [
                    'value' => $val,
                    'label' => $resolveLabel($field, $val)
                ];
            }
            // Các trường thường khác hoặc chưa cấu hình dịch
            return [
                'value' => $val,
                'label' => $val
            ];
        };

        // --- SAVE HISTORY ---
        $history_changes = [];
        $labels = [
            'ma_nhan_vien' => 'Mã nhân viên',
            'ho_va_ten' => 'Họ và tên',
            'gioi_tinh' => 'Giới tính',
            'ngay_sinh' => 'Ngày sinh',
            'mst_ca_nhan' => 'MST cá nhân',
            'id_don_vi_cong_tac' => 'Đơn vị công tác',
            'id_vi_tri_cong_viec' => 'Vị trí công việc',
            'id_quoc_tich' => 'Quốc tịch',
            'id_dan_toc' => 'Dân tộc',
            'id_ton_giao' => 'Tôn giáo',
            'hktt_id_quoc_gia' => 'Quốc gia (HKTT)',
            'hktt_id_tinh_tp' => 'Tỉnh/TP (HKTT)',
            'hktt_id_quan_huyen' => 'Quận/Huyện (HKTT)',
            'hktt_id_xa_phuong' => 'Phường/Xã (HKTT)',
            'hktt_so_nha' => 'Số nhà (HKTT)',
            'hktt_dia_chi' => 'Địa chỉ (HKTT)',
            'so_dien_thoai' => 'Số điện thoại',
            'email' => 'Email',
            'que_quan' => 'Quê quán',
            'avatar' => 'Ảnh đại diện',
            'ma_cham_cong' => 'Mã chấm công',
            'chuc_danh' => 'Chức danh',
            'cap' => 'Cấp',
            'bac' => 'Bậc',
            'trang_thai' => 'Trạng thái',
            'loai_hop_dong' => 'Loại hợp đồng',
            'ngay_tap_su' => 'Ngày tập sự',
            'ngay_thu_viec' => 'Ngày thử việc',
            'ngay_lam_chinh_thuc' => 'Ngày làm chính thức',
            'so_ngay_phep' => 'Số ngày phép',
            'cccd_so' => 'Số CCCD',
            'cccd_ngay_cap' => 'Ngày cấp CCCD',
            'cccd_noi_cap' => 'Nơi cấp CCCD',
            'cccd_ngay_het_han' => 'Ngày hết hạn CCCD'
        ];

        foreach ($dataNhanvien as $field => $new_value) {
            if (array_key_exists($field, (array) $nhanvien)) {
                $old_value = $nhanvien[$field];
                if ((string) $old_value !== (string) $new_value) {
                    $history_changes[] = [
                        'field' => $field,
                        'field_name' => $labels[$field] ?? $field,
                        'old_value' => $formatChangeValue($field, $old_value),
                        'new_value' => $formatChangeValue($field, $new_value)
                    ];
                }
            }
        }

        if ($congviec && !empty($dataCongviec)) {
            foreach ($dataCongviec as $field => $new_value) {
                if (array_key_exists($field, (array) $congviec)) {
                    $old_value = $congviec[$field];
                    if ((string) $old_value !== (string) $new_value) {
                        $history_changes[] = [
                            'field' => $field,
                            'field_name' => $labels[$field] ?? $field,
                            'old_value' => $formatChangeValue($field, $old_value),
                            'new_value' => $formatChangeValue($field, $new_value)
                        ];
                    }
                }
            }
        }

        foreach ($dataBaohiem as $field => $new_value) {
            if (array_key_exists($field, (array) $baohiem)) {
                $old_value = $baohiem[$field];
                if ((string) $old_value !== (string) $new_value) {
                    $history_changes[] = [
                        'field' => $field,
                        'field_name' => $labels[$field] ?? $field,
                        'old_value' => $formatChangeValue($field, $old_value),
                        'new_value' => $formatChangeValue($field, $new_value)
                    ];
                }
            }
        }

        if (!empty($history_changes)) {
            $auth = $this->getUserLogin();
            $this->db->insert('hrm_nhan_vien_lich_su', [
                'id_nhan_vien' => $id,
                'action' => 'Cập nhật hồ sơ',
                'changes' => json_encode($history_changes, JSON_UNESCAPED_UNICODE),
                'created_user_id' => $auth['ql_nguoi_dung_id'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Save to ql_nhat_ky to show up in common history interfaces
            // Đồng bộ cấu trúc lưu dữ liệu (FULL OBJECT) giống các controller khác
            $tenNhanVien = $nhanvien['ho_va_ten'] ?? 'ID ' . $id;
            $dataNew = array_merge((array) $nhanvien, $dataNhanvien, isset($baohiem) ? $baohiem : []);
            $this->createLog('update', 'Cập nhật hồ sơ nhân viên: ' . $tenNhanVien, (array) $nhanvien, $dataNew, 'hrm_nhan_vien');
        } else {
            // Fallback empty update
            $tenNhanVien = $nhanvien['ho_va_ten'] ?? 'ID ' . $id;
            $this->createLog('update', 'Cập nhật hồ sơ nhân viên (Không có thay đổi): ' . $tenNhanVien, (array) $nhanvien, (array) $nhanvien, 'hrm_nhan_vien');
        }
        // --- END SAVE HISTORY ---

        $this->db->trans_commit();
        //cập nhật thành công xóa avatar cũ
        if ($shouldDeleteOldAvatar && !empty($nhanvien['avatar'])) {
            $this->fileupload->delete($nhanvien['avatar']);
        }

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
        $file_excel_nhanvien = $_FILES['file_excel_nhanvien'] ? $_FILES['file_excel_nhanvien'] : null;
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
                'ma_nhan_vien' => $d['ma_nhan_vien'] ?? null,
                'ho_va_ten' => $d['ho_va_ten'] ?? null,
                'gioi_tinh' => $d['gioi_tinh'] ?? null,
                'ngay_sinh' => convertDateToISO($d['ngay_sinh']) ?? null,
                'so_dien_thoai' => $d['so_dien_thoai'] ?? null,
                'email' => $d['email'] ?? null,
                'que_quan' => $d['que_quan'] ?? null,
                'mst_ca_nhan' => $d['mst_ca_nhan'] ?? null,
                'id_don_vi_cong_tac' => $idDonVi ?? null,
                'id_vi_tri_cong_viec' => $id_vi_tri_cong_viec ?? null,
                'id_dan_toc' => $id_dan_toc ?? null,
                'id_ton_giao' => $id_ton_giao ?? null,
                'id_quoc_tich' => $id_quoc_gia ?? null,
                'cccd_so' => $d['cccd_so'] ?? null,
                'cccd_ngay_cap' => convertDateToISO($d['cccd_ngay_cap']) ?? null,
                'cccd_noi_cap' => $d['cccd_noi_cap'] ?? null,
                'cccd_ngay_het_han' => convertDateToISO($d['cccd_ngay_het_han']) ?? null,
                'ho_chieu_so' => $d['ho_chieu_so'] ?? null,
                'ho_chieu_ngay_cap' => convertDateToISO($d['ho_chieu_ngay_cap']) ?? null,
                'ho_chieu_noi_cap' => $d['ho_chieu_noi_cap'] ?? null,
                'ho_chieu_ngay_het_han' => convertDateToISO($d['ho_chieu_ngay_het_han']) ?? null,
                'trinh_do_vh' => $d['trinh_do_vh'] ?? null,
                'trinh_do_dt' => $d['trinh_do_dt'] ?? null,
                'noi_dt' => $d['noi_dt'] ?? null,
                'khoa_dt' => $d['khoa_dt'] ?? null,
                'nganh_dt' => $d['nganh_dt'] ?? null,
                'nam_tn' => $d['nam_tn'] ?? null,
                'xep_loai_tn' => $d['xep_loai_tn'] ?? null,
                'created_user_id' => $userId,
                'created_at' => $currentTime,
                'updated_user_id' => $userId,
                'updated_at' => $currentTime,
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
                'ma_cham_cong' => $d['ma_cham_cong'] ?? null,
                'chuc_danh' => $d['chuc_danh'] ?? null,
                'cap' => $d['cap'] ?? null,
                'bac' => $d['bac'] ?? null,
                'trang_thai' => $trang_thai ?? null,
                'loai_hop_dong' => $loai_hop_dong ?? null,
                'ngay_tap_su' => convertDateToISO($d['ngay_tap_su']) ?? null,
                'ngay_thu_viec' => convertDateToISO($d['ngay_thu_viec']) ?? null,
                'ngay_lam_chinh_thuc' => convertDateToISO($d['ngay_lam_chinh_thuc']) ?? null,
                'so_ngay_phep' => $d['so_ngay_phep'] ?? null,
                'created_user_id' => $userId,
                'created_at' => $currentTime,
                'updated_user_id' => $userId,
                'updated_at' => $currentTime,
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
            if ($issetCCCD)
                $rowErrors[] = 'Số căn cước đã trùng với ' . $issetCCCD['ho_va_ten'] . '.';
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
        return resSuccess(null, $message, 200, true, [
            'trang_thai' => Common::TRANG_THAI_CONG_VIEC['DANG_LAM_THU_TUC_THOI_VIEC']['value'],
        ]);
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
                if ($path)
                    unlink($path);
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

    // public function export_post()
    // {
    //     $postData = json_decode($this->input->raw_input_stream, true);
    //     $file_name = $postData['file_name'] ?? 'Danh_sach_nhan_su';

    //     // nếu không có đuôi .xlsx thì thêm vào
    //     if (pathinfo($file_name, PATHINFO_EXTENSION) !== 'xlsx') {
    //         $file_name .= '.xlsx';
    //     }

    //     $data = $this->Hrm_nhan_vien_model->Export($postData);
    //     if (empty($data)) {
    //         resError('Danh sách nhân viên trống');
    //     }

    //     $file_path = $this->setupExcel($file_name, $data);
    //     if (!file_exists($file_path)) {
    //         resError('Không tạo được file Excel');
    //     }

    //     $file_content = file_get_contents($file_path);
    //     $file_base64 = base64_encode($file_content);

    //     unlink($file_path);

    //     resSuccess([
    //         'file_name' => $file_name,
    //         'file_base64' => $file_base64,
    //     ]);
    // }


    // private function getEmployeeExportHeaders()
    // {
    //     return [
    //         // 'id_nhan_vien' => 'ID Nhân viên',
    //         'ma_nhan_vien' => 'Mã nhân viên',
    //         'ma_cham_cong' => 'Mã chấm công',
    //         'ho_va_ten' => 'Họ và tên',
    //         'email' => 'Email',
    //         'gioi_tinh' => 'Giới tính',
    //         'ngay_sinh' => 'Ngày sinh',
    //         'ten_don_vi' => 'Đơn vị',
    //         'trang_thai' => 'Trạng thái',
    //         'ngay_lam_chinh_thuc' => 'Ngày làm chính thức',
    //         'ngay_lam_chinh_thuc_ket_thuc' => 'Ngày kết thúc',
    //         'ten_cong_viec' => 'Chức vụ',
    //         'ca_lam_viec' => 'Ca làm việc',
    //         'cccd_so' => 'Số CCCD',
    //         'cccd_noi_cap' => 'Nơi cấp CCCD',
    //         'cccd_ngay_cap' => 'Ngày cấp CCCD',
    //         'cccd_ngay_het_han' => 'Ngày hết hạn CCCD',
    //         'ten_quoc_gia' => 'Quốc tịch',
    //         'ten_ton_giao' => 'Tôn giáo',
    //         'ten_dan_toc' => 'Dân tộc',
    //     ];
    // }


    // private function setupExcel($file_name, $data)
    // {
    //     $objPHPExcel = new PHPExcel;
    //     $sheet = $objPHPExcel->setActiveSheetIndex(0);
    //     $sheet->setTitle('Thông tin nhân viên');

    //     // Lấy headers từ function
    //     $headers = $this->getEmployeeExportHeaders();

    //     // Ghi header
    //     $col = 0;
    //     foreach ($headers as $header) {
    //         $sheet->setCellValueByColumnAndRow($col, 1, $header);

    //         // In đậm header
    //         $sheet->getStyleByColumnAndRow($col, 1)->getFont()->setBold(true);

    //         // Auto-size column
    //         $columnLetter = PHPExcel_Cell::stringFromColumnIndex($col);
    //         $sheet->getColumnDimension($columnLetter)->setAutoSize(true);

    //         $col++;
    //     }

    //     // Ghi data
    //     $row = 2;
    //     foreach ($data as $item) {
    //         $col = 0;
    //         foreach ($headers as $key => $header) {
    //             $value = $item[$key] ?? '';

    //             if (in_array($key, ['cccd_so', 'ma_nhan_vien', 'ma_cham_cong'])) {
    //                 $sheet->setCellValueExplicitByColumnAndRow(
    //                     $col,
    //                     $row,
    //                     $value,
    //                     PHPExcel_Cell_DataType::TYPE_STRING
    //                 );
    //             } else {
    //                 $sheet->setCellValueByColumnAndRow($col, $row, $value);
    //             }

    //             $col++;
    //         }
    //         $row++;
    //     }

    //     // đường dẫn vật lý trên server
    //     $file_path = FCPATH . 'assets/download/excel/excel_export/nhanvien/' . $file_name;

    //     $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    //     $writer->save($file_path);

    //     // trả về URL cho frontend download
    //     // $file_url = base_url('assets/download/excel/excel_export/nhanvien/' . $file_name);
    //     return $file_path;
    // }

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
        $ho_va_ten = $_POST['ho_va_ten'] ?? null;
        $ngay_sinh = $_POST['ngay_sinh'] ?? null;
        $cccd_so = $_POST['so_cccd'] ?? null;
        $cccd_ngay_cap = $_POST['ngay_cap'] ?? null;
        $cccd_noi_cap = $_POST['noi_cap'] ?? null;
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
                UPLOAD_ERR_INI_SIZE => 'Dung lượng ảnh vượt quá giới hạn',
                UPLOAD_ERR_FORM_SIZE => 'Dung lượng ảnh vượt quá giới hạn',
                UPLOAD_ERR_PARTIAL => 'Tập tin chỉ được tải lên một phần',
                UPLOAD_ERR_NO_FILE => 'Không có tập tin nào được tải lên',
                UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm',
                UPLOAD_ERR_CANT_WRITE => 'Không ghi được tập tin lên đĩa',
                UPLOAD_ERR_EXTENSION => 'Một extension của PHP đã chặn quá trình tải tập tin',
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

        $imageUrl = $data['image_url'] ?? null;
        $id_nhan_vien = $data['id_nhan_vien'] ?? null;

        $ho_va_ten = $data['ho_va_ten'] ?? null;
        $ngay_sinh = $data['ngay_sinh'] ?? null;
        $cccd_so = $data['so_cccd'] ?? null;
        $cccd_ngay_cap = $data['ngay_cap'] ?? null;
        $cccd_noi_cap = $data['noi_cap'] ?? null;
        $lhkc_sdt_di_dong = $data['sdt'] ?? null;
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
        return checkdate((int) $month, (int) $day, (int) $year);
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

    public function history_get($id)
    {
        $limit = commonRequest('limit') ? (int) commonRequest('limit') : 50;
        $offset = commonRequest('offset') ? (int) commonRequest('offset') : 0;

        $this->db->select('hrm_nhan_vien_lich_su.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten as created_by_name, ql_nguoi_dung.ql_nguoi_dung_avatar as created_by_avatar, ql_nguoi_dung.ql_nguoi_dung_email as created_by_email');
        $this->db->from('hrm_nhan_vien_lich_su');
        $this->db->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = hrm_nhan_vien_lich_su.created_user_id', 'left');
        $this->db->where('hrm_nhan_vien_lich_su.id_nhan_vien', $id);
        $this->db->order_by('hrm_nhan_vien_lich_su.created_at', 'DESC');
        if ($limit > 0) {
            $this->db->limit($limit, $offset);
        }

        $history = $this->db->get()->result_array();

        if (!empty($history)) {
            foreach ($history as &$row) {
                if (!empty($row['changes'])) {
                    $row['changes'] = json_decode($row['changes'], true);
                }
                if (!empty($row['created_by_avatar'])) {
                    $row['created_by_avatar'] = encryptString($row['created_by_avatar']);
                }
            }
            unset($row);
        }

        resSuccess($history, 'Lấy lịch sử thành công');
    }

    public function view_log_get()
    {
        $start = (int) (commonRequest('start') ?? 0);
        $length = (int) (commonRequest('length') ?? 20);
        $search = commonRequest('search') ?? '';
        $from_date = commonRequest('from_date') ?? '';
        $to_date = commonRequest('to_date') ?? '';
        $action_type = commonRequest('action_type') ?? ''; // create | update | delete

        // ── Field mapping: DB key → nhãn tiếng Việt (null = ẩn khỏi diff) ───
        $fieldMapping = [
            // Thông tin cơ bản
            'ho_va_ten' => 'Họ và tên',
            'email' => 'Email công ty',
            'email_ca_nhan' => 'Email cá nhân',
            'gioi_tinh' => 'Giới tính',
            'ngay_sinh' => 'Ngày sinh',
            'ma_nhan_vien' => 'Mã nhân viên',
            'ma_cham_cong' => 'Mã chấm công',
            'mst_ca_nhan' => 'Mã số thuế cá nhân',
            'so_dien_thoai' => 'Số điện thoại',
            'que_quan' => 'Quê quán',
            // CCCD / Hộ chiếu
            'cccd_so' => 'Số CCCD/CMND',
            'cccd_ngay_cap' => 'Ngày cấp CCCD',
            'cccd_noi_cap' => 'Nơi cấp CCCD',
            'cccd_ngay_het_han' => 'Ngày hết hạn CCCD',
            'ho_chieu_so' => 'Số hộ chiếu',
            'ho_chieu_ngay_cap' => 'Ngày cấp hộ chiếu',
            'ho_chieu_noi_cap' => 'Nơi cấp hộ chiếu',
            'ho_chieu_ngay_het_han' => 'Ngày hết hạn hộ chiếu',
            // Học vấn
            'trinh_do_vh' => 'Trình độ văn hóa',
            'trinh_do_dt' => 'Trình độ đào tạo',
            'hoc_ham' => 'Học hàm / Học vị',
            'noi_dt' => 'Nơi đào tạo',
            'khoa_dt' => 'Khoa đào tạo',
            'nganh_dt' => 'Ngành đào tạo',
            'nam_tn' => 'Năm tốt nghiệp',
            'xep_loai_tn' => 'Xếp loại tốt nghiệp',
            // Địa chỉ
            'hktt_dia_chi' => 'Địa chỉ hộ khẩu thường trú',
            'hktt_so_nha' => 'Số nhà HKTT',
            'cohn_dia_chi' => 'Địa chỉ chỗ ở hiện nay',
            'cohn_so_nha' => 'Số nhà chỗ ở hiện nay',
            // Liên hệ khẩn cấp
            'lhkc_ho_ten' => 'Tên liên hệ khẩn cấp',
            'lhkc_quan_he' => 'Quan hệ liên hệ khẩn cấp',
            'lhkc_sdt_di_dong' => 'SĐT liên hệ khẩn cấp',
            'lhkc_dia_chi' => 'Địa chỉ liên hệ khẩn cấp',
            // Đơn vị / chức danh (đã được map nhãn từ khi log)
            'Đơn vị công tác' => 'Đơn vị công tác',
            'Vị trí công việc' => 'Vị trí công việc',
            'id_ca_lam_viec' => 'Ca làm việc',
            'id_dan_toc' => 'Dân tộc',
            'id_ton_giao' => 'Tôn giáo',
            'id_quoc_tich' => 'Quốc tịch',
            // Bảo hiểm
            'ti_le_dong' => 'Tỷ lệ đóng BHXH (NLĐ %)',
            'ti_le_dong_dn' => 'Tỷ lệ đóng BHXH (DN %)',
            'noi_dk_kcb' => 'Nơi đăng ký KCB',
            'so_the_bhyt' => 'Số thẻ BHYT',
            'so_so_bhxh' => 'Số sổ BHXH',
            'ma_bhxh' => 'Mã BHXH',
            'avatar' => 'Ảnh đại diện',
            // ── Ẩn (null) ────────────────────────────────────────────────────
            'Mã nhân viên' => null,
            'ql_nguoi_dung_id' => null,
            'availability' => null,
            'Ngày tạo' => null,
            'Người tạo' => null,
            'Ngày cập nhật' => null,
            'Người cập nhật' => null,
            'Ngày xóa' => null,
            'Người xóa' => null,
            'id_nhan_vien_bao_hiem' => null,
            'ngay_tham_gia' => null,
            'ma_tinh_cap' => null,
            'ten_tinh_cap' => null,
            'ngay_het_han' => null,
            'id_noi_dk_kcb' => null,
            'ms_noi_kcb' => null,
            'hktt_id_quoc_gia' => null,
            'hktt_id_tinh_tp' => null,
            'hktt_id_quan_huyen' => null,
            'hktt_id_xa_phuong' => null,
            'hktt_so_ho_khau' => null,
            'hktt_ma_so_ho_gd' => null,
            'hktt_la_chu_ho' => null,
            'cohn_giong_hktt' => null,
            'cohn_id_quoc_gia' => null,
            'cohn_id_tinh_tp' => null,
            'cohn_id_quan_huyen' => null,
            'cohn_id_xa_phuong' => null,
            'lhkc_sdt_nha_rieng' => null,
            'lhkc_email' => null,
        ];

        // ── Build query ──────────────────────────────────────────────────────
        $this->db
            ->select('ql_nhat_ky.ql_nhat_ky_id, ql_nhat_ky.ql_nhat_ky_hanh_dong, ql_nhat_ky.ql_nhat_ky_noi_dung, ql_nhat_ky.ql_nhat_ky_gia_tri_cu, ql_nhat_ky.ql_nhat_ky_gia_tri_moi, ql_nhat_ky.ql_nhat_ky_bang_du_lieu, ql_nhat_ky.ql_nhat_ky_ngay_tao, ql_nguoi_dung.ql_nguoi_dung_ho_ten, ql_nguoi_dung.ql_nguoi_dung_email, ql_nguoi_dung.ql_nguoi_dung_avatar')
            ->from('ql_nhat_ky')
            ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = ql_nhat_ky.ql_nguoi_dung_id', 'left')
            ->where('ql_nhat_ky.ql_nhat_ky_controller', 'nhanvien')
            ->order_by('ql_nhat_ky.ql_nhat_ky_ngay_tao', 'DESC');

        if ($from_date)
            $this->db->where('ql_nhat_ky.ql_nhat_ky_ngay_tao >=', $from_date . ' 00:00:00');
        if ($to_date)
            $this->db->where('ql_nhat_ky.ql_nhat_ky_ngay_tao <=', $to_date . ' 23:59:59');

        if ($action_type) {
            if ($action_type === 'create')
                $this->db->like('ql_nhat_ky.ql_nhat_ky_hanh_dong', 'tao', 'both');
            elseif ($action_type === 'delete')
                $this->db->like('ql_nhat_ky.ql_nhat_ky_hanh_dong', 'xoa', 'both');
            elseif ($action_type === 'update')
                $this->db->like('ql_nhat_ky.ql_nhat_ky_hanh_dong', 'update', 'both');
        }

        if ($search) {
            $this->db->group_start();
            $this->db->like('ql_nhat_ky.ql_nhat_ky_hanh_dong', $search);
            $this->db->or_like('ql_nhat_ky.ql_nhat_ky_noi_dung', $search);
            $this->db->or_like('ql_nguoi_dung.ql_nguoi_dung_ho_ten', $search);
            $this->db->group_end();
        }

        // Count filtered
        $countQuery = clone $this->db;
        $recordsFiltered = $countQuery->count_all_results('', false);

        if ($length > 0)
            $this->db->limit($length, $start);
        $rows = $this->db->get()->result_array();

        // ── Process each row ─────────────────────────────────────────────────
        $data = [];
        foreach ($rows as $row) {
            $cuRaw = [];
            $moiRaw = [];

            if (!empty($row['ql_nhat_ky_gia_tri_cu']) && $row['ql_nhat_ky_gia_tri_cu'] !== 'null') {
                $decoded = json_decode($row['ql_nhat_ky_gia_tri_cu'], true);
                if (is_array($decoded))
                    $cuRaw = $decoded;
            }
            if (!empty($row['ql_nhat_ky_gia_tri_moi']) && $row['ql_nhat_ky_gia_tri_moi'] !== 'null') {
                $decoded = json_decode($row['ql_nhat_ky_gia_tri_moi'], true);
                if (is_array($decoded))
                    $moiRaw = $decoded;
            }

            $chi_tiet = [];

            // Detect new format: gia_tri_moi contains {field: {cu:{}, moi:{}}}
            $firstVal = !empty($moiRaw) ? reset($moiRaw) : null;
            $isNewFormat = is_array($firstVal) && (array_key_exists('cu', $firstVal) || array_key_exists('moi', $firstVal));

            if ($isNewFormat) {
                foreach ($moiRaw as $fieldKey => $change) {
                    if (!is_array($change))
                        continue;
                    $label = array_key_exists($fieldKey, $fieldMapping) ? $fieldMapping[$fieldKey] : $fieldKey;
                    if ($label === null)
                        continue;

                    $cuVal = isset($change['cu']) ? (is_array($change['cu']) && isset($change['cu']['label']) ? $change['cu']['label'] : $change['cu']) : null;
                    $moiVal = isset($change['moi']) ? (is_array($change['moi']) && isset($change['moi']['label']) ? $change['moi']['label'] : $change['moi']) : null;

                    $chi_tiet[$label] = ['cu' => $cuVal, 'moi' => $moiVal];
                }
            } else {
                // Old format: diff cuRaw vs moiRaw
                $allKeys = array_unique(array_merge(array_keys($cuRaw), array_keys($moiRaw)));
                foreach ($allKeys as $key) {
                    // Only process keys that are in our mapping
                    if (!array_key_exists($key, $fieldMapping))
                        continue;
                    $label = $fieldMapping[$key];
                    if ($label === null)
                        continue;

                    $cuVal = isset($cuRaw[$key]) ? $cuRaw[$key] : null;
                    $moiVal = isset($moiRaw[$key]) ? $moiRaw[$key] : null;

                    // Skip unchanged
                    if ($cuVal === $moiVal)
                        continue;
                    if (empty($cuVal) && empty($moiVal))
                        continue;

                    $chi_tiet[$label] = ['cu' => $cuVal, 'moi' => $moiVal];
                }
            }

            $data[] = [
                'ql_nhat_ky_id' => $row['ql_nhat_ky_id'],
                'ql_nhat_ky_hanh_dong' => $row['ql_nhat_ky_hanh_dong'],
                'ql_nhat_ky_noi_dung' => $row['ql_nhat_ky_noi_dung'],
                'ql_nhat_ky_bang_du_lieu' => $row['ql_nhat_ky_bang_du_lieu'],
                'ql_nhat_ky_ngay_tao' => $row['ql_nhat_ky_ngay_tao'],
                'ql_nguoi_dung_ho_ten' => $row['ql_nguoi_dung_ho_ten'],
                'ql_nguoi_dung_email' => $row['ql_nguoi_dung_email'],
                'ql_nguoi_dung_avatar' => !empty($row['ql_nguoi_dung_avatar']) ? encryptString($row['ql_nguoi_dung_avatar']) : null,
                'chi_tiet' => $chi_tiet,
            ];
        }

        resSuccess([
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ], 'Lấy lịch sử nhân viên thành công');
    }

    public function export_post()
    {
        $postData = json_decode($this->input->raw_input_stream, true);
        $data = $this->Hrm_nhan_vien_model->Export($postData);

        if (empty($data)) {
            resError('Không có dữ liệu để xuất');
        }

        $file_name = 'Danh_sach_nhan_su_' . date('dmY_His') . '.xlsx';
        $selectedColumns = $postData['selected_columns'] ?? [];
        $file_path = $this->setupExcel($file_name, $data, $selectedColumns);

        if ($file_path && file_exists($file_path)) {
            $file_base64 = base64_encode(file_get_contents($file_path));
            unlink($file_path);
            resSuccess(['file_base64' => $file_base64, 'file_name' => $file_name]);
        } else {
            resError('Lỗi khi tạo file Excel');
        }
    }

    private function setupExcel($file_name, $data, $selectedColumns = [])
    {
        $this->load->library('pxl');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Danh sách nhân sự');

        $allHeaders = $this->getEmployeeExportHeaders();
        $headers = [];

        // Luôn thêm STT vào đầu nếu được chọn hoặc không có lựa chọn nào
        $hasStt = empty($selectedColumns) || in_array('stt', $selectedColumns);
        $selectedColumnsWithoutStt = array_filter($selectedColumns, function ($k) {
            return $k !== 'stt';
        });

        if (!empty($selectedColumnsWithoutStt)) {
            foreach ($allHeaders as $h) {
                if ($h['key'] === 'stt')
                    continue; // xử lý STT riêng
                if (in_array($h['key'], $selectedColumnsWithoutStt)) {
                    $headers[] = $h;
                }
            }
        } else {
            foreach ($allHeaders as $h) {
                if ($h['key'] === 'stt')
                    continue;
                $headers[] = $h;
            }
        }

        // Xây dựng danh sách header cuối cùng (STT luôn đứng đầu)
        $finalHeaders = [];
        if ($hasStt) {
            $finalHeaders[] = ['key' => 'stt', 'label' => 'STT'];
        }
        foreach ($headers as $h) {
            $finalHeaders[] = $h;
        }

        // Style cho header row
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER],
        ];

        // Ghi header
        $col = 0;
        foreach ($finalHeaders as $header) {
            $columnLetter = PHPExcel_Cell::stringFromColumnIndex($col);
            $sheet->setCellValueByColumnAndRow($col, 1, $header['label']);
            $sheet->getStyleByColumnAndRow($col, 1)->applyFromArray($headerStyle);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
            $col++;
        }

        // Lấy màu từ Common::TRANG_THAI_CONG_VIEC, map theo label
        $statusColors = [];
        foreach (Common::TRANG_THAI_CONG_VIEC as $item) {
            $hex = ltrim($item['color'], '#'); // bỏ dấu '#' vì PHPExcel dùng 6 ký tự hex thuần
            $statusColors[$item['label']] = $hex;
        }

        // Ghi data
        $rowNum = 2;
        $sttNum = 1;
        foreach ($data as $item) {
            $col = 0;
            $statusValue = isset($item['trang_thai']) ? $item['trang_thai'] : '';

            foreach ($finalHeaders as $header) {
                if ($header['key'] === 'stt') {
                    $sheet->setCellValueByColumnAndRow($col, $rowNum, $sttNum);
                    $sheet->getStyleByColumnAndRow($col, $rowNum)->getAlignment()
                        ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                } elseif ($header['key'] === 'trang_thai') {
                    $value = $statusValue;
                    $sheet->setCellValueByColumnAndRow($col, $rowNum, $value);
                    // Chỉ tô màu chữ trạng thái
                    $fontColor = isset($statusColors[$value]) ? $statusColors[$value] : null;
                    if ($fontColor) {
                        $sheet->getStyleByColumnAndRow($col, $rowNum)->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => $fontColor],
                            ],
                            'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER],
                        ]);
                    }
                } else {
                    $value = isset($item[$header['key']]) ? $item[$header['key']] : '';
                    // Format specific columns as text
                    if (in_array($header['key'], ['ma_nhan_vien', 'ma_cham_cong', 'cccd_so', 'so_dien_thoai'])) {
                        $sheet->setCellValueExplicitByColumnAndRow(
                            $col,
                            $rowNum,
                            $value,
                            PHPExcel_Cell_DataType::TYPE_STRING
                        );
                    } else {
                        $sheet->setCellValueByColumnAndRow($col, $rowNum, $value);
                    }
                }
                $col++;
            }
            $sttNum++;
            $rowNum++;
        }

        $dir = FCPATH . 'assets/download/excel/excel_export/nhanvien/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $file_path = $dir . $file_name;

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($file_path);

        return file_exists($file_path) ? $file_path : false;
    }

    /**
     * GET admin/hrm/nhanvien/view_log
     * Lấy toàn bộ lịch sử nhật ký liên quan đến Hồ sơ nhân sự
     * (nhanvien, chứng chỉ, đào tạo, hợp đồng, ...)
     */
    // public function view_log_get()
    // {
    //     $searchValue = commonRequest('searchValue');
    //     $start       = (int)(commonRequest('start')  ?? 0);
    //     $length      = (int)(commonRequest('length') ?? 100);
    //     $filterController = commonRequest('loai'); // optional: filter theo 1 controller cụ thể

    //     // Danh sách controller liên quan đến nhân sự
    //     $controllers = [
    //         'nhanvien',
    //         'hrm_nhan_vien',
    //         'hrm_chung_chi',
    //         'chungchi',
    //         'hrm_nhanvien_daotao',
    //         'daotao',
    //         'bangcap',
    //         'hrm_nhan_vien_bang_cap',
    //         'hrm_hop_dong',
    //         'hopdong',
    //         'hrm_qua_trinh_cong_tac',
    //         'quatrinhcongtac',
    //         'hrm_nhan_vien_bao_hiem',
    //         'baohiem',
    //         'hrm_nhan_vien_khen_thuong',
    //         'khenthuong',
    //         'hrm_nhan_vien_kinh_nghiem',
    //         'kinhnghiem',
    //         'thuong',
    //         'thoiviec',
    //         'hrm_nhan_vien_thoi_viec',
    //     ];

    //     $this->Ql_nhat_ky_model
    //         ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = ql_nhat_ky.ql_nguoi_dung_id', 'left')
    //         ->whereIn('ql_nhat_ky_controller', $filterController ? [$filterController] : $controllers);

    //     if ($searchValue) {
    //         $this->Ql_nhat_ky_model->group_start();
    //         $this->Ql_nhat_ky_model->like('ql_nhat_ky_hanh_dong', $searchValue);
    //         $this->Ql_nhat_ky_model->or_like('ql_nhat_ky_noi_dung', $searchValue);
    //         $this->Ql_nhat_ky_model->or_like('ql_nguoi_dung_ho_ten', $searchValue);
    //         $this->Ql_nhat_ky_model->or_like('ql_nguoi_dung_email', $searchValue);
    //         $this->Ql_nhat_ky_model->group_end();
    //     }

    //     $total = $this->Ql_nhat_ky_model->count();

    //     $log = $this->Ql_nhat_ky_model
    //         ->select('
    //             ql_nhat_ky.ql_nhat_ky_id,
    //             ql_nhat_ky.ql_nhat_ky_hanh_dong,
    //             ql_nhat_ky.ql_nhat_ky_noi_dung,
    //             ql_nhat_ky.ql_nhat_ky_gia_tri_cu,
    //             ql_nhat_ky.ql_nhat_ky_gia_tri_moi,
    //             ql_nhat_ky.ql_nhat_ky_bang_du_lieu,
    //             ql_nhat_ky.ql_nhat_ky_controller,
    //             ql_nhat_ky.ql_nhat_ky_ngay_tao,
    //             ql_nguoi_dung.ql_nguoi_dung_id,
    //             ql_nguoi_dung.ql_nguoi_dung_ho_ten,
    //             ql_nguoi_dung.ql_nguoi_dung_email
    //         ')
    //         ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = ql_nhat_ky.ql_nguoi_dung_id', 'left')
    //         ->whereIn('ql_nhat_ky_controller', $filterController ? [$filterController] : $controllers)
    //         ->orderBy('ql_nhat_ky_ngay_tao', 'desc')
    //         ->get($length, $start);

    //     $hanhDongMap = [
    //         'create' => 'Tạo mới',
    //         'update' => 'Cập nhật',
    //         'delete' => 'Xóa',
    //         'import' => 'Import',
    //         'export' => 'Export',
    //     ];

    //     // Controller → nhãn hiển thị
    //     $controllerLabelMap = [
    //         'nhanvien'                      => 'Hồ sơ nhân viên',
    //         'hrm_nhan_vien'                 => 'Hồ sơ nhân viên',
    //         'hrm_chung_chi'                 => 'Chứng chỉ',
    //         'chungchi'                      => 'Chứng chỉ',
    //         'hrm_nhanvien_daotao'           => 'Đào tạo',
    //         'daotao'                        => 'Đào tạo',
    //         'bangcap'                       => 'Bằng cấp',
    //         'hrm_nhan_vien_bang_cap'        => 'Bằng cấp',
    //         'hrm_hop_dong'                  => 'Hợp đồng',
    //         'hopdong'                       => 'Hợp đồng',
    //         'hrm_qua_trinh_cong_tac'        => 'Quá trình công tác',
    //         'quatrinhcongtac'               => 'Quá trình công tác',
    //         'hrm_nhan_vien_bao_hiem'        => 'Bảo hiểm',
    //         'baohiem'                       => 'Bảo hiểm',
    //         'hrm_nhan_vien_khen_thuong'     => 'Khen thưởng',
    //         'khenthuong'                    => 'Khen thưởng',
    //         'hrm_nhan_vien_kinh_nghiem'     => 'Kinh nghiệm',
    //         'kinhnghiem'                    => 'Kinh nghiệm',
    //         'thuong'                        => 'Thưởng',
    //         'thoiviec'                      => 'Thôi việc',
    //         'hrm_nhan_vien_thoi_viec'       => 'Thôi việc',
    //     ];

    //     // Field labels tổng hợp cho tất cả module nhân sự
    //     $fieldLabels = [
    //         // Nhân viên cơ bản
    //         'ho_va_ten'                 => 'Họ và tên',
    //         'ma_nhan_vien'              => 'Mã nhân viên',
    //         'ma_cham_cong'              => 'Mã chấm công',
    //         'email'                     => 'Email',
    //         'so_dien_thoai'             => 'Số điện thoại',
    //         'gioi_tinh'                 => 'Giới tính',
    //         'ngay_sinh'                 => 'Ngày sinh',
    //         'ngay_vao_lam'              => 'Ngày vào làm',
    //         'ngay_lam_chinh_thuc'       => 'Ngày làm chính thức',
    //         'trang_thai'                => 'Trạng thái',
    //         'id_don_vi_cong_tac'        => 'Đơn vị công tác (ID)',
    //         'id_vi_tri_cong_viec'       => 'Vị trí công việc (ID)',
    //         'ten_don_vi'                => 'Tên đơn vị',
    //         'ten_cong_viec'             => 'Tên công việc / Chức danh',
    //         'chuc_danh'                 => 'Chức danh',
    //         'chuc_vu'                   => 'Chức vụ',
    //         'phong_ban'                 => 'Phòng ban',
    //         'don_vi'                    => 'Đơn vị',
    //         'vi_tri'                    => 'Vị trí',
    //         'dia_chi'                   => 'Địa chỉ',
    //         'que_quan'                  => 'Quê quán',
    //         'dan_toc'                   => 'Dân tộc',
    //         'ton_giao'                  => 'Tôn giáo',
    //         'quoc_tich'                 => 'Quốc tịch',
    //         'noi_sinh'                  => 'Nơi sinh',
    //         'tinh_trang_hon_nhan'       => 'Tình trạng hôn nhân',
    //         'so_con'                    => 'Số con',
    //         'trinh_do_hoc_van'          => 'Trình độ học vấn',
    //         'loai_hop_dong_lam_viec'    => 'Loại hợp đồng làm việc',
    //         'he_so_luong'               => 'Hệ số lương',
    //         'ti_le_huong_luong'         => 'Tỉ lệ hưởng lương (%)',
    //         'ngay_huong_luong'          => 'Ngày hưởng lương',
    //         // CCCD / Hộ chiếu
    //         'cccd_so'                   => 'Số CCCD',
    //         'cccd_ngay_cap'             => 'Ngày cấp CCCD',
    //         'cccd_noi_cap'              => 'Nơi cấp CCCD',
    //         'cccd_ngay_het_han'         => 'Ngày hết hạn CCCD',
    //         'so_ho_chieu'               => 'Số hộ chiếu',
    //         'ngay_cap_ho_chieu'         => 'Ngày cấp hộ chiếu',
    //         'ngay_het_han_ho_chieu'     => 'Ngày hết hạn hộ chiếu',
    //         // Chứng chỉ
    //         'ten_chung_chi'             => 'Tên chứng chỉ',
    //         'so_chung_chi'              => 'Số chứng chỉ',
    //         'ngay_cap_chung_chi'        => 'Ngày cấp chứng chỉ',
    //         'ngay_het_han_chung_chi'    => 'Ngày hết hạn chứng chỉ',
    //         'noi_cap_chung_chi'         => 'Nơi cấp chứng chỉ',
    //         'files'                     => 'Tệp đính kèm',
    //         // Hợp đồng
    //         'so_hop_dong'               => 'Số hợp đồng',
    //         'ten_hop_dong'              => 'Tên hợp đồng',
    //         'loai_hop_dong'             => 'Loại hợp đồng',
    //         'ngay_bat_dau'              => 'Ngày bắt đầu',
    //         'ngay_ket_thuc'             => 'Ngày kết thúc',
    //         'ngay_ky'                   => 'Ngày ký',
    //         'dang_hieu_luc'             => 'Trạng thái hiệu lực',
    //         'muc_luong'                 => 'Mức lương',
    //         'luong_co_ban'              => 'Lương cơ bản',
    //         'muc_luong_bao_hiem'        => 'Mức lương bảo hiểm',
    //         'phu_cap'                   => 'Phụ cấp',
    //         'phu_cap_chuc_vu'           => 'Phụ cấp chức vụ',
    //         'phu_cap_tham_nien'         => 'Phụ cấp thâm niên',
    //         // Đào tạo / Bằng cấp
    //         'ten_bang_cap'              => 'Tên bằng cấp',
    //         'cap_bang'                  => 'Cấp bằng',
    //         'truong_cap_bang'           => 'Trường cấp bằng',
    //         'nam_tot_nghiep'            => 'Năm tốt nghiệp',
    //         'chuyen_nganh'              => 'Chuyên ngành',
    //         'xep_loai'                  => 'Xếp loại',
    //         'ten_khoa_dao_tao'          => 'Tên khoá đào tạo',
    //         'noi_dao_tao'               => 'Nơi đào tạo',
    //         'thoi_gian_bat_dau'         => 'Thời gian bắt đầu',
    //         'thoi_gian_ket_thuc'        => 'Thời gian kết thúc',
    //         'chi_phi'                   => 'Chi phí đào tạo',
    //         // Quá trình công tác
    //         'noi_cong_tac'              => 'Nơi công tác',
    //         'chuc_vu_cong_tac'          => 'Chức vụ công tác',
    //         'ngay_bat_dau_cong_tac'     => 'Từ ngày',
    //         'ngay_ket_thuc_cong_tac'    => 'Đến ngày',
    //         'mo_ta'                     => 'Mô tả',
    //         // Bảo hiểm
    //         'ma_bhxh'                   => 'Mã BHXH',
    //         'ma_bhyt'                   => 'Mã BHYT',
    //         'noi_kham_benh'             => 'Nơi khám bệnh',
    //         'muc_dong_bhxh'             => 'Mức đóng BHXH',
    //         // Khen thưởng / Thưởng
    //         'ten_khen_thuong'           => 'Tên khen thưởng',
    //         'hinh_thuc_khen_thuong'     => 'Hình thức khen thưởng',
    //         'ly_do'                     => 'Lý do',
    //         'so_tien'                   => 'Số tiền',
    //         'ngay_khen_thuong'          => 'Ngày khen thưởng',
    //         // Thôi việc
    //         'ngay_thoi_viec'            => 'Ngày thôi việc',
    //         'ly_do_thoi_viec'           => 'Lý do thôi việc',
    //         'hinh_thuc_thoi_viec'       => 'Hình thức thôi việc',
    //         // Kinh nghiệm
    //         'ten_cong_ty'               => 'Tên công ty',
    //         'vi_tri_cong_viec'          => 'Vị trí công việc',
    //         'thoi_gian_lam_viec'        => 'Thời gian làm việc',
    //         // Meta
    //         'ghi_chu'                   => 'Ghi chú',
    //         'nguoi_tao'                 => 'Người tạo',
    //         'nguoi_sua'                 => 'Người sửa',
    //         'ngay_tao'                  => 'Ngày tạo',
    //         'ngay_sua'                  => 'Ngày sửa',
    //         'deleted_at'                => 'Ngày xóa',
    //     ];

    //     $skipFields = [
    //         'ngay_tao', 'ngay_sua', 'nguoi_tao', 'nguoi_sua',
    //         'deleted_at', 'updated_at', 'created_at',
    //         'avatar', 'password', 'token',
    //         'old_files', 'new_files', // raw backup fields
    //     ];


    //     $data = array_map(function ($item) use ($hanhDongMap, $fieldLabels, $skipFields, $controllerLabelMap) {
    //         $giaTri_cu  = $item['ql_nhat_ky_gia_tri_cu']  ? json_decode($item['ql_nhat_ky_gia_tri_cu'],  true) : null;
    //         $giaTri_moi = $item['ql_nhat_ky_gia_tri_moi'] ? json_decode($item['ql_nhat_ky_gia_tri_moi'], true) : null;

    //         $chi_tiet = [];
    //         if (is_array($giaTri_cu) && is_array($giaTri_moi)) {
    //             foreach ($giaTri_moi as $key => $valMoi) {
    //                 if (in_array($key, $skipFields)) continue;
    //                 $valCu = isset($giaTri_cu[$key]) ? $giaTri_cu[$key] : null;
    //                 if ($valCu !== $valMoi) {
    //                     $label = isset($fieldLabels[$key]) ? $fieldLabels[$key] : $key;
    //                     $chi_tiet[$label] = ['cu' => $valCu, 'moi' => $valMoi];
    //                 }
    //             }
    //         }

    //         $hanhDongRaw   = strtolower(trim($item['ql_nhat_ky_hanh_dong'] ?? ''));
    //         $hanhDongLabel = isset($hanhDongMap[$hanhDongRaw])
    //             ? $hanhDongMap[$hanhDongRaw]
    //             : ucfirst($item['ql_nhat_ky_hanh_dong'] ?? '');

    //         $controller    = $item['ql_nhat_ky_controller'] ?? '';
    //         $moduleLabel   = isset($controllerLabelMap[$controller]) ? $controllerLabelMap[$controller] : $controller;

    //         return [
    //             'id'                    => $item['ql_nhat_ky_id'],
    //             'hanh_dong'             => $hanhDongLabel,
    //             'noi_dung'              => $item['ql_nhat_ky_noi_dung'],
    //             'bang_du_lieu'          => $item['ql_nhat_ky_bang_du_lieu'],
    //             'module'                => $moduleLabel,
    //             'ten_nguoi_thuc_hien'   => $item['ql_nguoi_dung_ho_ten'],
    //             'email_nguoi_thuc_hien' => $item['ql_nguoi_dung_email'],
    //             'thoi_gian'             => $item['ql_nhat_ky_ngay_tao'],
    //             'chi_tiet'              => $chi_tiet,
    //         ];
    //     }, $log);

    //     resSuccess($data, 'Lấy nhật ký hồ sơ nhân sự thành công', REST_Controller::HTTP_OK, true, [
    //         'total' => $total,
    //     ]);
    // }

    public function get_export_columns_get()
    {
        resSuccess($this->getEmployeeExportHeaders());
    }

    private function getEmployeeExportHeaders()
    {
        return [
            ['key' => 'stt', 'label' => 'STT'],
            ['key' => 'ma_nhan_vien', 'label' => 'Mã nhân viên'],
            ['key' => 'ma_cham_cong', 'label' => 'Mã chấm công'],
            ['key' => 'ho_va_ten', 'label' => 'Họ và tên'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'gioi_tinh', 'label' => 'Giới tính'],
            ['key' => 'ngay_sinh', 'label' => 'Ngày sinh'],
            ['key' => 'ten_don_vi', 'label' => 'Phòng ban/Đơn vị'],
            ['key' => 'ten_cong_viec', 'label' => 'Vị trí công việc'],
            ['key' => 'trang_thai', 'label' => 'Trạng thái'],
            ['key' => 'ngay_lam_chinh_thuc', 'label' => 'Ngày bắt đầu'],
            ['key' => 'ngay_lam_chinh_thuc_ket_thuc', 'label' => 'Ngày kết thúc'],
            ['key' => 'ca_lam_viec', 'label' => 'Ca làm việc'],
            ['key' => 'cccd_so', 'label' => 'Số CCCD'],
            ['key' => 'cccd_noi_cap', 'label' => 'Nơi cấp CCCD'],
            ['key' => 'cccd_ngay_cap', 'label' => 'Ngày cấp CCCD'],
            ['key' => 'ten_quoc_gia', 'label' => 'Quốc tịch'],
            ['key' => 'ten_dan_toc', 'label' => 'Dân tộc'],
            ['key' => 'ten_ton_giao', 'label' => 'Tôn giáo'],
        ];
    }
}
