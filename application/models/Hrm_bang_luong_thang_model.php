<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @property Common $common
 */

class Hrm_bang_luong_thang_model extends MY_Model
{
    protected $table = 'hrm_bang_luong_thang';
    protected $primaryKey = 'bang_luong_thang_id';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->library(['Common']);
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $columns = [], $searchKey = array(), $fromDate = null, $toDate = null, $auth)
    {
        $this->db->from('hrm_bang_luong_thang')
            ->where('deleted_at IS NULL');
        // ->join('hrm_nhan_vien AS nguoi_tao', 'nguoi_tao.ql_nguoi_dung_id = hrm_bang_luong_thang.nguoi_tao_id', 'left')
        // ->join('hrm_nhan_vien AS nguoi_duyet', 'nguoi_duyet.ql_nguoi_dung_id = hrm_bang_luong_thang.nguoi_duyet_id', 'left');

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('hrm_bang_luong_thang.thang', $searchValue);
            $this->db->or_like('hrm_bang_luong_thang.tong_tien', $searchValue);
            $this->db->group_end();
        }

        if ($fromDate && $toDate) {
            $this->db->where("hrm_bang_luong_thang.thang BETWEEN '{$fromDate}' AND '{$toDate}'");
        }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                if (!empty($key)) {
                    switch ($key) {
                        case 'selectedClassify_BLThang':
                            switch ($value) {
                                case 'all':
                                    break;
                                case 'duyet_cap_mot':
                                    $this->db->where('hrm_bang_luong_thang.trang_thai', $this->common::STATUS_HOP_DONG['Da_duyet']['value']);
                                    break;
                                case 'duyet_cap_hai':
                                    $this->db->where('hrm_bang_luong_thang.trang_thai_cap_hai', $this->common::STATUS_HOP_DONG['Da_duyet']['value']);
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
                // 'ten_nguoi_tao'    => 'nguoi_tao.ql_nguoi_dung_ho_ten',
                // 'ten_khoi_co_quan' => 'e_khoi_co_quan.ten_khoi_co_quan',
                // 'ten_co_quan'      => 'e_co_quan.ten_co_quan',
                // 'ten_loai'         => 'e_loai.ten_loai',
                // 'ten_tinh_chat'    => 'e_tinh_chat.ten_tinh_chat',
                // 'trang_thai'       => 'e_van_ban.trang_thai', // xử lý riêng
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

        $this->db->select(
            '
            hrm_bang_luong_thang.*
            '
        );

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

    private function handleDatatableOrdering(array $orderBy, array $columns): void
    {
        if (empty($orderBy)) return;

        foreach ($orderBy as $order) {
            $colIndex = $order['column'] ?? null;
            $dir = $order['dir'] ?? 'asc';

            if (isset($columns[$colIndex])) {
                $colName = $columns[$colIndex]['data'] ?? null;
                if ($colName) {
                    $this->db->order_by("hrm_bang_luong_thang.$colName", $dir);
                }
            }
        }
    }

    function getTrangThaiValueFromLabel($label)
    {
        foreach (Common::STATUS_BANG_LUONG_THANG as $status) {
            // if (isset($status['label']) && strtolower($status['label']) == strtolower($label)) {
            //     return $status['value'];
            // }

            if (isset($status['label']) && mb_strtolower($status['label'], 'UTF-8') == mb_strtolower($label, 'UTF-8')) {
                return $status['value'];
            }
        }
        return null;
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
                $dbColumn = $columnMapping[$columnName] ?? "hrm_bang_luong_thang.$columnName";

                // Nếu là trạng_thai thì convert lại
                if ($columnName === 'trang_thai' || $columnName === 'trang_thai_cap_hai') {
                    $searchValue = $this->getTrangThaiValueFromLabel($searchValue);
                }

                $this->db->like($dbColumn, $searchValue);
            }
        }
    }
}
