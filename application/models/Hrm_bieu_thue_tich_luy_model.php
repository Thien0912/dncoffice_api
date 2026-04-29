<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_bieu_thue_tich_luy_model extends MY_Model
{
    protected $table = 'hrm_bieu_thue_tich_luy';

    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function get_all()
    {
        $this->db->select('*');
        $this->db->from('hrm_bieu_thue_tich_luy');
        $query = $this->db->get();
        return $query->result_array();
    }
}