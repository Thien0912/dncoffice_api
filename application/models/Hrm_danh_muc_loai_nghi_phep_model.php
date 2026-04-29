<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Hrm_danh_muc_loai_nghi_phep_model extends MY_Model
{
    protected $table = 'hrm_danh_muc_loai_nghi_phep';
    protected $primaryKey = 'id_loai_phep';
    protected $timestamps = true;
    protected $createdAtField = 'created_at';
    protected $updatedAtField = 'updated_at';
    protected $deletedAtField = 'deleted_at';

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $order = [], $columns = [], $searchKey = [])
    {
        $this->db->from($this->table);
        $this->db->where($this->deletedAtField, NULL);

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('ten_loai_phep', $searchValue);
            $this->db->or_like('ma_loai_phep', $searchValue);
            $this->db->group_end();
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE);

        $defaultColumns = [
            'id_loai_phep',
            'ma_loai_phep',
            'ten_loai_phep',
            'so_ngay_mac_dinh'
        ];

        if (!empty($order)) {
            $orderColumnIndex = $order[0]['column'];
            if (is_numeric($orderColumnIndex)) {
                $orderColumn = isset($columns[$orderColumnIndex]['data']) ? $columns[$orderColumnIndex]['data'] : (isset($defaultColumns[$orderColumnIndex]) ? $defaultColumns[$orderColumnIndex] : 'id_loai_phep');
            } else {
                $orderColumn = $orderColumnIndex;
            }

            $orderDir = $order[0]['dir'];

            if (!empty($orderColumn) && !empty($orderDir)) {
                $this->db->order_by($orderColumn, $orderDir);
            }
        } else {
            $this->db->order_by('id_loai_phep', 'ASC');
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
