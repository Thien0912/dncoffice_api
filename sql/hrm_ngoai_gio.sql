CREATE TABLE `hrm_ngoai_gio` (
  `id_ngoai_gio` int(11) NOT NULL AUTO_INCREMENT,
  `id_nhan_vien` int(11) NOT NULL,
  `ngay_dang_ky` date NOT NULL,
  `gio_bat_dau` time NOT NULL,
  `gio_ket_thuc` time NOT NULL,
  `noi_dung` text DEFAULT NULL,
  `so_gio` float DEFAULT NULL,
  
  `trang_thai_tong` varchar(50) DEFAULT 'Cho_duyet' COMMENT 'Cho_duyet, Da_duyet, Tu_choi',
  `cap_duyet_hien_tai` int(11) DEFAULT 1 COMMENT 'Cấp duyệt đang chờ xử lý (1, 2, ...)',
  `tong_so_cap_duyet` int(11) DEFAULT 2 COMMENT 'Tổng số cấp cần duyệt để hoàn thành',
  
  `created_user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `deleted_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_ngoai_gio`),
  KEY `id_nhan_vien` (`id_nhan_vien`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `hrm_ngoai_gio_nguoi_duyet` (
  `id_ngoai_gio_nguoi_duyet` int(11) NOT NULL AUTO_INCREMENT,
  `id_ngoai_gio` int(11) NOT NULL,
  `cap_duyet` int(11) NOT NULL COMMENT '1, 2, 3...',
  `id_nguoi_duyet` int(11) NOT NULL COMMENT 'ID của người có quyền duyệt',
  
  `trang_thai` varchar(50) DEFAULT 'Cho_duyet' COMMENT 'Cho_duyet, Da_duyet, Tu_choi',
  `thoi_gian_duyet` datetime DEFAULT NULL,
  `ly_do_duyet` text DEFAULT NULL,
  
  `duyet_ho` tinyint(2) DEFAULT 0 COMMENT '1: có người duyệt hộ',
  `id_duyet_ho` int(11) DEFAULT NULL,
  
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_ngoai_gio_nguoi_duyet`),
  KEY `id_ngoai_gio` (`id_ngoai_gio`),
  KEY `id_nguoi_duyet` (`id_nguoi_duyet`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================================
-- DỮ LIỆU MẪU ĐỂ TEST 
-- (Lưu ý: Bạn có thể cập nhật lại id_nhan_vien và id_nguoi_duyet theo đúng dữ liệu thực tế trong DB)
-- =========================================================

INSERT INTO `hrm_ngoai_gio` (`id_ngoai_gio`, `id_nhan_vien`, `ngay_dang_ky`, `gio_bat_dau`, `gio_ket_thuc`, `noi_dung`, `so_gio`, `trang_thai_tong`, `cap_duyet_hien_tai`, `tong_so_cap_duyet`, `created_user_id`) VALUES
-- 1. Đơn đang chờ duyệt cấp 1
(1, 1, '2026-03-24', '17:30:00', '21:30:00', 'Hỗ trợ sự kiện', 4.0, 'Cho_duyet', 1, 2, 1),
-- 2. Đơn đã duyệt cấp 1, đang chờ duyệt cấp 2
(2, 2, '2026-03-25', '18:00:00', '20:00:00', 'Bảo trì hệ thống server', 2.0, 'Cho_duyet', 2, 2, 2),
-- 3. Đơn đã duyệt thành công cả 2 cấp
(3, 3, '2026-03-26', '17:00:00', '19:00:00', 'Phỏng vấn ứng viên', 2.0, 'Da_duyet', 3, 2, 3),
-- 4. Đơn bị từ chối ở cấp 1
(4, 4, '2026-03-27', '18:00:00', '21:00:00', 'Làm thêm không rõ lý do', 3.0, 'Tu_choi', 1, 2, 4);


INSERT INTO `hrm_ngoai_gio_nguoi_duyet` (`id_ngoai_gio`, `cap_duyet`, `id_nguoi_duyet`, `trang_thai`, `thoi_gian_duyet`, `ly_do_duyet`) VALUES
-- Đơn 1 đang chờ duyệt cấp 1 (Giả sử id người quản lý = 10, id TCHC = 15)
(1, 1, 10, 'Cho_duyet', NULL, NULL),
(1, 2, 15, 'Cho_duyet', NULL, NULL),

-- Đơn 2 đã duyệt cấp 1, đang chờ duyệt cấp 2 (TCHC 15)
(2, 1, 11, 'Da_duyet', NOW(), 'Đồng ý duyệt cấp 1'),
(2, 2, 15, 'Cho_duyet', NULL, NULL),

-- Đơn 3 đã duyệt xong 2 cấp
(3, 1, 12, 'Da_duyet', NOW(), 'Lãnh đạo đơn vị duyệt'),
(3, 2, 15, 'Da_duyet', NOW(), 'Phòng TCHC đồng ý'),

-- Đơn 4 bị từ chối ở cấp 1
(4, 1, 10, 'Tu_choi', NOW(), 'Chưa cần thiết làm ngoài giờ hôm nay'),
(4, 2, 15, 'Cho_duyet', NULL, NULL);
