<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dx_loai_de_xuat_don_vi_model extends MY_Model
{
    protected $table = 'dx_loai_de_xuat_don_vi';
    protected $primaryKey = 'id_dx_loai_de_xuat_don_vi';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Lấy danh sách đơn vị trình ký theo loại đề xuất
     */
    public function getByLoaiDeXuat($id_dx_loai_de_xuat)
    {
        $this->db->select('
            ldxdv.id_dx_loai_de_xuat_don_vi,
            ldxdv.id_dx_loai_de_xuat,
            ldxdv.id_don_vi,
            ldxdv.thu_tu_trinh_ky,
            dv.ten_don_vi,
            dv.ma_don_vi
        ');
        $this->db->from('dx_loai_de_xuat_don_vi ldxdv');
        $this->db->join('e_don_vi dv', 'dv.id_don_vi = ldxdv.id_don_vi', 'left');
        $this->db->where('ldxdv.id_dx_loai_de_xuat', $id_dx_loai_de_xuat);
        $this->db->where('ldxdv.deleted_at IS NULL');
        $this->db->order_by('ldxdv.thu_tu_trinh_ky', 'ASC');
        return $this->db->get()->result_array();
    }

    /**
     * Đồng bộ danh sách đơn vị trình ký cho một loại đề xuất
     * - Soft delete các bản ghi cũ
     * - Insert các bản ghi mới theo đúng thứ tự
     */
    public function syncDonVi($id_dx_loai_de_xuat, array $donViList, $user_id)
    {
        // Hard delete toàn bộ don_vi cũ của loại này
        // (không soft delete vì unique key không bao gồm deleted_at → gây duplicate)
        $this->db->where('id_dx_loai_de_xuat', $id_dx_loai_de_xuat);
        $this->db->delete('dx_loai_de_xuat_don_vi');

        // Insert danh sách mới theo thứ tự, bắt đầu từ 2
        // (cấp 1 là đơn vị người tạo, tự động - không lưu vào DB)
        foreach ($donViList as $index => $item) {
            $id_don_vi = isset($item['id_don_vi']) ? (int) $item['id_don_vi'] : null;
            if (!$id_don_vi) continue;

            $this->db->insert('dx_loai_de_xuat_don_vi', [
                'id_dx_loai_de_xuat' => $id_dx_loai_de_xuat,
                'id_don_vi'          => $id_don_vi,
                'thu_tu_trinh_ky'    => $index + 2, // +2: cấp 1 = người tạo
                'created_user_id'    => $user_id,
            ]);
        }
    }
    /**
     * Lấy các cấp trong quy trình trình ký khi tạo/xem đề xuất
     * - Cấp 1 LUÔN là đơn vị của người tạo (lấy từ $auth)
     * - Từ cấp 2 trở đi: lấy từ cấu hình dx_loai_de_xuat_don_vi
     * Mỗi cấp kèm danh sách lãnh đạo đơn vị (lanh_dao_don_vi) và nhân sự (nhan_su)
     *
     * @param int   $id_dx_loai_de_xuat
     * @param array $auth  Thông tin user đang đăng nhập (cần id_don_vi)
     * @return array
     */
    public function getCacCapTrinhKy($id_dx_loai_de_xuat, $auth)
    {
        $result = [];

        // ── Cấp 1: Đơn vị người tạo (tự động) ──
        $id_don_vi_nguoi_tao = isset($auth['id_don_vi']) ? (int) $auth['id_don_vi'] : null;

        $donViNguoiTao = null;
        if ($id_don_vi_nguoi_tao) {
            $donViNguoiTao = $this->db
                ->select('id_don_vi, ten_don_vi, ma_don_vi')
                ->from('e_don_vi')
                ->where('id_don_vi', $id_don_vi_nguoi_tao)
                ->get()->row_array();
        }

        $lanhDaoCap1 = $id_don_vi_nguoi_tao
            ? $this->_getLanhDaoDonVi($id_don_vi_nguoi_tao)
            : [];

        $result[] = [
            'thu_tu_trinh_ky'    => 1,
            'id_don_vi'          => $donViNguoiTao['id_don_vi'] ?? $id_don_vi_nguoi_tao,
            'ten_don_vi'         => $donViNguoiTao['ten_don_vi'] ?? 'Đơn vị người tạo',
            'la_don_vi_nguoi_tao' => true,
            'lanh_dao_don_vi'    => $lanhDaoCap1,
            'nhan_su'            => [],
        ];

        // ── Cấp 2+: Các đơn vị cấu hình theo loại đề xuất ──
        $donViCauHinh = $this->db
            ->select('dldv.id_don_vi, dldv.thu_tu_trinh_ky, dv.ten_don_vi, dv.ma_don_vi')
            ->from('dx_loai_de_xuat_don_vi dldv')
            ->join('e_don_vi dv', 'dv.id_don_vi = dldv.id_don_vi', 'left')
            ->where('dldv.id_dx_loai_de_xuat', $id_dx_loai_de_xuat)
            ->where('dldv.deleted_at IS NULL')
            ->order_by('dldv.thu_tu_trinh_ky', 'ASC')
            ->get()->result_array();

        foreach ($donViCauHinh as $dv) {
            $idDonVi  = (int) $dv['id_don_vi'];
            $lanhDao  = $this->_getLanhDaoDonVi($idDonVi);

            $result[] = [
                'thu_tu_trinh_ky'     => (int) $dv['thu_tu_trinh_ky'], // 2, 3, 4... từ DB
                'id_don_vi'           => $idDonVi,
                'ten_don_vi'          => $dv['ten_don_vi'] ?? '',
                'la_don_vi_nguoi_tao' => false,
                'lanh_dao_don_vi'     => $lanhDao,
                'nhan_su'             => [],
            ];
        }

        return $result;
    }

    /**
     * Helper: Lấy danh sách lãnh đạo của một đơn vị (từ bảng e_lanh_dao_don_vi)
     */
    private function _getLanhDaoDonVi($id_don_vi)
    {
        return $this->db
            ->select('nd.ql_nguoi_dung_id, nv.ho_va_ten, nv.email, nd.ql_nguoi_dung_avatar')
            ->from('e_lanh_dao_don_vi ld')
            ->join('ql_nguoi_dung nd', 'nd.ql_nguoi_dung_id = ld.ql_nguoi_dung_id', 'left')
            ->join('hrm_nhan_vien nv', 'nv.ql_nguoi_dung_id = nd.ql_nguoi_dung_id', 'left')
            ->where('ld.id_don_vi', $id_don_vi)
            ->get()->result_array();
    }
}
