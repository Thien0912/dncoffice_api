<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class E_xu_ly_model extends MY_Model
{
    protected $table = 'e_xu_ly';
    protected $primaryKey = 'id_xu_ly';
    protected $timestamps = false;
    protected $createdAtField = 'ngay_tao';
    protected $updatedAtField = 'ngay_sua';

    public function __construct()
    {
        parent::__construct();
    }
}
