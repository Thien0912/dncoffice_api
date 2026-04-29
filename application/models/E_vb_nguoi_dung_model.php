<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class E_vb_nguoi_dung_model extends MY_Model
{
    protected $table = 'e_vb_nguoi_dung';
    protected $primaryKey = 'e_vb_nguoi_dung_id';
    protected $timestamps = false;
    protected $createdAtField = 'created_at';
    protected $updatedAtField = 'updated_at';

    public function __construct()
    {
        parent::__construct();
    }
}
