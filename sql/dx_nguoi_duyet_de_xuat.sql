DROP TABLE IF EXISTS dx_nguoi_duyet_de_xuat

CREATE TABLE `dx_nguoi_duyet_de_xuat` (
  `id_nguoi_duyet_de_xuat` int NOT NULL AUTO_INCREMENT,
  `id_de_xuat` int NOT NULL,
  `id_don_vi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_nguoi_duyet` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cap_duyet` int NOT NULL,
  `da_duyet` tinyint(1) DEFAULT NULL COMMENT '1: Đồng ý, 0: Từ chối, NULL: Chưa duyệt',
  `ly_do` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ngay_duyet` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_user_id` int NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_user_id` int DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_user_id` int DEFAULT NULL,
  PRIMARY KEY (`id_nguoi_duyet_de_xuat`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;