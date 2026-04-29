DROP TABLE IF EXISTS ql_nguoi_dung_pin_code;

CREATE TABLE `ql_nguoi_dung_pin_code` (
  `id_ql_nguoi_dung_pin_code` int NOT NULL AUTO_INCREMENT,
  `ql_nguoi_dung_id` int NOT NULL,
  `pin_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Đã mã hóa',
  `is_valid` tinyint(1) NOT NULL DEFAULT '1',
  `send_mail_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_user_id` int NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_user_id` int DEFAULT NULL,
  PRIMARY KEY (`id_ql_nguoi_dung_pin_code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;