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



class Chamcong extends REST_INSTANCE_Controller
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


        $data = $this->Hrm_cham_cong_model->get_all_bang_cham_cong_thang($start, $length, $searchValue, $orderBy, $searchKey, $fromDate, $toDate, );

        resSuccess($data, 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }


    public function show_get($id_don_vi)
    {
        // $this->permissionMiddleware();
        $don_vi = $this->E_don_vi_model->find($id_don_vi);

        if (!$don_vi) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                'message' => 'Không tìm thấy đơn vị',
                'success' => false,
                'data' => null
            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }

        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_CREATED,
            'message' => 'Success',
            'success' => true,
            'data' => $don_vi,
        ], REST_INSTANCE_Controller::HTTP_CREATED);
    }


    public function export_get()
    {
        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : -1;

        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;
        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];
        $fromDate = commonRequest('fromDate') ? commonRequest('fromDate') : null;
        $toDate = commonRequest('toDate') ? commonRequest('toDate') : null;
        $nhanvienid = commonRequest('nhanvien') ? commonRequest('nhanvien') : null;
        $ca = commonRequest('ca') ? commonRequest('ca') : null;

        $data = $this->Hrm_cham_cong_model->getListExportChamcong($start, $length, $searchValue, $searchKey, $fromDate, $toDate, $nhanvienid, $ca);

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
        $sheet->setCellValue('A1', 'Thống kê chấm công'); // Thêm cột thông báo

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
        $directory = 'uploads/export/' . date('Y') . '/'; // Thư mục để lưu file
        $filename = 'chamcong_' . time() . '.xlsx'; // Tên file kèm timestamp để tránh trùng lặp
        $filePath = $directory . $filename;
        // Kiểm tra và tạo thư mục nếu chưa tồn tại
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);

        if (file_exists($filePath)) {
            $this->createLog('Export', 'Export danh sách chấm công', NULL, 'Export danh sách chấm công', 'hrm_cham_cong');
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

    private function getExcelColumn()
    {
        $cols = [
            '' => 'STT',
            'ho_va_ten' => 'Họ và tên',
            'ma_nhan_vien' => 'Mã chấm công',
            'so_ngay_cong' => 'Số ngày công',
        ];
        return $cols;
    }

    public function import_post() {}

    private function getAuthToken()
    {
        $auth_url = 'http://113.161.210.124:8888/api-token-auth/';
        $data = json_encode([
            'username' => 'namcantho',
            'password' => 'namcantho2024'
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $auth_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        return $result['token'] ?? null;
    }

    public function transactions_list_post()
    {
        $token = $this->getAuthToken();
        if (!$token) {
            echo "Không thể lấy token";
            return;
        }

        $start_time = commonRequest('start_time') ?? date('Y-m-d', strtotime('-2 day')) . ' 00:00:00';
        $end_time   = commonRequest('end_time') ?? date('Y-m-d') . ' 23:59:59';
        $emp_codes  = commonRequest('emp_code') ?? [];

        // $this->test_lay_giao_dich_3_lan(null, $start_time, $end_time, $token);

        if (!is_array($emp_codes)) {
            $emp_codes = explode(',', $emp_codes);
        }

        if (!empty($emp_codes)) {
            foreach ($emp_codes as $emp_code) {
                $this->dong_bo_giao_dich_theo_nhan_vien(trim($emp_code), $start_time, $end_time, $token);
            }
        } else {
            // Không có emp_code, đồng bộ tất cả
            $this->dong_bo_giao_dich_theo_nhan_vien(null, $start_time, $end_time, $token);
        }

        $this->response([
            'status'  => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Đồng bộ thành công',
            'success' => true,
            'data'    => null
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    private function dong_bo_giao_dich_theo_nhan_vien($emp_code, $start_time, $end_time, $token)
    {

        $start_time_encoded = urlencode($start_time);
        $end_time_encoded   = urlencode($end_time);
        $url = "http://113.161.210.124:8888/iclock/api/transactions/?start_time=$start_time_encoded&end_time=$end_time_encoded&page_size=20000";

        if ($emp_code) {
            $url .= "&emp_code=$emp_code";
        }

        $this->db->trans_start();
        $records = [];
        $pageCount = 0;

        while ($url) {
            $pageCount++;
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Token $token",
                    "Content-Type: application/json"
                ],
                CURLOPT_TIMEOUT => 300,
                CURLOPT_CONNECTTIMEOUT => 10
            ]);

            $response = curl_exec($ch);

            if ($response === false) {
                log_message('error', 'CURL Error: ' . curl_error($ch));
                curl_close($ch);
                break;
            }

            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code !== 200) {
                log_message('error', "API HTTP Error $http_code for URL: $url");
                break;
            }

            $data = json_decode($response, true);
            if (!isset($data['data']) || !is_array($data['data']) || empty($data['data'])) {
                break;
            }

            $records = array_merge($records, $data['data']);

            $url = isset($data['next']) && !empty($data['next']) ? $data['next'] : null;
        }

        if (!empty($records)) {
            // Lọc trùng theo id
            $all_ids = array_column($records, 'id');
            $existing_ids = $this->Hrm_api_cham_cong_model->getExistingIds($all_ids); // return danh sách id đã tồn tại
            $existing_ids = array_flip($existing_ids); // để kiểm tra nhanh

            // Giữ lại các bản ghi chưa tồn tại
            $filtered_records = array_filter($records, function ($record) use ($existing_ids) {
                return !isset($existing_ids[$record['id']]);
            });

            // Insert dữ liệu chưa trùng
            if (!empty($filtered_records)) {
                
                foreach (array_chunk($filtered_records, 1000) as $chunk) {
                    $this->Hrm_api_cham_cong_model->insertBatch($chunk);
                }
            }
        }

        $this->db->trans_complete();

        // echo json_encode([
        //     'tong_so' => count($records),
        //     'trang' => $pageCount,
        // ]);
        // die();
    }

    private function xac_dinh_ca($emp_code)
    {
        $ca_lam_viec = $this->db->select('clv.*')
            ->from('hrm_nhan_vien as nv')
            ->join('hrm_ca_lam_viec as clv', 'clv.id = nv.id_ca_lam_viec', 'left')
            ->where('nv.ma_nhan_vien', $emp_code)
            ->limit(1)
            ->get()
            ->row_array();

        if ($ca_lam_viec) {
            return $ca_lam_viec;
        } else {
            return null;
        }
    }

    private function tinh_so_phut_di_muon($punch_time, $ca_lam_viec)
    {
        // Lấy giờ check-in và thời gian muộn nhất từ ca làm việc
        $gio_check_in = strtotime($ca_lam_viec['check_in']);
        $gio_muon_nhat = strtotime($ca_lam_viec['bat_dau_check_in']);

        // Chuyển đổi giờ điểm đến
        $time = strtotime(date('H:i:s', strtotime($punch_time)));

        // Kiểm tra thời gian đi muộn và làm tròn xuống
        return ($time > $gio_check_in && $time <= $gio_muon_nhat) ? floor(($time - $gio_check_in) / 60) : 0;
    }

    private function tinh_so_phut_ve_som($punch_time, $ca_lam_viec)
    {
        // Lấy giờ check-out chuẩn và giờ về sớm từ ca làm việc
        $gio_check_out = strtotime($ca_lam_viec['check_out']);
        $gio_ve_som = strtotime($ca_lam_viec['bat_dau_check_out']);

        // Chuyển đổi giờ điểm đến
        $time = strtotime(date('H:i:s', strtotime($punch_time)));

        // Kiểm tra thời gian về sớm và làm tròn xuống
        return ($time >= $gio_check_out && $time < $gio_ve_som) ? floor(($gio_ve_som - $time) / 60) : 0;
    }

    private function tinh_so_phut_lam_them($punch_time, $ca_lam_viec)
    {
        // Lấy giờ check-out và giờ làm thêm từ ca làm việc
        $gio_check_out = strtotime($ca_lam_viec['check_out']);
        $gio_ket_thuc = strtotime($ca_lam_viec['ket_thuc_check_out']);

        // Chuyển đổi giờ điểm đến
        $time = strtotime(date('H:i:s', strtotime($punch_time)));

        // Kiểm tra thời gian làm thêm và làm tròn xuống
        return ($time > $gio_ket_thuc) ? floor(($time - $gio_ket_thuc) / 60) : 0;
    }



    public function tao_bang_cham_cong_thang_post()
    {
        $token = $this->getAuthToken();
        if (!$token) {
            echo "Không thể lấy token";
            return;
        }
      
        // $thang_dang_xet = commonRequest('thang_dang_xet') ?? date('Y-m'); // YYYY-MM
        $thang_dang_xet = commonRequest('thang_dang_xet') ?? date('Y-m');
        $thang_truoc = (new DateTime("$thang_dang_xet-01"))->modify('-1 month');

        $ngay_bat_dau_input = commonRequest('ngay_bat_dau');
        $ngay_ket_thuc_input = commonRequest('ngay_ket_thuc');
        $ten_bang = commonRequest('ten_bang') ? commonRequest('ten_bang') : 'BangChamCongThang_' . $thang_dang_xet;
       

        $ngay_bat_dau = $ngay_bat_dau_input ? new DateTime($ngay_bat_dau_input): new DateTime($thang_truoc->format('Y-m') . '-23');

        $ngay_ket_thuc = $ngay_ket_thuc_input ? new DateTime($ngay_ket_thuc_input): new DateTime("$thang_dang_xet-22");

        $this->dong_bo_giao_dich_theo_nhan_vien(
            null,
            $ngay_bat_dau->format('Y-m-d 00:00:00'),
            $ngay_ket_thuc->format('Y-m-d 23:59:59'),
            $token
        );

        $data_cham_cong = $this->Hrm_cham_cong_model->get_data($ngay_bat_dau->format('Y-m-d 00:00:00'), $ngay_ket_thuc->format('Y-m-d 23:59:59'));

        $id_bang_cham_cong_thang = $this->tao_bang_cham_cong_thang($thang_dang_xet, $ngay_bat_dau->format('Y-m-d 00:00:00'), $ngay_ket_thuc->format('Y-m-d 23:59:59'), $ten_bang);

        $this->dem_ngay_cong($data_cham_cong, $thang_dang_xet, 26,$id_bang_cham_cong_thang);

        resSuccess(true, 'Success', REST_Controller::HTTP_OK, true);
    }

    function tao_bang_cham_cong_thang($thang_dang_xet, $ngay_bat_dau, $ngay_ket_thuc, $ten_bang)
    {
        // Tạo bảng tổng quát trước
        $bang_tong_quat = [
            'thang' => $thang_dang_xet,
            'ten_bang' => $ten_bang,
            'ngay_bat_dau' => $ngay_bat_dau,
            'ngay_ket_thuc' => $ngay_ket_thuc,
            'trang_thai' => 'dang_cho_duyet',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert('hrm_bang_cham_cong_thang', $bang_tong_quat);

        return $this->db->insert_id();
    }

    /**
     * Đếm số ngày công của từng nhân viên theo tháng.
     *
     * @param array $records Dữ liệu đồng bộ từ ZKBio.
     * @param string $thang_dang_xet Dạng 'YYYY-MM', ví dụ: '2025-04'.
     * 
     * @param int $so_ngay_chuan Số ngày công tiêu chuẩn, mặc định là 26.
     * @return array Mảng kết quả: [emp_code => ['so_ngay_cong' => int, 'du_cong' => true|false]]
     */
    function dem_ngay_cong(array $records, string $thang_dang_xet, int $so_ngay_chuan = 26 , $id_bang_cham_cong_thang): array
    {
        $cham_cong_theo_ngay = [];

        // ✅ Bước 1: Xác định khoảng thời gian từ ngày 23 tháng trước đến 22 tháng đang xét
        $thang_truoc = (new DateTime("$thang_dang_xet-01"))->modify('-1 month');
        $ngay_bat_dau = new DateTime($thang_truoc->format('Y-m') . '-23');
        $ngay_ket_thuc = new DateTime("$thang_dang_xet-22");

        // ✅ Bước 2: Gom dữ liệu theo ngày và nhân viên, lọc theo khoảng ngày trên
        foreach ($records as $record) {
            $ngay = substr($record['punch_time'], 0, 10); // yyyy-mm-dd
            $ma_nv = $record['emp_code'];

            if ($ngay >= $ngay_bat_dau->format('Y-m-d') && $ngay <= $ngay_ket_thuc->format('Y-m-d')) {
                $cham_cong_theo_ngay[$ma_nv][$ngay] = true;
            }
        }

        // echo json_encode($cham_cong_theo_ngay);
        // die();

        // ✅ Bước 3: Đếm số ngày công thực tế cho từng nhân viên (từ T2 đến T7)
        $ket_qua = [];
        foreach ($cham_cong_theo_ngay as $ma_nv => $danh_sach_ngay) {
            $so_ngay_cong = 0;

            $date = clone $ngay_bat_dau;
            while ($date <= $ngay_ket_thuc) {
                $ngay = $date->format('Y-m-d');
                $thu = $date->format('N'); // 1=Thứ 2, 7=Chủ nhật

                if ($thu <= 6 && isset($danh_sach_ngay[$ngay])) {
                    $so_ngay_cong++;
                }

                $date->modify('+1 day');
            }

            $ket_qua[$ma_nv] = [
                'so_ngay_cong' => $so_ngay_cong,
                'du_cong' => $so_ngay_cong >= $so_ngay_chuan
            ];

            // Thêm vào bảng hrm_cham_cong_chi_tiet
            $this->db->insert('hrm_cham_cong_chi_tiet', [
                'ma_nhan_vien' => $ma_nv,
                'id_bang_cham_cong_thang' => $id_bang_cham_cong_thang,
                'so_ngay_cong' => $so_ngay_cong,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        return $ket_qua;
    }

    public function get_chi_tiet_cham_cong_get()
    {
        $id_bang_cham_cong_thang = commonRequest('id_bang_cham_cong_thang') ? commonRequest('id_bang_cham_cong_thang') : null;
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
       
        $data = $this->Hrm_cham_cong_model->get_chi_tiet_cham_cong($start, $length, $searchValue, $orderBy, $searchKey, $fromDate, $toDate, $id_bang_cham_cong_thang);

        resSuccess($data, 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function duyet_post()
    {
        $id = commonRequest('id_bang_cham_cong');
        $user_id = $this->getUserLogin()['ql_nguoi_dung_id'];

        if (!$id) {
            return resError('Thiếu ID bảng chấm công.', REST_Controller::HTTP_BAD_REQUEST);
        }

        // Cập nhật trạng thái duyệt
        $update = $this->Hrm_cham_cong_model->update_bang_cham_cong($id, [
            'trang_thai' => Common::TRANG_THAI_BANG_CHAM_CONG['DA_DUYET']['value'],
            'nguoi_duyet' => $user_id,
            'ngay_duyet' => date('Y-m-d H:i:s')
        ]);

        if ($update) {
            return resSuccess(null, 'Duyệt bảng chấm công thành công.');
        } else {
            return resError('Không thể duyệt bảng chấm công.', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function get_trang_thai_cham_cong_post()
    {
        $id = commonRequest('id_bang_cham_cong');
        if (!$id) {
            return resError('Thiếu ID bảng chấm công.', REST_Controller::HTTP_BAD_REQUEST);
        }

        $trang_thai = $this->Hrm_cham_cong_model->get_trang_thai_bang_cham_cong($id);

        if ($trang_thai) {
            return resSuccess($trang_thai, 'Lấy trạng thái bảng chấm công thành công.');
        } else {
            return resError('Không tìm thấy trạng thái bảng chấm công.', REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function khoa_post()
    {
        $id = commonRequest('id_bang_cham_cong');
        $user_id = $this->getUserLogin()['ql_nguoi_dung_id'];

        if (!$id) {
            return resError('Thiếu ID bảng chấm công.', REST_Controller::HTTP_BAD_REQUEST);
        }

        // Cập nhật trạng thái khóa
        $update = $this->Hrm_cham_cong_model->update_bang_cham_cong($id, [
            'trang_thai' => Common::TRANG_THAI_BANG_CHAM_CONG['KHOA']['value'],
            'nguoi_duyet' => $user_id,
            'ngay_duyet' => date('Y-m-d H:i:s')
        ]);

        if ($update) {
            return resSuccess(null, 'Khóa bảng chấm công thành công.');
        } else {
            return resError('Không thể khóa bảng chấm công.', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function export_chi_tiet_post()
    {
      
        $id_bang_cham_cong_thang = commonRequest('id_bang_cham_cong_thang') ? commonRequest('id_bang_cham_cong_thang') : null;
        $start =  0;
        $length = -1;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;
        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];
        $fromDate = commonRequest('fromDate') ? commonRequest('fromDate') : null;
        $toDate = commonRequest('toDate') ? commonRequest('toDate') : null;

        $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
            'order' => commonRequest('order'),
            'columns' => commonRequest('columns')
        ] : [];
       
        $data = $this->Hrm_cham_cong_model->getListExportChamcongChiTiet($start, $length, $searchValue, $orderBy, $searchKey, $fromDate, $toDate, $id_bang_cham_cong_thang);

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
        $sheet->setCellValue('A1', 'Thống kê chấm công'); // Thêm cột thông báo

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
        $directory = 'uploads/export/' . date('Y') . '/'; // Thư mục để lưu file
        $filename = 'chamcong_' . time() . '.xlsx'; // Tên file kèm timestamp để tránh trùng lặp
        $filePath = $directory . $filename;
        // Kiểm tra và tạo thư mục nếu chưa tồn tại
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);

        if (file_exists($filePath)) {
            $this->createLog('Export', 'Export danh sách chấm công', NULL, 'Export danh sách chấm công', 'hrm_cham_cong');
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

    public function get_chi_tiet_tung_nhan_vien_theo_thang_get()
    {
        $ma_nhan_vien = commonRequest('ma_nhan_vien');
        $id_bang_cham_cong_thang = commonRequest('id_bang_cham_cong_thang');

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

        if (empty($ma_nhan_vien) || empty($id_bang_cham_cong_thang)) {
            return resError('Thiếu mã nhân viên hoặc ID bảng chấm công.', REST_Controller::HTTP_BAD_REQUEST);
        }

        // Gọi hàm model để lấy dữ liệu chấm công chi tiết theo nhân viên và bảng chấm công tháng
        $data = $this->Hrm_cham_cong_model->getChamCongApiByNhanVienAndBangChamCong($ma_nhan_vien,$id_bang_cham_cong_thang, $start, $length, $searchValue, $orderBy, $searchKey, $fromDate, $toDate);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    // public function export_chi_tiet_tung_thanh_vien_post()
    // {
    //     $ma_nhan_vien = commonRequest('ma_nhan_vien');
    //     $id_bang_cham_cong_thang = commonRequest('id_bang_cham_cong_thang');

    //     $start = 0;
    //     $length = -1;
    //     $searchValue = null;
    //     $searchKey = [];
    //     $fromDate = null;
    //     $toDate = null;
    //     $orderBy = [];

    //     if (empty($ma_nhan_vien) || empty($id_bang_cham_cong_thang)) {
    //         return resError('Thiếu mã nhân viên hoặc ID bảng chấm công.', REST_Controller::HTTP_BAD_REQUEST);
    //     }
    //     // Lấy dữ liệu chi tiết chấm công theo nhân viên và bảng chấm công tháng
    //     $data = $this->Hrm_cham_cong_model->getChamCongApiByNhanVienAndBangChamCong(
    //         $ma_nhan_vien,
    //         $id_bang_cham_cong_thang,
    //         $start,
    //         $length,
    //         $searchValue,
    //         $orderBy,
    //         $searchKey,
    //         $fromDate,
    //         $toDate
    //     );

    //     // Lấy thông tin nhân viên theo mã chấm công (ma_nhan_vien)
    //     $nhanvien = $this->Hrm_nhan_vien_model
    //         ->select('hrm_vi_tri_cong_viec.*, e_don_vi.*, hrm_nhan_vien_cong_viec.*,
    //           province.name as province_name, 
    //           district.name as district_name, 
    //           wards.name as ward_name, dm_quoc_gia.ten as ten_quoc_gia, hrm_nhan_vien.*')
    //         ->join('hrm_vi_tri_cong_viec', 'hrm_nhan_vien.id_vi_tri_cong_viec = hrm_vi_tri_cong_viec.id_vi_tri_cong_viec', 'left')
    //         ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_nhan_vien.id_don_vi_cong_tac', 'left')
    //         ->join('hrm_nhan_vien_cong_viec', 'hrm_nhan_vien_cong_viec.id_nhan_vien = hrm_nhan_vien.id_nhan_vien', 'left')
    //         ->join('dm_quoc_gia', 'dm_quoc_gia.id_quoc_gia = hrm_nhan_vien.cohn_id_quoc_gia', 'left')
    //         ->join('province', 'province.id = hrm_nhan_vien.cohn_id_tinh_tp', 'left')
    //         ->join('district', 'district.id = hrm_nhan_vien.cohn_id_quan_huyen', 'left')
    //         ->join('wards', 'wards.id = hrm_nhan_vien.cohn_id_xa_phuong', 'left')
    //         ->where('hrm_nhan_vien.ma_cham_cong', $ma_nhan_vien)
    //         ->first();

    //     // dd($nhanvien);

    //     // Định nghĩa tiêu đề cột cho Excel
    //     $titles = [
    //         'thu' => 'Thứ',
    //         'ngay' => 'Ngày',
    //         'gio_vao' => 'Giờ vào', 
    //         'gio_nghi_trua_bat_dau' => 'Giờ nghỉ trưa (bắt đầu)',
    //         'gio_nghi_trua_ket_thuc' => 'Giờ nghỉ trưa (kết thúc)',
    //         'gio_ra' => 'Giờ ra', 
    //         'di_muon' => 'Số phút đi trễ',
    //         've_som' => 'Số phút về sớm',
    //         'so_phut_lam_them' => 'Số phút làm thêm',
    //     ];

    //     $objPHPExcel = new PHPExcel();
    //     $objPHPExcel->setActiveSheetIndex(0);
    //     $sheet = $objPHPExcel->getActiveSheet();
    //     $sheet->setTitle($ma_nhan_vien);

    //     // Tiêu đề file
    //     $sheet->setCellValue('A1', 'Chi tiết chấm công nhân viên ' . $ma_nhan_vien);
    //     $sheet->mergeCells('A1:I1');
    //     $sheet->getStyle('A1')->applyFromArray([
    //         'font' => [
    //             'bold' => true,
    //             'size' => 16,
    //             'color' => ['rgb' => '0000FF']
    //         ],
    //         'alignment' => [
    //             'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
    //         ]
    //     ]);

    //     // Tiêu đề cột
    //     $column = 'A';
    //     foreach ($titles as $key => $title) {
    //         $sheet->setCellValue($column . '2', $title);
    //         $sheet->getStyle($column . '2')->applyFromArray([
    //             'borders' => [
    //                 'allborders' => [
    //                     'style' => PHPExcel_Style_Border::BORDER_THIN,
    //                     'color' => ['rgb' => '000000'],
    //                 ],
    //             ],
    //             'font' => [
    //                 'bold' => true,
    //                 'size' => 13,
    //             ],
    //             'alignment' => [
    //                 'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
    //             ]
    //         ]);
    //         $sheet->getColumnDimension($column)->setAutoSize(true);
    //         $column++; 
    //     }

    //     // Dữ liệu
    //     $row = 3;
    //     foreach ($data['data'] ?? [] as $item) {
    //         $column = 'A';
    //         foreach ($titles as $key => $title) {
    //             $sheet->setCellValue($column . $row, isset($item[$key]) ? $item[$key] : '');
    //             $sheet->getStyle($column . $row)->applyFromArray([
    //                 'borders' => [
    //                     'allborders' => [
    //                         'style' => PHPExcel_Style_Border::BORDER_THIN,
    //                         'color' => ['rgb' => '000000'],
    //                     ],
    //                 ],
    //                 'font' => [
    //                     'size' => 13,
    //                 ],
    //             ]);
    //             $sheet->getStyle($column . $row)->getAlignment()->setWrapText(true);
    //             $column++;
    //         }
    //         $row++;
    //     }

    //     // Lưu file
    //     $directory = 'uploads/export/' . date('Y') . '/';
    //     $filename = 'chamcong_chitiet_' . $ma_nhan_vien . '_' . time() . '.xlsx';
    //     $filePath = $directory . $filename;
    //     if (!is_dir($directory)) {
    //         mkdir($directory, 0755, true);
    //     }

    //     $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    //     $objWriter->save($filePath);


    //     if (file_exists($filePath)) {
    //         $this->createLog('Export', 'Export chi tiết chấm công nhân viên', NULL, 'Export chi tiết chấm công', 'hrm_cham_cong');
    //         $this->response([
    //             'status' => REST_INSTANCE_Controller::HTTP_OK,
    //             'message' => 'Success',
    //             'success' => true,
    //             'data' => base_url($filePath)
    //         ], REST_INSTANCE_Controller::HTTP_OK);
    //     } else {
    //         $this->response([
    //             'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
    //             'message' => 'File not found',
    //             'success' => false,
    //             'data' => null
    //         ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
    //     }
    // }

    // public function export_chi_tiet_tung_thanh_vien_post()
    // {
    //     $ma_nhan_vien_str = commonRequest('ma_nhan_vien');
    //     $id_bang_cham_cong_thang = commonRequest('id_bang_cham_cong_thang');
    //     $id_don_vi = commonRequest('id_don_vi');

    //     if (empty($ma_nhan_vien_str) || empty($id_bang_cham_cong_thang)) {
    //         return resError('Thiếu mã nhân viên hoặc ID bảng chấm công.', REST_Controller::HTTP_BAD_REQUEST);
    //     }

    //     $ma_nhan_vien_list = array_filter(array_map('trim', explode(',', $ma_nhan_vien_str)));

    //     if (empty($ma_nhan_vien_list)) {
    //         return resError('Danh sách mã nhân viên không hợp lệ.', REST_Controller::HTTP_BAD_REQUEST);
    //     }

    //     $objPHPExcel = new PHPExcel();
    //     $sheetIndex = 0;

    //     $titles = [
    //         'thu' => 'Thứ',
    //         'ngay' => 'Ngày',
    //         'gio_vao' => 'Giờ vào',
    //         'gio_nghi_trua_bat_dau' => 'Giờ nghỉ trưa (bắt đầu)',
    //         'gio_nghi_trua_ket_thuc' => 'Giờ nghỉ trưa (kết thúc)',
    //         'gio_ra' => 'Giờ ra',
    //         'di_muon' => 'Số phút đi trễ',
    //         've_som' => 'Số phút về sớm',
    //         'so_phut_lam_them' => 'Số phút làm thêm',
    //     ];

    //     foreach ($ma_nhan_vien_list as $ma_nhan_vien) {
    //         // Lấy thông tin nhân viên
    //         $nhanvien = $this->Hrm_nhan_vien_model
    //             ->select('hrm_vi_tri_cong_viec.*, e_don_vi.*, hrm_nhan_vien_cong_viec.*,
    //           province.name as province_name, 
    //           district.name as district_name, 
    //           wards.name as ward_name, dm_quoc_gia.ten as ten_quoc_gia, hrm_nhan_vien.*')
    //             ->join('hrm_vi_tri_cong_viec', 'hrm_nhan_vien.id_vi_tri_cong_viec = hrm_vi_tri_cong_viec.id_vi_tri_cong_viec', 'left')
    //             ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_nhan_vien.id_don_vi_cong_tac', 'left')
    //             ->join('hrm_nhan_vien_cong_viec', 'hrm_nhan_vien_cong_viec.id_nhan_vien = hrm_nhan_vien.id_nhan_vien', 'left')
    //             ->join('dm_quoc_gia', 'dm_quoc_gia.id_quoc_gia = hrm_nhan_vien.cohn_id_quoc_gia', 'left')
    //             ->join('province', 'province.id = hrm_nhan_vien.cohn_id_tinh_tp', 'left')
    //             ->join('district', 'district.id = hrm_nhan_vien.cohn_id_quan_huyen', 'left')
    //             ->join('wards', 'wards.id = hrm_nhan_vien.cohn_id_xa_phuong', 'left')
    //             ->where('hrm_nhan_vien.ma_cham_cong', $ma_nhan_vien)
    //             ->first();
    //         $ten_nhan_vien = $nhanvien['ho_va_ten'] ?? 'Không rõ';

    //         // Lấy dữ liệu chấm công
    //         $data = $this->Hrm_cham_cong_model->getChamCongApiByNhanVienAndBangChamCong(
    //             $ma_nhan_vien,
    //             $id_bang_cham_cong_thang,
    //             0,
    //             -1,
    //             null,
    //             [],
    //             [],
    //             null,
    //             null
    //         );

    //         if ($sheetIndex > 0) {
    //             $objPHPExcel->createSheet();
    //         }

    //         $objPHPExcel->setActiveSheetIndex($sheetIndex);
    //         $sheet = $objPHPExcel->getActiveSheet();

    //         // Đặt tên sheet, giới hạn 31 ký tự
    //         $sheet_name = substr($ma_nhan_vien . ' - ' . $ten_nhan_vien, 0, 31);
    //         $sheet->setTitle($sheet_name);

    //         // Tiêu đề
    //         $sheet->setCellValue('A1', 'Chi tiết chấm công nhân viên: ' . $ten_nhan_vien . ' (' . $ma_nhan_vien . ')');
    //         $sheet->mergeCells('A1:I1');
    //         $sheet->getStyle('A1')->applyFromArray([
    //             'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '0000FF']],
    //             'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER],
    //         ]);

    //         // Tiêu đề cột
    //         $column = 'A';
    //         foreach ($titles as $key => $title) {
    //             $sheet->setCellValue($column . '2', $title);
    //             $sheet->getStyle($column . '2')->applyFromArray([
    //                 'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
    //                 'font' => ['bold' => true, 'size' => 13],
    //                 'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER],
    //             ]);
    //             $sheet->getColumnDimension($column)->setAutoSize(true);
    //             $column++;
    //         }

    //         // Ghi dữ liệu
    //         $row = 3;
    //         foreach ($data['data'] ?? [] as $item) {
    //             $column = 'A';
    //             foreach ($titles as $key => $title) {
    //                 $sheet->setCellValue($column . $row, isset($item[$key]) ? $item[$key] : '');
    //                 $sheet->getStyle($column . $row)->applyFromArray([
    //                     'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
    //                     'font' => ['size' => 13],
    //                 ]);
    //                 $sheet->getStyle($column . $row)->getAlignment()->setWrapText(true);
    //                 $column++;
    //             }
    //             $row++;
    //         }

    //         $sheetIndex++;
    //     }

    //     // Lưu file
    //     $directory = 'uploads/export/' . date('Y') . '/';
    //     $filename = 'chamcong_chitiet_' . time() . '.xlsx';
    //     $filePath = $directory . $filename;

    //     if (!is_dir($directory)) {
    //         mkdir($directory, 0755, true);
    //     }

    //     $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    //     $objWriter->save($filePath);

    //     if (file_exists($filePath)) {
    //         $this->createLog('Export', 'Export chi tiết chấm công nhiều nhân viên', NULL, 'Export chi tiết chấm công', 'hrm_cham_cong');
    //         $this->response([
    //             'status' => REST_Controller::HTTP_OK,
    //             'message' => 'Success',
    //             'success' => true,
    //             'data' => base_url($filePath)
    //         ], REST_Controller::HTTP_OK);
    //     } else {
    //         $this->response([
    //             'status' => REST_Controller::HTTP_INTERNAL_SERVER_ERROR,
    //             'message' => 'Không thể tạo file Excel.',
    //             'success' => false,
    //             'data' => null
    //         ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    public function export_chi_tiet_tung_thanh_vien_post()
    {
        $ma_nhan_vien_str = commonRequest('ma_nhan_vien');
        $id_bang_cham_cong_thang = commonRequest('id_bang_cham_cong_thang');


        if (empty($ma_nhan_vien_str) || empty($id_bang_cham_cong_thang)) {
            return resError('Thiếu mã nhân viên hoặc ID bảng chấm công.', REST_Controller::HTTP_BAD_REQUEST);
        }
        $ma_nhan_vien_list = array_filter(array_map('trim', explode(',', $ma_nhan_vien_str)));

        if (empty($ma_nhan_vien_list)) {
            return resError('Danh sách mã nhân viên không hợp lệ.', REST_Controller::HTTP_BAD_REQUEST);
        }

        $objPHPExcel = new PHPExcel();
        $sheetIndex = 0;

        $titles = [
            'thu' => 'Thứ',
            'ngay' => 'Ngày',
            'gio_vao' => 'Giờ vào',
            'gio_nghi_trua_bat_dau' => 'Nghỉ trưa bắt đầu',
            'gio_nghi_trua_ket_thuc' => 'Nghỉ trưa kết thúc',
            'gio_ra' => 'Giờ ra',
            'di_muon' => 'Đi trễ',
            've_som' => 'Về sớm',
            'so_phut_lam_them_ngay_thuong' => 'Số phút làm thêm (ngày thường)',
            'so_phut_lam_them_chu_nhat' => 'Số phút làm thêm (ngày chủ nhật)'
        ];
        foreach ($ma_nhan_vien_list as $ma_nhan_vien) {
            // Lấy thông tin nhân viên
            $nhanvien = $this->Hrm_nhan_vien_model
                ->select('hrm_vi_tri_cong_viec.*, e_don_vi.*, hrm_nhan_vien_cong_viec.*, hrm_nhan_vien.*')
                ->join('hrm_vi_tri_cong_viec', 'hrm_nhan_vien.id_vi_tri_cong_viec = hrm_vi_tri_cong_viec.id_vi_tri_cong_viec', 'left')
                ->join('e_don_vi', 'e_don_vi.id_don_vi = hrm_nhan_vien.id_don_vi_cong_tac', 'left')
                ->join('hrm_nhan_vien_cong_viec', 'hrm_nhan_vien_cong_viec.id_nhan_vien = hrm_nhan_vien.id_nhan_vien', 'left')
                ->where('hrm_nhan_vien.ma_cham_cong', $ma_nhan_vien)
                ->first();            

            $ten_nhan_vien = $nhanvien['ho_va_ten'] ?? 'Không rõ';
            // Lấy dữ liệu chấm công
            // Nếu mã nhân viên null hoặc rỗng thì không lấy dữ liệu, để sheet trống
            if (empty($ma_nhan_vien)) {
                $data = ['data' => []];
            } else {
                $data = $this->Hrm_cham_cong_model->getChamCongApiByNhanVienAndBangChamCong(
                    $ma_nhan_vien,
                    $id_bang_cham_cong_thang,
                    0,
                    -1,
                    null,
                    [],
                    [],
                    null,
                    null
                );
            }

            if ($sheetIndex > 0) {
                $objPHPExcel->createSheet();
            }

            $objPHPExcel->setActiveSheetIndex($sheetIndex);
            $sheet = $objPHPExcel->getActiveSheet();

            // Đặt tên sheet, giới hạn 31 ký tự
            $sheet_name_raw = $ma_nhan_vien . ' - ' . $ten_nhan_vien;
            $sheet_name_raw = substr(preg_replace('/[\/:*?\[\]]/', '', $sheet_name_raw), 0, 31);
            $sheet_name = mb_convert_encoding($sheet_name_raw, 'UTF-8', 'auto');
            $sheet->setTitle($sheet_name);

            // Tiêu đề
            $sheet->setCellValue('A1', 'Chi tiết chấm công nhân viên: ' . $ten_nhan_vien . ' (' . $ma_nhan_vien . ')');
            $sheet->mergeCells('A1:J1');
            $sheet->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '0000FF']],
                'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER],
            ]);

            // Tiêu đề cột
            $column = 'A';
            foreach ($titles as $key => $title) {
                $sheet->setCellValue($column . '2', $title);
                $sheet->getStyle($column . '2')->applyFromArray([
                    'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
                    'font' => ['bold' => true, 'size' => 13],
                    'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getColumnDimension($column)->setAutoSize(true);
                $column++;
            }

            // Ghi dữ liệu
            $row = 3;
            foreach ($data['data'] ?? [] as $item) {
                $column = 'A';
                foreach ($titles as $key => $title) {
                    $sheet->setCellValue($column . $row, isset($item[$key]) ? $item[$key] : '');
                    $sheet->getStyle($column . $row)->applyFromArray([
                        'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
                        'font' => ['size' => 13],
                    ]);
                    $sheet->getStyle($column . $row)->getAlignment()->setWrapText(true);
                    $column++;
                }
                $row++;
            }

            $sheetIndex++;
        }

        // Lưu file
        $directory = 'uploads/export/' . date('Y') . '/';
        $filename = 'chamcong_chitiet_' . time() . '.xlsx';
        $filePath = $directory . $filename;
      
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);
        if (file_exists($filePath)) {
            $this->createLog('Export', 'Export chi tiết chấm công nhiều nhân viên', NULL, 'Export chi tiết chấm công', 'hrm_cham_cong');
            $this->response([
                'status' => REST_Controller::HTTP_OK,
                'message' => 'Success',
                'success' => true,
                'data' => base_url($filePath)
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => REST_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Không thể tạo file Excel.',
                'success' => false,
                'data' => null
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function export_chi_tiet_theo_don_vi_post()
    {
        $id_bang_cham_cong_thang = commonRequest('id_bang_cham_cong_thang');
        $id_don_vi = commonRequest('id_don_vi');

        

        if (empty($id_bang_cham_cong_thang)) {
            return resError('Thiếu ID bảng chấm công.', REST_Controller::HTTP_BAD_REQUEST);
        }

        $don_vi_list = [];

        if ($id_don_vi == -1) {
            // Lấy tất cả đơn vị
            $don_vi_list = $this->E_don_vi_model->select('id_don_vi, ten_don_vi')->get();
        } else {
            $id_don_vi_arr = array_filter(array_map('trim', explode(',', $id_don_vi)));
            if (empty($id_don_vi_arr)) {
                return resError('Danh sách đơn vị không hợp lệ.', REST_Controller::HTTP_BAD_REQUEST);
            }
            $this->E_don_vi_model->whereIn('id_don_vi', $id_don_vi_arr);
            $don_vi_list = $this->E_don_vi_model->select('id_don_vi, ten_don_vi')->get();
        }

        if (empty($don_vi_list)) {
            return resError('Không tìm thấy đơn vị.', REST_Controller::HTTP_NOT_FOUND);
        }


        $titles = [
            'ma_cham_cong' => 'Mã chấm công',
            'ho_va_ten' => 'Họ và tên',
            'thu' => 'Thứ',
            'ngay' => 'Ngày',
            'gio_vao' => 'Giờ vào',
            'gio_nghi_trua_bat_dau' => 'Nghỉ trưa bắt đầu',
            'gio_nghi_trua_ket_thuc' => 'Nghỉ trưa kết thúc',
            'gio_ra' => 'Giờ ra',
            'di_muon' => 'Đi trễ',
            've_som' => 'Về sớm',
            'so_phut_lam_them_ngay_thuong' => 'Số phút làm thêm (ngày thường)',
            'so_phut_lam_them_chu_nhat' => 'Số phút làm thêm (ngày chủ nhật)'
        ];

        $objPHPExcel = new PHPExcel();
        $sheetIndex = 0;

        foreach ($don_vi_list as $don_vi) {
            if ($sheetIndex > 0) {
                $objPHPExcel->createSheet();
            }

            $objPHPExcel->setActiveSheetIndex($sheetIndex);
            $sheet = $objPHPExcel->getActiveSheet();

            $sheet_name_raw = substr(preg_replace('/[\/:*?\[\]]/', '', $don_vi['ten_don_vi']), 0, 31);
            $sheet_name = mb_convert_encoding($sheet_name_raw, 'UTF-8', 'auto');
            $sheet->setTitle($sheet_name);

            $sheet->setCellValue('A1', 'Chi tiết chấm công - Đơn vị: ' . $don_vi['ten_don_vi']);
            $sheet->mergeCells('A1:K1');
            $sheet->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '0000FF']],
                'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER],
            ]);

            // Lấy danh sách nhân viên
            $this->Hrm_nhan_vien_model->select('id_nhan_vien, ma_cham_cong, ho_va_ten');
            $this->Hrm_nhan_vien_model->where('id_don_vi_cong_tac', $don_vi['id_don_vi']);
            $nhan_vien_list = $this->Hrm_nhan_vien_model->get();

            // Header
            // $column = 'A';
            // foreach ($titles as $title) {
            //     $sheet->setCellValue($column . '2', $title);
            //     $sheet->getStyle($column . '2')->applyFromArray([
            //         'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
            //         'font' => ['bold' => true],
            //         'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER],
            //     ]);
            //     $sheet->getColumnDimension($column)->setAutoSize(true);
            //     $column++;
            // }

            $row = 3;
            foreach ($nhan_vien_list as $nv) {
                if (!empty($nv['ma_cham_cong'])) {
                    $data = $this->Hrm_cham_cong_model->getChamCongApiByNhanVienAndBangChamCong(
                        $nv['ma_cham_cong'],
                        $id_bang_cham_cong_thang,
                        0,
                        -1,
                        null,
                        [],
                        [],
                        null,
                        null
                    );
                    

                    // Ghi tên nhân viên làm tiêu đề nhỏ
                    if (!empty($data['data'])) {
                        $first_row = reset($data['data']);
                        $ca_lam_viec = $first_row['ca_lam_viec'] ?? '';
                        $check_in = $first_row['check_in'] ?? '';
                        $ket_thuc_check_in = $first_row['ket_thuc_check_in'] ?? '';

                        $check_out = $first_row['check_out'] ?? '';
                        $bat_dau_check_out = $first_row['bat_dau_check_out'] ?? '';

                        $info = [];
                        if ($ca_lam_viec) $info[] = "Ca làm việc: $ca_lam_viec";
                        if ($check_in) $info[] = "Check in buổi sáng: $check_in";
                        if ($ket_thuc_check_in) $info[] = "Check out buổi sáng: $ket_thuc_check_in";
                        if ($check_out) $info[] = "Check out buổi chiều: $check_out";
                        if ($bat_dau_check_out) $info[] = "Check in buổi chiều: $bat_dau_check_out";

                        if (!empty($info)) {
                            $sheet->setCellValue('A' . $row, implode(' | ', $info));
                            $sheet->mergeCells("A{$row}:K{$row}");
                            $sheet->getStyle("A{$row}")->applyFromArray([
                                'font' => ['italic' => true, 'color' => ['rgb' => '000080'] , 'size' => 13],
                                'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT],
                            ]);
                            $row++;
                        }
                    }

                    $sheet->setCellValue('A' . $row, 'Nhân viên: ' . $nv['ho_va_ten'] . ' (' . $nv['ma_cham_cong'] . ')');
                    $sheet->mergeCells("A{$row}:K{$row}");
                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '006600'], 'size' => 13],
                        'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT],
                    ]);
                    
                    $row++;

                    // Ghi header của bảng
                    $column = 'A';
                    foreach ($titles as $title) {
                        $sheet->setCellValue($column . $row, $title);
                        $sheet->getStyle($column . $row)->applyFromArray([
                            'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
                            'font' => ['bold' => true,  'size' => 13],
                            'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER],
                        ]);
                        $sheet->getColumnDimension($column)->setAutoSize(true);
                        $column++;
                    }
                    $row++;

                    // Ghi dữ liệu chấm công
                    foreach ($data['data'] ?? [] as $item) {
                        $column = 'A';
                        $sheet->setCellValue($column++ . $row, $nv['ma_cham_cong']);
                        $sheet->setCellValue($column++ . $row, $nv['ho_va_ten']);
                      
                        foreach (array_keys($titles) as $key) {
                            if ($key !== 'ma_cham_cong' && $key !== 'ho_va_ten') {
                                $sheet->setCellValue($column . $row, $item[$key] ?? '');
                                // Thêm border cho từng ô
                                $sheet->getStyle($column . $row)->applyFromArray([
                                    'borders' => [
                                        'allborders' => [
                                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                                            'color' => ['rgb' => '000000'],
                                        ],
                                    ],
                                    'font' => ['size' => 13],
                                ]);
                                $column++;
                            }
                        }
                        // Thêm border cho 2 cột đầu (ma_cham_cong, ho_va_ten)
                        $sheet->getStyle('A' . $row)->applyFromArray([
                            'borders' => [
                                'allborders' => [
                                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                                    'color' => ['rgb' => '000000'],
                                ],
                            ],
                            'font' => ['size' => 13],

                        ]);
                        $sheet->getStyle('B' . $row)->applyFromArray([
                            'borders' => [
                                'allborders' => [
                                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                                    'color' => ['rgb' => '000000'],
                                ],
                            ],
                            'font' => ['size' => 13],

                        ]);
                        $row++;
                    }

                    // Cách 2 dòng trống trước nhân viên tiếp theo
                    $row += 2;
                }
            }

            $sheetIndex++;
        }

        $directory = 'uploads/export/' . date('Y') . '/';
        $filename = 'chamcong_theo_donvi_' . time() . '.xlsx';
        $filePath = $directory . $filename;


        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);

        if (file_exists($filePath)) {
            $this->createLog('Export', 'Export chi tiết chấm công theo đơn vị', NULL, 'Export theo đơn vị', 'hrm_cham_cong');
            $this->response([
                'statusssssss' => REST_Controller::HTTP_OK,
                'message' => 'Success',
                'success' => true,
                'data' => base_url($filePath)
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'statusssss' => REST_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Không thể tạo file Excel.',
                'success' => false,
                'data' => null
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function dong_bo_moi_ngay_post()
    {
        $token = $this->getAuthToken();
        if (!$token) {
            echo "Không thể lấy token";
            return;
        }

        // Lấy ngày hôm qua
        $ngay_hom_qua = new DateTime('yesterday');
        $ngay_bat_dau = $ngay_hom_qua->format('Y-m-d 00:00:00');
        $ngay_ket_thuc = $ngay_hom_qua->format('Y-m-d 23:59:59');

        $this->dong_bo_giao_dich_theo_nhan_vien(
            null,
            $ngay_bat_dau,
            $ngay_ket_thuc,
            $token
        );

        resSuccess(true, 'Success', REST_Controller::HTTP_OK, true);
    }
}
