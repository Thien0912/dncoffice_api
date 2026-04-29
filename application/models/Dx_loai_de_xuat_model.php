<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dx_loai_de_xuat_model extends MY_Model
{
    protected $table = 'dx_loai_de_xuat';
    protected $primaryKey = 'id_dx_loai_de_xuat';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Lấy danh sách có phân trang, tìm kiếm, sắp xếp
     */
    public function getList($start = 0, $length = 10, $searchValue = null, $order = [])
    {
        // Base query
        $this->db->select('ldx.*');
        $this->db->from('dx_loai_de_xuat ldx');
        $this->db->where('ldx.deleted_at IS NULL');

        // Tìm kiếm
        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('ldx.ma_loai', $searchValue);
            $this->db->or_like('ldx.ten_loai', $searchValue);
            $this->db->or_like('ldx.mo_ta', $searchValue);
            $this->db->group_end();
        }

        // Tổng sau filter
        $recordsFiltered = $this->db->count_all_results('', false);

        // Sắp xếp
        if (!empty($order)) {
            $columnMap = [
                'ma_loai'  => 'ldx.ma_loai',
                'ten_loai' => 'ldx.ten_loai',
            ];
            foreach ($order as $o) {
                $col = isset($o['column']) && isset($columnMap[$o['column']]) ? $columnMap[$o['column']] : 'ldx.created_at';
                $dir = isset($o['dir']) && strtolower($o['dir']) === 'asc' ? 'ASC' : 'DESC';
                $this->db->order_by($col, $dir);
            }
        } else {
            $this->db->order_by('ldx.created_at', 'DESC');
        }

        // Phân trang
        $this->db->limit($length, $start);
        $data = $this->db->get()->result_array();

        // Thêm STT
        foreach ($data as $index => &$row) {
            $row['stt'] = $start + $index + 1;
        }

        // Tổng tất cả (không filter)
        $recordsTotal = $this->db->where('deleted_at IS NULL')->count_all_results('dx_loai_de_xuat');

        return [
            'data'             => $data,
            'recordsTotal'     => $recordsTotal,
            'recordsFiltered'  => $recordsFiltered,
        ];
    }

    /**
     * Lấy tất cả loại đề xuất (dùng cho select/dropdown)
     */
    public function getAllLoaiDeXuat()
    {
        $this->db->select('ldx.*');
        $this->db->from('dx_loai_de_xuat ldx');
        $this->db->where('ldx.deleted_at IS NULL');
        $this->db->order_by('ldx.ten_loai', 'ASC');
        return $this->db->get()->result_array();
    }
}
