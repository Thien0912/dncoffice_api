<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_ca_lam_viec_model extends MY_Model
{
    protected $table = 'hrm_ca_lam_viec';
    protected $primaryKey = 'id';

    protected $timestamps = true;


    public function __construct()
    {
        parent::__construct();
    }

    public function getAll($start = null, $length = null, $searchValue = null, $orderBy = [], $searchKey = array(), $nhanvien = null, $fromDate = null, $toDate = null, $ca = null)
    {
        $this->db->select('hrm_ca_lam_viec.*');
        $this->db->from('hrm_ca_lam_viec');
        $this->db->where('hrm_ca_lam_viec.deleted_at IS NULL');
        
        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        // if ($searchValue) {
        //     $this->db->group_start();
        //     $this->db->like('hrm_ca_lam_viec.ca_lam_viec', $searchValue);
        //     $this->db->group_end();
        // }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query 

        if (!empty($orderBy)) {
      
            $order = $orderBy['order'];
            $orderColumnIndex = $order[0]['column'];
            $orderDir = $order[0]['dir'];

            $columns = $orderBy['columns'];
            $filed = $columns[$orderColumnIndex]['data'];

            $this->db->order_by('hrm_ca_lam_viec.' . $filed, $orderDir);
        }
        
        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        $query = $this->db->get();
        $data = $query->result_array();  

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'sql' => $this->db->last_query(),
        ];
    }


    public function getListExportChamcong($start = 0, $length = 10, $searchValue = null, $searchKey = array(), $fromDate = null, $toDate = null , $nhanvien = null , $ca = null)
    {
        $this->db->from('hrm_cham_cong')
            ->join('hrm_nhan_vien', 'hrm_nhan_vien.ma_cham_cong = hrm_cham_cong.ma_cham_cong', 'left')
            ->select(
                "hrm_nhan_vien.ho_va_ten as ho_ten, DATE_FORMAT(hrm_cham_cong.gio_diem_danh, '%d/%m/%Y %H:%i:%s') as gio_diem_danh, 
                CASE 
                    WHEN hrm_cham_cong.ca_lam_viec = 'sang' THEN 'Sáng' 
                    WHEN hrm_cham_cong.ca_lam_viec = 'chieu' THEN 'Chiều' 
                    WHEN hrm_cham_cong.ca_lam_viec = 'ngoai_gio' THEN 'Ngoài giờ' 
                    ELSE hrm_cham_cong.ca_lam_viec 
                END as ca_lam_viec, 
                hrm_cham_cong.ma_cham_cong as ma_cham_cong,
                CASE WHEN hrm_cham_cong.di_muon = 0 THEN '-' ELSE hrm_cham_cong.di_muon END as di_muon, 
                CASE WHEN hrm_cham_cong.ve_som = 0 THEN '-' ELSE hrm_cham_cong.ve_som END as ve_som, 
                CASE WHEN hrm_cham_cong.so_phut_lam_them = 0 THEN '-' ELSE hrm_cham_cong.so_phut_lam_them END as so_phut_lam_them, 
                hrm_cham_cong.ghi_chu as ghi_chu
                "
            );

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('hrm_nhan_vien.ho_va_ten', $searchValue);
            $this->db->or_like('hrm_cham_cong.ma_cham_cong', $searchValue);
            $this->db->group_end();
        }

        if ($nhanvien) {
            $this->db->where_in('hrm_nhan_vien.id_nhan_vien', explode(',', $nhanvien));
        }

        if($ca){
            $this->db->where('hrm_cham_cong.ca_lam_viec', $ca);
        }

        
        if ($fromDate && $toDate) {
            $this->db->where("DATE(hrm_cham_cong.gio_diem_danh) BETWEEN '{$fromDate}' AND '{$toDate}'");
        }

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        $query = $this->db->get();
        $data = $query->result_array();

        return $data;
    }

    public function getExistingIds($ids = [])
    {
        if (empty($ids)) return [];

        $this->db->select('id');
        $this->db->from('hrm_api_cham_cong'); 
        $this->db->where_in('id', $ids);
        $query = $this->db->get();

        return array_column($query->result_array(), 'id');
    }
}
