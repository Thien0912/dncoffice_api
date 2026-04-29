-- Thêm cột nháp và loại đề xuất vào bảng dx_de_xuat
ALTER TABLE `dx_de_xuat` 
ADD COLUMN `nhap` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0: Đã gửi, 1: Lưu nháp' AFTER `noi_dung`,
ADD COLUMN `id_dx_loai_de_xuat` INT(11) NULL COMMENT 'ID loại đề xuất' AFTER `nhap`;

-- Tạo index cho cột nhap và id_dx_loai_de_xuat để tối ưu query
ALTER TABLE `dx_de_xuat` 
ADD INDEX `idx_nhap` (`nhap`),
ADD INDEX `idx_id_dx_loai_de_xuat` (`id_dx_loai_de_xuat`);

-- Tạo foreign key constraint
ALTER TABLE `dx_de_xuat`
ADD CONSTRAINT `fk_dx_de_xuat_loai` 
FOREIGN KEY (`id_dx_loai_de_xuat`) 
REFERENCES `dx_loai_de_xuat` (`id_dx_loai_de_xuat`) 
ON DELETE SET NULL 
ON UPDATE CASCADE;
