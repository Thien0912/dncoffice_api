<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Ql_vai_tro_model extends MY_Model
{
    protected $table = 'ql_vai_tro';
    protected $primaryKey = 'ql_vai_tro_id';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Ql_quyen_model');
    }



    public function getSearch()
    {
        $ql_vai_tro_ten = commonRequest('ql_vai_tro_ten');
        $created_at = commonRequest('created_at');

        $draw = commonRequest('draw');

        $start = $this->input->get('start') ?? 0;
        $length = $this->input->get('length') ?? 10;

        $recordsTotal = $this->db->count_all('ql_vai_tro');

        if ($ql_vai_tro_ten)
            $this->db->like('ql_vai_tro_ten', $ql_vai_tro_ten);


        if ($created_at) {
            $this->db->where('created_at >=', "{$created_at} 00:00:00");
            $this->db->where('created_at <=', "{$created_at} 23:59:59");
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('ql_vai_tro', FALSE); // FALSE để không reset query

        $columns = [
            '',
            'ql_vai_tro_ten',
            'ql_vai_tro_mo_ta'
        ];


        if (commonRequest('order')) {
            $order = json_decode(commonRequest('order'), true);


            $orderColumnIndex = $order[0]['column'];
            $orderColumn = $columns[$orderColumnIndex];  // index starts at 0
            $orderDir = $order[0]['dir'];

            if (!empty($orderColumn) && !empty($orderDir)) {
                $this->db->order_by($orderColumn, $orderDir);
            }
        }



        $this->db->select('ql_vai_tro.*, (SELECT COUNT(*) FROM ql_vai_tro_nguoi_dung 
            JOIN ql_nguoi_dung ON ql_vai_tro_nguoi_dung.ql_nguoi_dung_id = ql_nguoi_dung.ql_nguoi_dung_id 
            LEFT JOIN hrm_nhan_vien ON hrm_nhan_vien.ql_nguoi_dung_id = ql_nguoi_dung.ql_nguoi_dung_id 
            LEFT JOIN hrm_nhan_vien_cong_viec ON hrm_nhan_vien_cong_viec.id_nhan_vien = hrm_nhan_vien.id_nhan_vien 
            WHERE ql_vai_tro_nguoi_dung.ql_vai_tro_id = ql_vai_tro.ql_vai_tro_id 
            AND ql_nguoi_dung.active_flag = 1 
            AND (hrm_nhan_vien_cong_viec.trang_thai IS NULL OR hrm_nhan_vien_cong_viec.trang_thai != "NGHI_VIEC")
        ) as total_members');
        $this->db->limit($length, $start);
        $query = $this->db->get('ql_vai_tro');

        $data = $query->result_array();

        return [
            'draw' => $this->input->get('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }

    public function getRoles($roleId = null)
    {
        if (!$roleId) {
            $roles = $this->get();
            foreach ($roles as $index => $role) {
                $roles[$index]['ql_quyen'] = $this->Ql_quyen_model->getPermissionsByRole($role['ql_vai_tro_id']);
            }
            return $roles;
        } else {
            $role = $this->find($roleId);
            if (!$role) {
                $response = [
                    'status' => 404,
                    'message' => 'Role not found',
                    'success' => false,
                    'data' => null
                ];
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            }
            $role['ql_quyen'] = $this->Ql_quyen_model->getPermissionsByRole($roleId);
            return $role;
        }
    }

    public function getRolePermissionsByRoleId($roleId)
    {
        return $this->db->where('ql_vai_tro_id', $roleId)->get('ql_vai_tro_quyen')->result_array();
    }

    public function insertRolePermission($roleId, $permissionId)
    {
        $this->db->insert('ql_vai_tro_quyen', [
            'ql_vai_tro_id' => $roleId,
            'ql_quyen_id' => $permissionId
        ]);
        return $this->db->insert_id();
    }

    public function deleteRolePermission($roleId, $permissionId)
    {
        $this->db->delete('ql_vai_tro_quyen', [
            'ql_vai_tro_id' => $roleId,
            'ql_quyen_id' => $permissionId
        ]);
        return $this->db->affected_rows();
    }

    public function deletePivotRoleUser($roleId)
    {
        $this->db->delete('ql_vai_tro_nguoi_dung', ['ql_vai_tro_id' => $roleId]);
    }
    public function deletePivotRolePermission($roleId)
    {
        $this->db->delete('ql_vai_tro_quyen', ['ql_vai_tro_id' => $roleId]);
    }


    public function syncPermissions($roleId, $permissionIds)
    {
        $this->db->trans_begin();

        try {
            // Lấy các quyền hiện tại của role
            $this->db->select('ql_quyen_id');
            $this->db->where('ql_vai_tro_id', $roleId);
            $query = $this->db->get('ql_vai_tro_quyen');
            $current_permissions = array_column($query->result_array(), 'ql_quyen_id');

            // Quyền cần xóa
            $permissions_to_delete = array_diff($current_permissions, $permissionIds);
            if (!empty($permissions_to_delete)) {
                $this->db->where('ql_vai_tro_id', $roleId);
                $this->db->where_in('ql_quyen_id', $permissions_to_delete);
                $this->db->delete('ql_vai_tro_quyen');
            }

            // Quyền cần thêm
            $permissions_to_add = array_diff($permissionIds, $current_permissions);
            if (!empty($permissions_to_add)) {
                $data = array();
                foreach ($permissions_to_add as $permission_id) {
                    $data[] = array('ql_vai_tro_id' => $roleId, 'ql_quyen_id' => $permission_id);
                }
                $this->db->insert_batch('ql_vai_tro_quyen', $data);
            }

            // Commit giao dịch
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                throw new Exception("Error updating role permissions");
            } else {
                $this->db->trans_commit();
            }
        } catch (Exception $e) {
            $this->db->trans_rollback();
            $response = [
                'status' => 500,
                'message' => $e->getMessage(),
                'success' => false,
                'data' => null
            ];
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }

    public function getPermissionsWithChildren($roleId, $mapChildren = false)
    {
        $permissions = $this->Ql_quyen_model->join('ql_vai_tro_quyen', 'ql_vai_tro_quyen.ql_quyen_id = ql_quyen.ql_quyen_id')
            ->join('ql_vai_tro', 'ql_vai_tro.ql_vai_tro_id = ql_vai_tro_quyen.ql_vai_tro_id')
            ->where('ql_vai_tro_quyen.ql_vai_tro_id', $roleId)
            ->select('ql_quyen.*')
            ->get();

        if ($mapChildren) {
            $permissions = array_map(function ($item) {
                $item['ql_quyen_children'] = $this->Ql_quyen_model->where('ql_quyen_parent_id', $item['ql_quyen_id'])->get();
                return $item;
            }, $permissions);
        }
        return $permissions;
    }

    /**
     * Lấy quyền của sinh viên để cấp quyền (mặc định sinh viên là )
     * 
     */
    public function getStudentRole()
    {
        $id = $this->db->select('ql_vai_tro_id')
            ->where('ql_vai_tro_loai_nguoi_dung', 'sinhvien')
            ->from('ql_vai_tro')
            ->get()->row_array();

        return !empty($id) ? $id['ql_vai_tro_id'] : null;
    }

    public function getRoleUsersByRoleId($roleId)
    {
        return $this->db->where('ql_vai_tro_id', $roleId)->get('ql_vai_tro_nguoi_dung')->result_array();
    }

    public function checkAssignUser($roleId, $userId)
    {
        return $this->db->where('ql_vai_tro_id', $roleId)
            ->where('ql_nguoi_dung_id', $userId)
            ->count_all_results('ql_vai_tro_nguoi_dung') > 0;
    }

    public function assignUser($roleId, $userIds, $userLoginId = null)
    {
        foreach ($userIds as $userId) {
            if (!$this->checkAssignUser($roleId, $userId)) {
                $this->db->insert('ql_vai_tro_nguoi_dung', [
                    'ql_vai_tro_id' => $roleId,
                    'ql_nguoi_dung_id' => $userId,
                    'created_user_id' => $userLoginId,
                    'updated_user_id' => $userLoginId
                ]);
            }
        }

        // $this->db->insert('ql_vai_tro_nguoi_dung', [
        //     'ql_vai_tro_id' => $roleId,
        //     'ql_nguoi_dung_id' => $userId,
        //     'created_user_id' => $userLoginId,
        //     'updated_user_id' => $userLoginId
        // ]);
    }

    // public function storeRole($data, $permissionIds)
    // {
    //     $role = $this->create($data);
    //     if ($role && is_array($permissionIds)) {
    //         foreach ($permissionIds as $permissionId) {
    //             $this->insertRolePermission($role['ql_vai_tro_id'], $permissionId);
    //         }
    //     }
    //     $role['ql_quyen'] = $this->Ql_quyen_model->getPermissionsByRole($role['ql_vai_tro_id']);
    //     return $role;
    // }
    // public function updateRole($roleId, $data, $permissionIds)
    // {
    //     $role = $this->getRoles($roleId);
    //     $this->deletePivotRolePermission($roleId);
    //     if ($this->update($roleId, $data)) {
    //         foreach ($permissionIds as $permissionId) {
    //             $this->insertRolePermission($roleId, $permissionId);
    //         }
    //     }
    //     return $this->getRoles($roleId);
    // }

    public function getMaVaiTro($ql_nguoi_dung_id){
        $this->db->select('ql_vai_tro.ql_vai_tro_id, ql_vai_tro.ql_vai_tro_ten, ql_vai_tro.ql_ma_vai_tro');
        $this->db->from('ql_vai_tro_nguoi_dung');
        $this->db->join('ql_vai_tro', 'ql_vai_tro.ql_vai_tro_id = ql_vai_tro_nguoi_dung.ql_vai_tro_id');
        $this->db->where('ql_vai_tro_nguoi_dung.ql_nguoi_dung_id', $ql_nguoi_dung_id);
        $this->db->where('ql_vai_tro_nguoi_dung.is_active', 1);
        return $this->db->get()->row_array();
    }

    /**
     * Lấy danh sách khóa quyền (ql_quyen_khoa) của user
     * @param int $ql_nguoi_dung_id
     * @return array ['ngoaigio.xemtatca', 'ngoaigio.them', 'nghiphep.taoho', ...]
     */
    public function getUserPermissionKeys($ql_nguoi_dung_id)
    {
        if (empty($ql_nguoi_dung_id)) {
            return [];
        }

        $this->db->select('ql_quyen.ql_quyen_khoa');
        $this->db->from('ql_vai_tro_nguoi_dung');
        $this->db->join('ql_vai_tro_quyen', 'ql_vai_tro_quyen.ql_vai_tro_id = ql_vai_tro_nguoi_dung.ql_vai_tro_id');
        $this->db->join('ql_quyen', 'ql_quyen.ql_quyen_id = ql_vai_tro_quyen.ql_quyen_id');
        $this->db->where('ql_vai_tro_nguoi_dung.ql_nguoi_dung_id', $ql_nguoi_dung_id);
        $this->db->where('ql_vai_tro_nguoi_dung.is_active', 1);
        $this->db->group_by('ql_quyen.ql_quyen_khoa');
        
        $result = $this->db->get()->result_array();
        return array_column($result, 'ql_quyen_khoa');
    }

    /**
     * Kiểm tra user có quyền cụ thể hay không
     * @param int $ql_nguoi_dung_id
     * @param string $permissionKey - Khóa quyền (VD: 'ngoaigio.xemtatca', 'nghiphep.taoho')
     * @return bool
     */
    public function hasPermission($ql_nguoi_dung_id, $permissionKey)
    {
        if (empty($ql_nguoi_dung_id) || empty($permissionKey)) {
            return false;
        }

        $count = $this->db
            ->from('ql_vai_tro_nguoi_dung')
            ->join('ql_vai_tro_quyen', 'ql_vai_tro_quyen.ql_vai_tro_id = ql_vai_tro_nguoi_dung.ql_vai_tro_id')
            ->join('ql_quyen', 'ql_quyen.ql_quyen_id = ql_vai_tro_quyen.ql_quyen_id')
            ->where('ql_vai_tro_nguoi_dung.ql_nguoi_dung_id', $ql_nguoi_dung_id)
            ->where('ql_vai_tro_nguoi_dung.is_active', 1)
            ->where('ql_quyen.ql_quyen_khoa', $permissionKey)
            ->count_all_results();

        return $count > 0;
    }

    /**
     * Kiểm tra user có ít nhất 1 trong các quyền được cung cấp
     * @param int $ql_nguoi_dung_id
     * @param array $permissionKeys - Mảng khóa quyền ['ngoaigio.xemtatca', 'nghiphep.taoho', ...]
     * @return bool
     */
    public function hasAnyPermission($ql_nguoi_dung_id, $permissionKeys)
    {
        if (empty($ql_nguoi_dung_id) || empty($permissionKeys)) {
            return false;
        }

        $count = $this->db
            ->from('ql_vai_tro_nguoi_dung')
            ->join('ql_vai_tro_quyen', 'ql_vai_tro_quyen.ql_vai_tro_id = ql_vai_tro_nguoi_dung.ql_vai_tro_id')
            ->join('ql_quyen', 'ql_quyen.ql_quyen_id = ql_vai_tro_quyen.ql_quyen_id')
            ->where('ql_vai_tro_nguoi_dung.ql_nguoi_dung_id', $ql_nguoi_dung_id)
            ->where('ql_vai_tro_nguoi_dung.is_active', 1)
            ->where_in('ql_quyen.ql_quyen_khoa', $permissionKeys)
            ->count_all_results();

        return $count > 0;
    }

}
