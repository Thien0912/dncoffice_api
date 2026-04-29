DROP TABLE IF EXISTS dx_binh_luan_log;

CREATE TABLE `dx_binh_luan_log` (
  `id_binh_luan_log` int NOT NULL AUTO_INCREMENT,
  `id_binh_luan` int NOT NULL,
  `noi_dung_hien_tai` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `noi_dung_cu` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_user_id` int NOT NULL,
  PRIMARY KEY (`id_binh_luan_log`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;