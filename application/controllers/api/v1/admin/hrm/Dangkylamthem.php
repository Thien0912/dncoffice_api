<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Hrm_quy_dinh_nghi_phep $Hrm_quy_dinh_nghi_phep
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 * @property Hrm_hop_dong_model $Hrm_hop_dong_model
 * @property Hrm_dang_ky_lam_them_model $Hrm_dang_ky_lam_them_model
 * @property Fileupload $fileupload
 * @property Common $common
 * @property CI_Upload $upload
 * @property Pxl $pxl
 */



class Dangkylamthem extends REST_INSTANCE_Controller
{
    private $luongNgayThuong = 30000;
    private $luongNgayNghi = 40000;
    private $luongNgayLe = 60000;

    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_nhan_vien_model', 'Ql_nguoi_dung_model', 'Hrm_hop_dong_model', 'Hrm_dang_ky_lam_them_model']);
        $this->load->library(['Validator', 'Fileupload', 'Common', 'upload', 'Pxl']);
    }
    public function index_get()
    {
        $user = $this->getUserLogin();
        $idNhanVien = '';

        $getDSNhanVien = commonRequest('getDSNhanVien') ? commonRequest('getDSNhanVien') : null;
        if ($getDSNhanVien) {
            $dsNhanvien = $this->Hrm_nhan_vien_model
                ->select("*")
                ->get();

            resSuccess($dsNhanvien, 'Lấy danh sách nhân viên thành công!');
        }

        $so_gio_nam = commonRequest('so_gio_nam') ? commonRequest('so_gio_nam') : null;
        $so_gio_thang = commonRequest('so_gio_thang') ? commonRequest('so_gio_thang') : null;
        $id_nhan_vien = commonRequest('id_nhan_vien') ? commonRequest('id_nhan_vien') : null;
        if ($id_nhan_vien) {
            $this->db->select("hrm_dang_ky_lam_them.*, SUM(so_gio) as tong_so_gio, COUNT(id) AS tong_so_buoi");
            $this->db->where('id_nhan_vien', $id_nhan_vien);
            $this->db->where('trang_thai', 'Da_duyet');

            // if ($so_gio_nam) {
            //     $this->db->where('YEAR(ngay)', $so_gio_nam);
            //     if ($so_gio_thang) {
            //         $this->db->where('MONTH(ngay)', $so_gio_thang);
            //     }
            // }

            $this->db->group_by('id_nhan_vien');
            $query = $this->db->get('hrm_dang_ky_lam_them'); // Lỗi dòng này

            resSuccess('$data', 'Lấy số giờ thành công!');

            $data['so_gio'] = $query->row_array();

            $dklt = $this->db
                ->select('hrm_dang_ky_lam_them.*, hrm_nhan_vien.ho_va_ten')
                ->join('hrm_nhan_vien', 'hrm_nhan_vien.id_nhan_vien = hrm_dang_ky_lam_them.id_nhan_vien', 'left')
                ->where('hrm_dang_ky_lam_them.id_nhan_vien', $id_nhan_vien)
                ->where('trang_thai', 'Da_duyet');


            // if ($so_gio_nam) {
            //     $dklt->where("YEAR(hrm_dang_ky_lam_them.ngay)", $so_gio_nam);
            //     if ($so_gio_thang) {
            //         $dklt->where("MONTH(hrm_dang_ky_lam_them.ngay)", $so_gio_thang);
            //     }
            // }

            $query = $dklt->get('hrm_dang_ky_lam_them');

            $dataDKLT = $query->result_array();
            foreach ($dataDKLT as &$item) {
                $item['thanh_tien'] = $item['so_gio'] * $this->luongNgayThuong;
            }
            $data['dklt'] = $dataDKLT;
            $data['only_year_month'] = false;

            resSuccess($data, 'Lấy số giờ thành công!');
        }

        if (!$id_nhan_vien && $so_gio_nam) {
            $dklt = $this->db
                ->select('
                    hrm_dang_ky_lam_them.id_nhan_vien, 
                    hrm_nhan_vien.ho_va_ten,
                    hrm_nhan_vien.ma_nhan_vien,
                    SUM(hrm_dang_ky_lam_them.so_gio) as tong_so_gio,
                    COUNT(hrm_dang_ky_lam_them.id) as tong_so_buoi
                    ')
                ->join('hrm_nhan_vien', 'hrm_nhan_vien.id_nhan_vien = hrm_dang_ky_lam_them.id_nhan_vien', 'left')
                ->where('trang_thai', 'Da_duyet');


            if ($so_gio_nam) {
                $dklt->where("YEAR(hrm_dang_ky_lam_them.ngay)", $so_gio_nam);
                if ($so_gio_thang) {
                    $dklt->where("MONTH(hrm_dang_ky_lam_them.ngay)", $so_gio_thang);
                }
            }

            $dklt->group_by('hrm_dang_ky_lam_them.id_nhan_vien, hrm_nhan_vien.ho_ten, hrm_nhan_vien.ma_nhan_vien');

            $query = $dklt->get('hrm_dang_ky_lam_them');

            $dataDKLT = $query->result_array();
            foreach ($dataDKLT as &$item) {
                $item['tong_tien'] = $item['tong_so_gio'] * $this->luongNgayThuong;
            }

            $data['dklt'] = $dataDKLT;
            $data['only_year_month'] = true;

            resSuccess($data, 'Lấy số giờ thành công!');
        }


        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;

        $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
            'order' => commonRequest('order'),
            'columns' => commonRequest('columns')
        ] : [];

        if ($user['ql_nguoi_dung_is_admin'] == 1) {
            $idNhanVien = null;
        } else {
            $tempNhanVien = $this->Hrm_nhan_vien_model->where('ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])->first();
            if ($tempNhanVien) {
                $idNhanVien = $tempNhanVien['id_nhan_vien'];
            } else {
                $permisstions = $this->getPermissionKeys();
                foreach ($permisstions as $per) {
                    if ($per['ql_quyen_khoa'] == 'dangkylamthem.change_status') {
                        $idNhanVien = null;
                        break;
                    }
                }
            }
        }

        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];

        $permisstions = $this->getPermissionKeys();
        $duyetLDDV = array_filter($permisstions, function ($per) {
            return $per['ql_quyen_khoa'] === 'dangkylamthem.change_status';
        });
        $duyetTCHC = array_filter($permisstions, function ($per) {
            return $per['ql_quyen_khoa'] === 'dangkylamthem.tchcduyet';
        });

        if ($duyetLDDV) {
            if (empty($searchKey)) {
                $searchKey['trang_thai'] = Common::STATUS_DANG_KY_LAM_THEM['Cho_duyet']['value'];
            }
        } else if ($duyetTCHC) {
            if (empty($searchKey)) {
                $searchKey['trang_thai_tchc'] = Common::STATUS_DANG_KY_LAM_THEM['Cho_duyet']['value'];
            }
            $searchKey['trang_thai'] = Common::STATUS_DANG_KY_LAM_THEM['Da_duyet']['value'];
        }

        $data = $this->Hrm_dang_ky_lam_them_model->getAll($start, $length, $searchValue, $orderBy, $searchKey, $idNhanVien);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }

    public function create_post()
    {
        $validator = new Validator();

        $loai_ngay = commonRequest('loai_ngay') ? commonRequest('loai_ngay') : null;
        if (!in_array($loai_ngay, ['Ngay_thuong', 'Ngay_nghi', 'Ngay_le'])) {
            $validator->addError('', 'loai_ngay', 'Loại ngày không hợp lệ');
            resBadrequest(resBadrequest($validator->errors()));
        }

        $trang_thai = commonRequest('trang_thai') ? commonRequest('trang_thai') : null;
        if (!in_array($trang_thai, ['Cho_duyet', 'Da_duyet', 'Tu_choi'])) {
            $validator->addError('', 'trang_thai', 'Trạng thái không hợp lệ');
            resBadrequest(resBadrequest($validator->errors()));
        }

        $heSoOT = $this->db->select('*')
            ->from('hrm_he_so_tang_ca')
            ->get()
            ->row_array();

        $auth = $this->getUserLogin();
        $data = [
            'id_nhan_vien' => commonRequest('id_nhan_vien') ? commonRequest('id_nhan_vien') : null,
            'ngay' => commonRequest('ngay') ? commonRequest('ngay') : null,
            'gio_bat_dau' => commonRequest('gio_bat_dau') ? commonRequest('gio_bat_dau') : null,
            'gio_ket_thuc' => commonRequest('gio_ket_thuc') ? commonRequest('gio_ket_thuc') : 0,
            'loai_ngay' => $loai_ngay,
            'trang_thai' => $trang_thai,
            'ghi_chu' => commonRequest('ghi_chu') ? commonRequest('ghi_chu') : null,
            'so_gio' => commonRequest('so_gio') ? commonRequest('so_gio') : null,
            'id_he_so_ot' => $heSoOT ? $heSoOT['id_he_so'] : null,
            'nguoi_tao' => $auth['ql_nguoi_dung_id'],
            'nguoi_sua' => $auth['ql_nguoi_dung_id'],
            'trang_thai_tchc' => Common::STATUS_DANG_KY_LAM_THEM['Cho_duyet']['value'],
            'lddv_duyet_id' => null,
            'tchc_duyet_id' => null
        ];
        $rules = [
            'id_nhan_vien' => 'required|integer',
            'ngay' => 'date',
            'gio_bat_dau' => 'required',
            'gio_ket_thuc' => 'required',
            'loai_ngay' => 'required',
            'trang_thai' => 'required',

        ];
        $customMessages = [
            'id_nhan_vien.required' => 'Vui lòng chọn nhân viên',
            'ngay.date' => 'Ngày không đúng định dạng',
            'gio_bat_dau.required' => 'Giờ bắt đầu bắt buộc nhập',
            'gio_ket_thuc.required' => 'Giờ kết thúc bắt buộc nhập',
            'loai_ngay.required' => 'Loại ngày bắt buộc nhập',
            'trang_thai.required' => 'Trạng thái bắt buộc nhập',
        ];

        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        $this->db->trans_start();

        $dklt = $this->Hrm_dang_ky_lam_them_model->create($data);

        //create log
        $this->createLog('create', 'Tạo mới đăng ký làm thêm', null, $dklt, 'hrm_dang_ky_lam_them');
        $this->db->trans_commit();
        resSuccess($dklt, 'Thêm thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }

    public function update_post($id)
    {
        $hopdong = $this->Hrm_hop_dong_model->find($id);
        if (!$hopdong) {
            resError('Hợp đồng khồng tồn tại', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }

        $validator = new Validator();

        $so_hop_dong = commonRequest('so_hop_dong') ? commonRequest('so_hop_dong') : null;
        if (!$so_hop_dong) {
            $validator->addError('', 'so_hop_dong', 'Số hợp đồng bắt buộc nhập');
            resBadrequest(resBadrequest($validator->errors()));
        }
        if ($this->Hrm_hop_dong_model->where('so_hop_dong', $so_hop_dong)->where('id_hop_dong !=', $id)->first()) {
            $validator->addError('', 'so_hop_dong', 'Số hợp đồng đã tồn tại');
            resBadrequest(resBadrequest($validator->errors()));
        }

        $loai_hop_dong = commonRequest('loai_hop_dong') ? commonRequest('loai_hop_dong') : null;
        if (!in_array($loai_hop_dong, ['Thu_viec', 'Co_thoi_han', 'Khong_thoi_han'])) {
            $validator->addError('', 'loai_hop_dong', 'Loại hợp đồng không hợp lệ');
            resBadrequest(resBadrequest($validator->errors()));
        }

        $auth = $this->getUserLogin();
        $data = [
            // 'id_nhan_vien' => commonRequest('id_nhan_vien') ? commonRequest('id_nhan_vien') : null,
            'id_nhan_vien' => $hopdong['id_nhan_vien'],
            'so_hop_dong' => $so_hop_dong,
            'ngay_bat_dau' => commonRequest('ngay_bat_dau') ? commonRequest('ngay_bat_dau') : null,
            'ngay_ket_thuc' => commonRequest('ngay_ket_thuc') ? commonRequest('ngay_ket_thuc') : null,
            'muc_luong' => commonRequest('muc_luong') ? commonRequest('muc_luong') : 0,
            'loai_hop_dong' => $loai_hop_dong,
            'tile_bhxh' => commonRequest('tile_bhxh') ? commonRequest('tile_bhxh') : null,
            'tile_bhyt' => commonRequest('tile_bhyt') ? commonRequest('tile_bhyt') : null,
            'tile_bhtn' => commonRequest('tile_bhtn') ? commonRequest('tile_bhtn') : null,
            'nguoi_tao' => $auth['ql_nguoi_dung_id'],
            'nguoi_sua' => $auth['ql_nguoi_dung_id']
        ];
        $rules = [
            'id_nhan_vien' => 'required|integer',
            'ngay_bat_dau' => 'date',
            'ngay_ket_thuc' => 'date',
            // 'tile_bhxh' => 'required',
            // 'tile_bhyt' => 'required',
            // 'tile_bhtn' => 'required',
        ];
        $customMessages = [
            'id_nhan_vien.required' => 'Vui lòng chọn nhân viên',
            'ngay_bat_dau.date' => 'Ngày bắt đầu hợp đồng không đúng định dạng',
            'ngay_ket_thuc.date' => 'Ngày kết thúc hợp đồng không đúng định dạng',
            'tile_bhxh.required' => 'Tỉ lệ bảo hiểm xã hội bắt buộc nhập',
            'tile_bhyt.required' => 'Tỉ lệ bảo hiểm y tế bắt buộc nhập',
            'tile_bhtn.required' => 'Tỉ lệ bảo hiểm tai nạn bắt buộc nhập',
        ];

        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        //Upload avatar
        $folderName = 'hop-dong/' . date('Y') . '/' . date('m');
        if (isset($_FILES['file_hop_dong'])) {
            $uploadedFile = $this->fileupload->upload($_FILES['file_hop_dong'], $folderName);
            if (!$uploadedFile['success']) {
                resBadrequest([
                    'file_hop_dong' => [
                        'Không thể tải lên file'
                    ]
                ]);
            } else {
                $data['file_hop_dong_duong_dan'] = $uploadedFile['file_path'];
                $data['file_hop_dong_ten_file_goc'] = $uploadedFile['file_name'];
                $data['file_hop_dong_loai_file'] = $uploadedFile['file_extension'];
                //delete file
                $deletefile = $this->fileupload->delete($hopdong['file_hop_dong_duong_dan']);
            }
        }

        $this->db->trans_start();

        $this->Hrm_hop_dong_model->where('id_hop_dong', $id)->update($data);

        $nhanvienOld = $this->Hrm_nhan_vien_model->find($hopdong['id_nhan_vien']);
        $hopdong['nhan_vien'] = $nhanvienOld;

        $hopdongNew = $this->Hrm_hop_dong_model->find($id);
        $nhanvienNew = $this->Hrm_nhan_vien_model->find($data['id_nhan_vien']);
        $hopdongNew['nhan_vien'] = $nhanvienNew;

        //create log
        $this->createLog('update', 'Cập nhật hợp đồng', $hopdong, $hopdongNew, 'hrm_hop_dong');
        $this->db->trans_commit();
        resSuccess($hopdong, 'Cập nhật thành công', REST_INSTANCE_Controller::HTTP_OK);
    }

    // public function delete_post($id)
    public function delete_post()
    {
        $ids = commonRequest('ids');
        $this->db->trans_start();
        $dklt = $this->Hrm_dang_ky_lam_them_model->whereIn('id_dang_ky_lam_them', $ids)->get();
        if (count($ids) != count($dklt)) {
            resError('Dữ liệu không hợp lệ');
        }

        foreach ($dklt as $dk) {
            if ($dk['trang_thai'] == Common::STATUS_DANG_KY_LAM_THEM['Da_duyet']['value']) {
                resError('Không thể xóa đăng ký làm thêm đã duyệt', REST_Controller::HTTP_BAD_REQUEST);
            }
        }

        $this->Hrm_dang_ky_lam_them_model->whereIn('id_dang_ky_lam_them', $ids)->update(
            [
                'deleted_at' => date('Y-m-d')
            ]
        );

        $this->createLog('delete', 'Xóa đăng ký làm thêm', $dklt,  null, 'hrm_dang_ky_lam_them');
        $this->db->trans_commit();
        resSuccess(null, 'Xóa thành công');
    }

    public function getByUserId_get($id)
    {
        // $data = [
        //     'start' => commonRequest('start') ?? 0,
        //     'length' => commonRequest('length') ?? 10,
        //     'searchValue' => commonRequest('searchValue') ?? null,
        //     'order' => commonRequest('order') ?? [],
        //     'columns' => commonRequest('columns') ?? [],
        //     // 'columnControl' => commonRequest('columnControl') ?? [],
        //     'searchKey' => commonRequest('searchKey') ? commonRequest('searchKey') : [],
        //     'fromDate' => commonRequest('fromDate') ? commonRequest('fromDate') : null,
        //     'toDate' => commonRequest('toDate') ? commonRequest('toDate') : null
        // ];

        // $data = $this->Hrm_dang_ky_lam_them_model->getAllByUserID($data['start'], $data['length'], $data['searchValue'], $data['order'], $data['columns'], $data['searchKey'],  $data['fromDate'], $data['toDate'], $auth);

        // resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
        //     'recordsTotal' => $data['recordsTotal'],
        //     'recordsFiltered' => $data['recordsFiltered'],
        //     // 'sql' => $data['sql'],
        // ]);


        $dsDangKyLamThem = $this->Hrm_dang_ky_lam_them_model
            ->join('hrm_nhan_vien', 'hrm_nhan_vien.id_nhan_vien = hrm_dang_ky_lam_them.id_nhan_vien')
            ->where('hrm_nhan_vien.id_nhan_vien', $id)
            ->get();

        if (!$dsDangKyLamThem) resError('Không tìm thấy văn bản', REST_Controller::HTTP_NOT_FOUND);

        resSuccess($dsDangKyLamThem);
    }

    public function change_status_put($id)
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError("Không tìm thấy người dùng!");
        }

        $item = $this->Hrm_dang_ky_lam_them_model->find($id);
        if (!$item) resError('Không tìm thấy đăng ký làm thêm', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        $status = commonRequest('trang_thai') ? commonRequest('trang_thai') : null;
        if (!in_array($status, ['Cho_duyet', 'Da_duyet', 'Tu_choi'])) {
            resBadrequest(['trang_thai' => 'Trạng thái không hợp lệ'], 'Trạng thái không hợp lệ');
        }
        $this->Hrm_dang_ky_lam_them_model->where('id_dang_ky_lam_them', $id)->update([
            'trang_thai' => $this->common::STATUS_DANG_KY_LAM_THEM[$status]['value'],
            'lddv_duyet_id' => $auth['ql_nguoi_dung_id']
        ]);
        $this->createLog('Update', 'Cập nhật trạng thái đăng ký làm thêm', $item, $this->Hrm_dang_ky_lam_them_model->find($id), 'hrm_dang_ky_lam_them');
        resSuccess(null, 'Cập nhật thành công');
    }

    public function import_post()
    {
        $user = $this->getUserLogin();
        $currentNhanVien = $this->Hrm_nhan_vien_model->where('ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])->first();

        try {

            $stop = false;
            $message = '';
            $dataList = [];
            $file_import = commonRequest('file_excel');

            if ($file_import) {

                $config['upload_path'] = 'uploads/excel/dangkylamthem/'; // Thư mục để lưu file
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

                    $dataList = $this->pxl->importExcel(10, 11, $path);

                    // Kiểm thử dữ liệu từ file excel
                    // resSuccess($dataList, 'Thành công', REST_Controller::HTTP_OK, true, [
                    //     'recordsTotal' => count($dataList),
                    // ]);

                    $objPHPExcel = PHPExcel_IOFactory::load($path);
                    $sheet = $objPHPExcel->getActiveSheet();
                    $highestColumn = $sheet->getHighestColumn();
                    $highestRow = $sheet->getHighestRow();

                    $highestColumn = 'L';
                    $columnResult = $highestColumn . '9';
                    $sheet->setCellValue($columnResult, 'KẾT QUẢ');
                    $sheet->getColumnDimension($highestColumn)->setAutoSize(true);
                    $sheet->getStyle($columnResult)->applyFromArray(
                        array(
                            'font' => array(
                                'bold' => true,
                                'size' => 13,             // Tăng kích thước font
                                'name' => 'Times New Roman',
                            ),
                            'alignment' => array(
                                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
                            ),
                            'fill' => [
                                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                'color' => ['rgb' => 'dcdde1']
                            ]
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
                        'id_nhan_vien',
                        'ngay',
                        'gio_bat_dau',
                        'gio_ket_thuc',
                        'loai_ngay',
                        'trang_thai',
                        'so_gio',
                        'id_he_so_ot',
                        'ghi_chu'
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
                        $startRow_columnResult = 11; // Dòng bắt đầu hiển thị kết quả
                        foreach ($dataList as $key => $l) {
                            $errorMessages = [];
                            $field_PrimaryKey = [
                                'id_nhan_vien' => 'Mã nhân viên đang rỗng.',
                                'ngay' => 'Ngày đang rỗng.',
                                'gio_bat_dau' => 'Giờ bắt đầu đang rỗng.',
                                'gio_ket_thuc' => 'Giờ kết thúc đang rỗng.',
                                'loai_ngay' => 'Loại ngày đang rỗng.',
                                'trang_thai' => 'Trạng thái đang rỗng.',
                                'id_he_so_ot' => 'Hệ số đang rỗng.',
                            ];

                            $keys = [];
                            $keys = array_keys($l);
                            foreach ($field_PrimaryKey as $key => $errorMessage) {
                                if (empty($l[$key])) {
                                    $errorMessages[] = $errorMessage;

                                    $indexKey = array_search($key, $keys) + 1; // bỏ đi cột stt
                                    $column = PHPExcel_Cell::stringFromColumnIndex($indexKey);
                                    $sheet->getStyle($column . $startRow_columnResult)->applyFromArray(
                                        array(
                                            'fill' => array(
                                                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                                'color' => array('rgb' => 'f9ca24')
                                            )
                                        )
                                    );
                                }
                            }

                            foreach ($keys as $dataListKey) {
                                if ($dataListKey == 'id_nhan_vien') {
                                    $nhanvien = $this->Hrm_nhan_vien_model->where('ma_nhan_vien', $l['id_nhan_vien'])->first();
                                    if (!$nhanvien) {
                                        $errorMessages[] = 'Mã nhân viên không tồn tại.';
                                        $indexKey = array_search($dataListKey, $keys) + 1; // bỏ đi cột stt
                                        $column = PHPExcel_Cell::stringFromColumnIndex($indexKey);
                                        $sheet->getStyle($column . $startRow_columnResult)->applyFromArray(
                                            array(
                                                'fill' => array(
                                                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                                    'color' => array('rgb' => 'f9ca24')
                                                )
                                            )
                                        );
                                    }

                                    if ($user['ql_nguoi_dung_is_admin'] == 0) {
                                        if ($l['id_nhan_vien'] != $currentNhanVien['ma_nhan_vien']) {
                                            $errorMessages[] = 'Không được quyền thêm nhân viên khác.';
                                            $indexKey = array_search($dataListKey, $keys) + 1; // bỏ đi cột stt
                                            $column = PHPExcel_Cell::stringFromColumnIndex($indexKey);
                                            $sheet->getStyle($column . $startRow_columnResult)->applyFromArray(
                                                array(
                                                    'fill' => array(
                                                        'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                                        'color' => array('rgb' => 'f9ca24')
                                                    )
                                                )
                                            );
                                        }
                                    }
                                }

                                if ($dataListKey == 'id_he_so_ot') {
                                    $heSoOT = $this->db
                                        ->from('hrm_he_so_tang_ca')
                                        ->where('loai_ngay', $l['id_he_so_ot'])
                                        ->get()
                                        ->row_array();

                                    if (!$heSoOT) {
                                        $errorMessages[] = 'Hệ số OT không tồn tại.';
                                        $indexKey = array_search($dataListKey, $keys) + 1; // bỏ đi cột stt
                                        $column = PHPExcel_Cell::stringFromColumnIndex($indexKey);
                                        $sheet->getStyle($column . $startRow_columnResult)->applyFromArray(
                                            array(
                                                'fill' => array(
                                                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                                    'color' => array('rgb' => 'f9ca24')
                                                )
                                            )
                                        );
                                    }
                                }
                            }

                            $l['ketqua'] = '';
                            if (!empty($errorMessages)) {
                                $l['ketqua'] = implode(' ', $errorMessages);
                                $countError++;
                            } else {
                                $invalidData = false;
                                if (!$invalidData) {
                                    $l['ketqua'] = 'Thành công';
                                    $countSuccess++;

                                    // $ngayDangKy = $l['ngay'];
                                    // $ngayDangKyFormat = DateTime::createFromFormat('d/m/Y', $ngayDangKy)->format('Y-m-d');

                                    if (is_numeric($l['ngay'])) {
                                        // Chuyển đổi số serial thành timestamp PHP
                                        $timestamp = PHPExcel_Shared_Date::ExcelToPHP($l['ngay']);
                                        // Định dạng lại thành ngày tháng (Y-m-d)
                                        $ngayDangKyFormat = date('Y-m-d', $timestamp);
                                    } else {
                                        // Nếu không phải số serial, giữ nguyên giá trị
                                        $ngayDangKyFormat = $l['ngay'];
                                    }

                                    $nhanvien = $this->Hrm_nhan_vien_model->where('ma_nhan_vien', $l['id_nhan_vien'])->first();

                                    $heSoOT = $this->db
                                        ->select('*')
                                        ->from('hrm_he_so_tang_ca')
                                        ->where('loai_ngay', $l['id_he_so_ot'])
                                        ->get()
                                        ->row_array();

                                    $dataInsert = [
                                        'id_nhan_vien' => $nhanvien['id_nhan_vien'],
                                        'ngay' => $ngayDangKyFormat,
                                        'gio_bat_dau' => $l['gio_bat_dau'],
                                        'gio_ket_thuc' => $l['gio_ket_thuc'],
                                        'loai_ngay' => $l['loai_ngay'],
                                        'trang_thai' => $l['trang_thai'],
                                        'so_gio' => $l['so_gio'],
                                        'id_he_so_ot' => $heSoOT['id_he_so'],
                                        'ghi_chu' => $l['ghi_chu'],
                                        'ngay_tao' => date('Y-m-d H:i:s'),
                                        'ngay_sua' => date('Y-m-d H:i:s'),
                                        'nguoi_tao' => $this->getUserLogin()['ql_nguoi_dung_id'],
                                        'nguoi_sua' => $this->getUserLogin()['ql_nguoi_dung_id'],
                                    ];

                                    // resSuccess($dataInsert, 'Thành công', REST_Controller::HTTP_OK, true, [
                                    //     'recordsTotal' => count($dataInsert),
                                    // ]);

                                    $dataInsertArray[] = $dataInsert;

                                    $id_dang_ky_lam_them = $this->Hrm_dang_ky_lam_them_model->insert($dataInsert);
                                }
                            }
                            $sheet->setCellValue($highestColumn . $startRow_columnResult, $l['ketqua']);
                            $color = strtolower($l['ketqua']) != strtolower('Thành công') ? 'f57878' : '77c884';
                            $sheet->getStyle($highestColumn . $startRow_columnResult)->applyFromArray(
                                array(
                                    'fill' => array(
                                        'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                        'color' => array('rgb' => $color)
                                    )
                                )
                            );

                            $sheet->getColumnDimension($highestColumn)->setAutoSize(true);
                            $startRow_columnResult++;
                        }
                        if (!empty($dataInsertArray)) {
                            // $this->db->trans_start();
                            $this->createLog('Import', 'Import đăng ký làm thêm', NULL, $dataInsert, 'hrm_dang_ky_lam_them');
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
                    // Đọc nội dung file vào biến
                    $fileContent = file_get_contents($path);

                    // Mã hóa nội dung file dưới dạng base64
                    $encodedContent = base64_encode($fileContent);

                    // Xóa file 
                    if (file_exists($path)) unlink($path);

                    $this->response([
                        'status' => REST_INSTANCE_Controller::HTTP_CREATED,
                        'message' => $message,
                        'success' => true,
                        'data' => [
                            'file_content' => $encodedContent,
                            'file_name' => $fileName,
                            // 'path_file_excel' => base_url($path)
                            'countError' => $countError,
                            'dataInsert' => $dataInsert,
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

    private function getExcelColumn()
    {
        $cols = [
            '' => 'STT',
            'ma_nhan_vien' => 'Mã nhân viên',
            'ho_va_ten' => 'Họ và tên',
            'ngay' => 'Ngày đăng ký',
            'gio_bat_dau' => 'Giờ bắt đầu',
            'gio_ket_thuc' => 'Giờ kết thúc',
            'loai_ngay' => 'Loại ngày',
            'trang_thai' => 'Trạng thái',
            'ghi_chu' => 'Ghi chú',
            'so_gio' => 'Số giờ',
            'id_he_so_ot' => 'Hệ số',
        ];
        return $cols;
    }


    public function export_get()
    {
        $id_nhan_vien = '';
        $user  = $this->getUserLogin();
        if ($user['ql_nguoi_dung_is_admin'] == 0) {
            $id_nhan_vien = ($this->Hrm_nhan_vien_model->where('ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])->first())['id_nhan_vien'];
        }


        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;
        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];
        $fromDate = commonRequest('fromDate') ? commonRequest('fromDate') : null;
        $toDate = commonRequest('toDate') ? commonRequest('toDate') : null;

        $data = $this->Hrm_dang_ky_lam_them_model->getListExport($start, $length, $searchValue, $searchKey, $fromDate, $toDate, $id_nhan_vien);

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
                ],
                'fill' => [
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => ['rgb' => 'dcdde1']
                ]
            ]);
            $column++;
        }
        $sheet->setCellValue('A1', 'Đăng ký làm thêm'); // Thêm cột thông báo
        $sheet->getStyle('A1')->applyFromArray([
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
            ]
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Hợp nhất các ô từ A1 đến H1
        $sheet->mergeCells('A1:K1');

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

                if ($key == 'loai_ngay') {
                    $text_loai_ngay = '';

                    foreach (Common::LOAI_NGAY as $ln_key => $loai_ngay) {
                        if ($ln_key == $item[$key]) {
                            $text_loai_ngay = $loai_ngay['label'];
                        }
                    }

                    $sheet->setCellValue($column . $row, $text_loai_ngay);
                } else if ($key == 'trang_thai') {
                    $text_trang_thai = '';

                    foreach (Common::STATUS_DANG_KY_LAM_THEM as $dklt_key => $dklt) {
                        if ($dklt_key == $item[$key]) {
                            $text_trang_thai = $dklt['label'];
                        }
                    }

                    $sheet->setCellValue($column . $row, $text_trang_thai);
                } else if ($key == 'ngay') {
                    $text_ngay = '';
                    if ($item[$key]) {
                        $text_ngay = (new DateTime($item[$key]))->format('d/m/Y');
                    }

                    $sheet->setCellValue($column . $row, $text_ngay);
                } else if ($key == 'id_he_so_ot') {
                    $text_he_so = '';
                    if ($item[$key]) {
                        $text_he_so = $this->db
                            ->select('*')
                            ->from('hrm_he_so_tang_ca')
                            ->where('id_he_so', $item[$key])
                            ->get()
                            ->row_array()['he_so_ot'];
                    }

                    $sheet->setCellValue($column . $row, $text_he_so);
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
        $filename = 'dangkylamthem_' . time() . '.xlsx'; // Tên file kèm timestamp để tránh trùng lặp
        $filePath = $directory . $filename;
        // Kiểm tra và tạo thư mục nếu chưa tồn tại
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);

        if (file_exists($filePath)) {
            $this->createLog('Export', 'Export danh sách đăng ký làm thêm', NULL, 'Export danh sách đăng ký làm thêm', 'hrm_dang_ky_lam_them');
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

    function duyetdklt_post()
    {
        $auth = $this->getUserLogin();
        if (!$auth) {
            resError('Không tìm thấy người dùng!');
        }

        $ids = commonRequest('ids');
        $status = commonRequest('trang_thai') ? commonRequest('trang_thai') : null;
        if (!array_key_exists($status, Common::STATUS_DANG_KY_LAM_THEM)) {
            resBadrequest(['trang_thai' => 'Trạng thái không hợp lệ'], 'Trạng thái không hợp lệ');
        }

        $this->db->trans_start();
        $dklt = $this->Hrm_dang_ky_lam_them_model->whereIn('id_dang_ky_lam_them', $ids)->get();
        if (count($ids) != count($dklt)) {
            resError('Dữ liệu không hợp lệ');
        }

        $data = [];
        $permisstions = $this->getPermissionKeys();
        $duyetLDDV = array_filter($permisstions, function ($per) {
            return $per['ql_quyen_khoa'] === 'dangkylamthem.change_status';
        });
        $duyetTCHC = array_filter($permisstions, function ($per) {
            return $per['ql_quyen_khoa'] === 'dangkylamthem.tchcduyet';
        });

        if ($duyetLDDV) {
            $data = [
                'trang_thai' => $status,
                'lddv_duyet_id' => $auth['ql_nguoi_dung_id']
            ];
        } else if ($duyetTCHC) {
            $data = [
                'trang_thai_tchc' => $status,
                'tchc_duyet_id' => $auth['ql_nguoi_dung_id']
            ];
        } else {
            resError('Không có quyền thao tác!');
        }

        foreach ($ids as $id) {
            $tempDKLT = $this->Hrm_dang_ky_lam_them_model->find($id);
            if ($duyetLDDV && (($tempDKLT['trang_thai'] == Common::STATUS_DANG_KY_LAM_THEM['Da_duyet']['value']) || ($tempDKLT['trang_thai'] == Common::STATUS_DANG_KY_LAM_THEM['Tu_choi']['value']))) {
                continue;
            } else if ($duyetTCHC && (($tempDKLT['trang_thai_tchc'] == Common::STATUS_DANG_KY_LAM_THEM['Da_duyet']['value']) || ($tempDKLT['trang_thai_tchc'] == Common::STATUS_DANG_KY_LAM_THEM['Tu_choi']['value']))) {
                continue;
            }
            $this->Hrm_dang_ky_lam_them_model->where('id_dang_ky_lam_them', $id)->update($data);
        }

        $this->createLog('update', 'Duyệt đăng ký làm thêm', $dklt,  null, 'hrm_dang_ky_lam_them');
        $this->db->trans_commit();
        resSuccess(null, 'Thao tác thành công');
    }
}
