<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 * @property Ql_vai_tro_model $Ql_vai_tro_model
 * @property Ql_quyen_model $Ql_quyen_model
 * @property E_tag_model $E_tag_model
 * @property Ql_personal_access_token_model $Ql_personal_access_token_model
 * @property Googleplus $googleplus
 * @property CI_Config $config
 * @property Ql_thong_bao_model $Ql_thong_bao_model
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Hrm_vi_tri_cong_viec_model $Hrm_vi_tri_cong_viec_model
 */

class Authentication extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Ql_nguoi_dung_model');
        $this->load->model('Ql_vai_tro_model');
        $this->load->model('Ql_quyen_model');
        $this->load->model('Ql_personal_access_token_model');
        $this->load->model('Ql_thong_bao_model');
        $this->load->model('E_tag_model');
        $this->load->model('Hrm_nhan_vien_model');
        $this->load->model('Hrm_vi_tri_cong_viec_model');
        $this->load->model('Sys_login_logs_model');
        $this->load->helper('url');
        $this->load->config('zalo');
        // $this->methods['login_post']['limit'] = 200;
    }

    public function me_post()
    {
        $user = $this->getUserLogin();
        $data = $this->getPermission_byUser($user);
        // dd($user);
        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => $data
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function notifications_public_get()
    {
        $notificationStudents = $this->Ql_thong_bao_model
            ->where('ql_thong_bao_cong_khai', 1)
            ->where('ql_thong_bao_da_gui', 1)
            ->where('ql_thong_bao_doi_tuong', self::USER_TYPE['student'])
            ->orderBy('ql_thong_bao_ngay_gui', 'desc')
            ->get(10);
        $notificationStaffs = $this->Ql_thong_bao_model
            ->where('ql_thong_bao_cong_khai', 1)
            ->where('ql_thong_bao_da_gui', 1)
            ->where('ql_thong_bao_doi_tuong', self::USER_TYPE['staff'])
            ->orderBy('ql_thong_bao_ngay_gui', 'desc')
            ->get(10);
        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => [
                'notification_students' => $notificationStudents,
                'notification_staffs' => $notificationStaffs

            ]
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function notification_public_show_get($id)
    {
        $notification = $this->Ql_thong_bao_model
            ->where('ql_thong_bao_cong_khai', 1)
            ->where('ql_thong_bao_da_gui', 1)
            ->where('ql_thong_bao_id', $id)->first();
        if (!$notification) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                'message' => 'Notification not found.',
                'success' => false,
                'data' => null
            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }
        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => $notification
        ]);
    }
    //login method post
    public function login_post()
    {
        $email = commonRequest('ql_nguoi_dung_email');
        $encryptedPassword = commonRequest('ql_nguoi_dung_mat_khau');
        $privateKey = file_get_contents(APPPATH . '../private_key.pem');
        $password = $this->decryptRSA($encryptedPassword, $privateKey);

        if (!empty($email) && !empty($password)) {

            $user = $this->Ql_nguoi_dung_model
                ->join('e_don_vi', 'e_don_vi.id_don_vi = ql_nguoi_dung.id_don_vi', 'left')
                ->where('ql_nguoi_dung_email', $email)->first();
            if (!$user || ($user && $user['active_flag'] != 1)) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                    'message' => 'Wrong email or password.',
                    'success' => false,
                    'data' => null
                ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
            }
            $verifyPassword = $this->Ql_nguoi_dung_model->verifyPassword($user['ql_nguoi_dung_id'], $password);
            // dd($verifyPassword);
            if ($user && $verifyPassword) {

                $user['start_time'] = microtime(true);

                $token = $this->token($user); //create token jwt

                //Create personal access
                $this->saveToken($token, (array)$user);

                if ($user['ql_nguoi_dung_is_admin'] == 1) {
                    $permission = $this->Ql_quyen_model->select('ql_quyen_khoa, ql_quyen_url, ql_quyen_parent_id,
                ql_quyen_icon, ql_quyen_url, ql_quyen_loai_module, 
                ql_quyen_thu_tu_hien_thi_chuc_nang, ql_quyen_ten, ql_quyen_ten_tieng_anh')->get();
                } else {
                    $permission = $this->getPermissionKeys($user['ql_nguoi_dung_id']);
                }
                $permissionKeys = array_map(function ($item) {
                    return $item['ql_quyen_khoa'];
                }, $permission);

                // dd($permission);
                $permissionAccessUrls = [];
                foreach ($permission as $p) {
                    if ($p['ql_quyen_url'] && !$p['ql_quyen_parent_id']) {
                        // $permissionAccessUrls[] = $p['ql_quyen_url'];
                        $permissionAccessUrls[] = [
                            'ql_quyen_icon' => $p['ql_quyen_icon'],
                            'ql_quyen_url' => $p['ql_quyen_url'],
                            'ql_quyen_loai_module' => $p['ql_quyen_loai_module'],
                            'ql_quyen_thu_tu_hien_thi_chuc_nang' => $p['ql_quyen_thu_tu_hien_thi_chuc_nang'],
                            'ql_quyen_ten' => $p['ql_quyen_ten'],
                            'ql_quyen_ten_tieng_anh' => $p['ql_quyen_ten_tieng_anh']
                        ];
                    }
                }
                $publicKey = file_get_contents(APPPATH . '../public_key.pem');

                $tags = $this->E_tag_model->get_user_tags($user['ql_nguoi_dung_id']) ?? [];

                $this->recordLogin($user['ql_nguoi_dung_id']);

                $role = $this->db
                    ->select("
                            CASE 
                                WHEN nd.ql_nguoi_dung_is_admin = 1 
                                    THEN 'IS_ADMIN'
                                ELSE vt.ql_ma_vai_tro
                            END AS ql_ma_vai_tro
                        ")
                    ->from('ql_nguoi_dung nd')
                    ->join('ql_vai_tro_nguoi_dung vtn', 'nd.ql_nguoi_dung_id = vtn.ql_nguoi_dung_id', 'left')
                    ->join('ql_vai_tro vt', 'vtn.ql_vai_tro_id = vt.ql_vai_tro_id', 'left')
                    ->where('nd.ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])
                    ->get()
                    ->result_array();


                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_OK,
                    'message' => 'Logged successfully',
                    'success' => true,
                    'data' => [
                        'user' => $user,
                        'token' => $token,
                        'permission_keys' => $permissionKeys,
                        'access_urls' => $permissionAccessUrls,
                        'public_key' => $publicKey,
                        'tags' => $tags,
                        'role_code' => array_values(array_column($role, 'ql_ma_vai_tro'))
                    ]
                ], REST_INSTANCE_Controller::HTTP_OK);
            } else {
                // Set the response and exit
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => 'Wrong email or password.',
                    'success' => false,
                    'data' => null
                ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else {
            // Set the response and exit
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Wrong email or password.',
                'success' => false,
                'data' => null
            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function profile_get()
    {
        $decode_token = $this->decodetoken(); //decode token from header
        if ($decode_token) {
            $userId = $decode_token->ql_nguoi_dung_id;
            $information = $this->Ql_nguoi_dung_model->find($userId); //information and user
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Success',
                'success' => true,
                'data' => $information
            ], REST_INSTANCE_Controller::HTTP_OK);
        }
    }

    public function login_google_get()
    {
        $this->load->library('googleplus');

        if (!commonRequest('code')) {
            resError('Không có code');
        }

        if (commonRequest('code')) {
            $this->googleplus->getAuthenticate();

            $gpInfo = $this->googleplus->getUserInfo();
            $email = $gpInfo['email'];
            $user = $this->Ql_nguoi_dung_model
                ->select('
                    ql_nguoi_dung.ql_nguoi_dung_id,
                    ql_nguoi_dung.ql_nguoi_dung_ho_ten,
                    ql_nguoi_dung.ql_nguoi_dung_email,
                    ql_nguoi_dung.ql_nguoi_dung_avatar,
                    ql_nguoi_dung.ql_nguoi_dung_loai,
                    ql_nguoi_dung.ql_nguoi_dung_is_admin,
                    ql_nguoi_dung.ql_nguoi_dung_la_lanh_dao,
                    ql_nguoi_dung.do_uu_tien_lanh_dao,
                    ql_nguoi_dung.id_don_vi,
                    ql_nguoi_dung.active_flag,
                    e_don_vi.ten_don_vi,
                    e_don_vi.ma_don_vi,
                ')
                ->join('e_don_vi', 'e_don_vi.id_don_vi = ql_nguoi_dung.id_don_vi', 'left')
                ->where('ql_nguoi_dung_email', $email)->first();
            if (!$user || ($user && $user['active_flag'] != 1)) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                    'message' => 'Your account does not exist in the system',
                    'success' => false,
                    'data' => null
                ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
            }


            $this->recordLogin($user['ql_nguoi_dung_id']);
            $data = $this->getPermission_byUser($user);
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Logged successfully',
                'success' => true,
                'data' => $data
            ], REST_INSTANCE_Controller::HTTP_OK);
        }
    }

    public function revoke_token_google_post()
    {
        $this->load->library('googleplus');
        $this->googleplus->revokeToken();
        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function reset_password_post()
    {
        $encryptedOldPassword = commonRequest('old_password');
        $encryptedNewPassword = commonRequest('new_password');
        $encryptedConfirmPassword = commonRequest('confirm_password');


        $privateKey = file_get_contents(APPPATH . '../private_key.pem');


        $old_password = $this->decryptRSA($encryptedOldPassword, $privateKey);
        $new_password = $this->decryptRSA($encryptedNewPassword, $privateKey);
        $confirm_password = $this->decryptRSA($encryptedConfirmPassword, $privateKey);


        if (!$new_password) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'The new password cannot be null',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }
        if (!$confirm_password) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'The confirm password cannot be null',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        $this->Ql_nguoi_dung_model->resetPassword($this->getUserLogin()['ql_nguoi_dung_id'], $old_password, $new_password, $confirm_password);
        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Updated password successfully',
            'success' => true
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function logout_get()
    {
        $tokenFromHeader = $this->getAuthTokenFromHeader();
        $user = $this->getUserLogin();
        $this->Ql_personal_access_token_model
            ->where('token', md5($tokenFromHeader))
            ->where('ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])
            ->delete();
        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Logged out successfully',
            'success' => true
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function checkEmail_post()
    {
        $email = commonRequest('email') ?? '';
        $user = $this->Ql_nguoi_dung_model->where('ql_nguoi_dung_email', $email)->where('active_flag', 1)->first();
        if (!$user) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Email không tồn tại',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }
        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Email found',
            'success' => true
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function checkAccountZalo_post()
    {
        $zaloUid  = commonRequest('ql_nguoi_dung_zalo_uid') ?? '';
        $user = $this->Ql_nguoi_dung_model
            ->join('e_don_vi', 'e_don_vi.id_don_vi = ql_nguoi_dung.id_don_vi', 'left')
            ->where('ql_nguoi_dung_zalo_uid', $zaloUid)
            ->where('active_flag', 1)
            ->first();

        if (!$user) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Tài khoản Zalo chưa được liên kết với tài khoản trong hệ thống.',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        $this->recordLogin($user['ql_nguoi_dung_id']);
        $data = $this->getPermission_byUser($user);

        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Đăng nhập bằng Zalo thành công!',
            'success' => true,
            'data' => $data
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function link_zalo_post()
    {
        $zaloUid = commonRequest('ql_nguoi_dung_zalo_uid') ?? '';
        $email = commonRequest('ql_nguoi_dung_email') ?? '';

        if (!$zaloUid) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Zalo UID không được để trống',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }
        if (!$email) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Email không được để trống',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        $user = $this->Ql_nguoi_dung_model->where('ql_nguoi_dung_email', $email)->where('active_flag', 1)->first();
        if (!$user) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Tài khoản không tồn tại',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }
        if ($user['ql_nguoi_dung_zalo_uid']) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Tài khoản đã liên kết với Zalo',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }
        $this->Ql_nguoi_dung_model->where('ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])->update([
            'ql_nguoi_dung_zalo_uid' => $zaloUid
        ]);
        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Liên kết Zalo thành công',
            'success' => true
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    private function getPermission_byUser($user)
    {
        $user['start_time'] = microtime(true);
        $token = $this->getAuthTokenFromHeader();
        if (!$token) {
            $token = $this->token($user);
        }
        $this->saveToken($token, (array)$user);

        if ($user['ql_nguoi_dung_is_admin'] == 1) {
            $permission = $this->Ql_quyen_model->select('
                                                        ql_quyen_khoa, ql_quyen_url, 
                                                        ql_quyen_parent_id,
                                                        ql_quyen_icon, ql_quyen_url, 
                                                        ql_quyen_loai_module, 
                                                        ql_quyen_thu_tu_hien_thi_chuc_nang, 
                                                        ql_quyen_ten, ql_quyen_ten_tieng_anh')
                ->get();
        } else {
            $permission = $this->getPermissionKeys($user['ql_nguoi_dung_id']);
        }

        $permissionKeys = array_map(function ($item) {
            return $item['ql_quyen_khoa'];
        }, $permission);

        $permissionAccessUrls = [];
        foreach ($permission as $p) {
            if ($p['ql_quyen_url'] && !$p['ql_quyen_parent_id']) {
                // $permissionAccessUrls[] = $p['ql_quyen_url'];
                $permissionAccessUrls[] = [
                    'ql_quyen_icon' => $p['ql_quyen_icon'],
                    'ql_quyen_url' => $p['ql_quyen_url'],
                    'ql_quyen_loai_module' => $p['ql_quyen_loai_module'],
                    'ql_quyen_thu_tu_hien_thi_chuc_nang' => $p['ql_quyen_thu_tu_hien_thi_chuc_nang'],
                    'ql_quyen_ten' => $p['ql_quyen_ten'],
                    'ql_quyen_ten_tieng_anh' => $p['ql_quyen_ten_tieng_anh']
                ];
            }
        }
        $publicKey = file_get_contents(APPPATH . '../public_key.pem');
        $tags = $this->E_tag_model->get_user_tags($user['ql_nguoi_dung_id']) ?? [];

        $nhanvien = $this->Hrm_nhan_vien_model->where('ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])->first();
        if ($nhanvien) {
            $vitricongviec = $this->Hrm_vi_tri_cong_viec_model->find($nhanvien['id_vi_tri_cong_viec']);
            $user['ten_cong_viec'] = $vitricongviec ? $vitricongviec['ten_cong_viec'] : 'Đang cập nhật';
        }

        $role = $this->db
            ->select("
                    CASE 
                        WHEN nd.ql_nguoi_dung_is_admin = 1 
                            THEN 'IS_ADMIN'
                        ELSE vt.ql_ma_vai_tro
                    END AS ql_ma_vai_tro
                ")
            ->from('ql_nguoi_dung nd')
            ->join('ql_vai_tro_nguoi_dung vtn', 'nd.ql_nguoi_dung_id = vtn.ql_nguoi_dung_id', 'left')
            ->join('ql_vai_tro vt', 'vtn.ql_vai_tro_id = vt.ql_vai_tro_id', 'left')
            ->where('nd.ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])
            ->get()
            ->result_array();


        return [
            'user' => $user,
            'token' => $token,
            'permission_keys' => $permissionKeys,
            'access_urls' => $permissionAccessUrls,
            'public_key' => $publicKey,
            'tags' => $tags,
            'role_code' => array_values(array_column($role, 'ql_ma_vai_tro'))
        ];
    }

    // Hủy liên kết Zalo
    public function unlink_zalo_post()
    {
        $zaloUid = commonRequest('ql_nguoi_dung_zalo_uid') ?? '';
        if (!$zaloUid) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Zalo UID không được để trống',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }
        $user = $this->Ql_nguoi_dung_model->where('ql_nguoi_dung_zalo_uid', $zaloUid)->where('active_flag', 1)->first();
        if (!$user) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Tài khoản không tồn tại',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }
        if (!$user['ql_nguoi_dung_zalo_uid']) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Tài khoản chưa liên kết với Zalo',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }
        $this->Ql_nguoi_dung_model->where('ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])->update([
            'ql_nguoi_dung_zalo_uid' => null
        ]);
        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Hủy liên kết Zalo thành công',
            'success' => true
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    // Ver React
    public function getGoogleClientID_get()
    {
        $this->load->config('googleplus');
        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Lấy Client ID Google thành công',
            'success' => true,
            'data' => [
                'client_id' => $this->config->item('googleplus')['client_id']
            ]
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function loginGoogle_post()
    {
        $token = commonRequest('token') ?? '';
        if (!$token) {
            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Token không được để trống',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }
        // Verify token với Google
        $verifyUrl = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $token;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $verifyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $userInfo = json_decode($response, true);

        if (!isset($userInfo['email'])) {
            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_UNAUTHORIZED,
                'message' => 'Token không hợp lệ',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_UNAUTHORIZED);
        }

        // 👉 user info từ Google
        $email   = $userInfo['email'];
        $name    = $userInfo['name'] ?? '';
        $picture = $userInfo['picture'] ?? '';

        // TODO: kiểm tra user có tồn tại trong DB chưa
        $user = $this->Ql_nguoi_dung_model->join('e_don_vi', 'e_don_vi.id_don_vi = ql_nguoi_dung.id_don_vi', 'left')->where('ql_nguoi_dung_email', $email)->first();
        if (!$user || ($user && $user['active_flag'] != 1)) {
            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_UNAUTHORIZED,
                'message' => 'Tài khoản không tồn tại hoặc đã bị khóa',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_UNAUTHORIZED);
        }

        //Kiểm tra user có avatar chưa, nếu chưa thì cập nhật avatar
        if (!$user['ql_nguoi_dung_avatar'] && $picture) {
            $this->Ql_nguoi_dung_model->where('ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])->update([
                'ql_nguoi_dung_avatar' => $picture
            ]);
        }

        // Ví dụ trả về luôn
        $this->recordLogin($user['ql_nguoi_dung_id']);
        return $this->response([
            'status'  => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Đăng nhập Google thành công',
            'success' => true,
            'data'    => $this->getPermission_byUser($user)
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    // public function loginZalo()
    // {
    //     $this->load->config('zalo');
    //     $zalo_app_id        = $this->config->item('zalo')['zalo_app_id'];
    //     $zalo_app_secret    = $this->config->item('zalo')['zalo_app_secret'];
    //     $zalo_redirect_uri  = $this->config->item('zalo')['zalo_redirect_uri'];
    //     $state              = uniqid();

    //     // PKCE
    //     $codeVerifier = bin2hex(random_bytes(32));
    //     // $this->session->set_userdata('zalo_code_verifier', $codeVerifier);
    //     $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

    //     // Scope hợp lệ
    //     $scope = "profile,user_avatar";

    //     $url = "https://oauth.zaloapp.com/v4/permission?app_id={$zalo_app_id}"
    //         . "&redirect_uri=" . urlencode($zalo_redirect_uri)
    //         . "&state={$state}"
    //         . "&code_challenge={$codeChallenge}"
    //         . "&scope={$scope}";

    //     // redirect($url);
    //     $this->response([
    //         'status' => REST_INSTANCE_Controller::HTTP_OK,
    //         'message' => 'Yêu cầu đăng nhập Zalo thành công',
    //         'success' => true,
    //         'data' => [
    //             'url' => $url
    //         ]
    //     ], REST_INSTANCE_Controller::HTTP_OK);
    // }

    // public function loginZaloCallback_post()
    // {
    //     $code = commonRequest('code') ?? '';
    //     if (!$code) {
    //         return $this->response([
    //             'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
    //             'message' => 'Code không được để trống',
    //             'success' => false
    //         ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
    //     }
    //     echo $code;
    // }

    // Được tạo ra để test callback React
    public function loginZaloCallbackReact_get()
    {
        $code = commonRequest('code') ?? '';
        if (!$code) {
            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Code không được để trống!!!!',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }
        echo $code;
    }

    // Custom Zalo Login
    public function loginZalo_get()
    {
        $zalo_app_id        = $this->config->item('zalo_app_id');
        $zalo_redirect_uri  = $this->config->item('zalo_redirect_uri');
        $state              = uniqid();

        // PKCE
        $codeVerifier = bin2hex(random_bytes(32));
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        // Scope hợp lệ
        $scope = "profile,user_avatar";

        $url = "https://oauth.zaloapp.com/v4/permission?app_id={$zalo_app_id}"
            . "&redirect_uri=" . urlencode($zalo_redirect_uri)
            . "&state={$state}"
            . "&code_challenge={$codeChallenge}"
            . "&scope={$scope}";

        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Yêu cầu đăng nhập Zalo thành công',
            'success' => true,
            'data' => [
                'url' => $url,
                'code_verifier' => $codeVerifier,
                'state' => $state,
            ]
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function loginZaloCallbackReact_post()
    {
        $code = commonRequest('code') ?? '';
        $codeVerifier = commonRequest('code_verifier') ?? '';
        $email = commonRequest('email') ?? '';

        if (!$code || !$codeVerifier) {
            return $this->response(['status' => 400, 'message' => 'Missing code or verifier'], 400);
        }

        $appId    = $this->config->item('zalo_app_id');
        $redirect = $this->config->item('zalo_redirect_uri');
        $appSecret = $this->config->item('zalo_app_secret');

        // Đổi code lấy token
        $url = "https://oauth.zaloapp.com/v4/access_token";
        $post = [
            'app_id'        => $appId,
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'code_verifier' => $codeVerifier,
            'redirect_uri'  => $redirect,
        ];


        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'secret_key: ' . $appSecret, // ✅ ĐÚNG theo tài liệu Zalo
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $res = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($res, true);

        if (isset($data['access_token'])) {
            $accessToken = trim($data['access_token']);

            // Lấy thông tin user
            $url = "https://graph.zalo.me/v2.0/me?fields=id,name,picture";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "access_token: {$accessToken}"
            ]);
            $response = curl_exec($ch);
            curl_close($ch);
            $userInfo = json_decode($response, true);

            if (!isset($userInfo['id'])) {
                return $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                    'message' => 'Lỗi khi lấy thông tin người dùng từ Zalo!',
                    'success' => false
                ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
            }

            $zaloUid = $userInfo['id'];
            // $user = $this->Ql_nguoi_dung_model->where('ql_nguoi_dung_email', $email)->where('active_flag', 1)->first();
            // if (!$user) {
            //     $this->response([
            //         'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
            //         'message' => 'Tài khoản không tồn tại',
            //         'success' => false
            //     ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
            // }
            // if ($user['ql_nguoi_dung_zalo_uid'] && ($user['ql_nguoi_dung_zalo_uid'] == $zaloUid)) {
            //     $this->response([
            //         'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
            //         'message' => 'Tài khoản đã liên kết với Zalo',
            //         'success' => false
            //     ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
            // } else  
            // if ($user['ql_nguoi_dung_zalo_uid'] && ($user['ql_nguoi_dung_zalo_uid'] != $zaloUid)) {
            //     $this->response([
            //         'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
            //         'message' => 'Tài khoản đã liên kết với Zalo khác!',
            //         'success' => false
            //     ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
            // }

            // if (!$user['ql_nguoi_dung_zalo_uid']) {
            //     $this->Ql_nguoi_dung_model->where('ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])->update([
            //         'ql_nguoi_dung_zalo_uid' => $zaloUid
            //     ]);
            // }

            $user = $this->Ql_nguoi_dung_model
                ->join('e_don_vi', 'e_don_vi.id_don_vi = ql_nguoi_dung.id_don_vi', 'left')
                ->where('ql_nguoi_dung_zalo_uid', $zaloUid)
                ->where('active_flag', 1)
                ->first();

            if (!$user) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                    'message' => 'Tài khoản Zalo chưa được liên kết với tài khoản trong hệ thống.',
                    'success' => false
                ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
            }

            $this->recordLogin($user['ql_nguoi_dung_id']);
            $data = $this->getPermission_byUser($user);

            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Đăng nhập bằng Zalo thành công!',
                'success' => true,
                'data' => $data
            ], REST_INSTANCE_Controller::HTTP_OK);
        } else {
            // $this->flashMessage('error', $response['message'] ?? "Lỗi khi lấy access token từ Zalo!");
            return redirect(base_url('auth/login'));
        }
    }

    private function recordLogin($userId)
    {
        // Cập nhật thời gian đăng nhập cuối cùng
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $this->Ql_nguoi_dung_model->where('ql_nguoi_dung_id', $userId)->update(['lan_dang_nhap_cuoi' => date('Y-m-d H:i:s')]);

        // Lưu lịch sử đăng nhập vào bảng sys_login_logs
        $this->Sys_login_logs_model->createLog(
            $userId,
            $this->input->ip_address(),
            $this->input->user_agent()
        );
    }
}
