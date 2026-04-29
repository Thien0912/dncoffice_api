<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Hrm_yeu_cau_cap_nhat_model $Hrm_yeu_cau_cap_nhat_model
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 * @property Fileupload $fileupload
 * @property Validate $validate
 */



class Nhanvientucapnhat extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model([
            'Hrm_nhan_vien_model',
            'Hrm_yeu_cau_cap_nhat_model',
            'Ql_nguoi_dung_model',
        ]);
        $this->load->library(['Validator', 'Fileupload', 'Validate']);
    }

    public function index_get()
    {
        $data = [
            'start' => commonRequest('start') ?? 0,
            'length' => commonRequest('length') ?? 10,
            'searchValue' => commonRequest('searchValue') ?? null,
            'order' => commonRequest('order') ?? [],
            'columns' => commonRequest('columns') ?? [],
            // 'columnControl' => commonRequest('columnControl') ?? [],
            'searchKey' => commonRequest('searchKey') ? commonRequest('searchKey') : [],
            'fromDate' => commonRequest('fromDate') ? commonRequest('fromDate') : null,
            'toDate' => commonRequest('toDate') ? commonRequest('toDate') : null
        ];
        $auth = $this->getUserLogin();

        $data = $this->Hrm_yeu_cau_cap_nhat_model->getAll($data['start'], $data['length'], $data['searchValue'], $data['order'], $data['columns'], $data['searchKey'], $data['fromDate'], $data['toDate'], $auth);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
            // 'sql' => $data['sql']
        ]);
    }

    public function duyet_post()
    {
        $id_yeu_cau_cap_nhat = commonRequest('id_yeu_cau_cap_nhat') ? commonRequest('id_yeu_cau_cap_nhat') : NULL;
        $trang_thai = commonRequest('trang_thai') ? commonRequest('trang_thai') : 0;
        $auth = $this->getUserLogin();

        $data = $this->Hrm_yeu_cau_cap_nhat_model->find($id_yeu_cau_cap_nhat);

        $du_lieu = json_decode($data['du_lieu'], true);
        if (count($du_lieu) > 0) {
            $nhanvien_response = $this->Hrm_nhan_vien_model->where('id_nhan_vien', $data['id_nhan_vien'])->update($du_lieu);

            if ($nhanvien_response) {

                $response = $this->Hrm_yeu_cau_cap_nhat_model->where('id_yeu_cau_cap_nhat', $id_yeu_cau_cap_nhat)->update([
                    'trang_thai' => $trang_thai,
                    'nguoi_duyet' => $auth['ql_nguoi_dung_id']
                ]);

                if ($response) {
                    resSuccess(null, 'Success', REST_Controller::HTTP_OK, true);
                } else {
                    resError("Đã có lỗi xảy ra khi cập nhật trạng thái yêu cầu.");
                }
            } else {
                resError("Đã có lỗi xảy ra khi cập nhật thông tin nhân viên.");
            }
        } else {
            resError("Không tìm thấy dữ liệu cần cập nhật.");
        }
    }

    public function duyet_nhieu_post()
    {
        $ids_yeu_cau_cap_nhat = commonRequest('ids_yeu_cau_cap_nhat') ? commonRequest('ids_yeu_cau_cap_nhat') : NULL;
        $trang_thai = commonRequest('trang_thai') ? commonRequest('trang_thai') : 0;
        $auth = $this->getUserLogin();

        foreach ($ids_yeu_cau_cap_nhat as $id_yeu_cau_cap_nhat) {
            $data = $this->Hrm_yeu_cau_cap_nhat_model->find($id_yeu_cau_cap_nhat);

            $du_lieu = json_decode($data['du_lieu'], true);
            if (count($du_lieu) > 0) {
                $response = $this->Hrm_yeu_cau_cap_nhat_model->where('id_yeu_cau_cap_nhat', $id_yeu_cau_cap_nhat)->update([
                    'trang_thai' => $trang_thai,
                    'nguoi_duyet' => $auth['ql_nguoi_dung_id'],
                    'ngay_cap_nhat' => date('Y-m-d H:i:s')
                ]);
                if (!$response) {
                    resError("Đã có lỗi xảy ra khi cập nhật trạng thái yêu cầu.");
                }

                $textStatus = $trang_thai == 1 ? 'Duyệt' : 'Từ chối';
                $nhanvien = $this->Hrm_nhan_vien_model->find($data['id_nhan_vien']);
                $this->createLog('create', $textStatus . ' cập nhật thông tin nhân viên ' . $nhanvien['ho_va_ten'], [], $data['du_lieu'], 'hrm_yeu_cau_cap_nhat');

                if ($trang_thai == 1) {
                    $nhanvien_response = $this->Hrm_nhan_vien_model->where('id_nhan_vien', $data['id_nhan_vien'])->update($du_lieu);
                    if (!$nhanvien_response) {
                        resError("Đã có lỗi xảy ra khi cập nhật thông tin nhân viên.");
                    }
                }
            } else {
                resError("Không tìm thấy dữ liệu cần cập nhật.");
            }
        }

        resSuccess(null, 'Success', REST_Controller::HTTP_OK, true);
    }

    public function tuchoi_post()
    {
        $id_yeu_cau_cap_nhat = commonRequest('id_yeu_cau_cap_nhat') ? commonRequest('id_yeu_cau_cap_nhat') : NULL;
        $trang_thai = commonRequest('trang_thai') ? commonRequest('trang_thai') : 0;
        $auth = $this->getUserLogin();

        if ($id_yeu_cau_cap_nhat && $trang_thai) {
            $response = $this->Hrm_yeu_cau_cap_nhat_model->where('id_yeu_cau_cap_nhat', $id_yeu_cau_cap_nhat)->update([
                'trang_thai' => $trang_thai,
                'nguoi_duyet' => $auth['ql_nguoi_dung_id']
            ]);

            if ($response) {
                resSuccess(null, 'Success', REST_Controller::HTTP_OK, true);
            } else {
                resError("Đã có lỗi xảy ra khi cập nhật trạng thái yêu cầu.");
            }
        } else {
            resError("Không tìm thấy dữ liệu cần cập nhật.");
        }
    }

    public function countRequest_get()
    {
        $rq = $this->Hrm_yeu_cau_cap_nhat_model->countRequest();
        if (!$rq) {
            resError('Không có yêu cầu nào cần cập nhật');
        }
        resSuccess($rq, 'Lấy số lượng yêu cầu thành công');
    }
}
