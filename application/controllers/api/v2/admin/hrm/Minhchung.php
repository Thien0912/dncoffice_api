<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property DB_query_builder $db
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 * @property Hrm_loai_minh_chung_model $Hrm_loai_minh_chung_model
 * @property Hrm_minh_chung_model $Hrm_minh_chung_model
 * @property Hrm_chung_chi_model $Hrm_chung_chi_model
 * @property Hrm_bang_cap_model $Hrm_bang_cap_model
 * @property Fileupload $fileupload
 */
class Minhchung extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model(['Ql_nguoi_dung_model', 'Hrm_loai_minh_chung_model', 'Hrm_minh_chung_model', 'Hrm_chung_chi_model', 'Hrm_bang_cap_model']);
        $this->load->library(['Validator', 'Fileupload']);
    }

    /**
     * GET /minhchung/loai
     * Lấy danh sách loại minh chứng
     */
    public function loai_get()
    {
        $data = $this->Hrm_loai_minh_chung_model->getAllActive();
        resSuccess($data);
    }

    /**
     * GET /minhchung/show/{id_nhan_vien}
     * Lấy tất cả minh chứng của 1 nhân viên, grouped by loại
     */
    public function show_get($idNhanVien)
    {
        if (!$idNhanVien) {
            resError('Vui lòng cung cấp ID nhân viên', REST_Controller::HTTP_BAD_REQUEST);
        }

        $data = $this->Hrm_minh_chung_model->getByNhanVien($idNhanVien);

        // Merge với tất cả loại minh chứng (để hiện category trống)
        $allLoai = $this->Hrm_loai_minh_chung_model->getAllActive();
        $existingLoaiIds = array_column($data, 'id_loai_minh_chung');

        foreach ($allLoai as $loai) {
            if (!in_array($loai['id_loai_minh_chung'], $existingLoaiIds)) {
                $data[] = [
                    'id_loai_minh_chung' => $loai['id_loai_minh_chung'],
                    'ma_loai' => $loai['ma_loai'],
                    'ten_loai' => $loai['ten_loai'],
                    'thu_tu' => $loai['thu_tu'],
                    'files' => []
                ];
            }
        }

        // Sort by thu_tu
        usort($data, function ($a, $b) {
            return ($a['thu_tu'] ?? 0) - ($b['thu_tu'] ?? 0);
        });

        resSuccess($data);
    }

    /**
     * POST /minhchung/create
     * Upload file minh chứng
     */
    public function create_post()
    {
        $validator = new Validator();

        $data = [
            'id_nhan_vien' => commonRequest('id_nhan_vien'),
            'id_loai_minh_chung' => commonRequest('id_loai_minh_chung'),
        ];

        $rules = [
            'id_nhan_vien' => 'required|integer',
            'id_loai_minh_chung' => 'required|integer',
        ];

        $customMessages = [
            'id_nhan_vien.required' => 'Vui lòng chọn nhân viên',
            'id_loai_minh_chung.required' => 'Vui lòng chọn loại minh chứng',
        ];

        $validator->setCustomMessages($customMessages);

        if (!$validator->validate($data, $rules)) {
            resBadrequest($validator->errors());
        }

        // Upload files
        $folderName = 'minh-chung/' . date('Y') . '/' . date('m');

        // Get files from $_FILES (FormData sends as files_minh_chung[0], files_minh_chung[1]...)
        $fileUpload = isset($_FILES['files_minh_chung']) ? $_FILES['files_minh_chung'] : null;
        if (!$fileUpload && commonRequest('files_minh_chung')) {
            $fileUpload = commonRequest('files_minh_chung');
        }

        if (!$fileUpload || empty($fileUpload['name'])) {
            resError('Vui lòng chọn file minh chứng', REST_Controller::HTTP_BAD_REQUEST);
        }

        // Get details for each file (FE sends as files_minh_chung_details[0], [1]...)
        $fileDetails = commonRequest('files_minh_chung_details') ?: [];

        $userId = $this->getUserLogin()['ql_nguoi_dung_id'];
        $this->db->trans_start();

        // Determine ma_loai for auto-insert logic
        $loaiMinhChung = $this->Hrm_loai_minh_chung_model->find($data['id_loai_minh_chung']);
        $maLoai = $loaiMinhChung ? $loaiMinhChung['ma_loai'] : null;
        $tenLoai = $loaiMinhChung ? $loaiMinhChung['ten_loai'] : 'Không xác định';

        $uploadedItems = [];
        $autoInsertChanges = [];
        $allowedTypes = 'jpg|jpeg|png|webp|gif|pdf|doc|docx|xls|xlsx|zip|rar';

        foreach ($fileUpload['name'] as $key => $fileName) {
            $file = [
                'name' => $fileUpload['name'][$key],
                'type' => $fileUpload['type'][$key],
                'tmp_name' => $fileUpload['tmp_name'][$key],
                'error' => $fileUpload['error'][$key],
                'size' => $fileUpload['size'][$key],
            ];

            $result = $this->fileupload->upload($file, $folderName, $allowedTypes);
            if ($result['success']) {
                $extension = pathinfo($fileName, PATHINFO_EXTENSION);

                // Parse details for this file
                $details = [];
                if (isset($fileDetails[$key])) {
                    $details = is_string($fileDetails[$key])
                        ? json_decode($fileDetails[$key], true) ?: []
                        : (array) $fileDetails[$key];
                }

                $insertData = [
                    'id_nhan_vien' => $data['id_nhan_vien'],
                    'id_loai_minh_chung' => $data['id_loai_minh_chung'],
                    'file_path' => $result['file_path'],
                    'file_name' => $result['file_name'],
                    'file_extension' => $extension ?: null,
                    'file_size' => $fileUpload['size'][$key],
                    'created_user_id' => $userId,
                ];

                // --- AUTO-INSERT: Bằng cấp → hrm_nhan_vien_bang_cap ---
                if ($maLoai === 'BANG_TN' && !empty($details)) {

                    $bangCapData = [
                        'id_nhan_vien' => $data['id_nhan_vien'],
                        'tu_thang' => $this->_parseMonthYear($details['tu_thang'] ?? null, $details['nam_tu'] ?? null),
                        'den_thang' => $this->_parseMonthYear($details['den_thang'] ?? null, $details['nam_den'] ?? null),
                        'noi_dao_tao' => $details['noi_dao_tao'] ?? null,
                        'chuyen_nganh' => $details['chuyen_nganh'] ?? null,
                        'trinh_do_dt' => $details['trinh_do_dao_tao'] ?? ($details['trinh_do_dt'] ?? null),
                        'xep_loai_dt' => $details['xep_loai'] ?? ($details['xep_loai_dt'] ?? null),
                        'file_path' => $result['file_path'],
                        'file_name' => $result['file_name'],
                        'file_extension' => $extension ?: null,
                        'file_size' => (int) $fileUpload['size'][$key],
                    ];
                    // dd($bangCapData);
                    $this->db->insert('hrm_nhan_vien_bang_cap', $bangCapData);
                    $newBangCapId = $this->db->insert_id();

                    // Link minh chứng → bằng cấp
                    $insertData['ref_table'] = 'hrm_nhan_vien_bang_cap';
                    $insertData['ref_id'] = $newBangCapId;

                    $autoInsertChanges[] = [
                        'field' => 'bang_cap',
                        'field_name' => 'Bằng cấp',
                        'old_value' => null,
                        'new_value' => [
                            'value' => 'Thêm mới',
                            'label' => ($details['noi_dao_tao'] ?? '') . ' - ' . ($details['chuyen_nganh'] ?? '')
                        ]
                    ];
                }

                // --- AUTO-INSERT: Chứng chỉ → hrm_nhan_vien_chung_chi ---
                if ($maLoai === 'CHUNG_CHI' && !empty($details)) {
                    $fileJson = json_encode([
                        [
                            'file_path' => $result['file_path'],
                            'file_name' => $result['file_name'],
                            'file_extension' => $extension ?: null,
                            'file_size' => (int) $fileUpload['size'][$key],
                        ]
                    ], JSON_UNESCAPED_UNICODE);

                    $chungChiData = [
                        'id_nhan_vien' => $data['id_nhan_vien'],
                        'ten_chung_chi' => $details['ten_chung_chi'] ?? null,
                        'ngay_cap_chung_chi' => $details['ngay_cap_chung_chi'] ?? null,
                        'noi_cap' => $details['noi_cap'] ?? null,
                        'files' => $fileJson,
                    ];
                    $this->db->insert('hrm_nhan_vien_chung_chi', $chungChiData);

                    $autoInsertChanges[] = [
                        'field' => 'chung_chi',
                        'field_name' => 'Chứng chỉ',
                        'old_value' => null,
                        'new_value' => [
                            'value' => 'Thêm mới',
                            'label' => 'Chứng chỉ: ' . ($details['ten_chung_chi'] ?? '') . ' - Nơi cấp: ' . ($details['noi_cap'] ?? '')
                        ]
                    ];
                }

                $created = $this->Hrm_minh_chung_model->create($insertData);
                $uploadedItems[] = $created;
            }
        }

        // Sync chứng chỉ minh chứng (re-sync all like Chungchi controller does)
        if ($maLoai === 'CHUNG_CHI' && !empty($autoInsertChanges)) {
            $this->_syncChungChiToMinhChung($data['id_nhan_vien'], $userId);
        }

        $this->createLog('create', 'Upload minh chứng nhân viên', null, $uploadedItems, 'hrm_minh_chung');

        // --- SAVE HISTORY vào bảng hrm_nhan_vien_lich_su ---
        $fileNames = array_map(function ($item) {
            return $item['file_name'] ?? '';
        }, $uploadedItems);

        $history_changes = [
            [
                'field' => 'minh_chung_upload',
                'field_name' => 'Hình ảnh minh chứng',
                'old_value' => null,
                'new_value' => [
                    'value' => count($uploadedItems) . ' file',
                    'label' => $tenLoai . ': ' . implode(', ', $fileNames)
                ]
            ]
        ];

        // Append auto-insert changes (bằng cấp / chứng chỉ)
        if (!empty($autoInsertChanges)) {
            $history_changes = array_merge($history_changes, $autoInsertChanges);
        }

        $this->db->insert('hrm_nhan_vien_lich_su', [
            'id_nhan_vien' => $data['id_nhan_vien'],
            'action' => 'Thêm minh chứng' . (!empty($autoInsertChanges) ? ' + ' . $tenLoai : ''),
            'changes' => json_encode($history_changes, JSON_UNESCAPED_UNICODE),
            'created_user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        // --- END SAVE HISTORY ---

        $this->db->trans_commit();

        // Trả về dữ liệu mới của nhân viên
        $responseData = $this->Hrm_minh_chung_model->getByNhanVien($data['id_nhan_vien']);
        resSuccess($responseData, 'Upload minh chứng thành công', REST_INSTANCE_Controller::HTTP_CREATED);
    }

    /**
     * POST /minhchung/delete/{id}
     * Soft delete 1 file minh chứng
     */
    public function delete_post($id)
    {
        $userId = $this->getUserLogin()['ql_nguoi_dung_id'];

        $item = $this->Hrm_minh_chung_model->find($id);
        if (!$item) {
            resError('Không tìm thấy minh chứng', REST_Controller::HTTP_NOT_FOUND);
        }

        $this->db->trans_start();

        $this->Hrm_minh_chung_model->where('id_minh_chung', $id)->update([
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_user_id' => $userId,
        ]);

        $this->createLog('delete', 'Xóa minh chứng nhân viên', $item, null, 'hrm_minh_chung');

        // --- SAVE HISTORY vào bảng hrm_nhan_vien_lich_su ---
        $loaiMinhChung = $this->Hrm_loai_minh_chung_model->find($item['id_loai_minh_chung']);
        $tenLoai = $loaiMinhChung ? $loaiMinhChung['ten_loai'] : 'Không xác định';

        $history_changes = [
            [
                'field' => 'minh_chung_delete',
                'field_name' => 'Hình ảnh minh chứng',
                'old_value' => [
                    'value' => $item['file_name'],
                    'label' => $tenLoai . ': ' . ($item['file_name'] ?? '')
                ],
                'new_value' => null
            ]
        ];

        $this->db->insert('hrm_nhan_vien_lich_su', [
            'id_nhan_vien' => $item['id_nhan_vien'],
            'action' => 'Xóa minh chứng',
            'changes' => json_encode($history_changes, JSON_UNESCAPED_UNICODE),
            'created_user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        // --- END SAVE HISTORY ---

        $this->db->trans_commit();

        // Trả về dữ liệu mới của nhân viên
        $responseData = $this->Hrm_minh_chung_model->getByNhanVien($item['id_nhan_vien']);
        resSuccess($responseData, 'Xóa minh chứng thành công');
    }

    /**
     * Sync tất cả chứng chỉ của nhân viên vào bảng minh chứng
     * (Xóa minh chứng CHUNG_CHI cũ → fetch tất cả chứng chỉ → insert lại)
     */
    private function _syncChungChiToMinhChung($idNhanVien, $userId)
    {
        $loai = $this->db->where('ma_loai', 'CHUNG_CHI')->get('hrm_loai_minh_chung')->row_array();
        if (!$loai)
            return;

        $loaiId = $loai['id_loai_minh_chung'];

        // Xóa minh chứng CHUNG_CHI cũ
        $this->Hrm_minh_chung_model->deleteSyncedByNhanVienAndLoai($idNhanVien, $loaiId, $userId);

        // Fetch tất cả chứng chỉ và re-insert
        $allChungChi = $this->Hrm_chung_chi_model->where('id_nhan_vien', $idNhanVien)->get();
        foreach ($allChungChi as $cc) {
            $files = json_decode($cc['files'], true);
            if (empty($files))
                continue;

            $this->Hrm_minh_chung_model->syncFromModule($idNhanVien, $loaiId, $files, $userId);
        }
    }

    /**
     * Parse month/year to DB date format (yyyy-mm-01)
     * Mode 1: _parseMonthYear("03", "2026") → separate month + year
     * Mode 2: _parseMonthYear("03/2026")    → single string mm/yyyy
     * Mode 3: _parseMonthYear("2026-03")    → single string yyyy-mm
     */
    private function _parseMonthYear($value, $year = null)
    {
        // Mode 1: separate month + year (FE sends tu_thang="03", nam_tu="2026")
        if ($year !== null) {
            if (empty($value) || empty($year))
                return null;
            return $year . '-' . str_pad($value, 2, '0', STR_PAD_LEFT) . '-01';
        }

        if (empty($value))
            return null;

        // Mode 2: mm/yyyy format
        if (preg_match('/^(\d{1,2})\/(\d{4})$/', $value, $m)) {
            return $m[2] . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT) . '-01';
        }

        // Mode 3: yyyy-mm format
        if (preg_match('/^(\d{4})-(\d{1,2})$/', $value, $m)) {
            return $m[1] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-01';
        }

        // yyyy-mm-dd already valid
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        return null;
    }
}
