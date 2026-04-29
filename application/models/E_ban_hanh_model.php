<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class E_ban_hanh_model extends MY_Model
{
    protected $table = 'e_ban_hanh';
    protected $primaryKey = 'id_ban_hanh';
    protected $timestamps = false;
    protected $createdAtField = 'ngay_tao';
    protected $updatedAtField = 'ngay_sua';

    public function __construct()
    {
        parent::__construct();
    }
}
