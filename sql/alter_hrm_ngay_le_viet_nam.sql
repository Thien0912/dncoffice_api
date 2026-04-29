-- Cập nhật thêm cột batdau, ketthuc, mota vào bảng hrm_ngay_le_viet_nam
ALTER TABLE `hrm_ngay_le_viet_nam`
ADD COLUMN `batdau` date DEFAULT NULL COMMENT 'Ngày bắt đầu nghỉ' AFTER `ngay`,
ADD COLUMN `ketthuc` date DEFAULT NULL COMMENT 'Ngày kết thúc nghỉ' AFTER `batdau`,
ADD COLUMN `mota` text DEFAULT NULL COMMENT 'Mô tả chi tiết' AFTER `ketthuc`,
ADD COLUMN `deleted_at` datetime DEFAULT NULL COMMENT 'Thời gian xóa tạm' AFTER `mota`;

-- Nếu muốn update dữ liệu batdau, ketthuc dựa trên ngay hiện tại
UPDATE `hrm_ngay_le_viet_nam`
SET `batdau` = `ngay`, `ketthuc` = `ngay`
WHERE `batdau` IS NULL AND `ketthuc` IS NULL;
