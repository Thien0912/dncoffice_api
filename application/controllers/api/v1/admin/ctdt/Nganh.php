<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';
require APPPATH . 'libraries/pxl/PHPExcel.php';

/**
 * @property CI_Upload $upload
 * @property Pxl $pxl
 * @property DB_query_builder $db
 * @property Nv_don_vi_model $Nv_don_vi_model
 * @property Ctdt_khoi_nganh_model $Ctdt_khoi_nganh_model
 * @property Ctdt_nganh_model $Ctdt_nganh_model
 * @property Ctdt_chuyen_nganh_model $Ctdt_chuyen_nganh_model
 * @property Sv_lop_model $Sv_lop_model
 * @property Ctdt_ke_hoach_hoc_tap_model $Ctdt_ke_hoach_hoc_tap_model
 * @property Ctdt_chuong_trinh_khung_model $Ctdt_chuong_trinh_khung_model
 * @property Ctdt_bac_dao_tao_model $Ctdt_bac_dao_tao_model
 * @property Ctdt_loai_hinh_dao_tao_model $Ctdt_loai_hinh_dao_tao_model
 * @property Ctdt_loai_bang_tot_nghiep_model $Ctdt_loai_bang_tot_nghiep_model
 */



class Nganh extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model('Ctdt_nganh_model');
        $this->load->model('Nv_don_vi_model');
        $this->load->model('Ctdt_khoi_nganh_model');
        $this->load->model(['Ctdt_bac_dao_tao_model', 'Ctdt_loai_hinh_dao_tao_model', 'Ctdt_loai_bang_tot_nghiep_model']);
        $this->load->library('Pxl');
        $this->load->library('upload');
    }

    public function index_get()
    {
        $nganh = $this->Ctdt_nganh_model->getSearch();

        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => $nganh
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function import_post()
    {
        try {

            $stop = false;
            $message = '';
            $dataList = [];
            $file_import = commonRequest('file_excel');

            if ($file_import) {

                $config['upload_path'] = 'uploads/excel/nganh/'; // Thư mục để lưu file
                $config['allowed_types'] = 'xls|xlsx';

                if (!is_dir($config['upload_path'])) {
                    mkdir($config['upload_path'], 0755, true);
                }
                //Đổi tên file
                $newFileName = pathinfo($file_import['name'], PATHINFO_FILENAME) . '-' . date('Ymd') . '-' . time() . '.' . pathinfo($file_import['name'], PATHINFO_EXTENSION);
                $config['file_name'] = $newFileName;

                $this->upload->initialize($config);

                if (!$this->upload->do_upload('file_excel')) {
                    $stop = true;
                    $message = 'Thất bại: ' . $this->upload->display_errors();
                } else {
                    $uploadData = $this->upload->data(); // Lấy dữ liệu file đã upload 
                    $fileName = $uploadData['file_name']; // Tên file 
                    //gọi đến importExcel của Pxl để xuất dữ liệu mảng 
                    $path = $config['upload_path'] . $fileName;

                    $dataList = $this->pxl->importExcel(3, 4, $path);
                    $objPHPExcel = PHPExcel_IOFactory::load($path);
                    $sheet = $objPHPExcel->getActiveSheet();
                    $highestColumn = $sheet->getHighestColumn();
                    $highestRow = $sheet->getHighestRow();

                    $columnResult = $highestColumn . '2';
                    $sheet->setCellValue($columnResult, 'RESULT');
                    $sheet->getColumnDimension($highestColumn)->setAutoSize(true);
                    $sheet->getStyle($columnResult)->applyFromArray(
                        array(
                            'font' => array(
                                'bold' => true,
                            ),
                            'alignment' => array(
                                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
                            ),
                        )
                    );

                    $range = $columnResult . ':' . $highestColumn . $highestRow;
                    $sheet->getStyle($range)->applyFromArray(
                        array(
                            'borders' => array(
                                'allborders' => array(
                                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                                    'color' => array('rgb' => '000000')
                                )
                            )
                        )
                    );
                    $requiredKeys = [
                        'ctdt_nganh_ma',
                        'ctdt_nganh_ten_tieng_viet',
                        'ctdt_nganh_ten_tieng_anh',
                        'ctdt_nganh_ten_viet_tat',
                        'ctdt_nganh_ma_tuyen_sinh',
                        'ctdt_nganh_stt',
                        'nv_don_vi_ten_tieng_viet',
                        'ctdt_nganh_khoi_thi',
                        'ctdt_nganh_ky_tu_mssv',
                        'ctdt_khoi_nganh_ten_tieng_viet',
                        'ctdt_nganh_ghi_chu',
                        'ctdt_nganh_hien_thi'
                    ];
                    //Kiểm tra có upload file rỗng không
                    foreach ($dataList as $index => $l) {
                        // Kiểm tra nếu tất cả các giá trị trong mảng đều rỗng thì loại bỏ
                        if (!array_filter($l)) {
                            unset($dataList[$index]);
                            continue;
                        }
                    }
                    if (!empty($dataList)) {
                        $firstDataList = reset($dataList); //reset chỉ lấy 1 mảng bên trong DataList
                        // Kiểm tra xem tất cả các khóa cần thiết có tồn tại trong phần tử không
                        $missingKeys = array_diff($requiredKeys, array_keys($firstDataList));
                        if (!empty($missingKeys)) {
                            unlink($path);
                            return $this->response([
                                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                                'message' => "File không đúng định dạng hoặc đã bị chỉnh sửa hàng mẫu, Vui lòng tải lại file mẫu và nhập lại dữ liệu",
                                'success' => false,
                                'data' => [],
                            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
                        }
                        $dataInsert = [];
                        $countError = $countSuccess = 0;
                        $totalRow = count($dataList);

                        $startRow_columnResult = 4;
                        foreach ($dataList as $key => $l) {
                            $errorMessages = [];
                            $field_PrimaryKey = [
                                'ctdt_nganh_ma' => 'Mã ngành đang không có dữ liệu.',
                                'ctdt_nganh_ten_tieng_viet' => 'Tên ngành (Tiếng Việt) đang không có dữ liệu.',
                                // 'ctdt_nganh_ten_tieng_anh' => 'Tên ngành (Tiếng Anh) đang không có dữ liệu.',
                            ];
                            foreach ($field_PrimaryKey as $key => $errorMessage) {
                                if (empty($l[$key])) {
                                    $errorMessages[] = $errorMessage;
                                }
                            }
                            if (!empty($errorMessages)) {
                                $l['ketqua'] = implode(' ', $errorMessages);
                                $countError++;
                            } else {
                                $nganh = $this->Ctdt_nganh_model->where('ctdt_nganh_ma', $l['ctdt_nganh_ma'])->first();
                                $donVi = $this->Nv_don_vi_model->where('nv_don_vi_ten_tieng_viet', $l['nv_don_vi_ten_tieng_viet'])->first();
                                $khoiNganh = $this->Ctdt_khoi_nganh_model->where('ctdt_khoi_nganh_ten_tieng_viet', $l['ctdt_khoi_nganh_ten_tieng_viet'])->first();
                                if ($nganh) {
                                    $l['ketqua'] = "Mã ngành này đã tồn tại";
                                    $countError++;
                                } else {
                                    $l['ketqua'] = $this->checkDataValidity_importNganh($l, $donVi);
                                    if ($l['ketqua'] !== 'Success') {
                                        $countError++;
                                    } else {
                                        $l['ketqua'] = 'Success';
                                        $countSuccess++;
                                        $dataInsert[] = [
                                            'ctdt_nganh_ma' => $l['ctdt_nganh_ma'],
                                            'ctdt_nganh_ten_tieng_viet' => $l['ctdt_nganh_ten_tieng_viet'],
                                            'nv_don_vi_id' => $donVi ? $donVi['nv_don_vi_id'] : null,

                                            'ctdt_khoi_nganh_id' => $khoiNganh ? $khoiNganh['ctdt_khoi_nganh_id'] : null,

                                            'ctdt_nganh_ten_tieng_anh' => $l['ctdt_nganh_ten_tieng_anh'],
                                            'ctdt_nganh_ten_viet_tat' => $l['ctdt_nganh_ten_viet_tat'],
                                            'ctdt_nganh_ma_tuyen_sinh' => $l['ctdt_nganh_ma_tuyen_sinh'],
                                            'ctdt_nganh_stt' => $l['ctdt_nganh_stt'],
                                            'ctdt_nganh_khoi_thi' => $l['ctdt_nganh_khoi_thi'],
                                            'ctdt_nganh_ky_tu_mssv' => $l['ctdt_nganh_ky_tu_mssv'],
                                            'ctdt_nganh_ghi_chu' => $l['ctdt_nganh_ghi_chu'],
                                            'ctdt_nganh_hien_thi' => strtolower($l['ctdt_nganh_hien_thi']) == 'có' ? 1 : 0,
                                            'ctdt_bac_dao_tao_id' =>  null,
                                            'ctdt_loai_hinh_dao_tao_id' => null,
                                            'ctdt_loai_bang_tot_nghiep_id' => null
                                        ];
                                    }
                                }
                            }
                            $sheet->setCellValue($highestColumn . $startRow_columnResult, $l['ketqua']);
                            $color = strtolower($l['ketqua']) != strtolower('Success') ? 'f57878' : '77c884';
                            $sheet->getStyle($highestColumn . $startRow_columnResult)->applyFromArray(
                                array(
                                    'fill' => array(
                                        'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                        'color' => array('rgb' => $color)
                                    )
                                )
                            );
                            $startRow_columnResult++;
                        }
                        if (!empty($dataInsert)) {
                            // $this->db->trans_start();
                            $this->Ctdt_nganh_model->insertBatch($dataInsert);
                            $this->createLog('Import', 'Import danh sách ngành', NULL, $dataInsert, 'ctdt_nganh');
                            // $this->db->trans_commit();
                            if ($countError > 0) {
                                $message = 'Thêm thành công ' . count($dataInsert) . '/' . $totalRow . ' dòng </br>' .
                                    'Thêm thất bại ' . $countError . '/' . $totalRow . ' dòng';
                            } else {
                                $message = $countSuccess . "/" . $totalRow . " dòng được thêm thành công";
                            }
                        } else {
                            $message = "Không có dòng dữ liệu import hợp lệ";
                        }
                    } else {
                        $stop = $unlinkFile = true;
                        $message = "File import đang rỗng";
                    }
                    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
                    $objWriter->save($path); // Lưu đè file gốc
                }
                if ($stop) {
                    if (isset($unlinkFile) && $unlinkFile) unlink($path);

                    $this->response([
                        'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                        'message' => $message,
                        'success' => false,
                        'data' => [],
                    ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
                } else {
                    $fileContent = file_get_contents($path);
                    $encodedContent = base64_encode($fileContent);
                    if (file_exists($path)) unlink($path);

                    $this->response([
                        'status' => REST_INSTANCE_Controller::HTTP_CREATED,
                        'message' => $message,
                        'success' => true,
                        'data' => [
                            'file_content' => $encodedContent,
                            'file_name' => $fileName,
                            'countError' => $countError,
                        ],
                    ], REST_INSTANCE_Controller::HTTP_CREATED);
                }
            } else {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => 'Không tìm thấy file này',
                    'success' => false,
                    'data' => [],
                ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (Exception $e) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $e->getMessage(),
                'success' => false,
                'data' => null
            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $t) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $t->getMessage(),
                'success' => false,
                'data' => null
            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    private function checkDataValidity_importNganh($l, $donvi)
    {
        $errors = '';

        if (!$donvi) $errors .= "Tên Khoa, ";
        // if (!$khoinganh) $errors .= "Tên Khối ngành, ";
        return $errors ? rtrim($errors, ", ") . ' chưa đúng hoặc sai định dạng' : 'Success';
    }
    private function getExcelColumn()
    {
        $cols = [
            '' => 'STT',
            'ctdt_nganh_ma' => 'Mã ngành',
            'ctdt_nganh_ten_tieng_viet' => 'Tên ngành',
            'ctdt_nganh_ten_tieng_anh' => 'Tên tiếng anh',
            'ctdt_nganh_ten_viet_tat' => 'Tên viết tắt',
            'ctdt_nganh_ma_tuyen_sinh' => 'Mã tuyển sinh',
            'ctdt_nganh_stt' => 'STT',
            'nv_don_vi_ten_tieng_viet' => 'Khoa',
            'ctdt_nganh_khoi_thi' => 'Khối thi',
            'ctdt_nganh_ky_tu_mssv' => 'Ký tự Mã SV',
            'ctdt_khoi_nganh_ten_tieng_viet' => 'Khối ngành',
            'ctdt_nganh_ghi_chu' => 'Ghi  chú',
            'ctdt_nganh_hien_thi' => 'Hiển thị'
        ];
        return $cols;
    }

    public function export_get()
    {
        $data = $this->Ctdt_nganh_model->getListExport();
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
        $sheet->setCellValue('A1', 'Danh mục ngành đào tạo'); // Thêm cột thông báo

        // Hợp nhất các ô từ A1 đến W1
        $sheet->mergeCells('A1:M1');

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
        // Đặt dữ liệu vào các hàng tiếp theo
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
        $directory = 'uploads/download/excel/excel_export/nganh/'; // Thư mục để lưu file
        $filename = 'Danhmucnganhdaotao_' . time() . '.xls'; // Tên file kèm timestamp để tránh trùng lặp
        $filePath = $directory . $filename;
        // Kiểm tra và tạo thư mục nếu chưa tồn tại
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);

        if (file_exists($filePath)) {
            $this->createLog('Export', 'Export danh sách ngành', NULL, 'Export danh sách ngành', 'ctdt_nganh');
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

    public function deletes_post()
    {
        $ids = commonRequest('ids');

        $this->load->model(['Sv_lop_model', 'Ctdt_chuyen_nganh_model', 'Ctdt_ke_hoach_hoc_tap_model', 'Ctdt_chuong_trinh_khung_model']);
        $this->db->trans_start();
        foreach ($ids as $id) {
            if (
                $this->Sv_lop_model->checkValueExists('ctdt_nganh_id', $id)
                || $this->Ctdt_chuyen_nganh_model->checkValueExists('ctdt_nganh_id', $id)
                || $this->Ctdt_ke_hoach_hoc_tap_model->checkValueExists('ctdt_nganh_id', $id)
                || $this->Ctdt_chuong_trinh_khung_model->checkValueExists('ctdt_nganh_id', $id)
            ) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                    'message' => 'Dữ liệu đang được sử dụng trong hệ thống!',
                    'success' => false
                ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
                exit();
            } else {
                $info = $this->Ctdt_nganh_model->get_ctdt_nganh_by_id($id);
                $LogData = [
                    'ctdt_nganh_id' => $info['ctdt_nganh_id'],
                    'ctdt_nganh_ma' => $info['ctdt_nganh_ma'],
                    'ctdt_nganh_ten_tieng_viet' => $info['ctdt_nganh_ten_tieng_viet'],
                    'ctdt_nganh_ten_tieng_anh' => $info['ctdt_nganh_ten_tieng_anh'],
                    'ctdt_nganh_ten_viet_tat' => $info['ctdt_nganh_ten_viet_tat'],
                    'ctdt_nganh_ma_tuyen_sinh' => $info['ctdt_nganh_ma_tuyen_sinh'],
                    'ctdt_nganh_stt' => $info['ctdt_nganh_stt'],
                    'nv_don_vi_id' => $info['nv_don_vi_id'],
                    'ctdt_nganh_khoi_thi' => $info['ctdt_nganh_khoi_thi'],
                    'ctdt_nganh_ky_tu_mssv' => $info['ctdt_nganh_ky_tu_mssv'],
                    'ctdt_khoi_nganh_id' => $info['ctdt_khoi_nganh_id'],
                    'ctdt_nganh_ghi_chu' => $info['ctdt_nganh_ghi_chu'],
                    'ctdt_nganh_hien_thi' => $info['ctdt_nganh_hien_thi'],
                    'ctdt_bac_dao_tao_id' => $info['ctdt_bac_dao_tao_id'],
                    'ctdt_loai_hinh_dao_tao_id' => $info['ctdt_loai_hinh_dao_tao_id'],
                    'ctdt_loai_bang_tot_nghiep_id' => $info['ctdt_loai_bang_tot_nghiep_id'],
                ];

                $this->createLog('delete', 'Xóa ngành', $LogData, null, 'ctdt_nganh');
            }
        }

        $result = $this->Ctdt_nganh_model->whereIn('ctdt_nganh_id', $ids)->delete();
        if ($result) {
            $this->db->trans_commit();
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Xóa thành công',
                'success' => true
            ], REST_INSTANCE_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Xóa thất bại',
                'success' => false,
            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store_post()
    {
        $ctdt_nganh_ma = commonRequest('ctdt_nganh_ma') ? commonRequest('ctdt_nganh_ma') : null;
        $ctdt_nganh_ten_tieng_viet = commonRequest('ctdt_nganh_ten_tieng_viet') ? commonRequest('ctdt_nganh_ten_tieng_viet') : null;
        $ctdt_nganh_ten_tieng_anh = commonRequest('ctdt_nganh_ten_tieng_anh') ? commonRequest('ctdt_nganh_ten_tieng_anh') : null;
        $ctdt_nganh_ten_viet_tat = commonRequest('ctdt_nganh_ten_viet_tat') ? commonRequest('ctdt_nganh_ten_viet_tat') : null;
        $ctdt_nganh_ma_tuyen_sinh = commonRequest('ctdt_nganh_ma_tuyen_sinh') ? commonRequest('ctdt_nganh_ma_tuyen_sinh') : null;
        $nv_don_vi_id = commonRequest('nv_don_vi_id') ? commonRequest('nv_don_vi_id') : null;
        $ctdt_nganh_khoi_thi = commonRequest('ctdt_nganh_khoi_thi') ? commonRequest('ctdt_nganh_khoi_thi') : null;
        $ctdt_nganh_ky_tu_mssv = commonRequest('ctdt_nganh_ky_tu_mssv') ? commonRequest('ctdt_nganh_ky_tu_mssv') : null;
        $ctdt_khoi_nganh_id = commonRequest('ctdt_khoi_nganh_id') ? commonRequest('ctdt_khoi_nganh_id') : null;
        $ctdt_bac_dao_tao_id = commonRequest('ctdt_bac_dao_tao_id') ? commonRequest('ctdt_bac_dao_tao_id') : null;
        $ctdt_loai_hinh_dao_tao_id = commonRequest('ctdt_loai_hinh_dao_tao_id') ? commonRequest('ctdt_loai_hinh_dao_tao_id') : null;
        $ctdt_loai_bang_tot_nghiep_id = commonRequest('ctdt_loai_bang_tot_nghiep_id') ? commonRequest('ctdt_loai_bang_tot_nghiep_id') : null;
        $ctdt_nganh_ghi_chu = commonRequest('ctdt_nganh_ghi_chu') ? commonRequest('ctdt_nganh_ghi_chu') : null;

        if (!$ctdt_nganh_ma) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Mã ngành không được để trống',
                'success' => false,
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        if (!$ctdt_nganh_ten_tieng_viet) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Tên tiếng Việt không được để trống',
                'success' => false,
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }
        if ($this->Ctdt_nganh_model->checkValueExists('ctdt_nganh_ma', $ctdt_nganh_ma)) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Mã ngành đã tồn tại',
                'success' => false,
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        if ($nv_don_vi_id) {
            if (!$this->Nv_don_vi_model->checkValueExists('nv_don_vi_id', $nv_don_vi_id)) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                    'message' => 'Khoa chủ quản không tồn tại',
                    'success' => false,
                ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
            }
        }

        if ($ctdt_khoi_nganh_id) {
            if (!$this->Ctdt_khoi_nganh_model->checkValueExists('ctdt_khoi_nganh_id', $ctdt_khoi_nganh_id)) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                    'message' => 'Khối ngành không tồn tại',
                    'success' => false,
                ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
            }
        }
        if ($ctdt_bac_dao_tao_id) {
            if (!$this->Ctdt_bac_dao_tao_model->checkValueExists('ctdt_bac_dao_tao_id', $ctdt_bac_dao_tao_id)) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                    'message' => 'Bậc đào tạo không tồn tại',
                    'success' => false,
                ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
            }
        }
        if ($ctdt_loai_hinh_dao_tao_id) {
            if (!$this->Ctdt_loai_hinh_dao_tao_model->checkValueExists('ctdt_loai_hinh_dao_tao_id', $ctdt_loai_hinh_dao_tao_id)) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                    'message' => 'Loại hình đào tạo không tồn tại',
                    'success' => false,
                ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
            }
        }
        if ($ctdt_loai_bang_tot_nghiep_id) {
            if (!$this->Ctdt_loai_bang_tot_nghiep_model->checkValueExists('ctdt_loai_bang_tot_nghiep_id', $ctdt_loai_bang_tot_nghiep_id)) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                    'message' => 'Loại bằng tốt nghiệp không tồn tại',
                    'success' => false,
                ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
            }
        }

        $data = [
            'ctdt_nganh_ma' => $ctdt_nganh_ma,
            'ctdt_nganh_ten_tieng_viet' => $ctdt_nganh_ten_tieng_viet,
            'ctdt_nganh_ten_tieng_anh' => $ctdt_nganh_ten_tieng_anh,
            'ctdt_nganh_ten_viet_tat' => $ctdt_nganh_ten_viet_tat,
            'ctdt_nganh_ma_tuyen_sinh' => $ctdt_nganh_ma_tuyen_sinh,
            'nv_don_vi_id' => $nv_don_vi_id,
            'ctdt_nganh_khoi_thi' => $ctdt_nganh_khoi_thi,
            'ctdt_nganh_ky_tu_mssv' => $ctdt_nganh_ky_tu_mssv,
            'ctdt_khoi_nganh_id' => $ctdt_khoi_nganh_id,
            'ctdt_bac_dao_tao_id' => $ctdt_bac_dao_tao_id,
            'ctdt_loai_hinh_dao_tao_id' => $ctdt_loai_hinh_dao_tao_id,
            'ctdt_loai_bang_tot_nghiep_id' => $ctdt_loai_bang_tot_nghiep_id,
            'ctdt_nganh_ghi_chu' => $ctdt_nganh_ghi_chu,
            'created_user_id' => $this->getUserLogin()['ql_nguoi_dung_id'],
            'updated_user_id' => $this->getUserLogin()['ql_nguoi_dung_id'],
        ];
        $this->db->trans_start();
        $major = $this->Ctdt_nganh_model->create($data);
        $this->createLog('create', 'Tạo mới danh mục ngành đào tạo', [], $major, 'ctdt_nganh');
        $this->db->trans_complete();
        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_CREATED,
            'data' => $major,
            'message' => 'Thêm mới ngành thành công',
            'success' => true,
        ], REST_INSTANCE_Controller::HTTP_CREATED);
    }

    public function update_put($id)
    {
        if (!$major = $this->Ctdt_nganh_model->find($id)) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                'message' => 'Ngành không tồn tại',
                'success' => false,
            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }
        $ctdt_nganh_ma = commonRequest('ctdt_nganh_ma') ? commonRequest('ctdt_nganh_ma') : null;
        $ctdt_nganh_ten_tieng_viet = commonRequest('ctdt_nganh_ten_tieng_viet') ? commonRequest('ctdt_nganh_ten_tieng_viet') : null;
        $ctdt_nganh_ten_tieng_anh = commonRequest('ctdt_nganh_ten_tieng_anh') ? commonRequest('ctdt_nganh_ten_tieng_anh') : null;
        $ctdt_nganh_ten_viet_tat = commonRequest('ctdt_nganh_ten_viet_tat') ? commonRequest('ctdt_nganh_ten_viet_tat') : null;
        $ctdt_nganh_ma_tuyen_sinh = commonRequest('ctdt_nganh_ma_tuyen_sinh') ? commonRequest('ctdt_nganh_ma_tuyen_sinh') : null;
        $nv_don_vi_id = commonRequest('nv_don_vi_id') ? commonRequest('nv_don_vi_id') : null;
        $ctdt_nganh_khoi_thi = commonRequest('ctdt_nganh_khoi_thi') ? commonRequest('ctdt_nganh_khoi_thi') : null;
        $ctdt_nganh_ky_tu_mssv = commonRequest('ctdt_nganh_ky_tu_mssv') ? commonRequest('ctdt_nganh_ky_tu_mssv') : null;
        $ctdt_khoi_nganh_id = commonRequest('ctdt_khoi_nganh_id') ? commonRequest('ctdt_khoi_nganh_id') : null;
        $ctdt_bac_dao_tao_id = commonRequest('ctdt_bac_dao_tao_id') ? commonRequest('ctdt_bac_dao_tao_id') : null;
        $ctdt_loai_hinh_dao_tao_id = commonRequest('ctdt_loai_hinh_dao_tao_id') ? commonRequest('ctdt_loai_hinh_dao_tao_id') : null;
        $ctdt_loai_bang_tot_nghiep_id = commonRequest('ctdt_loai_bang_tot_nghiep_id') ? commonRequest('ctdt_loai_bang_tot_nghiep_id') : null;
        $ctdt_nganh_ghi_chu = commonRequest('ctdt_nganh_ghi_chu') ? commonRequest('ctdt_nganh_ghi_chu') : null;

        if (!$ctdt_nganh_ma) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Mã ngành không được để trống',
                'success' => false,
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        if (!$ctdt_nganh_ten_tieng_viet) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Tên tiếng Việt không được để trống',
                'success' => false,
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }
        if ($this->Ctdt_nganh_model->where('ctdt_nganh_ma', $ctdt_nganh_ma)->where('ctdt_nganh_id !=', $id)->first()) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Mã ngành đã tồn tại',
                'success' => false,
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        if ($nv_don_vi_id) {
            if (!$this->Nv_don_vi_model->checkValueExists('nv_don_vi_id', $nv_don_vi_id)) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                    'message' => 'Khoa chủ quản không tồn tại',
                    'success' => false,
                ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
            }
        }

        if ($ctdt_khoi_nganh_id) {
            if (!$this->Ctdt_khoi_nganh_model->checkValueExists('ctdt_khoi_nganh_id', $ctdt_khoi_nganh_id)) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                    'message' => 'Khối ngành không tồn tại',
                    'success' => false,
                ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
            }
        }
        if ($ctdt_bac_dao_tao_id) {
            if (!$this->Ctdt_bac_dao_tao_model->checkValueExists('ctdt_bac_dao_tao_id', $ctdt_bac_dao_tao_id)) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                    'message' => 'Bậc đào tạo không tồn tại',
                    'success' => false,
                ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
            }
        }
        if ($ctdt_loai_hinh_dao_tao_id) {
            if (!$this->Ctdt_loai_hinh_dao_tao_model->checkValueExists('ctdt_loai_hinh_dao_tao_id', $ctdt_loai_hinh_dao_tao_id)) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                    'message' => 'Loại hình đào tạo không tồn tại',
                    'success' => false,
                ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
            }
        }
        if ($ctdt_loai_bang_tot_nghiep_id) {
            if (!$this->Ctdt_loai_bang_tot_nghiep_model->checkValueExists('ctdt_loai_bang_tot_nghiep_id', $ctdt_loai_bang_tot_nghiep_id)) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                    'message' => 'Loại bằng tốt nghiệp không tồn tại',
                    'success' => false,
                ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
            }
        }

        $data = [
            'ctdt_nganh_ma' => $ctdt_nganh_ma,
            'ctdt_nganh_ten_tieng_viet' => $ctdt_nganh_ten_tieng_viet,
            'ctdt_nganh_ten_tieng_anh' => $ctdt_nganh_ten_tieng_anh,
            'ctdt_nganh_ten_viet_tat' => $ctdt_nganh_ten_viet_tat,
            'ctdt_nganh_ma_tuyen_sinh' => $ctdt_nganh_ma_tuyen_sinh,
            'nv_don_vi_id' => $nv_don_vi_id,
            'ctdt_nganh_khoi_thi' => $ctdt_nganh_khoi_thi,
            'ctdt_nganh_ky_tu_mssv' => $ctdt_nganh_ky_tu_mssv,
            'ctdt_khoi_nganh_id' => $ctdt_khoi_nganh_id,
            'ctdt_bac_dao_tao_id' => $ctdt_bac_dao_tao_id,
            'ctdt_loai_hinh_dao_tao_id' => $ctdt_loai_hinh_dao_tao_id,
            'ctdt_loai_bang_tot_nghiep_id' => $ctdt_loai_bang_tot_nghiep_id,
            'ctdt_nganh_ghi_chu' => $ctdt_nganh_ghi_chu,
            'updated_user_id' => $this->getUserLogin()['ql_nguoi_dung_id'],
        ];



        $this->db->trans_start();
        $this->Ctdt_nganh_model->where('ctdt_nganh_id', $id)->update($data);
        $newMajor = $this->Ctdt_nganh_model->find($id);
        $this->createLog('update', 'Cập nhật danh mục ngành đào tạo', $major, $newMajor, 'ctdt_nganh');
        $this->db->trans_complete();



        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'data' => $newMajor,
            'message' => 'Cập nhật ngành thành công',
            'success' => true,
        ], REST_INSTANCE_Controller::HTTP_OK);
    }
}
