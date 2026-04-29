<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Hrm_minh_chung_model extends MY_Model
{
    protected $table = 'hrm_minh_chung';
    protected $primaryKey = 'id_minh_chung';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Lấy tất cả minh chứng của 1 nhân viên, grouped by loại minh chứng
     */
    public function getByNhanVien($idNhanVien)
    {
        $results = $this->db
            ->select('mc.*, lmc.ma_loai, lmc.ten_loai, lmc.thu_tu')
            ->from('hrm_minh_chung mc')
            ->join('hrm_loai_minh_chung lmc', 'lmc.id_loai_minh_chung = mc.id_loai_minh_chung', 'left')
            ->where('mc.id_nhan_vien', $idNhanVien)
            ->where('mc.deleted_at IS NULL', null, false)
            ->order_by('lmc.thu_tu', 'ASC')
            ->order_by('mc.created_at', 'DESC')
            ->get()
            ->result_array();

        // Encrypt file paths
        foreach ($results as &$row) {
            if (!empty($row['file_path'])) {
                $row['file_path'] = encryptString(encryptString($row['file_path']) . '?no_login=1');
            }
        }
        unset($row);

        // Group by loai_minh_chung
        $grouped = [];
        foreach ($results as $row) {
            $key = $row['id_loai_minh_chung'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'id_loai_minh_chung' => $row['id_loai_minh_chung'],
                    'ma_loai' => $row['ma_loai'],
                    'ten_loai' => $row['ten_loai'],
                    'thu_tu' => $row['thu_tu'],
                    'files' => []
                ];
            }
            $grouped[$key]['files'][] = [
                'id_minh_chung' => $row['id_minh_chung'],
                'file_path' => $row['file_path'],
                'file_name' => $row['file_name'],
                'file_extension' => $row['file_extension'],
                'file_size' => $row['file_size'],
                'created_at' => $row['created_at'],
            ];
        }

        return array_values($grouped);
    }

    /**
     * Sync file từ module khác (chứng chỉ, bằng cấp...) vào bảng minh chứng.
     *
     * @param int         $idNhanVien
     * @param int         $idLoaiMinhChung
     * @param array       $files           Mảng: [['file_path'=>..., 'file_name'=>..., ...], ...]
     * @param int         $userId
     * @param string|null $refTable        Bảng nguồn, vd: 'hrm_nhan_vien_bang_cap'
     * @param int|null    $refId           ID bản ghi nguồn
     */
    public function syncFromModule($idNhanVien, $idLoaiMinhChung, $files, $userId, $refTable = null, $refId = null)
    {
        if (empty($files))
            return;

        foreach ($files as $file) {
            if (empty($file['file_path']))
                continue;

            $this->db->insert($this->table, [
                'id_nhan_vien' => $idNhanVien,
                'id_loai_minh_chung' => $idLoaiMinhChung,
                'file_path' => $file['file_path'],
                'file_name' => $file['file_name'] ?? basename($file['file_path']),
                'file_extension' => $file['file_extension'] ?? null,
                'file_size' => $file['file_size'] ?? null,
                'ref_table' => $refTable,
                'ref_id' => $refId,
                'created_user_id' => $userId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Xóa tất cả minh chứng sync từ module khác cho 1 nhân viên + 1 loại.
     * Dùng khi update/delete ở module gốc cần re-sync toàn bộ.
     */
    public function deleteSyncedByNhanVienAndLoai($idNhanVien, $idLoaiMinhChung, $userId)
    {
        $this->db
            ->where('id_nhan_vien', $idNhanVien)
            ->where('id_loai_minh_chung', $idLoaiMinhChung)
            ->where('deleted_at IS NULL', null, false)
            ->update($this->table, [
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_user_id' => $userId,
            ]);
    }

    /**
     * Lấy danh sách minh chứng chưa xóa theo ref_table + ref_ids.
     * Dùng trước khi unlink file vật lý.
     */
    public function getActiveByRef($refTable, $refIds)
    {
        if (empty($refIds))
            return [];

        return $this->db
            ->select('id_minh_chung, file_path')
            ->from($this->table)
            ->where('ref_table', $refTable)
            ->where_in('ref_id', (array) $refIds)
            ->where('deleted_at IS NULL', null, false)
            ->get()
            ->result_array();
    }

    /**
     * Soft-delete tất cả minh chứng thuộc các bản ghi nguồn cụ thể.
     * Dùng khi xóa bangcap/chungchi cần cascade xóa ảnh minh chứng.
     */
    public function deleteByRef($refTable, $refIds, $userId)
    {
        if (empty($refIds))
            return;

        $this->db
            ->where('ref_table', $refTable)
            ->where_in('ref_id', (array) $refIds)
            ->where('deleted_at IS NULL', null, false)
            ->update($this->table, [
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_user_id' => $userId,
            ]);
    }
}
