<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_bang_cap_model extends MY_Model
{
    protected $table = 'hrm_nhan_vien_bang_cap';
    protected $primaryKey = 'id_bang_cap';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array())
    {
        $this->db->from('hrm_nhan_vien_bang_cap');

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('hrm_nhan_vien_bang_cap.ten_bang_cap', $searchValue);
            $this->db->or_like('hrm_nhan_vien_bang_cap.ngay_cap_bang_cap', $searchValue);
            $this->db->group_end();
        }
        $id_nhan_vien = commonRequest('id_nhan_vien');
        if ($id_nhan_vien) {
            $this->db->where('hrm_nhan_vien_bang_cap.id_nhan_vien', $id_nhan_vien);
        }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                $this->db->where('hrm_nhan_vien_bang_cap.' . $key, $value);
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
                $this->db->order_by('hrm_nhan_vien_bang_cap.' . $filed, $orderDir);
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
