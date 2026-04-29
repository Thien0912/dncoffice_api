-- Bảng lưu trữ OTP xác thực chung cho người dùng
CREATE TABLE IF NOT EXISTS `ql_nguoi_dung_otp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ql_nguoi_dung_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `otp_code` varchar(255) NOT NULL COMMENT 'Mã OTP đã mã hóa',
  `failed_attempts` int(11) DEFAULT 0 COMMENT 'Số lần nhập sai',
  `is_verified` tinyint(1) DEFAULT 0 COMMENT '0: Chưa xác thực, 1: Đã xác thực',
  `expired_at` datetime NOT NULL COMMENT 'Thời gian hết hạn OTP',
  `send_mail_at` datetime DEFAULT NULL COMMENT 'Thời gian gửi email',
  `verified_at` datetime DEFAULT NULL COMMENT 'Thời gian xác thực thành công',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_user_id` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_nguoi_dung` (`ql_nguoi_dung_id`),
  KEY `idx_verified` (`is_verified`),
  KEY `idx_expired` (`expired_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng quản lý OTP xác thực chung';
