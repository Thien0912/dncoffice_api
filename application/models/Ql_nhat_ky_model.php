<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Ql_nhat_ky_model extends MY_Model
{
    protected $table = 'ql_nhat_ky';
    protected $primaryKey = 'ql_nhat_ky_id';
    protected $hidden = [];
    protected $timestamps = true;
    protected $createdAtField = 'ql_nhat_ky_ngay_tao';
    protected $updatedAtField = 'ql_nhat_ky_ngay_cap_nhat';


    public function getSearch()
    {
        $search_from_date = commonRequest('search_from_date');
        $search_to_date = commonRequest('search_to_date');
        $ql_nguoi_dung_id = commonRequest('ql_nguoi_dung_id');

        $searchValue = commonRequest('searchValue');
        $dataFilter = commonRequest('dataFilter');
        
        $ql_nhat_ky_controller = commonRequest('ql_nhat_ky_controller');
        $ql_nhat_ky_bang_du_lieu = commonRequest('ql_nhat_ky_bang_du_lieu');

        if ($search_from_date) $search_from_date = $search_from_date . ' 00:00:00';
        if ($search_to_date) $search_to_date = $search_to_date . ' 23:59:59';

        $draw = commonRequest('draw');

        $start = $this->input->get('start') ?? 0;
        $length = $this->input->get('length') ?? 10;

        $this->db
            ->from('ql_nhat_ky')
            ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = ql_nhat_ky.ql_nguoi_dung_id');

        if ($search_from_date && $search_to_date) {
            $this->db->where('ql_nhat_ky.ql_nhat_ky_ngay_tao >=', $search_from_date);
            $this->db->where('ql_nhat_ky.ql_nhat_ky_ngay_tao <=', $search_to_date);
        } elseif ($search_from_date && !$search_to_date) {
            $this->db->where('ql_nhat_ky.ql_nhat_ky_ngay_tao >=', $search_from_date);
        } elseif (!$search_from_date && $search_to_date) {
            $this->db->where('ql_nhat_ky.ql_nhat_ky_ngay_tao <=', $search_to_date);
        }

        if ($ql_nguoi_dung_id) {
            $this->db->where('ql_nguoi_dung.ql_nguoi_dung_id', $ql_nguoi_dung_id);
        }

        if ($ql_nhat_ky_controller) {
            $this->db->where('ql_nhat_ky.ql_nhat_ky_controller', $ql_nhat_ky_controller);
        }

        if ($ql_nhat_ky_bang_du_lieu) {
            $this->db->where('ql_nhat_ky.ql_nhat_ky_bang_du_lieu', $ql_nhat_ky_bang_du_lieu);
        }


        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('ql_nhat_ky.ql_nhat_ky_hanh_dong', $searchValue);
            $this->db->or_like('ql_nhat_ky.ql_nhat_ky_noi_dung', $searchValue);

            $encodedValue = json_encode($searchValue);
            $encodedValue = trim($encodedValue, '"');
            $this->db->or_like('ql_nhat_ky.ql_nhat_ky_gia_tri_cu', $encodedValue);
            $this->db->or_like('ql_nhat_ky.ql_nhat_ky_gia_tri_moi', $encodedValue);

            $this->db->group_end();
        }



        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query 

        $columns = [
            '',
            'ql_nhat_ky.ql_nhat_ky_ngay_tao'
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


        // done apply the filtering
        $totalRecordsQuery = clone $this->db;
        $totalRecordsQuery->reset_query(); //
        $recordsTotal = $totalRecordsQuery->count_all('ql_nhat_ky');

        $data = $query->result_array();

        $data = array_map(function ($item) {
            if (isset($item['ql_nguoi_dung_mat_khau'])) {
                unset($item['ql_nguoi_dung_mat_khau']);
            }

            // Chuẩn hóa tên cột DB thành tiếng Việt cho frontend
            $fieldMapping = [
                // hrm_nhan_vien_thoi_viec
                'id_nhan_vien_thoi_viec' => 'Mã bản ghi thủ tục',
                'id_nhan_vien'           => 'Mã nhân viên',
                'id_tttv'                => 'Thủ tục',
                'ten_thu_tuc'            => 'Tên thủ tục',
                'nhom_thu_tuc'           => 'Nhóm thủ tục',
                'ngay_hoan_thanh'        => 'Ngày hoàn thành',
                'trang_thai'             => 'Trạng thái thủ tục',
                // hrm_nhan_vien_cong_viec
                'trang_thai_lam_viec'           => 'Trạng thái làm việc',
                'ly_do_thoi_viec'               => 'Lý do thôi việc',
                'ngay_lam_chinh_thuc'           => 'Ngày bắt đầu chính thức',
                'ngay_lam_chinh_thuc_ket_thuc'  => 'Ngày kết thúc làm việc',
                'loai_hop_dong'                 => 'Loại hợp đồng',
                'id_vi_tri_cong_viec'           => 'Vị trí công việc',
                'id_don_vi'                     => 'Đơn vị',
                'id_don_vi_cong_tac'            => 'Đơn vị công tác',
                'id_chuc_vu'                    => 'Chức vụ',
                'luong_co_ban'                  => 'Lương cơ bản',
                'muc_luong'                     => 'Mức lương',
                'muc_luong_thu_viec'            => 'Mức lương thử việc',
                'muc_luong_bao_hiem'            => 'Mức lương bảo hiểm',
                'ti_le_huong_luong'             => 'Tỉ lệ hưởng lương (%)',
                'phu_cap'                       => 'Phụ cấp',
                'ghi_chu'                       => 'Ghi chú',
                // Timestamps chung
                'created_at'        => 'Ngày tạo',
                'created_user_id'   => 'Người tạo',
                'updated_at'        => 'Ngày cập nhật',
                'updated_user_id'   => 'Người cập nhật',
                'deleted_at'        => 'Ngày xóa',
                'deleted_user_id'   => 'Người xóa',
            ];

            // Chuẩn hóa bộ giá trị enum
            $valueMapping = [
                // Trạng thái làm việc (hrm_nhan_vien_cong_viec.trang_thai)
                'DANG_LAM_VIEC'                 => 'Đang làm việc',
                'DANG_LAM_THU_TUC_THOI_VIEC'   => 'Đang làm thủ tục thôi việc',
                'NGHI_VIEC'                     => 'Nghỉ việc',
                'DA_THOI_VIEC'                  => 'Đã thôi việc',
                'DA_TIEP_NHAN_DON'              => 'Đã tiếp nhận đơn',
                'CHO_PHE_DUYET'                 => 'Chờ phê duyệt',
                'TU_CHOI'                       => 'Từ chối',
                // Loại hợp đồng / trạng thái nhân viên
                'thu_viec'      => 'Thử việc',
                'chinh_thuc'    => 'Chính thức',
                'nghi_viec'     => 'Nghỉ việc',
                // Trạng thái thủ tục thôi việc (hrm_nhan_vien_thoi_viec.trang_thai)
                'chua_hoan_thanh'   => 'Chưa hoàn thành',
                'hoan_thanh'        => 'Hoàn thành',
                'dang_xu_ly'        => 'Đang xử lý',
                // Boolean-like
                '0'  => 'Không',
                '1'  => 'Có',
            ];

            if (!empty($item['ql_nhat_ky_gia_tri_cu'])) {
                $oldVals = json_decode($item['ql_nhat_ky_gia_tri_cu'], true);
                if (is_array($oldVals)) {
                    $mappedVals = [];
                    foreach ($oldVals as $k => $v) {
                        $mappedKey = isset($fieldMapping[$k]) ? $fieldMapping[$k] : $k;
                        if (is_array($v) || is_null($v)) {
                            // Giữ nguyên nếu là array/null — không thể so sánh valueMapping
                            $mappedVals[$mappedKey] = $v;
                        } else {
                            $vStr = (string)$v;
                            $mappedVals[$mappedKey] = isset($valueMapping[$vStr]) ? $valueMapping[$vStr] : $v;
                        }
                    }
                    $item['ql_nhat_ky_gia_tri_cu'] = json_encode($mappedVals, JSON_UNESCAPED_UNICODE);
                }
            }

            if (!empty($item['ql_nhat_ky_gia_tri_moi'])) {
                $newVals = json_decode($item['ql_nhat_ky_gia_tri_moi'], true);
                if (is_array($newVals)) {
                    $mappedVals = [];
                    foreach ($newVals as $k => $v) {
                        $mappedKey = isset($fieldMapping[$k]) ? $fieldMapping[$k] : $k;
                        if (is_array($v) || is_null($v)) {
                            // Giữ nguyên nếu là array/null — không thể so sánh valueMapping
                            $mappedVals[$mappedKey] = $v;
                        } else {
                            $vStr = (string)$v;
                            $mappedVals[$mappedKey] = isset($valueMapping[$vStr]) ? $valueMapping[$vStr] : $v;
                        }
                    }
                    $item['ql_nhat_ky_gia_tri_moi'] = json_encode($mappedVals, JSON_UNESCAPED_UNICODE);
                }
            }

            return $item;
        }, $data);

        if ($dataFilter) {
            $filter = [
                'ql_nguoi_dung' => $this->Ql_nguoi_dung_model
                    ->where('ql_nguoi_dung_loai', 2)
                    ->select('ql_nguoi_dung_id, ql_nguoi_dung_ho_ten, ql_nguoi_dung_email')
                    ->get(),
            ];
            return [
                'draw' => $draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data,
                'dataFilter' => $filter
            ];
        }

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }

    public function getDailySV()
    {
        $result = $this
            ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = ql_nhat_ky.ql_nguoi_dung_id')
            ->where('ql_nhat_ky_bang_du_lieu', 'sv_sinh_vien')
            ->select('ql_nhat_ky.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten')
            ->orderBy('ql_nhat_ky.ql_nhat_ky_id', 'desc')
            ->get(10);

        return $result ? $result : [];
    }

    public function getDailyDiem()
    {
        $result = $this
            ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = ql_nhat_ky.ql_nguoi_dung_id')
            ->where('ql_nhat_ky_bang_du_lieu', 'diem_diem_hoc_phan')
            ->select('ql_nhat_ky.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten')
            ->orderBy('ql_nhat_ky.ql_nhat_ky_id', 'desc')
            ->get(10);

        return $result ? $result : [];
    }
}
