<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property E_file_dinh_kem_model $E_file_dinh_kem_model
 * @property E_van_ban_model $E_van_ban_model
 * @property E_don_vi_model $E_don_vi_model
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 * @property Common $common
 * @property E_xu_ly_model $E_xu_ly_model
 * @property E_don_vi_xu_ly_model $E_don_vi_xu_ly_model
 * @property Hrm_hop_dong_model $Hrm_hop_dong_model
 * @property CI_Output $output
 */

class Fileaccess extends CI_Controller
{
    const FILE_PRIVATE = 0;
    const FILE_INTERNAL = 1;
    const FILE_PUBLIC = 2;
    const NOT_A_DOCUMENT = 3;


    public function __construct()
    {
        parent::__construct();

        // Xử lý CORS
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, dhnct-authorization, dhnct-api-key");

        // Chặn request OPTIONS không cần thiết
        if ($_SERVER['REQUEST_METHOD'] == "OPTIONS") {
            exit(0);
        }

        $this->load->library(['Common']);
        $this->load->helper('url');
        $this->load->model([
            'E_van_ban_model',
            'E_file_dinh_kem_model',
            'E_don_vi_model',
            'Ql_nguoi_dung_model',
            'E_xu_ly_model',
            'E_don_vi_xu_ly_model',
            'Hrm_hop_dong_model'
        ]);
    }

