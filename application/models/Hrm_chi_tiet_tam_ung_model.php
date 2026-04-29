<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @property Common $common
 */

class Hrm_chi_tiet_tam_ung_model extends MY_Model
{
    protected $table = 'hrm_chi_tiet_tam_ung';
    protected $primaryKey = 'id_chi_tiet_tam_ung';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->library(['Common']);
    }

    public function get_by_tam_ung($id_tam_ung)
    {
        $this->db->from('hrm_chi_tiet_tam_ung AS cttu')
            ->join('hrm_tam_ung AS tu', 'cttu.id_tam_ung = tu.id_tam_ung', 'left')
            ->join('hrm_nhan_vien AS nv', 'nv.id_nhan_vien = tu.id_nhan_vien', 'left');

        $this->db->where('cttu.id_tam_ung', $id_tam_ung);
        $this->db->where('cttu.deleted_at IS NULL');

        $this->db->select(
            'cttu.*, nv.ma_nhan_vien, nv.ho_va_ten, '
        );

        $query = $this->db->get();
        $data = $query->result_array();

        return $data;
    }

    public function get_chi_tiet_tam_ung($chi_tiet_ids = [])
    {
        return $this->db->from('hrm_chi_tiet_tam_ung')
            ->where_in('id_chi_tiet_tam_ung', $chi_tiet_ids)
            ->get()
            ->result_array();
    }

    public function update_status($chi_tiet_ids = [])
    {
        if (empty($chi_tiet_ids) || !is_array($chi_tiet_ids)) {
            return false;
        }

        // Lấy danh sách chi tiết tạm ứng
        $chi_tiet_tam_ung = $this->db->from('hrm_chi_tiet_tam_ung')
            ->where_in('id_chi_tiet_tam_ung', $chi_tiet_ids)
            ->get()
            ->result_array();

        if (empty($chi_tiet_tam_ung)) {
            return false;
        }

        // Cập nhật trạng thái cho các bản ghi chi tiết
        $this->db->where_in('id_chi_tiet_tam_ung', $chi_tiet_ids)
            ->update('hrm_chi_tiet_tam_ung', ['trang_thai' => 1]);

        // Lấy danh sách các id_tam_ung liên quan
        $tam_ung_ids = array_unique(array_column($chi_tiet_tam_ung, 'id_tam_ung'));

        foreach ($tam_ung_ids as $id_tam_ung) {
            // Kiểm tra xem còn chi tiết nào của tạm ứng đó chưa hoàn ứng không
            $remaining = $this->db->from('hrm_chi_tiet_tam_ung')
                ->where('id_tam_ung', $id_tam_ung)
                ->where('trang_thai', 0)
                ->count_all_results();

            if ($remaining == 0) {
                // Cập nhật trạng thái của tạm ứng thành 1 (đã hoàn ứng hết)
                $this->db->where('id_tam_ung', $id_tam_ung)
                    ->update('hrm_tam_ung', ['trang_thai' => 2]);
            }
        }

        return $chi_tiet_tam_ung;
    }
}
