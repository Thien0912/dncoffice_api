<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_nhan_vien_thong_tin_gia_dinh extends MY_Model
{
    protected $table = 'hrm_nhan_vien_thong_tin_gia_dinh';
    protected $primaryKey = 'id_thong_tin_gia_dinh';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }
}
