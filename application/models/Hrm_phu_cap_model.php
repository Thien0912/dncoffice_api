<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_phu_cap_model extends MY_Model
{
    protected $table = 'hrm_phu_cap';
    protected $primaryKey = 'id_phu_cap';
    protected $timestamps = false;
    // protected $createdAtField = 'ngay_tao';
    // protected $updatedAtField = 'ngay_sua';

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array())
    {
        $this->db->from('hrm_phu_cap')
            ->where('deleted_at IS NULL');

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        // if ($searchValue) {
        //     $this->db->group_start();
        //     $this->db->like('hrm_phu_cap.ma_phu_cap', $searchValue);
        //     $this->db->or_like('hrm_phu_cap.ten_phu_cap', $searchValue);
        //     $this->db->or_like('hrm_phu_cap.mo_ta', $searchValue);
        //     $this->db->group_end();
        // }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                $this->db->where('hrm_phu_cap.' . $key, $value);
            }
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query

        // if (!empty($orderBy)) {
        //     $order = $orderBy['order'];
        //     $orderColumnIndex = $order[0]['column'];
        //     $orderDir = $order[0]['dir'];

        //     $columns = $orderBy['columns'];
        //     $filed = $columns[$orderColumnIndex]['data'];

        //     if (!empty($orderDir)) {
        //         $this->db->order_by('hrm_phu_cap.' . $filed, $orderDir);
        //     }
        // }

        // if ($length != '-1') {
        //     $this->db->limit($length, $start);
        // }

        $query = $this->db->get();
        $data = $query->result_array();

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ];
    }

    public function getAllTrash($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array())
    {
        $this->db->from('hrm_hop_dong')
            ->join('hrm_nhan_vien', 'hrm_nhan_vien.id_nhan_vien = hrm_hop_dong.id_nhan_vien')
            ->where('hrm_hop_dong.deleted_at IS NOT NULL');

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('hrm_dang_ky_lam_them.id_nhan_vien', $searchValue);
            $this->db->or_like('hrm_dang_ky_lam_them.ngay', $searchValue);
            $this->db->or_like('hrm_dang_ky_lam_them.gio_bat_dau', $searchValue);
            $this->db->or_like('hrm_dang_ky_lam_them.gio_ket_thuc', $searchValue);
            $this->db->or_like('hrm_dang_ky_lam_them.loai_ngay', $searchValue);
            $this->db->or_like('hrm_dang_ky_lam_them.trang_thai', $searchValue);
            $this->db->or_like('hrm_dang_ky_lam_them.ghi_chu', $searchValue);
            $this->db->or_like('hrm_nhan_vien.ho_ten', $searchValue);
            $this->db->or_like('hrm_nhan_vien.email', $searchValue);
            $this->db->or_like('hrm_nhan_vien.so_dien_thoai', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                $this->db->where('hrm_hop_dong.' . $key, $value);
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
                $this->db->order_by('hrm_hop_dong.' . $filed, $orderDir);
            }
        }

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        $this->db->select('
            hrm_hop_dong.*, 
            hrm_nhan_vien.ho_ten, 
            hrm_nhan_vien.gioi_tinh,
            hrm_nhan_vien.ngay_sinh,
            hrm_nhan_vien.so_dien_thoai,
        ');
        $query = $this->db->get();
        $data = $query->result_array();


        foreach ($data as &$dt) {
            $dt['file_hop_dong_duong_dan'] = encryptString($dt['file_hop_dong_duong_dan']);
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ];
    }
}
