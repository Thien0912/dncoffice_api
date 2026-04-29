<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class E_bo_phan_model extends MY_Model
{
    protected $table = 'e_bo_phan';
    protected $primaryKey = 'id_bo_phan';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $order = [], $searchKey = array())
    {
        $this->db->from('e_bo_phan');
        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('ten_bo_phan', $searchValue);
            $this->db->group_end();
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query 

        $columns = [
            '',
            '',
            'ten_bo_phan'
        ];

        if (!empty($order)) {
            $orderColumnIndex = $order[0]['column'];
            $orderColumn = $columns[$orderColumnIndex];  // index starts at 0
            $orderDir = $order[0]['dir'];

            if (!empty($orderColumn) && !empty($orderDir)) {
                $this->db->order_by($orderColumn, $orderDir);
            }
        }
        if ($length != '-1')
            $this->db->limit($length, $start);

        $query = $this->db->get();
        $data = $query->result_array();

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }

    

}
