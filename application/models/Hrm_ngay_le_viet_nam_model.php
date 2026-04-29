<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Hrm_ngay_le_viet_nam_model extends MY_Model
{
    protected $table = 'hrm_ngay_le_viet_nam';
    protected $primaryKey = 'id';
    protected $timestamps = false;
    
    public function __construct()
    {
        parent::__construct();
    }
}
