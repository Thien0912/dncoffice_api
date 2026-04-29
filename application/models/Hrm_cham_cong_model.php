<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_cham_cong_model extends MY_Model
{
    protected $table = 'hrm_cham_cong';
    protected $primaryKey = 'id';

    protected $timestamps = false;


    public function __construct()
    {
        parent::__construct();
    }

    public function getAll($start = null, $length = null, $searchValue = null, $orderBy = [], $searchKey = array(), $nhanvien = null, $fromDate = null, $toDate = null, $ca = null)
    {
        $this->db->select('hrm_cham_cong.*, hrm_nhan_vien.ho_va_ten');
        $this->db->from('hrm_cham_cong');
        $this->db->join('hrm_nhan_vien', 'hrm_nhan_vien.ma_cham_cong = hrm_cham_cong.ma_cham_cong', 'left');
        
        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('hrm_nhan_vien.ho_va_ten', $searchValue);
            $this->db->or_like('hrm_cham_cong.ma_cham_cong', $searchValue);
            $this->db->group_end();
        }

        if ($fromDate && $toDate) {
            $this->db->where("DATE(hrm_cham_cong.gio_diem_danh) BETWEEN '{$fromDate}' AND '{$toDate}'");
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query 

        if (!empty($orderBy)) {
      
            $order = $orderBy['order'];
            $orderColumnIndex = $order[0]['column'];
            $orderDir = $order[0]['dir'];

            $columns = $orderBy['columns'];
            $filed = $columns[$orderColumnIndex]['data'];

            $this->db->order_by('hrm_cham_cong.' . $filed, $orderDir);
        }
        
        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        $query = $this->db->get();
        $data = $query->result_array();  

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }


    public function getListExportChamcong($start = null, $length = -1, $searchValue = null, $searchKey = array(), $fromDate = null, $toDate = null , $nhanvien = null , $ca = null)
    {
        $this->db->from('hrm_cham_cong')
            ->join('hrm_nhan_vien', 'hrm_nhan_vien.ma_cham_cong = hrm_cham_cong.ma_cham_cong', 'left')
            ->select(
                "hrm_nhan_vien.ho_va_ten as ho_ten, DATE_FORMAT(hrm_cham_cong.gio_diem_danh, '%d/%m/%Y %H:%i:%s') as gio_diem_danh, 
                CASE 
                    WHEN hrm_cham_cong.ca_lam_viec = 'sang' THEN 'Sáng' 
                    WHEN hrm_cham_cong.ca_lam_viec = 'chieu' THEN 'Chiều' 
                    WHEN hrm_cham_cong.ca_lam_viec = 'ngoai_gio' THEN 'Ngoài giờ' 
                    ELSE hrm_cham_cong.ca_lam_viec 
                END as ca_lam_viec, 
                hrm_cham_cong.ma_cham_cong as ma_cham_cong,
                CASE WHEN hrm_cham_cong.di_muon = 0 THEN '-' ELSE hrm_cham_cong.di_muon END as di_muon, 
                CASE WHEN hrm_cham_cong.ve_som = 0 THEN '-' ELSE hrm_cham_cong.ve_som END as ve_som, 
                CASE WHEN hrm_cham_cong.so_phut_lam_them = 0 THEN '-' ELSE hrm_cham_cong.so_phut_lam_them END as so_phut_lam_them, 
                hrm_cham_cong.ghi_chu as ghi_chu
                "
            );

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('hrm_nhan_vien.ho_va_ten', $searchValue);
            $this->db->or_like('hrm_cham_cong.ma_cham_cong', $searchValue);
            $this->db->group_end();
        }

        if ($nhanvien) {
            $this->db->where_in('hrm_nhan_vien.id_nhan_vien', explode(',', $nhanvien));
        }

        if($ca){
            $this->db->where('hrm_cham_cong.ca_lam_viec', $ca);
        }

        
        if ($fromDate && $toDate) {
            $this->db->where("DATE(hrm_cham_cong.gio_diem_danh) BETWEEN '{$fromDate}' AND '{$toDate}'");
        }

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        $query = $this->db->get();
        $data = $query->result_array();

        return $data;
    }

    public function getExistingIds($ids = [])
    {
        if (empty($ids)) return [];

        $this->db->select('id');
        $this->db->from('hrm_api_cham_cong'); 
        $this->db->where_in('id', $ids);
        $query = $this->db->get();

        return array_column($query->result_array(), 'id');
    }

    public function get_data($start_date, $end_date)
    {
        $this->db->select('*');
        $this->db->from('hrm_api_cham_cong');
        $this->db->where('punch_time >=', $start_date);
        $this->db->where('punch_time <=', $end_date);
        $query = $this->db->get();

        return $query->result_array();
    }

    public function get_all_bang_cham_cong_thang($start = null, $length = null, $searchValue = null, $orderBy = [], $searchKey = array(), $fromDate = null, $toDate = null)
    {
        $this->db->select('cct.*');
        $this->db->from('hrm_bang_cham_cong_thang as cct');
        
        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('cct.ten_bang', $searchValue);
            $this->db->or_like('cct.thang', $searchValue);
            $this->db->group_end();
        }

        if ($fromDate && $toDate) {
            $this->db->group_start();
            $this->db->where('cct.ngay_ket_thuc >=', $fromDate);
            $this->db->where('cct.ngay_bat_dau <=', $toDate);
            $this->db->group_end();
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query 

        if (!empty($orderBy)) {
      
            $order = $orderBy['order'];
            $orderColumnIndex = $order[0]['column'];
            $orderDir = $order[0]['dir'];

            $columns = $orderBy['columns'];
            $filed = $columns[$orderColumnIndex]['data'];

            $this->db->order_by('cct.' . $filed, $orderDir);
        } else {
            $this->db->order_by('cct.ngay_bat_dau', 'DESC');
        }
        
        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        $query = $this->db->get();
        $data = $query->result_array();  

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }

    public function get_chi_tiet_cham_cong($start = null, $length = null, $searchValue = null, $orderBy = [], $searchKey = array(), $fromDate = null, $toDate = null, $id_bang_cham_cong_thang)
    {
        $this->db->select('cct.*, nv.ho_va_ten as ho_va_ten');
        $this->db->from('hrm_cham_cong_chi_tiet as cct');
        $this->db->join('hrm_nhan_vien as nv', 'nv.ma_cham_cong = cct.ma_nhan_vien', 'left');

        $this->db->where('cct.id_bang_cham_cong_thang', $id_bang_cham_cong_thang);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('cct.ma_cham_cong', $searchValue);
            $this->db->or_like('cct.ho_va_ten', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            $arr = ['ma_cham_cong', 'ho_va_ten'];
            foreach ($searchKey as $key => $value) {
                if (in_array($key, $arr)) {
                    $this->db->like('nv.' . $key, $value);
                } else {
                    if (is_array($value)) {
                        $this->db->where_in('nv.' . $key, $value);
                    } else {
                        $this->db->where('nv.' . $key, $value);
                    }
                }
            }
        }

        if ($fromDate && $toDate) {
            $this->db->where("DATE(cct.ngay_cham_cong) BETWEEN '{$fromDate}' AND '{$toDate}'");
        }

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE);

        if (!empty($orderBy)) {
            $order = $orderBy['order'];
            $orderColumnIndex = $order[0]['column'];
            $orderDir = $order[0]['dir'];

            $columns = $orderBy['columns'];
            $field = $columns[$orderColumnIndex]['data'];

            $this->db->order_by('cct.' . $field, $orderDir);
        }

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        $query = $this->db->get();
        $data = $query->result_array();

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'sql' => $this->db->last_query(),
        ];
    }
    public function update_bang_cham_cong($id, $data)
    {
        if (empty($id) || empty($data) || !is_array($data)) {
            return false;
        }

        $this->db->where('id', $id);
        return $this->db->update('hrm_bang_cham_cong_thang', $data);
    }

    public function get_trang_thai_bang_cham_cong($id_bang_cham_cong_thang)
    {
        if (empty($id_bang_cham_cong_thang)) {
            return null;
        }

        $this->db->select('trang_thai');
        $this->db->from('hrm_bang_cham_cong_thang');
        $this->db->where('id', $id_bang_cham_cong_thang);
        $query = $this->db->get();

        $result = $query->row_array();
        return $result ? $result['trang_thai'] : null;
    }

    public function getListExportChamcongChiTiet($start = null, $length = null, $searchValue = null, $orderBy = [] , $searchKey = array(), $fromDate = null, $toDate = null, $id_bang_cham_cong_thang = null)
    {
        $this->db->select('cct.*, nv.ho_va_ten as ho_va_ten');
        $this->db->from('hrm_cham_cong_chi_tiet as cct');
        $this->db->join('hrm_nhan_vien as nv', 'nv.ma_cham_cong = cct.ma_nhan_vien', 'left');

        $this->db->where('cct.id_bang_cham_cong_thang', $id_bang_cham_cong_thang);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('cct.ma_cham_cong', $searchValue);
            $this->db->or_like('cct.ho_va_ten', $searchValue);
            $this->db->group_end();
        }

        if ($fromDate && $toDate) {
            $this->db->where("DATE(cct.ngay_cham_cong) BETWEEN '{$fromDate}' AND '{$toDate}'");
        }

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE);

        if (!empty($orderBy)) {
            $order = $orderBy['order'];
            $orderColumnIndex = $order[0]['column'];
            $orderDir = $order[0]['dir'];

            $columns = $orderBy['columns'];
            $field = $columns[$orderColumnIndex]['data'];

            $this->db->order_by('cct.' . $field, $orderDir);
        }

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        $query = $this->db->get();
        $data = $query->result_array();

        return $data;
    }

    // public function getChamCongApiByNhanVienAndBangChamCong($ma_nhan_vien, $id_bang_cham_cong_thang, $start = null, $length = null, $searchValue = null, $orderBy = [], $searchKey = array(), $fromDate = null, $toDate = null)
    // {
    //     if (empty($ma_nhan_vien) || empty($id_bang_cham_cong_thang)) {
    //         return resSuccess([], 'Successsssss', REST_Controller::HTTP_OK, true, [
    //             'recordsTotalllll' => 0,
    //             'recordsFilteredddd' => 0
    //         ]);
    //     }

    //     // Lấy ngày bắt đầu và kết thúc từ bảng hrm_bang_cham_cong_thang
    //     $this->db->select('ngay_bat_dau, ngay_ket_thuc');
    //     $this->db->from('hrm_bang_cham_cong_thang');
    //     $this->db->where('id', $id_bang_cham_cong_thang);
    //     $query = $this->db->get();
    //     $row = $query->row_array();

    //     if (!$row) {
    //         return resSuccess([], 'Success', REST_Controller::HTTP_OK, true, [
    //             'recordsTotal' => 0,
    //             'recordsFiltered' => 0
    //         ]);
    //     }

    //     $ngay_bat_dau = $row['ngay_bat_dau'];
    //     $ngay_ket_thuc = $row['ngay_ket_thuc'];

    //     // Thông tin ca làm việc
    //     $this->db->select('nv.id_ca_lam_viec, ca.ca_lam_viec, ca.check_in, ca.bat_dau_check_in, ca.ket_thuc_check_in, ca.check_out, ca.bat_dau_check_out, ca.ket_thuc_check_out');
    //     $this->db->from('hrm_nhan_vien as nv');
    //     $this->db->join('hrm_ca_lam_viec as ca', 'nv.id_ca_lam_viec = ca.id', 'left');
    //     $this->db->where('nv.ma_cham_cong', $ma_nhan_vien);
    //     $ca_info = $this->db->get()->row_array();

    //     // Dữ liệu chấm công
    //     $this->db->select('*');
    //     $this->db->from('hrm_api_cham_cong');
    //     $this->db->where('emp_code', $ma_nhan_vien);

    //     if ($fromDate && $toDate) {
    //         $this->db->where('DATE(punch_time) >=', $fromDate);
    //         $this->db->where('DATE(punch_time) <=', $toDate);
    //     } else {
    //         $this->db->where('DATE(punch_time) >=', $ngay_bat_dau);
    //         $this->db->where('DATE(punch_time) <=', $ngay_ket_thuc);
    //     }

    //     $this->db->order_by('punch_time', 'ASC');
    //     $rows = $this->db->get()->result_array();

    //     // Gom nhóm theo ngày
    //     $dailyData = [];
    //     foreach ($rows as $row) {
    //         $date = date('Y-m-d', strtotime($row['punch_time']));
    //         $dailyData[$date][] = $row['punch_time'];
    //     }

    //     $result = [];
    //     foreach ($dailyData as $date => $punches) {
    //         sort($punches); // Đảm bảo thứ tự thời gian

    //         $count = count($punches);

    //         // Mặc định lần đầu là gio_vao, lần cuối là gio_ra, 2 giờ ở giữa là nghỉ trưa
    //         $gio_vao = $count > 0 ? date('H:i:s', strtotime($punches[0])) : null;
    //         $gio_ra = $count > 1 ? date('H:i:s', strtotime($punches[$count - 1])) : null;
    //         $gio_nghi_trua_bat_dau = $count > 2 ? date('H:i:s', strtotime($punches[1])) : null;
    //         $gio_nghi_trua_ket_thuc = $count > 3 ? date('H:i:s', strtotime($punches[2])) : null;

    //         // Nếu chỉ có 1 lần chấm thì gio_ra = null, nghỉ trưa = null
    //         if ($count == 1) {
    //             $gio_ra = null;
    //             $gio_nghi_trua_bat_dau = null;
    //             $gio_nghi_trua_ket_thuc = null;
    //         }
    //         // Nếu có 2 lần chấm thì nghỉ trưa = null
    //         if ($count == 2) {
    //             $gio_nghi_trua_bat_dau = null;
    //             $gio_nghi_trua_ket_thuc = null;
    //         }
    //         // Nếu có 3 lần chấm thì nghỉ trưa bắt đầu có, kết thúc = null
    //         if ($count == 3) {
    //             $gio_nghi_trua_ket_thuc = null;
    //         }

    //         $weekday = date('N', strtotime($date));
    //         $thuMap = [
    //             1 => 'Thứ hai',
    //             2 => 'Thứ ba',
    //             3 => 'Thứ tư',
    //             4 => 'Thứ năm',
    //             5 => 'Thứ sáu',
    //             6 => 'Thứ bảy',
    //             7 => 'Chủ nhật'
    //         ];
    //         $thu = $thuMap[$weekday];

    //         // Lấy thông tin ca làm việc
    //         $ca_lam_viec = $ca_info['ca_lam_viec'] ?? null;
    //         $check_in = $ca_info['check_in'] ?? null;
    //         $bat_dau_check_in = $ca_info['bat_dau_check_in'] ?? null;
    //         $ket_thuc_check_in = $ca_info['ket_thuc_check_in'] ?? null;
    //         $check_out = $ca_info['check_out'] ?? null;
    //         $bat_dau_check_out = $ca_info['bat_dau_check_out'] ?? null;
    //         $ket_thuc_check_out = $ca_info['ket_thuc_check_out'] ?? null;

    //         // Tính đi muộn
    //         $di_muon = 0;
    //         if ($gio_vao && $bat_dau_check_in && $check_in) {
    //             $punch_time = strtotime($date . ' ' . $gio_vao);
    //             $bat_dau_check_in_time = strtotime($date . ' ' . $bat_dau_check_in);
    //             $check_in_time = strtotime($date . ' ' . $check_in);
    //             if ($punch_time >= $bat_dau_check_in_time && $punch_time <= $check_in_time) {
    //                 $di_muon = 0;
    //             } elseif ($ket_thuc_check_in && $punch_time > $check_in_time && $punch_time <= strtotime($date . ' ' . $ket_thuc_check_in)) {
    //                 $di_muon = round(($punch_time - $check_in_time) / 60);
    //             }
    //         }

    //         // Tính về sớm và làm thêm
    //         $ve_som = 0;
    //         $so_phut_lam_them = 0;
    //         if ($gio_ra && $bat_dau_check_out && $check_out) {
    //             $punch_time_ra = strtotime($date . ' ' . $gio_ra);
    //             $bat_dau_check_out_time = strtotime($date . ' ' . $bat_dau_check_out);
    //             $check_out_time = strtotime($date . ' ' . $check_out);

    //             if ($punch_time_ra >= $bat_dau_check_out_time && $punch_time_ra < $check_out_time) {
    //                 $ve_som = round(($check_out_time - $punch_time_ra) / 60);
    //             }
    //             if ($check_out && $ket_thuc_check_out) {
    //                 $ket_thuc_check_out_time = strtotime($date . ' ' . $ket_thuc_check_out);
    //                 if ($punch_time_ra >= $check_out_time && $punch_time_ra <= $ket_thuc_check_out_time) {
    //                     $so_phut_lam_them = round(($punch_time_ra - $check_out_time) / 60);
    //                 }
    //             }
    //         }

    //         $row = [
    //             'thu' => $thu,
    //             'ngay' => date('d/m/Y', strtotime($date)),
    //             'gio_vao' => $gio_vao,
    //             'gio_nghi_trua_bat_dau' => $gio_nghi_trua_bat_dau,
    //             'gio_nghi_trua_ket_thuc' => $gio_nghi_trua_ket_thuc,
    //             'gio_ra' => $gio_ra,
    //             'ca_lam_viec' => $ca_lam_viec,
    //             'check_in' => $check_in,
    //             'bat_dau_check_in' => $bat_dau_check_in,
    //             'ket_thuc_check_in' => $ket_thuc_check_in,
    //             'check_out' => $check_out,
    //             'bat_dau_check_out' => $bat_dau_check_out,
    //             'ket_thuc_check_out' => $ket_thuc_check_out,
    //             'di_muon' => $di_muon,
    //             've_som' => $ve_som,
    //             'so_phut_lam_them' => $so_phut_lam_them,
    //         ];

    //         $result[] = $row;
    //     }

    //     $recordsTotal = count($result);
    //     $recordsFiltered = $recordsTotal;

    //     if ($length != '-1' && $start !== null) {
    //         $result = array_slice($result, $start, $length);
    //     }

    //     return [
    //         'data' => $result,
    //         'recordsTotal' => $recordsTotal,
    //         'recordsFiltered' => $recordsFiltered
    //     ];
    // }

    public function getChamCongApiByNhanVienAndBangChamCong($ma_nhan_vien, $id_bang_cham_cong_thang, $start = null, $length = null, $searchValue = null, $orderBy = [], $searchKey = array(), $fromDate = null, $toDate = null)
    {
        if (empty($ma_nhan_vien) || empty($id_bang_cham_cong_thang)) {
            return resSuccess([], 'Success', REST_Controller::HTTP_OK, true, [
                'recordsTotal' => 0,
                'recordsFiltered' => 0
            ]);
        }

        // Lấy ngày bắt đầu và kết thúc
        $this->db->select('ngay_bat_dau, ngay_ket_thuc');
        $this->db->from('hrm_bang_cham_cong_thang');
        $this->db->where('id', $id_bang_cham_cong_thang);
        $row = $this->db->get()->row_array();

        if (!$row) {
            return resSuccess([], 'Success', REST_Controller::HTTP_OK, true, [
                'recordsTotal' => 0,
                'recordsFiltered' => 0
            ]);
        }

        $ngay_bat_dau = $row['ngay_bat_dau'];
        $ngay_ket_thuc = $row['ngay_ket_thuc'];

        // Lấy thông tin ca làm việc
        $this->db->select('nv.id_ca_lam_viec, ca.ca_lam_viec, ca.check_in, ca.bat_dau_check_in, ca.ket_thuc_check_in, ca.check_out, ca.bat_dau_check_out, ca.ket_thuc_check_out');
        $this->db->from('hrm_nhan_vien as nv');
        $this->db->join('hrm_ca_lam_viec as ca', 'nv.id_ca_lam_viec = ca.id', 'left');
        $this->db->where('nv.ma_cham_cong', $ma_nhan_vien);
        $ca_info = $this->db->get()->row_array();

        // Lấy dữ liệu chấm công
        $this->db->select('*');
        $this->db->from('hrm_api_cham_cong');
        $this->db->where('emp_code', $ma_nhan_vien);

        if ($fromDate && $toDate) {
            $this->db->where('DATE(punch_time) >=', $fromDate);
            $this->db->where('DATE(punch_time) <=', $toDate);
        } else {
            $this->db->where('DATE(punch_time) >=', $ngay_bat_dau);
            $this->db->where('DATE(punch_time) <=', $ngay_ket_thuc);
        }

        $this->db->order_by('punch_time', 'ASC');
        $rows = $this->db->get()->result_array();

        // Gom nhóm theo ngày
        $dailyData = [];
        foreach ($rows as $row) {
            $date = date('Y-m-d', strtotime($row['punch_time']));
            $dailyData[$date][] = $row['punch_time'];
        }

        $result = [];
        foreach ($dailyData as $date => $punches) {
            sort($punches);
            $count = count($punches);

            $gio_vao = $count > 0 ? date('H:i:s', strtotime($punches[0])) : null;
            $gio_ra = $count > 1 ? date('H:i:s', strtotime($punches[$count - 1])) : null;
            $gio_nghi_trua_bat_dau = $count > 2 ? date('H:i:s', strtotime($punches[1])) : null;
            $gio_nghi_trua_ket_thuc = $count > 3 ? date('H:i:s', strtotime($punches[2])) : null;

            if ($count == 1) {
                $gio_ra = null;
                $gio_nghi_trua_bat_dau = null;
                $gio_nghi_trua_ket_thuc = null;
            } elseif ($count == 2) {
                $gio_nghi_trua_bat_dau = null;
                $gio_nghi_trua_ket_thuc = null;
            } elseif ($count == 3) {
                $gio_nghi_trua_ket_thuc = null;
            }

            $weekday = date('N', strtotime($date));
            $thuMap = [1 => 'Thứ hai', 2 => 'Thứ ba', 3 => 'Thứ tư', 4 => 'Thứ năm', 5 => 'Thứ sáu', 6 => 'Thứ bảy', 7 => 'Chủ nhật'];
            $thu = $thuMap[$weekday];

            // Ca làm việc
            $check_in = $ca_info['check_in'] ?? null;
            $bat_dau_check_in = $ca_info['bat_dau_check_in'] ?? null;
            $ket_thuc_check_in = $ca_info['ket_thuc_check_in'] ?? null;
            $check_out = $ca_info['check_out'] ?? null;
            $bat_dau_check_out = $ca_info['bat_dau_check_out'] ?? null;
            $ket_thuc_check_out = $ca_info['ket_thuc_check_out'] ?? null;

            // Tính đi muộn
            $di_muon = 0;
            if ($gio_vao && $bat_dau_check_in && $check_in) {
                $punch_time = strtotime("$date $gio_vao");
                $check_in_time = strtotime("$date $check_in");
                $bat_dau_check_in_time = strtotime("$date $bat_dau_check_in");
                $ket_thuc_check_in_time = strtotime("$date $ket_thuc_check_in");

                if ($punch_time > $check_in_time && $punch_time <= $ket_thuc_check_in_time) {
                    $di_muon = floor(($punch_time - $check_in_time) / 60);
                }
            }

            // Tính về sớm và làm thêm ngày thường
            $ve_som = 0;
            $so_phut_lam_them = 0;
            $punch_time_ra = $gio_ra ? strtotime("$date $gio_ra") : null;

            if ($weekday != 7 && $punch_time_ra && $check_out && $bat_dau_check_out && $ket_thuc_check_out) {
                $check_out_time = strtotime("$date $check_out");
                $bat_dau_check_out_time = strtotime("$date $bat_dau_check_out");
                $ket_thuc_check_out_time = strtotime("$date $ket_thuc_check_out");

                if ($punch_time_ra >= $bat_dau_check_out_time && $punch_time_ra < $check_out_time) {
                    $ve_som = floor(($check_out_time - $punch_time_ra) / 60);
                } elseif ($punch_time_ra >= $check_out_time && $punch_time_ra <= $ket_thuc_check_out_time) {
                    $so_phut_lam_them = floor(($punch_time_ra - $check_out_time) / 60);
                }
            }

            // Tính làm thêm ngày Chủ nhật
            $so_phut_lam_them_cn = 0;
            if ($weekday == 7) {
                $start1 = $gio_vao ? strtotime("$date $gio_vao") : null;
                $end1 = $gio_nghi_trua_bat_dau ? strtotime("$date $gio_nghi_trua_bat_dau") : null;
                $start2 = $gio_nghi_trua_ket_thuc ? strtotime("$date $gio_nghi_trua_ket_thuc") : null;
                $end2 = $gio_ra ? strtotime("$date $gio_ra") : null;

                if ($start1 && $end1 && $end1 > $start1) {
                    $so_phut_lam_them_cn += floor(($end1 - $start1) / 60);
                }

                if ($start2 && $end2 && $end2 > $start2) {
                    $so_phut_lam_them_cn += floor(($end2 - $start2) / 60);
                }

                // Trường hợp chỉ có 2 lần chấm công
                if ($count == 2 && $start1 && $end2 && $end2 > $start1) {
                    $so_phut_lam_them_cn = floor(($end2 - $start1) / 60);
                }
            }

            $result[] = [
                'thu' => $thu,
                'ngay' => date('d/m/Y', strtotime($date)),
                'gio_vao' => $gio_vao,
                'gio_nghi_trua_bat_dau' => $gio_nghi_trua_bat_dau,
                'gio_nghi_trua_ket_thuc' => $gio_nghi_trua_ket_thuc,
                'gio_ra' => $gio_ra,
                'ca_lam_viec' => $ca_info['ca_lam_viec'] ?? null,
                'check_in' => $check_in,
                'bat_dau_check_in' => $bat_dau_check_in,
                'ket_thuc_check_in' => $ket_thuc_check_in,
                'check_out' => $check_out,
                'bat_dau_check_out' => $bat_dau_check_out,
                'ket_thuc_check_out' => $ket_thuc_check_out,
                'di_muon' => $di_muon,
                've_som' => $ve_som,
                'so_phut_lam_them_ngay_thuong' => $so_phut_lam_them,
                'so_phut_lam_them_chu_nhat' => $so_phut_lam_them_cn
            ];
        }

        $recordsTotal = count($result);
        $recordsFiltered = $recordsTotal;

        if ($length != '-1' && $start !== null) {
            $result = array_slice($result, $start, $length);
        }

        return [
            'data' => $result,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered
        ];
    }
}
