<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');


class Hrm_nhan_vien_model extends MY_Model
{
    protected $table = 'hrm_nhan_vien';
    protected $primaryKey = 'id_nhan_vien';
    protected $timestamps = true;
    protected $createdAtField = 'created_at';
    protected $updatedAtField = 'updated_at';

    public function __construct()
    {
        parent::__construct();
        $this->load->library(['Common']);
    }

    // public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array())
    // {
    //     $this->db->from('hrm_nhan_vien');

    //     $this->db->where('hrm_nhan_vien.deleted_at IS NULL');

    //     $totalRecordsQuery = clone $this->db;
    //     $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

    //     if ($searchValue) {
    //         $this->db->group_start();
    //         $this->db->or_like('hrm_nhan_vien.ma_nhan_vien', $searchValue);
    //         $this->db->or_like('hrm_nhan_vien.ho_ten', $searchValue);
    //         $this->db->or_like('hrm_nhan_vien.gioi_tinh', $searchValue);
    //         $this->db->or_like('hrm_nhan_vien.ngay_sinh', $searchValue);
    //         $this->db->or_like('hrm_nhan_vien.so_dien_thoai', $searchValue);
    //         $this->db->or_like('hrm_nhan_vien.email', $searchValue);
    //         $this->db->or_like('hrm_nhan_vien.id_don_vi', $searchValue);
    //         $this->db->group_end();
    //     }

    //     if (!empty($searchKey)) {
    //         foreach ($searchKey as $key => $value) {
    //             $this->db->where('hrm_nhan_vien.' . $key, $value);
    //         }
    //     }

    //     $filteredQuery = clone $this->db;
    //     $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query

    //     if (!empty($orderBy)) {
    //         $order = $orderBy['order'];
    //         $orderColumnIndex = $order[0]['column'];
    //         $orderDir = $order[0]['dir'];

    //         $columns = $orderBy['columns'];
    //         $filed = $columns[$orderColumnIndex]['data'];

    //         if (!empty($orderDir)) {
    //             $this->db->order_by('hrm_nhan_vien.' . $filed, $orderDir);
    //         }
    //     }



    //     if ($length != '-1') {
    //         $this->db->limit($length, $start);
    //     }


    //     $query = $this->db->get();
    //     $data = $query->result_array();

    //     $nhanvienIds = array_unique(array_column($data, 'id_nhan_vien'));
    //     $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
    //     $lichsuchucvu = !empty($nhanvienIds)
    //         ? $this->db->join('hrm_chuc_vu', 'hrm_chuc_vu.id_chuc_vu = hrm_lich_su_chuc_vu.id_chuc_vu')
    //         ->where_in('hrm_lich_su_chuc_vu.id_nhan_vien', $nhanvienIds)
    //         ->order_by('hrm_lich_su_chuc_vu.ngay_bat_dau', 'DESC')
    //         ->order_by('hrm_lich_su_chuc_vu.ngay_ket_thuc', 'DESC')
    //         ->group_by('hrm_lich_su_chuc_vu.id_lich_su_chuc_vu')
    //         ->get('hrm_lich_su_chuc_vu')->result_array() : [];

    //     // $chucvuIds = array_unique(array_column($lichsuchucvu, 'id_chuc_vu'));
    //     // $chucvu = !empty($chucvuIds) ? $this->db->where_in('id_chuc_vu', $chucvuIds)->get('hrm_chuc_vu')->result_array() : [];

    //     // foreach ($lichsuchucvu as &$lscv) {
    //     //     $arr = [];
    //     //     foreach ($chucvu as $cv) {
    //     //         if ($cv['id_chuc_vu'] == $lscv['id_chuc_vu']) {
    //     //             $arr[] = $cv;
    //     //         }
    //     //     }
    //     //     $lscv['chuc_vu'] = $arr;
    //     // }

    //     foreach ($data as &$dt) {
    //         $dt['lich_su_chuc_vu'] = array_filter($lichsuchucvu, function ($item) use ($dt) {
    //             return $item['id_nhan_vien'] == $dt['id_nhan_vien'];
    //         });
    //     }

    //     return [
    //         'recordsTotal' => $recordsTotal,
    //         'recordsFiltered' => $recordsFiltered,
    //         'data' => $data
    //     ];
    // }


    public function getAll($postData)
    {
        $draw = intval($postData['draw']) ?? 1;
        $start = intval($postData['start']) ?? 0;
        $length = intval($postData['length']) ?? 10;
        $order = $postData['order'][0]['column'] ?? 0;
        $orderDir = $postData['order'][0]['dir'] ?? 'asc';
        $searchValue = $postData['search']['value'] ?? '';

        // 🔹 Cột cho phép order
        $columns = [
            0 => 'nv.ma_nhan_vien',
            1 => 'nv.ho_va_ten',
            2 => 'nv.email',
            3 => 'dv.ten_don_vi',
            4 => 'cv.ten_cong_viec'
        ];

        // ====== 1. Đếm tổng record ======
        $recordsTotal = $this->db->where('deleted_at IS NULL')
            ->count_all_results('hrm_nhan_vien');

        // ====== 2. Đếm filtered record ======
        $this->db->from('hrm_nhan_vien nv')
            ->join('e_don_vi dv', 'dv.id_don_vi = nv.id_don_vi_cong_tac', 'left')
            ->join('hrm_vi_tri_cong_viec cv', 'cv.id_vi_tri_cong_viec = nv.id_vi_tri_cong_viec', 'left')
            ->where('nv.deleted_at IS NULL');

        if (!empty($searchValue)) {
            $this->db->group_start()
                ->like('nv.ho_va_ten', $searchValue)
                ->or_like('nv.email', $searchValue)
                ->or_like('dv.ten_don_vi', $searchValue)
                ->or_like('cv.ten_cong_viec', $searchValue)
                ->group_end();
        }
        $recordsFiltered = $this->db->count_all_results();

        // ====== 3. Query data chính ======
        $this->db->from('hrm_nhan_vien nv')
            ->select([
                'nv.*',
                'dv.ten_don_vi',
                'cv.ten_cong_viec',
                'nvcv.trang_thai',
                'nvcv.ngay_lam_chinh_thuc'
            ])
            ->join('hrm_nhan_vien_cong_viec nvcv', 'nvcv.id_nhan_vien = nv.id_nhan_vien', 'left')
            ->join('e_don_vi dv', 'dv.id_don_vi = nv.id_don_vi_cong_tac', 'left')
            ->join('hrm_vi_tri_cong_viec cv', 'cv.id_vi_tri_cong_viec = nv.id_vi_tri_cong_viec', 'left')
            ->where('nv.deleted_at IS NULL');

        if (!empty($searchValue)) {
            $this->db->group_start()
                ->like('nv.ho_va_ten', $searchValue)
                ->or_like('nv.email', $searchValue)
                ->or_like('dv.ten_don_vi', $searchValue)
                ->or_like('cv.ten_cong_viec', $searchValue)
                ->group_end();
        }

        // Sắp xếp
        if (isset($columns[$order])) {
            $this->db->order_by($columns[$order], $orderDir);
        } else {
            $this->db->order_by('nv.ma_nhan_vien', 'DESC');
        }

        // Phân trang
        if ($length != -1) {
            $this->db->limit($length, $start);
        }

        $data = $this->db->get()->result_array();

        // ====== Map dữ liệu con (bank, hợp đồng, công tác) ======
        if (!empty($data)) {
            $ids = array_column($data, 'id_nhan_vien');

            // Ngân hàng
            $nganhang = $this->db->where_in('id_nhan_vien', $ids)
                ->get('hrm_nhan_vien_luong')->result_array();
            $nganhangMap = [];
            foreach ($nganhang as $nh) {
                $nganhangMap[$nh['id_nhan_vien']][] = $nh;
            }

            // Hợp đồng
            $hopdong = $this->db->where_in('id_nhan_vien', $ids)
                ->get('hrm_hop_dong')->result_array();
            $hopdongMap = [];
            foreach ($hopdong as $hd) {
                $hopdongMap[$hd['id_nhan_vien']][] = $hd;
            }

            // Quá trình công tác
            $qtct = $this->db->where_in('id_nhan_vien', $ids)
                ->get('hrm_qua_trinh_cong_tac')->result_array();
            $qtctMap = [];
            foreach ($qtct as $qt) {
                $qtctMap[$qt['id_nhan_vien']][] = $qt;
            }

            // Gắn vào data
            foreach ($data as &$row) {
                $id = $row['id_nhan_vien'];
                $row['ngan_hang'] = $nganhangMap[$id] ?? [];
                $row['hop_dong'] = $hopdongMap[$id] ?? [];
                $row['qua_trinh_cong_tac'] = $qtctMap[$id] ?? [];
            }
            unset($row);
        }

        return [
            "draw" => intval($draw),
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $data
        ];
    }

