<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class E_bao_cao_model extends MY_Model
{
    protected $table = 'e_bao_cao';
    protected $primaryKey = 'id_bao_cao';
    protected $timestamps = false;
    protected $createdAtField = 'ngay_tao';
    protected $updatedAtField = 'ngay_sua';

    public function __construct()
    {
        parent::__construct();
    }
}
