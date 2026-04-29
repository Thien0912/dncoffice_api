<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property E_van_ban_model $E_van_ban_model
 * @property E_file_dinh_kem_model $E_file_dinh_kem_model
 * @property E_but_phe_model $E_but_phe_model
 * @property E_xu_ly_model $E_xu_ly_model
 * @property E_don_vi_xu_ly_model $E_don_vi_xu_ly_model
 * @property E_don_vi_model $E_don_vi_model
 * @property E_bao_cao_model $E_bao_cao_model
 * @property Fileupload $fileupload
 * @property Common $common
 * @property Pxl $pxl
 * @property CI_Upload $upload
 * @property E_co_quan_model $E_co_quan_model
 * @property Ql_thong_bao_model $Ql_thong_bao_model
 * @property Hrm_nhan_vien_model $Hrm_nhan_vien_model
 * @property E_tag_model $E_tag_model
 */



class Tag extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        // $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model([
            'E_van_ban_model',
            'E_file_dinh_kem_model',
            'E_but_phe_model',
            'E_xu_ly_model',
            'E_don_vi_xu_ly_model',
            'E_don_vi_model',
            'E_bao_cao_model',
            'E_co_quan_model',
            'Ql_thong_bao_model',
            'Hrm_nhan_vien_model',
            'E_tag_model'
        ]);
        $this->load->library(['Validator', 'Fileupload', 'Common', 'Pxl', 'upload', 'session']);
    }

    public function index_get()
    {
        $auth = $this->getUserLogin();
        $data = $this->E_tag_model->get_user_tags($auth['ql_nguoi_dung_id']);
        resSuccess($data, 'Lấy thông tin nhãn thành công.');
    }

    public function getVanbanByTag_get()
    {
        $start = commonRequest('start') ? commonRequest('start') : 0;
        $length = commonRequest('length') ? commonRequest('length') : 10;
        $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;

        $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
            'order' => commonRequest('order'),
            'columns' => commonRequest('columns')
        ] : [];

        $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];

        if ($searchKey && $searchKey['id_tag']) {
            // Truy vấn lấy danh sách id_van_ban liên kết với id_tag
            $id_tags = $this->db->select('e_tag_van_ban.id_van_ban')
                ->from('e_tag')
                ->join('e_tag_van_ban', 'e_tag.id_tag = e_tag_van_ban.id_tag', 'left')
                ->where('e_tag_van_ban.id_tag', $searchKey['id_tag'])
                ->get()
                ->result_array();
            $id_van_bans = array_column($id_tags, 'id_van_ban');
            $searchKey['id_van_ban'] = $id_van_bans;
            unset($searchKey['id_tag']);
        }

        $fromDate = commonRequest('fromDate') ? commonRequest('fromDate') : null;
        $toDate = commonRequest('toDate') ? commonRequest('toDate') : null;
        $auth = $this->getUserLogin();

        $data = $this->E_tag_model->getAllVanban_byTag($start, $length, $searchValue, $orderBy, $searchKey, $fromDate, $toDate);

        $tags = $this->E_tag_model->get_user_tags($auth['ql_nguoi_dung_id']);
        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
            'tren_5_ngay' => $data['tren5ngay'],
            'duoi_5_ngay' => $data['duoi5ngay'],
            'hom_nay' => $data['homnay'],
            'qua_han' => $data['quahan'],
            'tags' => $tags,
        ]);
    }

    public function create_post()
    {
        // Validate dữ liệu
        $this->validate_data();

        // Lấy dữ liệu đã validate
        $data = $this->getValidationRules()['data'];

        // Lấy thông tin người dùng hiện tại
        $auth = $this->getUserLogin();

        // Thêm thông tin người tạo và thời gian tạo
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['created_user_id'] = $auth['ql_nguoi_dung_id'];
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['updated_user_id'] = $auth['ql_nguoi_dung_id'];

        // Bắt đầu transaction
        $this->db->trans_start();

        // Thêm tag vào bảng `e_tag`
        $result = $this->E_tag_model->create($data);

        // Tạo log
        $this->createLog('create', 'Thêm mới nhãn', null, $result, 'e_tag');

        // Commit transaction
        $this->db->trans_commit();

        $tags = $this->E_tag_model->get_user_tags($auth['ql_nguoi_dung_id']);


        // Trả về kết quả thành công
        resSuccess([
            'tag' => $result,
            'tags' => $tags
        ], 'Thêm nhãn thành công', REST_Controller::HTTP_CREATED);
    }

    public function show_get($id)
    {
        // Lấy thông tin người dùng hiện tại
        $auth = $this->getUserLogin();
        $tag = $this->E_tag_model->where('deleted_at IS NULL')->find($id);

        // Kiểm tra nếu không tìm thấy tag
        if (!$tag) resError('Không tìm thấy nhãn này.', REST_Controller::HTTP_NOT_FOUND);

        $tags = $this->E_tag_model->get_user_tags($auth['ql_nguoi_dung_id']);
        // Trả về dữ liệu
        resSuccess([
            'tag' => $tag,
            'tags' => $tags,
        ], 'Lấy thông tin nhãn và danh sách văn bản thành công');
    }

    public function update_post($id)
    {
        // Tìm tag theo ID
        $tag = $this->E_tag_model->find($id);
        if (!$tag) {
            resError('Không tìm thấy nhãn này.', REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        // Validate dữ liệu
        $this->validate_data();
        $data = $this->getValidationRules()['data'];

        // Thêm thông tin người cập nhật và thời gian cập nhật
        $auth = $this->getUserLogin();
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['updated_user_id'] = $auth['ql_nguoi_dung_id'];

        // Bắt đầu transaction
        $this->db->trans_start();
        $this->E_tag_model->where('id_tag', $id)->update($data);
        $updatedTag = $this->E_tag_model->find($id);

        // Tạo log
        $this->createLog('update', 'Cập nhật nhãn', $tag, $updatedTag, 'e_tag');
        $this->db->trans_commit();

        $tags = $this->E_tag_model->get_user_tags($auth['ql_nguoi_dung_id']);
        resSuccess([
            'tag' => $updatedTag,
            'tags' => $tags
        ], 'Cập nhật nhãn thành công');
    }

    public function delete_post($id)
    {
        // Tìm tag theo ID
        $tag = $this->E_tag_model->find($id);
        if (!$tag) {
            resError('Không tìm thấy nhãn này.', REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        // Bắt đầu transaction
        $this->db->trans_start();
        $auth = $this->getUserLogin();
        $this->E_tag_model->where('id_tag', $id)->update([
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_user_id' => $auth['ql_nguoi_dung_id'],
        ]);
        // Tạo log
        $this->createLog('delete', 'Xóa nhãn', $tag, null, 'e_tag');
        $this->db->trans_commit();

        $tags = $this->E_tag_model->get_user_tags($auth['ql_nguoi_dung_id']);

        // Trả về kết quả thành công
        resSuccess(['tags' => $tags], 'Xóa nhãn thành công');
    }

    public function addUserToTag_post()
    {
        $auth = $this->getUserLogin();
        // Lấy thông tin từ request và gom vào mảng $data
        $data = [
            'id_tag' => commonRequest('id_tag') ?? null,
            'id_nhan_vien' => commonRequest('id_nhan_vien') ?? [],
            'updated_at' => $auth['ql_nguoi_dung_id'],
            'updated_user_id' => $auth['ql_nguoi_dung_id'],
        ];

        // Kiểm tra id_tag hợp lệ
        if (empty($data['id_tag']))  resError('Thiếu ID nhãn.', 400);

        // Lấy tag và kiểm tra tồn tại
        $tag = $this->E_tag_model->find($data['id_tag']);
        if (!$tag || !isset($tag['ql_nguoi_dung_id'])) resError('Không tìm thấy thông tin nhãn.', 404);

        // Tách danh sách người dùng đã có trong tag
        $tag_nguoidungid = array_filter(array_map('trim', explode(',', $tag['ql_nguoi_dung_id'])));

        $ql_nguoi_dung_ids = $errors = [];

        // Đảm bảo $data['id_nhan_vien'] là mảng
        if (!is_array($data['id_nhan_vien'])) resError('Danh sách nhân viên không hợp lệ.', 400);

        // Lặp qua danh sách nhân viên
        foreach ($data['id_nhan_vien'] as $id_nhan_vien) {
            $nv = $this->Hrm_nhan_vien_model->find($id_nhan_vien);
            if ($nv && isset($nv['ql_nguoi_dung_id']) && $nv['ql_nguoi_dung_id']) {
                $ql_nguoi_dung_ids[] = $nv['ql_nguoi_dung_id'];
            } elseif ($nv) {
                $errors[] = $nv['ho_va_ten'] . ' chưa có tài khoản trên hệ thống, Không thể chia sẻ.';
            } else {
                $errors[] = 'Không tìm thấy nhân viên với ID: ' . $id_nhan_vien;
            }
        }

        // Nếu có lỗi thì trả về lỗi luôn
        if (count($errors) > 0) {
            resError('Chia sẻ nhãn thất bại.', 500, $errors);
        }

        // Gộp người dùng cũ + mới, loại trùng
        $ql_nguoi_dung_id_string = implode(', ', array_unique(array_merge($ql_nguoi_dung_ids, $tag_nguoidungid)));
        $data['ql_nguoi_dung_id'] = $ql_nguoi_dung_id_string;
        unset($data['id_nhan_vien']);

        $this->db->trans_start();
        $this->E_tag_model->where('id_tag', $data['id_tag'])->update($data);
        $this->db->trans_commit();

        $updatedtag = $this->E_tag_model->find($data['id_tag']);

        resSuccess($updatedtag, 'Đã chia sẻ nhãn!');
    }

    public function updateByKey_post($id)
    {
        $tag = $this->E_tag_model->find($id);
        if (!$tag) {
            resError('Không tìm thấy nhãn này.', REST_Controller::HTTP_NOT_FOUND);
            return;
        }
        $auth = $this->getUserLogin();

        $data = [
            'mau_tag' => commonRequest('mau_tag') ? commonRequest('mau_tag') : ($tag['mau_tag'] ?? null),
            'ten_tag' => commonRequest('ten_tag') ? commonRequest('ten_tag') : ($tag['ten_tag'] ?? null)
        ];

        $this->db->trans_start();
        $this->E_tag_model->where('id_tag', $id)->update($data);
        $this->db->trans_commit();

        $updatedTag = $this->E_tag_model->get_user_tags($auth['ql_nguoi_dung_id']);

        resSuccess($updatedTag, 'Thay đổi thông tin nhãn thành công!');
    }

    public function addVanbanToTag_post()
    {
        $auth = $this->getUserLogin();
        $data = [
            'id_tag' => commonRequest('id_tag') ? commonRequest('id_tag') : null,
            'id_van_ban' => commonRequest('id_van_ban') ? commonRequest('id_van_ban') : []
        ];
        $this->db->trans_begin();
        try {
            //code...
            foreach ($data['id_van_ban'] as $key => $id_vanban) {
                $tag = $this->E_tag_model->find($data['id_tag']);
                $vanban = $this->E_van_ban_model->find($id_vanban);
                if (!$tag || !$vanban) {
                    throw new \Exception('Không tìm thấy tag hoặc văn bản tương ứng, Vui lòng tải lại trang và thử lại');
                }
                $checkIsset = $this->db->select('*')->from('e_tag_van_ban')
                    ->where('id_tag', $data['id_tag'])
                    ->where('id_van_ban', $id_vanban)
                    ->get()->result_array();
                if (!$checkIsset) {
                    $this->db->insert('e_tag_van_ban', [
                        'id_tag' => $data['id_tag'],
                        'id_van_ban' => $id_vanban
                    ]);
                }
            }
            $this->db->trans_commit();
            resSuccess(NULL, 'Thêm văn bản vào nhãn thành công');
        } catch (\Throwable $th) {
            //throw $th;
            $this->db->trans_rollback();
            resError('Lỗi xử lý query', 500, $th->getMessage());
        }
    }

    private function getValidationRules()
    {
        $auth = $this->getUserLogin();
        $data = [
            'ten_tag' => commonRequest('ten_tag') ?? null,
            'mau_tag' => commonRequest('mau_tag') ?? null,
            'ql_nguoi_dung_id' => commonRequest('ql_nguoi_dung_id') ?? $auth['ql_nguoi_dung_id'], // Thêm trường ql_nguoi_dung_id
            'id_owner' => $auth['ql_nguoi_dung_id']
        ];

        $rules = [
            'ten_tag' => 'required',
            'mau_tag' => 'required',
        ];

        $customMessages = [
            'ten_tag.required' => 'Tên nhãn là bắt buộc',
            'mau_tag.required' => 'Màu nhãn là bắt buộc',
        ];

        return compact('data', 'rules', 'customMessages');
    }

    private function validate_data()
    {
        $validator = new Validator();
        $validation = $this->getValidationRules();

        $validator->setCustomMessages($validation['customMessages']);

        if (!$validator->validate($validation['data'], $validation['rules'])) {
            resBadrequest($validator->errors());
        }
    }
    public function deleteVanbanOnTag_post()
    {
        $data = [
            'id_tag' => commonRequest('id_tag') ? commonRequest('id_tag') : null,
            'id_van_ban' => commonRequest('id_van_ban') ? commonRequest('id_van_ban') : []
        ];
        $this->db->trans_begin();
        try {
            //code... 
            foreach ($data['id_van_ban'] as $key => $id_vanban) {
                $tag = $this->db->select('*')->where('id_tag', $data['id_tag'])->where('id_van_ban', $id_vanban)->get('e_tag_van_ban')->result_array();
                if (!$tag)  throw new \Exception('Không tìm thấy tag tương ứng');

                $this->db->where('id_tag', $data['id_tag'])->where('id_van_ban', $id_vanban)->delete('e_tag_van_ban');
            }
            $this->db->trans_commit();
            resSuccess(NULL, 'Đã xóa văn bản khỏi nhãn');
        } catch (\Throwable $th) {
            //throw $th;
            $this->db->trans_rollback();
            resError('Lỗi xử lý query', 500, $th->getMessage());
        }
    }
}
