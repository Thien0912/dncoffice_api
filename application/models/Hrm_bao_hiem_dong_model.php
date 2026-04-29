<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_bao_hiem_dong_model extends MY_Model
{
    protected $table = 'hrm_bao_hiem_dong';
    protected $primaryKey = 'id_bao_hiem_dong';

    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }
}