    public function xem_file($link)
    {
        // $filePath = decryptString($link);

        // $link = encryptString('MDAwMDAwMDAwMDAwMDAwMEcwaC85eU1wYk41WjRJU2pwSU9IYkRXUGQwdGNYNHBpelBCa041cEpjTG9tc2RNcStTUlNCMkxscE95RE9ha0VzZmFOQXFuUg?user_id=626');
        // dd(encryptString('MDAwMDAwMDAwMDAwMDAwMEcwaC85eU1wYk41WjRJU2pwSU9IYkRXUGQwdGNYNHBpelBCa041cEpjTG9tc2RNcStTUlNCMkxscE95RE9ha0VzZmFOQXFuUg'));

        $decryptLink = decryptString($link);

        $arr = explode('?', $decryptLink);

        /**
         * $arr[0] là link mã hóa của file
         * $arr[1] là user_id (nếu có)
         */
        $filePathEncryt = $arr[0];
        $filePath = decryptString($filePathEncryt);
        // dd($filePath);
        $file = $this->E_file_dinh_kem_model->where('duong_dan', $filePath)->first();

        if (!$file) {
            // show_404();
            die("Bạn không có quyền truy cập file");
        }

        if (!$this->checkIsPublic($file['id_file_dinh_kem'])) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'private_file',
                    'message' => 'Bạn không có quyền truy cập file.'
                ]));
            return;
        }

        $vanban = $this->E_van_ban_model->where('id_van_ban', $file['id_van_ban'])->where('deleted_at IS NULL')->first();

        if (!$vanban) {
            // show_404(); //không tìm thấy văn bản
            die("Không tìm thấy file");
        }

        if ($vanban['van_ban_chi_doc']) {
            /**
             * văn bản chỉ đọc thì không public file
             */

            if (isset($arr[1])) {
                $queryParams = [];
                parse_str($arr[1], $queryParams); // Phân tích query string thành mảng
                $userId = $queryParams['user_id'] ?? null; // Lấy giá trị user_id nếu có

                if (!$userId) {
                    // show_404();
                    die("Bạn không có quyền truy cập file");
                }
                $user = $this->Ql_nguoi_dung_model->find($userId);
                if (!$user) {
                    // show_404(); //không có user
                    die("Bạn không có quyền truy cập file");
                }
                $donviTochuchanhchinh = $this->E_don_vi_model->where('ma_don_vi', 'PHONG_TCHC')->first();
                if (!$donviTochuchanhchinh) {
                    // show_404();
                    die("Bạn không có quyền truy cập file");
                }

                //Không phải phòng tổ chức hành chính
                if ($user['id_don_vi'] != $donviTochuchanhchinh['id_don_vi']) {
                    //Nếu là văn bản nội bộ thì chỉ cho nội bộ xem file
                    if ($vanban['loai_van_ban'] == $this->common::VAN_BAN_NOI_BO) {
                        if ($user['id_don_vi'] != $vanban['id_don_vi_soan']) {
                            // show_404(); //khác đơn vị
                            die("Bạn không có quyền truy cập file");
                        }
                        //cùng đơn vị, trả nội dung file
                        $this->displayFile($filePath, $file['ten_file_goc']);
                    }

                    //Nếu là văn bản đến thì các đơn vị được ban hành sẽ được xem
                    if ($vanban['loai_van_ban'] == $this->common::VAN_BAN_DEN) {
                        //Xét trạng thái hợp lý
                        if (
                            !in_array($vanban['trang_thai'], [
                                $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
                                $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value'],
                                $this->common::STATUS_VAN_BAN_DEN['CHUA_PHAN_HOI']['value'],
                                $this->common::STATUS_VAN_BAN_DEN['DA_PHAN_HOI']['value']
                            ])
                        ) {
                            // show_404(); //Trạng thái chưa được ban hành
                            die("Bạn không có quyền truy cập file");
                        }
                        //Đã được ban hành, đơn vị xử lý chính và đơn vị phối hợp xử lý được phép xem
                        $xuly = $this->E_xu_ly_model->where('id_van_ban', $vanban['id_van_ban'])->first();
                        if (!$xuly) {
                            // show_404(); //không có xử lý
                            die("Bạn không có quyền truy cập file");
                        }
                        //đơn vị được giao thì xem được
                        $donvixuly = $this->E_don_vi_xu_ly_model->where('id_xu_ly', $xuly['id_xu_ly'])->get();
                        $donvixulyIds = array_column($donvixuly, 'id_don_vi');
                        if (in_array($user['id_don_vi'], $donvixulyIds)) {
                            $this->displayFile($filePath, $file['ten_file_goc']);
                        } else {
                            // show_404(); // không được giao cho xử lý
                            die("Bạn không có quyền truy cập file");
                        }
                    }

                    if ($vanban['loai_van_ban'] == $this->common::VAN_BAN_DI) {
                        if (
                            !in_array($vanban['trang_thai'], [
                                $this->common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value'],
                                $this->common::STATUS_VAN_BAN_DI['DA_PHAN_HOI']['value'],
                                $this->common::STATUS_VAN_BAN_DI['CHUA_PHAN_HOI']['value']
                            ])
                        ) {
                            // show_404(); //Trạng thái chưa được ban hành
                            die("Bạn không có quyền truy cập file");
                        }
                        //Đã được ban hành, đơn vị xử lý chính và đơn vị phối hợp xử lý được phép xem
                        $xuly = $this->E_xu_ly_model->where('id_van_ban', $vanban['id_van_ban'])->first();
                        if (!$xuly) {
                            // show_404(); //không có xử lý
                            die("Bạn không có quyền truy cập file");
                        }
                        //đơn vị được giao thì xem được
                        $donvixuly = $this->E_don_vi_xu_ly_model->where('id_xu_ly', $xuly['id_xu_ly'])->get();
                        $donvixulyIds = array_column($donvixuly, 'id_don_vi');
                        if (in_array($user['id_don_vi'], $donvixulyIds)) {
                            $this->displayFile($filePath, $file['ten_file_goc']);
                        } else {
                            // show_404(); // không được giao cho xử lý
                            die("Bạn không có quyền truy cập file");
                        }
                    }
                    // show_404();
                    die("Bạn không có quyền truy cập file");
                } else {
                    //Là phòng tổ chức hành chính thì trả file
                    $this->displayFile($filePath, $file['ten_file_goc']);
                }
            } else {
                // show_404();
                die("Bạn không có quyền truy cập file");
            }
        } else {
            // Trả về nội dung file
            $this->displayFile($filePath, $file['ten_file_goc']);
        }
    }

    public function download($link)
    {
        $decryptLink = decryptString($link);

        $arr = explode('?', $decryptLink);

        /**
         * $arr[0] là link mã hóa của file
         * $arr[1] là user_id (nếu có)
         */
        $filePathEncryt = $arr[0];
        $filePath = decryptString($filePathEncryt);
        // dd($filePath);
        $file = $this->E_file_dinh_kem_model->where('duong_dan', $filePath)->first();
        if (!$file) {
            // show_404();
            die("Bạn không có quyền truy cập file");
        }

        // if (!$this->checkIsPublic($file['id_file_dinh_kem'])) {
        //     redirect($this->config->item('frontend_url') . 'auth/login');
        // }

        $vanban = $this->E_van_ban_model->where('id_van_ban', $file['id_van_ban'])->where('deleted_at IS NULL')->first();

        if (!$vanban) {
            // show_404(); //không tìm thấy văn bản
            die("Không tìm thấy file");
        }

        if ($vanban['van_ban_chi_doc']) {
            /**
             * văn bản chỉ đọc thì không public file
             */

            if (isset($arr[1])) {
                $queryParams = [];
                parse_str($arr[1], $queryParams); // Phân tích query string thành mảng
                $userId = $queryParams['user_id'] ?? null; // Lấy giá trị user_id nếu có

                if (!$userId) {
                    // show_404();
                    die("Bạn không có quyền truy cập file");
                }
                $user = $this->Ql_nguoi_dung_model->find($userId);
                if (!$user) {
                    // show_404(); //không có user
                    die("Bạn không có quyền truy cập file");
                }
                $donviTochuchanhchinh = $this->E_don_vi_model->where('ma_don_vi', 'PHONG_TCHC')->first();
                if (!$donviTochuchanhchinh) {
                    // show_404();
                    die("Bạn không có quyền truy cập file");
                }

                //Không phải phòng tổ chức hành chính
                if ($user['id_don_vi'] != $donviTochuchanhchinh['id_don_vi']) {
                    //Nếu là văn bản nội bộ thì chỉ cho nội bộ xem file
                    if ($vanban['loai_van_ban'] == $this->common::VAN_BAN_NOI_BO) {
                        if ($user['id_don_vi'] != $vanban['id_don_vi_soan']) {
                            // show_404(); //khác đơn vị
                            die("Bạn không có quyền truy cập file");
                        }
                        //cùng đơn vị, trả nội dung file
                        $this->downloadFile($filePath, $file['ten_file_goc']);
                    }

                    //Nếu là văn bản đến thì các đơn vị được ban hành sẽ được xem
                    if ($vanban['loai_van_ban'] == $this->common::VAN_BAN_DEN) {
                        //Xét trạng thái hợp lý
                        if (
                            !in_array($vanban['trang_thai'], [
                                $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
                                $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value'],
                                $this->common::STATUS_VAN_BAN_DEN['CHUA_PHAN_HOI']['value'],
                                $this->common::STATUS_VAN_BAN_DEN['DA_PHAN_HOI']['value']
                            ])
                        ) {
                            // show_404(); //Trạng thái chưa được ban hành
                            die("Bạn không có quyền truy cập file");
                        }
                        //Đã được ban hành, đơn vị xử lý chính và đơn vị phối hợp xử lý được phép xem
                        $xuly = $this->E_xu_ly_model->where('id_van_ban', $vanban['id_van_ban'])->first();
                        if (!$xuly) {
                            // show_404(); //không có xử lý
                            die("Bạn không có quyền truy cập file");
                        }
                        //đơn vị được giao thì xem được
                        $donvixuly = $this->E_don_vi_xu_ly_model->where('id_xu_ly', $xuly['id_xu_ly'])->get();
                        $donvixulyIds = array_column($donvixuly, 'id_don_vi');
                        if (in_array($user['id_don_vi'], $donvixulyIds)) {
                            $this->downloadFile($filePath, $file['ten_file_goc']);
                        } else {
                            // show_404(); // không được giao cho xử lý
                            die("Bạn không có quyền truy cập file");
                        }
                    }

                    if ($vanban['loai_van_ban'] == $this->common::VAN_BAN_DI) {
                        if (
                            !in_array($vanban['trang_thai'], [
                                $this->common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value'],
                                $this->common::STATUS_VAN_BAN_DI['DA_PHAN_HOI']['value'],
                                $this->common::STATUS_VAN_BAN_DI['CHUA_PHAN_HOI']['value']
                            ])
                        ) {
                            // show_404(); //Trạng thái chưa được ban hành
                            die("Bạn không có quyền truy cập file");
                        }
                        //Đã được ban hành, đơn vị xử lý chính và đơn vị phối hợp xử lý được phép xem
                        $xuly = $this->E_xu_ly_model->where('id_van_ban', $vanban['id_van_ban'])->first();
                        if (!$xuly) {
                            // show_404(); //không có xử lý
                            die("Bạn không có quyền truy cập file");
                        }
                        //đơn vị được giao thì xem được
                        $donvixuly = $this->E_don_vi_xu_ly_model->where('id_xu_ly', $xuly['id_xu_ly'])->get();
                        $donvixulyIds = array_column($donvixuly, 'id_don_vi');
                        if (in_array($user['id_don_vi'], $donvixulyIds)) {
                            $this->downloadFile($filePath, $file['ten_file_goc']);
                        } else {
                            // show_404(); // không được giao cho xử lý
                            die("Bạn không có quyền truy cập file");
                        }
                    }
                    // show_404();
                    die("Bạn không có quyền truy cập file");
                } else {
                    //Là phòng tổ chức hành chính thì trả file
                    $this->downloadFile($filePath, $file['ten_file_goc']);
                }
            } else {
                // show_404();
                die("Bạn không có quyền truy cập file");
            }
        } else {
            // Trả về nội dung file
            $this->downloadFile($filePath, $file['ten_file_goc']);
        }
    }

    public function displayFile($filePath, $fileNameDownload)
    {
        if (file_exists($filePath)) {
            // Xác định loại MIME của file
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);

            // Đặt Content-Type phù hợp
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: inline; filename="' . $fileNameDownload . '"');
            // header('Content-Disposition: attachment; filename="' . $fileNameDownload . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));

            flush();
            readfile($filePath);
            exit;
        } else {
            show_404();
        }
    }

    public function downloadFile($filePath, $fileNameDownload)
    {
        if (file_exists($filePath)) {
            // Lấy tên tệp
            // $fileName = $file['ten_file_goc'];
            // Đặt header để tải xuống tệp
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $fileNameDownload . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));

            // Xóa bộ nhớ đệm và đọc tệp
            flush();
            readfile($filePath);
            exit;
        } else {
            show_404(); // Hiển thị lỗi 404 nếu không tìm thấy tệp
        }
    }

    public function xem_file_hop_dong($link)
    {
        $decryptLink = decryptString($link);

        $arr = explode('?', $decryptLink);

        /**
         * $arr[0] là link mã hóa của file
         * $arr[1] là user_id (nếu có)
         */
        $filePathEncryt = $arr[0];
        $filePath = decryptString($filePathEncryt);
        // dd($filePath);
        // $file = $this->Hrm_hop_dong_model->where('file_hop_dong_duong_dan', $filePath)->first();

        $file = null;
        $fileName = "";
        $dsHD = $this->Hrm_hop_dong_model->all();
        foreach ($dsHD as &$hd) {
            $filesHD = json_decode($hd['files_hop_dong'], true); // Giải mã JSON thành mảng

            foreach ($filesHD as $fileHD) {
                if ($fileHD['file_path'] === $filePath) {
                    if ($fileHD['is_public'] == 0) {
                        $this->output
                            ->set_content_type('application/json')
                            ->set_output(json_encode([
                                'status' => 'private_file',
                                'message' => 'File nội bộ.'
                            ]));
                        return;
                    }
                    $fileName = $fileHD['file_name'];
                    $file = $hd;
                    break 2; // Thoát cả 2 vòng lặp nếu đã tìm thấy
                }
            }
        }

        if (!$file) {
            // show_404();
            die("File không tồn tại");
        }
        // $this->displayFile($filePath, $file['file_hop_dong_ten_file_goc']);
        $this->displayFile($filePath, $fileName);
    }

    public function xem_file_phu_luc($link)
    {

        $decryptLink = decryptString($link);

        $arr = explode('?', $decryptLink);

        /**
         * $arr[0] là link mã hóa của file
         * $arr[1] là user_id (nếu có)
         */
        $filePathEncryt = $arr[0];
        $filePath = decryptString($filePathEncryt);
        // dd($filePath);
        // $file = $this->Hrm_hop_dong_model->where('file_hop_dong_duong_dan', $filePath)->first();

        $file = null;
        $fileName = "";
        $dsHD = $this->Hrm_hop_dong_model->all();
        foreach ($dsHD as &$hd) {
            $filesHD = json_decode($hd['files_hop_dong'], true); // Giải mã JSON thành mảng

            foreach ($filesHD as $fileHD) {
                if ($fileHD['file_path'] === $filePath) {
                    $fileName = $fileHD['file_name'];
                    $file = $hd;
                    break 2; // Thoát cả 2 vòng lặp nếu đã tìm thấy
                }
            }
        }

        if (!$file) {
            // show_404();
            die("File không tồn tại");
        }
        // $this->displayFile($filePath, $file['file_hop_dong_ten_file_goc']);
        $this->displayFile($filePath, $fileName);
    }

    private $useFunctionView = false; // Có đang dùng hàm view
    private $checkViewAccess = true; // Kiểm tra quyền truy cập view
    private function resetViewConfig()
    {
        $this->useFunctionView = false;
        $this->checkViewAccess = true;
    }

    private function checkViewAccessAndDie($text)
    {
        if ($this->useFunctionView) {
            $this->checkViewAccess = false;
        } else {
            die($text);
        }
    }

    public function view($link)
    {
        $this->useFunctionView = true;

        $path = $this->decryptUrl($link);
        parse_str($path[1], $query);
        $fileName = urldecode($query['file_name']);
        $realPath = FCPATH . ltrim($path[0], '/');
        // dd($query);
        // dd(['checkViewAccess' => $this->checkViewAccess]);
        if ($this->checkViewAccess == false) {
            $this->resetViewConfig();

            return $this->output
                ->set_status_header(404)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'data' => [],
                    'success' => false,
                    'status' => 'private_file',
                    'message' => 'File hiện hạn chế quyền xem !',
                ]));
        }

        if (!file_exists($realPath)) {
            die('File này không có trong hệ thống');
        } else {
            $mime = mime_content_type($realPath);
            header("Content-Type: $mime");
            header('Content-Disposition: inline; filename="' . $fileName . '"');
            header('Cache-Control: public, max-age=86400'); // 1 ngày

            readfile($realPath);
            exit;
        }
    }

    public function image($link)
    {
        $path = $this->decryptUrl($link);
        parse_str($path[1], $query);
        $fileName = urldecode($query['file_name']);
        $realPath = FCPATH . ltrim($path[0], '/');
        // dd($path);
        // Nếu không tồn tại hoặc không phải ảnh → trả ảnh lỗi
        if (!file_exists($realPath) || strpos(mime_content_type($realPath), 'image/') !== 0) {
            $fallback = FCPATH . 'assets/images/Image-not-found.png';
            // if (!file_exists($fallback)) show_404();
            header("Content-Type: image/png");
            readfile($fallback);
            exit;
        }

        // Hiển thị ảnh hợp lệ
        header("Content-Type: " . mime_content_type($realPath));
        header('Cache-Control: public, max-age=86400');
        readfile($realPath);
        exit;
    }

    public function view_avatar($link = null)
    {
        if (!$link)
            $link = $this->input->get('link');
        if (!$link)
            die("Link không hợp lệ");
        $filePath = decryptString($link);
        $arr = explode('?', $filePath);
        $path = str_replace('\\', '/', $arr[0]);
        $realPath = FCPATH . ltrim($path, '/');

        if (!file_exists($realPath) || is_dir($realPath)) {
            $fallback = FCPATH . 'assets/images/avatar/default-avatar.png'; // Giả định path avatar mặc định
            header("Content-Type: image/png");
            if (file_exists($fallback))
                readfile($fallback);
            exit;
        }

        header("Content-Type: " . mime_content_type($realPath));
        header('Cache-Control: public, max-age=86400');
        readfile($realPath);
        exit;
    }

    public function view_image($link = null)
    {
        if (!$link)
            $link = $this->input->get('link');
        $this->serveEncrypted($link, 'image');
    }

    public function view_file($link = null)
    {
        if (!$link)
            $link = $this->input->get('link');
        $this->serveEncrypted($link, 'file');
    }

    public function force_download($link = null)
    {
        if (!$link)
            $link = $this->input->get('link');
        $this->serveEncrypted($link, 'file', true);
    }

    private function serveEncrypted($link, $type, $download = false)
    {
        $decryptedBody = decryptString($link);

        if (!$decryptedBody) {
            die('Mã hóa không hợp lệ hoặc đã hết hạn.');
        }


        $arr = explode('?', $decryptedBody);
        $path = $arr[0];

        // Kiểm tra xem có bị mã hóa 2 lớp không
        if (preg_match('/^[a-zA-Z0-9\-_]+$/', $path)) {
            $decryptedPath = decryptString($path);
            if ($decryptedPath) {
                $path = $decryptedPath;
            }
        }

        $queryString = isset($arr[1]) ? $arr[1] : '';
        $queryParams = [];
        parse_str($queryString, $queryParams);

        // Xử lý cờ không cần đăng nhập hoặc link thuần (không chứa user_id/user_seen)
        $noLogin = isset($queryParams['no_login']) && $queryParams['no_login'] == '1';
        $hasAuthInfo = isset($queryParams['user_id']) || isset($queryParams['user_seen']);

        // dd([
        // 'link' => $link,
        // 'decryptedBody' => $decryptedBody,
        // 'noLogin' => $noLogin,
        // 'hasAuthInfo' => $hasAuthInfo,
        // 'queryParams' => $queryParams
        // ]);

        $this->createLog('View file access', 'File', [
            'link' => $link,
            'decryptedBody' => $decryptedBody,
            'noLogin' => $noLogin,
            'hasAuthInfo' => $hasAuthInfo,
            'queryParams' => $queryParams,
            'path' => $path,
            'type' => $type,
            'download' => $download
        ], [], 'View file access');

        if (!$noLogin && $hasAuthInfo) {
            // Chỉ kiểm tra quyền nếu có thông tin đăng nhập đi kèm trong link (user_id / user_seen)
            $this->validatePermissionAndReturnUrl($path, $queryParams);
        }

        // Chuẩn hóa đường dẫn
        $path = str_replace('\\', '/', $path);
        $realPath = FCPATH . ltrim($path, '/');

        if (!file_exists($realPath) || is_dir($realPath)) {
            if ($type === 'image') {
                $fallback = FCPATH . 'assets/images/Image-not-found.png';
                header("Content-Type: image/png");
                if (file_exists($fallback)) {
                    readfile($fallback);
                }
                exit;
            }
            show_404();
        }

        $mime = mime_content_type($realPath);

        // Nếu yêu cầu là ảnh mà file không phải ảnh thì trả về fallback
        if ($type === 'image' && strpos($mime, 'image/') !== 0) {
            $fallback = FCPATH . 'assets/images/Image-not-found.png';
            header("Content-Type: image/png");
            if (file_exists($fallback)) {
                readfile($fallback);
            }
            exit;
        }

        header("Content-Type: " . $mime);

        // Ưu tiên lấy tên file từ GET (tên đẹp từ Frontend) -> rồi mới đến tên trong link mã hóa -> cuối cùng là tên gốc
        $fileName = $this->input->get('file_name') ?: (isset($queryParams['file_name']) ? $queryParams['file_name'] : basename($realPath));

        // Xử lý download hay preview
        if ($download) {
            header("Content-Disposition: attachment; filename=\"" . $fileName . "\"");
        } else {
            // Đối với xem trực tiếp, skip filename để tránh browser ép tải về ở một số trường hợp
            header("Content-Disposition: inline");
        }
        header('Cache-Control: public, max-age=86400');
        header('Content-Length: ' . filesize($realPath));

        if (ob_get_level()) {
            ob_end_clean();
        }
        readfile($realPath);
        exit;
    }

    private function decryptUrl($link)
    {
        $decryptLink = decryptString($link);
        $arr = explode('?', $decryptLink); //Tách chuỗi

        // Kiểm tra xem có bị mã hóa 2 lớp không
        if (preg_match('/^[a-zA-Z0-9\-_]+$/', $arr[0])) {
            $decrypted = decryptString($arr[0]);
            if ($decrypted) {
                $arr[0] = $decrypted;
            }
        }

        if (empty($arr[0])) {
            die('Đường dẫn không đúng, Vui lòng kiểm tra lại');
        }
        $queryParams = [];
        if (isset($arr[1])) {
            parse_str($arr[1], $queryParams);
        }
        // $queryString = http_build_query($queryParams);
        // $decryptLink = $arr[0] . '?' . $queryString;
        // dd($arr[0]);

        $this->validatePermissionAndReturnUrl($arr[0], $queryParams);
        return $arr;
    }

    private function validatePermissionAndReturnUrl($url, $arr = []): void
    {
        if (empty($arr))
            die('Lỗi khi xử lý thông tin đăng nhập!');
        if (isset($arr['no_login']) && $arr['no_login'] == '1')
            return;
        $user_id = $arr['user_id'] ?? null;
        $user_seen = $arr['user_seen'] ?? null;

        // Biến dùng cho phân loại file (văn bản, hợp đồng,...)
        $file = null;
        $hopDong = null;

        $model_tuong_ung = $this->getModelFromUrl(str_replace('\\', '', $url));
        switch ($model_tuong_ung) {
            case 'van_ban':
                $file = $this->E_file_dinh_kem_model->where('duong_dan', $url)->first();
                break;
            case 'hop_dong':
                $hop_dong_co_file = $this->Hrm_hop_dong_model->where('files_hop_dong IS NOT NULL')->get();
                foreach ($hop_dong_co_file as $hop_dong) {
                    $files_hop_dong = json_decode($hop_dong['files_hop_dong'], true);
                    foreach ($files_hop_dong as $key => $fileHD) {
                        if (str_replace("\\", "", $fileHD['file_path']) == str_replace("\\", "", $url)) {
                            $file = $fileHD;
                            $hopDong = $hop_dong;
                            break 2;
                        }
                    }
                }
                break;

            default:
                $file = $this->E_file_dinh_kem_model->where('duong_dan', $url)->first();
                break;
        }

        if ($file) {
            if (!in_array($file['is_public'], [self::FILE_PRIVATE, self::FILE_INTERNAL, self::FILE_PUBLIC]))
                die('The public status of the file is invalid');
            $is_public = $file['is_public'];
        } else {
            // die('Không tìm thấy file!');
            $is_public = self::NOT_A_DOCUMENT; // Mặc định là NOT_A_DOCUMENT nếu không tìm thấy file
        }

        switch ($is_public) {
            case self::FILE_PRIVATE:
                // File chỉ người tạo văn bản hoặc người cùng đơn vị được thấy
                if (!$user_id && !$user_seen) {
                    die('Không tìm thấy thông tin đăng nhập!');
                } else {
                    $ql_nguoi_dung = $this->Ql_nguoi_dung_model->find($user_seen);

                    if (!$ql_nguoi_dung)
                        $this->checkViewAccessAndDie('PRIVATE__File chỉ được phép truy cập bởi người dùng đã được đăng ký!');
                    if ($ql_nguoi_dung['ql_nguoi_dung_is_admin'] != 1) {
                        $id_don_vi = $ql_nguoi_dung['id_don_vi'];

                        if (!$id_don_vi)
                            $this->checkViewAccessAndDie('PRIVATE__Không tìm thấy thông tin đơn vị!');

                        switch ($model_tuong_ung) {
                            case 'van_ban':
                                // Tìm văn bản
                                $van_ban = $this->E_van_ban_model->find($file['id_van_ban']);
                                if (!$van_ban) {
                                    $this->checkViewAccessAndDie('PRIVATE__Không tìm thấy văn bản!');
                                }

                                $nguoi_tao = $this->Ql_nguoi_dung_model->find($van_ban['id_nguoi_tao']);
                                if (!$nguoi_tao) {
                                    $this->checkViewAccessAndDie('PRIVATE__Không tìm thấy thông tin người tạo!');
                                }

                                if ($nguoi_tao['id_don_vi'] != $id_don_vi) {
                                    $this->checkViewAccessAndDie('PRIVATE__Không có quyền truy cập file!');
                                }
                                break;
                            case 'hop_dong':
                                if ($hopDong['nguoi_tao'] != $ql_nguoi_dung['ql_nguoi_dung_id']) {
                                    $this->checkViewAccessAndDie('PRIVATE__Không có quyền truy cập file!');
                                }
                                break;

                            default:
                                $file = $this->E_file_dinh_kem_model->where('duong_dan', $url)->first();
                                break;
                        }
                    }
                }
                break;
            case self::FILE_INTERNAL:
                // Công khai trong hệ thống
                if (!$user_id && !$user_seen) {
                    $this->checkViewAccessAndDie('INTERNAL__Không tìm thấy thông tin đăng nhập!');
                } else {
                    // Lấy thông tin người đang xem
                    $ql_nguoi_dung = $this->Ql_nguoi_dung_model->find($user_seen);
                    if (!$ql_nguoi_dung) {
                        $this->checkViewAccessAndDie('INTERNAL__File chỉ được phép truy cập bởi người dùng đã được đăng ký!');
                    } else {

                        if ($ql_nguoi_dung['ql_nguoi_dung_is_admin'] != 1) {
                            $id_don_vi = $ql_nguoi_dung['id_don_vi'];
                            if (!$id_don_vi) {
                                $this->checkViewAccessAndDie('INTERNAL__Không tìm thấy thông tin đơn vị!');
                            }

                            switch ($model_tuong_ung) {
                                case 'van_ban':
                                    // Tìm văn bản
                                    $van_ban = $this->E_van_ban_model->find($file['id_van_ban']);
                                    if (!$van_ban)
                                        $this->checkViewAccessAndDie('INTERNAL__Không tìm thấy văn bản!');
                                    $nguoi_tao = $this->Ql_nguoi_dung_model->find($van_ban['id_nguoi_tao'])['id_don_vi'];

                                    if (
                                        in_array($van_ban['trang_thai'], [
                                            Common::STATUS_VAN_BAN_DEN['TIEP_NHAN']['value'],
                                            Common::STATUS_VAN_BAN_DEN['CHO_LANH_DAO_BUT_PHE']['value'],
                                            Common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value'],
                                            Common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value'],
                                        ])
                                    ) {
                                        // if ($id_don_vi != $nguoi_tao) {
                                        //     die('INTERNAL__Không có quyền truy cập file1!');
                                        // }
                                    } else {
                                        // Ngoại lệ trường hợp văn bản lưu trữ không có đơn vị xử lý
                                        if (
                                            (($van_ban['loai_van_ban'] == Common::VAN_BAN_DI) && ($van_ban['trang_thai'] != Common::STATUS_VAN_BAN_DI['LUU_TRU']['value'])) ||
                                            (($van_ban['loai_van_ban'] == Common::VAN_BAN_DEN) && ($van_ban['trang_thai'] != Common::STATUS_VAN_BAN_DEN['LUU_TRU']['value']))
                                        ) {
                                            // Tìm xử lý
                                            $xu_ly = $this->E_xu_ly_model->where('id_van_ban', $van_ban['id_van_ban'])->first()['id_xu_ly'];
                                            if (!$xu_ly)
                                                die('INTERNAL__Không tìm thấy xử lý!');

                                            // Tìm đơn vị xử lý
                                            $donvixuly = $this->E_don_vi_xu_ly_model->where('id_xu_ly', $xu_ly)->get();
                                            if (!$donvixuly)
                                                $this->checkViewAccessAndDie('INTERNAL__Không tìm thấy đơn vị xử lý!');

                                            $donViXuLy_ids = array_unique(array_column($donvixuly, 'id_don_vi'));
                                            $donViXuLy_ids[] = $id_don_vi;
                                            $nguoi_xu_ly_ids = array_unique(array_column($donvixuly, 'id_nguoi_xu_ly'));

                                            if (!in_array($id_don_vi, $donViXuLy_ids) && !(!empty($nguoi_xu_ly_ids) && in_array($user_seen, $nguoi_xu_ly_ids)) && $ql_nguoi_dung['ql_nguoi_dung_id'] != $van_ban['id_nguoi_tao']) {
                                                $this->checkViewAccessAndDie('INTERNAL__Không có quyền truy cập file!');
                                            }
                                        }
                                    }
                                    break;

                                case 'hop_dong':
                                    $donvi = $this->E_don_vi_model->where('id_don_vi', $ql_nguoi_dung['id_don_vi'])->first();
                                    if ($donvi['ma_don_vi'] != 'PHONG_TCHC')
                                        $this->checkViewAccessAndDie('PRIVATE__Không có quyền của đơn vị TC-HC!');
                                    break;

                                default: {
                                    break;
                                }
                            }
                        }
                    }
                }
                break;
            case self::FILE_PUBLIC:
                // Công khai ngoài hệ thống
                break;
            case self::NOT_A_DOCUMENT:
                // Không phải là văn bản, chỉ cần kiểm tra người dùng đã đăng ký hay chưa
                if (!$user_id && !$user_seen) {
                    die('NOT_A_DOCUMENT__Không tìm thấy thông tin đăng nhập!');
                } else {
                    $ql_nguoi_dung = $this->Ql_nguoi_dung_model->find($user_seen);
                    if (!$ql_nguoi_dung)
                        die('NOT_A_DOCUMENT__File chỉ được phép truy cập bởi người dùng đã được đăng ký!');
                }
                break;
            default:
                // File chỉ người tạo văn bản hoặc người cùng đơn vị được thấy
                if (!$user_id && !$user_seen) {
                    die('Không tìm thấy thông tin đăng nhập!');
                } else {
                    $ql_nguoi_dung = $this->Ql_nguoi_dung_model->find($user_seen);

                    if (!$ql_nguoi_dung)
                        die('default__File chỉ được phép truy cập bởi người dùng đã được đăng ký!');

                    if ($ql_nguoi_dung['ql_nguoi_dung_is_admin'] != 1) {
                        $id_don_vi = $ql_nguoi_dung['id_don_vi'];

                        if (!$id_don_vi)
                            die('default__Không tìm thấy thông tin đơn vị!');

                        // Tìm văn bản
                        $van_ban = $this->E_van_ban_model->find($file['id_van_ban']);
                        if (!$van_ban)
                            die('default__Không tìm thấy văn bản!');

                        $nguoi_tao = $this->Ql_nguoi_dung_model->find($van_ban['id_nguoi_tao']);
                        if (!$nguoi_tao)
                            die('default__Không tìm thấy thông tin người tạo!');

                        if ($nguoi_tao['id_don_vi'] != $id_don_vi)
                            die('default__Không có quyền truy cập file!');
                    }
                }
                break;
        }
    }

    function getModelFromUrl($url)
    {
        $arrayMapModel = [
            // thư mục chứa file => model tương ứng
            'uploads/documents/' => 'van_ban',
            'uploads/hop-dong/' => 'hop_dong',
            'uploads/hop-dong-phu-luc/' => 'hop_dong_phu_luc',
            'hrm/minh-chung-nghi-phep/' => 'nghi_phep',
        ];

        foreach ($arrayMapModel as $path => $model) {
            if (strpos($url, $path) !== false) {
                return $model;
            }
        }
        return null; // Không khớp thì trả về null
    }

    private function createLog($hanh_dong, $noi_dung = '', $gia_tri_cu = [], $gia_tri_moi = [], $bang_du_lieu = '')
    {
        $this->load->model('Ql_nhat_ky_model');
        $controller = $this->uri->segment(5);
        $data = [
            'ql_nguoi_dung_id' => 1,
            'ql_nhat_ky_hanh_dong' => $hanh_dong,
            'ql_nhat_ky_noi_dung' => $noi_dung,
            'ql_nhat_ky_gia_tri_cu' => json_encode($gia_tri_cu),
            'ql_nhat_ky_gia_tri_moi' => json_encode($gia_tri_moi),
            'ql_nhat_ky_bang_du_lieu' => $bang_du_lieu,
            'ql_nhat_ky_controller' => $controller
        ];
        $this->Ql_nhat_ky_model->insert($data);
    }
}
