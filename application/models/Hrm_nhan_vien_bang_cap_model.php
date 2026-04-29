<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_nhan_vien_bang_cap_model extends MY_Model
{
    protected $table = 'hrm_nhan_vien_bang_cap';
    protected $primaryKey = 'id_bang_cap';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }
}
