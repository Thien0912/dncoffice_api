<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dx_binh_luan_model extends MY_Model
{
    protected $table = 'dx_binh_luan';
    protected $primaryKey = 'id_binh_luan';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Thêm bình luận
     */
    public function themBinhLuan($idDeXuat, $noiDung, $userId, $parentId = null)
    {
        $insertData = [
            'id_de_xuat' => $idDeXuat,
            'parent_id' => $parentId,
            'noi_dung' => $noiDung,
            'created_user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert('dx_binh_luan', $insertData);
        return $this->db->insert_id();
    }

    /**
     * Xóa mềm bình luận
     */
    public function xoaBinhLuan($idBinhLuan, $userId)
    {
        $updateData = [
            'deleted_user_id' => $userId,
            'deleted_at' => date('Y-m-d H:i:s')
        ];

        $this->db->where('id_binh_luan', $idBinhLuan);
        $this->db->where('created_user_id', $userId); // Chỉ người tạo mới được xóa
        return $this->db->update('dx_binh_luan', $updateData);
    }

    /**
     * Lấy danh sách bình luận theo dạng cây (tree structure)
     */
    public function getBinhLuanTree($idDeXuat)
    {
        $this->db->select("
            bl.*,
            nv.ql_nguoi_dung_ho_ten as ten_nguoi_binh_luan,
            nv.ql_nguoi_dung_id as ma_nhan_vien,
            COALESCE(NULLIF(hrm_nv.avatar, ''), nv.ql_nguoi_dung_avatar) as avatar,
            hrm_nv.gioi_tinh,
            edv.ten_don_vi as ten_don_vi,
            vtcv.ten_cong_viec as ten_vi_tri_cong_viec
        ", FALSE);
        $this->db->from('dx_binh_luan bl');
        $this->db->join('ql_nguoi_dung nv', 'nv.ql_nguoi_dung_id = bl.created_user_id', 'left');
        $this->db->join('hrm_nhan_vien hrm_nv', 'hrm_nv.ql_nguoi_dung_id = nv.ql_nguoi_dung_id', 'left');
        $this->db->join('e_don_vi edv', 'edv.id_don_vi = hrm_nv.id_don_vi_cong_tac', 'left');
        $this->db->join('hrm_vi_tri_cong_viec vtcv', 'vtcv.id_vi_tri_cong_viec = hrm_nv.id_vi_tri_cong_viec', 'left');
        $this->db->where('bl.id_de_xuat', $idDeXuat);
        $this->db->where('bl.deleted_at IS NULL');
        $this->db->order_by('bl.created_at', 'ASC');
        $comments = $this->db->get()->result_array();

        $uniqueComments = [];
        $seen = [];

        foreach ($comments as $comment) {
            $key = $comment['id_binh_luan'];

            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $uniqueComments[] = $comment;
            }
        }

        $comments = $uniqueComments;

        if (!empty($comments)) {
            foreach ($comments as &$comment) {
                if (!empty($comment['avatar']) && !filter_var($comment['avatar'], FILTER_VALIDATE_URL)) {
                    $comment['avatar'] = encryptString($comment['avatar']);
                }
            }
        }

        // Xây dựng cấu trúc cây
        return $this->buildTree($comments);
    }

    /**
     * Xây dựng cấu trúc cây từ danh sách bình luận phẳng
     */
    private function buildTree($comments, $parentId = null)
    {
        $tree = [];
        foreach ($comments as $comment) {
            if ($comment['parent_id'] == $parentId) {
                $comment['replies'] = $this->buildTree($comments, $comment['id_binh_luan']);
                $comment['so_luong_reply'] = count($comment['replies']);
                $tree[] = $comment;
            }
        }
        return $tree;
    }

    public function get_all_comment($auth)
    {
        $this->db->select('dx_de_xuat.id_de_xuat,dx_de_xuat.tieu_de, dx_de_xuat.created_user_id, dx_binh_luan.id_binh_luan, dx_binh_luan.noi_dung, dx_nguoi_duyet_de_xuat.id_nguoi_duyet');
        $this->db->from('dx_de_xuat');
        $this->db->join('dx_binh_luan', 'dx_de_xuat.id_de_xuat = dx_binh_luan.id_de_xuat');
        $this->db->join('dx_nguoi_duyet_de_xuat', 'dx_de_xuat.id_de_xuat = dx_nguoi_duyet_de_xuat.id_de_xuat', 'left');

        // Lấy theo id của người đang đăng nhập có trong dx_de_xuat hoặc dx_nguoi_duyet_de_xuat
        $this->db->group_start();
        $this->db->where('dx_de_xuat.created_user_id', $auth['ql_nguoi_dung_id']);
        $this->db->or_where('dx_nguoi_duyet_de_xuat.id_nguoi_duyet', $auth['ql_nguoi_dung_id']);
        $this->db->group_end();

        $query = $this->db->get();
        $data = $query->result_array();
        $sql = $this->db->last_query();

        foreach ($data as &$item) {
            $item['la_de_xuat_nhan'] = $item['id_nguoi_duyet'] == $auth['ql_nguoi_dung_id'] ? true : false;

            $nguoiTao = $this->db->select('ql_nguoi_dung_ho_ten')->from('ql_nguoi_dung')->where('ql_nguoi_dung_id', $item['created_user_id'])->get()->row_array();
            $item['ten_nguoi_tao'] = $nguoiTao['ql_nguoi_dung_ho_ten'];

            $nguoiDuyet = $this->db->select('ql_nguoi_dung_ho_ten')->from('ql_nguoi_dung')->where('ql_nguoi_dung_id', $item['id_nguoi_duyet'])->get()->row_array();
            $item['ten_nguoi_duyet'] = $nguoiDuyet ? $nguoiDuyet['ql_nguoi_dung_ho_ten'] : '';
        }

        return [
            'data' => $data,
            'sql' => $sql
        ];
    }
}
