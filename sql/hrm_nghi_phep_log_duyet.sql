-- Bảng lưu log thay đổi trạng thái duyệt nghỉ phép
CREATE TABLE `hrm_nghi_phep_log_duyet` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `id_nghi_phep` int(11) NOT NULL COMMENT 'ID đơn nghỉ phép',
  `cap_duyet` tinyint(1) NOT NULL COMMENT 'Cấp duyệt: 1=Lãnh đạo đơn vị, 2=TC-HC',
  `trang_thai_cu` enum('Cho_duyet','Da_duyet','Tu_choi') DEFAULT NULL COMMENT 'Trạng thái trước khi thay đổi',
  `trang_thai_moi` enum('Cho_duyet','Da_duyet','Tu_choi') NOT NULL COMMENT 'Trạng thái sau khi thay đổi',
  `hanh_dong` enum('duyet','tu_choi','sua_duyet') NOT NULL COMMENT 'Hành động: duyet, tu_choi, sua_duyet',
  `id_nguoi_duyet` int(11) NOT NULL COMMENT 'ID người thực hiện duyệt',
  `ly_do` text COMMENT 'Lý do duyệt/từ chối/sửa',
  `thoi_gian_thay_doi` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian thay đổi',
  PRIMARY KEY (`id_log`),
  KEY `idx_id_nghi_phep` (`id_nghi_phep`),
  KEY `idx_id_nguoi_duyet` (`id_nguoi_duyet`),
  KEY `idx_thoi_gian_thay_doi` (`thoi_gian_thay_doi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log lịch sử thay đổi trạng thái duyệt nghỉ phép';
