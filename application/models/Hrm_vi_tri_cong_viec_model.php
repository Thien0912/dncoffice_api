<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_vi_tri_cong_viec_model extends MY_Model
{
    protected $table = 'hrm_vi_tri_cong_viec';
    protected $primaryKey = 'id_vi_tri_cong_viec';
    protected $timestamps = false;
    protected $createdAtField = 'ngay_tao';
    protected $updatedAtField = 'ngay_sua';

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = [])
    {
        $this->db->from('hrm_vi_tri_cong_viec');
        $this->db->where('deleted_at IS NULL', null, false);
        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('ten_cong_viec', $searchValue);
            $this->db->group_end();
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query 

        if (!empty($orderBy)) {

            $order = $orderBy['order'];
            $orderColumnIndex = $order[0]['column'];
            $orderDir = $order[0]['dir'];

            $columns = $orderBy['columns'];
            $filed = $columns[$orderColumnIndex]['data'];

            $this->db->order_by($filed, $orderDir);
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
