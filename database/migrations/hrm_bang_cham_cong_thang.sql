-- ========================================================
-- MIGRATION: Quản lý bảng chấm công tháng cho ngoài giờ
-- Ngày tạo: 2026-03-31
-- Mô tả: Cho phép TCHC tạo bảng chấm công theo kỳ, nhân viên đăng ký ngoài giờ trước 1 tuần
-- ========================================================

-- Bước 1: Tạo bảng chấm công tháng
CREATE TABLE IF NOT EXISTS `hrm_bang_cham_cong_thang` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `thang` VARCHAR(125) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Tháng (MM/YYYY)',
  `ngay_bat_dau` DATE DEFAULT NULL COMMENT 'Ngày bắt đầu kỳ chấm công',
  `ngay_ket_thuc` DATE DEFAULT NULL COMMENT 'Ngày kết thúc kỳ chấm công',
  `ten_bang` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Tên bảng chấm công',
  `trang_thai` ENUM('DANG_CHO_DUYET','DA_DUYET','BI_TU_CHOI','KHOA','MO') 
    CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'MO' 
    COMMENT 'Trạng thái: MO=cho phép đăng ký, KHOA=không cho đăng ký',
  `nguoi_duyet` INT DEFAULT NULL COMMENT 'ID người duyệt bảng chấm công',
  `ngay_duyet` DATETIME DEFAULT NULL COMMENT 'Thời gian duyệt',
  `ly_do_tu_choi` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Lý do từ chối',
  `ghi_chu` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Ghi chú',
  `locked_dates` JSON DEFAULT NULL COMMENT 'Lưu trữ các khoảng thời gian bị khóa dạng JSON',
  `created_user_id` INT DEFAULT NULL COMMENT 'Người tạo',
  `updated_user_id` INT DEFAULT NULL COMMENT 'Người cập nhật',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_thang` (`thang`),
  INDEX `idx_ngay_bat_dau` (`ngay_bat_dau`),
  INDEX `idx_ngay_ket_thuc` (`ngay_ket_thuc`),
  INDEX `idx_trang_thai` (`trang_thai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci 
COMMENT='Quản lý bảng chấm công tháng cho đăng ký ngoài giờ';

-- Bước 2: Thêm cột tham chiếu bảng chấm công vào hrm_ngoai_gio
ALTER TABLE `hrm_ngoai_gio`
  ADD COLUMN `id_bang_cham_cong` INT DEFAULT NULL COMMENT 'ID bảng chấm công tháng' AFTER `id_nhan_vien`,
  ADD INDEX `idx_bang_cham_cong` (`id_bang_cham_cong`);

-- Bước 3: Insert dữ liệu mẫu cho tháng hiện tại
INSERT INTO `hrm_bang_cham_cong_thang` 
  (`thang`, `ngay_bat_dau`, `ngay_ket_thuc`, `ten_bang`, `trang_thai`, `created_user_id`) 
VALUES 
  ('03/2026', '2026-03-01', '2026-03-31', 'Bảng chấm công tháng 3/2026', 'MO', 1),
  ('04/2026', '2026-04-01', '2026-04-30', 'Bảng chấm công tháng 4/2026', 'MO', 1);

-- Bước 4: Thêm foreign key constraint (tùy chọn)
-- ALTER TABLE `hrm_ngoai_gio`
--   ADD CONSTRAINT `fk_ngoai_gio_bang_cham_cong` 
--   FOREIGN KEY (`id_bang_cham_cong`) 
--   REFERENCES `hrm_bang_cham_cong_thang` (`id`) 
--   ON DELETE SET NULL 
--   ON UPDATE CASCADE;
