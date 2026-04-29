DROP TABLE IF EXISTS e_lanh_dao_don_vi;
CREATE TABLE e_lanh_dao_don_vi (
    id_lanh_dao_don_vi INT AUTO_INCREMENT PRIMARY KEY,

    ql_nguoi_dung_id INT NOT NULL
        COMMENT 'ID người dùng (lãnh đạo)',

    id_don_vi INT NOT NULL
        COMMENT 'ID đơn vị',

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Một người có thể làm lãnh đạo nhiều đơn vị
    -- Một đơn vị có thể có nhiều lãnh đạo
    -- Nhưng 1 cặp người + đơn vị chỉ được xuất hiện 1 lần
    UNIQUE KEY uq_nguoi_don_vi (
        ql_nguoi_dung_id,
        id_don_vi
    )

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;
