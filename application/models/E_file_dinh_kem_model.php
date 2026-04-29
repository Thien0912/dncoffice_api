<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class E_file_dinh_kem_model extends MY_Model
{
    protected $table = 'e_file_dinh_kem';
    protected $primaryKey = 'id_file_dinh_kem';
    protected $timestamps = true;
    protected $createdAtField = 'ngay_tao';
    protected $updatedAtField = 'ngay_sua';

    public function __construct()
    {
        parent::__construct();
    }
}
