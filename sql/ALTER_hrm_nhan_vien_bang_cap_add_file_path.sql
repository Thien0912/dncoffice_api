-- Thêm cột file_path vào bảng hrm_nhan_vien_bang_cap
-- Cho phép đính kèm file bằng cấp (ảnh hoặc PDF), không bắt buộc

ALTER TABLE `hrm_nhan_vien_bang_cap`
ADD COLUMN `file_path` VARCHAR(500) NULL DEFAULT NULL COMMENT 'Đường dẫn file đính kèm bằng cấp (ảnh/PDF), có thể null' AFTER `xep_loai_dt`;
