<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Demo_khoa_model extends MY_Model
{
    protected $table = 'demo_khoa';
    protected $primaryKey = 'id_khoa';
    protected $timestamps = false;
    protected $createdAtField = 'created_at';

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $columns = [], $searchKey = array())
    {
        // Đếm tổng số records (không dùng distinct để tránh lỗi với COUNT)
        $this->db->from($this->table);
        $this->db->join('demo_truong', 'demo_truong.id_truong = demo_khoa.id_truong', 'left');
        $this->db->where('demo_khoa.deleted_at IS NULL', null, false);
        $this->db->where('demo_truong.deleted_at IS NULL', null, false);
        
        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('demo_khoa.ten_khoa', $searchValue);
            $this->db->or_like('demo_truong.ten_truong', $searchValue);
            $this->db->group_end();
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE);

        if (!empty($orderBy)) {
            foreach ($orderBy as $order) {
                $colName = $order['column'] ?? null;
                $dir = $order['dir'] ?? 'asc';
                if ($colName) {
                    $this->db->order_by("demo_khoa.$colName", $dir);
                }
            }
        }

        if ($length != '-1')
            $this->db->limit($length, $start);

        // Chỉ select các cột cần thiết, không dùng * để tránh duplicate column names
        $this->db->select('demo_khoa.id_khoa, demo_khoa.id_truong, demo_khoa.ten_khoa, demo_khoa.created_at, demo_khoa.deleted_at, demo_truong.ten_truong');
        $query = $this->db->get();
        $data = $query->result_array();
        $sql = $this->db->last_query();

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'sql' => $sql
        ];
    }

    public function getListExport($searchValue = null, $orderBy = [], $searchKey = [])
    {
        $this->db->from($this->table);
        $this->db->join('demo_truong', 'demo_truong.id_truong = demo_khoa.id_truong', 'left');
        $this->db->where('demo_khoa.deleted_at IS NULL', null, false);
        $this->db->where('demo_truong.deleted_at IS NULL', null, false);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('demo_khoa.ten_khoa', $searchValue);
            $this->db->group_end();
        }

        // Chỉ select các cột cần thiết
        $this->db->select('demo_khoa.id_khoa, demo_khoa.id_truong, demo_khoa.ten_khoa, demo_khoa.created_at, demo_khoa.deleted_at, demo_truong.ten_truong');
        $query = $this->db->get();
        return $query->result_array();
    }

    public function getByTruong($id_truong)
    {
        $this->db->from($this->table);
        $this->db->where('demo_khoa.id_truong', $id_truong);
        $this->db->where('demo_khoa.deleted_at IS NULL', null, false);
        // Chỉ select các cột từ bảng khoa
        $this->db->select('demo_khoa.id_khoa, demo_khoa.id_truong, demo_khoa.ten_khoa, demo_khoa.created_at, demo_khoa.deleted_at');
        $query = $this->db->get();
        return $query->result_array();
    }

    // Soft delete
    public function softDelete($id)
    {
        return $this->db->where('id_khoa', $id)->update($this->table, ['deleted_at' => date('Y-m-d H:i:s')]);
    }
}
