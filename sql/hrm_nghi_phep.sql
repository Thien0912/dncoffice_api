DROP TABLE IF EXISTS hrm_nghi_phep;

CREATE TABLE hrm_nghi_phep (
    id_nghi_phep INT AUTO_INCREMENT PRIMARY KEY,

    id_nhan_vien INT NOT NULL COMMENT 'Nhân viên đăng ký nghỉ',
    id_loai_phep INT NOT NULL COMMENT 'Loại nghỉ phép',

    loai_nghi ENUM('Binh_thuong','Dot_xuat')
        DEFAULT 'Binh_thuong'
        COMMENT 'Hình thức nghỉ',

    trang_thai_cap_mot ENUM('Cho_duyet','Da_duyet','Tu_choi')
        DEFAULT 'Cho_duyet'
        COMMENT 'Trạng thái duyệt cấp 1',
    nguoi_duyet_cap_mot_id INT NULL COMMENT 'Người duyệt cấp 1',

    trang_thai_cap_hai ENUM('Cho_duyet','Da_duyet','Tu_choi')
        DEFAULT 'Cho_duyet'
        COMMENT 'Trạng thái duyệt cấp 2',
    nguoi_duyet_cap_hai_id INT NULL COMMENT 'Người duyệt cấp 2',

    ly_do_nghi TEXT NULL COMMENT 'Lý do nghỉ',
    minh_chung VARCHAR(255) NULL COMMENT 'Minh chứng đính kèm',

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    created_user_id INT NULL,

    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    updated_user_id INT NULL,

    deleted_at TIMESTAMP NULL,
    deleted_user_id INT NULL

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;

ALTER TABLE hrm_nghi_phep 
ADD COLUMN uuid_nghi_phep CHAR(36) NULL AFTER id_nghi_phep;
