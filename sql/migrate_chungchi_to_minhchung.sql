-- Migration: Sync dữ liệu chứng chỉ đã có vào bảng hrm_minh_chung
-- Chạy 1 lần sau khi tạo bảng hrm_loai_minh_chung và hrm_minh_chung

-- Lấy id_loai_minh_chung của CHUNG_CHI
SET @loai_chung_chi = (SELECT id_loai_minh_chung FROM hrm_loai_minh_chung WHERE ma_loai = 'CHUNG_CHI' LIMIT 1);

-- MySQL 8.0+ (có JSON_TABLE)
INSERT INTO hrm_minh_chung (id_nhan_vien, id_loai_minh_chung, file_path, file_name, file_extension, file_size, created_at)
SELECT 
    cc.id_nhan_vien,
    @loai_chung_chi,
    JSON_UNQUOTE(JSON_EXTRACT(f.file_data, '$.file_path')),
    JSON_UNQUOTE(JSON_EXTRACT(f.file_data, '$.file_name')),
    SUBSTRING_INDEX(JSON_UNQUOTE(JSON_EXTRACT(f.file_data, '$.file_name')), '.', -1),
    NULL,
    NOW()
FROM hrm_nhan_vien_chung_chi cc
CROSS JOIN JSON_TABLE(
    cc.files, 
    '$[*]' COLUMNS (
        file_data JSON PATH '$'
    )
) AS f
WHERE cc.files IS NOT NULL 
  AND cc.files != '' 
  AND cc.files != '[]';
