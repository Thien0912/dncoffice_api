-- ========================================================
-- MIGRATION: Tính năng Phúc Khảo (Re-review leave request)
-- Ngày tạo: 2026-03-28
-- Mô tả: Thêm cột theo dõi phúc khảo và mở rộng ENUM log
-- ========================================================

-- Bước 1: Thêm cột theo dõi phúc khảo vào bảng đơn nghỉ phép
--   so_lan_phuc_khao : Số lần đã phúc khảo (max = 1/đơn)
--   thoi_gian_phuc_khao : Thời điểm bấm phúc khảo gần nhất
ALTER TABLE `hrm_nghi_phep`
    ADD COLUMN `so_lan_phuc_khao`    TINYINT(1) DEFAULT 0    COMMENT 'Số lần đã phúc khảo (tối đa 1 lần/đơn)',
    ADD COLUMN `thoi_gian_phuc_khao` DATETIME   DEFAULT NULL COMMENT 'Thời điểm bấm phúc khảo';

-- Bước 2: Mở rộng ENUM hành động trong bảng Log để ghi nhận thao tác 'phuc_khao'
ALTER TABLE `hrm_nghi_phep_log_duyet`
    MODIFY COLUMN `hanh_dong`
        ENUM('duyet','tu_choi','sua_duyet','phuc_khao')
        CHARACTER SET utf8mb4
        COLLATE utf8mb4_unicode_ci
        NOT NULL;
