<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property DB_query_builder $db
 * @property Demo_truong_model $Demo_truong_model
 */
class Truong extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Phân quyền: Cho phép tất cả các thao tác CRUD không cần kiểm tra quyền
        // $this->permissionMiddleware();

        $this->load->helper('url');
        $this->load->model(['Demo_truong_model']);
    }

    public function index_get()
    {
        $searchKey = [];
        if (is_string(commonRequest('searchKey'))) {
            $searchKey = json_decode(commonRequest('searchKey'), true);
        }

        $data = [
            'start' => commonRequest('start') ?? 0,
            'length' => commonRequest('length') ?? 10,
            'searchValue' => commonRequest('searchValue') ?? null,
            'order' => commonRequest('order') ?? [],
            'columns' => commonRequest('columns') ?? [],
            'searchKey' => $searchKey,
        ];

        $data = $this->Demo_truong_model->getAll(
            $data['start'], 
            $data['length'], 
            $data['searchValue'], 
            $data['order'], 
            $data['columns'], 
            $data['searchKey']
        );

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function create_post()
    {
        $this->load->library(['Validator']);

        $data = [
            'ten_truong' => commonRequest('ten_truong') ? commonRequest('ten_truong') : null,
            'ma_truong' => commonRequest('ma_truong') ? commonRequest('ma_truong') : null,
        ];

        $rules = ['ten_truong' => 'required'];
        $customMessages = ['ten_truong.required' => 'Tên trường bắt buộc nhập'];
        
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();
        $result = $this->Demo_truong_model->create($data);
        $this->createLog('create', 'Tạo mới trường', null, $result, 'demo_truong');
        $this->db->trans_commit();
        
        resSuccess($result, 'Thêm mới trường thành công!');
    }

    public function show_get($id)
    {
        $result = $this->Demo_truong_model->find($id);
        if (!$result)
            resError('Không tìm thấy trường!');
        resSuccess($result, 'Lấy chi tiết trường thành công!');
    }

    public function update_post($id)
    {
        $this->load->library(['Validator']);

        $oldData = $this->Demo_truong_model->find($id);
        if (!$oldData)
            resError('Không tìm thấy trường!');

        if (commonRequest('inline_edit')) {
            $this->db->trans_start();
            $this->Demo_truong_model
                ->where('id', $id)
                ->update([commonRequest('column') => commonRequest('value')]);
            $this->db->trans_commit();
            resSuccess($this->Demo_truong_model->find($id));
        }

        $data = [
            'ten_truong' => commonRequest('ten_truong') ? commonRequest('ten_truong') : null,
            'ma_truong' => commonRequest('ma_truong') ? commonRequest('ma_truong') : null,
        ];

        $rules = ['ten_truong' => 'required'];
        $customMessages = ['ten_truong.required' => 'Tên trường bắt buộc nhập'];
        
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();
        $this->Demo_truong_model->where('id', $id)->update($data);
        $newData = $this->Demo_truong_model->find($id);
        $this->createLog('update', 'Cập nhật trường', $oldData, $newData, 'demo_truong');
        $this->db->trans_commit();

        resSuccess($newData, 'Cập nhật trường thành công!');
    }

    public function delete_delete($id)
    {
        $oldData = $this->Demo_truong_model->find($id);
        if (!$oldData)
            resError('Không tìm thấy trường!');

        $this->db->trans_start();
        $this->Demo_truong_model->softDelete($id);
        $this->createLog('delete', 'Xóa trường', $oldData, null, 'demo_truong');
        $this->db->trans_commit();

        resSuccess(null, 'Xóa trường thành công!');
    }

    public function export_get()
    {
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;
        $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
            'order' => commonRequest('order'),
            'columns' => commonRequest('columns')
        ] : [];

        $data = $this->Demo_truong_model->getListExport($searchValue, $orderBy);

        $titles = [
            '' => 'STT',
            'ten_truong' => 'Tên trường',
            'ma_truong' => 'Mã trường',
            'mo_ta' => 'Mô tả',
        ];

        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $sheet = $objPHPExcel->getActiveSheet();

        $column = 'A';
        foreach ($titles as $key => $title) {
            $sheet->setCellValue($column . '2', $title);
            $sheet->getStyle($column . '2')->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER]
            ]);
            $column++;
        }
        
        $sheet->setCellValue('A1', 'Danh sách trường');
        $sheet->mergeCells('A1:C1');
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ]
        ]);

        $row = 3;
        $stt = 0;
        foreach ($data as $item) {
            $column = 'A';
            ++$stt;
            foreach ($titles as $key => $title) {
                $sheet->setCellValue('A' . $row, $stt);
                $sheet->setCellValue($column . $row, isset($item[$key]) ? $item[$key] : '');
                $sheet->getStyle($column . $row)->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                $sheet->getStyle($column . $row)->getAlignment()->setWrapText(true);
                $sheet->getColumnDimension($column)->setAutoSize(true);
                $sheet->getRowDimension($row)->setRowHeight(-1);
                $column++;
            }
            $row++;
        }

        $directory = 'uploads/export/' . date('Y') . '/';
        $filename = 'truong_' . time() . '.xlsx';
        $filePath = $directory . $filename;
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);

        if (file_exists($filePath)) {
            $this->createLog('Export', 'Export danh sách trường', NULL, 'Export trường', 'demo_truong');
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
}
