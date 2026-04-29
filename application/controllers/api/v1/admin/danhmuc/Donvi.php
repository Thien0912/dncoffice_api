<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property E_don_vi_model $E_don_vi_model
 * @property Fileupload $fileupload
 * @property Pxl $pxl
 */



class Donvi extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['E_don_vi_model']);
        $this->load->library(['Pxl']);
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

        $data = $this->E_don_vi_model->getAll($start, $length, $searchValue, $orderBy, $searchKey);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function theophongban_get()
    {
        $data = $this->E_don_vi_model->getAllTheoPhongBan();
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
        ]);
    }

    public function create_post()
    {
        $this->load->library(['Validator']);

        $data = [
            'ten_don_vi' => commonRequest('ten_don_vi') ? commonRequest('ten_don_vi') : null,
            'ma_don_vi' => commonRequest('ma_don_vi') ? commonRequest('ma_don_vi') : null,
            'loai' => commonRequest('loai') ? commonRequest('loai') : null,
            'email' => commonRequest('email') ? commonRequest('email') : null,
        ];

        $existEmail = $this->E_don_vi_model->checkValueExists('email', $data['email']);
        if ($existEmail) {
            resError('Email đã tồn tại!');
        }

        $rules = [
            'ten_don_vi' => 'required',
            // 'ma_don_vi' => 'required',
            'loai' => 'required',
            'email' => 'required|email',
        ];

        $customMessages = [
            'ten_don_vi.required' => 'Tên đơn vị bắt buộc nhập',
            // 'ma_don_vi.required' => 'Mã đơn vị bắt buộc nhập',
            'loai.required' => 'Loại bắt buộc nhập',
            'email.required' => 'Email đơn vị bắt buộc nhập',
            'email.email' => 'Email nhập đúng định dạng',
        ];
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();

        $result = $this->E_don_vi_model->create($data);
        $this->createLog(
            'create',
            'Tạo mới đơn vị',
            null,
            $result,
            'e_don_vi'
        );

        $this->db->trans_commit();
        resSuccess($result, 'Thêm mới đơn vị thành công!');
    }

    public function show_get($id)
    {
        $result = $this->E_don_vi_model->find($id);

        if (!$result) resError('Không tìm thấy đơn vị!');

        resSuccess($result, 'Lấy chi tiết đơn vị thành công!');
    }

    public function update_post($id)
    {
        $this->load->library(['Validator']);

        $oldData = $this->E_don_vi_model->find($id);

        if (!$oldData) resError('Không tìm thấy đơn vị!');

        $data = [
            'ten_don_vi' => commonRequest('ten_don_vi') ? commonRequest('ten_don_vi') : null,
            'ma_don_vi' => commonRequest('ma_don_vi') ? commonRequest('ma_don_vi') : null,
            'loai' => commonRequest('loai') ? commonRequest('loai') : null,
            'email' => commonRequest('email') ? commonRequest('email') : null,
        ];

        $rules = [
            'ten_don_vi' => 'required',
        ];

        $customMessages = [
            'ten_don_vi.required' => 'Tên đơn vị bắt buộc nhập',
        ];
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();

        //Lưu đơn vị
        $result = $this->E_don_vi_model
            ->where('id_don_vi', $id)
            ->update($data);

        $this->createLog(
            'create',
            'Cập nhật đơn vị',
            $oldData,
            $result,
            'e_don_vi'
        );


        $this->db->trans_commit();

        resSuccess($result, 'Cập nhật đơn vị thành công!');
    }

    private function getExcelColumn()
    {
        $cols = [
            '' => 'STT',
            'ten_don_vi' => 'Tên đơn vị',
            'ma_don_vi' => 'Mã đơn vị',
            'loai' => 'Loại',
            'email' => 'Mail',
        ];
        return $cols;
    }


    public function export_get()
    {
        $auth  = $this->getUserLogin();
        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;
        $fromDate = commonRequest('fromDate') ? commonRequest('fromDate') : null;
        $toDate = commonRequest('toDate') ? commonRequest('toDate') : null;

        $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
            'order' => commonRequest('order'),
            'columns' => commonRequest('columns')
        ] : [];

        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];

        $data = $this->E_don_vi_model->getListExport($start, $length, $searchValue, $orderBy, $searchKey, $fromDate, $toDate);

        $titles = $this->getExcelColumn();
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $sheet = $objPHPExcel->getActiveSheet();

        // Đặt tiêu đề cột vào hàng đầu tiên dựa trên mảng $titles
        $column = 'A';
        foreach ($titles as $key => $title) {
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
                ]
            ]);
            $column++;
        }
        $sheet->setCellValue('A1', 'Danh sách đơn vị'); // Thêm cột thông báo

        // Hợp nhất các ô từ A1 đến E1
        $sheet->mergeCells('A1:E1');

        // Thiết lập chiều cao cho hàng 1
        $sheet->getRowDimension(1)->setRowHeight(30);

        // In đậm chữ và tăng kích thước font cho ô A1
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,           // In đậm
                'size' => 16,             // Tăng kích thước font
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,  // Căn giữa nội dung
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,  // Căn giữa nội dung
            ]
        ]);
        // Đặt dữ liệu vào các hàng tiếp theo
        $row = 3;
        $stt = 0;
        foreach ($data as $item) {
            $column = 'A';
            ++$stt;
            foreach ($titles as $key => $title) {
                $sheet->setCellValue('A' . $row, $stt);

                if ($key == 'loai') {
                    $loaiDonVi = Common::LOAI_DON_VI;
                    foreach ($loaiDonVi as $ldv) {
                        if ($ldv['value'] == $item[$key]) {
                            $sheet->setCellValue($column . $row, $ldv['label']);
                        }
                    }
                } else {
                    $sheet->setCellValue($column . $row, isset($item[$key]) ? $item[$key] : '');
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
        $filename = 'don-vi_' . time() . '.xlsx'; // Tên file kèm timestamp để tránh trùng lặp
        $filePath = $directory . $filename;
        // Kiểm tra và tạo thư mục nếu chưa tồn tại
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);

        if (file_exists($filePath)) {
            $this->createLog('Export', 'Export danh sách đơn vị', NULL, 'Export danh sách đơn vị', 'e_don_vi');
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
}
