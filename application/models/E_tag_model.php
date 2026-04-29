<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class E_tag_model extends MY_Model
{
    protected $table = 'e_tag';
    protected $primaryKey = 'id_tag';
    protected $timestamps = false;
    protected $createdAtField = 'created_at';
    protected $updatedAtField = 'updated_at';

    public function __construct()
    {
        parent::__construct();
    }

    public function get_user_tags($user_id)
    {
        $sql = "
            SELECT `e_tag`.*, IF(id_owner = " . $user_id . ", 1, 0) AS is_owner 
            FROM `e_tag` 
            WHERE `e_tag`.`deleted_at` IS NULL 
                AND  `ql_nguoi_dung_id` LIKE '%" . $user_id . "%' ESCAPE '!' 
                ORDER BY `e_tag`.`created_at` ASC
        ";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getAllVanban_byTag($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array(), $fromDate = null, $toDate = null)
    {
        $now = date('Y-m-d');
        $fiveDaysLater = date('Y-m-d', strtotime('5 days'));
        $this->db->from('e_van_ban')
            ->select('e_van_ban.*, e_tinh_chat.ten_tinh_chat, e_loai.ten_loai, e_khoi_co_quan.ten_khoi_co_quan, e_co_quan.ten_co_quan')
            ->join('e_tinh_chat', 'e_tinh_chat.id_tinh_chat = e_van_ban.id_tinh_chat', 'left')
            ->join('e_loai', 'e_loai.id_loai =e_van_ban.id_loai', 'left')
            ->join('e_khoi_co_quan', 'e_khoi_co_quan.id_khoi_co_quan = e_van_ban.id_khoi_co_quan', 'left')
            ->join('e_co_quan', 'e_co_quan.id_co_quan = e_van_ban.id_co_quan', 'left')
            ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'left');
        // ->join('e_trang_thai', 'e_trang_thai.id_trang_thai = e_van_ban.id_trang_thai', 'left');
 
        $this->db->where('e_van_ban.deleted_at IS NULL');
        $this->db->where_in('e_van_ban.id_van_ban', $searchKey['id_van_ban'] ?? []);
        unset($searchKey['id_van_ban']);


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

        if (!empty($orderBy)) {
            $order = $orderBy['order'];
            $orderColumnIndex = $order[0]['column'];
            $orderDir = $order[0]['dir'];

            $columns = $orderBy['columns'];
            $filed = $columns[$orderColumnIndex]['data'];

            if (!empty($orderDir) && $filed == 'so_van_ban') {
                $this->db->order_by('e_van_ban.ngay_nhan', $orderDir);
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

        // dd($this->db->last_query());

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

            // Lấy ra tag -> văn bản thuộc tag nào
            $dt['tags_in_vb'] = [];
            $tags = $this->db->from('e_tag_van_ban')
                ->where('e_tag_van_ban.id_van_ban', $dt['id_van_ban'])
                ->get()->result_array();
            if ($tags) {
                $id_tags = array_unique(array_column($tags, 'id_tag'));
                $dt['tags_in_vb'] = $this->db->select('*')->from('e_tag')->where_in('id_tag', $id_tags)->get()->result_array();
            }
        }

        // Văn bản trên 5 ngày
        $tren5ngay = $this->db->from('e_van_ban')
            ->where("thoi_gian_xu_ly > ", $fiveDaysLater)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
            ->where('deleted_at IS NULL')
            ->get()
            ->num_rows();

        // Văn bản trong vòng 5 ngày
        $duoi5ngay = $this->db->from('e_van_ban')
            ->where("thoi_gian_xu_ly <= ", $fiveDaysLater)
            ->where("thoi_gian_xu_ly > ", $now)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
            ->where('deleted_at IS NULL')
            ->get()
            ->num_rows();

        // Văn bản của ngày hôm nay
        $homnay = $this->db->from('e_van_ban')
            ->where("DATE(thoi_gian_xu_ly)", $now)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
            ->where('deleted_at IS NULL')
            ->get()
            ->num_rows();

        // Văn bản quá hạn
        $quahan = $this->db->from('e_van_ban')
            ->where("thoi_gian_xu_ly < ", $now)
            ->where('thoi_gian_xu_ly IS NOT NULL')
            ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
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
        ];
    }
}
