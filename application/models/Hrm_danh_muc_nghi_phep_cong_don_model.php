<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_danh_muc_nghi_phep_cong_don_model extends MY_Model
{
    protected $table = 'hrm_danh_muc_nghi_phep_cong_don';
    protected $primaryKey = 'id_danh_muc_nghi_phep_cong_don';

    protected $timestamps = false;


    public function __construct()
    {
        parent::__construct();
    }


    public function getSearch()
    {

        $draw = commonRequest('draw');

        $start =  commonRequest('start');
        $length = commonRequest('length');

        $recordsTotal = $this->db->count_all('hrm_danh_muc_nghi_phep_cong_don');


        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('hrm_danh_muc_nghi_phep_cong_don', FALSE); // FALSE để không reset query

        $columns = [
            '',
            '',
            'id_danh_muc_nghi_phep_cong_don',
            'so_nam_lam_viec',
            'so_ngay_phep', 
        ];

        if (commonRequest('order')) {
            $order = json_decode(commonRequest('order'), true);


            $orderColumnIndex = $order[0]['column'];
            $orderColumn = $columns[$orderColumnIndex];  // index starts at 0
            $orderDir = $order[0]['dir'];

            if (!empty($orderColumn) && !empty($orderDir)) {
                $this->db->order_by($orderColumn, $orderDir);
            }
        }

        $this->db->limit($length, $start);
        $query = $this->db->get('hrm_danh_muc_nghi_phep_cong_don');

        $data = $query->result_array();

        return [
            'draw' => $this->input->get('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }
}
