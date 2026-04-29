<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Hrm_hop_dong_phu_luc_model extends MY_Model
{
    protected $table = 'hrm_hop_dong_phu_luc';
    protected $primaryKey = 'id_hop_dong_phu_luc';
    protected $timestamps = true;
    protected $createdAtField = 'created_at';
    protected $updatedAtField = 'updated_at';

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $columns = [], $searchKey = array(), $fromDate = null, $toDate = null, $auth)
    {
        $this->db->from('hrm_hop_dong_phu_luc')
            ->join('hrm_hop_dong', 'hrm_hop_dong.id_hop_dong = hrm_hop_dong_phu_luc.id_hop_dong', 'left')
            ->join('ql_nguoi_dung as nguoitao', 'nguoitao.ql_nguoi_dung_id = hrm_hop_dong_phu_luc.created_user_id', 'left')
            ->where('hrm_hop_dong_phu_luc.deleted_at', null)
            ->order_by('hrm_hop_dong_phu_luc.id_hop_dong_phu_luc', 'DESC');

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('hrm_hop_dong_phu_luc.ten_phu_luc', $searchValue);
            $this->db->or_like('hrm_hop_dong_phu_luc.ngay_ky_phu_luc', $searchValue);
            $this->db->or_like('hrm_hop_dong_phu_luc.ngay_hieu_luc', $searchValue);
            $this->db->group_end();
        }

        if ($fromDate && $toDate) {
            $this->db->where("ngay_hieu_luc BETWEEN '{$fromDate}' AND '{$toDate}'");
        }

        if (!empty($searchKey)) {
            foreach ($searchKey as $key => $value) {
                if (!empty($key)) {
                    // $this->db->where('hrm_hop_dong_phu_luc.id_hop_dong', 254);

                    switch ($key) {
                        case 'id_hop_dong':
                            $this->db->where('hrm_hop_dong_phu_luc.id_hop_dong', $value);
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
                // 'ten_nguoi_tao'      => 'nguoitao.ql_nguoi_dung_ho_ten',
                // Mặc định nếu không map thì nó sẽ lấy e_van_ban.[columnName]
            ];
            $this->handleDatatableColumns($columns, $columnMapping);
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query

        if ($length != '-1') {
            $this->db->limit($length, $start);
        }


        $this->db->select('
            hrm_hop_dong_phu_luc.*,
            nguoitao.ql_nguoi_dung_ho_ten AS ten_nguoi_tao,
            nguoitao.ql_nguoi_dung_email AS email_nguoi_tao,
        ');

        $query = $this->db->get();
        $data = $query->result_array();
        $sql = $this->db->last_query();


        foreach ($data as &$dt) {
            $dsFile = '';
            $dsFile = json_decode($dt['file_phu_luc'], true);
            if ($dsFile) {
                foreach ($dsFile as &$file) {
                    $file['ten_file_goc'] = $file['file_name'];
                    $file['duong_dan'] = encryptString($file['file_path']);
                    $file['dung_luong'] = $file['file_size'];
                    $file['loai_file'] = $file['file_extension'];
                }
                unset($file);
            }

            $dt['file_phu_luc'] = $dsFile;

            $dsPhuCap = $this->db->select('hrm_phu_luc_phu_cap.id_hop_dong_phu_luc, hrm_phu_luc_phu_cap.so_tien, hrm_phu_cap.id_phu_cap, hrm_phu_cap.ten_phu_cap, hrm_phu_cap.ma_phu_cap')
                ->from('hrm_phu_luc_phu_cap')
                ->join('hrm_phu_cap', 'hrm_phu_cap.id_phu_cap = hrm_phu_luc_phu_cap.id_phu_cap', 'left')
                ->where('hrm_phu_luc_phu_cap.id_hop_dong_phu_luc', $dt['id_hop_dong_phu_luc'])
                ->get()
                ->result_array();

            $dt['phu_cap'] = $dsPhuCap;
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'sql' => $sql
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
                $dbColumn = $columnMapping[$columnName] ?? "hrm_hop_dong_phu_luc.$columnName";

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

        foreach ($orderBy as $order) {
            $colIndex = $order['column'] ?? null;
            $dir = $order['dir'] ?? 'asc';

            if (isset($columns[$colIndex])) {
                $colName = $columns[$colIndex]['data'] ?? null;
                if ($colName) {
                    $this->db->order_by("hrm_hop_dong_phu_luc.$colName", $dir);
                }
            }
        }
    }
}
