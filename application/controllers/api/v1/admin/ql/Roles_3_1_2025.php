<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 * @property Ql_quyen_model $Ql_quyen_model
 * @property CI_Input $input
 * @property CI_Upload $upload
 * @property CI_DB_query_builder $db
 * @property Ql_vai_tro_nguoi_dung_model $Ql_vai_tro_nguoi_dung_model
 * @property Ql_personal_access_token_model $Ql_personal_access_token_model
 */
class Roles extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Ql_vai_tro_model');
        // $this->permissionMiddleware(); // check permissions access
    }
    //if parameter getRoles is null then it get information from token
    public function index_get()
    {
        $this->permissionMiddleware();
        $roles = $this->Ql_vai_tro_model->getSearch();

        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => $roles
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    //Lấy tất cả quyền
    public function permissions_get()
    {
        $this->load->model('Ql_quyen_model');
        resSuccess($this->Ql_quyen_model->get());
    }
    public function create_post()
    {
        $this->permissionMiddleware();
        try {
            $data = [
                'ql_vai_tro_ten' => commonRequest('ql_vai_tro_ten'),
                'ql_vai_tro_mo_ta' => commonRequest('ql_vai_tro_mo_ta')
            ];
            if (!commonRequest('ql_vai_tro_ten')) {
                $this->response([

                    'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,

                    'message' => 'Vui lòng nhập Tên vai trò',

                    'success' => false,

                    'data' => null

                ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
            $this->db->trans_start();
            $role = $this->Ql_vai_tro_model->create($data);

            $this->createLog(
                'create',
                'Tạo mới vai trò',
                null,
                $role,
                'ql_vai_tro'
            );

            //Quyền vai trò
            $permissionIds = commonRequest('ql_quyen_ids') ? commonRequest('ql_quyen_ids')  : [];
            foreach ($permissionIds as $permissionId) {
                $this->Ql_vai_tro_model->insertRolePermission($role['ql_vai_tro_id'], $permissionId);
            }



            $this->createLog(
                'set_permissions',
                'Phân quyền cho vai trò',
                null,
                [
                    'ql_vai_tro' => $role,
                    'permissions' => !empty($permissionIds) ? $this->Ql_quyen_model->whereIn('ql_quyen_id', $permissionIds)->select('ql_quyen_id, ql_quyen_ten, ql_quyen_khoa')->get() : []
                ],
                'ql_vai_tro_quyen'
            );


            $this->db->trans_commit();

            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_CREATED,
                'message' => 'Tạo vai trò thành công',
                'success' => true,
                'data' => $role
            ], REST_INSTANCE_Controller::HTTP_CREATED);
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
    //show 
    public function show_get($roleId)
    {
        $this->permissionMiddleware();
        $role = $this->Ql_vai_tro_model->find($roleId);
        if (!$role) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                'message' => 'Role not found',
                'success' => false,
                'data' => null
            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }
        $this->load->model('Ql_vai_tro_nguoi_dung_model');

        $roleUsers = $this->Ql_vai_tro_nguoi_dung_model->where('ql_vai_tro_id', $roleId)->get();

        $userIds = array_column($roleUsers, 'ql_nguoi_dung_id');
        // dd($userIds);
        $role['ql_quyen'] = $this->Ql_vai_tro_model->getPermissionsWithChildren($roleId);
        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_CREATED,
            'message' => 'Success',
            'success' => true,
            'data' => $role,
            'usersInRole' => !empty($userIds) ? $this->Ql_nguoi_dung_model->whereIn('ql_nguoi_dung_id', $userIds)
                ->select('ql_nguoi_dung_id, ql_nguoi_dung_ho_ten, ql_nguoi_dung_email, ql_nguoi_dung_avatar')
                ->get() : [],
            'usersNotInRole' => !empty($userIds) ? $this->Ql_nguoi_dung_model
                ->whereNotIn('ql_nguoi_dung_id', $userIds)
                ->where('ql_nguoi_dung_loai', self::USER_TYPE['staff'])
                ->select('ql_nguoi_dung_id, ql_nguoi_dung_ho_ten, ql_nguoi_dung_email, ql_nguoi_dung_avatar')
                ->get()
                : $this->Ql_nguoi_dung_model
                ->where('ql_nguoi_dung_loai', self::USER_TYPE['staff'])
                ->select('ql_nguoi_dung_id, ql_nguoi_dung_ho_ten, ql_nguoi_dung_email, ql_nguoi_dung_avatar')
                ->get(),
        ], REST_INSTANCE_Controller::HTTP_CREATED);
    }
    public function update_put($roleId)
    {
        $this->permissionMiddleware();
        try {
            $role = $this->Ql_vai_tro_model->find($roleId);
            if (!$role) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                    'message' => 'Không tìm thấy vai trò',
                    'success' => false,
                    'data' => null
                ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
            }
            if (!commonRequest('ql_vai_tro_ten')) {
                $this->response([

                    'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,

                    'message' => 'Vui lòng nhập Tên vai trò',

                    'success' => false,

                    'data' => null

                ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
            $data = [
                'ql_vai_tro_ten' => commonRequest('ql_vai_tro_ten'),
                'ql_vai_tro_mo_ta' => commonRequest('ql_vai_tro_mo_ta')
            ];
            $result = $this->Ql_vai_tro_model->where('ql_vai_tro_id', $roleId)->update($data);

            $this->createLog(
                'update',
                'Cập nhật vai trò',
                $role,
                $this->Ql_vai_tro_model->find($roleId),
                'ql_vai_tro'
            );


            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Cập nhật vai trò thành công',
                'success' => true,
                'data' => $result
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

    public function delete_delete($roleId)
    {
        $this->permissionMiddleware();
        try {

            $role = $this->Ql_vai_tro_model->find($roleId);
            if (!$role) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                    'message' => 'Role not found',
                    'success' => false,
                    'data' => null
                ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
            }

            //Check xem có người dùng trong vai trò thì không xóa được
            $roleUsers = $this->Ql_vai_tro_model->getRoleUsersByRoleId($roleId);
            if (count($roleUsers)) {
                resError('Không thể xóa, vai trò hiện đang có thành viên');
            }



            // $roleUsers = $this->Ql_vai_tro_model->getRoleUsersByRoleId($roleId);
            // $uIds = [];
            // foreach ($roleUsers as $ru) {
            //     $uIds[] = $ru['ql_nguoi_dung_id'];
            // }

            // $this->load->model('Ql_personal_access_token_model');
            // if ($uIds) {
            //     $this->Ql_personal_access_token_model->whereIn('ql_nguoi_dung_id', $uIds)->delete();
            // }


            // $this->Ql_nguoi_dung_model->deletePivotRoleUser($roleId);

            // $this->Ql_vai_tro_model->deletePivotRolePermission($roleId);

            $this->db->delete('ql_vai_tro_quyen', ['ql_vai_tro_id' => $roleId]);

            $this->Ql_vai_tro_model->where('ql_vai_tro_id', $roleId)->delete();

            $this->createLog(
                'delete',
                'Xóa vai trò',
                $role,
                null,
                'ql_vai_tro'
            );

            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,

                'message' => 'Delete role successfully',

                'success' => true,

                'data' => null

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

    public function role_permissions_get($roleId)
    {

        $this->load->model('Ql_quyen_model');
        $role = $this->Ql_vai_tro_model->find($roleId);

        if (!$role) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                'message' => 'Role not found',
                'success' => false,
                'data' => null
            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }

        $role['ql_quyen'] = $this->Ql_vai_tro_model->getPermissionsWithChildren($roleId);
        $this->load->model('Ql_quyen_model');
        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'success',
            'success' => true,
            'data' => $role,
            'permissions' => $this->Ql_quyen_model->get()
        ], REST_INSTANCE_Controller::HTTP_OK);
    }
    public function set_role_permissions_put($roleId)
    {
        $permissionIds = commonRequest('permission_ids') ? commonRequest('permission_ids') : [];
        $role = $this->Ql_vai_tro_model->find($roleId);
        if (!$role) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                'message' => 'Role not found',
                'success' => false,
                'data' => null
            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }






        $rolePermission = $this->Ql_vai_tro_model->getRolePermissionsByRoleId($roleId);
        $pIds = [];
        foreach ($rolePermission as $rp) {
            $pIds[] = $rp['ql_quyen_id'];
        }


        $this->Ql_vai_tro_model->syncPermissions($roleId, $permissionIds);

        $this->createLog(
            'set_permissions',
            'Cập nhật đặc quyền',
            [
                'ql_vai_tro' => $role,
                'permissions' => !empty($pIds) ? $this->Ql_quyen_model->whereIn('ql_quyen_id', $pIds)->select('ql_quyen_id, ql_quyen_ten, ql_quyen_khoa')->get() : []
            ],
            [
                'ql_vai_tro' => $role,
                'permissions' => !empty($permissionIds) ? $this->Ql_quyen_model->whereIn('ql_quyen_id', $permissionIds)->select('ql_quyen_id, ql_quyen_ten, ql_quyen_khoa')->get() : []
            ],
            'ql_vai_tro_quyen'
        );

        // logout user trong role đang set
        $roleUsers = $this->Ql_vai_tro_model->getRoleUsersByRoleId($roleId);

        $uIds = [];
        foreach ($roleUsers as $ru) {
            $uIds[] = $ru['ql_nguoi_dung_id'];
        }

        $this->load->model('Ql_personal_access_token_model');
        if (!empty($uIds))
            $this->Ql_personal_access_token_model->whereIn('ql_nguoi_dung_id', $uIds)->delete();
        //end logout

        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Cập nhật đặc quyền thành công',
            'success' => true,
            'data' => $permissionIds
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function add_user_post($roleId)
    {
        $this->permissionMiddleware();
        $this->load->model('Ql_personal_access_token_model');
        $userIds = commonRequest('user_ids');
        $role = $this->Ql_vai_tro_model->find($roleId);
        if (!$role) {
            resError('Vai trò không tồn tại');
        }
        if (empty($userIds)) {
            resError('Vui lòng chọn người dùng', REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        //Kiểm tra nếu có người dùng không tồn tại
        $this->load->model('Ql_nguoi_dung_model');
        $getUsers = $this->Ql_nguoi_dung_model->whereIn('ql_nguoi_dung_id', $userIds)->where('active_flag', 1)->get();

        if (count($getUsers) < count($userIds)) {
            resError('Có người dùng không tồn tại hoặc đã bị xóa', REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        $this->db->trans_begin();

        $oldRoleUsers = $this->db->from('ql_vai_tro_nguoi_dung')->where('ql_vai_tro_id', $roleId)->get()->result_array();
        $oldUserIds = array_column($oldRoleUsers, 'ql_nguoi_dung_id');

        $userLogin = $this->getUserLogin();
        $this->Ql_vai_tro_model->assignUser($roleId, $userIds, $userLogin['ql_nguoi_dung_id']);



        $newRoleUsers = $this->db->from('ql_vai_tro_nguoi_dung')->where('ql_vai_tro_id', $roleId)->get()->result_array();
        $newUserIds = array_column($newRoleUsers, 'ql_nguoi_dung_id');

        //create log
        $this->createLog(
            'add_user_role',
            'Thêm người dùng vào vai trò',
            [
                'ql_vai_tro' => $role,
                'users' => !empty($oldUserIds) ? $this->Ql_nguoi_dung_model
                    ->whereIn('ql_nguoi_dung_id', $oldUserIds)
                    ->select('ql_nguoi_dung_id, ql_nguoi_dung_ho_ten, ql_nguoi_dung_email')
                    ->get() : []
            ],
            [
                'ql_vai_tro' => $role,
                'users' => !empty($newUserIds) ? $this->Ql_nguoi_dung_model
                    ->whereIn('ql_nguoi_dung_id', $newUserIds)
                    ->select('ql_nguoi_dung_id, ql_nguoi_dung_ho_ten, ql_nguoi_dung_email')
                    ->get() : []
            ],
            'ql_vai_tro_nguoi_dung'
        );
        // Xóa token của các người vừa thêm vào
        $this->Ql_personal_access_token_model->deleteTokenByUserIds($userIds);
        $this->db->trans_commit();

        resSuccess('Thêm người dùng thành công');
    }

    public function remove_user_post($roleId)
    {
        $this->load->model('Ql_personal_access_token_model');
        $userIds = commonRequest('user_ids');
        $role = $this->Ql_vai_tro_model->find($roleId);
        if (!$role) {
            resError('Vai trò không tồn tại');
        }
        if (empty($userIds)) {
            resError('Vui lòng chọn người dùng', REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        //Kiểm tra nếu có người dùng không tồn tại
        $this->load->model('Ql_nguoi_dung_model');
        $getUsers = $this->Ql_nguoi_dung_model->whereIn('ql_nguoi_dung_id', $userIds)->where('active_flag', 1)->get();

        if (count($getUsers) < count($userIds)) {
            resError('Có người dùng không tồn tại hoặc đã bị xóa', REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        $this->db->trans_begin();

        $oldRoleUsers = $this->db->from('ql_vai_tro_nguoi_dung')->where('ql_vai_tro_id', $roleId)->get()->result_array();
        $oldUserIds = array_column($oldRoleUsers, 'ql_nguoi_dung_id');

        // $userLogin = $this->getUserLogin();
        // $this->Ql_vai_tro_model->assignUser($roleId, $userIds, $userLogin['ql_nguoi_dung_id']);
        foreach ($userIds as $uId) {
            $this->db->delete('ql_vai_tro_nguoi_dung', ['ql_nguoi_dung_id' => $uId, 'ql_vai_tro_id' => $roleId]);
        }




        $newRoleUsers = $this->db->from('ql_vai_tro_nguoi_dung')->where('ql_vai_tro_id', $roleId)->get()->result_array();
        $newUserIds = array_column($newRoleUsers, 'ql_nguoi_dung_id');

        //create log
        $this->createLog(
            'remove_user_role',
            'Xóa người dùng khỏi vai trò',
            [
                'ql_vai_tro' => $role,
                'users' => !empty($oldUserIds) ? $this->Ql_nguoi_dung_model
                    ->whereIn('ql_nguoi_dung_id', $oldUserIds)
                    ->select('ql_nguoi_dung_id, ql_nguoi_dung_ho_ten, ql_nguoi_dung_email')
                    ->get() : []
            ],
            [
                'ql_vai_tro' => $role,
                'users' => !empty($newUserIds) ? $this->Ql_nguoi_dung_model
                    ->whereIn('ql_nguoi_dung_id', $newUserIds)
                    ->select('ql_nguoi_dung_id, ql_nguoi_dung_ho_ten, ql_nguoi_dung_email')
                    ->get() : []
            ],
            'ql_vai_tro_nguoi_dung'
        );
        // Xóa token đăng nhập
        $this->Ql_personal_access_token_model->deleteTokenByUserIds($userIds);
        $this->db->trans_commit();

        resSuccess('Xóa người dùng thành công');
    }
}
