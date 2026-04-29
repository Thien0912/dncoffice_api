<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_hop_dong_model extends MY_Model
{
    protected $table = 'hrm_hop_dong';
    protected $primaryKey = 'id_hop_dong';
    protected $timestamps = true;
    protected $createdAtField = 'ngay_tao';
    protected $updatedAtField = 'ngay_sua';

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $columns = [], $searchKey = array(), $fromDate = null, $toDate = null, $auth)
    {
        $this->db->from('hrm_hop_dong')
            ->join('hrm_nhan_vien', 'hrm_nhan_vien.id_nhan_vien = hrm_hop_dong.id_nhan_vien', 'left')
            ->join('hrm_vi_tri_cong_viec', 'hrm_hop_dong.id_vi_tri_cong_viec = hrm_vi_tri_cong_viec.id_vi_tri_cong_viec', 'left')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_hop_dong.id_don_vi_cong_tac', 'left')
            ->join('hrm_nhan_vien_bao_hiem', 'hrm_nhan_vien_bao_hiem.id_nhan_vien = hrm_nhan_vien.id_nhan_vien', 'left')
            ->join('ql_nguoi_dung AS nguoitao', 'nguoitao.ql_nguoi_dung_id = hrm_hop_dong.nguoi_tao', 'left');
        // ->where('hrm_hop_dong.deleted_at', null)
        // ->order_by('hrm_hop_dong.id_hop_dong', 'DESC');

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('hrm_hop_dong.id_nhan_vien', $searchValue);
            $this->db->or_like('hrm_hop_dong.so_hop_dong', $searchValue);
            $this->db->or_like('hrm_hop_dong.ngay_bat_dau', $searchValue);
            $this->db->or_like('hrm_hop_dong.ngay_ket_thuc', $searchValue);
            $this->db->or_like('hrm_hop_dong.luong_co_ban', $searchValue);
            $this->db->or_like('hrm_hop_dong.muc_luong_bao_hiem', $searchValue);
            $this->db->or_like('hrm_hop_dong.loai_hop_dong', $searchValue);
            $this->db->or_like('hrm_hop_dong.ngay_tao', $searchValue);
            $this->db->or_like('hrm_nhan_vien.ho_va_ten', $searchValue);
            $this->db->or_like('hrm_nhan_vien.email', $searchValue);
            $this->db->or_like('hrm_nhan_vien.so_dien_thoai', $searchValue);
            $this->db->group_end();
        }
        if (!empty($fromDate) && !empty($toDate) && $fromDate != 'null' && $toDate != 'null' && $fromDate != 'undefined' && $toDate != 'undefined') {
            $this->db->where("ngay_bat_dau BETWEEN '{$fromDate}' AND '{$toDate}'");
        }

        if (!empty($searchKey)) {
            if (!empty($searchKey['ngay_ky_tu']) && !empty($searchKey['ngay_ky_den'])) {
                $from = $searchKey['ngay_ky_tu'];
                $to   = $searchKey['ngay_ky_den'];
                $this->db->where("DATE(hrm_hop_dong.ngay_bat_dau) BETWEEN '{$from}' AND '{$to}'");
            }

            if (!empty($searchKey['ngay_ket_thuc_tu']) && !empty($searchKey['ngay_ket_thuc_den'])) {
                $from = $searchKey['ngay_ket_thuc_tu'];
                $to   = $searchKey['ngay_ket_thuc_den'];
                $this->db->where("DATE(hrm_hop_dong.ngay_ket_thuc) BETWEEN '{$from}' AND '{$to}'");
            }

            foreach ($searchKey as $key => $value) {
                if (!empty($value)) {
                    switch ($key) {
                        case 'so_hop_dong':
                            $this->db->like('hrm_hop_dong.so_hop_dong', $value);
                            break;
                        case 'loai_hop_dong':
                            $this->db->where('hrm_hop_dong.loai_hop_dong', $value);
                            break;
                        case 'year':
                            if ($value !== 'all_years') {
                                $this->db->where("YEAR(hrm_hop_dong.ngay_bat_dau)", $value);
                            }
                            break;
                        case 'selectedClassify':
                            switch ($value) {
                                case 'all':
                                    $this->db->where('hrm_hop_dong.deleted_at', null);
                                    break;
                                case 'dang_hieu_luc':
                                    $this->db->where('hrm_hop_dong.dang_hieu_luc', 1);
                                    $this->db->where('hrm_hop_dong.deleted_at', null);
                                    break;
                                case 'het_hieu_luc':
                                    $this->db->where('hrm_hop_dong.dang_hieu_luc', 0);
                                    $this->db->where('hrm_hop_dong.deleted_at', null);
                                    break;
                                case 'tren_30_ngay':
                                    $this->db->where('DATEDIFF(hrm_hop_dong.ngay_ket_thuc, CURDATE()) > ', 30);
                                    $this->db->where('hrm_hop_dong.deleted_at', null);
                                    break;
                                case 'duoi_30_ngay':
                                    $this->db->where('DATEDIFF(hrm_hop_dong.ngay_ket_thuc, CURDATE()) <= ', 30);
                                    $this->db->where('DATEDIFF(hrm_hop_dong.ngay_ket_thuc, CURDATE()) >= ', 0);
                                    $this->db->where('hrm_hop_dong.deleted_at', null);
                                    break;
                                case 'duoi_15_ngay':
                                    $this->db->where('DATEDIFF(hrm_hop_dong.ngay_ket_thuc, CURDATE()) <= ', 15);
                                    $this->db->where('DATEDIFF(hrm_hop_dong.ngay_ket_thuc, CURDATE()) >= ', 0);
                                    $this->db->where('hrm_hop_dong.deleted_at', null);
                                    break;
                                case 'het_han':
                                    $this->db->where('DATEDIFF(hrm_hop_dong.ngay_ket_thuc,  CURDATE()) < ', 0);
                                    $this->db->where('hrm_hop_dong.deleted_at', null);
                                    break;

                                case 'da_xoa':
                                    $this->db->where('hrm_hop_dong.deleted_at IS NOT NULL');
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

        if (!empty($columns)) {
            // Map tên cột hiển thị (alias) sang cột thật trong DB
            $columnMapping = [
                'ho_va_ten'        => 'hrm_nhan_vien.ho_va_ten',
                'ten_nguoi_tao'      => 'nguoitao.ql_nguoi_dung_ho_ten',
                // Mặc định nếu không map thì nó sẽ lấy e_van_ban.[columnName]
            ];
            $this->handleDatatableColumns($columns, $columnMapping);
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query

        // if (!empty($orderBy)) {
        //     $order = $orderBy['order'];
        //     $orderColumnIndex = $order[0]['column'];
        //     $orderDir = $order[0]['dir'];

        //     $columns = $orderBy['columns'];
        //     $filed = $columns[$orderColumnIndex]['data'];

        //     if (!empty($orderDir)) {
        //         $this->db->order_by('hrm_hop_dong.' . $filed, $orderDir);
        //     }
        // }

        // if (!empty($orderBy)) {
        //     $this->handleDatatableOrdering($orderBy, $columns);
        // }

        if (!empty($orderBy) && is_array($orderBy)) {
            foreach ($orderBy as $orderItem) {
                if (
                    isset($orderItem['column']) &&
                    isset($orderItem['dir']) &&
                    !empty($orderItem['column']) &&
                    !empty($orderItem['dir'])
                ) {
                    // Tên cột anh gửi từ client: so_hop_dong, ngay_bat_dau, ...
                    $col = $orderItem['column'];
                    $dir = strtolower($orderItem['dir']) === 'desc' ? 'DESC' : 'ASC';

                    // Thực hiện ORDER BY
                    $this->db->order_by("hrm_hop_dong.$col", $dir);
                }
            }
        }



        if ($length != '-1') {
            $this->db->limit($length, $start);
        }


        $this->db->select('
            hrm_hop_dong.*, 
            hrm_nhan_vien.email, 
            hrm_nhan_vien.ma_nhan_vien, 
            hrm_nhan_vien.ho_va_ten, 
            hrm_nhan_vien.gioi_tinh,
            hrm_nhan_vien.ngay_sinh,
            hrm_nhan_vien.so_dien_thoai,
            hrm_nhan_vien.avatar,
            hrm_nhan_vien.cccd_so,
            hrm_nhan_vien.cccd_ngay_cap,
            hrm_nhan_vien.cccd_noi_cap,
            hrm_nhan_vien.mst_ca_nhan,
            hrm_nhan_vien_bao_hiem.ma_bhxh,
            e_don_vi.ten_don_vi,
            hrm_vi_tri_cong_viec.ten_cong_viec,
            nguoitao.ql_nguoi_dung_ho_ten AS ten_nguoi_tao,
            nguoitao.ql_nguoi_dung_email AS email_nguoi_tao,
        ');

        $query = $this->db->get();
        $data = $query->result_array();
        $sql = $this->db->last_query();

        foreach ($data as &$dt) {
            $dt['file_hop_dong_duong_dan'] = encryptString($dt['file_hop_dong_duong_dan']);

            $dsFile = '';
            $dsFile = json_decode($dt['files_hop_dong'], true);
            if ($dsFile) {
                foreach ($dsFile as &$file) {
                    $file['file_path'] = encryptString($file['file_path']);
                    $file['ten_file_goc'] = $file['file_name'];
                    $file['duong_dan'] = ($file['file_path']);
                    $file['dung_luong'] = $file['file_size'];
                    $file['loai_file'] = $file['file_extension'];
                }
                unset($file);
            }

            $dt['files_hop_dong'] = $dsFile;

            if ($dt['avatar']) {
                $dt['path_anh_dai_dien'] = base_url($dt['avatar']);
            } else {
                $dt['path_anh_dai_dien'] = null;
            }

            $dt['phu_cap'] = $this->db
                ->select('hrm_phu_cap_nhan_vien.*, hrm_phu_cap.ten_phu_cap, hrm_phu_cap.ma_phu_cap, hrm_phu_cap.mo_ta')
                ->from('hrm_phu_cap_nhan_vien')
                ->join('hrm_phu_cap', 'hrm_phu_cap.id_phu_cap = hrm_phu_cap_nhan_vien.id_phu_cap')
                ->where('hrm_phu_cap_nhan_vien.id_nhan_vien', $dt['id_nhan_vien'])
                ->get()
                ->result_array();

            $dt['tong_phu_luc'] = $this->db
                ->from('hrm_hop_dong_phu_luc')
                ->where('id_hop_dong', $dt['id_hop_dong'])
                ->where('ngay_hieu_luc IS NOT NULL')
                ->count_all_results();

            $phuLucHopDong = $this->db
                ->select('*')
                ->from('hrm_hop_dong_phu_luc')
                ->where('id_hop_dong', $dt['id_hop_dong'])
                ->order_by('id_hop_dong_phu_luc', 'DESC')
                ->get()
                ->row_array();

            if ($phuLucHopDong) {
                $dt['ngay_hieu_luc_phu_luc'] = $phuLucHopDong['ngay_hieu_luc'];
            } else {
                $dt['ngay_hieu_luc_phu_luc'] = '';
            }
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'sql' => $sql
        ];
    }

    public function getAllTrash($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array())
    {
        $this->db->from('hrm_hop_dong')
            ->join('hrm_nhan_vien', 'hrm_nhan_vien.id_nhan_vien = hrm_hop_dong.id_nhan_vien', 'left')
            ->where('hrm_hop_dong.deleted_at IS NOT NULL');

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('hrm_hop_dong.id_nhan_vien', $searchValue);
            $this->db->or_like('hrm_hop_dong.so_hop_dong', $searchValue);
            $this->db->or_like('hrm_hop_dong.ngay_bat_dau', $searchValue);
            $this->db->or_like('hrm_hop_dong.ngay_ket_thuc', $searchValue);
            $this->db->or_like('hrm_hop_dong.muc_luong', $searchValue);
            $this->db->or_like('hrm_hop_dong.loai_hop_dong', $searchValue);
            $this->db->or_like('hrm_hop_dong.ngay_tao', $searchValue);
            $this->db->or_like('hrm_nhan_vien.ho_ten', $searchValue);
            $this->db->or_like('hrm_nhan_vien.email', $searchValue);
            $this->db->or_like('hrm_nhan_vien.so_dien_thoai', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                $this->db->where('hrm_hop_dong.' . $key, $value);
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
                $this->db->order_by('hrm_hop_dong.' . $filed, $orderDir);
            }
        }

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        $this->db->select('
            hrm_hop_dong.*, 
            hrm_nhan_vien.ho_va_ten, 
            hrm_nhan_vien.gioi_tinh,
            hrm_nhan_vien.ngay_sinh,
            hrm_nhan_vien.so_dien_thoai,
            hrm_nhan_vien.avatar,
        ');
        $query = $this->db->get();
        $data = $query->result_array();
        $sql = $this->db->last_query();


        foreach ($data as &$dt) {
            $dt['file_hop_dong_duong_dan'] = encryptString($dt['file_hop_dong_duong_dan']);

            if ($dt['avatar']) {
                $dt['path_anh_dai_dien'] = base_url($dt['avatar']);
            } else {
                $dt['path_anh_dai_dien'] = null;
            }
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'sql' => $sql
        ];
    }

    public function getListExport($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = [], $fromDate = null, $toDate = null)
    {
        $this->db->from('hrm_hop_dong')
            ->join('hrm_nhan_vien', 'hrm_nhan_vien.id_nhan_vien = hrm_hop_dong.id_nhan_vien', 'left')
            ->join('hrm_vi_tri_cong_viec', 'hrm_hop_dong.id_vi_tri_cong_viec = hrm_vi_tri_cong_viec.id_vi_tri_cong_viec', 'left')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_hop_dong.id_don_vi_cong_tac', 'left')
            ->join('hrm_nhan_vien_bao_hiem', 'hrm_nhan_vien_bao_hiem.id_nhan_vien = hrm_nhan_vien.id_nhan_vien', 'left')
            ->where('hrm_hop_dong.deleted_at', null);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('hrm_hop_dong.id_nhan_vien', $searchValue);
            $this->db->or_like('hrm_hop_dong.so_hop_dong', $searchValue);
            $this->db->or_like('hrm_hop_dong.ngay_bat_dau', $searchValue);
            $this->db->or_like('hrm_hop_dong.ngay_ket_thuc', $searchValue);
            $this->db->or_like('hrm_hop_dong.luong_co_ban', $searchValue);
            $this->db->or_like('hrm_hop_dong.muc_luong_bao_hiem', $searchValue);
            $this->db->or_like('hrm_hop_dong.loai_hop_dong', $searchValue);
            $this->db->or_like('hrm_hop_dong.ngay_tao', $searchValue);
            $this->db->or_like('hrm_nhan_vien.ho_va_ten', $searchValue);
            $this->db->or_like('hrm_nhan_vien.email', $searchValue);
            $this->db->or_like('hrm_nhan_vien.so_dien_thoai', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                $this->db->where('hrm_hop_dong.' . $key, $value);
            }
        }

        if (!empty($fromDate) && !empty($toDate) && $fromDate != 'null' && $toDate != 'null' && $fromDate != 'undefined' && $toDate != 'undefined') {
            $this->db->where("ngay_bat_dau BETWEEN '{$fromDate}' AND '{$toDate}'");
        }

        $this->db->select('
            hrm_hop_dong.*, 
            hrm_nhan_vien.ho_va_ten, 
            hrm_nhan_vien.ma_nhan_vien, 
            hrm_nhan_vien.gioi_tinh,
            hrm_nhan_vien.ngay_sinh,
            hrm_nhan_vien.so_dien_thoai,
            hrm_nhan_vien.avatar,
            hrm_nhan_vien.cccd_so,
            hrm_nhan_vien.cccd_ngay_cap,
            hrm_nhan_vien.cccd_noi_cap,
            hrm_nhan_vien.mst_ca_nhan,
            hrm_nhan_vien_bao_hiem.ma_bhxh,
            e_don_vi.ten_don_vi,
            hrm_vi_tri_cong_viec.ten_cong_viec
        ');

        $query = $this->db->get();
        $data = $query->result_array();


        foreach ($data as &$dt) {
            // $dt['file_hop_dong_duong_dan'] = encryptString($dt['file_hop_dong_duong_dan']);

            // if ($dt['avatar']) {
            //     $dt['path_anh_dai_dien'] = base_url($dt['avatar']);
            // } else {
            //     $dt['path_anh_dai_dien'] = null;
            // }

            $dt['phu_cap'] = $this->db
                ->select('hrm_phu_cap_nhan_vien.*, hrm_phu_cap.ten_phu_cap, hrm_phu_cap.ma_phu_cap, hrm_phu_cap.mo_ta')
                ->from('hrm_phu_cap_nhan_vien')
                ->join('hrm_phu_cap', 'hrm_phu_cap.id_phu_cap = hrm_phu_cap_nhan_vien.id_phu_cap')
                ->where('hrm_phu_cap_nhan_vien.id_nhan_vien', $dt['id_nhan_vien'])
                ->get()
                ->result_array();
        }

        return $data;
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
                $dbColumn = $columnMapping[$columnName] ?? "hrm_hop_dong.$columnName";

                // // Nếu là trạng_thai thì convert lại
                // if ($columnName === 'trang_thai') {
                //     $searchValue = $this->getTrangThaiValueFromLabel($searchValue);
                // }

                $this->db->like($dbColumn, $searchValue);
            }
        }
    }

    private function handleDatatableOrdering(array $orderBy, array $columns): void
    {
        if (empty($orderBy)) return;

        $dsColNameTemp = [
            'trang_thai',
            'ten_phu_luc',
            'ngay_ky_phu_luc',
            'ngay_hieu_luc',
            'file_phu_luc'
        ];

        foreach ($orderBy as $order) {
            $colIndex = $order['column'] ?? null;
            $dir = $order['dir'] ?? 'asc';

            if (isset($columns[$colIndex])) {
                $colName = $columns[$colIndex]['data'] ?? null;
                if ($colName && (!in_array($colName, $dsColNameTemp))) {
                    $this->db->order_by("hrm_hop_dong.$colName", $dir);
                }
            }
        }
    }
}
