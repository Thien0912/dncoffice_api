<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class E_lich_cong_tac_model extends MY_Model
{
    protected $table = 'e_lich_cong_tac';
    protected $primaryKey = 'id_lich_cong_tac ';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function getSearch()
    {
        $ngay = commonRequest('ngay') ? commonRequest('ngay') : null;
        $noidung = commonRequest('noidung') ? commonRequest('noidung') : null;

        $draw = commonRequest('draw');

        $start = commonRequest('start') ? commonRequest('start') : null;
        $length = commonRequest('length') ? commonRequest('length') : null;

        $recordsTotal = $this->db->count_all('e_lich_cong_tac');

        if ($ngay) {
            $this->db->where('ngay >=', "{$ngay} 00:00:00");
            $this->db->where('ngay <=', "{$ngay} 23:59:59");
        }

        if ($noidung) {
            $this->db->like('noi_dung', $noidung);
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('e_lich_cong_tac', FALSE); // FALSE để không reset query

        $columns = [
            '',
            '',
            'ngay',
            'gio',
            'noi_dung', 
            'bo_phan',
            'trang_thai'
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
        $query = $this->db->get('e_lich_cong_tac');

        $data = $query->result_array();

        return [
            'draw' => $this->input->get('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }

    public function getList($start_date = null, $end_date = null)
    {
        $this->db->select('*');
        $this->db->from('e_lich_cong_tac');

        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('ngay >=', $start_date);
            $this->db->where('ngay <=', $end_date);
        }

        $query = $this->db->get();

        return [
            'data' => $query->result_array(),
        ];
    }
}