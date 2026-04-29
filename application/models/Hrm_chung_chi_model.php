<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_chung_chi_model extends MY_Model
{
    protected $table = 'hrm_nhan_vien_chung_chi';
    protected $primaryKey = 'id_chung_chi';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array())
    {
        $this->db->from('hrm_nhan_vien_chung_chi');

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('hrm_nhan_vien_chung_chi.ten_chung_chi', $searchValue);
            $this->db->or_like('hrm_nhan_vien_chung_chi.ngay_cap_chung_chi', $searchValue);
            $this->db->group_end();
        }
        $id_nhan_vien = commonRequest('id_nhan_vien');
        if ($id_nhan_vien) {
            $this->db->where('hrm_nhan_vien_chung_chi.id_nhan_vien', $id_nhan_vien);
        }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                $this->db->where('hrm_nhan_vien_chung_chi.' . $key, $value);
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
                $this->db->order_by('hrm_nhan_vien_chung_chi.' . $filed, $orderDir);
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
