DROP TABLE IF EXISTS dx_de_xuat_gan_sao;

CREATE TABLE `dx_de_xuat_gan_sao` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_de_xuat` int NOT NULL,
  `user_id` int NOT NULL COMMENT 'ID người dùng gắn sao',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_de_xuat` (`id_de_xuat`, `user_id`),
  KEY `idx_id_de_xuat` (`id_de_xuat`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS dx_de_xuat_quan_trong;

CREATE TABLE `dx_de_xuat_quan_trong` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_de_xuat` int NOT NULL,
  `user_id` int NOT NULL COMMENT 'ID người dùng đánh dấu quan trọng',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_de_xuat` (`id_de_xuat`, `user_id`),
  KEY `idx_id_de_xuat` (`id_de_xuat`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
