-- Thêm các cột duyệt hộ vào bảng hrm_nghi_phep_nguoi_duyet

ALTER TABLE `hrm_nghi_phep_nguoi_duyet` 
ADD COLUMN `duyet_ho` TINYINT(1) DEFAULT 0 COMMENT '0: Không duyệt hộ, 1: Có duyệt hộ' AFTER `ly_do`,
ADD COLUMN `id_duyet_ho` INT(11) DEFAULT NULL COMMENT 'ID người duyệt hộ (ql_nguoi_dung_id)' AFTER `duyet_ho`,
ADD COLUMN `minh_chung_duyet_ho` VARCHAR(500) DEFAULT NULL COMMENT 'Minh chứng của người duyệt hộ (hình ảnh)' AFTER `id_duyet_ho`;

-- Thêm index cho cột id_duyet_ho
ALTER TABLE `hrm_nghi_phep_nguoi_duyet` 
ADD INDEX `idx_id_duyet_ho` (`id_duyet_ho`);

-- Thêm foreign key (optional)
-- ALTER TABLE `hrm_nghi_phep_nguoi_duyet` 
-- ADD CONSTRAINT `fk_id_duyet_ho` FOREIGN KEY (`id_duyet_ho`) REFERENCES `ql_nguoi_dung`(`ql_nguoi_dung_id`) ON DELETE SET NULL;
