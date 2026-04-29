<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_bao_hiem_model extends MY_Model
{
    protected $table = 'hrm_bao_hiem_xa_hoi';
    protected $primaryKey = 'id_bao_hiem_xa_hoi';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $order = [], $searchKey = array())
    {
        $this->db->from('hrm_bao_hiem_xa_hoi')->join("hrm_nhan_vien", "hrm_bao_hiem_xa_hoi.id_nhan_vien = hrm_nhan_vien.id_nhan_vien")
            ->select("hrm_bao_hiem_xa_hoi.*, hrm_nhan_vien.ho_va_ten as nhan_vien_ho_ten");
        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        // if ($searchValue) {
        //   $this->db->group_start();
        //   $this->db->like('luong_co_ban', $searchValue);
        //   $this->db->group_end();
        // }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query 

        $this->db->where("hrm_bao_hiem_xa_hoi.deleted_at is null");

        $this->db->order_by("hrm_bao_hiem_xa_hoi.id_bao_hiem_xa_hoi", "desc");

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

    public function getById($id)
    {
        $this->db->from('hrm_bao_hiem_xa_hoi')
            ->where('id_bao_hiem_xa_hoi', $id);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function getCanhBaoHetHanDongBHXH($start = 0, $length = 10, $searchValue = null, $order = [], $searchKey = array())
    {
        $today = date('Y-m-d');
        $next30 = date('Y-m-d', strtotime('+30 days'));

        // Subquery tính tuổi nghỉ hưu theo giới tính
        $this->db->from('hrm_nhan_vien')
            ->select('hrm_nhan_vien.*, hrm_bao_hiem_xa_hoi.so_bhxh, hrm_bao_hiem_xa_hoi.muc_dong, hrm_bao_hiem_xa_hoi.ngay_bat_dau, hrm_bao_hiem_xa_hoi.ngay_ket_thuc, hrm_bao_hiem_xa_hoi.trang_thai, hrm_bao_hiem_xa_hoi.deleted_at')
            ->join('hrm_bao_hiem_xa_hoi', 'hrm_nhan_vien.id_nhan_vien = hrm_bao_hiem_xa_hoi.id_nhan_vien')
            ->where('hrm_bao_hiem_xa_hoi.trang_thai', 'Dang_dong')
            ->where('hrm_bao_hiem_xa_hoi.deleted_at IS NULL');

        // Tuổi nghỉ hưu
        // Nam: nghỉ hưu 62 tuổi
        // Nữ: nghỉ hưu 60 tuổi 4 tháng

        // Điều kiện: ngày sinh + tuổi nghỉ hưu nằm trong khoảng từ hôm nay đến 30 ngày tới
        $this->db->group_start();
        $this->db->where("(hrm_nhan_vien.gioi_tinh = 1 AND DATEDIFF(DATE_ADD(hrm_nhan_vien.ngay_sinh, INTERVAL 62 YEAR), '{$today}') < 30)", null, false);
        $this->db->or_where("(hrm_nhan_vien.gioi_tinh = 2 AND DATEDIFF(DATE_ADD(DATE_ADD(hrm_nhan_vien.ngay_sinh, INTERVAL 60 YEAR), INTERVAL 4 MONTH), '{$today}') < 30)", null, false);
        $this->db->group_end();


        // Clone để tính tổng
        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', false);

        // Tìm kiếm theo họ tên
        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('hrm_nhan_vien.ho_va_ten', $searchValue);
            $this->db->group_end();
        }

        // Clone để đếm total
        $searchQuery = clone $this->db;
        $recordsTotal = $searchQuery->count_all_results('', false);

        // Sắp xếp
        $this->db->order_by("hrm_nhan_vien.id_nhan_vien", "desc");

        // Giới hạn phân trang
        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        $query = $this->db->get();
        $data = $query->result_array();

        // Tính số ngày còn lại đến tuổi nghỉ hưu
        foreach ($data as &$row) {
            if ($row['gioi_tinh'] == 1) {
                $retireDate = date('Y-m-d', strtotime($row['ngay_sinh'] . ' +62 years'));
            } else {
                $retireDate = date('Y-m-d', strtotime($row['ngay_sinh'] . ' +60 years +4 months'));
            }
            $row['ngay_nghi_huu'] = $retireDate;
            $row['so_ngay_con_lai'] = (int)((strtotime($retireDate) - strtotime($today)) / (60 * 60 * 24));
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }
}
