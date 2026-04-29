<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @property Common $common
 */

class Hrm_tam_ung_model extends MY_Model
{
    protected $table = 'hrm_tam_ung';
    protected $primaryKey = 'id_tam_ung';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->library(['Common']);
    }

    public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array(), $fromDate = null, $toDate = null)
    {
        $this->db->from('hrm_tam_ung AS tu')
        ->join('hrm_nhan_vien AS nv', 'nv.id_nhan_vien = tu.id_nhan_vien', 'left')
        ->join('hrm_nhan_vien AS nd', 'nd.id_nhan_vien = tu.nguoi_duyet', 'left');

        // Tính tổng bản ghi (chưa áp dụng filter)
        $totalRecordsQuery = clone $this->db;
        $totalRecordsQuery->where('tu.deleted_at IS NULL');
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        // Áp dụng tìm kiếm toàn cục
        if (!empty($searchValue)) {
            $this->db->group_start();

            $searchFields = [
                'nv.ma_nhan_vien', 
                'nv.ho_va_ten', 
                'nv.cccd_so', 
                'nv.email',
                'tu.so_tien', 
                'tu.so_thang', 
                'tu.ly_do'
            ];

            foreach ($searchFields as $field) {
                $this->db->or_like($field, $searchValue);
            }

            $this->db->group_end();
        }

        // Áp dụng filter chi tiết
        if (!empty($searchKey)) {
            $likeFields = ['ma_nhan_vien', 'ho_va_ten', 'email'];

            foreach ($searchKey as $key => $value) {
                if ($key === 'trang_thai') {
                    if (is_array($value)) {
                        $statuses = array_filter($value, fn($v) => $v !== '' && $v !== null && $v !== 'null');
                        if (!empty($statuses)) {
                            $statuses = array_map('intval', $statuses);
                            $this->db->where_in('tu.trang_thai', $statuses);
                        }
                    } elseif ($value !== '' && $value !== null && $value !== 'null') {
                        $this->db->where('tu.trang_thai', (int)$value);
                    }
                    continue; // tránh xử lý tiếp bên dưới
                }

                if ($key === 'ly_do' && !empty($value)) {
                    $this->db->like('tu.ly_do', $value);
                    continue;
                }

                if (in_array($key, $likeFields)) {
                    $this->db->like('nv.' . $key, $value);
                } else {
                    if (is_array($value)) {
                        $this->db->where_in('tu.' . $key, $value);
                    } else {
                        $this->db->where('tu.' . $key, $value);
                    }
                }
            }
        }



        if ($fromDate && $toDate) {
            $fromDate = $fromDate . ' 00:00:00';
            $toDate = $toDate . ' 23:59:59';
            $this->db->where("tu.created_at BETWEEN '{$fromDate}' AND '{$toDate}'");
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
                $this->db->order_by('tu.' . $filed, $orderDir);
            } else {
                $this->db->order_by('tu.created_at', 'DESC');
            }
        } else {
            $this->db->order_by('tu.created_at', 'DESC');
        }



        if ($length != '-1') {
            $this->db->limit($length, $start);
        }

        $this->db->where("tu.deleted_at IS NULL");

        $this->db->select(
            'tu.*, 
            nv.ma_nhan_vien, 
            nv.ho_va_ten as nhan_vien_ho_ten,
            nd.ho_va_ten as nguoi_duyet_ho_ten,
            '
        );

        $query = $this->db->get();
        $data = $query->result_array();

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ];
    }

    public function get_tam_ung($id_tam_ung)
    {
        return $this->db->from('hrm_tam_ung')
            ->join('hrm_nhan_vien', 'hrm_nhan_vien.id_nhan_vien = hrm_tam_ung.id_nhan_vien')
            ->where('id_tam_ung', $id_tam_ung)
            ->get()
            ->row_array();
    }
}
