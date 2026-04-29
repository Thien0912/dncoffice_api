-- Bổ sung cột lưu vết người xác nhận hoàn thành (TCHC) cho bảng de_xuat
-- Ngày: 2026-02-25

ALTER TABLE dx_de_xuat 
ADD COLUMN id_user_confirm_completed INT(11) NULL COMMENT 'ID người TCHC xác nhận hoàn thành' AFTER trang_thai,
ADD COLUMN time_confirm_completed DATETIME NULL COMMENT 'Thời gian TCHC xác nhận hoàn thành' AFTER id_user_confirm_completed;
