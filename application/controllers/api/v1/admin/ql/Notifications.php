<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 * @property Ql_thong_bao_model $Ql_thong_bao_model
 * @property CI_DB_query_builder $db
 * @property Nv_can_bo_model $Nv_can_bo_model
 * @property Sv_sinh_vien_model $Sv_sinh_vien_model
 * @property Ctdt_khoa_hoc_model $Ctdt_khoa_hoc_model
 * @property Sv_lop_model $Sv_lop_model
 * @property E_don_vi_model $E_don_vi_model
 * @property Ctdt_nganh_model $Ctdt_nganh_model
 */
class Notifications extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Ql_nguoi_dung_model');
        $this->load->model('Ql_thong_bao_model');
        // $this->permissionMiddleware();
    }

    public function index_get()
    {
        $this->permissionMiddleware();
        $notifications = $this->Ql_thong_bao_model->getSearch();
        $this->response([
            'status' => REST_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => $notifications
        ], REST_Controller::HTTP_OK);
    }
    public function delete_delete($id)
    {
        $this->permissionMiddleware();
        $this->Ql_thong_bao_model->deletePivotsById($id);
        $this->Ql_thong_bao_model->where('ql_thong_bao_id', $id)->delete();
        $this->response([
            'status' => REST_Controller::HTTP_OK,
            'message' => 'Deleted Successfully',
            'success' => true
        ], REST_Controller::HTTP_OK);
    }

    public function getDonVi_get()
    {
        $this->load->model('E_don_vi_model');
        $data = [
            'e_don_vi' => json_encode($this->E_don_vi_model->get())
        ];
        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => $data
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function get_officer_by_units_get()
    {
        $unitIds = commonRequest('unit_ids');

        if (!$unitIds) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Đơn vị không được rỗng',
                'success' => false,
            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
        $unitIds = json_decode($unitIds, true);

        $this->load->model('E_don_vi_model');
        $this->load->model('Ql_nguoi_dung_model');

        $officers = $this->Ql_nguoi_dung_model
            ->leftJoin('e_don_vi', 'e_don_vi.id_don_vi = ql_nguoi_dung.id_don_vi')
            ->whereIn('e_don_vi.id_don_vi', $unitIds)
            ->where('ql_nguoi_dung.ql_nguoi_dung_loai', self::USER_TYPE['staff'])
            ->select(
                'ql_nguoi_dung.ql_nguoi_dung_id,
                ql_nguoi_dung.ql_nguoi_dung_ho_ten,
                ql_nguoi_dung.ql_nguoi_dung_email,
                ql_nguoi_dung.ql_nguoi_dung_la_lanh_dao,
                e_don_vi.id_don_vi,
                e_don_vi.ten_don_vi'
            )
            ->get();

        $units = [];
        foreach ($officers as $key => $officer) {
            $unitId = $officer['id_don_vi'];

            if (!isset($units[$unitId])) {
                $units[$unitId] = [
                    'id_don_vi' => $officer['id_don_vi'],
                    'ten_don_vi' =>   $officer['ten_don_vi'],
                    'nv_can_bo' => []
                ];
            }


            if ($officer['ql_nguoi_dung_id']) {
                $units[$unitId]['nv_can_bo'][] = [
                    'ql_nguoi_dung_id' => $officer['ql_nguoi_dung_id'],
                    'ql_nguoi_dung_ho_ten' => $officer['ql_nguoi_dung_ho_ten'],
                    'ql_nguoi_dung_email' => $officer['ql_nguoi_dung_email'],
                    'ql_nguoi_dung_la_lanh_dao' => $officer['ql_nguoi_dung_la_lanh_dao'],

                ];
            }
        }
        $uniqueUnitsArray = array_values($units);


        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => $uniqueUnitsArray
        ], REST_INSTANCE_Controller::HTTP_OK);
    }
    public function create_post()
    {
        $this->permissionMiddleware();
        // dd($this->input->post());

        $save = commonRequest('save');
        $save_and_send = commonRequest('save_and_send');

        $ql_thong_bao_loai = commonRequest('ql_thong_bao_loai');
        $ql_thong_bao_cong_khai = commonRequest('ql_thong_bao_cong_khai');
        $ql_thong_bao_ngay_gui = commonRequest('ql_thong_bao_ngay_gui');
        $ql_thong_bao_tieu_de = commonRequest('ql_thong_bao_tieu_de');
        $ql_thong_bao_tieu_de_tieng_anh = commonRequest('ql_thong_bao_tieu_de_tieng_anh');
        $ql_thong_bao_noi_dung = commonRequest('ql_thong_bao_noi_dung');
        $ql_thong_bao_noi_dung_tieng_anh = commonRequest('ql_thong_bao_noi_dung_tieng_anh');
        // $ql_thong_bao_doi_tuong = commonRequest('ql_thong_bao_doi_tuong');

        $ql_thong_bao_don_vi = commonRequest('ql_thong_bao_don_vi') ? json_decode(commonRequest('ql_thong_bao_don_vi')) : null;

        $ql_thong_bao_can_bo_ids = commonRequest('ql_thong_bao_can_bo_ids') ? json_decode(commonRequest('ql_thong_bao_can_bo_ids')) : [];

        //validate

        if (!$ql_thong_bao_loai) {
            $this->response([
                'status' => REST_Controller::HTTP_BAD_REQUEST,
                'message' => 'Loại thông báo không được để trống',
                'success' => false
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        if (!$ql_thong_bao_tieu_de) {
            $this->response([
                'status' => REST_Controller::HTTP_BAD_REQUEST,
                'message' => 'Tiêu đề thông báo không được để trống',
                'success' => false
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        if ($save) {
            $ql_thong_bao_da_gui = 0;

            if (!$ql_thong_bao_ngay_gui) {
                $this->response([
                    'status' => REST_Controller::HTTP_BAD_REQUEST,
                    'message' => 'Ngày gửi không được để trống',
                    'success' => false
                ], REST_Controller::HTTP_BAD_REQUEST);
            }

            $dateSend = new DateTime($ql_thong_bao_ngay_gui);
            $ql_thong_bao_ngay_gui = $dateSend->format('Y-m-d H:i:s');
        } elseif ($save_and_send) {
            $dateSend = new DateTime();
            $ql_thong_bao_ngay_gui = $dateSend->format('Y-m-d H:i:s');
            $ql_thong_bao_da_gui = 1;
        }
        // dd($save_and_send);



        $dataInsertNoti = [
            'ql_thong_bao_tieu_de' => $ql_thong_bao_tieu_de,
            'ql_thong_bao_tieu_de_tieng_anh' => $ql_thong_bao_tieu_de_tieng_anh,
            'ql_thong_bao_noi_dung' => $ql_thong_bao_noi_dung,
            'ql_thong_bao_noi_dung_tieng_anh' => $ql_thong_bao_noi_dung_tieng_anh,
            'ql_thong_bao_ngay_gui' => $ql_thong_bao_ngay_gui,
            'ql_thong_bao_loai' => $ql_thong_bao_loai,
            // 'ql_thong_bao_doi_tuong' => $ql_thong_bao_doi_tuong,
            'ql_thong_bao_da_gui' => $ql_thong_bao_da_gui,
            'ql_thong_bao_tu_dong_gui' => 0,
            'ql_thong_bao_cong_khai' => $ql_thong_bao_cong_khai,
            'created_user_id' => $this->getUserLogin() ? $this->getUserLogin()['ql_nguoi_dung_id'] : null,
            'updated_user_id' => $this->getUserLogin() ? $this->getUserLogin()['ql_nguoi_dung_id'] : null,

        ];

        $dataInsertNoti['ql_thong_bao_ds_don_vi_id'] = $ql_thong_bao_don_vi ? json_encode($ql_thong_bao_don_vi) : null;
        $users = [];
        $this->db->trans_begin();
        $userLoginId = $this->getUserLogin()['ql_nguoi_dung_id'];
        if (!$ql_thong_bao_don_vi) {
            //thông báo cho tất cả các cán bộ
            $users = $this->Ql_nguoi_dung_model
                ->where('ql_nguoi_dung.ql_nguoi_dung_loai', self::USER_TYPE['staff'])
                ->whereNot('ql_nguoi_dung.ql_nguoi_dung_id', $userLoginId)
                ->select('ql_nguoi_dung.ql_nguoi_dung_id')
                ->get();
        } else {

            //thông báo cho cán bộ nằm trong đơn vị đã chọn

            // $users = [];
            if (!empty($ql_thong_bao_can_bo_ids)) {
                $users = $this->Ql_nguoi_dung_model
                    ->whereIn('ql_nguoi_dung.ql_nguoi_dung_id', $ql_thong_bao_can_bo_ids)
                    ->where('ql_nguoi_dung.ql_nguoi_dung_loai', self::USER_TYPE['staff'])
                    ->select('ql_nguoi_dung.ql_nguoi_dung_id')
                    ->get();
            }
        }

        //lưu dữ liệu
        $notificationId = $this->Ql_thong_bao_model->insert($dataInsertNoti);
        $dataInsertNotiUser = [];

        foreach ($users as $user) {
            $dataInsertNotiUser[] = [
                'ql_thong_bao_id' => $notificationId,
                'ql_nguoi_dung_id' => $user['ql_nguoi_dung_id'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'created_user_id' => $userLoginId,
                'updated_user_id' => $userLoginId,
            ];
        }
        if (!empty($dataInsertNotiUser))
            $this->Ql_thong_bao_model->insertThongBaoNguoiDung($dataInsertNotiUser);
        // Commit giao dịch
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->response([
                'status' => REST_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Lỗi hệ thống',
                'success' => false
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        } else {
            $this->db->trans_commit();
        }
        $this->response([
            'status' => REST_Controller::HTTP_OK,
            'message' => 'Thêm thông báo thành công',
            'success' => true
        ], REST_Controller::HTTP_OK);
    }

    public function show_get($id)
    {
        $this->permissionMiddleware();
        $notification = $this->Ql_thong_bao_model->find($id);
        if (!$notification) {
            $this->response([
                'status' => REST_Controller::HTTP_NOT_FOUND,
                'message' => 'Không tìm thấy thông báo',
                'success' => false
            ], REST_Controller::HTTP_NOT_FOUND);
        }

        $this->load->model('E_don_vi_model');

        $donvi = [];
        $canbo = [];

        if ($notification['ql_thong_bao_ds_don_vi_id']) {
            $donviIds = json_decode($notification['ql_thong_bao_ds_don_vi_id'], true);
            $donvi = $this->E_don_vi_model
                ->whereIn('id_don_vi', $donviIds)
                ->get();
            $canbo = $this->Ql_nguoi_dung_model
                ->join('ql_thong_bao_nguoi_dung', 'ql_thong_bao_nguoi_dung.ql_nguoi_dung_id = ql_nguoi_dung.ql_nguoi_dung_id')
                ->where('ql_thong_bao_nguoi_dung.ql_thong_bao_id', $id)
                ->where('ql_nguoi_dung.ql_nguoi_dung_loai', self::USER_TYPE['staff'])
                ->select(
                    'ql_nguoi_dung.ql_nguoi_dung_id, 
                    ql_nguoi_dung.ql_nguoi_dung_ho_ten, 
                    ql_nguoi_dung.ql_nguoi_dung_email, 
                    ql_nguoi_dung.ql_nguoi_dung_la_lanh_dao'
                )
                ->get();
        }
        $notification['ql_thong_bao_don_vi'] = $donvi;
        $notification['ql_thong_bao_can_bo'] = $canbo;


        $this->response([
            'status' => REST_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => $notification
        ], REST_Controller::HTTP_OK);
    }


    public function update_put($id)
    {
        $this->permissionMiddleware();
        $notification = $this->Ql_thong_bao_model->find($id);
        if (!$notification) {
            $this->response([
                'status' => REST_Controller::HTTP_NOT_FOUND,
                'message' => 'Notification not found',
                'success' => false
            ], REST_Controller::HTTP_NOT_FOUND);
        }

        $save = commonRequest('save');
        $save_and_send = commonRequest('save_and_send');

        $ql_thong_bao_loai = commonRequest('ql_thong_bao_loai');
        $ql_thong_bao_cong_khai = commonRequest('ql_thong_bao_cong_khai');
        $ql_thong_bao_ngay_gui = commonRequest('ql_thong_bao_ngay_gui');
        $ql_thong_bao_tieu_de = commonRequest('ql_thong_bao_tieu_de');
        $ql_thong_bao_tieu_de_tieng_anh = commonRequest('ql_thong_bao_tieu_de_tieng_anh');
        $ql_thong_bao_noi_dung = commonRequest('ql_thong_bao_noi_dung');
        $ql_thong_bao_noi_dung_tieng_anh = commonRequest('ql_thong_bao_noi_dung_tieng_anh');
        $ql_thong_bao_don_vi = commonRequest('ql_thong_bao_don_vi') ? json_decode(commonRequest('ql_thong_bao_don_vi')) : null;
        $ql_thong_bao_can_bo_ids = commonRequest('ql_thong_bao_can_bo_ids') ? json_decode(commonRequest('ql_thong_bao_can_bo_ids')) : [];

        //validate

        if (!$ql_thong_bao_loai) {
            $this->response([
                'status' => REST_Controller::HTTP_BAD_REQUEST,
                'message' => 'Loại thông báo không được để trống',
                'success' => false
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        if (!$ql_thong_bao_tieu_de) {
            $this->response([
                'status' => REST_Controller::HTTP_BAD_REQUEST,
                'message' => 'Tiêu đề thông báo không được để trống',
                'success' => false
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        if ($save) {
            $ql_thong_bao_da_gui = 0;

            if (!$ql_thong_bao_ngay_gui) {
                $this->response([
                    'status' => REST_Controller::HTTP_BAD_REQUEST,
                    'message' => 'Ngày gửi không được để trống',
                    'success' => false
                ], REST_Controller::HTTP_BAD_REQUEST);
            }

            $dateSend = new DateTime($ql_thong_bao_ngay_gui);
            // $currentDate = new DateTime();
            // if ($dateSend < $currentDate) {
            //     $this->response([
            //         'status' => REST_Controller::HTTP_BAD_REQUEST,
            //         'message' => 'Thời gian gửi phải lớn hơn hoặc bằng thời gian hiện tại',
            //         'success' => false
            //     ], REST_Controller::HTTP_BAD_REQUEST);
            // }
            $ql_thong_bao_ngay_gui = $dateSend->format('Y-m-d H:i:s');
        } elseif ($save_and_send) {
            $dateSend = new DateTime();
            $ql_thong_bao_ngay_gui = $dateSend->format('Y-m-d H:i:s');
            $ql_thong_bao_da_gui = 1;
        }
        // dd($save_and_send);



        $dataUpdateNoti = [
            'ql_thong_bao_tieu_de' => $ql_thong_bao_tieu_de,
            'ql_thong_bao_tieu_de_tieng_anh' => $ql_thong_bao_tieu_de_tieng_anh,
            'ql_thong_bao_noi_dung' => $ql_thong_bao_noi_dung,
            'ql_thong_bao_noi_dung_tieng_anh' => $ql_thong_bao_noi_dung_tieng_anh,
            'ql_thong_bao_ngay_gui' => $ql_thong_bao_ngay_gui,
            'ql_thong_bao_loai' => $ql_thong_bao_loai,
            'ql_thong_bao_cong_khai' => $ql_thong_bao_cong_khai,
            'ql_thong_bao_da_gui' => $ql_thong_bao_da_gui,
            'ql_thong_bao_tu_dong_gui' => 0,
            'updated_user_id' => $this->getUserLogin() ? $this->getUserLogin()['ql_nguoi_dung_id'] : null,
        ];

        $dataUpdateNoti['ql_thong_bao_ds_don_vi_id'] = $ql_thong_bao_don_vi ? json_encode($ql_thong_bao_don_vi) : null;
        $users = [];
        $this->db->trans_begin();
        $userLoginId = $this->getUserLogin()['ql_nguoi_dung_id'];

        //Xóa bảng ql_thong_bao_nguoi_dung

        $this->Ql_thong_bao_model->deletePivotsById($id);

        if (!$ql_thong_bao_don_vi) {
            //thông báo cho tất cả các cán bộ
            $users = $this->Ql_nguoi_dung_model
                ->where('ql_nguoi_dung.ql_nguoi_dung_loai', self::USER_TYPE['staff'])
                ->whereNot('ql_nguoi_dung.ql_nguoi_dung_id', $userLoginId)
                ->select('ql_nguoi_dung.ql_nguoi_dung_id')
                ->get();
        } else {

            //thông báo cho cán bộ nằm trong đơn vị đã chọn

            // $users = [];
            if (!empty($ql_thong_bao_can_bo_ids)) {
                $users = $this->Ql_nguoi_dung_model
                    ->whereIn('ql_nguoi_dung.ql_nguoi_dung_id', $ql_thong_bao_can_bo_ids)
                    ->where('ql_nguoi_dung.ql_nguoi_dung_loai', self::USER_TYPE['staff'])
                    ->select('ql_nguoi_dung.ql_nguoi_dung_id')
                    ->get();
            }
        }

        //lưu dữ liệu
        $this->Ql_thong_bao_model->where('ql_thong_bao_id', $id)->update($dataUpdateNoti);
        $dataInsertNotiUser = [];

        foreach ($users as $user) {
            $dataInsertNotiUser[] = [
                'ql_thong_bao_id' => $id,
                'ql_nguoi_dung_id' => $user['ql_nguoi_dung_id'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'created_user_id' => $userLoginId,
                'updated_user_id' => $userLoginId,
            ];
        }
        if (!empty($dataInsertNotiUser))
            $this->Ql_thong_bao_model->insertThongBaoNguoiDung($dataInsertNotiUser);
        // Commit giao dịch
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->response([
                'status' => REST_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Lỗi hệ thống',
                'success' => false
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        } else {
            $this->db->trans_commit();
        }
        $this->response([
            'status' => REST_Controller::HTTP_OK,
            'message' => 'Cập nhật thông báo thành công',
            'success' => true
        ], REST_Controller::HTTP_OK);
    }
}
