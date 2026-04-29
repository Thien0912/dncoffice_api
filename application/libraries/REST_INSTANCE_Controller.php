<?php

defined('BASEPATH') or exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

/**
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 * @property Ql_vai_tro_model $Ql_vai_tro_model
 * @property Ql_quyen_model $Ql_quyen_model
 * @property Ql_personal_access_token_model $Ql_personal_access_token_model
 * @property CI_config $config
 * @property CI_URI $uri
 * @property Ql_nhat_ky_model $Ql_nhat_ky_model
 * @property CI_input $input
 * @property E_van_ban_model $E_van_ban_model
 * @property Ql_thong_bao_model $Ql_thong_bao_model
 * @property E_don_vi_model $E_don_vi_model
 * @property E_xu_ly_model $E_xu_ly_model
 * @property E_don_vi_xu_ly_model $E_don_vi_xu_ly_model
 * @property E_file_dinh_kem_model $E_file_dinh_kem_model
 * @property E_loai_model $E_loai_model
 */


class REST_INSTANCE_Controller extends REST_Controller

{
    const USER_TYPE = [
        'student' => 1, //join with sv_sinh_vien
        'staff' => 2 //join with nv_can_bo
    ];
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('n8n'); //Load helper n8n
        $this->load->helper('socket'); // Load socket helper
        $this->load->model('Ql_quyen_model');
        $this->load->model('Ql_nguoi_dung_model');
        $this->load->model([
            'E_van_ban_model',
            'Ql_thong_bao_model',
            'E_don_vi_model',
            'E_xu_ly_model',
            'E_don_vi_xu_ly_model',
            'E_loai_model',
        ]);
    }
    //get permission of role of user, format object
    public function getRolesPermissions($userId)
    {
        $permissions = $this->getPermissions($userId, 'ql_vai_tro.*, ql_quyen.*');
        $roles = [];
        foreach ($permissions as $permission) {
            $roleId = $permission['ql_vai_tro_id'];

            // Nếu vai trò chưa có trong mảng thì thêm vào
            if (!isset($roles[$roleId])) {
                $roles[$roleId] = [
                    'ql_vai_tro_id' => $permission['ql_vai_tro_id'],
                    'ql_vai_tro_ten' => $permission['ql_vai_tro_ten'],
                    'ql_vai_tro_mo_ta' => $permission['ql_vai_tro_mo_ta'],
                    'ql_quyen' => [] // Khởi tạo mảng để lưu quyền của vai trò
                ];
            }

            // Thêm quyền vào vai trò tương ứng
            $roles[$roleId]['ql_quyen'][] = [
                'ql_quyen_id' => $permission['ql_quyen_id'],
                'ql_quyen_ten' => $permission['ql_quyen_ten'],
                'ql_quyen_mo_ta' => $permission['ql_quyen_mo_ta'],
                'ql_quyen_khoa' => $permission['ql_quyen_khoa']
            ];
        }
        $uniqueRolesArray = array_values($roles);
        return $uniqueRolesArray;
    }
    //get permission of user
    public function getPermissions($userId, $select = '')
    {
        return $this->Ql_quyen_model
            ->join('ql_vai_tro_quyen', 'ql_vai_tro_quyen.ql_quyen_id = ql_quyen.ql_quyen_id')
            ->join('ql_vai_tro', 'ql_vai_tro.ql_vai_tro_id = ql_vai_tro_quyen.ql_vai_tro_id')
            ->join('ql_vai_tro_nguoi_dung', 'ql_vai_tro_nguoi_dung.ql_vai_tro_id = ql_vai_tro_quyen.ql_vai_tro_id')
            ->where('ql_vai_tro_nguoi_dung.ql_nguoi_dung_id', $userId)
            // ->where('ql_vai_tro_nguoi_dung.is_active', 1)
            ->groupBy('ql_quyen.ql_quyen_id')
            ->select($select)
            ->get();
    }
    //get permission of user, if userId null then get user login current by jwt header token
    /**
     * @param mixed $userId
     * @return mixed
     */
    public function getPermissionKeys($userId = null)
    {
        if ($userId) {
            $permissions = $this->getPermissions(
                $userId,
                'ql_quyen.ql_quyen_khoa, ql_quyen.ql_quyen_url, ql_quyen.ql_quyen_parent_id,
                ql_quyen.ql_quyen_icon, ql_quyen.ql_quyen_url, ql_quyen.ql_quyen_loai_module, 
                ql_quyen.ql_quyen_thu_tu_hien_thi_chuc_nang, ql_quyen.ql_quyen_ten, ql_quyen.ql_quyen_ten_tieng_anh'
            );
            return $permissions;
        } else {

            $user = $this->getUserLogin();
            if ($user) {
                $permissions = $this->getPermissions(
                    $user['ql_nguoi_dung_id'],
                    'ql_quyen.ql_quyen_khoa, ql_quyen.ql_quyen_url, ql_quyen.ql_quyen_parent_id, 
                            ql_quyen.ql_quyen_icon, ql_quyen.ql_quyen_url, ql_quyen.ql_quyen_loai_module, 
                            ql_quyen.ql_quyen_thu_tu_hien_thi_chuc_nang, ql_quyen.ql_quyen_ten, ql_quyen.ql_quyen_ten_tieng_anh'
                );
                return $permissions;
            }
        }
    }


    public function isLogin(): bool
    {
        $decode_token = $this->decodetoken();
        if (isset($decode_token->ql_nguoi_dung_id)) {
            return $this->Ql_nguoi_dung_model->find($decode_token->ql_nguoi_dung_id) ? true : false;
        }
        return false;
    }

    public function getUserLogin()
    {
        $decode_token = $this->decodetoken();
        if (isset($decode_token->ql_nguoi_dung_id)) {
            $user = $this->findUserInSystem(['ql_nguoi_dung.ql_nguoi_dung_id' => $decode_token->ql_nguoi_dung_id]);
            if (!$user || ($user && $user['active_flag'] != 1)) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_UNAUTHORIZED,
                    'message' => 'Unauthorized',
                    'success' => false
                ], REST_INSTANCE_Controller::HTTP_UNAUTHORIZED);
            }
            $this->load->model('Ql_personal_access_token_model');
            // TODO: check token in personal_access_token table
            if (
                !$this->Ql_personal_access_token_model
                    ->where('token', md5($this->getAuthTokenFromHeader()))
                    ->where('ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])
                    ->first()
            ) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_UNAUTHORIZED,
                    'message' => 'Unauthorized',
                    'success' => false
                ], REST_INSTANCE_Controller::HTTP_UNAUTHORIZED);
            }

            $user['permissions'] = $this->permissionMerge($user['ql_nguoi_dung_id']);
            if ($user['ql_nguoi_dung_is_admin'] == 1) {
                $user['permissions'][] = 'IS_ADMIN';
                $user['permissions'] = array_values(array_unique($user['permissions']));
            }

            return $user;
        }
        return null;
    }

    public function inSegment($seg = [])
    {
        // $controller = $this->uri->segment(5);
        // $method = $this->uri->segment(6) ? '.' . $this->uri->segment(6) : null;
        $controller = strtolower($this->router->fetch_class());
        $method = strtolower($this->router->fetch_method());
        $curentSegment = $controller . '.' . $method;
        return in_array($curentSegment, $seg);
    }


    //access with key map with segment is controller and method
    public function permissionMiddleware()
    {
        if ($this->getUserLogin()) {
            if ($this->getUserLogin()['ql_nguoi_dung_is_admin'] != 1) {
                $controller = strtolower($this->router->fetch_class());
                $method = strtolower($this->router->fetch_method());

                // Tạo danh sách các key cần kiểm tra
                $checkKeys = [$controller . '.' . $method];

                // Nếu method là index, kiểm tra thêm key ngắn gọn (chỉ tên controller)
                if ($method === 'index') {
                    $checkKeys[] = $controller;
                }

                $allPermissions = $this->permissionMerge();

                // Kiểm tra giao nhau: Nếu không có phần tử chung nào -> Forbidden
                if (empty(array_intersect($checkKeys, $allPermissions))) {
                    $this->response([
                        'status' => REST_INSTANCE_Controller::HTTP_FORBIDDEN,
                        'message' => 'Forbidden',
                        'success' => false,
                        'data' => $allPermissions
                    ], REST_INSTANCE_Controller::HTTP_FORBIDDEN);
                }
            }
        }
    }

    public function permissionMerge($userId = null)
    {
        // Lấy quyền từ hệ thống (đảm bảo luôn là mảng)
        $systemPermissions = array_column($this->getPermissionKeys($userId) ?? [], 'ql_quyen_khoa');

        // Lấy quyền từ SSO (đảm bảo luôn là mảng)
        $ssoPermissions = $this->getUserInfo_SSO()['data']['application_permissions'] ?? [];
        $ssoPermissions = (array)$ssoPermissions;
        // loại trùng
        $allPermissions = array_unique(array_map('strtolower', array_merge($systemPermissions, $ssoPermissions)));

        // reset index (0..n-1)
        return array_values($allPermissions);
    }

    // Backup permission check logic (Legacy Version using uri->segment)
    public function permissionMiddleware_old()
    {
        if ($this->getUserLogin()) {
            if ($this->getUserLogin()['ql_nguoi_dung_is_admin'] != 1) {
                $controller = $this->uri->segment(5);
                $method = $this->uri->segment(6) ? '.' . $this->uri->segment(6) : null;
                $key = $controller . $method;
                $permissionKeys =  $this->getPermissionKeys();
                $flag = false;
                foreach ($permissionKeys as $permissionKey) {
                    if (strtolower($permissionKey['ql_quyen_khoa']) == strtolower($key)) {
                        $flag = true;
                        break;
                    }
                }
                if (!$flag) {
                    $this->response([
                        'status' => REST_INSTANCE_Controller::HTTP_FORBIDDEN,
                        'message' => 'Forbidden',
                        'success' => false,
                        'data' => null
                    ], REST_INSTANCE_Controller::HTTP_FORBIDDEN);
                }
            }
        }
    }

    public function createLog($hanh_dong, $noi_dung = '', $gia_tri_cu = [], $gia_tri_moi = [], $bang_du_lieu = '')
    {
        $this->load->model('Ql_nhat_ky_model');
        $controller = $this->uri->segment(5);
        $data = [
            'ql_nguoi_dung_id' => $this->getUserLogin()['ql_nguoi_dung_id'],
            'ql_nhat_ky_hanh_dong' => $hanh_dong,
            'ql_nhat_ky_noi_dung' => $noi_dung,
            'ql_nhat_ky_gia_tri_cu' => json_encode($gia_tri_cu),
            'ql_nhat_ky_gia_tri_moi' => json_encode($gia_tri_moi),
            'ql_nhat_ky_bang_du_lieu' => $bang_du_lieu,
            'ql_nhat_ky_controller' => $controller
        ];
        $this->Ql_nhat_ky_model->insert($data);
    }

    public function logEmployeeHistory($id_nhan_vien, $action, $changes)
    {
        if (empty($changes)) {
            return;
        }
        
        $userId = $this->getUserLogin()['ql_nguoi_dung_id'] ?? null;
        
        $this->db->insert('hrm_nhan_vien_lich_su', [
            'id_nhan_vien' => $id_nhan_vien,
            'action' => $action,
            'changes' => json_encode($changes, JSON_UNESCAPED_UNICODE),
            'created_user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    function decryptRSA($encryptedData, $privateKey)
    {
        openssl_private_decrypt(base64_decode($encryptedData), $decrypted, $privateKey);
        return $decrypted;
    }

    public function createNotification($auth, $idVanBan, $idsDonViNhan = [], $idsNguoiNhan = [], $sendMail = false, $sendZalo = false)
    {
        $vanban = $this->E_van_ban_model->find($idVanBan);
        $file_dinh_kem = $this->E_file_dinh_kem_model->where('id_van_ban', $idVanBan)->get();

        // $file_dinh_kem = !empty($file_dinh_kem) ? array_column($file_dinh_kem, 'duong_dan') : [];

        // Tạm bỏ qua, hiện tại ban hành trực tiếp cho cá nhân ở đơn vị
        $prefix = '';
        $donViNhan = '';
        // if (!empty($idsDonViNhan)) {
        //     if ($vanban['loai_van_ban'] == 1) {
        //         // $prefix = $vanban['so_hieu_van_ban'] . ' ' . $vanban['trich_yeu'];
        //         $prefix = $vanban['trich_yeu'];
        //     } else if ($vanban['loai_van_ban'] == 2) {
        //         $prefix = $vanban['trich_yeu'];
        //     }

        //     $donvithongbaoIds = array_unique($idsDonViNhan);
        //     $tatcadonviIds = array_column($this->E_don_vi_model->all(), 'id_don_vi');

        //     sort($donvithongbaoIds);
        //     sort($tatcadonviIds);

        //     if ($donvithongbaoIds == $tatcadonviIds) {
        //         $donViNhan = 'Tất cả các đơn vị thuộc trường';
        //     } else {
        //         $dsDonViNhan = $this->E_don_vi_model->whereIn('id_don_vi', $donvithongbaoIds)->get();
        //         $tenDonVis = array_column($dsDonViNhan, 'ten_viet_tat');
        //         $tenDonViCuoi = end($tenDonVis);
        //         // $donViNhan = implode(', ', $tenDonVis);
        //         foreach ($tenDonVis as $ten) {
        //             // $donViNhan = $donViNhan . "\n +" . $ten;
        //             $donViNhan = $donViNhan . $ten;
        //             if ($ten != $tenDonViCuoi) {
        //                 $donViNhan = $donViNhan . ', ';
        //             } else if ($ten == $tenDonViCuoi) {
        //                 $donViNhan = $donViNhan . '.';
        //             }
        //         }
        //     }

        //     $noidungthongbao = $prefix .
        //         ".\nLink: " . $this->config->item('frontend_url') . 'vanban/vanbandendonvi/xemchitiet/' . $vanban['id_van_ban'] .
        //         ".\n=> Đơn vị nhận: " . $donViNhan;

        //     $notification = $this->Ql_thong_bao_model->create([
        //         'ql_thong_bao_tieu_de' => $vanban['trich_yeu'],
        //         'ql_thong_bao_tieu_de_tieng_anh' => $vanban['trich_yeu'],
        //         'ql_thong_bao_noi_dung' => $noidungthongbao,
        //         'ql_thong_bao_noi_dung_tieng_anh' => $noidungthongbao,
        //         'ql_thong_bao_ngay_gui' => date('Y-m-d H:i:s'),
        //         'ql_thong_bao_loai' => 1, //1: thông báo, 2: nhắc nhở
        //         'ql_thong_bao_doi_tuong' => 2,
        //         'ql_thong_bao_da_gui' => 1,
        //         'ql_thong_bao_tu_dong_gui' => 1,
        //         'ql_thong_bao_ds_don_vi_id' => !empty($donvithongbaoIds) ? json_encode($donvithongbaoIds) : null,
        //         'ql_thong_bao_cong_khai' => 0, //Nội bộ
        //         'created_user_id' => $auth['ql_nguoi_dung_id'],
        //         'updated_user_id' => $auth['ql_nguoi_dung_id'],
        //         'ql_thong_bao_link' => $this->config->item('frontend_url') . 'vanban/vanbandendonvi/xemchitiet/' . $vanban['id_van_ban']
        //     ]);

        //     $vaiTroNhanVB = [
        //         'VAN_THU_TO_CHUC_HANH_CHINH',
        //         'LANH_DAO_TCHC',
        //         'VAN_THU_DON_VI',
        //         'LANH_DAO_DON_VI'
        //     ];
        //     $users = $this->Ql_nguoi_dung_model
        //         ->leftJoin('ql_vai_tro_nguoi_dung', 'ql_vai_tro_nguoi_dung.ql_nguoi_dung_id = ql_nguoi_dung.ql_nguoi_dung_id')
        //         ->leftJoin('ql_vai_tro', 'ql_vai_tro.ql_vai_tro_id = ql_vai_tro_nguoi_dung.ql_vai_tro_id')
        //         ->whereIn('ql_ma_vai_tro', $vaiTroNhanVB)
        //         ->whereIn('id_don_vi', $donvithongbaoIds)
        //         ->get();
        //     $userIds = array_column($users, 'ql_nguoi_dung_id');

        //     // Tạo thông báo cho các văn thư trong đơn vị
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

        //         $nguoidung = $this->Ql_nguoi_dung_model->find($uId);
        //         $donvi = $this->E_don_vi_model->find($auth['id_don_vi']);
        //         $donvibanhanh = $this->E_don_vi_model->find($vanban['id_don_vi_soan']);
        //         // if ($sendMail) {
        //         //     $this->sendVBDi_post([
        //         //         'ql_nguoi_dung_ho_ten' => $nguoidung['ql_nguoi_dung_ho_ten'],
        //         //         'so_hieu_van_ban'      => $vanban['so_hieu_van_ban'],
        //         //         'ngay_ky'              => date('d/m/Y', strtotime($vanban['ngay_ky'])),
        //         //         'trich_yeu'           => $vanban['trich_yeu'],
        //         //         'ten_don_vi_ban_hanh'  => $donvibanhanh['ten_don_vi'],
        //         //         'duong_dan'            => $this->config->item('frontend_url') . 'vanban/vanbandendonvi/xemchitiet/' . $vanban['id_van_ban'],
        //         //         'email'                => $nguoidung['ql_nguoi_dung_email']
        //         //         'file_dinh_kem'      => $file_dinh_kem,
        //         //     ]);
        //         // }
        //     }
        //     if (!empty($dataInsertNotiUser)) {
        //         $this->Ql_thong_bao_model->insertThongBaoNguoiDung($dataInsertNotiUser);
        //     }

        //     // foreach ($donvithongbaoIds as $idDonVi) {
        //     //     $donViNhanMail = $this->E_don_vi_model->find($idDonVi);
        //     //     if ($sendMail && $donViNhanMail['email']) {
        //     //         $this->sendVBDi_post([
        //     //             'ql_nguoi_dung_ho_ten' => $donViNhanMail['ten_don_vi'],
        //     //             'so_hieu_van_ban'      => $vanban['so_hieu_van_ban'],
        //     //             'ngay_ky'              => date('d/m/Y', strtotime($vanban['ngay_ky'])),
        //     //             'trich_yeu'            => $vanban['trich_yeu'],
        //     //             'ten_don_vi_ban_hanh'  => $donViNhanMail['ten_don_vi'],
        //     //             'duong_dan'            => $this->config->item('frontend_url') . 'vanban/vanbandendonvi/xemchitiet/' . $vanban['id_van_ban'],
        //     //             'email'                => $donViNhanMail['email'],
        //     //             'file_dinh_kem'      => $file_dinh_kem,
        //     //         ]);
        //     //     }
        //     // }
        // }

        if (!empty($idsNguoiNhan)) {
            $donvithongbaoIds = array_unique($idsDonViNhan);
            $tatcadonviIds = array_column($this->E_don_vi_model->all(), 'id_don_vi');

            sort($donvithongbaoIds);
            sort($tatcadonviIds);

            if ($donvithongbaoIds == $tatcadonviIds) {
                $donViNhan = 'Tất cả các đơn vị thuộc trường';
            } else {
                $donViNhan = '';
                $dsDonViNhan = $this->E_don_vi_model->whereIn('id_don_vi', $donvithongbaoIds)->get();
                $tenDonVis = array_column($dsDonViNhan, 'ten_viet_tat');
                $tenDonViCuoi = end($tenDonVis);
                foreach ($tenDonVis as $ten) {
                    $donViNhan = $donViNhan . $ten;
                    if ($ten != $tenDonViCuoi) {
                        $donViNhan = $donViNhan . ', ';
                    } else if ($ten == $tenDonViCuoi) {
                        $donViNhan = $donViNhan . '.';
                    }
                }
            }

            $loaiVB = $this->E_loai_model->find($vanban['id_loai']);
            $soHieuVBTuyChinh = '';
            if ($vanban['loai_van_ban'] == 1) {
                $soHieuVBTuyChinh = $vanban['so_hieu_van_ban'] . ' ';
            } else if ($vanban['loai_van_ban'] == 2) {
                $soHieuVBTuyChinh = '';
            }

            $noidungthongbao = $loaiVB['ten_loai'] . " số " . $soHieuVBTuyChinh . $vanban['trich_yeu'] .
                ".\nLink: " . $this->config->item('frontend_url') . 'vanban/vanbandendonvi/xemchitiet/' . $vanban['id_van_ban'] .
                "\n=> Đơn vị nhận: " . $donViNhan;

            $notification = $this->Ql_thong_bao_model->create([
                'ql_thong_bao_tieu_de' => $loaiVB['ten_loai'] . " số " . $soHieuVBTuyChinh . $vanban['trich_yeu'],
                'ql_thong_bao_tieu_de_tieng_anh' => $loaiVB['ten_loai'] . " số " . $soHieuVBTuyChinh . $vanban['trich_yeu'],
                'ql_thong_bao_noi_dung' => $noidungthongbao,
                'ql_thong_bao_noi_dung_tieng_anh' => $noidungthongbao,
                'ql_thong_bao_ngay_gui' => date('Y-m-d H:i:s'),
                'ql_thong_bao_loai' => 1, //1: thông báo, 2: nhắc nhở
                'ql_thong_bao_doi_tuong' => 3,
                'ql_thong_bao_da_gui' => 1,
                'ql_thong_bao_tu_dong_gui' => 1,
                'ql_thong_bao_ds_don_vi_id' => null,
                'ql_thong_bao_cong_khai' => 1,
                'created_user_id' => $auth['ql_nguoi_dung_id'],
                'updated_user_id' => $auth['ql_nguoi_dung_id'],
                'ql_thong_bao_link' => $this->config->item('frontend_url') . 'vanban/vanbandendonvi/xemchitiet/' . $vanban['id_van_ban'],
                'ql_thong_bao_gui_zalo' => 1,
            ]);

            $idsNguoiNhan = array_unique($idsNguoiNhan);
            $dsNguoiDung = $this->Ql_nguoi_dung_model->whereIn('ql_nguoi_dung_id', $idsNguoiNhan)->get();
            $cc = array_column($dsNguoiDung, 'ql_nguoi_dung_email');
            $bcc = array_column($dsNguoiDung, 'ql_nguoi_dung_email');
            $dataInsertNotiUser = [];
                if ($sendMail) {
                    $donvibanhanh = $this->E_don_vi_model->find($vanban['id_don_vi_soan']);
                    // Map indexed list to associative for fast lookup
                    $nguoidungMap = [];
                    foreach ($dsNguoiDung as $nd) {
                        $nguoidungMap[$nd['ql_nguoi_dung_id']] = $nd;
                    }
                }
            
                foreach ($idsNguoiNhan as $uId) {
                    $dataInsertNotiUser[] = [
                        'ql_thong_bao_id' => $notification['ql_thong_bao_id'],
                        'ql_nguoi_dung_id' => $uId,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'created_user_id' => $auth['ql_nguoi_dung_id'],
                        'updated_user_id' => $auth['ql_nguoi_dung_id'],
                    ];

                    if ($sendMail) {
                        $nguoidung = isset($nguoidungMap[$uId]) ? $nguoidungMap[$uId] : null;
                        if (!$nguoidung) continue;

                        $this->sendVBDi_post([
                            // 'ql_nguoi_dung_ho_ten' => $nguoidung['ql_nguoi_dung_ho_ten'],
                            'so_hieu_van_ban'      => $vanban['so_hieu_van_ban'],
                            'ngay_ky'              => date('d/m/Y', strtotime($vanban['ngay_ky'])),
                            'trich_yeu'            => $loaiVB['ten_loai'] . " số " . $soHieuVBTuyChinh . $vanban['trich_yeu'],
                            // 'ten_don_vi_ban_hanh'  => $donvibanhanh['ten_don_vi'],
                            'duong_dan'            => $this->config->item('frontend_url') . 'vanban/vanbandendonvi/xemchitiet/' . $vanban['id_van_ban'],
                            'email'                => $nguoidung['ql_nguoi_dung_email'],
                            'file_dinh_kem'        => $file_dinh_kem,
                            'cc'                   => $cc,
                            'bcc'                  => $bcc,
                        ]);
                    }
                }

            if (!empty($dataInsertNotiUser)) {
                $this->Ql_thong_bao_model->insertThongBaoNguoiDung($dataInsertNotiUser);

                // DEBUG LOG
                log_message('error', 'DEBUG CREATE_NOTI: User IDs for Socket: ' . json_encode($idsNguoiNhan));
                if (function_exists('send_socket_notify')) {
                     log_message('error', 'DEBUG CREATE_NOTI: send_socket_notify function exists. Sending...');
                } else {
                     log_message('error', 'DEBUG CREATE_NOTI: send_socket_notify function DOES NOT EXIST!');
                }

                // Gửi thông báo Socket Realtime
                $relativeLink = '/vanban/vanbandendonvi/xemchitiet/' . $vanban['id_van_ban'];
                $res = send_socket_notify($idsNguoiNhan, 'notification', [
                    'title' => $notification['ql_thong_bao_tieu_de'], 
                    'message' => $vanban['trich_yeu'],
                    'link' => $relativeLink,
                    'type' => 'info'
                ]);

                // Lấy tổng số thông báo chưa đọc cho tất cả người dùng trong 1 query duy nhất
                $newCounts = $this->Ql_thong_bao_model
                    ->join('ql_thong_bao_nguoi_dung', 'ql_thong_bao_nguoi_dung.ql_thong_bao_id = ql_thong_bao.ql_thong_bao_id')
                    ->whereIn('ql_thong_bao_nguoi_dung.ql_nguoi_dung_id', $idsNguoiNhan)
                    ->where('ql_thong_bao.ql_thong_bao_da_gui', 1)
                    ->where('ql_thong_bao_nguoi_dung.ql_thong_bao_da_doc !=', 1)
                    ->select('ql_thong_bao_nguoi_dung.ql_nguoi_dung_id, count(*) as total')
                    ->groupBy('ql_thong_bao_nguoi_dung.ql_nguoi_dung_id')
                    ->get();

                $countMap = [];
                foreach ((array) $newCounts as $row) {
                    $countMap[$row['ql_nguoi_dung_id']] = $row['total'];
                }

                // Gửi cập nhật số lượng thông báo chưa đọc cho từng user
                foreach ($idsNguoiNhan as $uId) {
                    $newCount = isset($countMap[$uId]) ? $countMap[$uId] : 0;
                    send_socket_unread_count($uId, $newCount);
                }

                log_message('error', 'DEBUG CREATE_NOTI: Socket Result: ' . json_encode($res));
            }
        }

        if ($sendZalo) {
            // send_zalo_message();
        }
    } 

    public function sendVBDi_post($data)
    {
        $message = $this->load->view('email/vanbandi_template.php', $data, true);
        $user_info = $data['email']; // Địa chỉ email người nhận
        $subject = $data['trich_yeu'];
        $file_dinh_kem = $data['file_dinh_kem'];
        $cc = $data['cc'];
        $bcc = $data['bcc'];


        // if (!send_email($user_info, $subject, $message)) {
        //     echo ('Gửi mail ' . $data['ql_nguoi_dung_ho_ten'] . ' thất bại');
        // } else {
        //     echo ('Gửi mail ' . $data['ql_nguoi_dung_ho_ten'] . ' thành công');
        // }
        send_email($user_info, $subject, $message, 'noreply@tchc.nctu.edu.vn', 'Trường Đại Học Nam Cần Thơ - DNC University', $file_dinh_kem, $cc, $bcc);
    }

    public function copyZaloMessage($id_van_ban, $textHinhThuc)
    {
        $vb = $this->E_van_ban_model->find($id_van_ban);

        $textZalo = '';
        $thongBao = $this->Ql_thong_bao_model
            // ->where('ql_thong_bao_ds_don_vi_id IS NOT NULL')
            ->like('ql_thong_bao_link', 'xemchitiet/' . $id_van_ban, 'before')
            ->orderBy('ql_thong_bao_id', 'desc')
            ->first();

        if ($thongBao) {
            $textZalo = "
                    Kính chào Quý Thầy/Cô!
                    Phòng Tổ chức - Hành chính ban hành văn bản trên MyOffice" . $textHinhThuc . ": "
                . $thongBao['ql_thong_bao_noi_dung'] .
                "Kính nhờ các đơn vị kiểm tra MyOffice" . $textHinhThuc . ", nếu đơn vị nào chưa nhận được vui lòng phản hồi lại giúp em ạ.
                    Cảm ơn các đơn vị đã phối hợp!
                ";
        } else {
            $textZalo = "
                    Kính chào Quý Thầy/Cô!
                    Phòng Tổ chức - Hành chính ban hành văn bản trên Myoffice" . $textHinhThuc . ":"
                . "Văn bản số: " . $vb['so_hieu_van_ban'] . ", Trích yếu: " . $vb['trich_yeu'] . ".
                    Kính nhờ các đơn vị kiểm tra MyOffice" . $textHinhThuc . " nếu đơn vị nào chưa nhận được vui lòng phản hồi lại giúp em ạ.
                    Cảm ơn các đơn vị đã phối hợp!
                ";
        }

        return $textZalo;
    }

    public function soHieuVanBan($auth, $loai_van_ban, $id_loai_selected, $current_year)
    {
        $ds_van_ban_phu_hop = $this->E_van_ban_model
            ->select('so_hieu_van_ban, ngay_ky')
            ->where('loai_van_ban', $loai_van_ban)
            ->where('id_loai', $id_loai_selected)
            ->where('YEAR(ngay_ky)', $current_year)
            ->where('id_don_vi', $auth['id_don_vi'])
            ->get();

        $sql = $this->db->last_query();

        $suitableItem = null;
        $sohieuvanban_next = 1;
        if (!empty($ds_van_ban_phu_hop)) {
            $soHieuMax = 1;
            foreach ($ds_van_ban_phu_hop as $item) {
                $so_hieu = explode('/', $item['so_hieu_van_ban'])[0];

                if (intval($so_hieu) >= $soHieuMax) {
                    $soHieuMax = intval($so_hieu);
                    $suitableItem = $item;
                }
            }

            $sohieuvanban_next =  intval($soHieuMax) + 1;
        }

        $selectedDocumentType = $this->E_loai_model->find($id_loai_selected);
        $sohieuvanban_last = (string)$sohieuvanban_next . '/' . $selectedDocumentType['hau_to'];

        // Trường hợp có thêm số năm
        if ($suitableItem && count(explode('/', $suitableItem['so_hieu_van_ban'])) == 3) {
            $sohieuvanban_last = (string)$sohieuvanban_next . '/' . $current_year . '/' . $selectedDocumentType['hau_to'];
        }

        $options = null;
        if ($selectedDocumentType['thuoc_nhom'] == 'CTHDT') {
            $options = [
                'hau_to' => $selectedDocumentType['hau_to'],
                'nguoi_ky' => 'TS-LS Nguyễn Tiến Dũng'
            ];
        }

        return [
            'sohieuvanban_last' => $sohieuvanban_last,
            'options' => $options,
            'sql' => $sql
        ];
    }
    protected function getLogDiff($action, $old, $new, $statusMap = [], $dataMaps = [])
    {
        if (!$old || !$new) return [];
        if (in_array(strtolower($action), ['create', 'import'])) return [];

        $diffs = [];
        $ignore = ['ngay_sua', 'ngay_tao', 'updated_at', 'created_at', 'id_nguoi_sua', 'id_nguoi_tao', 'id_van_ban'];

        $fieldMap = [
            'so_hieu_van_ban' => 'Số hiệu',
            'trich_yeu' => 'Trích yếu',
            'ngay_ban_hanh' => 'Ngày ban hành',
            'thoi_gian_xu_ly' => 'Thời hạn xử lý',
            'nguoi_ky' => 'Người ký',
            'trang_thai' => 'Trạng thái',
            'ngay_nhan' => 'Ngày nhận',
            'ghi_chu' => 'Ghi chú',
            'don_vi_nhan' => 'Đơn vị nhận',
            'noi_nhan' => 'Nơi nhận',
            'don_vi_gui' => 'Đơn vị gửi',
            'id_don_vi_gui' => 'Đơn vị gửi',
            'id_don_vi' => 'Đơn vị', // Alias
            'noi_dung_but_phe' => 'Nội dung bút phê',
            'nguoi_but_phe' => 'Người bút phê',
            'id_loai_van_ban' => 'Loại văn bản',
            'loai_van_ban' => 'Loại văn bản',
            'id_loai' => 'Loại văn bản', // Alias
            'id_linh_vuc' => 'Lĩnh vực',
            'id_so_van_ban' => 'Sổ văn bản',
            'id_don_vi_soan' => 'Đơn vị soạn',
            'id_tinh_chat' => 'Tính chất',
            'id_hinh_thuc' => 'Hình thức nhận',
            'noi_luu_tru' => 'Nơi lưu trữ',
            'id_bao_mat' => 'Độ mật',
            'id_khoi_co_quan' => 'Khối cơ quan',
            'id_co_quan' => 'Cơ quan ban hành',
            'linh_vuc' => 'Lĩnh vực',
            'so_van_ban' => 'Số văn bản',
            'nguoi_soan_vb_di' => 'Người soạn',
            'ngay_ky' => 'Ngày ký',
            'tra_loi_cv_den' => 'Trả lời văn bản đến',
            'ngay_tra_loi_cv_den' => 'Ngày trả lời',
            'noi_dung' => 'Nội dung',
            'id_don_vi_phan_hoi' => 'Đơn vị phản hồi',
            'ngay_bao_cao' => 'Ngày báo cáo',
            'id_nguoi_but_phe' => 'Người bút phê',
            'ngay_but_phe' => 'Ngày bút phê',
        ];

        $dateFields = ['ngay_ban_hanh', 'ngay_nhan', 'han_xu_ly', 'ngay_vao_so', 'ngay_het_han', 'thoi_gian_xu_ly', 'ngay_ky', 'ngay_tra_loi_cv_den'];

        // Configuration for mapping fields to their data source in $dataMaps
        $mapRules = [
            'don_vi_nhan' => 'don_vi',
            'noi_nhan' => 'don_vi',
            'don_vi_gui' => 'don_vi',
            'id_don_vi_gui' => 'don_vi',
            'id_don_vi_soan' => 'don_vi',
            'id_don_vi' => 'don_vi',
            'id_don_vi_phan_hoi' => 'don_vi',
            'nguoi_but_phe' => 'nguoi_dung',
            'id_nguoi_but_phe' => 'nguoi_dung',
            'id_nguoi_sua' => 'nguoi_dung',
            'id_nguoi_tao' => 'nguoi_dung',
            'nguoi_soan_vb_di' => 'nguoi_dung',
            'id_loai_van_ban' => 'loai_van_ban',
            'loai_van_ban' => 'loai_van_ban',
            'id_loai' => 'loai_van_ban',
            'id_linh_vuc' => 'linh_vuc',
            'linh_vuc' => 'linh_vuc',
            'id_so_van_ban' => 'so_van_ban',
            'id_tinh_chat' => 'tinh_chat',
            'id_hinh_thuc' => 'hinh_thuc',
            'id_bao_mat' => 'bao_mat',
            'id_khoi_co_quan' => 'khoi_co_quan',
            'id_co_quan' => 'co_quan'
        ];

        foreach ($new as $key => $newVal) {
            if (in_array($key, $ignore)) continue;

            $oldVal = isset($old[$key]) ? $old[$key] : null;

            // --- Generic Mapping Logic ---
            if (isset($mapRules[$key]) && isset($dataMaps[$mapRules[$key]]) && !empty($dataMaps[$mapRules[$key]])) {
                $map = $dataMaps[$mapRules[$key]];

                // Map New Value
                if (is_array($newVal)) {
                    $newVal = array_map(function ($v) use ($map) {
                        return $map[$v] ?? $v;
                    }, $newVal);
                } elseif ($newVal !== null && isset($map[$newVal])) {
                    $newVal = $map[$newVal];
                }

                // Map Old Value
                if (is_array($oldVal)) {
                    $oldVal = array_map(function ($v) use ($map) {
                        return $map[$v] ?? $v;
                    }, $oldVal);
                } elseif ($oldVal !== null && isset($map[$oldVal])) {
                    $oldVal = $map[$oldVal];
                }
            }
            // ---------------------------

            // Handle arrays (display as comma separated string)
            if (is_array($newVal)) {
                $newValStr = implode(', ', $newVal);
                $oldArray = is_array($oldVal) ? $oldVal : ($oldVal ? [$oldVal] : []);
                $oldValStr = implode(', ', $oldArray);

                if ($newValStr != $oldValStr) {
                    $diffs[] = [
                        'field' => $fieldMap[$key] ?? $key,
                        'old' => $oldValStr ?: 'Trống',
                        'new' => $newValStr ?: 'Trống'
                    ];
                }
                continue;
            }

            if ($oldVal != $newVal) {
                // Format values
                $formattedOld = $oldVal;
                $formattedNew = $newVal;

                if (in_array($key, $dateFields)) {
                    $formattedOld = $oldVal ? date('d/m/Y', strtotime($oldVal)) : 'Trống';
                    $formattedNew = $newVal ? date('d/m/Y', strtotime($newVal)) : 'Trống';
                }

                if ($key == 'trang_thai') {
                    $formattedOld = isset($statusMap[$oldVal]) ? $statusMap[$oldVal] : $oldVal;
                    $formattedNew = isset($statusMap[$newVal]) ? $statusMap[$newVal] : $newVal;
                }

                $diffs[] = [
                    'field' => $fieldMap[$key] ?? $key,
                    'old' => $formattedOld ?: 'Trống',
                    'new' => $formattedNew ?: 'Trống'
                ];
            }
        }

        return $diffs;
    }

    public function getLogViewer($id_van_ban, $statusMap = [])
    {
        if (!$id_van_ban) {
            return [];
        }

        $this->db->select('ql_nhat_ky.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten as nguoi_thuc_hien');
        $this->db->from('ql_nhat_ky');
        $this->db->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = ql_nhat_ky.ql_nguoi_dung_id', 'left');
        $this->db->like('ql_nhat_ky_bang_du_lieu', 'e_van_ban');
        $this->db->group_start();
        $this->db->group_start();
        $this->db->where("JSON_UNQUOTE(JSON_EXTRACT(ql_nhat_ky_gia_tri_moi, '$.id_van_ban')) = '$id_van_ban'", null, false);
        $this->db->or_where("JSON_UNQUOTE(JSON_EXTRACT(ql_nhat_ky_gia_tri_cu, '$.id_van_ban')) = '$id_van_ban'", null, false);
        $this->db->group_end();
        $this->db->group_end();
        $this->db->order_by('ql_nhat_ky_ngay_tao', 'DESC');

        $logs = $this->db->get()->result_array();

        // --- Fetch Data Lookups ---
        $dataMaps = [];

        // 1. Units
        $units = $this->db->select('id_don_vi, ten_don_vi')->get('e_don_vi')->result_array();
        if ($units) {
            $dataMaps['don_vi'] = array_column($units, 'ten_don_vi', 'id_don_vi');
        }

        // 2. Users
        $users = $this->db->select('ql_nguoi_dung_id, ql_nguoi_dung_ho_ten')->get('ql_nguoi_dung')->result_array();
        if ($users) {
            $dataMaps['nguoi_dung'] = array_column($users, 'ql_nguoi_dung_ho_ten', 'ql_nguoi_dung_id');
        }

        // 3. Document Types (Loại văn bản)
        if ($this->db->table_exists('e_loai')) {
            $types = $this->db->select('id_loai, ten_loai')->get('e_loai')->result_array();
            if ($types) {
                $dataMaps['loai_van_ban'] = array_column($types, 'ten_loai', 'id_loai');
            }
        }

        // 4. Fields (Lĩnh vực)
        if ($this->db->table_exists('e_linh_vuc')) {
            $fields = $this->db->select('id_linh_vuc, ten_linh_vuc')->get('e_linh_vuc')->result_array();
            if ($fields) {
                $dataMaps['linh_vuc'] = array_column($fields, 'ten_linh_vuc', 'id_linh_vuc');
            }
        }

        // 5. Books (Sổ văn bản)
        if ($this->db->table_exists('e_so_van_ban')) {
            $books = $this->db->select('id_so_van_ban, ten_so_van_ban')->get('e_so_van_ban')->result_array();
            if ($books) {
                $dataMaps['so_van_ban'] = array_column($books, 'ten_so_van_ban', 'id_so_van_ban');
            }
        }

        // 6. Urgency/Nature (Tính chất/Độ khẩn)
        if ($this->db->table_exists('e_tinh_chat')) {
            $urgency = $this->db->select('id_tinh_chat, ten_tinh_chat')->get('e_tinh_chat')->result_array();
            if ($urgency) {
                $dataMaps['tinh_chat'] = array_column($urgency, 'ten_tinh_chat', 'id_tinh_chat');
            }
        }

        // 7. Delivery Methods (Hình thức nhận)
        if ($this->db->table_exists('e_hinh_thuc')) {
            $methods = $this->db->select('id_hinh_thuc, ten_hinh_thuc')->get('e_hinh_thuc')->result_array();
            if ($methods) {
                $dataMaps['hinh_thuc'] = array_column($methods, 'ten_hinh_thuc', 'id_hinh_thuc');
            }
        }

        // 8. Security Level (Độ mật)
        if ($this->db->table_exists('e_bao_mat')) {
            $security = $this->db->select('id_bao_mat, ten_bao_mat')->get('e_bao_mat')->result_array();
            if ($security) {
                $dataMaps['bao_mat'] = array_column($security, 'ten_bao_mat', 'id_bao_mat');
            }
        }

        // 9. Khoi co quan
        if ($this->db->table_exists('e_khoi_co_quan')) {
            $khoi = $this->db->select('id_khoi_co_quan, ten_khoi_co_quan')->get('e_khoi_co_quan')->result_array();
            if ($khoi) {
                $dataMaps['khoi_co_quan'] = array_column($khoi, 'ten_khoi_co_quan', 'id_khoi_co_quan');
            }
        }

        // 10. Co quan
        if ($this->db->table_exists('e_co_quan')) {
            $coquan = $this->db->select('id_co_quan, ten_co_quan')->get('e_co_quan')->result_array();
            if ($coquan) {
                $dataMaps['co_quan'] = array_column($coquan, 'ten_co_quan', 'id_co_quan');
            }
        }
        // --------------------------

        $formattedLogs = [];
        foreach ($logs as $log) {
            $old = json_decode($log['ql_nhat_ky_gia_tri_cu'], true);
            $new = json_decode($log['ql_nhat_ky_gia_tri_moi'], true);
            $formattedLogs[] = [
                'id' => $log['ql_nhat_ky_id'],
                'nguoi_thuc_hien' => $log['nguoi_thuc_hien'],
                'hanh_dong' => $log['ql_nhat_ky_hanh_dong'],
                'noi_dung' => $log['ql_nhat_ky_noi_dung'],
                'ngay_tao' => $log['ql_nhat_ky_ngay_tao'],
                'diff' => $this->getLogDiff($log['ql_nhat_ky_hanh_dong'], $old, $new, $statusMap, $dataMaps)
            ];
        }

        return $formattedLogs;
    }

    /**
     * Tạo thông báo và gửi cho danh sách người dùng
     * @param array $data Dữ liệu cho bảng ql_thong_bao
     * @param array $userIds Danh sách ID người nhận (ql_nguoi_dung_id)
     * @return mixed Kết quả tạo thông báo
     */
    public function createAndNotify($data, $userIds = [])
    {
        $user = $this->getUserLogin();
        $userId = $user['ql_nguoi_dung_id'] ?? null;

        // Default data
        $defaults = [
            'ql_thong_bao_tieu_de' => '', // Required
            'ql_thong_bao_noi_dung' => '', // Required
            'ql_thong_bao_ngay_gui' => date('Y-m-d H:i:s'),
            'ql_thong_bao_loai' => 1, // 1: Thông báo
            'ql_thong_bao_doi_tuong' => 3, // 3: Cán bộ, quản lý
            'ql_thong_bao_da_gui' => 1,
            'ql_thong_bao_tu_dong_gui' => 1,
            'ql_thong_bao_cong_khai' => 1, // Nội bộ
            'ql_thong_bao_gui_zalo' => 0,
            'ql_thong_bao_trinh_duyet' => 0,
            'ql_thong_bao_phan_he' => 1,
            'created_user_id' => $userId,
            'updated_user_id' => $userId,
        ];

        // Merge user data with defaults
        $insertData = array_merge($defaults, $data);

        // Create notification
        $notification = $this->Ql_thong_bao_model->create($insertData);

        if ($notification && !empty($userIds)) {
            $dataInsertNotiUser = [];
            $now = date('Y-m-d H:i:s');

            // Ensure unique user IDs
            $userIds = array_unique($userIds);

            foreach ($userIds as $uId) {
                $dataInsertNotiUser[] = [
                    'ql_thong_bao_id' => $notification['ql_thong_bao_id'],
                    'ql_nguoi_dung_id' => $uId,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'created_user_id' => $userId,
                    'updated_user_id' => $userId,
                ];
            }

            if (!empty($dataInsertNotiUser)) {
                $this->Ql_thong_bao_model->insertThongBaoNguoiDung($dataInsertNotiUser);

                // Gửi Socket Realtime
                $link = '';
                if(isset($data['ql_thong_bao_link'])) {
                    $link = str_replace($this->config->item('frontend_url'), '', $data['ql_thong_bao_link']);
                }

                send_socket_notify($userIds, 'notification', [
                    'title' => $data['ql_thong_bao_tieu_de'],
                    'message' => $data['ql_thong_bao_noi_dung'],
                    'link' => $link,
                    'type' => 'warning'
                ]);
            }
        }

        return $notification;
    }

    // Lấy dữ liệu danh mục
    public function getCategoryData($table, $fieldName = '', $fieldValue = '', $start = 0, $length = 9999, $orderBy = '') {
        if($table == 'e_don_vi') {
            $data = $this->E_don_vi_model->getAll($start, $length);
            return [
                'data' => $data['data'],
            ];
        }else if($table == 'e_loai') {
            if(commonRequest('theo_phong_ban') && commonRequest('id_don_vi_nguoi_dung')){
                $this->db->from('e_loai');
                $this->db->where('is_disabled', 0);
                $this->db->group_start();
                $this->db->where('id_don_vi', commonRequest('id_don_vi_nguoi_dung'));
                $this->db->or_where('id_don_vi', null);
                $this->db->group_end();

                if($length > 0) {
                    $this->db->limit($length);
                }
                if($orderBy) {
                    $this->db->order_by($orderBy);
                }
                
                $query = $this->db->get();
                $data = $query->result_array();

                return [
                    'data' => $data,
                ];
            }
        }else if($table == 'ql_nguoi_dung'){
            $this->db->select('
                ql_nguoi_dung.active_flag,
                ql_nguoi_dung.do_uu_tien_lanh_dao,
                ql_nguoi_dung.id_don_vi,
                ql_nguoi_dung.ql_nguoi_dung_email,
                ql_nguoi_dung.ql_nguoi_dung_ho_ten,
                ql_nguoi_dung.ql_nguoi_dung_id,
                ql_nguoi_dung.ql_nguoi_dung_la_lanh_dao,
                hrm_nhan_vien.trinh_do_dt
            ');
            $this->db->from('ql_nguoi_dung');
            $this->db->join('hrm_nhan_vien', 'hrm_nhan_vien.ql_nguoi_dung_id = ql_nguoi_dung.ql_nguoi_dung_id', 'left');
            $this->db->join('hrm_nhan_vien_cong_viec', 'hrm_nhan_vien_cong_viec.id_nhan_vien = hrm_nhan_vien.id_nhan_vien', 'left');
            $this->db->join('e_don_vi', 'e_don_vi.id_don_vi = ql_nguoi_dung.id_don_vi', 'left');

            $this->db->where('ql_nguoi_dung.active_flag', 1);

            // Tài khoản khoa/đơn vị (không có hrm_nhan_vien) HOẶC nhân viên đang làm việc
            $this->db->group_start();
            $this->db->where('hrm_nhan_vien.id_nhan_vien IS NULL', null, false);
            $this->db->or_where('hrm_nhan_vien_cong_viec.trang_thai', 'DANG_LAM_VIEC');
            $this->db->group_end();

            if ($fieldName && $fieldValue) {
                $this->db->where("ql_nguoi_dung." . $fieldName, $fieldValue);
            }

            if($length > 0) {
                $this->db->limit($length);
            }
            if($orderBy) {
                $this->db->order_by($orderBy);
            }

            $query = $this->db->get();
            $data = $query->result_array();

            $hocHamHocVi = $this->db
                ->select('*')
                ->from('hrm_hoc_ham_hoc_vi')
                ->get()
                ->result_array();

            return [
                'data' => $data,
                'options' => [
                    'hoc_ham_hoc_vi' => $hocHamHocVi
                ]
            ];
        }

        $this->db->select('*');
        $this->db->from($table);
        if($fieldName && $fieldValue) {
            $this->db->where($fieldName, $fieldValue);
        }
        
        $this->db->limit($length);
        if($orderBy) {
            $this->db->order_by($orderBy);
        }
        $query = $this->db->get();
        $data = $query->result_array();

        return [
            'data' => $data,
        ];
    }
}
