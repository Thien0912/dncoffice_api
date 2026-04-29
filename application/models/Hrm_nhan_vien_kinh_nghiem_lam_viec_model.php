<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_nhan_vien_kinh_nghiem_lam_viec_model extends MY_Model
{
    protected $table = 'hrm_nhan_vien_kinh_nghiem_lam_viec';
    protected $primaryKey = 'id_kinh_nghiem';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }
}
