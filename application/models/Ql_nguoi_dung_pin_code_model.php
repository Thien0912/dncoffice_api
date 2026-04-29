<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Ql_nguoi_dung_pin_code_model extends MY_Model
{
    protected $table = 'ql_nguoi_dung_pin_code';
    protected $primaryKey = 'id_ql_nguoi_dung_pin_code';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Ql_nguoi_dung_model');
    }
}