    public function getAllNhanvien($post)
    {
        $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");

        $draw = $post['draw'] ?? 1;
        $start = $post['start'] ?? 0;
        $length = $post['length'] ?? 10;

        $searchValue = $post['search']['value'] ?? '';
        $filters = $post['filter'] ?? [];

        // ==== Base select ====
        $this->db->select('
                nv.id_nhan_vien,
                nv.ql_nguoi_dung_id,
                nv.ma_nhan_vien,
                nv.ma_cham_cong,
                nv.ho_va_ten,
                nv.avatar,
                nv.email,
                nv.gioi_tinh,
                nv.ngay_sinh,
                nv.id_don_vi_cong_tac,
                dv.ten_don_vi,
                nv.id_vi_tri_cong_viec,
                cv.ten_cong_viec,
                nvcv.trang_thai,
                nv.mst_ca_nhan,
                nv.nganh_dt,
                nv.noi_dt,
                nv.trinh_do_dt,
                nv.hoc_ham,
                nvcv.ngay_lam_chinh_thuc
            ', FALSE);
        $this->db->from('hrm_nhan_vien nv');
        $this->db->join('hrm_nhan_vien_cong_viec nvcv', 'nvcv.id_nhan_vien = nv.id_nhan_vien', 'left');
        $this->db->join('e_don_vi dv', 'dv.id_don_vi = nv.id_don_vi_cong_tac', 'left');
        $this->db->join('hrm_vi_tri_cong_viec cv', 'cv.id_vi_tri_cong_viec = nv.id_vi_tri_cong_viec', 'left');

        $this->db->where('nv.deleted_at IS NULL', NULL, FALSE);

        // Không lọc trang_thai mặc định → applyFilter() sẽ xử lý khi có giá trị cụ thể

        $this->db->group_by('nv.id_nhan_vien');

        // ==== Đếm total (chưa filter) ====
        $count_query = clone $this->db;
        $recordsTotal = $count_query->count_all_results();

        if (!empty($searchValue)) {
            $this->db->group_start();
            $this->db->like('nv.ho_va_ten', $searchValue);
            $this->db->or_like('nv.email', $searchValue);
            $this->db->or_like('dv.ten_don_vi', $searchValue);
            $this->db->or_like('cv.ten_cong_viec', $searchValue);
            $this->db->or_like('nv.ma_nhan_vien', $searchValue);
            $this->db->or_like('nv.hoc_ham', $searchValue);
            $this->db->or_like('nv.trinh_do_dt', $searchValue);
            $this->db->or_like('nv.noi_dt', $searchValue);
            $this->db->group_end();
        }

        // ==== Áp filter ====
        $columnMapping = [
            'ten_don_vi' => 'dv.ten_don_vi',
            'ten_cong_viec' => 'cv.ten_cong_viec',
            'trang_thai' => 'nvcv.trang_thai',
            'ngay_lam_chinh_thuc' => 'nvcv.ngay_lam_chinh_thuc',
        ];

        $this->applyFilter($this->db, $columnMapping, $post);
        if (!empty($filters)) {
            if (!empty($filters['id_don_vi'])) {
                $this->db->where('nv.id_don_vi_cong_tac', $filters['id_don_vi']);
            }

            if (!empty($filters['id_vi_tri_cong_viec'])) {
                $this->db->where('nv.id_vi_tri_cong_viec', $filters['id_vi_tri_cong_viec']);
            }

            //trang_thai
            if (!empty($filters['trang_thai'])) {
                $this->db->where('nvcv.trang_thai', $filters['trang_thai']);
            }


            if (!empty($filters['ngay_lam_chinh_thuc'])) {
                $from = $filters['ngay_lam_chinh_thuc']['from'];
                $to = $filters['ngay_lam_chinh_thuc']['to'];

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
        $sql = $this->db->last_query();

        foreach ($data as &$item) {
            $id = $item['id_nhan_vien'];
            $item['encryptId'] = encryptString($id);
            $item['avatar'] = encryptString($item['avatar']);
        }

        return [
            'draw' => intval($draw),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'sql' => $sql,
            'filters' => $filters,

        ];
    }


    private function applyFilter($q, $map, $post, $default = 'nv.')
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
        // if (!isset($post['order'])) {
        //     $q->order_by('nv.id_nhan_vien', 'desc');
        // } else {
        foreach ($post['order'] ?? [] as $o) {
            $col = $post['columns'][$o['column']]['data'];
            $f = $map[$col] ?? $default . $col;
            $q->order_by($f, $o['dir']);
        }
        // }
    }



    public function __getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $columns = [], $searchKey = array(), $fromDate = null, $toDate = null)
    {
        $this->db
            ->select('*')
            ->from('hrm_nhan_vien')
            ->join('hrm_nhan_vien_cong_viec', 'hrm_nhan_vien_cong_viec.id_nhan_vien = hrm_nhan_vien.id_nhan_vien', 'left')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_nhan_vien.id_don_vi_cong_tac', 'left')
            ->join('hrm_vi_tri_cong_viec', 'hrm_vi_tri_cong_viec.id_vi_tri_cong_viec = hrm_nhan_vien.id_vi_tri_cong_viec', 'left');

        $this->db->where('hrm_nhan_vien.deleted_at IS NULL');

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('hrm_nhan_vien.ma_nhan_vien', $searchValue);
            $this->db->or_like('hrm_nhan_vien.ho_va_ten', $searchValue);
            $this->db->or_like('hrm_nhan_vien.email', $searchValue);
            // $this->db->or_like('hrm_nhan_vien.gioi_tinh', $searchValue);
            // $this->db->or_like('hrm_nhan_vien.ngay_sinh', $searchValue);
            $this->db->or_like('hrm_vi_tri_cong_viec.ten_cong_viec', $searchValue);
            $this->db->or_like('e_don_vi.ten_don_vi', $searchValue);
            $this->db->or_like('hrm_nhan_vien.trinh_do_dt', $searchValue);
            $this->db->or_like('hrm_nhan_vien.noi_dt', $searchValue);
            $this->db->or_like('hrm_nhan_vien.nganh_dt', $searchValue);
            $this->db->or_like('hrm_nhan_vien.hoc_ham', $searchValue);
            // $this->db->or_like('hrm_nhan_vien_cong_viec.ngay_tap_su', $searchValue);

            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            $arr = ['ma_nhan_vien', 'ho_va_ten', 'email'];
            $arrCongViec = ['trang_thai'];
            foreach ($searchKey as $key => $value) {
                if (in_array($key, $arr)) {
                    $this->db->like('hrm_nhan_vien.' . $key, $value);
                } else {
                    if (is_array($value)) {
                        if (in_array($key, $arrCongViec)) {
                            $this->db->where_in('hrm_nhan_vien_cong_viec.' . $key, $value);
                        } else {
                            $this->db->where_in('hrm_nhan_vien.' . $key, $value);
                        }
                    } else {
                        $this->db->where('hrm_nhan_vien.' . $key, $value);
                    }
                }
            }
        }


        if ($fromDate && $toDate) {
            $fromDate = $fromDate . ' 00:00:00';
            $toDate = $toDate . ' 23:59:59';
            $this->db->where("hrm_nhan_vien.created_at BETWEEN '{$fromDate}' AND '{$toDate}'");
        }

        if (!empty($columns)) {
            // Map tên cột hiển thị (alias) sang cột thật trong DB
            $columnMapping = [
                'ma_nhan_vien' => 'hrm_nhan_vien.ma_nhan_vien',
                'ma_cham_cong' => 'hrm_nhan_vien.ma_cham_cong',
                'ho_va_ten' => 'hrm_nhan_vien.ho_va_ten',
                'avatar' => 'hrm_nhan_vien.avatar',
                'email' => 'hrm_nhan_vien.email',
                'gioi_tinh' => 'hrm_nhan_vien.gioi_tinh',
                'ngay_sinh' => 'hrm_nhan_vien.ngay_sinh',
                'ten_cong_viec' => 'hrm_vi_tri_cong_viec.ten_cong_viec',
                'ten_don_vi' => 'e_don_vi.ten_don_vi',
                'trang_thai' => 'hrm_nhan_vien_cong_viec.trang_thai',
                'trinh_do_dt' => 'hrm_nhan_vien.trinh_do_dt',
                'noi_dt' => 'hrm_nhan_vien.noi_dt',
                'nganh_dt' => 'hrm_nhan_vien.nganh_dt',
                'ngay_tap_su' => 'hrm_nhan_vien_cong_viec.ngay_tap_su',
                'ngay_tap_su_ket_thuc' => 'hrm_nhan_vien_cong_viec.ngay_tap_su_ket_thuc',
                'ngay_thu_viec' => 'hrm_nhan_vien_cong_viec.ngay_thu_viec',
                'ngay_thu_viec_ket_thuc' => 'hrm_nhan_vien_cong_viec.ngay_thu_viec_ket_thuc',
                'ngay_lam_chinh_thuc' => 'hrm_nhan_vien_cong_viec.ngay_lam_chinh_thuc',
                'ngay_lam_chinh_thuc_ket_thuc' => 'hrm_nhan_vien_cong_viec.ngay_lam_chinh_thuc_ket_thuc',
                // Nếu không có trong mapping thì mặc định dùng: hrm_nhan_vien.[column]
            ];

            $this->handleDatatableColumns($columns, $columnMapping);
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query

        // Xử lý order sau khi xử lý search
        if (!empty($orderBy)) {
            $this->handleDatatableOrdering($orderBy, $columns);
        }

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        $this->db->select(
            '   hrm_nhan_vien.id_nhan_vien,
                hrm_nhan_vien.ma_nhan_vien, 
                hrm_nhan_vien.ma_cham_cong,
                hrm_nhan_vien.ho_va_ten, 
                hrm_nhan_vien.avatar, 
                hrm_nhan_vien.email, 
                hrm_nhan_vien.gioi_tinh, 
                hrm_nhan_vien.ngay_sinh, 
                hrm_vi_tri_cong_viec.ten_cong_viec, 
                e_don_vi.ten_don_vi, 
                hrm_nhan_vien_cong_viec.trang_thai, 
                hrm_nhan_vien.trinh_do_dt, 
                hrm_nhan_vien.noi_dt, 
                hrm_nhan_vien.nganh_dt, 
                hrm_nhan_vien_cong_viec.ngay_tap_su,
                hrm_nhan_vien_cong_viec.ngay_tap_su_ket_thuc,
                hrm_nhan_vien_cong_viec.ngay_thu_viec,
                hrm_nhan_vien_cong_viec.ngay_thu_viec_ket_thuc,
                hrm_nhan_vien_cong_viec.ngay_lam_chinh_thuc,
                hrm_nhan_vien_cong_viec.ngay_lam_chinh_thuc_ket_thuc '
        );

        $this->db->order_by('hrm_nhan_vien.id_nhan_vien', 'DESC');
        $query = $this->db->get();
        $data = $query->result_array();
        $sql = $this->db->last_query();

        $nhanvienIds = array_unique(array_column($data, 'id_nhan_vien'));

        if (!empty($nhanvienIds)) {
            //Thông tin công việc
            $congviec = $this->db->where_in('id_nhan_vien', $nhanvienIds)->get('hrm_nhan_vien_cong_viec')->result_array();
            $congviecMap = [];
            foreach ($congviec as $cv) {
                $congviecMap[$cv['id_nhan_vien']] = $cv;
            }

            //Thông tin bảo hiểm
            $baohiem = $this->db->where_in('id_nhan_vien', $nhanvienIds)->get('hrm_nhan_vien_bao_hiem')->result_array();
            $baohiemMap = [];
            foreach ($baohiem as $bh) {
                $baohiemMap[$bh['id_nhan_vien']] = $bh;
            }

            //Thông tin chính trị, y tế, quân sự
            $chinhtriytequansu = $this->db->where_in('id_nhan_vien', $nhanvienIds)->get('hrm_nhan_vien_chinhtri_yte_quansu')->result_array();
            $chinhtriytequansuMap = [];
            foreach ($chinhtriytequansu as $ct) {
                $chinhtriytequansuMap[$ct['id_nhan_vien']] = $ct;
            }

            //Hợp đồng
            $hopdong = $this->db->where_in('id_nhan_vien', $nhanvienIds)->get('hrm_hop_dong')->result_array();

            //Quá trình công tác
            $quatrinhcongtac = $this->db->where_in('id_nhan_vien', $nhanvienIds)->get('hrm_qua_trinh_cong_tac')->result_array();

            foreach ($data as &$dt) {
                $dt['cong_viec'] = $congviecMap[$dt['id_nhan_vien']] ?? null;
                $dt['bao_hiem'] = $baohiemMap[$dt['id_nhan_vien']] ?? null;
                $dt['chinhtri_yte_quansu'] = $chinhtriytequansuMap[$dt['id_nhan_vien']] ?? null;

                $dt['hop_dong'] = array_filter($hopdong, function ($item) use ($dt) {
                    return $item['id_nhan_vien'] == $dt['id_nhan_vien'];
                });

                $dt['qua_trinh_cong_tac'] = array_filter($quatrinhcongtac, function ($item) use ($dt) {
                    return $item['id_nhan_vien'] == $dt['id_nhan_vien'];
                });

                $dt['ngan_hang'] = $this->db->where('id_nhan_vien', $dt['id_nhan_vien'])->get('hrm_nhan_vien_luong')->result_array();
            }
            unset($dt);
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'sql' => $sql,
        ];
    }

    public function getTrash($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array())
    {
        $this->db->from('hrm_nhan_vien')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_nhan_vien.id_don_vi', 'left')
            ->join('hrm_chuc_vu', 'hrm_chuc_vu.id_chuc_vu = hrm_nhan_vien.id_chuc_vu', 'left');
        $this->db->where('hrm_nhan_vien.deleted_at IS NOT NULL');

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('hrm_nhan_vien.ho_ten', $searchValue);
            $this->db->or_like('hrm_nhan_vien.gioi_tinh', $searchValue);
            $this->db->or_like('hrm_nhan_vien.ngay_sinh', $searchValue);
            $this->db->or_like('hrm_nhan_vien.so_dien_thoai', $searchValue);
            $this->db->or_like('hrm_nhan_vien.email', $searchValue);
            $this->db->or_like('hrm_nhan_vien.id_don_vi', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                $this->db->where('hrm_nhan_vien.' . $key, $value);
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
                $this->db->order_by('hrm_nhan_vien.' . $filed, $orderDir);
            }
        }



        if ($length != '-1') {
            $this->db->limit($length, $start);
        }


        $query = $this->db->get();
        $data = $query->result_array();

        if (!empty($data)) {
            $this->load->helper('hrm');
            // append_hhhv_to_list($data, 'ho_va_ten');
        }

        $nhanvienIds = array_unique(array_column($data, 'id_nhan_vien'));
        $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
        $lichsuchucvu = !empty($nhanvienIds)
            ? $this->db->join('hrm_chuc_vu', 'hrm_chuc_vu.id_chuc_vu = hrm_lich_su_chuc_vu.id_chuc_vu')
                ->where_in('hrm_lich_su_chuc_vu.id_nhan_vien', $nhanvienIds)
                ->order_by('hrm_lich_su_chuc_vu.ngay_bat_dau', 'DESC')
                ->order_by('hrm_lich_su_chuc_vu.ngay_ket_thuc', 'DESC')
                ->group_by('hrm_lich_su_chuc_vu.id_lich_su_chuc_vu')
                ->get('hrm_lich_su_chuc_vu')->result_array() : [];
        // $lichsuchucvu = !empty($nhanvienIds) ? $this->db->where_in('id_nhan_vien', $nhanvienIds)->get('hrm_lich_su_chuc_vu')->result_array() : [];

        // $chucvuIds = array_unique(array_column($lichsuchucvu, 'id_chuc_vu'));
        // $chucvu = !empty($chucvuIds) ? $this->db->where_in('id_chuc_vu', $chucvuIds)->get('hrm_chuc_vu')->result_array() : [];

        // foreach ($lichsuchucvu as &$lscv) {
        //     $arr = [];
        //     foreach ($chucvu as $cv) {
        //         if ($cv['id_chuc_vu'] == $lscv['id_chuc_vu']) {
        //             $arr[] = $cv;
        //         }
        //     }
        //     $lscv['chuc_vu'] = $arr;
        // }

        foreach ($data as &$dt) {
            $dt['lich_su_chuc_vu'] = array_filter($lichsuchucvu, function ($item) use ($dt) {
                return $item['id_nhan_vien'] == $dt['id_nhan_vien'];
            });
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }

    public function Export($contains = null)
    {
        $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
        $this->db
            ->select('
                    IFNULL(hrm_nhan_vien.ma_nhan_vien, "") AS ma_nhan_vien,
                    IFNULL(hrm_nhan_vien.ma_cham_cong, "") AS ma_cham_cong,
                    IFNULL(hrm_nhan_vien.ho_va_ten, "") AS ho_va_ten,
                    IFNULL(hrm_nhan_vien.email, "") AS email,
                    CASE 
                        WHEN hrm_nhan_vien.gioi_tinh = "1" THEN "Nam"
                        WHEN hrm_nhan_vien.gioi_tinh = "2" THEN "Nữ"
                    END AS gioi_tinh,
                    IFNULL(DATE_FORMAT(hrm_nhan_vien.ngay_sinh, "%d/%m/%Y"), "") AS ngay_sinh,
                    IFNULL(e_don_vi.ten_don_vi, "") AS ten_don_vi,
                    IFNULL(hrm_nhan_vien_cong_viec.trang_thai, "") AS trang_thai,
                    IFNULL(DATE_FORMAT(hrm_nhan_vien_cong_viec.ngay_lam_chinh_thuc, "%d/%m/%Y"), "") AS ngay_lam_chinh_thuc,
                    IFNULL(DATE_FORMAT(hrm_nhan_vien_cong_viec.ngay_lam_chinh_thuc_ket_thuc, "%d/%m/%Y"), "") AS ngay_lam_chinh_thuc_ket_thuc,
                    IFNULL(hrm_vi_tri_cong_viec.ten_cong_viec, "") AS ten_cong_viec,
                    IFNULL(hrm_ca_lam_viec.ca_lam_viec, "") AS ca_lam_viec,

                    IFNULL(hrm_nhan_vien.cccd_so, "") AS cccd_so,
                    IFNULL(hrm_nhan_vien.cccd_noi_cap, "") AS cccd_noi_cap,
                    IFNULL(DATE_FORMAT(hrm_nhan_vien.cccd_ngay_cap, "%d/%m/%Y"), "") AS cccd_ngay_cap,
                    IFNULL(DATE_FORMAT(hrm_nhan_vien.cccd_ngay_het_han, "%d/%m/%Y"), "") AS cccd_ngay_het_han,
                    IFNULL(dm_quoc_gia.ten, "") AS ten_quoc_gia,
                    IFNULL(dm_ton_giao.ten, "") AS ten_ton_giao,
                    IFNULL(dm_dan_toc.ten, "") AS ten_dan_toc
            ', false)
            ->from('hrm_nhan_vien');
        $this->db->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_nhan_vien.id_don_vi_cong_tac', 'left');
        $this->db->join('hrm_vi_tri_cong_viec', 'hrm_vi_tri_cong_viec.id_vi_tri_cong_viec = hrm_nhan_vien.id_vi_tri_cong_viec', 'left');
        $this->db->join('dm_quoc_gia', 'dm_quoc_gia.id_quoc_gia = hrm_nhan_vien.id_quoc_tich', 'left');
        $this->db->join('dm_dan_toc', 'dm_dan_toc.id_dan_toc = hrm_nhan_vien.id_dan_toc', 'left');
        $this->db->join('dm_ton_giao', 'dm_ton_giao.id_ton_giao = hrm_nhan_vien.id_ton_giao', 'left');
        $this->db->join('hrm_ca_lam_viec', 'hrm_ca_lam_viec.id = hrm_nhan_vien.id_ca_lam_viec', 'left');
        $this->db->join('hrm_nhan_vien_cong_viec', 'hrm_nhan_vien_cong_viec.id_nhan_vien = hrm_nhan_vien.id_nhan_vien', 'left');

        // $this->db->where_not_in('hrm_nhan_vien_cong_viec.trang_thai', [Common::TRANG_THAI_CONG_VIEC['NGHI_VIEC']['value']]);
        $this->db->where('hrm_nhan_vien.deleted_at IS NULL');
        $this->db->order_by('hrm_nhan_vien.id_nhan_vien', 'ASC');
        $this->db->group_by('hrm_nhan_vien.id_nhan_vien');

        if (isset($contains['trang_thai']) && $contains['trang_thai'] != '') {
            $this->db->where_in('hrm_nhan_vien_cong_viec.trang_thai', $contains['trang_thai']);
        }

        if (isset($contains['don_vi']) && $contains['don_vi'] != '') {
            $this->db->where_in('hrm_nhan_vien.id_don_vi_cong_tac', array_values($contains['don_vi']));
        }

        if (isset($contains['chuc_vu']) && $contains['chuc_vu'] != '') {
            $this->db->where_in('hrm_vi_tri_cong_viec.id_vi_tri_cong_viec', array_values($contains['chuc_vu']));
        }

        if (isset($contains['has_email']) && $contains['has_email'] != '') {
            if ($contains['has_email'] == 'yes') {
                $this->db->where('hrm_nhan_vien.email IS NOT NULL AND hrm_nhan_vien.email != ""');
            } else if ($contains['has_email'] == 'no') {
                $this->db->where('(hrm_nhan_vien.email IS NULL OR hrm_nhan_vien.email = "")');
            }
        }

        if (isset($contains['ngay_vao_lam_from']) && $contains['ngay_vao_lam_from'] != '') {
            $this->db->where('hrm_nhan_vien_cong_viec.ngay_lam_chinh_thuc >=', $contains['ngay_vao_lam_from']);
        }
        if (isset($contains['ngay_vao_lam_to']) && $contains['ngay_vao_lam_to'] != '') {
            $this->db->where('hrm_nhan_vien_cong_viec.ngay_lam_chinh_thuc <=', $contains['ngay_vao_lam_to']);
        }

        if (isset($contains['length']) && $contains['length'] != '' && $contains['length'] != -1) {
            $this->db->limit($contains['length']);
        }

        $datas = $this->db->get()->result_array();

        foreach ($datas as &$data) {
            $data['trang_thai'] = isset(Common::TRANG_THAI_CONG_VIEC[$data['trang_thai']]) ? Common::TRANG_THAI_CONG_VIEC[$data['trang_thai']]['label'] : $data['trang_thai'];
        }


        return $datas;


        return $this->db->last_query();


        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('hrm_nhan_vien.ma_nhan_vien', $searchValue);
            $this->db->or_like('hrm_nhan_vien.ho_va_ten', $searchValue);
            $this->db->or_like('hrm_nhan_vien.email', $searchValue);
            // $this->db->or_like('hrm_nhan_vien.gioi_tinh', $searchValue);
            // $this->db->or_like('hrm_nhan_vien.ngay_sinh', $searchValue);
            $this->db->or_like('hrm_vi_tri_cong_viec.ten_cong_viec', $searchValue);
            $this->db->or_like('e_don_vi.ten_don_vi', $searchValue);
            $this->db->or_like('hrm_nhan_vien.trinh_do_dt', $searchValue);
            $this->db->or_like('hrm_nhan_vien.noi_dt', $searchValue);
            $this->db->or_like('hrm_nhan_vien.nganh_dt', $searchValue);
            $this->db->or_like('hrm_nhan_vien.hoc_ham', $searchValue);
            // $this->db->or_like('hrm_nhan_vien_cong_viec.ngay_tap_su', $searchValue);

            $this->db->group_end();
        }

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


        if ($fromDate && $toDate) {
            $fromDate = $fromDate . ' 00:00:00';
            $toDate = $toDate . ' 23:59:59';
            $this->db->where("hrm_nhan_vien.created_at BETWEEN '{$fromDate}' AND '{$toDate}'");
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query

        $query = $this->db->get();
        $data = $query->result_array();
        $sql = $this->db->last_query();

        $nhanvienIds = array_unique(array_column($data, 'id_nhan_vien'));

        if (!empty($nhanvienIds)) {
            foreach ($data as $key => &$dt) {

                // Công việc
                $congviec = $this->db->select('*')->where('id_nhan_vien', $dt['id_nhan_vien'])->get('hrm_nhan_vien_cong_viec')->result_array();
                if (!empty($congviec)) {
                    $trang_thai = $congviec[0]['trang_thai'];
                    $loai_hop_dong = $congviec[0]['loai_hop_dong'];
                    $congviec[0]['trang_thai'] = $trang_thai ? Common::TRANG_THAI_CONG_VIEC[$trang_thai]['label'] : NULL;
                    $congviec[0]['loai_hop_dong'] = $loai_hop_dong ? Common::LOAI_HOP_DONG[$loai_hop_dong]['label'] : NULL;

                    $dt['cong_viec'] = $congviec[0];
                } else {
                    $dt['cong_viec'] = [];
                }

                // Bảo hiểm
                $baohiem = $this->db->select('*')->where('id_nhan_vien', $dt['id_nhan_vien'])->get('hrm_nhan_vien_bao_hiem')->result_array();
                (!empty($baohiem)) ? $dt['bao_hiem'] = $baohiem[0] : $dt['bao_hiem'] = [];

                // Thông tin gia đình
                $thongtingiadinh = $this->db->select('*')->where('id_nhan_vien', $dt['id_nhan_vien'])->get('hrm_nhan_vien_thong_tin_gia_dinh')->result_array();
                (!empty($thongtingiadinh)) ? $dt['thong_tin_gia_dinh'] = $thongtingiadinh : $dt['thong_tin_gia_dinh'] = [];

                // Hợp đồng lao động
                $hop_dong = $this->db->select('*')
                    ->where('id_nhan_vien', $dt['id_nhan_vien'])
                    // ->order_by("CASE 
                    //                 WHEN ngay_ket_thuc IS NULL OR ngay_ket_thuc = '0000-00-00' THEN 1 
                    //                 ELSE 0 
                    //             END", 'ASC')
                    ->order_by('ngay_ket_thuc', 'DESC')
                    ->get('hrm_hop_dong')->result_array();
                if (!empty($hop_dong)) {
                    foreach ($hop_dong as &$hd) {
                        if (isset($hd['dang_hieu_luc'])) {
                            $hd['dang_hieu_luc'] = $hd['dang_hieu_luc'] == '1' ? 'Đang có hiệu lực' : 'Hết hiệu lực';
                        }
                        $hd['loai_hop_dong'] = $hd['loai_hop_dong'] ? Common::LOAI_HOP_DONG[$hd['loai_hop_dong']]['label'] : NULL;
                    }
                    $dt['hop_dong'] = $hop_dong;
                } else {
                    $dt['hop_dong'] = [];
                }

                // Quá trình công tác
                $hrm_qua_trinh_cong_tac = $this->db->select('*')
                    ->where('id_nhan_vien', $dt['id_nhan_vien'])
                    ->join('hrm_vi_tri_cong_viec', 'hrm_vi_tri_cong_viec.id_vi_tri_cong_viec = hrm_qua_trinh_cong_tac.id_vi_tri_cong_viec', 'left')
                    ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_qua_trinh_cong_tac.id_don_vi', 'left')
                    // ->order_by("CASE 
                    //                 WHEN ngay_ket_thuc IS NULL OR ngay_ket_thuc = '0000-00-00' THEN 1 
                    //                 ELSE 0 
                    //             END", 'ASC')
                    ->order_by('ngay_ket_thuc', 'DESC')
                    ->get('hrm_qua_trinh_cong_tac')->result_array();

                if (!empty($hrm_qua_trinh_cong_tac)) {
                    $dt['qua_trinh_cong_tac'] = $hrm_qua_trinh_cong_tac;
                } else {
                    $dt['qua_trinh_cong_tac'] = [];
                }

                // Khen thưởng
                $khen_thuong = $this->db->select('*')->where('id_nhan_vien', $dt['id_nhan_vien'])->order_by('ngay_khen_thuong', 'DESC')->get('hrm_nhan_vien_khen_thuong')->result_array();
                (!empty($khen_thuong)) ? $dt['khen_thuong'] = $khen_thuong : $dt['khen_thuong'] = [];

                // Quá trình đào tạo
                $nhan_vien_dao_tao = $this->db->select('*')
                    ->join('hrm_dao_tao', 'hrm_dao_tao.id_dao_tao = hrm_nhan_vien_dao_tao.id_dao_tao', 'left')
                    ->where('id_nhan_vien', $dt['id_nhan_vien'])->get('hrm_nhan_vien_dao_tao')->result_array();
                (!empty($nhan_vien_dao_tao)) ? $dt['nhan_vien_dao_tao'] = $nhan_vien_dao_tao : $dt['nhan_vien_dao_tao'] = [];

                // Đánh giá
                $danh_gia_nhan_su = $this->db->select('*')->where('id_nhan_vien', $dt['id_nhan_vien'])->order_by('thang', 'DESC')->get('hrm_danh_gia_nhan_su')->result_array();
                (!empty($danh_gia_nhan_su)) ? $dt['danh_gia_nhan_su'] = $danh_gia_nhan_su : $dt['danh_gia_nhan_su'] = [];

                // Bằng cấp
                $bang_cap = $this->db->select('*')->where('id_nhan_vien', $dt['id_nhan_vien'])->order_by('den_thang', 'DESC')->get('hrm_nhan_vien_bang_cap')->result_array();
                (!empty($bang_cap)) ? $dt['bang_cap'] = $bang_cap : $dt['bang_cap'] = [];

                // Chứng chỉ
                $chung_chi = $this->db->select('*')->where('id_nhan_vien', $dt['id_nhan_vien'])->order_by('ngay_cap_chung_chi', 'DESC')->get('hrm_nhan_vien_chung_chi')->result_array();
                (!empty($chung_chi)) ? $dt['chung_chi'] = $chung_chi : $dt['chung_chi'] = [];

                // Kinh nghiệm làm việc
                $kinh_nghiem_lam_viec = $this->db->select('*')
                    ->where('id_nhan_vien', $dt['id_nhan_vien'])
                    // ->order_by("CASE 
                    //                 WHEN ngay_ket_thuc IS NULL OR ngay_ket_thuc = '0000-00-00' THEN 1 
                    //                 ELSE 0 
                    //             END", 'ASC')
                    ->order_by('ngay_ket_thuc', 'DESC')
                    ->get('hrm_nhan_vien_kinh_nghiem_lam_viec')->result_array();
                (!empty($kinh_nghiem_lam_viec)) ? $dt['kinh_nghiem_lam_viec'] = $kinh_nghiem_lam_viec : $dt['kinh_nghiem_lam_viec'] = [];

                // Thôi việc
                $thoi_viec = $this->db->select('*')
                    ->where('id_nhan_vien', $dt['id_nhan_vien'])
                    ->order_by('created_at', 'DESC')
                    ->get('hrm_nhan_vien_thoi_viec')->result_array();
                (!empty($thoi_viec)) ? $dt['thoi_viec'] = $thoi_viec : $dt['thoi_viec'] = [];
            }
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'sql' => $sql,
        ];
    }

    public function last_code()
    {
        // Lấy 4 số cuối lớn nhất
        $this->db->select("
            CONCAT( RIGHT(YEAR(CURDATE()), 2), LPAD( IFNULL(MAX(CAST(RIGHT(CAST(ma_nhan_vien AS CHAR), 4) AS UNSIGNED)), 0) + 1, 4, '0' ) ) AS last_ma_nhan_vien
        ");
        $this->db->from('hrm_nhan_vien');
        $this->db->where('deleted_at IS NULL');
        // $this->db->order_by('last_ma_nhan_vien', 'DESC');
        $this->db->limit(1);
        $query = $this->db->get()->row_array();
        return $query['last_ma_nhan_vien'];
    }
    public function statistical()
    {
        $sql_trinh_do = "
                SELECT 
                    hrm_nhan_vien.trinh_do_dt,
                    COUNT(*) AS so_luong
                FROM hrm_nhan_vien
                LEFT JOIN hrm_nhan_vien_cong_viec ON hrm_nhan_vien_cong_viec.id_nhan_vien = hrm_nhan_vien.id_nhan_vien
                WHERE hrm_nhan_vien.deleted_at IS NULL AND (hrm_nhan_vien_cong_viec.trang_thai IS NULL OR hrm_nhan_vien_cong_viec.trang_thai NOT IN ('DANG_LAM_THU_TUC_THOI_VIEC', 'NGHI_VIEC'))
                GROUP BY hrm_nhan_vien.trinh_do_dt
                ORDER BY
                    CASE 
                        WHEN hrm_nhan_vien.trinh_do_dt = 'Tiến sĩ' THEN 1
                        WHEN hrm_nhan_vien.trinh_do_dt = 'Thạc sĩ' THEN 2
                        WHEN hrm_nhan_vien.trinh_do_dt = 'Bác sĩ' THEN 3
                        WHEN hrm_nhan_vien.trinh_do_dt = 'Kỹ sư' THEN 4
                        WHEN hrm_nhan_vien.trinh_do_dt = 'Cử nhân' THEN 5
                        WHEN hrm_nhan_vien.trinh_do_dt = 'Cao đẳng' THEN 6
                        WHEN hrm_nhan_vien.trinh_do_dt = 'Trung cấp' THEN 7
                        WHEN hrm_nhan_vien.trinh_do_dt = 'Đại học' THEN 8
                        WHEN hrm_nhan_vien.trinh_do_dt = 'THPT' THEN 9
                        WHEN hrm_nhan_vien.trinh_do_dt = 'Khác' THEN 10
                        WHEN hrm_nhan_vien.trinh_do_dt IS NULL THEN 99
                        ELSE 100
                    END
                ";

        $query_trinh_do = $this->db->query($sql_trinh_do);
        $data['hoc_vi'] = $query_trinh_do->result_array();
        $query_trinh_do->free_result();

        $hocham = "
                SELECT
                    SUM( CASE WHEN hoc_ham = 'Giáo sư' THEN 1 ELSE 0 END ) AS tong_giao_su,
                    SUM( CASE WHEN hoc_ham = 'Phó Giáo sư' THEN 1 ELSE 0 END ) AS tong_pho_giao_su 
                FROM
                    hrm_nhan_vien
                    LEFT JOIN hrm_nhan_vien_cong_viec ON hrm_nhan_vien_cong_viec.id_nhan_vien = hrm_nhan_vien.id_nhan_vien 
                WHERE
                    hrm_nhan_vien.deleted_at IS NULL 
                    AND (hrm_nhan_vien_cong_viec.trang_thai IS NULL OR hrm_nhan_vien_cong_viec.trang_thai NOT IN ('DANG_LAM_THU_TUC_THOI_VIEC', 'NGHI_VIEC' ));
                ";
        $query_hoc_ham = $this->db->query($hocham);
        $data['hoc_ham'] = $query_hoc_ham->row_array();
        $query_hoc_ham->free_result();

        $sql_tong_ho_so = "
                SELECT COUNT(hrm_nhan_vien.id_nhan_vien) AS tong_ho_so
                FROM hrm_nhan_vien
                LEFT JOIN hrm_nhan_vien_cong_viec ON hrm_nhan_vien_cong_viec.id_nhan_vien = hrm_nhan_vien.id_nhan_vien
                WHERE hrm_nhan_vien.deleted_at IS NULL AND (hrm_nhan_vien_cong_viec.trang_thai IS NULL OR hrm_nhan_vien_cong_viec.trang_thai NOT IN ('DANG_LAM_THU_TUC_THOI_VIEC', 'NGHI_VIEC' ));
        ";
        $query_tong_ho_so = $this->db->query($sql_tong_ho_so);
        $data['tong_ho_so'] = $query_tong_ho_so->row_array()['tong_ho_so'];
        $query_tong_ho_so->free_result();

        $sql_chuc_vu = "
            SELECT
                SUM(CASE WHEN hrm_vi_tri_cong_viec.ten_cong_viec = 'Nhân viên' THEN 1 ELSE 0 END) AS tong_nhan_vien,
                SUM(CASE WHEN hrm_vi_tri_cong_viec.ten_cong_viec = 'Giảng viên' THEN 1 ELSE 0 END) AS tong_giang_vien
            FROM hrm_nhan_vien
            LEFT JOIN hrm_vi_tri_cong_viec ON hrm_vi_tri_cong_viec.id_vi_tri_cong_viec = hrm_nhan_vien.id_vi_tri_cong_viec
            LEFT JOIN hrm_nhan_vien_cong_viec ON hrm_nhan_vien_cong_viec.id_nhan_vien = hrm_nhan_vien.id_nhan_vien
            WHERE hrm_nhan_vien.deleted_at IS NULL AND (hrm_nhan_vien_cong_viec.trang_thai IS NULL OR hrm_nhan_vien_cong_viec.trang_thai NOT IN ('DANG_LAM_THU_TUC_THOI_VIEC', 'NGHI_VIEC'));
    
        ";
        $query_chuc_vu = $this->db->query($sql_chuc_vu);
        $data['chuc_vu'] = $query_chuc_vu->row_array();
        $query_chuc_vu->free_result();

        return $data;
    }

    private function handleDatatableColumns(array $columns, array $columnMapping): void
    {
        foreach ($columns as $column) {
            $columnName = $column['data'] ?? '';
            if ($columnName === '')
                continue;

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
                $dbColumn = $columnMapping[$columnName] ?? "hrm_nhan_vien.$columnName";
                $this->db->like($dbColumn, $searchValue);
            }
        }
    }

    private function handleDatatableOrdering(array $orderBy, array $columns): void
    {
        if (empty($orderBy))
            return;

        foreach ($orderBy as $order) {
            $colIndex = $order['column'] ?? null;
            $dir = $order['dir'] ?? 'asc';

            if (isset($columns[$colIndex])) {
                $colName = $columns[$colIndex]['data'] ?? null;
                if ($colName) {
                    $this->db->order_by("hrm_nhan_vien.$colName", $dir);
                }
            }
        }
    }

    public function getDanhSach_forInthe($start = 0, $length = 10, $searchValue = null, $orderBy = [], $columns = [], $searchKey = array(), $fromDate = null, $toDate = null)
    {
        $this->db
            ->select('
                    hrm_nhan_vien.id_nhan_vien,
                    hrm_nhan_vien.ma_nhan_vien,
                    hrm_nhan_vien.ho_va_ten,
                    hrm_nhan_vien.avatar,
                    hrm_nhan_vien.gioi_tinh,
                    hrm_nhan_vien.ngay_sinh,
                    e_don_vi.id_don_vi,
                    e_don_vi.ten_don_vi,
                    hrm_vi_tri_cong_viec.id_vi_tri_cong_viec,
                    hrm_vi_tri_cong_viec.ten_cong_viec
            ')
            ->from('hrm_nhan_vien')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_nhan_vien.id_don_vi_cong_tac', 'left')
            ->join('hrm_vi_tri_cong_viec', 'hrm_vi_tri_cong_viec.id_vi_tri_cong_viec = hrm_nhan_vien.id_vi_tri_cong_viec', 'left');

        $this->db->where('hrm_nhan_vien.deleted_at IS NULL');

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('hrm_nhan_vien.ma_nhan_vien', $searchValue);
            $this->db->or_like('hrm_nhan_vien.ho_va_ten', $searchValue);
            $this->db->or_like('hrm_nhan_vien.email', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            $arr = ['ma_nhan_vien', 'ho_va_ten', 'email'];
            $arrCongViec = ['trang_thai'];
            foreach ($searchKey as $key => $value) {
                if (in_array($key, $arr)) {
                    $this->db->like('hrm_nhan_vien.' . $key, $value);
                } else {
                    if (is_array($value)) {
                        if (in_array($key, $arrCongViec)) {
                            $this->db->where_in('hrm_nhan_vien_cong_viec.' . $key, $value);
                        } else {
                            $this->db->where_in('hrm_nhan_vien.' . $key, $value);
                        }
                    } else {
                        $this->db->where('hrm_nhan_vien.' . $key, $value);
                    }
                }
            }
        }


        if ($fromDate && $toDate) {
            $fromDate = $fromDate . ' 00:00:00';
            $toDate = $toDate . ' 23:59:59';
            $this->db->where("hrm_nhan_vien.created_at BETWEEN '{$fromDate}' AND '{$toDate}'");
        }

        if (!empty($columns)) {
            // Map tên cột hiển thị (alias) sang cột thật trong DB
            $columnMapping = [
                'ma_nhan_vien' => 'hrm_nhan_vien.ma_nhan_vien',
                'ma_cham_cong' => 'hrm_nhan_vien.ma_cham_cong',
                'ho_va_ten' => 'hrm_nhan_vien.ho_va_ten',
                'avatar' => 'hrm_nhan_vien.avatar',
                'email' => 'hrm_nhan_vien.email',
                'gioi_tinh' => 'hrm_nhan_vien.gioi_tinh',
                'ngay_sinh' => 'hrm_nhan_vien.ngay_sinh',
                'ten_don_vi' => 'e_don_vi.ten_don_vi',
                'ten_cong_viec' => 'hrm_vi_tri_cong_viec.ten_cong_viec',
                // Nếu không có trong mapping thì mặc định dùng: hrm_nhan_vien.[column]
            ];

            $this->handleDatatableColumns($columns, $columnMapping);
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query

        // Xử lý order sau khi xử lý search
        if (!empty($orderBy)) {
            $this->handleDatatableOrdering($orderBy, $columns);
        }

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        $this->db->order_by('hrm_nhan_vien.id_nhan_vien', 'DESC');
        $query = $this->db->get();
        $data = $query->result_array();
        $sql = $this->db->last_query();

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'sql' => $sql,
        ];
    }


    public function get_employee_by_code($ma_nhan_vien)
    {
        $this->db->select('
            hrm_nhan_vien.ma_nhan_vien,
            hrm_nhan_vien.ma_cham_cong,
            hrm_nhan_vien.avatar,
            hrm_nhan_vien.ho_va_ten,
            hrm_nhan_vien.email,
            hrm_nhan_vien.gioi_tinh,
            hrm_nhan_vien.ngay_sinh,
            hrm_nhan_vien.so_dien_thoai,
            hrm_nhan_vien.trinh_do_dt,
            hrm_nhan_vien.nganh_dt,
            hrm_nhan_vien.xep_loai_tn,
            hrm_nhan_vien.khoa_dt,
            hrm_nhan_vien.noi_dt,
            hrm_nhan_vien.nam_tn,
            hrm_nhan_vien.cohn_dia_chi,
            e_don_vi.ten_don_vi,
            hrm_vi_tri_cong_viec.ten_cong_viec,
            hrm_nhan_vien_cong_viec.chuc_danh,
            hrm_nhan_vien_cong_viec.trang_thai,
            hrm_nhan_vien_cong_viec.ngay_lam_chinh_thuc
        ');
        $this->db->from('hrm_nhan_vien');
        $this->db->join('hrm_nhan_vien_cong_viec', 'hrm_nhan_vien_cong_viec.id_nhan_vien = hrm_nhan_vien.id_nhan_vien', 'left');
        $this->db->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_nhan_vien.id_don_vi_cong_tac', 'left');
        $this->db->join('hrm_vi_tri_cong_viec', 'hrm_vi_tri_cong_viec.id_vi_tri_cong_viec = hrm_nhan_vien.id_vi_tri_cong_viec', 'left');
        $this->db->where('hrm_nhan_vien.deleted_at', NULL);
        $this->db->where('hrm_nhan_vien.ma_nhan_vien', $ma_nhan_vien);

        $query = $this->db->get();
        return $query->row_array(); // Trả về 1 dòng
    }

    /**
     * Update record theo key
     *
     * @param string|array $where  điều kiện (vd: ['id' => 5] hoặc ['email' => 'a@b.com'])
     * @param array $data dữ liệu update (vd: ['avatar' => null, 'status' => 1])
     * @return bool
     */
    public function update_by($where, $data)
    {
        if (empty($where) || empty($data)) {
            return false;
        }
        $this->db->where($where);
        return $this->db->update($this->table, $data);
    }

    public function getNhanVienCungDonVi($id_don_vi)
    {
        $this->db->select('
                    hrm_nhan_vien.id_nhan_vien, 
                    hrm_nhan_vien.ho_va_ten, 
                    hrm_nhan_vien.ma_nhan_vien,
                    hrm_nhan_vien.ql_nguoi_dung_id,
                    IF(e_lanh_dao_don_vi.ql_nguoi_dung_id IS NOT NULL, 1, 0) AS lanh_dao
                    ')
            ->from('hrm_nhan_vien')
            ->join('hrm_nhan_vien_cong_viec', 'hrm_nhan_vien_cong_viec.id_nhan_vien = hrm_nhan_vien.id_nhan_vien', 'left')
            ->join('e_lanh_dao_don_vi', 'e_lanh_dao_don_vi.ql_nguoi_dung_id  = hrm_nhan_vien.ql_nguoi_dung_id ', 'left')
            // ->where('hrm_nhan_vien_cong_viec.trang_thai', Common::TRANG_THAI_CONG_VIEC['DANG_LAM_VIEC']['value'])
            ->where('hrm_nhan_vien.deleted_at IS NULL')
            ->where('hrm_nhan_vien.id_don_vi_cong_tac', $id_don_vi);

        $query = $this->db->get();
        return $query->result_array();
    }

    public function get_all_for_sheet()
    {
        return $this->db
            ->select('
                ma_nhan_vien,
                ho_va_ten,
                email,
                gioi_tinh,
                ngay_sinh,
                so_dien_thoai,
                id_don_vi_cong_tac
            ')
            ->where('deleted_at IS NULL', null, false)
            ->order_by('id_nhan_vien', 'ASC')
            ->get($this->table)
            ->result_array();
    }
    public function getEmployeesByUnit($unitId, $excludePositions = [])
    {
        $this->db->select("
            nv.id_nhan_vien,
            nv.ma_nhan_vien,
            nv.ql_nguoi_dung_id,
            CASE 
                WHEN nv.avatar IS NOT NULL AND nv.avatar != '' THEN nv.avatar 
                ELSE u.ql_nguoi_dung_avatar 
            END AS ql_nguoi_dung_avatar,
            nv.email as ql_nguoi_dung_email,
            nv.ho_va_ten as ql_nguoi_dung_ho_ten,
            nv.hoc_ham,
            nv.trinh_do_dt,
            u.ql_nguoi_dung_la_lanh_dao,
            cvic.ten_cong_viec
        ", FALSE);
        $this->db->from('hrm_nhan_vien as nv');
        $this->db->join('ql_nguoi_dung as u', 'u.ql_nguoi_dung_id = nv.ql_nguoi_dung_id', 'inner');
        $this->db->join('hrm_nhan_vien_cong_viec as cv', 'cv.id_nhan_vien = nv.id_nhan_vien', 'left');
        $this->db->join('hrm_vi_tri_cong_viec as cvic', 'cvic.id_vi_tri_cong_viec = nv.id_vi_tri_cong_viec', 'left');
        // JOIN bảng đơn vị kiêm nhiệm (left join vì không phải ai cũng có)
        $this->db->join('hrm_nhan_vien_don_vi as nv_dv', "nv_dv.id_nhan_vien = nv.id_nhan_vien AND nv_dv.id_don_vi_cong_tac = {$this->db->escape($unitId)}", 'left');

        // Lấy nhân viên thuộc đơn vị chính HOẶC có đơn vị kiêm nhiệm trùng unitId
        $this->db->group_start();
        $this->db->where('nv.id_don_vi_cong_tac', $unitId);
        $this->db->or_where('nv_dv.id_nhan_vien IS NOT NULL', NULL, FALSE);
        $this->db->group_end();

        $this->db->where('nv.deleted_at IS NULL');
        $this->db->where('u.active_flag', 1);

        // $this->db->group_start();
        // $this->db->where('cv.trang_thai IS NULL', NULL, FALSE);
        // $this->db->or_where_not_in('cv.trang_thai', ['NGHI_VIEC']);
        // $this->db->group_end();

        if (!empty($excludePositions)) {
            $this->db->group_start();
            foreach ($excludePositions as $position) {
                $this->db->not_like('LOWER(cvic.ten_cong_viec)', mb_strtolower($position, 'UTF-8'));
            }
            $this->db->group_end();
        }

        // Tránh trùng kết quả nếu nhân viên vừa thuộc đơn vị chính vừa có kiêm nhiệm
        $this->db->group_by('nv.id_nhan_vien');

        $query = $this->db->get();
        return $query->result_array();
    }
}
