<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property DB_query_builder $db
 * @property Hrm_nhan_vien_thoi_viec_model $Hrm_nhan_vien_thoi_viec_model
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Hrm_nhan_vien_cong_viec_model $Hrm_nhan_vien_cong_viec_model
 * @property Hrm_thuc_tuc_thoi_viec_model $Hrm_thu_tuc_thoi_viec_model 
 * @property Fileupload $fileupload
 */
class Thoiviec extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model(['Hrm_nhan_vien_thoi_viec_model', 'Hrm_thu_tuc_thoi_viec_model', 'Hrm_nhan_vien_model', 'Hrm_nhan_vien_cong_viec_model']);
        $this->load->library(['Validator', 'Fileupload']);
    }

    /**
     * Hàm lấy quy tắc validate
     */
    private function getValidationRules()
    {
        $rules = [
            'id_tttv' => 'required|integer',
            'ngay_hoan_thanh' => 'date',
            // 'trang_thai' => 'required',
        ];

        $customMessages = [
            'id_tttv.required' => 'Vui lòng chọn thủ tục',
            'ngay_hoan_thanh.date' => 'Ngày hoàn thành không đúng định dạng',
            'trang_thai.required' => 'Vui lòng nhập trạng thái',
        ];

        return compact('rules', 'customMessages');
    }

    /**
     * Hàm validate dữ liệu
     */
    private function validateData($data)
    {
        $validator = new Validator();
        $validation = $this->getValidationRules();

        $validator->setCustomMessages($validation['customMessages']);

        if (!$validator->validate($data, $validation['rules'])) {
            resBadrequest($validator->errors());
        }
    }
    public function index_post()
    {
        $postData = $this->post();
        $response = $this->Hrm_nhan_vien_thoi_viec_model->getAllNhanvien_thoiviec($postData);
        $thongke = $this->Hrm_nhan_vien_thoi_viec_model->thongke_hoso();
        resSuccess([
            'draw' => isset($postData['draw']) ? intval($postData['draw']) : 1,
            'data' => $response['data'] ?? [],
            'recordsTotal' => $response['recordsTotal'] ?? count($response['data']),
            'recordsFiltered' => $response['recordsFiltered'] ?? count($response['data']),
            'sql' => $response['sql'] ?? '',
            'filters' => $response['filters'] ?? [],
            'thongke' => $thongke ?? [],
        ], 'Successful');
    }
    public function thoi_viec_byIdNhanVien_post()
    {
        $postData = $this->post();
        $response = $this->Hrm_nhan_vien_thoi_viec_model->getThoiviec_byNhanvien($postData);

        resSuccess([
            'draw' => isset($postData['draw']) ? intval($postData['draw']) : 1,
            'data' => $response['data'] ?? [],
            'recordsTotal' => $response['recordsTotal'] ?? count($response['data']),
            'recordsFiltered' => $response['recordsFiltered'] ?? count($response['data']),
            'sql' => $response['sql'] ?? '',
            'filters' => $response['filters'] ?? [],
        ], 'Successful');
    }

    public function index1_get()
    {
        $auth = $this->getUserLogin();
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
        $response = $this->Hrm_nhan_vien_thoi_viec_model->getNhanVienThoiViec($data['start'], $data['length'], $data['searchValue'], $data['order'], $data['columns'], $data['searchKey'],  $data['fromDate'], $data['toDate'], $auth);
        resSuccess($response['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $response['recordsTotal'],
            'recordsFiltered' => $response['recordsFiltered'],
            // 'sql' => $response['sql'],
        ]);

        return;


        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;

        $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
            'order' => commonRequest('order'),
            'columns' => commonRequest('columns')
        ] : [];

        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];

        $data = $this->Hrm_nhan_vien_thoi_viec_model->getNhanVienThoiViec($start, $length, $searchValue, $orderBy, $searchKey);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
            'sql' => $data['sql'],
        ]);
    }

    public function thoi_viec_byIdNhanVien1_get($id_nhan_vien)
    {
        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;

        $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
            'order' => commonRequest('order'),
            'columns' => commonRequest('columns')
        ] : [];

        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];
        $searchKey['id_nhan_vien'] = $id_nhan_vien;

        $data = $this->Hrm_nhan_vien_thoi_viec_model->getThuTuc_byIdNhanVien($start, $length, $searchValue, $orderBy, $searchKey);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
            'sql' => $data['sql']
        ]);
    }

    public function create_post()
    {
        $data = commonRequest('thu_tuc') ? json_decode(json_encode(commonRequest('thu_tuc')), true) : [];

        $id_nhan_vien_arr = array_values(array_unique(array_column($data, 'id_nhan_vien')));
        foreach ($id_nhan_vien_arr as $key => $id_nhan_vien) {
            $nhanvien = $this->Hrm_nhan_vien_model->find($id_nhan_vien);
            if (!$nhanvien) {
                resError('Không tìm thấy thông tin nhân viên.', REST_Controller::HTTP_NOT_FOUND);
            }
        }

        $this->db->trans_begin();
        foreach ($data as $key => $tt) {
            $tttv = $this->Hrm_thu_tuc_thoi_viec_model->find($tt['id_tttv']);
            if (!$tttv) {
                resError('Thủ tục thôi việc không tồn tại, Vui lòng kiểm tra lại', REST_Controller::HTTP_NOT_FOUND);
            }
            $this->validateData($tt);

            if (isset($tt['ngay_hoan_thanh']) && $tt['ngay_hoan_thanh'] === '') {
                $tt['ngay_hoan_thanh'] = null;
            }

            if (!isset($tt['trang_thai']) || $tt['trang_thai'] === '') {
                unset($tt['trang_thai']);
            }

            $this->Hrm_nhan_vien_thoi_viec_model->create($tt);

            $nvcv = $this->Hrm_nhan_vien_cong_viec_model->where('id_nhan_vien', $tt['id_nhan_vien'])->first();
            if (!in_array($nvcv['trang_thai'], [Common::TRANG_THAI_CONG_VIEC['DANG_LAM_THU_TUC_THOI_VIEC']['value'], Common::TRANG_THAI_CONG_VIEC['NGHI_VIEC']['value']])) {
                $this->Hrm_nhan_vien_cong_viec_model->where('id_nhan_vien', $tt['id_nhan_vien'])->update(['trang_thai' => Common::TRANG_THAI_CONG_VIEC['DANG_LAM_THU_TUC_THOI_VIEC']['value']]);
            }
        }
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            resError('Lỗi hệ thống, Vui lòng thử lại.', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }

        //createLog
        $this->createLog('create', 'Tạo mới thủ tục thôi việc', null, $data, 'hrm_nhan_vien_thoi_viec');
        $this->db->trans_commit();


        resSuccess($data, 'Success', REST_Controller::HTTP_OK, true);


        // Logic cũ
        // return dd($data);

        $id_nhan_vien = commonRequest('id_nhan_vien') ? commonRequest('id_nhan_vien') : null;
        $ids = commonRequest('ids') ? commonRequest('ids') : null;
        $ids = json_decode(json_encode($ids), true); // Chuyển object thành array

        // Kiểm tra trạng thái hiện tại của nhân viên
        $nhanVien = $this->Hrm_nhan_vien_cong_viec_model->where('id_nhan_vien', $id_nhan_vien)->first();
        if (!$nhanVien) {
            resError('Không tìm thấy thông tin nhân viên.', REST_Controller::HTTP_NOT_FOUND);
        }

        // Cập nhật trạng thái DANG_LAM_THU_TUC_THOI_VIEC
        $this->Hrm_nhan_vien_cong_viec_model->where('id_nhan_vien', $id_nhan_vien)->update(['trang_thai' => Common::TRANG_THAI_CONG_VIEC['DANG_LAM_THU_TUC_THOI_VIEC']['value']]);


        foreach ($ids as $key => $id) {
            $tttv = $this->Hrm_thu_tuc_thoi_viec_model->find($id['id']);
            if (!$tttv) {
                resError('Thủ tục thôi việc không tồn tại, Vui lòng kiểm tra lại', REST_Controller::HTTP_NOT_FOUND);
            }
            $datas[] = [
                'id_nhan_vien' => $id_nhan_vien,
                'id_tttv' => $id['id'],
                'ngay_hoan_thanh' => $id['ngay_hoan_thanh'] ?? '0000-00-00',
                'trang_thai' => $id['trang_thai'],
                'created_at' => date('Y-m-d H:i:s'),
                'created_user_id' => $this->getUserLogin()['ql_nguoi_dung_id'],
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_user_id' => $this->getUserLogin()['ql_nguoi_dung_id'],
            ];
        }

        // Validate từng phần tử trong mảng dữ liệu
        foreach ($datas as $data) {
            $this->validateData($data);
        }

        $this->db->trans_start();
        $thoiviec = [];
        foreach ($datas as $key => $data) {
            $thoiviec[$key] = $this->Hrm_nhan_vien_thoi_viec_model->create($data);
            //join với bảng thu_tuc_thoi_viec để lấy tên thủ tục thôi việc
            $tttv = $this->Hrm_thu_tuc_thoi_viec_model->find($data['id_tttv']);
            $thoiviec[$key]['ten_thu_tuc'] = $tttv['ten_thu_tuc'];
            $thoiviec[$key]['nhom_thu_tuc'] = $tttv['nhom_thu_tuc'];
            $thoiviec[$key]['trang_thai_lam_viec'] =  Common::TRANG_THAI_CONG_VIEC['DANG_LAM_THU_TUC_THOI_VIEC']['value'];
        }

        //create log
        $this->createLog('create', 'Tạo mới thủ tục thôi việc', null, $thoiviec, 'hrm_nhan_vien_thoi_viec');
        $this->db->trans_commit();

        resSuccess($thoiviec, 'Thêm thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }

    public function delete_post($id)
    {
        // Xóa thủ tục thôi việc
        $tttv = $this->Hrm_nhan_vien_thoi_viec_model->where('id_nhan_vien_thoi_viec', $id)->get();
        if (!$tttv) {
            resError('Không tìm thấy thủ tục này vui lòng tải lại trang để tiếp tục.', REST_Controller::HTTP_NOT_FOUND);
        }

        // cập nhật lại deleted_at & deleted_user_id bảng hrm_nhan_vien_thoi_viec
        $this->db->trans_start();
        $auth = $this->getUserLogin();
        $delete_thoiviec = $this->Hrm_nhan_vien_thoi_viec_model->where('id_nhan_vien_thoi_viec', $id)->update([
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_user_id' => $auth['ql_nguoi_dung_id'],
        ]);
        //create log
        $this->createLog('delete', 'Xóa thủ tục thôi việc', $tttv, $delete_thoiviec,  'hrm_nhan_vien_thoi_viec');
        $this->db->trans_commit();
        resSuccess(null, 'Xóa thủ tục thành công');
    }

    public function deletes_post()
    {
        $ids = commonRequest('ids') ? commonRequest('ids') : [];
        // dd($ids);
        $giatricu = [];
        foreach ($ids as $key => $id) {
            $thutuc = $this->Hrm_nhan_vien_thoi_viec_model->find($id);
            if (!$thutuc) {
                resError('Có thủ tục không tồn tại, vui lòng tải lại trang trước khi xóa');
            }
            $giatricu[] = $thutuc;
        }

        $this->db->trans_start();
        $auth = $this->getUserLogin();
        $delete_thoiviec = $this->Hrm_nhan_vien_thoi_viec_model->whereIn('id_nhan_vien_thoi_viec', $ids)
            ->update([
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_user_id' => $auth['ql_nguoi_dung_id'],
            ]);
        //create log
        $this->createLog('delete', 'Xóa thủ tục thôi việc', $giatricu, $delete_thoiviec,  'hrm_nhan_vien_thoi_viec');
        $this->db->trans_commit();
        resSuccess(null, 'Xóa thủ tục thành công');
    }

    public function accept_post($id)
    {
        $tttv = $this->Hrm_nhan_vien_thoi_viec_model->where('id_nhan_vien_thoi_viec', $id)->get();
        if (!$tttv) {
            resError('Không tìm thấy thủ tục này vui lòng tải lại trang để tiếp tục.', REST_Controller::HTTP_NOT_FOUND);
        }
        $this->db->trans_start();
        $auth = $this->getUserLogin();
        $updated_thoiviec = $this->Hrm_nhan_vien_thoi_viec_model->where('id_nhan_vien_thoi_viec', $id)->update([
            'trang_thai' => 'Hoan_thanh',
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_user_id' => $auth['ql_nguoi_dung_id'],
        ]);
        //create log
        $this->createLog('update', 'Cập nhật thủ tục thôi việc', $tttv, $updated_thoiviec, 'hrm_nhan_vien_thoi_viec');

        // Kiểm tra nếu tất cả các thủ tục thôi việc của nhân viên đều là "Hoàn thành"
        $id_nhan_vien = $tttv['id_nhan_vien'];
        $allCompleted = $this->db->from('hrm_nhan_vien_thoi_viec')
            ->where('id_nhan_vien', $id_nhan_vien) // Đảm bảo bảng hrm_nhan_vien_thoi_viec có cột id_nhan_vien
            ->where('trang_thai !=', 'Hoan_thanh')
            ->count_all_results() === 0;

        // Nếu tất cả đều hoàn thành, cập nhật trạng thái nhân viên thành "NGHI_VIEC"
        if ($allCompleted) {
            $this->Hrm_nhan_vien_cong_viec_model->where('id_nhan_vien', $id_nhan_vien)->update([
                'trang_thai' => Common::TRANG_THAI_CONG_VIEC['NGHI_VIEC']['value'],
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_user_id' => $auth['ql_nguoi_dung_id'],
            ]);
        }

        $this->db->trans_commit();
        resSuccess(null, 'Cập nhật thành công');
    }

    public function show_get($id)
    {
        // Tìm thông tin thủ tục thôi việc theo ID
        $tttv = $this->Hrm_nhan_vien_thoi_viec_model
            ->where('hrm_nhan_vien_thoi_viec.deleted_at IS NULL')
            ->where('hrm_nhan_vien_thoi_viec.id_nhan_vien_thoi_viec', $id)
            ->join('hrm_thu_tuc_thoi_viec', 'hrm_thu_tuc_thoi_viec.id_tttv = hrm_nhan_vien_thoi_viec.id_tttv', 'left')
            ->select('hrm_nhan_vien_thoi_viec.*, hrm_thu_tuc_thoi_viec.ten_thu_tuc, hrm_thu_tuc_thoi_viec.nhom_thu_tuc')
            ->find($id);

        !$tttv ?
            resError('Không tìm thấy thủ tục thôi việc này.', REST_Controller::HTTP_NOT_FOUND) :
            resSuccess($tttv, 'Lấy thông tin thủ tục thôi việc thành công');
    }

    public function update_post($id)
    {
        // Tìm thông tin thủ tục thôi việc theo ID
        $tttv = $this->Hrm_nhan_vien_thoi_viec_model->where('deleted_at IS NULL')->find($id);

        // Kiểm tra nếu không tìm thấy dữ liệu
        if (!$tttv) {
            resError('Không tìm thấy thủ tục thôi việc này.', REST_Controller::HTTP_NOT_FOUND);
        }

        // Lấy dữ liệu từ request
        $data = [
            'id_tttv' => commonRequest('id_tttv') ?? null,
            'ngay_hoan_thanh' => commonRequest('ngay_hoan_thanh') ?? null,
            'trang_thai' => commonRequest('trang_thai') == 'Hoan_thanh' ? 'Hoan_thanh' : 'Chua_hoan_thanh',
        ];

        $auth = $this->getUserLogin();
        // Validate dữ liệu
        $this->validateData($data);

        // Bắt đầu transaction
        $this->db->trans_start();

        // Cập nhật thông tin thủ tục thôi việc
        $this->Hrm_nhan_vien_thoi_viec_model->where('deleted_at IS NULL')->where('id_nhan_vien_thoi_viec', $id)->update($data);

        // Lấy dữ liệu sau khi cập nhật
        $updated_thoiviec = $this->Hrm_nhan_vien_thoi_viec_model->where('deleted_at IS NULL')->find($id);
        $updated_thoiviec['ten_thu_tuc'] = $this->Hrm_thu_tuc_thoi_viec_model->find($updated_thoiviec['id_tttv'])['ten_thu_tuc'];
        $updated_thoiviec['nhom_thu_tuc'] = $this->Hrm_thu_tuc_thoi_viec_model->find($updated_thoiviec['id_tttv'])['nhom_thu_tuc'];

        // Kiểm tra nếu tất cả các thủ tục thôi việc của nhân viên đều là "Hoàn thành"
        $id_nhan_vien = $tttv['id_nhan_vien'];
        $allCompleted = $this->db->from('hrm_nhan_vien_thoi_viec')
            ->where('id_nhan_vien', $id_nhan_vien) // Đảm bảo bảng hrm_nhan_vien_thoi_viec có cột id_nhan_vien
            ->where('trang_thai !=', 'Hoan_thanh')
            ->where('deleted_at IS NULL')
            ->count_all_results() === 0;

        // Nếu tất cả đều hoàn thành, cập nhật trạng thái nhân viên thành "NGHI_VIEC"
        if ($allCompleted) {
            $this->Hrm_nhan_vien_cong_viec_model->where('id_nhan_vien', $id_nhan_vien)->update([
                'trang_thai' => Common::TRANG_THAI_CONG_VIEC['NGHI_VIEC']['value'],
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_user_id' => $auth['ql_nguoi_dung_id'],
            ]);
            $updated_thoiviec['trang_thai'] =  Common::TRANG_THAI_CONG_VIEC['NGHI_VIEC']['value'];
        }

        // Ghi log cập nhật
        $this->createLog('update', 'Cập nhật thủ tục thôi việc', $tttv, $updated_thoiviec, 'hrm_nhan_vien_thoi_viec');

        // Kết thúc transaction
        $this->db->trans_commit();

        // Trả về kết quả thành công
        resSuccess($updated_thoiviec, 'Cập nhật thủ tục thôi việc thành công');
    }

    public function addNhanvienthoiviec_post()
    {
        $ids = commonRequest('ids') ? commonRequest('ids') : null;

        if (!$ids) resBadrequest(null, 'Không thể thực hiện khi danh sách nhân viên rỗng');

        $nhanvienArray = $this->Hrm_nhan_vien_model->whereIn('id_nhan_vien', $ids)->get();

        if (empty($nhanvienArray)) {
            resBadrequest(null, 'Không tìm thấy nhân viên');
        }
        $this->Hrm_nhan_vien_cong_viec_model->whereIn('id_nhan_vien', $ids)->update(['trang_thai' => Common::TRANG_THAI_CONG_VIEC['DANG_LAM_THU_TUC_THOI_VIEC']['value']]);

        resSuccess(null, 'Đã chuyển trạng thái nhân viên sang "Đang làm thủ tục thôi việc"');
    }

    public function updates_post()
    {
        $auth = $this->getUserLogin();
        $postData = $this->post();

        if (isset($postData['type']) && $postData['type'] != '') {
            switch ($postData['type']) {
                // Hoàn thành tất cả thủ tục
                case 'successAll':
                    $ids_nhan_vien = is_array($postData['ids_nhan_vien']) ? $postData['ids_nhan_vien'] : [];

                    foreach ($ids_nhan_vien as $key => $id_nhan_vien) {
                        $this->Hrm_nhan_vien_thoi_viec_model->where('id_nhan_vien', $id_nhan_vien)->update([
                            'trang_thai' => 'Hoan_thanh',
                            'updated_at' => date('Y-m-d H:i:s'),
                            'updated_user_id' => $auth['ql_nguoi_dung_id'],
                        ]);
                        $exits = $this->Hrm_nhan_vien_thoi_viec_model->where('id_nhan_vien', $id_nhan_vien)->where('trang_thai', 'Chua_hoan_thanh')->where('deleted_at IS NULL')->get();
                        if (count($exits) == 0) {
                            $this->Hrm_nhan_vien_cong_viec_model->where('id_nhan_vien', $id_nhan_vien)->update([
                                'trang_thai' => Common::TRANG_THAI_CONG_VIEC['NGHI_VIEC']['value'],
                                'updated_at' => date('Y-m-d H:i:s'),
                                'updated_user_id' => $auth['ql_nguoi_dung_id'],
                            ]);
                        }
                    }
                    resSuccess(null, 'Đã cập nhật trạng thái cho ' . count($ids_nhan_vien) . ' nhân viên');
                    break;

                // Hủy tất cả thủ tục
                case 'cancelAll':
                    $ids_nhan_vien = is_array($postData['ids_nhan_vien']) ? $postData['ids_nhan_vien'] : [];

                    foreach ($ids_nhan_vien as $key => $id_nhan_vien) {
                        $this->Hrm_nhan_vien_thoi_viec_model->where('id_nhan_vien', $id_nhan_vien)->update([
                            'deleted_at' => date('Y-m-d H:i:s'),
                            'deleted_user_id' => $auth['ql_nguoi_dung_id'],
                        ]);
                        $exits = $this->Hrm_nhan_vien_thoi_viec_model->where('id_nhan_vien', $id_nhan_vien)->where('trang_thai', 'Chua_hoan_thanh')->where('deleted_at IS NULL')->get();
                        if (count($exits) == 0) {
                            $this->Hrm_nhan_vien_cong_viec_model->where('id_nhan_vien', $id_nhan_vien)->update([
                                'trang_thai' => Common::TRANG_THAI_CONG_VIEC['DANG_LAM_VIEC']['value'],
                                'updated_at' => date('Y-m-d H:i:s'),
                                'updated_user_id' => $auth['ql_nguoi_dung_id'],
                            ]);
                        }
                    }
                    resSuccess(null, 'Đã cập nhật trạng thái cho ' . count($ids_nhan_vien) . ' nhân viên');
                    break;
                case 'thu_tuc':
                    $ids = is_array($postData['ids']) ? $postData['ids'] : [];
                    $trang_thai = $postData['trang_thai'] ?? null;
                    $id_nhan_vien = $postData['id_nhan_vien'] ?? null;

                    if (!$id_nhan_vien) resBadrequest(null, 'Không có nhân viên');

                    if (!$trang_thai || !in_array($trang_thai, ['Hoan_thanh', 'Chua_hoan_thanh'])) resBadrequest(null, 'Trạng thái không hợp lệ');
                    foreach ($ids as $key => $id) {
                        $this->Hrm_nhan_vien_thoi_viec_model->where('id_nhan_vien_thoi_viec', $id)->update([
                            'trang_thai' => $postData['trang_thai'],
                            'updated_at' => date('Y-m-d H:i:s'),
                            'updated_user_id' => $auth['ql_nguoi_dung_id'],
                        ]);
                    }

                    $exits = $this->Hrm_nhan_vien_thoi_viec_model->where('id_nhan_vien', $id_nhan_vien)->where('trang_thai', 'Chua_hoan_thanh')->where('deleted_at IS NULL')->get();
                    if (count($exits) == 0) {
                        $this->Hrm_nhan_vien_cong_viec_model->where('id_nhan_vien', $id_nhan_vien)->update([
                            'trang_thai' => Common::TRANG_THAI_CONG_VIEC['NGHI_VIEC']['value'],
                            'updated_at' => date('Y-m-d H:i:s'),
                            'updated_user_id' => $auth['ql_nguoi_dung_id'],
                        ]);
                    }

                    resSuccess(null, 'Đã cập nhật trạng thái cho ' . count($ids) . ' thủ tục');

                    break;
            }
        }
    }
}
