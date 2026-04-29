<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_nghi_phep_cong_don_model extends MY_Model
{
    protected $table = 'hrm_nghi_phep_cong_don';
    protected $primaryKey = 'id_nghi_phep_cong_don';

    protected $timestamps = false;


    public function __construct()
    {
        parent::__construct();
    }
}
