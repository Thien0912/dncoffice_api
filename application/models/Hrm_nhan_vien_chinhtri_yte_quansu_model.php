<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_nhan_vien_chinhtri_yte_quansu_model extends MY_Model
{
    protected $table = 'hrm_nhan_vien_chinhtri_yte_quansu';
    protected $primaryKey = 'id_nhan_vien_chinhtri_yte_quansu';
    protected $timestamps = false;
    protected $createdAtField = 'ngay_tao';
    protected $updatedAtField = 'ngay_sua';

    public function __construct()
    {
        parent::__construct();
    }
}
