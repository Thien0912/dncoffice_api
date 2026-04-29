-- Thêm cột deleted_at vào bảng hrm_ngoai_gio_nguoi_duyet để hỗ trợ soft delete
ALTER TABLE `hrm_ngoai_gio_nguoi_duyet` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL;
