<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property Hrm_yeu_cau_cap_nhat_model $Hrm_yeu_cau_cap_nhat_model
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 * @property Hrm_chung_chi_model $Hrm_chung_chi_model
 * @property Hrm_bang_cap_model $Hrm_bang_cap_model
 * @property Fileupload $fileupload
 * @property Validate $validate
 */



class Nhanvientucapnhat extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct(); 
        $this->load->helper('url');
        $this->load->model([
            'Hrm_nhan_vien_model',
            'Hrm_yeu_cau_cap_nhat_model',
            'Ql_nguoi_dung_model',
            'Hrm_minh_chung_model',
            'Hrm_chung_chi_model',
            'Hrm_bang_cap_model',
        ]);
        $this->load->library(['Validator', 'Fileupload', 'Validate']);

        $publicSegments = ['nhanvientucapnhat.index'];
        if (!$this->inSegment($publicSegments)) {
            $this->permissionMiddleware();
        }
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
            // 'columnControl' => commonRequest('columnControl') ?? [],
            // 'searchKey' => commonRequest('searchKey') ? commonRequest('searchKey') : [],
            'searchKey' => $searchKey,
            'fromDate' => commonRequest('fromDate') ? commonRequest('fromDate') : null,
            'toDate' => commonRequest('toDate') ? commonRequest('toDate') : null
        ];
        $auth = $this->getUserLogin();

        $data = $this->Hrm_yeu_cau_cap_nhat_model->getAll($data['start'], $data['length'], $data['searchValue'], $data['order'], $data['columns'], $data['searchKey'], $data['fromDate'], $data['toDate'], $auth);

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
            // 'sql' => $data['sql']
        ]);
    }

    public function duyet_post()
    {
        $id_yeu_cau_cap_nhat = commonRequest('id_yeu_cau_cap_nhat') ? commonRequest('id_yeu_cau_cap_nhat') : NULL;
        $trang_thai = commonRequest('trang_thai') ? commonRequest('trang_thai') : 0;
        $auth = $this->getUserLogin();

        $data = $this->Hrm_yeu_cau_cap_nhat_model->find($id_yeu_cau_cap_nhat);

        $du_lieu = json_decode($data['du_lieu'], true);
        if (count($du_lieu) > 0) {
            // TÃ¡ch don_vi_kiem_nhiem ra khá»i payload update hrm_nhan_vien
            $don_vi_kiem_nhiem = null;
            if (isset($du_lieu['don_vi_kiem_nhiem'])) {
                $don_vi_kiem_nhiem = $du_lieu['don_vi_kiem_nhiem'];
                unset($du_lieu['don_vi_kiem_nhiem']);
            }

            // TÃ¡ch minh_chung ra khá»i payload
            $minh_chung = null;
            if (isset($du_lieu['minh_chung'])) {
                $minh_chung = $du_lieu['minh_chung'];
                unset($du_lieu['minh_chung']);
            }

            // TÃ¡ch chung_chi ra khá»i payload
            $chung_chi = null;
            if (isset($du_lieu['chung_chi'])) {
                $chung_chi = $du_lieu['chung_chi'];
                unset($du_lieu['chung_chi']);
            }

            // TÃ¡ch bang_cap ra khá»i payload
            $bang_cap = null;
            if (isset($du_lieu['bang_cap'])) {
                $bang_cap = $du_lieu['bang_cap'];
                unset($du_lieu['bang_cap']);
            }
            // Xử lý delete_avatar: nếu có thì xóa avatar thay vì update column không tồn tại
            if (isset($du_lieu['delete_avatar']) && $du_lieu['delete_avatar']) {
                $du_lieu['avatar'] = null;
                unset($du_lieu['delete_avatar']);
            }

            // Loại bỏ các trường ảo không có trong database
            if (isset($du_lieu['dang_yeu_cau_cap_nhat'])) {
                unset($du_lieu['dang_yeu_cau_cap_nhat']);
            }

            if (isset($du_lieu['tu_dong_tang_phep'])) {
                unset($du_lieu['tu_dong_tang_phep']);
            }
            $nhanvien_cu = (array) $this->Hrm_nhan_vien_model->find($data['id_nhan_vien']);
            $old_values = [];
            foreach ($du_lieu as $key => $val) {
                if (array_key_exists($key, $nhanvien_cu)) $old_values[$key] = $nhanvien_cu[$key];
            }
            $ho_va_ten = $nhanvien_cu['ho_va_ten'] ?? '';

            $nhanvien_response = true;
            if (!empty($du_lieu)) {
                // Fix lỗi NULL cho các trường integer/date khi giá trị là chuỗi rỗng
                foreach ($du_lieu as $key => $value) {
                    if ($value === null || (is_string($value) && trim($value) === '')) {
                        $du_lieu[$key] = null;
                    }
                }
                $nhanvien_response = $this->Hrm_nhan_vien_model->where('id_nhan_vien', $data['id_nhan_vien'])->update($du_lieu);
            }

            if ($nhanvien_response) {
                // Cáº­p nháº­t don_vi_kiem_nhiem náº¿u cÃ³
                if (!is_null($don_vi_kiem_nhiem) && is_array($don_vi_kiem_nhiem)) {
                    $this->_applyDonViKiemNhiem($data['id_nhan_vien'], $don_vi_kiem_nhiem);
                }

                if ($trang_thai == 1 && !is_null($minh_chung) && is_array($minh_chung)) {
                    $this->_applyMinhChung($data['id_nhan_vien'], $minh_chung, $auth['ql_nguoi_dung_id']);
                }

                // Ã p dá»¥ng chá»©ng chá»‰ náº¿u duyá»‡t
                if ($trang_thai == 1 && !is_null($chung_chi) && is_array($chung_chi)) {
                    $this->_applyChungChi($data['id_nhan_vien'], $chung_chi);
                }

                // Ã p dá»¥ng báº±ng cáº¥p náº¿u duyá»‡t
                if ($trang_thai == 1 && !is_null($bang_cap) && is_array($bang_cap)) {
                    $this->_applyBangCap($data['id_nhan_vien'], $bang_cap);
                }

                $response = $this->Hrm_yeu_cau_cap_nhat_model->where('id_yeu_cau_cap_nhat', $id_yeu_cau_cap_nhat)->update([
                    'trang_thai' => $trang_thai,
                    'nguoi_duyet' => $auth['ql_nguoi_dung_id']
                ]);

                if ($response) {
                    $textStatus = $trang_thai == 1 ? 'Duyệt' : 'Từ chối';
                    $actionType = $trang_thai == 1 ? 'update' : 'từ chối';
                    $this->createLog($actionType, $textStatus . ' cập nhật thông tin nhân viên ' . $ho_va_ten, $old_values, $du_lieu, 'hrm_yeu_cau_cap_nhat');
                    resSuccess(null, 'Success', REST_Controller::HTTP_OK, true);
                } else {
                    resError("Đã có lỗi xảy ra khi cập nhật trạng thái yêu cầu.");
                }
            } else {
                resError("Đã có lỗi xảy ra khi cập nhật thông tin nhân viên.");
            }
        } else {
            resError("Không tìm thấy dữ liệu cần cập nhật.");
        }
    }

    public function duyet_nhieu_post()
    {
        $ids_yeu_cau_cap_nhat = commonRequest('ids_yeu_cau_cap_nhat') ? commonRequest('ids_yeu_cau_cap_nhat') : NULL;
        $trang_thai = commonRequest('trang_thai') ? commonRequest('trang_thai') : 0;
        $auth = $this->getUserLogin();

        foreach ($ids_yeu_cau_cap_nhat as $id_yeu_cau_cap_nhat) {
            $data = $this->Hrm_yeu_cau_cap_nhat_model->find($id_yeu_cau_cap_nhat);

            $du_lieu = json_decode($data['du_lieu'], true);
            if (count($du_lieu) > 0) {
                // TÃ¡ch don_vi_kiem_nhiem ra khá»i payload
                $don_vi_kiem_nhiem = null;
                if (isset($du_lieu['don_vi_kiem_nhiem'])) {
                    $don_vi_kiem_nhiem = $du_lieu['don_vi_kiem_nhiem'];
                    unset($du_lieu['don_vi_kiem_nhiem']);
                }

                // TÃ¡ch minh_chung ra khá»i payload
                $minh_chung = null;
                if (isset($du_lieu['minh_chung'])) {
                    $minh_chung = $du_lieu['minh_chung'];
                    unset($du_lieu['minh_chung']);
                }

                // TÃ¡ch chung_chi ra khá»i payload
                $chung_chi = null;
                if (isset($du_lieu['chung_chi'])) {
                    $chung_chi = $du_lieu['chung_chi'];
                    unset($du_lieu['chung_chi']);
                }

                // TÃ¡ch bang_cap ra khá»i payload
                $bang_cap = null;
                if (isset($du_lieu['bang_cap'])) {
                    $bang_cap = $du_lieu['bang_cap'];
                    unset($du_lieu['bang_cap']);
                }
                // Xử lý delete_avatar: nếu có thì xóa avatar thay vì update column không tồn tại
                if (isset($du_lieu['delete_avatar']) && $du_lieu['delete_avatar']) {
                    $du_lieu['avatar'] = null;
                    unset($du_lieu['delete_avatar']);
                }

                // Loại bỏ các trường ảo không có trong database
                if (isset($du_lieu['dang_yeu_cau_cap_nhat'])) {
                    unset($du_lieu['dang_yeu_cau_cap_nhat']);
                }
                $response = $this->Hrm_yeu_cau_cap_nhat_model->where('id_yeu_cau_cap_nhat', $id_yeu_cau_cap_nhat)->update([
                    'trang_thai' => $trang_thai,
                    'nguoi_duyet' => $auth['ql_nguoi_dung_id']
                ]);
                if (!$response) {
                    resError("ÄÃ£ cÃ³ lá»—i xáº£y ra khi cáº­p nháº­t tráº¡ng thÃ¡i yÃªu cáº§u.");
                }

                $textStatus = $trang_thai == 1 ? 'Duyệt' : 'Từ chối';
                $nhanvien = (array) $this->Hrm_nhan_vien_model->find($data['id_nhan_vien']);
                $old_values = [];
                foreach ($du_lieu as $key => $val) {
                    if (array_key_exists($key, $nhanvien)) $old_values[$key] = $nhanvien[$key];
                }
                $actionType = $trang_thai == 1 ? 'update' : 'từ chối';
                $this->createLog($actionType, $textStatus . ' cập nhật thông tin nhân viên ' . $nhanvien['ho_va_ten'], $old_values, $du_lieu, 'hrm_yeu_cau_cap_nhat');

                if ($trang_thai == 1) {

                    //Fix lỗi NULL
                    foreach ($du_lieu as $key => $value) {
                        if ($value === null || (is_string($value) && trim($value) === '')) {
                            $du_lieu[$key] = null;
                        }
                    }

                    if (!empty($du_lieu)) {
                        $nhanvien_response = $this->Hrm_nhan_vien_model->where('id_nhan_vien', $data['id_nhan_vien'])->update($du_lieu);
                        if (!$nhanvien_response) {
                            resError("ÄÃ£ cÃ³ lá»—i xáº£y ra khi cáº­p nháº­t thÃ´ng tin nhÃ¢n viÃªn.");
                        }
                    }
                    // Cáº­p nháº­t don_vi_kiem_nhiem náº¿u cÃ³
                    if (!is_null($don_vi_kiem_nhiem) && is_array($don_vi_kiem_nhiem)) {
                        $this->_applyDonViKiemNhiem($data['id_nhan_vien'], $don_vi_kiem_nhiem);
                    }

                    if (!is_null($minh_chung) && is_array($minh_chung)) {
                        $this->_applyMinhChung($data['id_nhan_vien'], $minh_chung, $auth['ql_nguoi_dung_id']);
                    }

                    // Ãp dá»¥ng chá»©ng chá»‰ náº¿u duyá»‡t
                    if (!is_null($chung_chi) && is_array($chung_chi)) {
                        $this->_applyChungChi($data['id_nhan_vien'], $chung_chi);
                    }

                    // Ãp dá»¥ng báº±ng cáº¥p náº¿u duyá»‡t
                    if (!is_null($bang_cap) && is_array($bang_cap)) {
                        $this->_applyBangCap($data['id_nhan_vien'], $bang_cap);
                    }
                }
            } else {
                resError("Không tìm thấy dữ liệu cần cập nhật");
            }
        }

        resSuccess(null, 'Success', REST_Controller::HTTP_OK, true);
    }

    public function tuchoi_post()
    {
        $id_yeu_cau_cap_nhat = commonRequest('id_yeu_cau_cap_nhat') ? commonRequest('id_yeu_cau_cap_nhat') : NULL;
        $trang_thai = commonRequest('trang_thai') ? commonRequest('trang_thai') : 0;
        $auth = $this->getUserLogin();

        if ($id_yeu_cau_cap_nhat && $trang_thai) {
            $data = $this->Hrm_yeu_cau_cap_nhat_model->find($id_yeu_cau_cap_nhat);
            $response = $this->Hrm_yeu_cau_cap_nhat_model->where('id_yeu_cau_cap_nhat', $id_yeu_cau_cap_nhat)->update([
                'trang_thai' => $trang_thai,
                'nguoi_duyet' => $auth['ql_nguoi_dung_id']
            ]);

            if ($response) {
                $nhanvien = $this->Hrm_nhan_vien_model->find($data['id_nhan_vien']);
                $du_lieu = json_decode($data['du_lieu'], true) ?? [];
                $old_values = [];
                foreach ($du_lieu as $key => $val) {
                    if (array_key_exists($key, $nhanvien)) $old_values[$key] = $nhanvien[$key];
                }
                $this->createLog('từ chối', 'Từ chối cập nhật thông tin nhân viên ' . ($nhanvien['ho_va_ten'] ?? ''), $old_values, $du_lieu, 'hrm_yeu_cau_cap_nhat');
                resSuccess(null, 'Success', REST_Controller::HTTP_OK, true);
            } else {
                resError("ÄÃ£ cÃ³ lá»—i xáº£y ra khi cáº­p nháº­t tráº¡ng thÃ¡i yÃªu cáº§u.");
            }
        } else {
            resError("KhÃ´ng tÃ¬m tháº¥y dá»¯ liá»‡u cáº§n cáº­p nháº­t.");
        }
    }

    /**
     * Cáº­p nháº­t báº£ng hrm_nhan_vien_don_vi: xÃ³a cÅ© vÃ  chÃ¨n má»›i
     */
    private function _applyDonViKiemNhiem($idNhanVien, array $danhSach)
    {
        $now = date('Y-m-d H:i:s');
        // XÃ³a toÃ n bá»™ kiÃªm nhiá»‡m hiá»‡n táº¡i
        $this->db->where('id_nhan_vien', $idNhanVien)->delete('hrm_nhan_vien_don_vi');

        foreach ($danhSach as $kn) {
            $knData = (array) $kn;
            if (empty($knData['id_don_vi_cong_tac'])) continue;

            $laLanhDao = isset($knData['la_lanh_dao']) && ($knData['la_lanh_dao'] == 1 || $knData['la_lanh_dao'] === true) ? 1 : 0;
            $this->db->insert('hrm_nhan_vien_don_vi', [
                'id_nhan_vien'        => $idNhanVien,
                'id_don_vi_cong_tac'  => $knData['id_don_vi_cong_tac'],
                'id_vi_tri_cong_viec' => $knData['id_vi_tri_cong_viec'] ?: null,
                'la_lanh_dao'         => $laLanhDao,
                'ghi_chu'             => $knData['ghi_chu'] ?? null,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);

            // Náº¿u lÃ  lÃ£nh Ä‘áº¡o thÃ¬ thÃªm vÃ o e_lanh_dao_don_vi
            if ($laLanhDao) {
                $exists = $this->db->where('id_nhan_vien', $idNhanVien)
                    ->where('id_don_vi', $knData['id_don_vi_cong_tac'])
                    ->count_all_results('e_lanh_dao_don_vi');
                if (!$exists) {
                    $this->db->insert('e_lanh_dao_don_vi', [
                        'id_nhan_vien' => $idNhanVien,
                        'id_don_vi'    => $knData['id_don_vi_cong_tac'],
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ]);
                }
            }
        }
    }

    public function countRequest_get()
    {
        $rq = $this->Hrm_yeu_cau_cap_nhat_model->countRequest();
        if (!$rq) {
            resError('KhÃ´ng cÃ³ yÃªu cáº§u nÃ o cáº§n cáº­p nháº­t');
        }
        resSuccess($rq, 'Láº¥y sá»‘ lÆ°á»£ng yÃªu cáº§u thÃ nh cÃ´ng');
    }

    private function _applyMinhChung($idNhanVien, array $minh_chung, $userId)
    {
        $now = date('Y-m-d H:i:s');
        foreach ($minh_chung as $mc) {
            $action = $mc['action'] ?? 'add';

            if ($action === 'delete') {
                // Soft-delete existing minh_chung by id_minh_chung
                if (!empty($mc['id_minh_chung'])) {
                    $this->db
                        ->where('id_minh_chung', $mc['id_minh_chung'])
                        ->where('id_nhan_vien', $idNhanVien) // safety: only own records
                        ->update('hrm_minh_chung', [
                            'deleted_at'      => $now,
                            'deleted_user_id' => $userId,
                        ]);
                }
                continue;
            }

            // action = 'add' (default)
            if (empty($mc['file_path'])) continue;

            $this->db->insert('hrm_minh_chung', [
                'id_nhan_vien'       => $idNhanVien,
                'id_loai_minh_chung' => $mc['id_loai_minh_chung'] ?? 1,
                'file_path'          => $mc['file_path'],
                'file_name'          => $mc['file_name'] ?? null,
                'file_extension'     => $mc['file_extension'] ?? null,
                'file_size'          => $mc['file_size'] ?? 0,
                'ref_table'          => $mc['ref_table'] ?? null,
                'ref_id'             => $mc['ref_id'] ?? null,
                'created_user_id'    => $userId,
                'created_at'         => $now,
            ]);
        }
    }

    /**
     * Ãp dá»¥ng thay Ä‘á»•i chá»©ng chá»‰ khi duyá»‡t yÃªu cáº§u
     * Há»— trá»£: add (thÃªm má»›i), update (cáº­p nháº­t), delete (xÃ³a)
     * Khi thÃªm má»›i: tá»± Ä‘á»™ng sync vÃ o báº£ng hrm_minh_chung + ghi log lá»‹ch sá»­
     */
    private function _applyChungChi($idNhanVien, array $danhSach)
    {
        $changes = [];
        $needSync = false;

        foreach ($danhSach as $item) {
            $action = isset($item['action']) ? $item['action'] : 'add';

            if ($action === 'delete' && !empty($item['id_chung_chi'])) {
                // Láº¥y thÃ´ng tin trÆ°á»›c khi xÃ³a Ä‘á»ƒ ghi log
                $existing = $this->db->where('id_chung_chi', $item['id_chung_chi'])->get('hrm_nhan_vien_chung_chi')->row_array();
                if ($existing) {
                    $this->db->where('id_chung_chi', $item['id_chung_chi'])->delete('hrm_nhan_vien_chung_chi');
                    $changes[] = [
                        'field'      => 'chung_chi',
                        'field_name' => 'Chá»©ng chá»‰',
                        'old_value'  => ['value' => 'XoÃ¡', 'label' => 'Chá»©ng chá»‰: ' . $existing['ten_chung_chi']],
                        'new_value'  => null
                    ];
                    $needSync = true;
                }
            } elseif ($action === 'update' && !empty($item['id_chung_chi'])) {
                $updateData = [
                    'ten_chung_chi'      => $item['ten_chung_chi'] ?? null,
                    'ngay_cap_chung_chi' => $item['ngay_cap_chung_chi'] ?? null,
                    'noi_cap'            => $item['noi_cap'] ?? null,
                    'ngay_het_han'       => $item['ngay_het_han'] ?? null,
                ];
                // LÆ°u danh sÃ¡ch files vÃ o column `files` (JSON)
                $resolvedFiles = $this->_resolveFilesJson($item['files'] ?? null);
                if ($resolvedFiles !== null) {
                    $updateData['files'] = $resolvedFiles;
                }
                $this->db->where('id_chung_chi', $item['id_chung_chi'])->update('hrm_nhan_vien_chung_chi', array_filter($updateData, function($v) { return !is_null($v); }));
                $changes[] = [
                    'field'      => 'chung_chi',
                    'field_name' => 'Chá»©ng chá»‰',
                    'old_value'  => ['value' => 'Cáº­p nháº­t'],
                    'new_value'  => ['value' => 'Cáº­p nháº­t', 'label' => 'Chá»©ng chá»‰: ' . ($item['ten_chung_chi'] ?? '')]
                ];
                $needSync = true;
            } else {
                // add: insert new record with files
                $insertData = [
                    'id_nhan_vien'       => $idNhanVien,
                    'ten_chung_chi'      => $item['ten_chung_chi'] ?? null,
                    'ngay_cap_chung_chi' => $item['ngay_cap_chung_chi'] ?? null,
                    'noi_cap'            => $item['noi_cap'] ?? null,
                    'ngay_het_han'       => $item['ngay_het_han'] ?? null,
                ];
                // LÆ°u danh sÃ¡ch files vÃ o column `files` (JSON)
                $resolvedFiles = $this->_resolveFilesJson($item['files'] ?? null);
                if ($resolvedFiles !== null) {
                    $insertData['files'] = $resolvedFiles;
                }
                $this->db->insert('hrm_nhan_vien_chung_chi', $insertData);
                $changes[] = [
                    'field'      => 'chung_chi',
                    'field_name' => 'Chá»©ng chá»‰',
                    'old_value'  => null,
                    'new_value'  => ['value' => 'ThÃªm má»›i', 'label' => 'Chá»©ng chá»‰: ' . ($item['ten_chung_chi'] ?? '') . ' - NÆ¡i cáº¥p: ' . ($item['noi_cap'] ?? '')]
                ];
                $needSync = true;
            }
        }

        // Sync chá»©ng chá»‰ vÃ o báº£ng minh chá»©ng
        if ($needSync) {
            $this->_syncChungChiToMinhChung($idNhanVien);
        }

        // Ghi log lá»‹ch sá»­
        if (!empty($changes)) {
            $this->logEmployeeHistory($idNhanVien, 'Duyá»‡t cáº­p nháº­t chá»©ng chá»‰', $changes);
        }
    }

    /**
     * Ãp dá»¥ng thay Ä‘á»•i báº±ng cáº¥p khi duyá»‡t yÃªu cáº§u
     * Há»— trá»£: add (thÃªm má»›i), update (cáº­p nháº­t), delete (xÃ³a)
     * Sau khi add/update: náº¿u cÃ³ file_path â†’ upsert hrm_minh_chung loáº¡i BANG_TN
     */
    private function _applyBangCap($idNhanVien, array $danhSach)
    {
        $changes = [];
        $auth    = $this->getUserLogin();

        // Láº¥y id_loai_minh_chung cá»§a loáº¡i BANG_TN má»™t láº§n
        $loaiBangTN = $this->db->where('ma_loai', 'BANG_TN')->get('hrm_loai_minh_chung')->row_array();
        $idLoaiBangTN = $loaiBangTN ? (int)$loaiBangTN['id_loai_minh_chung'] : null;

        foreach ($danhSach as $item) {
            $action = isset($item['action']) ? $item['action'] : 'add';

            if ($action === 'delete' && !empty($item['id_bang_cap'])) {
                $existing = $this->db->where('id_bang_cap', $item['id_bang_cap'])->get('hrm_nhan_vien_bang_cap')->row_array();
                if ($existing) {
                    $this->db->where('id_bang_cap', $item['id_bang_cap'])->delete('hrm_nhan_vien_bang_cap');

                    // Soft-delete linked minh_chung
                    $now = date('Y-m-d H:i:s');
                    $this->db
                        ->where('ref_table', 'hrm_nhan_vien_bang_cap')
                        ->where('ref_id', $item['id_bang_cap'])
                        ->where('id_nhan_vien', $idNhanVien)
                        ->update('hrm_minh_chung', [
                            'deleted_at'      => $now,
                            'deleted_user_id' => $auth['ql_nguoi_dung_id'],
                        ]);

                    $changes[] = [
                        'field'      => 'bang_cap',
                        'field_name' => 'Báº±ng cáº¥p',
                        'old_value'  => ['value' => 'XoÃ¡', 'label' => ($existing['noi_dao_tao'] ?? '') . ' - ' . ($existing['chuyen_nganh'] ?? '')],
                        'new_value'  => null
                    ];
                }
            } elseif ($action === 'update' && !empty($item['id_bang_cap'])) {
                $updateData = [
                    'tu_thang'       => $item['tu_thang']       ?? null,
                    'den_thang'      => $item['den_thang']       ?? null,
                    'noi_dao_tao'    => $item['noi_dao_tao']     ?? null,
                    'chuyen_nganh'   => $item['chuyen_nganh']    ?? null,
                    'trinh_do_dt'    => $item['trinh_do_dt']     ?? null,
                    'xep_loai_dt'    => $item['xep_loai_dt']     ?? null,
                    'file_path'      => $item['file_path']       ?? null,
                    'file_name'      => $item['file_name']       ?? null,
                    'file_extension' => $item['file_extension']  ?? null,
                    'file_size'      => isset($item['file_size']) ? (int)$item['file_size'] : null,
                ];
                $this->db
                    ->where('id_bang_cap', $item['id_bang_cap'])
                    ->update('hrm_nhan_vien_bang_cap', array_filter($updateData, function($v) { return !is_null($v); }));

                // Náº¿u cÃ³ file má»›i â†’ upsert hrm_minh_chung
                if (!empty($item['file_path']) && $idLoaiBangTN) {
                    $this->_upsertBangCapMinhChung(
                        $idNhanVien,
                        (int)$item['id_bang_cap'],
                        $idLoaiBangTN,
                        $item,
                        $auth['ql_nguoi_dung_id']
                    );
                }

                $changes[] = [
                    'field'      => 'bang_cap',
                    'field_name' => 'Báº±ng cáº¥p',
                    'old_value'  => ['value' => 'Cáº­p nháº­t'],
                    'new_value'  => ['value' => 'Cáº­p nháº­t', 'label' => ($item['noi_dao_tao'] ?? '') . ' - ' . ($item['chuyen_nganh'] ?? '')]
                ];
            } else {
                // add
                $this->db->insert('hrm_nhan_vien_bang_cap', [
                    'id_nhan_vien'   => $idNhanVien,
                    'tu_thang'       => $item['tu_thang']      ?? null,
                    'den_thang'      => $item['den_thang']      ?? null,
                    'noi_dao_tao'    => $item['noi_dao_tao']    ?? null,
                    'chuyen_nganh'   => $item['chuyen_nganh']   ?? null,
                    'trinh_do_dt'    => $item['trinh_do_dt']    ?? null,
                    'xep_loai_dt'    => $item['xep_loai_dt']    ?? null,
                    'file_path'      => $item['file_path']      ?? null,
                    'file_name'      => $item['file_name']      ?? null,
                    'file_extension' => $item['file_extension'] ?? null,
                    'file_size'      => isset($item['file_size']) ? (int)$item['file_size'] : null,
                ]);
                $newBangCapId = $this->db->insert_id();

                // Insert hrm_minh_chung linked to bang_cap má»›i
                if ($newBangCapId && !empty($item['file_path']) && $idLoaiBangTN) {
                    $this->_upsertBangCapMinhChung(
                        $idNhanVien,
                        $newBangCapId,
                        $idLoaiBangTN,
                        $item,
                        $auth['ql_nguoi_dung_id']
                    );
                }

                $changes[] = [
                    'field'      => 'bang_cap',
                    'field_name' => 'Báº±ng cáº¥p',
                    'old_value'  => null,
                    'new_value'  => ['value' => 'ThÃªm má»›i', 'label' => ($item['noi_dao_tao'] ?? '') . ' - ' . ($item['chuyen_nganh'] ?? '')]
                ];
            }
        }

        // Ghi log lá»‹ch sá»­
        if (!empty($changes)) {
            $this->logEmployeeHistory($idNhanVien, 'Duyá»‡t cáº­p nháº­t báº±ng cáº¥p', $changes);
        }
    }

    /**
     * Upsert hrm_minh_chung cho 1 báº±ng cáº¥p:
     * - Soft-delete minh_chung cÅ© linked Ä‘áº¿n bang_cap nÃ y (náº¿u cÃ³)
     * - Insert báº£n ghi má»›i vá»›i ref_table + ref_id
     */
    private function _upsertBangCapMinhChung($idNhanVien, $idBangCap, $idLoaiBangTN, array $item, $userId)
    {
        $now = date('Y-m-d H:i:s');

        // Soft-delete minh_chung cÅ© cá»§a bang_cap nÃ y (trÃ¡nh duplicate)
        $this->db
            ->where('ref_table', 'hrm_nhan_vien_bang_cap')
            ->where('ref_id', $idBangCap)
            ->where('id_nhan_vien', $idNhanVien)
            ->where('deleted_at IS NULL', null, false)
            ->update('hrm_minh_chung', [
                'deleted_at'      => $now,
                'deleted_user_id' => $userId,
            ]);

        // Insert minh_chung má»›i linked Ä‘áº¿n bang_cap
        $this->db->insert('hrm_minh_chung', [
            'id_nhan_vien'       => $idNhanVien,
            'id_loai_minh_chung' => $idLoaiBangTN,
            'file_path'          => $item['file_path'],
            'file_name'          => $item['file_name']      ?? basename($item['file_path']),
            'file_extension'     => $item['file_extension'] ?? null,
            'file_size'          => isset($item['file_size']) ? (int)$item['file_size'] : null,
            'ref_table'          => 'hrm_nhan_vien_bang_cap',
            'ref_id'             => $idBangCap,
            'created_user_id'    => $userId,
        ]);
    }

    /**
     * Sync táº¥t cáº£ chá»©ng chá»‰ cá»§a nhÃ¢n viÃªn vÃ o báº£ng minh chá»©ng (tÆ°Æ¡ng tá»± Chungchi controller)
     */
    private function _syncChungChiToMinhChung($idNhanVien)
    {
        $this->load->model('Hrm_minh_chung_model');
        $this->load->model('Hrm_chung_chi_model');

        $loai = $this->db->where('ma_loai', 'CHUNG_CHI')->get('hrm_loai_minh_chung')->row_array();
        if (!$loai) return;

        $loaiId = $loai['id_loai_minh_chung'];
        $auth = $this->getUserLogin();
        $userId = $auth['ql_nguoi_dung_id'];

        // XÃ³a minh chá»©ng CHUNG_CHI cÅ©
        $this->Hrm_minh_chung_model->deleteSyncedByNhanVienAndLoai($idNhanVien, $loaiId, $userId);

        // Fetch táº¥t cáº£ chá»©ng chá»‰ vÃ  re-insert
        $allChungChi = $this->Hrm_chung_chi_model->where('id_nhan_vien', $idNhanVien)->get();
        foreach ($allChungChi as $cc) {
            $files = json_decode($cc['files'], true);
            if (empty($files)) continue;

            $this->Hrm_minh_chung_model->syncFromModule($idNhanVien, $loaiId, $files, $userId);
        }
    }
    /**
     * Normalize `files` field: accepts array or JSON string, returns valid JSON string or null.
     * FE may send files as JSON.stringify(...) (string) or as a decoded array.
     */
    private function _resolveFilesJson($files)
    {
        if (empty($files)) return null;

        if (is_array($files)) {
            return !empty($files) ? json_encode($files, JSON_UNESCAPED_UNICODE) : null;
        }

        if (is_string($files)) {
            $decoded = json_decode($files, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !empty($decoded)) {
                return $files; // already valid JSON string, store as-is
            }
        }

        return null;
    }

    public function view_log_get()
    {
        $start       = (int)(commonRequest('start') ?? 0);
        $length      = (int)(commonRequest('length') ?? 20);
        $search      = commonRequest('search') ?? '';
        $from_date   = commonRequest('from_date') ?? '';
        $to_date     = commonRequest('to_date') ?? '';
        $action_type = commonRequest('action_type') ?? ''; // create | update | delete

        // ── Field mapping: DB key → nhãn tiếng Việt (null = ẩn khỏi diff) ───
        $fieldMapping = [
            // Thông tin cơ bản
            'ho_va_ten'             => 'Họ và tên',
            'email'                 => 'Email công ty',
            'email_ca_nhan'         => 'Email cá nhân',
            'gioi_tinh'             => 'Giới tính',
            'ngay_sinh'             => 'Ngày sinh',
            'ma_nhan_vien'          => 'Mã nhân viên',
            'ma_cham_cong'          => 'Mã chấm công',
            'mst_ca_nhan'           => 'Mã số thuế cá nhân',
            'so_dien_thoai'         => 'Số điện thoại',
            'que_quan'              => 'Quê quán',
            // CCCD / Hộ chiếu
            'cccd_so'               => 'Số CCCD/CMND',
            'cccd_ngay_cap'         => 'Ngày cấp CCCD',
            'cccd_noi_cap'          => 'Nơi cấp CCCD',
            'cccd_ngay_het_han'     => 'Ngày hết hạn CCCD',
            'ho_chieu_so'           => 'Số hộ chiếu',
            'ho_chieu_ngay_cap'     => 'Ngày cấp hộ chiếu',
            'ho_chieu_noi_cap'      => 'Nơi cấp hộ chiếu',
            'ho_chieu_ngay_het_han' => 'Ngày hết hạn hộ chiếu',
            // Học vấn
            'trinh_do_vh'           => 'Trình độ văn hóa',
            'trinh_do_dt'           => 'Trình độ đào tạo',
            'hoc_ham'               => 'Học hàm / Học vị',
            'noi_dt'                => 'Nơi đào tạo',
            'khoa_dt'               => 'Khoa đào tạo',
            'nganh_dt'              => 'Ngành đào tạo',
            'nam_tn'                => 'Năm tốt nghiệp',
            'xep_loai_tn'           => 'Xếp loại tốt nghiệp',
            // Địa chỉ
            'hktt_dia_chi'          => 'Địa chỉ hộ khẩu thường trú',
            'hktt_so_nha'           => 'Số nhà HKTT',
            'cohn_dia_chi'          => 'Địa chỉ chỗ ở hiện nay',
            'cohn_so_nha'           => 'Số nhà chỗ ở hiện nay',
            // Liên hệ khẩn cấp
            'lhkc_ho_ten'           => 'Tên liên hệ khẩn cấp',
            'lhkc_quan_he'          => 'Quan hệ liên hệ khẩn cấp',
            'lhkc_sdt_di_dong'      => 'SĐT liên hệ khẩn cấp',
            'lhkc_dia_chi'          => 'Địa chỉ liên hệ khẩn cấp',
            // Đơn vị / chức danh (đã được map nhãn từ khi log)
            'Đơn vị công tác'       => 'Đơn vị công tác',
            'Vị trí công việc'      => 'Vị trí công việc',
            'id_ca_lam_viec'        => 'Ca làm việc',
            'id_dan_toc'            => 'Dân tộc',
            'id_ton_giao'           => 'Tôn giáo',
            'id_quoc_tich'          => 'Quốc tịch',
            // Bảo hiểm
            'ti_le_dong'            => 'Tỷ lệ đóng BHXH (NLĐ %)',
            'ti_le_dong_dn'         => 'Tỷ lệ đóng BHXH (DN %)',
            'noi_dk_kcb'            => 'Nơi đăng ký KCB',
            'so_the_bhyt'           => 'Số thẻ BHYT',
            'so_so_bhxh'            => 'Số sổ BHXH',
            'ma_bhxh'               => 'Mã BHXH',
            'avatar'                => 'Ảnh đại diện',
            // ── Ẩn (null) ────────────────────────────────────────────────────
            'Mã nhân viên'          => null,
            'ql_nguoi_dung_id'      => null,
            'availability'          => null,
            'Ngày tạo'              => null,
            'Người tạo'             => null,
            'Ngày cập nhật'         => null,
            'Người cập nhật'        => null,
            'Ngày xóa'              => null,
            'Người xóa'             => null,
            'id_nhan_vien_bao_hiem' => null,
            'ngay_tham_gia'         => null,
            'ma_tinh_cap'           => null,
            'ten_tinh_cap'          => null,
            'ngay_het_han'          => null,
            'id_noi_dk_kcb'         => null,
            'ms_noi_kcb'            => null,
            'hktt_id_quoc_gia'      => null,
            'hktt_id_tinh_tp'       => null,
            'hktt_id_quan_huyen'    => null,
            'hktt_id_xa_phuong'     => null,
            'hktt_so_ho_khau'       => null,
            'hktt_ma_so_ho_gd'      => null,
            'hktt_la_chu_ho'        => null,
            'cohn_giong_hktt'       => null,
            'cohn_id_quoc_gia'      => null,
            'cohn_id_tinh_tp'       => null,
            'cohn_id_quan_huyen'    => null,
            'cohn_id_xa_phuong'     => null,
            'lhkc_sdt_nha_rieng'    => null,
            'lhkc_email'            => null,
        ];

        // ── Build query ──────────────────────────────────────────────────────
        $this->db
            ->select('ql_nhat_ky.ql_nhat_ky_id, ql_nhat_ky.ql_nhat_ky_hanh_dong, ql_nhat_ky.ql_nhat_ky_noi_dung, ql_nhat_ky.ql_nhat_ky_gia_tri_cu, ql_nhat_ky.ql_nhat_ky_gia_tri_moi, ql_nhat_ky.ql_nhat_ky_bang_du_lieu, ql_nhat_ky.ql_nhat_ky_ngay_tao, ql_nguoi_dung.ql_nguoi_dung_ho_ten, ql_nguoi_dung.ql_nguoi_dung_email, ql_nguoi_dung.ql_nguoi_dung_avatar')
            ->from('ql_nhat_ky')
            ->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = ql_nhat_ky.ql_nguoi_dung_id', 'left')
            ->like('ql_nhat_ky.ql_nhat_ky_controller', 'nhanvientucapnhat', 'both')
            ->order_by('ql_nhat_ky.ql_nhat_ky_ngay_tao', 'DESC');

        if ($from_date) $this->db->where('ql_nhat_ky.ql_nhat_ky_ngay_tao >=', $from_date . ' 00:00:00');
        if ($to_date)   $this->db->where('ql_nhat_ky.ql_nhat_ky_ngay_tao <=', $to_date . ' 23:59:59');

        if ($action_type) {
            if ($action_type === 'create')      $this->db->like('ql_nhat_ky.ql_nhat_ky_hanh_dong', 'tao', 'both');
            elseif ($action_type === 'delete')  $this->db->like('ql_nhat_ky.ql_nhat_ky_hanh_dong', 'xoa', 'both');
            elseif ($action_type === 'update')  $this->db->like('ql_nhat_ky.ql_nhat_ky_hanh_dong', 'update', 'both');
        }

        if ($search) {
            $this->db->group_start();
            $this->db->like('ql_nhat_ky.ql_nhat_ky_hanh_dong', $search);
            $this->db->or_like('ql_nhat_ky.ql_nhat_ky_noi_dung', $search);
            $this->db->or_like('ql_nguoi_dung.ql_nguoi_dung_ho_ten', $search);
            $this->db->group_end();
        }

        // Count filtered
        $countQuery      = clone $this->db;
        $recordsFiltered = $countQuery->count_all_results('', false);

        if ($length > 0) $this->db->limit($length, $start);
        $rows = $this->db->get()->result_array();

        // ── Process each row ─────────────────────────────────────────────────
        $refCache = [
            'id_dan_toc'     => [],
            'id_quoc_tich'   => [],
            'id_ton_giao'    => [],
            'id_ca_lam_viec' => [],
        ];

        $resolveValue = function($key, $id) use (&$refCache) {
            if (is_null($id) || trim($id) === '') return $id;
            
            if (!array_key_exists($id, $refCache[$key])) {
                switch ($key) {
                    case 'id_dan_toc':
                        $row = $this->db->where('id_dan_toc', $id)->get('dm_dan_toc')->row_array();
                        $refCache[$key][$id] = $row ? $row['ten'] : $id;
                        break;
                    case 'id_quoc_tich':
                        $row = $this->db->where('id_quoc_gia', $id)->get('dm_quoc_gia')->row_array();
                        $refCache[$key][$id] = $row ? $row['ten'] : $id;
                        break;
                    case 'id_ton_giao':
                        $row = $this->db->where('id_ton_giao', $id)->get('dm_ton_giao')->row_array();
                        $refCache[$key][$id] = $row ? $row['ten'] : $id;
                        break;
                    case 'id_ca_lam_viec':
                        $row = $this->db->where('id', $id)->get('hrm_ca_lam_viec')->row_array();
                        $refCache[$key][$id] = $row ? $row['ca_lam_viec'] : $id;
                        break;
                    default:
                        $refCache[$key][$id] = $id;
                }
            }
            return $refCache[$key][$id];
        };

        $data = [];
        foreach ($rows as $row) {
            $cuRaw  = [];
            $moiRaw = [];

            if (!empty($row['ql_nhat_ky_gia_tri_cu']) && $row['ql_nhat_ky_gia_tri_cu'] !== 'null') {
                $decoded = json_decode($row['ql_nhat_ky_gia_tri_cu'], true);
                if (is_array($decoded)) $cuRaw = $decoded;
            }
            if (!empty($row['ql_nhat_ky_gia_tri_moi']) && $row['ql_nhat_ky_gia_tri_moi'] !== 'null') {
                $decoded = json_decode($row['ql_nhat_ky_gia_tri_moi'], true);
                if (is_array($decoded)) $moiRaw = $decoded;
            }

            $chi_tiet = [];

            // Detect new format: gia_tri_moi contains {field: {cu:{}, moi:{}}}
            $firstVal    = !empty($moiRaw) ? reset($moiRaw) : null;
            $isNewFormat = is_array($firstVal) && (array_key_exists('cu', $firstVal) || array_key_exists('moi', $firstVal));

            if ($isNewFormat) {
                foreach ($moiRaw as $fieldKey => $change) {
                    if (!is_array($change)) continue;
                    $label = array_key_exists($fieldKey, $fieldMapping) ? $fieldMapping[$fieldKey] : $fieldKey;
                    if ($label === null) continue;

                    $cuVal  = isset($change['cu'])  ? (is_array($change['cu'])  && isset($change['cu']['label'])  ? $change['cu']['label']  : $change['cu'])  : null;
                    $moiVal = isset($change['moi']) ? (is_array($change['moi']) && isset($change['moi']['label']) ? $change['moi']['label'] : $change['moi']) : null;

                    if (isset($refCache[$fieldKey])) {
                        $cuVal  = $resolveValue($fieldKey, $cuVal);
                        $moiVal = $resolveValue($fieldKey, $moiVal);
                    }

                    $chi_tiet[$label] = ['cu' => $cuVal, 'moi' => $moiVal];
                }
            } else {
                // Old format: diff cuRaw vs moiRaw
                $allKeys = array_unique(array_merge(array_keys($cuRaw), array_keys($moiRaw)));
                foreach ($allKeys as $key) {
                    // Only process keys that are in our mapping
                    if (!array_key_exists($key, $fieldMapping)) continue;
                    $label = $fieldMapping[$key];
                    if ($label === null) continue;

                    $cuVal  = isset($cuRaw[$key])  ? $cuRaw[$key]  : null;
                    $moiVal = isset($moiRaw[$key]) ? $moiRaw[$key] : null;

                    // Skip unchanged
                    if ($cuVal === $moiVal) continue;
                    if (empty($cuVal) && empty($moiVal)) continue;

                    if (isset($refCache[$key])) {
                        $cuVal  = $resolveValue($key, $cuVal);
                        $moiVal = $resolveValue($key, $moiVal);
                    }

                    $chi_tiet[$label] = ['cu' => $cuVal, 'moi' => $moiVal];
                }
            }

            $data[] = [
                'ql_nhat_ky_id'         => $row['ql_nhat_ky_id'],
                'ql_nhat_ky_hanh_dong'  => $row['ql_nhat_ky_hanh_dong'],
                'ql_nhat_ky_noi_dung'   => $row['ql_nhat_ky_noi_dung'],
                'ql_nhat_ky_bang_du_lieu' => $row['ql_nhat_ky_bang_du_lieu'],
                'ql_nhat_ky_ngay_tao'   => $row['ql_nhat_ky_ngay_tao'],
                'ql_nguoi_dung_ho_ten'  => $row['ql_nguoi_dung_ho_ten'],
                'ql_nguoi_dung_email'   => $row['ql_nguoi_dung_email'],
                'ql_nguoi_dung_avatar'  => !empty($row['ql_nguoi_dung_avatar']) ? encryptString($row['ql_nguoi_dung_avatar']) : null,
                'chi_tiet'              => $chi_tiet,
            ];
        }

        resSuccess([
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ], 'Lấy lịch sử cập nhật của yêu cầu thành công');
    }
}
