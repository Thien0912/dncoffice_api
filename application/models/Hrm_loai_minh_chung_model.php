<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Hrm_loai_minh_chung_model extends MY_Model
{
    protected $table = 'hrm_loai_minh_chung';
    protected $primaryKey = 'id_loai_minh_chung';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Lấy tất cả loại minh chứng đang hoạt động, sắp xếp theo thứ tự
     */
    public function getAllActive()
    {
        return $this->db
            ->where('deleted_at IS NULL', null, false)
            ->order_by('thu_tu', 'ASC')
            ->get($this->table)
            ->result_array();
    }
}
