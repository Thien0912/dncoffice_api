<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

class Baohiem extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model('Hrm_bao_hiem_model');
        $this->load->library(['Validator', 'Fileupload', 'Pxl']);
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

        $data = $this->Hrm_bao_hiem_model->getAll($start, $length, $searchValue, $orderBy, $searchKey);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
        ]);
    }

    public function create_post()
    {
        $validator = new Validator();

        $data = (array) commonRequest('data');

        $rules = [
            'id_nhan_vien' => 'required',
            'so_bhxh' => 'required',
            'ngay_bat_dau' => 'required',
            'ngay_ket_thuc' => 'required',
            'trang_thai' => 'required',
        ];

        $customMessages = [
            'id_nhan_vien.required' => 'Nhân viên không được để trống',
            'so_bhxh.required' => 'Số BHXH không được để trống',
            'ngay_bat_dau.required' => 'Ngày bắt đầu không được để trống',
            'ngay_ket_thuc.required' => 'Ngày kết thúc không được để trống',
            'trang_thai.required' => 'Trạng thái không được để trống',
        ];

        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resSuccess(['errors' => $validator->errors()], 'Success', REST_Controller::HTTP_OK, false);
        }

        $this->Hrm_bao_hiem_model->insert($data);

        resSuccess(null, 'Success', REST_Controller::HTTP_OK, true);
    }

    public function edit_get()
    {
        $id = commonRequest('id');
        $data = $this->Hrm_bao_hiem_model->getById($id);
        resSuccess($data, 'Success', REST_Controller::HTTP_OK, true);
    }

    public function update_post()
    {
        $data = commonRequest('data');
        $id = commonRequest('id');

        $this->Hrm_bao_hiem_model->where("id_bao_hiem_xa_hoi", $id)->update($data);

        resSuccess(null, 'Success', REST_Controller::HTTP_OK, true);
    }

    public function delete_get()
    {
        $id = commonRequest('id');
        $this->Hrm_bao_hiem_model->where("id_bao_hiem_xa_hoi", $id)->update(["deleted_at" => date('Y-m-d H:i:s')]);
        resSuccess(null, 'Success', REST_Controller::HTTP_OK, true);
    }

    public function canhBaoHetHanDongBHXH_get()
    {
        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;

        $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
            'order' => commonRequest('order'),
            'columns' => commonRequest('columns')
        ] : [];

        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];

        $data = $this->Hrm_bao_hiem_model->getCanhBaoHetHanDongBHXH($start, $length, $searchValue, $orderBy, $searchKey);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
        ]);
    }

    public function canhBaoHetHanDongBHXHExport_post()
    {
        $data = $this->Hrm_bao_hiem_model->getCanhBaoHetHanDongBHXH(0, -1, null, [], []);

        $titles = [
            'stt'              => 'STT',
            'ho_va_ten' => 'Họ tên nhân viên',
            'so_bhxh'          => 'Mã BHXH',
            'ngay_bat_dau'     => 'Ngày bắt đầu',
            'trang_thai'       => 'Trạng thái',
            'ngay_nghi_huu'    => 'Ngày nghỉ hưu',
            'so_ngay_con_lai'  => 'Số ngày còn lại'
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
                } elseif ($key == 'ngay_bat_dau' || $key == 'ngay_nghi_huu') {
                    $value = !empty($item[$key]) ? date('d/m/Y', strtotime($item[$key])) : '';
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
                'filename'   => 'canh_bao_het_han_dong_bhxh_' . date('Ymd_His') . '.xlsx',
                'base64'     => $base64,
                'mime_type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        ], REST_INSTANCE_Controller::HTTP_OK);
    }
}
