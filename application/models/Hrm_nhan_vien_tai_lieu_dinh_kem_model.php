<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_nhan_vien_tai_lieu_dinh_kem_model extends MY_Model
{
  protected $table = 'hrm_nhan_vien_tai_lieu_dinh_kem';
  protected $primaryKey = 'id_tai_lieu_dinh_kem';
  protected $timestamps = false;

  public function __construct()
  {
    parent::__construct();
  }
}
