<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property Sys_login_logs_model $Sys_login_logs_model
 */
class Loginlogs extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Sys_login_logs_model');
    }

    public function index_get()
    {
        $this->permissionMiddleware();

        $page = $this->input->get('page') ?: 1;
        $per_page = $this->input->get('per_page') ?: 20;
        $offset = ($page - 1) * $per_page;

        $userId = $this->input->get('user_id');
        $search = $this->input->get('search'); // General search keyword
        // $filter = $this->input->get('filter'); // Add filter support if needed later

        $this->db->start_cache();

        // Base join
        $this->db->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = sys_login_logs.ql_nguoi_dung_id', 'left');

        if ($userId) {
            $this->db->where('sys_login_logs.ql_nguoi_dung_id', $userId);
        }

        if ($search) {
            $this->db->group_start();
            $this->db->like('sys_login_logs.ip_address', $search);
            $this->db->or_like('ql_nguoi_dung.ql_nguoi_dung_ho_ten', $search);
            $this->db->or_like('ql_nguoi_dung.ql_nguoi_dung_email', $search);
            $this->db->group_end();
        }

        // Date range filtering example (can be expanded)
        $startDate = $this->input->get('start_date');
        $endDate = $this->input->get('end_date');
        if ($startDate) {
            $this->db->where('sys_login_logs.created_at >=', $startDate . ' 00:00:00');
        }
        if ($endDate) {
            $this->db->where('sys_login_logs.created_at <=', $endDate . ' 23:59:59');
        }

        $this->db->stop_cache();

        $totalQuery = clone $this->db;
        $total = $totalQuery->count_all_results('sys_login_logs');

        $this->db->select('sys_login_logs.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten, ql_nguoi_dung.ql_nguoi_dung_email, ql_nguoi_dung.ql_nguoi_dung_avatar');
        $this->db->from('sys_login_logs');
        // Join is already in cache
        $this->db->order_by('sys_login_logs.created_at', 'DESC');
        $this->db->limit($per_page, $offset);

        $data = $this->db->get()->result_array();

        $this->db->flush_cache();

        // Process avatars
        foreach ($data as &$row) {
            if (!empty($row['ql_nguoi_dung_avatar']) && !filter_var($row['ql_nguoi_dung_avatar'], FILTER_VALIDATE_URL)) {
                $row['ql_nguoi_dung_avatar'] = encryptString($row['ql_nguoi_dung_avatar']);
            }
        }

        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => [
                'data' => $data,
                'total' => $total,
                'current_page' => (int)$page,
                'last_page' => ceil($total / $per_page)
            ]
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function statistics_get()
    {
        $this->permissionMiddleware();

        // Date range filtering
        $startDate = $this->input->get('start_date');
        $endDate = $this->input->get('end_date');

        // Tổng số lần đăng nhập
        $this->db->from('sys_login_logs');
        if ($startDate) {
            $this->db->where('created_at >=', $startDate . ' 00:00:00');
        }
        if ($endDate) {
            $this->db->where('created_at <=', $endDate . ' 23:59:59');
        }
        $totalLogins = $this->db->count_all_results();

        // Tổng số người đã truy cập (unique users)
        $this->db->select('COUNT(DISTINCT ql_nguoi_dung_id) as total_users');
        $this->db->from('sys_login_logs');
        if ($startDate) {
            $this->db->where('created_at >=', $startDate . ' 00:00:00');
        }
        if ($endDate) {
            $this->db->where('created_at <=', $endDate . ' 23:59:59');
        }
        $uniqueUsers = $this->db->get()->row()->total_users;

        // Thống kê theo đơn vị/khoa
        $this->db->select('dv.ten_don_vi as don_vi, COUNT(*) as login_count, COUNT(DISTINCT sys_login_logs.ql_nguoi_dung_id) as user_count');
        $this->db->from('sys_login_logs');
        $this->db->join('ql_nguoi_dung nd', 'nd.ql_nguoi_dung_id = sys_login_logs.ql_nguoi_dung_id', 'left');
        $this->db->join('e_don_vi dv', 'dv.id_don_vi = nd.id_don_vi', 'left');
        if ($startDate) {
            $this->db->where('sys_login_logs.created_at >=', $startDate . ' 00:00:00');
        }
        if ($endDate) {
            $this->db->where('sys_login_logs.created_at <=', $endDate . ' 23:59:59');
        }
        $this->db->group_by('nd.id_don_vi');
        $this->db->order_by('login_count', 'DESC');
        $departmentStats = $this->db->get()->result_array();

        // Process department stats to ensure numeric values
        foreach ($departmentStats as &$dept) {
            $dept['login_count'] = (int)$dept['login_count'];
            $dept['user_count'] = (int)$dept['user_count'];
            if (empty($dept['don_vi'])) {
                $dept['don_vi'] = 'Chưa xác định';
            }
        }

        // Top 20 người truy cập nhiều nhất
        $this->db->select('nd.ql_nguoi_dung_id, nd.ql_nguoi_dung_ho_ten, nd.ql_nguoi_dung_email, nd.ql_nguoi_dung_avatar, dv.ten_don_vi as don_vi, COUNT(*) as login_count');
        $this->db->from('sys_login_logs');
        $this->db->join('ql_nguoi_dung nd', 'nd.ql_nguoi_dung_id = sys_login_logs.ql_nguoi_dung_id', 'left');
        $this->db->join('e_don_vi dv', 'dv.id_don_vi = nd.id_don_vi', 'left');
        if ($startDate) {
            $this->db->where('sys_login_logs.created_at >=', $startDate . ' 00:00:00');
        }
        if ($endDate) {
            $this->db->where('sys_login_logs.created_at <=', $endDate . ' 23:59:59');
        }
        $this->db->group_by('sys_login_logs.ql_nguoi_dung_id');
        $this->db->order_by('login_count', 'DESC');
        $this->db->limit(20);
        $topUsers = $this->db->get()->result_array();

        // Process avatars
        foreach ($topUsers as &$row) {
            if (!empty($row['ql_nguoi_dung_avatar']) && !filter_var($row['ql_nguoi_dung_avatar'], FILTER_VALIDATE_URL)) {
                $row['ql_nguoi_dung_avatar'] = encryptString($row['ql_nguoi_dung_avatar']);
            }
        }

        // Thống kê theo ngày (last 30 days hoặc theo date range)
        $this->db->select('DATE(created_at) as date, COUNT(*) as login_count, COUNT(DISTINCT ql_nguoi_dung_id) as user_count');
        $this->db->from('sys_login_logs');
        if ($startDate) {
            $this->db->where('created_at >=', $startDate . ' 00:00:00');
        } else {
            $this->db->where('created_at >=', date('Y-m-d', strtotime('-30 days')) . ' 00:00:00');
        }
        if ($endDate) {
            $this->db->where('created_at <=', $endDate . ' 23:59:59');
        }
        $this->db->group_by('DATE(created_at)');
        $this->db->order_by('date', 'ASC');
        $dailyStats = $this->db->get()->result_array();

        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => [
                'summary' => [
                    'total_logins' => (int)$totalLogins,
                    'total_users' => (int)$uniqueUsers
                ],
                'by_department' => $departmentStats,
                'top_users' => $topUsers,
                'daily_stats' => $dailyStats
            ]
        ], REST_INSTANCE_Controller::HTTP_OK);
    }
}
