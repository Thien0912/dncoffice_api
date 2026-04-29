<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class E_trang_thai_model extends MY_Model
{
    protected $table = 'e_trang_thai';
    protected $primaryKey = 'id_trang_thai';
    protected $timestamps = false;
    protected $createdAtField = 'ngay_tao';
    protected $updatedAtField = 'ngay_sua';

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $order = [], $searchKey = array())
    {
        $this->db->from('e_trang_thai');
        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('ten_trang_thai', $searchValue);
            $this->db->or_like('loai', $searchValue);
            $this->db->group_end();
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query 

        $columns = [
            '',
            '',
            'ten_trang_thai',
            'loai'
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
