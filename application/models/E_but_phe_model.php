<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class E_but_phe_model extends MY_Model
{
    protected $table = 'e_but_phe';
    protected $primaryKey = 'id_but_phe';
    protected $timestamps = true;
    protected $createdAtField = 'ngay_tao';
    protected $updatedAtField = 'ngay_sua';

    public function __construct()
    {
        parent::__construct();
    }
}
