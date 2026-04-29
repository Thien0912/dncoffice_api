<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class E_don_vi_model extends MY_Model
{
    protected $table = 'e_don_vi';
    protected $primaryKey = 'id_don_vi';
    protected $timestamps = false;
    protected $createdAtField = 'ngay_tao';
    protected $updatedAtField = 'ngay_sua';

    public function __construct()
    {
        parent::__construct();
        require_once(APPPATH . 'libraries/Common.php');
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $columns = [], $searchKey = array())
    {
        $this->db->from('e_don_vi');
        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('ten_don_vi', $searchValue);
            $this->db->or_like('ma_don_vi', $searchValue);
            $this->db->or_like('email', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                if (!empty($value)) {
                    switch ($key) {
                        case 'selectedClassify':
                            switch ($value) {
                                case 'all':
                                    break;
                                default:
                                    $this->db->where('e_don_vi.loai', $value);
                                    break;
                            }
                            break;
                        default:
                            # code...
                            break;
                    }
                }
            }
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query 

        if (!empty($orderBy)) {
            foreach ($orderBy as $order) {
                $colName = $order['column'] ?? null;
                $dir = $order['dir'] ?? 'asc';

                if ($colName) {
                    $this->db->order_by("e_don_vi.$colName", $dir);
                }
            }
        }

        if ($length != '-1')
            $this->db->limit($length, $start);

        $query = $this->db->get();
        $data = $query->result_array();
        $sql = $this->db->last_query();

        foreach ($data as &$dt) {
            $ma_vai_tro_hop_le = [
                'VAN_THU_TO_CHUC_HANH_CHINH',
                'VAN_THU_DON_VI',
                'LANH_DAO_TCHC',
                'LANH_DAO_DON_VI',
                'SUPER_ADMIN',
            ];

            $danhSachNhanSu = $this->db
                ->select('
                ql_nguoi_dung.ql_nguoi_dung_id,
                ql_nguoi_dung.ql_nguoi_dung_ho_ten,
                ql_nguoi_dung.ql_nguoi_dung_email,
                ql_vai_tro.ql_ma_vai_tro,
                ql_vai_tro.ql_vai_tro_ten
                ')
                ->from('ql_nguoi_dung')
                ->join('e_don_vi', 'ql_nguoi_dung.id_don_vi = e_don_vi.id_don_vi')
                ->join('ql_vai_tro_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = ql_vai_tro_nguoi_dung.ql_nguoi_dung_id')
                ->join('ql_vai_tro', 'ql_vai_tro_nguoi_dung.ql_vai_tro_id = ql_vai_tro.ql_vai_tro_id')
                ->where('ql_nguoi_dung.active_flag', 1)
                ->where('ql_nguoi_dung.id_don_vi', $dt['id_don_vi'])
                ->get()
                ->result_array();

            $dt['nhan_su'] = $danhSachNhanSu;
            
            $nguoiCoQuyenVanThu = $this->db
                ->select('
                ql_nguoi_dung.ql_nguoi_dung_id,
                ql_nguoi_dung.ql_nguoi_dung_ho_ten,
                ql_nguoi_dung.ql_nguoi_dung_email,
                ql_vai_tro.ql_ma_vai_tro,
                ql_vai_tro.ql_vai_tro_ten
                ')
                ->from('ql_nguoi_dung')
                ->join('e_don_vi', 'ql_nguoi_dung.id_don_vi = e_don_vi.id_don_vi')
                ->join('ql_vai_tro_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = ql_vai_tro_nguoi_dung.ql_nguoi_dung_id')
                ->join('ql_vai_tro', 'ql_vai_tro_nguoi_dung.ql_vai_tro_id = ql_vai_tro.ql_vai_tro_id')
                ->where('ql_nguoi_dung.active_flag', 1)
                ->where('ql_nguoi_dung.id_don_vi', $dt['id_don_vi'])
                ->where_in('ql_ma_vai_tro', $ma_vai_tro_hop_le)
                ->get()
                ->result_array();

            $dt['nguoi_co_quyen_van_thu'] = $nguoiCoQuyenVanThu;

            $lanhDaoDonVi = $this->db
                ->select('
                ql_nguoi_dung.ql_nguoi_dung_id,
                ql_nguoi_dung.ql_nguoi_dung_ho_ten,
                ql_nguoi_dung.ql_nguoi_dung_email
                ')
                ->from('e_lanh_dao_don_vi')
                ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_lanh_dao_don_vi.ql_nguoi_dung_id')
                ->join('e_don_vi', 'e_lanh_dao_don_vi.id_don_vi = e_don_vi.id_don_vi')
                ->where('ql_nguoi_dung.active_flag', 1)
                ->where('e_lanh_dao_don_vi.id_don_vi', $dt['id_don_vi'])
                ->get()
                ->result_array();

            $dt['lanh_dao_don_vi'] = $lanhDaoDonVi;
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'sql' => $sql
        ];
    }

    public function getAllTheoPhongBan()
    {
        $this->db->from('e_don_vi');
        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        $query = $this->db->get();
        $data = $query->result_array();

        $loaiList = array_column(Common::LOAI_DON_VI, 'value');
        $loaiList = array_values($loaiList);

        // ── 1. Tạo nhóm theo Trường (mỗi trường 1 nhóm, options là các khoa thuộc trường đó) ──
        $nhomTheoTruong = [];
        foreach (Common::TRUONG as $key => $truongInfo) {
            $nhomTheoTruong[$key] = [
                'label'   => $truongInfo['label'],
                'options' => []
            ];
        }

        // ── 2. Tạo nhóm theo loại đơn vị (giữ nguyên như cũ) ──
        $dataPhongBan = [];
        foreach ($loaiList as $loai) {
            $dataPhongBan[] = [
                'label'   => $loai,
                'options' => []
            ];
        }

        // ── 3. Phân loại từng đơn vị ──
        foreach ($data as $dt) {
            $truong = !empty($dt['truong']) ? trim($dt['truong']) : null;
            $truongLabel = ($truong && isset(Common::TRUONG[$truong]))
                ? Common::TRUONG[$truong]['label']
                : null;

            $option = [
                'value'        => $dt['id_don_vi'],
                'text'         => $dt['ten_don_vi'],
                'truong'       => $truong,
                'truong_label' => $truongLabel
            ];

            // Nếu là KHOA_BOMON và có thuộc trường → đưa vào nhóm trường tương ứng
            if ($dt['loai'] === 'KHOA_BOMON' && $truong && isset($nhomTheoTruong[$truong])) {
                $nhomTheoTruong[$truong]['options'][] = $option;
            } else {
                // Còn lại → đưa vào nhóm loại đơn vị như cũ
                foreach ($dataPhongBan as &$dp) {
                    if ($dp['label'] == $dt['loai']) {
                        $dp['options'][] = $option;
                        break;
                    }
                }
                unset($dp);
            }
        }

        // ── 4. Gán lại label (human-readable) cho nhóm loại đơn vị ──
        foreach ($dataPhongBan as &$pb) {
            $valueToFind = $pb['label'];
            $result = array_filter(Common::LOAI_DON_VI, function ($item) use ($valueToFind) {
                return isset($item['value']) && $item['value'] === $valueToFind;
            });
            $found = reset($result);
            $pb['label'] = $found['label'];
        }
        unset($pb);

        // ── 5. Ghép: Lãnh đạo → Trường → còn lại ──
        $lanhDao  = array_shift($dataPhongBan); // Lấy nhóm Lãnh đạo (đầu tiên trong LOAI_DON_VI)
        $result   = [$lanhDao];
        foreach (array_values($nhomTheoTruong) as $nhom) {
            $result[] = $nhom;
        }
        foreach ($dataPhongBan as $pb) {
            $result[] = $pb;
        }

        return [
            'recordsTotal' => $recordsTotal,
            'data'         => $result,
        ];
    }

    public function getAllTheoPhongBanV2()
    {
        $this->db->from('e_don_vi');
        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        $query = $this->db->get();
        $data = $query->result_array();

        // ── 1. Nhóm Trường ──
        $nhomTruong = [];
        foreach (Common::TRUONG as $key => $truongInfo) {
            $nhomTruong[$key] = [
                'label'   => $truongInfo['label'],
                'options' => []
            ];
        }

        // Nhóm Khoa (Không thuộc trường nào)
        $nhomKhoa = [
            'label' => 'Khoa',
            'options' => []
        ];

        // ── 2. Nhóm Phòng ban ──
        $nhomPhongBan = [
            'label' => 'Phòng ban',
            'options' => []
        ];

        // ── 3. Nhóm Trung tâm ──
        $nhomTrungTam = [
            'label' => 'Trung tâm',
            'options' => []
        ];

        // ── 4. Nhóm Doanh nghiệp ──
        $nhomDoanhNghiep = [
            'label' => 'Doanh nghiệp',
            'options' => []
        ];

        // ── 5. Khác ──
        $nhomKhac = [
            'label' => 'Khác',
            'options' => []
        ];

        // ── Phân loại từng đơn vị ──
        foreach ($data as $dt) {
            $loai = $dt['loai'];
            $truong = !empty($dt['truong']) ? trim($dt['truong']) : null;
            $truongLabel = ($truong && isset(Common::TRUONG[$truong]))
                ? Common::TRUONG[$truong]['label']
                : null;

            $option = [
                'value'        => $dt['id_don_vi'],
                'text'         => $dt['ten_don_vi'],
                'truong'       => $truong,
                'truong_label' => $truongLabel
            ];

            if ($loai === 'KHOA_BOMON') {
                if ($truong && isset($nhomTruong[$truong])) {
                    $nhomTruong[$truong]['options'][] = $option;
                } else {
                    $nhomKhoa['options'][] = $option;
                }
            } elseif ($loai === 'PHONG' || $loai === 'BAN') {
                $nhomPhongBan['options'][] = $option;
            } elseif ($loai === 'TRUNG_TAM') {
                $nhomTrungTam['options'][] = $option;
            } elseif ($loai === 'DOANH_NGHIEP') {
                $nhomDoanhNghiep['options'][] = $option;
            } else {
                $nhomKhac['options'][] = $option;
            }
        }

        // ── Gom kết quả ──
        $result = [];
        
        foreach ($nhomTruong as $nhom) {
            if (!empty($nhom['options'])) {
                $result[] = $nhom;
            }
        }
        
        if (!empty($nhomKhoa['options'])) {
            $result[] = $nhomKhoa;
        }

        if (!empty($nhomPhongBan['options'])) {
            $result[] = $nhomPhongBan;
        }

        if (!empty($nhomTrungTam['options'])) {
            $result[] = $nhomTrungTam;
        }

        if (!empty($nhomDoanhNghiep['options'])) {
            $result[] = $nhomDoanhNghiep;
        }

        if (!empty($nhomKhac['options'])) {
            $result[] = $nhomKhac;
        }

        return [
            'recordsTotal' => $recordsTotal,
            'data'         => $result,
        ];
    }

    public function getListExport($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = [], $fromDate = null, $toDate = null)
    {
        $this->db->from('e_don_vi');

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('ten_don_vi', $searchValue);
            $this->db->or_like('ma_don_vi', $searchValue);
            $this->db->or_like('email', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                $this->db->where('e_don_vi.' . $key, $value);
            }
        }

        $this->db->select('
            e_don_vi.*
        ');

        $query = $this->db->get();
        $data = $query->result_array();

        return $data;
    }
}
