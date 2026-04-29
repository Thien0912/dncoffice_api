<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property Hrm_bang_luong_model $Hrm_bang_luong_model
 * @property Hrm_bang_luong_thang_model $Hrm_bang_luong_thang_model
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Hrm_bao_hiem_dong_model $Hrm_bao_hiem_dong_model
 * @property Fileupload $fileupload
 * @property Common $common
 * @property CI_Upload $upload
 * @property Pxl $pxl
 * @property email $email
 * @property DB_query_builder $db
 */

class Bangluong extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model(['Hrm_bang_luong_model', 'Hrm_bang_luong_thang_model', 'Hrm_nhan_vien_model', 'Hrm_bao_hiem_dong_model']);
        // $this->load->library(['Validator', 'Fileupload', 'Pxl']);
        $this->load->library(['Validator', 'Fileupload', 'Common', 'Pxl', 'upload']);
    }

    // hrm_bang_luong_thang (Bảng lương tháng)
    public function index_get()
    {
        $auth  = $this->getUserLogin();
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

        $data = $this->Hrm_bang_luong_thang_model->getAll($data['start'], $data['length'], $data['searchValue'], $data['order'], $data['columns'], $data['searchKey'], $data['fromDate'], $data['toDate'], $auth);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
            'sql' => $data['sql'],
        ]);
    }

    // hrm_bang_luong (Bảng lương chi tiết tháng)
    public function bangluong_get($idBangLuongThang)
    {
        // $start = commonRequest('start') ? commonRequest('start') : 0;
        // $length = commonRequest('length') ? commonRequest('length') : 10;
        // $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;

        // $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
        //     'order' => commonRequest('order'),
        //     'columns' => commonRequest('columns')
        // ] : [];

        // $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];

        // $fromDate = commonRequest('fromDate') ? commonRequest('fromDate') : null;
        // $toDate = commonRequest('toDate') ? commonRequest('toDate') : null;

        // $fromMonth = commonRequest('fromMonth') ? commonRequest('fromMonth') : null;
        // $toMonth = commonRequest('toMonth') ? commonRequest('toMonth') : null;

        $auth  = $this->getUserLogin();
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

        $data = $this->Hrm_bang_luong_model->getAll($data['start'], $data['length'], $data['searchValue'], $data['order'], $data['columns'], $data['searchKey'], $data['fromDate'], $data['toDate'], $auth, $idBangLuongThang);
        $bangLuongThang = $this->Hrm_bang_luong_thang_model->find($idBangLuongThang);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
            'thang' => $bangLuongThang['thang'],
            'gui_mail' => $bangLuongThang['gui_mail']
        ]);
    }

    public function create_post() {}


    public function update_post() {}

    public function delete_get() {}

    public function tinh_luong_get($nhanvienId)
    {
        $fromDate = commonRequest('fromDate');
        $toDate = commonRequest('toDate');

        $this->Hrm_bang_luong_model->tinh_luong($nhanvienId, $fromDate, $toDate);
    }

    // public function tinh_luong($nhanvienId, $fromDate, $toDate)
    // {
    //     $result = $this->Hrm_bang_luong_model->tinh_luong($nhanvienId, $fromDate, $toDate);

    //     return $result;
    // }

    public function export_thuong_post()
    {
        $tham_nien = commonRequest('tham_nien') ? commonRequest('tham_nien') : null;
        $tieu_de = commonRequest('tieu_de') ? commonRequest('tieu_de') : null;
        $noi_dung_thuong = commonRequest('noi_dung_thuong') ? commonRequest('noi_dung_thuong') : null;
        $so_tien = commonRequest('so_tien') ? commonRequest('so_tien') : null;

        $data_rule = [
            'tham_nien' => $tham_nien,
            'tieu_de' => $tieu_de,
            'noi_dung_thuong' => $noi_dung_thuong,
            'so_tien' => $so_tien,
        ];
        $rules = [
            'tham_nien' => 'required|numeric',
            'tieu_de' => 'required',
            'noi_dung_thuong' => 'required',
            'so_tien' => 'required',
        ];

        $customMessages = [
            'tham_nien.required' => 'Thâm niên bắt buộc nhập.',
            'tham_nien.numeric' => 'Thâm niên phải là một số.',
            'tieu_de.required' => 'Tiêu đề bắt buộc nhập.',
            'noi_dung_thuong.required' => 'Nội dung thưởng bắt buộc nhập.',
            'so_tien.required' => 'Số tiền bắt buộc nhập.',
        ];

        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data_rule, $rules)) {
            resBadrequest($validator->errors(), 'Vui lòng kiểm tra lại các trường cần nhập');
        }

        // Lấy danh sách nhân viên
        $data = $this->Hrm_bang_luong_model->export_thuong($so_tien, $tham_nien);
        // dd($this->db->last_query());

        $titles = [
            'stt' => 'STT',
            'ngan_hang_hd' => 'HDBank',
            'ngan_hang_viettin' => 'Viettinbank',
            'ngan_hang_khac' => 'Khác',
            'ho_va_ten' => 'Họ và tên',
            'vi_tri_cong_viec' => 'Vị trí công việc',
            'trinh_do_dt' => 'Trình độ đào tạo',
            'nganh_dt' => 'Ngành đào tạo',
            'ngay_lam_chinh_thuc' => 'Ngày làm chính thức',
            'tham_nien' => 'Thâm niên',
            $noi_dung_thuong => $noi_dung_thuong,
            'thue_tncn' => 'Thuế TNCN (10%)',
            'thuc_nhan' => 'Thực nhận sau thuế TNCN',
            'hinh_thuc_tt' => 'Hình thức thanh toán',
            'tk_ngan_hang' => 'Tài khoản ngân hàng',
            'mst_ca_nhan' => 'MST',
            'ngay_sinh' => 'Ngày sinh',
            'cccd_so' => 'Số CCCD',
            'cccd_ngay_cap' => 'Ngày Cấp CCCD',
            'cccd_noi_cap' => 'Nơi Cấp CCCD',
            'loc_chuc_vu' => 'Lọc chức vụ',
            'loc_gioi_tinh' => 'Lọc giới tính',
        ];
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Thưởng');

        // Đặt tiêu đề cột vào hàng đầu tiên dựa trên mảng $titles
        $column = 'A';
        foreach ($titles as $key => $title) {
            $sheet->setCellValue($column . '4', $title);
            $sheet->getStyle($column . '4')->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
                'font' => [
                    'bold' => true,
                    'size' => 14.5,
                    'name' => 'Times New Roman',
                ],
                'alignment' => [
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                ]
            ]);
            $sheet->getStyle($column . '4')->getAlignment()->setWrapText(true);

            // Set column width based on text length
            $sheet->getColumnDimension($column)->setAutoSize(true);

            $column++;
        }

        $sheet->setCellValue('A1', 'Trường Đại học Nam Cần Thơ');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14.5,
                'name' => 'Times New Roman',
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            ],
        ]);

        // A2: Phòng Tài chính - Kế hoạch
        $sheet->setCellValue('A2', 'Phòng Tài chính - Kế hoạch');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14.5,
                'name' => 'Times New Roman',
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            ],
        ]);

        // A3: $tieu_de (tiêu đề chính căn giữa)
        $sheet->setCellValue('A3', $tieu_de);
        $sheet->mergeCells('A3:K3'); // merge như bạn đã làm trước đó
        $sheet->getStyle('A3')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 20,
                'name' => 'Times New Roman',
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Đặt dữ liệu vào các hàng tiếp theo
        $row = 5;
        $stt = 0;
        foreach ($data as $item) {
            $column = 'A';
            ++$stt;
            foreach ($titles as $key => $title) {
                $sheet->setCellValue('A' . $row, $stt);
                $value = $key === $noi_dung_thuong ? $so_tien : (isset($item[$key]) ? $item[$key] : '');
                $sheet->setCellValue($column . $row, $value);


                $sheet->getStyle($column . $row)->applyFromArray([
                    'font' => [
                        'size' => 14.5,
                        'name' => 'Times New Roman',
                    ],
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                $sheet->getColumnDimension($column)->setAutoSize(true);
                $column++;
            }
            $row++;
        }

        // Đặt tiêu đề cho file Excel
        $directory = 'uploads/export/' . date('Y') . '/';
        $filename = 'danhsach_thuong' . time() . '.xlsx';
        $filePath = $directory . $filename;

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);

        if (file_exists($filePath)) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Success',
                'success' => true,
                'data' => base_url($filePath)
            ], REST_INSTANCE_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                'message' => 'File not found',
                'success' => false,
                'data' => null
            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }
    }

    public function export_tong_thu_nhap_nam_post()
    {
        $nam = commonRequest('nam') ? commonRequest('nam') : null;

        $data_rule = [
            'nam' => $nam,
        ];
        $rules = [
            'nam' => 'required|numeric',
        ];

        $customMessages = [
            'nam.required' => 'Năm bắt buộc chọn.',
            'nam.numeric' => 'Năm phải là một số.',
        ];

        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data_rule, $rules)) {
            resBadrequest($validator->errors(), 'Vui lòng kiểm tra lại các trường cần nhập');
        }

        $data = $this->Hrm_bang_luong_model->get_tong_thu_nhap_theo_nam($nam);

        $titles = [
            'stt' => 'STT',
            'ngan_hang_hd' => 'HDBank',
            'ngan_hang_viettin' => 'Viettinbank',
            'ngan_hang_khac' => 'Khác',
            'ho_va_ten' => 'Họ và tên',
            'vi_tri_cong_viec' => 'Chức vụ',
            'hoc_ham_hoc_vi' => 'Học hàm, học vị',
            'chuyen_nganh' => 'Chuyên ngành',
            'ngoai_gio_cn' => 'Ngoài giờ CN (ngày)',
            'ngoai_gio_le' => 'Ngoài giờ Lễ (ngày)',
            'ngoai_gio_khac' => 'Ngoài giờ Khác (ngày)',
            'ngay_lam_chinh_thuc' => 'Ngày làm chính thức',
            'tham_nien' => 'Thâm niên (tháng)',
            'tong_muc_luong_chinh' => 'Mức lương chính',
            'tong_phu_cap' => 'Phụ cấp',
            'tong_phu_cap_khac' => 'Phụ cấp khác',
            'tong_ngoai_gio' => 'Ngoài giờ',
            'tong_luong' => 'Tổng lương + PC',
            'tong_thu_nhap' => 'Tổng thu nhập',
            'tong_bao_hiem' => 'BHXH + YT + TN (10.5%)',
            'dpcd' => 'ĐPCĐ (1%)',
            'tong_thu_nhap_chiu_thue' => 'Tổng thu nhập chịu thuế',
            'tong_thue_tncn' => 'Thuế TNCN',
            'tong_luong_thuc_nhan' => 'Thực nhận (VNĐ)',
            'hinh_thuc_tt' => 'Hình thức TT',
            'stk' => 'STK',
            'ngan_hang' => 'Ngân hàng',
            'mst' => 'MST',
            'ngay_sinh' => 'Ngày sinh',
            'cccd_so' => 'Số CCCD',
            'cccd_ngay_cap' => 'Ngày Cấp CCCD',
            'cccd_noi_cap' => 'Nơi Cấp CCCD',
            'loc_chuc_vu' => 'Lọc chức vụ',
            'loc_gioi_tinh' => 'Lọc giới tính',
        ];
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Tổng hợp thu nhập theo năm');

        // Đặt tiêu đề cột vào hàng đầu tiên dựa trên mảng $titles
        $column = 'A';
        foreach ($titles as $key => $title) {
            $sheet->setCellValue($column . '4', $title);
            $sheet->getStyle($column . '4')->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
                'font' => [
                    'bold' => true,
                    'size' => 14.5,
                    'name' => 'Times New Roman',
                ],
                'alignment' => [
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                ]
            ]);
            $sheet->getStyle($column . '4')->getAlignment()->setWrapText(true);

            // Set column width based on text length
            $sheet->getColumnDimension($column)->setAutoSize(true);

            $column++;
        }

        $sheet->setCellValue('A1', 'Trường Đại học Nam Cần Thơ');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14.5,
                'name' => 'Times New Roman',
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            ],
        ]);

        // A2: Phòng Tài chính - Kế hoạch
        $sheet->setCellValue('A2', 'Phòng Tài chính - Kế hoạch');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14.5,
                'name' => 'Times New Roman',
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            ],
        ]);

        // A3: $tieu_de (tiêu đề chính căn giữa)
        $sheet_title = 'Danh sách tổng thu nhập theo năm ' . $nam;
        $sheet->setCellValue('A3', $sheet_title);
        $sheet->mergeCells('A3:K3'); // merge như bạn đã làm trước đó
        $sheet->getStyle('A3')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 20,
                'name' => 'Times New Roman',
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Đặt dữ liệu vào các hàng tiếp theo
        $row = 5;
        $stt = 0;
        foreach ($data as $item) {
            $column = 'A';
            ++$stt;
            foreach ($titles as $key => $title) {
                $sheet->setCellValue('A' . $row, $stt);
                $sheet->setCellValue($column . $row, isset($item[$key]) ? $item[$key] : '');

                $sheet->getStyle($column . $row)->applyFromArray([
                    'font' => [
                        'size' => 14.5,
                        'name' => 'Times New Roman',
                    ],
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                $sheet->getColumnDimension($column)->setAutoSize(true);
                $column++;
            }
            $row++;
        }

        // Auto-size all columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Đặt tiêu đề cho file Excel
        $directory = 'uploads/export/' . date('Y') . '/';
        $filename = 'danhsach_tongthunhap' . time() . '.xlsx';
        $filePath = $directory . $filename;

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);

        if (file_exists($filePath)) {
            resSuccess(base_url($filePath), 'Xuất file thành công', REST_INSTANCE_Controller::HTTP_OK);
        } else {
            resError('File không tồn tại', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }
    }

    public function export_danh_sach_luong_ngan_hang_khac_post()
    {
        $thang = commonRequest('thang') ? commonRequest('thang') : null;
        $nam = commonRequest('nam') ? commonRequest('nam') : null;
        $noi_dung = commonRequest('noi_dung') ? commonRequest('noi_dung') : null;

        $data_rule = [
            'thang' => $thang,
            'nam' => $nam,
            'noi_dung' => $noi_dung,
        ];
        $rules = [
            'nam' => 'required|numeric',
            'thang' => 'required|numeric',
            'noi_dung' => 'required',
        ];

        $customMessages = [
            'nam.required' => 'Năm bắt buộc chọn.',
            'nam.numeric' => 'Năm phải là một số.',
            'thang.required' => 'Tháng bắt buộc chọn.',
            'thang.numeric' => 'Tháng phải là một số.',
            'noi_dung.required' => 'Nội dung bắt buộc nhập.',
        ];

        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data_rule, $rules)) {
            resBadrequest($validator->errors(), 'Vui lòng kiểm tra lại các trường cần nhập');
        }

        $data = $this->Hrm_bang_luong_model->xuat_danh_sach_ngan_hang_khac($thang, $nam);

        $titles = [
            'stt' => 'STT',
            'noi_dung' => 'Nội dung tùy chọn/Content (*)',
            'ten_nguoi_thu_huong' => 'Tên người thụ hưởng/Name of Beneficiary (*)',
            'so_tien' => 'Số tiền/Amount (*)',
            'ten_tai_khoan' => 'Tài khoản hưởng/Beneficiary Account (*)',
            'ten_ngan_hang' => 'Tên chi nhánh Ngân hàng thụ hưởng/Beneficiary Bank',
            'ghi_chu' => 'Ghi chú/Note'
        ];
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Xuat ra file chuyen NH khac');


        // Đặt tiêu đề cột vào hàng đầu tiên dựa trên mảng $titles
        $column = 'A';
        foreach ($titles as $key => $title) {
            $sheet->setCellValue($column . '3', $title);
            $sheet->getStyle($column . '3')->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
                'font' => [
                    'bold' => true,
                    'size' => 11,
                    'name' => 'Times New Roman',
                ],
                'alignment' => [
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                ]
            ]);
            $sheet->getStyle($column . '3')->getAlignment()->setWrapText(true);

            // Set column width based on text length
            // if ($column != 'D') {
            //     $sheet->getColumnDimension($column)->setAutoSize(true);
            // } else {
            //     $sheet->getColumnDimension($column)->setAutoSize(false);
            //     $sheet->getColumnDimension($column)->setWidth(300);
            // }
            $sheet->getColumnDimension($column)->setAutoSize(true);

            // Set a minimum column width
            $column++;
        }

        $sheet->getRowDimension(3)->setRowHeight(50);
        // Set width for column D


        $sheet->setCellValue('A1', mb_strtoupper('Danh sách chi lương', 'UTF-8'));
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'name' => 'Times New Roman',
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Đặt dữ liệu vào các hàng tiếp theo
        $row = 4;
        $stt = 0;
        foreach ($data as $item) {
            $column = 'A';
            ++$stt;
            foreach ($titles as $key => $title) {
                $sheet->setCellValue('A' . $row, $stt);
                $value = $key === 'noi_dung' ? $noi_dung : (isset($item[$key]) ? $item[$key] : '');
                $sheet->setCellValue($column . $row, $value);

                $sheet->getStyle($column . $row)->applyFromArray([
                    'font' => [
                        'size' => 11,
                        'name' => 'Times New Roman',
                    ],
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                $sheet->getColumnDimension($column)->setAutoSize(true);
                $column++;
            }
            $row++;
        }

        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Đặt tiêu đề cho file Excel
        $directory = 'uploads/export/' . date('Y') . '/';
        $filename = 'danhsach_chiluongnhkhac' . time() . '.xlsx';
        $filePath = $directory . $filename;

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);

        if (file_exists($filePath)) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Success',
                'success' => true,
                'data' => base_url($filePath)
            ], REST_INSTANCE_Controller::HTTP_OK);

            resSuccess(base_url($filePath), 'Success', REST_Controller::HTTP_OK, true);
        } else {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                'message' => 'File not found',
                'success' => false,
                'data' => null
            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }
    }

    public function xuatbangluongthang_post()
    {
        $auth = $this->getUserLogin();
        set_time_limit(300);

        $bluong_nam = commonRequest('bluongthang_nam') ? commonRequest('bluongthang_nam') : null;
        $bluong_thang = commonRequest('bluongthang_thang') ? commonRequest('bluongthang_thang') : null;

        $idBangLuongThang = $this->Hrm_bang_luong_thang_model->insert([
            'thang' => $bluong_nam . '-' . $bluong_thang . '-01',
            'tong_luong_co_ban' => 0,
            'tong_luong_thuc_nhan' => 0,
            'tong_phu_cap' => 0,
            'tong_tien_tang_ca' => 0,
            'tong_khau_tru' => 0,
            'tong_bhxh_nv' => 0,
            'tong_bhxh_dn' => 0,
            'tong_bhyt_nv' => 0,
            'tong_bhyt_dn' => 0,
            'tong_bhtn_nv' => 0,
            'tong_bhtn_dn' => 0,
            'tong_thu_nhap_chiu_thue' => 0,
            'tong_dpcd' => 0,
            'trang_thai' => Common::STATUS_BANG_LUONG_THANG['Chua_duyet']['value'],
            'trang_thai_cap_hai' => Common::STATUS_BANG_LUONG_THANG['Chua_duyet']['value'],
            'gui_mail' => false,
            'nguoi_tao_id' => $auth['ql_nguoi_dung_id'],
            'nguoi_duyet_id' => null,
            'nguoi_duyet_cap_hai_id' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => null,
            'deleted_at' => null,
        ]);

        if ($bluong_thang == 1) {
            $dauThang = date('Y-m-d', strtotime(($bluong_nam - 1) . '-12-23'));
        } else {
            $dauThang = date('Y-m-d', strtotime($bluong_nam . '-' . ($bluong_thang - 1) . '-23'));
        }
        $cuoiThang = date('Y-m-d', strtotime($bluong_nam . '-' . $bluong_thang . '-22'));

        // Nhân viên đã có bảng lương
        $nhanVienThang = $this->Hrm_bang_luong_model->select('*')
            ->where('bang_luong_thang_id', $idBangLuongThang)
            ->get();
        $ids_nhan_vien_co_bang_luong = array_column($nhanVienThang, 'id_nhan_vien');

        // Lấy tất cả nhân viên
        $nhanVien = $this->Hrm_nhan_vien_model->all();
        $ids_nhan_vien = array_column($nhanVien, 'id_nhan_vien');
        // $ids_nhan_vien_chua_co_luong = array_diff($ids_nhan_vien, $ids_nhan_vien_co_bang_luong);

        $ids_nhan_vien_chua_co_luong = $ids_nhan_vien;

        // Khởi tạo excel
        $titles = [
            '' => 'STT',
            'ma_nhan_vien' => 'Mã nhân viên',
            'ho_va_ten' => 'Họ và tên',
            'trang_thai' => 'Trạng thái',
            'ghi_chu' => 'Ghi chú',
        ];
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $sheet = $objPHPExcel->getActiveSheet();

        // Đặt tiêu đề cột vào hàng đầu tiên dựa trên mảng $titles
        $column = 'A';
        foreach ($titles as $key => $title) {
            $sheet->getRowDimension(2)->setRowHeight(25); // 25 là chiều cao tùy chỉnh (đơn vị point)
            $sheet->setCellValue($column . '2', $title);
            $sheet->getStyle($column . '2')->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN, // Kiểu viền (mỏng)
                        'color' => ['rgb' => '000000'], // Màu viền (đen)
                    ],
                ],
                'font' => [
                    'bold' => true,           // In đậm
                    // 'size' => 16,             // Tăng kích thước font
                ],
                'alignment' => [
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,  // Căn giữa nội dung
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,      // Căn giữa dọc
                ],
                'fill' => [
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => ['rgb' => 'dcdde1']
                ]
            ]);
            $column++;
        }
        $sheet->setCellValue('A1', 'Trạng thái xuất bảng lương'); // Thêm cột thông báo
        $sheet->getStyle('A1')->applyFromArray([
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
            ]
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Hợp nhất các ô từ A1 đến E1
        $sheet->mergeCells('A1:E1');

        // In đậm chữ và tăng kích thước font cho ô A1
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,           // In đậm
                'size' => 16,             // Tăng kích thước font
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,  // Căn giữa nội dung
            ]
        ]);

        $row = 3;
        $stt = 0;
        foreach ($ids_nhan_vien_chua_co_luong as $id_nhan_vien) {
            $column = 'A';
            ++$stt;
            $nhanvien = $this->Hrm_nhan_vien_model->find($id_nhan_vien);

            $result = $this->Hrm_bang_luong_model->tinh_luong($id_nhan_vien, $dauThang, $cuoiThang, $idBangLuongThang);
            // sleep(1);

            foreach ($titles as $key => $title) {
                $sheet->setCellValue('A' . $row, $stt);

                if ($key == 'ma_nhan_vien') {
                    $sheet->setCellValue($column . $row, $nhanvien['ma_nhan_vien']);
                    $sheet->getStyle($column . $row)
                        ->getAlignment()
                        ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                } else if ($key == 'ho_va_ten') {
                    $sheet->setCellValue($column . $row, $nhanvien['ho_va_ten']);
                } else if ($key == 'trang_thai') {
                    if ($result['success'] == true) {
                        $sheet->setCellValue($column . $row, 'Thành công')->getStyle($column . $row)->applyFromArray(
                            array(
                                'font' => array(
                                    'bold' => true,
                                    'size' => 11,
                                    'name' => 'Times New Roman',
                                ),
                                'alignment' => array(
                                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
                                ),
                                'fill' => [
                                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                    'color' => ['rgb' => '4cd137']
                                ]
                            )
                        );
                    } else {
                        $sheet->setCellValue($column . $row, 'Thất bại')->getStyle($column . $row)->applyFromArray(
                            array(
                                'font' => array(
                                    'bold' => true,
                                    'size' => 11,
                                    'name' => 'Times New Roman',
                                ),
                                'alignment' => array(
                                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
                                ),
                                'fill' => [
                                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                    'color' => ['rgb' => 'ff4757']
                                ]
                            )
                        );
                    }
                } else if ($key == 'ghi_chu') {
                    $sheet->setCellValue($column . $row, $result['message']);
                } else {
                    $sheet->setCellValue($column . $row, 'Test');
                }

                $sheet->getStyle($column . $row)->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THIN, // Kiểu viền (mỏng)
                            'color' => ['rgb' => '000000'], // Màu viền (đen)
                        ],
                    ],
                ]);
                // Bật wrap text cho ô 
                $sheet->getStyle($column . $row)->getAlignment()->setWrapText(true);

                // Đặt tự động điều chỉnh độ rộng cho cột 
                $sheet->getColumnDimension($column)->setAutoSize(true);

                // Đặt tự động điều chỉnh độ cao cho hàng 
                $sheet->getRowDimension($row)->setRowHeight(-1);
                $column++;
            }
            $row++;
        }

        // Đặt tiêu đề cho file Excel
        $directory = 'uploads/export/' . date('Y') . '/'; // Thư mục để lưu file
        $filename = 'xuat_luong_thang_tong_' . $bluong_thang . '-' . $bluong_nam . '_' . time() . '.xlsx'; // Tên file kèm timestamp để tránh trùng lặp
        $filePath = $directory . $filename;
        // Kiểm tra và tạo thư mục nếu chưa tồn tại
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);

        $ds_bangluong = $this->Hrm_bang_luong_model->where('bang_luong_thang_id', $idBangLuongThang)->get();
        $tongLuongCoBan = 0;
        $tong_luong_thuc_nhan = 0;
        $tong_phu_cap = 0;
        $tong_tien_tang_ca = 0;
        $tong_khau_tru = 0;
        $tong_bhxh_nv = 0;
        $tong_bhxh_dn = 0;
        $tong_bhyt_nv = 0;
        $tong_bhyt_dn = 0;
        $tong_bhtn_nv = 0;
        $tong_bhtn_dn = 0;
        $tong_thu_nhap_chiu_thue = 0;
        $tong_dpcd = 0;
        foreach ($ds_bangluong as $bl) {
            $tongLuongCoBan += $bl['luong_co_ban'];
            $tong_luong_thuc_nhan += $bl['luong_thuc_nhan'];
            $tong_phu_cap += $bl['tong_phu_cap'];
            $tong_tien_tang_ca += $bl['tien_tang_ca'];
            $tong_khau_tru += $bl['khau_tru'];
            $tong_bhxh_nv += $bl['bhxh'];
            $tong_bhyt_nv += $bl['bhyt'];
            $tong_bhtn_nv += $bl['bhtn'];
            $tong_thu_nhap_chiu_thue += $bl['thu_nhap_chiu_thue'];
            $tong_dpcd += $bl['dpcd'];
        }

        $dsBaoHiemDong = $this->Hrm_bao_hiem_dong_model
            ->select('*')
            ->where('thang', $bluong_nam . '-' . $bluong_thang . '-01')
            ->get();

        foreach ($dsBaoHiemDong as $bhd) {
            $tong_bhxh_dn += $bhd['bhxh_dn'];
            $tong_bhyt_dn += $bhd['bhyt_dn'];
            $tong_bhtn_dn += $bhd['bhtn_dn'];
        }

        $this->Hrm_bang_luong_thang_model->where('bang_luong_thang_id', $idBangLuongThang)->update([
            'tong_luong_co_ban' => $tongLuongCoBan,
            'tong_luong_thuc_nhan' => $tong_luong_thuc_nhan,
            'tong_phu_cap' => $tong_phu_cap,
            'tong_tien_tang_ca' => $tong_tien_tang_ca,
            'tong_khau_tru' => $tong_khau_tru,
            'tong_bhxh_nv' => $tong_bhxh_nv,
            'tong_bhxh_dn' => $tong_bhxh_dn,
            'tong_bhyt_nv' => $tong_bhyt_nv,
            'tong_bhyt_dn' => $tong_bhyt_dn,
            'tong_bhtn_nv' => $tong_bhtn_nv,
            'tong_bhtn_dn' => $tong_bhtn_dn,
            'tong_thu_nhap_chiu_thue' => $tong_thu_nhap_chiu_thue,
            'tong_dpcd' => $tong_dpcd,
        ]);

        if (file_exists($filePath)) {
            $this->createLog('Export', 'Export danh sách đăng ký làm thêm', NULL, 'Export danh sách đăng ký làm thêm', 'hrm_dang_ky_lam_them');
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Success',
                'success' => true,
                'data' => base_url($filePath)
            ], REST_INSTANCE_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                'message' => 'File not found',
                'success' => false,
                'data' => null
            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }
        // exit;
    }

    public function calculateSalary_post()
    {
        // Dữ liệu giả định - bạn nên lấy từ CSDL hoặc hệ thống chấm công/lương thực tế
        $data = [
            [
                'mY'                => date('m/Y'),
                'ma_nhan_vien'      => 'EMP2025',
                'ho_va_ten'         => 'Trương Khánh',
                'don_vi'            => 'Phòng Kế toán',
                'so_tai_khoan'      => '0123xxxx4567',
                'ngan_hang'         => 'Vietcombank',
                'so_tien_nhan'      => number_format(12000000, 0, ',', '.') . ' VNĐ',
                'ngay_chuyen_khoan' => date('d/m/Y'),
                'email'             => 'truongkhanh0202kt@gmail.com'
            ],
            [
                'mY'                => date('m/Y'),
                'ma_nhan_vien'      => 'EMP2025',
                'ho_va_ten'         => 'Tô Thiện Khôi',
                'don_vi'            => 'Trung tâm phát triển và ứng dụng phần mềm',
                'so_tai_khoan'      => '0123xxxx4567',
                'ngan_hang'         => 'Viettinbank',
                'so_tien_nhan'      => number_format(12000000, 0, ',', '.') . ' VNĐ',
                'ngay_chuyen_khoan' => date('d/m/Y'),
                'email'             => 'thienkhoi2805@gmail.com'
            ]
        ];

        // Gọi hàm gửi email với dữ liệu đã chuẩn bị
        foreach ($data as $employee) {
            $this->sendSalary_post($employee);
        }
    }

    private function sendSalary_post($data)
    {
        // Tải template email với đầy đủ thông tin
        $message = $this->load->view('email/luong_template.php', $data, true);


        $user_info = $data['email']; // Địa chỉ email người nhận
        $subject = "ĐH Nam Cần Thơ chuyển lương tháng " . $data['mY'];


        if (!send_email($user_info, $subject, $message)) {
            echo ('Gửi mail ' . $data['ho_va_ten'] . ' thất bại');
        } else {
            echo ('Gửi mail ' . $data['ho_va_ten'] . ' thành công');
        }
    }


    public function xuatbangluong_post()
    {
        set_time_limit(300);

        $bluong_nam = commonRequest('bluong_nam') ? commonRequest('bluong_nam') : null;
        $bluong_thang = commonRequest('bluong_thang') ? commonRequest('bluong_thang') : null;
        $idBangLuongThang = commonRequest('idBangLuongThang') ? commonRequest('idBangLuongThang') : null;

        // $dauThang = date('Y-m-d', strtotime($bluong_nam . '-' . $bluong_thang . '-01'));
        // $cuoiThang = date('Y-m-t', strtotime($bluong_nam . '-' . $bluong_thang . '-01'));

        $dauThang = date('Y-m-d', strtotime($bluong_nam . '-' . ($bluong_thang - 1) . '-23'));
        $cuoiThang = date('Y-m-t', strtotime($bluong_nam . '-' . $bluong_thang . '-22'));

        // Nhân viên đã có bảng lương
        $nhanVienThang = $this->Hrm_bang_luong_model->select('*')
            ->where('bang_luong_thang_id', $idBangLuongThang)
            ->get();
        $ids_nhan_vien_co_bang_luong = array_column($nhanVienThang, 'id_nhan_vien');

        // Nhân viên chưa có bảng lương
        $nhanVien = $this->Hrm_nhan_vien_model->all();
        // $nhanVien = $this->Hrm_nhan_vien_model
        //     ->select('*')
        //     ->get(10);

        $ids_nhan_vien = array_column($nhanVien, 'id_nhan_vien');
        $ids_nhan_vien_chua_co_luong = array_diff($ids_nhan_vien, $ids_nhan_vien_co_bang_luong);

        // Khởi tạo excel
        $titles = [
            '' => 'STT',
            'ma_nhan_vien' => 'Mã nhân viên',
            'ho_va_ten' => 'Họ và tên',
            'trang_thai' => 'Trạng thái',
            'ghi_chu' => 'Ghi chú',
        ];
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $sheet = $objPHPExcel->getActiveSheet();

        // Đặt tiêu đề cột vào hàng đầu tiên dựa trên mảng $titles
        $column = 'A';
        foreach ($titles as $key => $title) {
            $sheet->getRowDimension(2)->setRowHeight(25); // 25 là chiều cao tùy chỉnh (đơn vị point)
            $sheet->setCellValue($column . '2', $title);
            $sheet->getStyle($column . '2')->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN, // Kiểu viền (mỏng)
                        'color' => ['rgb' => '000000'], // Màu viền (đen)
                    ],
                ],
                'font' => [
                    'bold' => true,           // In đậm
                    // 'size' => 16,             // Tăng kích thước font
                ],
                'alignment' => [
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,  // Căn giữa nội dung
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,      // Căn giữa dọc
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => ['rgb' => 'dcdde1']
                ]
            ]);
        }
        $sheet->setCellValue('A1', 'Trạng thái xuất bảng lương'); // Thêm cột thông báo
        $sheet->getStyle('A1')->applyFromArray([
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
            ]
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Hợp nhất các ô từ A1 đến E1
        $sheet->mergeCells('A1:E1');

        // In đậm chữ và tăng kích thước font cho ô A1
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,           // In đậm
                'size' => 16,             // Tăng kích thước font
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,  // Căn giữa nội dung
            ]
        ]);

        $row = 3;
        $stt = 0;
        foreach ($ids_nhan_vien_chua_co_luong as $id_nhan_vien) {
            $column = 'A';
            ++$stt;
            $nhanvien = $this->Hrm_nhan_vien_model->find($id_nhan_vien);

            $result = $this->Hrm_bang_luong_model->tinh_luong($id_nhan_vien, $dauThang, $cuoiThang, $idBangLuongThang);
            // sleep(1);

            foreach ($titles as $key => $title) {
                $sheet->setCellValue('A' . $row, $stt);

                if ($key == 'ma_nhan_vien') {
                    $sheet->setCellValue($column . $row, $nhanvien['ma_nhan_vien']);
                    $sheet->getStyle($column . $row)
                        ->getAlignment()
                        ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                } else if ($key == 'ho_va_ten') {
                    $sheet->setCellValue($column . $row, $nhanvien['ho_va_ten']);
                } else if ($key == 'trang_thai') {
                    if ($result['success'] == true) {
                        $sheet->setCellValue($column . $row, 'Thành công')->getStyle($column . $row)->applyFromArray(
                            array(
                                'font' => array(
                                    'bold' => true,
                                    'size' => 11,
                                    'name' => 'Times New Roman',
                                ),
                                'alignment' => array(
                                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
                                ),
                                'fill' => [
                                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                    'color' => ['rgb' => '4cd137']
                                ]
                            )
                        );
                    } else {
                        $sheet->setCellValue($column . $row, 'Thất bại')->getStyle($column . $row)->applyFromArray(
                            array(
                                'font' => array(
                                    'bold' => true,
                                    'size' => 11,
                                    'name' => 'Times New Roman',
                                ),
                                'alignment' => array(
                                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
                                ),
                                'fill' => [
                                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                    'color' => ['rgb' => 'ff4757']
                                ]
                            )
                        );
                    }
                } else if ($key == 'ghi_chu') {
                    $sheet->setCellValue($column . $row, $result['message']);
                } else {
                    $sheet->setCellValue($column . $row, 'Test');
                }

                $sheet->getStyle($column . $row)->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THIN, // Kiểu viền (mỏng)
                            'color' => ['rgb' => '000000'], // Màu viền (đen)
                        ],
                    ],
                ]);
                // Bật wrap text cho ô 
                $sheet->getStyle($column . $row)->getAlignment()->setWrapText(true);

                // Đặt tự động điều chỉnh độ rộng cho cột 
                $sheet->getColumnDimension($column)->setAutoSize(true);

                // Đặt tự động điều chỉnh độ cao cho hàng 
                $sheet->getRowDimension($row)->setRowHeight(-1);
                $column++;
            }
            $row++;
        }

        // Đặt tiêu đề cho file Excel
        $directory = 'uploads/export/' . date('Y') . '/'; // Thư mục để lưu file
        $filename = 'xuat_luong_thang_' . $bluong_thang . '-' . $bluong_nam . '_' . time() . '.xlsx'; // Tên file kèm timestamp để tránh trùng lặp
        $filePath = $directory . $filename;
        // Kiểm tra và tạo thư mục nếu chưa tồn tại
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);

        $ds_bangluong = $this->Hrm_bang_luong_model->where('bang_luong_thang_id', $idBangLuongThang)->get();
        $tongLuongCoBan = 0;
        $tong_luong_thuc_nhan = 0;
        $tong_phu_cap = 0;
        $tong_tien_tang_ca = 0;
        $tong_khau_tru = 0;
        $tong_bhxh_nv = 0;
        $tong_bhxh_dn = 0;
        $tong_bhyt_nv = 0;
        $tong_bhyt_dn = 0;
        $tong_bhtn_nv = 0;
        $tong_bhtn_dn = 0;
        $tong_thu_nhap_chiu_thue = 0;
        $tong_dpcd = 0;
        foreach ($ds_bangluong as $bl) {
            $tongLuongCoBan += $bl['luong_co_ban'];
            $tong_luong_thuc_nhan += $bl['luong_thuc_nhan'];
            $tong_phu_cap += $bl['tong_phu_cap'];
            $tong_tien_tang_ca += $bl['tien_tang_ca'];
            $tong_khau_tru += $bl['khau_tru'];
            $tong_bhxh_nv += $bl['bhxh'];
            $tong_bhyt_nv += $bl['bhyt'];
            $tong_bhtn_nv += $bl['bhtn'];
            $tong_thu_nhap_chiu_thue += $bl['thu_nhap_chiu_thue'];
            $tong_dpcd += $bl['dpcd'];
        }

        $dsBaoHiemDong = $this->Hrm_bao_hiem_dong_model
            ->select('*')
            ->where('thang', $bluong_nam . '-' . $bluong_thang . '-01')
            ->get();

        foreach ($dsBaoHiemDong as $bhd) {
            $tong_bhxh_dn += $bhd['bhxh_dn'];
            $tong_bhyt_dn += $bhd['bhyt_dn'];
            $tong_bhtn_dn += $bhd['bhtn_dn'];
        }

        $this->Hrm_bang_luong_thang_model->where('bang_luong_thang_id', $idBangLuongThang)->update([
            'tong_luong_co_ban' => $tongLuongCoBan,
            'tong_luong_thuc_nhan' => $tong_luong_thuc_nhan,
            'tong_phu_cap' => $tong_phu_cap,
            'tong_tien_tang_ca' => $tong_tien_tang_ca,
            'tong_khau_tru' => $tong_khau_tru,
            'tong_bhxh_nv' => $tong_bhxh_nv,
            'tong_bhxh_dn' => $tong_bhxh_dn,
            'tong_bhyt_nv' => $tong_bhyt_nv,
            'tong_bhyt_dn' => $tong_bhyt_dn,
            'tong_bhtn_nv' => $tong_bhtn_nv,
            'tong_bhtn_dn' => $tong_bhtn_dn,
            'tong_thu_nhap_chiu_thue' => $tong_thu_nhap_chiu_thue,
            'tong_dpcd' => $tong_dpcd,
        ]);

        if (file_exists($filePath)) {
            $this->createLog('Export', 'Export danh sách đăng ký làm thêm', NULL, 'Export danh sách đăng ký làm thêm', 'hrm_dang_ky_lam_them');
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Success',
                'success' => true,
                'data' => base_url($filePath)
            ], REST_INSTANCE_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                'message' => 'File not found',
                'success' => false,
                'data' => null
            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }
        // exit;
    }

    public function exportLuongThang_get()
    {
        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;
        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];
        $fromDate = commonRequest('fromDate') ? commonRequest('fromDate') : null;
        $toDate = commonRequest('toDate') ? commonRequest('toDate') : null;
        $nhanvienid = commonRequest('nhanvien') ? commonRequest('nhanvien') : null;

        $bluong_thang = commonRequest('bluong_thang') ? commonRequest('bluong_thang') : null;
        $bluong_nam = commonRequest('bluong_nam') ? commonRequest('bluong_nam') : null;

        $objPHPExcel = new PHPExcel();

        // $data = $this->Hrm_bang_luong_model->getListExportBangluong($start, $length, $searchValue, $searchKey, $fromDate, $toDate, $nhanvienid);
        $dataSheet0  = $this->Hrm_bang_luong_model->getListExportBangluongSheet0($start, $length, $searchValue, $searchKey, $bluong_thang, $bluong_nam);
        // $titlesColSh0 = $this->getExcelColumnSheet0();
        $objPHPExcel->setActiveSheetIndex(0);
        $sheet = $objPHPExcel->getActiveSheet();

        // Đặt tiêu đề  cột vào hàng đầu tiên dựa trên mảng $titles
        if ($dataSheet0) {


            $objPHPExcel->createSheet();
            $objPHPExcel->setActiveSheetIndex(0);
            $sheet2 = $objPHPExcel->getActiveSheet();
            $sheet2->setTitle('Luong');
            $titles = $this->getExcelColumnSheet0();


            //Group theo Đơn vị
            $grouped = [];
            foreach ($dataSheet0 as $dataRow) {
                $ten_don_vi = $dataRow['ten_don_vi'];
                if (!isset($grouped[$ten_don_vi])) {
                    $grouped[$ten_don_vi] = [];
                }
                $grouped[$ten_don_vi][] = $dataRow;
            }

            //Xuất file đã phân theo đơn vị
            $rowInd = 6;
            $sttDonViInd = 1;
            foreach ($grouped as $tenDonVi => $dsNhanVien) {

                // Ghi tên đơn vị vào dòng đầu tiên
                $objPHPExcel->getActiveSheet()->setCellValue('A' . $rowInd, $sttDonViInd . '. ' . $tenDonVi);
                // $objPHPExcel->getActiveSheet()->getStyle('A' . $rowInd)->getFont()->setBold(true);
                $sheet->getStyle('A' . $rowInd)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14.5,
                        'name' => 'Times New Roman',
                        'color'     => array(
                            'rgb' => '0000FF'
                        )
                    ],
                    'alignment' => [
                        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
                        'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    ]
                ]);
                $rowInd++;

                $sttInd = 1;
                // Duyệt danh sách nhân viên
                foreach ($dsNhanVien as $item) {
                    $colInd = 'A';
                    foreach ($titles as $key => $title) {
                        $sheet2->setCellValue('A' . $rowInd, $sttInd);
                        $sheet2->setCellValue($colInd . $rowInd, isset($item[$key]) ? $item[$key] : "");
                        $sheet2->getStyle($colInd . $rowInd)->applyFromArray([
                            'borders' => [
                                'allborders' => [
                                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                                    'color' => ['rgb' => '000000'],
                                ],
                            ],
                            'alignment' => [
                                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                                'wrap' => true,
                            ],
                            'font' => [
                                'size' => 14.5,
                                'name' => 'Times New Roman',
                            ],
                        ]);
                        // Bật wrap text cho ô 
                        $sheet2->getStyle($colInd . $rowInd)->getAlignment()->setWrapText(true);

                        // Đặt tự động điều chỉnh độ rộng cho cột 
                        // $sheet2->getcolIndDimension($colInd)->setAutoSize(true);

                        // Đặt tự động điều chỉnh độ cao cho hàng 
                        $sheet2->getRowDimension($rowInd)->setRowHeight(-1);
                        $colInd++;
                    }
                    $rowInd++;
                    $sttInd++;
                }
                ++$sttDonViInd;
                // Cách ra một dòng nếu cần
                $rowInd++;
            }





            // Set some stuff
            $sheet2->setCellValue('A1', 'Trường Đại học Nam Cần Thơ');
            $sheet2->setCellValue('A2', 'Phòng Tài chính - Kế hoạch');
            $sheet2->mergeCells('A3:AM3');
            $sheet2->setCellValue('A3', 'BẢNG LƯƠNG CÁN BỘ - NHÂN VIÊN - GIẢNG VIÊN THÁNG ' . $bluong_thang . '/' . $bluong_nam);

            // In đậm chữ và tăng kích thước font cho ô A1
            $this->formatSheet0($sheet2);
            // Đặt dữ liệu vào các hàng tiếp theo

            // foreach ($dataSheet0 as $item) {
            //     $column = 'A';
            //     ++$stt;
            //     foreach ($titles as $key => $title) {
            //         $sheet2->setCellValue('A' . $row, $stt);
            //         $sheet2->setCellValue($column . $row, isset($item[$key]) ? $item[$key] : "");
            //         $sheet2->getStyle($column . $row)->applyFromArray([
            //             'borders' => [
            //                 'allborders' => [
            //                     'style' => PHPExcel_Style_Border::BORDER_THIN, // Kiểu viền (mỏng)
            //                     'color' => ['rgb' => '000000'], // Màu viền (đen)
            //                 ],
            //             ],
            //         ]);
            //         // Bật wrap text cho ô 
            //         $sheet2->getStyle($column . $row)->getAlignment()->setWrapText(true);

            //         // Đặt tự động điều chỉnh độ rộng cho cột 
            //         // $sheet2->getColumnDimension($column)->setAutoSize(true);

            //         // Đặt tự động điều chỉnh độ cao cho hàng 
            //         $sheet2->getRowDimension($row)->setRowHeight(-1);
            //         $column++;
            //     }
            //     $row++;
            // }
        }

        // Đặt tiêu đề cho file Excel
        $directory = 'uploads/export/' . date('Y') . '/'; // Thư mục để lưu file
        $filename = 'bangluong_' . time() . '.xlsx'; // Tên file kèm timestamp để tránh trùng lặp
        $filePath = $directory . $filename;
        // Kiểm tra và tạo thư mục nếu chưa tồn tại
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);

        if (file_exists($filePath)) {
            $this->createLog('Export', 'Export bảng lương', NULL, 'Export bảng lương', 'hrm_bang_luong');
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Success',
                'success' => true,
                'data' => base_url($filePath)
            ], REST_INSTANCE_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                'message' => 'File not found',
                'success' => false,
                'data' => null
            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }
        // exit;
    }

    public function exportLuongViettin_get()
    {
        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;
        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];
        $fromDate = commonRequest('fromDate') ? commonRequest('fromDate') : null;
        $toDate = commonRequest('toDate') ? commonRequest('toDate') : null;
        $nhanvienid = commonRequest('nhanvien') ? commonRequest('nhanvien') : null;

        $bluong_thang = commonRequest('bluong_thang') ? commonRequest('bluong_thang') : null;
        $bluong_nam = commonRequest('bluong_nam') ? commonRequest('bluong_nam') : null;
        $bank = commonRequest('bank') ? commonRequest('bank') : null;
        $ndck = commonRequest('noidung') ? commonRequest('noidung') : null;

        $objPHPExcel = new PHPExcel();

        $dataSheet2  = $this->Hrm_bang_luong_model->getListExportBangluongSheet2($start, $length, $searchValue, $searchKey, $bluong_thang, $bluong_nam, $bank);
        $titlesColSh0 = $this->getExcelColumnSheet0();
        $objPHPExcel->setActiveSheetIndex(0);
        $sheet = $objPHPExcel->getActiveSheet();

        // Đặt tiêu đề  cột vào hàng đầu tiên dựa trên mảng $titles
        if ($dataSheet2) {

            $objPHPExcel->createSheet();
            $objPHPExcel->setActiveSheetIndex(0);
            $sheet2 = $objPHPExcel->getActiveSheet();
            $sheet2->setTitle('Luong');
            $titles = $this->getExcelColumnSheet2();


            $rowInd = 3;
            $sttInd = 1;

            foreach ($dataSheet2 as $item) {
                $colInd = 'A';
                foreach ($titles as $key => $title) {
                    // $sheet2->setCellValue('A' . $rowInd, $sttInd);
                    // $sheet2->setCellValue($colInd . $rowInd, isset($item[$key]) ? $item[$key] : "");
                    // if ($title == 'noidungck') {
                    //     $sheet2->setCellValue($colInd . $rowInd, isset($ndck) ? $ndck : "");
                    // }
                    if ($key == '') {
                        // Cột STT
                        $sheet2->setCellValue($colInd . $rowInd, $sttInd);
                    } elseif ($key == 'noidungck') {
                        // Cột nội dung chuyển khoản từ biến riêng
                        $sheet2->setCellValue($colInd . $rowInd, isset($ndck) ? $ndck : 'ssss');
                    } else {
                        // Các cột bình thường từ $item
                        $sheet2->setCellValue($colInd . $rowInd, isset($item[$key]) ? $item[$key] : '');
                    }
                    $sheet2->getStyle($colInd . $rowInd)->applyFromArray([
                        'borders' => [
                            'allborders' => [
                                'style' => PHPExcel_Style_Border::BORDER_THIN,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                        'alignment' => [
                            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                            'wrap' => true,
                        ],
                        'font' => [
                            'size' => 12.5,
                            'name' => 'Times New Roman',
                        ],
                    ]);
                    // Bật wrap text cho ô 
                    $sheet2->getStyle($colInd . $rowInd)->getAlignment()->setWrapText(true);

                    // Đặt tự động điều chỉnh độ rộng cho cột 
                    // $sheet2->getcolIndDimension($colInd)->setAutoSize(true);

                    // Đặt tự động điều chỉnh độ cao cho hàng 
                    $sheet2->getRowDimension($rowInd)->setRowHeight(-1);
                    $colInd++;
                }
                $rowInd++;
                $sttInd++;
            }

            // Set some stuff           
            $this->formatSheet2($sheet2, $rowInd);
        }

        // Đặt tiêu đề cho file Excel
        $directory = 'uploads/export/' . date('Y') . '/'; // Thư mục để lưu file
        $filename = 'bangluong_' . time() . '.xlsx'; // Tên file kèm timestamp để tránh trùng lặp
        $filePath = $directory . $filename;
        // Kiểm tra và tạo thư mục nếu chưa tồn tại
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);

        if (file_exists($filePath)) {
            $this->createLog('Export', 'Export bảng lương', NULL, 'Export bảng lương', 'hrm_bang_luong');
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Success',
                'success' => true,
                'data' => base_url($filePath)
            ], REST_INSTANCE_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                'message' => 'File not found',
                'success' => false,
                'data' => null
            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }
        // exit;
    }

    function int_to_roman($number)
    {
        $map = [
            'M'  => 1000,
            'CM' => 900,
            'D'  => 500,
            'CD' => 400,
            'C'  => 100,
            'XC' => 90,
            'L'  => 50,
            'XL' => 40,
            'X'  => 10,
            'IX' => 9,
            'V'  => 5,
            'IV' => 4,
            'I'  => 1
        ];
        $returnValue = '';
        while ($number > 0) {
            foreach ($map as $roman => $int) {
                if ($number >= $int) {
                    $number -= $int;
                    $returnValue .= $roman;
                    break;
                }
            }
        }
        return $returnValue;
    }


    private function getExcelColumn()
    {
        $cols = [
            '' => 'STT',
            'ho_va_ten' => 'Tên nhân viên',
            'thang' => 'Tháng',
            'luong_co_ban' => 'Lương cơ bản',
            'tong_phu_cap' => 'Tổng phụ cấp',
            'tien_tang_ca' => 'Tiền tăng ca',
            'khau_tru' => 'Khấu trừ',
            'luong_thuc_nhan' => 'Lương thực nhận',
            'ngay_tao' => 'Ngày tạo'
        ];
        return $cols;
    }
    private function getExcelColumnSheet0()
    {
        $cols = [
            '' => 'STT',
            'ngan_hang_hd' => 'Tên ngân hàng',
            'ngan_hang_vt' => 'Tên ngân hàng',
            'ngan_hang_khac' => 'Tên ngân hàng',
            'ho_va_ten' => 'Tên nhân viên',
            'tencvu' => 'Tên chức vụ',
            'trinh_do_dt' => 'Học hàm, học vị',
            'nganh_dt' => 'Chuyên ngành',

            'tong_gio_ngay_nghi' => 'Tổng giờ ngày nghĩ',
            'tong_gio_ngay_le' => 'Tổng giờ ngày lễ',
            'tong_gio_ngay_thuong' => 'Tổng giờ ngày thường',
            'ngay_lam_chinh_thuc' => 'Ngày vào làm chính thức',
            'so_thang_lam_viec' => 'Thăm niên (tháng)',

            'luong_co_ban' => 'Lương cơ bản',
            'tong_pc' => 'Tổng phụ cấp',
            'pc_khac' => 'Phụ cấp khác',
            'ngoai_gio' => 'Ngoài giờ',
            'tong_luong_pc' => 'Tổng lương và Phụ cấp',
            'tong_thu_nhap' => 'Tổng thu nhập',

            'cp_baohiem' => 'Đóng bảo hiểm',
            'dpcd' => 'Đóng công đoàn',
            'khau_tru_gc' => 'Khấu trừ gia cảnh',
            'giamtru_khac' => 'Giảm trừ khác',
            'thu_nhap_chiu_thue' => 'Thu nhập chịu thuế',
            'thue_tncn' => 'Thuế TNCN',

            'tam_ung' => 'Tạm ứng',
            'hoan_thue_tncn_bs' => 'Hoàn thuế TNCN bổ sung',
            'nop_thu_tncn_bs' => 'Hoàn thuế TNCN bổ sung',
            'luong_thuc_nhan_vnd' => 'Lương thực nhận',
            'hinh_thuc_tt' => 'Hình thức thanh toán',
            'tk_ngan_hang' => 'Số tk ngân hàng',

            'ngan_hang' => 'Ngân hàng',
            'mst_ca_nhan' => 'Mã số thuế',
            'ngay_sinh' => 'Ngày sinh',
            'cccd_so' => 'CCCD',
            'cccd_ngay_cap' => 'Ngày cấp',
            'cccd_noi_cap' => 'Nơi cấp',
            'ten_cvu' => 'Chức vụ để lọc',
            'gioi_tinh' => 'Giới tính để lọc',
            'ten_don_vi' => 'Tên đơn vị',
            // 'tien_tang_ca' => 'Tiền tăng ca',
            // 'khau_tru' => 'Khấu trừ',
            // 'luong_thuc_nhan' => 'Lương thực nhận',
        ];
        return $cols;
    }

    private function getExcelColumnSheet1()
    {
        //Sheet nhận tiền mặt
    }
    private function getExcelColumnSheet2()
    {
        //Sheet nhận Viettin
        $cols = [
            '' => 'STT',
            'id_nhan_vien' => 'Tên ngân hàng',
            'noidungck' => 'Nội dung chuyển khoản',
            'ten_chu_tai_khoan' => 'Tên người thụ hưởng',
            'luong_thuc_nhan_vnd' => 'Lương thực nhận',
            'tk_ngan_hang' => 'Số Tài khoản ngân hàng',
            'ngan_hang' => 'Tên Ngân hàng',
            'ghi_chu' => 'Ghi chú',
        ];
        return $cols;
    }


    private function formatSheet0($sheet)
    {
        $sheet->getStyle('A1:A2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14.5,
                'name' => 'Times New Roman',
                'color'     => array(
                    'rgb' => '000000'
                )
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,  // Căn giữa nội dung
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,  // Căn giữa nội dung
            ]
        ]);
        $sheet->getStyle('A3')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 25,
                'name' => 'Times New Roman',
                'color'     => array(
                    'rgb' => '000000'
                )
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,  // Căn giữa nội dung
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,  // Căn giữa nội dung
            ]
        ]);

        $headerCells = [
            'A4:A5' => 'STT',
            'B4:B5' => 'HDBank',
            'C4:C5' => 'ViettinBank',
            'D4:D5' => 'Khác',
            'E4:E5' => 'Họ và Tên',
            'F4:F5' => 'Chức vụ',
            'G4:G5' => 'Học hàm, học vị',
            'H4:H5' => 'Chuyên ngành',

            'I4:I5' => 'Ngoài giờ CN (ngày)',
            'J4:J5' => 'Ngoài giờ Lễ (ngày)',
            'K4:K5' => 'Ngoài giờ khác (ngày)',
            'L4:L5' => 'Ngày vào làm chính thức',
            'M4:M5' => 'Thâm niên (năm)',

            'N4:R4' => 'Các khoản thu nhập',
            'S4:S5' => 'Tổng thu nhập',
            'T4:W4' => 'Các khoản giảm trừ',

            'X4:X5' => 'Thu nhập chịu Thuế TNCN',
            'Y4:Y5' => 'Thuế TNCN',
            'Z4:Z5' => 'Tạm ứng lương/Tạm ứng',
            'AA4:AA5' => 'Hoàn thuế TNCN bổ sung năm 2023',
            'AB4:AB5' => 'Nộp thuế TNCN bổ sung năm 2023',
            'AC4:AC5' => 'Thực nhận (vnđ)',
            'AD4:AD5' => 'Hình thức TT',
            'AE4:AE5' => 'Số tài khoản',
            'AF4:AF5' => 'Ngân hàng',

            'AG4:AG5' => 'Mã số thuế',
            'AH4:AH5' => 'Ngày tháng năm sinh',
            'AI4:AI5' => 'Số Căn cước',
            'AJ4:AJ5' => 'Ngày cấp',
            'AK4:AK5' => 'Nơi cấp',
            'AL4:AL5' => 'Lọc chức vụ',
            'AM4:AM5' => 'Lọc giới tính',
        ];

        $sheet->setCellValue('N5', 'Mức lương chính');
        $sheet->setCellValue('O5', 'Phụ cấp');
        $sheet->setCellValue('P5', 'Phụ cấp trợ lý/Khác');
        $sheet->setCellValue('Q5', 'Ngoài giờ');
        $sheet->setCellValue('R5', 'Tổng lương + Phụ cấp');
        $sheet->getStyle('N5:R5')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14.5,
                'name' => 'Times New Roman',
            ],
            'borders' => [
                'allborders' => [
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                'wrap' => true,
            ]
        ]);
        $sheet->setCellValue('T5', 'BHXH + YT + TN (10.5%)');
        $sheet->setCellValue('U5', 'ĐPCĐ (1%)');
        $sheet->setCellValue('V5', 'Giảm trừ gia cảnh');
        $sheet->setCellValue('W5', 'Giảm trừ khác');
        $sheet->getStyle('T5:W5')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14.5,
                'name' => 'Times New Roman',
            ],
            'borders' => [
                'allborders' => [
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                'wrap' => true,
            ]
        ]);

        foreach ($headerCells as $range => $value) {
            $sheet->mergeCells($range);
            // Lấy ô đầu tiên để đặt giá trị (ví dụ: từ 'A4:A5' lấy 'A4')
            $cell = explode(':', $range)[0];
            $sheet->setCellValue($cell, $value);

            $sheet->getStyle($range)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 14.5,
                    'name' => 'Times New Roman',
                ],
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'wrap' => true,
                ]
            ]);
        }

        //Set Height & Width
        $sheet->getDefaultRowDimension()->setRowHeight(15);

        $sheet->getRowDimension(3)->setRowHeight(30); // Dòng 1 cao 30px

        $sheet->getColumnDimension('A')->setWidth(9);
        $sheet->getColumnDimension('B')->setWidth(12.5);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(9);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(9);
        $sheet->getColumnDimension('G')->setWidth(9);
        $sheet->getColumnDimension('H')->setWidth(10.5);
        $sheet->getColumnDimension('I')->setWidth(9);
        $sheet->getColumnDimension('K')->setWidth(9);
        $sheet->getColumnDimension('L')->setWidth(14);
        $sheet->getColumnDimension('M')->setWidth(9);
        $sheet->getColumnDimension('N')->setWidth(15);
        $sheet->getColumnDimension('O')->setWidth(15);
        $sheet->getColumnDimension('P')->setWidth(15);
        $sheet->getColumnDimension('Q')->setWidth(15);
        $sheet->getColumnDimension('R')->setWidth(15);
        $sheet->getColumnDimension('S')->setWidth(15);
        $sheet->getColumnDimension('T')->setWidth(15);
        $sheet->getColumnDimension('U')->setWidth(15);
        $sheet->getColumnDimension('V')->setWidth(15);
        $sheet->getColumnDimension('W')->setWidth(9);
        $sheet->getColumnDimension('X')->setWidth(12);
        $sheet->getColumnDimension('Y')->setWidth(14);
        $sheet->getColumnDimension('Z')->setWidth(14);
        $sheet->getColumnDimension('AA')->setWidth(12);
        $sheet->getColumnDimension('AB')->setWidth(12);
        $sheet->getColumnDimension('AC')->setWidth(12);
        $sheet->getColumnDimension('AD')->setWidth(9);
        $sheet->getColumnDimension('AE')->setWidth(15);
        $sheet->getColumnDimension('AF')->setWidth(14);
        $sheet->getColumnDimension('AG')->setWidth(14);
        $sheet->getColumnDimension('AI')->setWidth(14);
        $sheet->getColumnDimension('AK')->setWidth(14);
        $sheet->getColumnDimension('AL')->setWidth(14);
        $sheet->getColumnDimension('AN')->setWidth(9);
        $sheet->getColumnDimension('AM')->setWidth(9);

        $sheet->getStyle('C')->getFont()->setBold(true);
    }
    private function formatSheet2($sheet, $footerIndex)
    {

        // Dòng tiêu đề A1
        $richText = new PHPExcel_RichText();
        $dscl = $richText->createTextRun("DANH SÁCH CHI LƯƠNG\n");
        $dscl->getFont()->setBold(true)->setSize(14)->setName('Arial');

        $spl = $richText->createTextRun("SALARY PAYMENT LIST\n");
        $spl->getFont()->setBold(true)->setSize(14)->setName('Arial');

        $tct = $richText->createTextRun("Tên công ty: TRƯỜNG ĐẠI HỌC NAM CẦN THƠ\n");
        $tct->getFont()->setItalic(true)->setSize(11)->setName('Arial');

        $tkc = $richText->createTextRun("Tài khoản chuyển: 112000113404\n");
        $tkc->getFont()->setItalic(true)->setSize(11)->setName('Arial');

        $today = date('d/m/Y');
        $ncl = $richText->createTextRun("Ngày chi lương: {$today}");
        $ncl->getFont()->setItalic(true)->setSize(11)->setName('Arial');

        $sheet->getCell('A1')->setValue($richText);
        $sheet->getStyle('A1')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $sheet->mergeCells('A1:H1');

        // Các cột B2 đến H2
        function setHeaderRichText($sheet, $cell, $viText, $enText)
        {
            $rich = new PHPExcel_RichText();
            $top = $rich->createTextRun($viText . "\n");
            $top->getFont()->setBold(true)->setSize(11)->setName('Arial');
            $bottom = $rich->createTextRun($enText);
            $bottom->getFont()->setBold(true)->setSize(11)->setName('Arial');
            $sheet->setCellValue($cell, $rich);
        }

        // Đặt tiêu đề từng cột
        setHeaderRichText($sheet, 'A2', 'STT', '');
        setHeaderRichText($sheet, 'B2', 'Mã nhân viên/', 'StaffCode');
        setHeaderRichText($sheet, 'C2', 'Nội dung tùy chọn/', 'Content');
        setHeaderRichText($sheet, 'D2', 'Tên người thụ hưởng/', 'Name of Beneficiary (*)');
        setHeaderRichText($sheet, 'E2', 'Số tiền/', 'Amount (*)');
        setHeaderRichText($sheet, 'F2', 'Số tài khoản/', 'Account No. (*)');
        setHeaderRichText($sheet, 'G2', 'Tên ngân hàng/', 'Name of Bank (*)');
        setHeaderRichText($sheet, 'H2', 'Ghi chú/', 'Note');
        $sheet->getStyle('B2:H2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2:H2')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $sheet->getStyle('B2:H2')->getAlignment()->setWrapText(true);


        $sheet->getStyle('A2:H2')->applyFromArray([
            // 'font' => [
            //     'bold' => true,
            //     'size' => 14.5,
            //     'name' => 'Times New Roman',
            // ],
            'borders' => [
                'allborders' => [
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                'wrap' => true,
            ]
        ]);

        //Set Height & Width
        $sheet->getDefaultRowDimension()->setRowHeight(15);

        $sheet->getRowDimension(1)->setRowHeight(84); // Dòng 1 cao
        $sheet->getRowDimension(2)->setRowHeight(30); // Dòng 2 cao

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(28);
        $sheet->getColumnDimension('D')->setWidth(32);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(24);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(18);

        $sheet->getStyle('C')->getFont()->setBold(true);

        $footerIndex += 2;
        $sheet->setCellValue('A' . $footerIndex, 'Lưu ý/Notes:');
        $sheet->setCellValue('A' . ++$footerIndex, '1');
        $richText = new PHPExcel_RichText();
        $bold = $richText->createTextRun('Quý khách giữ nguyên định dạng của các trường dữ liệu/hoặc format cell là text và nhập tiếng Việt không dấu/' . "\n");
        $bold->getFont()->setBold(false)->setSize(11)->setName('Times New Roman');
        $italic = $richText->createTextRun('Please keep format of Data and cell as sample');
        $italic->getFont()->setItalic(true)->setSize(11)->setName('Times New Roman');
        $sheet->getCell('B' . $footerIndex)->setValue($richText);
        $sheet->mergeCells('B' . $footerIndex . ':G' . $footerIndex);
        $sheet->getRowDimension($footerIndex)->setRowHeight(36);

        $sheet->setCellValue('A' . ++$footerIndex, '2');
        $richText = new PHPExcel_RichText();
        $bold = $richText->createTextRun('(Cột G) Tên ngân hàng)/' . "\n");
        $bold->getFont()->setBold(false)->setSize(11)->setName('Times New Roman');
        $italic = $richText->createTextRun('Column G: Name of Bank');
        $italic->getFont()->setItalic(true)->setSize(11)->setName('Times New Roman');
        $sheet->getCell('B' . $footerIndex)->setValue($richText);
        $sheet->mergeCells('B' . $footerIndex . ':G' . $footerIndex);
        $sheet->getRowDimension($footerIndex)->setRowHeight(36);

        $sheet->getStyle('B' . ($footerIndex - 1) . ':H' . ($footerIndex - 1))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $richText = new PHPExcel_RichText();
        $bold1 = $richText->createTextRun(' - Với Điện chỉ lương tới tài khoản trong hệ thống ViettinBank, Quý khách tra cứu Mã số và tên chi nhánh ngân hàng thụ hương tại Bảng danh sách ngân hàng trong nước và nhập dữ liệu theo định dạng: ');
        $bold1->getFont()->setBold(false)->setSize(11)->setName('Times New Roman');
        $italic1 = $richText->createTextRun('Mã số - Tên chi nhánh ngân hàng');
        $italic1->getFont()->setBold(true)->setSize(11)->setName('Times New Roman');
        $bold = $richText->createTextRun(' - To transfer to other domestic bank accounts: Please look up the Domestic Banks List, then enter the beneficiary bank with format: ');
        $bold->getFont()->setBold(false)->setSize(11)->setName('Times New Roman');
        $italic = $richText->createTextRun('Code - Bank name');
        $italic->getFont()->setBold(true)->setSize(11)->setName('Times New Roman');
        $sheet->getCell('B' . ++$footerIndex)->setValue($richText);
        $sheet->mergeCells('B' . $footerIndex . ':G' . $footerIndex);
        $sheet->getRowDimension($footerIndex)->setRowHeight(52);

        $sheet->setCellValue('B' . ++$footerIndex, '[Link] Danh sách ngân hàng trong nước');
        $sheet->getCell('B' . $footerIndex)->getHyperlink()->setUrl('https://ebanking.vietinbank.vn/efast/assets/misc/danh-sach-ngan-hang-trong-nuoc.xls');
        $sheet->getStyle('B' . ($footerIndex - 4) . ':B' . ($footerIndex))->getAlignment()->setWrapText(true);
        $sheet->getStyle('B' . ($footerIndex - 4) . ':B' . ($footerIndex))->getFont()->getColor()->setARGB('FF0070C0');
    }

    private function formatSheet1($sheet) {}

    public function duyetbangluongthang_post()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Không tìm thấy người dùng hợp lệ!');
        }

        $id = commonRequest('bang_luong_thang_id') ? commonRequest('bang_luong_thang_id') : null;
        $status = commonRequest('trang_thai') ? commonRequest('trang_thai') : null;
        if (!array_key_exists($status, Common::STATUS_BANG_LUONG_THANG)) {
            resBadrequest(['trang_thai' => 'Trạng thái không hợp lệ'], 'Trạng thái không hợp lệ');
        }

        $blthang = $this->Hrm_bang_luong_thang_model->find($id);
        if (!$blthang) {
            resError('Không tìm thấy bảng lương tháng!');
        }

        $result = $this->Hrm_bang_luong_thang_model
            ->where('bang_luong_thang_id', $id)
            ->update([
                'trang_thai' => $status,
                'nguoi_duyet_id' => $auth['ql_nguoi_dung_id'],
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        // $dsBangLuong = $this->Hrm_bang_luong_model
        //     ->where('bang_luong_thang_id', $id)
        //     ->get();
        // foreach ($dsBangLuong as $bl) {
        //     $hopdong = $this->db->select('*')
        //         ->from('hrm_hop_dong')
        //         ->where('ngay_bat_dau <=', date('Y-m-d')) // Hợp đồng phải có hiệu lực trước hoặc bằng hôm nay
        //         ->where('deleted_at IS NULL')
        //         ->where('id_nhan_vien', $bl['id_nhan_vien'])
        //         ->where('dang_hieu_luc', 1) // Hợp đồng đang hiệu lực
        //         ->group_start()
        //         ->where('ngay_ket_thuc >=', date('Y-m-d')) // Hợp đồng chưa hết hạn
        //         ->or_where('ngay_ket_thuc IS NULL') // Hoặc không có ngày kết thúc
        //         ->group_end()
        //         ->order_by('ngay_bat_dau', 'DESC')
        //         ->get()
        //         ->row_array(); // Lấy hợp đồng mới nhất trước

        //     $muc_luong_bao_hiem = $hopdong['muc_luong_bao_hiem'];

        //     //Bảo hiểm
        //     $nvBaoHiem = $this->db
        //         ->select('*')
        //         ->from('hrm_nhan_vien_bao_hiem')
        //         ->where('id_nhan_vien', $bl['id_nhan_vien'])
        //         ->get()
        //         ->row_array();

        //     $bhxh = 0;
        //     $bhyt = 0;
        //     $bhtn = 0;
        //     $bhxh_dn = 0;
        //     $bhyt_dn = 0;
        //     $bhtn_dn = 0;
        //     $tile_tong_dn_dong = 0;
        //     $tile_tong_dn_dong = 0;
        //     if ($nvBaoHiem && $nvBaoHiem['ti_le_dong']) {
        //         $bhxh = $muc_luong_bao_hiem * ($nvBaoHiem['ti_le_dong'] / 100);
        //         $tile_tong_nv_dong = $nvBaoHiem['ti_le_dong'];

        //         if ($nvBaoHiem['ti_le_dong_dn']) {
        //             $bhxh_dn = ($nvBaoHiem['ti_le_dong_dn'] / 100) * $muc_luong_bao_hiem;
        //             $bhyt_dn = 0;
        //             $bhtn_dn = 0;
        //             $tile_tong_dn_dong = $nvBaoHiem['ti_le_dong_dn'];
        //         }
        //     } else if ($hopdong['id_ty_le_bao_hiem']) {
        //         $tylebaohiem = $this->db->where('id_ty_le_bao_hiem', $hopdong['id_ty_le_bao_hiem'])
        //             ->get('hrm_ty_le_bao_hiem')
        //             ->row_array();

        //         if ($tylebaohiem) {
        //             $bhxh = $muc_luong_bao_hiem * ($tylebaohiem['bhxh_nv'] / 100);
        //             $bhyt = $muc_luong_bao_hiem * ($tylebaohiem['bhyt_nv'] / 100);
        //             $bhtn = $muc_luong_bao_hiem * ($tylebaohiem['bhtn_nv'] / 100);

        //             $bhxh_dn = ($tylebaohiem['bhxh_dn'] / 100) * $muc_luong_bao_hiem;
        //             $bhyt_dn = ($tylebaohiem['bhyt_dn'] / 100) * $muc_luong_bao_hiem;
        //             $bhtn_dn = ($tylebaohiem['bhtn_dn'] / 100) * $muc_luong_bao_hiem;
        //             $tile_tong_dn_dong = $tylebaohiem['bhxh_dn'] + $tylebaohiem['bhyt_dn'] + $tylebaohiem['bhtn_dn'];
        //             $tile_tong_nv_dong = $tylebaohiem['bhxh_nv'] + $tylebaohiem['bhyt_nv'] + $tylebaohiem['bhtn_nv'];
        //         }
        //     }

        //     // Quá trình đóng bảo hiểm
        //     list($bluong_nam, $bluong_thang, $bluong_ngay) = explode('-', $bl['thang']);
        //     $dauThang = date('Y-m-d', strtotime($bluong_nam . '-' . ($bluong_thang - 1) . '-23'));
        //     $cuoiThang = date('Y-m-d', strtotime($bluong_nam . '-' . $bluong_thang . '-22'));
        //     $nhanvienbaohiem = $this->db->where('id_nhan_vien', $bl['id_nhan_vien'])->get('hrm_nhan_vien_bao_hiem')->row_array();
        //     if ($tile_tong_nv_dong) {
        //         $this->db->insert('hrm_bao_hiem_dong', [
        //             'id_nhan_vien' => $bl['id_nhan_vien'],
        //             'thang' =>  $bl['thang'],
        //             'tile_bhxh_nv' => $tylebaohiem['bhxh_nv'] ?? 0,
        //             'tile_bhyt_nv' => $tylebaohiem['bhyt_nv'] ?? 0,
        //             'tile_bhtn_nv' => $tylebaohiem['bhtn_nv'] ?? 0,
        //             'tile_tong_nv_dong' => $tile_tong_nv_dong,
        //             'tile_tong_dn_dong' => $tile_tong_dn_dong,
        //             'tong_nv_dong' => $bhxh + $bhyt + $bhtn,
        //             'muc_luong_dong' => $muc_luong_bao_hiem,
        //             'bhxh_nv' => $bhxh,
        //             'bhyt_nv' => $bhyt,
        //             'bhtn_nv' => $bhtn,
        //             'bhxh_dn' => $bhxh_dn,
        //             'bhyt_dn' => $bhyt_dn,
        //             'bhtn_dn' => $bhtn_dn,
        //             'tong_dn_dong' => $bhxh_dn + $bhyt_dn + $bhtn_dn,
        //             'tong_dong' => $bhxh + $bhyt + $bhtn + $bhxh_dn + $bhyt_dn + $bhtn_dn,
        //             'trang_thai' => 'Chua_nop',
        //             'tu_thang' =>  $dauThang,
        //             'den_thang' => $cuoiThang,
        //             'so_so_bhxh' => $nhanvienbaohiem['so_so_bhxh'] ?? '',
        //             'ma_bhxh' => $nhanvienbaohiem['ma_bhxh'] ?? '',
        //             'ma_tinh_cap' => $nhanvienbaohiem['ma_tinh_cap'] ?? '',
        //             'ten_tinh_cap' => $nhanvienbaohiem['ten_tinh_cap'] ?? '',
        //             'so_the_bhyt' => $nhanvienbaohiem['so_the_bhyt'] ?? '',
        //             'ngay_het_han' => $nhanvienbaohiem['ngay_het_han'] ?? null,
        //             'noi_dk_kcb' => $nhanvienbaohiem['noi_dk_kcb'] ?? '',
        //             'ms_noi_kcb' => $nhanvienbaohiem['ms_noi_kcb'] ?? ''
        //         ]);
        //     }
        // }

        if (!$result) {
            resError('Có lỗi khi duyệt bảng lương tháng!');
        }

        $blthangNew = $this->Hrm_bang_luong_thang_model->find($id);

        resSuccess($blthangNew, 'Duyệt lương cấp 1 thành công!');
    }

    public function duyetbangluongthangcaphai_post()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Không tìm thấy người dùng hợp lệ!');
        }

        $id = commonRequest('bang_luong_thang_id') ? commonRequest('bang_luong_thang_id') : null;
        $status = commonRequest('trang_thai') ? commonRequest('trang_thai') : null;
        if (!array_key_exists($status, Common::STATUS_BANG_LUONG_THANG)) {
            resBadrequest(['trang_thai' => 'Trạng thái không hợp lệ'], 'Trạng thái không hợp lệ');
        }

        $blthang = $this->Hrm_bang_luong_thang_model->find($id);
        if (!$blthang) {
            resError('Không tìm thấy bảng lương tháng!');
        }

        $result = $this->Hrm_bang_luong_thang_model
            ->where('bang_luong_thang_id', $id)
            ->update([
                'trang_thai_cap_hai' => $status,
                'nguoi_duyet_cap_hai_id' => $auth['ql_nguoi_dung_id'],
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        if ($status == Common::STATUS_BANG_LUONG_THANG['Da_duyet']['value']) {
            $dsBangLuong = $this->Hrm_bang_luong_model
                ->where('bang_luong_thang_id', $id)
                ->get();
            foreach ($dsBangLuong as $bl) {
                $hopdong = $this->db->select('*')
                    ->from('hrm_hop_dong')
                    ->where('ngay_bat_dau <=', date('Y-m-d')) // Hợp đồng phải có hiệu lực trước hoặc bằng hôm nay
                    ->where('deleted_at IS NULL')
                    ->where('id_nhan_vien', $bl['id_nhan_vien'])
                    ->where('dang_hieu_luc', 1) // Hợp đồng đang hiệu lực
                    ->group_start()
                    ->where('ngay_ket_thuc >=', date('Y-m-d')) // Hợp đồng chưa hết hạn
                    ->or_where('ngay_ket_thuc IS NULL') // Hoặc không có ngày kết thúc
                    ->group_end()
                    ->order_by('ngay_bat_dau', 'DESC')
                    ->get()
                    ->row_array(); // Lấy hợp đồng mới nhất trước

                $muc_luong_bao_hiem = $hopdong['muc_luong_bao_hiem'];

                //Bảo hiểm
                $nvBaoHiem = $this->db
                    ->select('*')
                    ->from('hrm_nhan_vien_bao_hiem')
                    ->where('id_nhan_vien', $bl['id_nhan_vien'])
                    ->get()
                    ->row_array();

                $bhxh = 0;
                $bhyt = 0;
                $bhtn = 0;
                $bhxh_dn = 0;
                $bhyt_dn = 0;
                $bhtn_dn = 0;
                $tile_tong_nv_dong = 0;
                $tile_tong_dn_dong = 0;
                if ($nvBaoHiem && $nvBaoHiem['ti_le_dong']) {
                    $bhxh = $muc_luong_bao_hiem * ($nvBaoHiem['ti_le_dong'] / 100);
                    $tile_tong_nv_dong = $nvBaoHiem['ti_le_dong'];

                    if ($nvBaoHiem['ti_le_dong_dn']) {
                        $bhxh_dn = ($nvBaoHiem['ti_le_dong_dn'] / 100) * $muc_luong_bao_hiem;
                        $bhyt_dn = 0;
                        $bhtn_dn = 0;
                        $tile_tong_dn_dong = $nvBaoHiem['ti_le_dong_dn'];
                    }
                } else if ($hopdong['id_ty_le_bao_hiem']) {
                    $tylebaohiem = $this->db->where('id_ty_le_bao_hiem', $hopdong['id_ty_le_bao_hiem'])
                        ->get('hrm_ty_le_bao_hiem')
                        ->row_array();

                    if ($tylebaohiem) {
                        $bhxh = $muc_luong_bao_hiem * ($tylebaohiem['bhxh_nv'] / 100);
                        $bhyt = $muc_luong_bao_hiem * ($tylebaohiem['bhyt_nv'] / 100);
                        $bhtn = $muc_luong_bao_hiem * ($tylebaohiem['bhtn_nv'] / 100);

                        $bhxh_dn = ($tylebaohiem['bhxh_dn'] / 100) * $muc_luong_bao_hiem;
                        $bhyt_dn = ($tylebaohiem['bhyt_dn'] / 100) * $muc_luong_bao_hiem;
                        $bhtn_dn = ($tylebaohiem['bhtn_dn'] / 100) * $muc_luong_bao_hiem;
                        $tile_tong_dn_dong = $tylebaohiem['bhxh_dn'] + $tylebaohiem['bhyt_dn'] + $tylebaohiem['bhtn_dn'];
                        $tile_tong_nv_dong = $tylebaohiem['bhxh_nv'] + $tylebaohiem['bhyt_nv'] + $tylebaohiem['bhtn_nv'];
                    }
                }

                // Quá trình đóng bảo hiểm
                list($bluong_nam, $bluong_thang, $bluong_ngay) = explode('-', $bl['thang']);
                $dauThang = date('Y-m-d', strtotime($bluong_nam . '-' . ($bluong_thang - 1) . '-23'));
                $cuoiThang = date('Y-m-d', strtotime($bluong_nam . '-' . $bluong_thang . '-22'));
                if ($tile_tong_nv_dong) {
                    $this->db->insert('hrm_bao_hiem_dong', [
                        'id_nhan_vien' => $bl['id_nhan_vien'],
                        'thang' =>  $bl['thang'],
                        'tile_bhxh_nv' => $tylebaohiem['bhxh_nv'] ?? 0,
                        'tile_bhyt_nv' => $tylebaohiem['bhyt_nv'] ?? 0,
                        'tile_bhtn_nv' => $tylebaohiem['bhtn_nv'] ?? 0,
                        'tile_tong_nv_dong' => $tile_tong_nv_dong,
                        'tile_tong_dn_dong' => $tile_tong_dn_dong,
                        'tong_nv_dong' => $bhxh + $bhyt + $bhtn,
                        'muc_luong_dong' => $muc_luong_bao_hiem,
                        'bhxh_nv' => $bhxh,
                        'bhyt_nv' => $bhyt,
                        'bhtn_nv' => $bhtn,
                        'bhxh_dn' => $bhxh_dn,
                        'bhyt_dn' => $bhyt_dn,
                        'bhtn_dn' => $bhtn_dn,
                        'tong_dn_dong' => $bhxh_dn + $bhyt_dn + $bhtn_dn,
                        'tong_dong' => $bhxh + $bhyt + $bhtn + $bhxh_dn + $bhyt_dn + $bhtn_dn,
                        'trang_thai' => 'Chua_nop',
                        'tu_thang' =>  $dauThang,
                        'den_thang' => $cuoiThang,
                        'so_so_bhxh' => $nvBaoHiem['so_so_bhxh'] ?? '',
                        'ma_bhxh' => $nvBaoHiem['ma_bhxh'] ?? '',
                        'ma_tinh_cap' => $nvBaoHiem['ma_tinh_cap'] ?? '',
                        'ten_tinh_cap' => $nvBaoHiem['ten_tinh_cap'] ?? '',
                        'so_the_bhyt' => $nvBaoHiem['so_the_bhyt'] ?? '',
                        'ngay_het_han' => $nvBaoHiem['ngay_het_han'] ?? null,
                        'noi_dk_kcb' => $nvBaoHiem['noi_dk_kcb'] ?? '',
                        'ms_noi_kcb' => $nvBaoHiem['ms_noi_kcb'] ?? ''
                    ]);

                    $this->Hrm_bang_luong_model
                        ->where('id_bang_luong', $bl['id_bang_luong'])
                        ->update([
                            'bhxh' => $bhxh,
                            'bhyt' => $bhyt,
                            'bhtn' => $bhtn
                        ]);
                }
            }

            $tongLuongCoBan = 0;
            $tong_luong_thuc_nhan = 0;
            $tong_phu_cap = 0;
            $tong_tien_tang_ca = 0;
            $tong_khau_tru = 0;
            $tong_bhxh_nv = 0;
            $tong_bhxh_dn = 0;
            $tong_bhyt_nv = 0;
            $tong_bhyt_dn = 0;
            $tong_bhtn_nv = 0;
            $tong_bhtn_dn = 0;
            $tong_thu_nhap_chiu_thue = 0;
            $tong_dpcd = 0;
            foreach ($dsBangLuong as $bl) {
                $tongLuongCoBan += $bl['luong_co_ban'];
                $tong_luong_thuc_nhan += $bl['luong_thuc_nhan'];
                $tong_phu_cap += $bl['tong_phu_cap'];
                $tong_tien_tang_ca += $bl['tien_tang_ca'];
                $tong_khau_tru += $bl['khau_tru'];
                $tong_bhxh_nv += $bl['bhxh'];
                $tong_bhyt_nv += $bl['bhyt'];
                $tong_bhtn_nv += $bl['bhtn'];
                $tong_thu_nhap_chiu_thue += $bl['thu_nhap_chiu_thue'];
                $tong_dpcd += $bl['dpcd'];
            }

            $dsBaoHiemDong = $this->Hrm_bao_hiem_dong_model
                ->select('*')
                ->where('thang', $bluong_nam . '-' . $bluong_thang . '-01')
                ->get();

            foreach ($dsBaoHiemDong as $bhd) {
                $tong_bhxh_dn += $bhd['bhxh_dn'];
                $tong_bhyt_dn += $bhd['bhyt_dn'];
                $tong_bhtn_dn += $bhd['bhtn_dn'];
            }

            $this->Hrm_bang_luong_thang_model->where('bang_luong_thang_id', $id)->update([
                'tong_luong_co_ban' => $tongLuongCoBan,
                'tong_luong_thuc_nhan' => $tong_luong_thuc_nhan,
                'tong_phu_cap' => $tong_phu_cap,
                'tong_tien_tang_ca' => $tong_tien_tang_ca,
                'tong_khau_tru' => $tong_khau_tru,
                'tong_bhxh_nv' => $tong_bhxh_nv,
                'tong_bhxh_dn' => $tong_bhxh_dn,
                'tong_bhyt_nv' => $tong_bhyt_nv,
                'tong_bhyt_dn' => $tong_bhyt_dn,
                'tong_bhtn_nv' => $tong_bhtn_nv,
                'tong_bhtn_dn' => $tong_bhtn_dn,
                'tong_thu_nhap_chiu_thue' => $tong_thu_nhap_chiu_thue,
                'tong_dpcd' => $tong_dpcd,
            ]);
        } else if ($status == Common::STATUS_BANG_LUONG_THANG['Huy_duyet']['value']) {
            // Xóa các bảo hiểm đóng và bảng lương liên quan
            // $this->Hrm_bang_luong_model->where('bang_luong_thang_id', $id)->delete();

            // $this->Hrm_bao_hiem_dong_model
            //     ->where('thang', $blthang['thang'])
            //     ->delete();

            $this->db->where('bang_luong_thang_id', $id)
                ->update('hrm_bang_luong', ['deleted_at' => date('Y-m-d H:i:s')]);

            $this->db->delete('hrm_bao_hiem_dong', ['thang' => $blthang['thang']]);
        }

        if (!$result) {
            resError('Có lỗi khi duyệt bảng lương tháng!');
        }

        $blthangNew = $this->Hrm_bang_luong_thang_model->find($id);

        resSuccess($blthangNew, 'Duyệt lương cấp 2 thành công!');
    }

    public function mailbangluongthang_get($id)
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Không tìm thấy người dùng hợp lệ!');
        }

        $blthang = $this->Hrm_bang_luong_thang_model->find($id);
        if (!$blthang) {
            resError('Không tìm thấy bảng lương tháng!');
        }

        $data = [];
        $dsBangLuong = $this->Hrm_bang_luong_model
            ->select('
                hrm_bang_luong.luong_thuc_nhan,
                hrm_nhan_vien.ho_va_ten,
                hrm_nhan_vien.ma_nhan_vien,
                hrm_nhan_vien.email,
                e_don_vi.ten_don_vi,
                hrm_nhan_vien_luong.ngan_hang,
                hrm_nhan_vien_luong.tk_ngan_hang,
            ')
            ->leftJoin('hrm_nhan_vien', 'hrm_bang_luong.id_nhan_vien = hrm_nhan_vien.id_nhan_vien')
            ->leftJoin('hrm_nhan_vien_luong', 'hrm_bang_luong.id_nhan_vien = hrm_nhan_vien_luong.id_nhan_vien')
            ->leftJoin('e_don_vi', 'hrm_nhan_vien.id_don_vi_cong_tac = e_don_vi.id_don_vi')
            ->where('bang_luong_thang_id', $id)
            ->get();

        foreach ($dsBangLuong as $bl) {
            $data[] = [
                'mY'                => date('m/Y'),
                'ma_nhan_vien'      => $bl['ma_nhan_vien'],
                'ho_va_ten'         => $bl['ho_va_ten'],
                'don_vi'            => $bl['ten_don_vi'],
                'so_tai_khoan'      => $bl['tk_ngan_hang'],
                'ngan_hang'         => $bl['ngan_hang'],
                'so_tien_nhan'      => number_format($bl['luong_thuc_nhan'], 0, ',', '.') . ' VNĐ',
                'ngay_chuyen_khoan' => date('d/m/Y'),
                'email'             => $bl['email']
            ];
        }

        // Gọi hàm gửi email với dữ liệu đã chuẩn bị
        // foreach ($data as $employee) {
        //     $this->sendSalary_post($employee);
        // }

        $this->Hrm_bang_luong_thang_model->where('bang_luong_thang_id', $id)
            ->update([
                'gui_mail' => true
            ]);

        $blthangNew = $this->Hrm_bang_luong_thang_model->find($id);

        resSuccess($blthangNew, 'Duyệt lương thành công!');
    }

    public function delete_post()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Không tìm thấy người dùng!');
        }

        $ids = commonRequest('ids');
        $this->db->trans_start();
        $bluongthang = $this->Hrm_bang_luong_thang_model->whereIn('bang_luong_thang_id', $ids)->get();
        if (count($ids) != count($bluongthang)) {
            resError('Dữ liệu không hợp lệ');
        }

        foreach ($bluongthang as $np) {
            if (($np['trang_thai'] == Common::STATUS_NGHI_PHEP['Da_duyet']['value']) && ($np['trang_thai_cap_hai'] == Common::STATUS_NGHI_PHEP['Da_duyet']['value'])) {
                resError('Không thể xóa bảng lương tháng đã duyệt', REST_Controller::HTTP_BAD_REQUEST);
            }
        }

        $this->Hrm_bang_luong_thang_model->whereIn('bang_luong_thang_id', $ids)->update([
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);

        $this->createLog('delete', 'Xóa bảng lương tháng', $bluongthang,  null, 'hrm_bang_luong_thang');
        $this->db->trans_commit();
        resSuccess(null, 'Xóa thành công');
    }
}
