<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_quy_dinh_nghi_phep extends MY_Model
{
    protected $table = 'hrm_quy_dinh_nghi_phep';
    protected $primaryKey = 'id_quy_dinh_nghi_phep';
    protected $timestamps = false;
    protected $createdAtField = 'ngay_tao';
    protected $updatedAtField = 'ngay_sua';

    public function __construct()
    {
        parent::__construct();
    }
}
