<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property DB_query_builder $db
 * @property Dx_loai_de_xuat_model $Dx_loai_de_xuat_model
 */

class Danhmucdexuat extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model('Dx_loai_de_xuat_model');
        $this->load->model('Dx_loai_de_xuat_don_vi_model');
        $this->load->library('Validator');
    }

    /**
     * Lấy danh sách loại đề xuất (có phân trang, tìm kiếm)
     * GET /api/v2/admin/hrm/danhmucdexuat
     */
    public function index_get()
    {
        try {
            $start       = commonRequest('start')       ? (int) commonRequest('start')   : 0;
            $length      = commonRequest('length')      ? (int) commonRequest('length')  : 10;
            $searchValue = commonRequest('searchValue') ? commonRequest('searchValue')   : null;
            $order       = commonRequest('order')       ? commonRequest('order')         : [];

            $result = $this->Dx_loai_de_xuat_model->getList($start, $length, $searchValue, $order);

            resSuccess($result['data'], 'Lấy danh sách loại đề xuất thành công', REST_Controller::HTTP_OK, true, [
                'recordsTotal'    => $result['recordsTotal'],
                'recordsFiltered' => $result['recordsFiltered'],
            ]);
        } catch (Exception $e) {
            resError('Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Lấy chi tiết một loại đề xuất
     * GET /api/v2/admin/hrm/danhmucdexuat/show/{id}
     */
    public function show_get($id)
    {
        $item = $this->Dx_loai_de_xuat_model->find($id);
        if (!$item) {
            resError('Không tìm thấy loại đề xuất', REST_Controller::HTTP_NOT_FOUND);
        }

        // Gắn danh sách đơn vị trình ký
        $item['quy_trinh_ky'] = $this->Dx_loai_de_xuat_don_vi_model->getByLoaiDeXuat($id);

        resSuccess($item, 'Lấy chi tiết thành công');
    }

    /**
     * Thêm mới loại đề xuất
     * POST /api/v2/admin/hrm/danhmucdexuat/create
     */
    public function create_post()
    {
        $validator = new Validator();

        $ma_loai  = commonRequest('ma_loai')  ? trim(commonRequest('ma_loai'))  : null;
        $ten_loai = commonRequest('ten_loai') ? trim(commonRequest('ten_loai')) : null;
        $mo_ta    = commonRequest('mo_ta')    ? trim(commonRequest('mo_ta'))    : null;
        $chon_don_vi = commonRequest('chon_don_vi') !== null ? (int) commonRequest('chon_don_vi') : 0;

        $data = [
            'ma_loai'     => $ma_loai,
            'ten_loai'    => $ten_loai,
            'mo_ta'       => $mo_ta,
            'chon_don_vi' => $chon_don_vi,
        ];

        $rules = [
            'ma_loai'  => 'required',
            'ten_loai' => 'required',
        ];
        $customMessages = [
            'ma_loai.required'  => 'Vui lòng nhập mã loại đề xuất',
            'ten_loai.required' => 'Vui lòng nhập tên loại đề xuất',
        ];

        $validator->setCustomMessages($customMessages);
        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        // Kiểm tra mã loại đã tồn tại chưa
        if ($this->Dx_loai_de_xuat_model->checkValueExists('ma_loai', $ma_loai)) {
            resBadrequest(['ma_loai' => ['Mã loại đề xuất đã tồn tại']]);
        }

        $auth = $this->getUserLogin();
        $data['created_user_id'] = $auth['ql_nguoi_dung_id'];

        $result = $this->Dx_loai_de_xuat_model->create($data);
        if (!$result) {
            resError('Thêm loại đề xuất thất bại');
        }

        // Lưu quy trình ký (DrawerCommon gửi array dưới dạng JSON string)
        $quy_trinh_ky_raw = commonRequest('quy_trinh_ky') ? commonRequest('quy_trinh_ky') : [];
        $quy_trinh_ky = is_string($quy_trinh_ky_raw) ? json_decode($quy_trinh_ky_raw, true) : $quy_trinh_ky_raw;
        if (!empty($quy_trinh_ky) && is_array($quy_trinh_ky)) {
            $this->Dx_loai_de_xuat_don_vi_model->syncDonVi(
                $result['id_dx_loai_de_xuat'],
                $quy_trinh_ky,
                $auth['ql_nguoi_dung_id']
            );
            $result['quy_trinh_ky'] = $this->Dx_loai_de_xuat_don_vi_model->getByLoaiDeXuat($result['id_dx_loai_de_xuat']);
        } else {
            $result['quy_trinh_ky'] = [];
        }

        $this->createLog('create', 'Thêm loại đề xuất', null, $result, 'dx_loai_de_xuat');
        resSuccess($result, 'Thêm loại đề xuất thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }

    /**
     * Cập nhật loại đề xuất
     * POST /api/v2/admin/hrm/danhmucdexuat/update/{id}
     */
    public function update_post($id)
    {
        $item = $this->Dx_loai_de_xuat_model->find($id);
        if (!$item) {
            resError('Không tìm thấy loại đề xuất', REST_Controller::HTTP_NOT_FOUND);
        }

        $validator = new Validator();

        $ten_loai    = commonRequest('ten_loai')    ? trim(commonRequest('ten_loai')) : null;
        $mo_ta       = commonRequest('mo_ta')       ? trim(commonRequest('mo_ta'))    : null;
        $chon_don_vi = commonRequest('chon_don_vi') !== null ? (int) commonRequest('chon_don_vi') : 0;

        $data = [
            'ten_loai'    => $ten_loai,
            'mo_ta'       => $mo_ta,
            'chon_don_vi' => $chon_don_vi,
        ];

        $rules = [
            'ten_loai' => 'required',
        ];
        $customMessages = [
            'ten_loai.required' => 'Vui lòng nhập tên loại đề xuất',
        ];

        $validator->setCustomMessages($customMessages);
        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $auth = $this->getUserLogin();
        $data['updated_user_id'] = $auth['ql_nguoi_dung_id'];

        $this->Dx_loai_de_xuat_model->where('id_dx_loai_de_xuat', $id)->update($data);

        // Lưu quy trình ký (DrawerCommon gửi array dưới dạng JSON string)
        $quy_trinh_ky_raw = commonRequest('quy_trinh_ky') ? commonRequest('quy_trinh_ky') : [];
        $quy_trinh_ky = is_string($quy_trinh_ky_raw) ? json_decode($quy_trinh_ky_raw, true) : $quy_trinh_ky_raw;
        if (is_array($quy_trinh_ky)) {
            $this->Dx_loai_de_xuat_don_vi_model->syncDonVi($id, $quy_trinh_ky, $auth['ql_nguoi_dung_id']);
        }

        $updated = $this->Dx_loai_de_xuat_model->find($id);
        $updated['quy_trinh_ky'] = $this->Dx_loai_de_xuat_don_vi_model->getByLoaiDeXuat($id);
        $this->createLog('update', 'Cập nhật loại đề xuất', $item, $updated, 'dx_loai_de_xuat');
        resSuccess($updated, 'Cập nhật loại đề xuất thành công');
    }

    /**
     * Xóa loại đề xuất (soft delete)
     * DELETE /api/v2/admin/hrm/danhmucdexuat/{id}
     */
    public function index_delete($id)
    {
        $item = $this->Dx_loai_de_xuat_model->find($id);
        if (!$item) {
            resError('Không tìm thấy loại đề xuất', REST_Controller::HTTP_NOT_FOUND);
        }

        $auth = $this->getUserLogin();
        $this->Dx_loai_de_xuat_model->where('id_dx_loai_de_xuat', $id)->update([
            'deleted_at'      => date('Y-m-d H:i:s'),
            'deleted_user_id' => $auth['ql_nguoi_dung_id'],
        ]);

        $this->createLog('delete', 'Xóa loại đề xuất', $item, null, 'dx_loai_de_xuat');
        resSuccess(null, 'Xóa loại đề xuất thành công');
    }

    /**
     * Lấy lịch sử chỉnh sửa từ bảng ql_nhat_ky
     * GET /api/v2/admin/hrm/danhmucdexuat/view_log
     */
    public function view_log_get()
    {
        $searchValue = commonRequest('searchValue');
        $start = (int)(commonRequest('start') ?? 0);
        $length = (int)(commonRequest('length') ?? 100);

        $this->Ql_nhat_ky_model
            ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = ql_nhat_ky.ql_nguoi_dung_id', 'left')
            ->where('ql_nhat_ky_bang_du_lieu', 'dx_loai_de_xuat');

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
            ->where('ql_nhat_ky_bang_du_lieu', 'dx_loai_de_xuat')
            ->orderBy('ql_nhat_ky_ngay_tao', 'desc')
            ->get($length, $start);

        $hanhDongMap = [
            'create' => 'Tạo mới',
            'update' => 'Cập nhật',
            'delete' => 'Xóa',
        ];

        $fieldLabels = [
            'ma_loai'     => 'Mã loại',
            'ten_loai'    => 'Tên loại',
            'mo_ta'       => 'Mô tả',
            'chon_don_vi' => 'Chọn đơn vị',
        ];

        $skipFields = ['created_at', 'updated_at', 'deleted_at', 'created_user_id', 'updated_user_id', 'deleted_user_id'];

        $data = array_map(function ($item) use ($hanhDongMap, $fieldLabels, $skipFields) {
            $giaTri_cu  = $item['ql_nhat_ky_gia_tri_cu']  ? json_decode($item['ql_nhat_ky_gia_tri_cu'], true)  : null;
            $giaTri_moi = $item['ql_nhat_ky_gia_tri_moi'] ? json_decode($item['ql_nhat_ky_gia_tri_moi'], true) : null;

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

        resSuccess($data, 'Lấy lịch sử lịch sử danh mục thành công', REST_Controller::HTTP_OK, true, [
            'total' => $total,
        ]);
    }
}
