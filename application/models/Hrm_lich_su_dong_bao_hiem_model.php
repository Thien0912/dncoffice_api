<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_lich_su_dong_bao_hiem_model extends MY_Model
{
  protected $table = 'hrm_lich_su_dong_bhxh';
  protected $primaryKey = 'id_lich_su_dong_bhxh';
  protected $timestamps = false;

  public function __construct()
  {
    parent::__construct();
  }

  public function getAll($start = 0, $length = 10, $searchValue = null, $order = [], $searchKey = array())
  {
    $this->db->from('hrm_lich_su_dong_bhxh')->join("hrm_nhan_vien", "hrm_lich_su_dong_bhxh.id_nhan_vien = hrm_nhan_vien.id_nhan_vien")
      ->select("hrm_bao_hiem_xa_hoi.*, hrm_nhan_vien.ho_ten as nhan_vien_ho_ten");
    $totalRecordsQuery = clone $this->db;
    $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

    // if ($searchValue) {
    //   $this->db->group_start();
    //   $this->db->like('luong_co_ban', $searchValue);
    //   $this->db->group_end();
    // }

    $filteredQuery = clone $this->db;
    $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query 

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
