<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_nhan_vien_luong_model extends MY_Model
{
    protected $table = 'hrm_nhan_vien_luong';
    protected $primaryKey = 'id_nhan_vien_luong';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array())
    {
        $this->db->select('
                        hrm_nhan_vien.id_nhan_vien,
                        hrm_nhan_vien.ma_nhan_vien,
                        hrm_nhan_vien.ma_cham_cong,
                        hrm_nhan_vien.ho_va_ten,
                        hrm_nhan_vien.email,
                        hrm_nhan_vien_luong.tk_ngan_hang,
                        hrm_nhan_vien_luong.ngan_hang,
                        hrm_nhan_vien_luong.ten_chu_tai_khoan
                ');
        $this->db->from('hrm_nhan_vien');
        $this->db->join('hrm_nhan_vien_luong', 'hrm_nhan_vien.id_nhan_vien = hrm_nhan_vien_luong.id_nhan_vien', 'left');

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('hrm_nhan_vien.ma_nhan_vien', $searchValue);
            $this->db->or_like('hrm_nhan_vien.ma_cham_cong', $searchValue);
            $this->db->or_like('hrm_nhan_vien.ho_va_ten', $searchValue);
            $this->db->or_like('hrm_nhan_vien.email', $searchValue);
            $this->db->or_like('hrm_nhan_vien.ho_va_ten', $searchValue);
            $this->db->or_like('hrm_nhan_vien_luong.tk_ngan_hang', $searchValue);
            $this->db->or_like('hrm_nhan_vien_luong.ngan_hang', $searchValue);
            $this->db->or_like('hrm_nhan_vien_luong.ten_chu_tai_khoan', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            $arr = ['tk_ngan_hang', 'ngan_hang', 'ten_chu_tai_khoan'];
            foreach ($searchKey as $key => $value) {
                if (in_array($key, $arr)) {
                    $this->db->like('hrm_nhan_vien_luong.' . $key, $value);
                } else {
                    if (is_array($value)) {
                        $this->db->where_in('hrm_nhan_vien_luong.' . $key, $value);
                    } else {
                        $this->db->where('hrm_nhan_vien_luong.' . $key, $value);
                    }
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
                $this->db->order_by('hrm_nhan_vien_luong.' . $filed, $orderDir);
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
