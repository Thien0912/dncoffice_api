-- Thêm file_name, file_extension, file_size vào hrm_nhan_vien_bang_cap
-- để lưu metadata file đính kèm (dùng cho preview và link sang hrm_minh_chung)

ALTER TABLE `hrm_nhan_vien_bang_cap`
  ADD COLUMN `file_name`      VARCHAR(255) NULL DEFAULT NULL COMMENT 'Tên file gốc user upload' AFTER `file_path`,
  ADD COLUMN `file_extension` VARCHAR(20)  NULL DEFAULT NULL COMMENT 'Đuôi file: jpg, png, pdf...' AFTER `file_name`,
  ADD COLUMN `file_size`      INT          NULL DEFAULT NULL COMMENT 'Dung lượng (bytes)' AFTER `file_extension`;
