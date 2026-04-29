<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';
require APPPATH . 'libraries/pxl/PHPExcel.php';
/**  
 * @property Ql_quy_dinh_thoi_gian_nam_hoc_model $Ql_quy_dinh_thoi_gian_nam_hoc_model
 * @property Dm_tinh_thanh_model $Dm_tinh_thanh_model
 * @property Diem_bang_diem_nam_hoc_model $Diem_bang_diem_nam_hoc_model
 * @property Sv_sinh_vien_model $Sv_sinh_vien_model
 * @property CI_Upload $upload
 * @property Pxl $pxl
 * @property CI_DB_query_builder $db
 */
class Quydinhthoigiannamhoc extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->permissionMiddleware();

        $this->load->model('Ql_quy_dinh_thoi_gian_nam_hoc_model');
        $this->load->model('Dm_tinh_thanh_model');
        $this->load->model('Diem_bang_diem_nam_hoc_model');
        $this->load->model('Sv_sinh_vien_model');
        $this->load->library('upload');
        $this->load->helper('url');
    }

    public function index_get()
    {
        $dataFilter = commonRequest('dataFilter');

        $data = $this->Ql_quy_dinh_thoi_gian_nam_hoc_model->getNamHoc();

        if ($dataFilter) {
            $data['dataFilter'] = [
                'quy_dinh_thoi_gian_nam_hoc' => $this->Ql_quy_dinh_thoi_gian_nam_hoc_model
                    ->select('nam_hoc')
                    ->distinct()
                    ->get()
            ];
        }

        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => $data
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function export_get()
    {
        $data = $this->Dm_tinh_thanh_model->getListExport();

        $titles = [
            '' => 'STT',
            'dm_tinh_thanh_id' => 'ID tỉnh thành',
            'dm_tinh_thanh_ma' => 'Mã tỉnh thành',
            'dm_tinh_thanh_ten_tieng_viet' => 'Tên tỉnh thành tiếng Việt',
            'dm_tinh_thanh_ten_tieng_anh' => 'Tên tỉnh thành tiếng Anh'
        ];

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
        $sheet->setCellValue('A1', 'Danh sách tỉnh thành'); // Thêm cột thông báo

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
        $directory = 'assets/download/excel/excel_export/danhmuc/'; // Thư mục để lưu file

        $filename = 'Dm_tinh_thanh_' . time() . '.xlsx'; // Tên file kèm timestamp để tránh trùng lặp
        $filePath = $directory . $filename;

        // Kiểm tra và tạo thư mục nếu chưa tồn tại
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);
        //ghi log
        $this->createLog('export', 'Export danh sách tỉnh thành', null, json_encode(['path' => $filePath]), 'dm_tinh_thanh');

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

    public function create_post()
    {
        $this->db->trans_start();

        $nam_hoc = trim(commonRequest('nam_hoc'));
        $hoc_ky = trim(commonRequest('hoc_ky'));
        $ngay_bat_dau_hoc_ky = commonRequest('ngay_bat_dau_hoc_ky');
        $ngay_ket_thuc_hoc_ky = commonRequest('ngay_ket_thuc_hoc_ky');

        // Kiểm tra trùng lặp
        if (strlen($nam_hoc) > 4) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Năm học không hợp lệ !',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($ngay_bat_dau_hoc_ky >= $ngay_ket_thuc_hoc_ky) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Thời gian bắt đầu và kết thúc không hợp lệ',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }

        $namHocKy = $this->Ql_quy_dinh_thoi_gian_nam_hoc_model->get();

        foreach ($namHocKy as $nhk) {
            if (($nhk['nam_hoc'] . $nhk['hoc_ky']) == ($nam_hoc . $hoc_ky)) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => 'Thời gian năm học bị trùng lặp',
                    'success' => false
                ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
                break;
            }

            if (($ngay_bat_dau_hoc_ky >= $nhk['ngay_bat_dau_hoc_ky']) && ($ngay_bat_dau_hoc_ky <= $nhk['ngay_ket_thuc_hoc_ky'])) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => 'Ngày bắt đầu học kỳ thuộc 1 học kỳ khác',
                    'success' => false
                ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
                break;
            }

            if (($ngay_ket_thuc_hoc_ky >= $nhk['ngay_bat_dau_hoc_ky']) && ($ngay_ket_thuc_hoc_ky <= $nhk['ngay_ket_thuc_hoc_ky'])) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => 'Ngày kết thúc học kỳ thuộc 1 học kỳ khác',
                    'success' => false
                ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
                break;
            }
        }

        $data = array(
            'nam_hoc' => $nam_hoc,
            'hoc_ky' => $hoc_ky,
            'ngay_bat_dau_hoc_ky' => $ngay_bat_dau_hoc_ky,
            'ngay_ket_thuc_hoc_ky' => $ngay_ket_thuc_hoc_ky,
            'la_nam_hoc_hoc_ky_hien_tai' => 0
        );

        $namHocId =  $this->Ql_quy_dinh_thoi_gian_nam_hoc_model
            ->insert($data);

        $info = $this->Ql_quy_dinh_thoi_gian_nam_hoc_model->find($namHocId);
        $newData = [
            'ql_quy_dinh_thoi_gian_nam_hoc_id' => $info['ql_quy_dinh_thoi_gian_nam_hoc_id'],
            'nam_hoc' => $info['nam_hoc'],
            'hoc_ky' => $info['hoc_ky'],
            'ngay_bat_dau_hoc_ky' => $info['ngay_bat_dau_hoc_ky'],
            'ngay_ket_thuc_hoc_ky' => $info['ngay_ket_thuc_hoc_ky'],
            'la_nam_hoc_hoc_ky_hien_tai' => $info['la_nam_hoc_hoc_ky_hien_tai'],
        ];
        $this->createLog('create', 'Thêm quy định thời gian năm học', null, $newData, 'ql_quy_dinh_thoi_gian_nam_hoc');

        $this->db->trans_commit();

        $this->response([
            'status' => REST_Controller::HTTP_OK,
            'message' => 'Thêm thời gian năm học thành công',
            'success' => true
        ], REST_Controller::HTTP_OK);
    }

    public function detail_get($namHocId)
    {
        $data = $this->Ql_quy_dinh_thoi_gian_nam_hoc_model->getInfoById($namHocId);

        if ($data) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Success',
                'success' => true,
                'data' => $data
            ], REST_INSTANCE_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                'message' => 'Data not found',
                'success' => false,
                'data' => null
            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }
    }

    public function update_put($namHocId)
    {
        if (!$this->Ql_quy_dinh_thoi_gian_nam_hoc_model->find($namHocId)) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Không tìm thấy thời gian năm học',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }

        $nam_hoc = commonRequest('nam_hoc');
        $hoc_ky = commonRequest('hoc_ky');
        $ngay_bat_dau_hoc_ky = commonRequest('ngay_bat_dau_hoc_ky');
        $ngay_ket_thuc_hoc_ky = commonRequest('ngay_ket_thuc_hoc_ky');
        if (commonRequest('la_nam_hoc_hoc_ky_hien_tai') == true) {
            $la_nam_hoc_hoc_ky_hien_tai = 1;
        } else {
            $la_nam_hoc_hoc_ky_hien_tai = 0;
        }

        $info = $this->Ql_quy_dinh_thoi_gian_nam_hoc_model->find($namHocId);
        $oldData = [
            'ql_quy_dinh_thoi_gian_nam_hoc_id' => $info['ql_quy_dinh_thoi_gian_nam_hoc_id'],
            'nam_hoc' => $info['nam_hoc'],
            'hoc_ky' => $info['hoc_ky'],
            'ngay_bat_dau_hoc_ky' => $info['ngay_bat_dau_hoc_ky'],
            'ngay_ket_thuc_hoc_ky' => $info['ngay_ket_thuc_hoc_ky'],
            'la_nam_hoc_hoc_ky_hien_tai' => $info['la_nam_hoc_hoc_ky_hien_tai'],
        ];

        // Kiểm tra trùng lặp
        if (strlen($nam_hoc) > 4) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Năm học không hợp lệ !',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Kiểm tra trùng lặp
        if ($ngay_bat_dau_hoc_ky >= $ngay_ket_thuc_hoc_ky) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Thời gian bắt đầu và kết thúc không hợp lệ',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }

        $namHocKy = $this->Ql_quy_dinh_thoi_gian_nam_hoc_model->get();

        foreach ($namHocKy as $nhk) {
            if ($nhk['ql_quy_dinh_thoi_gian_nam_hoc_id'] == $namHocId) {
                continue;
            } else {
                if (($nhk['nam_hoc'] . $nhk['hoc_ky']) == ($nam_hoc . $hoc_ky)) {
                    $this->response([
                        'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                        'message' => 'Thời gian năm học bị trùng lặp',
                        'success' => false
                    ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
                    break;
                }

                if (($ngay_bat_dau_hoc_ky >= $nhk['ngay_bat_dau_hoc_ky']) && ($ngay_bat_dau_hoc_ky <= $nhk['ngay_ket_thuc_hoc_ky'])) {
                    $this->response([
                        'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                        'message' => 'Ngày bắt đầu học kỳ thuộc 1 học kỳ khác',
                        'success' => false,

                        'la_nam_hoc_hoc_ky_hien_tai' => $la_nam_hoc_hoc_ky_hien_tai,

                    ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
                    break;
                }

                if (($ngay_ket_thuc_hoc_ky >= $nhk['ngay_bat_dau_hoc_ky']) && ($ngay_ket_thuc_hoc_ky <= $nhk['ngay_ket_thuc_hoc_ky'])) {
                    $this->response([
                        'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                        'message' => 'Ngày kết thúc học kỳ thuộc 1 học kỳ khác',
                        'success' => false
                    ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
                    break;
                }
            }
        }

        $this->db->trans_start();

        if ($la_nam_hoc_hoc_ky_hien_tai) {
            $this->Ql_quy_dinh_thoi_gian_nam_hoc_model
                ->where('la_nam_hoc_hoc_ky_hien_tai', 1)
                ->update([
                    'la_nam_hoc_hoc_ky_hien_tai' => 0,
                ]);

            $this->Ql_quy_dinh_thoi_gian_nam_hoc_model
                ->where('ql_quy_dinh_thoi_gian_nam_hoc_id', $namHocId)
                ->update([
                    'nam_hoc' => $nam_hoc,
                    'hoc_ky' => $hoc_ky,
                    'ngay_bat_dau_hoc_ky' => $ngay_bat_dau_hoc_ky,
                    'ngay_ket_thuc_hoc_ky' => $ngay_ket_thuc_hoc_ky,
                    'la_nam_hoc_hoc_ky_hien_tai' => $la_nam_hoc_hoc_ky_hien_tai,
                ]);
        } else {
            $this->Ql_quy_dinh_thoi_gian_nam_hoc_model
                ->where('ql_quy_dinh_thoi_gian_nam_hoc_id', $namHocId)
                ->update([
                    'nam_hoc' => $nam_hoc,
                    'hoc_ky' => $hoc_ky,
                    'ngay_bat_dau_hoc_ky' => $ngay_bat_dau_hoc_ky,
                    'ngay_ket_thuc_hoc_ky' => $ngay_ket_thuc_hoc_ky,
                ]);
        }

        $info = $this->Ql_quy_dinh_thoi_gian_nam_hoc_model->find($namHocId);
        $newData = [
            'ql_quy_dinh_thoi_gian_nam_hoc_id' => $info['ql_quy_dinh_thoi_gian_nam_hoc_id'],
            'nam_hoc' => $info['nam_hoc'],
            'hoc_ky' => $info['hoc_ky'],
            'ngay_bat_dau_hoc_ky' => $info['ngay_bat_dau_hoc_ky'],
            'ngay_ket_thuc_hoc_ky' => $info['ngay_ket_thuc_hoc_ky'],
            'la_nam_hoc_hoc_ky_hien_tai' => $info['la_nam_hoc_hoc_ky_hien_tai'],
        ];
        $this->createLog('update', 'Cập nhật thời gian năm học', $oldData, $newData, 'ql_quy_dinh_thoi_gian_nam_hoc');

        $this->db->trans_commit();

        $this->response([
            'status' => REST_Controller::HTTP_OK,
            'message' => 'Cập nhật thời gian năm học thành công',
            'success' => true
        ], REST_Controller::HTTP_OK);
    }

    public function deletes_post()
    {
        $ids = commonRequest('ids');

        $this->db->trans_start();
        foreach ($ids as $id) {
            $qdtgnh = $this->Ql_quy_dinh_thoi_gian_nam_hoc_model->find($id);
            if ($qdtgnh['la_nam_hoc_hoc_ky_hien_tai'] == 1) {
                $this->response([
                    'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => 'Không thể xóa thời gian năm học hiện tại !',
                    'success' => false,
                ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
                break;
            }

            $info = $this->Ql_quy_dinh_thoi_gian_nam_hoc_model->find($id);
            $this->createLog('delete', 'Xóa quy định thời gian năm học ' . $info['nam_hoc'] . '-' . $info['hoc_ky'], $info, null, 'ql_quy_dinh_thoi_gian_nam_hoc');
        }

        $result = $this->Ql_quy_dinh_thoi_gian_nam_hoc_model->whereIn('ql_quy_dinh_thoi_gian_nam_hoc_id', $ids)->delete();
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
}
