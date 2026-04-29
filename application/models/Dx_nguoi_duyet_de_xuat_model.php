<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dx_nguoi_duyet_de_xuat_model extends MY_Model
{
    protected $table = 'dx_nguoi_duyet_de_xuat';
    protected $primaryKey = 'id_nguoi_duyet_de_xuat';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Thêm người duyệt cho đề xuất
     */
    public function themNguoiDuyet($idDeXuat, $idNguoiDuyet, $capDuyet, $userId)
    {
        $insertData = [
            'id_de_xuat' => $idDeXuat,
            'id_nguoi_duyet' => $idNguoiDuyet,
            'cap_duyet' => $capDuyet,
            'da_duyet' => 0,
            'created_user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert('dx_nguoi_duyet_de_xuat', $insertData);
        return $this->db->insert_id();
    }

    /**
     * Thêm người duyệt theo đơn vị
     */
    public function themNguoiDuyetTheoDonVi($idDeXuat, $idDonVi, $idNguoiDuyet, $capDuyet, $userId)
    {
        $insertData = [
            'id_de_xuat' => $idDeXuat,
            'id_don_vi' => $idDonVi,
            'id_nguoi_duyet' => $idNguoiDuyet,
            'cap_duyet' => $capDuyet,
            'da_duyet' => NULL,
            'created_user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert('dx_nguoi_duyet_de_xuat', $insertData);
        return $this->db->insert_id();
    }

    /**
     * Duyệt đề xuất (cơ chế tuần tự theo cấp)
     */
    public function duyetDeXuat($idDeXuat, $idNguoiDuyet, $daDuyet, $lyDo = null)
    {
        $updateData = [
            'da_duyet' => $daDuyet,
            'ly_do' => $lyDo,
            'thoi_gian_duyet' => date('Y-m-d H:i:s')
        ];

        $this->db->where('id_de_xuat', $idDeXuat);
        $this->db->where('id_nguoi_duyet', $idNguoiDuyet);
        return $this->db->update('dx_nguoi_duyet_de_xuat', $updateData);
    }

    /**
     * Kiểm tra quyền duyệt (chỉ được duyệt khi đến cấp của mình)
     */
    public function kiemTraQuyenDuyet($idDeXuat, $idNguoiDuyet)
    {
        // Lấy thông tin người duyệt
        $this->db->select('nd.*, dx.trang_thai');
        $this->db->from('dx_nguoi_duyet_de_xuat nd');
        $this->db->join('dx_de_xuat dx', 'dx.id_de_xuat = nd.id_de_xuat');
        $this->db->where('nd.id_de_xuat', $idDeXuat);
        $this->db->where('nd.id_nguoi_duyet', $idNguoiDuyet);
        $this->db->where('nd.da_duyet IS NULL OR nd.da_duyet = 0');

        $nguoiDuyet = $this->db->get()->row_array();

        if (!$nguoiDuyet) {
            return false;
        }

        // Lấy cấp duyệt hiện tại (cấp nhỏ nhất chưa được duyệt hoặc bị từ chối)
        $capHienTai = $this->getCapDuyetHienTai($idDeXuat);

        // Chỉ được duyệt nếu đúng cấp của mình
        return $nguoiDuyet['cap_duyet'] == $capHienTai;
    }

    /**
     * Lấy cấp duyệt hiện tại (cấp nhỏ nhất chưa được duyệt hoặc bị từ chối)
     */
    public function getCapDuyetHienTai($idDeXuat)
    {
        $this->db->select_min('cap_duyet');
        $this->db->from('dx_nguoi_duyet_de_xuat');
        $this->db->where('id_de_xuat', $idDeXuat);
        $this->db->where('(da_duyet IS NULL OR da_duyet = 0)');

        $result = $this->db->get()->row_array();
        return $result['cap_duyet'] ?? null;
    }

    /**
     * Kiểm tra tất cả người duyệt cùng cấp đã duyệt chưa
     */
    public function kiemTraCapDaDuyet($idDeXuat, $capDuyet)
    {
        // Đếm tổng số người duyệt cấp này
        $this->db->where('id_de_xuat', $idDeXuat);
        $this->db->where('cap_duyet', $capDuyet);
        $tongNguoi = $this->db->count_all_results('dx_nguoi_duyet_de_xuat');

        // Đếm số người đã duyệt (da_duyet = 1)
        $this->db->where('id_de_xuat', $idDeXuat);
        $this->db->where('cap_duyet', $capDuyet);
        $this->db->where('da_duyet', 1);
        $soDaDuyet = $this->db->count_all_results('dx_nguoi_duyet_de_xuat');

        return $tongNguoi > 0 && $tongNguoi == $soDaDuyet;
    }

    /**
     * Lấy danh sách người duyệt cấp tiếp theo từ cấu hình routing
     * Logic: Nếu không còn đơn vị nào ở thu_tu tiếp theo → đó là cấp cuối
     */
    public function layDanhSachCapTiepTheo($idDeXuat, $capHienTai)
    {
        // Lấy thông tin loại đề xuất
        $this->db->select('dx.id_dx_loai_de_xuat');
        $this->db->from('dx_de_xuat dx');
        $this->db->where('dx.id_de_xuat', $idDeXuat);
        $deXuat = $this->db->get()->row_array();

        if (!$deXuat) {
            return null;
        }

        $idLoaiDeXuat = $deXuat['id_dx_loai_de_xuat'];
        $capTiepTheo = $capHienTai + 1;

        // Lấy danh sách đơn vị cho cấp tiếp theo
        $this->db->select('dv.*');
        $this->db->from('dx_loai_de_xuat_don_vi dv');
        $this->db->where('dv.id_dx_loai_de_xuat', $idLoaiDeXuat);
        $this->db->where('dv.thu_tu', $capTiepTheo);
        $danhSachDonVi = $this->db->get()->result_array();

        if (empty($danhSachDonVi)) {
            return null; // Không còn cấp tiếp theo → đây là cấp cuối
        }

        // Lấy danh sách người duyệt từ các đơn vị
        $danhSachNguoiDuyet = [];
        foreach ($danhSachDonVi as $donVi) {
            // Lấy lãnh đạo đơn vị hoặc người được chỉ định
            if ($donVi['id_nguoi_duyet']) {
                $danhSachNguoiDuyet[] = [
                    'id_don_vi' => $donVi['id_don_vi'],
                    'id_nguoi_duyet' => $donVi['id_nguoi_duyet'],
                    'cap_duyet' => $capTiepTheo
                ];
            }
        }

        if (empty($danhSachNguoiDuyet)) {
            return null;
        }

        // Kiểm tra có còn cấp sau nữa không
        $this->db->select('COUNT(*) as count');
        $this->db->from('dx_loai_de_xuat_don_vi');
        $this->db->where('id_dx_loai_de_xuat', $idLoaiDeXuat);
        $this->db->where('thu_tu', $capTiepTheo + 1);
        $result = $this->db->get()->row_array();
        $coCapSau = $result['count'] > 0;

        return [
            'cap_duyet' => $capTiepTheo,
            'nguoi_duyet' => $danhSachNguoiDuyet,
            'la_cap_cuoi' => !$coCapSau  // Không còn cấp sau → đây là cấp cuối
        ];
    }

    /**
     * Reset về cấp 1 khi bị từ chối
     */
    public function resetVeCap1($idDeXuat)
    {
        // Xóa tất cả trạng thái duyệt của cấp > 1
        $this->db->where('id_de_xuat', $idDeXuat);
        $this->db->where('cap_duyet >', 1);
        $this->db->delete('dx_nguoi_duyet_de_xuat');

        // Reset trạng thái cấp 1 về NULL
        $this->db->where('id_de_xuat', $idDeXuat);
        $this->db->where('cap_duyet', 1);
        $this->db->update('dx_nguoi_duyet_de_xuat', [
            'da_duyet' => NULL,
            'ly_do' => NULL,
            'thoi_gian_duyet' => NULL
        ]);

        return true;
    }

    /**
     * Thêm người duyệt cấp tiếp theo
     */
    public function themNguoiDuyetCapTiepTheo($idDeXuat, $danhSachNguoiDuyet, $userId)
    {
        foreach ($danhSachNguoiDuyet as $nguoiDuyet) {
            $insertData = [
                'id_de_xuat' => $idDeXuat,
                'id_don_vi' => $nguoiDuyet['id_don_vi'],
                'id_nguoi_duyet' => $nguoiDuyet['id_nguoi_duyet'],
                'cap_duyet' => $nguoiDuyet['cap_duyet'],
                'da_duyet' => NULL,
                'created_user_id' => $userId,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->db->insert('dx_nguoi_duyet_de_xuat', $insertData);
        }

        return true;
    }
}
