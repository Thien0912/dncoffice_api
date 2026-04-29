<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class E_loai_model extends MY_Model
{
    protected $table = 'e_loai';
    protected $primaryKey = 'id_loai';
    protected $timestamps = false;
    protected $createdAtField = 'ngay_tao';
    protected $updatedAtField = 'ngay_sua';

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $columns = [], $searchKey = array())
    {
        $this->db->from('e_loai');
        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('ten_loai', $searchValue);
            $this->db->or_like('tien_to', $searchValue);
            $this->db->or_like('hau_to', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                if (!empty($value)) {
                    switch ($key) {
                        case 'selectedClassify':
                            switch ($value) {
                                case 'all':
                                    break;
                                default:
                                    $this->db->where('e_loai.thuoc_nhom', $value);
                                    break;
                            }
                            break;
                        default:
                            # code...
                            break;
                    }
                }
            }
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query 

        if (!empty($orderBy)) {
            foreach ($orderBy as $order) {
                $colName = $order['column'] ?? null;
                $dir = $order['dir'] ?? 'asc';

                if ($colName) {
                    $this->db->order_by("e_loai.$colName", $dir);
                }
            }
        }

        if ($length != '-1')
            $this->db->limit($length, $start);

        $query = $this->db->get();
        $data = $query->result_array();
        $sql = $this->db->last_query();

        foreach($data as &$dt){
            if($dt['id_don_vi']){
                $donvi = $this->db->select('*')->from('e_don_vi')->where('id_don_vi', $dt['id_don_vi'])->get()->row_array();
                $dt['ten_don_vi'] = $donvi['ten_don_vi'];
            }else {
                $dt['ten_don_vi'] = '';
            }
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'sql' => $sql
        ];
    }
}
