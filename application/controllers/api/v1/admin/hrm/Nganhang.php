<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Hrm_nhan_vien_luong_model $Hrm_nhan_vien_luong_model
 * @property Fileupload $fileupload 
 * @property Pxl $pxl

 */



class Nganhang extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_nhan_vien_luong_model', 'Hrm_nhan_vien_model']);
        $this->load->library(['Validator', 'Fileupload', 'Validate', 'Pxl', 'Common']);
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

        $data = $this->Hrm_nhan_vien_luong_model->getAll($start, $length, $searchValue, $orderBy, $searchKey);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function show_get($id)
    {
        // Lấy thông tin nhân viên
        $nhan_vien = $this->Hrm_nhan_vien_model->where('id_nhan_vien', $id)->first();
        if (!$nhan_vien) {
            resError('Không tìm thấy nhân viên');
        }

        // Lấy thông tin ngân hàng của nhân viên
        $ngan_hang = $this->Hrm_nhan_vien_luong_model->where('id_nhan_vien', $id)->first();
        if (!$ngan_hang) {
            resError('Không tìm thấy thông tin ngân hàng cho nhân viên này');
        }

        // Trả về thông tin ngân hàng
        resSuccess($ngan_hang, 'Lấy thông tin ngân hàng thành công');
    }

    public function update_post()
    {
        $data = [
            'id_nhan_vien' => commonRequest('id_nhan_vien') ? commonRequest('id_nhan_vien') : null,
            'tk_ngan_hang' => commonRequest('tk_ngan_hang') ? commonRequest('tk_ngan_hang') : null,
            'ngan_hang' => commonRequest('ngan_hang') ? commonRequest('ngan_hang') : null,
            'ten_chu_tai_khoan' => commonRequest('ten_chu_tai_khoan') ? commonRequest('ten_chu_tai_khoan') : null,
        ];
        $ttnh = $this->Hrm_nhan_vien_luong_model->where('id_nhan_vien', $data['id_nhan_vien'])->get();
        $this->db->trans_start();
        if ($ttnh) {
            //Cập nhật
            $this->Hrm_nhan_vien_luong_model->where('id_nhan_vien', $data['id_nhan_vien'])->update($data);
        } else {
            //Tạo mới dữ liệu
            $this->Hrm_nhan_vien_luong_model->insert($data);
        }
        $this->db->trans_complete();
        resSuccess(null, 'Cập nhật thông tin ngân hàng thành công');
    }

    public function import_post()
    {
        $file_thongtinnganhang = commonRequest('file_thongtinnganhang') ? commonRequest('file_thongtinnganhang') : null;
        if (!$file_thongtinnganhang) {
            resError('Không tìm thấy file import');
        }
        // Upload file
        $folderName = 'employees/import/' . date('Y') . '/' . date('m');
        $result = $this->fileupload->upload($file_thongtinnganhang, $folderName);
        $path = $result['file_path'];

        $rowKey = 10;
        $rowData = 11;
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

        $columnResult = $highestColumn . '9';
        $sheet->setCellValue($columnResult, 'KẾT QUẢ IMPORT');

        $requiredKeys = [
            'cccd_so',
            'tk_ngan_hang',
            'ngan_hang',
            'ten_chu_tai_khoan',
        ];
        $thongtinnganhangKeys = [
            'ma_nhan_vien',
            'cccd_so',
            'tk_ngan_hang',
            'ngan_hang',
            'ten_chi_nhanh',
            'ten_chu_tai_khoan',
        ];

        // Kiểm tra xem các trường bắt buộc có bị thiếu hay không.
        $this->checkMissingFields($data, $requiredKeys, $path);

        foreach ($data as $key => $d) {

            $errors = [];
            if (empty($d['cccd_so'])) {
                $errors[] = 'Số CCCD/Thẻ Căn Cước không được để trống';
            } else {
                $issetCCCD = $this->Hrm_nhan_vien_model->where('cccd_so', $d['cccd_so'])->first();
                if (!$issetCCCD) {
                    $errors[] = 'Số CCCD/Thẻ Căn Cước không đúng';
                    $id_nhan_vien = null;
                } else {
                    $id_nhan_vien = $issetCCCD['id_nhan_vien'];
                }
            }
            if (empty($d['tk_ngan_hang'])) {
                $errors[] = 'Số tài khoản không được để trống';
            }
            if (empty($d['ngan_hang'])) {
                $errors[] = 'Tên ngân hàng không được để trống';
            }
            if (empty($d['ten_chu_tai_khoan'])) {
                $errors[] = 'Chủ tài khoản không không được để trống';
            }
            // Nếu có lỗi, ghi vào Excel và tiếp tục vòng lặp
            $errorCell = $highestColumn . $rowData;   // Xác định ô ghi kết quả
            if (!empty($errors)) {
                $errorMessage = implode("\n", $errors); // Ghép nhiều lỗi thành chuỗi xuống dòng
                $sheet->setCellValue($errorCell, $errorMessage);
                $sheet->getStyle($errorCell)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FF3300'] // Màu chữ đỏ
                    ],
                ]);
                $countError++;
                $rowData++; // Tăng dòng
                continue;
            } else {
                $sheet->setCellValue($errorCell, 'Thành công');
                $sheet->getStyle($errorCell)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '006400'] // Màu chữ xanh đậm
                    ],
                ]);

                $dataUpdate = [
                    'tk_ngan_hang' => $d['tk_ngan_hang'],
                    'tk_ngan_hang' => $d['tk_ngan_hang'],
                    'ngan_hang' => $d['ngan_hang'],
                    'ten_chu_tai_khoan' => $d['ten_chu_tai_khoan'],
                ];
                $dataInsert = [
                    'id_nhan_vien' => $id_nhan_vien,
                    'tk_ngan_hang' => $d['tk_ngan_hang'],
                    'tk_ngan_hang' => $d['tk_ngan_hang'],
                    'ngan_hang' => $d['ngan_hang'],
                    'ten_chu_tai_khoan' => $d['ten_chu_tai_khoan'],
                ];
                $this->db->trans_start();

                $this->db->where('id_nhan_vien', $id_nhan_vien);
                $exists = $this->db->count_all_results('hrm_nhan_vien_luong') > 0;

                $exists
                    ? $this->db->where('id_nhan_vien', $id_nhan_vien)->update('hrm_nhan_vien_luong', $dataUpdate)
                    : $this->db->insert('hrm_nhan_vien_luong', $dataInsert);

                $this->db->trans_complete();


                $dataUpdate['id_nhan_vien'] = $id_nhan_vien;
                $this->createLog('Import', 'Import thông tin ngân hàng cho nhân viên', NULL, $dataUpdate, 'hrm_nhan_vien_luong');

                $countSuccess++;
                $rowData++;
            }
        }

        $sheet->getColumnDimension($highestColumn)->setAutoSize(true);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($path); // Lưu đè file gốc

        $filename = pathinfo($file_thongtinnganhang['name'], PATHINFO_FILENAME);
        $fileNameEncrypt = pathinfo($path, PATHINFO_FILENAME);
        $fileextension = pathinfo($file_thongtinnganhang['name'], PATHINFO_EXTENSION);

        $newFileName = $filename . '_' . date('Y-m-d') . '_' . $fileNameEncrypt . '.' . $fileextension;

        $newPath = dirname($path) . '/' . $newFileName;
        rename($path, $newPath); // Đổi tên file

        $fileContent = file_get_contents($newPath);
        $encodedContent = base64_encode($fileContent);
        // dd([$encodedContent, $path, $file_thongtinnganhang, $newPath]);
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
}
