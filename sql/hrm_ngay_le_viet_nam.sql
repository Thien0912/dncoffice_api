-- Bảng quản lý ngày lễ Việt Nam
CREATE TABLE IF NOT EXISTS `hrm_ngay_le_viet_nam` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ngay_le` date NOT NULL COMMENT 'Ngày lễ (Y-m-d)',
  `ten_ngay_le` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên ngày lễ',
  `mo_ta` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mô tả thêm',
  `la_nghi_buoi` tinyint(1) DEFAULT 0 COMMENT '0: Nghỉ cả ngày, 1: Nghỉ nửa ngày',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '1: Đang áp dụng, 0: Không áp dụng',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_ngay_le` (`ngay_le`),
  KEY `idx_year` (YEAR(`ngay_le`)),
  KEY `idx_active` (`is_active`),
  KEY `idx_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh sách ngày lễ Việt Nam';

-- Insert ngày lễ năm 2026
INSERT INTO `hrm_ngay_le_viet_nam` (`ngay_le`, `ten_ngay_le`, `mo_ta`, `la_nghi_buoi`, `is_active`) VALUES
('2026-01-01', 'Tết Dương lịch', 'Năm mới', 0, 1),
('2026-01-29', 'Tết Nguyên Đán (29/12 ÂL)', 'Nghỉ Tết Nguyên Đán', 0, 1),
('2026-01-30', 'Tết Nguyên Đán (30/12 ÂL)', 'Nghỉ Tết Nguyên Đán', 0, 1),
('2026-01-31', 'Mồng 1 Tết (01/01 ÂL)', 'Tết Nguyên Đán', 0, 1),
('2026-02-01', 'Mồng 2 Tết (02/01 ÂL)', 'Tết Nguyên Đán', 0, 1),
('2026-02-02', 'Mồng 3 Tết (03/01 ÂL)', 'Tết Nguyên Đán', 0, 1),
('2026-02-03', 'Mồng 4 Tết (04/01 ÂL)', 'Nghỉ bù Tết', 0, 1),
('2026-02-04', 'Mồng 5 Tết (05/01 ÂL)', 'Nghỉ bù Tết', 0, 1),
('2026-04-02', 'Giỗ Tổ Hùng Vương (10/03 ÂL)', 'Giỗ Tổ Hùng Vương', 0, 1),
('2026-04-30', 'Ngày Giải phóng Miền Nam', '30/4', 0, 1),
('2026-05-01', 'Ngày Quốc tế Lao động', '1/5', 0, 1),
('2026-05-04', 'Nghỉ bù 30/4 và 1/5', 'Nghỉ bù cuối tuần', 0, 1),
('2026-09-02', 'Quốc khánh Việt Nam', '2/9', 0, 1);

-- Insert ngày lễ năm 2027 (dự kiến)
INSERT INTO `hrm_ngay_le_viet_nam` (`ngay_le`, `ten_ngay_le`, `mo_ta`, `la_nghi_buoi`, `is_active`) VALUES
('2027-01-01', 'Tết Dương lịch', 'Năm mới', 0, 1),
('2027-02-05', 'Tết Nguyên Đán (28/12 ÂL)', 'Nghỉ Tết Nguyên Đán', 0, 1),
('2027-02-06', 'Tết Nguyên Đán (29/12 ÂL)', 'Nghỉ Tết Nguyên Đán', 0, 1),
('2027-02-07', 'Tết Nguyên Đán (30/12 ÂL)', 'Nghỉ Tết Nguyên Đán', 0, 1),
('2027-02-08', 'Mồng 1 Tết (01/01 ÂL)', 'Tết Nguyên Đán', 0, 1),
('2027-02-09', 'Mồng 2 Tết (02/01 ÂL)', 'Tết Nguyên Đán', 0, 1),
('2027-02-10', 'Mồng 3 Tết (03/01 ÂL)', 'Tết Nguyên Đán', 0, 1),
('2027-02-11', 'Mồng 4 Tết (04/01 ÂL)', 'Nghỉ bù Tết', 0, 1),
('2027-04-21', 'Giỗ Tổ Hùng Vương (10/03 ÂL)', 'Giỗ Tổ Hùng Vương', 0, 1),
('2027-04-30', 'Ngày Giải phóng Miền Nam', '30/4', 0, 1),
('2027-05-03', 'Ngày Quốc tế Lao động', 'Nghỉ bù 1/5', 0, 1),
('2027-09-02', 'Quốc khánh Việt Nam', '2/9', 0, 1),
('2027-09-03', 'Nghỉ bù Quốc khánh', 'Nghỉ bù 2/9', 0, 1);
