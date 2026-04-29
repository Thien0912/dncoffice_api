<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dx_file_dinh_kem_model extends MY_Model
{
    protected $table = 'dx_file_dinh_kem';
    protected $primaryKey = 'id_file_dinh_kem';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Thêm file đính kèm
     */
    public function themFileDinhKem($idDeXuat, $fileData, $userId)
    {
        $insertData = [
            'id_de_xuat' => $idDeXuat,
            'ten_file_goc' => $fileData['ten_file_goc'],
            'dung_luong' => $fileData['dung_luong'],
            'duong_dan' => $fileData['duong_dan'],
            'loai_file' => $fileData['loai_file'],
            'created_user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert('dx_file_dinh_kem', $insertData);
        return $this->db->insert_id();
    }

    /**
     * Xóa file đính kèm
     */
    public function xoaFileDinhKem($idFileDinhKem)
    {
        $this->db->where('id_file_dinh_kem', $idFileDinhKem);
        return $this->db->delete('dx_file_dinh_kem');
    }

    /**
     * Lấy danh sách file theo đề xuất
     */
    public function getFilesByDeXuat($idDeXuat)
    {
        $this->db->select('*');
        $this->db->from('dx_file_dinh_kem');
        $this->db->where('id_de_xuat', $idDeXuat);
        return $this->db->get()->result_array();
    }
}
