DROP TABLE IF EXISTS dx_binh_luan;

CREATE TABLE `dx_binh_luan` (
  `id_binh_luan` int NOT NULL AUTO_INCREMENT,
  `id_de_xuat` int NOT NULL,
  `parent_id` int DEFAULT NULL COMMENT 'ID bình luận cha (NULL nếu là bình luận gốc)',
  `noi_dung` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_user_id` int NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_user_id` int DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_user_id` int DEFAULT NULL,
  PRIMARY KEY (`id_binh_luan`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_id_de_xuat` (`id_de_xuat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;