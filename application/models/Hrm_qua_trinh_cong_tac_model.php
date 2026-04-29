<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_qua_trinh_cong_tac_model extends MY_Model
{
    protected $table = 'hrm_qua_trinh_cong_tac';
    protected $primaryKey = 'id_qua_trinh_cong_tac';
    protected $timestamps = false;
    protected $createdAtField = 'created_at';
    protected $updatedAtField = 'updated_at';

    public function __construct()
    {
        parent::__construct();
    }
}
