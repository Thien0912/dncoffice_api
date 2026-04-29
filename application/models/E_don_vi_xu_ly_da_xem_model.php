<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class E_don_vi_xu_ly_da_xem_model extends MY_Model
{
    protected $table = 'e_don_vi_xu_ly_da_xem';
    protected $primaryKey = 'id_don_vi_xu_ly_da_xem';
    protected $timestamps = false;
    // protected $createdAtField = 'ngay_tao';
    // protected $updatedAtField = 'ngay_sua';

    public function __construct()
    {
        parent::__construct();
    }
}
