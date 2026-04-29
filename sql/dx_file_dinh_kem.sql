DROP TABLE IF EXISTS dx_file_dinh_kem

CREATE TABLE `dx_file_dinh_kem` (
  `id_file_dinh_kem` int NOT NULL AUTO_INCREMENT,
  `id_de_xuat` int NOT NULL,
  `ten_file_goc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `dung_luong` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `duong_dan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `loai_file` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_user_id` int NOT NULL,
  PRIMARY KEY (`id_file_dinh_kem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;