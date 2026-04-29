-- Thêm cột để lưu token chưa mã hóa vào bảng ql_nguoi_dung
-- Token này dùng để theo dõi token hiện tại của user (chưa hash)
-- Chạy script này để cập nhật bảng ql_nguoi_dung

ALTER TABLE `ql_nguoi_dung` 
ADD COLUMN `ql_nguoi_dung_token` TEXT NULL COMMENT 'Token đăng nhập hiện tại (chưa mã hóa)' AFTER `lan_dang_nhap_cuoi`;

-- Ghi chú: 
-- - Token này sẽ được cập nhật mỗi lần user đăng nhập
-- - Dùng để so sánh hoặc thu hồi token khi cần
