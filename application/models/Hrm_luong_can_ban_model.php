<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_luong_can_ban_model extends MY_Model
{
    protected $table = 'hrm_luong_can_ban';
    protected $primaryKey = 'luong_can_ban_id';
    protected $timestamps = true;
    protected $createdAtField = 'created_at';
    protected $updatedAtField = 'updated_at';

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array(), $fromDate = null, $toDate = null)
    {
        $this->db->from('hrm_hop_dong')
            ->join('hrm_nhan_vien', 'hrm_nhan_vien.id_nhan_vien = hrm_hop_dong.id_nhan_vien', 'left')
            ->join('hrm_vi_tri_cong_viec', 'hrm_hop_dong.id_vi_tri_cong_viec = hrm_vi_tri_cong_viec.id_vi_tri_cong_viec', 'left')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_hop_dong.id_don_vi_cong_tac', 'left')
            ->join('hrm_nhan_vien_bao_hiem', 'hrm_nhan_vien_bao_hiem.id_nhan_vien = hrm_nhan_vien.id_nhan_vien', 'left')
            ->where('hrm_hop_dong.deleted_at', null);

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

        // if (!empty($searchKey)) {
        //     foreach ($searchKey as $key => $value) {
        //         $this->db->where('hrm_hop_dong.' . $key, $value);
        //     }
        // }

        if (!empty($searchKey)) {
            $arr = ['so_hop_dong', 'ten_hop_dong'];
            foreach ($searchKey as $key => $value) {
                if (in_array($key, $arr)) {
                    $this->db->like('hrm_hop_dong.' . $key, $value);
                } else {
                    if (is_array($value)) {
                        $this->db->where_in('hrm_hop_dong.' . $key, $value);
                    } else {
                        $this->db->where('hrm_hop_dong.' . $key, $value);
                    }
                }
            }
        }

        if ($fromDate && $toDate) {
            $this->db->where("ngay_bat_dau BETWEEN '{$fromDate}' AND '{$toDate}'");
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
            hrm_vi_tri_cong_viec.ten_cong_viec
        ');

        $query = $this->db->get();
        $data = $query->result_array();


        foreach ($data as &$dt) {
            $dt['file_hop_dong_duong_dan'] = encryptString($dt['file_hop_dong_duong_dan']);

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
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ];
    }

    public function getAllTrash($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array())
    {
        $this->db->from('hrm_hop_dong')
            ->join('hrm_nhan_vien', 'hrm_nhan_vien.id_nhan_vien = hrm_hop_dong.id_nhan_vien')
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
            'data' => $data
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

        if ($fromDate && $toDate) {
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
}
