<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

class Ngayle extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Hrm_ngay_le_viet_nam_model', 'Hrm_nhan_vien_model']);
        
        // Cho phép public get
        if (!$this->inSegment(['ngayle.index'])) {
            $this->permissionMiddleware();
        }
    }

    private function checkCrudPermission()
    {
        $auth = $this->getUserLogin();
        if (!$auth) return false;
        
        $isAdmin = isset($auth['ql_nguoi_dung_is_admin']) && $auth['ql_nguoi_dung_is_admin'] == 1;
        
        $nhanVien = $this->db->select('e_don_vi.ma_don_vi')
            ->from('hrm_nhan_vien')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_nhan_vien.id_don_vi_cong_tac', 'left')
            ->where('hrm_nhan_vien.ql_nguoi_dung_id', $auth['ql_nguoi_dung_id'])
            ->get()
            ->row_array();
            
        $isPTCHC = (!empty($nhanVien['ma_don_vi']) && $nhanVien['ma_don_vi'] === 'PHONG_TCHC');
        
        return $isAdmin || $isPTCHC;
    }

    public function index_get()
    {
        $start = commonRequest('start', 0);
        $length = commonRequest('length', -1);
        $search = commonRequest('search'); 
        $searchValue = commonRequest('searchValue') ?: (isset($search['value']) ? $search['value'] : null);
        
        $this->db->select('*')->from('hrm_ngay_le_viet_nam');
        $this->db->group_start();
        $this->db->where('deleted_at IS NULL', null, false);
        $this->db->group_end();
        
        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('ten_ngay_le', $searchValue);
            $this->db->group_end();
        }
        $this->db->order_by('batdau', 'ASC');
        
        $tempDb = clone $this->db;
        $total = $tempDb->count_all_results('', false);
        
        if ($length > 0) {
            $this->db->limit($length, $start);
        }
        $data = $this->db->get()->result_array();
        
        resSuccess([
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $data
        ], 'Lấy danh sách ngày lễ thành công');
    }

    public function create_post()
    {
        if (!$this->checkCrudPermission()) resError('Không có quyền thao tác (Yêu cầu Super Admin hoặc Phòng TCHC)', 403);
        
        $data = [
            'ten_ngay_le' => commonRequest('ten_ngay_le'),
            'batdau'      => commonRequest('batdau'),
            'ketthuc'     => commonRequest('ketthuc'),
            'mota'        => commonRequest('mota'),
            'la_nghi_buoi'  => commonRequest('la_nghi_buoi', 0),
            'is_active'   => commonRequest('is_active', 1),
            'ngay_am'     => commonRequest('ngay_am'),
            'la_ngay_le_am' => commonRequest('la_ngay_le_am', 0),
            'duoc_nghi'   => commonRequest('duoc_nghi', 1),
        ];
        
        $this->db->insert('hrm_ngay_le_viet_nam', $data);
        $insertId = $this->db->insert_id();
        
        $this->createLog('create', 'Thêm mới ngày lễ', null, $data, 'hrm_ngay_le_viet_nam');
        
        resSuccess(['id' => $insertId], 'Thêm ngày lễ thành công');
    }

    public function update_post($id)
    {
        if (!$this->checkCrudPermission()) resError('Không có quyền thao tác (Yêu cầu Super Admin hoặc Phòng TCHC)', 403);
        
        $oldData = $this->db->where('id_ngay_le', $id)->get('hrm_ngay_le_viet_nam')->row_array();
        if (!$oldData) resError('Không tìm thấy ngày lễ', 404);
        
        $data = [
            'ten_ngay_le'   => commonRequest('ten_ngay_le'),
            'batdau'        => commonRequest('batdau'),
            'ketthuc'       => commonRequest('ketthuc'),
            'mota'          => commonRequest('mota'),
            'la_nghi_buoi'  => commonRequest('la_nghi_buoi'),
            'is_active'     => commonRequest('is_active'),
            'ngay_am'       => commonRequest('ngay_am'),
            'la_ngay_le_am' => commonRequest('la_ngay_le_am'),
            'duoc_nghi'     => commonRequest('duoc_nghi'),
        ];

        // Xóa null fields để ko đè mất
        foreach ($data as $key => $val) {
            if ($val === null && !in_array($key, ['batdau', 'ketthuc', 'mota', 'ngay_am'])) {
                unset($data[$key]);
            }
        }
        
        $this->db->where('id_ngay_le', $id)->update('hrm_ngay_le_viet_nam', $data);
        
        $this->createLog('update', 'Cập nhật ngày lễ', $oldData, $data, 'hrm_ngay_le_viet_nam');
        
        resSuccess(null, 'Cập nhật ngày lễ thành công');
    }

    public function delete_post($id)
    {
        if (!$this->checkCrudPermission()) resError('Không có quyền thao tác (Yêu cầu Super Admin hoặc Phòng TCHC)', 403);
        
        $oldData = $this->db->where('id_ngay_le', $id)->get('hrm_ngay_le_viet_nam')->row_array();
        if (!$oldData) resError('Không tìm thấy ngày lễ', 404);
        
        $this->db->where('id_ngay_le', $id)->update('hrm_ngay_le_viet_nam', [
            'deleted_at' => date('Y-m-d H:i:s'),
            'is_active'  => 0
        ]);
        
        $this->createLog('delete', 'Xóa ngày lễ', $oldData, null, 'hrm_ngay_le_viet_nam');
        
        resSuccess(null, 'Xóa ngày lễ thành công');
    }
}
