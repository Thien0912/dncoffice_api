<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Hrm_quy_dinh_nghi_phep $Hrm_quy_dinh_nghi_phep
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 * @property Fileupload $fileupload
 * @property Hrm_nghi_phep_cong_don_model $Hrm_nghi_phep_cong_don_model
 * @property Hrm_danh_muc_nghi_phep_cong_don_model $Hrm_danh_muc_nghi_phep_cong_don_model
 */



class Nhanvien extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model([
            'Hrm_nhan_vien_model',
            'Hrm_quy_dinh_nghi_phep',
            'Ql_nguoi_dung_model',
            'Hrm_nghi_phep_cong_don_model',
            'Hrm_danh_muc_nghi_phep_cong_don_model'
        ]);
        $this->load->library(['Validator', 'Fileupload']);
    }

    public function index_get()
    {
        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;

        $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
            'order' => commonRequest('order'),
            'columns' => commonRequest('columns')
        ] : [];

        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];

        $data = $this->Hrm_nhan_vien_model->getAll($start, $length, $searchValue, $orderBy, $searchKey);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function create_post()
    {
        $validator = new Validator();

        $ma_nhan_vien = commonRequest('ma_nhan_vien') ? commonRequest('ma_nhan_vien') : null;
        if (!$ma_nhan_vien) {
            $validator->addError('', 'ma_nhan_vien', 'Mã nhân viên bắt buộc nhập');
            resBadrequest(resBadrequest($validator->errors()));
        }
        if ($this->Hrm_nhan_vien_model->checkValueExists('ma_nhan_vien', $ma_nhan_vien)) {
            $validator->addError('', 'ma_nhan_vien', 'Mã nhân viên đã tồn tại');
            resBadrequest(resBadrequest($validator->errors()));
        }

        $email = commonRequest('email') ? commonRequest('email') : null;
        if (!$email) {
            $validator->addError('', 'email', 'Email bắt buộc nhập');
            resBadrequest(resBadrequest($validator->errors()));
        }
        if ($this->Hrm_nhan_vien_model->checkValueExists('email', $email) || $this->Ql_nguoi_dung_model->checkValueExists('ql_nguoi_dung_email', $email)) {
            $validator->addError('', 'email', 'Email đã tồn tại');
            resBadrequest(resBadrequest($validator->errors()));
        }

        $gioitinh = commonRequest('gioi_tinh') ? commonRequest('gioi_tinh') : null;
        if (!in_array($gioitinh, ['M', 'F'])) {
            $validator->addError('', 'gioi_tinh', 'Giới tính không hợp lệ');
            resBadrequest(resBadrequest($validator->errors()));
        }


        $auth = $this->getUserLogin();
        $data = [
            'ma_nhan_vien' => $ma_nhan_vien,
            'ho_ten' => commonRequest('ho_ten') ? commonRequest('ho_ten') : null,
            'gioi_tinh' => $gioitinh,
            'ngay_sinh' => commonRequest('ngay_sinh') ? commonRequest('ngay_sinh') : null,
            'so_dien_thoai' => commonRequest('so_dien_thoai') ? commonRequest('so_dien_thoai') : null,
            'email' => $email,
            'id_don_vi' => commonRequest('id_don_vi') ? commonRequest('id_don_vi') : null,
            'id_chuc_vu' => commonRequest('id_chuc_vu') ? commonRequest('id_chuc_vu') : null,
            'ngay_vao_lam' => commonRequest('ngay_vao_lam') ? commonRequest('ngay_vao_lam') : null,
            'ngay_vao_lam_chinh_thuc' => commonRequest('ngay_vao_lam_chinh_thuc') ? commonRequest('ngay_vao_lam_chinh_thuc') : null,
            'so_nguoi_phu_thuoc' => commonRequest('so_nguoi_phu_thuoc') ? commonRequest('so_nguoi_phu_thuoc') : 0,
            'nguoi_tao' => $auth['ql_nguoi_dung_id'],
            'nguoi_sua' => $auth['ql_nguoi_dung_id']
        ];
        $rules = [
            'ho_ten' => 'required',
            'ngay_sinh' => 'date',
            'email' => 'email',
            'id_don_vi' => 'integer',
            'id_chuc_vu' => 'integer',
            'ngay_vao_lam' => 'date',
        ];
        $customMessages = [
            'ho_ten.required' => 'Họ tên bắt buộc nhập',
            'ngay_sinh.date' => 'Ngày sinh không đúng định dạng',
            'email.email' => 'Email không đúng định dạng',
            'ngay_vao_lam.date' => 'Ngày vào làm không đúng định dạng'
        ];

        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        //Upload avatar
        $folderName = 'employees/' . date('Y') . '/' . date('m');
        if (isset($_FILES['anh_dai_dien'])) {
            $uploadedFile = $this->fileupload->upload($_FILES['anh_dai_dien'], $folderName);
            if (!$uploadedFile['success']) {
                resBadrequest([
                    'anh_dai_dien' => [
                        'Không thể tải lên file'
                    ]
                ]);
            } else {
                $data['anh_dai_dien'] = $uploadedFile['file_path'];
            }
        }

        $this->db->trans_start();

        $nhanvien = $this->Hrm_nhan_vien_model->create($data);

        //Thêm vào ngày phép cộng dồn
        $ngay_vao_lam_chinh_thuc = commonRequest('ngay_vao_lam_chinh_thuc') ? commonRequest('ngay_vao_lam_chinh_thuc') : null;
        $id_quy_dinh_nghi_phep = commonRequest('id_quy_dinh_nghi_phep') ? commonRequest('id_quy_dinh_nghi_phep') : null;
        if ($id_quy_dinh_nghi_phep && $ngay_vao_lam_chinh_thuc) {
            $quydinhnghiphep = $this->Hrm_quy_dinh_nghi_phep->where('id_quy_dinh_nghi_phep', $id_quy_dinh_nghi_phep)->first();
            if (!$quydinhnghiphep) {
                resError('Quy định nghỉ phép không hợp lệ');
            }
            $ngayphepcoban = $quydinhnghiphep['ngay_phep_co_ban'];
            $currentYear = date('Y');
            $namvaolam = date('Y', strtotime($ngay_vao_lam_chinh_thuc));
            $ngaycuoinam =  new DateTime($namvaolam . '-12-31');
            $ngaylamchinhthuc = new DateTime($ngay_vao_lam_chinh_thuc);

            $diff = $ngaylamchinhthuc->diff($ngaycuoinam);
            $sothangdencuoinamvaolam = $diff->m;

            // $danhmucnghiphepcongdon = $this->Hrm_danh_muc_nghi_phep_cong_don_model->

            //Chạy từ năm vào làm đến năm hiện tại
            for ($i = $namvaolam; $i <= $currentYear; $i++) {
                $ngayphepcongdon = $this->Hrm_nghi_phep_cong_don_model->where('id_nhan_vien')->where('nam', $i)->first();
                if (!$ngayphepcongdon) {
                    if ($i == $namvaolam) {
                        $this->Hrm_nghi_phep_cong_don_model->insert([
                            'id_nhan_vien' => $nhanvien['id_nhan_vien'],
                            'nam' => $i,
                            'so_ngay_phep' => $sothangdencuoinamvaolam, //số ngày phép tương ứng với số tháng
                            'so_ngay_cong_don' => 0
                        ]);
                    } else {
                        $this->Hrm_nghi_phep_cong_don_model->insert([
                            'id_nhan_vien' => $nhanvien['id_nhan_vien'],
                            'nam' => $i,
                            'so_ngay_phep' => $ngayphepcoban, //số ngày phép tương ứng với số tháng
                            'so_ngay_cong_don' => 0
                        ]);
                    }
                }
            }
        }

        //create log
        $this->createLog('create', 'Tạo mới nhân viên', null, $nhanvien, 'hrm_nhan_vien');
        $this->db->trans_commit();
        resSuccess($nhanvien, 'Thêm thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }

    public function show_get($id)
    {
        $nhanvien = $this->Hrm_nhan_vien_model->find($id);
        if (!$nhanvien) {
            resError('Dữ liệu không tồn tại', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }
        resSuccess($nhanvien);
    }

    public function update_put($id)
    {
        $validator = new Validator();

        $nhanvien = $this->Hrm_nhan_vien_model->find($id);
        if (!$nhanvien) {
            resError('Nhân viên không tồn tại', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }

        $ma_nhan_vien = commonRequest('ma_nhan_vien') ? commonRequest('ma_nhan_vien') : null;
        if (!$ma_nhan_vien) {
            $validator->addError('', 'ma_nhan_vien', 'Mã nhân viên bắt buộc nhập');
            resBadrequest(resBadrequest($validator->errors()));
        }
        if ($this->Hrm_nhan_vien_model->where('ma_nhan_vien', $ma_nhan_vien)->where('id_nhan_vien !=', $id)->first()) {
            $validator->addError('', 'ma_nhan_vien', 'Mã nhân viên đã tồn tại');
            resBadrequest(resBadrequest($validator->errors()));
        }

        $email = commonRequest('email') ? commonRequest('email') : null;
        if (!$email) {
            $validator->addError('', 'email', 'Email bắt buộc nhập');
            resBadrequest(resBadrequest($validator->errors()));
        }
        if (
            $this->Hrm_nhan_vien_model->where('email', $email)->where('id_nhan_vien !=', $id)->first()
            || $this->Ql_nguoi_dung_model->where('ql_nguoi_dung_email', $email)->where('ql_nguoi_dung_id !=', $nhanvien['ql_nguoi_dung_id'])->first()
        ) {
            $validator->addError('', 'email', 'Email đã tồn tại');
            resBadrequest(resBadrequest($validator->errors()));
        }

        $gioitinh = commonRequest('gioi_tinh') ? commonRequest('gioi_tinh') : null;
        if (!in_array($gioitinh, ['M', 'F'])) {
            $validator->addError('', 'gioi_tinh', 'Giới tính không hợp lệ');
            resBadrequest(resBadrequest($validator->errors()));
        }


        $auth = $this->getUserLogin();
        $data = [
            'ma_nhan_vien' => $ma_nhan_vien,
            'ho_ten' => commonRequest('ho_ten') ? commonRequest('ho_ten') : null,
            'gioi_tinh' => $gioitinh,
            'ngay_sinh' => commonRequest('ngay_sinh') ? commonRequest('ngay_sinh') : null,
            'so_dien_thoai' => commonRequest('so_dien_thoai') ? commonRequest('so_dien_thoai') : null,
            'email' => $email,
            'id_don_vi' => commonRequest('id_don_vi') ? commonRequest('id_don_vi') : null,
            'id_chuc_vu' => commonRequest('id_chuc_vu') ? commonRequest('id_chuc_vu') : null,
            // 'ngay_vao_lam' => commonRequest('ngay_vao_lam') ? commonRequest('ngay_vao_lam') : null,
            // 'ngay_vao_lam_chinh_thuc' => commonRequest('ngay_vao_lam_chinh_thuc') ? commonRequest('ngay_vao_lam_chinh_thuc') : null,
            'so_nguoi_phu_thuoc' => commonRequest('so_nguoi_phu_thuoc') ? commonRequest('so_nguoi_phu_thuoc') : 0,
            'nguoi_tao' => $auth['ql_nguoi_dung_id'],
            'nguoi_sua' => $auth['ql_nguoi_dung_id']
        ];
        $rules = [
            'ho_ten' => 'required',
            'ngay_sinh' => 'date',
            'email' => 'email',
            'id_don_vi' => 'integer',
            'id_chuc_vu' => 'integer',
            'ngay_vao_lam' => 'date',
        ];
        $customMessages = [
            'ho_ten.required' => 'Họ tên bắt buộc nhập',
            'ngay_sinh.date' => 'Ngày sinh không đúng định dạng',
            'email.email' => 'Email không đúng định dạng',
            'ngay_vao_lam.date' => 'Ngày vào làm không đúng định dạng'
        ];

        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        //Upload avatar
        $folderName = 'employees/' . date('Y') . '/' . date('m');
        if (isset($_FILES['anh_dai_dien'])) {
            $uploadedFile = $this->fileupload->upload($_FILES['anh_dai_dien'], $folderName);
            if (!$uploadedFile['success']) {
                resBadrequest([
                    'anh_dai_dien' => [
                        'Không thể tải lên file'
                    ]
                ]);
            } else {
                $data['anh_dai_dien'] = $uploadedFile['file_path'];
                //Xóa ảnh đại diện cũ
                $deleteFile = $this->fileupload->delete($nhanvien['anh_dai_dien']);
            }
        }

        //Thêm vào ngày phép cộng dồn
        // $quydinhnghiphep = $this->Hrm_quy_dinh_nghi_phep->where('loai_cong_viec', 'Binh_thuong')->first();
        // if ($quydinhnghiphep && commonRequest('ngay_vao_lam')) {
        //     $songayphep = 
        // }

        $this->db->trans_start();

        $this->Hrm_nhan_vien_model->where('id_nhan_vien', $id)->update($data);

        //create log
        $this->createLog('update', 'Cập nhật nhân viên', $nhanvien, $this->Hrm_nhan_vien_model->find($id), 'hrm_nhan_vien');
        $this->db->trans_commit();
        resSuccess($nhanvien, 'Cập nhật thành công', REST_INSTANCE_Controller::HTTP_OK);
    }

    public function delete_post()
    {
        $ids = commonRequest('ids');
        $this->db->trans_start();
        $nhanvien = $this->Hrm_nhan_vien_model->whereIn('id_nhan_vien', $ids)->where('deleted_at IS NULL')->get();
        if (count($ids) != count($nhanvien)) {
            resError('Dữ liệu không hợp lệ');
        }

        $this->Hrm_nhan_vien_model->whereIn('id_nhan_vien', $ids)->update([
            'deleted_at' => date('Y-m-d H:i:s')
        ]);

        $this->createLog('delete', 'Xóa nhân viên', $nhanvien,  null, 'hrm_nhan_vien');
        $this->db->trans_commit();
        resSuccess(null, 'Xóa thành công');
    }
}
