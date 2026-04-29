<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_nhan_vien_thong_tin_gia_dinh $Hrm_nhan_vien_thong_tin_gia_dinh
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Fileupload $fileupload
 * @property Validate $validate
 */



class Thongtingiadinh extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_nhan_vien_thong_tin_gia_dinh', 'Hrm_nhan_vien_model']);
        $this->load->library(['Validator', 'Fileupload', 'Validate']);
    }

    public function create_post()
    {
        $validator = new Validator();
        $data = [
            'ho_ten' => commonRequest('ho_ten') ?? null,
            // 'ngay_sinh' => commonRequest('ngay_sinh') ?? null,
            'nam_sinh' => commonRequest('nam_sinh') ?? null,
            'gioi_tinh' => commonRequest('gioi_tinh') ?? null,
            'moi_quan_he' => commonRequest('moi_quan_he') ?? null,
            'nghe_nghiep' => commonRequest('nghe_nghiep') ?? null,
            'id_nhan_vien' => commonRequest('id_nhan_vien') ?? null,
            'so_dien_thoai' => commonRequest('so_dien_thoai') ?? null,
        ];
        $rules = [
            'ho_ten' => 'required',
            // 'ngay_sinh' => 'date',
            'gioi_tinh' => 'required',
            'moi_quan_he' => 'required',
        ];
        $customMessages = [
            'ho_ten.required' => 'Vui lòng nhập họ tên',
            // 'ngay_sinh.date' => 'Ngày sinh không đúng định dạng',
            'gioi_tinh.required' => 'Vui lòng chọn giới tính',
            'moi_quan_he.required' => 'Vui lòng nhập mối quan hệ',
            'so_dien_thoai.required' => 'Vui lòng nhập số điện thoại',
        ];
        $validator->setCustomMessages($customMessages);
        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors(), 'Vui lòng nhập đầy đủ các trường!');
        }
        $this->db->trans_start();
        $thongTinGiaDinh = $this->Hrm_nhan_vien_thong_tin_gia_dinh->create($data);
        $thongTinGiaDinh['count'] = $this->Hrm_nhan_vien_thong_tin_gia_dinh
            ->where('id_nhan_vien', commonRequest('id_nhan_vien'))
            ->count();
        //create log
        $this->createLog('create', 'Tạo mới thông tin gia đình', null, $thongTinGiaDinh, 'hrm_nhan_vien_thong_tin_gia_dinh');
        
        $changes = [
            [
                'field' => 'thong_tin_gia_dinh',
                'field_name' => 'Thông tin gia đình',
                'old_value' => null,
                'new_value' => [
                    'value' => 'Thêm mới',
                    'label' => 'Mối quan hệ: ' . $data['moi_quan_he'] . ' - Họ tên: ' . $data['ho_ten']
                ]
            ]
        ];
        $this->logEmployeeHistory($data['id_nhan_vien'], 'Thêm thông tin gia đình', $changes);
        
        $this->db->trans_commit();
        resSuccess($thongTinGiaDinh, 'Thêm thành thông tin gia đình công');
    }

    public function show_get($id)
    {
        $data = $this->Hrm_nhan_vien_thong_tin_gia_dinh->find($id);

        if (!$data) {
            resError('Không tìm thấy thông tin gia đình!');
        }

        resSuccess($data, 'Lấy thông tin gia đình thành công!');
    }

    public function update_post($id)
    {
        $oldData = $this->Hrm_nhan_vien_thong_tin_gia_dinh->find($id);
        if (!$oldData) {
            resError('Không tìm thấy thông tin gia đình!');
        }

        $validator = new Validator();
        $data = [
            'ho_ten' => commonRequest('ho_ten') ?? null,
            // 'ngay_sinh' => commonRequest('ngay_sinh') ?? null,
            'nam_sinh' => commonRequest('nam_sinh') ?? null,
            'gioi_tinh' => commonRequest('gioi_tinh') ?? null,
            'moi_quan_he' => commonRequest('moi_quan_he') ?? null,
            'nghe_nghiep' => commonRequest('nghe_nghiep') ?? null,
            'so_dien_thoai' => commonRequest('so_dien_thoai') ?? null,
        ];
        $rules = [
            'ho_ten' => 'required',
            // 'ngay_sinh' => 'date',
            'gioi_tinh' => 'required',
            'moi_quan_he' => 'required',
        ];
        $customMessages = [
            'ho_ten.required' => 'Vui lòng nhập họ tên',
            // 'ngay_sinh.date' => 'Ngày sinh không đúng định dạng',
            'gioi_tinh.required' => 'Vui lòng chọn giới tính',
            'moi_quan_he.required' => 'Vui lòng nhập mối quan hệ',
            'so_dien_thoai.required' => 'Vui lòng nhập số điện thoại',
        ];
        $validator->setCustomMessages($customMessages);
        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors(), 'Vui lòng nhập đầy đủ các trường!');
        }
        $this->db->trans_start();
        $this->Hrm_nhan_vien_thong_tin_gia_dinh
            ->where('id_thong_tin_gia_dinh', $id)
            ->update($data);

        $thongTinGiaDinh = $this->Hrm_nhan_vien_thong_tin_gia_dinh->find($id);
        $thongTinGiaDinh['dsTTGD'] = $this->Hrm_nhan_vien_thong_tin_gia_dinh
            ->where('id_nhan_vien', $oldData['id_nhan_vien'])
            ->get();

        //create log
        $this->createLog('create', 'Tạo mới thông tin gia đình', null, $thongTinGiaDinh, 'hrm_nhan_vien_thong_tin_gia_dinh');
        
        $changes = [
            [
                'field' => 'thong_tin_gia_dinh',
                'field_name' => 'Thông tin gia đình',
                'old_value' => [
                    'value' => 'Cập nhật',
                    'label' => 'Mối quan hệ: ' . $oldData['moi_quan_he'] . ' - Họ tên: ' . $oldData['ho_ten']
                ],
                'new_value' => [
                    'value' => 'Cập nhật',
                    'label' => 'Mối quan hệ: ' . $data['moi_quan_he'] . ' - Họ tên: ' . $data['ho_ten']
                ]
            ]
        ];
        $this->logEmployeeHistory($oldData['id_nhan_vien'], 'Cập nhật thông tin gia đình', $changes);
        
        $this->db->trans_commit();
        resSuccess($thongTinGiaDinh, 'Sửa thành thông tin gia đình công', 200, true);
    }

    public function delete_post()
    {
        $ids = commonRequest('ids');
        $ttgdDS = $this->Hrm_nhan_vien_thong_tin_gia_dinh->whereIn('id_thong_tin_gia_dinh', $ids)->get();

        if (count($ids) != count($ttgdDS)) {
            resError('Danh sách ID không hợp lệ!');
        }

        $this->db->trans_start();
        $this->Hrm_nhan_vien_thong_tin_gia_dinh->whereIn('id_thong_tin_gia_dinh', $ids)->delete();

        $this->createLog('delete', 'Xóa thông tin gia đình', $ttgdDS,  null, 'hrm_nhan_vien_thong_tin_gia_dinh');
        
        $changes = [];
        $id_nhan_vien = $ttgdDS[0]['id_nhan_vien'] ?? null;
        if ($id_nhan_vien) {
            foreach($ttgdDS as $item) {
                $changes[] = [
                    'field' => 'thong_tin_gia_dinh',
                    'field_name' => 'Thông tin gia đình',
                    'old_value' => [
                        'value' => 'Xoá',
                        'label' => 'Mối quan hệ: ' . $item['moi_quan_he'] . ' - Họ tên: ' . $item['ho_ten']
                    ],
                    'new_value' => null
                ];
            }
            $this->logEmployeeHistory($id_nhan_vien, 'Xoá thông tin gia đình', $changes);
        }
        
        $this->db->trans_commit();
        resSuccess($ttgdDS, 'Xóa thành công');
    }
}
