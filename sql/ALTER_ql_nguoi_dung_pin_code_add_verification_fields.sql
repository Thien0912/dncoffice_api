-- Thêm các cột cần thiết cho xác thực PIN code bằng token
-- Chạy script này để cập nhật bảng ql_nguoi_dung_pin_code

ALTER TABLE `ql_nguoi_dung_pin_code` 
ADD COLUMN `verification_token` TEXT NULL COMMENT 'Token mã hóa để xác thực qua email' AFTER `pin_code`,
ADD COLUMN `verified_at` DATETIME NULL COMMENT 'Thời gian xác thực thành công' AFTER `send_mail_at`;

-- Thêm index cho việc tìm kiếm nhanh
ALTER TABLE `ql_nguoi_dung_pin_code`
ADD INDEX `idx_verification_token` (`verification_token`(255));

-- Ghi chú: verification_token sẽ được xóa (set NULL) sau khi xác thực thành công để không thể sử dụng lại
