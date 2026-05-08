<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property DB_query_builder $db
 * @property Demo_phong_ban_model $Demo_phong_ban_model
 */
class Phongban extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Phân quyền: Cho phép tất cả các thao tác CRUD không cần kiểm tra quyền
        // $this->permissionMiddleware();

        $this->load->helper('url');
        $this->load->model(['Demo_phong_ban_model']);
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

        $data = $this->Demo_phong_ban_model->getAll(
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
            'ten_phong_ban' => commonRequest('ten_phong_ban') ? commonRequest('ten_phong_ban') : null,
            'ten_viet_tat' => commonRequest('ten_viet_tat') ? commonRequest('ten_viet_tat') : null,
            'ten_tieng_anh' => commonRequest('ten_tieng_anh') ? commonRequest('ten_tieng_anh') : null,
            'ma_don_vi' => commonRequest('ma_don_vi') ? commonRequest('ma_don_vi') : null,
            'email' => commonRequest('email') ? commonRequest('email') : null,
            'truong' => commonRequest('truong') ? commonRequest('truong') : null,
            'id_don_vi_goc' => commonRequest('id_don_vi_goc') ? commonRequest('id_don_vi_goc') : null,
        ];

        $rules = [
            'ten_phong_ban' => 'required',
        ];

        $customMessages = [
            'ten_phong_ban.required' => 'Tên phòng ban bắt buộc nhập',
        ];
        
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();
        $result = $this->Demo_phong_ban_model->create($data);
        $this->createLog('create', 'Tạo mới phòng ban', null, $result, 'demo_phong_ban');
        $this->db->trans_commit();
        
        resSuccess($result, 'Thêm mới phòng ban thành công!');
    }

    public function show_get($id)
    {
        $result = $this->Demo_phong_ban_model->find($id);
        if (!$result)
            resError('Không tìm thấy phòng ban!');
        resSuccess($result, 'Lấy chi tiết phòng ban thành công!');
    }

    public function update_post($id)
    {
        $this->load->library(['Validator']);

        $oldData = $this->Demo_phong_ban_model->find($id);
        if (!$oldData)
            resError('Không tìm thấy phòng ban!');

        // Tích hợp inline edit
        if (commonRequest('inline_edit')) {
            $this->db->trans_start();
            $this->Demo_phong_ban_model
                ->where('id', $id)
                ->update([commonRequest('column') => commonRequest('value')]);
            $this->db->trans_commit();
            resSuccess($this->Demo_phong_ban_model->find($id));
        }

        $data = [
            'ten_phong_ban' => commonRequest('ten_phong_ban') ? commonRequest('ten_phong_ban') : null,
            'ten_viet_tat' => commonRequest('ten_viet_tat') ? commonRequest('ten_viet_tat') : null,
            'ten_tieng_anh' => commonRequest('ten_tieng_anh') ? commonRequest('ten_tieng_anh') : null,
            'ma_don_vi' => commonRequest('ma_don_vi') ? commonRequest('ma_don_vi') : null,
            'email' => commonRequest('email') ? commonRequest('email') : null,
            'truong' => commonRequest('truong') ? commonRequest('truong') : null,
            'id_don_vi_goc' => commonRequest('id_don_vi_goc') ? commonRequest('id_don_vi_goc') : null,
        ];

        $rules = ['ten_phong_ban' => 'required'];
        $customMessages = ['ten_phong_ban.required' => 'Tên phòng ban bắt buộc nhập'];
        
        $validator = new Validator();
        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();
        $this->Demo_phong_ban_model->where('id', $id)->update($data);
        $newData = $this->Demo_phong_ban_model->find($id);
        $this->createLog('update', 'Cập nhật phòng ban', $oldData, $newData, 'demo_phong_ban');
        $this->db->trans_commit();

        resSuccess($newData, 'Cập nhật phòng ban thành công!');
    }

    public function delete_delete($id)
    {
        $oldData = $this->Demo_phong_ban_model->find($id);
        if (!$oldData)
            resError('Không tìm thấy phòng ban!');

        $this->db->trans_start();
        $this->Demo_phong_ban_model->softDelete($id);
        $this->createLog('delete', 'Xóa phòng ban', $oldData, null, 'demo_phong_ban');
        $this->db->trans_commit();

        resSuccess(null, 'Xóa phòng ban thành công!');
    }

    public function export_get()
    {
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;
        $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
            'order' => commonRequest('order'),
            'columns' => commonRequest('columns')
        ] : [];

        $data = $this->Demo_phong_ban_model->getListExport($searchValue, $orderBy);

        $titles = [
            '' => 'STT',
            'ten_phong_ban' => 'Tên phòng ban',
            'ma_phong_ban' => 'Mã phòng ban',
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
        
        $sheet->setCellValue('A1', 'Danh sách phòng ban');
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
        $filename = 'phong-ban_' . time() . '.xlsx';
        $filePath = $directory . $filename;
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);

        if (file_exists($filePath)) {
            $this->createLog('Export', 'Export danh sách phòng ban', NULL, 'Export phòng ban', 'demo_phong_ban');
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
