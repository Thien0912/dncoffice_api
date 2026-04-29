<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class E_vb_co_quan_model extends MY_Model
{
    protected $table = 'e_vb_co_quan';
    protected $primaryKey = 'id_vb_co_quan';
    protected $timestamps = false;
    protected $createdAtField = 'ngay_tao';
    protected $updatedAtField = 'ngay_sua';

    public function __construct()
    {
        parent::__construct();
    }
}
