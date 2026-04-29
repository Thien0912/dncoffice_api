<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class E_don_vi_xu_ly_model extends MY_Model
{
    protected $table = 'e_don_vi_xu_ly';
    protected $primaryKey = 'id_don_vi_xu_ly';
    protected $timestamps = true;
    protected $createdAtField = 'ngay_tao';
    protected $updatedAtField = 'ngay_sua';

    public function __construct()
    {
        parent::__construct();
    }
    public function getUnitIdsByDocId($docId)
    {
        $result = $this->db
            ->select('e_don_vi_xu_ly.id_don_vi')
            ->from('e_don_vi_xu_ly')
            ->join('e_xu_ly', 'e_xu_ly.id_xu_ly = e_don_vi_xu_ly.id_xu_ly', 'right')
            ->where('e_xu_ly.id_van_ban', $docId)
            ->get()
            ->result_array();

        if (empty($result)) {
            return [];
        }

        return array_unique(array_column($result, 'id_don_vi'));
    }
}
