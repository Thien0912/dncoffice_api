<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property Hrm_tam_ung_model $Hrm_tam_ung_model
 * @property Hrm_chi_tiet_tam_ung_model $Hrm_chi_tiet_tam_ung_model
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Fileupload $fileupload
 * @property Common $common
 * @property CI_Upload $upload
 * @property Pxl $pxl
 */

class Tamung extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();

        $this->load->helper('url');
        $this->load->model(['Hrm_tam_ung_model', 'Hrm_nhan_vien_model', 'Hrm_chi_tiet_tam_ung_model']);
        $this->load->library(['Validator', 'Fileupload', 'Common', 'Pxl', 'upload']);
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

        $fromDate = commonRequest('fromDate') ? commonRequest('fromDate') : null;
        $toDate = commonRequest('toDate') ? commonRequest('toDate') : null;

        $data = $this->Hrm_tam_ung_model->getAll($start, $length, $searchValue, $orderBy, $searchKey, $fromDate, $toDate);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }

    public function tam_ung_post()
    {
        $id_tam_ung = commonRequest('id_tam_ung');
        $data = $this->Hrm_tam_ung_model->get($id_tam_ung);
        resSuccess($data, 'Success', REST_Controller::HTTP_OK, true, []);
    }

    public function chiTiet_post()
    {
        $id_tam_ung = commonRequest('id_tam_ung');
        $data = $this->Hrm_chi_tiet_tam_ung_model->get_by_tam_ung($id_tam_ung);
        resSuccess($data, 'Success', REST_Controller::HTTP_OK, true, []);
    }

    public function getTamUng_get()
    {
        $id_tam_ung = commonRequest('id_tam_ung');
        $data = $this->Hrm_tam_ung_model->find($id_tam_ung);
        resSuccess($data, 'Success', REST_Controller::HTTP_OK, true, []);
    }

    public function create_post()
    {
        $id_nhan_vien = commonRequest('id_nhan_vien') ? commonRequest('id_nhan_vien') : null;
        $nhanVien = $this->Hrm_nhan_vien_model->where('id_nhan_vien', $id_nhan_vien)->first();

        if (!$nhanVien) {
            resError('Không tìm thấy thông tin nhân viên.', REST_Controller::HTTP_NOT_FOUND);
        }

        $tamUng = $this->Hrm_tam_ung_model
            ->where('id_nhan_vien', $id_nhan_vien)
            ->where('trang_thai !=', 2) // Hoàn thành
            ->where('deleted_at IS NULL')
            ->first();

        if ($tamUng) {
            resError('Nhân viên đang có khoản tạm ứng chưa hoàn tất.', REST_Controller::HTTP_BAD_REQUEST);
        }

        $nguoiLap = $this->Hrm_nhan_vien_model->where('ql_nguoi_dung_id', $this->getUserLogin()['ql_nguoi_dung_id'])->first();

        $data = [
            'id_nhan_vien' => $id_nhan_vien,
            'ngay_lap' => $this->requestOrDefault('ngay_lap', date('Y-m-d H:i:s')),
            'so_tien' => $this->requestOrDefault('so_tien', 0),
            'ly_do' => $this->requestOrDefault('ly_do', null),
            'trang_thai' => $this->requestOrDefault('trang_thai', Common::TAM_UNG['CHUA_DUYET']['value']),
            'nguoi_lap' => $nguoiLap['id_nhan_vien'] ?? null,
            'nguoi_duyet' => $this->requestOrDefault('nguoi_duyet', null),
            'ngay_duyet' => $this->requestOrDefault('ngay_duyet', null),
            'hinh_thuc_hoan_tra' => commonRequest('so_thang') == 1 ? Common::HINH_THUC_HOAN_TRA['MOT_LAN']['value'] : Common::HINH_THUC_HOAN_TRA['TUNG_THANG']['value'],
            'thang_bat_dau' => $this->requestOrDefault('thang_bat_dau', date('Y-m-t', strtotime('first day of next month'))),
            'so_thang' => $this->requestOrDefault('so_thang', 1),
        ];

        $validation = $this->validateData($data);
        if ($validation !== true) {
            resError($validation['message'], REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->db->trans_start();

        $result = $this->Hrm_tam_ung_model->create($data);

        if (!$result) {
            $this->db->trans_rollback();
            resError('Lỗi khi tạo bản ghi tạm ứng.', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
        $tam_ung = $result;

        $this->createLog('create', '', null, $tam_ung, 'hrm_tam_ung');
        $this->db->trans_commit();

        resSuccess(['tam_ung' => $tam_ung], 'Thêm 1 bản ghi tạm ứng thành công', REST_Controller::HTTP_CREATED);
    }

    public function approve_post()
    {
        $id_tam_ung = commonRequest('id_tam_ung') ? commonRequest('id_tam_ung') : null;

        if (!$id_tam_ung) {
            resError('Thiếu thông tin nhân viên hoặc tạm ứng.', REST_Controller::HTTP_BAD_REQUEST);
        }

        $tamUng = $this->Hrm_tam_ung_model->where('deleted_at', null)->where('id_tam_ung', $id_tam_ung)->first();

        if (!$tamUng) {
            resError('Không tìm thấy thông tin tạm ứng.', REST_Controller::HTTP_NOT_FOUND);
        }

        $nhanVien = $this->Hrm_nhan_vien_model->where('ql_nguoi_dung_id', $this->getUserLogin()['ql_nguoi_dung_id'])->first();

        if (!$nhanVien) {
            resError('Không tìm thấy thông tin nhân viên.', REST_Controller::HTTP_NOT_FOUND);
        }



        if ($tamUng['nguoi_duyet'] || $tamUng['ngay_duyet']) {
            resError('Tạm ứng đã được duyệt trước đó.', REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->db->trans_start();

        $update_result = $this->Hrm_tam_ung_model->where('deleted_at', null)->where('id_tam_ung', $id_tam_ung)->update([
            'nguoi_duyet' => $nhanVien['id_nhan_vien'],
            'ngay_duyet' => date('Y-m-d H:i:s'),
            'trang_thai' => Common::TAM_UNG['DA_DUYET']['value']
        ]);

        if (!$update_result) {
            $this->db->trans_rollback();
            resError('Lỗi khi cập nhật thông tin tạm ứng.', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }

        $detail_datas = [];
        if ($tamUng['hinh_thuc_hoan_tra'] == 1) { // Monthly repayment
            $so_thang = $tamUng['so_thang'];
            if ($so_thang <= 0) {
                $this->db->trans_rollback();
                resError('Số tháng hoàn trả không hợp lệ.', REST_Controller::HTTP_BAD_REQUEST);
            }
            $so_tien_per_month = $tamUng['so_tien'] / $so_thang;
            $ngay_lap = strtotime($tamUng['ngay_lap']);

            for ($i = 0; $i < $so_thang; $i++) {
                $thoi_han_thanh_toan = date('Y-m-t', strtotime("+" . ($i + 1) . " month", $ngay_lap));
                $detail_datas[] = [
                    'id_tam_ung' => $tamUng['id_tam_ung'],
                    'thoi_han_thanh_toan' => $thoi_han_thanh_toan,
                    'so_tien' => $so_tien_per_month,
                    'trang_thai' => $this->requestOrDefault('trang_thai', 0),
                    'ngay_thanh_toan' => $this->requestOrDefault('ngay_thanh_toan', null),
                ];
            }
        } else { // One-time repayment
            $detail_datas[] = [
                'id_tam_ung' => $tamUng['id_tam_ung'],
                'thoi_han_thanh_toan' => date('Y-m-t', strtotime("+1 month", strtotime($tamUng['ngay_lap']))),
                'so_tien' => $tamUng['so_tien'],
                'trang_thai' => $this->requestOrDefault('trang_thai', 0),
                'ngay_thanh_toan' => $this->requestOrDefault('ngay_thanh_toan', null),
            ];
        }

        foreach ($detail_datas as $detail) {
            $detail_result = $this->Hrm_chi_tiet_tam_ung_model->create($detail);
            if (!$detail_result) {
                $this->db->trans_rollback();
                resError('Lỗi khi tạo bản ghi chi tiết tạm ứng.', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $this->createLog('approve', $nhanVien['ho_va_ten'] . ' đã duyệt hồ sơ tạm ứng ' . $tamUng['id_tam_ung'], null, $tamUng, 'hrm_tam_ung');
        $this->db->trans_commit();

        resSuccess(['tam_ung' => $tamUng, 'detail_datas' => $detail_datas], 'Duyệt tạm ứng thành công', REST_Controller::HTTP_OK);
    }

    public function unapproved_post()
    {
        $id_tam_ung = commonRequest('id_tam_ung') ? commonRequest('id_tam_ung') : null;

        if (!$id_tam_ung) {
            resError('Thiếu thông tin nhân viên hoặc tạm ứng.', REST_Controller::HTTP_BAD_REQUEST);
        }

        $tamUng = $this->Hrm_tam_ung_model->where('id_tam_ung', $id_tam_ung)->first();

        if (!$tamUng) {
            resError('Không tìm thấy thông tin tạm ứng.', REST_Controller::HTTP_NOT_FOUND);
        }

        $nhanVien = $this->Hrm_nhan_vien_model->where('ql_nguoi_dung_id', $this->getUserLogin()['ql_nguoi_dung_id'])->first();

        if (!$nhanVien) {
            resError('Không tìm thấy thông tin nhân viên.', REST_Controller::HTTP_NOT_FOUND);
        }

        $chiTietTamUng = $this->Hrm_chi_tiet_tam_ung_model->where('deleted_at', null)->where('id_tam_ung', $id_tam_ung)->get();

        foreach ($chiTietTamUng as $chiTiet) {
            if ($chiTiet['trang_thai'] == 1) {
                resError('Không thể hủy tạm ứng đã thanh toán.', REST_Controller::HTTP_BAD_REQUEST);
            }
        }

        $this->db->trans_start();

        $update_result = $this->Hrm_tam_ung_model
            ->where('deleted_at', null)
            ->where('id_tam_ung', $id_tam_ung)
            ->update([
                'nguoi_duyet' => null,
                'ngay_duyet' => null,
                'trang_thai' => Common::TAM_UNG['CHUA_DUYET']['value']
            ]);

        if (!$update_result) {
            $this->db->trans_rollback();
            resError('Lỗi khi cập nhật thông tin tạm ứng.', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }

        $delete_result = $this->Hrm_chi_tiet_tam_ung_model
            ->where('deleted_at', null)
            ->where('id_tam_ung', $id_tam_ung)
            ->update([
                'deleted_at' => date('Y-m-d H:i:s')
            ]);

        if (!$delete_result) {
            $this->db->trans_rollback();
            resError('Lỗi khi xoá bản ghi chi tiết tạm ứng.', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->createLog('approve', $nhanVien['ho_va_ten'] . ' đã huỷ duyệt hồ sơ tạm ứng ' . $tamUng['id_tam_ung'], null, $tamUng, 'hrm_tam_ung');
        $this->db->trans_commit();

        resSuccess(['tam_ung' => $tamUng], 'Huỷ duyệt tạm ứng thành công', REST_Controller::HTTP_OK);
    }

    /**
     * Hàm cập nhật tạm ứng
     */
    public function update_post()
    {
        $id_tam_ung = commonRequest('id_tam_ung') ? commonRequest('id_tam_ung') : null;

        if (!$id_tam_ung) {
            resError('Thiếu ID tạm ứng.', REST_Controller::HTTP_BAD_REQUEST);
        }

        $tamUng = $this->Hrm_tam_ung_model->where('id_tam_ung', $id_tam_ung)->first();


        if (!$tamUng) {
            resError('Không tìm thấy thông tin tạm ứng.', REST_Controller::HTTP_NOT_FOUND);
        }

        if ($tamUng['trang_thai'] != Common::TAM_UNG['CHUA_DUYET']['value']) {
            resError('Không thể cập nhật tạm ứng đã được duyệt.', REST_Controller::HTTP_BAD_REQUEST);
        }

        $data = [
            'id_nhan_vien' => $this->requestOrDefault('id_nhan_vien', $tamUng['id_nhan_vien']),
            'ngay_lap' => $this->requestOrDefault('ngay_lap', $tamUng['ngay_lap']),
            'so_tien' => $this->requestOrDefault('so_tien', $tamUng['so_tien']),
            'ly_do' => $this->requestOrDefault('ly_do', $tamUng['ly_do']),
            'trang_thai' => $this->requestOrDefault('trang_thai', $tamUng['trang_thai']),
            'nguoi_lap' => $this->requestOrDefault('nguoi_lap', $tamUng['nguoi_lap']),
            'nguoi_duyet' => $this->requestOrDefault('nguoi_duyet', $tamUng['nguoi_duyet']),
            'ngay_duyet' => $this->requestOrDefault('ngay_duyet', $tamUng['ngay_duyet']),
            'hinh_thuc_hoan_tra' => $this->requestOrDefault('hinh_thuc_hoan_tra', $tamUng['hinh_thuc_hoan_tra']),
            'thang_bat_dau' => $this->requestOrDefault('thang_bat_dau', $tamUng['thang_bat_dau']),
            'so_thang' => $this->requestOrDefault('so_thang', $tamUng['so_thang']),
        ];

        $this->validateData($data);

        // Check if critical fields changed
        $need_regenerate_details = (
            $data['so_tien'] != $tamUng['so_tien'] ||
            $data['so_thang'] != $tamUng['so_thang'] ||
            $data['ngay_lap'] != $tamUng['ngay_lap']
        );

        $this->db->trans_start();

        $update_result = $this->Hrm_tam_ung_model->where('id_tam_ung', $id_tam_ung)->update($data);
        if (!$update_result) {
            $this->db->trans_rollback();
            resError('Lỗi khi cập nhật bản ghi tạm ứng.', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }

        $detail_datas = [];
        if ($need_regenerate_details) {
            // Delete existing detail records
            $this->Hrm_chi_tiet_tam_ung_model->where('id_tam_ung', $id_tam_ung)->set(['deleted_at' => date('Y-m-d H:i:s')])
                ->update();

            // Regenerate detail records
            $date = new DateTime($data['ngay_lap']);
            if ($data['hinh_thuc_hoan_tra'] == 1) { // Monthly repayment
                $so_thang = $data['so_thang'];
                if ($so_thang <= 0) {
                    $this->db->trans_rollback();
                    resError('Số tháng hoàn trả không hợp lệ.', REST_Controller::HTTP_BAD_REQUEST);
                }
                $so_tien_per_month = $data['so_tien'] / $so_thang;

                for ($i = 0; $i < $so_thang; $i++) {
                    $date->modify('+1 month');
                    $thoi_han_thanh_toan = $date->format('Y-m-t');
                    $detail_datas[] = [
                        'id_tam_ung' => $id_tam_ung,
                        'thoi_han_thanh_toan' => $thoi_han_thanh_toan,
                        'so_tien' => $so_tien_per_month,
                        'trang_thai' => $this->requestOrDefault('detail_trang_thai', 0),
                        'ngay_thanh_toan' => $this->requestOrDefault('ngay_thanh_toan', null),
                    ];
                }
            } else { // One-time repayment
                $date->modify('+1 month');
                $thoi_han_thanh_toan = $date->format('Y-m-t');
                $detail_datas[] = [
                    'id_tam_ung' => $id_tam_ung,
                    'thoi_han_thanh_toan' => $thoi_han_thanh_toan,
                    'so_tien' => $data['so_tien'],
                    'trang_thai' => $this->requestOrDefault('detail_trang_thai', 0),
                    'ngay_thanh_toan' => $this->requestOrDefault('ngay_thanh_toan', null),
                ];
            }

            foreach ($detail_datas as $detail) {
                $detail_result = $this->Hrm_chi_tiet_tam_ung_model->create($detail);
                if (!$detail_result) {
                    $this->db->trans_rollback();
                    resError('Lỗi khi tạo bản ghi chi tiết tạm ứng.', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
        }

        $this->createLog('update', '', null, $tamUng, 'hrm_tam_ung');
        $this->db->trans_commit();

        resSuccess(['tam_ung' => $data, 'detail_datas' => $detail_datas], 'Cập nhật tạm ứng thành công', REST_Controller::HTTP_OK);
    }

    /**
     * Hàm xóa tạm ứng (xoá mềm)
     */
    public function delete_post()
    {
        $id_tam_ung = commonRequest('id_tam_ung') ? commonRequest('id_tam_ung') : null;

        if (!$id_tam_ung) {
            resError('Thiếu ID tạm ứng.', REST_Controller::HTTP_BAD_REQUEST);
        }

        $tamUng = $this->Hrm_tam_ung_model->where('id_tam_ung', $id_tam_ung)->first();
        if (!$tamUng) {
            resError('Không tìm thấy thông tin tạm ứng.', REST_Controller::HTTP_NOT_FOUND);
        }

        $this->db->trans_start();

        // Xoá mềm chi tiết tạm ứng
        $detail_delete_result = $this->Hrm_chi_tiet_tam_ung_model
            ->where('id_tam_ung', $id_tam_ung)
            ->update(['deleted_at' => date('Y-m-d H:i:s')]);

        if ($detail_delete_result === false) {
            $this->db->trans_rollback();
            resError('Lỗi khi cập nhật xoá chi tiết tạm ứng.', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Xoá mềm bản ghi tạm ứng
        $tam_ung_delete_result = $this->Hrm_tam_ung_model
            ->where('id_tam_ung', $id_tam_ung)
            ->update(['deleted_at' => date('Y-m-d H:i:s')]);

        if ($tam_ung_delete_result === false) {
            $this->db->trans_rollback();
            resError('Lỗi khi cập nhật xoá tạm ứng.', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->createLog('soft_delete', '', null, $tamUng, 'hrm_tam_ung');
        $this->db->trans_commit();

        resSuccess([], 'Xoá tạm ứng thành công', REST_Controller::HTTP_OK);
    }

    /**
     * Hàm lấy quy tắc validate
     */
    private function getValidationRules()
    {
        $rules = [
            'id_nhan_vien' => 'required|string|min:1',
            'ngay_lap' => 'required|datetime',
            'so_tien' => 'required|numeric|min:0.01',
            'ly_do' => 'nullable|string|max:255',
            'trang_thai' => 'nullable|in:0,1,2', // Adjust based on valid statuses
            'nguoi_lap' => 'nullable|min:1',
            'nguoi_duyet' => 'nullable|min:1',
            'ngay_duyet' => 'nullable|date',
            'hinh_thuc_hoan_tra' => 'nullable|in:0,1', // Adjust based on valid repayment methods
            'thang_bat_dau' => 'nullable|date',
            'so_thang' => 'required|integer|min:1',
        ];

        $customMessages = [
            'id_nhan_vien.required' => 'ID nhân viên là bắt buộc.',
            'id_nhan_vien.string' => 'ID nhân viên phải là chuỗi.',
            'id_nhan_vien.min' => 'ID nhân viên không được để trống.',
            'ngay_lap.required' => 'Ngày lập là bắt buộc.',
            'ngay_lap.date' => 'Ngày lập không đúng định dạng.',
            'so_tien.required' => 'Số tiền là bắt buộc.',
            'so_tien.numeric' => 'Số tiền phải là số.',
            'so_tien.min' => 'Số tiền phải lớn hơn 0.',
            'ly_do.string' => 'Lý do phải là chuỗi.',
            'ly_do.max' => 'Lý do không được dài quá 255 ký tự.',
            'trang_thai.integer' => 'Trạng thái phải là số nguyên.',
            'trang_thai.in' => 'Trạng thái không hợp lệ.',
            'nguoi_duyet.min' => 'Người duyệt không được để trống nếu cung cấp.',
            'ngay_duyet.date' => 'Ngày duyệt không đúng định dạng.',
            'hinh_thuc_hoan_tra.string' => 'Hình thức hoàn trả phải là chuỗi.',
            'hinh_thuc_hoan_tra.in' => 'Hình thức hoàn trả không hợp lệ.',
            'thang_bat_dau.date' => 'Tháng bắt đầu không đúng định dạng.',
            'so_thang.required' => 'Số tháng là bắt buộc.',
            'so_thang.integer' => 'Số tháng phải là số nguyên.',
            'so_thang.min' => 'Số tháng phải lớn hơn hoặc bằng 1.',
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

        return true;
    }

    function requestOrDefault($key, $default)
    {
        $value = commonRequest($key);
        return $value !== null && $value !== '' ? $value : $default;
    }

    public function tamUngExport_post()
    {
        $data = $this->Hrm_tam_ung_model->getAll(0, -1, null, [], []);

        $titles = [
            'stt'              => 'STT',
            'nhan_vien_ho_ten' => 'Họ tên nhân viên',
        ];

        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $sheet = $objPHPExcel->getActiveSheet();

        // Tiêu đề lớn
        $sheet->setCellValue('A1', 'Danh sách cảnh báo hết hạn đóng BHXH');
        $sheet->mergeCells('A1:G1');
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
            ]
        ]);

        // Ghi tiêu đề cột
        $column = 'A';
        foreach ($titles as $title) {
            $sheet->setCellValue($column . '2', $title);
            $sheet->getStyle($column . '2')->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
                'font' => [
                    'bold' => true,
                ],
                'alignment' => [
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                ],
            ]);
            $sheet->getColumnDimension($column)->setAutoSize(true);
            $column++;
        }

        // Ghi dữ liệu
        $rowIndex = 3;
        $stt = 1;
        foreach ($data['data'] as $item) {
            $column = 'A';
            foreach (array_keys($titles) as $key) {
                $value = '';

                if ($key == 'stt') {
                    $value = $stt++;
                } else {
                    $value = isset($item[$key]) ? $item[$key] : '';
                }

                $sheet->setCellValue($column . $rowIndex, $value);

                $sheet->getStyle($column . $rowIndex)->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                $sheet->getStyle($column . $rowIndex)->getAlignment()->setWrapText(true);
                $column++;
            }
            $rowIndex++;
        }

        // Xuất file ra base64
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        ob_start();
        $objWriter->save('php://output');
        $excelOutput = ob_get_clean();

        $base64 = base64_encode($excelOutput);

        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Export thành công',
            'success' => true,
            'data' => [
                'filename'   => 'tam_ung_' . date('Ymd_His') . '.xlsx',
                'base64'     => $base64,
                'mime_type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function hoanUng_post()
    {
        $id_tam_ung = commonRequest('id_tam_ung');
        $chi_tiet_ids = commonRequest('chi_tiet_ids');

        if (!$id_tam_ung || !is_array($chi_tiet_ids) || empty($chi_tiet_ids)) {
            return $this->response([
                'success' => false,
                'message' => 'Thiếu dữ liệu hoặc danh sách chi tiết không hợp lệ.'
            ]);
        }

        // Lấy thông tin tạm ứng
        $tamUng = $this->Hrm_tam_ung_model->get_tam_ung($id_tam_ung);

        if (!$tamUng) {
            return $this->response([
                'success' => false,
                'message' => 'Không tìm thấy thông tin tạm ứng.'
            ]);
        }

        // Lấy danh sách chi tiết
        $chiTietList = $this->Hrm_chi_tiet_tam_ung_model->get_chi_tiet_tam_ung($chi_tiet_ids);

        if (empty($chiTietList)) {
            return $this->response([
                'success' => false,
                'message' => 'Không có chi tiết tạm ứng phù hợp.'
            ]);
        }

        $this->Hrm_chi_tiet_tam_ung_model->update_status($chi_tiet_ids);

        // Tạo HTML
        $html = '
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Phiếu hoàn ứng</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.5; padding: 20px; }
            table { border-collapse: collapse; width: 100%; margin-top: 20px; }
            th, td { border: 1px solid #000; padding: 8px; text-align: center; }
            h3 { text-align: center; }
        </style>
    </head>
    <body>
        <h3>PHIẾU HOÀN ỨNG</h3>
        <p><strong>Mã tạm ứng:</strong> ' . str_pad(htmlspecialchars($tamUng['id_tam_ung']), 6, '0', STR_PAD_LEFT) . '</p>
        <p><strong>Họ tên:</strong> ' . htmlspecialchars($tamUng['ho_va_ten']) . '</p>
        <table>
            <thead>
                <tr><th>STT</th><th>Tháng</th><th>Số tiền</th></tr>
            </thead>
            <tbody>';

        $tongTien = 0;
        foreach ($chiTietList as $index => $ct) {
            $thang = date('m/Y', strtotime($ct['thoi_han_thanh_toan']));
            $soTien = number_format($ct['so_tien'], 0, ',', '.');
            $tongTien += $ct['so_tien'];
            $html .= "<tr>
            <td>" . ($index + 1) . "</td>
            <td>$thang</td>
            <td>$soTien VNĐ</td>
        </tr>";
        }

        $html .= '
            <tr>
                <td colspan="2"><strong>Tổng cộng</strong></td>
                <td><strong>' . number_format($tongTien, 0, ',', '.') . ' VNĐ</strong></td>
            </tr>
        </tbody>
        </table>
    </body>
    </html>';

        return $this->response([
            'success' => true,
            'html' => $html,
            'message' => 'Tạo phiếu hoàn ứng thành công.'
        ]);
    }

    public function download_post($file_name)
    {
        $path = 'assets/download/excel/excel_sample/tamung/' . $file_name . '.docx';
        resSuccess(['path' => $path], 'Download file import');
    }
}
