<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 * @property CI_Input $input
 * @property CI_Upload $upload
 * @property Ql_personal_access_token_model $Ql_personal_access_token_model
 */
class Users extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();

        // $this->permissionMiddleware();

        $this->load->model('Ql_nguoi_dung_model');
    }
    //get users with page number and number of record
    public function index_get()
    {
        // $this->permissionMiddleware();
        $data = $this->Ql_nguoi_dung_model->getSearch();
        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => $data
        ], REST_INSTANCE_Controller::HTTP_OK);
    }
    //create user with post method
    public function create_post()
    {
        $this->permissionMiddleware();
        try {
            //get data with json type
            $ten = commonRequest('ql_nguoi_dung_ho_ten') ? commonRequest('ql_nguoi_dung_ho_ten') : null;
            $email = commonRequest('ql_nguoi_dung_email') ? commonRequest('ql_nguoi_dung_email') : null;
            $password = commonRequest('ql_nguoi_dung_mat_khau') ? commonRequest('ql_nguoi_dung_mat_khau') : null;
            $id_don_vi = commonRequest('id_don_vi') ? commonRequest('id_don_vi') : null;
            if (!$ten) {
                resError('Tên người dùng bắt buộc nhập');
            }
            if (!$email) {
                resError('Email người dùng bắt buộc nhập');
            }

            if ($this->Ql_nguoi_dung_model->where('ql_nguoi_dung_email', $email)->first()) {
                resError('Email đã tồn tại');
            }

            if (!$password) {
                resError('Mật khẩu người dùng bắt buộc nhập');
            }
            if (!$id_don_vi) {
                resError('Đơn vị bắt buộc');
            }
            $data = [
                'ql_nguoi_dung_ho_ten' => $ten,
                'ql_nguoi_dung_email' => $email,
                'ql_nguoi_dung_mat_khau' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                'ql_nguoi_dung_loai' => 2,
                'ql_nguoi_dung_la_lanh_dao' => commonRequest('ql_nguoi_dung_la_lanh_dao') ? commonRequest('ql_nguoi_dung_la_lanh_dao') : null,
                'id_don_vi' => $id_don_vi
            ];
            $user = $this->Ql_nguoi_dung_model->storeUser($data);

            if ($user)
                //create log
                $this->createLog(
                    'create',
                    'Thêm mới người dùng',
                    null,
                    $user,
                    'ql_nguoi_dung'
                );

            $this->response([

                'status' => REST_INSTANCE_Controller::HTTP_CREATED,

                'message' => 'Thêm mới người dùng thành công.',

                'success' => true,

                'data' => $user

            ], REST_INSTANCE_Controller::HTTP_CREATED);

            $this->response([

                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,

                'message' => 'Thêm mới người dùng thất bại',

                'success' => false,

                'data' => $user

            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
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
    //show user
    public function show_get($id)
    {
        $this->permissionMiddleware();
        $user = $this->Ql_nguoi_dung_model->find($id); //get user with id => row_array, logic in MY_Model
        if (!$user || ($user && $user['ql_nguoi_dung_is_admin'] == 1)) {
            $this->response([

                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,

                'message' => 'Không tìm thấy người dùng',

                'success' => false,

                'data' => null

            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }
        $this->response([

            'status' => REST_INSTANCE_Controller::HTTP_OK,

            'message' => 'Thành công',

            'success' => true,

            'data' => $user

        ], REST_INSTANCE_Controller::HTTP_OK);
    }
    //update user
    public function update_put($id)
    {
        $this->permissionMiddleware();
        try {
            $findUser = $this->Ql_nguoi_dung_model->find($id);
            if (!$findUser) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                    'message' => 'Không tìm thấy người dùng',
                    'success' => false,
                    'data' => null
                ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
                return;
            }

            $data = [];
            $rolesChanged = false;
            $newRoleIds = null;

            if (commonRequest('ql_nguoi_dung_ho_ten') !== null) {
                $val = commonRequest('ql_nguoi_dung_ho_ten');
                if (trim((string)$val) === '') {
                    resError('Tên người dùng bắt buộc nhập');
                    return;
                }
                if ((string)$val !== (string)$findUser['ql_nguoi_dung_ho_ten']) {
                    $data['ql_nguoi_dung_ho_ten'] = $val;
                }
            }

            if (commonRequest('ql_nguoi_dung_email') !== null) {
                $val = commonRequest('ql_nguoi_dung_email');
                if (trim((string)$val) === '') {
                    resError('Email người dùng bắt buộc nhập');
                    return;
                }
                if ((string)$val !== (string)$findUser['ql_nguoi_dung_email']) {
                    $data['ql_nguoi_dung_email'] = $val;
                }
            }

            if (commonRequest('id_don_vi') !== null) {
                $val = commonRequest('id_don_vi');
                if (trim((string)$val) === '') {
                    resError('Đơn vị bắt buộc');
                    return;
                }
                if ((string)$val !== (string)$findUser['id_don_vi']) {
                    $data['id_don_vi'] = $val;
                }
            }

            if (commonRequest('ql_nguoi_dung_zalo_oa_uid') !== null) {
                $val = commonRequest('ql_nguoi_dung_zalo_oa_uid');
                if ((string)$val !== (string)$findUser['ql_nguoi_dung_zalo_oa_uid']) {
                    $data['ql_nguoi_dung_zalo_oa_uid'] = $val;
                }
            }
            if (commonRequest('ql_nguoi_dung_la_lanh_dao') !== null) {
                $val = commonRequest('ql_nguoi_dung_la_lanh_dao');
                if ((string)$val !== (string)$findUser['ql_nguoi_dung_la_lanh_dao']) {
                    $data['ql_nguoi_dung_la_lanh_dao'] = $val;
                }
            }
            if (commonRequest('active_flag') !== null) {
                $val = commonRequest('active_flag');
                if ((string)$val !== (string)$findUser['active_flag']) {
                    $data['active_flag'] = $val;
                }
            }
            if (commonRequest('ql_nguoi_dung_is_admin') !== null) {
                $val = commonRequest('ql_nguoi_dung_is_admin');
                if ((string)$val !== (string)$findUser['ql_nguoi_dung_is_admin']) {
                    $data['ql_nguoi_dung_is_admin'] = $val;
                }
            }
            // Handle Role Updates
            if (commonRequest('role_ids') !== null) {
                // Bảo vệ tài khoản quản trị gốc (ID 1)
                if ($findUser && $id == 1 && $findUser['ql_nguoi_dung_is_admin'] == 1 && commonRequest('ql_nguoi_dung_is_admin') === '0') {
                    resError('Không thể gỡ bỏ quyền quản trị của tài khoản gốc hệ thống');
                    return;
                }

                // Chỉ chặn thay đổi vai trò nếu tài khoản ĐANG là admin và KHÔNG có yêu cầu tắt quyền admin
                if ($findUser && $findUser['ql_nguoi_dung_is_admin'] == 1 && commonRequest('ql_nguoi_dung_is_admin') !== '0') {
                    resError('Không thể thay đổi vai trò khi đang là quản trị cấp cao. Hãy tắt quyền quản trị trước nếu muốn hạ cấp.');
                    return;
                }

                $roleIdsRaw = commonRequest('role_ids');
                $roleIds = [];
                if (is_array($roleIdsRaw)) {
                    $roleIds = $roleIdsRaw;
                } else if (trim((string)$roleIdsRaw) !== '') {
                    $roleIds = explode(',', (string)$roleIdsRaw);
                }
                
                $roleIds = array_unique(array_filter(array_map('trim', $roleIds)));
                sort($roleIds);

                $currentRoles = $this->Ql_nguoi_dung_model->getRoleUsersByUserId($id);
                $currentRoleIds = array_column($currentRoles, 'ql_vai_tro_id');
                $currentRoleIds = array_unique(array_filter(array_map('trim', $currentRoleIds)));
                sort($currentRoleIds);

                if ($roleIds !== $currentRoleIds) {
                    $rolesChanged = true;
                    $newRoleIds = $roleIds;
                }
            }

            if (empty($data) && !$rolesChanged) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_OK,
                    'message' => 'Không có thay đổi nào được thực hiện',
                    'success' => true,
                    'data' => $findUser
                ], REST_INSTANCE_Controller::HTTP_OK);
                return;
            }

            if ($rolesChanged && $newRoleIds !== null) {
                $this->Ql_nguoi_dung_model->deletePivotRoleUser($id);
                foreach ($newRoleIds as $rId) {
                    if (trim($rId)) $this->Ql_nguoi_dung_model->insertRoleUser($id, trim($rId));
                }
            }

            $user = null;
            if (!empty($data)) {
                 $user = $this->Ql_nguoi_dung_model->updateUser($id, $data);
            } else {
                 $user = $this->Ql_nguoi_dung_model->find($id);
            }

            $this->createLog(
                'update',
                'Cập nhật người dùng',
                $findUser,
                $user,
                'ql_nguoi_dung'
            );

            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Cập nhật người dùng thành công',
                'success' => true,
                'data' => $user
            ], REST_INSTANCE_Controller::HTTP_OK);
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

    //update user
    public function delete_delete($id)
    {
        $this->permissionMiddleware();
        try {
            $user = $this->Ql_nguoi_dung_model->find($id);
            if (!$user || ($user && $user['ql_nguoi_dung_is_admin'] == 1)) {
                $this->response([

                    'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,

                    'message' => 'Không tìm thấy người dùng',

                    'success' => false,

                    'data' => null

                ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
            //Xóa chính mình
            if ($this->getUserLogin()['ql_nguoi_dung_id'] == $id) {
                $this->response([

                    'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,

                    'message' => 'Bạn không thể tự xóa chính mình',

                    'success' => false,

                    'data' => null

                ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
            // $this->Ql_nguoi_dung_model->deletePivotRoleUser($id);
            // $result = $this->Ql_nguoi_dung_model->where('ql_nguoi_dung_id', $id)->delete();

            //Kiểm tra nếu đang có quyền thì không được xóa
            $hasRole = $this->Ql_nguoi_dung_model
                ->join('ql_vai_tro_nguoi_dung', 'ql_vai_tro_nguoi_dung.ql_nguoi_dung_id = ql_nguoi_dung.ql_nguoi_dung_id')
                ->where('ql_nguoi_dung.ql_nguoi_dung_id', $id)
                ->first();

            if ($hasRole) {
                resError('Người dùng đang được phân quyền, vui lòng gỡ hết vai trò để xóa người dùng');
            }

            $result = $this->Ql_nguoi_dung_model->where('ql_nguoi_dung_id', $id)->update([
                'active_flag' => 0
            ]);

            if ($result) {

                $this->createLog(
                    'delete',
                    'Xóa người dùng',
                    $user,
                    null,
                    'ql_nguoi_dung'
                );

                $this->response([

                    'status' => REST_INSTANCE_Controller::HTTP_OK,

                    'message' => 'Xóa người dùng thành công',

                    'success' => true,

                    'data' => null

                ], REST_INSTANCE_Controller::HTTP_OK);
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

    //get roles of user
    public function user_roles_get($userId)
    {
        $this->permissionMiddleware();
        $findUser = $this->Ql_nguoi_dung_model->find($userId);
        if (!$findUser || ($findUser &&  $findUser['ql_nguoi_dung_is_admin'] == 1)) {
            $this->response([

                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,

                'message' => 'Không tìm thấy người dùng',

                'success' => false,

                'data' => null

            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }

        $user = $this->Ql_nguoi_dung_model->getUsersRoles(10, 0, $userId);
        $this->response([

            'status' => REST_INSTANCE_Controller::HTTP_OK,

            'message' => 'Thành công',

            'success' => true,

            'data' => $user

        ], REST_INSTANCE_Controller::HTTP_OK);
    }
    //set roles for user
    public function user_set_roles_put($userId)
    {
        $this->permissionMiddleware();
        $findUser = $this->Ql_nguoi_dung_model->find($userId);
        if (!$findUser) {
            $this->response([

                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,

                'message' => 'Không tìm thấy người dùng',

                'success' => false,

                'data' => null

            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }

        if ($findUser && $findUser['ql_nguoi_dung_is_admin'] == 1) {
            resError('Không thể thay đổi vai trò của tài khoản quản trị cấp cao');
        }

        $userRoles = $this->Ql_nguoi_dung_model->getRoleUsersByUserId($userId);
        $rIds = [];
        foreach ($userRoles as $ur) {
            $rIds[] = $ur['ql_vai_tro_id'];
        }

        $roleIds = commonRequest('ql_vai_tro_ids') ? json_decode(commonRequest('ql_vai_tro_ids')) : [];

        $this->Ql_nguoi_dung_model->deletePivotRoleUser($userId);

        if (is_array($roleIds)) {
            foreach ($roleIds as $roleId) {
                $this->Ql_nguoi_dung_model->insertRoleUser($userId, $roleId);
            }
        }



        $this->createLog(
            'set_roles',
            'Cập nhật vai trò cho người dùng',
            [
                'ql_nguoi_dung' => $this->Ql_nguoi_dung_model->find($userId),
                'roles' => !empty($rIds) ? $this->Ql_vai_tro_model->whereIn('ql_vai_tro_id', $rIds)->get() : []
            ],
            [
                'ql_nguoi_dung' => $this->Ql_nguoi_dung_model->find($userId),
                'roles' => !empty($roleIds) ? $this->Ql_vai_tro_model->whereIn('ql_vai_tro_id', $roleIds)->get() : []
            ],
            'ql_vai_tro_nguoi_dung'
        );



        //Xóa toàn bộ token của user đó
        $this->load->model('Ql_personal_access_token_model');
        $this->Ql_personal_access_token_model->where('ql_nguoi_dung_id', $userId)->delete();

        $this->response([

            'status' => REST_INSTANCE_Controller::HTTP_OK,

            'message' => 'Thành công',

            'success' => true,

            'data' => $this->Ql_nguoi_dung_model->getUsersRoles(10, 0, $userId)

        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function get_lanhdao_get()
    {
        $users = $this->Ql_nguoi_dung_model->where('ql_nguoi_dung_la_lanh_dao', 1)->get();
        resSuccess($users);
    }
}
