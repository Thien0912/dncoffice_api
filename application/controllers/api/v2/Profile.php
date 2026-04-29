<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';
/**
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Hrm_yeu_cau_cap_nhat_model $Hrm_yeu_cau_cap_nhat_model
 * @property Hrm_chung_chi_model $Hrm_chung_chi_model
 * @property Hrm_bang_cap_model $Hrm_bang_cap_model
 * @property Fileupload $fileupload
 */

class Profile extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model(['Hrm_nhan_vien_model', 'Hrm_yeu_cau_cap_nhat_model', 'Hrm_chung_chi_model', 'Hrm_bang_cap_model']);
        $this->load->library(['Common', 'fileupload']);
    }

    public function index_get()
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

        $auth = $this->getUserLogin();
        $data = $this->Hrm_nhan_vien_model
            ->where('ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])
            ->first();

        $dang_yeu_cau_cap_nhat = $this->Hrm_yeu_cau_cap_nhat_model
            ->where('id_nhan_vien', $data['id_nhan_vien'])
            ->where('trang_thai', 0)
            ->first();

        $data['dang_yeu_cau_cap_nhat'] = !empty($dang_yeu_cau_cap_nhat) ? 1 : 0;
        $data['avatar'] = encryptString($data['avatar']);

        // Lấy danh sách đơn vị kiêm nhiệm
        $don_vi_kiem_nhiem = $this->db
            ->select('id_don_vi_cong_tac, id_vi_tri_cong_viec, la_lanh_dao, ghi_chu')
            ->where('id_nhan_vien', $data['id_nhan_vien'])
            ->get('hrm_nhan_vien_don_vi')
            ->result_array();

        $data['don_vi_kiem_nhiem'] = array_map(function ($item) {
            return [
                'id_don_vi_cong_tac' => (string) $item['id_don_vi_cong_tac'],
                'id_vi_tri_cong_viec' => (string) ($item['id_vi_tri_cong_viec'] ?? ''),
                'la_lanh_dao' => (bool) $item['la_lanh_dao'],
                'ghi_chu' => $item['ghi_chu'] ?? null,
            ];
        }, $don_vi_kiem_nhiem);

        // Lấy danh sách chứng chỉ
        $data['chung_chi'] = $this->Hrm_chung_chi_model->where('id_nhan_vien', $data['id_nhan_vien'])->get();

        // Lấy danh sách bằng cấp
        $data['bang_cap'] = $this->Hrm_bang_cap_model->where('id_nhan_vien', $data['id_nhan_vien'])->get();

        // Lấy danh sách minh chứng hiện có (grouped by loai)
        $this->load->model('Hrm_minh_chung_model');
        $data['minh_chung'] = $this->Hrm_minh_chung_model->getByNhanVien($data['id_nhan_vien']);

        resSuccess($data);
    }

    public function yeucaucapnhat_post()
    {
        $auth = $this->getUserLogin();
        $data = commonRequest('payload');

        $nhanvien = $this->Hrm_nhan_vien_model
            ->where('hrm_nhan_vien.ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])
            ->first();

        if (!$nhanvien) {
            resError("Không tìm thấy nhân viên");
        }

        $result = $this->Hrm_yeu_cau_cap_nhat_model->insert([
            'id_nhan_vien' => $nhanvien['id_nhan_vien'],
            'du_lieu' => json_encode($data),
        ]);

        if ($result) {
            $id_yeu_cau_cap_nhat = $this->db->insert_id();
            resSuccess(['id_yeu_cau_cap_nhat' => $id_yeu_cau_cap_nhat], 'Yêu cầu cập nhật thông tin của bạn đã được gửi.', REST_Controller::HTTP_OK, true);
        } else {
            resError("Đã có lỗi xảy ra khi gửi yêu cầu");
        }
    }

    public function uploadAvatar_post()
    {
        $avatar = commonRequest('avatar') ? commonRequest('avatar') : null;
        $id_yeu_cau_cap_nhat = commonRequest('id_yeu_cau_cap_nhat') ? commonRequest('id_yeu_cau_cap_nhat') : null;

        // dd($avatar);
        $errors = [];
        $folderName = 'employees/avatars';

        if (isset($avatar)) {

            $uploadedFile = $this->fileupload->upload($avatar, $folderName);

            if (!$uploadedFile['success']) {
                $errors = array_merge($errors, ['avatar' => 'Không thể tải lên ảnh đại diện']);
            } else {

                if ($id_yeu_cau_cap_nhat) {

                    $yeu_cau = $this->Hrm_yeu_cau_cap_nhat_model->find($id_yeu_cau_cap_nhat);
                    $du_lieu = json_decode($yeu_cau['du_lieu'], true);
                    $du_lieu['avatar'] = $uploadedFile['file_path'];

                    $result = $this->Hrm_yeu_cau_cap_nhat_model->where('id_yeu_cau_cap_nhat', $id_yeu_cau_cap_nhat)
                        ->update(['du_lieu' => json_encode($du_lieu)]);

                    resSuccess($result, 'Ảnh đại diện đã được tải lên, vui lòng đợi duyệt.', REST_Controller::HTTP_OK, true);
                } else {

                    $auth = $this->getUserLogin();
                    $du_lieu['avatar'] = $uploadedFile['file_path'];

                    $nhanvien = $this->Hrm_nhan_vien_model
                        ->where('ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])
                        ->first();

                    $result = $this->Hrm_yeu_cau_cap_nhat_model->insert([
                        'id_nhan_vien' => $nhanvien['id_nhan_vien'],
                        'du_lieu' => json_encode($du_lieu),
                    ]);

                    resSuccess($result, 'Yêu cầu cập nhật thông tin của bạn đã được gửi.', REST_Controller::HTTP_OK, true);
                }
            }
        }

        $errors = array_merge($errors, ['avatar' => ['Không thể tải lên ảnh đại diện']]);

        resError("Đã có lỗi xảy ra", 500, $errors);
    }

    public function uploadMinhChungYeuCau_post()
    {
        $id_yeu_cau_cap_nhat = commonRequest('id_yeu_cau_cap_nhat');
        $id_loai_minh_chung = commonRequest('id_loai_minh_chung');
        $file = commonRequest('file');
        $details_str = commonRequest('details');

        $details = null;
        if ($details_str) {
            $details = json_decode($details_str, true);
        }

        if (!$id_yeu_cau_cap_nhat || !$id_loai_minh_chung || !$file) {
            resError("Thiếu tham số bắt buộc.", 400);
        }

        $yeu_cau = $this->Hrm_yeu_cau_cap_nhat_model->find($id_yeu_cau_cap_nhat);
        if (!$yeu_cau) {
            resError("Yêu cầu cập nhật không tồn tại.", 404);
        }

        $folderName = 'employees/minh_chung';
        $uploadedFile = $this->fileupload->upload($file, $folderName);

        if (!$uploadedFile['success']) {
            resError("Không thể tải định dạng tệp hoặc dung lượng vượt quá giới hạn.", 500);
        }

        $du_lieu = json_decode($yeu_cau['du_lieu'], true);
        if (!isset($du_lieu['minh_chung'])) {
            $du_lieu['minh_chung'] = [];
        }

        $file_info = [
            'id_loai_minh_chung' => $id_loai_minh_chung,
            'file_path' => $uploadedFile['file_path'],
            'file_name' => $file['name'] ?? null,
            'file_extension' => isset($file['name']) ? pathinfo($file['name'], PATHINFO_EXTENSION) : null,
            'file_size' => $file['size'] ?? 0,
        ];

        if ($details && is_array($details)) {
            $file_info = array_merge($file_info, $details);
        }

        $du_lieu['minh_chung'][] = $file_info;

        $result = $this->Hrm_yeu_cau_cap_nhat_model
            ->where('id_yeu_cau_cap_nhat', $id_yeu_cau_cap_nhat)
            ->update(['du_lieu' => json_encode($du_lieu)]);

        if ($result) {
            resSuccess(null, 'Tải lên thành công.', REST_Controller::HTTP_OK, true);
        } else {
            resError("Lỗi cập nhật dữ liệu.", 500);
        }
    }

    public function registerMinhChungFile_post()
    {
        $id_yeu_cau_cap_nhat = commonRequest('id_yeu_cau_cap_nhat');
        $id_loai_minh_chung = commonRequest('id_loai_minh_chung');
        $file_path = commonRequest('file_path');
        $file_name = commonRequest('file_name');
        $file_size = commonRequest('file_size') ?? 0;
        $file_extension = commonRequest('file_extension');
        $details_str = commonRequest('details');

        if (!$id_yeu_cau_cap_nhat || !$id_loai_minh_chung || !$file_path) {
            resError("Thiếu tham số bắt buộc.", 400);
            return;
        }

        $yeu_cau = $this->Hrm_yeu_cau_cap_nhat_model->find($id_yeu_cau_cap_nhat);
        if (!$yeu_cau) {
            resError("Yêu cầu cập nhật không tồn tại.", 404);
            return;
        }

        $details = null;
        if ($details_str) {
            $details_decoded = json_decode($details_str, true);
            if (is_array($details_decoded)) {
                $details = $details_decoded;
            }
        }

        $du_lieu = json_decode($yeu_cau['du_lieu'], true);
        if (!isset($du_lieu['minh_chung'])) {
            $du_lieu['minh_chung'] = [];
        }

        $file_info = [
            'id_loai_minh_chung' => $id_loai_minh_chung,
            'file_path' => $file_path,
            'file_name' => $file_name ?? null,
            'file_extension' => $file_extension ?? null,
            'file_size' => (int) $file_size,
        ];

        if ($details && is_array($details)) {
            $file_info = array_merge($file_info, $details);
        }

        $du_lieu['minh_chung'][] = $file_info;

        $result = $this->Hrm_yeu_cau_cap_nhat_model
            ->where('id_yeu_cau_cap_nhat', $id_yeu_cau_cap_nhat)
            ->update(['du_lieu' => json_encode($du_lieu)]);

        if ($result) {
            resSuccess(null, 'Đăng ký minh chứng thành công.', REST_Controller::HTTP_OK, true);
        } else {
            resError("Lỗi cập nhật dữ liệu.", 500);
        }
    }

    public function uploadBangCapFile_post()
    {
        $file = commonRequest('file');

        if (!$file) {
            resError("Thiếu file.", 400);
            return;
        }

        $folderName = 'employees/bang_cap';
        $uploadedFile = $this->fileupload->upload($file, $folderName);

        if (!$uploadedFile['success']) {
            resError("Không thể tải lên file. Vui lòng kiểm tra định dạng hoặc dung lượng.", 500);
            return;
        }

        resSuccess([
            'file_path'      => $uploadedFile['file_path'],
            'file_name'      => $file['name'] ?? null,
            'file_extension' => isset($file['name']) ? pathinfo($file['name'], PATHINFO_EXTENSION) : null,
            'file_size'      => $file['size'] ?? 0,
        ], 'Tải lên thành công.', REST_Controller::HTTP_OK, true);
    }

    public function code_get($code)
    {
        $data = $this->Hrm_nhan_vien_model->get_employee_by_code($code);
        if (!$data) {
            resError("Không tìm thấy nhân viên");
        }
        $data['avatar'] = encryptString($data['avatar']);
        resSuccess($data, 'Success');
    }

    /**
     * User requests to delete one or more minh_chung files — creates a single pending
     * yeu_cau_cap_nhat record containing all requested deletions for HR review.
     * POST body: { items: [{ id_minh_chung, file_name?, loai_label? }, ...] }
     */
    public function requestDeleteMinhChung_post()
    {
        $auth = $this->getUserLogin();

        // Support both single item (legacy) and batch array
        $items_raw = commonRequest('items');
        if (empty($items_raw)) {
            // fallback: single item
            $single_id = commonRequest('id_minh_chung');
            if (!$single_id) {
                resError("Thiếu tham số items hoặc id_minh_chung.", 400);
            }
            $items_raw = [[
                'id_minh_chung' => $single_id,
                'file_name'     => commonRequest('file_name'),
                'loai_label'    => commonRequest('loai_label'),
            ]];
        }

        $items = is_string($items_raw) ? json_decode($items_raw, true) : $items_raw;

        if (empty($items) || !is_array($items)) {
            resError("Danh sách file cần xóa không hợp lệ.", 400);
        }

        $nhanvien = $this->Hrm_nhan_vien_model
            ->where('ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])
            ->first();

        if (!$nhanvien) {
            resError("Không tìm thấy nhân viên.");
        }

        $minh_chung_payload = [];

        foreach ($items as $item_raw) {
            // REST_Controller may decode JSON body as stdClass objects
            $item = is_object($item_raw) ? (array) $item_raw : (array) $item_raw;

            $id_minh_chung = (int) ($item['id_minh_chung'] ?? 0);
            if (!$id_minh_chung) continue;

            // Verify ownership and fetch category name
            $mc = $this->db
                ->select('hm.*, hlm.ten_loai')
                ->from('hrm_minh_chung hm')
                ->join('hrm_loai_minh_chung hlm', 'hlm.id_loai_minh_chung = hm.id_loai_minh_chung', 'left')
                ->where('hm.id_minh_chung', $id_minh_chung)
                ->where('hm.id_nhan_vien', $nhanvien['id_nhan_vien'])
                ->where('hm.deleted_at IS NULL', null, false)
                ->get()
                ->row_array();

            if (!$mc) continue; // skip files that don't belong to this employee

            $minh_chung_payload[] = [
                'action'         => 'delete',
                'id_minh_chung'  => $id_minh_chung,
                'file_name'      => ($item['file_name'] ?: null) ?: $mc['file_name'],
                'loai_label'     => $mc['ten_loai'] ?? ($item['loai_label'] ?? null),
                'file_path'      => $mc['file_path'] ?? null,
                'file_extension' => $mc['file_extension'] ?? null,
            ];
        }

        if (empty($minh_chung_payload)) {
            resError("Không có minh chứng hợp lệ để yêu cầu xóa.", 400);
        }

        $du_lieu = ['minh_chung' => $minh_chung_payload];

        $result = $this->Hrm_yeu_cau_cap_nhat_model->insert([
            'id_nhan_vien' => $nhanvien['id_nhan_vien'],
            'du_lieu'      => json_encode($du_lieu),
        ]);

        if ($result) {
            $id_yeu_cau = $this->db->insert_id();
            resSuccess(
                ['id_yeu_cau_cap_nhat' => $id_yeu_cau],
                'Yêu cầu xóa ' . count($minh_chung_payload) . ' minh chứng đã được gửi, vui lòng chờ HR xét duyệt.',
                REST_Controller::HTTP_OK,
                true
            );
        } else {
            resError("Đã có lỗi xảy ra khi gửi yêu cầu.");
        }
    }
}
