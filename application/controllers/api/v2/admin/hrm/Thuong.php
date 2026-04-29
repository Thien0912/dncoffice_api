<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property Hrm_thuong_model $Hrm_thuong_model
 * @property Fileupload $fileupload
 * @property Common $common
 * @property CI_Upload $upload
 * @property Pxl $pxl
 * @property DB_query_builder $db
 */

class Thuong extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model(['Hrm_thuong_model']);
        $this->load->library(['Validator', 'Fileupload', 'Common', 'Pxl', 'upload']);
    }

    public function index_get()
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

        // $data = $this->Hrm_thuong_model->getAll($start, $length, $searchValue, $orderBy, $searchKey, $fromDate, $toDate);

        $auth = $this->getUserLogin();
        $fromDate = commonRequest('fromDate') ? commonRequest('fromDate') : null;
        $toDate = commonRequest('toDate') ? commonRequest('toDate') : null;

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

        $data = $this->Hrm_thuong_model->getAll($data['start'], $data['length'], $data['searchValue'], $data['order'], $data['columns'], $data['searchKey'],  $data['fromDate'], $data['toDate'], $auth);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }

    public function create_post()
    {
        $nv_id = commonRequest('nhan_vien_id');
        $tham_nien_details = commonRequest('tham_nien_details'); // Mảng chi tiết thâm niên

        // Nếu $tham_nien_details là string dạng xuất print_r/var_export, cần chuyển thành mảng object
        if (is_string($tham_nien_details)) {
            $tham_nien_details = trim($tham_nien_details);
            if (strpos($tham_nien_details, 'Array') === 0) {
                $tham_nien_details = preg_replace('/\s*Array\s*\(/', '[', $tham_nien_details, 1);
                $tham_nien_details = preg_replace('/\)\s*$/', ']', $tham_nien_details);
                $tham_nien_details = preg_replace('/\[(\d+)\] => /', '', $tham_nien_details);
                $tham_nien_details = preg_replace('/stdClass Object\s*\(/', '(object)[', $tham_nien_details);
                $tham_nien_details = preg_replace('/\)/', ']', $tham_nien_details);
                $tham_nien_details = preg_replace('/\[(\w+)\] => /', '"$1"=>', $tham_nien_details);
                $tham_nien_details = str_replace('(object)[', '(object)[', $tham_nien_details);
                $tham_nien_details = eval('return ' . $tham_nien_details . ';');
            }
        }

        $data_tong_quat = [
            'ten_thuong'    => commonRequest('ten_thuong'),
            'loai_thuong'   => commonRequest('loai_thuong'),
            'so_tien'       => commonRequest('so_tien'),
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        $this->db->trans_start();

        $thuong_id = $this->Hrm_thuong_model->create($data_tong_quat);
        if (!$thuong_id) {
            $this->db->trans_rollback();
            resError('Lỗi khi tạo bản ghi thưởng.', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->createLog('create', '', null, $thuong_id, 'hrm_thuong');

        // Lưu chi tiết thưởng
        if (!empty($tham_nien_details) && is_array($tham_nien_details)) {
            resSuccess($tham_nien_details, 'Success', REST_Controller::HTTP_OK);
            foreach ($tham_nien_details as $detail) {
                if (is_array($detail)) {
                    $detail = (object)$detail;
                }
                $data_chi_tiet = [
                    'thuong_id'             => $thuong_id['id'],
                    'nhan_vien_id'          => $data_tong_quat['loai_thuong'] === 'chung' ? 'all' : json_encode($nv_id),
                    'tham_nien'             => $detail->tham_nien,
                    'so_tien'               => $detail->loai_dieu_kien === 'so_tien' ? $detail->so_tien_tham_nien : null,
                    'so_tien_tham_nien'     => $detail->loai_dieu_kien === 'so_tien' ? $detail->so_tien_tham_nien : null,
                    'dieukien'              => $detail->dieukien,
                    'loai_dieu_kien'        => $detail->loai_dieu_kien,
                    'thang_luong_tham_nien' => $detail->loai_dieu_kien === 'thang_luong' ? $detail->thang_luong_tham_nien : null,
                    'created_at'            => date('Y-m-d H:i:s'),
                    'updated_at'            => date('Y-m-d H:i:s'),
                ];
                $this->Hrm_thuong_model->create_chi_tiet($data_chi_tiet);
            }
        } else {
            $data_chi_tiet = [
                'thuong_id'     => $thuong_id['id'],
                'nhan_vien_id'  => $data_tong_quat['loai_thuong'] === 'chung' ? 'all' : json_encode($nv_id),
                'so_tien'       => $data_tong_quat['so_tien'],
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ];
            $this->Hrm_thuong_model->create_chi_tiet($data_chi_tiet);
        }

        // Lấy lại thông tin thưởng và chi tiết để tạo danh sách thưởng phù hợp theo thâm niên
        $this->db->where('id', $thuong_id['id']);
        $thuong = $this->db->get('hrm_thuong')->row_array();

        $this->db->where('thuong_id', $thuong_id['id']);
        $thuong_chi_tiet = $this->db->get('hrm_thuong_chi_tiet')->result_array();

        if (empty($thuong)) {
            $this->db->trans_rollback();
            resError('Không tìm thấy thông tin thưởng.', REST_Controller::HTTP_NOT_FOUND);
        }

        // Sắp xếp chi tiết thưởng theo thâm niên giảm dần
        usort($thuong_chi_tiet, function ($a, $b) {
            return $b['tham_nien'] <=> $a['tham_nien'];
        });

        $all_nhan_vien = [];
        $added_ids = [];

        foreach ($thuong_chi_tiet as $chi_tiet) {
            $conditions = [];

            if (!empty($chi_tiet['tham_nien']) && !empty($chi_tiet['dieukien'])) {
                $allowed_operators = ['=', '>', '>=', '<', '<='];
                if (in_array($chi_tiet['dieukien'], $allowed_operators)) {
                    $conditions[] = [
                        'type' => 'tham_nien',
                        'operator' => $chi_tiet['dieukien'],
                        'value' => $chi_tiet['tham_nien']
                    ];
                }
            }

            $nv_list_data = $this->get_nhan_vien_thuong_by_conditions($conditions);

            foreach ($nv_list_data['data'] as $nv) {
                if (!in_array($nv['id_nhan_vien'], $added_ids)) {
                    $nv['so_tien_thuong_phu_hop'] = $chi_tiet['so_tien'];
                    $all_nhan_vien[] = $nv;
                    $added_ids[] = $nv['id_nhan_vien'];

                    // Thêm vào bảng hrm_thuong_danh_sach
                    $data_insert = [
                        'id_nhan_vien' => $nv['id_nhan_vien'],
                        'id_thuong'    => $thuong['id'],
                        'so_tien'      => $chi_tiet['so_tien'],
                        'tham_nien_tai_thoi_diem' => $nv['tham_nien'],
                        'dieu_kien_xet' => $chi_tiet['dieukien'] . $chi_tiet['tham_nien'],
                        'created_at'   => date('Y-m-d H:i:s'),
                        'updated_at'   => date('Y-m-d H:i:s'),
                    ];
                    $this->db->insert('hrm_thuong_danh_sach', $data_insert);
                }
            }
        }

        $this->db->trans_commit();

        resSuccess(['thuong_id' => $thuong_id], 'Thêm 1 bản ghi thưởng thành công', REST_Controller::HTTP_CREATED);
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

    public function duyet_thuong_post($id)
    {
        if (empty($id)) {
            resError('ID không hợp lệ.', REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->db->where('id', $id)->update('hrm_thuong', [
            'trang_thai' => 'da_duyet'
        ]);

        resSuccess(['id' => $id], 'Duyệt thưởng thành công.');
    }

    public function huy_duyet_thuong_post($id)
    {
        if (empty($id)) {
            resError('ID không hợp lệ.', REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->db->where('id', $id)->update('hrm_thuong', [
            'trang_thai' => 'chua_duyet'
        ]);

        resSuccess(['id' => $id], 'Hủy duyệt thưởng thành công.');
    }

    public function get_nhan_vien_thuong_post()
    {
        $id = commonRequest('id');

        $all_nhan_vien = $this->get_danh_sach_nhan_vien($id);

        resSuccess($all_nhan_vien['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $all_nhan_vien['recordsTotal'],
            'recordsFiltered' => $all_nhan_vien['recordsFiltered']
        ]);
    }

    public function get_danh_sach_nhan_vien($id)
    {
        $this->db->select('ds.id, nv.id_nhan_vien,, nv.ho_va_ten, nvcv.ngay_lam_chinh_thuc, 
            TIMESTAMPDIFF(MONTH, nvcv.ngay_lam_chinh_thuc, CURDATE()) as tham_nien, vtcv.ten_cong_viec, ds.so_tien as so_tien');
        $this->db->from('hrm_thuong_danh_sach as ds');
        $this->db->join('hrm_nhan_vien as nv', 'nv.id_nhan_vien = ds.id_nhan_vien', 'left');
        $this->db->join('hrm_nhan_vien_cong_viec as nvcv', 'nvcv.id_nhan_vien = nv.id_nhan_vien', 'left');
        $this->db->join('hrm_vi_tri_cong_viec as vtcv', 'vtcv.id_vi_tri_cong_viec = nv.id_vi_tri_cong_viec', 'left');

        $this->db->where('ds.id_thuong', $id);

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE);


        $query = $this->db->get();

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $query->result_array()
        ];
    }

    public function get_nhan_vien_thuong_by_conditions($conditions = [])
    {
        $this->db->select('nv.id_nhan_vien,, nv.ho_va_ten, nvcv.ngay_lam_chinh_thuc, 
            TIMESTAMPDIFF(MONTH, nvcv.ngay_lam_chinh_thuc, CURDATE()) as tham_nien, vtcv.ten_cong_viec');
        $this->db->from('hrm_nhan_vien as nv');
        $this->db->join('hrm_nhan_vien_cong_viec as nvcv', 'nvcv.id_nhan_vien = nv.id_nhan_vien', 'left');
        $this->db->join('hrm_vi_tri_cong_viec as vtcv', 'vtcv.id_vi_tri_cong_viec = nv.id_vi_tri_cong_viec', 'left');

        if (!empty($conditions)) {
            foreach ($conditions as $cond) {
                if ($cond['type'] === 'tham_nien') {
                    $operator = $cond['operator'];
                    $value = $cond['value'];
                    $this->db->where("TIMESTAMPDIFF(MONTH, nvcv.ngay_lam_chinh_thuc, CURDATE()) {$operator}", $value);
                }
            }
        }

        $totalRecordsQuery = clone $this->db;
        $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('', FALSE);


        $query = $this->db->get();

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $query->result_array()
        ];
    }

    private function getExcelColumn()
    {
        $cols = [
            '' => 'STT',
            'id_nhan_vien' => 'ID nhân viên',
            'ho_va_ten' => 'Họ và tên',
            'so_tien' => 'Số tiền (VNĐ)',
        ];
        return $cols;
    }
    public function export_thuong_post()
    {
        $id_thuong = commonRequest('id_thuong') ? commonRequest('id_thuong') : null;
        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : -1;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;
        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];
        $fromDate = commonRequest('fromDate') ? commonRequest('fromDate') : null;
        $toDate = commonRequest('toDate') ? commonRequest('toDate') : null;

        $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
            'order' => commonRequest('order'),
            'columns' => commonRequest('columns')
        ] : [];

        $data = $this->get_danh_sach_nhan_vien($id_thuong);

        // dd($data);

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
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
                'font' => [
                    'bold' => true,
                ],
                'alignment' => [
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                ]
            ]);
            // Thêm key vào dòng 3
            $sheet->setCellValue($column . '3', $key);
            $sheet->getStyle($column . '3')->applyFromArray([
                'borders' => [
                    'allborders' => [
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
                'font' => [
                    'italic' => true,
                    'color' => ['rgb' => '888888'],
                ],
                'alignment' => [
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                ]
            ]);
            $column++;
        }
        $sheet->setCellValue('A1', 'Danh sách thưởng');

        // Hợp nhất các ô từ A1 đến H1
        $sheet->mergeCells('A1:D1');

        // In đậm chữ và tăng kích thước font cho ô A1
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,           // In đậm
                'size' => 16,             // Tăng kích thước font
                'color'     => array(
                    'rgb' => '0000FF'
                )
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,  // Căn giữa nội dung
            ]
        ]);
        // Đặt dữ liệu vào các hàng tiếp theo
        $row = 4;
        $stt = 0;
        foreach ($data['data'] as $item) {
            $column = 'A';
            ++$stt;
            foreach ($titles as $key => $title) {
                $sheet->setCellValue('A' . $row, $stt);
                // Nếu là cột số tiền thì format theo định dạng Việt Nam
                if ($key === 'so_tien_thuong_phu_hop' && isset($item[$key])) {
                    $value = number_format($item[$key], 0, '.', ',');
                } else {
                    $value = isset($item[$key]) ? $item[$key] : '';
                }

                $sheet->setCellValue($column . $row, $value);
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

        // Đặt tiêu đề cho file Excel
        $directory = 'uploads/export/' . date('Y') . '/'; // Thư mục để lưu file
        $filename = 'thuong_' . time() . '.xlsx'; // Tên file kèm timestamp để tránh trùng lặp
        $filePath = $directory . $filename;
        // Kiểm tra và tạo thư mục nếu chưa tồn tại
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);

        if (file_exists($filePath)) {
            $this->createLog('Export', 'Export danh sách thưởng', NULL, 'Export danh sách thưởng', 'hrm_thuong');
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


    public function import_thuong_post()
    {
        $file_thongtindanhsach = commonRequest('file_thongtindanhsach') ? commonRequest('file_thongtindanhsach') : null;
        $id_thuong = commonRequest('id_thuong') ? commonRequest('id_thuong') : null;

        if (!$file_thongtindanhsach) {
            resError('Không tìm thấy file import');
        }
        // Upload file
        $folderName = 'employees/import/' . date('Y') . '/' . date('m');
        $result = $this->fileupload->upload($file_thongtindanhsach, $folderName);
        $path = $result['file_path'];

        $rowKey = 3;
        $rowData = 4;
        $countSuccess = $countError = 0;

        $data = $this->pxl->importExcel($rowKey, $rowData, $path);
        // dd($data);
        // Kiểm tra có phải người dùng đang upload file rỗng hay không.
        $this->emptyData($data);

        // Đọc lại file vừa được upload
        $objPHPExcel = PHPExcel_IOFactory::load($path);
        $sheet = $objPHPExcel->getActiveSheet();
        $highestColumn = $sheet->getHighestColumn();

        $highestRow = $sheet->getHighestRow();

        $columnResult = $highestColumn . '3';
        $sheet->setCellValue($columnResult, 'KẾT QUẢ IMPORT');

        $requiredKeys = [
            'id_nhan_vien',
            'so_tien',
        ];

        // Kiểm tra xem các trường bắt buộc có bị thiếu hay không.
        $this->checkMissingFields($data, $requiredKeys, $path);

        foreach ($data as $key => $d) {
            $errors = [];

            $id_nhan_vien = isset($d['id_nhan_vien']) ? trim($d['id_nhan_vien']) : null;
            $so_tien = isset($d['so_tien']) ? str_replace([',', '.'], '', $d['so_tien']) : null;

            if (empty($id_nhan_vien)) {
                $errors[] = 'ID nhân viên không được để trống';
            }
            if (empty($so_tien) || !is_numeric($so_tien)) {
                $errors[] = 'Số tiền không hợp lệ';
            }

            // Xác định ô ghi kết quả
            // Tìm cột tiếp theo sau highestColumn để ghi kết quả
            $errorCell = $highestColumn . $rowData;

            if (!empty($errors)) {
                $errorMessage = implode("\n", $errors);
                $sheet->setCellValue($errorCell, $errorMessage);
                $sheet->getStyle($errorCell)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FF3300']
                    ],
                ]);
                $countError++;
                $rowData++;
                continue;
            } else {
                // Kiểm tra tồn tại theo id_thuong và id_nhan_vien
                $this->db->where('id_thuong', $id_thuong);
                $this->db->where('id_nhan_vien', $id_nhan_vien);
                $exists = $this->db->count_all_results('hrm_thuong_danh_sach') > 0;

                $dataInsertUpdate = [
                    'id_thuong' => $id_thuong,
                    'id_nhan_vien' => $id_nhan_vien,
                    'so_tien' => $so_tien,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                $this->db->trans_start();
                if ($exists) {
                    $this->db->where('id_thuong', $id_thuong);
                    $this->db->where('id_nhan_vien', $id_nhan_vien);
                    $this->db->update('hrm_thuong_danh_sach', $dataInsertUpdate);
                } else {
                    $dataInsertUpdate['created_at'] = date('Y-m-d H:i:s');
                    $this->db->insert('hrm_thuong_danh_sach', $dataInsertUpdate);
                }
                $this->db->trans_complete();

                $sheet->setCellValue($errorCell, 'Thành công');
                $sheet->getStyle($errorCell)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '006400']
                    ],
                ]);

                $this->createLog('Import', 'Import thưởng cho nhân viên', NULL, $dataInsertUpdate, 'hrm_thuong_danh_sach');

                $countSuccess++;
                $rowData++;
            }
        }

        $sheet->getColumnDimension($highestColumn)->setAutoSize(true);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($path);

        $filename = pathinfo($file_thongtindanhsach['name'], PATHINFO_FILENAME);
        $fileNameEncrypt = pathinfo($path, PATHINFO_FILENAME);
        $fileextension = pathinfo($file_thongtindanhsach['name'], PATHINFO_EXTENSION);

        $newFileName = $filename . '_' . date('Y-m-d') . '_' . $fileNameEncrypt . '.' . $fileextension;

        $newPath = dirname($path) . '/' . $newFileName;
        rename($path, $newPath);

        $fileContent = file_get_contents($newPath);
        $encodedContent = base64_encode($fileContent);

        resSuccess([
            'count' => [
                'countSuccess' => $countSuccess,
                'countError' => $countError,
            ],
            'file_base64' => $encodedContent,
            'file_name' => $newFileName
        ], 'Import thành công');
    }


    private function emptyData(array $data): void
    {
        $isEmpty = array_reduce($data, fn($carry, $item) => $carry && empty(array_filter($item)), true);

        if ($isEmpty) {
            resError('File đang không có dữ liệu, vui lòng kiểm tra lại!');
        }
    }
    private function checkMissingFields(array $data, array $requiredKeys, string $path = ''): void
    {
        if (!empty($data)) {
            $missing = array_diff($requiredKeys, array_keys($data[0]));

            if (!empty($missing)) {
                if ($path) unlink($path);
                resError('Thiếu trường: ' . implode(', ', $missing) . '. Vui lòng kiểm tra lại!');
            }
        }
    }

    public function update_so_tien_post()
    {
        $id_thuong = commonRequest('id_thuong');
        $so_tien = commonRequest('so_tien');


        if (empty($id_thuong) || !is_numeric($so_tien)) {
            resError('ID thưởng hoặc số tiền không hợp lệ.', REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->db->where('id', $id_thuong);
        $updated = $this->db->update('hrm_thuong_danh_sach', [
            'so_tien' => $so_tien,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        if ($updated) {
            $this->createLog('Update', 'Cập nhật số tiền thưởng', null, $id_thuong, 'hrm_thuong_danh_sach');
            resSuccess(['id_thuong' => $id_thuong, 'so_tien' => $so_tien], 'Cập nhật số tiền thành công.', REST_Controller::HTTP_OK, true);
        } else {
            resError('Cập nhật số tiền thất bại.', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create_thuong_post()
    {
        $auth = $this->getUserLogin();

        $data_tong_quat = [
            'ten_thuong'    => commonRequest('ten_thuong'),
            'loai_thuong'   => commonRequest('loai_thuong'),
            'so_tien'       => commonRequest('so_tien'),
            'noi_dung'       => commonRequest('noi_dung'),
            'created_at'    => date('Y-m-d H:i:s'),
            'created_user_id' => $auth['ql_nguoi_dung_id'],
            'updated_at'    => date('Y-m-d H:i:s'),
            'updated_user_id' => $auth['ql_nguoi_dung_id'],
        ];

        $thuong = $this->Hrm_thuong_model->create($data_tong_quat);
        if ($thuong) {
            resSuccess($thuong, 'Tạo thưởng thành công.', REST_Controller::HTTP_OK, true);
        } else {
            resError('Tạo thưởng thất bại.', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show_get($id)
    {
        $thuong = $this->Hrm_thuong_model->find($id);
        if ($thuong) {
            resSuccess($thuong, 'Lấy thưởng thành công.', REST_Controller::HTTP_OK, true);
        } else {
            resError('Lấy thưởng thất bại.', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update_post($id)
    {
        $thuong = $this->Hrm_thuong_model->find($id);

        if (!$thuong) {
            resError('Lấy thưởng thất bại.', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }

        $auth = $this->getUserLogin();
        $data_tong_quat = [
            'ten_thuong'    => commonRequest('ten_thuong'),
            'loai_thuong'   => commonRequest('loai_thuong'),
            'so_tien'       => commonRequest('so_tien'),
            'noi_dung'       => commonRequest('noi_dung'),
            'updated_at'    => date('Y-m-d H:i:s'),
            'updated_user_id' => $auth['ql_nguoi_dung_id'],
        ];

        $updated = $this->Hrm_thuong_model
            ->where('id', $id)
            ->update($data_tong_quat);
        if ($updated) {
            $thuong_danh_sach = $this->db
                ->select('*')
                ->from('hrm_thuong_danh_sach')
                ->where('id_thuong', $id)
                ->get()
                ->result_array();

            if (!empty($thuong_danh_sach)) {
                $this->db
                    ->where('id_thuong', $id)
                    ->update('hrm_thuong_danh_sach', [
                        'so_tien' => $data_tong_quat['so_tien'],
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }

            resSuccess(['id_thuong' => $id, 'data' => $data_tong_quat], 'Cập nhật thưởng thành công.', REST_Controller::HTTP_OK, true);
        } else {
            resError('Cập nhật thưởng thất bại.', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create_thuong_danh_sach_post()
    {
        $validator = new Validator();

        $id_khen_thuong = commonRequest('id_khen_thuong') ? commonRequest('id_khen_thuong') : null;
        $ids_nhan_vien = commonRequest('ids_nhan_vien') ? commonRequest('ids_nhan_vien') : null;

        if (!$ids_nhan_vien) {
            $validator->addError('', 'id_nhan_vien', 'ID nhân viên bắt buộc nhập');
        }
        if (!$id_khen_thuong) {
            $validator->addError('', 'id_dao_tao', 'ID đào tạo bắt buộc nhập');
        }

        $this->db->trans_start();
        $ds_nhan_vien_thuong_danh_sach_id = [];
        $ids_nhan_vien = explode(",", $ids_nhan_vien);

        $thuong = $this->Hrm_thuong_model->find($id_khen_thuong);
        foreach ($ids_nhan_vien as $id) {
            $this->db->insert('hrm_thuong_danh_sach', [
                'id_nhan_vien' => $id,
                'id_thuong' => $id_khen_thuong,
                'so_tien' => $thuong['so_tien'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if ($this->db->affected_rows() > 0) {
                $id_moi = $this->db->insert_id();
                $ds_nhan_vien_thuong_danh_sach_id[] = $id_moi;
            }
        }

        $thuong_danh_sach = $this->db
            ->select('hrm_thuong_danh_sach.*, hrm_thuong.ten_thuong, hrm_thuong.loai_thuong, hrm_thuong.noi_dung, hrm_thuong.trang_thai')
            ->where_in('hrm_thuong_danh_sach.id', $ds_nhan_vien_thuong_danh_sach_id)
            ->from('hrm_thuong_danh_sach')
            ->join('hrm_thuong', 'hrm_thuong.id = hrm_thuong_danh_sach.id_thuong', 'left')
            ->get()->result();

        //create log
        $this->createLog('create', 'Tạo mới thưởng danh sách', null, $thuong_danh_sach, 'hrm_thuong_danh_sach');
        $this->db->trans_commit();
        resSuccess($thuong_danh_sach, 'Thêm thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }

    public function delete_thuong_danh_sach_post()
    {
        $ids = commonRequest('ids') ? commonRequest('ids') : null;
        $khenthuong = $this->db
            ->select('*')
            ->from('hrm_thuong_danh_sach')
            ->where_in('id', $ids)
            ->get()
            ->result_array();

        if (!$khenthuong) {
            resError('Không tìm thấy khen thưởng này');
        }
        $this->db->trans_start();
        $this->db->where_in('id', $ids);
        $this->db->update('hrm_thuong_danh_sach', [
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);

        //create log
        $this->createLog('delete', 'Xóa khen thưởng', $khenthuong, null, 'hrm_thuong_danh_sach');
        $this->db->trans_commit();

        resSuccess($khenthuong, 'Xóa thành công');
    }
}
