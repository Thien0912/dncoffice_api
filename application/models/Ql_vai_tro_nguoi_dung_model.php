<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Ql_vai_tro_nguoi_dung_model extends MY_Model
{
    protected $table = 'ql_vai_tro_nguoi_dung';
    protected $primaryKey = 'ql_vai_tro_nguoi_dung_id';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }
}
