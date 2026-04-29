<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_nhan_vien_thoi_viec_model extends MY_Model
{
    protected $table = 'hrm_nhan_vien_thoi_viec';
    protected $primaryKey = 'id_nhan_vien_thoi_viec';
    protected $timestamps = false;


    public function __construct()
    {
        parent::__construct();
    }

    public function getThuTuc_byIdNhanVien($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array())
    {
        $this->db->select('*');
        $this->db->from('hrm_nhan_vien_thoi_viec')
            ->join('hrm_thu_tuc_thoi_viec', 'hrm_thu_tuc_thoi_viec.id_tttv = hrm_nhan_vien_thoi_viec.id_tttv', 'left');

        $this->db->where('hrm_nhan_vien_thoi_viec.deleted_at IS NULL');

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('hrm_thu_tuc_thoi_viec.ten_thu_tuc', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            $arr = ['ten_thu_tuc'];
            foreach ($searchKey as $key => $value) {
                if (in_array($key, $arr)) {
                    $this->db->like('hrm_thu_tuc_thoi_viec.' . $key, $value);
                } else {
                    if (is_array($value)) {
                        $this->db->where_in('hrm_nhan_vien_thoi_viec.' . $key, $value);
                    } else {
                        $this->db->where('hrm_nhan_vien_thoi_viec.' . $key, $value);
                    }
                }
            }
        }



        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query

        if (!empty($orderBy)) {
            $order = $orderBy['order'];
            $orderColumnIndex = $order[0]['column'];
            $orderDir = $order[0]['dir'];

            $columns = $orderBy['columns'];
            $filed = $columns[$orderColumnIndex]['data'];

            if (!empty($orderDir)) {
                $this->db->order_by('hrm_nhan_vien_thoi_viec.' . $filed, $orderDir);
            }
        }

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        $query = $this->db->get();
        $data = $query->result_array();
        $sql = $this->db->last_query();

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'sql' => $sql
        ];
    }

    public function getNhanVienThoiViec($start = 0, $length = 10, $searchValue = null, $orderBy = [], $columns = [], $searchKey = array(), $fromDate = null, $toDate = null, $auth)
    {
        $this->db->select('
            hrm_nhan_vien.*,
            hrm_nhan_vien_cong_viec.trang_thai,
            e_don_vi.ten_don_vi,
            hrm_vi_tri_cong_viec.ten_cong_viec
        ');
        $this->db->from('hrm_nhan_vien');
        $this->db->join('hrm_nhan_vien_cong_viec', 'hrm_nhan_vien_cong_viec.id_nhan_vien = hrm_nhan_vien.id_nhan_vien', 'left');
        $this->db->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_nhan_vien.id_don_vi_cong_tac', 'left');
        $this->db->join('hrm_vi_tri_cong_viec', 'hrm_vi_tri_cong_viec.id_vi_tri_cong_viec = hrm_nhan_vien_cong_viec.id_vi_tri_cong_viec', 'left');
        $this->db->where('hrm_nhan_vien.deleted_at IS NULL');
        $this->db->group_by('hrm_nhan_vien.id_nhan_vien');
        // Lọc trạng thái
        $this->db->where_in('hrm_nhan_vien_cong_viec.trang_thai', [
            Common::TRANG_THAI_CONG_VIEC['DANG_LAM_THU_TUC_THOI_VIEC']['value'],
            Common::TRANG_THAI_CONG_VIEC['NGHI_VIEC']['value']
        ]);

        // Tổng số bản ghi
        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        // Tìm kiếm
        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('hrm_nhan_vien.ho_va_ten', $searchValue); // Tìm kiếm theo tên nhân viên
            $this->db->or_like('hrm_nhan_vien.email', $searchValue); // Tìm kiếm theo tên nhân viên
            $this->db->or_like('e_don_vi.ten_don_vi', $searchValue);
            $this->db->or_like('hrm_vi_tri_cong_viec.ten_cong_viec', $searchValue);
            $this->db->group_end();
        }

        // Lọc theo các điều kiện khác
        if (!empty($searchKey)) {
            $arr = ['ma_nhan_vien', 'ho_va_ten', 'email'];
            foreach ($searchKey as $key => $value) {
                if (in_array($key, $arr)) {
                    $this->db->like('hrm_nhan_vien.' . $key, $value);
                } else {
                    if (is_array($value)) {
                        $this->db->where_in('hrm_nhan_vien.' . $key, $value);
                    } else {
                        $this->db->where('hrm_nhan_vien.' . $key, $value);
                    }
                }
            }
        }

        if (!empty($columns)) {
            // Map tên cột hiển thị (alias) sang cột thật trong DB
            $columnMapping = [
                'ten_cong_viec'    => 'hrm_vi_tri_cong_viec.ten_cong_viec',
                'ten_don_vi' => 'e_don_vi.ten_don_vi',
                // Mặc định nếu không map thì nó sẽ lấy e_van_ban.[columnName]
            ];
            $this->handleDatatableColumns($columns, $columnMapping);
        }

        // Tổng số bản ghi sau khi lọc
        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE);

        // Sắp xếp
        if (!empty($orderBy)) {
            $order = $orderBy['order'];
            $orderColumnIndex = $order[0]['column'];
            $orderDir = $order[0]['dir'];

            $columns = $orderBy['columns'];
            $filed = $columns[$orderColumnIndex]['data'];

            if (!empty($orderDir)) {
                $this->db->order_by('hrm_nhan_vien.' . $filed, $orderDir);
            }
        }

        // Giới hạn số lượng bản ghi
        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        // Lấy dữ liệu
        $query = $this->db->get();
        $data = $query->result_array();
        $sql = $this->db->last_query();

        foreach ($data as $key => $item) {
            $thoiviec = $this->db->from('hrm_nhan_vien_thoi_viec')
                ->where('hrm_nhan_vien_thoi_viec.id_nhan_vien', $item['id_nhan_vien'])
                ->where('deleted_at IS NULL')
                ->get()
                ->result_array();

            $data[$key]['thu_tuc_thoi_viec'] = $thoiviec;
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'sql' => $sql
        ];
    }

    private function handleDatatableColumns(array $columns, array $columnMapping): void
    {
        foreach ($columns as $column) {
            $columnName = $column['data'] ?? '';
            if ($columnName === '') continue;

            $searchValue = $column['search']['value'] ?? '';

            // Ưu tiên lấy search từ fixed nếu có
            if (!empty($column['search']['fixed']) && is_array($column['search']['fixed'])) {
                foreach ($column['search']['fixed'] as $fixedSearch) {
                    if (!empty($fixedSearch['term'])) {
                        $searchValue = convertDateToISO($fixedSearch['term']);
                        break;
                    }
                }
            }

            if ($searchValue !== '') {
                $dbColumn = $columnMapping[$columnName] ?? "hrm_nhan_vien_thoi_viec.$columnName";
                $this->db->like($dbColumn, $searchValue);
            }
        }
    }

    private function handleDatatableOrdering(array $orderBy, array $columns): void
    {
        if (empty($orderBy)) return;

        foreach ($orderBy as $order) {
            $colIndex = $order['column'] ?? null;
            $dir = $order['dir'] ?? 'asc';

            if (isset($columns[$colIndex])) {
                $colName = $columns[$colIndex]['data'] ?? null;
                if ($colName) {
                    $this->db->order_by("hrm_nhan_vien_thoi_viec.$colName", $dir);
                }
            }
        }
    }


    public function getAllNhanvien_thoiviec($post)
    {
        $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
        $draw   = $post['draw'] ?? 1;
        $start  = $post['start'] ?? 0;
        $length = $post['length'] ?? 10;

        $searchValue = $post['search']['value'] ?? '';
        $filters = $post['filter'] ?? [];

        // ==== Base select ====
        $this->db->select('
                hrm_nhan_vien.id_nhan_vien,
                hrm_nhan_vien.ho_va_ten,
                hrm_nhan_vien.ma_nhan_vien,
                hrm_nhan_vien.gioi_tinh,
                hrm_nhan_vien.so_dien_thoai,
                hrm_nhan_vien.ngay_sinh,
                hrm_nhan_vien.email,
                hrm_nhan_vien.avatar,
                hrm_nhan_vien_cong_viec.trang_thai,
                hrm_vi_tri_cong_viec.ten_cong_viec,
                e_don_vi.ten_don_vi,
                hrm_nhan_vien_cong_viec.ly_do_thoi_viec,
                hrm_nhan_vien_cong_viec.ngay_lam_chinh_thuc,
                hrm_nhan_vien_cong_viec.ngay_lam_chinh_thuc_ket_thuc
            ', FALSE);
        $this->db->from('hrm_nhan_vien');
        $this->db->join('hrm_nhan_vien_cong_viec', 'hrm_nhan_vien_cong_viec.id_nhan_vien = hrm_nhan_vien.id_nhan_vien', 'left');
        $this->db->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_nhan_vien.id_don_vi_cong_tac', 'left');
        $this->db->join('hrm_vi_tri_cong_viec', 'hrm_vi_tri_cong_viec.id_vi_tri_cong_viec = hrm_nhan_vien_cong_viec.id_vi_tri_cong_viec', 'left');

        $this->db->where('hrm_nhan_vien.deleted_at IS NULL', NULL, FALSE);
        $this->db->group_start();
        $this->db->where('hrm_nhan_vien_cong_viec.trang_thai IS NULL', NULL, FALSE);
        $this->db->or_where_in('hrm_nhan_vien_cong_viec.trang_thai', ['DANG_LAM_THU_TUC_THOI_VIEC', 'NGHI_VIEC']);
        $this->db->group_end();
        $this->db->group_by('hrm_nhan_vien.id_nhan_vien');
        $this->db->order_by('hrm_nhan_vien_cong_viec.ngay_lam_chinh_thuc_ket_thuc', 'DESC');
        // ==== Đếm total (chưa filter) ====
        $count_query = clone $this->db;
        $recordsTotal = $count_query->count_all_results();

        if (!empty($searchValue)) {
            $this->db->group_start();
            $this->db->like('hrm_nhan_vien.ho_va_ten', $searchValue);
            $this->db->or_like('hrm_nhan_vien.email', $searchValue);
            $this->db->or_like('e_don_vi.ten_don_vi', $searchValue);
            $this->db->or_like('hrm_vi_tri_cong_viec.ten_cong_viec', $searchValue);
            $this->db->group_end();
        }

        // ==== Áp filter ====
        $columnMapping = [
            'ten_don_vi'    => 'e_don_vi.ten_don_vi',
            'ten_cong_viec' => 'hrm_vi_tri_cong_viec.ten_cong_viec',
            'trang_thai'    => 'hrm_nhan_vien_cong_viec.trang_thai',
            'ngay_lam_chinh_thuc'  => 'hrm_nhan_vien_cong_viec.ngay_lam_chinh_thuc',
            'ngay_lam_chinh_thuc_ket_thuc' => 'hrm_nhan_vien_cong_viec.ngay_lam_chinh_thuc_ket_thuc',
            'ly_do_thoi_viec' => 'hrm_nhan_vien_cong_viec.ly_do_thoi_viec',
        ];
        $this->applyFilter($this->db, $columnMapping, $post);
        if (!empty($filters)) {
            if (!empty($filters['id_don_vi'])) {
                $this->db->where('hrm_nhan_vien.id_don_vi_cong_tac', $filters['id_don_vi']);
            }

            if (!empty($filters['id_vi_tri_cong_viec'])) {
                $this->db->where('hrm_nhan_vien_cong_viec.id_vi_tri_cong_viec', $filters['id_vi_tri_cong_viec']);
            }

            if (!empty($filters['ma_nhan_vien'])) {
                $this->db->like('hrm_nhan_vien.ma_nhan_vien', $filters['ma_nhan_vien']);
            }

            if (!empty($filters['ho_ten'])) {
                $this->db->like('hrm_nhan_vien.ho_va_ten', $filters['ho_ten']);
            }

            //trang_thai
            if (!empty($filters['trang_thai'])) {
                $this->db->where('hrm_nhan_vien_cong_viec.trang_thai', $filters['trang_thai']);
            }


            if (!empty($filters['ngay_lam_chinh_thuc'])) {
                $from = $filters['ngay_lam_chinh_thuc']['from'] ?? null;
                $to   = $filters['ngay_lam_chinh_thuc']['to'] ?? null;

                if ($from) $this->db->where("DATE(hrm_nhan_vien_cong_viec.ngay_lam_chinh_thuc) >=", $from);
                if ($to) $this->db->where("DATE(hrm_nhan_vien_cong_viec.ngay_lam_chinh_thuc) <=", $to);
            }

            if (!empty($filters['ngay_lam_chinh_thuc_ket_thuc'])) {
                $from = $filters['ngay_lam_chinh_thuc_ket_thuc']['from'] ?? null;
                $to   = $filters['ngay_lam_chinh_thuc_ket_thuc']['to'] ?? null;

                if ($from) $this->db->where("DATE(hrm_nhan_vien_cong_viec.ngay_lam_chinh_thuc_ket_thuc) >=", $from);
                if ($to) $this->db->where("DATE(hrm_nhan_vien_cong_viec.ngay_lam_chinh_thuc_ket_thuc) <=", $to);
            }
        }

        // ==== Đếm filtered ====
        $count_query2 = clone $this->db;
        $recordsFiltered = $count_query2->count_all_results();

        // ==== Lấy data ====
        if ($length != -1) {
            $this->db->limit($length, $start);
        }
        $data = $this->db->get()->result_array();

        foreach ($data as &$item) {
            if (!empty($item['avatar'])) {
                $item['avatar'] = encryptString($item['avatar']);
            }
        }
        
        $sql  = $this->db->last_query();

        return [
            'draw'            => intval($draw),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
            'sql'             => $sql,
            'filters'         => $filters,

        ];
    }

    public function getThoiviec_byNhanvien($post)
    {
        $draw   = $post['draw'] ?? 1;
        $start  = $post['start'] ?? 0;
        $length = $post['length'] ?? 10;

        $searchValue = $post['search']['value'] ?? '';
        $filters = $post['filter'] ?? [];

        // ==== Base select ====
        $this->db->select('
                hrm_nhan_vien_thoi_viec.id_nhan_vien_thoi_viec,
                hrm_nhan_vien_thoi_viec.id_nhan_vien,
                hrm_nhan_vien_thoi_viec.id_tttv,
                hrm_thu_tuc_thoi_viec.ten_thu_tuc,
                hrm_nhan_vien_thoi_viec.ngay_hoan_thanh,
                hrm_nhan_vien_thoi_viec.trang_thai
            ', FALSE);
        $this->db->from('hrm_nhan_vien_thoi_viec');
        $this->db->join('hrm_thu_tuc_thoi_viec', 'hrm_nhan_vien_thoi_viec.id_tttv = hrm_thu_tuc_thoi_viec.id_tttv', 'left');

        $this->db->where('hrm_nhan_vien_thoi_viec.deleted_at IS NULL', NULL, FALSE);
        $this->db->where('hrm_nhan_vien_thoi_viec.id_nhan_vien', $post['id_nhan_vien']);

        // ==== Đếm total (chưa filter) ====
        $count_query = clone $this->db;
        $recordsTotal = $count_query->count_all_results();

        if (!empty($searchValue)) {
            $this->db->group_start();
            $this->db->like('hrm_thu_tuc_thoi_viec.ten_thu_tuc', $searchValue);
            $this->db->group_end();
        }

        // ==== Áp filter ====
        $columnMapping = [
            'ten_don_vi'    => 'e_don_vi.ten_don_vi',
            'ten_cong_viec' => 'hrm_vi_tri_cong_viec.ten_cong_viec',
            'trang_thai'    => 'hrm_nhan_vien_cong_viec.trang_thai',
            'ngay_lam_chinh_thuc'  => 'hrm_nhan_vien_cong_viec.ngay_lam_chinh_thuc',
        ];
        $this->applyFilter($this->db, $columnMapping, $post);
        if (!empty($filters)) {
            if (!empty($filters['id_don_vi'])) {
                $this->db->where('hrm_nhan_vien.id_don_vi_cong_tac', $filters['id_don_vi']);
            }

            if (!empty($filters['id_vi_tri_cong_viec'])) {
                $this->db->where('hrm_nhan_vien_cong_viec.id_vi_tri_cong_viec', $filters['id_vi_tri_cong_viec']);
            }

            if (!empty($filters['ngay_lam_chinh_thuc'])) {
                $from = $filters['ngay_lam_chinh_thuc']['from'];
                $to   = $filters['ngay_lam_chinh_thuc']['to'];

                $this->db->where("DATE(nvcv.ngay_lam_chinh_thuc) >=", $from);
                $this->db->where("DATE(nvcv.ngay_lam_chinh_thuc) <=", $to);
            }
        }

        // ==== Đếm filtered ====
        $count_query2 = clone $this->db;
        $recordsFiltered = $count_query2->count_all_results();

        // ==== Lấy data ====
        if ($length != -1) {
            $this->db->limit($length, $start);
        }
        $data = $this->db->get()->result_array();
        $sql  = $this->db->last_query();

        return [
            'draw'            => intval($draw),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
            'sql'             => $sql,
            'filters'         => $filters,

        ];
    }

    private function applyFilter($q, $map, $post, $default = 'hrm_nhan_vien.')
    {
        // Search theo column
        foreach ($post['columns'] ?? [] as $c) {
            $val = $c['search']['value'];
            if (($val === null || $val === '') && !empty($c['search']['fixed'][0]['term'])) {
                $val = $c['search']['fixed'][0]['term'];
            }

            if ($val !== null && $val !== '') {
                $f = $map[$c['data']] ?? $default . $c['data'];
                $q->like($f, $val);
            }
        }

        // Order
        foreach ($post['order'] ?? [] as $o) {
            $col = $post['columns'][$o['column']]['data'];
            $f   = $map[$col] ?? $default . $col;
            $q->order_by($f, $o['dir']);
        }
    }

    public function thongke_hoso()
    {
        $trang_thai = [
            Common::TRANG_THAI_CONG_VIEC['DANG_LAM_THU_TUC_THOI_VIEC']['value'],
            Common::TRANG_THAI_CONG_VIEC['NGHI_VIEC']['value']
        ];

        $sql = "
            SELECT
                SUM(CASE cv.trang_thai WHEN '{$trang_thai[0]}' THEN 1 ELSE 0 END) AS DANG_LAM_THU_TUC_THOI_VIEC,
                SUM(CASE cv.trang_thai WHEN '{$trang_thai[1]}' THEN 1 ELSE 0 END) AS NGHI_VIEC,
                COUNT(nv.id_nhan_vien) AS TONG
            FROM hrm_nhan_vien nv
            INNER JOIN hrm_nhan_vien_cong_viec cv ON cv.id_nhan_vien = nv.id_nhan_vien AND cv.trang_thai IN ('{$trang_thai[0]}', '{$trang_thai[1]}')
            WHERE nv.deleted_at IS NULL
        ";

        $query  = $this->db->query($sql);
        return $query->row_array();
    }
}
