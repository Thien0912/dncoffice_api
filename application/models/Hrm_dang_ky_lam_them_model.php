<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_dang_ky_lam_them_model extends MY_Model
{
    protected $table = 'hrm_dang_ky_lam_them';
    protected $primaryKey = 'id_dang_ky_lam_them';
    protected $timestamps = true;
    protected $createdAtField = 'ngay_tao';
    protected $updatedAtField = 'ngay_sua';

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array(), $id_nhan_vien = null)
    {
        $this->db->from('hrm_dang_ky_lam_them')
            ->join('hrm_nhan_vien', 'hrm_nhan_vien.id_nhan_vien = hrm_dang_ky_lam_them.id_nhan_vien', 'left')
            ->where('hrm_dang_ky_lam_them.deleted_at IS NULL');

        if ($id_nhan_vien) {
            $this->db->where('hrm_dang_ky_lam_them.id_nhan_vien', $id_nhan_vien);
        }

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('hrm_dang_ky_lam_them.id_nhan_vien', $searchValue);
            $this->db->or_like('hrm_dang_ky_lam_them.ngay', $searchValue);
            $this->db->or_like('hrm_dang_ky_lam_them.gio_bat_dau', $searchValue);
            $this->db->or_like('hrm_dang_ky_lam_them.gio_ket_thuc', $searchValue);
            $this->db->or_like('hrm_dang_ky_lam_them.loai_ngay', $searchValue);
            $this->db->or_like('hrm_dang_ky_lam_them.trang_thai', $searchValue);
            $this->db->or_like('hrm_dang_ky_lam_them.ghi_chu', $searchValue);
            $this->db->or_like('hrm_nhan_vien.ho_va_ten', $searchValue);
            $this->db->or_like('hrm_nhan_vien.email', $searchValue);
            $this->db->or_like('hrm_nhan_vien.so_dien_thoai', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                $this->db->where('hrm_dang_ky_lam_them.' . $key, $value);
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
                $this->db->order_by('hrm_dang_ky_lam_them.' . $filed, $orderDir);
            }
        }

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        $this->db->select('
            hrm_dang_ky_lam_them.*, 
            hrm_nhan_vien.ho_va_ten, 
            hrm_nhan_vien.gioi_tinh,
            hrm_nhan_vien.ngay_sinh,
            hrm_nhan_vien.so_dien_thoai,
        ');
        $query = $this->db->get();
        $data = $query->result_array();

        foreach ($data as &$dt) {
            $dt['lddv_duyet'] = $this->db
                ->select('*')
                ->from('hrm_nhan_vien')
                ->where('ql_nguoi_dung_id', $dt['lddv_duyet_id'])
                ->get()
                ->row_array();

            if (!$dt['lddv_duyet']) {
                $dt['lddv_duyet'] = $this->db
                    ->select('*')
                    ->from('ql_nguoi_dung')
                    ->where('ql_nguoi_dung_id', $dt['lddv_duyet_id'])
                    ->get()
                    ->row_array();
            } else {
                $dt['lddv_duyet'] = null;
            }

            $dt['tchc_duyet'] = $this->db
                ->select('*')
                ->from('hrm_nhan_vien')
                ->where('ql_nguoi_dung_id', $dt['tchc_duyet_id'])
                ->get()
                ->row_array();

            if (!$dt['tchc_duyet']) {
                $dt['tchc_duyet'] = $this->db
                    ->select('*')
                    ->from('ql_nguoi_dung')
                    ->where('ql_nguoi_dung_id', $dt['tchc_duyet_id'])
                    ->get()
                    ->row_array();
            } else {
                $dt['tchc_duyet'] = null;
            }
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ];
    }

    public function getAllByUserID($start = 0, $length = 10, $searchValue = null, $orderBy = [], $columns = [], $searchKey = array(), $fromDate = null, $toDate = null, $auth)
    {
        $this->db->from('e_van_ban')
            ->select('
                e_van_ban.*, 
                e_tinh_chat.ten_tinh_chat, 
                e_loai.ten_loai, 
                e_khoi_co_quan.ten_khoi_co_quan, 
                e_bao_mat.ten_bao_mat, 
                nguoi_tao.ql_nguoi_dung_ho_ten as ten_nguoi_tao,
                nguoi_tao.ql_nguoi_dung_email as email_nguoi_tao,
                ql_vai_tro_nguoi_dung.*
            ')
            ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
            ->join('e_bao_mat', 'e_bao_mat.id_bao_mat = e_van_ban.id_bao_mat', 'left')
            ->join('e_loai', 'e_loai.id_loai = e_van_ban.id_loai', 'left')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
            ->join('ql_nguoi_dung AS nguoi_tao', 'nguoi_tao.ql_nguoi_dung_id = e_van_ban.id_nguoi_tao', 'left')
            ->join('ql_vai_tro_nguoi_dung', 'nguoi_tao.ql_nguoi_dung_id = ql_vai_tro_nguoi_dung.ql_nguoi_dung_id', 'left')
            ->join('ql_vai_tro', 'ql_vai_tro.ql_vai_tro_id = ql_vai_tro_nguoi_dung.ql_vai_tro_id', 'left')
            ->join('e_don_vi', 'nguoi_tao.id_don_vi = e_don_vi.id_don_vi', 'left');


        $this->db->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DI);
        $this->db->where('e_van_ban.deleted_at IS NULL');
        // $this->db->where('e_don_vi.ma_don_vi', 'PHONG_TCHC');

        $this->db->group_start();
        $this->db->where('ql_vai_tro.ql_ma_vai_tro', 'VAN_THU_TO_CHUC_HANH_CHINH');
        $this->db->or_where('ql_vai_tro.ql_ma_vai_tro', 'LANH_DAO_TCHC');
        $this->db->or_where('ql_vai_tro.ql_ma_vai_tro', 'SUPER_ADMIN');
        $this->db->or_where('nguoi_tao.ql_nguoi_dung_is_admin', 1);
        $this->db->group_end();


        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $escaped = $this->db->escape_like_str($searchValue);
            $this->db->group_start();
            $this->db->like('e_van_ban.ten_van_ban', $escaped);
            $this->db->or_like('e_van_ban.so_van_ban', $escaped);
            $this->db->or_like('e_van_ban.so_hieu_van_ban', $escaped);
            $this->db->or_like('e_van_ban.trich_yeu', $escaped);

            // Trick: dùng or_where với subquery SELECT GROUP_CONCAT(...)
            $this->db->or_where("
                        EXISTS (
                            SELECT 1 
                            FROM e_file_dinh_kem 
                            WHERE e_file_dinh_kem.id_van_ban = e_van_ban.id_van_ban
                            AND noi_dung_trich_xuat LIKE '%{$escaped}%'
                        )
                    ", null, false);
            $this->db->group_end();
        }

        if ($fromDate && $toDate) {
            $this->db->where("ngay_ky BETWEEN '{$fromDate}' AND '{$toDate}'");
        }

        if (!empty($searchKey)) {
            if (!empty($searchKey['ngay_ky_tu']) && !empty($searchKey['ngay_ky_den'])) {
                $from = $searchKey['ngay_ky_tu'];
                $to   = $searchKey['ngay_ky_den'];
                $this->db->where("DATE(e_van_ban.ngay_ky) BETWEEN '{$from}' AND '{$to}'");
            }

            if (!empty($searchKey['ngay_tra_loi_cv_den_tu']) && !empty($searchKey['ngay_tra_loi_cv_den_den'])) {
                $from = $searchKey['ngay_tra_loi_cv_den_tu'];
                $to   = $searchKey['ngay_tra_loi_cv_den_den'];
                $this->db->where("DATE(e_van_ban.ngay_tra_loi_cv_den) BETWEEN '{$from}' AND '{$to}'");
            }

            foreach ($searchKey as $key => $value) {
                if (!empty($key)) {
                    switch ($key) {
                        case 'so_hieu_van_ban':
                            $this->db->like('e_van_ban.so_hieu_van_ban', $value);
                            break;
                        case 'trich_yeu':
                            $this->db->like('e_van_ban.trich_yeu', $value);
                            break;
                        case 'id_loai':
                            $this->db->where('e_van_ban.id_loai', $value);
                            break;
                        case 'selectedClassifyVBDi':
                            switch ($value) {
                                case 'all':
                                    break;
                                case 'luu_tru':
                                    $this->db->where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DI['LUU_TRU']['value']);
                                    break;
                                case 'cho_xu_ly':
                                    $this->db->where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value']);
                                    break;
                                case 'hoan_thanh':
                                    $this->db->where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DI['HOAN_THANH']['value']);
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
                'ten_nguoi_tao'    => 'nguoi_tao.ql_nguoi_dung_ho_ten',
                'ten_khoi_co_quan' => 'e_khoi_co_quan.ten_khoi_co_quan',
                'ten_co_quan'      => 'e_co_quan.ten_co_quan',
                'ten_loai'         => 'e_loai.ten_loai',
                'ten_tinh_chat'    => 'e_tinh_chat.ten_tinh_chat',
                'trang_thai'       => 'e_van_ban.trang_thai', // xử lý riêng
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
        //         $this->db->order_by('e_van_ban.' . $filed, $orderDir);
        //     }
        // }
        if (!empty($orderBy)) {
            $this->handleDatatableOrdering($orderBy, $columns);
        }

        $this->db->order_by('e_van_ban.ngay_nhan', 'DESC');
        $this->db->order_by('e_van_ban.so_van_ban', 'DESC');

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }


        $query = $this->db->get();
        $data = $query->result_array();
        $sql = $this->db->last_query();

        foreach ($data as &$dt) {
            $files = $this->db
                ->where('id_van_ban', $dt['id_van_ban'])
                ->where('la_file_ban_hanh', 1)
                ->get('e_file_dinh_kem')
                ->result_array();
            foreach ($files as $key => &$file) {
                $file['duong_dan'] = encryptString($file['duong_dan']);
            }
            $dt['files'] = $files;

            $files_noi_bo = $this->db
                ->where('id_van_ban', $dt['id_van_ban'])
                ->where('la_file_ban_hanh', 0)
                ->get('e_file_dinh_kem')
                ->result_array();
            foreach ($files_noi_bo as $key => &$item) {
                $item['duong_dan'] = encryptString($item['duong_dan']);
            }
            $dt['files_noi_bo'] = $files_noi_bo;

            // $dt['files'] = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_file_dinh_kem')->result_array();

            $dt['e_vb_khoi_co_quan'] = $this->db
                ->select('e_khoi_co_quan.ten_khoi_co_quan')
                ->from('e_vb_khoi_co_quan')
                ->join('e_khoi_co_quan', 'e_vb_khoi_co_quan.id_khoi_co_quan = e_khoi_co_quan.id_khoi_co_quan')
                ->where('id_van_ban', $dt['id_van_ban'])
                ->get()
                ->result_array();

            $dt['e_vb_co_quan'] = $this->db
                ->select('e_co_quan.ten_co_quan')
                ->from('e_vb_co_quan')
                ->join('e_co_quan', 'e_vb_co_quan.id_co_quan = e_co_quan.id_co_quan')
                ->where('id_van_ban', $dt['id_van_ban'])
                ->get()
                ->result_array();

            // $dt['e_ban_hanh'] = $this->db
            //     ->select('e_don_vi.ten_don_vi as don_vi_ban_hanh')
            //     ->from('e_ban_hanh')
            //     ->join('e_don_vi', 'e_ban_hanh.id_don_vi = e_don_vi.id_don_vi')
            //     ->where('id_van_ban', $dt['id_van_ban'])
            //     ->get()
            //     ->result_array();

            $dt['e_don_vi_xu_ly'] = $this->db
                ->select('e_don_vi.ten_don_vi as don_vi_xu_ly')
                ->from('e_xu_ly')
                ->join('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly')
                ->join('e_don_vi', 'e_don_vi_xu_ly.id_don_vi = e_don_vi.id_don_vi')
                ->where('e_xu_ly.id_van_ban', $dt['id_van_ban'])
                ->get()
                ->result_array();

            //Xem danh sách đơn vị xử lý và phối hợp
            // $xuly = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_xu_ly')->row_array();
            // $donvixuly = [];
            // if ($xuly) {
            //     $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
            //     $donvixuly = $this->db
            //         ->join('e_don_vi', 'e_don_vi_xu_ly.id_don_vi = e_don_vi.id_don_vi')
            //         ->where('e_don_vi_xu_ly.id_xu_ly', $xuly['id_xu_ly'])
            //         ->select('e_don_vi_xu_ly.id_don_vi_xu_ly, e_don_vi_xu_ly.don_vi_xu_ly_chinh, e_don_vi_xu_ly.da_xem, e_don_vi.id_don_vi, e_don_vi.ten_don_vi, e_don_vi.ma_don_vi')
            //         ->group_by('e_don_vi_xu_ly.id_don_vi_xu_ly')
            //         ->get('e_don_vi_xu_ly')
            //         ->result_array();

            //     $donvixulyIds = array_unique(array_column($donvixuly, 'id_don_vi_xu_ly'));
            //     if (!empty($donvixulyIds)) {
            //         $donvixulydaxem = $this->db
            //             ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_don_vi_xu_ly_da_xem.ql_nguoi_dung_id')
            //             ->where_in('e_don_vi_xu_ly_da_xem.id_don_vi_xu_ly', $donvixulyIds)
            //             ->select('e_don_vi_xu_ly_da_xem.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten, ql_nguoi_dung.ql_nguoi_dung_email, ql_nguoi_dung.ql_nguoi_dung_avatar')
            //             ->get('e_don_vi_xu_ly_da_xem')
            //             ->result_array();
            //         foreach ($donvixuly as &$dvxl) {
            //             $dvxl['danh_sach_da_xem'] = array_filter($donvixulydaxem, function ($item) use ($dvxl) {
            //                 return $item['id_don_vi_xu_ly'] == $dvxl['id_don_vi_xu_ly'];
            //             });
            //         }
            //     }
            // }
            // $dt['don_vi_xu_ly'] = $donvixuly;
            $xu_ly = $this->getXuly_byidVanbandi($dt['id_van_ban']);
            $donvi_xuly = array_merge(
                $xu_ly['don_vi_xu_ly_chinh'] ?? [],
                $xu_ly['don_vi_xu_ly_phoi_hop'] ?? []
            );
            $dt['don_vi_xu_ly'] = $donvi_xuly;

            $donvidaphanhoi = $this->db
                ->select('*')
                ->from('e_bao_cao')
                ->join('e_bao_cao_da_xem', 'e_bao_cao.id_bao_cao = e_bao_cao_da_xem.id_bao_cao', 'left')
                ->join('e_don_vi', 'e_bao_cao.id_don_vi_phan_hoi = e_don_vi.id_don_vi')
                ->where('e_bao_cao.id_van_ban', $dt['id_van_ban'])
                ->get()
                ->result_array();

            $dt['don_vi_da_phan_hoi'] = $donvidaphanhoi;

            $phanhoichuaxem = $this->db
                ->select('*')
                ->from('e_bao_cao')
                ->join('e_bao_cao_da_xem', 'e_bao_cao.id_bao_cao = e_bao_cao_da_xem.id_bao_cao', 'left')
                ->join('e_don_vi', 'e_bao_cao.id_don_vi_phan_hoi = e_don_vi.id_don_vi')
                ->where('e_bao_cao.id_van_ban', $dt['id_van_ban'])
                ->where('e_bao_cao_da_xem.nguoi_xem IS NULL')
                ->get()
                ->result_array();

            $dt['phan_hoi_chua_xem'] = $phanhoichuaxem;

            // Lấy ra tag -> văn bản thuộc tag nào
            $dt['tags_in_vb'] = [];
            $tags = $this->db->from('e_tag')
                ->join('e_tag_van_ban', 'e_tag.id_tag = e_tag_van_ban.id_tag', 'left')
                ->where('e_tag_van_ban.id_van_ban', $dt['id_van_ban'])
                ->like('e_tag.ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])
                ->get()->result_array();
            if ($tags) {
                $id_tags = array_unique(array_column($tags, 'id_tag'));
                $dt['tags_in_vb'] = $this->db->select('*')->from('e_tag')->where_in('id_tag', $id_tags)->get()->result_array();
            }


            // Xử lý bên copy tin nhắn Zalo
            $dsHinhThuc = $this->db
                ->select('*')
                ->from('e_vb_hinh_thuc')
                ->where('id_van_ban', $dt['id_van_ban'])
                ->get()
                ->result();

            $textHinhThuc = '';
            $ids_hinh_thuc_array = array_column($dsHinhThuc, 'id_hinh_thuc');
            foreach ($ids_hinh_thuc_array as $id_hinh_thuc) {
                $hinhthuc = $this->E_hinh_thuc_model->find($id_hinh_thuc);
                if ($hinhthuc && ($hinhthuc['ma_hinh_thuc'] == 'EMAIL')) {
                    $textHinhThuc = '/' . $hinhthuc['ten_hinh_thuc'];
                }
            }

            $thongBao = $this->db
                ->select('*')
                ->from('ql_thong_bao')
                ->like('ql_thong_bao_link', 'xemchitiet/' . $dt['id_van_ban'], 'before')
                ->order_by('ql_thong_bao_id', 'DESC')
                ->get()
                ->row_array();

            if ($thongBao) {
                $textZalo = "
                    Kính chào Quý Thầy/Cô!
                    Phòng Tổ chức - Hành chính ban hành văn bản trên MyOffice" . $textHinhThuc . ": "
                    . $thongBao['ql_thong_bao_noi_dung'] .
                    "Kính nhờ các đơn vị kiểm tra MyOffice" . $textHinhThuc . ", nếu đơn vị nào chưa nhận được vui lòng phản hồi lại giúp em ạ.
                    Cảm ơn các đơn vị đã phối hợp!
                ";

                $dt['noi_dung_tin_nhan_zalo'] = $textZalo;
            }
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'sql' => $sql,
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
            $this->db->like('hrm_dang_ky_lam_them.id_nhan_vien', $searchValue);
            $this->db->or_like('hrm_dang_ky_lam_them.ngay', $searchValue);
            $this->db->or_like('hrm_dang_ky_lam_them.gio_bat_dau', $searchValue);
            $this->db->or_like('hrm_dang_ky_lam_them.gio_ket_thuc', $searchValue);
            $this->db->or_like('hrm_dang_ky_lam_them.loai_ngay', $searchValue);
            $this->db->or_like('hrm_dang_ky_lam_them.trang_thai', $searchValue);
            $this->db->or_like('hrm_dang_ky_lam_them.ghi_chu', $searchValue);
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
            hrm_nhan_vien.ho_ten, 
            hrm_nhan_vien.gioi_tinh,
            hrm_nhan_vien.ngay_sinh,
            hrm_nhan_vien.so_dien_thoai,
        ');
        $query = $this->db->get();
        $data = $query->result_array();


        foreach ($data as &$dt) {
            $dt['file_hop_dong_duong_dan'] = encryptString($dt['file_hop_dong_duong_dan']);
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ];
    }

    public function getListExport($start = 0, $length = 10, $searchValue = null, $searchKey = array(), $fromDate = null, $toDate = null, $id_nhan_vien)
    {
        $this->db->from('hrm_dang_ky_lam_them')
            ->join('hrm_nhan_vien', 'hrm_nhan_vien.id_nhan_vien = hrm_dang_ky_lam_them.id_nhan_vien', 'left');

        if ($id_nhan_vien) {
            $this->db->where('hrm_dang_ky_lam_them.id_nhan_vien', $id_nhan_vien);
        }

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('hrm_dang_ky_lam_them.id_nhan_vien', $searchValue);
            $this->db->or_like('hrm_dang_ky_lam_them.ngay', $searchValue);
            $this->db->or_like('hrm_dang_ky_lam_them.gio_bat_dau', $searchValue);
            $this->db->or_like('hrm_dang_ky_lam_them.gio_ket_thuc', $searchValue);
            $this->db->or_like('hrm_dang_ky_lam_them.loai_ngay', $searchValue);
            $this->db->or_like('hrm_dang_ky_lam_them.trang_thai', $searchValue);
            $this->db->or_like('hrm_dang_ky_lam_them.ghi_chu', $searchValue);
            $this->db->or_like('hrm_nhan_vien.ho_va_ten', $searchValue);
            $this->db->or_like('hrm_nhan_vien.email', $searchValue);
            $this->db->or_like('hrm_nhan_vien.so_dien_thoai', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                $this->db->where('hrm_dang_ky_lam_them.' . $key, $value);
            }
        }

        // if ($fromDate && $toDate) {
        //     $this->db->where("ngay BETWEEN '{$fromDate}' AND '{$toDate}'");
        // }

        // if ($length != '-1') {
        //     $this->db->limit($length, $start);
        // }
        $this->db->select('
            hrm_dang_ky_lam_them.*, 
            hrm_nhan_vien.ma_nhan_vien, 
            hrm_nhan_vien.ho_va_ten, 
            hrm_nhan_vien.gioi_tinh,
            hrm_nhan_vien.ngay_sinh,
            hrm_nhan_vien.so_dien_thoai,
        ');

        $query = $this->db->get();
        $data = $query->result_array();

        return $data;
    }
}
