-- Bảng hình ảnh minh chứng nhân viên
CREATE TABLE `hrm_minh_chung` (
  `id_minh_chung` int NOT NULL AUTO_INCREMENT,
  `id_nhan_vien` int NOT NULL COMMENT 'FK → hrm_nhan_vien',
  `id_loai_minh_chung` int NOT NULL COMMENT 'FK → hrm_loai_minh_chung',
  `file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Đường dẫn trên server',
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Tên file gốc user upload',
  `file_extension` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Đuôi file: jpg, png, pdf...',
  `file_size` int DEFAULT NULL COMMENT 'Dung lượng (bytes)',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_user_id` int DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_user_id` int DEFAULT NULL,
  PRIMARY KEY (`id_minh_chung`),
  KEY `idx_nhan_vien` (`id_nhan_vien`),
  KEY `idx_loai` (`id_loai_minh_chung`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Hình ảnh minh chứng nhân viên';
