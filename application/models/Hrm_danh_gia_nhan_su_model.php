<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_danh_gia_nhan_su_model extends MY_Model
{
    protected $table = 'hrm_danh_gia_nhan_su';
    protected $primaryKey = 'id_danh_gia_nhan_su';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }
}
