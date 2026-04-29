DROP TABLE IF EXISTS hrm_danh_muc_loai_nghi_phep;

CREATE TABLE hrm_danh_muc_loai_nghi_phep (
    id_loai_phep INT AUTO_INCREMENT PRIMARY KEY,

    ma_loai_phep VARCHAR(50) NOT NULL
        COMMENT 'Mã loại nghỉ phép',

    ten_loai_phep VARCHAR(255) NOT NULL
        COMMENT 'Tên loại nghỉ phép',

    ghi_chu TEXT NULL
        COMMENT 'Ghi chú / mô tả',

    so_ngay_mac_dinh DECIMAL(5,2) NULL
        COMMENT 'Số ngày nghỉ mặc định',

    co_tinh_luong TINYINT(1) DEFAULT 1
        COMMENT '1 = có tính lương, 0 = không tính lương',

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    UNIQUE KEY uq_ma_loai_phep (ma_loai_phep)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;
