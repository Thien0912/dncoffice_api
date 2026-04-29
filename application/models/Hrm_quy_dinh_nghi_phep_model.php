<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_quy_dinh_nghi_phep_model extends MY_Model
{
    protected $table = 'hrm_quy_dinh_nghi_phep';
    protected $primaryKey = 'id';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $order = [], $searchKey = array())
    {
        $this->db->from('hrm_quy_dinh_nghi_phep');
        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('loai_cong_viec', $searchValue);
            $this->db->or_like('ngay_phep_co_ban', $searchValue);
            $this->db->group_end();
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query 

        $columns = [
            '',
            '',
            'loai_cong_viec',
            'ngay_phep_co_ban'
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

    public function getSearch()
    {
        $draw = commonRequest('draw');

        $start = $this->input->get('start') ?? 0;
        $length = $this->input->get('length') ?? 10;

        $recordsTotal = $this->db->count_all('hrm_quy_dinh_nghi_phep');

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('hrm_quy_dinh_nghi_phep', FALSE); // FALSE để không reset query

        $this->db->limit($length, $start);
        $query = $this->db->get('hrm_quy_dinh_nghi_phep');

        $data = $query->result_array();

        return [
            'draw' => $this->input->get('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }
}
