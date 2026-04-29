<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_nhan_vien_khen_thuong_model extends MY_Model
{
    protected $table = 'hrm_nhan_vien_khen_thuong';
    protected $primaryKey = 'id_nhan_vien_khen_thuong';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }
}
