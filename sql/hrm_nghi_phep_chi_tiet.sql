DROP TABLE IF EXISTS hrm_nghi_phep_chi_tiet;

CREATE TABLE hrm_nghi_phep_chi_tiet (
    id_nghi_phep_chi_tiet INT AUTO_INCREMENT PRIMARY KEY,

    id_nghi_phep INT NOT NULL
        COMMENT 'ID đơn nghỉ phép',

    ngay_nghi DATE NOT NULL
        COMMENT 'Ngày nghỉ',

    buoi_nghi ENUM('Sang','Chieu') NOT NULL
        COMMENT 'Buổi nghỉ: Sáng hoặc Chiều',

    so_ngay_nghi DECIMAL(3,1) NOT NULL DEFAULT 0.5
        COMMENT 'Số ngày nghỉ (mỗi buổi = 0.5)',

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    -- Không cho trùng 1 buổi trong 1 ngày của cùng 1 đơn
    UNIQUE KEY uq_nghi_phep_ngay_buoi (
        id_nghi_phep,
        ngay_nghi,
        buoi_nghi
    )

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;
