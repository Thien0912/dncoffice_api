DROP TABLE IF EXISTS dx_loai_de_xuat_don_vi;

CREATE TABLE `dx_loai_de_xuat_don_vi` (
  `id_dx_loai_de_xuat_don_vi` int NOT NULL AUTO_INCREMENT,
  `id_dx_loai_de_xuat` int NOT NULL COMMENT 'ID loại đề xuất',
  `id_don_vi` int DEFAULT NULL COMMENT 'ID đơn vị',
  `thu_tu_trinh_ky` int NOT NULL DEFAULT '1' COMMENT 'Thứ tự trình ký của đơn vị (1, 2, 3...)',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_user_id` int NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_user_id` int DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_user_id` int DEFAULT NULL,
  PRIMARY KEY (`id_dx_loai_de_xuat_don_vi`),
  UNIQUE KEY `uk_loai_don_vi` (`id_dx_loai_de_xuat`,`id_don_vi`,`thu_tu_trinh_ky`) USING BTREE,
  KEY `idx_id_dx_loai_de_xuat` (`id_dx_loai_de_xuat`),
  KEY `idx_id_don_vi` (`id_don_vi`),
  KEY `idx_thu_tu_trinh_ky` (`thu_tu_trinh_ky`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Liên kết loại đề xuất với đơn vị và thứ tự trình ký';