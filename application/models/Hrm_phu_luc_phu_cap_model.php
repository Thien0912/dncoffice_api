<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_phu_luc_phu_cap_model extends MY_Model
{
    protected $table = 'hrm_phu_luc_phu_cap';
    protected $primaryKey = 'hrm_phu_luc_phu_cap_id';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }
}
