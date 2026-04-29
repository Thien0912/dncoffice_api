<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_ty_le_bao_hiem_model extends MY_Model
{
    protected $table = 'hrm_ty_le_bao_hiem';
    protected $primaryKey = 'id_ty_le_bao_hiem';

    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array())
    {
        $this->db->from('hrm_ty_le_bao_hiem');

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        // if ($searchValue) {
        //     $this->db->group_start();
        //     $this->db->like('hrm_ty_le_bao_hiem.ngay_ap_dung', $searchValue);
        //     $this->db->group_end();
        // }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                if ($key == 'year_tylebaohiem') {
                    $this->db->where('YEAR(hrm_ty_le_bao_hiem.ngay_ap_dung)', $value, FALSE);
                } else {
                    $this->db->where('hrm_ty_le_bao_hiem.' . $key, $value);
                }
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
                $this->db->order_by('hrm_ty_le_bao_hiem.' . $filed, $orderDir);
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
