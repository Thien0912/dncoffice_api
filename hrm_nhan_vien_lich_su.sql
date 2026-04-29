CREATE TABLE IF NOT EXISTS `hrm_nhan_vien_lich_su` (
  `id_lich_su` int NOT NULL AUTO_INCREMENT,
  `id_nhan_vien` int NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `changes` json DEFAULT NULL,
  `created_user_id` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_lich_su`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
