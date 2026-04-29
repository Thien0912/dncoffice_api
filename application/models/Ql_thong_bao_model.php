<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Ql_thong_bao_model extends MY_Model
{
    protected $table = 'ql_thong_bao';
    protected $primaryKey = 'ql_thong_bao_id';
    protected $hidden = [];
    protected $timestamps = true;

    const SENT = 1;
    const NOT_SENT_YET = 0;

    const REMINDER = 2;
    const NOTIFICATION = 1;


    public function __construct()
    {
        parent::__construct();
    }

    public function getNotificationsByUserId($userId)
    {
        $page = commonRequest('page') ? commonRequest('page') : 1;
        $perPage = commonRequest('per_page') ? commonRequest('per_page') : 20;

        $ql_thong_bao_loai = commonRequest('ql_thong_bao_loai'); //1 thông báo, 2 nhắc nhở
        $ql_thong_bao_sao = commonRequest('ql_thong_bao_sao');
        $ql_thong_bao_da_doc = commonRequest('ql_thong_bao_da_doc');

        $dateFrom = commonRequest('date_from');
        $dateTo = commonRequest('date_to');

        if ($dateFrom) $dateFrom = $dateFrom . ' 00:00:00';
        if ($dateTo) $dateTo = $dateTo . ' 23:59:59';

        $search = commonRequest('search');

        $this->db->from('ql_thong_bao')
            ->join('ql_thong_bao_nguoi_dung', 'ql_thong_bao_nguoi_dung.ql_thong_bao_id = ql_thong_bao.ql_thong_bao_id');

        if ($ql_thong_bao_loai)
            $this->db->where('ql_thong_bao.ql_thong_bao_loai', $ql_thong_bao_loai);

        if ($ql_thong_bao_sao !== null && $ql_thong_bao_sao !== '')
            $this->db->where('ql_thong_bao_nguoi_dung.ql_thong_bao_sao', $ql_thong_bao_sao);

        if ($ql_thong_bao_da_doc !== null && $ql_thong_bao_da_doc !== '')
            $this->db->where('ql_thong_bao_nguoi_dung.ql_thong_bao_da_doc', $ql_thong_bao_da_doc);

        if ($dateFrom && $dateTo) {
            // $this->db->whereBetween('ql_thong_bao.ql_thong_bao_ngay_gui', $dateFrom, $dateTo);
            $this->db->where("ql_thong_bao.ql_thong_bao_ngay_gui BETWEEN '$dateFrom' AND '$dateTo'");
        } elseif ($dateFrom && !$dateTo) {
            $this->db->where('ql_thong_bao.ql_thong_bao_ngay_gui >=', $dateFrom);
        } elseif (!$dateFrom && $dateTo) {
            $this->db->where('ql_thong_bao.ql_thong_bao_ngay_gui <=', $dateTo);
        }

        if ($search) {
            $this->db->group_start(); // Bắt đầu nhóm các điều kiện LIKE
            $this->db->like('ql_thong_bao.ql_thong_bao_tieu_de', $search);
            $this->db->or_like('ql_thong_bao_tieu_de_tieng_anh', $search);
            $this->db->or_like('ql_thong_bao.ql_thong_bao_noi_dung', $search);
            $this->db->or_like('ql_thong_bao_noi_dung_tieng_anh', $search);
            $this->db->group_end(); // Kết thúc nhóm các điều kiện LIKE
        }
        $this->db->where('ql_thong_bao_nguoi_dung.ql_nguoi_dung_id', $userId);
        $this->db->where('ql_thong_bao.ql_thong_bao_da_gui', self::SENT);

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query

        $this->db->order_by('ql_thong_bao.ql_thong_bao_ngay_gui', 'desc');
        $this->db->limit($perPage, ($page - 1) * $perPage);
        $result = $this->db->get()->result_array();

        foreach ($result as &$rs) {
            $rs['thoi_gian_gui'] = explode(' ', ($rs['ql_thong_bao_ngay_gui']))[1];
        }
        unset($rs);

        return [
            'data' => $result,
            'recordsFiltered' => $recordsFiltered
        ];
    }

    public function getSearch()
    {
        $search_from_date = commonRequest('search_from_date');
        $search_to_date = commonRequest('search_to_date');

        $ql_thong_bao_loai = commonRequest('ql_thong_bao_loai');
        $ql_thong_bao_tieude_or_noidung = commonRequest('ql_thong_bao_tieude_or_noidung');

        if ($search_from_date) $search_from_date = $search_from_date . ' 00:00:00';
        if ($search_to_date) $search_to_date = $search_to_date . ' 23:59:59';

        $draw = commonRequest('draw');

        $start = $this->input->get('start') ?? 0;
        $length = $this->input->get('length') ?? 10;

        $this->db
            ->from('ql_thong_bao');


        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);



        if ($search_from_date && $search_to_date) {
            $this->db->where('ql_thong_bao_ngay_gui >=', $search_from_date);
            $this->db->where('ql_thong_bao_ngay_gui <=', $search_to_date);
        } elseif ($search_from_date && !$search_to_date) {
            $this->db->where('ql_thong_bao_ngay_gui >=', $search_from_date);
        } elseif (!$search_from_date && $search_to_date) {
            $this->db->where('ql_thong_bao_ngay_gui <=', $search_to_date);
        }

        if ($ql_thong_bao_loai)
            $this->db->where('ql_thong_bao_loai', $ql_thong_bao_loai);

        if ($ql_thong_bao_tieude_or_noidung) {
            $this->db->group_start(); // Bắt đầu nhóm các điều kiện LIKE
            $this->db->like('ql_thong_bao_tieu_de', $ql_thong_bao_tieude_or_noidung);
            $this->db->like('ql_thong_bao_tieu_de_tieng_anh', $ql_thong_bao_tieude_or_noidung);
            $this->db->or_like('ql_thong_bao_noi_dung', $ql_thong_bao_tieude_or_noidung);
            $this->db->or_like('ql_thong_bao_noi_dung_tieng_anh', $ql_thong_bao_tieude_or_noidung);
            $this->db->group_end(); // Kết thúc nhóm các điều kiện LIKE
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query 

        $columns = [
            'ql_thong_bao_ngay_gui',
            'ql_thong_bao_loai',
            'ql_thong_bao_tieu_de',
            'ql_thong_bao_tieu_de_tieng_anh',
            'ql_thong_bao_noi_dung',
            'ql_thong_bao_noi_dung_tieng_anh',
            'ql_thong_bao_da_gui'
        ];


        if (commonRequest('order')) {
            $order = json_decode(commonRequest('order'), true);


            $orderColumnIndex = $order[0]['column'];
            $orderColumn = $columns[$orderColumnIndex];  // index starts at 0
            $orderDir = $order[0]['dir'];

            if (!empty($orderColumn) && !empty($orderDir)) {
                $this->db->order_by($orderColumn, $orderDir);
            }
        }

        if ($length != '-1') $this->db->limit($length, $start);

        $query = $this->db->get();

        $data = $query->result_array();

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }

    public function insertThongBaoNguoiDung($dataInsertNotiUser)
    {
        $this->db->insert_batch('ql_thong_bao_nguoi_dung', $dataInsertNotiUser);
    }

    public function deletePivotsById($id)
    {
        $this->db->where('ql_thong_bao_id', $id);
        $this->db->delete('ql_thong_bao_nguoi_dung');
    }
    public function deletePivotsByUserId($id)
    {
        $this->db->where('ql_nguoi_dung_id', $id);
        $this->db->delete('ql_thong_bao_nguoi_dung');
    }
    public function updateStarStatus($thongBaoId, $userId, $sao)
    {
        return $this->db->where('ql_thong_bao_id', $thongBaoId)
            ->where('ql_nguoi_dung_id', $userId)
            ->update('ql_thong_bao_nguoi_dung', [
                'ql_thong_bao_sao' => $sao,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }
    
    public function deleteNotificationsForUser($thongBaoIds, $userId)
    {
        if (empty($thongBaoIds) || !is_array($thongBaoIds)) return false;
        $this->db->where_in('ql_thong_bao_id', $thongBaoIds);
        $this->db->where('ql_nguoi_dung_id', $userId);
        return $this->db->delete('ql_thong_bao_nguoi_dung');
    }
}
