<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @property Common $common
 */

class Hrm_thuong_model extends MY_Model
{
    protected $table = 'hrm_thuong';
    protected $primaryKey = 'id';
    protected $timestamps = true;

    public function __construct()
    {
        parent::__construct();
        $this->load->library(['Common']);
    }

    // public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array(), $fromDate = null, $toDate = null)
    public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $columns = [], $searchKey = array(), $fromDate = null, $toDate = null, $auth)
    {
        $this->db->select('thuong.*');
        $this->db->from('hrm_thuong AS thuong');

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                $this->db->where('thuong.' . $key, $value);
            }
        }

        if ($fromDate && $toDate) {
            $fromDate = $fromDate . ' 00:00:00';
            $toDate = $toDate . ' 23:59:59';
            $this->db->where("thuong.created_at BETWEEN '{$fromDate}' AND '{$toDate}'");
        }

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE);

        // if (!empty($orderBy)) {
        //     $order = $orderBy['order'];
        //     $orderColumnIndex = $order[0]['column'];
        //     $orderDir = $order[0]['dir'];

        //     $columns = $orderBy['columns'];
        //     $filed = $columns[$orderColumnIndex]['data'];

        //     if (!empty($orderDir)) {
        //         $this->db->order_by('thuong.' . $filed, $orderDir);
        //     } else {
        //         $this->db->order_by('thuong.created_at', 'DESC');
        //     }
        // }

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        $query = $this->db->get();
        $thuongData = $query->result_array();

        foreach ($thuongData as &$thuong) {
            $this->db->select('chi_tiet.*');
            $this->db->from('hrm_thuong_chi_tiet AS chi_tiet');
            $this->db->where('chi_tiet.thuong_id', $thuong['id']);
            $chiTietQuery = $this->db->get();
            $thuong['chi_tiet'] = $chiTietQuery->result_array();
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $thuongData
        ];
    }

    public function create_chi_tiet($data)
    {
        $this->db->insert('hrm_thuong_chi_tiet', $data);
        return $this->db->insert_id();
    }
}
