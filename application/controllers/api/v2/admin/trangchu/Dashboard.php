<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';
require APPPATH . 'libraries/pxl/PHPExcel.php';

/**
 * @property DB_query_builder $db
 * @property E_van_ban_model $E_van_ban_model
 * @property CI_Input $input
 * @property CI_Upload $upload
 * @property Ql_thong_bao_model $Ql_thong_bao_model
 * @property E_don_vi_model $E_don_vi_model
 * @property Common $common
 */
class Dashboard extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model('E_van_ban_model');
        $this->load->model('Ql_thong_bao_model');
        $this->load->model('E_don_vi_model');
        $this->load->library(['email', 'Common']);
    }

    public function index_get() //$id)
    {
        $result = [];
        $userDonVi = $this->getUserLogin()['id_don_vi'];
        $userId = $this->getUserLogin()['ql_nguoi_dung_id'];
        $data = $this->E_van_ban_model->thongKeVanBan($userId, $userDonVi);

        // biểu đồ thống kê sinh viên theo năm
        $result['thong_ke'] = ($data) ? $this->makeDataToChart($data) : [];

        $this->response([

            'status' => REST_INSTANCE_Controller::HTTP_OK,

            'message' => 'Success',

            'success' => true,

            'data' => $result,

        ], REST_INSTANCE_Controller::HTTP_OK);
    }
    /**
     * 
     */

    public function is_phong_tchc_get()
    {
        $userId = $this->getUserLogin()['ql_nguoi_dung_id'];
        $user = $this->Ql_nguoi_dung_model->find($userId);
        if (!$user) {
            show_404(); //không có user
        }
        $donviTochuchanhchinh = $this->E_don_vi_model->where('ma_don_vi', 'PHONG_TCHC')->first();
        if (!$donviTochuchanhchinh) {
            show_404();
        }

        resSuccess(
            [
                'kiem_tra' => [$donviTochuchanhchinh]
            ]

        );
    }
    private function makeDataToChart($data)
    {
        $result = array();

        $userDonVi = $this->getUserLogin()['id_don_vi'];
        $userId = $this->getUserLogin()['ql_nguoi_dung_id'];
        $user = $this->Ql_nguoi_dung_model->find($userId);
        if (!$user) {
            show_404(); //không có user
        }
        $donviTochuchanhchinh = $this->E_don_vi_model->where('ma_don_vi', 'PHONG_TCHC')->first();
        if (!$donviTochuchanhchinh) {
            show_404();
        }

        if ($userDonVi == $donviTochuchanhchinh['id_don_vi']) {
            foreach ($data as $item) {
                $year = $item['nam'];
                $result[$year] = array(
                    "sl_vb_den" => $item['sl_vb_den'],
                    "sl_vb_di" => $item['sl_vb_di'],
                    "sl_vb_noibo" => $item['sl_vb_noibo']
                );
            }
        } else {
            foreach ($data as $item) {
                $year = $item['nam'];
                $result[$year] = array(
                    "sl_vb_den" => $item['sl_vb_den'],
                    "sl_vb_noibo" => $item['sl_vb_noibo']
                );
            }
        }

        return $result;
    }



    public function thong_ke_trang_thai_van_ban_den_get()
    {
        $currentYear = date('Y');

        $userDonVi = $this->getUserLogin()['id_don_vi'];
        $userId = $this->getUserLogin()['ql_nguoi_dung_id'];
        $user = $this->Ql_nguoi_dung_model->find($userId);

        if (!$user) {
            show_404(); //không có user
        }
        $donviTochuchanhchinh = $this->E_don_vi_model->where('ma_don_vi', 'PHONG_TCHC')->first();
        if (!$donviTochuchanhchinh) {
            show_404();
        }
        if ($userDonVi == $donviTochuchanhchinh['id_don_vi']) {
            $datiepnhan = $this->E_van_ban_model
                ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
                ->where('trang_thai', $this->common::STATUS_VAN_BAN_DEN['TIEP_NHAN']['value'])
                ->where("DATE_FORMAT(ngay_nhan,'%Y')", $currentYear)
                ->where('e_van_ban.deleted_at IS NULL')
                ->count();
            $dangxuly = $this->E_van_ban_model
                ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
                ->whereIn('trang_thai', [
                    $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
                    $this->common::STATUS_VAN_BAN_DEN['CHO_LANH_DAO_BUT_PHE']['value'],
                    $this->common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value'],
                ])
                ->where("DATE_FORMAT(ngay_nhan,'%Y')", $currentYear) // version sau xem lai
                ->where('e_van_ban.deleted_at IS NULL')
                ->count();
            $daxuly = $this->E_van_ban_model
                ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
                ->whereIn('trang_thai', [
                    $this->common::STATUS_VAN_BAN_DEN['LUU_TRU']['value'],
                    $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value'],
                ])
                ->where("DATE_FORMAT(ngay_nhan,'%Y')", $currentYear) // version sau xem lai
                ->where('e_van_ban.deleted_at IS NULL')
                ->count();
            $chuaphanhoi = $this->E_van_ban_model
                ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
                ->where('trang_thai', $this->common::STATUS_VAN_BAN_DEN['CHUA_PHAN_HOI']['value'])
                ->where("DATE_FORMAT(ngay_nhan,'%Y')", $currentYear)
                ->where('e_van_ban.deleted_at IS NULL')
                ->count();
            $daphanhoi = $this->E_van_ban_model
                ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
                ->where('trang_thai', $this->common::STATUS_VAN_BAN_DEN['DA_PHAN_HOI']['value'])
                ->where("DATE_FORMAT(ngay_nhan,'%Y')", $currentYear)
                ->where('e_van_ban.deleted_at IS NULL')
                ->count();

            $tongden = $this->E_van_ban_model
                ->where("DATE_FORMAT(ngay_ky,'%Y')", $currentYear) //version sau xet vanbanden: ngay_nhan, vanbannoibo va vanbandi: ngay_ky
                ->where('loai_van_ban', $this->common::VAN_BAN_DEN)->count();
            $tongdi = $this->E_van_ban_model
                ->where("DATE_FORMAT(ngay_ky,'%Y')", $currentYear) //version sau xet vanbanden: ngay_nhan, vanbannoibo va vanbandi: ngay_ky
                ->where('loai_van_ban', $this->common::VAN_BAN_DI)->count();
            $noibo = $this->E_van_ban_model
                ->where("DATE_FORMAT(ngay_ky,'%Y')", $currentYear) //version sau xet vanbanden: ngay_nhan, vanbannoibo va vanbandi: ngay_ky
                ->where('loai_van_ban', $this->common::VAN_BAN_NOI_BO)
                ->where('id_don_vi_soan', $userDonVi)
                ->count();
        } else {
            // $datiepnhan = $this->E_van_ban_model
            //     ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'left')
            //     ->join('e_don_vi_xu_ly', 'e_xu_ly.id_xu_ly = e_don_vi_xu_ly.id_xu_ly', 'left')
            //     ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
            //     ->where('trang_thai', $this->common::STATUS_VAN_BAN_DEN['TIEP_NHAN']['value'])
            //     ->where("DATE_FORMAT(ngay_nhan,'%Y')", $currentYear)
            //     ->where('e_van_ban.deleted_at IS NULL')
            //     ->where('e_don_vi_xu_ly.id_don_vi', $userDonVi)
            //     ->count();
            // $dangxuly = $this->E_van_ban_model
            //     ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'left')
            //     ->join('e_don_vi_xu_ly', 'e_xu_ly.id_xu_ly = e_don_vi_xu_ly.id_xu_ly', 'left')
            //     ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
            //     ->whereIn('trang_thai', [
            //         $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
            //         $this->common::STATUS_VAN_BAN_DEN['CHO_LANH_DAO_BUT_PHE']['value'],
            //         $this->common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value'],
            //     ])
            //     ->where("DATE_FORMAT(ngay_nhan,'%Y')", $currentYear) // version sau xem lai
            //     ->where('e_don_vi_xu_ly.id_don_vi', $userDonVi)
            //     ->where('e_van_ban.deleted_at IS NULL')
            //     ->count();
            // $daxuly = $this->E_van_ban_model
            //     ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'left')
            //     ->join('e_don_vi_xu_ly', 'e_xu_ly.id_xu_ly = e_don_vi_xu_ly.id_xu_ly', 'left')
            //     ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
            //     ->whereIn('trang_thai', [
            //         $this->common::STATUS_VAN_BAN_DEN['LUU_TRU']['value'],
            //         $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value'],
            //     ])
            //     ->where("DATE_FORMAT(ngay_nhan,'%Y')", $currentYear) // version sau xem lai
            //     ->where('e_don_vi_xu_ly.id_don_vi', $userDonVi)
            //     ->where('e_van_ban.deleted_at IS NULL')
            //     ->count();

            // $chuaphanhoi = $this->E_van_ban_model
            //     ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'left')
            //     ->join('e_don_vi_xu_ly', 'e_xu_ly.id_xu_ly = e_don_vi_xu_ly.id_xu_ly', 'left')
            //     ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
            //     ->where('trang_thai', $this->common::STATUS_VAN_BAN_DEN['CHUA_PHAN_HOI']['value'])
            //     ->where("DATE_FORMAT(ngay_nhan,'%Y')", $currentYear)
            //     ->where('e_don_vi_xu_ly.id_don_vi', $userDonVi)
            //     ->where('e_van_ban.deleted_at IS NULL')
            //     ->count();
            // $daphanhoi = $this->E_van_ban_model
            //     ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'left')
            //     ->join('e_don_vi_xu_ly', 'e_xu_ly.id_xu_ly = e_don_vi_xu_ly.id_xu_ly', 'left')
            //     ->where('loai_van_ban', $this->common::VAN_BAN_DEN)
            //     ->where('trang_thai', $this->common::STATUS_VAN_BAN_DEN['DA_PHAN_HOI']['value'])
            //     ->where("DATE_FORMAT(ngay_nhan,'%Y')", $currentYear)
            //     ->where('e_don_vi_xu_ly.id_don_vi', $userDonVi)
            //     ->where('e_van_ban.deleted_at IS NULL')
            //     ->count();

            ///////////////////////////////////////////////////////////////////////////////
            $this->E_van_ban_model->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");

            $datiepnhan = $this->E_van_ban_model
                ->select('e_van_ban.id_van_ban, e_van_ban.trang_thai')
                ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'inner')
                ->join('e_don_vi_xu_ly', 'e_xu_ly.id_xu_ly = e_don_vi_xu_ly.id_xu_ly', 'inner')
                ->where('e_van_ban.deleted_at IS NULL')
                ->where('e_don_vi_xu_ly.id_don_vi', $userDonVi)
                ->where("DATE_FORMAT(ngay_ky,'%Y')", $currentYear)
                ->where('e_van_ban.trang_thai', $this->common::STATUS_VAN_BAN_DEN['TIEP_NHAN']['value'])
                ->where("(
                    (e_van_ban.loai_van_ban = 1 AND e_van_ban.trang_thai IN (" .
                    implode(',', [
                        $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
                        $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value'],
                        $this->common::STATUS_VAN_BAN_DEN['CHUA_PHAN_HOI']['value'],
                        $this->common::STATUS_VAN_BAN_DEN['DA_PHAN_HOI']['value']
                    ]) . "))
                    OR
                    (e_van_ban.loai_van_ban = 2 AND e_van_ban.trang_thai IN (" .
                    implode(',', [
                        $this->common::STATUS_VAN_BAN_DI['DA_BAN_HANH']['value'],
                        $this->common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value'],
                        $this->common::STATUS_VAN_BAN_DI['DA_PHAN_HOI']['value'],
                        $this->common::STATUS_VAN_BAN_DI['CHUA_PHAN_HOI']['value']
                    ]) . ")))", NULL, FALSE)
                ->groupBy('e_van_ban.id_van_ban')
                ->count();


            $dangxuly = $this->E_van_ban_model
                ->select('e_van_ban.id_van_ban, e_van_ban.trang_thai')
                ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'inner')
                ->join('e_don_vi_xu_ly', 'e_xu_ly.id_xu_ly = e_don_vi_xu_ly.id_xu_ly', 'inner')
                ->where('e_van_ban.deleted_at IS NULL')
                ->where('e_don_vi_xu_ly.id_don_vi', $userDonVi)
                ->where("DATE_FORMAT(ngay_ky,'%Y')", $currentYear)
                ->whereIn('trang_thai', [
                    $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
                    $this->common::STATUS_VAN_BAN_DEN['CHO_LANH_DAO_BUT_PHE']['value'],
                    $this->common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value'],
                ])
                ->where("(
                    (e_van_ban.loai_van_ban = 1 AND e_van_ban.trang_thai IN (" .
                    implode(',', [
                        $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
                        $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value'],
                        $this->common::STATUS_VAN_BAN_DEN['CHUA_PHAN_HOI']['value'],
                        $this->common::STATUS_VAN_BAN_DEN['DA_PHAN_HOI']['value']
                    ]) . "))
                    OR
                    (e_van_ban.loai_van_ban = 2 AND e_van_ban.trang_thai IN (" .
                    implode(',', [
                        $this->common::STATUS_VAN_BAN_DI['DA_BAN_HANH']['value'],
                        $this->common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value'],
                        $this->common::STATUS_VAN_BAN_DI['DA_PHAN_HOI']['value'],
                        $this->common::STATUS_VAN_BAN_DI['CHUA_PHAN_HOI']['value']
                    ]) . ")))", NULL, FALSE)
                ->groupBy('e_van_ban.id_van_ban')
                ->count();


            $daxuly = $this->E_van_ban_model
                ->select('e_van_ban.id_van_ban, e_van_ban.trang_thai')
                ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'inner')
                ->join('e_don_vi_xu_ly', 'e_xu_ly.id_xu_ly = e_don_vi_xu_ly.id_xu_ly', 'inner')
                ->where('e_van_ban.deleted_at IS NULL')
                ->where('e_don_vi_xu_ly.id_don_vi', $userDonVi)
                ->where("DATE_FORMAT(ngay_ky,'%Y')", $currentYear)
                ->whereIn('trang_thai', [
                    $this->common::STATUS_VAN_BAN_DEN['LUU_TRU']['value'],
                    $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value'],
                ])
                ->where("(
                    (e_van_ban.loai_van_ban = 1 AND e_van_ban.trang_thai IN (" .
                    implode(',', [
                        $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
                        $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value'],
                        $this->common::STATUS_VAN_BAN_DEN['CHUA_PHAN_HOI']['value'],
                        $this->common::STATUS_VAN_BAN_DEN['DA_PHAN_HOI']['value']
                    ]) . "))
                    OR
                    (e_van_ban.loai_van_ban = 2 AND e_van_ban.trang_thai IN (" .
                    implode(',', [
                        $this->common::STATUS_VAN_BAN_DI['DA_BAN_HANH']['value'],
                        $this->common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value'],
                        $this->common::STATUS_VAN_BAN_DI['DA_PHAN_HOI']['value'],
                        $this->common::STATUS_VAN_BAN_DI['CHUA_PHAN_HOI']['value']
                    ]) . ")))", NULL, FALSE)
                ->groupBy('e_van_ban.id_van_ban')
                ->count();


            $chuaphanhoi = $this->E_van_ban_model
                ->select('e_van_ban.id_van_ban, e_van_ban.trang_thai')
                ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'inner')
                ->join('e_don_vi_xu_ly', 'e_xu_ly.id_xu_ly = e_don_vi_xu_ly.id_xu_ly', 'inner')
                ->where('e_van_ban.deleted_at IS NULL')
                ->where('e_don_vi_xu_ly.id_don_vi', $userDonVi)
                ->where("DATE_FORMAT(ngay_ky,'%Y')", $currentYear)
                ->where('trang_thai', $this->common::STATUS_VAN_BAN_DEN['CHUA_PHAN_HOI']['value'])
                ->where("(
                    (e_van_ban.loai_van_ban = 1 AND e_van_ban.trang_thai IN (" .
                    implode(',', [
                        $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
                        $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value'],
                        $this->common::STATUS_VAN_BAN_DEN['CHUA_PHAN_HOI']['value'],
                        $this->common::STATUS_VAN_BAN_DEN['DA_PHAN_HOI']['value']
                    ]) . "))
                    OR
                    (e_van_ban.loai_van_ban = 2 AND e_van_ban.trang_thai IN (" .
                    implode(',', [
                        $this->common::STATUS_VAN_BAN_DI['DA_BAN_HANH']['value'],
                        $this->common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value'],
                        $this->common::STATUS_VAN_BAN_DI['DA_PHAN_HOI']['value'],
                        $this->common::STATUS_VAN_BAN_DI['CHUA_PHAN_HOI']['value']
                    ]) . ")))", NULL, FALSE)
                ->groupBy('e_van_ban.id_van_ban')
                ->count();


            $daphanhoi = $this->E_van_ban_model
                ->select('e_van_ban.id_van_ban, e_van_ban.trang_thai')
                ->join('e_xu_ly', 'e_van_ban.id_van_ban = e_xu_ly.id_van_ban', 'inner')
                ->join('e_don_vi_xu_ly', 'e_xu_ly.id_xu_ly = e_don_vi_xu_ly.id_xu_ly', 'inner')
                ->where('e_van_ban.deleted_at IS NULL')
                ->where('e_don_vi_xu_ly.id_don_vi', $userDonVi)
                ->where("DATE_FORMAT(ngay_ky,'%Y')", $currentYear)
                ->where('trang_thai', $this->common::STATUS_VAN_BAN_DEN['DA_PHAN_HOI']['value'])
                ->where("(
                    (e_van_ban.loai_van_ban = 1 AND e_van_ban.trang_thai IN (" .
                    implode(',', [
                        $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
                        $this->common::STATUS_VAN_BAN_DEN['DA_XU_LY']['value'],
                        $this->common::STATUS_VAN_BAN_DEN['CHUA_PHAN_HOI']['value'],
                        $this->common::STATUS_VAN_BAN_DEN['DA_PHAN_HOI']['value']
                    ]) . "))
                    OR
                    (e_van_ban.loai_van_ban = 2 AND e_van_ban.trang_thai IN (" .
                    implode(',', [
                        $this->common::STATUS_VAN_BAN_DI['DA_BAN_HANH']['value'],
                        $this->common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value'],
                        $this->common::STATUS_VAN_BAN_DI['DA_PHAN_HOI']['value'],
                        $this->common::STATUS_VAN_BAN_DI['CHUA_PHAN_HOI']['value']
                    ]) . ")))", NULL, FALSE)
                ->groupBy('e_van_ban.id_van_ban')
                ->count();


            $tongden = $this->E_van_ban_model
                ->where("DATE_FORMAT(ngay_ky,'%Y')", $currentYear) //version sau xet vanbanden: ngay_nhan, vanbannoibo va vanbandi: ngay_ky
                ->where('loai_van_ban', $this->common::VAN_BAN_DEN)->count();
            $tongdi = $this->E_van_ban_model
                ->where("DATE_FORMAT(ngay_ky,'%Y')", $currentYear) //version sau xet vanbanden: ngay_nhan, vanbannoibo va vanbandi: ngay_ky
                ->where('loai_van_ban', $this->common::VAN_BAN_DI)->count();
            $noibo = $this->E_van_ban_model
                ->where("DATE_FORMAT(ngay_ky,'%Y')", $currentYear) //version sau xet vanbanden: ngay_nhan, vanbannoibo va vanbandi: ngay_ky
                ->where('loai_van_ban', $this->common::VAN_BAN_NOI_BO)
                ->where('id_don_vi_soan', $userDonVi)
                ->count();
        }

        resSuccess(
            [
                'tong_den' => $tongden,
                'tong_di' => $tongdi,
                'noi_bo' => $noibo,
                'trang_thai_xu_ly' => [
                    [
                        'name' => 'Đã tiếp nhận',
                        'tong_sl' => $datiepnhan
                    ],
                    [
                        'name' => 'Đang xử lý',
                        'tong_sl' => $dangxuly
                    ],
                    [
                        'name' => 'Đã xử lý',
                        'tong_sl' => $daxuly
                    ],
                    [
                        'name' => 'Chưa phản hồi',
                        'tong_sl' => $chuaphanhoi
                    ],
                    [
                        'name' => 'Đã phản hồi',
                        'tong_sl' => $daphanhoi
                    ]
                ]
            ]

        );
    }

    public function thong_ke_trang_thai_van_ban_di_get()
    {
        $currentYear = date('Y');
        $userId = $this->getUserLogin()['ql_nguoi_dung_id'];
        $user = $this->Ql_nguoi_dung_model->find($userId);
        if (!$user) {
            show_404(); //không có user
        }
        $donviTochuchanhchinh = $this->E_don_vi_model->where('ma_don_vi', 'PHONG_TCHC')->first();
        if (!$donviTochuchanhchinh) {
            show_404();
        }

        $taomoi = $this->E_van_ban_model
            ->where('loai_van_ban', $this->common::VAN_BAN_DI)
            ->where('trang_thai', $this->common::STATUS_VAN_BAN_DI['TAO_MOI']['value'])
            ->where("DATE_FORMAT(ngay_ky,'%Y')", $currentYear)
            ->where('e_van_ban.deleted_at IS NULL')
            ->count();

        $dabanhanh = $this->E_van_ban_model
            ->where('loai_van_ban', $this->common::VAN_BAN_DI)
            ->where('trang_thai', $this->common::STATUS_VAN_BAN_DI['DA_BAN_HANH']['value'])
            ->where("DATE_FORMAT(ngay_ky,'%Y')", $currentYear) // version sau xem lai
            ->where('e_van_ban.deleted_at IS NULL')
            ->count();

        $choxuly = $this->E_van_ban_model
            ->where('loai_van_ban', $this->common::VAN_BAN_DI)
            ->where('trang_thai', $this->common::STATUS_VAN_BAN_DI['CHO_XU_LY']['value'])
            ->where("DATE_FORMAT(ngay_ky,'%Y')", $currentYear) // version sau xem lai
            ->where('e_van_ban.deleted_at IS NULL')
            ->count();

        $chuaphanhoi = $this->E_van_ban_model
            ->where('loai_van_ban', $this->common::VAN_BAN_DI)
            ->where('trang_thai', $this->common::STATUS_VAN_BAN_DI['CHUA_PHAN_HOI']['value'])
            ->where("DATE_FORMAT(ngay_ky,'%Y')", $currentYear)
            ->where('e_van_ban.deleted_at IS NULL')
            ->count();

        $daphanhoi = $this->E_van_ban_model
            ->where('loai_van_ban', $this->common::VAN_BAN_DI)
            ->where('trang_thai', $this->common::STATUS_VAN_BAN_DI['DA_PHAN_HOI']['value'])
            ->where("DATE_FORMAT(ngay_ky,'%Y')", $currentYear)
            ->where('e_van_ban.deleted_at IS NULL')
            ->count();

        $luutru = $this->E_van_ban_model
            ->where('loai_van_ban', $this->common::VAN_BAN_DI)
            ->where('trang_thai', $this->common::STATUS_VAN_BAN_DI['LUU_TRU']['value'])
            ->where("DATE_FORMAT(ngay_ky,'%Y')", $currentYear)
            ->where('e_van_ban.deleted_at IS NULL')
            ->count();


        resSuccess(
            [
                'trang_thai_xu_ly' => [
                    [
                        'name' => 'Tạo mới',
                        'tong_sl' => $taomoi
                    ],
                    [
                        'name' => 'Đã ban hành',
                        'tong_sl' => $dabanhanh
                    ],
                    [
                        'name' => 'Chờ xử lý',
                        'tong_sl' => $choxuly
                    ],
                    [
                        'name' => 'Chưa phản hồi',
                        'tong_sl' => $chuaphanhoi
                    ],
                    [
                        'name' => 'Đã phản hồi',
                        'tong_sl' => $daphanhoi
                    ],
                    [
                        'name' => 'Lưu trữ',
                        'tong_sl' => $luutru
                    ]
                ]
            ]

        );
    }


    public function tinh_trang_xu_ly_van_ban_den_theo_phong_ban_get()
    {

        $now = date('Y-m-d');
        $fiveDaysAgo = date('Y-m-d', strtotime('5 days'));

        $donvi = $this->E_don_vi_model->get();
        $currentYear = date('Y');

        $this->E_van_ban_model->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
        foreach ($donvi as &$dv) {
            $tren5ngay = $this->E_van_ban_model
                ->select('COUNT(e_van_ban.id_van_ban) AS sl_vb_den')
                ->join('e_xu_ly', 'e_xu_ly.id_van_ban = e_van_ban.id_van_ban', 'left')
                ->join('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly', 'left')
                ->where('e_don_vi_xu_ly.id_don_vi', $dv['id_don_vi'])
                ->where("thoi_gian_xu_ly > ", $fiveDaysAgo)
                ->where('thoi_gian_xu_ly IS NOT NULL')
                ->where('deleted_at IS NULL')
                ->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DEN)
                ->whereIn('trang_thai', [
                    $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
                    $this->common::STATUS_VAN_BAN_DEN['CHO_LANH_DAO_BUT_PHE']['value'],
                    $this->common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value'],
                ])
                ->groupBy('e_van_ban.id_van_ban')
                ->count();


            $duoi5ngay = $this->E_van_ban_model
                ->select('COUNT(e_van_ban.id_van_ban) AS sl_vb_den')
                ->join('e_xu_ly', 'e_xu_ly.id_van_ban = e_van_ban.id_van_ban', 'left')
                ->join('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly', 'left')
                ->where('e_don_vi_xu_ly.id_don_vi', $dv['id_don_vi'])
                ->where("thoi_gian_xu_ly <= ", $fiveDaysAgo)
                ->where("thoi_gian_xu_ly >= ", $now)
                ->where('thoi_gian_xu_ly IS NOT NULL')
                ->where('deleted_at IS NULL')
                ->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DEN)
                ->whereIn('trang_thai', [
                    $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
                    $this->common::STATUS_VAN_BAN_DEN['CHO_LANH_DAO_BUT_PHE']['value'],
                    $this->common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value'],
                ])
                ->groupBy('e_van_ban.id_van_ban')
                ->count();

            $homnay = $this->E_van_ban_model
                ->select('COUNT(e_van_ban.id_van_ban) AS sl_vb_den')
                ->join('e_xu_ly', 'e_xu_ly.id_van_ban = e_van_ban.id_van_ban', 'left')
                ->join('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly', 'left')
                ->where('e_don_vi_xu_ly.id_don_vi', $dv['id_don_vi'])
                ->where('thoi_gian_xu_ly IS NOT NULL')
                ->where("DATE(thoi_gian_xu_ly)", $now)
                ->where('deleted_at IS NULL')
                ->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DEN)
                ->whereIn('trang_thai', [
                    $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
                    $this->common::STATUS_VAN_BAN_DEN['CHO_LANH_DAO_BUT_PHE']['value'],
                    $this->common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value'],
                ])
                ->groupBy('e_van_ban.id_van_ban')
                ->count();

            $quahan = $this->E_van_ban_model
                ->select('COUNT(e_van_ban.id_van_ban) AS sl_vb_den')
                ->join('e_xu_ly', 'e_xu_ly.id_van_ban = e_van_ban.id_van_ban', 'left')
                ->join('e_don_vi_xu_ly', 'e_don_vi_xu_ly.id_xu_ly = e_xu_ly.id_xu_ly', 'left')
                ->where('e_don_vi_xu_ly.id_don_vi', $dv['id_don_vi'])
                ->where('thoi_gian_xu_ly IS NOT NULL')
                ->where("thoi_gian_xu_ly < ", $now)
                ->where('deleted_at IS NULL')
                ->where('e_van_ban.loai_van_ban', $this->common::VAN_BAN_DEN)
                ->whereIn('trang_thai', [
                    $this->common::STATUS_VAN_BAN_DEN['CHO_XU_LY']['value'],
                    $this->common::STATUS_VAN_BAN_DEN['CHO_LANH_DAO_BUT_PHE']['value'],
                    $this->common::STATUS_VAN_BAN_DEN['DA_BUT_PHE']['value'],
                ])
                ->groupBy('e_van_ban.id_van_ban')
                ->count();

            $tinhtrang = [
                'tren_5_ngay' => $tren5ngay,
                'duoi_5_ngay' => $duoi5ngay,
                'trong_hom_nay' => $homnay,
                'da_qua_han' => $quahan
            ];
            $dv['tinh_trang'] = $tinhtrang;
        }

        resSuccess($donvi);
    }


    public function cap_nhat_trang_thai_post()
    {
        $id = commonRequest('id');
        $trangthai = commonRequest('trangthai');
        $userId = $this->getUserLogin()['ql_nguoi_dung_id'];
        $thongbao = $this->Ql_thong_bao_model->where('ql_thong_bao_id', $id)->first();

        // if (count($id) != count($thongbao)) {
        //     resError('Có văn bản không tồn tại');
        // }

        // Cập nhật bảng `ql_thong_bao`
        $this->Ql_thong_bao_model->where('ql_thong_bao_id', $id)->update([
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Cập nhật bảng `ql_thong_bao_nguoi_dung`
        $daxem = $this->db->select('*')
            ->from('ql_thong_bao_nguoi_dung')
            ->where('ql_thong_bao_id', $id)
            ->where('ql_nguoi_dung_id', $userId)
            ->get()
            ->row_array();

        if ($daxem['ql_thong_bao_da_doc'] == 0) {
            $this->db->where('ql_thong_bao_nguoi_dung.ql_thong_bao_id', $id)
                ->where('ql_nguoi_dung_id', $userId)
                ->update('ql_thong_bao_nguoi_dung', [
                    'ql_thong_bao_da_doc' => $trangthai,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

            // Gửi cập nhật số lượng thông báo chưa đọc qua Socket
            $this->load->helper('socket_helper');
            $newCount = $this->Ql_thong_bao_model
                ->join('ql_thong_bao_nguoi_dung', 'ql_thong_bao_nguoi_dung.ql_thong_bao_id = ql_thong_bao.ql_thong_bao_id')
                ->where('ql_thong_bao_nguoi_dung.ql_nguoi_dung_id', $userId)
                ->where('ql_thong_bao.ql_thong_bao_da_gui', 1)
                ->where('ql_thong_bao_nguoi_dung.ql_thong_bao_da_doc !=', 1)
                ->count();
            send_socket_unread_count($userId, $newCount);
        }
        // $this->createLog('Update', noi_dung: 'Cập nhật trạng thái thông báo', $thongbao, $this->Ql_thong_bao_model->find($id), 'ql_thong_bao');
        resSuccess(null, 'cập nhật thành công');
    }

    public function cap_nhat_dau_sao_post()
    {
        $id = commonRequest('id');
        $trangthai = commonRequest('trangthai');
        $userId = $this->getUserLogin()['ql_nguoi_dung_id'];

        $dausao = $this->db->select('*')
            ->from('ql_thong_bao_nguoi_dung')
            ->where('ql_thong_bao_id', $id)
            ->where('ql_nguoi_dung_id', $userId)
            ->get()
            ->row_array();

        if ($dausao) {
            $this->db->where('ql_thong_bao_nguoi_dung.ql_thong_bao_id', $id)
                ->where('ql_nguoi_dung_id', $userId)
                ->update('ql_thong_bao_nguoi_dung', [
                    'ql_thong_bao_sao' => $trangthai,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

            $sql = $this->db->last_query();
            resSuccess($sql, 'À ùm');
        }
        resSuccess(null, 'cập nhật thành công');
    }


    public function thongkeVanbantheoDonvi_get()
    {
        $postData = $this->get();
        $nam = $postData['nam'] ?? date('Y');
        $data = [
            'nam' => $nam,
            'id_don_vi' => $this->getUserLogin()['id_don_vi'],
        ];
        $response = $this->E_van_ban_model->thongkeVanbantheoDonvi($data);
        resSuccess($response, 'Thành công!');
    }
    public function thongkeVanbantheoNam_get()
    {
        $data = [
            'id_don_vi' => $this->getUserLogin()['id_don_vi'],
        ];
        $data = $this->E_van_ban_model->thongkeVanbanTheoNam($data);
        resSuccess($data);
    }
    public function thongkeTrangthaiVanbanden_get()
    {
        $postData = $this->input->get();
        $data = $this->E_van_ban_model->thongkeTrangthaiVanbanden($postData);
        resSuccess($data);
    }
    public function thongkeTrangthaiVanbandi_get()
    {
        $postData = $this->input->get();
        $data = $this->E_van_ban_model->thongkeTrangthaiVanbandi($postData);
        resSuccess($data);
    }
    public function thongkePhanhoiCuaDonvi_get()
    {
        $postData = $this->input->get();
        $data = $this->E_van_ban_model->thongkePhanhoiCuaDonvi($postData);
        resSuccess($data);
    }

    public function thongkevanban_v2_get()
    {
        $user = $this->getUserLogin();
        if (!$user) {
            resError('Người dùng không tồn tại');
        }

        $data['stats'] = $this->E_van_ban_model->thongkevanban_v2($user);
        $data['vanbanmoihomnay'] = $this->E_van_ban_model->vanbanmoihomnay($user);
        resSuccess($data);
    }
}
