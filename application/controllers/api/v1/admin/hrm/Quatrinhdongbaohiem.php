<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Hrm_ty_le_bao_hiem_model $Hrm_ty_le_bao_hiem_model
 * @property DB_query_builder $db
 * @property Hrm_bao_hiem_dong_model $Hrm_bao_hiem_dong_model
 * @property Validate $validate
 */

class Quatrinhdongbaohiem extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_nhan_vien_model', 'Hrm_ty_le_bao_hiem_model', 'Hrm_bao_hiem_dong_model']);
        $this->load->library(['Validator', 'Fileupload', 'Validate']);
    }

    public function index_get() {}

    public function create_post()
    {
        $nhanvienId = commonRequest('id_nhan_vien');
        $nhanvien = $this->Hrm_nhan_vien_model->find($nhanvienId);
        if (!$nhanvien) {
            resError('Nhân viên không tồn tại', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }

        //Tỷ lệ đóng bảo hiểm được áp dụng
        $tyledongbaohiem = $this->Hrm_ty_le_bao_hiem_model->orderBy('ngay_ap_dung', 'desc')->first();
        if (!$tyledongbaohiem) {
            resError('Tỷ lệ đóng bảo hiểm chưa được cập nhật');
        }

        // $tile_bhxh_nv = commonRequest('tile_bhxh_nv') ? commonRequest('tile_bhxh_nv') : 0;
        // $tile_bhyt_nv = commonRequest('tile_bhyt_nv') ? commonRequest('tile_bhyt_nv') : 0;
        // $tile_bhtn_nv = commonRequest('tile_bhtn_nv') ? commonRequest('tile_bhtn_nv') : 0;

        $tile_bhxh_nv = $tyledongbaohiem['bhxh_nv'];
        $tile_bhyt_nv = $tyledongbaohiem['bhyt_nv'];
        $tile_bhtn_nv = $tyledongbaohiem['bhtn_nv'];

        $mucluongdongbaohiem = commonRequest('muc_luong_dong') ? commonRequest('muc_luong_dong') : 0;
        // $tyledong = commonRequest('tile_tong_nv_dong') ? commonRequest('tile_tong_nv_dong') : 0; //Tỷ lệ nhân viên đóng bảo hiểm
        $tyledong = $tile_bhxh_nv + $tile_bhyt_nv + $tile_bhtn_nv; //Tỷ lệ nhân viên đóng bảo hiểm
        $tongnhanviendong = $mucluongdongbaohiem * ($tyledong / 100);
        $bhxh_nv = $mucluongdongbaohiem * ($tile_bhxh_nv / 100);
        $bhyt_nv = $mucluongdongbaohiem * ($tile_bhyt_nv / 100);
        $bhtn_nv = $mucluongdongbaohiem * ($tile_bhtn_nv / 100);


        $tyletongdoanhnghiepdong = $tyledongbaohiem['bhxh_dn'] + $tyledongbaohiem['bhyt_dn'] + $tyledongbaohiem['bhtn_dn'];
        $bhxh_dn = $mucluongdongbaohiem * ($tyledongbaohiem['bhxh_dn'] / 100);
        $bhyt_dn = $mucluongdongbaohiem * ($tyledongbaohiem['bhyt_dn'] / 100);
        $bhtn_dn = $mucluongdongbaohiem * ($tyledongbaohiem['bhtn_dn'] / 100);

        //Tổng doanh nghiệp đóng
        $tong_dn_dong = $mucluongdongbaohiem * ($tyletongdoanhnghiepdong / 100);

        //Tổng đóng
        $tong_dong = $tongnhanviendong + $tong_dn_dong;

        $data = [
            'id_nhan_vien' => $nhanvienId,
            'thang' => commonRequest('thang') ? commonRequest('thang') : null,
            'tile_bhxh_nv' => $tile_bhxh_nv,
            'tile_bhyt_nv' => $tile_bhyt_nv,
            'tile_bhtn_nv' => $tile_bhtn_nv,
            'tile_tong_nv_dong' => $tyledong,
            'tile_tong_dn_dong' => $tyletongdoanhnghiepdong,
            'tong_nv_dong' => $tongnhanviendong,
            'muc_luong_dong' => $mucluongdongbaohiem,
            'bhxh_nv' => $bhxh_nv,
            'bhyt_nv' => $bhyt_nv,
            'bhtn_nv' => $bhtn_nv,
            'bhxh_dn' => $bhxh_dn,
            'bhyt_dn' => $bhyt_dn,
            'bhtn_dn' => $bhtn_dn,
            'tong_dn_dong' => $tong_dn_dong,
            'tong_dong' => $tong_dong,
            'trang_thai' => commonRequest('trang_thai') ? commonRequest('trang_thai') : null,
            'tu_thang' => commonRequest('tu_thang') ? commonRequest('tu_thang') : null,
            'den_thang' => commonRequest('den_thang') ? commonRequest('den_thang') : null,
            'ghi_chu' => commonRequest('ghi_chu'),
            'so_so_bhxh' => commonRequest('so_so_bhxh') ? commonRequest('so_so_bhxh') : null,
            'ma_bhxh' => commonRequest('ma_bhxh') ? commonRequest('ma_bhxh') : null,
            'ma_tinh_cap' =>  commonRequest('ma_tinh_cap') ? commonRequest('ma_tinh_cap') : null,
            'ten_tinh_cap' => commonRequest('ten_tinh_cap') ? commonRequest('ten_tinh_cap') : null,
            'so_the_bhyt' => commonRequest('so_the_bhyt') ? commonRequest('so_the_bhyt') : null,
            'ngay_het_han' =>  commonRequest('ngay_het_han') ? commonRequest('ngay_het_han') : null,
            'noi_dk_kcb' => commonRequest('noi_dk_kcb') ? commonRequest('noi_dk_kcb') : null,
            'ms_noi_kcb' => commonRequest('ms_noi_kcb') ? commonRequest('ms_noi_kcb') : null,
            'id_ty_le_bao_hiem' => $tyledongbaohiem['id_ty_le_bao_hiem']
        ];
        $validator = new Validator();
        $rules = [
            'thang' => 'required',
            'tu_thang' => 'required|date',
            'den_thang' => 'required|date',
            'so_the_bhyt' => 'required',
            'so_so_bhxh' => 'required'
        ];
        $messages = [
            'thang.required' => 'Tháng không được để trống',
            'tu_thang.required' => 'Từ tháng không được để trống',
            'den_thang.required' => 'Đến tháng không được để trống',
            'tu_thang.date' => 'Từ tháng không đúng định dạng',
            'den_thang.date' => 'Đến tháng không đúng định dạng',
            'so_the_bhyt.required' => 'Số thẻ bảo hiểm y tế không được để trống',
            'so_so_bhxh.required' => 'Số sổ bảo hiểm xã hội không được để trống',
        ];
        $validator->setCustomMessages($messages);
        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();

        $quatrinhdongbaohiem = $this->Hrm_bao_hiem_dong_model->create($data);
        // $this->db->trans_rollback();
        $this->db->trans_commit();
        resSuccess($quatrinhdongbaohiem, 'Thêm thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }
}
