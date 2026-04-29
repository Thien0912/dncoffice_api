<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @property Common $common
 */

class Hrm_bang_luong_model extends MY_Model
{
    protected $table = 'hrm_bang_luong';
    protected $primaryKey = 'id_bang_luong';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->library(['Common']);
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $columns = [], $searchKey = array(), $fromDate = null, $toDate = null, $auth, $idBangLuongThang)
    {
        $this->db->from('hrm_bang_luong')
            ->select('
                hrm_bang_luong.*, 
                nv.ma_nhan_vien, 
                nv.ho_va_ten, 
                nv.email,
                nv.avatar,
                e_don_vi.ten_don_vi,
            ')
            ->join('hrm_nhan_vien AS nv', 'nv.id_nhan_vien = hrm_bang_luong.id_nhan_vien', 'left')
            ->join('e_don_vi', 'nv.id_don_vi_cong_tac = e_don_vi.id_don_vi', 'left')
            ->where('hrm_bang_luong.bang_luong_thang_id', $idBangLuongThang);

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('hrm_bang_luong.luong_co_ban', $searchValue);
            $this->db->or_like('hrm_bang_luong.tong_phu_cap', $searchValue);
            $this->db->or_like('hrm_bang_luong.tien_tang_ca', $searchValue);
            $this->db->or_like('hrm_bang_luong.khau_tru', $searchValue);
            $this->db->or_like('hrm_bang_luong.luong_thuc_nhan', $searchValue);
            $this->db->or_like('hrm_bang_luong.thue_tncn', $searchValue);
            $this->db->or_like('nv.ho_va_ten', $searchValue);
            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            if (!empty($searchKey['id_don_vi'])) {
                $this->db->where("nv.id_don_vi_cong_tac", $searchKey['id_don_vi']);
            }
        }

        if (!empty($columns)) {
            // Map tên cột hiển thị (alias) sang cột thật trong DB
            $columnMapping = [
                'ho_va_ten'    => 'nv.ho_va_ten',
                // Mặc định nếu không map thì nó sẽ lấy e_van_ban.[columnName]
            ];
            $this->handleDatatableColumns($columns, $columnMapping);
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query

        if (!empty($orderBy)) {
            $this->handleDatatableOrdering($orderBy, $columns);
        }

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        $query = $this->db->get();
        $data = $query->result_array();
        $sql = $this->db->last_query();

        foreach ($data as &$dt) {
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
            'sql' => $sql,
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
                $dbColumn = $columnMapping[$columnName] ?? "hrm_bang_luong.$columnName";
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
                    $this->db->order_by("hrm_bang_luong.$colName", $dir);
                }
            }
        }
    }

    public function tinh_luong($nhanvienId, $fromDate, $toDate, $idBangLuongThang = null)
    {
        /**
         * 1. Lấy hợp đồng đang có hiệu lực
         * -> Tổng lương = Thu nhập (lương cơ bản + phụ cấp + tăng ca ...) - Khấu trừ (bảo hiểm + công đoàn + phạt...)
         */

        $luong_co_ban = 0;
        $tong_phu_cap = 0;
        $tien_tang_ca = 0;
        $khau_tru = 0;
        $thue_tncn = 0;
        $luong_thuc_nhan = 0; //Thực nhận
        $thue_suat = 0;

        // Thông tin bảo hiểm 
        $bhxh = 0;
        $bhyt = 0;
        $bhtn = 0;

        $bhxh_dn = 0;
        $bhyt_dn = 0;
        $bhtn_dn = 0;
        $tile_tong_dn_dong = 0;
        $tile_tong_nv_dong = 0;

        // Tạm ứng
        $tam_ung = 0;

        $hopdong = $this->db->select('*')
            ->from('hrm_hop_dong')
            ->where('ngay_bat_dau <=', date('Y-m-d')) // Hợp đồng phải có hiệu lực trước hoặc bằng hôm nay
            ->where('deleted_at IS NULL')
            ->where('id_nhan_vien', $nhanvienId)
            ->where('dang_hieu_luc', 1) // Hợp đồng đang hiệu lực
            ->group_start()
            ->where('ngay_ket_thuc >=', date('Y-m-d')) // Hợp đồng chưa hết hạn
            ->or_where('ngay_ket_thuc IS NULL') // Hoặc không có ngày kết thúc
            ->group_end()
            ->order_by('ngay_bat_dau', 'DESC')
            ->get()
            ->row_array(); // Lấy hợp đồng mới nhất trước

        if (!$hopdong) {
            // echo 'Không có hợp đồng đang hiệu lực';
            return [
                'success' => false,
                'idNhanVien' => $nhanvienId,
                'message' => 'Không có hợp đồng đang hiệu lực',
            ];
        } else {
            $luong_co_ban = $hopdong['luong_co_ban'];

            $muc_luong_bao_hiem = $hopdong['muc_luong_bao_hiem'];

            //Phụ cấp
            $hopDongPhuLuc = $this->db
                ->select('*')
                ->from('hrm_hop_dong_phu_luc')
                ->where('id_hop_dong', $hopdong['id_hop_dong'])
                ->get()
                ->row_array();

            if ($hopDongPhuLuc) {
                $idsPhuCap = explode(',', $hopDongPhuLuc['ids_phu_cap']);

                // Model cũ
                // $phucapnhanvien = $this->db->where('id_nhan_vien', $nhanvienId)->get('hrm_phu_cap_nhan_vien')->result_array();

                $phucapnhanvien = $this->db
                    ->select('*')
                    ->from('hrm_phu_cap')
                    ->where_in('id_phu_cap', $idsPhuCap)
                    ->get()
                    ->result_array();
                foreach ($phucapnhanvien as $pc) {
                    if ($pc['chiu_thue']) {
                        $tong_phu_cap += $pc['so_tien'];
                    }
                }
            }

            /**
             * Tính tiền OT
             * 1. Lấy ra ngày lễ trong khoảng thời gian tính lương
             * 2. So sánh từ ngày lễ -> ngày nghỉ -> ngày thường để nhân lương căn bản với hệ số OT (Khi đăng ký thì hệ thống sẽ tính phần này)
             */
            // $dsngayle = $this->db
            //   ->where('ngay >=', $fromDate)
            //   ->where('ngay <=', $toDate)
            //   ->where('duoc_nghi', 1)
            //   ->select('ngay')
            //   ->get('hrm_ngay_le_viet_nam')
            //   ->result_array();
            // $dsngayle = array_column($dsngayle, 'ngay');
            // $dsngaynghi = [7]; //Thứ 2 bắt đầu từ 1, 1 -> 7

            //Số công chuẩn
            $thongtinluong = $this->db->where('id_nhan_vien', $nhanvienId)->get('hrm_nhan_vien_luong')->row_array();

            if ($thongtinluong) {
                $dangkylamthem = $this->db
                    ->from('hrm_dang_ky_lam_them')
                    ->join('hrm_he_so_tang_ca', 'hrm_he_so_tang_ca.id_he_so = hrm_dang_ky_lam_them.id_he_so_ot')
                    ->where('id_nhan_vien', $nhanvienId)
                    ->where('trang_thai', $this->common::STATUS_DANG_KY_LAM_THEM['Da_duyet']['value'])
                    ->where('ngay >=', $fromDate)
                    ->where('ngay <=', $toDate)
                    ->where('deleted_at IS NULL')
                    ->where('trang_thai_tchc', Common::STATUS_DANG_KY_LAM_THEM['Da_duyet']['value'])
                    ->get()
                    ->result_array();

                $luongmoingay = $luong_co_ban / $thongtinluong['so_cong_chuan'];  // Lương mỗi ngày
                $luongmoigio = $luongmoingay / 8; //Lương mỗi giờ

                foreach ($dangkylamthem as $dklt) {
                    $tien_tang_ca += $luongmoigio * $dklt['he_so_ot'] * $dklt['so_gio'];
                }
            }


            //Bảo hiểm
            // $nvBaoHiem = $this->db->where('id_nhan_vien', $nhanvienId)->get('hrm_nhan_vien_bao_hiem')->row_array();
            // if ($nvBaoHiem && $nvBaoHiem['ti_le_dong']) {
            //     $bhxh = $muc_luong_bao_hiem * ($nvBaoHiem['ti_le_dong'] / 100);
            //     $tile_tong_nv_dong = $nvBaoHiem['ti_le_dong'];

            //     $tylebaohiem = $this->db->where('id_ty_le_bao_hiem', $hopdong['id_ty_le_bao_hiem'])
            //         ->get('hrm_ty_le_bao_hiem')
            //         ->row_array();

            //     if ($tylebaohiem) {
            //         $bhxh_dn = ($tylebaohiem['bhxh_dn'] / 100) * $muc_luong_bao_hiem;
            //         $bhyt_dn = ($tylebaohiem['bhyt_dn'] / 100) * $muc_luong_bao_hiem;
            //         $bhtn_dn = ($tylebaohiem['bhtn_dn'] / 100) * $muc_luong_bao_hiem;
            //         $tile_tong_dn_dong = $tylebaohiem['bhxh_dn'] + $tylebaohiem['bhyt_dn'] + $tylebaohiem['bhtn_dn'];
            //     }
            // } else if ($hopdong['id_ty_le_bao_hiem']) {
            //     $tylebaohiem = $this->db->where('id_ty_le_bao_hiem', $hopdong['id_ty_le_bao_hiem'])
            //         ->get('hrm_ty_le_bao_hiem')
            //         ->row_array();

            //     if ($tylebaohiem) {
            //         $bhxh = $muc_luong_bao_hiem * ($tylebaohiem['bhxh_nv'] / 100);
            //         $bhyt = $muc_luong_bao_hiem * ($tylebaohiem['bhyt_nv'] / 100);
            //         $bhtn = $muc_luong_bao_hiem * ($tylebaohiem['bhtn_nv'] / 100);

            //         $bhxh_dn = ($tylebaohiem['bhxh_dn'] / 100) * $muc_luong_bao_hiem;
            //         $bhyt_dn = ($tylebaohiem['bhyt_dn'] / 100) * $muc_luong_bao_hiem;
            //         $bhtn_dn = ($tylebaohiem['bhtn_dn'] / 100) * $muc_luong_bao_hiem;
            //         $tile_tong_dn_dong = $tylebaohiem['bhxh_dn'] + $tylebaohiem['bhyt_dn'] + $tylebaohiem['bhtn_dn'];
            //         $tile_tong_nv_dong = $tylebaohiem['bhxh_nv'] + $tylebaohiem['bhyt_nv'] + $tylebaohiem['bhtn_nv'];
            //     }
            // }



            // Thuế thu nhập
            //mức chịu thuế = Tổng thu nhập - Bảo hiểm - Khấu trừ - giảm trừ chịu thuế (giảm trừ gia cảnh, làm tử thiện...)
            $giamtrugiacanhbanthan = 11000000;
            $giamtrugiacanhphuthuoc = 4400000;
            $tonggiamtrugiacanhphuthuoc = $giamtrugiacanhphuthuoc * 0; // Có bao nhiêu người phục thuộc

            $muc_chiu_thue = ($luong_co_ban + $tong_phu_cap + $tien_tang_ca) - ($bhxh + $bhyt + $bhtn) - $khau_tru - $giamtrugiacanhbanthan - $tonggiamtrugiacanhphuthuoc;

            $bieuthuetichluy = $this->db->order_by('muc_thu_nhap_toi_thieu', 'DESC')->get('hrm_bieu_thue_tich_luy')->result_array();

            foreach ($bieuthuetichluy as $bttl) {
                if ($muc_chiu_thue >= $bttl['muc_thu_nhap_toi_thieu'] && (!$bttl['muc_thu_nhap_toi_da'] || $muc_chiu_thue <= $bttl['muc_thu_nhap_toi_da'])) {
                    $thue_tncn = $muc_chiu_thue * ($bttl['thue_suat'] / 100);
                    $thue_suat = $bttl['thue_suat']; //thêm thuê suất ngày 17/4/2025
                    break;
                }
            }

            $this->db->trans_start();
            // Quá trình đóng bảo hiểm
            list($fromYear, $fromMonth, $fromDay) = explode('-', $fromDate);
            list($toYear, $toMonth, $toDay) = explode('-', $toDate);
            // $nhanvienbaohiem = $this->db->where('id_nhan_vien', $nhanvienId)->get('hrm_nhan_vien_bao_hiem')->row_array();
            // if ($tile_tong_nv_dong) {
            //     $this->db->insert('hrm_bao_hiem_dong', [
            //         'id_nhan_vien' => $nhanvienId,
            //         'thang' =>  $toYear . '-' . $toMonth . '-01',
            //         'tile_bhxh_nv' => $tylebaohiem['bhxh_nv'] ?? 0,
            //         'tile_bhyt_nv' => $tylebaohiem['bhyt_nv'] ?? 0,
            //         'tile_bhtn_nv' => $tylebaohiem['bhtn_nv'] ?? 0,
            //         'tile_tong_nv_dong' => $tile_tong_nv_dong,
            //         'tile_tong_dn_dong' => $tile_tong_dn_dong,
            //         'tong_nv_dong' => $bhxh + $bhyt + $bhtn,
            //         'muc_luong_dong' => $muc_luong_bao_hiem,
            //         'bhxh_nv' => $bhxh,
            //         'bhyt_nv' => $bhyt,
            //         'bhtn_nv' => $bhtn,
            //         'bhxh_dn' => $bhxh_dn,
            //         'bhyt_dn' => $bhyt_dn,
            //         'bhtn_dn' => $bhtn_dn,
            //         'tong_dn_dong' => $bhxh_dn + $bhyt_dn + $bhtn_dn,
            //         'tong_dong' => $bhxh + $bhyt + $bhtn + $bhxh_dn + $bhyt_dn + $bhtn_dn,
            //         'trang_thai' => 'Chua_nop',
            //         'tu_thang' =>  $fromYear . '-' . $fromMonth . '-01',
            //         'den_thang' => $toYear . '-' . $toMonth . '-01',
            //         'so_so_bhxh' => $nhanvienbaohiem['so_so_bhxh'] ?? '',
            //         'ma_bhxh' => $nhanvienbaohiem['ma_bhxh'] ?? '',
            //         'ma_tinh_cap' => $nhanvienbaohiem['ma_tinh_cap'] ?? '',
            //         'ten_tinh_cap' => $nhanvienbaohiem['ten_tinh_cap'] ?? '',
            //         'so_the_bhyt' => $nhanvienbaohiem['so_the_bhyt'] ?? '',
            //         'ngay_het_han' => $nhanvienbaohiem['ngay_het_han'] ?? null,
            //         'noi_dk_kcb' => $nhanvienbaohiem['noi_dk_kcb'] ?? '',
            //         'ms_noi_kcb' => $nhanvienbaohiem['ms_noi_kcb'] ?? ''
            //     ]);
            // }

            //Phụ cấp lương
            if (!empty($phucapnhanvien)) {
                $dataPhucap = [];
                foreach ($phucapnhanvien as $pc) {
                    $dataPhucap[] = [
                        'id_nhan_vien' => $nhanvienId,
                        'id_phu_cap' => $pc['id_phu_cap'],
                        'so_tien' => $pc['so_tien'],
                        'thang' => $toYear . '-' . $toMonth . '-01'
                    ];
                }

                if (!empty($dataPhucap)) {
                    $this->db->insert_batch('hrm_phu_cap_luong', $dataPhucap);
                }
            }

            //Thuế

            if ($thue_tncn) {
                $dataThue = [
                    'id_nhan_vien' => $nhanvienId,
                    'thue_suat' => $thue_suat,
                    'thue_thu_nhap' => $thue_tncn, //Mức thế phải đóng
                    'thang_tinh_thue' => $toYear . '-' . $toMonth . '-01',
                    'thu_nhap_chiu_thue' => $muc_chiu_thue
                ];

                $this->db->insert('hrm_nhan_vien_thue', $dataThue);
            }

            // Tạm ứng
            $tamUng = $this->db
                ->select('*')
                ->from('hrm_tam_ung')
                ->where('id_nhan_vien', $nhanvienId)
                ->where('trang_thai', 1)
                ->get()
                ->row_array();

            if ($tamUng) {
                list($nam, $thang, $ngay) = explode('-', $toDate);

                $chiTietTamUng = $this->db
                    ->select('*')
                    ->from('hrm_chi_tiet_tam_ung')
                    ->where('id_tam_ung', $tamUng['id_tam_ung'])
                    ->where('trang_thai', 0)
                    ->where("DATE_FORMAT(thoi_han_thanh_toan, '%Y-%m') =", "$nam-$thang")
                    ->get()
                    ->row_array();

                if ($chiTietTamUng) {
                    $tam_ung = $chiTietTamUng['so_tien'];

                    $this->db
                        ->where('id_chi_tiet_tam_ung', $chiTietTamUng['id_chi_tiet_tam_ung'])
                        ->update(
                            'hrm_chi_tiet_tam_ung',
                            [
                                'trang_thai' => 1
                            ]
                        );

                    $checkTatToan = $this->db
                        ->select('*')
                        ->from('hrm_chi_tiet_tam_ung')
                        ->where('id_tam_ung', $tamUng['id_tam_ung'])
                        ->where('trang_thai', 0)
                        ->get()
                        ->result_array();

                    if (empty($checkTatToan)) {
                        $this->db
                            ->where('id_tam_ung', $tamUng['id_tam_ung'])
                            ->update('hrm_tam_ung', [
                                'trang_thai' => 2
                            ]);
                    }
                }
            }

            //Tạo bảng lương
            $luong_thuc_nhan = ($luong_co_ban + $tong_phu_cap + $tien_tang_ca) - ($bhxh + $bhyt + $bhtn) - $khau_tru - $thue_tncn - $tam_ung;
            $tien_cong_doan = $luong_co_ban * 0.01; //Tiền công đoàn
            $luong_thuc_nhan = $luong_thuc_nhan - $tien_cong_doan;
            $dataBangluong = [
                'id_nhan_vien' => $nhanvienId,
                'thang' => $toYear . '-' . $toMonth . '-01',
                'luong_co_ban' => $luong_co_ban,
                'tong_phu_cap' => $tong_phu_cap,
                'tien_tang_ca' => $tien_tang_ca,
                'khau_tru' => $khau_tru,
                'luong_thuc_nhan' => $luong_thuc_nhan,
                'id_ty_le_bao_hiem' => $tylebaohiem['id_ty_le_bao_hiem'] ?? null,
                'bhxh' => $bhxh,
                'bhyt' => $bhyt,
                'bhtn' => $bhtn,
                'thu_nhap_chiu_thue' => $muc_chiu_thue,
                'thue_tncn' => $thue_tncn,
                'dpcd' => $tien_cong_doan,
                'bang_luong_thang_id' => $idBangLuongThang
            ];
            $this->db->insert('hrm_bang_luong', $dataBangluong);
            $this->db->trans_commit();
            // echo 'Thành công' . PHP_EOL;
            return [
                'success' => true,
                'idNhanVien' => $nhanvienId,
                'message' => 'Tính lương thành công',
            ];
        }
    }

    public function getListExportBangluong($start = 0, $length = 10, $searchValue = null, $searchKey = array(), $fromDate = null, $toDate = null)
    {

        $this->db->from('hrm_bang_luong as bl')
            ->join('hrm_nhan_vien AS nv', 'nv.id_nhan_vien = bl.id_nhan_vien', 'left')
            ->select("
          bl.id_bang_luong,
          nv.ho_va_ten,
          DATE_FORMAT(bl.thang, '%m/%Y') AS thang,
          FORMAT(bl.luong_co_ban, 0) AS luong_co_ban,
          FORMAT(bl.tong_phu_cap, 0) AS tong_phu_cap,
          FORMAT(bl.tien_tang_ca, 0) AS tien_tang_ca,
          FORMAT(bl.khau_tru, 0) AS khau_tru,
          FORMAT(bl.luong_thuc_nhan, 0) AS luong_thuc_nhan,
          FORMAT(bl.bhxh, 0) AS bhxh,
          FORMAT(bl.bhyt, 0) AS bhyt,
          FORMAT(bl.bhtn, 0) AS bhtn,
          FORMAT(bl.thue_tncn, 0) AS thue_tncn,
          DATE_FORMAT(bl.ngay_tao, '%d/%m/%Y') AS ngay_tao
      ");


        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('nv.ho_va_ten', $searchValue);
            $this->db->or_like('nv.luong_co_ban', $searchValue);
            $this->db->or_like('nv.luong_phu_cap', $searchValue);
            $this->db->or_like('nv.luong_tang_ca', $searchValue);
            $this->db->or_like('nv.luong_thuc_nhan', $searchValue);
            $this->db->group_end();
        }

        if ($fromDate && $toDate) {
            $this->db->where("DATE(bl.created_at) BETWEEN '{$fromDate}' AND '{$toDate}'");
        }


        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query 

        if (!empty($orderBy)) {

            $order = $orderBy['order'];
            $orderColumnIndex = $order[0]['column'];
            $orderDir = $order[0]['dir'];

            $columns = $orderBy['columns'];
            $filed = $columns[$orderColumnIndex]['data'];

            $this->db->order_by('bl.' . $filed, $orderDir);
        }

        if ($length != '-1')
            $this->db->limit($length, $start);

        $query = $this->db->get();
        $data = $query->result_array();

        return $data;
    }

    public function export_thuong($so_tien, $tham_nien)
    {
        $this->db->select("nv.id_nhan_vien, 
      IF(`nvl`.`ngan_hang` = 'HDBank', `nv`.`ho_va_ten`, '') ngan_hang_hd,
      IF(`nvl`.`ngan_hang` = 'Viettinbank', `nv`.`ho_va_ten`, '') ngan_hang_viettin,
      IF(`nvl`.`ngan_hang` != 'Viettinbank' AND `nvl`.`ngan_hang` != 'HDBank', `nv`.`ho_va_ten`, '') AS ngan_hang_khac, 
      nvl.ngan_hang, nv.ho_va_ten, 
      vtcv.ten_cong_viec AS vi_tri_cong_viec, 
      nv.trinh_do_dt, 
      nv.nganh_dt, 
      DATE_FORMAT(nvcv.ngay_lam_chinh_thuc, '%d/%m/%Y') AS ngay_lam_chinh_thuc, 
      IF(
            nvcv.ngay_lam_chinh_thuc_ket_thuc IS NOT NULL,
            TIMESTAMPDIFF(MONTH, nvcv.ngay_lam_chinh_thuc, nvcv.ngay_lam_chinh_thuc_ket_thuc),
            TIMESTAMPDIFF(MONTH, nvcv.ngay_lam_chinh_thuc, CURDATE())
        ) AS tham_nien,
        ($so_tien * 0.1) AS thue_tncn,
        ($so_tien - ($so_tien * 0.1)) AS thuc_nhan, 
         nvl.ngan_hang,
         CASE 
           WHEN nvl.ngan_hang = 'Viettinbank' THEN 'VT'
           WHEN nvl.ngan_hang = 'HDbank' THEN 'HD'
           ELSE nvl.ngan_hang
         END AS hinh_thuc_tt, nvl.tk_ngan_hang, nv.mst_ca_nhan, nv.ngay_sinh, nv.cccd_so, nv.cccd_ngay_cap, nv.cccd_noi_cap")
            ->from('hrm_nhan_vien AS nv')
            ->join('hrm_nhan_vien_luong AS nvl', 'nvl.id_nhan_vien = nv.id_nhan_vien', 'left')
            ->join('hrm_nhan_vien_cong_viec AS nvcv', 'nvcv.id_nhan_vien = nv.id_nhan_vien', 'left')
            ->join('hrm_vi_tri_cong_viec AS vtcv', 'vtcv.id_vi_tri_cong_viec = nv.id_vi_tri_cong_viec', 'left')
        ;

        if ($tham_nien) {
            $this->db->having('tham_nien >=', $tham_nien);
        }

        $query = $this->db->get();
        return $query->result_array();
    }

    public function get_tong_thu_nhap_theo_nam($nam)
    {
        $this->db->select("nv.id_nhan_vien, 
            IF(`nvl`.`ngan_hang` = 'HDBank', `nv`.`ho_va_ten`, '') ngan_hang_hd,
            IF(`nvl`.`ngan_hang` = 'Viettinbank', `nv`.`ho_va_ten`, '') ngan_hang_viettin,
            IF(`nvl`.`ngan_hang` != 'Viettinbank' AND `nvl`.`ngan_hang` != 'HDBank', `nv`.`ho_va_ten`, '') AS ngan_hang_khac,       nv.ho_va_ten, 
            vtcv.ten_cong_viec AS vi_tri_cong_viec, 
            nv.trinh_do_dt as hoc_ham_hoc_vi, 
            nv.nganh_dt as chuyen_nganh,
            DATE_FORMAT(nvcv.ngay_lam_chinh_thuc, '%d/%m/%Y') AS ngay_lam_chinh_thuc, 
            IF(
                nvcv.ngay_lam_chinh_thuc_ket_thuc IS NOT NULL,
                TIMESTAMPDIFF(MONTH, nvcv.ngay_lam_chinh_thuc, nvcv.ngay_lam_chinh_thuc_ket_thuc),
                TIMESTAMPDIFF(MONTH, nvcv.ngay_lam_chinh_thuc, CURDATE())
            ) AS tham_nien,
            bl.luong_co_ban,
            bl.tong_phu_cap,
            SUM(bl.luong_co_ban) as tong_muc_luong_chinh,
            SUM(bl.tong_phu_cap) as tong_phu_cap,
            SUM(bl.tien_tang_ca) as tong_ngoai_gio,
            SUM(bl.luong_co_ban + bl.tong_phu_cap) AS tong_luong,
            SUM(bl.luong_co_ban + bl.tong_phu_cap + bl.tien_tang_ca) AS tong_thu_nhap,
            SUM(bl.bhxh) AS tong_bhxh,
            SUM(bl.bhyt) AS tong_bhyt,
            SUM(bl.bhtn) AS tong_bhtn,
            SUM(bl.bhyt + bl.bhxh + bl.bhtn) AS tong_bao_hiem,
            SUM(bl.dpcd) as dpcd,
            SUM(bl.khau_tru) as tong_giam_tru,
            SUM(bl.thu_nhap_chiu_thue) as tong_thu_nhap_chiu_thue,
            SUM(bl.thue_tncn) as tong_thue_tncn,
            SUM(bl.luong_thuc_nhan) AS tong_luong_thuc_nhan,
            CASE 
            WHEN nvl.ngan_hang = 'Viettinbank' THEN 'VT'
            WHEN nvl.ngan_hang = 'HDbank' THEN 'HD'
            ELSE 'Khác'
            END AS hinh_thuc_tt,
            nvl.tk_ngan_hang as stk,
            CASE 
            WHEN nvl.ngan_hang = 'Viettinbank' THEN 'Viettinbank'
            WHEN nvl.ngan_hang = 'HDbank' THEN 'HDBank'
            ELSE nvl.ngan_hang
            END AS ngan_hang,
            nv.mst_ca_nhan as mst,
            DATE_FORMAT(nv.ngay_sinh, '%d/%m/%Y') as ngay_sinh,
            nv.cccd_so, nv.cccd_ngay_cap, nv.cccd_noi_cap, 
            ngoai_gio_khac,
            ngoai_gio_cn,
            ngoai_gio_le,
            SUM(bl.luong_co_ban + bl.tong_phu_cap + bl.tien_tang_ca) AS tong_thu_nhap_cong_lai,
            SUM(bl.luong_co_ban + bl_tong_phu_cap + bl.tien_tang_ca - bl.bhxh - bl.bhyt -bl.bhtn -bl.khau_tru - bl.thue_tncn) AS tong_thu_nhap_cong_lai_thuc_nhan,
            SUM(bl.luong_co_ban + bl.tong_phu_cap + bl.tien_tang_ca - bl.bhxh - bl.bhyt -bl.bhtn -bl.khau_tru - bl.thue_tncn) AS tong_thu_nhap_cong_lai_thuc_nhan,

            ")
            ->from('hrm_bang_luong AS bl')
            ->join('hrm_nhan_vien AS nv', 'nv.id_nhan_vien = bl.id_nhan_vien', 'left')
            ->join('hrm_nhan_vien_luong AS nvl', 'nvl.id_nhan_vien = nv.id_nhan_vien', 'left')
            ->join('hrm_nhan_vien_cong_viec AS nvcv', 'nvcv.id_nhan_vien = nv.id_nhan_vien', 'left')
            ->join('hrm_vi_tri_cong_viec AS vtcv', 'vtcv.id_vi_tri_cong_viec = nv.id_vi_tri_cong_viec', 'left')
            ->join("
                (
                    SELECT
                        id_nhan_vien,
                        SUM(CASE WHEN loai_ngay = 'Ngay_thuong' THEN so_gio ELSE 0 END) AS ngoai_gio_khac,
                        SUM(CASE WHEN loai_ngay = 'Ngay_nghi' THEN so_gio ELSE 0 END) AS ngoai_gio_cn,
                        SUM(CASE WHEN loai_ngay = 'Ngay_le' THEN so_gio ELSE 0 END) AS ngoai_gio_le
                    FROM hrm_dang_ky_lam_them
                    WHERE trang_thai = 'Da_duyet'
                    AND YEAR(ngay) = " . $nam . "
                    GROUP BY id_nhan_vien
                ) AS tong_gio
                ", 'tong_gio.id_nhan_vien = nv.id_nhan_vien', 'left')
            ->where('YEAR(bl.thang)', $nam)
            ->group_by('nv.id_nhan_vien')
        ;

        $query = $this->db->get();
        return $query->result_array();
    }

    public function xuat_danh_sach_ngan_hang_khac($thang, $nam)
    {
        $this->db->select("nvl.ten_chu_tai_khoan as ten_nguoi_thu_huong, bl.luong_thuc_nhan as so_tien, nvl.ngan_hang as ten_ngan_hang, nvl.tk_ngan_hang as ten_tai_khoan, nvl.ghi_chu as ghi_chu")
            ->from('hrm_bang_luong as bl')
            ->join('hrm_nhan_vien_luong as nvl', 'nvl.id_nhan_vien = bl.id_nhan_vien', 'left')
            ->where('MONTH(bl.thang)', $thang)
            ->where('YEAR(bl.thang)', $nam)
            ->where('nvl.ngan_hang !=', 'Viettinbank')
            ->where('nvl.ngan_hang !=', 'HDBank');

        $query = $this->db->get();
        return $query->result_array();
    }
}
