<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_nhan_vien_di_cong_tac_model extends MY_Model
{
    protected $table = 'hrm_nhan_vien_di_cong_tac';
    protected $primaryKey = 'id_nhan_vien_di_cong_tac';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }

}
