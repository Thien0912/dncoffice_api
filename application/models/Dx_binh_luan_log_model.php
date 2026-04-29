<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dx_binh_luan_log_model extends MY_Model
{
    protected $table = 'dx_binh_luan_log';
    protected $primaryKey = 'id_binh_luan_log';

    protected $timestamps = false; // 👈 QUAN TRỌNG

    public function __construct()
    {
        parent::__construct();
    }
}
