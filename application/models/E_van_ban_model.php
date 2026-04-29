<?php


if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * @property Common $common
 */

class E_van_ban_model extends MY_Model
{
    protected $table = 'e_van_ban';
    protected $primaryKey = 'id_van_ban';
    protected $timestamps = true;
    protected $createdAtField = 'ngay_tao';
    protected $updatedAtField = 'ngay_sua';

    public function __construct()
    {
        parent::__construct();
        $this->load->library(['Validator', 'Fileupload', 'Common']);
    }

    // Danh sách văn bản đến của TCHC
    public function getAllVanbanden($start = 0, $length = 10, $searchValue = null, $orderBy = [], $columns = [], $searchKey = array(), $fromDate = null, $toDate = null, $auth, $dataSource = [])
    {
        $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
        $tiepnhan = 0;
        $chobutphe = 0;
        $now = date('Y-m-d');
        $fiveDaysLater = date('Y-m-d', strtotime('5 days'));
        $this->db->from('e_van_ban')
            ->select('
                    e_van_ban.id_van_ban,
                    e_van_ban.nam,
                    e_van_ban.so_van_ban,
                    e_van_ban.so_van_ban_hau_to,
                    e_van_ban.so_hieu_van_ban,
                    e_van_ban.loai_van_ban,
                    e_loai.id_loai,
                    e_loai.ten_loai,
                    e_van_ban.trich_yeu,
                    e_van_ban.ngay_nhan,
                    e_van_ban.ngay_ban_hanh,
                    DATE_FORMAT(e_van_ban.thoi_gian_xu_ly, "%Y-%m-%d")  AS thoi_gian_xu_ly,
                    e_van_ban.trang_thai,
                    e_khoi_co_quan.id_khoi_co_quan,
                    e_khoi_co_quan.ten_khoi_co_quan,
                    e_co_quan.id_co_quan,
                    e_co_quan.ten_co_quan,
                    e_hinh_thuc.id_hinh_thuc,
                    e_hinh_thuc.ten_hinh_thuc,
                    e_van_ban.linh_vuc,
                    e_tinh_chat.id_tinh_chat,
                    e_tinh_chat.ten_tinh_chat,
                    e_bao_mat.id_bao_mat,
                    e_bao_mat.ten_bao_mat,
                    e_don_vi.id_don_vi,
                    e_don_vi.ten_don_vi ho_so_don_vi,
                    e_van_ban.noi_luu_tru,
                    e_van_ban.trang_thai_huy_vb,
                    e_van_ban.luu_tru_noi_bo,
                    e_van_ban.nguoi_ky,
                    e_van_ban.ngay_ky,
                    e_van_ban.van_ban_chi_doc,
                    e_van_ban.ngay_tao,
                    e_van_ban.ngay_sua,
                    e_van_ban.deleted_at,
                    nguoi_butphe.ql_nguoi_dung_id,
                    nguoi_butphe.ql_nguoi_dung_ho_ten AS nguoi_but_phe,
                    e_but_phe.ngay_but_phe,
                    e_but_phe.noi_dung_but_phe,
                    e_van_ban.id_nguoi_tao,
                    nguoi_tao.ql_nguoi_dung_ho_ten AS ten_nguoi_tao,
                    nguoi_tao.ql_nguoi_dung_email AS email_nguoi_tao
            ')
            ->join('e_loai', 'e_loai.id_loai = e_van_ban.id_loai', 'left')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
            ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
            ->join('e_bao_mat', 'e_bao_mat.id_bao_mat = e_van_ban.id_bao_mat', 'left')
            ->join('e_but_phe', 'e_but_phe.id_van_ban = e_van_ban.id_van_ban', 'left')
            ->join('ql_nguoi_dung nguoi_butphe', 'nguoi_butphe.ql_nguoi_dung_id = e_but_phe.id_nguoi_but_phe', 'left')
            ->join('ql_nguoi_dung nguoi_tao', 'nguoi_tao.ql_nguoi_dung_id = e_van_ban.id_nguoi_tao', 'left')
            ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'left')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = e_van_ban.id_don_vi', 'left')
            ->join('e_hinh_thuc', 'e_hinh_thuc.id_hinh_thuc = e_van_ban.id_hinh_thuc', 'left');

        $this->db->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DEN);
        $this->db->group_by('e_van_ban.id_van_ban');


        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue && !empty($searchValue)) {
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

        if (!empty($searchKey)) {
            if (!empty($searchKey['year']) && $searchKey['year'] !== 'all_years') {
                $this->db->where("e_van_ban.nam", $searchKey['year']);
            }

            if (!empty($searchKey['ngay_tao_tu']) && !empty($searchKey['ngay_tao_den'])) {
                $from = $searchKey['ngay_tao_tu'];
                $to   = $searchKey['ngay_tao_den'];
                $this->db->where("DATE(e_van_ban.ngay_tao) BETWEEN '{$from}' AND '{$to}'");
            }

            if (!empty($searchKey['ngay_ban_hanh_tu']) && !empty($searchKey['ngay_ban_hanh_den'])) {
                $from = $searchKey['ngay_ban_hanh_tu'];
                $to   = $searchKey['ngay_ban_hanh_den'];
                $this->db->where("DATE(e_van_ban.ngay_ban_hanh) BETWEEN '{$from}' AND '{$to}'");
            }

            if (!empty($searchKey['ngay_nhan_tu']) && !empty($searchKey['ngay_nhan_den'])) {
                $from = $searchKey['ngay_nhan_tu'];
                $to   = $searchKey['ngay_nhan_den'];
                $this->db->where("DATE(e_van_ban.ngay_nhan) BETWEEN '{$from}' AND '{$to}'");
            }

            if (!empty($searchKey['thoi_gian_xu_ly_tu']) && !empty($searchKey['thoi_gian_xu_ly_den'])) {
                $from = $searchKey['thoi_gian_xu_ly_tu'];
                $to   = $searchKey['thoi_gian_xu_ly_den'];
                $this->db->where("DATE(e_van_ban.thoi_gian_xu_ly) BETWEEN '{$from}' AND '{$to}'");
            }

            foreach ($searchKey as $key => $value) {
                if (!empty($value)) {
                    switch ($key) {
                        case 'so_van_ban':
                            $this->db->like('e_van_ban.so_van_ban', $value);
                            break;
                        case 'so_hieu_van_ban':
                            $this->db->like('e_van_ban.so_hieu_van_ban', $value);
                            break;
                        case 'trich_yeu':
                            $this->db->like('e_van_ban.trich_yeu', $value);
                            break;
                        case 'loai_van_ban':
                            $this->db->like('e_loai.id_loai', $value);
                            break;
                        case 'ngay_nhan':
                            $this->db->where('DATE(e_van_ban.ngay_nhan)', $value);
                            break;
                        case 'thoi_gian_xu_ly':
                            $this->db->where('DATE(e_van_ban.thoi_gian_xu_ly)', $value);
                            break;
                        case 'id_don_vi_xu_ly':
                            $this->db->join('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly', 'left');
                            $this->db->where('e_don_vi_xu_ly.id_don_vi', $value);
                            $this->db->group_by('e_van_ban.id_van_ban');
                            break;
                        case 'year':
                            // Đã xử lý ở đầu khối searchKey
                            break;
                        case 'selectedClassify':
                            $excludeTrash = "NOT EXISTS (SELECT 1 FROM e_van_ban_da_xoa WHERE e_van_ban_da_xoa.id_van_ban = e_van_ban.id_van_ban AND e_van_ban_da_xoa.id_don_vi = " . $this->db->escape($auth['id_don_vi']) . ")";
                            switch ($value) {
                                case 'all':
                                    $this->db->where('e_van_ban.deleted_at IS NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'tiep_nhan':
                                    $this->db->where('e_van_ban.trang_thai =', Common::STATUS_VAN_BAN_DEN['TIEP_NHAN']['value']);
                                    $this->db->where('e_van_ban.deleted_at IS NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'luu_tru':
                                    $this->db->where('e_van_ban.trang_thai !=', Common::STATUS_VAN_BAN_DEN['HOAN_THANH']['value']);
                                    $this->db->where('e_van_ban.trang_thai =', Common::STATUS_VAN_BAN_DEN['LUU_TRU']['value']);
                                    $this->db->where('e_van_ban.deleted_at IS NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'cho_but_phe':
                                    $this->db->where('e_van_ban.trang_thai !=', Common::STATUS_VAN_BAN_DEN['HOAN_THANH']['value']);
                                    $this->db->where('e_van_ban.trang_thai =', Common::STATUS_VAN_BAN_DEN['CHO_LANH_DAO_BUT_PHE']['value']);
                                    $this->db->where('e_van_ban.deleted_at IS NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'da_but_phe':
                                    $this->db->where('e_van_ban.trang_thai !=', Common::STATUS_VAN_BAN_DEN['HOAN_THANH']['value']);
                                    $this->db->where('e_van_ban.trang_thai =', Common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value']);
                                    $this->db->where('e_van_ban.deleted_at IS NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'da_chuyen_don_vi_xu_ly':
                                    $this->db->where('e_van_ban.trang_thai !=', Common::STATUS_VAN_BAN_DEN['HOAN_THANH']['value']);
                                    $this->db->where('e_van_ban.trang_thai =', Common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value']);
                                    $this->db->where('e_van_ban.deleted_at IS NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'hoan_thanh':
                                    $this->db->where('e_van_ban.trang_thai =', Common::STATUS_VAN_BAN_DEN['HOAN_THANH']['value']);
                                    $this->db->where('e_van_ban.deleted_at IS NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'tren_5_ngay':
                                    $this->db->where('e_van_ban.trang_thai !=', Common::STATUS_VAN_BAN_DEN['HOAN_THANH']['value']);
                                    $this->db->where("e_van_ban.thoi_gian_xu_ly > ", $fiveDaysLater)->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL');
                                    $this->db->where('e_van_ban.deleted_at IS NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'duoi_5_ngay':
                                    $this->db->where('e_van_ban.trang_thai !=', Common::STATUS_VAN_BAN_DEN['HOAN_THANH']['value']);
                                    $this->db->where("e_van_ban.thoi_gian_xu_ly <= ", $fiveDaysLater)->where("e_van_ban.thoi_gian_xu_ly > ", $now)
                                        ->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL');
                                    $this->db->where('e_van_ban.deleted_at IS NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'hom_nay':
                                    $this->db->where('e_van_ban.trang_thai !=', Common::STATUS_VAN_BAN_DEN['HOAN_THANH']['value']);
                                    $this->db->where("DATE(e_van_ban.thoi_gian_xu_ly)", $now)->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL');
                                    $this->db->where('e_van_ban.deleted_at IS NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'qua_han':
                                    $this->db->where('e_van_ban.trang_thai !=', Common::STATUS_VAN_BAN_DEN['HOAN_THANH']['value']);
                                    $this->db->where("e_van_ban.thoi_gian_xu_ly < ", $now)->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL');
                                    $this->db->where('e_van_ban.deleted_at IS NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'thu_hoi':
                                    $this->db->where('e_van_ban.deleted_at IS NOT NULL', null, false);
                                    $this->db->where($excludeTrash, null, false);
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

        if ($fromDate && $toDate) {
            $this->db->where("e_van_ban.ngay_tao BETWEEN '{$fromDate}' AND '{$toDate}'");
        }
        // if ($fromDate && $toDate) {
        //     $this->db->group_start();
        //     $this->db->where("ngay_nhan BETWEEN '{$fromDate} 00:00:00' AND '{$toDate} 23:59:59'");
        //     $this->db->or_where('ngay_nhan IS NULL');
        //     $this->db->group_end();
        // }

        if (!empty($columns)) {
            // Map tên cột hiển thị (alias) sang cột thật trong DB
            $columnMapping = [
                'ten_nguoi_tao'    => 'nguoi_tao.ql_nguoi_dung_ho_ten',
                'ten_khoi_co_quan' => 'e_khoi_co_quan.ten_khoi_co_quan',
                'ten_co_quan'      => 'e_co_quan.ten_co_quan',
                'ten_loai'         => 'e_loai.ten_loai',
                'ten_tinh_chat'    => 'e_tinh_chat.ten_tinh_chat',
                'nguoi_but_phe'    => 'ql_nguoi_dung.ql_nguoi_dung_ho_ten',
                'trang_thai'       => 'e_van_ban.trang_thai', // xử lý riêng
                // Mặc định nếu không map thì nó sẽ lấy e_van_ban.[columnName]
            ];
            $this->handleDatatableColumns($columns, $columnMapping);
        }


        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query

        // Xử lý order sau khi xử lý search
        // Xử lý order ưu tiên sortOrder từ popup filter
        if (!empty($searchKey['sortOrder'])) {
            if ($searchKey['sortOrder'] === 'newest') {
                $this->db->order_by('e_van_ban.id_van_ban', 'DESC');
            } else if ($searchKey['sortOrder'] === 'oldest') {
                $this->db->order_by('e_van_ban.id_van_ban', 'ASC');
            }
        } else if (!empty($orderBy)) {
            $this->handleDatatableOrdering($orderBy, $columns);
        } else if (!empty($dataSource['order'])) {
            $this->applyOrdering($dataSource['order']);
        } else {
            // Default order
            $this->db->order_by('e_van_ban.id_van_ban', 'DESC');
        }


        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        $query = $this->db->get();
        $data = $query->result_array();
        $sql = $this->db->last_query();


        foreach ($data as $key => &$d) {
            $files = $this->db->where('id_van_ban', $d['id_van_ban'])->get('e_file_dinh_kem')->result_array();
            foreach ($files as $key => &$f) {
                $f['duong_dan'] = encryptString($f['duong_dan']);
            }
            $d['files'] = $files;

            $xuly = $this->db->where('id_van_ban', $d['id_van_ban'])->get('e_xu_ly')->row_array();
            if ($xuly) {
                $donvixuly = $this->db
                    ->join('e_don_vi', 'e_don_vi.id_don_vi = e_don_vi_xu_ly.id_don_vi', 'left')
                    ->where('id_xu_ly', $xuly['id_xu_ly'])
                    ->get('e_don_vi_xu_ly')
                    ->result_array();
                $xuly['don_vi_xu_ly_chinh'] = array_values(array_filter($donvixuly, fn($dv) => $dv['don_vi_xu_ly_chinh'] == 1));
                $xuly['don_vi_xu_ly_phoi_hop'] = array_values(array_filter($donvixuly, fn($dv) => is_null($dv['don_vi_xu_ly_chinh']) || $dv['don_vi_xu_ly_chinh'] != 1));

                $dv_chinh_ids = []; // Theo đơn vị
                $dv_chinh_ds = [];
                foreach ($xuly['don_vi_xu_ly_chinh'] as $key => &$dvxlpp) {
                    if (!in_array($dvxlpp['id_don_vi'], $dv_chinh_ids)) {
                        $dv_chinh_ids[] = $dvxlpp['id_don_vi'];
                        $dv_chinh_ds[] = [
                            'id_xu_ly' => $dvxlpp['id_xu_ly'],
                            'id_don_vi' => $dvxlpp['id_don_vi'],
                            'ten_don_vi' => $dvxlpp['ten_don_vi'],
                            'ma_don_vi' => $dvxlpp['ma_don_vi'],
                            'don_vi_xu_ly_chinh' => $dvxlpp['don_vi_xu_ly_chinh'],
                            'loai' => $dvxlpp['loai'],
                            'email' => $dvxlpp['email'],
                            'ngay_tao' => $dvxlpp['ngay_tao'],
                            'da_xem' => $dvxlpp['da_xem'],
                            'nguoi_xu_ly_ids' => [$dvxlpp['id_nguoi_xu_ly']],
                            'don_vi_xu_ly_ids' => [$dvxlpp['id_don_vi_xu_ly']],
                        ];
                    } else if (in_array($dvxlpp['id_don_vi'], $dv_chinh_ids)) {
                        foreach ($dv_chinh_ds as &$dv) {
                            if ($dv['id_don_vi'] == $dvxlpp['id_don_vi']) {
                                $dv['nguoi_xu_ly_ids'][] = $dvxlpp['id_nguoi_xu_ly'];
                                $dv['don_vi_xu_ly_ids'][] = $dvxlpp['id_don_vi_xu_ly'];

                                if ($dvxlpp['da_xem']) {
                                    $dv['da_xem'] = true;
                                }
                                break;
                            }
                        }
                    }
                }

                foreach ($dv_chinh_ds as $key => &$dv_chinh) {
                    $dv_chinh['nguoi_xem'] = $this->db
                        ->select('e_don_vi_xu_ly_da_xem.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten, ql_nguoi_dung.ql_nguoi_dung_email')
                        ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_don_vi_xu_ly_da_xem.ql_nguoi_dung_id', 'left')
                        ->where_in('id_don_vi_xu_ly', $dv_chinh['don_vi_xu_ly_ids'])
                        ->get('e_don_vi_xu_ly_da_xem')
                        ->result_array();
                    $dv_chinh['so_nguoi_xem'] = count($dv_chinh['nguoi_xem']);
                }

                $xuly['don_vi_xu_ly_chinh'] = $dv_chinh_ds;

                // foreach ($xuly['don_vi_xu_ly_chinh'] as $key => &$dvxlc) {
                //     $dvxlc['nguoi_xem'] = $this->db
                //         ->select('
                //                 e_don_vi_xu_ly_da_xem.*, 
                //                 ql_nguoi_dung.ql_nguoi_dung_ho_ten, 
                //                 ql_nguoi_dung.ql_nguoi_dung_email
                //         ')
                //         ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_don_vi_xu_ly_da_xem.ql_nguoi_dung_id', 'left')
                //         ->where('id_don_vi_xu_ly', $dvxlc['id_don_vi_xu_ly'])
                //         ->get('e_don_vi_xu_ly_da_xem')
                //         ->result_array();
                //     $dvxlc['so_nguoi_xem'] = count($dvxlc['nguoi_xem']);
                // }

                $dv_phoi_hop_ids = []; // Theo đơn vị
                $dv_phoi_dop_ds = [];
                foreach ($xuly['don_vi_xu_ly_phoi_hop'] as $key => &$dvxlpp) {
                    if (!in_array($dvxlpp['id_don_vi'], $dv_phoi_hop_ids)) {
                        $dv_phoi_hop_ids[] = $dvxlpp['id_don_vi'];
                        $dv_phoi_dop_ds[] = [
                            'id_xu_ly' => $dvxlpp['id_xu_ly'],
                            'id_don_vi' => $dvxlpp['id_don_vi'],
                            'ten_don_vi' => $dvxlpp['ten_don_vi'],
                            'ma_don_vi' => $dvxlpp['ma_don_vi'],
                            'don_vi_xu_ly_chinh' => $dvxlpp['don_vi_xu_ly_chinh'],
                            'loai' => $dvxlpp['loai'],
                            'email' => $dvxlpp['email'],
                            'ngay_tao' => $dvxlpp['ngay_tao'],
                            'da_xem' => $dvxlpp['da_xem'],
                            'nguoi_xu_ly_ids' => [$dvxlpp['id_nguoi_xu_ly']],
                            'don_vi_xu_ly_ids' => [$dvxlpp['id_don_vi_xu_ly']],
                        ];
                    } else if (in_array($dvxlpp['id_don_vi'], $dv_phoi_hop_ids)) {
                        foreach ($dv_phoi_dop_ds as &$dv) {
                            if ($dv['id_don_vi'] == $dvxlpp['id_don_vi']) {
                                $dv['nguoi_xu_ly_ids'][] = $dvxlpp['id_nguoi_xu_ly'];
                                $dv['don_vi_xu_ly_ids'][] = $dvxlpp['id_don_vi_xu_ly'];

                                if ($dvxlpp['da_xem']) {
                                    $dv['da_xem'] = true;
                                }
                                break;
                            }
                        }
                    }
                }

                foreach ($dv_phoi_dop_ds as $key => &$dv_phoi_dop) {
                    $dv_phoi_dop['nguoi_xem'] = $this->db
                        ->select('e_don_vi_xu_ly_da_xem.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten, ql_nguoi_dung.ql_nguoi_dung_email')
                        ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_don_vi_xu_ly_da_xem.ql_nguoi_dung_id', 'left')
                        ->where_in('id_don_vi_xu_ly', $dv_phoi_dop['don_vi_xu_ly_ids'])
                        ->get('e_don_vi_xu_ly_da_xem')
                        ->result_array();
                    $dv_phoi_dop['so_nguoi_xem'] = count($dv_phoi_dop['nguoi_xem']);
                }

                $xuly['don_vi_xu_ly_phoi_hop'] = $dv_phoi_dop_ds;
                // foreach ($xuly['don_vi_xu_ly_phoi_hop'] as $key => &$dvxlc) {
                //     $dvxlc['nguoi_xem'] = $this->db
                //         ->select('
                //                 e_don_vi_xu_ly_da_xem.*, 
                //                 ql_nguoi_dung.ql_nguoi_dung_ho_ten, 
                //                 ql_nguoi_dung.ql_nguoi_dung_email
                //         ')
                //         ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_don_vi_xu_ly_da_xem.ql_nguoi_dung_id', 'left')
                //         ->where('id_don_vi_xu_ly', $dvxlc['id_don_vi_xu_ly'])
                //         ->get('e_don_vi_xu_ly_da_xem')
                //         ->result_array();
                //     $dvxlc['so_nguoi_xem'] = count($dvxlc['nguoi_xem']);
                // }
            }
            $d['xu_ly'] = $xuly;

            $phanhoivanban = $this->db->where('id_van_ban', $d['id_van_ban'])->get('e_bao_cao')->result_array();
            $d['phan_hoi'] = $phanhoivanban ?? [];
            //Lấy ra đơn vị đã xem văn bản được ban hành
            // $d['don_vi_xu_ly_da_xem'] = $donvixulydaxem;

            //  Lấy ra tag -> văn bản thuộc tag nào
            $tags = $this->db
                ->join('e_tag_van_ban', 'e_tag.id_tag = e_tag_van_ban.id_tag', 'left')
                ->where('e_tag_van_ban.id_van_ban', $d['id_van_ban'])
                ->like('e_tag.ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])
                ->get('e_tag')->result_array();
            if ($tags) {
                $id_tags = array_unique(array_column($tags, 'id_tag'));
                $d['tags_in_vb'] = $this->db->select('*')->from('e_tag')->where_in('id_tag', $id_tags)->get()->result_array();
            } else {
                $d['tags_in_vb'] = [];
            }
        }

        $excludeTrash = "NOT EXISTS (SELECT 1 FROM e_van_ban_da_xoa WHERE e_van_ban_da_xoa.id_van_ban = e_van_ban.id_van_ban AND e_van_ban_da_xoa.id_don_vi = " . $this->db->escape($auth['id_don_vi']) . ")";

        $trang_thai_hoan_thanh = [Common::STATUS_VAN_BAN_DEN['HOAN_THANH']['value']];
        $base_thoi_han = clone $totalRecordsQuery;
        if (!empty($searchKey['year']) && $searchKey['year'] !== 'all_years') {
            $base_thoi_han->where("YEAR(e_van_ban.ngay_nhan)", $searchKey['year']);
        }

        // Văn bản tiếp nhận
        $tiepnhan = (clone $base_thoi_han)->where('trang_thai', Common::STATUS_VAN_BAN_DEN['TIEP_NHAN']['value'])->where('loai_van_ban', $this->common::VAN_BAN_DEN)->where('deleted_at IS NULL')->where($excludeTrash, null, false)->count_all_results('', false);

        // Văn bản chờ bút phê
        $chobutphe = (clone $base_thoi_han)->where('trang_thai', Common::STATUS_VAN_BAN_DEN['CHO_LANH_DAO_BUT_PHE']['value'])->where('loai_van_ban', $this->common::VAN_BAN_DEN)->where('deleted_at IS NULL')->where($excludeTrash, null, false)->count_all_results('', false);

        // Văn bản trên 5 ngày
        $tren5ngay = (clone $base_thoi_han)->where("thoi_gian_xu_ly > ", $fiveDaysLater)->where_not_in('trang_thai', $trang_thai_hoan_thanh)->where('thoi_gian_xu_ly IS NOT NULL')->where('loai_van_ban', $this->common::VAN_BAN_DEN)->where('deleted_at IS NULL')->where($excludeTrash, null, false)->count_all_results('', false);

        // Văn bản trong vòng 5 ngày
        $duoi5ngay = (clone $base_thoi_han)->where("thoi_gian_xu_ly <= ", $fiveDaysLater)->where_not_in('trang_thai', $trang_thai_hoan_thanh)->where("thoi_gian_xu_ly > ", $now)->where('thoi_gian_xu_ly IS NOT NULL')->where('loai_van_ban', $this->common::VAN_BAN_DEN)->where('deleted_at IS NULL')->where($excludeTrash, null, false)->count_all_results('', false);

        // Văn bản của ngày hôm nay
        $homnay = (clone $base_thoi_han)->where("DATE(thoi_gian_xu_ly)", $now)->where_not_in('trang_thai', $trang_thai_hoan_thanh)->where('thoi_gian_xu_ly IS NOT NULL')->where('loai_van_ban', $this->common::VAN_BAN_DEN)->where('deleted_at IS NULL')->where($excludeTrash, null, false)->count_all_results('', false);

        // Văn bản quá hạn
        $quahan = (clone $base_thoi_han)->where("thoi_gian_xu_ly < ", $now)->where_not_in('trang_thai', $trang_thai_hoan_thanh)->where('thoi_gian_xu_ly IS NOT NULL')->where('loai_van_ban', $this->common::VAN_BAN_DEN)->where('deleted_at IS NULL')->where($excludeTrash, null, false)->count_all_results('', false);

        // Văn bản lưu trữ
        $luutru = (clone $base_thoi_han)->where('trang_thai', Common::STATUS_VAN_BAN_DEN['LUU_TRU']['value'])->where('loai_van_ban', $this->common::VAN_BAN_DEN)->where('deleted_at IS NULL')->where($excludeTrash, null, false)->count_all_results('', false);

        // Văn bản đã bút phê
        $dabutphe = (clone $base_thoi_han)->where('trang_thai', Common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value'])->where('loai_van_ban', $this->common::VAN_BAN_DEN)->where('deleted_at IS NULL')->where($excludeTrash, null, false)->count_all_results('', false);

        // Văn bản đã chuyển xử lý
        $dachuyen = (clone $base_thoi_han)->where('trang_thai', Common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'])->where('loai_van_ban', $this->common::VAN_BAN_DEN)->where('deleted_at IS NULL')->where($excludeTrash, null, false)->count_all_results('', false);

        // Văn bản đã xử lý
        $daxuly = (clone $base_thoi_han)->where('trang_thai', Common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value'])->where('loai_van_ban', $this->common::VAN_BAN_DEN)->where('deleted_at IS NULL')->where($excludeTrash, null, false)->count_all_results('', false);

        // Văn bản hoàn thành
        $hoanthanh = (clone $base_thoi_han)->where('trang_thai', Common::STATUS_VAN_BAN_DEN['HOAN_THANH']['value'])->where('loai_van_ban', $this->common::VAN_BAN_DEN)->where('deleted_at IS NULL')->where($excludeTrash, null, false)->count_all_results('', false);


        // Văn bản thu hồi
        $thuhoi = (clone $base_thoi_han)->where('loai_van_ban', $this->common::VAN_BAN_DEN)->where('deleted_at IS NOT NULL')->where($excludeTrash, null, false)->count_all_results('', false);

        // Tất cả (trừ thùng rác và thu hồi)
        $all_active = (clone $base_thoi_han)->where('loai_van_ban', $this->common::VAN_BAN_DEN)->where('deleted_at IS NULL')->where($excludeTrash, null, false)->count_all_results('', false);

        return [
            'recordsTotal'      => $recordsTotal,
            'recordsFiltered'   => $recordsFiltered,
            'data'              => $data,
            'sql'               => $sql,
            'columns'           => $columns,
            'orderBy'           => $orderBy,
            'thoi_han'          => [
                'all'         => $all_active,
                'tiep_nhan'   => $tiepnhan,
                'cho_but_phe' => $chobutphe,
                'da_but_phe'  => $dabutphe,
                'da_chuyen_don_vi_xu_ly' => $dachuyen,
                'da_xu_ly'    => $daxuly,
                'hoan_thanh'  => $hoanthanh,
                'luu_tru'     => $luutru,
                'thu_hoi'     => $thuhoi,
                'tren_5_ngay' => $tren5ngay,
                'duoi_5_ngay' => $duoi5ngay,
                'hom_nay'     => $homnay,
                'qua_han'     => $quahan,
            ]
        ];
    }

    public function getTrashVanbanden($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array(), $fromDate = null, $toDate = null)
    {
        $this->db->from('e_van_ban')
            ->select('e_van_ban.*, e_tinh_chat.ten_tinh_chat, e_loai.ten_loai, e_khoi_co_quan.ten_khoi_co_quan, e_co_quan.ten_co_quan, (
                    SELECT GROUP_CONCAT("- ", e_don_vi.ten_don_vi SEPARATOR "<br>")
                    FROM e_don_vi_xu_ly
                    LEFT JOIN e_don_vi ON e_don_vi.id_don_vi = e_don_vi_xu_ly.id_don_vi
                    WHERE e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly AND e_don_vi_xu_ly.don_vi_xu_ly_chinh IS NOT NULL
                ) AS ten_don_vi_xu_ly,
                  (
                    SELECT GROUP_CONCAT("- ", e_don_vi.ten_don_vi SEPARATOR "<br>")
                    FROM e_don_vi_xu_ly
                    LEFT JOIN e_don_vi ON e_don_vi.id_don_vi = e_don_vi_xu_ly.id_don_vi
                    WHERE e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly AND e_don_vi_xu_ly.don_vi_xu_ly_chinh IS NULL
                ) AS ten_don_vi_phoi_hop')
            ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
            ->join('e_loai', 'e_loai.id_loai =e_van_ban.id_loai', 'left')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
            ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'left');
        // ->join('e_trang_thai', 'e_trang_thai.id_trang_thai = e_van_ban.id_trang_thai', 'left');

        $this->db->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DEN);
        $this->db->where('e_van_ban.deleted_at IS NOT NULL');

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('e_van_ban.ten_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.so_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.so_hieu_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.trich_yeu', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                $this->db->where('e_van_ban.' . $key, $value);
            }
        }

        if ($fromDate && $toDate) {
            $this->db->where("ngay_ky BETWEEN '{$fromDate}' AND '{$toDate}'");
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
            $order = $orderBy['order'];
            $orderColumnIndex = $order[0]['column'];
            $orderDir = $order[0]['dir'];

            $columns = $orderBy['columns'];
            $filed = $columns[$orderColumnIndex]['data'];

            if (!empty($orderDir) && $filed == 'so_van_ban') {
                $this->db->order_by('e_van_ban.so_van_ban', $orderDir);
                $this->db->order_by('e_van_ban.so_van_ban_hau_to', $orderDir);
            } else {
                $this->db->order_by('e_van_ban.' . $filed, $orderDir);
            }
        }



        if ($length != '-1') {
            $this->db->limit($length, $start);
        }


        $query = $this->db->get();
        $data = $query->result_array();

        foreach ($data as &$dt) {
            $files = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_file_dinh_kem')->result_array();
            foreach ($files as $key => &$file) {
                $file['duong_dan'] = encryptString($file['duong_dan']);
            }
            $dt['files'] = $files;

            // $dt['files'] = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_file_dinh_kem')->result_array();
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }

    public function getTrashVanbandi($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array(), $fromDate = null, $toDate = null)
    {
        $this->db->from('e_van_ban')
            ->select('e_van_ban.*, e_tinh_chat.ten_tinh_chat, e_loai.ten_loai, e_khoi_co_quan.ten_khoi_co_quan')
            ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
            ->join('e_loai', 'e_loai.id_loai =e_van_ban.id_loai', 'left')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left');

        $this->db->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DI);
        $this->db->where('e_van_ban.deleted_at IS NOT NULL');

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('e_van_ban.ten_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.so_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.so_hieu_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.trich_yeu', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                $this->db->where('e_van_ban.' . $key, $value);
            }
        }

        if ($fromDate && $toDate) {
            $this->db->where("ngay_ky BETWEEN '{$fromDate}' AND '{$toDate}'");
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
                $this->db->order_by('e_van_ban.' . $filed, $orderDir);
            }
        }

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }


        $query = $this->db->get();
        $data = $query->result_array();

        foreach ($data as &$dt) {
            $files = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_file_dinh_kem')->result_array();
            foreach ($files as $key => &$file) {
                $file['duong_dan'] = encryptString($file['duong_dan']);
            }
            $dt['files'] = $files;

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
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }

    public function getTrashVanbannoibo($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array(), $fromDate = null, $toDate = null, $auth)
    {
        $donviId = $auth['id_don_vi'];
        $this->db->from('e_van_ban')
            ->select('e_van_ban.*, e_tinh_chat.ten_tinh_chat, e_loai.ten_loai, e_khoi_co_quan.ten_khoi_co_quan')
            ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
            ->join('e_loai', 'e_loai.id_loai =e_van_ban.id_loai', 'left')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
            ->where('e_van_ban.id_don_vi_soan', $donviId);

        $this->db->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_NOI_BO);
        $this->db->where('e_van_ban.deleted_at IS NOT NULL');

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('e_van_ban.ten_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.so_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.so_hieu_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.trich_yeu', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                $this->db->where('e_van_ban.' . $key, $value);
            }
        }

        if ($fromDate && $toDate) {
            $this->db->where("ngay_ky BETWEEN '{$fromDate}' AND '{$toDate}'");
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
                $this->db->order_by('e_van_ban.' . $filed, $orderDir);
            }
        }

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }


        $query = $this->db->get();
        $data = $query->result_array();

        foreach ($data as &$dt) {
            $files = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_file_dinh_kem')->result_array();
            foreach ($files as $key => &$file) {
                $file['duong_dan'] = encryptString($file['duong_dan']);
            }
            $dt['files'] = $files;

            // $dt['files'] = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_file_dinh_kem')->result_array();
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }

    public function getListExportVanbanden($start = 0, $length = 10, $searchValue = null, $searchKey = array(), $fromDate = null, $toDate = null)
    {
        $this->db->from('e_van_ban')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
            ->select(
                "DATE_FORMAT(ngay_nhan,'%Y') as nam, DATE_FORMAT(ngay_nhan,'%d/%m/%Y') as ngay_nhan, so_hieu_van_ban, ten_co_quan AS noi_gui, DATE_FORMAT(ngay_ban_hanh,'%d/%m/%Y') as ngay_ban_hanh, trich_yeu AS noi_dung, nguoi_ky"
            );
        // ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
        // ->join('e_loai', 'e_loai.id_loai =e_van_ban.id_loai', 'left')

        $this->db->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DEN);
        $this->db->where('e_van_ban.deleted_at IS NULL');

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('e_van_ban.ten_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.so_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.so_hieu_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.trich_yeu', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                $this->db->where('e_van_ban.' . $key, $value);
            }
        }

        if ($fromDate && $toDate) {
            $this->db->where("ngay_ky BETWEEN '{$fromDate}' AND '{$toDate}'");
        }

        $this->db->order_by('e_van_ban.so_van_ban', 'DESC');
        $this->db->order_by('e_van_ban.so_van_ban_hau_to', 'DESC');

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        $query = $this->db->get();
        $data = $query->result_array();

        return $data;
    }

    // Danh sách văn bản đi của TCHC
    public function getAllVanbandi($start = 0, $length = 10, $searchValue = null, $orderBy = [], $columns = [], $searchKey = array(), $fromDate = null, $toDate = null, $auth, $dataSource = [])
    {
        $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
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
        // $this->db->where('e_van_ban.deleted_at IS NULL');
        
        $this->db->group_start();
        $this->db->where('e_don_vi.ma_don_vi', 'PHONG_TCHC');
        $this->db->where('ql_vai_tro.ql_ma_vai_tro', 'VAN_THU_TO_CHUC_HANH_CHINH');
        $this->db->or_where('ql_vai_tro.ql_ma_vai_tro', 'LANH_DAO_TCHC');
        $this->db->or_where('ql_vai_tro.ql_ma_vai_tro', 'SUPER_ADMIN');
        $this->db->or_where('nguoi_tao.ql_nguoi_dung_is_admin', 1);
        $this->db->group_end();
        $this->db->group_by('e_van_ban.id_van_ban');

        $baseQuery =  clone $this->db;
        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue && !empty($searchValue)) {
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
            if (!empty($searchKey['year']) && $searchKey['year'] !== 'all_years') {
                $this->db->where("YEAR(e_van_ban.ngay_ban_hanh)", $searchKey['year']);
            }

            if (!empty($searchKey['ngay_ky_tu']) && !empty($searchKey['ngay_ky_den'])) {
                $from = $searchKey['ngay_ky_tu'];
                $to   = $searchKey['ngay_ky_den'];
                $this->db->where("DATE(e_van_ban.ngay_ky) BETWEEN '{$from}' AND '{$to}'");
            }

            if (!empty($searchKey['ngay_nhan_tu']) && !empty($searchKey['ngay_nhan_den'])) {
                $from = $searchKey['ngay_nhan_tu'];
                $to   = $searchKey['ngay_nhan_den'];
                $this->db->where("DATE(e_van_ban.ngay_nhan) BETWEEN '{$from}' AND '{$to}'");
            }

            if (!empty($searchKey['thoi_gian_xu_ly_tu']) && !empty($searchKey['thoi_gian_xu_ly_den'])) {
                $from = $searchKey['thoi_gian_xu_ly_tu'];
                $to   = $searchKey['thoi_gian_xu_ly_den'];
                $this->db->where("DATE(e_van_ban.thoi_gian_xu_ly) BETWEEN '{$from}' AND '{$to}'");
            }

            if (!empty($searchKey['ngay_tra_loi_cv_den_tu']) && !empty($searchKey['ngay_tra_loi_cv_den_den'])) {
                $from = $searchKey['ngay_tra_loi_cv_den_tu'];
                $to   = $searchKey['ngay_tra_loi_cv_den_den'];
                $this->db->where("DATE(e_van_ban.ngay_tra_loi_cv_den) BETWEEN '{$from}' AND '{$to}'");
            }

            foreach ($searchKey as $key => $value) {
                if (!empty($value)) {
                    switch ($key) {
                        case 'so_hieu_van_ban':
                            $this->db->like('e_van_ban.so_hieu_van_ban', $value);
                            $this->db->where('e_van_ban.deleted_at IS NULL');
                            break;
                        case 'trich_yeu':
                            $this->db->like('e_van_ban.trich_yeu', $value);
                            $this->db->where('e_van_ban.deleted_at IS NULL');
                            break;
                        case 'id_loai':
                            $this->db->where('e_van_ban.id_loai', $value);
                            $this->db->where('e_van_ban.deleted_at IS NULL');
                            break;
                        case 'ngay_nhan':
                            $this->db->where('DATE(e_van_ban.ngay_nhan)', $value);
                            break;
                        case 'thoi_gian_xu_ly':
                            $this->db->where('DATE(e_van_ban.thoi_gian_xu_ly)', $value);
                            break;
                        case 'id_don_vi_xu_ly':
                            $this->db->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'left');
                            $this->db->join('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly', 'left');
                            $this->db->where('e_don_vi_xu_ly.id_don_vi', $value);
                            $this->db->group_by('e_van_ban.id_van_ban');
                            $this->db->where('e_van_ban.deleted_at IS NULL');
                            break;
                        case 'year':
                            // Đã xử lý ở đầu khối searchKey
                            break;
                        case 'selectedClassify':
                            $excludeTrash = "NOT EXISTS (SELECT 1 FROM e_van_ban_da_xoa WHERE e_van_ban_da_xoa.id_van_ban = e_van_ban.id_van_ban AND e_van_ban_da_xoa.id_don_vi = " . $this->db->escape($auth['id_don_vi']) . ")";
                            switch ($value) {
                                case 'all':
                                    $this->db->where('e_van_ban.deleted_at IS NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'luu_tru':
                                    $this->db->where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DI['LUU_TRU']['value']);
                                    $this->db->where('e_van_ban.deleted_at IS NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'cho_xu_ly':
                                    $this->db->where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value']);
                                    $this->db->where('e_van_ban.deleted_at IS NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'hoan_thanh':
                                    $this->db->where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DI['HOAN_THANH']['value']);
                                    $this->db->where('e_van_ban.deleted_at IS NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'thu_hoi':
                                    $this->db->where('e_van_ban.deleted_at IS NOT NULL', null, false);
                                    $this->db->where($excludeTrash, null, false);
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

        if (!empty($dataSource)) {
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
        // Xử lý order ưu tiên sortOrder từ popup filter
        if (!empty($searchKey['sortOrder'])) {
            if ($searchKey['sortOrder'] === 'newest') {
                $this->db->order_by('e_van_ban.id_van_ban', 'DESC');
            } else if ($searchKey['sortOrder'] === 'oldest') {
                $this->db->order_by('e_van_ban.id_van_ban', 'ASC');
            }
        } else if (!empty($orderBy)) {
            $this->handleDatatableOrdering($orderBy, $columns);
        } else if (!empty($dataSource['order'])) {
            $this->applyOrdering($dataSource['order']);
        } else {
            // Default order
            $this->db->order_by('e_van_ban.ngay_nhan', 'DESC');
            $this->db->order_by('e_van_ban.so_van_ban', 'DESC');
        }

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
            $dt['files_tchc'] = $files_noi_bo;

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
        // Thẻ thống kê
        $excludeTrash = "NOT EXISTS (SELECT 1 FROM e_van_ban_da_xoa WHERE e_van_ban_da_xoa.id_van_ban = e_van_ban.id_van_ban AND e_van_ban_da_xoa.id_don_vi = " . $this->db->escape($auth['id_don_vi']) . ")";

        $st_all = (clone $baseQuery)->where('deleted_at IS NULL')->where($excludeTrash, null, false)->count_all_results();
        $st_luu_tru = (clone $baseQuery)->where('trang_thai', Common::STATUS_VAN_BAN_DI['LUU_TRU']['value'])->where('deleted_at IS NULL')->where($excludeTrash, null, false)->count_all_results();
        $st_cho_xu_ly = (clone $baseQuery)->where('trang_thai', Common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value'])->where('deleted_at IS NULL')->where($excludeTrash, null, false)->count_all_results();
        $st_hoan_thanh = (clone $baseQuery)->where('trang_thai', Common::STATUS_VAN_BAN_DI['HOAN_THANH']['value'])->where('deleted_at IS NULL')->where($excludeTrash, null, false)->count_all_results();
        $st_thu_hoi = (clone $baseQuery)->where('deleted_at IS NOT NULL')->where($excludeTrash, null, false)->count_all_results();

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'sql' => $sql,
            'thoi_han' => [
                'all' => $st_all,
                'luu_tru' => $st_luu_tru,
                'cho_xu_ly' => $st_cho_xu_ly,
                'hoan_thanh' => $st_hoan_thanh,
                'thu_hoi' => $st_thu_hoi
            ]
        ];
    }

    // Danh sách văn bản đi của đơn vị
    public function getAllVanbandidonvi($start = 0, $length = 10, $searchValue = null, $orderBy = [], $columns = [], $searchKey = array(), $fromDate = null, $toDate = null, $auth, $dataSource = [])
    {
        $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
        $this->db->from('e_van_ban')
            ->select('
                e_van_ban.*, 
                e_tinh_chat.ten_tinh_chat, 
                e_loai.ten_loai, 
                e_khoi_co_quan.ten_khoi_co_quan, 
                e_bao_mat.ten_bao_mat, 
                nguoi_tao.ql_nguoi_dung_ho_ten as ten_nguoi_tao,
                nguoi_tao.ql_nguoi_dung_email as email_nguoi_tao
            ')
            ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
            ->join('e_bao_mat', 'e_bao_mat.id_bao_mat = e_van_ban.id_bao_mat', 'left')
            ->join('e_loai', 'e_loai.id_loai = e_van_ban.id_loai', 'left')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
            ->join('ql_nguoi_dung AS nguoi_tao', 'nguoi_tao.ql_nguoi_dung_id = e_van_ban.id_nguoi_tao', 'left')
            ->join('e_don_vi', 'nguoi_tao.id_don_vi = e_don_vi.id_don_vi', 'left');

        $this->db->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DI);
        // $this->db->where('e_van_ban.deleted_at IS NULL');
        $this->db->where('nguoi_tao.id_don_vi', $auth['id_don_vi']);

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue && !empty($searchValue)) {
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

        if (!empty($searchKey)) {
            if (!empty($searchKey['ngay_ky_tu']) && !empty($searchKey['ngay_ky_den'])) {
                $from = $searchKey['ngay_ky_tu'];
                $to   = $searchKey['ngay_ky_den'];
                $this->db->where("DATE(e_van_ban.ngay_ky) BETWEEN '{$from}' AND '{$to}'");
            }

            if (!empty($searchKey['ngay_nhan_tu']) && !empty($searchKey['ngay_nhan_den'])) {
                $from = $searchKey['ngay_nhan_tu'];
                $to   = $searchKey['ngay_nhan_den'];
                $this->db->where("DATE(e_van_ban.ngay_nhan) BETWEEN '{$from}' AND '{$to}'");
            }

            if (!empty($searchKey['thoi_gian_xu_ly_tu']) && !empty($searchKey['thoi_gian_xu_ly_den'])) {
                $from = $searchKey['thoi_gian_xu_ly_tu'];
                $to   = $searchKey['thoi_gian_xu_ly_den'];
                $this->db->where("DATE(e_van_ban.thoi_gian_xu_ly) BETWEEN '{$from}' AND '{$to}'");
            }

            if (!empty($searchKey['ngay_tra_loi_cv_den_tu']) && !empty($searchKey['ngay_tra_loi_cv_den_den'])) {
                $from = $searchKey['ngay_tra_loi_cv_den_tu'];
                $to   = $searchKey['ngay_tra_loi_cv_den_den'];
                $this->db->where("DATE(e_van_ban.ngay_tra_loi_cv_den) BETWEEN '{$from}' AND '{$to}'");
            }

            foreach ($searchKey as $key => $value) {
                if (!empty($value)) {
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
                        case 'ngay_nhan':
                            $this->db->where('DATE(e_van_ban.ngay_nhan)', $value);
                            break;
                        case 'thoi_gian_xu_ly':
                            $this->db->where('DATE(e_van_ban.thoi_gian_xu_ly)', $value);
                            break;
                        case 'year':
                            if ($value !== 'all_years') {
                                $this->db->where("YEAR(e_van_ban.ngay_ban_hanh)", $value);
                            }
                            break;
                        case 'selectedClassify':
                            $excludeTrash = "NOT EXISTS (SELECT 1 FROM e_van_ban_da_xoa WHERE e_van_ban_da_xoa.id_van_ban = e_van_ban.id_van_ban AND e_van_ban_da_xoa.id_don_vi = " . $this->db->escape($auth['id_don_vi']) . ")";
                            switch ($value) {
                                case 'all':
                                    $this->db->where('e_van_ban.deleted_at IS NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'luu_tru':
                                    $this->db->where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DI['LUU_TRU']['value']);
                                    $this->db->where('e_van_ban.deleted_at IS NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'cho_xu_ly':
                                    $this->db->where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value']);
                                    $this->db->where('e_van_ban.deleted_at IS NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'hoan_thanh':
                                    $this->db->where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DI['HOAN_THANH']['value']);
                                    $this->db->where('e_van_ban.deleted_at IS NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'thu_hoi':
                                    $this->db->where('e_van_ban.deleted_at IS NOT NULL');
                                    $this->db->where($excludeTrash, null, false);
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

        if ($fromDate && $toDate) {
            $this->db->where("ngay_ky BETWEEN '{$fromDate}' AND '{$toDate}'");
        }

        if (!empty($columns)) {
            // Map tên cột hiển thị (alias) sang cột thật trong DB
            $columnMapping = [
                'ten_nguoi_tao'    => 'nguoi_tao.ql_nguoi_dung_ho_ten',
                'ten_khoi_co_quan' => 'e_khoi_co_quan.ten_khoi_co_quan',
                'ten_co_quan'      => 'e_co_quan.ten_co_quan',
                'ten_loai'         => 'e_loai.ten_loai',
                'ten_tinh_chat'    => 'e_tinh_chat.ten_tinh_chat',
                'nguoi_but_phe'    => 'ql_nguoi_dung.ql_nguoi_dung_ho_ten',
                'trang_thai'       => 'e_van_ban.trang_thai', // xử lý riêng
                // Mặc định nếu không map thì nó sẽ lấy e_van_ban.[columnName]
            ];
            $this->handleDatatableColumns($columns, $columnMapping);
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query

        // Xử lý order sau khi xử lý search
        // Xử lý order ưu tiên sortOrder từ popup filter
        if (!empty($searchKey['sortOrder'])) {
            if ($searchKey['sortOrder'] === 'newest') {
                $this->db->order_by('e_van_ban.id_van_ban', 'DESC');
            } else if ($searchKey['sortOrder'] === 'oldest') {
                $this->db->order_by('e_van_ban.id_van_ban', 'ASC');
            }
        } else if (!empty($orderBy)) {
            $this->handleDatatableOrdering($orderBy, $columns);
        } else if (!empty($dataSource['order'])) {
            $this->applyOrdering($dataSource['order']);
        } else {
            // Default order
            $this->db->order_by('e_van_ban.id_van_ban', 'DESC');
        }

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

            $dt['nguoi_tao_van_ban'] = $this->db
                ->select('
                    ql_nguoi_dung.ql_nguoi_dung_ho_ten, 
                    ql_nguoi_dung.ql_nguoi_dung_id, 
                    e_don_vi.id_don_vi, 
                    e_don_vi.ten_don_vi
                ')
                ->from('ql_nguoi_dung')
                ->join('e_don_vi', 'ql_nguoi_dung.id_don_vi = e_don_vi.id_don_vi', 'left')
                ->where('ql_nguoi_dung_id', $dt['id_nguoi_tao'])
                ->get()
                ->row_array();

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
        }

        $excludeTrash = "NOT EXISTS (SELECT 1 FROM e_van_ban_da_xoa WHERE e_van_ban_da_xoa.id_van_ban = e_van_ban.id_van_ban AND e_van_ban_da_xoa.id_don_vi = " . $this->db->escape($auth['id_don_vi']) . ")";

        // Tất cả (trừ thùng rác của đơn vị)
        $all = $this->db->from('e_van_ban')
            ->join('ql_nguoi_dung AS nguoi_tao', 'nguoi_tao.ql_nguoi_dung_id = e_van_ban.id_nguoi_tao', 'left')
            ->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DI)
            ->where('nguoi_tao.id_don_vi', $auth['id_don_vi'])
            ->where('e_van_ban.deleted_at IS NULL')
            ->where($excludeTrash, null, false)
            ->get()
            ->num_rows();

        // Lưu trữ
        $luutru = $this->db->from('e_van_ban')
            ->join('ql_nguoi_dung AS nguoi_tao', 'nguoi_tao.ql_nguoi_dung_id = e_van_ban.id_nguoi_tao', 'left')
            ->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DI)
            ->where('nguoi_tao.id_don_vi', $auth['id_don_vi'])
            ->where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DI['LUU_TRU']['value'])
            ->where('e_van_ban.deleted_at IS NULL')
            ->where($excludeTrash, null, false)
            ->get()
            ->num_rows();

        // Chờ xử lý
        $choxuly = $this->db->from('e_van_ban')
            ->join('ql_nguoi_dung AS nguoi_tao', 'nguoi_tao.ql_nguoi_dung_id = e_van_ban.id_nguoi_tao', 'left')
            ->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DI)
            ->where('nguoi_tao.id_don_vi', $auth['id_don_vi'])
            ->where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value'])
            ->where('e_van_ban.deleted_at IS NULL')
            ->where($excludeTrash, null, false)
            ->get()
            ->num_rows();

        // Hoàn thành
        $hoanthanh = $this->db->from('e_van_ban')
            ->join('ql_nguoi_dung AS nguoi_tao', 'nguoi_tao.ql_nguoi_dung_id = e_van_ban.id_nguoi_tao', 'left')
            ->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DI)
            ->where('nguoi_tao.id_don_vi', $auth['id_don_vi'])
            ->where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DI['HOAN_THANH']['value'])
            ->where('e_van_ban.deleted_at IS NULL')
            ->where($excludeTrash, null, false)
            ->get()
            ->num_rows();

        // Thu hồi
        $thuhoi = $this->db->from('e_van_ban')
            ->join('ql_nguoi_dung AS nguoi_tao', 'nguoi_tao.ql_nguoi_dung_id = e_van_ban.id_nguoi_tao', 'left')
            ->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DI)
            ->where('nguoi_tao.id_don_vi', $auth['id_don_vi'])
            ->where('e_van_ban.deleted_at IS NOT NULL')
            ->where($excludeTrash, null, false)
            ->get()
            ->num_rows();

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'sql' => $sql,
            'thoi_han' => [
                'all' => $all,
                'luu_tru' => $luutru,
                'cho_xu_ly' => $choxuly,
                'hoan_thanh' => $hoanthanh,
                'thu_hoi' => $thuhoi,
            ]
        ];
    }

    public function getListExportVanbandi($start = 0, $length = 10, $searchValue = null, $searchKey = array(), $fromDate = null, $toDate = null, $auth)
    {
        $this->db->from('e_van_ban')
            ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
            ->join('e_bao_mat', 'e_bao_mat.id_bao_mat = e_van_ban.id_bao_mat', 'left')
            ->join('e_loai', 'e_loai.id_loai =e_van_ban.id_loai', 'left')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
            ->select(
                "
                    DATE_FORMAT(ngay_ky,'%Y') as nam, 
                    DATE_FORMAT(ngay_nhan,'%d/%m/%Y') as ngay_nhan, 
                    so_hieu_van_ban, ten_co_quan AS noi_nhan, 
                    DATE_FORMAT(ngay_ban_hanh,'%d/%m/%Y') as ngay_ban_hanh, 
                    trich_yeu AS noi_dung, nguoi_ky, 
                    DATE_FORMAT(ngay_ky, '%d/%m/%Y') as ngay_ky,
                    e_van_ban.id_van_ban
                "
            );

        $this->db->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DI);
        $this->db->where('e_van_ban.deleted_at IS NULL');

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('e_van_ban.ten_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.so_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.so_hieu_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.trich_yeu', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            if (!empty($searchKey['ngay_ky_tu']) && !empty($searchKey['ngay_ky_den'])) {
                $from = $searchKey['ngay_ky_tu'];
                $to   = $searchKey['ngay_ky_den'];
                $this->db->where("DATE(e_van_ban.ngay_ky) BETWEEN '{$from}' AND '{$to}'");
            }

            if (!empty($searchKey['ngay_nhan_tu']) && !empty($searchKey['ngay_nhan_den'])) {
                $from = $searchKey['ngay_nhan_tu'];
                $to   = $searchKey['ngay_nhan_den'];
                $this->db->where("DATE(e_van_ban.ngay_nhan) BETWEEN '{$from}' AND '{$to}'");
            }

            if (!empty($searchKey['thoi_gian_xu_ly_tu']) && !empty($searchKey['thoi_gian_xu_ly_den'])) {
                $from = $searchKey['thoi_gian_xu_ly_tu'];
                $to   = $searchKey['thoi_gian_xu_ly_den'];
                $this->db->where("DATE(e_van_ban.thoi_gian_xu_ly) BETWEEN '{$from}' AND '{$to}'");
            }

            if (!empty($searchKey['ngay_tra_loi_cv_den_tu']) && !empty($searchKey['ngay_tra_loi_cv_den_den'])) {
                $from = $searchKey['ngay_tra_loi_cv_den_tu'];
                $to   = $searchKey['ngay_tra_loi_cv_den_den'];
                $this->db->where("DATE(e_van_ban.ngay_tra_loi_cv_den) BETWEEN '{$from}' AND '{$to}'");
            }

            foreach ($searchKey as $key => $value) {
                if (!empty($value)) {
                    switch ($key) {
                        case 'so_hieu_van_ban':
                            $this->db->like('e_van_ban.so_hieu_van_ban', $value);
                            $this->db->where('e_van_ban.deleted_at IS NULL');
                            break;
                        case 'trich_yeu':
                            $this->db->like('e_van_ban.trich_yeu', $value);
                            $this->db->where('e_van_ban.deleted_at IS NULL');
                            break;
                        case 'id_loai':
                            $this->db->where('e_van_ban.id_loai', $value);
                            $this->db->where('e_van_ban.deleted_at IS NULL');
                            break;
                        case 'ngay_nhan':
                            $this->db->where('DATE(e_van_ban.ngay_nhan)', $value);
                            break;
                        case 'thoi_gian_xu_ly':
                            $this->db->where('DATE(e_van_ban.thoi_gian_xu_ly)', $value);
                            break;
                        case 'id_don_vi_xu_ly':
                            $this->db->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'left');
                            $this->db->join('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly', 'left');
                            $this->db->where('e_don_vi_xu_ly.id_don_vi', $value);
                            $this->db->group_by('e_van_ban.id_van_ban');
                            $this->db->where('e_van_ban.deleted_at IS NULL');
                            break;
                        case 'selectedClassify':
                            $excludeTrash = "NOT EXISTS (SELECT 1 FROM e_van_ban_da_xoa WHERE e_van_ban_da_xoa.id_van_ban = e_van_ban.id_van_ban AND e_van_ban_da_xoa.id_don_vi = " . $this->db->escape($auth['id_don_vi']) . ")";
                            switch ($value) {
                                case 'all':
                                    $this->db->where('e_van_ban.deleted_at IS NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'luu_tru':
                                    $this->db->where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DI['LUU_TRU']['value']);
                                    $this->db->where('e_van_ban.deleted_at IS NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'cho_xu_ly':
                                    $this->db->where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value']);
                                    $this->db->where('e_van_ban.deleted_at IS NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'hoan_thanh':
                                    $this->db->where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DI['HOAN_THANH']['value']);
                                    $this->db->where('e_van_ban.deleted_at IS NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'thu_hoi':
                                    $this->db->where('e_van_ban.deleted_at IS NOT NULL', null, false);
                                    $this->db->where($excludeTrash, null, false);
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

        if ($fromDate && $toDate) {
            $this->db->where("ngay_ky BETWEEN '{$fromDate}' AND '{$toDate}'");
        }

        // if ($length != '-1') {
        //     $this->db->limit($length, $start);
        // }

        $query = $this->db->get();
        $data = $query->result_array();

        return $data;
    }

    public function getAllButPhe($start = 0, $length = 10, $searchValue = null, $orderBy = [], $columns = [], $searchKey = array(), $fromDate = null, $toDate = null)
    {
        $now = date('Y-m-d');
        $fiveDaysLater = date('Y-m-d', strtotime('5 days'));

        $this->db->from('e_van_ban')
            ->select('
                    e_van_ban.*, 
                    e_tinh_chat.ten_tinh_chat, 
                    e_loai.ten_loai, 
                    e_khoi_co_quan.ten_khoi_co_quan, 
                    e_co_quan.ten_co_quan, 
                    e_don_vi.ten_don_vi,
                    e_but_phe.noi_dung_but_phe,
                    ql_nguoi_dung.ql_nguoi_dung_ho_ten,
                    ql_nguoi_dung.ql_nguoi_dung_email
            ')
            ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
            ->join('e_loai', 'e_loai.id_loai =e_van_ban.id_loai', 'left')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
            ->join('e_don_vi', 'e_van_ban.id_don_vi = e_don_vi.id_don_vi', 'left')
            ->join('e_but_phe', 'e_but_phe.id_van_ban = e_van_ban.id_van_ban', 'left')
            ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'left')
            ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_but_phe.id_nguoi_but_phe', 'left');

        $this->db->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DEN);
        $this->db->where('e_van_ban.deleted_at IS NULL');

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        $this->db->group_start();
        $this->db->where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DEN['CHO_LANH_DAO_BUT_PHE']['value']);
        $this->db->or_where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value']);
        $this->db->or_where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DEN['LUU_TRU']['value']);
        $this->db->group_end();

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('e_van_ban.ten_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.so_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.so_hieu_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.trich_yeu', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            $arr = ['so_van_ban', 'trich_yeu'];
            foreach ($searchKey as $key => $value) {
                if ($key == 'thoi_han') {
                    switch ($value) {
                        case 'tren_5_ngay':
                            $this->db->where("e_van_ban.thoi_gian_xu_ly > ", $fiveDaysLater)
                                ->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL');
                            break;
                        case 'duoi_5_ngay':
                            $this->db->where("e_van_ban.thoi_gian_xu_ly <= ", $fiveDaysLater)
                                ->where("e_van_ban.thoi_gian_xu_ly > ", $now)
                                ->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL');
                            break;
                        case 'hom_nay':
                            $this->db->where("DATE(e_van_ban.thoi_gian_xu_ly)", $now)
                                ->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL');
                            break;
                        case 'qua_han':
                            $this->db->where("e_van_ban.thoi_gian_xu_ly < ", $now)
                                ->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL');
                            break;
                    }
                } else if ($key == 'id_nguoi_but_phe') {

                    $this->db->where('e_but_phe.id_nguoi_but_phe', $value);
                } else if (in_array($key, $arr)) {

                    $this->db->like('e_van_ban.' . $key, $value);
                } else {

                    if (is_array($value)) {
                        $this->db->where_in('e_van_ban.' . $key, $value);
                    } else {
                        $this->db->where('e_van_ban.' . $key, $value);
                    }
                }
            }
        }

        if ($fromDate && $toDate) {
            $this->db->where("ngay_nhan BETWEEN '{$fromDate}' AND '{$toDate}'");
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query

        if (!empty($columns)) {
            foreach ($columns as $key => $column) {
                if ($column['search']['value'] != '') {
                    $columnName = $column['data'];
                    $searchColumn = $column['search']['value'];

                    if (in_array($columnName, ['ten_khoi_co_quan'])) {
                        // e_khoi_co_quan
                        $this->db->like('e_khoi_co_quan.' . $columnName, $searchColumn);
                    } else if (in_array($columnName, ['ten_co_quan'])) {
                        // e_co_quan
                        $this->db->like('e_co_quan.' . $columnName, $searchColumn);
                    } else if (in_array($columnName, ['ten_loai'])) {
                        // e_loai 
                        $this->db->like('e_loai.' . $columnName, $searchColumn);
                    } else if (in_array($columnName, ['ten_tinh_chat'])) {
                        // e_tinh_chat
                        $this->db->like('e_tinh_chat.' . $columnName, $searchColumn);
                    } else if (in_array($columnName, ['ql_nguoi_dung_ho_ten'])) {
                        // e_tinh_chat
                        $this->db->like('ql_nguoi_dung.' . $columnName, $searchColumn);
                    } else {
                        // e_van_ban 
                        if ($columnName == 'trang_thai') {
                            $searchColumn =  $this->getTrangThaiValueFromLabel($searchColumn);
                            $this->db->like('e_van_ban.' . $columnName, $searchColumn);
                        }
                        $this->db->like('e_van_ban.' . $columnName, $searchColumn);
                    }
                }
            }
        }

        // Xử lý order sau khi xử lý search
        if (!empty($orderBy)) {
            foreach ($orderBy as $order) {
                // Giả sử $order có 'column' và 'dir'
                $colIndex = $order['column'];
                $dir = $order['dir'];

                if (isset($columns[$colIndex])) {
                    $colName = $columns[$colIndex]['data'];
                    $this->db->order_by('e_van_ban.' . $colName, $dir);
                }
            }
        }

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }


        $query = $this->db->get();
        $data = $query->result_array();
        $sql = $this->db->last_query();

        foreach ($data as &$dt) {
            $files = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_file_dinh_kem')->result_array();
            foreach ($files as $key => &$file) {
                $file['duong_dan'] = encryptString($file['duong_dan']);
            }
            $dt['files'] = $files;

            // $dt['files'] = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_file_dinh_kem')->result_array();
            //Xem danh sách đơn vị xử lý và phối hợp
            $xuly = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_xu_ly')->row_array();
            $donvixuly = [];
            if ($xuly) {
                $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
                $donvixuly = $this->db
                    ->join('e_don_vi', 'e_don_vi_xu_ly.id_don_vi = e_don_vi.id_don_vi')
                    ->where('e_don_vi_xu_ly.id_xu_ly', $xuly['id_xu_ly'])
                    ->select('e_don_vi_xu_ly.id_don_vi_xu_ly, e_don_vi_xu_ly.don_vi_xu_ly_chinh, e_don_vi_xu_ly.da_xem, e_don_vi.id_don_vi, e_don_vi.ten_don_vi, e_don_vi.ma_don_vi')
                    ->group_by('e_don_vi_xu_ly.id_don_vi_xu_ly')
                    ->get('e_don_vi_xu_ly')
                    ->result_array();

                $donvixulyIds = array_unique(array_column($donvixuly, 'id_don_vi_xu_ly'));
                if (!empty($donvixulyIds)) {
                    $donvixulydaxem = $this->db
                        ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_don_vi_xu_ly_da_xem.ql_nguoi_dung_id')
                        ->where_in('e_don_vi_xu_ly_da_xem.id_don_vi_xu_ly', $donvixulyIds)
                        ->select('e_don_vi_xu_ly_da_xem.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten, ql_nguoi_dung.ql_nguoi_dung_email, ql_nguoi_dung.ql_nguoi_dung_avatar')
                        ->get('e_don_vi_xu_ly_da_xem')
                        ->result_array();
                    foreach ($donvixuly as &$dvxl) {
                        $dvxl['danh_sach_da_xem'] = array_filter($donvixulydaxem, function ($item) use ($dvxl) {
                            return $item['id_don_vi_xu_ly'] == $dvxl['id_don_vi_xu_ly'];
                        });
                    }
                }
            }
            $dt['don_vi_xu_ly'] = $donvixuly;
        }

        // Văn bản trên 5 ngày
        $tren5ngay = $this->db->from('e_van_ban')
            ->where("thoi_gian_xu_ly > ", $fiveDaysLater)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
            ->where_in('trang_thai', [
                $this->common::STATUS_VAN_BAN_DEN['CHO_LANH_DAO_BUT_PHE']['value'],
                $this->common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value'],
                $this->common::STATUS_VAN_BAN_DEN['LUU_TRU']['value']
            ])
            ->where('deleted_at IS NULL')
            ->get()
            ->num_rows();

        // Văn bản trong vòng 5 ngày
        $duoi5ngay = $this->db->from('e_van_ban')
            ->where("thoi_gian_xu_ly <= ", $fiveDaysLater)
            ->where("thoi_gian_xu_ly > ", $now)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
            ->where_in('trang_thai', [
                $this->common::STATUS_VAN_BAN_DEN['CHO_LANH_DAO_BUT_PHE']['value'],
                $this->common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value'],
                $this->common::STATUS_VAN_BAN_DEN['LUU_TRU']['value']
            ])
            ->where('deleted_at IS NULL')
            ->get()
            ->num_rows();

        // Văn bản của ngày hôm nay
        $homnay = $this->db->from('e_van_ban')
            ->where("DATE(thoi_gian_xu_ly)", $now)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
            ->where_in('trang_thai', [
                $this->common::STATUS_VAN_BAN_DEN['CHO_LANH_DAO_BUT_PHE']['value'],
                $this->common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value'],
                $this->common::STATUS_VAN_BAN_DEN['LUU_TRU']['value']
            ])
            ->where('deleted_at IS NULL')
            ->get()
            ->num_rows();

        // Văn bản quá hạn
        $quahan = $this->db->from('e_van_ban')
            ->where("thoi_gian_xu_ly < ", $now)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
            ->where_in('trang_thai', [
                $this->common::STATUS_VAN_BAN_DEN['CHO_LANH_DAO_BUT_PHE']['value'],
                $this->common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value'],
                $this->common::STATUS_VAN_BAN_DEN['LUU_TRU']['value']
            ])
            ->where('deleted_at IS NULL')
            ->get()
            ->num_rows();

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'tren5ngay' => $tren5ngay,
            'duoi5ngay' => $duoi5ngay,
            'homnay' => $homnay,
            'quahan' => $quahan,
            'sql' => $sql,
        ];
    }

    public function getAllXuLyVanBan($start = 0, $length = 10, $searchValue = null, $orderBy = [], $columns = [], $searchKey = array(), $fromDate = null, $toDate = null)
    {
        $now = date('Y-m-d');
        $fiveDaysLater = date('Y-m-d', strtotime('5 days'));

        $this->db->from('e_van_ban')
            ->select('e_van_ban.*,
                e_tinh_chat.ten_tinh_chat,
                e_loai.ten_loai,
                e_khoi_co_quan.ten_khoi_co_quan,
                e_co_quan.ten_co_quan, 
                e_xu_ly.nguoi_phu_trach, 
        ')
            ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
            ->join('e_loai', 'e_loai.id_loai = e_van_ban.id_loai', 'left')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
            ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'left');

        $this->db->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DEN);
        // ->group_by('e_van_ban.id_van_ban');
        $this->db->where('e_van_ban.deleted_at IS NULL');

        $this->db->where_in('e_van_ban.trang_thai', [
            $this->common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value'],
            $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
            $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value'],
            $this->common::STATUS_VAN_BAN_DEN['DA_XEM']['value']
        ]);

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        // $this->db->group_start();
        // $this->db->where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value']);
        // $this->db->or_where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value']);
        // $this->db->or_where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value']);
        // $this->db->where_in('e_van_ban.trang_thai', [
        //     $this->common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value'],
        //     $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
        //     $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value'],
        //     $this->common::STATUS_VAN_BAN_DEN['DA_XEM']['value']
        // ]);
        // $this->db->group_end();

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('e_van_ban.ten_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.so_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.so_hieu_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.trich_yeu', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            $arr = ['so_van_ban', 'trich_yeu'];
            foreach ($searchKey as $key => $value) {
                if ($key == 'thoi_han') {
                    switch ($value) {
                        case 'tren_5_ngay':
                            $this->db->where("e_van_ban.thoi_gian_xu_ly > ", $fiveDaysLater)
                                ->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL');
                            break;
                        case 'duoi_5_ngay':
                            $this->db->where("e_van_ban.thoi_gian_xu_ly <= ", $fiveDaysLater)
                                ->where("e_van_ban.thoi_gian_xu_ly > ", $now)
                                ->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL');
                            break;
                        case 'hom_nay':
                            $this->db->where("DATE(e_van_ban.thoi_gian_xu_ly)", $now)
                                ->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL');
                            break;
                        case 'qua_han':
                            $this->db->where("e_van_ban.thoi_gian_xu_ly < ", $now)
                                ->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL');
                            break;
                    }
                } else if (in_array($key, $arr)) {
                    $this->db->like('e_van_ban.' . $key, $value);
                } else {
                    if (is_array($value)) {
                        $this->db->where_in('e_van_ban.' . $key, $value);
                    } else {
                        $this->db->where('e_van_ban.' . $key, $value);
                    }
                }
            }
        }

        if ($fromDate && $toDate) {
            $this->db->where("ngay_nhan BETWEEN '{$fromDate}' AND '{$toDate}'");
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query

        if (!empty($columns)) {
            foreach ($columns as $key => $column) {
                if ($column['search']['value'] != '') {
                    $columnName = $column['data'];
                    $searchColumn = $column['search']['value'];

                    if (in_array($columnName, ['ten_khoi_co_quan'])) {
                        // e_khoi_co_quan
                        $this->db->like('e_khoi_co_quan.' . $columnName, $searchColumn);
                    } else if (in_array($columnName, ['ten_co_quan'])) {
                        // e_co_quan
                        $this->db->like('e_co_quan.' . $columnName, $searchColumn);
                    } else if (in_array($columnName, ['ten_loai'])) {
                        // e_loai 
                        $this->db->like('e_loai.' . $columnName, $searchColumn);
                    } else if (in_array($columnName, ['ten_tinh_chat'])) {
                        // e_tinh_chat
                        $this->db->like('e_tinh_chat.' . $columnName, $searchColumn);
                    } else {
                        // e_van_ban 
                        if ($columnName == 'trang_thai') {
                            $searchColumn =  $this->getTrangThaiValueFromLabel($searchColumn);
                            $this->db->like('e_van_ban.' . $columnName, $searchColumn);
                        }
                        $this->db->like('e_van_ban.' . $columnName, $searchColumn);
                    }
                }
            }
        }

        // Xử lý order sau khi xử lý search
        if (!empty($orderBy)) {
            foreach ($orderBy as $order) {
                // Giả sử $order có 'column' và 'dir'
                $colIndex = $order['column'];
                $dir = $order['dir'];

                if (isset($columns[$colIndex])) {
                    $colName = $columns[$colIndex]['data'];
                    $this->db->order_by('e_van_ban.' . $colName, $dir);
                }
            }
        }

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }


        $query = $this->db->get();
        $data = $query->result_array();
        $sql = $this->db->last_query();

        foreach ($data as &$dt) {
            $files = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_file_dinh_kem')->result_array();
            foreach ($files as $key => &$file) {
                $file['duong_dan'] = encryptString($file['duong_dan']);
            }
            $dt['files'] = $files;

            // $dt['files'] = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_file_dinh_kem')->result_array();

            //Xem danh sách đơn vị xử lý và phối hợp
            $xuly = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_xu_ly')->row_array();
            $donvixuly = [];
            if ($xuly) {
                $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
                $donvixuly = $this->db
                    ->join('e_don_vi', 'e_don_vi_xu_ly.id_don_vi = e_don_vi.id_don_vi')
                    ->where('e_don_vi_xu_ly.id_xu_ly', $xuly['id_xu_ly'])
                    ->select('e_don_vi_xu_ly.id_don_vi_xu_ly, e_don_vi_xu_ly.don_vi_xu_ly_chinh, e_don_vi_xu_ly.da_xem, e_don_vi.id_don_vi, e_don_vi.ten_don_vi, e_don_vi.ma_don_vi')
                    ->group_by('e_don_vi_xu_ly.id_don_vi_xu_ly')
                    ->get('e_don_vi_xu_ly')
                    ->result_array();
                $donvixulyIds = array_unique(array_column($donvixuly, 'id_don_vi_xu_ly'));
                if (!empty($donvixulyIds)) {
                    $donvixulydaxem = $this->db
                        ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_don_vi_xu_ly_da_xem.ql_nguoi_dung_id')
                        ->where_in('e_don_vi_xu_ly_da_xem.id_don_vi_xu_ly', $donvixulyIds)
                        ->select('e_don_vi_xu_ly_da_xem.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten, ql_nguoi_dung.ql_nguoi_dung_email, ql_nguoi_dung.ql_nguoi_dung_avatar')
                        ->get('e_don_vi_xu_ly_da_xem')
                        ->result_array();
                    foreach ($donvixuly as &$dvxl) {
                        $dvxl['danh_sach_da_xem'] = array_filter($donvixulydaxem, function ($item) use ($dvxl) {
                            return $item['id_don_vi_xu_ly'] == $dvxl['id_don_vi_xu_ly'];
                        });
                    }
                }
            }
            $dt['don_vi_xu_ly'] = $donvixuly;
        }

        // Văn bản trên 5 ngày
        $tren5ngay = $this->db->from('e_van_ban')
            ->where("thoi_gian_xu_ly > ", $fiveDaysLater)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
            ->where_in('trang_thai', [
                $this->common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value'],
                $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
                $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value']
            ])
            ->where('deleted_at IS NULL')
            ->get()
            ->num_rows();

        // Văn bản trong vòng 5 ngày
        $duoi5ngay = $this->db->from('e_van_ban')
            ->where("thoi_gian_xu_ly <= ", $fiveDaysLater)
            ->where("thoi_gian_xu_ly > ", $now)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
            ->where_in('trang_thai', [
                $this->common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value'],
                $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
                $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value']
            ])
            ->where('deleted_at IS NULL')
            ->get()
            ->num_rows();

        // Văn bản của ngày hôm nay
        $homnay = $this->db->from('e_van_ban')
            ->where("DATE(thoi_gian_xu_ly)", $now)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
            ->where_in('trang_thai', [
                $this->common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value'],
                $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
                $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value']
            ])
            ->where('deleted_at IS NULL')
            ->get()
            ->num_rows();

        // Văn bản quá hạn
        $quahan = $this->db->from('e_van_ban')
            ->where("thoi_gian_xu_ly < ", $now)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
            ->where_in('trang_thai', [
                $this->common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value'],
                $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
                $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value']
            ])
            ->where('deleted_at IS NULL')
            ->get()
            ->num_rows();

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'tren5ngay' => $tren5ngay,
            'duoi5ngay' => $duoi5ngay,
            'homnay' => $homnay,
            'quahan' => $quahan,
            'sql' => $sql,
        ];
    }

    public function getAllBaoCaoPhanHoi($start = 0, $length = 10, $searchValue = null, $orderBy = [], $columns = [], $searchKey = array(), $fromDate = null, $toDate = null)
    {
        $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
        $now = date('Y-m-d');
        $fiveDaysLater = date('Y-m-d', strtotime('5 days'));

        $this->db->from('e_van_ban')
            ->select('e_van_ban.*, 
                        e_tinh_chat.ten_tinh_chat, 
                        e_loai.ten_loai, 
                        e_khoi_co_quan.ten_khoi_co_quan, 
                        e_co_quan.ten_co_quan,
                        ')
            ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
            ->join('e_loai', 'e_loai.id_loai = e_van_ban.id_loai', 'left')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
            ->join('e_bao_cao', 'e_van_ban.id_van_ban = e_bao_cao.id_van_ban', 'left')
            ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'left');



        $this->db->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DEN)
            ->group_by('e_van_ban.id_van_ban');
        $this->db->where('e_van_ban.deleted_at IS NULL');

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        // $this->db->group_start();
        // $this->db->where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value']);
        // $this->db->or_where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value']);
        // $this->db->or_where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DEN['CHUA_PHAN_HOI']['value']);
        // $this->db->or_where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DEN['DA_PHAN_HOI']['value']);
        // $this->db->group_end();
        $this->db->where_in('e_van_ban.trang_thai', [
            $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
            $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value'],
            $this->common::STATUS_VAN_BAN_DEN['CHUA_PHAN_HOI']['value'],
            $this->common::STATUS_VAN_BAN_DEN['DA_PHAN_HOI']['value']
        ]);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('e_van_ban.ten_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.so_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.so_hieu_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.trich_yeu', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            $arr = ['so_van_ban', 'trich_yeu'];
            foreach ($searchKey as $key => $value) {
                if ($key == 'thoi_han') {
                    switch ($value) {
                        case 'tren_5_ngay':
                            $this->db->where("e_van_ban.thoi_gian_xu_ly > ", $fiveDaysLater)
                                ->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL');
                            break;
                        case 'duoi_5_ngay':
                            $this->db->where("e_van_ban.thoi_gian_xu_ly <= ", $fiveDaysLater)
                                ->where("e_van_ban.thoi_gian_xu_ly > ", $now)
                                ->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL');
                            break;
                        case 'hom_nay':
                            $this->db->where("DATE(e_van_ban.thoi_gian_xu_ly)", $now)
                                ->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL');
                            break;
                        case 'qua_han':
                            $this->db->where("e_van_ban.thoi_gian_xu_ly < ", $now)
                                ->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL');
                            break;
                    }
                } else if (in_array($key, $arr)) {
                    $this->db->like('e_van_ban.' . $key, $value);
                } else {
                    if (is_array($value)) {
                        $this->db->where_in('e_van_ban.' . $key, $value);
                    } else {
                        $this->db->where('e_van_ban.' . $key, $value);
                    }
                }
            }
        }

        if ($fromDate && $toDate) {
            $this->db->where("ngay_nhan BETWEEN '{$fromDate}' AND '{$toDate}'");
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query

        if (!empty($columns)) {
            foreach ($columns as $key => $column) {
                if ($column['search']['value'] != '') {
                    $columnName = $column['data'];
                    $searchColumn = $column['search']['value'];

                    if (in_array($columnName, ['ten_khoi_co_quan'])) {
                        // e_khoi_co_quan
                        $this->db->like('e_khoi_co_quan.' . $columnName, $searchColumn);
                    } else if (in_array($columnName, ['ten_co_quan'])) {
                        // e_co_quan
                        $this->db->like('e_co_quan.' . $columnName, $searchColumn);
                    } else if (in_array($columnName, ['ten_loai'])) {
                        // e_loai 
                        $this->db->like('e_loai.' . $columnName, $searchColumn);
                    } else if (in_array($columnName, ['ten_tinh_chat'])) {
                        // e_tinh_chat
                        $this->db->like('e_tinh_chat.' . $columnName, $searchColumn);
                    } else {
                        // e_van_ban 
                        if ($columnName == 'trang_thai') {
                            $searchColumn =  $this->getTrangThaiValueFromLabel($searchColumn);
                            $this->db->like('e_van_ban.' . $columnName, $searchColumn);
                        }
                        $this->db->like('e_van_ban.' . $columnName, $searchColumn);
                    }
                }
            }
        }

        // Xử lý order sau khi xử lý search
        if (!empty($orderBy)) {
            foreach ($orderBy as $order) {
                // Giả sử $order có 'column' và 'dir'
                $colIndex = $order['column'];
                $dir = $order['dir'];

                if (isset($columns[$colIndex])) {
                    $colName = $columns[$colIndex]['data'];
                    $this->db->order_by('e_van_ban.' . $colName, $dir);
                }
            }
        }

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }


        $query = $this->db->get();
        $data = $query->result_array();
        $sql = $this->db->last_query();

        foreach ($data as &$dt) {
            $files = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_file_dinh_kem')->result_array();
            foreach ($files as $key => &$file) {
                $file['duong_dan'] = encryptString($file['duong_dan']);
            }
            $dt['files'] = $files;

            // $dt['files'] = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_file_dinh_kem')->result_array();
            $baocao = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_bao_cao')->result_array();
            if (!empty($baocao)) {
                $donviIds = array_column($baocao, 'id_don_vi_phan_hoi');
                $dv = $this->db->from('e_don_vi')
                    ->join('e_bao_cao', 'e_bao_cao.id_don_vi_phan_hoi = e_don_vi.id_don_vi', 'left')
                    ->where_in('id_don_vi', $donviIds)
                    ->select('e_don_vi.*, e_bao_cao.noi_dung, e_bao_cao.ngay_bao_cao, e_bao_cao.dinh_kem, e_bao_cao.id_bao_cao')
                    ->where('e_bao_cao.id_van_ban', $dt['id_van_ban'])
                    // ->group_by('e_don_vi.id_don_vi')
                    ->get()
                    ->result_array();
                // $dv = $this->db->where_in('id_don_vi', $donviIds)->get('e_don_vi')->result_array();
                $dt['don_vi_da_phan_hoi'] = $dv;
                // $dt['don_vi_da_phan_hoi'] = $this->db->where_in('id_don_vi', $donviIds)->get('e_don_vi')->result_array();
            } else {
                $dt['don_vi_da_phan_hoi'] = [];
            }

            $xuly = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_xu_ly')->row_array();
            if ($xuly) {
                // $donvixuly = $this->db->where('id_xu_ly', $xuly['id_xu_ly'])->where('don_vi_xu_ly_chinh IS NOT NULL')->get('e_don_vi_xu_ly')->result_array();
                $donvixuly = $this->db
                    ->join('e_don_vi', 'e_don_vi_xu_ly.id_don_vi = e_don_vi.id_don_vi')
                    ->where('e_don_vi_xu_ly.id_xu_ly', $xuly['id_xu_ly'])
                    ->where('don_vi_xu_ly_chinh IS NOT NULL')
                    ->select('e_don_vi_xu_ly.id_don_vi_xu_ly, e_don_vi_xu_ly.don_vi_xu_ly_chinh, e_don_vi_xu_ly.da_xem, e_don_vi.id_don_vi, e_don_vi.ten_don_vi, e_don_vi.ma_don_vi')
                    ->group_by('e_don_vi_xu_ly.id_don_vi_xu_ly')
                    ->get('e_don_vi_xu_ly')
                    ->result_array();
                if (!empty($donvixuly)) {
                    $donvixulyIds = array_column($donvixuly, 'id_don_vi');
                    // $dt['don_vi_xu_ly'] = $this->db->where_in('id_don_vi', $donvixulyIds)->get('e_don_vi')->result_array();
                    $dt['don_vi_xu_ly'] =  $donvixuly;
                } else {
                    $dt['don_vi_xu_ly'] = [];
                }

                // $donviphoihop = $this->db->where('id_xu_ly', $xuly['id_xu_ly'])->where('don_vi_xu_ly_chinh IS NULL')->get('e_don_vi_xu_ly')->result_array();
                $donviphoihop = $this->db
                    ->join('e_don_vi', 'e_don_vi_xu_ly.id_don_vi = e_don_vi.id_don_vi')
                    ->where('e_don_vi_xu_ly.id_xu_ly', $xuly['id_xu_ly'])
                    ->where('don_vi_xu_ly_chinh IS NULL')
                    ->select('e_don_vi_xu_ly.id_don_vi_xu_ly, e_don_vi_xu_ly.don_vi_xu_ly_chinh, e_don_vi_xu_ly.da_xem, e_don_vi.id_don_vi, e_don_vi.ten_don_vi, e_don_vi.ma_don_vi')
                    ->group_by('e_don_vi_xu_ly.id_don_vi_xu_ly')
                    ->get('e_don_vi_xu_ly')
                    ->result_array();
                if (!empty($donviphoihop)) {
                    $donviphoihopIds = array_column($donviphoihop, 'id_don_vi');
                    // $dt['don_vi_phoi_hop'] = $this->db->where_in('id_don_vi', $donviphoihopIds)->get('e_don_vi')->result_array();
                    $dt['don_vi_phoi_hop'] = $donviphoihop;
                } else {
                    $dt['don_vi_phoi_hop'] = [];
                }
            } else {
                $dt['don_vi_xu_ly'] = [];
                $dt['don_vi_phoi_hop'] = [];
            }
        }

        // Văn bản trên 5 ngày
        $tren5ngay = $this->db->from('e_van_ban')
            ->where("thoi_gian_xu_ly > ", $fiveDaysLater)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->group_start()
            ->group_start()
            ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
            ->where_in('trang_thai', [
                $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
                $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value'],
                $this->common::STATUS_VAN_BAN_DEN['CHUA_PHAN_HOI']['value'],
                $this->common::STATUS_VAN_BAN_DEN['DA_PHAN_HOI']['value']
            ])
            ->group_end()
            ->or_group_start()
            ->where('loai_van_ban', $this->common::VAN_BAN_DI)
            ->where_in('trang_thai', [
                $this->common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value'],
                $this->common::STATUS_VAN_BAN_DI['DA_BAN_HANH']['value'],
                $this->common::STATUS_VAN_BAN_DI['DA_PHAN_HOI']['value']
            ])
            ->group_end()
            ->group_end()
            ->where('deleted_at IS NULL')
            ->get()
            ->num_rows();

        // Văn bản trong vòng 5 ngày
        $duoi5ngay = $this->db->from('e_van_ban')
            ->where("thoi_gian_xu_ly <= ", $fiveDaysLater)
            ->where("thoi_gian_xu_ly >= ", $now)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->group_start()
            ->group_start()
            ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
            ->where_in('trang_thai', [
                $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
                $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value'],
                $this->common::STATUS_VAN_BAN_DEN['CHUA_PHAN_HOI']['value'],
                $this->common::STATUS_VAN_BAN_DEN['DA_PHAN_HOI']['value']
            ])
            ->group_end()
            ->or_group_start()
            ->where('loai_van_ban', $this->common::VAN_BAN_DI)
            ->where_in('trang_thai', [
                $this->common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value'],
                $this->common::STATUS_VAN_BAN_DI['DA_BAN_HANH']['value'],
                $this->common::STATUS_VAN_BAN_DI['DA_PHAN_HOI']['value']
            ])
            ->group_end()
            ->group_end()
            ->where('deleted_at IS NULL')
            ->get()
            ->num_rows();

        // Văn bản của ngày hôm nay
        $homnay = $this->db->from('e_van_ban')
            ->where("DATE(thoi_gian_xu_ly)", $now)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->group_start()
            ->group_start()
            ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
            ->where_in('trang_thai', [
                $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
                $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value'],
                $this->common::STATUS_VAN_BAN_DEN['CHUA_PHAN_HOI']['value'],
                $this->common::STATUS_VAN_BAN_DEN['DA_PHAN_HOI']['value']
            ])
            ->group_end()
            ->or_group_start()
            ->where('loai_van_ban', $this->common::VAN_BAN_DI)
            ->where_in('trang_thai', [
                $this->common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value'],
                $this->common::STATUS_VAN_BAN_DI['DA_BAN_HANH']['value'],
                $this->common::STATUS_VAN_BAN_DI['DA_PHAN_HOI']['value']
            ])
            ->group_end()
            ->group_end()
            ->where('deleted_at IS NULL')
            ->get()
            ->num_rows();

        // Văn bản quá hạn
        $quahan = $this->db->from('e_van_ban')
            ->where("thoi_gian_xu_ly < ", $now)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->group_start()
            ->group_start()
            ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
            ->where_in('trang_thai', [
                $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
                $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value'],
                $this->common::STATUS_VAN_BAN_DEN['CHUA_PHAN_HOI']['value'],
                $this->common::STATUS_VAN_BAN_DEN['DA_PHAN_HOI']['value']
            ])
            ->group_end()
            ->or_group_start()
            ->where('loai_van_ban', $this->common::VAN_BAN_DI)
            ->where_in('trang_thai', [
                $this->common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value'],
                $this->common::STATUS_VAN_BAN_DI['DA_BAN_HANH']['value'],
                $this->common::STATUS_VAN_BAN_DI['DA_PHAN_HOI']['value']
            ])
            ->group_end()
            ->group_end()
            ->where('deleted_at IS NULL')
            ->get()
            ->num_rows();

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'tren5ngay' => $tren5ngay,
            'duoi5ngay' => $duoi5ngay,
            'homnay' => $homnay,
            'quahan' => $quahan,
            'sql' => $sql
        ];
    }

    public function toChucHanhChinhXemBaoCaoPhanHoi($baocaoId, $auth)
    {
        $baocao = $this->db->where('id_bao_cao', $baocaoId)->get('e_bao_cao')->row_array();
        if ($baocao) {
            $baocaodaxem = $this->db->where('id_bao_cao', $baocaoId)->get('e_bao_cao_da_xem')->row_array();
            if (!$baocaodaxem) {
                $this->db->insert(
                    'e_bao_cao_da_xem',
                    [
                        'id_bao_cao' => $baocaoId,
                        'nguoi_xem' => $auth['ql_nguoi_dung_id'],
                        'ngay_xem' => date('Y-m-d H:i:s'),
                        'id_van_ban' => $baocao['id_van_ban']
                    ]
                );
            }
        }
    }

    public function getAllBaoCaoPhanHoiTheoDonvi1($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array(), $fromDate = null, $toDate = null, $auth)
    {
        // dd($auth);
        $this->db->from('e_van_ban')
            ->select('e_van_ban.*, 
                        e_tinh_chat.ten_tinh_chat, 
                        e_loai.ten_loai, 
                        e_khoi_co_quan.ten_khoi_co_quan, 
                        e_co_quan.ten_co_quan,
                        GROUP_CONCAT(e_don_vi_xu_ly.id_nguoi_xu_ly) AS id_nguoi_xu_ly,
                        nguoi_tao.ql_nguoi_dung_ho_ten AS ten_nguoi_tao,
                        nguoi_tao.ql_nguoi_dung_email AS email_nguoi_tao,
                        GROUP_CONCAT(nguoi_xu_ly.ql_nguoi_dung_ho_ten) AS ten_nguoi_xu_ly
                        ')
            ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
            ->join('e_loai', 'e_loai.id_loai = e_van_ban.id_loai', 'left')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
            ->join('e_bao_cao', 'e_van_ban.id_van_ban = e_bao_cao.id_van_ban', 'left')
            ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'left')
            ->join('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly', 'left')
            ->join('ql_nguoi_dung AS nguoi_tao', 'nguoi_tao.ql_nguoi_dung_id = e_van_ban.id_nguoi_tao', 'left')
            ->join('ql_nguoi_dung AS nguoi_xu_ly', 'nguoi_xu_ly.ql_nguoi_dung_id = e_don_vi_xu_ly.id_nguoi_xu_ly', 'left')
            ->where('e_don_vi_xu_ly.id_don_vi', $auth['id_don_vi']);

        $this->db->group_start();

        $this->db->group_start();
        $this->db->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DEN);
        $this->db->where_in('e_van_ban.trang_thai', [
            $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
            $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value'],
            $this->common::STATUS_VAN_BAN_DEN['CHUA_PHAN_HOI']['value'],
            $this->common::STATUS_VAN_BAN_DEN['DA_PHAN_HOI']['value']
        ]);
        $this->db->group_end();

        $this->db->or_group_start();
        $this->db->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DI);
        $this->db->where_in('e_van_ban.trang_thai', [
            $this->common::STATUS_VAN_BAN_DI['DA_BAN_HANH']['value'],
            $this->common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value'],
            $this->common::STATUS_VAN_BAN_DI['DA_PHAN_HOI']['value'],
            $this->common::STATUS_VAN_BAN_DI['CHUA_PHAN_HOI']['value'],
        ]);
        $this->db->group_end();
        $this->db->group_end();

        $this->db->group_by('e_van_ban.id_van_ban');
        $this->db->where('e_van_ban.deleted_at IS NULL');

        $totalRecordsQuery = clone $this->db;

        $totalRecordsTren5ngay = clone $this->db;
        $totalRecordsDuoi5ngay = clone $this->db;
        $totalRecordsHomnay = clone $this->db;
        $totalRecordsQuahan = clone $this->db;


        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        $now = date('Y-m-d');
        $fiveDaysLater = date('Y-m-d', strtotime('5 days'));

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('e_van_ban.ten_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.so_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.so_hieu_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.trich_yeu', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            if (!empty($searchKey['ngay_tao_tu']) && !empty($searchKey['ngay_tao_den'])) {
                $from = $searchKey['ngay_tao_tu'];
                $to   = $searchKey['ngay_tao_den'];
                $this->db->where("DATE(e_van_ban.ngay_tao) BETWEEN '{$from}' AND '{$to}'");
            }

            if (!empty($searchKey['ngay_ban_hanh_tu']) && !empty($searchKey['ngay_ban_hanh_den'])) {
                $from = $searchKey['ngay_ban_hanh_tu'];
                $to   = $searchKey['ngay_ban_hanh_den'];
                $this->db->where("DATE(e_van_ban.ngay_ban_hanh) BETWEEN '{$from}' AND '{$to}'");
            }

            if (!empty($searchKey['ngay_nhan_tu']) && !empty($searchKey['ngay_nhan_den'])) {
                $from = $searchKey['ngay_nhan_tu'];
                $to   = $searchKey['ngay_nhan_den'];
                $this->db->where("DATE(e_van_ban.ngay_nhan) BETWEEN '{$from}' AND '{$to}'");
            }

            foreach ($searchKey as $key => $value) {
                if (!empty($value)) {
                    switch ($key) {
                        case 'so_van_ban':
                            $this->db->like('e_van_ban.so_van_ban', $value);
                            break;
                        case 'trich_yeu':
                            $this->db->like('e_van_ban.trich_yeu', $value);
                            break;
                        case 'loai_van_ban':
                        case 'id_loai':
                            $this->db->where('e_van_ban.id_loai', $value);
                            break;
                        case 'so_hieu_van_ban':
                            $this->db->like('e_van_ban.so_hieu_van_ban', $value);
                            break;
                        case 'year':
                            $this->db->where('YEAR(e_van_ban.ngay_tao)', $value);
                            break;
                        case 'id_don_vi_xu_ly':
                            $this->db->join('e_xu_ly as xl_filter', 'e_van_ban.id_van_ban = xl_filter.id_van_ban', 'left');
                            $this->db->join('e_don_vi_xu_ly as dvxl_filter', 'dvxl_filter.id_xu_ly = xl_filter.id_xu_ly', 'left');
                            $this->db->where('dvxl_filter.id_don_vi', $value);
                            $this->db->group_by('e_van_ban.id_van_ban');
                            break;
                        case 'selectedClassify':
                            switch ($value) {
                                case 'van_ban_den':
                                    $this->db->where('e_van_ban.loai_van_ban', 1);
                                    break;
                                case 'van_ban_di':
                                    $this->db->where('e_van_ban.loai_van_ban', 2);
                                    break;
                                case 'van_ban_noi_bo':
                                    $this->db->where('e_van_ban.loai_van_ban', 3);
                                    break;
                                case 'all':
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

        if ($fromDate && $toDate) {
            $this->db->where("e_don_vi_xu_ly.ngay_tao BETWEEN '{$fromDate}' AND '{$toDate}'");
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
            $order = $orderBy['order'];
            $orderColumnIndex = $order[0]['column'];
            $orderDir = $order[0]['dir'];

            $columns = $orderBy['columns'];
            $filed = $columns[$orderColumnIndex]['data'];

            if (!empty($orderDir) && $filed == 'so_van_ban') {
                $this->db->order_by('e_van_ban.so_van_ban', $orderDir);
                $this->db->order_by('e_van_ban.so_van_ban_hau_to', $orderDir);
            } else {
                $this->db->order_by('e_van_ban.' . $filed, $orderDir);
            }
        }



        if ($length != '-1') {
            $this->db->limit($length, $start);
        }


        $query = $this->db->get();
        $data = $query->result_array();
        $sql = $this->db->last_query();

        foreach ($data as &$dt) {

            if ($dt['loai_van_ban'] == $this->common::VAN_BAN_DI) {
                $files = $this->db
                    ->where('id_van_ban', $dt['id_van_ban'])
                    ->where('la_file_ban_hanh', 1)
                    ->get('e_file_dinh_kem')
                    ->result_array();
            } else {
                $files = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_file_dinh_kem')->result_array();
            }

            foreach ($files as $key => &$file) {
                $file['duong_dan'] = encryptString($file['duong_dan']);
            }
            $dt['files'] = $files;

            // $dt['files'] = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_file_dinh_kem')->result_array();
            $baocao = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_bao_cao')->result_array();
            if (!empty($baocao)) {
                $donviIds = array_column($baocao, 'id_don_vi_phan_hoi');
                $dv = $this->db->from('e_don_vi')
                    ->join('e_bao_cao', 'e_bao_cao.id_don_vi_phan_hoi = e_don_vi.id_don_vi', 'left')
                    ->where_in('id_don_vi', $donviIds)
                    ->select('e_don_vi.*, e_bao_cao.noi_dung, e_bao_cao.ngay_bao_cao, e_bao_cao.dinh_kem')
                    // ->group_by('e_don_vi.id_don_vi')
                    ->where('e_bao_cao.id_van_ban', $dt['id_van_ban'])
                    ->get()
                    ->result_array();
                // $dv = $this->db->where_in('id_don_vi', $donviIds)->get('e_don_vi')->result_array();
                $dt['don_vi_da_phan_hoi'] = $dv;
                // $dt['don_vi_da_phan_hoi'] = $this->db->where_in('id_don_vi', $donviIds)->get('e_don_vi')->result_array();

            } else {
                $dt['don_vi_da_phan_hoi'] = [];
            }

            $xuly = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_xu_ly')->row_array();
            if ($xuly) {
                $donvixuly = $this->db->where('id_xu_ly', $xuly['id_xu_ly'])->where('don_vi_xu_ly_chinh IS NOT NULL')->get('e_don_vi_xu_ly')->result_array();
                $donviphoihop = $this->db->where('id_xu_ly', $xuly['id_xu_ly'])->where('don_vi_xu_ly_chinh IS NULL')->get('e_don_vi_xu_ly')->result_array();
                if (!empty($donvixuly)) {
                    // $donvixulyIds = array_column($donvixuly, 'id_don_vi');
                    // $dt['don_vi_xu_ly'] = $this->db->where_in('id_don_vi', $donvixulyIds)->get('e_don_vi')->result_array();

                    $dv_chinh_ids = []; // Theo đơn vị
                    $dv_chinh_ds = [];
                    foreach ($donvixuly as $key => &$dvxlc) {
                        if (!in_array($dvxlc['id_don_vi'], $dv_chinh_ids)) {
                            $dv_chinh_ids[] = $dvxlc['id_don_vi'];
                            $dv_chinh_ds[] = [
                                'id_xu_ly' => $dvxlc['id_xu_ly'],
                                'id_don_vi' => $dvxlc['id_don_vi'],
                                'ten_don_vi' => $dvxlc['ten_don_vi'],
                                'ma_don_vi' => $dvxlc['ma_don_vi'],
                                'don_vi_xu_ly_chinh' => $dvxlc['don_vi_xu_ly_chinh'],
                                'loai' => $dvxlc['loai'],
                                'email' => $dvxlc['email'],
                                'ngay_tao' => $dvxlc['ngay_tao'],
                                'da_xem' => $dvxlc['da_xem'],
                                'nguoi_xu_ly_ids' => [$dvxlc['id_nguoi_xu_ly']],
                                'don_vi_xu_ly_ids' => [$dvxlc['id_don_vi_xu_ly']],
                            ];
                        } else if (in_array($dvxlc['id_don_vi'], $dv_chinh_ids)) {
                            foreach ($dv_chinh_ds as &$dv) {
                                if ($dv['id_don_vi'] == $dvxlc['id_don_vi']) {
                                    $dv['nguoi_xu_ly_ids'][] = $dvxlc['id_nguoi_xu_ly'];
                                    $dv['don_vi_xu_ly_ids'][] = $dvxlc['id_don_vi_xu_ly'];

                                    if ($dvxlc['da_xem']) {
                                        $dv['da_xem'] = true;
                                    }
                                    break;
                                }
                            }
                        }
                    }

                    foreach ($dv_chinh_ds as $key => &$dv_chinh) {
                        $dv_chinh['nguoi_xem'] = $this->db
                            ->select('e_don_vi_xu_ly_da_xem.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten, ql_nguoi_dung.ql_nguoi_dung_email')
                            ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_don_vi_xu_ly_da_xem.ql_nguoi_dung_id', 'left')
                            ->where_in('id_don_vi_xu_ly', $dv_chinh['don_vi_xu_ly_ids'])
                            ->get('e_don_vi_xu_ly_da_xem')
                            ->result_array();
                        $dv_chinh['so_nguoi_xem'] = count($dv_chinh['nguoi_xem']);
                    }

                    $xuly['don_vi_xu_ly'] = $dv_chinh_ds;
                } else {
                    $dt['don_vi_xu_ly'] = [];
                }

                if (!empty($donviphoihop)) {
                    // $donviphoihopIds = array_column($donviphoihop, 'id_don_vi');
                    // $dt['don_vi_phoi_hop'] = $this->db->where_in('id_don_vi', $donviphoihopIds)->get('e_don_vi')->result_array();

                    $dv_phoi_hop_ids = []; // Theo đơn vị
                    $dv_phoi_dop_ds = [];
                    foreach ($donviphoihop as $key => &$dvxlpp) {
                        if (!in_array($dvxlpp['id_don_vi'], $dv_phoi_hop_ids)) {
                            $dv_phoi_hop_ids[] = $dvxlpp['id_don_vi'];
                            $dv_phoi_dop_ds[] = [
                                'id_xu_ly' => $dvxlpp['id_xu_ly'],
                                'id_don_vi' => $dvxlpp['id_don_vi'],
                                'ten_don_vi' => $dvxlpp['ten_don_vi'],
                                'ma_don_vi' => $dvxlpp['ma_don_vi'],
                                'don_vi_xu_ly_chinh' => $dvxlpp['don_vi_xu_ly_chinh'],
                                'loai' => $dvxlpp['loai'],
                                'email' => $dvxlpp['email'],
                                'ngay_tao' => $dvxlpp['ngay_tao'],
                                'da_xem' => $dvxlpp['da_xem'],
                                'nguoi_xu_ly_ids' => [$dvxlpp['id_nguoi_xu_ly']],
                                'don_vi_xu_ly_ids' => [$dvxlpp['id_don_vi_xu_ly']],
                            ];
                        } else if (in_array($dvxlpp['id_don_vi'], $dv_phoi_hop_ids)) {
                            foreach ($dv_phoi_dop_ds as &$dv) {
                                if ($dv['id_don_vi'] == $dvxlpp['id_don_vi']) {
                                    $dv['nguoi_xu_ly_ids'][] = $dvxlpp['id_nguoi_xu_ly'];
                                    $dv['don_vi_xu_ly_ids'][] = $dvxlpp['id_don_vi_xu_ly'];

                                    if ($dvxlpp['da_xem']) {
                                        $dv['da_xem'] = true;
                                    }
                                    break;
                                }
                            }
                        }
                    }

                    foreach ($dv_phoi_dop_ds as $key => &$dv_phoi_dop) {
                        $dv_phoi_dop['nguoi_xem'] = $this->db
                            ->select('e_don_vi_xu_ly_da_xem.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten, ql_nguoi_dung.ql_nguoi_dung_email')
                            ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_don_vi_xu_ly_da_xem.ql_nguoi_dung_id', 'left')
                            ->where_in('id_don_vi_xu_ly', $dv_phoi_dop['don_vi_xu_ly_ids'])
                            ->get('e_don_vi_xu_ly_da_xem')
                            ->result_array();
                        $dv_phoi_dop['so_nguoi_xem'] = count($dv_phoi_dop['nguoi_xem']);
                    }

                    $xuly['don_vi_phoi_hop'] = $dv_phoi_dop_ds;
                } else {
                    $dt['don_vi_phoi_hop'] = [];
                }
            } else {
                $dt['don_vi_xu_ly'] = [];
                $dt['don_vi_phoi_hop'] = [];
            }

            //Đã xem
            $dt['da_xem'] = 0;
            foreach ($donvixuly as $dvxl) {
                if ($dvxl['id_don_vi'] == $auth['id_don_vi']) {
                    if ($dvxl['da_xem']) {
                        $dt['da_xem'] = 1;
                        break;
                    }
                }
            }
            if (!$dt['da_xem']) {
                foreach ($donviphoihop as $dvph) {
                    if ($dvph['id_don_vi'] == $auth['id_don_vi']) {
                        if ($dvph['da_xem']) {
                            $dt['da_xem'] = 1;
                            break;
                        }
                    }
                }
            }
        }

        // Văn bản trên 5 ngày
        $tren5ngay = $totalRecordsTren5ngay
            ->where("e_van_ban.thoi_gian_xu_ly > ", $fiveDaysLater)
            ->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL')
            ->count_all_results('', FALSE);

        // Văn bản dưới 5 ngày
        $duoi5ngay = $totalRecordsDuoi5ngay
            ->where("thoi_gian_xu_ly <= ", $fiveDaysLater)
            ->where("thoi_gian_xu_ly > ", $now)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->count_all_results('', FALSE);

        // Văn bản hom nay
        $homnay = $totalRecordsHomnay
            ->where("DATE(thoi_gian_xu_ly)", $now)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->count_all_results('', FALSE);

        //Văn bản quá hạn
        $quahan = $totalRecordsQuahan
            ->where("thoi_gian_xu_ly < ", $now)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->count_all_results('', FALSE);

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'sql' => $sql,
            'thoi_han' => [
                'tren_5_ngay' => $tren5ngay,
                'duoi_5_ngay' => $duoi5ngay,
                'hom_nay' => $homnay,
                'qua_han' => $quahan,
            ]
        ];
    }

    // Danh sách văn bản đến của đơn vị
    public function getAllBaoCaoPhanHoiTheoDonvi($start = 0, $length = 10, $searchValue = null, $orderBy = [], $columns = [], $searchKey = array(), $fromDate = null, $toDate = null, $auth, $dataSource = [])
    {
        $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
        $now = date('Y-m-d');
        $fiveDaysLater = date('Y-m-d', strtotime('5 days'));
        // trạng thái văn bản hoàn thành
        $trang_thai_hoan_thanh =  [Common::STATUS_VAN_BAN_DEN['HOAN_THANH']['value'], Common::STATUS_VAN_BAN_DI['HOAN_THANH']['value']];
        // trạng thái hợp lệ
        $trang_thai_hop_le = [Common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value'], Common::STATUS_VAN_BAN_DI['DA_BAN_HANH']['value'], Common::STATUS_VAN_BAN_DI['HOAN_THANH']['value']];

        $this->db->select('
                        e_van_ban.id_van_ban,
                        e_van_ban.so_van_ban,
                        e_van_ban.so_van_ban_hau_to,
                        e_van_ban.so_hieu_van_ban,
                        e_van_ban.loai_van_ban,
                        e_loai.id_loai,
                        e_loai.ten_loai,
                        e_van_ban.trich_yeu,
                        e_van_ban.ngay_nhan,
                        e_van_ban.ngay_ban_hanh,
                        DATE_FORMAT(e_van_ban.thoi_gian_xu_ly, "%Y-%m-%d")  AS thoi_gian_xu_ly,
                        e_van_ban.trang_thai,
                        e_khoi_co_quan.id_khoi_co_quan,
                        e_khoi_co_quan.ten_khoi_co_quan,
                        e_co_quan.id_co_quan,
                        e_co_quan.ten_co_quan,
                        e_hinh_thuc.id_hinh_thuc,
                        e_hinh_thuc.ten_hinh_thuc,
                        e_van_ban.linh_vuc,
                        e_tinh_chat.id_tinh_chat,
                        e_tinh_chat.ten_tinh_chat,
                        e_bao_mat.id_bao_mat,
                        e_bao_mat.ten_bao_mat,
                        e_don_vi.id_don_vi,
                        e_don_vi.ten_don_vi ho_so_don_vi,
                        e_van_ban.noi_luu_tru,
                        e_van_ban.trang_thai_huy_vb,
                        e_van_ban.luu_tru_noi_bo,
                        e_van_ban.nguoi_ky,
                        e_van_ban.ngay_ky,
                        e_van_ban.van_ban_chi_doc,
                        e_van_ban.ngay_tao,
                        e_van_ban.ngay_sua,
                        e_van_ban.deleted_at,
                        e_but_phe.id_nguoi_but_phe,
                        nguoi_butphe.ql_nguoi_dung_ho_ten AS nguoi_but_phe,
                        e_but_phe.ngay_but_phe,
                        e_but_phe.noi_dung_but_phe,
                        e_van_ban.id_nguoi_tao,
                        nguoi_tao.ql_nguoi_dung_ho_ten AS ten_nguoi_tao,
                        nguoi_tao.ql_nguoi_dung_email AS email_nguoi_tao,
                        e_xu_ly.id_xu_ly,
                        e_xu_ly.nguoi_duyet,
                        e_xu_ly.ngay_duyet,
                        e_xu_ly.ghi_chu_duyet,
                        e_xu_ly.ngay_tao AS ngay_xu_ly,
                        e_xu_ly.nguoi_tao AS id_nguoi_chuyen_xu_ly,
                        nguoi_chuyen.ql_nguoi_dung_ho_ten AS ten_nguoi_chuyen_xu_ly,
                        nguoi_chuyen.ql_nguoi_dung_email AS email_nguoi_chuyen_xu_ly,
                        e_don_vi_xu_ly.da_xem,
                        e_don_vi_xu_ly.id_nguoi_xu_ly,
                        e_don_vi_xu_ly.ngay_tao AS ngay_giao_xu_ly,
                    ');
        $this->db->from('e_van_ban');
        $this->db->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'left');
        $this->db->join('ql_nguoi_dung AS nguoi_chuyen', 'e_xu_ly.nguoi_tao = nguoi_chuyen.ql_nguoi_dung_id', 'left');
        $this->db->join('e_don_vi_xu_ly', 'e_xu_ly.id_xu_ly = e_don_vi_xu_ly.id_xu_ly', 'left');
        $this->db->join('e_don_vi', 'e_don_vi_xu_ly.id_don_vi = e_don_vi.id_don_vi', 'left');
        $this->db->join('e_loai', 'e_van_ban.id_loai = e_loai.id_loai', 'left');
        $this->db->join('e_khoi_co_quan', 'e_van_ban.id_khoi_co_quan = e_khoi_co_quan.id_khoi_co_quan', 'left');
        $this->db->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left');
        $this->db->join('e_tinh_chat', 'e_van_ban.id_tinh_chat = e_tinh_chat.id_tinh_chat', 'left');
        $this->db->join('e_bao_mat', 'e_van_ban.id_bao_mat = e_bao_mat.id_bao_mat', 'left');
        $this->db->join('e_but_phe', 'e_van_ban.id_van_ban = e_but_phe.id_van_ban', 'left');
        $this->db->join('ql_nguoi_dung AS nguoi_butphe', 'e_but_phe.id_nguoi_but_phe = nguoi_butphe.ql_nguoi_dung_id', 'left');
        $this->db->join('e_hinh_thuc', 'e_van_ban.id_hinh_thuc = e_hinh_thuc.id_hinh_thuc', 'left');
        $this->db->join('ql_nguoi_dung AS nguoi_tao', 'e_van_ban.id_nguoi_tao = nguoi_tao.ql_nguoi_dung_id', 'left');

        $this->db->group_by('e_van_ban.id_van_ban');
        $this->db->where('e_van_ban.deleted_at IS NULL');
        $this->db->group_start();
        $this->db->where('e_don_vi_xu_ly.id_don_vi', $auth['id_don_vi']);
        $this->db->where('e_don_vi_xu_ly.id_nguoi_xu_ly', $auth['ql_nguoi_dung_id']);
        $this->db->group_end();

        if (!empty($searchKey['year']) && $searchKey['year'] !== 'all_years') {
            $this->db->where("YEAR(e_van_ban.ngay_nhan)", $searchKey['year']);
        }

        $totalRecordsQuery = clone $this->db;

        $totalRecordsTren5ngay = clone $this->db;
        $totalRecordsDuoi5ngay = clone $this->db;
        $totalRecordsHomnay = clone $this->db;
        $totalRecordsQuahan = clone $this->db;

        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue && !empty($searchValue)) {
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


        if (!empty($searchKey)) {
            if (!empty($searchKey['ngay_tao_tu']) && !empty($searchKey['ngay_tao_den'])) {
                $from = $searchKey['ngay_tao_tu'];
                $to   = $searchKey['ngay_tao_den'];
                $this->db->where("DATE(e_van_ban.ngay_tao) BETWEEN '{$from}' AND '{$to}'");
            }

            if (!empty($searchKey['ngay_ban_hanh_tu']) && !empty($searchKey['ngay_ban_hanh_den'])) {
                $from = $searchKey['ngay_ban_hanh_tu'];
                $to   = $searchKey['ngay_ban_hanh_den'];
                $this->db->where("DATE(e_van_ban.ngay_ban_hanh) BETWEEN '{$from}' AND '{$to}'");
            }

            if (!empty($searchKey['ngay_nhan_tu']) && !empty($searchKey['ngay_nhan_den'])) {
                $from = $searchKey['ngay_nhan_tu'];
                $to   = $searchKey['ngay_nhan_den'];
                $this->db->where("DATE(e_van_ban.ngay_nhan) BETWEEN '{$from}' AND '{$to}'");
            }

            if (!empty($searchKey['thoi_gian_xu_ly_tu']) && !empty($searchKey['thoi_gian_xu_ly_den'])) {
                $from = $searchKey['thoi_gian_xu_ly_tu'];
                $to   = $searchKey['thoi_gian_xu_ly_den'];
                $this->db->where("DATE(e_van_ban.thoi_gian_xu_ly) BETWEEN '{$from}' AND '{$to}'");
            }

            foreach ($searchKey as $key => $value) {
                if (!empty($value)) {
                    switch ($key) {
                        case 'so_van_ban':
                            $this->db->like('e_van_ban.so_van_ban', $value);
                            break;
                        case 'so_hieu_van_ban':
                            $this->db->like('e_van_ban.so_hieu_van_ban', $value);
                            break;
                        case 'trich_yeu':
                            $this->db->like('e_van_ban.trich_yeu', $value);
                            break;
                        case 'loai_van_ban':
                            $this->db->where('e_loai.id_loai', $value);
                            break;
                        case 'ids':
                            if (!empty($value)) {
                                if (is_array($value)) {
                                    $this->db->where_in('e_van_ban.id_van_ban', $value);
                                } else {
                                    // Phục hồi lại mảng ID từ chuỗi token Base64 do dùng TO_BASE64 trong n8n SQL
                                    $queryDec = $this->db->query("SELECT CAST(FROM_BASE64(?) AS CHAR) as dec_ids", [$value]);
                                    $rowDec = $queryDec->row_array();
                                    
                                    if ($rowDec && !empty($rowDec['dec_ids'])) {
                                        $arr_ids = explode(',', $rowDec['dec_ids']);
                                        $this->db->where_in('e_van_ban.id_van_ban', $arr_ids);
                                    } else {
                                        // Nếu mã hoá rác/sai thì không trả về kết quả
                                        $this->db->where('1=0');
                                    }
                                }
                            }
                            break;
                        case 'id_don_vi_xu_ly':
                            $this->db->where('e_don_vi_xu_ly.id_don_vi', $value);
                            break;
                        case 'year':
                            // Đã xử lý ở đầu khối searchKey
                            break;
                        case 'selectedClassify':
                            $excludeTrash = "NOT EXISTS (SELECT 1 FROM e_van_ban_da_xoa WHERE e_van_ban_da_xoa.id_van_ban = e_van_ban.id_van_ban AND e_van_ban_da_xoa.id_don_vi = " . $this->db->escape($auth['id_don_vi']) . ")";
                            switch ($value) {
                                case 'da_phan_hoi':
                                    $this->db->where("EXISTS (
                                        SELECT 1 FROM e_bao_cao 
                                        WHERE e_bao_cao.id_van_ban = e_van_ban.id_van_ban 
                                        AND e_bao_cao.id_don_vi_phan_hoi = " . (int)$auth['id_don_vi'] . "
                                    )", null, false);
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'chua_phan_hoi':
                                    $this->db->where("NOT EXISTS (
                                        SELECT 1 FROM e_bao_cao 
                                        WHERE e_bao_cao.id_van_ban = e_van_ban.id_van_ban 
                                        AND e_bao_cao.id_don_vi_phan_hoi = " . (int)$auth['id_don_vi'] . "
                                    )", null, false);
                                    $this->db->where($excludeTrash, null, false);
                                    break;

                                case 'all':
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'tiep_nhan':
                                    $this->db->where('e_van_ban.trang_thai', Common::STATUS_VAN_BAN_DEN['TIEP_NHAN']['value']);
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'cho_but_phe':
                                    $this->db->where('e_van_ban.trang_thai', Common::STATUS_VAN_BAN_DEN['CHO_LANH_DAO_BUT_PHE']['value']);
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'da_but_phe':
                                    $this->db->where('e_van_ban.trang_thai', Common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value']);
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'da_chuyen_don_vi_xu_ly':
                                    $this->db->where('e_van_ban.trang_thai', Common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value']);
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'da_xu_ly':
                                    $this->db->where('e_van_ban.trang_thai', Common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value']);
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'hoan_thanh':
                                    $this->db->where_in('e_van_ban.trang_thai', $trang_thai_hoan_thanh);
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'luu_tru':
                                    $this->db->where('e_van_ban.trang_thai', Common::STATUS_VAN_BAN_DEN['LUU_TRU']['value']);
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'thu_hoi':
                                    $this->db->where('e_van_ban.deleted_at IS NOT NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'tren_5_ngay':
                                    $this->db->where_not_in('e_van_ban.trang_thai', $trang_thai_hoan_thanh);
                                    $this->db->where("e_van_ban.thoi_gian_xu_ly > ", $fiveDaysLater)
                                        ->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'duoi_5_ngay':
                                    $this->db->where_not_in('e_van_ban.trang_thai', $trang_thai_hoan_thanh);
                                    $this->db->where("e_van_ban.thoi_gian_xu_ly <= ", $fiveDaysLater)
                                        ->where("e_van_ban.thoi_gian_xu_ly > ", $now)
                                        ->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'hom_nay':
                                    $this->db->where_not_in('e_van_ban.trang_thai', $trang_thai_hoan_thanh);
                                    $this->db->where("DATE(e_van_ban.thoi_gian_xu_ly)", $now)
                                        ->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'qua_han':
                                    $this->db->where_not_in('e_van_ban.trang_thai', $trang_thai_hoan_thanh);
                                    $this->db->where("e_van_ban.thoi_gian_xu_ly < ", $now)
                                        ->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL');
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'ca_nhan':
                                    $this->db->where('e_don_vi_xu_ly.id_nguoi_xu_ly', $auth['ql_nguoi_dung_id']);
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'chua_xem':
                                    $this->db->where('e_don_vi_xu_ly.da_xem', 0);
                                    $this->db->where('e_don_vi_xu_ly.id_nguoi_xu_ly', $auth['ql_nguoi_dung_id']);
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'da_xem':
                                    $this->db->where('e_don_vi_xu_ly.da_xem', 1);
                                    $this->db->where('e_don_vi_xu_ly.id_nguoi_xu_ly', $auth['ql_nguoi_dung_id']);
                                    $this->db->where($excludeTrash, null, false);
                                    break;
                                case 'nhan_hom_nay':
                                    $this->db->where('DATE(e_don_vi_xu_ly.ngay_tao)', $now);
                                    $this->db->where($excludeTrash, null, false);
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

        // if ($fromDate && $toDate) {
        //     $this->db->where("e_don_vi_xu_ly.ngay_tao BETWEEN '{$fromDate}' AND '{$toDate}'");
        // }
        if ($fromDate && $toDate) {
            $this->db->group_start();
            $this->db->where("e_don_vi_xu_ly.ngay_tao BETWEEN '{$fromDate} 00:00:00' AND '{$toDate} 23:59:59'");
            $this->db->or_where('e_don_vi_xu_ly.ngay_tao IS NULL');
            $this->db->group_end();
        }


        $filteredQuery      = clone $this->db;
        $recordsFiltered    = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query

        if (!empty($columns)) {
            // Map tên cột hiển thị (alias) sang cột thật trong DB
            $columnMapping = [
                'ten_khoi_co_quan' => 'e_khoi_co_quan.ten_khoi_co_quan',
                'ten_co_quan'      => 'e_co_quan.ten_co_quan',
                'ten_loai'         => 'e_loai.ten_loai',
                'ten_tinh_chat'    => 'e_tinh_chat.ten_tinh_chat',
                'nguoi_but_phe'    => 'ql_nguoi_dung.ql_nguoi_dung_ho_ten',
                'trang_thai'       => 'e_van_ban.trang_thai', // xử lý riêng
                // Mặc định nếu không map thì nó sẽ lấy e_van_ban.[columnName]
            ];
            $this->handleDatatableColumns($columns, $columnMapping);
        }

        // Xử lý order ưu tiên sortOrder từ popup filter
        if (!empty($searchKey['sortOrder'])) {
            if ($searchKey['sortOrder'] === 'newest') {
                $this->db->order_by('e_don_vi_xu_ly.ngay_tao', 'DESC');
            } else if ($searchKey['sortOrder'] === 'oldest') {
                $this->db->order_by('e_don_vi_xu_ly.ngay_tao', 'ASC');
            }
        } else if (!empty($orderBy)) {
            $this->handleDatatableOrdering($orderBy, $columns);
        } else if (!empty($dataSource['order'])) {
            $this->applyOrdering($dataSource['order']);
        } else {
            // Default order
            $this->db->order_by('e_don_vi_xu_ly.ngay_tao', 'DESC');
        }


        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        $query      = $this->db->get();
        $data       = $query->result_array();
        $sql        = $this->db->last_query();

        foreach ($data as &$dt) {

            if ($dt['loai_van_ban'] == $this->common::VAN_BAN_DI) {
                $files = $this->db
                    ->where('id_van_ban', $dt['id_van_ban'])
                    ->where('la_file_ban_hanh', 1)
                    ->get('e_file_dinh_kem')
                    ->result_array();
            } else {
                $files = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_file_dinh_kem')->result_array();
            }

            foreach ($files as $key => &$file) {
                $file['duong_dan'] = encryptString($file['duong_dan']);
            }
            $dt['files'] = $files;

            // $dt['files'] = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_file_dinh_kem')->result_array();
            $baocao = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_bao_cao')->result_array();
            if (!empty($baocao)) {
                $donviIds = array_column($baocao, 'id_don_vi_phan_hoi');

                if (in_array($auth['id_don_vi'], $donviIds)) {
                    $dt['da_phan_hoi_van_ban'] = true;
                } else {
                    $dt['da_phan_hoi_van_ban'] = false;
                }

                $dv = $this->db->from('e_don_vi')
                    ->join('e_bao_cao', 'e_bao_cao.id_don_vi_phan_hoi = e_don_vi.id_don_vi', 'left')
                    ->where_in('id_don_vi', $donviIds)
                    ->select('e_don_vi.*, e_bao_cao.noi_dung, e_bao_cao.ngay_bao_cao, e_bao_cao.dinh_kem')
                    // ->group_by('e_don_vi.id_don_vi')
                    ->where('e_bao_cao.id_van_ban', $dt['id_van_ban'])
                    ->get()
                    ->result_array();
                // $dv = $this->db->where_in('id_don_vi', $donviIds)->get('e_don_vi')->result_array();
                $dt['don_vi_da_phan_hoi'] = $dv;
                // $dt['don_vi_da_phan_hoi'] = $this->db->where_in('id_don_vi', $donviIds)->get('e_don_vi')->result_array();

            } else {
                $dt['don_vi_da_phan_hoi'] = [];
                $dt['da_phan_hoi_van_ban'] = false;
            }

            // $xuly = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_xu_ly')->row_array();
            // if ($xuly) {
            //     $donvixuly = $this->db->where('id_xu_ly', $xuly['id_xu_ly'])->where('don_vi_xu_ly_chinh IS NOT NULL')->get('e_don_vi_xu_ly')->result_array();
            //     $donviphoihop = $this->db->where('id_xu_ly', $xuly['id_xu_ly'])->where('don_vi_xu_ly_chinh IS NULL')->get('e_don_vi_xu_ly')->result_array();
            //     if (!empty($donvixuly)) {
            //         $donvixulyIds = array_column($donvixuly, 'id_don_vi');
            //         $dt['don_vi_xu_ly'] = $this->db->where_in('id_don_vi', $donvixulyIds)->get('e_don_vi')->result_array();
            //     } else {
            //         $dt['don_vi_xu_ly'] = [];
            //     }

            //     if (!empty($donviphoihop)) {
            //         $donviphoihopIds = array_column($donviphoihop, 'id_don_vi');
            //         $dt['don_vi_phoi_hop'] = $this->db->where_in('id_don_vi', $donviphoihopIds)->get('e_don_vi')->result_array();
            //     } else {
            //         $dt['don_vi_phoi_hop'] = [];
            //     }
            // } else {
            //     $dt['don_vi_xu_ly'] = [];
            //     $dt['don_vi_phoi_hop'] = [];
            // }
            $dt['xu_ly'] =  $this->getXuly_byidVanbandi($dt['id_van_ban']);

            $donvi_xuly = array_merge(
                $dt['xu_ly']['don_vi_xu_ly_chinh'] ?? [],
                $dt['xu_ly']['don_vi_xu_ly_phoi_hop'] ?? []
            );

            $dt['da_xem'] = 0;
            foreach ($donvi_xuly as $dv) {
                if ($dv['id_don_vi'] == $auth['id_don_vi'] && !empty($dv['da_xem'])) {
                    $dt['da_xem'] = 1;
                    break;
                }
            }
        }


        // Văn bản trên 5 ngày
        $tren5ngay = $totalRecordsTren5ngay
            ->where("e_van_ban.thoi_gian_xu_ly > ", $fiveDaysLater)
            ->where_not_in('e_van_ban.trang_thai', $trang_thai_hoan_thanh)
            ->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL')
            ->count_all_results('', FALSE);

        // Văn bản dưới 5 ngày
        $duoi5ngay = $totalRecordsDuoi5ngay
            ->where("thoi_gian_xu_ly <= ", $fiveDaysLater)
            ->where_not_in('e_van_ban.trang_thai', $trang_thai_hoan_thanh)
            ->where("thoi_gian_xu_ly > ", $now)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->count_all_results('', FALSE);

        // Văn bản hom nay
        $homnay = $totalRecordsHomnay
            ->where("DATE(thoi_gian_xu_ly)", $now)
            ->where_not_in('e_van_ban.trang_thai', $trang_thai_hoan_thanh)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->count_all_results('', FALSE);

        //Văn bản quá hạn
        $quahan = $totalRecordsQuahan
            ->where("thoi_gian_xu_ly < ", $now)
            ->where_not_in('e_van_ban.trang_thai', $trang_thai_hoan_thanh)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->count_all_results('', FALSE);

        $st_all = (clone $totalRecordsQuery)
            ->where('e_van_ban.deleted_at IS NULL')
            ->where($excludeTrash, null, false)
            ->count_all_results('', FALSE);

        $st_chua_xem = (clone $totalRecordsQuery)
            ->where('e_don_vi_xu_ly.da_xem', 0)
            ->where('e_don_vi_xu_ly.id_nguoi_xu_ly', $auth['ql_nguoi_dung_id'])
            ->where('e_van_ban.deleted_at IS NULL')
            ->where($excludeTrash, null, false)
            ->count_all_results('', FALSE);

        $st_da_xem = (clone $totalRecordsQuery)
            ->where('e_don_vi_xu_ly.da_xem', 1)
            ->where('e_don_vi_xu_ly.id_nguoi_xu_ly', $auth['ql_nguoi_dung_id'])
            ->where('e_van_ban.deleted_at IS NULL')
            ->where($excludeTrash, null, false)
            ->count_all_results('', FALSE);

        $st_tiep_nhan = (clone $totalRecordsQuery)
            ->where('e_van_ban.trang_thai', Common::STATUS_VAN_BAN_DEN['TIEP_NHAN']['value'])
            ->where('e_van_ban.deleted_at IS NULL')
            ->where($excludeTrash, null, false)
            ->count_all_results('', FALSE);

        $st_cho_but_phe = (clone $totalRecordsQuery)
            ->where('e_van_ban.trang_thai', Common::STATUS_VAN_BAN_DEN['CHO_LANH_DAO_BUT_PHE']['value'])
            ->where('e_van_ban.deleted_at IS NULL')
            ->where($excludeTrash, null, false)
            ->count_all_results('', FALSE);

        $st_da_but_phe = (clone $totalRecordsQuery)
            ->where('e_van_ban.trang_thai', Common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value'])
            ->where('e_van_ban.deleted_at IS NULL')
            ->where($excludeTrash, null, false)
            ->count_all_results('', FALSE);

        $st_da_chuyen = (clone $totalRecordsQuery)
            ->where('e_van_ban.trang_thai', Common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'])
            ->where('e_van_ban.deleted_at IS NULL')
            ->where($excludeTrash, null, false)
            ->count_all_results('', FALSE);

        $st_da_xu_ly = (clone $totalRecordsQuery)
            ->where('e_van_ban.trang_thai', Common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value'])
            ->where('e_van_ban.deleted_at IS NULL')
            ->where($excludeTrash, null, false)
            ->count_all_results('', FALSE);

        $st_hoan_thanh = (clone $totalRecordsQuery)
            ->where_in('e_van_ban.trang_thai', $trang_thai_hoan_thanh)
            ->where('e_van_ban.deleted_at IS NULL')
            ->where($excludeTrash, null, false)
            ->count_all_results('', FALSE);

        $st_luu_tru = (clone $totalRecordsQuery)
            ->where('e_van_ban.trang_thai', Common::STATUS_VAN_BAN_DEN['LUU_TRU']['value'])
            ->where('e_van_ban.deleted_at IS NULL')
            ->where($excludeTrash, null, false)
            ->count_all_results('', FALSE);

        $st_thu_hoi = (clone $totalRecordsQuery)
            ->where('e_van_ban.deleted_at IS NOT NULL')
            ->where($excludeTrash, null, false)
            ->count_all_results('', FALSE);

        $st_nhan_hom_nay = (clone $totalRecordsQuery)
            ->where('DATE(e_don_vi_xu_ly.ngay_tao)', $now)
            ->where('e_van_ban.deleted_at IS NULL')
            ->where($excludeTrash, null, false)
            ->count_all_results('', FALSE);

        return [
            'data'              => $data,
            'recordsTotal'      => $recordsTotal,
            'recordsFiltered'   => $recordsFiltered,
            'sql'               => $sql,
            'thoi_han'          => [
                'all'         => $st_all,
                'chua_xem'    => $st_chua_xem,
                'da_xem'      => $st_da_xem,
                'tiep_nhan'   => $st_tiep_nhan,
                'cho_but_phe' => $st_cho_but_phe,
                'da_but_phe'  => $st_da_but_phe,
                'da_chuyen_don_vi_xu_ly' => $st_da_chuyen,
                'da_xu_ly'    => $st_da_xu_ly,
                'hoan_thanh'  => $st_hoan_thanh,
                'luu_tru'     => $st_luu_tru,
                'thu_hoi'     => $st_thu_hoi,
                'tren_5_ngay' => $tren5ngay,
                'duoi_5_ngay' => $duoi5ngay,
                'hom_nay'     => $homnay,
                'qua_han'     => $quahan,
                'nhan_hom_nay' => $st_nhan_hom_nay,
            ]
        ];
    }

    // Danh sách văn bản đã xóa
    public function getVanbandaxoa($start = 0, $length = 10, $searchValue = null, $orderBy = [], $columns = [], $searchKey = array(), $fromDate = null, $toDate = null, $auth, $dataSource = [])
    {
        $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
        $now = date('Y-m-d');
        $fiveDaysLater = date('Y-m-d', strtotime('5 days'));
        // trạng thái văn bản hoàn thành
        $trang_thai_hoan_thanh =  [Common::STATUS_VAN_BAN_DEN['HOAN_THANH']['value'], Common::STATUS_VAN_BAN_DI['HOAN_THANH']['value']];
        $this->db->select('
                        e_van_ban.id_van_ban,
                        e_van_ban.so_van_ban,
                        e_van_ban.so_van_ban_hau_to,
                        e_van_ban.so_hieu_van_ban,
                        e_van_ban.loai_van_ban,
                        e_loai.id_loai,
                        e_loai.ten_loai,
                        e_van_ban.trich_yeu,
                        e_van_ban.ngay_nhan,
                        e_van_ban.ngay_ban_hanh,
                        DATE_FORMAT(e_van_ban.thoi_gian_xu_ly, "%Y-%m-%d")  AS thoi_gian_xu_ly,
                        e_van_ban.trang_thai,
                        e_khoi_co_quan.id_khoi_co_quan,
                        e_khoi_co_quan.ten_khoi_co_quan,
                        e_co_quan.id_co_quan,
                        e_co_quan.ten_co_quan,
                        e_hinh_thuc.id_hinh_thuc,
                        e_hinh_thuc.ten_hinh_thuc,
                        e_van_ban.linh_vuc,
                        e_tinh_chat.id_tinh_chat,
                        e_tinh_chat.ten_tinh_chat,
                        e_bao_mat.id_bao_mat,
                        e_bao_mat.ten_bao_mat,
                        e_don_vi.id_don_vi,
                        e_don_vi.ten_don_vi ho_so_don_vi,
                        e_van_ban.noi_luu_tru,
                        e_van_ban.trang_thai_huy_vb,
                        e_van_ban.luu_tru_noi_bo,
                        e_van_ban.nguoi_ky,
                        e_van_ban.ngay_ky,
                        e_van_ban.van_ban_chi_doc,
                        e_van_ban.ngay_tao,
                        e_van_ban.ngay_sua,
                        e_van_ban.deleted_at,
                        e_but_phe.id_nguoi_but_phe,
                        nguoi_butphe.ql_nguoi_dung_ho_ten AS nguoi_but_phe,
                        e_but_phe.ngay_but_phe,
                        e_but_phe.noi_dung_but_phe,
                        e_van_ban.id_nguoi_tao,
                        nguoi_tao.ql_nguoi_dung_ho_ten AS ten_nguoi_tao,
                        nguoi_tao.ql_nguoi_dung_email AS email_nguoi_tao,
                        nguoi_xoa.ql_nguoi_dung_ho_ten AS ten_nguoi_xoa,
                        nguoi_xoa.ql_nguoi_dung_email AS email_nguoi_xoa,
                        e_xu_ly.id_xu_ly,
                        e_xu_ly.nguoi_duyet,
                        e_xu_ly.ngay_duyet,
                        e_xu_ly.ghi_chu_duyet,
                        e_xu_ly.ngay_tao AS ngay_xu_ly,
                        e_xu_ly.nguoi_tao AS id_nguoi_chuyen_xu_ly,
                        nguoi_chuyen.ql_nguoi_dung_ho_ten AS ten_nguoi_chuyen_xu_ly,
                        nguoi_chuyen.ql_nguoi_dung_email AS email_nguoi_chuyen_xu_ly,
                        e_don_vi_xu_ly.da_xem,
                        e_don_vi_xu_ly.id_nguoi_xu_ly
                    ');
        $this->db->from('e_van_ban');
        $this->db->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'left');
        $this->db->join('ql_nguoi_dung AS nguoi_chuyen', 'e_xu_ly.nguoi_tao = nguoi_chuyen.ql_nguoi_dung_id', 'left');
        $this->db->join('e_don_vi_xu_ly', 'e_xu_ly.id_xu_ly = e_don_vi_xu_ly.id_xu_ly', 'left');
        $this->db->join('e_don_vi', 'e_don_vi_xu_ly.id_don_vi = e_don_vi.id_don_vi', 'left');
        $this->db->join('e_loai', 'e_van_ban.id_loai = e_loai.id_loai', 'left');
        $this->db->join('e_khoi_co_quan', 'e_van_ban.id_khoi_co_quan = e_khoi_co_quan.id_khoi_co_quan', 'left');
        $this->db->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left');
        $this->db->join('e_tinh_chat', 'e_van_ban.id_tinh_chat = e_tinh_chat.id_tinh_chat', 'left');
        $this->db->join('e_bao_mat', 'e_van_ban.id_bao_mat = e_bao_mat.id_bao_mat', 'left');
        $this->db->join('e_but_phe', 'e_van_ban.id_van_ban = e_but_phe.id_van_ban', 'left');
        $this->db->join('ql_nguoi_dung AS nguoi_butphe', 'e_but_phe.id_nguoi_but_phe = nguoi_butphe.ql_nguoi_dung_id', 'left');
        $this->db->join('e_hinh_thuc', 'e_van_ban.id_hinh_thuc = e_hinh_thuc.id_hinh_thuc', 'left');
        $this->db->join('ql_nguoi_dung AS nguoi_tao', 'e_van_ban.id_nguoi_tao = nguoi_tao.ql_nguoi_dung_id', 'left');
        $this->db->join('e_van_ban_da_xoa', 'e_van_ban_da_xoa.id_van_ban = e_van_ban.id_van_ban', 'inner');
        $this->db->join('ql_nguoi_dung AS nguoi_xoa', 'e_van_ban_da_xoa.nguoi_xoa = nguoi_xoa.ql_nguoi_dung_id', 'left');

        // $this->db->where('e_van_ban.deleted_at IS NOT NULL');
        // $this->db->where('e_don_vi_xu_ly.id_don_vi', $auth['id_don_vi']);
        $this->db->where('e_van_ban_da_xoa.id_don_vi', $auth['id_don_vi']);
        $this->db->where('e_van_ban_da_xoa.da_xoa_vinh_vien', 0); // Chỉ lấy văn bản chưa bị xóa vĩnh viễn bởi đơn vị này
        $this->db->order_by('e_van_ban_da_xoa.ngay_xoa', 'DESC');
        $this->db->group_by('e_van_ban.id_van_ban');

        $totalRecordsQuery = clone $this->db;

        $totalRecordsVanBanDen = clone $this->db;
        $totalRecordsVanBanDi = clone $this->db;
        $totalRecordsVanBanNoiBo = clone $this->db;

        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue && !empty($searchValue)) {
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


        if (!empty($searchKey)) {
            if (!empty($searchKey['ngay_tao_tu']) && !empty($searchKey['ngay_tao_den'])) {
                $from = $searchKey['ngay_tao_tu'];
                $to   = $searchKey['ngay_tao_den'];
                $this->db->where("DATE(e_van_ban.ngay_tao) BETWEEN '{$from}' AND '{$to}'");
            }

            if (!empty($searchKey['ngay_ban_hanh_tu']) && !empty($searchKey['ngay_ban_hanh_den'])) {
                $from = $searchKey['ngay_ban_hanh_tu'];
                $to   = $searchKey['ngay_ban_hanh_den'];
                $this->db->where("DATE(e_van_ban.ngay_ban_hanh) BETWEEN '{$from}' AND '{$to}'");
            }

            if (!empty($searchKey['ngay_nhan_tu']) && !empty($searchKey['ngay_nhan_den'])) {
                $from = $searchKey['ngay_nhan_tu'];
                $to   = $searchKey['ngay_nhan_den'];
                $this->db->where("DATE(e_van_ban.ngay_nhan) BETWEEN '{$from}' AND '{$to}'");
            }

            foreach ($searchKey as $key => $value) {
                if (!empty($value)) {
                    switch ($key) {
                        case 'so_van_ban':
                            $this->db->like('e_van_ban.so_van_ban', $value);
                            break;
                        case 'trich_yeu':
                            $this->db->like('e_van_ban.trich_yeu', $value);
                            break;
                        case 'loai_van_ban':
                            $this->db->like('e_loai.id_loai', $value);
                            break;
                        case 'year':
                            if ($value !== 'all_years') {
                                $this->db->where("YEAR(e_van_ban.ngay_tao)", $value);
                            }
                            break;
                        case 'selectedClassify':
                            switch ($value) {
                                case 'van_ban_den':
                                    $this->db->where('e_van_ban.loai_van_ban', 1);
                                    break;
                                case 'van_ban_di':
                                    $this->db->where('e_van_ban.loai_van_ban', 2);
                                    break;
                                case 'van_ban_noi_bo':
                                    $this->db->where('e_van_ban.loai_van_ban', 3);
                                    break;
                                case 'hom_nay':
                                    $this->db->where('DATE(e_van_ban_da_xoa.ngay_xoa)', date('Y-m-d'));
                                    break;
                                case '7_ngay_qua':
                                    $sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
                                    $this->db->where('DATE(e_van_ban_da_xoa.ngay_xoa) >=', $sevenDaysAgo);
                                    break;
                                case 'truoc_do':
                                    $sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
                                    $this->db->where('DATE(e_van_ban_da_xoa.ngay_xoa) <', $sevenDaysAgo);
                                    break;
                                case 'all':
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

        // if ($fromDate && $toDate) {
        //     $this->db->where("e_don_vi_xu_ly.ngay_tao BETWEEN '{$fromDate}' AND '{$toDate}'");
        // }
        if ($fromDate && $toDate) {
            $this->db->group_start();
            $this->db->where("e_don_vi_xu_ly.ngay_tao BETWEEN '{$fromDate}' AND '{$toDate}'");
            $this->db->or_where('e_don_vi_xu_ly.ngay_tao IS NULL');
            $this->db->group_end();
        }


        $filteredQuery      = clone $this->db;
        $recordsFiltered    = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query

        if (!empty($columns)) {
            // Map tên cột hiển thị (alias) sang cột thật trong DB
            $columnMapping = [
                'ten_khoi_co_quan' => 'e_khoi_co_quan.ten_khoi_co_quan',
                'ten_co_quan'      => 'e_co_quan.ten_co_quan',
                'ten_loai'         => 'e_loai.ten_loai',
                'ten_tinh_chat'    => 'e_tinh_chat.ten_tinh_chat',
                'nguoi_but_phe'    => 'ql_nguoi_dung.ql_nguoi_dung_ho_ten',
                'ten_nguoi_xoa'    => 'nguoi_xoa.ql_nguoi_dung_ho_ten',
                'trang_thai'       => 'e_van_ban.trang_thai', // xử lý riêng
                // Mặc định nếu không map thì nó sẽ lấy e_van_ban.[columnName]
            ];
            $this->handleDatatableColumns($columns, $columnMapping);
        }

        // Xử lý order sau khi xử lý search
        if (!empty($orderBy)) {
            $this->handleDatatableOrdering($orderBy, $columns);
        }

        // Xử lý order cho react
        if (!empty($dataSource['order'])) {
            $this->applyOrdering($dataSource['order']);
        }


        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        $query      = $this->db->get();
        $data       = $query->result_array();
        $sql        = $this->db->last_query();

        foreach ($data as &$dt) {

            if ($dt['loai_van_ban'] == $this->common::VAN_BAN_DI) {
                $files = $this->db
                    ->where('id_van_ban', $dt['id_van_ban'])
                    ->where('la_file_ban_hanh', 1)
                    ->get('e_file_dinh_kem')
                    ->result_array();
            } else {
                $files = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_file_dinh_kem')->result_array();
            }

            foreach ($files as $key => &$file) {
                $file['duong_dan'] = encryptString($file['duong_dan']);
            }
            $dt['files'] = $files;

            // $dt['files'] = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_file_dinh_kem')->result_array();
            $baocao = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_bao_cao')->result_array();
            if (!empty($baocao)) {
                $donviIds = array_column($baocao, 'id_don_vi_phan_hoi');

                if (in_array($auth['id_don_vi'], $donviIds)) {
                    $dt['da_phan_hoi_van_ban'] = true;
                } else {
                    $dt['da_phan_hoi_van_ban'] = false;
                }

                $dv = $this->db->from('e_don_vi')
                    ->join('e_bao_cao', 'e_bao_cao.id_don_vi_phan_hoi = e_don_vi.id_don_vi', 'left')
                    ->where_in('id_don_vi', $donviIds)
                    ->select('e_don_vi.*, e_bao_cao.noi_dung, e_bao_cao.ngay_bao_cao, e_bao_cao.dinh_kem')
                    // ->group_by('e_don_vi.id_don_vi')
                    ->where('e_bao_cao.id_van_ban', $dt['id_van_ban'])
                    ->get()
                    ->result_array();
                // $dv = $this->db->where_in('id_don_vi', $donviIds)->get('e_don_vi')->result_array();
                $dt['don_vi_da_phan_hoi'] = $dv;
                // $dt['don_vi_da_phan_hoi'] = $this->db->where_in('id_don_vi', $donviIds)->get('e_don_vi')->result_array();

            } else {
                $dt['don_vi_da_phan_hoi'] = [];
                $dt['da_phan_hoi_van_ban'] = false;
            }

            // $xuly = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_xu_ly')->row_array();
            // if ($xuly) {
            //     $donvixuly = $this->db->where('id_xu_ly', $xuly['id_xu_ly'])->where('don_vi_xu_ly_chinh IS NOT NULL')->get('e_don_vi_xu_ly')->result_array();
            //     $donviphoihop = $this->db->where('id_xu_ly', $xuly['id_xu_ly'])->where('don_vi_xu_ly_chinh IS NULL')->get('e_don_vi_xu_ly')->result_array();
            //     if (!empty($donvixuly)) {
            //         $donvixulyIds = array_column($donvixuly, 'id_don_vi');
            //         $dt['don_vi_xu_ly'] = $this->db->where_in('id_don_vi', $donvixulyIds)->get('e_don_vi')->result_array();
            //     } else {
            //         $dt['don_vi_xu_ly'] = [];
            //     }

            //     if (!empty($donviphoihop)) {
            //         $donviphoihopIds = array_column($donviphoihop, 'id_don_vi');
            //         $dt['don_vi_phoi_hop'] = $this->db->where_in('id_don_vi', $donviphoihopIds)->get('e_don_vi')->result_array();
            //     } else {
            //         $dt['don_vi_phoi_hop'] = [];
            //     }
            // } else {
            //     $dt['don_vi_xu_ly'] = [];
            //     $dt['don_vi_phoi_hop'] = [];
            // }
            $dt['xu_ly'] =  $this->getXuly_byidVanbandi($dt['id_van_ban']);

            $donvi_xuly = array_merge(
                $dt['xu_ly']['don_vi_xu_ly_chinh'] ?? [],
                $dt['xu_ly']['don_vi_xu_ly_phoi_hop'] ?? []
            );

            $dt['da_xem'] = 0;
            foreach ($donvi_xuly as $dv) {
                if ($dv['id_don_vi'] == $auth['id_don_vi'] && !empty($dv['da_xem'])) {
                    $dt['da_xem'] = 1;
                    break;
                }
            }
        }


        // Văn bản đến
        $vanbanden = $totalRecordsVanBanDen
            ->where('e_van_ban.loai_van_ban', 1)
            ->count_all_results('', FALSE);

        // Văn bản đi
        $vanbandi = $totalRecordsVanBanDi
            ->where('e_van_ban.loai_van_ban', 2)
            ->count_all_results('', FALSE);

        // Văn bản nội bộ
        $vanbannoibo = $totalRecordsVanBanNoiBo
            ->where('e_van_ban.loai_van_ban', 3)
            ->count_all_results('', FALSE);


        $today_date = date('Y-m-d');
        $seven_days_ago = date('Y-m-d', strtotime('-7 days'));

        $hom_nay = (clone $totalRecordsQuery)
            ->where('DATE(e_van_ban_da_xoa.ngay_xoa)', $today_date)
            ->count_all_results('', FALSE);

        $seven_days = (clone $totalRecordsQuery)
            ->where('DATE(e_van_ban_da_xoa.ngay_xoa) >=', $seven_days_ago)
            ->count_all_results('', FALSE);

        $truoc_do = (clone $totalRecordsQuery)
            ->where('DATE(e_van_ban_da_xoa.ngay_xoa) <', $seven_days_ago)
            ->count_all_results('', FALSE);

        return [
            'data'              => $data,
            'recordsTotal'      => $recordsTotal,
            'recordsFiltered'   => $recordsFiltered,
            'sql'               => $sql,
            'thoi_han'          => [
                'all' => $recordsTotal,
                'van_ban_den' => $vanbanden,
                'van_ban_di' => $vanbandi,
                'van_ban_noi_bo' => $vanbannoibo,
                'hom_nay' => $hom_nay,
                '7_ngay_qua' => $seven_days,
                'truoc_do' => $truoc_do,
            ]
        ];
    }

    public function getAllVanBanDenTuDonVi($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array(), $fromDate = null, $toDate = null, $auth)
    {
        $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
        // dd($auth);
        $this->db->from('e_van_ban')
            ->select('e_van_ban.*, 
                        e_tinh_chat.ten_tinh_chat, 
                        e_loai.ten_loai, 
                        e_khoi_co_quan.ten_khoi_co_quan, 
                        e_co_quan.ten_co_quan,
                        e_don_vi.ten_don_vi')
            ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
            ->join('e_loai', 'e_loai.id_loai = e_van_ban.id_loai', 'left')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
            ->join('e_bao_cao', 'e_van_ban.id_van_ban = e_bao_cao.id_van_ban', 'left')
            ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'left')
            ->join('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = e_van_ban.id_don_vi_soan')
            ->where('e_don_vi_xu_ly.id_don_vi', $auth['id_don_vi'])
            ->where('e_van_ban.id_don_vi_soan !=', $auth['id_don_vi']);   // điều kiện thêm

        $this->db->group_start();

        $this->db->group_start();
        $this->db->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DEN);
        $this->db->where_in('e_van_ban.trang_thai', [
            $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
            $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value'],
            $this->common::STATUS_VAN_BAN_DEN['CHUA_PHAN_HOI']['value'],
            $this->common::STATUS_VAN_BAN_DEN['DA_PHAN_HOI']['value']
        ]);
        $this->db->group_end();

        $this->db->or_group_start();
        $this->db->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DI);
        $this->db->where_in('e_van_ban.trang_thai', [
            $this->common::STATUS_VAN_BAN_DI['DA_BAN_HANH']['value'],
            $this->common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value'],
            $this->common::STATUS_VAN_BAN_DI['DA_PHAN_HOI']['value'],
            $this->common::STATUS_VAN_BAN_DI['CHUA_PHAN_HOI']['value'],
        ]);
        $this->db->group_end();
        $this->db->group_end();

        $this->db->group_by('e_van_ban.id_van_ban');
        $this->db->where('e_van_ban.deleted_at IS NULL');

        if (!empty($searchKey['year']) && $searchKey['year'] !== 'all_years') {
            $this->db->where("YEAR(e_van_ban.ngay_nhan)", $searchKey['year']);
        }

        $totalRecordsQuery = clone $this->db;

        $totalRecordsTren5ngay = clone $this->db;
        $totalRecordsDuoi5ngay = clone $this->db;
        $totalRecordsHomnay = clone $this->db;
        $totalRecordsQuahan = clone $this->db;


        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        $now = date('Y-m-d');
        $fiveDaysLater = date('Y-m-d', strtotime('5 days'));

        // Văn bản trên 5 ngày
        $tren5ngay = $totalRecordsTren5ngay
            ->where("e_van_ban.thoi_gian_xu_ly > ", $fiveDaysLater)
            ->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL')
            ->count_all_results('', FALSE);

        // Văn bản dưới 5 ngày
        $duoi5ngay = $totalRecordsDuoi5ngay
            ->where("thoi_gian_xu_ly <= ", $fiveDaysLater)
            ->where("thoi_gian_xu_ly > ", $now)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->count_all_results('', FALSE);

        // Văn bản hom nay
        $homnay = $totalRecordsHomnay
            ->where("DATE(thoi_gian_xu_ly)", $now)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->count_all_results('', FALSE);

        //Văn bản quá hạn
        $quahan = $totalRecordsQuahan
            ->where("thoi_gian_xu_ly < ", $now)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->count_all_results('', FALSE);



        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('e_van_ban.ten_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.so_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.so_hieu_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.trich_yeu', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                if ($key == 'thoi_han') {
                    switch ($value) {
                        case 'tren_5_ngay':
                            $this->db->where("e_van_ban.thoi_gian_xu_ly > ", $fiveDaysLater)
                                ->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL');
                            break;
                        case 'duoi_5_ngay':
                            $this->db->where("e_van_ban.thoi_gian_xu_ly <= ", $fiveDaysLater)
                                ->where("e_van_ban.thoi_gian_xu_ly > ", $now)
                                ->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL');
                            break;
                        case 'hom_nay':
                            $this->db->where("DATE(e_van_ban.thoi_gian_xu_ly)", $now)
                                ->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL');
                            break;
                        case 'qua_han':
                            $this->db->where("e_van_ban.thoi_gian_xu_ly < ", $now)
                                ->where('e_van_ban.thoi_gian_xu_ly IS NOT NULL');
                            break;
                    }
                } else if ($key != 'year') {
                    $this->db->where('e_van_ban.' . $key, $value);
                }
            }
        }

        if ($fromDate && $toDate) {
            $this->db->where("ngay_nhan BETWEEN '{$fromDate}' AND '{$toDate}'");
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

        if (!empty($searchKey['sortOrder'])) {
            if ($searchKey['sortOrder'] === 'newest') {
                $this->db->order_by('e_van_ban.id_van_ban', 'DESC');
            } else if ($searchKey['sortOrder'] === 'oldest') {
                $this->db->order_by('e_van_ban.id_van_ban', 'ASC');
            }
        } else if (!empty($orderBy)) {
            $order = $orderBy['order'];
            $orderColumnIndex = $order[0]['column'];
            $orderDir = $order[0]['dir'];

            $columns = $orderBy['columns'];
            $filed = $columns[$orderColumnIndex]['data'];

            if (!empty($orderDir) && $filed == 'so_van_ban') {
                $this->db->order_by('e_van_ban.so_van_ban', $orderDir);
                $this->db->order_by('e_van_ban.so_van_ban_hau_to', $orderDir);
            } else {
                $this->db->order_by('e_van_ban.' . $filed, $orderDir);
            }
        } else {
            // Default order
            $this->db->order_by('e_van_ban.id_van_ban', 'DESC');
        }



        if ($length != '-1') {
            $this->db->limit($length, $start);
        }


        $query = $this->db->get();
        $data = $query->result_array();

        foreach ($data as &$dt) {

            if ($dt['loai_van_ban'] == $this->common::VAN_BAN_DI) {
                $files = $this->db
                    ->where('id_van_ban', $dt['id_van_ban'])
                    ->where('la_file_ban_hanh', 1)
                    ->get('e_file_dinh_kem')
                    ->result_array();
            } else {
                $files = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_file_dinh_kem')->result_array();
            }

            foreach ($files as $key => &$file) {
                $file['duong_dan'] = encryptString($file['duong_dan']);
            }
            $dt['files'] = $files;

            // $dt['files'] = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_file_dinh_kem')->result_array();
            $baocao = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_bao_cao')->result_array();
            if (!empty($baocao)) {
                $donviIds = array_column($baocao, 'id_don_vi_phan_hoi');
                $dv = $this->db->from('e_don_vi')
                    ->join('e_bao_cao', 'e_bao_cao.id_don_vi_phan_hoi = e_don_vi.id_don_vi', 'left')
                    ->where_in('id_don_vi', $donviIds)
                    ->select('e_don_vi.*, e_bao_cao.noi_dung, e_bao_cao.ngay_bao_cao, e_bao_cao.dinh_kem')
                    // ->group_by('e_don_vi.id_don_vi')
                    ->where('e_bao_cao.id_van_ban', $dt['id_van_ban'])
                    ->get()
                    ->result_array();
                // $dv = $this->db->where_in('id_don_vi', $donviIds)->get('e_don_vi')->result_array();
                $dt['don_vi_da_phan_hoi'] = $dv;
                // $dt['don_vi_da_phan_hoi'] = $this->db->where_in('id_don_vi', $donviIds)->get('e_don_vi')->result_array();

            } else {
                $dt['don_vi_da_phan_hoi'] = [];
            }

            $xuly = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_xu_ly')->row_array();
            if ($xuly) {
                $donvixuly = $this->db->where('id_xu_ly', $xuly['id_xu_ly'])->where('don_vi_xu_ly_chinh IS NOT NULL')->get('e_don_vi_xu_ly')->result_array();
                $donviphoihop = $this->db->where('id_xu_ly', $xuly['id_xu_ly'])->where('don_vi_xu_ly_chinh IS NULL')->get('e_don_vi_xu_ly')->result_array();
                if (!empty($donvixuly)) {
                    $donvixulyIds = array_column($donvixuly, 'id_don_vi');
                    $dt['don_vi_xu_ly'] = $this->db->where_in('id_don_vi', $donvixulyIds)->get('e_don_vi')->result_array();
                } else {
                    $dt['don_vi_xu_ly'] = [];
                }

                if (!empty($donviphoihop)) {
                    $donviphoihopIds = array_column($donviphoihop, 'id_don_vi');
                    $dt['don_vi_phoi_hop'] = $this->db->where_in('id_don_vi', $donviphoihopIds)->get('e_don_vi')->result_array();
                } else {
                    $dt['don_vi_phoi_hop'] = [];
                }
            } else {
                $dt['don_vi_xu_ly'] = [];
                $dt['don_vi_phoi_hop'] = [];
            }

            //Đã xem
            $dt['da_xem'] = 0;
            foreach ($donvixuly as $dvxl) {
                if ($dvxl['id_don_vi'] == $auth['id_don_vi']) {
                    if ($dvxl['da_xem']) {
                        $dt['da_xem'] = 1;
                        break;
                    }
                }
            }
            if (!$dt['da_xem']) {
                foreach ($donviphoihop as $dvph) {
                    if ($dvph['id_don_vi'] == $auth['id_don_vi']) {
                        if ($dvph['da_xem']) {
                            $dt['da_xem'] = 1;
                            break;
                        }
                    }
                }
            }
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'tren5ngay' => $tren5ngay,
            'duoi5ngay' => $duoi5ngay,
            'homnay' => $homnay,
            'quahan' => $quahan
        ];
    }

    public function thongKeVanBan($userId, $userDonVi)
    {

        $user = $this->Ql_nguoi_dung_model->find($userId);
        if (!$user) {
            show_404(); //không có user
        }
        $donviTochuchanhchinh = $this->E_don_vi_model->where('ma_don_vi', 'PHONG_TCHC')->first();
        if (!$donviTochuchanhchinh) {
            show_404();
        }
        $this->E_van_ban_model->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");

        if ($userDonVi == $donviTochuchanhchinh['id_don_vi']) {
            $student_count =
                $this->db->select("
                YEAR(e_van_ban.ngay_ky) as nam,
                count(e_van_ban.id_van_ban) as tong,
                SUM(IF(e_van_ban.loai_van_ban = 1, 1, 0)) AS sl_vb_den,
                SUM(IF(e_van_ban.loai_van_ban = 2, 1, 0)) AS sl_vb_di,
                SUM(IF(e_van_ban.loai_van_ban = 3 AND id_don_vi_soan= '" . $this->db->escape_str($donviTochuchanhchinh['id_don_vi']) . "', 1, 0)) AS sl_vb_noibo")
                ->from('e_van_ban')
                ->where('e_van_ban.deleted_at IS NULL')
                ->group_by('YEAR(e_van_ban.ngay_ky)') //version sau xet vanbanden: ngay_nhan, vanbannoibo va vanbandi: ngay_ky
                ->order_by("nam", "ASC")
                ->get()
                ->result_array();
        } else {

            // Subquery 1: Văn bản đến loại 1
            $this->db->select("
            YEAR(e_van_ban.ngay_ky) AS nam,
            COUNT(DISTINCT(e_van_ban.id_van_ban)) AS sl_vb_den,
            0 AS sl_vb_noibo
            ");
            $this->db->from('e_van_ban');
            $this->db->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'inner');
            $this->db->join('e_don_vi_xu_ly', 'e_xu_ly.id_xu_ly = e_don_vi_xu_ly.id_xu_ly', 'inner');
            $this->db->where('e_van_ban.deleted_at IS NULL');
            $this->db->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DEN);
            $this->db->where('e_don_vi_xu_ly.id_don_vi', $userDonVi);
            $this->db->where_in('e_van_ban.trang_thai', [
                $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
                $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value'],
                $this->common::STATUS_VAN_BAN_DEN['CHUA_PHAN_HOI']['value'],
                $this->common::STATUS_VAN_BAN_DEN['DA_PHAN_HOI']['value']
            ]);
            $this->db->group_by("YEAR(e_van_ban.ngay_ky)");
            $subquery1 = $this->db->get_compiled_select();

            // Subquery 2: Văn bản đi loại  2
            $this->db->select("
            YEAR(e_van_ban.ngay_ky) AS nam,
            COUNT(DISTINCT(e_van_ban.id_van_ban)) AS sl_vb_den,
            0 AS sl_vb_noibo
            ");
            $this->db->from('e_van_ban');
            $this->db->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'inner');
            $this->db->join('e_don_vi_xu_ly', 'e_xu_ly.id_xu_ly = e_don_vi_xu_ly.id_xu_ly', 'inner');
            $this->db->where('e_van_ban.deleted_at IS NULL');
            $this->db->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DI);
            $this->db->where('e_don_vi_xu_ly.id_don_vi', $userDonVi);
            $this->db->where_in('e_van_ban.trang_thai', [
                $this->common::STATUS_VAN_BAN_DI['DA_BAN_HANH']['value'],
                $this->common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value'],
                $this->common::STATUS_VAN_BAN_DI['DA_PHAN_HOI']['value'],
                $this->common::STATUS_VAN_BAN_DI['CHUA_PHAN_HOI']['value'],
            ]);
            $this->db->group_by("YEAR(e_van_ban.ngay_ky)");
            $subquery2 = $this->db->get_compiled_select();

            // Subquery 3: Văn bản nội bộ
            $this->db->select("
            YEAR(e_van_ban.ngay_ky) AS nam,
            0 AS sl_vb_den,
            SUM(IF(e_van_ban.loai_van_ban = 3, 1, 0)) AS sl_vb_noibo
            ");
            $this->db->from('e_van_ban');
            $this->db->where('e_van_ban.deleted_at IS NULL');
            $this->db->where('e_van_ban.id_don_vi_soan', $userDonVi);
            $this->db->group_by("YEAR(e_van_ban.ngay_ky)");
            $subquery3 = $this->db->get_compiled_select();

            // Main query: Merge subqueries
            $this->db->select("
            nam,
            SUM(sl_vb_den) AS sl_vb_den,
            SUM(sl_vb_noibo) AS sl_vb_noibo
            ");
            $this->db->from("($subquery1 UNION ALL $subquery2 UNION ALL $subquery3) AS merged_data", false);
            $this->db->group_by('nam');
            $this->db->order_by("nam", "ASC");
            $query = $this->db->get();

            // Get results
            $student_count = $query->result_array();
        }

        return $student_count;
    }

    public function thongkeVanbanTheoDonvi($data = [])
    {
        $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
        $this->db->query("SET  @id_don_vi = " . $data['id_don_vi']);
        $this->db->query("SET  @year = " . $data['nam']);
        $this->db->query("SET  @tchc = 'PHONG_TCHC' COLLATE utf8_general_ci");
        $this->db->query("SET @countVanbandendonvi = (
                                SELECT COUNT(DISTINCT vb.id_van_ban)
                                FROM e_van_ban vb
                                LEFT JOIN e_xu_ly xl ON vb.id_van_ban = xl.id_van_ban
                                LEFT JOIN e_don_vi_xu_ly dvxl ON xl.id_xu_ly = dvxl.id_xu_ly
                                WHERE vb.deleted_at IS NULL
                                            AND YEAR(vb.ngay_tao) = @year
                                            AND dvxl.id_don_vi = @id_don_vi
                            )");
        $sql = "
            SELECT 
                IFNULL(van_ban_den, 0) + IFNULL(van_ban_di, 0) + IFNULL(van_ban_noi_bo, 0) AS total,
                IFNULL(van_ban_den, 0) AS van_ban_den,
                IFNULL(van_ban_di, 0) AS van_ban_di,
                IFNULL(van_ban_noi_bo, 0) AS van_ban_noi_bo,
                IFNULL(ROUND(van_ban_den * 100.0 / NULLIF(van_ban_den + van_ban_di + van_ban_noi_bo, 0), 2), 0) AS pt_van_ban_den,
                IFNULL(ROUND(van_ban_di  * 100.0 / NULLIF(van_ban_den + van_ban_di + van_ban_noi_bo, 0), 2), 0) AS pt_van_ban_di,
                IFNULL(ROUND(van_ban_noi_bo * 100.0 / NULLIF(van_ban_den + van_ban_di + van_ban_noi_bo, 0), 2), 0) AS pt_van_ban_noi_bo
            FROM (
                SELECT
                    (CASE WHEN dv.ma_don_vi = @tchc THEN SUM(vb.loai_van_ban = 1) ELSE @countVanbandendonvi END) AS van_ban_den,
                    SUM(vb.loai_van_ban = 2) AS van_ban_di,
                    SUM(vb.loai_van_ban = 3) AS van_ban_noi_bo
                FROM e_van_ban vb
                INNER JOIN ql_nguoi_dung u ON u.ql_nguoi_dung_id = vb.id_nguoi_tao
                INNER JOIN e_don_vi dv ON dv.id_don_vi = u.id_don_vi
                WHERE vb.deleted_at IS NULL
                AND YEAR(vb.ngay_tao) = @year
                AND dv.id_don_vi = @id_don_vi
            ) AS x;
        ";

        $query = $this->db->query($sql);
        return $query->row_array();
    }

    public function thongkeVanbanTheoNam($data = [])
    {
        $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
        $this->db->query("SET  @id_don_vi = " . $data['id_don_vi']);
        $this->db->query("SET  @tchc = 'PHONG_TCHC' COLLATE utf8_general_ci");
        $sql = "
            SELECT 
                YEAR(vb.ngay_tao) AS nam,
                    IFNULL( CASE dv.ma_don_vi 
                    WHEN @tchc THEN SUM(vb.loai_van_ban = 1)
                    ELSE (
                        SELECT COUNT(DISTINCT vb1.id_van_ban)
                        FROM e_van_ban vb1
                            LEFT JOIN e_xu_ly xl ON vb1.id_van_ban = xl.id_van_ban
                            LEFT JOIN e_don_vi_xu_ly dvxl ON xl.id_xu_ly = dvxl.id_xu_ly
                        WHERE vb1.deleted_at IS NULL
                        AND YEAR(vb1.ngay_tao) = YEAR(vb.ngay_tao)
                        AND dvxl.id_don_vi = @id_don_vi
                    )
                END, 0)AS van_ban_den,
                    IFNULL(SUM(vb.loai_van_ban = 2), 0) AS van_ban_di,
                    IFNULL(SUM(vb.loai_van_ban = 3), 0) AS van_ban_noibo
            FROM e_van_ban vb
                INNER JOIN ql_nguoi_dung u ON u.ql_nguoi_dung_id = vb.id_nguoi_tao
                INNER JOIN e_don_vi dv ON dv.id_don_vi = u.id_don_vi 
            WHERE vb.deleted_at IS NULL
                    AND YEAR(vb.ngay_tao) BETWEEN 2020 AND YEAR(CURRENT_DATE())
                    AND dv.id_don_vi = @id_don_vi
            GROUP BY YEAR(vb.ngay_tao)
            ORDER BY nam;
        ";
        $query = $this->db->query($sql);
        return $query->result_array();
    }
    public function thongkeTrangthaiVanbanden($data = [])
    {
        $nam = $data['nam'] ?? null;
        if (!$nam)  return [];
        // Gom nhóm trạng thái
        $tiepNhan  = [Common::STATUS_VAN_BAN_DEN['TIEP_NHAN']['value']];
        $dangXuLy  = [
            Common::STATUS_VAN_BAN_DEN['CHO_LANH_DAO_BUT_PHE']['value'],
            Common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value'],
            Common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value'],
            Common::STATUS_VAN_BAN_DEN['CHUA_PHAN_HOI']['value'],
            Common::STATUS_VAN_BAN_DEN['DA_PHAN_HOI']['value'],
            Common::STATUS_VAN_BAN_DEN['DA_XEM']['value'],
        ];
        $choXuLy   = [Common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value']];
        $hoanThanh = [Common::STATUS_VAN_BAN_DEN['HOAN_THANH']['value']];

        $sql = "
            SELECT 
                YEAR(vb.ngay_tao) AS nam,
                SUM(CASE WHEN vb.trang_thai IN (" . implode(',', $tiepNhan   ?: [0]) . ") THEN 1 ELSE 0 END) AS tiep_nhan,
                SUM(CASE WHEN vb.trang_thai IN (" . implode(',', $dangXuLy   ?: [0]) . ") THEN 1 ELSE 0 END) AS dang_xu_ly,
                SUM(CASE WHEN vb.trang_thai IN (" . implode(',', $choXuLy    ?: [0]) . ") THEN 1 ELSE 0 END) AS cho_don_vi_xu_ly,
                SUM(CASE WHEN vb.trang_thai IN (" . implode(',', $hoanThanh  ?: [0]) . ") THEN 1 ELSE 0 END) AS hoan_thanh
            FROM e_van_ban vb
            WHERE vb.deleted_at IS NULL
                AND YEAR(vb.ngay_tao) BETWEEN 2020 AND $nam
                AND vb.loai_van_ban = " . Common::VAN_BAN_DEN . "
            GROUP BY YEAR(vb.ngay_tao)
            ORDER BY nam;
        ";

        return $this->db->query($sql)->result_array();
    }


    public function thongkeTrangthaiVanbandi($data = [])
    {
        $nam = $data['nam'] ?? null;
        if (!$nam)  return [];
        // mapping từ constant
        $luuTru     = [Common::STATUS_VAN_BAN_DI['LUU_TRU']['value']];
        $choXuLy    = [Common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value']];
        $hoanThanh  = [Common::STATUS_VAN_BAN_DI['HOAN_THANH']['value']];

        $sql = "
            SELECT 
                YEAR(vb.ngay_tao) AS nam,
                SUM(CASE WHEN vb.trang_thai IN (" . implode(',', $luuTru    ?: [0]) . ") THEN 1 ELSE 0 END) AS luu_tru,
                SUM(CASE WHEN vb.trang_thai IN (" . implode(',', $choXuLy   ?: [0]) . ") THEN 1 ELSE 0 END) AS cho_xu_ly, 
                SUM(CASE WHEN vb.trang_thai IN (" . implode(',', $hoanThanh ?: [0]) . ") THEN 1 ELSE 0 END) AS hoan_thanh
            FROM e_van_ban vb
            WHERE vb.deleted_at IS NULL
            AND YEAR(vb.ngay_tao) BETWEEN 2020 AND $nam
            AND vb.loai_van_ban = " . Common::VAN_BAN_DI . "
            GROUP BY YEAR(vb.ngay_tao) 
            ORDER BY nam;
        ";

        return $this->db->query($sql)->result_array();
    }

    public function thongkePhanhoiCuaDonvi($data = [])
    {
        $nam = $data['nam'] ?? null;
        if (!$nam)  return [];
        $this->db->query("SET  @year = " . $nam);
        $sql = "
            SELECT
                dv.id_don_vi,
                dv.ten_don_vi,
                (
                    SELECT COUNT(DISTINCT vb.id_van_ban)
                    FROM e_van_ban vb
                    LEFT JOIN e_xu_ly xl ON vb.id_van_ban = xl.id_van_ban
                    LEFT JOIN e_don_vi_xu_ly dvxl ON xl.id_xu_ly = dvxl.id_xu_ly
                    WHERE vb.deleted_at IS NULL
                        AND YEAR(vb.ngay_tao) = @year
                        AND dvxl.id_don_vi = dv.id_don_vi
                ) AS tong_van_ban_cua_don_vi,
                (
                    SELECT COUNT(DISTINCT bc.id_van_ban)
                    FROM e_bao_cao bc
                    LEFT JOIN e_van_ban vb ON vb.id_van_ban = bc.id_van_ban
                    WHERE vb.deleted_at IS NULL
                        AND YEAR(vb.ngay_tao) = @year
                        AND bc.id_don_vi_phan_hoi = dv.id_don_vi
                ) AS da_phan_hoi
            FROM e_don_vi dv 
            WHERE (dv.ma_don_vi <> @tchc OR dv.ma_don_vi IS NULL)
                AND (dv.loai COLLATE utf8mb4_general_ci <> 'LANH_DAO')
                AND (dv.email NOT IN ('phongcntt@nctu.edu.vn', 'vpdanguy@nctu.edu.vn', 'ncthang@nctu.edu.vn')
                    AND dv.email IS NOT NULL)
            ORDER BY dv.ten_don_vi ASC, da_phan_hoi DESC
        ";


        return $this->db->query($sql)->result_array();
    }


    public function getAllVanbannoibo($start = 0, $length = 10, $searchValue = null, $orderBy = [], $columns = [], $searchKey = array(), $fromDate = null, $toDate = null, $auth, $dataSource = [])
    {
        $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
        $donviId = $auth['id_don_vi'];

        $isSuperAdmin = false;
        if (isset($auth['vai_tro']) && is_array($auth['vai_tro'])) {
            $vaiTroSuperAdmin = $this->common::VAI_TRO_SUPER_ADMIN;
            foreach ($auth['vai_tro'] as $role) {
                if (isset($role['ql_ma_vai_tro']) && in_array($role['ql_ma_vai_tro'], $vaiTroSuperAdmin)) {
                    $isSuperAdmin = true;
                    break;
                }
            }
        }

        $this->db->from('e_van_ban')
            ->select('
                e_van_ban.*,
                e_loai.ten_loai,
                e_khoi_co_quan.ten_khoi_co_quan,
                e_bao_mat.ten_bao_mat,
                e_tinh_chat.ten_tinh_chat,
                nguoi_tao.ql_nguoi_dung_ho_ten nguoi_tao_ho_ten,
                nguoi_tao.ql_nguoi_dung_email nguoi_tao_email')

            ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
            ->join('e_bao_mat', 'e_bao_mat.id_bao_mat = e_van_ban.id_bao_mat', 'left')
            ->join('e_loai', 'e_loai.id_loai =e_van_ban.id_loai', 'left')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
            ->join('ql_nguoi_dung nguoi_tao', 'nguoi_tao.ql_nguoi_dung_id = e_van_ban.id_nguoi_tao', 'left');

        if (!$isSuperAdmin) {
            $this->db->where('nguoi_tao.id_don_vi', $donviId);
        }

        $this->db->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_NOI_BO);
        $this->db->where('e_van_ban.deleted_at IS NULL');

        $excludeTrash = "NOT EXISTS (SELECT 1 FROM e_van_ban_da_xoa WHERE e_van_ban_da_xoa.id_van_ban = e_van_ban.id_van_ban AND e_van_ban_da_xoa.id_don_vi = " . $this->db->escape($auth['id_don_vi']) . ")";
        $includeTrash = "EXISTS (SELECT 1 FROM e_van_ban_da_xoa WHERE e_van_ban_da_xoa.id_van_ban = e_van_ban.id_van_ban AND e_van_ban_da_xoa.id_don_vi = " . $this->db->escape($auth['id_don_vi']) . ")";

        if (empty($searchKey['selectedClassify']) || $searchKey['selectedClassify'] != 'da_xoa') {
            $this->db->where($excludeTrash, null, false);
        } else {
            $this->db->where($includeTrash, null, false);
        }

        if (!$isSuperAdmin) {
            $this->db->group_start();
            $this->db->where('e_van_ban.id_van_ban IN (SELECT DISTINCT id_van_ban FROM e_nguoi_xem_vbnoibo WHERE ql_nguoi_dung_id = ' . $auth['ql_nguoi_dung_id'] . ')');
            $this->db->or_where('e_van_ban.id_nguoi_tao', $auth['ql_nguoi_dung_id']);
            $this->db->group_end();
        }


        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue && !empty($searchValue)) {
            $escaped = $this->db->escape_like_str($searchValue);

            $this->db->group_start();
            $this->db->like('e_van_ban.ten_van_ban', $escaped);
            $this->db->or_like('e_van_ban.so_van_ban', $escaped);
            $this->db->or_like('e_van_ban.so_hieu_van_ban', $escaped);
            $this->db->or_like('e_van_ban.trich_yeu', $escaped);
            $this->db->or_like('e_van_ban.nguoi_ky', $escaped);
            $this->db->or_like('nguoi_tao.ql_nguoi_dung_ho_ten', $escaped);
            $this->db->or_like('nguoi_tao.ql_nguoi_dung_email', $escaped);
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
            if (!empty($searchKey['year']) && $searchKey['year'] !== 'all_years') {
                $this->db->where("YEAR(e_van_ban.ngay_tao)", $searchKey['year']);
            }

            if (!empty($searchKey['ngay_ky_tu']) && !empty($searchKey['ngay_ky_den'])) {
                $from = $searchKey['ngay_ky_tu'];
                $to   = $searchKey['ngay_ky_den'];
                $this->db->where("DATE(e_van_ban.ngay_ky) BETWEEN '{$from}' AND '{$to}'");
            }

            if (!empty($searchKey['ngay_nhan_tu']) && !empty($searchKey['ngay_nhan_den'])) {
                $from = $searchKey['ngay_nhan_tu'];
                $to   = $searchKey['ngay_nhan_den'];
                $this->db->where("DATE(e_van_ban.ngay_nhan) BETWEEN '{$from}' AND '{$to}'");
            }

            if (!empty($searchKey['thoi_gian_xu_ly_tu']) && !empty($searchKey['thoi_gian_xu_ly_den'])) {
                $from = $searchKey['thoi_gian_xu_ly_tu'];
                $to   = $searchKey['thoi_gian_xu_ly_den'];
                $this->db->where("DATE(e_van_ban.thoi_gian_xu_ly) BETWEEN '{$from}' AND '{$to}'");
            }

            foreach ($searchKey as $key => $value) {
                if (!empty($value)) {
                    switch ($key) {
                        case 'so_hieu_van_ban':
                            $this->db->like('e_van_ban.so_hieu_van_ban', $value);
                            break;
                        case 'trich_yeu':
                            $this->db->like('e_van_ban.trich_yeu', $value);
                            break;
                        case 'id_don_vi_soan':
                            $this->db->where('nguoi_tao.id_don_vi', $value);
                            break;
                        case 'loai_van_ban':
                            $this->db->like('e_loai.id_loai', $value);
                            break;
                        case 'ngay_nhan':
                            $this->db->where('DATE(e_van_ban.ngay_nhan)', $value);
                            break;
                        case 'thoi_gian_xu_ly':
                            $this->db->where('DATE(e_van_ban.thoi_gian_xu_ly)', $value);
                            break;
                        case 'year':
                            // Đã xử lý ở đầu khối searchKey
                            break;
                        case 'selectedClassify':
                            $now = date('Y-m-d');
                            switch ($value) {
                                case 'hom_nay':
                                    $this->db->where("DATE(e_van_ban.ngay_tao)", $now);
                                    break;
                                case '7_ngay':
                                    $sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
                                    $this->db->where("DATE(e_van_ban.ngay_tao) >=", $sevenDaysAgo);
                                    break;
                                case 'trong_thang':
                                    $this->db->where("MONTH(e_van_ban.ngay_tao)", date('m'));
                                    $this->db->where("YEAR(e_van_ban.ngay_tao)", date('Y'));
                                    break;
                                case 'truoc_do':
                                    $firstDayOfMonth = date('Y-m-01');
                                    $this->db->where("DATE(e_van_ban.ngay_tao) <", $firstDayOfMonth);
                                    break;
                                case 'all':
                                default:
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
                'nguoi_tao_ho_ten'      => 'nguoi_tao.ql_nguoi_dung_ho_ten',
                'ten_loai'              => 'e_loai.ten_loai',
                'ten_tinh_chat'         => 'e_tinh_chat.ten_tinh_chat',
                'nguoi_but_phe'         => 'ql_nguoi_dung.ql_nguoi_dung_ho_ten',
                // Mặc định nếu không map thì nó sẽ lấy e_van_ban.[columnName]
            ];
            $this->handleDatatableColumns($columns, $columnMapping);
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query 

        // Xử lý order sau khi xử lý search
        // Xử lý order ưu tiên sortOrder từ popup filter
        if (!empty($searchKey['sortOrder'])) {
            if ($searchKey['sortOrder'] === 'newest') {
                $this->db->order_by('e_van_ban.id_van_ban', 'DESC');
            } else if ($searchKey['sortOrder'] === 'oldest') {
                $this->db->order_by('e_van_ban.id_van_ban', 'ASC');
            }
        } else if (!empty($orderBy)) {
            $this->handleDatatableOrdering($orderBy, $columns);
        } else if (!empty($dataSource['order'])) {
            $this->applyOrdering($dataSource['order']);
        } else {
            // Default order
            $this->db->order_by('e_van_ban.id_van_ban', 'DESC');
        }

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }


        $query = $this->db->get();
        $data = $query->result_array();
        $sql = $this->db->last_query();

        foreach ($data as &$dt) {
            $files = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_file_dinh_kem')->result_array();
            foreach ($files as $key => &$file) {
                $file['duong_dan'] = encryptString($file['duong_dan']);
            }
            $dt['files'] = $files;

            // $dt['files'] = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_file_dinh_kem')->result_array();

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

            $gioiHanDoc = $this->db
                ->select('*')
                ->from('e_nguoi_xem_vbnoibo')
                ->where('id_van_ban', $dt['id_van_ban'])
                ->where('nguoi_dong_so_huu_id', $auth['ql_nguoi_dung_id'])
                ->get()
                ->row_array();

            $dt['co_quyen_dong_so_huu'] = $gioiHanDoc ? true : false;
        }

        $now = date('Y-m-d');
        // $fiveDaysLater = date('Y-m-d', strtotime('5 days'));

        $all_active = (clone $totalRecordsQuery)->where('e_van_ban.deleted_at IS NULL')->where($excludeTrash, null, false)->count_all_results();

        $homnay = (clone $totalRecordsQuery)->where('e_van_ban.deleted_at IS NULL')
            ->where($excludeTrash, null, false)
            ->where('DATE(e_van_ban.ngay_tao)', $now)
            ->count_all_results();

        $sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
        $bayngay = (clone $totalRecordsQuery)->where('e_van_ban.deleted_at IS NULL')
            ->where($excludeTrash, null, false)
            ->where('DATE(e_van_ban.ngay_tao) >=', $sevenDaysAgo)
            ->count_all_results();

        $trong_thang = (clone $totalRecordsQuery)->where('e_van_ban.deleted_at IS NULL')
            ->where($excludeTrash, null, false)
            ->where("MONTH(e_van_ban.ngay_tao)", date('m'))
            ->where("YEAR(e_van_ban.ngay_tao)", date('Y'))
            ->count_all_results();

        $firstDayOfMonth = date('Y-m-01');
        $truoc_do = (clone $totalRecordsQuery)->where('e_van_ban.deleted_at IS NULL')
            ->where($excludeTrash, null, false)
            ->where("DATE(e_van_ban.ngay_tao) <", $firstDayOfMonth)
            ->count_all_results();

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'query' => $this->db->last_query(),
            'data' => $data,
            'sql' => $sql,
            'thoi_han' => [
                'all' => $all_active,
                'hom_nay' => $homnay,
                '7_ngay' => $bayngay,
                'trong_thang' => $trong_thang,
                'truoc_do' => $truoc_do,
            ]
        ];
    }

    public function getListExportVanbannoibo($start = 0, $length = 10, $searchValue = null, $searchKey = array(), $fromDate = null, $toDate = null, $auth)
    {
        $this->db->from('e_van_ban')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
            ->select(
                "
                    DATE_FORMAT(ngay_ky,'%Y') as nam, 
                    DATE_FORMAT(ngay_nhan,'%d/%m/%Y') as ngay_nhan, 
                    so_hieu_van_ban, ten_co_quan AS noi_nhan, 
                    DATE_FORMAT(ngay_ban_hanh,'%d/%m/%Y') as ngay_ban_hanh, 
                    trich_yeu AS noi_dung, 
                    nguoi_ky,
                    DATE_FORMAT(ngay_ky, '%d/%m/%Y') as ngay_ky,
                    e_van_ban.id_van_ban
                "
            )->where('e_van_ban.id_don_vi_soan', $auth['id_don_vi']);

        $this->db->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_NOI_BO);
        $this->db->where('e_van_ban.deleted_at IS NULL');

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('e_van_ban.ten_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.so_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.so_hieu_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.trich_yeu', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                $this->db->where('e_van_ban.' . $key, $value);
            }
        }

        if ($fromDate && $toDate) {
            $this->db->where("ngay_ky BETWEEN '{$fromDate}' AND '{$toDate}'");
        }

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        $query = $this->db->get();
        $data = $query->result_array();

        return $data;
    }

    public function getAllBaoCaoPhanHoiVanBanDi($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array(), $fromDate = null, $toDate = null)
    {
        $this->db->from('e_van_ban')
            ->select('e_van_ban.*, 
                        e_tinh_chat.ten_tinh_chat, 
                        e_loai.ten_loai, 
                        e_khoi_co_quan.ten_khoi_co_quan, 
                        e_co_quan.ten_co_quan,
                        e_bao_mat.ten_bao_mat
                        ')
            ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
            ->join('e_bao_mat', 'e_bao_mat.id_bao_mat = e_van_ban.id_bao_mat', 'left')
            ->join('e_loai', 'e_loai.id_loai = e_van_ban.id_loai', 'left')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
            ->join('e_bao_cao', 'e_van_ban.id_van_ban = e_bao_cao.id_van_ban', 'left');



        $this->db->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DI)
            ->group_by('e_van_ban.id_van_ban');
        $this->db->where('e_van_ban.deleted_at IS NULL');

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        // $this->db->group_start();
        // $this->db->where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value']);
        // $this->db->or_where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value']);
        // $this->db->or_where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DEN['CHUA_PHAN_HOI']['value']);
        // $this->db->or_where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DEN['DA_PHAN_HOI']['value']);
        // $this->db->group_end();
        $this->db->where_in('e_van_ban.trang_thai', [
            $this->common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value'],
            $this->common::STATUS_VAN_BAN_DI['CHUA_PHAN_HOI']['value'],
            $this->common::STATUS_VAN_BAN_DI['DA_PHAN_HOI']['value'],
        ]);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('e_van_ban.ten_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.so_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.so_hieu_van_ban', $searchValue);
            $this->db->or_like('e_van_ban.trich_yeu', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                $this->db->where('e_van_ban.' . $key, $value);
            }
        }

        if ($fromDate && $toDate) {
            $this->db->where("ngay_ky BETWEEN '{$fromDate}' AND '{$toDate}'");
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query


        // Xử lý order ưu tiên sortOrder từ popup filter
        if (!empty($searchKey['sortOrder'])) {
            if ($searchKey['sortOrder'] === 'newest') {
                $this->db->order_by('e_van_ban.id_van_ban', 'DESC');
            } else if ($searchKey['sortOrder'] === 'oldest') {
                $this->db->order_by('e_van_ban.id_van_ban', 'ASC');
            }
        } else if (!empty($orderBy)) {
            $order = $orderBy['order'];
            if (!empty($order)) {
                $orderColumnIndex = $order[0]['column'];
                $orderDir = $order[0]['dir'];

                $columns = $orderBy['columns'];
                $filed = $columns[$orderColumnIndex]['data'];

                if (!empty($orderDir)) {
                    $this->db->order_by('e_van_ban.' . $filed, $orderDir);
                }
            }
        } else {
            // Default order
            $this->db->order_by('e_van_ban.id_van_ban', 'DESC');
        }



        if ($length != '-1') {
            $this->db->limit($length, $start);
        }


        $query = $this->db->get();
        $data = $query->result_array();

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

            // $dt['files'] = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_file_dinh_kem')->result_array();

            $baocao = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_bao_cao')->result_array();
            if (!empty($baocao)) {
                $donviIds = array_column($baocao, 'id_don_vi_phan_hoi');
                $dv = $this->db->from('e_don_vi')
                    ->join('e_bao_cao', 'e_bao_cao.id_don_vi_phan_hoi = e_don_vi.id_don_vi', 'left')
                    ->where_in('id_don_vi', $donviIds)
                    ->select('e_don_vi.*, e_bao_cao.noi_dung, e_bao_cao.ngay_bao_cao, e_bao_cao.dinh_kem, e_bao_cao.files_dinh_kem')
                    ->where('e_bao_cao.id_van_ban', $dt['id_van_ban'])
                    ->get()
                    ->result_array();

                foreach ($dv as &$item) {
                    // $item['dinh_kem_url'] = base_url() . '/' . $item['dinh_kem'];

                    $files_dinh_kem = json_decode($item['files_dinh_kem'], true);
                    foreach ($files_dinh_kem as &$file) {
                        $file['file_path'] = base_url() . '/' . $file['file_path'];
                    }
                    unset($file);

                    $item['files_dinh_kem'] = $files_dinh_kem;
                }
                unset($item);

                $dt['don_vi_da_phan_hoi'] = $dv;
                // $dt['don_vi_da_phan_hoi'] = $this->db->where_in('id_don_vi', $donviIds)->get('e_don_vi')->result_array();
            } else {
                $dt['don_vi_da_phan_hoi'] = [];
            }

            $xuly = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_xu_ly')->row_array();
            if ($xuly) {
                $donvixuly = $this->db->where('id_xu_ly', $xuly['id_xu_ly'])->where('don_vi_xu_ly_chinh IS NOT NULL')->get('e_don_vi_xu_ly')->result_array();
                $donviphoihop = $this->db->where('id_xu_ly', $xuly['id_xu_ly'])->where('don_vi_xu_ly_chinh IS NULL')->get('e_don_vi_xu_ly')->result_array();
                if (!empty($donvixuly)) {
                    $donvixulyIds = array_column($donvixuly, 'id_don_vi');
                    $dt['don_vi_xu_ly'] = $this->db->where_in('id_don_vi', $donvixulyIds)->get('e_don_vi')->result_array();
                } else {
                    $dt['don_vi_xu_ly'] = [];
                }

                if (!empty($donviphoihop)) {
                    $donviphoihopIds = array_column($donviphoihop, 'id_don_vi');
                    $dt['don_vi_phoi_hop'] = $this->db->where_in('id_don_vi', $donviphoihopIds)->get('e_don_vi')->result_array();
                } else {
                    $dt['don_vi_phoi_hop'] = [];
                }
            } else {
                $dt['don_vi_xu_ly'] = [];
                $dt['don_vi_phoi_hop'] = [];
            }

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
            $xuly = $this->db->where('id_van_ban', $dt['id_van_ban'])->get('e_xu_ly')->row_array();
            $donvixuly = [];
            if ($xuly) {
                $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
                $donvixuly = $this->db
                    ->join('e_don_vi', 'e_don_vi_xu_ly.id_don_vi = e_don_vi.id_don_vi')
                    ->where('e_don_vi_xu_ly.id_xu_ly', $xuly['id_xu_ly'])
                    ->select('e_don_vi_xu_ly.id_don_vi_xu_ly, e_don_vi_xu_ly.don_vi_xu_ly_chinh, e_don_vi_xu_ly.da_xem, e_don_vi.id_don_vi, e_don_vi.ten_don_vi, e_don_vi.ma_don_vi')
                    ->group_by('e_don_vi_xu_ly.id_don_vi_xu_ly')
                    ->get('e_don_vi_xu_ly')
                    ->result_array();
            }
            $dt['don_vi_xu_ly'] = $donvixuly;
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }
    function getTrangThaiValueFromLabel($label)
    {
        foreach (Common::STATUS_VAN_BAN_DEN as $status) {
            if (isset($status['label']) && strtolower($status['label']) == strtolower($label)) {
                return $status['value'];
            }
        }
        return null;
    }


    // Lấy văn bản theo id
    public function detail_vanbanden($id, $auth = NULL)
    {
        $this->db->from('e_van_ban')
            ->select('
                    e_van_ban.id_van_ban,
                    e_van_ban.so_van_ban,
                    e_van_ban.so_van_ban_hau_to,
                    e_van_ban.so_hieu_van_ban,
                    e_van_ban.loai_van_ban,
                    e_loai.id_loai,
                    e_loai.ten_loai,
                    e_van_ban.trich_yeu,
                    e_van_ban.ngay_nhan,
                    e_van_ban.ngay_ban_hanh,
                    DATE_FORMAT(e_van_ban.thoi_gian_xu_ly, "%Y-%m-%d")  AS thoi_gian_xu_ly,
                    e_van_ban.trang_thai,
                    e_khoi_co_quan.id_khoi_co_quan,
                    e_khoi_co_quan.ten_khoi_co_quan,
                    e_co_quan.id_co_quan,
                    e_co_quan.ten_co_quan,
                    e_hinh_thuc.id_hinh_thuc,
                    e_hinh_thuc.ten_hinh_thuc,
                    e_van_ban.linh_vuc,
                    e_tinh_chat.id_tinh_chat,
                    e_tinh_chat.ten_tinh_chat,
                    e_tinh_chat.class_color AS color_tinh_chat,
                    e_bao_mat.id_bao_mat,
                    e_bao_mat.ten_bao_mat,
                    e_bao_mat.class_color AS color_bao_mat,
                    e_don_vi.id_don_vi,
                    e_don_vi.ten_don_vi ho_so_don_vi,
                    e_van_ban.noi_luu_tru,
                    e_van_ban.trang_thai_huy_vb,
                    e_van_ban.luu_tru_noi_bo,
                    e_van_ban.nguoi_ky,
                    e_van_ban.ngay_ky,
                    e_van_ban.van_ban_chi_doc,
                    e_van_ban.ngay_tao,
                    e_van_ban.ngay_sua,
                    e_van_ban.deleted_at,
                    ql_nguoi_dung.ql_nguoi_dung_id,
                    ql_nguoi_dung.ql_nguoi_dung_ho_ten AS nguoi_but_phe,
                    e_but_phe.ngay_but_phe,
                    e_but_phe.noi_dung_but_phe
            ')
            ->join('e_loai', 'e_loai.id_loai = e_van_ban.id_loai', 'left')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
            ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
            ->join('e_bao_mat', 'e_bao_mat.id_bao_mat = e_van_ban.id_bao_mat', 'left')
            ->join('e_but_phe', 'e_but_phe.id_van_ban = e_van_ban.id_van_ban', 'left')
            ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_but_phe.id_nguoi_but_phe', 'left')
            ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'left')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = e_van_ban.id_don_vi', 'left')
            ->join('e_hinh_thuc', 'e_hinh_thuc.id_hinh_thuc = e_van_ban.id_hinh_thuc', 'left');

        $this->db->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DEN);
        // $this->db->where('e_van_ban.deleted_at IS NULL');
        $this->db->where('e_van_ban.id_van_ban', $id);

        $query = $this->db->get();
        $data = $query->row_array();
        $sql = $this->db->last_query();


        $data['files'] = $this->getFiles_byidVanban($data['id_van_ban']);

        $data['but_phe'] = $this->getButphe_byidVanban($data['id_van_ban']);

        $data['xu_ly'] =  $this->getXuly_byidVanban($data['id_van_ban']);

        $phanhoivanban = $this->db
            ->select('
                e_bao_cao.*,
                e_bao_cao.ngay_tao,
                e_don_vi.ten_don_vi
            ')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = e_bao_cao.id_don_vi_phan_hoi', 'left')
            ->where('id_van_ban', $data['id_van_ban'])
            ->order_by('e_bao_cao.ngay_tao', 'ASC')
            ->get('e_bao_cao')->result_array();

        foreach ($phanhoivanban as $key => &$phanhoi) {
            $files_phanhoi  = json_decode($phanhoi['files_dinh_kem'], true);
            if (json_last_error() === JSON_ERROR_NONE && !empty($files_phanhoi)) {
                foreach ($files_phanhoi as &$fph) {
                    $fph['file_path'] = encryptString($fph['file_path']);
                }
                $phanhoi['files_dinh_kem'] = $files_phanhoi;
            } else {
                $phanhoi['files_dinh_kem'] = [];
            }

            $phanhoi['send'] = false;
            if ($auth['id_don_vi'] == $phanhoi['id_don_vi_phan_hoi']) {
                $phanhoi['send'] = true;
            }
            $phanhoi['files_dinh_kem'] = $files_phanhoi;
        }
        $data['phan_hoi'] = $phanhoivanban ?? [];



        // Bắt buộc để ở cuối khi dữ liệu đã được lấy đầy đủ
        $data['timeline'] =  $this->getTimelineVanBanDen($data);

        return [
            'data' =>  $data,
            'sql' => $sql
        ];
    }

    public function detail_vanbandendonvi($id, $auth = NULL)
    {
        $this->db->from('e_van_ban')
            ->select('
                    e_van_ban.id_van_ban,
                    e_van_ban.so_van_ban,
                    e_van_ban.so_van_ban_hau_to,
                    e_van_ban.so_hieu_van_ban,
                    e_van_ban.loai_van_ban,
                    e_loai.id_loai,
                    e_loai.ten_loai,
                    e_van_ban.trich_yeu,
                    e_van_ban.ngay_nhan,
                    e_van_ban.ngay_ban_hanh,
                    DATE_FORMAT(e_van_ban.thoi_gian_xu_ly, "%Y-%m-%d")  AS thoi_gian_xu_ly,
                    e_van_ban.trang_thai,
                    e_khoi_co_quan.id_khoi_co_quan,
                    e_khoi_co_quan.ten_khoi_co_quan,
                    e_co_quan.id_co_quan,
                    e_co_quan.ten_co_quan,
                    e_hinh_thuc.id_hinh_thuc,
                    e_hinh_thuc.ten_hinh_thuc,
                    e_van_ban.linh_vuc,
                    e_tinh_chat.id_tinh_chat,
                    e_tinh_chat.ten_tinh_chat,
                    e_tinh_chat.class_color AS color_tinh_chat,
                    e_bao_mat.id_bao_mat,
                    e_bao_mat.ten_bao_mat,
                    e_bao_mat.class_color AS color_bao_mat,
                    e_don_vi.id_don_vi,
                    e_don_vi.ten_don_vi ho_so_don_vi,
                    e_van_ban.noi_luu_tru,
                    e_van_ban.trang_thai_huy_vb,
                    e_van_ban.luu_tru_noi_bo,
                    e_van_ban.nguoi_ky,
                    e_van_ban.ngay_ky,
                    e_van_ban.van_ban_chi_doc,
                    e_van_ban.ngay_tao,
                    e_van_ban.ngay_sua,
                    e_van_ban.deleted_at,
                    ql_nguoi_dung.ql_nguoi_dung_id,
                    ql_nguoi_dung.ql_nguoi_dung_ho_ten AS nguoi_but_phe,
                    e_but_phe.ngay_but_phe,
                    e_but_phe.noi_dung_but_phe,
                    nguoi_tao.id_don_vi id_don_vi_nguoi_tao
            ')
            ->join('e_loai', 'e_loai.id_loai = e_van_ban.id_loai', 'left')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
            ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
            ->join('e_bao_mat', 'e_bao_mat.id_bao_mat = e_van_ban.id_bao_mat', 'left')
            ->join('e_but_phe', 'e_but_phe.id_van_ban = e_van_ban.id_van_ban', 'left')
            ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_but_phe.id_nguoi_but_phe', 'left')
            ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'left')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = e_van_ban.id_don_vi', 'left')
            ->join('e_hinh_thuc', 'e_hinh_thuc.id_hinh_thuc = e_van_ban.id_hinh_thuc', 'left')
            ->join('ql_nguoi_dung as nguoi_tao', 'nguoi_tao.ql_nguoi_dung_id = e_van_ban.id_nguoi_tao', 'left');

        // $this->db->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DEN);
        $this->db->where('e_van_ban.deleted_at IS NULL');
        $this->db->where('e_van_ban.id_van_ban', $id);

        $query = $this->db->get();
        $data = $query->row_array();
        $sql = $this->db->last_query();

        $data['files'] = $this->getFiles_byidVanban($data['id_van_ban'], 1);
        // $data['files_tchc'] = $this->getFiles_byidVanban($data['id_van_ban'], 0);

        $data['but_phe'] = $this->getButphe_byidVanban($data['id_van_ban']);

        $data['xu_ly'] =  $this->getXuly_byidVanbandi($data['id_van_ban']);

        $phanhoivanban = $this->db
            ->select('
                e_bao_cao.*,
                DATE_FORMAT(ngay_tao, "%H:%i:%s - %d/%m/%Y") AS ngay_tao,
                e_bao_cao.ngay_tao,
                e_don_vi.ten_don_vi
            ')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = e_bao_cao.id_don_vi_phan_hoi', 'left')
            ->where('id_van_ban', $data['id_van_ban'])
            ->order_by('e_bao_cao.ngay_tao', 'ASC')
            ->get('e_bao_cao')->result_array();

        foreach ($phanhoivanban as $key => &$phanhoi) {
            $files_phanhoi  = json_decode($phanhoi['files_dinh_kem'], true);
            if (json_last_error() === JSON_ERROR_NONE && !empty($files_phanhoi)) {
                foreach ($files_phanhoi as &$fph) {
                    $fph['file_path'] = encryptString($fph['file_path']);
                }
                $phanhoi['files_dinh_kem'] = $files_phanhoi;
            } else {
                $phanhoi['files_dinh_kem'] = [];
            }

            $phanhoi['send'] = false;
            if ($auth['id_don_vi'] == $phanhoi['id_don_vi_phan_hoi']) {
                $phanhoi['send'] = true;
            }
            $phanhoi['files_dinh_kem'] = $files_phanhoi;
        }
        $data['bao_cao'] = $phanhoivanban ?? [];
        $data['phan_hoi'] = $phanhoivanban ?? [];

        $data['e_vb_khoi_co_quan'] = $this->E_vb_khoi_co_quan_model
            ->select('e_vb_khoi_co_quan.*, e_khoi_co_quan.ten_khoi_co_quan')
            ->leftJoin('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_vb_khoi_co_quan.id_khoi_co_quan')
            ->where('id_van_ban', $id)
            ->get();

        $data['e_vb_co_quan'] = $this->E_vb_co_quan_model
            ->select('e_vb_co_quan.*, e_co_quan.ten_co_quan')
            ->leftJoin('e_co_quan', 'e_co_quan.id_co_quan = e_vb_co_quan.id_co_quan')
            ->where('id_van_ban', $id)
            ->get();

        $data['e_don_vi_xu_ly'] = $this->E_xu_ly_model
            ->select('e_don_vi_xu_ly.*, e_don_vi.ten_don_vi, e_don_vi.loai')
            ->leftJoin('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly')
            ->leftJoin('e_don_vi', 'e_don_vi.id_don_vi = e_don_vi_xu_ly.id_don_vi')
            ->where('e_xu_ly.id_van_ban', $id)
            // ->where('e_don_vi_xu_ly.id_nguoi_xu_ly IS NULL')
            ->groupBy('e_don_vi_xu_ly.id_don_vi')
            ->get();

        $data['e_nguoi_xu_ly'] = $this->E_xu_ly_model
            ->select('e_don_vi_xu_ly.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten, e_don_vi.ten_don_vi')
            ->leftJoin('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly')
            ->leftJoin('e_don_vi', 'e_don_vi.id_don_vi = e_don_vi_xu_ly.id_don_vi')
            ->leftJoin('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_don_vi_xu_ly.id_nguoi_xu_ly')
            ->where('e_xu_ly.id_van_ban', $id)
            ->where('e_don_vi_xu_ly.id_nguoi_xu_ly IS NOT NULL')
            ->get();

        // Dùng cho Vite
        $data['thong_tin_ban_hanh'] = [
            'e_vb_co_quan' => $this->E_vb_co_quan_model
                ->select('e_vb_co_quan.*, e_co_quan.ten_co_quan')
                ->leftJoin('e_co_quan', 'e_co_quan.id_co_quan = e_vb_co_quan.id_co_quan')
                ->where('id_van_ban', $id)
                ->get(),
            'e_don_vi_xu_ly' => $this->E_xu_ly_model
                ->select('e_don_vi_xu_ly.*, e_don_vi.ten_don_vi')
                ->leftJoin('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly')
                ->leftJoin('e_don_vi', 'e_don_vi.id_don_vi = e_don_vi_xu_ly.id_don_vi')
                ->where('e_xu_ly.id_van_ban', $id)
                ->groupBy('e_don_vi_xu_ly.id_don_vi')
                ->get(),
            'e_nguoi_xu_ly' => $this->E_xu_ly_model
                ->select('e_don_vi_xu_ly.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten, e_don_vi.ten_don_vi')
                ->leftJoin('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly')
                ->leftJoin('e_don_vi', 'e_don_vi.id_don_vi = e_don_vi_xu_ly.id_don_vi')
                ->leftJoin('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_don_vi_xu_ly.id_nguoi_xu_ly')
                ->where('e_xu_ly.id_van_ban', $id)
                ->where('e_don_vi_xu_ly.id_nguoi_xu_ly IS NOT NULL')
                ->where('ql_nguoi_dung.active_flag', 1)
                ->get(),

        ];


        // Bắt buộc để ở cuối khi dữ liệu đã được lấy đầy đủ
        if ($data['loai_van_ban'] == Common::VAN_BAN_DEN) {
            $data['timeline'] =  $this->getTimelineVanBanDen($data);
        } else if ($data['loai_van_ban'] == Common::VAN_BAN_DI) {
            $data['timeline'] =  $this->getTimelineVanBanDi($data);
        }

        return [
            'data' => $data,
            'sql' => $sql
        ];
    }

    public function detail_vanbandi($id, $auth = NULL)
    {
        $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
        $this->db->from('e_van_ban')
            ->select('
                    e_van_ban.id_van_ban,
                    e_van_ban.so_van_ban,
                    e_van_ban.so_van_ban_hau_to,
                    e_van_ban.so_hieu_van_ban,
                    e_van_ban.loai_van_ban,
                    e_loai.id_loai,
                    e_loai.ten_loai,
                    e_van_ban.trich_yeu,
                    e_van_ban.ngay_nhan,
                    e_van_ban.ngay_ban_hanh,
                    DATE_FORMAT(e_van_ban.thoi_gian_xu_ly, "%Y-%m-%d")  AS thoi_gian_xu_ly,
                    e_van_ban.trang_thai,
                    e_van_ban.id_don_vi_soan,
                    e_khoi_co_quan.id_khoi_co_quan,
                    e_khoi_co_quan.ten_khoi_co_quan,
                    e_co_quan.id_co_quan,
                    e_co_quan.ten_co_quan,
                    e_hinh_thuc.id_hinh_thuc,
                    e_hinh_thuc.ten_hinh_thuc,
                    e_van_ban.linh_vuc,
                    e_tinh_chat.id_tinh_chat,
                    e_tinh_chat.ten_tinh_chat,
                    e_tinh_chat.class_color AS color_tinh_chat,
                    e_bao_mat.id_bao_mat,
                    e_bao_mat.ten_bao_mat,
                    e_bao_mat.class_color AS color_bao_mat,
                    e_don_vi.id_don_vi,
                    e_don_vi.ten_don_vi ho_so_don_vi,
                    e_van_ban.noi_luu_tru,
                    e_van_ban.trang_thai_huy_vb,
                    e_van_ban.luu_tru_noi_bo,
                    e_van_ban.nguoi_ky,
                    e_van_ban.ngay_ky,
                    e_van_ban.van_ban_chi_doc,
                    e_van_ban.ngay_tao,
                    e_van_ban.ngay_sua,
                    e_van_ban.deleted_at,
                    e_van_ban.ghi_chu,
                    e_van_ban.ngay_tra_loi_cv_den,
                    e_van_ban.linh_vuc,
                    e_van_ban.tra_loi_cv_den,
                    e_van_ban.nguoi_soan_vb_di,
            ')
            ->join('e_loai', 'e_loai.id_loai = e_van_ban.id_loai', 'left')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
            ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
            ->join('e_bao_mat', 'e_bao_mat.id_bao_mat = e_van_ban.id_bao_mat', 'left')
            ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'left')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = e_van_ban.id_don_vi', 'left')
            ->join('e_hinh_thuc', 'e_hinh_thuc.id_hinh_thuc = e_van_ban.id_hinh_thuc', 'left');

        $this->db->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DI);
        // $this->db->where('e_van_ban.deleted_at IS NULL');
        $this->db->where('e_van_ban.id_van_ban', $id);

        $query = $this->db->get();
        $data = $query->row_array();
        $sql = $this->db->last_query();

        $is_public = $this->E_file_dinh_kem_model
            ->where('id_van_ban', $id)
            ->first();
        $data['is_public'] = $is_public ? $is_public['is_public'] : false;

        $data['files'] = $this->E_file_dinh_kem_model
            ->where('id_van_ban', $id)
            ->where('la_file_ban_hanh', 1)
            ->get();

        if ($data['files']) {
            foreach ($data['files'] as &$file) {
                $file['duong_dan'] = encryptString($file['duong_dan']);
            }
            unset($file);
        }

        $data['files_tchc'] = $this->E_file_dinh_kem_model
            ->where('id_van_ban', $id)
            ->where('la_file_ban_hanh', 0)
            ->get();
        if ($data['files_tchc']) {
            foreach ($data['files_tchc'] as &$file) {
                $file['duong_dan'] = encryptString($file['duong_dan']);
            }
            unset($file);
        }

        $data['xu_ly'] =  $this->getXuly_byidVanbandi($data['id_van_ban']);

        $data['e_vb_khoi_co_quan'] = $this->E_vb_khoi_co_quan_model
            ->select('e_vb_khoi_co_quan.*, e_khoi_co_quan.ten_khoi_co_quan')
            ->leftJoin('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_vb_khoi_co_quan.id_khoi_co_quan')
            ->where('id_van_ban', $id)
            ->get();

        $data['e_vb_co_quan'] = $this->E_vb_co_quan_model
            ->select('e_vb_co_quan.*, e_co_quan.ten_co_quan')
            ->leftJoin('e_co_quan', 'e_co_quan.id_co_quan = e_vb_co_quan.id_co_quan')
            ->where('id_van_ban', $id)
            ->get();

        $data['e_don_vi_xu_ly'] = $this->E_xu_ly_model
            ->select('e_don_vi_xu_ly.*, e_don_vi.ten_don_vi')
            ->leftJoin('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly')
            ->leftJoin('e_don_vi', 'e_don_vi.id_don_vi = e_don_vi_xu_ly.id_don_vi')
            ->where('e_xu_ly.id_van_ban', $id)
            // ->where('e_don_vi_xu_ly.id_nguoi_xu_ly IS NULL')
            ->groupBy('e_don_vi_xu_ly.id_don_vi')
            ->get();

        $e_nguoi_xu_ly = $this->E_xu_ly_model
            ->select('e_don_vi_xu_ly.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten, e_don_vi.ten_don_vi')
            ->leftJoin('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly')
            ->leftJoin('e_don_vi', 'e_don_vi.id_don_vi = e_don_vi_xu_ly.id_don_vi')
            ->leftJoin('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_don_vi_xu_ly.id_nguoi_xu_ly')
            ->where('e_xu_ly.id_van_ban', $id)
            ->where('e_don_vi_xu_ly.id_nguoi_xu_ly IS NOT NULL')
            ->where('ql_nguoi_dung.active_flag', 1)
            ->get();
        $data['e_nguoi_xu_ly'] = $e_nguoi_xu_ly ?? [];


        $data['e_vb_hinh_thuc'] = $this->E_vb_hinh_thuc_model
            ->select('e_vb_hinh_thuc.*, e_hinh_thuc.ten_hinh_thuc')
            ->leftJoin('e_hinh_thuc', 'e_hinh_thuc.id_hinh_thuc = e_vb_hinh_thuc.id_hinh_thuc')
            ->where('id_van_ban', $id)
            ->get();

        $data['color_bao_mat'] = ($this->E_bao_mat_model->find($data['id_bao_mat']))['class_color'];
        $data['color_tinh_chat'] = ($this->E_tinh_chat_model->find($data['id_tinh_chat']))['class_color'];
        $data['ten_don_vi_soan'] = $this->E_don_vi_model->find($data['id_don_vi_soan'])['ten_don_vi'];
        $data['ho_so_don_vi'] = $this->E_don_vi_model->find($data['id_don_vi'])['ten_don_vi'];
        $data['hinh_thuc_gui_vb'] = '';

        $ds_hinh_thuc = $this->E_vb_hinh_thuc_model
            ->select('ten_hinh_thuc')
            ->leftJoin('e_hinh_thuc', 'e_vb_hinh_thuc.id_hinh_thuc = e_hinh_thuc.id_hinh_thuc')
            ->where('e_vb_hinh_thuc.id_van_ban', $id)
            ->get();
        if ($ds_hinh_thuc) {
            $arr_hinh_thuc = [];
            foreach ($ds_hinh_thuc as $ht) {
                $arr_hinh_thuc[] = $ht['ten_hinh_thuc'];
            }

            $vb['hinh_thuc_gui_vb'] = implode(', ', $arr_hinh_thuc);
        }

        $phanhoivanban = $this->db
            ->select('
                e_bao_cao.*,
                DATE_FORMAT(ngay_tao, "%H:%i:%s - %d/%m/%Y") AS ngay_tao,
                e_don_vi.ten_don_vi
            ')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = e_bao_cao.id_don_vi_phan_hoi', 'left')
            ->where('id_van_ban', $data['id_van_ban'])
            ->order_by('e_bao_cao.ngay_tao', 'ASC')
            ->get('e_bao_cao')->result_array();

        foreach ($phanhoivanban as $key => &$phanhoi) {
            $files_phanhoi  = json_decode($phanhoi['files_dinh_kem'], true);
            if (json_last_error() === JSON_ERROR_NONE && !empty($files_phanhoi)) {
                foreach ($files_phanhoi as &$fph) {
                    $fph['file_path'] = encryptString($fph['file_path']);
                }
                $phanhoi['files_dinh_kem'] = $files_phanhoi;
            } else {
                $phanhoi['files_dinh_kem'] = [];
            }

            $phanhoi['send'] = false;
            if ($auth['id_don_vi'] == $phanhoi['id_don_vi_phan_hoi']) {
                $phanhoi['send'] = true;
            }
            $phanhoi['files_dinh_kem'] = $files_phanhoi;

            $baocaodaxem = $this->db
                ->select('*')
                ->from('e_bao_cao_da_xem')
                ->where('id_van_ban', $id)
                ->where('id_bao_cao', $phanhoi['id_bao_cao'])
                ->get()
                ->row_array();

            if ($baocaodaxem) {
                $phanhoi['da_xem'] = $baocaodaxem;
            } else {
                $phanhoi['da_xem'] = [];
            }
        }
        $data['phan_hoi'] = $phanhoivanban ?? [];

        //Xem danh sách đơn vị xử lý và phối hợp
        $xuly = $this->db->where('id_van_ban', $data['id_van_ban'])->get('e_xu_ly')->row_array();
        $donvixuly = [];
        if ($xuly) {
            $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
            $donvixuly = $this->db
                ->join('e_don_vi', 'e_don_vi_xu_ly.id_don_vi = e_don_vi.id_don_vi')
                ->where('e_don_vi_xu_ly.id_xu_ly', $xuly['id_xu_ly'])
                ->select('e_don_vi_xu_ly.id_don_vi_xu_ly, e_don_vi_xu_ly.don_vi_xu_ly_chinh, e_don_vi_xu_ly.da_xem, e_don_vi.id_don_vi, e_don_vi.ten_don_vi, e_don_vi.ma_don_vi')
                ->group_by('e_don_vi_xu_ly.id_don_vi_xu_ly')
                ->get('e_don_vi_xu_ly')
                ->result_array();

            $donvixulyIds = array_unique(array_column($donvixuly, 'id_don_vi_xu_ly'));
            if (!empty($donvixulyIds)) {
                $donvixulydaxem = $this->db
                    ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_don_vi_xu_ly_da_xem.ql_nguoi_dung_id')
                    ->where_in('e_don_vi_xu_ly_da_xem.id_don_vi_xu_ly', $donvixulyIds)
                    ->select('e_don_vi_xu_ly_da_xem.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten, ql_nguoi_dung.ql_nguoi_dung_email, ql_nguoi_dung.ql_nguoi_dung_avatar')
                    ->get('e_don_vi_xu_ly_da_xem')
                    ->result_array();
                foreach ($donvixuly as &$dvxl) {
                    $dvxl['danh_sach_da_xem'] = array_filter($donvixulydaxem, function ($item) use ($dvxl) {
                        return $item['id_don_vi_xu_ly'] == $dvxl['id_don_vi_xu_ly'];
                    });
                }
            }
        }
        $data['don_vi_xu_ly'] = $donvixuly;

        // Dùng cho Vite
        $data['thong_tin_ban_hanh'] = [
            'e_vb_co_quan' => $this->E_vb_co_quan_model
                ->select('e_vb_co_quan.*, e_co_quan.ten_co_quan')
                ->leftJoin('e_co_quan', 'e_co_quan.id_co_quan = e_vb_co_quan.id_co_quan')
                ->where('id_van_ban', $id)
                ->get(),
            'e_don_vi_xu_ly' => $this->E_xu_ly_model
                ->select('e_don_vi_xu_ly.*, e_don_vi.ten_don_vi')
                ->leftJoin('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly')
                ->leftJoin('e_don_vi', 'e_don_vi.id_don_vi = e_don_vi_xu_ly.id_don_vi')
                ->where('e_xu_ly.id_van_ban', $id)
                ->groupBy('e_don_vi_xu_ly.id_don_vi')
                ->get(),
            'e_nguoi_xu_ly' => $this->E_xu_ly_model
                ->select('e_don_vi_xu_ly.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten, e_don_vi.ten_don_vi')
                ->leftJoin('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly')
                ->leftJoin('e_don_vi', 'e_don_vi.id_don_vi = e_don_vi_xu_ly.id_don_vi')
                ->leftJoin('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_don_vi_xu_ly.id_nguoi_xu_ly')
                ->where('e_xu_ly.id_van_ban', $id)
                ->where('e_don_vi_xu_ly.id_nguoi_xu_ly IS NOT NULL')
                ->where('ql_nguoi_dung.active_flag', 1)
                ->get(),

        ];

        // Bắt buộc để ở cuối khi dữ liệu đã được lấy đầy đủ
        $data['timeline'] =  $this->getTimelineVanBanDi($data);

        return [
            'data' => $data,
            'sql' => $sql
        ];
    }

    public function detail_vanbandidonvi($id, $auth = NULL)
    {
        $this->db->from('e_van_ban')
            ->select('
                    e_van_ban.id_van_ban,
                    e_van_ban.so_van_ban,
                    e_van_ban.so_van_ban_hau_to,
                    e_van_ban.so_hieu_van_ban,
                    e_van_ban.loai_van_ban,
                    e_loai.id_loai,
                    e_loai.ten_loai,
                    e_van_ban.trich_yeu,
                    e_van_ban.ngay_nhan,
                    e_van_ban.ngay_ban_hanh,
                    DATE_FORMAT(e_van_ban.thoi_gian_xu_ly, "%Y-%m-%d")  AS thoi_gian_xu_ly,
                    e_van_ban.trang_thai,
                    e_van_ban.id_don_vi_soan,
                    e_khoi_co_quan.id_khoi_co_quan,
                    e_khoi_co_quan.ten_khoi_co_quan,
                    e_co_quan.id_co_quan,
                    e_co_quan.ten_co_quan,
                    e_hinh_thuc.id_hinh_thuc,
                    e_hinh_thuc.ten_hinh_thuc,
                    e_van_ban.linh_vuc,
                    e_tinh_chat.id_tinh_chat,
                    e_tinh_chat.ten_tinh_chat,
                    e_tinh_chat.class_color AS color_tinh_chat,
                    e_bao_mat.id_bao_mat,
                    e_bao_mat.ten_bao_mat,
                    e_bao_mat.class_color AS color_bao_mat,
                    e_don_vi.id_don_vi,
                    e_don_vi.ten_don_vi ho_so_don_vi,
                    e_van_ban.noi_luu_tru,
                    e_van_ban.trang_thai_huy_vb,
                    e_van_ban.luu_tru_noi_bo,
                    e_van_ban.nguoi_ky,
                    e_van_ban.ngay_ky,
                    e_van_ban.van_ban_chi_doc,
                    e_van_ban.ngay_tao,
                    e_van_ban.ngay_sua,
                    e_van_ban.deleted_at,
            ')
            ->join('e_loai', 'e_loai.id_loai = e_van_ban.id_loai', 'left')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
            ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
            ->join('e_bao_mat', 'e_bao_mat.id_bao_mat = e_van_ban.id_bao_mat', 'left')
            ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'left')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = e_van_ban.id_don_vi', 'left')
            ->join('e_hinh_thuc', 'e_hinh_thuc.id_hinh_thuc = e_van_ban.id_hinh_thuc', 'left');

        $this->db->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DI);
        $this->db->where('e_van_ban.deleted_at IS NULL');
        $this->db->where('e_van_ban.id_van_ban', $id);

        $query = $this->db->get();
        $data = $query->row_array();
        $sql = $this->db->last_query();


        $data['files'] = $this->E_file_dinh_kem_model
            ->where('id_van_ban', $id)
            ->where('la_file_ban_hanh', 1)
            ->get();
        if ($data['files']) {
            foreach ($data['files'] as &$file) {
                $file['duong_dan'] = encryptString($file['duong_dan']);
            }
            unset($file);
        }

        $data['files_tchc'] = $this->E_file_dinh_kem_model
            ->where('id_van_ban', $id)
            ->where('la_file_ban_hanh', 0)
            ->get();
        if ($data['files_tchc']) {
            foreach ($data['files_tchc'] as &$file) {
                $file['duong_dan'] = encryptString($file['duong_dan']);
            }
            unset($file);
        }

        $data['xu_ly'] =  $this->getXuly_byidVanbandi($data['id_van_ban']);

        $data['e_vb_khoi_co_quan'] = $this->E_vb_khoi_co_quan_model
            ->select('e_vb_khoi_co_quan.*, e_khoi_co_quan.ten_khoi_co_quan')
            ->leftJoin('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_vb_khoi_co_quan.id_khoi_co_quan')
            ->where('id_van_ban', $id)
            ->get();

        $data['e_vb_co_quan'] = $this->E_vb_co_quan_model
            ->select('e_vb_co_quan.*, e_co_quan.ten_co_quan')
            ->leftJoin('e_co_quan', 'e_co_quan.id_co_quan = e_vb_co_quan.id_co_quan')
            ->where('id_van_ban', $id)
            ->get();

        $data['e_don_vi_xu_ly'] = $this->E_xu_ly_model
            ->select('e_don_vi_xu_ly.*, e_don_vi.ten_don_vi')
            ->leftJoin('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly')
            ->leftJoin('e_don_vi', 'e_don_vi.id_don_vi = e_don_vi_xu_ly.id_don_vi')
            ->where('e_xu_ly.id_van_ban', $id)
            ->where('e_don_vi_xu_ly.id_nguoi_xu_ly IS NULL')
            ->get();

        $e_nguoi_xu_ly = $this->E_xu_ly_model
            ->select('e_don_vi_xu_ly.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten, e_don_vi.ten_don_vi')
            ->leftJoin('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly')
            ->leftJoin('e_don_vi', 'e_don_vi.id_don_vi = e_don_vi_xu_ly.id_don_vi')
            ->leftJoin('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_don_vi_xu_ly.id_nguoi_xu_ly')
            ->where('e_xu_ly.id_van_ban', $id)
            ->where('e_don_vi_xu_ly.id_nguoi_xu_ly IS NOT NULL')
            ->get();
        $data['e_nguoi_xu_ly'] = $e_nguoi_xu_ly ?? [];


        $data['e_vb_hinh_thuc'] = $this->E_vb_hinh_thuc_model
            ->select('e_vb_hinh_thuc.*, e_hinh_thuc.ten_hinh_thuc')
            ->leftJoin('e_hinh_thuc', 'e_hinh_thuc.id_hinh_thuc = e_vb_hinh_thuc.id_hinh_thuc')
            ->where('id_van_ban', $id)
            ->get();

        $data['color_bao_mat'] = ($this->E_bao_mat_model->find($data['id_bao_mat']))['class_color'];
        $data['color_tinh_chat'] = ($this->E_tinh_chat_model->find($data['id_tinh_chat']))['class_color'];
        $data['ten_don_vi_soan'] = $this->E_don_vi_model->find($data['id_don_vi_soan'])['ten_don_vi'];
        $data['ho_so_don_vi'] = $this->E_don_vi_model->find($data['id_don_vi'])['ten_don_vi'];
        $data['hinh_thuc_gui_vb'] = '';

        $ds_hinh_thuc = $this->E_vb_hinh_thuc_model
            ->select('ten_hinh_thuc')
            ->leftJoin('e_hinh_thuc', 'e_vb_hinh_thuc.id_hinh_thuc = e_hinh_thuc.id_hinh_thuc')
            ->where('e_vb_hinh_thuc.id_van_ban', $id)
            ->get();
        if ($ds_hinh_thuc) {
            $arr_hinh_thuc = [];
            foreach ($ds_hinh_thuc as $ht) {
                $arr_hinh_thuc[] = $ht['ten_hinh_thuc'];
            }

            $vb['hinh_thuc_gui_vb'] = implode(', ', $arr_hinh_thuc);
        }

        $phanhoivanban = $this->db
            ->select('
                e_bao_cao.*,
                DATE_FORMAT(ngay_tao, "%H:%i:%s - %d/%m/%Y") AS ngay_tao,
                e_don_vi.ten_don_vi
            ')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = e_bao_cao.id_don_vi_phan_hoi', 'left')
            ->where('id_van_ban', $data['id_van_ban'])
            ->order_by('e_bao_cao.ngay_tao', 'ASC')
            ->get('e_bao_cao')->result_array();

        foreach ($phanhoivanban as $key => &$phanhoi) {
            $files_phanhoi  = json_decode($phanhoi['files_dinh_kem'], true);
            if (json_last_error() === JSON_ERROR_NONE && !empty($files_phanhoi)) {
                foreach ($files_phanhoi as &$fph) {
                    $fph['file_path'] = encryptString($fph['file_path']);
                }
                $phanhoi['files_dinh_kem'] = $files_phanhoi;
            } else {
                $phanhoi['files_dinh_kem'] = [];
            }

            $phanhoi['send'] = false;
            if ($auth['id_don_vi'] == $phanhoi['id_don_vi_phan_hoi']) {
                $phanhoi['send'] = true;
            }
            $phanhoi['files_dinh_kem'] = $files_phanhoi;

            $baocaodaxem = $this->db
                ->select('*')
                ->from('e_bao_cao_da_xem')
                ->where('id_van_ban', $id)
                ->where('id_bao_cao', $phanhoi['id_bao_cao'])
                ->get()
                ->row_array();

            if ($baocaodaxem) {
                $phanhoi['da_xem'] = $baocaodaxem;
            } else {
                $phanhoi['da_xem'] = [];
            }
        }
        $data['phan_hoi'] = $phanhoivanban ?? [];

        // Dùng cho Vite
        $data['thong_tin_ban_hanh'] = [
            'e_vb_co_quan' => $this->E_vb_co_quan_model
                ->select('e_vb_co_quan.*, e_co_quan.ten_co_quan')
                ->leftJoin('e_co_quan', 'e_co_quan.id_co_quan = e_vb_co_quan.id_co_quan')
                ->where('id_van_ban', $id)
                ->get(),
            'e_don_vi_xu_ly' => $this->E_xu_ly_model
                ->select('e_don_vi_xu_ly.*, e_don_vi.ten_don_vi')
                ->leftJoin('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly')
                ->leftJoin('e_don_vi', 'e_don_vi.id_don_vi = e_don_vi_xu_ly.id_don_vi')
                ->where('e_xu_ly.id_van_ban', $id)
                ->groupBy('e_don_vi_xu_ly.id_don_vi')
                ->get(),
            'e_nguoi_xu_ly' => $this->E_xu_ly_model
                ->select('e_don_vi_xu_ly.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten, e_don_vi.ten_don_vi')
                ->leftJoin('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly')
                ->leftJoin('e_don_vi', 'e_don_vi.id_don_vi = e_don_vi_xu_ly.id_don_vi')
                ->leftJoin('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_don_vi_xu_ly.id_nguoi_xu_ly')
                ->where('e_xu_ly.id_van_ban', $id)
                ->where('e_don_vi_xu_ly.id_nguoi_xu_ly IS NOT NULL')
                ->where('ql_nguoi_dung.active_flag', 1)
                ->get(),

        ];

        // Bắt buộc để ở cuối khi dữ liệu đã được lấy đầy đủ
        $data['timeline'] =  $this->getTimelineVanBanDi($data);

        return [
            'data' => $data,
            'sql' => $sql
        ];
    }

    public function getFiles_v2($type, $offset = 0, $limit = 20, $filters = [])
    {
        $this->db->select('f.id_file_dinh_kem, f.ten_file_goc, f.dung_luong, f.duong_dan, f.loai_file, f.ngay_tao, 
                       f.is_public, v.ten_van_ban, v.so_van_ban, v.so_hieu_van_ban, u.ql_nguoi_dung_ho_ten as author_name')
            ->from('e_file_dinh_kem f')
            ->join('e_van_ban v', 'f.id_van_ban = v.id_van_ban', 'left')
            ->join('ql_nguoi_dung u', 'v.id_nguoi_tao = u.ql_nguoi_dung_id', 'left');
        if ($type === 'trash') {
            $this->db->where('v.deleted_at IS NOT NULL');
        } else {
            if ($type != null) {
                $this->db->where('v.loai_van_ban', $type);
            }
            $this->db->where('v.deleted_at IS NULL');
        }

        // Filter tìm kiếm tên file
        if (!empty($filters['search'])) {
            $this->db->like('f.ten_file_goc', $filters['search']);
        }

        // Filter loại file
        if (!empty($filters['fileType'])) {
            $this->db->where('f.loai_file', $filters['fileType']);
        }

        // Filter tác giả
        if (!empty($filters['author'])) {
            $this->db->where('v.id_nguoi_tao', $filters['author']);
        }

        // Filter khoảng ngày tạo file
        if (!empty($filters['date'])) {
            $this->db->where('f.ngay_tao >=', $filters['date']['start']);
            $this->db->where('f.ngay_tao <=', $filters['date']['end']);
        }

        // Đếm tổng
        $total = $this->db->count_all_results('', false);

        // Phân trang
        $this->db->limit($limit, $offset);

        $query = $this->db->get();

        $data = array_map(function ($file) {
            $file['duong_dan'] = encryptString($file['duong_dan']);
            return $file;
        }, $query->result_array());

        return [
            'data' => $data,
            'total' => $total
        ];
    }


    //Danh sách file đính kèm của văn bản
    public function getFiles_byLoaiVanBan($type_vanban, $params = [], $auth = null)
    {
        $keyword   = isset($params['keyword']) ? $this->db->escape_like_str($params['keyword']) : '';
        $page      = isset($params['page']) ? max(1, (int)$params['page']) : 1;
        $per_page  = isset($params['per_page']) ? max(1, (int)$params['per_page']) : 10;
        $offset    = ($page - 1) * $per_page;

        // Xây câu SQL id_van_ban theo loại
        $sqlString = '';
        if ($type_vanban) {
            switch ($type_vanban) {
                case 1:
                    $sqlString = 'SELECT id_van_ban FROM e_van_ban WHERE deleted_at IS NULL AND loai_van_ban = 1';
                    break;
                case 2:
                    $sqlString = 'SELECT id_van_ban FROM e_van_ban WHERE deleted_at IS NULL AND loai_van_ban = 2';
                    break;
                case 3:
                    $sqlString = 'SELECT id_van_ban FROM e_van_ban WHERE deleted_at IS NULL AND loai_van_ban = 3';
                    break;
                case 4:
                    if (!empty($auth)) {
                        $sqlString = 'SELECT DISTINCT e_van_ban.id_van_ban
                                  FROM e_van_ban
                                  LEFT JOIN e_xu_ly ON e_xu_ly.id_van_ban = e_van_ban.id_van_ban
                                  LEFT JOIN e_don_vi_xu_ly ON e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly
                                  WHERE e_van_ban.deleted_at IS NULL 
                                  AND e_don_vi_xu_ly.id_don_vi = ' . (int)$auth['id_don_vi'];
                    }
                    break;
                case 5:
                    $sqlString = 'SELECT id_van_ban 
                              FROM e_van_ban 
                              LEFT JOIN ql_nguoi_dung ON ql_nguoi_dung.ql_nguoi_dung_id = e_van_ban.id_van_ban
                              WHERE deleted_at IS NULL AND loai_van_ban = 2
                              AND ql_nguoi_dung.id_don_vi = ' . (int)$auth['id_don_vi'];
                    break;
            }
        }

        if (empty($sqlString)) {
            return ['files' => [], 'total' => 0];
        }

        $this->db->from('e_file_dinh_kem')
            ->where("id_van_ban IN ($sqlString)");

        // Thêm lọc theo keyword nếu có
        if (!empty($keyword)) {
            $this->db->group_start()
                ->like('e_file_dinh_kem.noi_dung_trich_xuat', $keyword)
                ->or_like('e_file_dinh_kem.ten_file_goc', $keyword)
                ->group_end();
        }

        // Thêm lọc theo mốc thời gian nếu có
        if (!empty($params['range'])) {
            switch ($params['range']) {
                case 'today':
                    $this->db->where('DATE(e_file_dinh_kem.ngay_tao)', date('Y-m-d'));
                    break;

                case 'yesterday':
                    $yesterday = date('Y-m-d', strtotime('-1 day'));
                    $this->db->where('DATE(e_file_dinh_kem.ngay_tao)', $yesterday);
                    break;

                case 'last7': // 7 ngày gần nhất
                    $startDate = date('Y-m-d', strtotime('-6 day'));
                    $this->db->where("DATE(e_file_dinh_kem.ngay_tao) BETWEEN '{$startDate}' AND '" . date('Y-m-d') . "'", null, false);
                    break;

                case 'thisMonth':
                    $startMonth = date('Y-m-01');
                    $this->db->where("DATE(e_file_dinh_kem.ngay_tao) BETWEEN '{$startMonth}' AND '" . date('Y-m-d') . "'", null, false);
                    break;

                case 'lastMonth':
                    $startLastMonth = date('Y-m-01', strtotime('-1 month'));
                    $endLastMonth = date('Y-m-t', strtotime('-1 month'));
                    $this->db->where("DATE(e_file_dinh_kem.ngay_tao) BETWEEN '{$startLastMonth}' AND '{$endLastMonth}'", null, false);
                    break;

                default:
                    break;
            }
        }



        // Lấy tổng số kết quả
        $total = $this->db->count_all_results('', false); // false để giữ nguyên query

        // Lấy dữ liệu phân trang
        $this->db->limit($per_page, $offset);
        $files = $this->db->get()->result_array();

        if ($files) {
            foreach ($files as &$f) {
                $f['duong_dan'] = encryptString($f['duong_dan']);
            }
        }

        return [
            'files' => $files,
            'total' => $total,
            'totalSearch' => $total,
            'sql' => $this->db->last_query(),
        ];
    }

    private function getFiles_byidVanban($id, $la_file_ban_hanh = null)
    {
        $this->db->where('id_van_ban', $id);

        // nếu có truyền la_file_ban_hanh thì mới lọc theo field này
        if ($la_file_ban_hanh !== null) {
            $this->db->where('la_file_ban_hanh', $la_file_ban_hanh);
        }

        $files = $this->db->get('e_file_dinh_kem')->result_array();

        foreach ($files as &$f) {
            $f['duong_dan'] = encryptString($f['duong_dan']);
        }

        return $files;
    }


    private function getButphe_byidVanban($id)
    {
        $butphe = $this->db
            ->select('e_but_phe.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten AS nguoi_but_phe_ho_ten')
            ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_but_phe.id_nguoi_but_phe', 'left')
            ->where('id_van_ban', $id)
            ->get('e_but_phe')
            ->row_array();
        if ($butphe) {
            if (!empty($butphe['file_but_phe'])) {
                $files = json_decode($butphe['file_but_phe'], true);
                if (is_array($files) && !empty($files)) {
                    foreach ($files as $key => &$f) {
                        $f['file_path'] = encryptString($f['file_path']);
                    }
                    $butphe['file_but_phe'] = $files;
                }
            }
        } else {
            $butphe = [];
        }
        return $butphe;
    }

    private function getXuly_byidVanban($id)
    {
        $xuly = $this->db->where('id_van_ban', $id)->get('e_xu_ly')->row_array();
        if ($xuly) {
            $donvixuly = $this->db
                ->join('e_don_vi', 'e_don_vi.id_don_vi = e_don_vi_xu_ly.id_don_vi', 'left')
                ->where('id_xu_ly', $xuly['id_xu_ly'])
                ->get('e_don_vi_xu_ly')
                ->result_array();

            $dv_chinh_ids = [];
            $dv_chinh_ds = [];
            $rawMain = array_filter($donvixuly, fn($dv) => $dv['don_vi_xu_ly_chinh'] == 1);

            foreach ($rawMain as $dvxlpp) {
                if (!in_array($dvxlpp['id_don_vi'], $dv_chinh_ids)) {
                    $dv_chinh_ids[] = $dvxlpp['id_don_vi'];
                    $dvxlpp['nguoi_xu_ly_ids'] = [$dvxlpp['id_nguoi_xu_ly']];
                    $dvxlpp['don_vi_xu_ly_ids'] = [$dvxlpp['id_don_vi_xu_ly']];
                    $dv_chinh_ds[] = $dvxlpp;
                } else {
                    foreach ($dv_chinh_ds as &$dv) {
                        if ($dv['id_don_vi'] == $dvxlpp['id_don_vi']) {
                            $dv['nguoi_xu_ly_ids'][] = $dvxlpp['id_nguoi_xu_ly'];
                            $dv['don_vi_xu_ly_ids'][] = $dvxlpp['id_don_vi_xu_ly'];
                            if ($dvxlpp['da_xem']) $dv['da_xem'] = $dvxlpp['da_xem'];
                            break;
                        }
                    }
                }
            }
            $xuly['don_vi_xu_ly_chinh'] = $dv_chinh_ds;

            $dv_phoi_hop_ids = [];
            $dv_phoi_hop_ds = [];
            $rawSub = array_filter($donvixuly, fn($dv) => is_null($dv['don_vi_xu_ly_chinh']) || $dv['don_vi_xu_ly_chinh'] != 1);

            foreach ($rawSub as $dvxlpp) {
                if (!in_array($dvxlpp['id_don_vi'], $dv_phoi_hop_ids)) {
                    $dv_phoi_hop_ids[] = $dvxlpp['id_don_vi'];
                    $dvxlpp['nguoi_xu_ly_ids'] = [$dvxlpp['id_nguoi_xu_ly']];
                    $dvxlpp['don_vi_xu_ly_ids'] = [$dvxlpp['id_don_vi_xu_ly']];
                    $dv_phoi_hop_ds[] = $dvxlpp;
                } else {
                    foreach ($dv_phoi_hop_ds as &$dv) {
                        if ($dv['id_don_vi'] == $dvxlpp['id_don_vi']) {
                            $dv['nguoi_xu_ly_ids'][] = $dvxlpp['id_nguoi_xu_ly'];
                            $dv['don_vi_xu_ly_ids'][] = $dvxlpp['id_don_vi_xu_ly'];
                            if ($dvxlpp['da_xem']) $dv['da_xem'] = $dvxlpp['da_xem'];
                            break;
                        }
                    }
                }
            }
            $xuly['don_vi_xu_ly_phoi_hop'] = $dv_phoi_hop_ds;

            foreach ($xuly['don_vi_xu_ly_chinh'] as $key => &$dvxlc) {
                $dvxlc['nguoi_xem'] = $this->db
                    ->select('e_don_vi_xu_ly_da_xem.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten, ql_nguoi_dung.ql_nguoi_dung_email')
                    ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_don_vi_xu_ly_da_xem.ql_nguoi_dung_id', 'left')
                    ->where_in('id_don_vi_xu_ly', $dvxlc['don_vi_xu_ly_ids'])
                    ->get('e_don_vi_xu_ly_da_xem')
                    ->result_array();
                $dvxlc['so_nguoi_xem'] = count($dvxlc['nguoi_xem']);
            }

            foreach ($xuly['don_vi_xu_ly_phoi_hop'] as $key => &$dvxlc) {
                $dvxlc['nguoi_xem'] = $this->db
                    ->select('e_don_vi_xu_ly_da_xem.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten, ql_nguoi_dung.ql_nguoi_dung_email')
                    ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_don_vi_xu_ly_da_xem.ql_nguoi_dung_id', 'left')
                    ->where_in('id_don_vi_xu_ly', $dvxlc['don_vi_xu_ly_ids'])
                    ->get('e_don_vi_xu_ly_da_xem')
                    ->result_array();
                $dvxlc['so_nguoi_xem'] = count($dvxlc['nguoi_xem']);
            }
        } else {
            $xuly = [];
        }
        return $xuly;
    }

    private function getXuly_byidVanbandi($id)
    {
        $xuly = $this->db->where('id_van_ban', $id)->get('e_xu_ly')->row_array();
        if ($xuly) {
            $donvixuly = $this->db
                ->join('e_don_vi', 'e_don_vi.id_don_vi = e_don_vi_xu_ly.id_don_vi', 'left')
                ->where('id_xu_ly', $xuly['id_xu_ly'])
                // ->where('id_nguoi_xu_ly IS NULL')
                ->get('e_don_vi_xu_ly')
                ->result_array();
            $xuly['don_vi_xu_ly_chinh'] = array_values(array_filter($donvixuly, fn($dv) => $dv['don_vi_xu_ly_chinh'] == 1));
            // $xuly['id_don_vi_xu_ly'] = array_column(array_filter($donvixuly, fn($dv) => $dv['don_vi_xu_ly_chinh'] == 1), 'id_don_vi');
            $xuly['don_vi_xu_ly_phoi_hop'] =  array_values(array_filter($donvixuly, fn($dv) => is_null($dv['don_vi_xu_ly_chinh']) || $dv['don_vi_xu_ly_chinh'] != 1));
            // $xuly['id_don_vi_phoi_hop'] = array_column(array_filter($donvixuly, fn($dv) => is_null($dv['don_vi_xu_ly_chinh'])), 'id_don_vi');

            $dv_chinh_ids = []; // Theo đơn vị
            $dv_chinh_ds = [];
            foreach ($xuly['don_vi_xu_ly_chinh'] as $key => &$dvxlchinh) {
                if (!in_array($dvxlchinh['id_don_vi'], $dv_chinh_ids)) {
                    $dv_chinh_ids[] = $dvxlchinh['id_don_vi'];
                    $dv_chinh_ds[] = [
                        'id_xu_ly' => $dvxlchinh['id_xu_ly'],
                        'id_don_vi' => $dvxlchinh['id_don_vi'],
                        'ten_don_vi' => $dvxlchinh['ten_don_vi'],
                        'ma_don_vi' => $dvxlchinh['ma_don_vi'],
                        'don_vi_xu_ly_chinh' => $dvxlchinh['don_vi_xu_ly_chinh'],
                        'loai' => $dvxlchinh['loai'],
                        'email' => $dvxlchinh['email'],
                        'ngay_tao' => $dvxlchinh['ngay_tao'],
                        'da_xem' => $dvxlchinh['da_xem'],
                        'nguoi_xu_ly_ids' => [$dvxlchinh['id_nguoi_xu_ly']],
                        'don_vi_xu_ly_ids' => [$dvxlchinh['id_don_vi_xu_ly']],
                    ];
                } else if (in_array($dvxlchinh['id_don_vi'], $dv_chinh_ids)) {
                    foreach ($dv_chinh_ds as &$dv) {
                        if ($dv['id_don_vi'] == $dvxlchinh['id_don_vi']) {
                            $dv['nguoi_xu_ly_ids'][] = $dvxlchinh['id_nguoi_xu_ly'];
                            $dv['don_vi_xu_ly_ids'][] = $dvxlchinh['id_don_vi_xu_ly'];

                            if ($dvxlchinh['da_xem']) {
                                $dv['da_xem'] = true;
                            }
                            break;
                        }
                    }
                }
            }

            foreach ($dv_chinh_ds as $key => &$dvxlc) {
                $dvxlc['nguoi_xem'] = $this->db
                    ->select('e_don_vi_xu_ly_da_xem.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten, ql_nguoi_dung.ql_nguoi_dung_email')
                    ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_don_vi_xu_ly_da_xem.ql_nguoi_dung_id', 'left')
                    ->where_in('id_don_vi_xu_ly', $dvxlc['don_vi_xu_ly_ids'])
                    ->get('e_don_vi_xu_ly_da_xem')
                    ->result_array();
                $dvxlc['so_nguoi_xem'] = count($dvxlc['nguoi_xem']);
            }
            $xuly['don_vi_xu_ly_chinh'] = $dv_chinh_ds;


            $dv_phoi_hop_ids = []; // Theo đơn vị
            $dv_phoi_dop_ds = [];
            foreach ($xuly['don_vi_xu_ly_phoi_hop'] as $key => &$dvxlpp) {
                if (!in_array($dvxlpp['id_don_vi'], $dv_phoi_hop_ids)) {
                    $dv_phoi_hop_ids[] = $dvxlpp['id_don_vi'];
                    $dv_phoi_dop_ds[] = [
                        'id_xu_ly' => $dvxlpp['id_xu_ly'],
                        'id_don_vi' => $dvxlpp['id_don_vi'],
                        'ten_don_vi' => $dvxlpp['ten_don_vi'],
                        'ma_don_vi' => $dvxlpp['ma_don_vi'],
                        'don_vi_xu_ly_chinh' => $dvxlpp['don_vi_xu_ly_chinh'],
                        'loai' => $dvxlpp['loai'],
                        'email' => $dvxlpp['email'],
                        'ngay_tao' => $dvxlpp['ngay_tao'],
                        'da_xem' => $dvxlpp['da_xem'],
                        'nguoi_xu_ly_ids' => [$dvxlpp['id_nguoi_xu_ly']],
                        'don_vi_xu_ly_ids' => [$dvxlpp['id_don_vi_xu_ly']],
                    ];
                } else if (in_array($dvxlpp['id_don_vi'], $dv_phoi_hop_ids)) {
                    foreach ($dv_phoi_dop_ds as &$dv) {
                        if ($dv['id_don_vi'] == $dvxlpp['id_don_vi']) {
                            $dv['nguoi_xu_ly_ids'][] = $dvxlpp['id_nguoi_xu_ly'];
                            $dv['don_vi_xu_ly_ids'][] = $dvxlpp['id_don_vi_xu_ly'];

                            if ($dvxlpp['da_xem']) {
                                $dv['da_xem'] = true;
                            }
                            break;
                        }
                    }
                }
            }

            foreach ($dv_phoi_dop_ds as $key => &$dv_phoi_dop) {
                $dv_phoi_dop['nguoi_xem'] = $this->db
                    ->select('e_don_vi_xu_ly_da_xem.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten, ql_nguoi_dung.ql_nguoi_dung_email')
                    ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = e_don_vi_xu_ly_da_xem.ql_nguoi_dung_id', 'left')
                    ->where_in('id_don_vi_xu_ly', $dv_phoi_dop['don_vi_xu_ly_ids'])
                    ->get('e_don_vi_xu_ly_da_xem')
                    ->result_array();
                $dv_phoi_dop['so_nguoi_xem'] = count($dv_phoi_dop['nguoi_xem']);
            }

            $xuly['don_vi_xu_ly_phoi_hop'] = $dv_phoi_dop_ds;
        } else {
            $xuly = [];
        }
        return $xuly;
    }

    private function getBaocao_byidVanban($id) {}


    private function getTimelineVanBanDen($data)
    {
        if (empty($data)) {
            return [];
        }
        $vanban = $data;
        $but_phe = $data['but_phe'] ?? [];
        $xu_ly = $data['xu_ly'] ?? [];
        $phan_hoi = $data['phan_hoi'] ?? [];

        //Danh sách đơn vị xử lý chính và phối hợp 
        $extractDonVi = function ($ds) {
            return array_map(function ($item) {
                return [
                    'id_don_vi' => $item['id_don_vi'] ?? null,
                    'ten_don_vi' => $item['ten_don_vi'] ?? null,
                    'ngay_tao' => $item['ngay_tao'] ?? null,
                ];
            }, $ds ?? []);
        };

        $dvxl_chinh =  $extractDonVi($xu_ly['don_vi_xu_ly_chinh'] ?? []) ?? [];
        $dvxl_phoi_hop = $extractDonVi($xu_ly['don_vi_xu_ly_phoi_hop'] ?? []) ?? [];


        // Xử lý đơn vị cho PHAN_HOI
        // Lấy tất cả đơn vị và lọc ra chỉ xuất hiện 1 lần
        $all_donvi = array_merge($dvxl_chinh, $dvxl_phoi_hop);
        if (!empty($all_donvi)) {
            $donvi_map = [];
            foreach ($all_donvi as $dv) {
                $id = $dv['id_don_vi'];
                if (!isset($donvi_map[$id]) || strtotime($dv['ngay_tao']) > strtotime($donvi_map[$id]['ngay_tao'])) {
                    $donvi_map[$id] = $dv;
                }
            }
            $all_donvi = array_values($donvi_map);
            $ids_don_vi_phan_hoi = array_values(array_unique(array_column($phan_hoi, 'id_don_vi_phan_hoi')));

            $ds_phan_hoi_don_vi = array_map(function ($donvi) use ($ids_don_vi_phan_hoi) {
                $donvi['da_phan_hoi'] = in_array($donvi['id_don_vi'], $ids_don_vi_phan_hoi);
                return $donvi;
            }, array_values($donvi_map));

            $className_phanhoi = !empty($ds_phan_hoi_don_vi) ? (in_array(false, array_column($ds_phan_hoi_don_vi, 'da_phan_hoi')) ? 'progress' : 'completed') : 'pending';
            $date_phan_hoi = !empty($ds_phan_hoi_don_vi) ? min(array_column($ds_phan_hoi_don_vi, 'ngay_tao')) : '';
        } else {
            // Xử lý khi không có đơn vị
            $className_phanhoi = empty($xu_ly) ? 'pending' : 'completed';
            $date_phan_hoi = '';
            $ds_phan_hoi_don_vi = [];
        }

        // Xác định class và ngày hoàn thành
        if ($vanban['trang_thai'] == Common::STATUS_VAN_BAN_DEN['HOAN_THANH']['value']) {
            $className_hoanthanh = 'completed';
            $date_hoanthanh = $vanban['ngay_sua'];
        } elseif (!empty($ds_phan_hoi_don_vi)) {
            $has_chua_phan_hoi = in_array(false, array_column($ds_phan_hoi_don_vi, 'da_phan_hoi'));
            $className_hoanthanh = $has_chua_phan_hoi ? 'pending' : 'completed';
            $date_hoanthanh = $has_chua_phan_hoi ? '' : $vanban['ngay_sua'];
        } else {
            $className_hoanthanh = 'pending';
            $date_hoanthanh = '';
        }

        if (!empty($ds_phan_hoi_don_vi)) {
            foreach ($ds_phan_hoi_don_vi as &$dvph) {
                $dvph['ngay_bao_bao'] = $this->db
                    ->select('ngay_tao')
                    ->from('e_bao_cao')
                    ->where([
                        'id_van_ban' => $data['id_van_ban'],
                        'id_don_vi_phan_hoi' => $dvph['id_don_vi']
                    ])
                    ->get()
                    ->row('ngay_tao') ?? '';
            }
        }

        $timeline_vanbanden = [
            0 => [
                'code' => 'TIEP_NHAN',
                'label' => 'Tiếp nhận',
                'description' => 'Phòng Tổ chức - Hành chính tiếp nhận văn bản.',
                'icon' => 'fa fa-inbox',
                'class_name' => !empty($vanban) ? 'completed' : 'progress',
                'date_time' => $vanban['ngay_tao'] ?? '',
                'data' => []
            ],
            1 => [
                'code' => 'BUT_PHE',
                'label' => 'Bút phê',
                'description' => 'Lãnh đạo đã xem xét và phê duyệt văn bản.',
                'icon' => 'fa fa-pencil',
                'class_name' =>  $this->getStatus($but_phe, $vanban),
                'date_time' => $but_phe['ngay_tao'] ?? '',
                'data' => []
            ],
            2 => [
                'code' => 'CHUYEN_DON_VI',
                'label' => 'Chuyển đơn vị',
                'description' => 'Văn bản được chuyển đến đơn vị để thực hiện nội dung.',
                'icon' => 'fa fa-exchange',
                'class_name' =>  $this->getStatus($xu_ly, $but_phe),
                'date_time' => $xu_ly['ngay_tao'] ?? '',
                'data' => [
                    'don_vi_xu_ly_chinh' => $dvxl_chinh,
                    'don_vi_xu_ly_phoi_hop' => $dvxl_phoi_hop,
                ]
            ],
            3 => [
                'code' => 'DON_VI_PHAN_HOI',
                'label' => 'Đơn vị phản hồi',
                'description' => 'Các đơn vị đã phản hồi văn bản.',
                'icon' => 'fa fa-reply',
                'class_name' => $className_phanhoi,
                'date_time' => $date_phan_hoi,
                'data' => $ds_phan_hoi_don_vi
            ],
            4 => [
                'code' => 'HOAN_THANH',
                'label' => 'Hoàn thành',
                'description' => 'Quy trình xử lý văn bản đã hoàn tất.',
                'icon' => 'fa fa-check',
                'class_name' => $className_hoanthanh,
                'date_time' => $date_hoanthanh,
                'data' => []
            ]
        ];
        return $timeline_vanbanden;
    }

    private function getTimelineVanBanDi($data)
    {
        if (empty($data)) {
            return [];
        }
        $vanban = $data;
        $xu_ly = $data['xu_ly'] ?? [];
        $phan_hoi = $data['phan_hoi'] ?? [];

        //Danh sách đơn vị xử lý chính và phối hợp
        $dvxl_chinh = [];
        $dvxl_phoi_hop = [];
        if (!empty($xu_ly['don_vi_xu_ly_chinh'])) {
            $dvxl_chinh = array_map(function ($item) {
                return [
                    'id_don_vi' => $item['id_don_vi'] ?? null,
                    'ten_don_vi' => $item['ten_don_vi'] ?? null,
                    'ngay_tao' => $item['ngay_tao'] ?? null,
                ];
            }, $xu_ly['don_vi_xu_ly_chinh'] ?? []);
        }
        if (!empty($xu_ly['don_vi_xu_ly_phoi_hop'])) {
            $dvxl_phoi_hop = array_map(function ($item) {
                return [
                    'id_don_vi' => $item['id_don_vi'] ?? null,
                    'ten_don_vi' => $item['ten_don_vi'] ?? null,
                    'ngay_tao' => $item['ngay_tao'] ?? null,
                ];
            }, $xu_ly['don_vi_xu_ly_phoi_hop'] ?? []);
        }

        // // Danh sách đơn vị đã phản hồi
        // $don_vi_phan_hoi = [];
        // if (!empty($data['phan_hoi'])) {
        //     $don_vi_phan_hoi = array_values(array_reduce($data['phan_hoi'], function ($carry, $item) {
        //         $id = $item['id_don_vi_phan_hoi'];
        //         if (!isset($carry[$id])) {
        //             $carry[$id] = [
        //                 'id' => $id,
        //                 'ten' => $item['ten_don_vi'],
        //                 'ngay_tao' => $item['ngay_tao'],
        //             ];
        //         }
        //         return $carry;
        //     }, []));
        // }

        // // Hoàn thành văn bản
        // $tong_don_vi = array_merge(
        //     $data['xu_ly']['don_vi_xu_ly_chinh'] ?? [],
        //     $data['xu_ly']['don_vi_xu_ly_phoi_hop'] ?? []
        // );

        // $ds_id_don_vi = array_unique(array_column($tong_don_vi, 'id_don_vi'));
        // $ds_id_phan_hoi = array_unique(array_column($data['phan_hoi'], 'id_don_vi_phan_hoi'));

        // $hoan_thanh = count($ds_id_don_vi) > 0 && count(array_intersect($ds_id_don_vi, $ds_id_phan_hoi)) === count($ds_id_don_vi)
        //     ? 'completed'
        //     : (empty($data['phan_hoi']) ? 'pending' : 'progress');

        // $ngay_hoan_thanh = '';
        // if (!empty($data['phan_hoi'])) {
        //     $ngay_hoan_thanh = max(array_column($data['phan_hoi'], 'ngay_tao'));
        //     list($gio, $ngay) = array_map('trim', explode('-', $ngay_hoan_thanh));
        //     $ngay_hoan_thanh = $ngay . ' ' . $gio;
        // }

        // Xử lý đơn vị cho PHAN_HOI
        // Lấy tất cả đơn vị và lọc ra chỉ xuất hiện 1 lần
        $all_donvi = array_merge($dvxl_chinh, $dvxl_phoi_hop);
        if (!empty($all_donvi)) {
            $donvi_map = [];
            foreach ($all_donvi as $dv) {
                $id = $dv['id_don_vi'];
                if (!isset($donvi_map[$id]) || strtotime($dv['ngay_tao']) > strtotime($donvi_map[$id]['ngay_tao'])) {
                    $donvi_map[$id] = $dv;
                }
            }
            $all_donvi = array_values($donvi_map);
            $ids_don_vi_phan_hoi = array_values(array_unique(array_column($phan_hoi, 'id_don_vi_phan_hoi')));

            $ds_phan_hoi_don_vi = array_map(function ($donvi) use ($ids_don_vi_phan_hoi) {
                $donvi['da_phan_hoi'] = in_array($donvi['id_don_vi'], $ids_don_vi_phan_hoi);
                return $donvi;
            }, array_values($donvi_map));

            $className_phanhoi = !empty($ds_phan_hoi_don_vi) ? (in_array(false, array_column($ds_phan_hoi_don_vi, 'da_phan_hoi')) ? 'progress' : 'completed') : 'pending';
            $date_phan_hoi = !empty($ds_phan_hoi_don_vi) ? min(array_column($ds_phan_hoi_don_vi, 'ngay_tao')) : '';
        } else {
            // Xử lý khi không có đơn vị
            $className_phanhoi = empty($xu_ly) ? 'pending' : 'completed';
            $date_phan_hoi = '';
            $ds_phan_hoi_don_vi = [];
        }

        // Xác định class và ngày hoàn thành
        if ($vanban['trang_thai'] == Common::STATUS_VAN_BAN_DI['HOAN_THANH']['value']) {
            $className_hoanthanh = 'completed';
            $date_hoanthanh = $vanban['ngay_sua'];
        } elseif (!empty($ds_phan_hoi_don_vi)) {
            $has_chua_phan_hoi = in_array(false, array_column($ds_phan_hoi_don_vi, 'da_phan_hoi'));
            $className_hoanthanh = $has_chua_phan_hoi ? 'pending' : 'completed';
            $date_hoanthanh = $has_chua_phan_hoi ? '' : $vanban['ngay_sua'];
        } else {
            $className_hoanthanh = 'pending';
            $date_hoanthanh = '';
        }

        if (!empty($ds_phan_hoi_don_vi)) {
            foreach ($ds_phan_hoi_don_vi as &$dvph) {
                $dvph['ngay_bao_bao'] = $this->db
                    ->select('ngay_tao')
                    ->from('e_bao_cao')
                    ->where([
                        'id_van_ban' => $data['id_van_ban'],
                        'id_don_vi_phan_hoi' => $dvph['id_don_vi']
                    ])
                    ->get()
                    ->row('ngay_tao') ?? '';
            }
        }

        $timeline_vanbandi = [
            0 => [
                'code' => 'TIEP_NHAN',
                'label' => 'Tiếp nhận',
                'description' => 'Phòng Tổ chức - Hành chính tiếp nhận văn bản.',
                'icon' => 'fa fa-inbox',
                'class_name' => !empty($vanban) ? 'completed' : 'progress',
                'date_time' => $vanban['ngay_tao'] ?? '',
                'data' => []
            ],
            1 => [
                'code' => 'CHUYEN_DON_VI',
                'label' => 'Chuyển đơn vị',
                'description' => 'Văn bản được chuyển đến đơn vị để thực hiện nội dung.',
                'icon' => 'fa fa-exchange',
                'class_name' =>  $this->getStatus($xu_ly, $vanban),
                'date_time' => $xu_ly['ngay_tao'] ?? '',
                'data' => [
                    'don_vi_xu_ly_chinh' => $dvxl_chinh,
                    'don_vi_xu_ly_phoi_hop' => $dvxl_phoi_hop,
                ]
            ],
            2 => [
                'code' => 'DON_VI_PHAN_HOI',
                'label' => 'Đơn vị phản hồi',
                'description' => 'Các đơn vị đã phản hồi văn bản.',
                'icon' => 'fa fa-reply',
                'class_name' => $className_phanhoi,
                'date_time' => $date_phan_hoi,
                'data' => $ds_phan_hoi_don_vi
            ],
            3 => [
                'code' => 'HOAN_THANH',
                'label' => 'Hoàn thành',
                'description' => 'Quy trình xử lý văn bản đã hoàn tất.',
                'icon' => 'fa fa-check',
                'class_name' => $className_hoanthanh,
                'date_time' => $date_hoanthanh,
                'data' => []
            ]
        ];
        return $timeline_vanbandi;
    }

    private function getStatus($target, $before = [])
    {
        if (!empty($target)) {
            return 'completed';
        }
        foreach ($before as $b) {
            if (!empty($b)) return 'progress';
        }
        return 'pending';
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
                $dbColumn = $columnMapping[$columnName] ?? "e_van_ban.$columnName";

                // Nếu là trạng_thai thì convert lại
                if ($columnName === 'trang_thai') {
                    $searchValue = $this->getTrangThaiValueFromLabel($searchValue);
                }

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
                    $this->db->order_by("e_van_ban.$colName", $dir);
                }
            }
        }
    }

    private function applyOrdering(array $orderBy, string $table = 'e_van_ban'): void
    {
        if (empty($orderBy)) return;

        foreach ($orderBy as $order) {
            $column = $order['column'] ?? null;
            $dir    = strtolower($order['dir'] ?? 'asc');

            // Validate
            if (!$column) continue;
            if (!in_array($dir, ['asc', 'desc'])) $dir = 'asc';

            // Thực hiện ORDER BY
            $this->db->order_by("$table.$column", $dir);
        }
    }
    public function getDistinctCreators()
    {
        $this->db->select('DISTINCT(e_van_ban.id_nguoi_tao), ql_nguoi_dung.ql_nguoi_dung_ho_ten, ql_nguoi_dung.ql_nguoi_dung_email');
        $this->db->from('e_van_ban');
        $this->db->join('ql_nguoi_dung', 'e_van_ban.id_nguoi_tao = ql_nguoi_dung.ql_nguoi_dung_id', 'left');
        $this->db->where('e_van_ban.deleted_at IS NULL');
        $this->db->where('e_van_ban.id_nguoi_tao IS NOT NULL');

        $query = $this->db->get();
        return $query->result_array();
    }

    public function thongkevanban_v2($user)
    {
        $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");

        $id_don_vi = $user['id_don_vi'] ?? null;
        $ql_nguoi_dung_id = $user['ql_nguoi_dung_id'] ?? null;

        if (empty($id_don_vi) || empty($ql_nguoi_dung_id)) {
            return [
                'totalVanbanden' => 0,
                'totalVanbandi' => 0,
                'totalVanbannoibo' => 0,
                'totalVanbandendonvi' => 0,
            ];
        }

        // Văn bản đến của tổ chức hành chính
        $totalVanbanden = $this->db
            ->from('e_van_ban')
            ->join('ql_nguoi_dung', 'e_van_ban.id_nguoi_tao = ql_nguoi_dung.ql_nguoi_dung_id', 'inner')
            ->join('e_don_vi', 'ql_nguoi_dung.id_don_vi = e_don_vi.id_don_vi', 'inner')
            ->join('e_van_ban_da_xoa', 'e_van_ban.id_van_ban = e_van_ban_da_xoa.id_van_ban', 'left')

            ->where('loai_van_ban', Common::VAN_BAN_DEN)
            ->where('e_van_ban.deleted_at IS NULL')
            ->where('e_van_ban_da_xoa.id_van_ban IS NULL')

            // 🔥 PHONG_TCHC HOẶC ADMIN
            ->group_start()

            // ADMIN hoặc PHONG_TCHC
            ->group_start()
            ->where('ql_nguoi_dung.ql_nguoi_dung_is_admin', 1)
            ->or_where('e_don_vi.ma_don_vi', 'PHONG_TCHC')
            ->group_end()

            // NGƯỜI THƯỜNG
            ->or_group_start()
            ->where('ql_nguoi_dung.ql_nguoi_dung_is_admin', 0)
            ->where('e_don_vi.ma_don_vi <>', 'PHONG_TCHC')
            ->group_start()
            ->where('ql_nguoi_dung.id_don_vi', $id_don_vi)
            ->or_where('ql_nguoi_dung.ql_nguoi_dung_id', $ql_nguoi_dung_id)
            ->group_end()
            ->group_end()
            ->group_end()
            ->count_all_results();


        // reset query builder
        $this->db->reset_query();

        // Văn bản đi của tổ chức hành chính
        $totalVanbandi =  $this->db
            ->from('e_van_ban')
            ->join('ql_nguoi_dung', 'e_van_ban.id_nguoi_tao = ql_nguoi_dung.ql_nguoi_dung_id', 'inner')
            ->join('e_don_vi', 'ql_nguoi_dung.id_don_vi = e_don_vi.id_don_vi', 'inner')
            ->join('e_van_ban_da_xoa', 'e_van_ban.id_van_ban = e_van_ban_da_xoa.id_van_ban', 'left')

            ->where('loai_van_ban', Common::VAN_BAN_DI)
            ->where('e_van_ban.deleted_at IS NULL')
            ->where('e_van_ban_da_xoa.id_van_ban IS NULL')
            ->where('e_don_vi.ma_don_vi', 'PHONG_TCHC')
            // 🔥 PHONG_TCHC HOẶC ADMIN
            ->group_start()

            // ADMIN hoặc PHONG_TCHC
            ->group_start()
            ->where('ql_nguoi_dung.ql_nguoi_dung_is_admin', 1)
            ->or_where('e_don_vi.ma_don_vi', 'PHONG_TCHC')
            ->group_end()

            // NGƯỜI THƯỜNG
            ->or_group_start()
            ->where('ql_nguoi_dung.ql_nguoi_dung_is_admin', 0)
            ->where('e_don_vi.ma_don_vi <>', 'PHONG_TCHC')
            ->group_start()
            ->where('ql_nguoi_dung.id_don_vi', $id_don_vi)
            ->or_where('ql_nguoi_dung.ql_nguoi_dung_id', $ql_nguoi_dung_id)
            ->group_end()
            ->group_end()
            ->group_end()
            ->count_all_results();

        // reset query builder
        $this->db->reset_query();

        // Văn bản nội bộ (Văn bản mà từng đơn vị tạo ra)
        $totalVanbannoibo = $this->db
            ->from('e_van_ban')

            ->join('ql_nguoi_dung', 'e_van_ban.id_nguoi_tao = ql_nguoi_dung.ql_nguoi_dung_id', 'inner')
            ->join('e_van_ban_da_xoa', 'e_van_ban.id_van_ban = e_van_ban_da_xoa.id_van_ban', 'left')

            ->where('loai_van_ban', Common::VAN_BAN_NOI_BO)
            ->where('e_van_ban.deleted_at IS NULL')
            ->where('e_van_ban_da_xoa.id_van_ban IS NULL')
            ->where('ql_nguoi_dung.id_don_vi', $id_don_vi)

            ->count_all_results();

        $this->db->reset_query();

        // Văn bản đến đơn vị (Văn bản mà đơn vị nhận được từ Phòng tổ chức hành chính hoặc từ các đơn vị khác)
        $totalVanbandendonvi = $this->db
            ->distinct()
            ->select('e_van_ban.id_van_ban')
            ->from('e_van_ban')

            ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'inner')
            ->join('e_don_vi_xu_ly', 'e_xu_ly.id_xu_ly = e_don_vi_xu_ly.id_xu_ly', 'inner')
            ->join('ql_nguoi_dung', 'e_van_ban.id_nguoi_tao = ql_nguoi_dung.ql_nguoi_dung_id', 'inner')
            ->join('e_van_ban_da_xoa', 'e_van_ban.id_van_ban = e_van_ban_da_xoa.id_van_ban', 'left')

            ->where_in('e_van_ban.loai_van_ban', [Common::VAN_BAN_DEN, Common::VAN_BAN_DI])
            ->where('e_van_ban.deleted_at IS NULL')
            ->where('e_van_ban_da_xoa.id_van_ban IS NULL')
            ->where('e_don_vi_xu_ly.id_don_vi', $id_don_vi)

            ->count_all_results();

        $this->db->reset_query();

        // Văn bản đi đơn vị (Văn bản mà đơn vị tạo ra để gửi đi cho các đơn vị khác)
        $totalVanbandidonvi = $this->db
            ->from('e_van_ban')
            ->join('ql_nguoi_dung', 'e_van_ban.id_nguoi_tao = ql_nguoi_dung.ql_nguoi_dung_id', 'inner')
            ->join('e_van_ban_da_xoa', 'e_van_ban.id_van_ban = e_van_ban_da_xoa.id_van_ban', 'left')

            ->where('e_van_ban.loai_van_ban', Common::VAN_BAN_DI)
            ->where('e_van_ban.deleted_at IS NULL')
            ->where('e_van_ban_da_xoa.id_van_ban IS NULL')
            ->where('ql_nguoi_dung.id_don_vi', $id_don_vi)

            ->count_all_results();

        return [
            'vanbanden'         => $totalVanbanden ?? 0,
            'vanbandi'          => $totalVanbandi ?? 0,
            'vanbannoibo'       => $totalVanbannoibo ?? 0,
            'vanbandendonvi'    => $totalVanbandendonvi ?? 0,
            'vanbandidonvi'     => $totalVanbandidonvi ?? 0,
        ];
    }

    public function vanbanmoihomnay($user)
    {
        $id_don_vi = (int)($user['id_don_vi'] ?? 0);
        $ql_nguoi_dung_id = (int)($user['ql_nguoi_dung_id'] ?? 0);
        $today = date('Y-m-d');

        // Đếm số văn bản đơn vị nhận được hôm nay
        $totalVanbandendonvi = $this->db
            ->distinct()
            ->select('e_van_ban.id_van_ban')
            ->from('e_van_ban')

            ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'inner')
            ->join('e_don_vi_xu_ly', 'e_xu_ly.id_xu_ly = e_don_vi_xu_ly.id_xu_ly', 'inner')
            ->join('ql_nguoi_dung', 'e_van_ban.id_nguoi_tao = ql_nguoi_dung.ql_nguoi_dung_id', 'inner')
            ->join('e_van_ban_da_xoa', 'e_van_ban.id_van_ban = e_van_ban_da_xoa.id_van_ban', 'left')

            ->where_in('e_van_ban.loai_van_ban', [Common::VAN_BAN_DEN, Common::VAN_BAN_DI])
            ->where('e_van_ban.deleted_at IS NULL')
            ->where('e_van_ban_da_xoa.id_van_ban IS NULL')
            ->where('e_don_vi_xu_ly.id_don_vi', $id_don_vi)
            ->where('DATE(e_don_vi_xu_ly.ngay_tao)', $today)

            ->count_all_results();

        $this->db->reset_query();

        // Đếm số văn bản chưa xem của đơn vị
        $totalVanbanchuaxem = $this->db
            ->distinct()
            ->select('e_van_ban.id_van_ban')
            ->from('e_van_ban')

            ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'inner')
            ->join(
                'e_don_vi_xu_ly',
                'e_xu_ly.id_xu_ly = e_don_vi_xu_ly.id_xu_ly',
                'inner'
            )
            ->join('e_van_ban_da_xoa', 'e_van_ban.id_van_ban = e_van_ban_da_xoa.id_van_ban', 'left')
            ->join('ql_nguoi_dung', 'e_van_ban.id_nguoi_tao = ql_nguoi_dung.ql_nguoi_dung_id', 'inner')

            ->where_in('e_van_ban.loai_van_ban', [Common::VAN_BAN_DEN, Common::VAN_BAN_DI])
            ->where('e_van_ban.deleted_at IS NULL')
            ->where('e_van_ban_da_xoa.id_van_ban IS NULL')

            // Cơ chế ban hành cho từng người trong đơn vị nên lấy theo từng người
            ->where('e_don_vi_xu_ly.id_don_vi', $id_don_vi)
            ->where('e_don_vi_xu_ly.id_nguoi_xu_ly', $ql_nguoi_dung_id)
            ->where('e_don_vi_xu_ly.da_xem', 0)

            // ->get()->result_array();
            ->count_all_results();

        return [
            'vanbandenhomnay' => $totalVanbandendonvi ?? 0,
            'vanbanchuaxem'   => $totalVanbanchuaxem ?? 0,
        ];
    }
}
