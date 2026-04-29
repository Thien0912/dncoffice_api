<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Ql_nguoi_dung_model extends MY_Model
{
    protected $table = 'ql_nguoi_dung';
    protected $primaryKey = 'ql_nguoi_dung_id';
    protected $hidden = ['ql_nguoi_dung_mat_khau'];
    protected $timestamps = true;
    protected $createdAtField = 'ql_nguoi_dung_ngay_tao';
    protected $updatedAtField = 'ql_nguoi_dung_ngay_cap_nhat';



    public function __construct()
    {
        parent::__construct();
        $this->load->model('Ql_vai_tro_model');
    }

    public function verifyPassword($userId, $password)
    {
        $user = $this->db->where('ql_nguoi_dung_id', $userId)->get('ql_nguoi_dung')->row_array();
        if (!$user) return false;
        return password_verify($password, $user['ql_nguoi_dung_mat_khau']);
    }

    public function resetPassword($userId, $old_password, $new_password, $confirm_password)
    {
        $user = $this->db->where('ql_nguoi_dung_id', $userId)->get('ql_nguoi_dung')->row_array();
        if (!$user) {
            $response = [
                'status' => 404,
                'message' => 'User not found',
                'success' => false,
            ];
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
        if (!password_verify($old_password, $user['ql_nguoi_dung_mat_khau'])) {
            $response = [
                'status' => 500,
                'message' => 'The old password is incorrect',
                'success' => false,
            ];
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
        if ($new_password !== $confirm_password) {
            $response = [
                'status' => 500,
                'message' => 'The confirm password is incorrect',
                'success' => false,
            ];
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }

        $this->db
            ->where('ql_nguoi_dung_id', $userId)
            ->update('ql_nguoi_dung', ['ql_nguoi_dung_mat_khau' => password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12])]);
    }

    public function getSearch()
    {
        $searchValue = commonRequest('searchValue');
        $ql_nguoi_dung_loai = commonRequest('ql_nguoi_dung_loai');
        $ql_vai_tro_id = commonRequest('ql_vai_tro_id');
        $id_don_vi = commonRequest('id_don_vi') ? commonRequest('id_don_vi') : null;
        $created_at = commonRequest('created_at');

        $active_flag = commonRequest('active_flag');

        $draw = commonRequest('draw');

        $start = $this->input->get('start') ?? 0;
        $length = $this->input->get('length') ?? 10;
        $dataFilter = commonRequest('dataFilter');


        // $this->db
        //     ->from('ql_nguoi_dung')
        //     ->join('ql_vai_tro_nguoi_dung', 'ql_vai_tro_nguoi_dung.ql_nguoi_dung_id = ql_nguoi_dung.ql_nguoi_dung_id', 'left')
        //     ->join('ql_vai_tro', 'ql_vai_tro.ql_vai_tro_id = ql_vai_tro_nguoi_dung.ql_vai_tro_id', 'left');


        $this->db
            ->select('ql_nguoi_dung.*, e_don_vi.ten_don_vi, GROUP_CONCAT(ql_vai_tro.ql_vai_tro_ten SEPARATOR ", ") as vai_tro, GROUP_CONCAT(ql_vai_tro.ql_vai_tro_id) as role_ids')
            ->from('ql_nguoi_dung')
            ->join('ql_vai_tro_nguoi_dung', 'ql_vai_tro_nguoi_dung.ql_nguoi_dung_id = ql_nguoi_dung.ql_nguoi_dung_id', 'left')
            ->join('ql_vai_tro', 'ql_vai_tro.ql_vai_tro_id = ql_vai_tro_nguoi_dung.ql_vai_tro_id', 'left')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = ql_nguoi_dung.id_don_vi', 'left')
            ->join('hrm_nhan_vien', 'hrm_nhan_vien.ql_nguoi_dung_id = ql_nguoi_dung.ql_nguoi_dung_id', 'left')
            ->join('hrm_nhan_vien_cong_viec', 'hrm_nhan_vien_cong_viec.id_nhan_vien = hrm_nhan_vien.id_nhan_vien', 'left')
            // ->where('ql_nguoi_dung.active_flag', 1) // Removed hardcoded active_flag
            ->group_by('ql_nguoi_dung.ql_nguoi_dung_id');


        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);


        if ($searchValue) {
            // $this->db->where("LOWER(CONCAT(IFNULL(ql_nguoi_dung_ho, ''), IFNULL(ql_nguoi_dung_ten_dem, ''), IFNULL(ql_nguoi_dung_ten, ''))) LIKE", strtolower("%$ql_nguoi_dung_ho_ten%"));
            $this->db->group_start();
            $this->db->like('ql_nguoi_dung.ql_nguoi_dung_ho_ten', $searchValue);
            $this->db->or_like('ql_nguoi_dung.ql_nguoi_dung_email', $searchValue);
            $this->db->or_like('e_don_vi.ten_don_vi', $searchValue);
            $this->db->group_end();
        }

        if ($ql_nguoi_dung_loai)
            $this->db->where('ql_nguoi_dung.ql_nguoi_dung_loai', $ql_nguoi_dung_loai);

        if ($ql_vai_tro_id) {
            $this->db->where("ql_nguoi_dung.ql_nguoi_dung_id IN (
                    SELECT ql_vai_tro_nguoi_dung.ql_nguoi_dung_id 
                    FROM ql_vai_tro_nguoi_dung 
                    WHERE ql_vai_tro_nguoi_dung.ql_vai_tro_id = $ql_vai_tro_id
                )");
        }

        if ($id_don_vi) {
            $this->db->where('ql_nguoi_dung.id_don_vi', $id_don_vi);
        }

        $exclude_ql_vai_tro_id = commonRequest('exclude_ql_vai_tro_id');
        if ($exclude_ql_vai_tro_id) {
            $this->db->where("ql_nguoi_dung.ql_nguoi_dung_id NOT IN (
                    SELECT ql_vai_tro_nguoi_dung.ql_nguoi_dung_id 
                    FROM ql_vai_tro_nguoi_dung 
                    WHERE ql_vai_tro_nguoi_dung.ql_vai_tro_id = $exclude_ql_vai_tro_id
                )");
        }

        if ($active_flag !== null && $active_flag !== '') {
            $this->db->where('ql_nguoi_dung.active_flag', $active_flag);
            if ($active_flag == 1) {
                $this->db->group_start();
                $this->db->where('hrm_nhan_vien_cong_viec.trang_thai IS NULL');
                $this->db->or_where('hrm_nhan_vien_cong_viec.trang_thai !=', 'NGHI_VIEC');
                $this->db->group_end();
            }
        }

        if ($created_at) {
            $this->db->where('ql_nguoi_dung.created_at >=', "{$created_at} 00:00:00");
            $this->db->where('ql_nguoi_dung.created_at <=', "{$created_at} 23:59:59");
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query 

        $columns = [
            'ql_nguoi_dung.ql_nguoi_dung_ho_ten',
            'ql_nguoi_dung.ql_nguoi_dung_email',
            'ql_nguoi_dung.ql_nguoi_dung_loai'
        ];


        if (commonRequest('order')) {
            $order = json_decode(commonRequest('order'), true);

            if (isset($order[0]['column']) && isset($order[0]['dir'])) {
                $orderColumnVal = $order[0]['column'];
                $orderDir = $order[0]['dir'];

                $orderColumn = null;
                if (is_numeric($orderColumnVal)) {
                    $orderColumn = isset($columns[$orderColumnVal]) ? $columns[$orderColumnVal] : null;
                } else {
                    $orderColumn = $orderColumnVal;
                }

                if (!empty($orderColumn) && !empty($orderDir)) {
                    // Prevent SQL injection if passing string directly by verifying against allowed column list or regex if needed.
                    // For now, trusted internal usage or basic validation.
                    // Basic whitelist or check if it matches a known alias/field could be added here for security.
                    $this->db->order_by($orderColumn, $orderDir);
                }
            }
        }


        if ($length != '-1')
            $this->db->limit($length, $start);

        // $this->db->where('ql_nguoi_dung.active_flag', 1); // Removed hardcoded active_flag
        $query = $this->db->get();


        $data = $query->result_array();


        if ($dataFilter) {
            $this->load->model('Ql_vai_tro_model');
            $filter = [
                'ql_vai_tro' => $this->Ql_vai_tro_model->select('ql_vai_tro_id, ql_vai_tro_ten ')->get(),
                'e_don_vi' => $this->db->get('e_don_vi')->result_array()
            ];
            return [
                'draw' => $this->input->get('draw'),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data,
                'dataFilter' => $filter
            ];
        }


        return [
            'draw' => $this->input->get('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }

    //get list roles of user
    public function getRolesByUserId($userId)
    {
        return $this->Ql_vai_tro_model->join('ql_vai_tro_nguoi_dung', 'ql_vai_tro_nguoi_dung.ql_vai_tro_id = ql_vai_tro.ql_vai_tro_id')
            ->where('ql_vai_tro_nguoi_dung.ql_nguoi_dung_id', $userId)->get();
    }

    //if $userId null then get list with $limit and $offset, if not null then get 1 record with userId
    public function getUsersRoles($limit = 10, $offset = 0, $userId = null)
    {
        if (!$userId) {
            $users = $this->paginate($limit, $offset);

            foreach ($users as $key => $user) {
                $users[$key]['ql_vai_tro'] = $this->getRolesByUserId($user['ql_nguoi_dung_id']);
            }
            return $users;
        }
        $user = $this->find($userId);
        if (!$user) {
            $response = [
                'status' => 404,
                'message' => 'User not found',
                'success' => false,
                'data' => null
            ];
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
        $user['ql_vai_tro'] = $this->getRolesByUserId($user['ql_nguoi_dung_id']);
        return $user;
    }

    //check email exist, if $userId not null then check email orther user. if $userId null then check all users
    public function emailExist($email, $userId = null): bool
    {
        if (!$userId) {
            return $this->where('ql_nguoi_dung_email', $email)->first() ? true : false;
        }
        return $this->where('ql_nguoi_dung_email', $email)->where('ql_nguoi_dung_id !=', $userId)->first() ? true : false;
    }
    //insert pivot table
    public function insertRoleUser($userId, $roleId)
    {
        $this->db->insert('ql_vai_tro_nguoi_dung', [
            'ql_nguoi_dung_id' => $userId,
            'ql_vai_tro_id' => $roleId
        ]);
    }

    public function deleteRoleUser($userId, $roleId)
    {
        $this->db->delete('ql_vai_tro_nguoi_dung', ['ql_nguoi_dung_id' => $userId, 'ql_vai_tro_id' => $roleId]);
    }

    //delete pivot table
    public function deletePivotRoleUser($userId)
    {
        $this->db->delete('ql_vai_tro_nguoi_dung', ['ql_nguoi_dung_id' => $userId]);
    }
    public function storeUser($data)
    {
        if (is_array($data) && isset($data['ql_nguoi_dung_email'])) {
            if ($this->emailExist($data['ql_nguoi_dung_email'])) { //validate email
                $response = [
                    'status' => 400,
                    'message' => 'Email already exists',
                    'success' => false,
                    'data' => null
                ];
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            }
            $user = $this->create($data); //return row_array user

            return $user;
        }
        return null;
    }

    public function updateUser($userId, $data)
    {
        if (is_array($data)) {
            if (isset($data['ql_nguoi_dung_email'])) {
                if ($this->emailExist($data['ql_nguoi_dung_email'], $userId)) {
                    $response = [
                        'status' => 400,
                        'message' => 'Email already exists',
                        'success' => false,
                        'data' => null
                    ];
                    http_response_code(400);
                    header('Content-Type: application/json');
                    echo json_encode($response);
                    exit;
                }
            }
            $this->where('ql_nguoi_dung_id', $userId)->update($data);
            return $this->find($userId);
        }
        return null;
    }


    public function uploadImage($field)
    {
        //upload avatar
        $config['upload_path'] = './uploads/images/users/images';
        $config['allowed_types'] = 'gif|jpg|png';
        $config['max_size'] = 2048;
        // $config['max_width'] = 1024;
        // $config['max_height'] = 768;

        $this->load->library('upload', $config);
        if (!file_exists($config['upload_path'])) {
            mkdir($config['upload_path'], 0777, true);
        }
        if (!$this->upload->do_upload($field)) {
            // $error = array('error' => $this->upload->display_errors());
            return false;
        } else {
            $dataUpload = array('upload_data' => $this->upload->data());
            return [
                'data' => $dataUpload,
                'file_path' => $config['upload_path'] . '/' . $dataUpload['upload_data']['file_name']
            ];
        }
    }

    public function updateImage($ql_nguoi_dung_id, $avatar)
    {
        $this->db->where('ql_nguoi_dung_id', $ql_nguoi_dung_id)
            ->update('ql_nguoi_dung', $avatar);
        return true;
    }

    public function getRoleUsersByUserId($userId)
    {
        return $this->db->where('ql_nguoi_dung_id', $userId)->get('ql_vai_tro_nguoi_dung')->result_array();
    }


    public function getAll($start = 0, $length = 10, $searchValue = null, $order = [], $searchKey = array())
    {
        $user_type = commonRequest('user_type') ? commonRequest('user_type') : null;
        $this->db->from('ql_nguoi_dung')->select('ql_nguoi_dung.ql_nguoi_dung_id, ql_nguoi_dung.ql_nguoi_dung_ho_ten, ql_nguoi_dung.ql_nguoi_dung_email, ql_nguoi_dung.ql_nguoi_dung_la_lanh_dao, ql_nguoi_dung.id_don_vi, hrm_nhan_vien.trinh_do_dt, ql_nguoi_dung.do_uu_tien_lanh_dao, ql_nguoi_dung.active_flag')
            ->join('hrm_nhan_vien', 'hrm_nhan_vien.ql_nguoi_dung_id = ql_nguoi_dung.ql_nguoi_dung_id', 'left')
            ->where('active_flag', 1);
        // $totalRecordsQuery = clone $this->db;
        // $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        // if ($searchValue) {
        //     $this->db->group_start();
        //     $this->db->group_end();
        // }

        // $filteredQuery = clone $this->db;
        // $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query 

        // $columns = [
        //     '',
        //     '',
        //     'ten_bao_mat'
        // ];

        // if (!empty($order)) {
        //     $orderColumnIndex = $order[0]['column'];
        //     $orderColumn = $columns[$orderColumnIndex];  // index starts at 0
        //     $orderDir = $order[0]['dir'];

        //     if (!empty($orderColumn) && !empty($orderDir)) {
        //         $this->db->order_by($orderColumn, $orderDir);
        //     }
        // }
        if ($user_type == 'lanh_dao') {
            $this->db->where('ql_nguoi_dung_la_lanh_dao', 1);
        }
        if ($length != '-1')
            $this->db->limit($length, $start);

        $query = $this->db->get();
        $data = $query->result_array();

        return [
            // 'recordsTotal' => $recordsTotal,
            // 'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }
    /**
     * Lấy danh sách ID người dùng thuộc các đơn vị có vai trò cụ thể
     * @param array $unitIds Danh sách ID đơn vị
     * @param string $role_code Tên vai trò (ví dụ: 'LANH_DAO, VAN_THU')
     * @return array Danh sách ID người dùng
     */
    public function getUserIdsByUnitAndRole($unitIds, $role_code = null)
    {
        if (empty($unitIds)) {
            return [];
        }

        $query = $this->db
            ->select('ql_nguoi_dung.ql_nguoi_dung_id')
            ->from('ql_nguoi_dung')
            ->where('ql_nguoi_dung.active_flag', 1)
            ->where_in('ql_nguoi_dung.id_don_vi', $unitIds);

        if (!empty($role_code)) {
            $query->join('ql_vai_tro_nguoi_dung', 'ql_vai_tro_nguoi_dung.ql_nguoi_dung_id = ql_nguoi_dung.ql_nguoi_dung_id')
                ->join('ql_vai_tro', 'ql_vai_tro.ql_vai_tro_id = ql_vai_tro_nguoi_dung.ql_vai_tro_id');
            
            if (is_array($role_code)) {
                $query->where_in('ql_vai_tro.ql_ma_vai_tro', $role_code);
            } else {
                $query->where('ql_vai_tro.ql_ma_vai_tro', $role_code);
            }
        }

        $users = $query->get()->result_array();

        return array_unique(array_column($users, 'ql_nguoi_dung_id'));
    }
}
