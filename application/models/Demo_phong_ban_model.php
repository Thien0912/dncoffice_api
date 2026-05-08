<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Demo_phong_ban_model extends MY_Model
{
    protected $table = 'demo_phong_ban';
    protected $primaryKey = 'id';
    protected $timestamps = false;
    protected $createdAtField = 'created_at';

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $columns = [], $searchKey = array())
    {
        $this->db->from($this->table);
        $this->db->where('deleted_at IS NULL', null, false);
        
        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('ten_phong_ban', $searchValue);
            $this->db->or_like('ten_viet_tat', $searchValue);
            $this->db->or_like('ma_don_vi', $searchValue);
            $this->db->or_like('email', $searchValue);
            $this->db->group_end();
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE);

        if (!empty($orderBy)) {
            foreach ($orderBy as $order) {
                $colName = $order['column'] ?? null;
                $dir = $order['dir'] ?? 'asc';
                if ($colName) {
                    $this->db->order_by("$this->table.$colName", $dir);
                }
            }
        }

        if ($length != '-1')
            $this->db->limit($length, $start);

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
        $this->db->where('deleted_at IS NULL', null, false);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('ten_phong_ban', $searchValue);
            $this->db->or_like('ten_viet_tat', $searchValue);
            $this->db->or_like('ma_don_vi', $searchValue);
            $this->db->or_like('email', $searchValue);
            $this->db->group_end();
        }

        $this->db->select('*');
        $query = $this->db->get();
        return $query->result_array();
    }

    // Soft delete
    public function softDelete($id)
    {
        return $this->db->where('id', $id)->update($this->table, ['deleted_at' => date('Y-m-d H:i:s')]);
    }
}
