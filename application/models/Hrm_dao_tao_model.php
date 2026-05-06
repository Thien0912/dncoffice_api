<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_dao_tao_model extends MY_Model
{
    protected $table = 'hrm_dao_tao';
    protected $primaryKey = 'id_dao_tao';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function getAllDaotao($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array(), $fromDate = null, $toDate = null)
    {
        $this->db->from('hrm_dao_tao');
        $this->db->where('deleted_at IS NULL', null, false);
        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('hrm_dao_tao.ten_khoa_hoc', $searchValue);
            $this->db->like('hrm_dao_tao.noi_dung', $searchValue);
            $this->db->group_end();
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }


        $query = $this->db->get();
        $data = $query->result_array();

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }

    public function get_all_hrm_dao_tao()
    {
        $this->db->select('*');
        $this->db->from('hrm_dao_tao');
        $query = $this->db->get();
        return $query->result_array();
    }

    public function create_nhanvien_daotao($data) {
        return $this->db->insert('hrm_nhan_vien_dao_tao', $data);
    }
}
