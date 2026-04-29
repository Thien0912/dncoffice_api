DROP TABLE IF EXISTS hrm_nghi_phep_nguoi_duyet;

CREATE TABLE hrm_nghi_phep_nguoi_duyet (
    id_nghi_phep_nguoi_duyet INT AUTO_INCREMENT PRIMARY KEY,

    id_nghi_phep INT NOT NULL
        COMMENT 'ID đơn nghỉ phép',

    cap_duyet TINYINT NOT NULL
        COMMENT '1 = cấp 1, 2 = cấp 2',

    id_nguoi_duyet INT NOT NULL
        COMMENT 'Người có quyền duyệt',

    da_duyet TINYINT(1) DEFAULT 0
        COMMENT '0 = chưa duyệt, 1 = đã duyệt',

    ly_do VARCHAR(255) DEFAULT NULL
        COMMENT 'Lý do duyệt / từ chối',

    thoi_gian_duyet TIMESTAMP NULL
        COMMENT 'Thời điểm duyệt',

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    -- 1 người chỉ xuất hiện 1 lần / 1 cấp / 1 đơn
    UNIQUE KEY uq_nghi_phep_cap_nguoi (
        id_nghi_phep,
        cap_duyet,
        id_nguoi_duyet
    )

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;
