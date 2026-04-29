<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property Ql_nguoi_dung_model $Ql_nguoi_dung_model
 * @property Ql_thong_bao_model $Ql_thong_bao_model
 */

class Notifications extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Ql_thong_bao_model');
    }
    public function index_get()
    {
        $user = $this->getUserLogin();

        $result = $this->Ql_thong_bao_model->getNotificationsByUserId($user['ql_nguoi_dung_id']);
        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => [
                'notifications' => $result['data'],
                'countAllRecord' => $result['recordsFiltered']
            ]
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function show_get($id)
    {
        $user = $this->getUserLogin();
        $notification = $this->Ql_thong_bao_model
            ->join('ql_thong_bao_nguoi_dung', 'ql_thong_bao_nguoi_dung.ql_thong_bao_id = ql_thong_bao.ql_thong_bao_id')
            ->where('ql_thong_bao_nguoi_dung.ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])
            ->where('ql_thong_bao.ql_thong_bao_da_gui', 1)
            ->where('ql_thong_bao.ql_thong_bao_id', $id)
            ->first();

        if (!$notification) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_NOT_FOUND,
                'message' => 'Notification not found',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }
        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'data' => $notification
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function new_notifications_get()
    {
        $user = $this->getUserLogin();
        $newNoti = $this->Ql_thong_bao_model
            ->join('ql_thong_bao_nguoi_dung', 'ql_thong_bao_nguoi_dung.ql_thong_bao_id = ql_thong_bao.ql_thong_bao_id')
            ->where('ql_thong_bao_nguoi_dung.ql_nguoi_dung_id', $user['ql_nguoi_dung_id'])
            ->where('ql_thong_bao.ql_thong_bao_da_gui', 1)
            ->where('ql_thong_bao_nguoi_dung.ql_thong_bao_da_doc !=', 1)
            ->count();

        $this->response([
            'status' => REST_INSTANCE_Controller::HTTP_OK,
            'message' => 'Success',
            'success' => true,
            'new_noti' => $newNoti
        ], REST_INSTANCE_Controller::HTTP_OK);
    }

    public function update_star_post()
    {
        $user = $this->getUserLogin();
        $id = commonRequest('id');
        $sao = commonRequest('sao');

        if (!$id) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Missing notification ID',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        $result = $this->Ql_thong_bao_model->updateStarStatus($id, $user['ql_nguoi_dung_id'], $sao);

        if ($result) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Update successful',
                'success' => true
            ], REST_INSTANCE_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Update failed',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete_post()
    {
        $user = $this->getUserLogin();
        $ids = commonRequest('ids');

        if (empty($ids) || !is_array($ids)) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Missing or invalid notification IDs',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        $result = $this->Ql_thong_bao_model->deleteNotificationsForUser($ids, $user['ql_nguoi_dung_id']);

        if ($result) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Delete successful',
                'success' => true
            ], REST_INSTANCE_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Delete failed',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Đánh dấu một hoặc nhiều thông báo là đã đọc
     * Request body: { ids: [1, 2, 3] }
     */
    public function mark_as_read_post()
    {
        $user = $this->getUserLogin();
        $ids = commonRequest('ids');

        if (empty($ids) || !is_array($ids)) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Missing or invalid notification IDs',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        // Update ql_thong_bao_da_doc = 1 cho user này
        $this->db->where('ql_nguoi_dung_id', $user['ql_nguoi_dung_id']);
        $this->db->where_in('ql_thong_bao_id', $ids);
        $result = $this->db->update('ql_thong_bao_nguoi_dung', ['ql_thong_bao_da_doc' => 1]);

        if ($result) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Mark as read successful',
                'success' => true
            ], REST_INSTANCE_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Mark as read failed',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Đánh dấu một hoặc nhiều thông báo là chưa đọc
     * Request body: { ids: [1, 2, 3] }
     */
    public function mark_as_unread_post()
    {
        $user = $this->getUserLogin();
        $ids = commonRequest('ids');

        if (empty($ids) || !is_array($ids)) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_BAD_REQUEST,
                'message' => 'Missing or invalid notification IDs',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_BAD_REQUEST);
        }

        // Update ql_thong_bao_da_doc = 0 cho user này
        $this->db->where('ql_nguoi_dung_id', $user['ql_nguoi_dung_id']);
        $this->db->where_in('ql_thong_bao_id', $ids);
        $result = $this->db->update('ql_thong_bao_nguoi_dung', ['ql_thong_bao_da_doc' => 0]);

        if ($result) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Mark as unread successful',
                'success' => true
            ], REST_INSTANCE_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Mark as unread failed',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Đánh dấu tất cả thông báo là đã đọc cho user
     */
    public function mark_all_as_read_post()
    {
        $user = $this->getUserLogin();

        // Update tất cả thông báo của user
        $this->db->where('ql_nguoi_dung_id', $user['ql_nguoi_dung_id']);
        $result = $this->db->update('ql_thong_bao_nguoi_dung', ['ql_thong_bao_da_doc' => 1]);

        if ($result) {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_OK,
                'message' => 'Mark all as read successful',
                'success' => true
            ], REST_INSTANCE_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Mark all as read failed',
                'success' => false
            ], REST_INSTANCE_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function test_send_post()
    {
        $this->load->model('Ql_nguoi_dung_model');
        $this->load->helper('socket');

        // 1. Tìm User ID theo Email
        $emails = ['tvkhanh@nctu.edu.vn', 'nvtoan@nctu.edu.vn'];

        // Fix: Sử dụng Query Builder chuẩn của CI thay vì gọi qua Model wrapper chưa hỗ trợ
        $this->db->select('ql_nguoi_dung_id');
        $this->db->where_in('ql_nguoi_dung_email', $emails);
        $users = $this->db->get('ql_nguoi_dung')->result_array();

        if (empty($users)) {
            return $this->response([
                'success' => false,
                'message' => 'Không tìm thấy user nào với email đã cho'
            ], 404);
        }

        $user_ids = array_column($users, 'ql_nguoi_dung_id');

        // 2. Tạo bản ghi Thông báo gốc
        $noti_data = [
            'ql_thong_bao_tieu_de' => 'Test Socket Notification ' . date('H:i:s'),
            'ql_thong_bao_noi_dung' => 'Đây là thông báo test realtime từ nút bấm trang chủ.',
            'ql_thong_bao_loai' => 1, // 1: Thông báo thường
            'ql_thong_bao_ngay_gui' => date('Y-m-d H:i:s'),
            'ql_thong_bao_da_gui' => 1
        ];

        // Insert vào bảng ql_thong_bao
        $this->Ql_thong_bao_model->insert($noti_data);
        $new_noti_id = $this->db->insert_id();

        // 3. Link thông báo cho từng User
        $link_data = [];
        foreach ($user_ids as $uid) {
            $link_data[] = [
                'ql_thong_bao_id' => $new_noti_id,
                'ql_nguoi_dung_id' => $uid,
                'ql_thong_bao_da_doc' => 0, // Chưa đọc
                'ql_thong_bao_sao' => 0
            ];
        }

        if (!empty($link_data)) {
            $this->db->insert_batch('ql_thong_bao_nguoi_dung', $link_data);
        }

        $socket_sent = send_socket_notify($user_ids, 'notification', [
            'id' => $new_noti_id,
            'title' => $noti_data['ql_thong_bao_tieu_de'],
            'content' => $noti_data['ql_thong_bao_noi_dung'],
            'created_at' => $noti_data['ql_thong_bao_ngay_gui']
        ]);

        // Gửi cập nhật số lượng thông báo chưa đọc cho từng User
        foreach ($user_ids as $uid) {
            $newCount = $this->Ql_thong_bao_model
                ->join('ql_thong_bao_nguoi_dung', 'ql_thong_bao_nguoi_dung.ql_thong_bao_id = ql_thong_bao.ql_thong_bao_id')
                ->where('ql_thong_bao_nguoi_dung.ql_nguoi_dung_id', $uid)
                ->where('ql_thong_bao.ql_thong_bao_da_gui', 1)
                ->where('ql_thong_bao_nguoi_dung.ql_thong_bao_da_doc !=', 1)
                ->count();
            send_socket_unread_count($uid, $newCount);
        }

        $this->response([
            'success' => true,
            'message' => 'Đã gửi thông báo test thành công',
            'data' => [
                'noti_id' => $new_noti_id,
                'receivers' => $emails,
                'socket_sent' => $socket_sent
            ]
        ], REST_INSTANCE_Controller::HTTP_OK);
    }
}
