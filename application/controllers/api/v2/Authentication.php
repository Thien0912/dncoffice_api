<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 * @property Ql_nguoi_dung_pin_code_model $Ql_nguoi_dung_pin_code_model
 * @property Ql_nguoi_dung_otp_model $Ql_nguoi_dung_otp_model
 * @property Ql_vai_tro_model $Ql_vai_tro_model
 * @property Ql_quyen_model $Ql_quyen_model
 * @property E_tag_model $E_tag_model
 * @property Ql_personal_access_token_model $Ql_personal_access_token_model
 * @property Googleplus $googleplus
 * @property CI_Config $config
 * @property Ql_thong_bao_model $Ql_thong_bao_model
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Hrm_vi_tri_cong_viec_model $Hrm_vi_tri_cong_viec_model
 * @property Sys_user_online_model $Sys_user_online_model
 */

class Authentication extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Ql_nguoi_dung_model');
        $this->load->model('Ql_nguoi_dung_pin_code_model');
        $this->load->model('Ql_nguoi_dung_otp_model');
        $this->load->model('Ql_vai_tro_model');
        $this->load->model('Ql_quyen_model');
        $this->load->model('Ql_personal_access_token_model');
        $this->load->model('Ql_thong_bao_model');
        $this->load->model('E_tag_model');
        $this->load->model('Hrm_nhan_vien_model');
        $this->load->model('Hrm_vi_tri_cong_viec_model');
        $this->load->model('Sys_user_online_model');
        $this->load->model('Sys_login_logs_model');
        $this->load->helper('url');
        $this->load->config('zalo');
        $this->load->helper('cookie');
        $this->load->helper('email');
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

            $user = $this->findUserInSystem(['ql_nguoi_dung_email' => $email]);
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
                $this->recordLogin($user['ql_nguoi_dung_id']);
                $data = $this->getPermission_byUser($user);

                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_OK,
                    'message' => 'Logged successfully',
                    'success' => true,
                    'data' => $data
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

        // 👇 custom redirect_uri tại đây
        $redirectUri = $this->config->item('frontend_v2') . 'auth/google/callback';
        $this->googleplus->client->setRedirectUri($redirectUri);
        if (!commonRequest('code')) {
            resError('Không có code');
        }

        $this->googleplus->getAuthenticate();

        $gpInfo = $this->googleplus->getUserInfo();
        $email = $gpInfo['email'];

        // Lấy thông tin user và tự động cập nhật avatar nếu cần
        $picture = $gpInfo['picture'] ?? '';
        $user = $this->findUserInSystem(['ql_nguoi_dung_email' => $email], $picture);

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
        // $user = $this->Ql_nguoi_dung_model->where('ql_nguoi_dung_email', $email)->where('active_flag', 1)->first();
        $user = $this->findUserInSystem([
            'ql_nguoi_dung_email' => $email,
            'active_flag' => 1
        ]);
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
            'success' => true,
            'data' => $user,
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function checkAccountZalo_post()
    {
        $zaloUid  = commonRequest('ql_nguoi_dung_zalo_uid') ?? '';
        $user = $this->findUserInSystem([
            'ql_nguoi_dung_zalo_uid' => $zaloUid,
            'active_flag' => 1
        ]);

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

        // if (!empty($user['ql_nguoi_dung_avatar']) && !filter_var($user['ql_nguoi_dung_avatar'], FILTER_VALIDATE_URL)) {
        //     $user['ql_nguoi_dung_avatar'] = encryptString($user['ql_nguoi_dung_avatar']);
        // }

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

        // TODO: kiểm tra user có tồn tại trong DB và cập nhật avatar nếu cần
        $user = $this->findUserInSystem(['ql_nguoi_dung_email' => $email], $picture);
        if (!$user || ($user && $user['active_flag'] != 1)) {
            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_UNAUTHORIZED,
                'message' => 'Tài khoản không tồn tại hoặc đã bị khóa',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_UNAUTHORIZED);
        }

        // Ví dụ trả về luôn
        $this->recordLogin($user['ql_nguoi_dung_id']);
        return $this->response([
            'status'  => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Đăng nhập Google thành công',
            'success' => true,
            // 'data'    => [
            //     'email'   => $email,
            //     'name'    => $name,
            //     'picture' => $picture,
            //     'user'    => $this->getPermission_byUser($user),
            // ]
            'data'    => $this->getPermission_byUser($user)
        ], REST_INSTANCE_Controller::HTTP_OK);
    }



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
    public function heartbeat_post()
    {
        $user = $this->getUserLogin();
        if (!$user) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_UNAUTHORIZED,
                'message' => 'Unauthorized',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_UNAUTHORIZED);
        }

        $ip = $this->input->ip_address();
        $userAgent = $this->input->user_agent();

        $this->Sys_user_online_model->updateHeartbeat($user['ql_nguoi_dung_id'], $ip, $userAgent);

        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Heartbeat received',
            'success' => true
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function online_users_get()
    {
        // dd(1);
        $user = $this->getUserLogin();
        if (!$user) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_UNAUTHORIZED,
                'message' => 'Unauthorized',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_UNAUTHORIZED);
        }

        // Check if requesting count only or full list
        $type = $this->input->get('type'); // 'count' or 'list'

        if ($type === 'count') {
            $count = $this->Sys_user_online_model->countOnlineUsers(2); // 2 minutes
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Success',
                'success' => true,
                'data' => ['count' => $count]
            ], REST_INSTANCE_Controller::HTTP_OK);
        } else {
            $users = $this->Sys_user_online_model->getOnlineUsers(2); // 2 minutes
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Success',
                'success' => true,
                'data' => $users
            ], REST_INSTANCE_Controller::HTTP_OK);
        }
    }


    public function login_sso_post()
    {
        $access_token =  $this->getAuthTokenFromHeader();
        if (!$access_token) {
            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Access token không được để trống',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }
        $uSSO = $this->getUserInfo_SSO($access_token);
        $email   = strtolower($uSSO['status']) == strtolower('success') ? $uSSO['data']['email'] : null;
        if (!$uSSO || !$email) {
            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_UNAUTHORIZED,
                'message' => 'Truy vấn userinfo từ SSO thất bại',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_UNAUTHORIZED);
        }
        $user = $this->findUserInSystem(['ql_nguoi_dung_email' => $email]);
        if (!$user || ($user && $user['active_flag'] != 1)) {
            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_UNAUTHORIZED,
                'message' => 'Tài khoản không tồn tại hoặc đã bị khóa',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_UNAUTHORIZED);
        }

        $uSSO['data']['user'] = $user;

        $this->recordLogin($user['ql_nguoi_dung_id']);

        return $this->response([
            'status'  => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Đăng nhập SSO thành công',
            'success' => true,
            'data'    => $uSSO['data']
        ], REST_INSTANCE_Controller::HTTP_OK);
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

    public function pin_register_post()
    {
        $encryptedPinCode = commonRequest('pin_code');

        if (!$encryptedPinCode) {
            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Mã PIN không được để trống',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        // Giải mã PIN code
        $privateKey = file_get_contents(APPPATH . '../private_key.pem');
        $pinCode = $this->decryptRSA($encryptedPinCode, $privateKey);

        if (!$pinCode) {
            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Không thể giải mã PIN code',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        if (strlen($pinCode) != 6) {
            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Mã PIN phải có 6 chữ số',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        $user = $this->getUserLogin();
        if (!$user) {
            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_UNAUTHORIZED,
                'message' => 'Unauthorized',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_UNAUTHORIZED);
        }

        // Kiểm tra xem đã có mã PIN chưa
        $existingPin = $this->Ql_nguoi_dung_pin_code_model
            ->where('ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])
            ->where('is_valid', 1)
            ->first();

        if ($existingPin) {
            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Tài khoản của bạn đã được đăng ký mã PIN trước đó.',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        $pinCodeResult = $this->Ql_nguoi_dung_pin_code_model->create([
            'ql_nguoi_dung_id' => $user['ql_nguoi_dung_id'],
            'pin_code' => encryptString($pinCode),
            'created_user_id' => $user['ql_nguoi_dung_id'],
            'created_at' => date('Y-m-d H:i:s'),
            'is_valid' => 0
        ]);

        unset($pinCodeResult['pin_code']);

        $this->Ql_nguoi_dung_pin_code_model
            ->where('ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])
            ->where('send_mail_at IS NOT NULL')
            ->where('is_valid', 0)
            ->where('deleted_at', null)
            ->update([
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_user_id' => $user['ql_nguoi_dung_id']
            ]);
        // Gửi socket thông báo đăng ký thành công (chờ xác thực)
        $this->load->helper('socket_helper');
        send_socket_pin_registered($user['ql_nguoi_dung_id']);

        resSuccess(
            $pinCodeResult,
            'Đăng ký mã PIN thành công. Đang gửi email xác thực...'
        );
    }

    public function sendPinCodeApprovalEmail_get($id)
    {
        if (is_array($id)) {
            $id = $id['id_ql_nguoi_dung_pin_code'] ?? null;
        }

        if (!$id) {
            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'ID không hợp lệ',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        $auth = $this->getUserLogin();
        if (!$auth) {
            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_UNAUTHORIZED,
                'message' => 'Unauthorized',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_UNAUTHORIZED);
        }
        $userData = $auth;

        try {
            // Lấy thông tin PIN code từ database
            $pinCodeRecord = $this->Ql_nguoi_dung_pin_code_model
                ->where('id_ql_nguoi_dung_pin_code', $id)
                ->where('ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])
                ->first();

            if (!$pinCodeRecord) {
                return $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                    'message' => 'Không tìm thấy thông tin mã PIN',
                    'success' => false
                ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
            }

            // Tạo token mã hóa chứa thông tin
            $tokenData = json_encode([
                'id_ql_nguoi_dung_pin_code' => $id,
                'ql_nguoi_dung_id' => $auth['ql_nguoi_dung_id'],
                'timestamp' => time(),
                'random' => bin2hex(random_bytes(16))
            ]);
            
            // Mã hóa token với base64url (URL-safe)
            $encrypted = encryptString($tokenData);
            $verificationToken = rtrim(strtr(base64_encode($encrypted), '+/', '-_'), '=');
            
            // Lưu token vào database
            $this->Ql_nguoi_dung_pin_code_model->where('id_ql_nguoi_dung_pin_code', $id)->update([
                'verification_token' => $verificationToken
            ]);

            $to = $auth['ql_nguoi_dung_email'];
            $subject = 'Xác thực đăng ký Mã PIN - DNC Office';

            // Tạo link với token (không cần urlencode vì đã dùng base64url)
            $proposeId = $this->input->get('id_de_xuat');
            $viewMode = $this->input->get('view');
            $linkXacThuc = $this->config->item('frontend_v2') . 'authentication/activate_pin_code/' . $verificationToken;
            
            $linkParams = [];
            if ($proposeId) $linkParams[] = 'id_de_xuat=' . $proposeId;
            if ($viewMode) $linkParams[] = 'view=' . $viewMode;
            
            if (!empty($linkParams)) {
                $linkXacThuc .= '?' . implode('&', $linkParams);
            }

            $viewData = [
                'data' => $userData,
                'link_xac_thuc' => $linkXacThuc
            ];

            $message = $this->load->view('email/pin_code_verification.php', $viewData, true);

            $result = send_email($to, $subject, $message, 'noreply@tchc.nctu.edu.vn', 'Trường Đại Học Nam Cần Thơ - DNC University');

            if ($result) {
                $this->Ql_nguoi_dung_pin_code_model->where('id_ql_nguoi_dung_pin_code', $id)->update([
                    'send_mail_at' => date('Y-m-d H:i:s')
                ]);
                return $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_OK,
                    'message' => 'Email xác thực đã được gửi',
                    'success' => true
                ], REST_INSTANCE_Controller::HTTP_OK);
            }

            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Không thể gửi email xác thực',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            log_message('error', 'Lỗi gửi email mã PIN: ' . $e->getMessage());
            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Lỗi hệ thống khi gửi email',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function activate_pin_code_get($token)
    {
        // URL decode token để xử lý ký tự đặc biệt
        $token = urldecode($token);
        
        if (!$token) {
            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Đường dẫn không hợp lệ',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        return $this->processActivatePinCode($token);
    }

    public function activate_pin_code_post()
    {
        $token = commonRequest('token');
        
        if (!$token) {
            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Token không được để trống',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        return $this->processActivatePinCode($token);
    }

    private function processActivatePinCode($token)
    {
        try {
            // Chuyển base64url về base64 thông thường
            $base64 = strtr($token, '-_', '+/');
            // Thêm padding nếu cần
            $base64 .= str_repeat('=', (4 - strlen($base64) % 4) % 4);
            
            // Giải mã token
            $decryptedToken = decryptString(base64_decode($base64));
            $tokenData = json_decode($decryptedToken, true);

            if (!$tokenData || !isset($tokenData['id_ql_nguoi_dung_pin_code']) || !isset($tokenData['ql_nguoi_dung_id'])) {
                return $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                    'message' => 'Token không hợp lệ',
                    'success' => false
                ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
            }

            $id = $tokenData['id_ql_nguoi_dung_pin_code'];
            $userId = $tokenData['ql_nguoi_dung_id'];

            // Lấy thông tin PIN code từ database
            $pinCode = $this->Ql_nguoi_dung_pin_code_model
                ->where('id_ql_nguoi_dung_pin_code', $id)
                ->where('ql_nguoi_dung_id', $userId)
                ->where('verification_token', $token)
                ->where('deleted_at', null)
                ->first();

            if (!$pinCode) {
                return $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                    'message' => 'Link xác thực không hợp lệ hoặc đã được sử dụng',
                    'success' => false
                ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
            }

            // Kiểm tra xem đã xác thực chưa
            if ($pinCode['is_valid'] == 1) {
                return $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                    'message' => 'Mã PIN này đã được xác thực trước đó',
                    'success' => false
                ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
            }

            // Kiểm tra thời gian hết hạn
            $expiredMinutes = 5;
            $isExpiredTime = time() > (strtotime($pinCode['send_mail_at']) + $expiredMinutes * 60);
            if ($isExpiredTime) {
                return $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                    'message' => 'Thời gian xác thực đã hết hạn (quá 5 phút)',
                    'success' => false
                ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
            }

            // Cập nhật trạng thái xác thực
            $this->Ql_nguoi_dung_pin_code_model->where('id_ql_nguoi_dung_pin_code', $id)->update([
                'is_valid' => 1,
                'verified_at' => date('Y-m-d H:i:s')
            ]);

            // Xóa token để không thể sử dụng lại
            $this->Ql_nguoi_dung_pin_code_model->where('id_ql_nguoi_dung_pin_code', $id)->update([
                'verification_token' => null
            ]);

            // Gửi socket thông báo xác thực thành công
            $this->load->helper('socket_helper');
            send_socket_pin_verified($userId);

            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Xác thực mã PIN thành công',
                'success' => true,
                'data' => [
                    'ql_nguoi_dung_id' => $userId,
                    'verified_at' => date('Y-m-d H:i:s')
                ]
            ], REST_INSTANCE_Controller::HTTP_OK);
        } catch (Exception $e) {
            log_message('error', 'Lỗi xác thực mã PIN: ' . $e->getMessage());
            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Lỗi hệ thống khi xác thực mã PIN',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function pin_code_verify_post()
    {
        $encryptedPinCode = commonRequest('pin_code');
        if (!$encryptedPinCode) {
            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Mã PIN không được để trống',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }
        // Giải mã PIN code
        $privateKey = file_get_contents(APPPATH . '../private_key.pem');
        $pinCode = $this->decryptRSA($encryptedPinCode, $privateKey);

        if (!$pinCode) {
            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Không thể giải mã PIN code',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        $user = $this->getUserLogin();
        if (!$user) {
            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_UNAUTHORIZED,
                'message' => 'Unauthorized',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_UNAUTHORIZED);
        }
        $pinCode = $this->Ql_nguoi_dung_pin_code_model->select('*')
            ->where('ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])
            ->where('pin_code', encryptString($pinCode))
            ->where('is_valid', 1)
            ->where('deleted_at', null)
            ->first();

        if (!$pinCode) {
            $has_pin = null;
            $pinCodeUserValid = $this->Ql_nguoi_dung_pin_code_model
                ->where('ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])
                ->where('is_valid', 1)
                ->where('deleted_at', null)
                ->first();

            if (!$pinCodeUserValid) {
                $pinCodeUserInValid = $this->Ql_nguoi_dung_pin_code_model
                    ->where('ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])
                    ->where('is_valid', 0)
                    ->where('deleted_at', null)
                    ->first();

                if ($pinCodeUserInValid['pin_failed_attempts'] >= 5) {
                    $has_pin = 3;
                    return $this->response([
                        'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                        'message' => 'Mã PIN của bạn đã bị vô hiệu hóa',
                        'success' => false,
                        'has_pin' => $has_pin
                    ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
                } else if (!$pinCodeUserInValid['send_mail_at']) {
                    $has_pin = 2;
                    return $this->response([
                        'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                        'message' => 'Mã PIN của bạn chưa được kích hoạt',
                        'success' => false,
                        'has_pin' => $has_pin
                    ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
                }
            }

            $new_pin_failed_attempts = $pinCodeUserValid ? (int)$pinCodeUserValid['pin_failed_attempts'] + 1 : 0;
            $this->Ql_nguoi_dung_pin_code_model
                ->where('ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])
                ->where('is_valid', 1)
                ->update([
                    'pin_failed_attempts' => $new_pin_failed_attempts
                ]);

            $messageResponse = '';
            if ($new_pin_failed_attempts >= 5) {
                $has_pin = 3;
                $messageResponse = 'Mã PIN đã bị khóa do nhập sai quá số lần quy định';
                $this->Ql_nguoi_dung_pin_code_model
                    ->where('ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])
                    ->where('is_valid', 1)
                    ->update([
                        'is_valid' => 0
                    ]);
            } else {
                $messageResponse = 'Mã PIN không tồn tại. Số lần thử: ' . $new_pin_failed_attempts . '/5';
            }

            return $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => $messageResponse,
                'success' => false,
                'has_pin' => $has_pin
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        $this->Ql_nguoi_dung_pin_code_model
            ->where('ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])
            ->where('is_valid', 1)
            ->update([
                'pin_failed_attempts' => 0
            ]);

        resSuccess(
            null,
            'Mã PIN hợp lệ'
        );
    }

    /**
     * Tạo OTP và gửi email (Pattern giống PIN code)
     * POST /api/v2/authentication/send_otp
     * 
     * @return json
     */
    public function send_otp_post()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Vui lòng đăng nhập để sử dụng chức năng này', REST_Controller::HTTP_UNAUTHORIZED);
        }

        // Kiểm tra vai trò: chỉ cho phép SUPER_ADMIN, VĂN THƯ TCHC, LÃNH ĐẠO TCHC
        $allowedRoleCodes = ['SUPER_ADMIN', 'VAN_THU_TO_CHUC_HANH_CHINH', 'LANH_DAO_TCHC', 'PHONG_CTCT_QLSV'];
        
        // Lấy tất cả vai trò của user từ database (user có thể có nhiều vai trò)
        $userRoles = $this->db
            ->select('vt.ql_ma_vai_tro')
            ->from('ql_vai_tro_nguoi_dung vtn')
            ->join('ql_vai_tro vt', 'vtn.ql_vai_tro_id = vt.ql_vai_tro_id', 'inner')
            ->where('vtn.ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])
            ->get()
            ->result_array();
        
        // Lấy mảng mã vai trò
        $userRoleCodes = array_column($userRoles, 'ql_ma_vai_tro');
        
        // Kiểm tra có ít nhất 1 vai trò hợp lệ
        $hasValidRole = !empty(array_intersect($userRoleCodes, $allowedRoleCodes));
        
        // Kiểm tra nếu là admin hoặc có vai trò được phép
        if ($auth['ql_nguoi_dung_is_admin'] != 1 && !$hasValidRole) {
            resError('Bạn không có quyền sử dụng tính năng xác thực OTP. Chỉ Lãnh đạo TCHC, Văn thư TCHC và Quản trị viên mới được phép', REST_Controller::HTTP_FORBIDDEN);
        }

        // Lấy email từ thông tin người dùng
        $email = $auth['ql_nguoi_dung_email'];
        if (empty($email)) {
            resError('Tài khoản của bạn chưa có email. Vui lòng liên hệ quản trị viên', REST_Controller::HTTP_BAD_REQUEST);
        }

        try {
            // Kiểm tra xem có OTP đang hoạt động không
            $activeOtp = $this->Ql_nguoi_dung_otp_model->getActiveOTP($auth['ql_nguoi_dung_id']);
            if ($activeOtp) {
                $remainingTime = strtotime($activeOtp['expired_at']) - time();
                if ($remainingTime > 0) {
                    resError('Mã OTP trước đó vẫn còn hiệu lực. Vui lòng chờ ' . ceil($remainingTime / 60) . ' phút để tạo mới', REST_Controller::HTTP_TOO_MANY_REQUESTS, [
                        'remaining_seconds' => $remainingTime,
                        'expired_at' => $activeOtp['expired_at'],
                        'id' => $activeOtp['id']
                    ]);
                }
            }

            // 1. Tạo OTP và lưu vào database TRƯỚC (mặc định hết hạn sau 5 phút)
            $otp = $this->Ql_nguoi_dung_otp_model->createOTP($auth['ql_nguoi_dung_id'], 5);

            // 2. Trả về kết quả NGAY (trước khi gửi email)
            // Đăng ký hàm gửi email chạy sau khi response trả về
            $otpId = $otp['id'];
            $user = $auth;
            register_shutdown_function(function() use ($otpId, $user) {
                // Gửi email sau khi response đã được gửi về client
                $CI = &get_instance();
                $CI->sendOTPEmailSync($otpId, $user);
            });

            resSuccess([
                'id' => $otp['id'],
                'expired_at' => $otp['expired_at'],
                'expire_minutes' => 5
            ], 'Mã OTP đã được tạo và đang gửi email...', REST_Controller::HTTP_OK);

        } catch (Exception $e) {
            log_message('error', 'Lỗi tạo OTP: ' . $e->getMessage());
            resError('Có lỗi xảy ra khi tạo mã OTP', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Gửi email OTP (có thể gọi riêng để retry)
     * GET /api/v2/authentication/send_otp_email/:id
     * 
     * @param int $id - ID của OTP record
     * @return json
     */
    public function send_otp_email_get($id)
    {
        if (is_array($id)) {
            $id = $id['id'] ?? null;
        }

        if (!$id) {
            resError('ID không hợp lệ', REST_Controller::HTTP_BAD_REQUEST);
        }

        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Vui lòng đăng nhập để sử dụng chức năng này', REST_Controller::HTTP_UNAUTHORIZED);
        }

        try {
            $result = $this->sendOTPEmail($id, $auth);

            if ($result) {
                resSuccess(null, 'Email OTP đã được gửi thành công', REST_Controller::HTTP_OK);
            } else {
                resError('Không thể gửi email OTP. Vui lòng thử lại', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (Exception $e) {
            log_message('error', 'Lỗi gửi email OTP: ' . $e->getMessage());
            resError('Có lỗi xảy ra khi gửi email', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Gửi email OTP đồng bộ
     * @return bool
     */
    private function sendOTPEmailSync($otpId, $user)
    {
        try {
            // Lấy thông tin OTP từ database
            $otp = $this->db
                ->where('id', $otpId)
                ->where('ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])
                ->where('is_verified', 0)
                ->where('deleted_at IS NULL')
                ->get('ql_nguoi_dung_otp')
                ->row_array();

            if (!$otp) {
                return false;
            }

            // Kiểm tra OTP đã hết hạn chưa
            if (strtotime($otp['expired_at']) < time()) {
                return false;
            }

            // Chuẩn bị nội dung email
            $subject = 'Mã OTP xác thực bảo mật - DNC Office';
            
            $message = $this->load->view('email/otp_verification_template.php', [
                'ho_ten' => $user['ql_nguoi_dung_ho_ten'],
                'otp_code' => decryptString($otp['otp_code']),
                'expired_at' => date('H:i d/m/Y', strtotime($otp['expired_at']))
            ], true);

            // Gửi email
            $sendResult = send_email(
                $user['ql_nguoi_dung_email'],
                $subject,
                $message,
                'noreply@tchc.nctu.edu.vn',
                'Trường Đại Học Nam Cần Thơ - DNC University'
            );

            if ($sendResult) {
                // Cập nhật thời gian gửi email
                $this->db->where('id', $otpId)->update('ql_nguoi_dung_otp', [
                    'send_mail_at' => date('Y-m-d H:i:s')
                ]);
            }

            return $sendResult;

        } catch (Exception $e) {
            log_message('error', 'Lỗi trong sendOTPEmail: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Xác thực mã OTP (Trả về true nếu OK)
     * POST /api/v2/authentication/verify_otp
     * 
     * @param string otp_code - Mã OTP 6 số
     * @return json
     */
    public function verify_otp_post()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Vui lòng đăng nhập để sử dụng chức năng này', REST_Controller::HTTP_UNAUTHORIZED);
        }

        $otpCode = commonRequest('otp_code');

        if (empty($otpCode)) {
            resError(['otp_code' => 'Mã OTP không được để trống'], 'Dữ liệu không hợp lệ', REST_Controller::HTTP_BAD_REQUEST);
        }

        // Chuẩn hóa mã OTP (bỏ khoảng trắng)
        $otpCode = trim($otpCode);

        try {
            $result = $this->Ql_nguoi_dung_otp_model->verifyOTP(
                $auth['ql_nguoi_dung_id'],
                $otpCode
            );

            if ($result['success']) {
                // Gửi socket notification nếu cần
                $this->load->helper('socket_helper');
                // send_socket_otp_verified($auth['ql_nguoi_dung_id']);

                // Trả về true như yêu cầu
                resSuccess([
                    'verified' => true
                ], $result['message'], REST_Controller::HTTP_OK);
            } else {
                $httpCode = REST_Controller::HTTP_BAD_REQUEST;
                
                if ($result['code'] === 'OTP_NOT_FOUND') {
                    $httpCode = REST_Controller::HTTP_NOT_FOUND;
                }

                // Trả về false khi sai
                resError([
                    'verified' => false,
                    'code' => $result['code'],
                    'failed_attempts' => $result['failed_attempts'] ?? null,
                    'remaining_attempts' => $result['remaining_attempts'] ?? null
                ], $result['message'], $httpCode);
            }

        } catch (Exception $e) {
            log_message('error', 'Lỗi xác thực OTP: ' . $e->getMessage());
            resError('Có lỗi xảy ra khi xác thực mã OTP', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Chuyển đổi vai trò (Dành cho user có nhiều vai trò)
     * POST /api/v2/authentication/change_role
     * 
     * Body: {
     *   "ql_vai_tro_nguoi_dung_id": 123 | "ql_vai_tro_id": 46
     * }
     * 
     * @return json
     */
    public function change_role_post()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Vui lòng đăng nhập để sử dụng chức năng này', REST_Controller::HTTP_UNAUTHORIZED);
        }

        // Kiểm tra số lượng vai trò của user
        $soLuongVaiTro = $this->db
            ->where('ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])
            ->count_all_results('ql_vai_tro_nguoi_dung');

        if ($soLuongVaiTro < 2) {
            resError('Tài khoản của bạn chỉ có 1 vai trò, không thể chuyển đổi', REST_Controller::HTTP_BAD_REQUEST);
        }

        $vaiTroNguoiDungId = commonRequest('ql_vai_tro_nguoi_dung_id');
        $vaiTroId = commonRequest('ql_vai_tro_id');
        
        if (!$vaiTroNguoiDungId && !$vaiTroId) {
            resError('Vui lòng chọn vai trò muốn chuyển đến', REST_Controller::HTTP_BAD_REQUEST);
        }

        // Kiểm tra vai trò có thuộc về user này không
        // Chọn vai trò theo ql_vai_tro_id (FE đang gửi theo ql_vai_tro_id)
        // Nếu có ql_vai_tro_nguoi_dung_id thì coi như fallback sang ql_vai_tro_id
        $roleIdToActivate = $vaiTroId;
        if (!$roleIdToActivate && $vaiTroNguoiDungId) {
            $row = $this->db
                ->select('ql_vai_tro_id')
                ->from('ql_vai_tro_nguoi_dung')
                ->where('ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])
                ->where('ql_vai_tro_id', $vaiTroNguoiDungId)
                ->get()
                ->row_array();
            $roleIdToActivate = $row['ql_vai_tro_id'] ?? null;
        }

        $vaiTroNguoiDung = $this->db
            ->select('vtn.ql_nguoi_dung_id, vtn.ql_vai_tro_id, vt.ql_ma_vai_tro, vt.ql_vai_tro_ten')
            ->from('ql_vai_tro_nguoi_dung vtn')
            ->join('ql_vai_tro vt', 'vt.ql_vai_tro_id = vtn.ql_vai_tro_id', 'left')
            ->where('vtn.ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])
            ->where('vtn.ql_vai_tro_id', $roleIdToActivate)
            ->get()
            ->row_array();

        if (!$vaiTroNguoiDung) {
            resError('Vai trò này không thuộc về tài khoản của bạn', REST_Controller::HTTP_NOT_FOUND);
        }

        // Bắt đầu transaction
        $this->db->trans_start();

        // Tắt tất cả vai trò của user
        $this->db->where('ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])
                 ->update('ql_vai_tro_nguoi_dung', ['is_active' => 0]);

        // Bật đúng vai trò được chọn
        $this->db->where('ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])
             ->where('ql_vai_tro_id', $vaiTroNguoiDung['ql_vai_tro_id'])
             ->update('ql_vai_tro_nguoi_dung', ['is_active' => 1]);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            resError('Có lỗi xảy ra khi chuyển đổi vai trò', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Lấy lại thông tin user sau khi chuyển vai trò
        $user = $this->findUserInSystem(['ql_nguoi_dung.ql_nguoi_dung_id' => $auth['ql_nguoi_dung_id']]);
        $data = $this->getPermission_byUser($user);

        resSuccess(
            $data,
            'Chuyển đổi vai trò thành công sang: ' . $vaiTroNguoiDung['ql_vai_tro_ten'],
            REST_Controller::HTTP_OK
        );
    }

    
}

