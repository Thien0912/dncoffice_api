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
        $this->permissionMiddleware();
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

                'message' => 'Create user successful.',

                'success' => true,

                'data' => $user

            ], REST_INSTANCE_Controller::HTTP_CREATED);

            $this->response([

                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,

                'message' => 'Create user failed',

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

                'message' => 'User not found',

                'success' => false,

                'data' => null

            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }
        $this->response([

            'status' => REST_INSTANCE_Controller::HTTP_OK,

            'message' => 'Success',

            'success' => true,

            'data' => $user

        ], REST_INSTANCE_Controller::HTTP_OK);
    }
    //update user
    public function update_put($id)
    {
        $this->permissionMiddleware();
        try {
            $ten = commonRequest('ql_nguoi_dung_ho_ten') ? commonRequest('ql_nguoi_dung_ho_ten') : null;
            $email = commonRequest('ql_nguoi_dung_email') ? commonRequest('ql_nguoi_dung_email') : null;
            $id_don_vi = commonRequest('id_don_vi') ? commonRequest('id_don_vi') : null;

            if (!$ten) {
                resError('Tên người dùng bắt buộc nhập');
            }
            if (!$email) {
                resError('Email người dùng bắt buộc nhập');
            }

            if (!$id_don_vi) {
                resError('Đơn vị bắt buộc');
            }

            if ($this->Ql_nguoi_dung_model->where('ql_nguoi_dung_email', $email)->where('ql_nguoi_dung_id !=', $id)->first()) {
                resError('Email đã tồn tại');
            }

            $data = [
                'ql_nguoi_dung_ho_ten' => $ten,
                'ql_nguoi_dung_email' => $email,
                'ql_nguoi_dung_loai' => 2,
                'ql_nguoi_dung_la_lanh_dao' => commonRequest('ql_nguoi_dung_la_lanh_dao') ? commonRequest('ql_nguoi_dung_la_lanh_dao') : null,
                'id_don_vi' => $id_don_vi
            ];
            $findUser = $this->Ql_nguoi_dung_model->find($id);
            if (!$findUser || ($findUser && $findUser['ql_nguoi_dung_is_admin'] == 1)) {
                $this->response([

                    'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,

                    'message' => 'User not found',

                    'success' => false,

                    'data' => null

                ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
            }

            $user = $this->Ql_nguoi_dung_model->updateUser($id, $data);

            $this->createLog(
                'update',
                'Cập nhật người dùng',
                $findUser,
                $user,
                'ql_nguoi_dung'
            );

            $this->response([

                'status' => REST_INSTANCE_Controller::HTTP_OK,

                'message' => 'Update user successfully',

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

                    'message' => 'User not found',

                    'success' => false,

                    'data' => null

                ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
            //Xóa chính mình
            if ($this->getUserLogin()['ql_nguoi_dung_id'] == $id) {
                $this->response([

                    'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,

                    'message' => 'You cannot delete yourself',

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

                    'message' => 'Delete user successfully',

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

                'message' => 'User not found',

                'success' => false,

                'data' => null

            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }

        $user = $this->Ql_nguoi_dung_model->getUsersRoles(10, 0, $userId);
        $this->response([

            'status' => REST_INSTANCE_Controller::HTTP_OK,

            'message' => 'Succcess',

            'success' => true,

            'data' => $user

        ], REST_INSTANCE_Controller::HTTP_OK);
    }
    //set roles for user
    public function user_set_roles_put($userId)
    {
        $this->permissionMiddleware();
        $findUser = $this->Ql_nguoi_dung_model->find($userId);
        if (!$findUser || ($findUser && $findUser['ql_nguoi_dung_is_admin'] == 1)) {
            $this->response([

                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,

                'message' => 'User not found',

                'success' => false,

                'data' => null

            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
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

            'message' => 'Succcess',

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
