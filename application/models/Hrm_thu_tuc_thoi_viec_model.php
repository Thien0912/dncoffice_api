<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_thu_tuc_thoi_viec_model extends MY_Model
{
    protected $table = 'hrm_thu_tuc_thoi_viec';
    protected $primaryKey = 'id_tttv';
    protected $timestamps = false;


    public function __construct()
    {
        parent::__construct();
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array())
    {
        $this->db->from('hrm_thu_tuc_thoi_viec');
        $this->db->where('deleted_at IS NULL');

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->or_like('hrm_thu_tuc_thoi_viec.ten_ntttv', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                $this->db->where('hrm_thu_tuc_thoi_viec.' . $key, $value);
            }
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query

        if (!empty($orderBy)) {
            $order = $orderBy['order'];
            $orderColumnIndex = $order[0]['column'];
            $orderDir = $order[0]['dir'];

            $columns = $orderBy['columns'];
            $filed = $columns[$orderColumnIndex]['data'];

            if (!empty($orderDir)) {
                $this->db->order_by('hrm_thu_tuc_thoi_viec.' . $filed, $orderDir);
            }
        }

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        $query = $this->db->get();
        $data = $query->result_array();

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ];
    }
}
