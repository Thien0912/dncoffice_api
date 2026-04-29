<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Sys_user_online_model extends MY_Model
{
    protected $table = 'sys_user_online';
    protected $primaryKey = 'id';
    public $timestamps = false; 

    public function __construct()
    {
        parent::__construct();
    }

    public function updateHeartbeat($userId, $ip, $deviceInfo)
    {
        // Check if table exists, if not create it (Auto-migration)
        if (!$this->db->table_exists($this->table)) {
            $this->createTable();
        }

        $sql = "INSERT INTO {$this->table} (ql_nguoi_dung_id, last_active_at, ip_address, device_info) 
                VALUES (?, NOW(), ?, ?) 
                ON DUPLICATE KEY UPDATE last_active_at = NOW(), ip_address = VALUES(ip_address), device_info = VALUES(device_info)";
        
        $this->db->query($sql, [$userId, $ip, $deviceInfo]);
    }

    public function getOnlineUsers($minutes = 2)
    {
        if (!$this->db->table_exists($this->table)) {
            return [];
        }

        // Get users active within last X minutes
        $this->db->select('
            sys_user_online.*, 
            ql_nguoi_dung.ql_nguoi_dung_ho_ten, 
            ql_nguoi_dung.ql_nguoi_dung_avatar, 
            ql_nguoi_dung.ql_nguoi_dung_email, 
            hrm_nhan_vien.ma_nhan_vien,
            e_don_vi.ten_don_vi,
            hrm_vi_tri_cong_viec.ten_cong_viec
        ');
        $this->db->from($this->table);
        $this->db->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = sys_user_online.ql_nguoi_dung_id');
        $this->db->join('hrm_nhan_vien', 'hrm_nhan_vien.ql_nguoi_dung_id = ql_nguoi_dung.ql_nguoi_dung_id', 'left');
        // Join department and job title via hrm_nhan_vien
        $this->db->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_nhan_vien.id_don_vi_cong_tac', 'left');
        $this->db->join('hrm_vi_tri_cong_viec', 'hrm_vi_tri_cong_viec.id_vi_tri_cong_viec = hrm_nhan_vien.id_vi_tri_cong_viec', 'left');
        
        $this->db->where('last_active_at >=', date('Y-m-d H:i:s', strtotime("-{$minutes} minutes")));
        $this->db->order_by('last_active_at', 'DESC');
        
        $users = $this->db->get()->result_array();

        // Process avatar if needed
        foreach ($users as &$user) {
            if (!empty($user['ql_nguoi_dung_avatar']) && !filter_var($user['ql_nguoi_dung_avatar'], FILTER_VALIDATE_URL)) {
                if (function_exists('encryptString')) {
                    $user['ql_nguoi_dung_avatar'] = encryptString($user['ql_nguoi_dung_avatar']);
                }
            }
        }
        return $users;
    }

    public function countOnlineUsers($minutes = 2)
    {
        if (!$this->db->table_exists($this->table)) {
            return 0;
        }

        $this->db->where('last_active_at >=', date('Y-m-d H:i:s', strtotime("-{$minutes} minutes")));
        return $this->db->count_all_results($this->table);
    }

    private function createTable()
    {
        $this->load->dbforge();
        $fields = [
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ],
            'ql_nguoi_dung_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unique' => TRUE,
            ],
            'last_active_at' => [
                'type' => 'DATETIME',
                'null' => TRUE,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => TRUE,
            ],
            'device_info' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => TRUE,
            ],
        ];
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('id', TRUE);
        // Add unique key for ql_nguoi_dung_id manually if add_field unique doesn't work as expected in all CI versions, 
        // but 'unique' => TRUE in field definition usually works for CI3 dbforge.
        // Alternatively: $this->dbforge->add_key('ql_nguoi_dung_id', FALSE, TRUE); // keyname, primary, unique
        
        $this->dbforge->create_table($this->table, TRUE);
        
        // Ensure unique index exists if create_table didn't handle it perfectly (sometimes it just adds a key)
        // $sql = "ALTER TABLE {$this->table} ADD UNIQUE INDEX IF NOT EXISTS idx_user_id (ql_nguoi_dung_id)";
        // $this->db->query($sql);
    }
}
