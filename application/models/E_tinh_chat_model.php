<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class E_tinh_chat_model extends MY_Model
{
    protected $table = 'e_tinh_chat';
    protected $primaryKey = 'id_tinh_chat';
    protected $timestamps = false;
    protected $createdAtField = 'ngay_tao';
    protected $updatedAtField = 'ngay_sua';

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $order = [], $columns = [], $searchKey = [])
    {
        $this->db->from('e_tinh_chat');
        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('ten_tinh_chat', $searchValue);
            $this->db->group_end();
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query 

        $defaultColumns = [
            'id_tinh_chat',
            'ten_tinh_chat',
            'class_color'
        ];

        if (!empty($order)) {
            $orderColumnIndex = $order[0]['column'];
            $orderColumn = isset($columns[$orderColumnIndex]['data']) ? $columns[$orderColumnIndex]['data'] : (isset($defaultColumns[$orderColumnIndex]) ? $defaultColumns[$orderColumnIndex] : 'id_tinh_chat');
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
