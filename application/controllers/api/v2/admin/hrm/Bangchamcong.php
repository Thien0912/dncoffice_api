<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_cham_cong_model $Hrm_cham_cong_model
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Hrm_api_cham_cong_model $Hrm_api_cham_cong_model
 * @property E_don_vi_model $E_don_vi_model
 * @property Fileupload $fileupload
 */



class Bangchamcong extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_cham_cong_model', 'Hrm_api_cham_cong_model', 'Hrm_nhan_vien_model', 'E_don_vi_model']);
        $this->load->library(['Validator', 'Fileupload', 'Common', 'Pxl', 'upload']);
        // $this->token = $this->getAuthToken();
    }

    public function index_get()
    {
        $start =commonRequest('start') ?? 0;
        $length =commonRequest('length') ?? 10;
        $searchValue =commonRequest('search')['value'] ?? null;
        $orderBy = [
            'order' =>commonRequest('order') ?? [],
            'columns' =>commonRequest('columns') ?? []
        ];
        
        $tab =commonRequest('tab') ?? 'tat-ca'; // Default tab
        $fromDate =commonRequest('from_date');
        $toDate =commonRequest('to_date');
        $date =commonRequest('date');
        $idDonVi =commonRequest('id_don_vi');
        $idNhanVienFilter =commonRequest('id_nhan_vien');
        
        // Lấy thông tin user hiện tại
        $auth = $this->getUserLogin();
        $qlNguoiDungId = $auth['ql_nguoi_dung_id'];

        // Lấy thông tin nhân viên của user hiện tại
        $nhanVienRaw = $this->Hrm_nhan_vien_model
            ->select('hrm_nhan_vien.id_nhan_vien, hrm_nhan_vien.id_don_vi_cong_tac, e_don_vi.ma_don_vi')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_nhan_vien.id_don_vi_cong_tac', 'left')
            ->where('hrm_nhan_vien.ql_nguoi_dung_id', $qlNguoiDungId)
            ->first();
        $nhanVien = is_array($nhanVienRaw) || is_object($nhanVienRaw) ? (array) $nhanVienRaw : [];

        $idNhanVien = !empty($nhanVien['id_nhan_vien']) ? $nhanVien['id_nhan_vien'] : null;
        $idDonViCongTac = !empty($nhanVien['id_don_vi_cong_tac']) ? $nhanVien['id_don_vi_cong_tac'] : $auth['id_don_vi'];
        $maDonVi = !empty($nhanVien['ma_don_vi']) ? $nhanVien['ma_don_vi'] : null;
        
        // Build search key with filters
        $searchKey = [];
        
        if ($fromDate) {
            $searchKey['from_date'] = $fromDate;
        }
        if ($toDate) {
            $searchKey['to_date'] = $toDate;
        }
        if ($date) {
            $searchKey['date'] = $date;
        }
        if ($tab === 'tat-ca' && $idDonVi) {
            $searchKey['id_don_vi'] = $idDonVi;
        }
        if ($tab === 'tat-ca' && $idNhanVienFilter) {
            $searchKey['id_nhan_vien'] = $idNhanVienFilter;
        }
        
        // Get data from model
        $result = $this->Hrm_api_cham_cong_model->getAll(
            $start,
            $length,
            $searchValue,
            $orderBy,
            $searchKey,
            $tab,
            $idNhanVien,
            $qlNguoiDungId,
            $idDonViCongTac
        );
        
        return $this->response([
            'status' => REST_Controller::HTTP_OK,
            'data' => $result['data'],
            'recordsTotal' => $result['recordsTotal'],
            'recordsFiltered' => $result['recordsFiltered'],
            'draw' => $this->get('draw') ?? 1
        ], REST_Controller::HTTP_OK);
    }

    public function export_excel_post()
    {
        $this->load->library('Pxl');

        // Lấy thông tin user hiện tại
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Vui lòng đăng nhập', REST_Controller::HTTP_UNAUTHORIZED);
        }

        $qlNguoiDungId = $auth['ql_nguoi_dung_id'];

        // Lấy thông tin nhân viên của user hiện tại
        $nhanVienRaw = $this->Hrm_nhan_vien_model
            ->select('hrm_nhan_vien.id_nhan_vien, hrm_nhan_vien.id_don_vi_cong_tac, e_don_vi.ma_don_vi')
            ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_nhan_vien.id_don_vi_cong_tac', 'left')
            ->where('hrm_nhan_vien.ql_nguoi_dung_id', $qlNguoiDungId)
            ->first();
        $nhanVien = is_array($nhanVienRaw) || is_object($nhanVienRaw) ? (array) $nhanVienRaw : [];

        $idNhanVien = !empty($nhanVien['id_nhan_vien']) ? $nhanVien['id_nhan_vien'] : null;
        $idDonViCongTac = !empty($nhanVien['id_don_vi_cong_tac']) ? $nhanVien['id_don_vi_cong_tac'] : $auth['id_don_vi'];

        // Lấy dữ liệu từ POST - chỉ cần from_date, to_date, id_don_vi
        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];
        if (is_object($searchKey)) {
            $searchKey = json_decode(json_encode($searchKey), true);
        }

        $fromDate = isset($searchKey['from_date']) ? $searchKey['from_date'] : null;
        $toDate   = isset($searchKey['to_date'])   ? $searchKey['to_date']   : null;
        $idDonVi  = isset($searchKey['id_don_vi']) ? $searchKey['id_don_vi'] : null;

        // Split id_don_vi nếu có dấu phẩy để query từng đơn vị riêng
        $donViIds = [];
        if ($idDonVi) {
            $donViIds = array_map('trim', explode(',', $idDonVi));
        }

        try {
            $data = [];
            
            // Query 1 lần cho tất cả đơn vị (tối ưu performance)
            $searchKeyData = [];
            if ($fromDate) $searchKeyData['from_date'] = $fromDate;
            if ($toDate)   $searchKeyData['to_date']   = $toDate;
            
            // Nếu có filter đơn vị, truyền chuỗi comma-separated để model xử lý
            if (!empty($donViIds)) {
                $searchKeyData['id_don_vi'] = $idDonVi; // Giữ nguyên chuỗi comma-separated
            }

            $result = $this->Hrm_api_cham_cong_model->getAll(
                0,
                -1, // Lấy tất cả
                '',
                [],
                $searchKeyData,
                'tat-ca',
                $idNhanVien,
                $qlNguoiDungId,
                $idDonViCongTac
            );

            $data = $result['data'] ?? [];

            if (empty($data)) {
                resError('Không có dữ liệu để xuất', REST_Controller::HTTP_BAD_REQUEST);
            }

            // Create Excel file
            require_once APPPATH . '/libraries/pxl/PHPExcel.php';
            require_once APPPATH . '/libraries/pxl/PHPExcel/IOFactory.php';

            $objPHPExcel = new PHPExcel();
            $objPHPExcel->getProperties()
                ->setTitle('Báo cáo chấm công')
                ->setSubject('Báo cáo chấm công')
                ->setDescription('Báo cáo chấm công');

            // Áp dụng format chi tiết cho tất cả trường hợp
            $this->createExcelByDonVi($objPHPExcel, $data);

            // Save file
            $directory = FCPATH . 'uploads/exports/';
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $filename = 'BaoCaoChamCong_' . date('YmdHis') . '.xlsx';
            $filePath = $directory . $filename;

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save($filePath);

            resSuccess([
                'file_path' => base_url('uploads/exports/' . $filename),
                'filename' => $filename,
                'total_records' => count($data)
            ], 'Xuất báo cáo thành công');

        } catch (Exception $e) {
            resError('Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Helper: Format giờ từ số thập phân sang HH:mm
     */
    private function formatWorkTime($hours) {
        if ($hours <= 0) return '00:00';
        $totalMinutes = round($hours * 60);
        $h = floor($totalMinutes / 60);
        $m = $totalMinutes % 60;
        return sprintf('%02d:%02d', $h, $m);
    }

    /**
     * Helper: Tính giờ bù từ chấm công thực tế
     */
    private function calculateGioBu($record) {
        $gioBu = 0;
        
        // Parse time từ HH:mm:ss hoặc HH:mm
        $parseTime = function($timeStr) {
            if (empty($timeStr)) return 0;
            $parts = explode(':', $timeStr);
            $hours = isset($parts[0]) ? intval($parts[0]) : 0;
            $minutes = isset($parts[1]) ? intval($parts[1]) : 0;
            return $hours * 60 + $minutes; // Trả về tổng số phút
        };
        
        $punch4 = $record['punch_4'] ?? ($record['gio_ra_chieu_hieu_luc'] ?? null);
        $punch2 = $record['punch_2'] ?? ($record['gio_ra_sang_hieu_luc'] ?? null);
        $caCheckOut = $record['ca_check_out'] ?? null;
        $caKetThucCheckIn = $record['ca_ket_thuc_check_in'] ?? null;
        
        // Ưu tiên tính từ ca chiều (punch_4)
        if ($punch4 && $caCheckOut) {
            $punch4Minutes = $parseTime($punch4);
            $checkOutMinutes = $parseTime($caCheckOut);
            
            if ($punch4Minutes > $checkOutMinutes) {
                $gioBu = ($punch4Minutes - $checkOutMinutes) / 60.0; // Chuyển sang giờ
            }
        } elseif ($punch2 && $caKetThucCheckIn && !$punch4) {
            // Nếu chỉ làm ca sáng
            $punch2Minutes = $parseTime($punch2);
            $checkInEndMinutes = $parseTime($caKetThucCheckIn);
            
            if ($punch2Minutes > $checkInEndMinutes) {
                $gioBu = ($punch2Minutes - $checkInEndMinutes) / 60.0;
            }
        }
        
        return $gioBu;
    }

    /**
     * Tạo Excel theo format chi tiết - nhóm theo đơn vị và nhân viên
     * Tối ưu: Mỗi đơn vị là một sheet riêng
     */
    private function createExcelByDonVi($objPHPExcel, $data)
    {
        // Nhóm dữ liệu theo đơn vị
        $dataByDonVi = [];
        foreach ($data as $record) {
            // Ưu tiên id_don_vi_cong_tac (đúng hơn id_don_vi)
            $idDonVi = $record['id_don_vi_cong_tac'] ?? $record['id_don_vi'] ?? 0;
            $tenDonVi = $record['ten_don_vi'] ?? 'Chưa xác định';
            
            if (!isset($dataByDonVi[$idDonVi])) {
                $dataByDonVi[$idDonVi] = [
                    'ten_don_vi' => $tenDonVi,
                    'nhan_vien' => []
                ];
            }
            
            $maNhanVien = $record['ma_nhan_vien'] ?? '';
            if (!isset($dataByDonVi[$idDonVi]['nhan_vien'][$maNhanVien])) {
                $dataByDonVi[$idDonVi]['nhan_vien'][$maNhanVien] = [
                    'ho_va_ten' => $record['ho_va_ten'] ?? '',
                    'cham_cong' => []
                ];
            }
            
            $dataByDonVi[$idDonVi]['nhan_vien'][$maNhanVien]['cham_cong'][] = $record;
        }

        // Sắp xếp đơn vị theo tên (A-Z)
        uasort($dataByDonVi, function($a, $b) {
            return strcmp($a['ten_don_vi'], $b['ten_don_vi']);
        });

        // Định nghĩa các cột
        $titles = [
            'Mã NV',
            'Họ và tên',
            'Ngày',
            'Giờ vào',
            'Giờ ra',
            'Giờ làm sáng (h)',
            'Giờ làm chiều (h)',
            'Tổng giờ làm (h)',
            'Đi trễ (phút)',
            'Về sớm (phút)',
            'Tổng giờ nợ',
            'OT đã ĐK (giờ)'
        ];

        $sheetIndex = 0;

        // Tạo sheet cho từng đơn vị
        foreach ($dataByDonVi as $idDonVi => $donViData) {
            if ($sheetIndex > 0) {
                $objPHPExcel->createSheet();
            }

            $objPHPExcel->setActiveSheetIndex($sheetIndex);
            $sheet = $objPHPExcel->getActiveSheet();

            // Đặt tên sheet (giới hạn 31 ký tự, loại bỏ ký tự đặc biệt)
            $sheetName = substr(preg_replace('/[\/:*?\[\]]/', '', $donViData['ten_don_vi']), 0, 31);
            $sheet->setTitle($sheetName);

            // Tiêu đề chính
            $sheet->setCellValue('A1', 'Chi tiết chấm công - Đơn vị: ' . $donViData['ten_don_vi']);
            $sheet->mergeCells('A1:L1');
            $sheet->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '0000FF']],
                'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER],
            ]);

            $row = 3;
            
            // Sắp xếp nhân viên theo mã nhân viên
            ksort($donViData['nhan_vien']);

            // Duyệt qua từng nhân viên
            foreach ($donViData['nhan_vien'] as $maNhanVien => $nhanVienData) {
                if (empty($nhanVienData['cham_cong'])) {
                    continue;
                }

                // Sắp xếp chấm công theo ngày (mới nhất trước)
                usort($nhanVienData['cham_cong'], function($a, $b) {
                    return strcmp($b['ngay_cham_cong'] ?? '', $a['ngay_cham_cong'] ?? '');
                });

                // Dòng tên nhân viên
                $sheet->setCellValue('A' . $row, 'Nhân viên: ' . $nhanVienData['ho_va_ten'] . ' (' . $maNhanVien . ')');
                $sheet->mergeCells("A{$row}:L{$row}");
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '006600'], 'size' => 13],
                    'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT],
                    'fill' => [
                        'type' => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => ['rgb' => 'E8F5E9']
                    ]
                ]);
                $row++;

                // Header bảng
                $column = 'A';
                foreach ($titles as $title) {
                    $sheet->setCellValue($column . $row, $title);
                    $sheet->getStyle($column . $row)->applyFromArray([
                        'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
                        'font' => ['bold' => true, 'size' => 12],
                        'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER],
                        'fill' => [
                            'type' => PHPExcel_Style_Fill::FILL_SOLID,
                            'color' => ['rgb' => 'E8F4F8']
                        ]
                    ]);
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                    $column++;
                }
                $row++;

                // Biến tổng hợp
                $tongGioLamSang = 0;
                $tongGioLamChieu = 0;
                $tongGioLam = 0;
                $tongDiTre = 0;
                $tongVeSom = 0;
                $tongGioNo = 0;
                $tongOT = 0;

                // Dữ liệu chấm công
                foreach ($nhanVienData['cham_cong'] as $record) {
                    $sheet->setCellValue('A' . $row, $record['ma_nhan_vien'] ?? '');
                    $sheet->setCellValue('B' . $row, $record['ho_va_ten'] ?? '');
                    
                    // Format ngày theo dd/mm/yyyy
                    $ngayChamCong = $record['ngay_cham_cong'] ?? '';
                    if (!empty($ngayChamCong)) {
                        $timestamp = strtotime($ngayChamCong);
                        if ($timestamp !== false) {
                            $excelDate = PHPExcel_Shared_Date::PHPToExcel($timestamp);
                            $sheet->setCellValue('C' . $row, $excelDate);
                            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                        } else {
                            $sheet->setCellValue('C' . $row, $ngayChamCong);
                        }
                    }
                    
                    $sheet->setCellValue('D' . $row, $record['gio_vao'] ?? '');
                    $sheet->setCellValue('E' . $row, $record['gio_ra'] ?? '');
                    
                    // Format giờ làm theo HH:mm
                    $gioLamSang = $record['gio_lam_sang'] ?? 0;
                    $gioLamChieu = $record['gio_lam_chieu'] ?? 0;
                    $tongGioLamRecord = $record['tong_gio_lam'] ?? 0;
                    $diTre = round($record['gio_di_tre'] ?? 0);
                    $veSom = round($record['gio_ve_som'] ?? 0);
                    $tongNo = $diTre + $veSom;
                    
                    // Tính giờ bù
                    $gioBu = $this->calculateGioBu($record);
                    $gioOT = floatval($record['ot_so_gio_dang_ky'] ?? 0);
                    $tongGioBu = $gioBu + $gioOT;
                    $conThieu = ($tongNo / 60.0) - $tongGioBu; // Số giờ còn thiếu (âm = đã bù dư)
                    
                    // Hiển thị các cột theo format HH:mm
                    $sheet->setCellValue('F' . $row, $this->formatWorkTime($gioLamSang));
                    $sheet->setCellValue('G' . $row, $this->formatWorkTime($gioLamChieu));
                    $sheet->setCellValue('H' . $row, $this->formatWorkTime($tongGioLamRecord));
                    $sheet->setCellValue('I' . $row, $diTre);
                    $sheet->setCellValue('J' . $row, $veSom);
                    
                    // Cột Tổng giờ nợ: chỉ hiển thị nếu > 30 phút, format +/-HH:mm
                    if ($tongNo > 30) {
                        $prefix = $conThieu > 0.01 ? '-' : '+';
                        $sheet->setCellValue('K' . $row, $prefix . $this->formatWorkTime(abs($conThieu)));
                    } else {
                        $sheet->setCellValue('K' . $row, '—');
                    }
                    
                    $sheet->setCellValue('L' . $row, $this->formatWorkTime($gioOT));

                    // Cộng dồn (giữ nguyên để tính tổng)
                    $tongGioLamSang += $gioLamSang;
                    $tongGioLamChieu += $gioLamChieu;
                    $tongGioLam += $tongGioLamRecord;
                    $tongDiTre += $diTre;
                    $tongVeSom += $veSom;
                    $tongGioNo += $tongNo;
                    $tongOT += $gioOT;

                    // Border cho tất cả các ô
                    for ($col = 'A'; $col <= 'L'; $col++) {
                        $sheet->getStyle($col . $row)->applyFromArray([
                            'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
                            'font' => ['size' => 11],
                        ]);
                    }
                    
                    $row++;
                }

                // Dòng tổng hợp cho nhân viên
                $sheet->setCellValue('A' . $row, 'Tổng cộng');
                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->setCellValue('F' . $row, $this->formatWorkTime($tongGioLamSang));
                $sheet->setCellValue('G' . $row, $this->formatWorkTime($tongGioLamChieu));
                $sheet->setCellValue('H' . $row, $this->formatWorkTime($tongGioLam));
                $sheet->setCellValue('I' . $row, $tongDiTre);
                $sheet->setCellValue('J' . $row, $tongVeSom);
                $sheet->setCellValue('K' . $row, $tongGioNo > 30 ? $tongGioNo . 'p' : '—');
                $sheet->setCellValue('L' . $row, $this->formatWorkTime($tongOT));
                
                // Style cho dòng tổng
                for ($col = 'A'; $col <= 'L'; $col++) {
                    $sheet->getStyle($col . $row)->applyFromArray([
                        'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_MEDIUM]],
                        'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0000FF']],
                        'fill' => [
                            'type' => PHPExcel_Style_Fill::FILL_SOLID,
                            'color' => ['rgb' => 'FFF9C4']
                        ]
                    ]);
                }

                // Cách 2 dòng trống trước nhân viên tiếp theo
                $row += 2;
            }

            $sheetIndex++;
        }
    }
}