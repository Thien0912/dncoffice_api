DROP TABLE IF EXISTS dx_loai_de_xuat;

CREATE TABLE `dx_loai_de_xuat` (
  `id_dx_loai_de_xuat` int NOT NULL AUTO_INCREMENT,
  `ma_loai` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mã loại đề xuất',
  `ten_loai` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Tên loại đề xuất',
  `mo_ta` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Mô tả loại đề xuất',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_user_id` int NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_user_id` int DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_user_id` int DEFAULT NULL,
  PRIMARY KEY (`id_dx_loai_de_xuat`),
  UNIQUE KEY `uk_ma_loai` (`ma_loai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh mục loại đề xuất';
