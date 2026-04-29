<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_nhan_vien_thue_model extends MY_Model
{
    protected $table = 'hrm_nhan_vien_thue';
    protected $primaryKey = 'id_nhan_vien_thue';
    protected $timestamps = false;
    protected $createdAtField = 'created_at';
    protected $updatedAtField = 'updated_at';

    public function __construct()
    {
        parent::__construct();
    }

}
