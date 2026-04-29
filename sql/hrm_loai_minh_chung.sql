-- Bảng danh mục loại minh chứng nhân viên
CREATE TABLE `hrm_loai_minh_chung` (
  `id_loai_minh_chung` int NOT NULL AUTO_INCREMENT,
  `ma_loai` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mã loại: CCCD, BANG_TN, CHUNG_CHI...',
  `ten_loai` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Tên hiển thị',
  `thu_tu` int NOT NULL DEFAULT '0' COMMENT 'Thứ tự hiển thị trên UI',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_loai_minh_chung`),
  UNIQUE KEY `uk_ma_loai` (`ma_loai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh mục loại minh chứng nhân viên';

-- Seed data
INSERT INTO `hrm_loai_minh_chung` (`ma_loai`, `ten_loai`, `thu_tu`) VALUES
('CCCD', 'CCCD/Hộ chiếu', 1),
('BANG_TN', 'Bằng tốt nghiệp', 2),
('CHUNG_CHI', 'Chứng chỉ', 3),
('HOC_BA', 'Học bạ (10, 11, 12)', 4),
('KET_QUA_HOC_LUC', 'Kết quả học lực (10, 11, 12)', 5);
