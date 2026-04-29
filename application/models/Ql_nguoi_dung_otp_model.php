<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Ql_nguoi_dung_otp_model extends MY_Model
{
    protected $table = 'ql_nguoi_dung_otp';
    protected $primaryKey = 'id';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Tạo OTP mới cho user
     */
    public function createOTP($qlNguoiDungId, $expireMinutes = 5)
    {
        // Vô hiệu hóa các OTP cũ chưa xác thực
        $this->db->where('ql_nguoi_dung_id', $qlNguoiDungId)
            ->where('is_verified', 0)
            ->where('deleted_at IS NULL')
            ->update($this->table, [
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_user_id' => $qlNguoiDungId
            ]);

        // Tạo mã OTP 6 số
        $otpCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Tạo bản ghi OTP mới
        $data = [
            'ql_nguoi_dung_id' => $qlNguoiDungId,
            'otp_code' => encryptString($otpCode),
            'failed_attempts' => 0,
            'is_verified' => 0,
            'expired_at' => date('Y-m-d H:i:s', strtotime("+{$expireMinutes} minutes")),
            'created_at' => date('Y-m-d H:i:s'),
            'created_user_id' => $qlNguoiDungId
        ];

        $this->db->insert($this->table, $data);
        $data['id'] = $this->db->insert_id();
        $data['otp_code_plain'] = $otpCode; // Trả về mã gốc để gửi email

        return $data;
    }

    /**
     * Lấy OTP hiện tại đang hoạt động
     */
    public function getActiveOTP($qlNguoiDungId)
    {
        return $this->db
            ->where('ql_nguoi_dung_id', $qlNguoiDungId)
            ->where('is_verified', 0)
            ->where('deleted_at IS NULL')
            ->where('expired_at >', date('Y-m-d H:i:s'))
            ->order_by('created_at', 'DESC')
            ->limit(1)
            ->get($this->table)
            ->row_array();
    }

    /**
     * Xác thực OTP
     */
    public function verifyOTP($qlNguoiDungId, $otpCode)
    {
        $otp = $this->getActiveOTP($qlNguoiDungId);

        if (!$otp) {
            return [
                'success' => false,
                'message' => 'Mã OTP không tồn tại hoặc đã hết hạn',
                'code' => 'OTP_NOT_FOUND'
            ];
        }

        // Kiểm tra mã OTP
        if (decryptString($otp['otp_code']) !== $otpCode) {
            // Tăng số lần nhập sai
            $failedAttempts = $otp['failed_attempts'] + 1;
            $updateData = [
                'failed_attempts' => $failedAttempts
            ];

            // Nếu nhập sai >= 5 lần thì vô hiệu hóa
            if ($failedAttempts >= 5) {
                $updateData['deleted_at'] = date('Y-m-d H:i:s');
                $updateData['deleted_user_id'] = $qlNguoiDungId;
            }

            $this->db->where('id', $otp['id'])->update($this->table, $updateData);

            return [
                'success' => false,
                'message' => 'Mã OTP không đúng',
                'code' => 'OTP_INCORRECT',
                'failed_attempts' => $failedAttempts,
                'remaining_attempts' => max(0, 5 - $failedAttempts)
            ];
        }

        // OTP đúng - cập nhật trạng thái
        $this->db->where('id', $otp['id'])->update($this->table, [
            'is_verified' => 1,
            'verified_at' => date('Y-m-d H:i:s')
        ]);

        return [
            'success' => true,
            'message' => 'Xác thực OTP thành công',
            'code' => 'OTP_VERIFIED'
        ];
    }
}
