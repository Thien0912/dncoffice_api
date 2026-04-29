<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property DB_query_builder $db
 * @property Hrm_nhan_vien_thoi_viec_model $Hrm_nhan_vien_thoi_viec_model
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Hrm_nhan_vien_cong_viec_model $Hrm_nhan_vien_cong_viec_model
 * @property Hrm_thu_tuc_thoi_viec_model $Hrm_thu_tuc_thoi_viec_model 
 * @property Fileupload $fileupload
 */
class Thoiviec extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->library(['Validator', 'Fileupload']);
        $this->load->helper(['hrm']);
        $this->load->model([
            'Hrm_nhan_vien_thoi_viec_model',
            'Hrm_nhan_vien_model',
            'Hrm_nhan_vien_cong_viec_model',
            'Hrm_thu_tuc_thoi_viec_model'
        ]);
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

        // Xử lý ordering từ frontend (nếu gửi dạng {column: 'name', dir: 'asc'})
        if (isset($postData['order']) && is_array($postData['order'])) {
            $columns = $postData['columns'] ?? [];
            $newOrder = [];

            foreach ($postData['order'] as $index => $item) {
                if (isset($item['column']) && !is_numeric($item['column'])) {
                    // Frontend gửi tên cột thay vì index
                    $colName = $item['column'];

                    // Tìm xem cột đã có trong columns chưa
                    $colIndex = -1;
                    foreach ($columns as $idx => $col) {
                        if (isset($col['data']) && $col['data'] === $colName) {
                            $colIndex = $idx;
                            break;
                        }
                    }

                    // Nếu chưa có, thêm vào
                    if ($colIndex === -1) {
                        $columns[] = [
                            'data' => $colName,
                            'name' => '',
                            'searchable' => 'true',
                            'orderable' => 'true',
                            'search' => [
                                'value' => '',
                                'regex' => 'false'
                            ]
                        ];
                        $colIndex = count($columns) - 1;
                    }

                    $newOrder[] = [
                        'column' => $colIndex,
                        'dir' => $item['dir']
                    ];
                } else {
                    $newOrder[] = $item;
                }
            }

            $postData['columns'] = $columns;
            $postData['order'] = $newOrder;
        }

        // Filter được xử lý trong model getAllNhanvien_thoiviec
        $response = $this->Hrm_nhan_vien_thoi_viec_model->getAllNhanvien_thoiviec($postData);
        $thongke = $this->Hrm_nhan_vien_thoi_viec_model->thongke_hoso();
        if (!empty($response['data'])) {
            // append_hhhv_to_list($response['data'], 'ho_va_ten'); // Không dùng cho danh sách thôi việc
        }
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
        if (!empty($response['data'])) {
            // append_hhhv_to_list($response['data'], 'ho_va_ten');
        }
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

            $newNVTV = $this->Hrm_nhan_vien_thoi_viec_model->create($tt);
            $data[$key]['id_nhan_vien_thoi_viec'] = $newNVTV['id_nhan_vien_thoi_viec'];

            $nvcv = $this->Hrm_nhan_vien_cong_viec_model->where('id_nhan_vien', $tt['id_nhan_vien'])->first();
            if (!in_array($nvcv['trang_thai'], [Common::TRANG_THAI_CONG_VIEC['DANG_LAM_THU_TUC_THOI_VIEC']['value'], Common::TRANG_THAI_CONG_VIEC['NGHI_VIEC']['value']])) {
                $this->Hrm_nhan_vien_cong_viec_model->where('id_nhan_vien', $tt['id_nhan_vien'])->update(['trang_thai' => Common::TRANG_THAI_CONG_VIEC['DANG_LAM_THU_TUC_THOI_VIEC']['value']]);
            }
        }
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            resError('Lỗi hệ thống, Vui lòng thử lại.', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }

        $changesArray = [];
        foreach ($data as $key => $tt) {
            $tttv = $this->Hrm_thu_tuc_thoi_viec_model->find($tt['id_tttv']);
            if ($tttv) {
                $changesArray[$tt['id_nhan_vien']][] = [
                    'field' => 'thutucthoiviec',
                    'field_name' => 'Thủ tục thôi việc',
                    'old_value' => null,
                    'new_value' => [
                        'value' => 'Thêm mới',
                        'label' => $tttv['ten_thu_tuc']
                    ]
                ];
            }
        }
        foreach ($changesArray as $id_nv => $changes) {
            $this->logEmployeeHistory($id_nv, 'Thêm thủ tục thôi việc', $changes);
        }

        //createLog
        $this->createLog('create', 'Tạo mới thủ tục thôi việc', null, $data, 'hrm_nhan_vien_thoi_viec');
        $this->db->trans_commit();

        $nhan_vien_trang_thai_thoi_viec = [];
        foreach ($id_nhan_vien_arr as $key => $id_nhan_vien) {
            $arr_thu_tuc = $this->Hrm_nhan_vien_thoi_viec_model->where('id_nhan_vien', $id_nhan_vien)->get();

            $isNghiViec = true;
            foreach ($arr_thu_tuc as $tt) {
                if ($tt['trang_thai'] != 'Hoan_thanh') {
                    $isNghiViec = false;
                    break;
                }
            }

            $this->Hrm_nhan_vien_cong_viec_model->where('id_nhan_vien', $id_nhan_vien)->update([
                'trang_thai' => $isNghiViec ? Common::TRANG_THAI_CONG_VIEC['NGHI_VIEC']['value'] : Common::TRANG_THAI_CONG_VIEC['DANG_LAM_THU_TUC_THOI_VIEC']['value'],
            ]);
            $nhan_vien_trang_thai_thoi_viec[$key] = [
                'id_nhan_vien' => $id_nhan_vien,
                'isNghiViec' => $isNghiViec,
            ];
        }


        resSuccess($data, 'Success', REST_Controller::HTTP_OK, true, [
            'nhan_vien_trang_thai_thoi_viec' => $nhan_vien_trang_thai_thoi_viec,
        ]);


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
        resError('Chức năng xóa đã bị vô hiệu hóa trên trang này.', REST_Controller::HTTP_BAD_REQUEST);
    }

    public function deletes_post()
    {
        resError('Chức năng xóa đã bị vô hiệu hóa trên trang này.', REST_Controller::HTTP_BAD_REQUEST);
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
        $thu_tuc_danh_muc = $this->Hrm_thu_tuc_thoi_viec_model->find($tttv['id_tttv']);
        $changes = [
            [
                'field' => 'thutucthoiviec',
                'field_name' => 'Thủ tục thôi việc',
                'old_value' => [
                    'value' => 'Cập nhật',
                    'label' => $thu_tuc_danh_muc['ten_thu_tuc'] . ' - Chưa hoàn thành'
                ],
                'new_value' => [
                    'value' => 'Cập nhật',
                    'label' => $thu_tuc_danh_muc['ten_thu_tuc'] . ' - Hoàn thành'
                ]
            ]
        ];
        $this->logEmployeeHistory($tttv['id_nhan_vien'], 'Cập nhật thủ tục thôi việc', $changes);

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
        $postData = $this->post();
        $data = [];

        if (array_key_exists('id_tttv', $postData)) {
            $data['id_tttv'] = $postData['id_tttv'];
        }
        if (array_key_exists('ngay_hoan_thanh', $postData)) {
            $data['ngay_hoan_thanh'] = $postData['ngay_hoan_thanh'];
        }
        if (array_key_exists('trang_thai', $postData)) {
            $data['trang_thai'] = $postData['trang_thai'] == 'Hoan_thanh' ? 'Hoan_thanh' : 'Chua_hoan_thanh';
        }

        $auth = $this->getUserLogin();
        // Validate dữ liệu
        // $this->validateData($data);
        // Validate chỉ những trường có trong data
        $validation = $this->getValidationRules();
        $rules = array_intersect_key($validation['rules'], $data);

        $validator = new Validator();
        $validator->setCustomMessages($validation['customMessages']);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

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

        $thu_tuc_danh_muc = $this->Hrm_thu_tuc_thoi_viec_model->find($tttv['id_tttv']);
        $trang_thai_cu = $tttv['trang_thai'] == 'Hoan_thanh' ? 'Hoàn thành' : 'Chưa hoàn thành';
        $trang_thai_moi = $updated_thoiviec['trang_thai'] == 'Hoan_thanh' ? 'Hoàn thành' : 'Chưa hoàn thành';
        
        $changes = [
            [
                'field' => 'thutucthoiviec',
                'field_name' => 'Thủ tục thôi việc',
                'old_value' => [
                    'value' => 'Cập nhật',
                    'label' => $thu_tuc_danh_muc['ten_thu_tuc'] . ' - ' . $trang_thai_cu
                ],
                'new_value' => [
                    'value' => 'Cập nhật',
                    'label' => $thu_tuc_danh_muc['ten_thu_tuc'] . ' - ' . $trang_thai_moi
                ]
            ]
        ];
        $this->logEmployeeHistory($tttv['id_nhan_vien'], 'Cập nhật thủ tục thôi việc', $changes);

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

                case 'returnToWork':
                    $ids_nhan_vien = is_array($postData['ids_nhan_vien']) ? $postData['ids_nhan_vien'] : [];

                    $this->db->trans_start();
                    foreach ($ids_nhan_vien as $id_nhan_vien) {
                        $this->Hrm_nhan_vien_cong_viec_model->where('id_nhan_vien', $id_nhan_vien)->update([
                            'trang_thai' => Common::TRANG_THAI_CONG_VIEC['DANG_LAM_VIEC']['value'],
                            'updated_at' => date('Y-m-d H:i:s'),
                            'updated_user_id' => $auth['ql_nguoi_dung_id'],
                        ]);
                        
                        $changes = [
                            [
                                'field' => 'trang_thai',
                                'field_name' => 'Trạng thái',
                                'old_value' => ['value' => 'Thôi việc/Nghỉ việc', 'label' => 'Đang làm thủ tục thôi việc / Nghỉ việc'],
                                'new_value' => ['value' => 'Đang làm việc', 'label' => 'Đang làm việc']
                            ]
                        ];
                        $this->logEmployeeHistory($id_nhan_vien, 'Quay lại làm việc', $changes);
                    }
                    $this->db->trans_commit();
                    
                    resSuccess(null, 'Đã cập nhật nhân viên quay lại làm việc');
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

    public function update_employee_info_post()
    {
        $id_nhan_vien = commonRequest('id_nhan_vien');
        if (!$id_nhan_vien) {
            resBadrequest(null, 'Vui lòng chọn nhân viên');
        }

        $data = [];
        $postData = $this->post();

        if (array_key_exists('trang_thai', $postData)) {
            $data['trang_thai'] = $postData['trang_thai'];
        }
        if (array_key_exists('ly_do_thoi_viec', $postData)) {
            $data['ly_do_thoi_viec'] = $postData['ly_do_thoi_viec'];
        }
        if (array_key_exists('ngay_lam_chinh_thuc_ket_thuc', $postData)) {
            $data['ngay_lam_chinh_thuc_ket_thuc'] = $postData['ngay_lam_chinh_thuc_ket_thuc'] ?: null;
        }

        if (empty($data)) {
            resBadrequest(null, 'Không có dữ liệu cập nhật');
        }

        $auth = $this->getUserLogin();
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['updated_user_id'] = $auth['ql_nguoi_dung_id'];

        $this->db->trans_start();

        // Lấy thông tin cũ để ghi log
        $oldData = $this->Hrm_nhan_vien_cong_viec_model->where('id_nhan_vien', $id_nhan_vien)->first();

        $this->Hrm_nhan_vien_cong_viec_model->where('id_nhan_vien', $id_nhan_vien)->update($data);

        // Lấy thông tin mới
        $newData = $this->Hrm_nhan_vien_cong_viec_model->where('id_nhan_vien', $id_nhan_vien)->first();

        // Cố định lưu log vào controller 'nhanvien' để hiện bên Lịch sử Nhân sự
        $this->load->model('Ql_nhat_ky_model');
        $this->Ql_nhat_ky_model->insert([
            'ql_nguoi_dung_id' => $auth['ql_nguoi_dung_id'],
            'ql_nhat_ky_hanh_dong' => 'update',
            'ql_nhat_ky_noi_dung' => 'Cập nhật thông tin thôi việc nhân viên',
            'ql_nhat_ky_gia_tri_cu' => json_encode($oldData),
            'ql_nhat_ky_gia_tri_moi' => json_encode($newData),
            'ql_nhat_ky_bang_du_lieu' => 'hrm_nhan_vien_cong_viec',
            'ql_nhat_ky_controller' => 'nhanvien' // Quan trọng: Ghi đè thành nhanvien
        ]);

        $changes = [];
        if (isset($oldData['trang_thai']) && isset($newData['trang_thai'])) {
            if ($oldData['trang_thai'] != $newData['trang_thai']) {
                $statusMap = [
                    'DANG_LAM_VIEC' => 'Đang làm việc',
                    'DANG_THU_VIEC' => 'Đang thử việc',
                    'DANG_HOC_VIEC' => 'Đang học việc',
                    'TAM_NGHI' => 'Tạm nghỉ',
                    'DANG_LAM_THU_TUC_THOI_VIEC' => 'Đang làm thủ tục thôi việc',
                    'NGHI_VIEC' => 'Nghỉ việc',
                ];
                $changes[] = [
                    'field' => 'trang_thai',
                    'field_name' => 'Trạng thái',
                    'old_value' => ['value' => $oldData['trang_thai'], 'label' => $statusMap[$oldData['trang_thai']] ?? $oldData['trang_thai']],
                    'new_value' => ['value' => $newData['trang_thai'], 'label' => $statusMap[$newData['trang_thai']] ?? $newData['trang_thai']]
                ];
            }
        }
        if (isset($oldData['ly_do_thoi_viec']) || isset($newData['ly_do_thoi_viec'])) {
            if ($oldData['ly_do_thoi_viec'] != $newData['ly_do_thoi_viec']) {
                $changes[] = [
                    'field' => 'ly_do_thoi_viec',
                    'field_name' => 'Lý do thôi việc',
                    'old_value' => ['value' => $oldData['ly_do_thoi_viec'], 'label' => $oldData['ly_do_thoi_viec']],
                    'new_value' => ['value' => $newData['ly_do_thoi_viec'], 'label' => $newData['ly_do_thoi_viec']]
                ];
            }
        }
        if (isset($oldData['ngay_lam_chinh_thuc_ket_thuc']) || isset($newData['ngay_lam_chinh_thuc_ket_thuc'])) {
            if ($oldData['ngay_lam_chinh_thuc_ket_thuc'] != $newData['ngay_lam_chinh_thuc_ket_thuc']) {
                $changes[] = [
                    'field' => 'ngay_lam_chinh_thuc_ket_thuc',
                    'field_name' => 'Ngày kết thúc làm việc',
                    'old_value' => ['value' => $oldData['ngay_lam_chinh_thuc_ket_thuc'], 'label' => $oldData['ngay_lam_chinh_thuc_ket_thuc']],
                    'new_value' => ['value' => $newData['ngay_lam_chinh_thuc_ket_thuc'], 'label' => $newData['ngay_lam_chinh_thuc_ket_thuc']]
                ];
            }
        }
        if (!empty($changes)) {
            $this->logEmployeeHistory($id_nhan_vien, 'Cập nhật thông tin thôi việc', $changes);
        }

        $this->db->trans_commit();

        $trangThai = '';
        if ($oldData['trang_thai'] == Common::TRANG_THAI_CONG_VIEC['DANG_LAM_VIEC']['value']) {
            $trangThai = Common::TRANG_THAI_CONG_VIEC['DANG_LAM_THU_TUC_THOI_VIEC']['value'];
        } else {
            $trangThai = Common::TRANG_THAI_CONG_VIEC[$newData['trang_thai']]['value'];
        }

        resSuccess($newData, 'Cập nhật thành công', 200, true, [
            'trang_thai' => $trangThai,
        ]);
    }

    public function view_log_get()
    {
        $start       = (int)(commonRequest('start') ?? 0);
        $length      = (int)(commonRequest('length') ?? 20);
        $search      = commonRequest('search') ?? '';
        $from_date   = commonRequest('from_date') ?? '';
        $to_date     = commonRequest('to_date') ?? '';
        $action_type = commonRequest('action_type') ?? '';

        // ── Field mapping: DB key → nhãn tiếng Việt (null = ẩn) ───
        $fieldMapping = [
            // Thông tin thôi việc
            'ten_thu_tuc'                   => 'Tên thủ tục',
            'nhom_thu_tuc'                  => 'Nhóm thủ tục',
            'trang_thai'                    => 'Trạng thái',
            'ngay_hoan_thanh'               => 'Ngày hoàn thành',
            'ly_do_thoi_viec'               => 'Lý do thôi việc',
            'ngay_lam_chinh_thuc_ket_thuc'  => 'Ngày kết thúc làm việc',
            // Thông tin nhân viên cơ bản (có thể xuất hiện trong log)
            'ho_va_ten'                     => 'Họ và tên',
            'ma_nhan_vien'                  => 'Mã nhân viên',
            // Thủ tục thôi việc (new format)
            'thutucthoiviec'                => 'Thủ tục thôi việc',
            'Thủ tục thôi việc'             => 'Thủ tục thôi việc',
            'Lý do thôi việc'               => 'Lý do thôi việc',
            'Ngày kết thúc làm việc'        => 'Ngày kết thúc làm việc',
            // ── Ẩn (null) ────────────────────────────────────────
            'id_nhan_vien'                  => null,
            'id_tttv'                       => null,
            'id_nhan_vien_thoi_viec'        => null,
            'created_at'                    => null,
            'created_user_id'               => null,
            'updated_at'                    => null,
            'updated_user_id'               => null,
            'deleted_at'                    => null,
            'deleted_user_id'               => null,
            'Ngày tạo'                      => null,
            'Người tạo'                     => null,
            'Ngày cập nhật'                 => null,
            'Người cập nhật'                => null,
            'Ngày xóa'                      => null,
            'Người xóa'                     => null,
        ];

        // ── Build query ──────────────────────────────────────────────────────
        $this->db
            ->select('ql_nhat_ky.ql_nhat_ky_id, ql_nhat_ky.ql_nhat_ky_hanh_dong, ql_nhat_ky.ql_nhat_ky_noi_dung, ql_nhat_ky.ql_nhat_ky_gia_tri_cu, ql_nhat_ky.ql_nhat_ky_gia_tri_moi, ql_nhat_ky.ql_nhat_ky_bang_du_lieu, ql_nhat_ky.ql_nhat_ky_ngay_tao, ql_nguoi_dung.ql_nguoi_dung_ho_ten, ql_nguoi_dung.ql_nguoi_dung_email, ql_nguoi_dung.ql_nguoi_dung_avatar')
            ->from('ql_nhat_ky')
            ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = ql_nhat_ky.ql_nguoi_dung_id', 'left')
            ->where('ql_nhat_ky.ql_nhat_ky_controller', 'thoiviec')
            ->order_by('ql_nhat_ky.ql_nhat_ky_ngay_tao', 'DESC');

        if ($from_date) $this->db->where('ql_nhat_ky.ql_nhat_ky_ngay_tao >=', $from_date . ' 00:00:00');
        if ($to_date)   $this->db->where('ql_nhat_ky.ql_nhat_ky_ngay_tao <=', $to_date . ' 23:59:59');

        if ($action_type) {
            if ($action_type === 'create')      $this->db->like('ql_nhat_ky.ql_nhat_ky_hanh_dong', 'tao', 'both');
            elseif ($action_type === 'delete')  $this->db->like('ql_nhat_ky.ql_nhat_ky_hanh_dong', 'xoa', 'both');
            elseif ($action_type === 'update')  $this->db->like('ql_nhat_ky.ql_nhat_ky_hanh_dong', 'update', 'both');
        }

        if ($search) {
            $this->db->group_start();
            $this->db->like('ql_nhat_ky.ql_nhat_ky_hanh_dong', $search);
            $this->db->or_like('ql_nhat_ky.ql_nhat_ky_noi_dung', $search);
            $this->db->or_like('ql_nguoi_dung.ql_nguoi_dung_ho_ten', $search);
            $this->db->group_end();
        }

        // Count filtered
        $countQuery      = clone $this->db;
        $recordsFiltered = $countQuery->count_all_results('', false);

        if ($length > 0) $this->db->limit($length, $start);
        $rows = $this->db->get()->result_array();

        // ── Value mapping: mapping các giá trị đặc biệt sang tiếng Việt ───
        $valueMapping = [
            'trang_thai' => [
                'DANG_LAM_VIEC'              => 'Đang làm việc',
                'DANG_LAM_THU_TUC_THOI_VIEC' => 'Đang làm thủ tục thôi việc',
                'NGHI_VIEC'                  => 'Nghỉ việc',
                'THOI_VIEC'                  => 'Thôi việc',
            ]
        ];

        // ── Process each row ─────────────────────────────────────────────────
        $data = [];
        foreach ($rows as $row) {
            $cuRaw  = [];
            $moiRaw = [];

            if (!empty($row['ql_nhat_ky_gia_tri_cu']) && $row['ql_nhat_ky_gia_tri_cu'] !== 'null') {
                $decoded = json_decode($row['ql_nhat_ky_gia_tri_cu'], true);
                if (is_array($decoded)) $cuRaw = $decoded;
            }
            if (!empty($row['ql_nhat_ky_gia_tri_moi']) && $row['ql_nhat_ky_gia_tri_moi'] !== 'null') {
                $decoded = json_decode($row['ql_nhat_ky_gia_tri_moi'], true);
                if (is_array($decoded)) $moiRaw = $decoded;
            }

            $chi_tiet = [];

            // Detect new format: gia_tri_moi contains {field: {cu:{}, moi:{}}}
            $firstVal    = !empty($moiRaw) ? reset($moiRaw) : null;
            $isNewFormat = is_array($firstVal) && (array_key_exists('cu', $firstVal) || array_key_exists('moi', $firstVal));

            if ($isNewFormat) {
                foreach ($moiRaw as $fieldKey => $change) {
                    if (!is_array($change)) continue;
                    $label = array_key_exists($fieldKey, $fieldMapping) ? $fieldMapping[$fieldKey] : $fieldKey;
                    if ($label === null) continue;

                    $cuVal  = isset($change['cu'])  ? (is_array($change['cu'])  && isset($change['cu']['label'])  ? $change['cu']['label']  : $change['cu'])  : null;
                    $moiVal = isset($change['moi']) ? (is_array($change['moi']) && isset($change['moi']['label']) ? $change['moi']['label'] : $change['moi']) : null;

                    // Dịch value nếu có trong mảng
                    if (isset($valueMapping[$fieldKey])) {
                        if (isset($valueMapping[$fieldKey][$cuVal])) $cuVal = $valueMapping[$fieldKey][$cuVal];
                        if (isset($valueMapping[$fieldKey][$moiVal])) $moiVal = $valueMapping[$fieldKey][$moiVal];
                    }

                    $chi_tiet[$label] = ['cu' => $cuVal, 'moi' => $moiVal];
                }
            } else {
                $allKeys = array_unique(array_merge(array_keys($cuRaw), array_keys($moiRaw)));
                foreach ($allKeys as $key) {
                    if (!array_key_exists($key, $fieldMapping)) continue;
                    $label = $fieldMapping[$key];
                    if ($label === null) continue;

                    $cuVal  = isset($cuRaw[$key])  ? $cuRaw[$key]  : null;
                    $moiVal = isset($moiRaw[$key]) ? $moiRaw[$key] : null;

                    // Dịch value nếu có trong mảng
                    if (isset($valueMapping[$key])) {
                        if (isset($valueMapping[$key][$cuVal])) $cuVal = $valueMapping[$key][$cuVal];
                        if (isset($valueMapping[$key][$moiVal])) $moiVal = $valueMapping[$key][$moiVal];
                    }

                    if ($cuVal === $moiVal) continue;
                    if (empty($cuVal) && empty($moiVal)) continue;

                    $chi_tiet[$label] = ['cu' => $cuVal, 'moi' => $moiVal];
                }
            }

            $data[] = [
                'ql_nhat_ky_id'           => $row['ql_nhat_ky_id'],
                'ql_nhat_ky_hanh_dong'    => $row['ql_nhat_ky_hanh_dong'],
                'ql_nhat_ky_noi_dung'     => $row['ql_nhat_ky_noi_dung'],
                'ql_nhat_ky_bang_du_lieu' => $row['ql_nhat_ky_bang_du_lieu'],
                'ql_nhat_ky_ngay_tao'     => $row['ql_nhat_ky_ngay_tao'],
                'ql_nguoi_dung_ho_ten'    => $row['ql_nguoi_dung_ho_ten'],
                'ql_nguoi_dung_email'     => $row['ql_nguoi_dung_email'],
                'ql_nguoi_dung_avatar'    => !empty($row['ql_nguoi_dung_avatar']) ? encryptString($row['ql_nguoi_dung_avatar']) : null,
                'chi_tiet'                => $chi_tiet,
            ];
        }

        resSuccess([
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ], 'Lấy lịch sử thôi việc thành công');
    }
}
