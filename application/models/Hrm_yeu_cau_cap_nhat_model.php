<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_yeu_cau_cap_nhat_model extends MY_Model
{
    protected $table = 'hrm_yeu_cau_cap_nhat';
    protected $primaryKey = 'id_yeu_cau_cap_nhat';
    protected $timestamps = false;
    protected $createdAtField = 'ngay_tao';
    protected $updatedAtField = 'ngay_cap_nhat';

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $columns = [], $searchKey = array(), $fromDate = null, $toDate = null, $auth)
    {
        $this->db->from('hrm_yeu_cau_cap_nhat')
            ->join('ql_nguoi_dung nguoi_duyet', 'nguoi_duyet.ql_nguoi_dung_id = hrm_yeu_cau_cap_nhat.nguoi_duyet', 'left')
            ->join('hrm_nhan_vien', 'hrm_nhan_vien.id_nhan_vien = hrm_yeu_cau_cap_nhat.id_nhan_vien', 'left');

        $this->db->where('hrm_nhan_vien.deleted_at IS NULL');
        $this->db->order_by('hrm_yeu_cau_cap_nhat.id_yeu_cau_cap_nhat', 'DESC');

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('hrm_nhan_vien.ma_nhan_vien', $searchValue);
            $this->db->or_like('hrm_nhan_vien.ho_va_ten', $searchValue);
            $this->db->or_like('hrm_nhan_vien.gioi_tinh', $searchValue);
            $this->db->or_like('hrm_nhan_vien.ngay_sinh', $searchValue);
            $this->db->or_like('hrm_nhan_vien.trinh_do_dt', $searchValue);
            $this->db->or_like('hrm_nhan_vien.noi_dt', $searchValue);
            $this->db->or_like('hrm_nhan_vien.nganh_dt', $searchValue);

            $this->db->group_end();
        }

        if (!empty($searchKey)) {
            $arr = ['ma_nhan_vien', 'ho_va_ten', 'email'];
            foreach ($searchKey as $key => $value) {
                if (in_array($key, $arr)) {
                    $this->db->like('hrm_nhan_vien.' . $key, $value);
                } 
                // else {
                //     if (is_array($value)) {
                //         $this->db->where_in('hrm_nhan_vien.' . $key, $value);
                //     } else {
                //         $this->db->where('hrm_nhan_vien.' . $key, $value);
                //     }
                // }
            }
        }

        if (!empty($columns)) {
            // Map tên cột hiển thị (alias) sang cột thật trong DB
            $columnMapping = [
                'ma_nhan_vien'      => 'hrm_nhan_vien.ma_nhan_vien',
                'ho_va_ten'         => 'hrm_nhan_vien.ho_va_ten',
                'ngay_sinh'         => 'hrm_nhan_vien.ngay_sinh',
                'trinh_do_dt'       => 'hrm_nhan_vien.trinh_do_dt',
                'noi_dt'            => 'hrm_nhan_vien.noi_dt',
                'email'             => 'hrm_nhan_vien.email',
                'nguoi_duyet'       => 'ql_nguoi_dung.ql_nguoi_dung_ho_ten',
                // Mặc định nếu không map thì nó sẽ lấy e_van_ban.[columnName]
            ];
            $this->handleDatatableColumns($columns, $columnMapping);
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE);

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
                hrm_nhan_vien.ho_va_ten,
                hrm_nhan_vien.gioi_tinh,
                hrm_nhan_vien.ngay_sinh,
                hrm_nhan_vien.trinh_do_dt,
                hrm_nhan_vien.noi_dt,
                hrm_nhan_vien.email,
                hrm_yeu_cau_cap_nhat.*,
                nguoi_duyet.ql_nguoi_dung_ho_ten
            '
        );

        $query = $this->db->get();
        $data = $query->result_array();
        $sql = $this->db->last_query();

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'sql' => $sql
        ];
    }

    public function countRequest()
    {
        $this->db->select()
            ->from($this->table)
            ->where('trang_thai', 0);
        $query = $this->db->get();
        return $query->num_rows();
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
                    $this->db->order_by("hrm_yeu_cau_cap_nhat.$colName", $dir);
                }
            }
        }
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
                $dbColumn = $columnMapping[$columnName] ?? "hrm_yeu_cau_cap_nhat.$columnName";
                $this->db->like($dbColumn, $searchValue);
            }
        }
    }
}
