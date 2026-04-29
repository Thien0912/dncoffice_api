<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_nhan_vien_dao_tao_model extends MY_Model
{
    protected $table = 'hrm_nhan_vien_dao_tao';
    protected $primaryKey = 'id_nhan_vien_dao_tao';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }
}
