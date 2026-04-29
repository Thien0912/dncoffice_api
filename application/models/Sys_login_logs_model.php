<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Sys_login_logs_model extends MY_Model
{
    protected $table = 'sys_login_logs';
    protected $primaryKey = 'id';
    protected $timestamps = false; // We handle created_at manually or let DB do it

    public function createLog($userId, $ip, $userAgent)
    {
        $data = [
            'ql_nguoi_dung_id' => $userId,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'created_at' => date('Y-m-d H:i:s')
        ];
        return $this->create($data);
    }
}
