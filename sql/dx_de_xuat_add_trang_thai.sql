-- Thêm cột trạng thái cho đề xuất
-- Chạy migration này để bổ sung cơ chế duyệt tuần tự theo cấp

ALTER TABLE `dx_de_xuat` 
ADD COLUMN `trang_thai` ENUM('nhap', 'dang_xu_ly', 'da_duyet', 'tu_choi') 
DEFAULT 'nhap' 
COMMENT 'Trạng thái đề xuất: nhap=nháp chưa gửi, dang_xu_ly=đang xử lý duyệt, da_duyet=đã duyệt hoàn tất, tu_choi=bị từ chối' 
AFTER `nhap`;

-- Thêm index để tăng tốc query
ALTER TABLE `dx_de_xuat` ADD INDEX `idx_trang_thai` (`trang_thai`);

-- Cập nhật dữ liệu cũ
UPDATE `dx_de_xuat` SET `trang_thai` = 'nhap' WHERE `nhap` = 1;
UPDATE `dx_de_xuat` SET `trang_thai` = 'dang_xu_ly' WHERE `nhap` = 0 AND `trang_thai` = 'nhap';

-- LƯU Ý: 
-- Không cần cột so_cap_duyet_toi_da vì logic đơn giản hơn:
-- Hệ thống sẽ tự động xác định cấp cuối dựa vào bảng dx_loai_de_xuat_don_vi
-- Nếu không còn đơn vị nào ở thu_tu tiếp theo → đó là cấp cuối
