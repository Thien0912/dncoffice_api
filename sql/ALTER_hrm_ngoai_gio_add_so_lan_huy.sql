-- Thêm cột đếm số lần hủy vào bảng hrm_ngoai_gio
-- Giới hạn: Chỉ được hủy tối đa 3 lần, lần thứ 4 không cho phép mở lại

ALTER TABLE `hrm_ngoai_gio` 
ADD COLUMN `so_lan_huy` INT(11) DEFAULT 0 COMMENT 'Số lần đã hủy đăng ký (giới hạn tối đa 3 lần)' 
AFTER `ly_do_huy`;

-- Comment giải thích
-- so_lan_huy = 0: Chưa bao giờ hủy
-- so_lan_huy = 1: Đã hủy 1 lần (có thể mở lại)
-- so_lan_huy = 2: Đã hủy 2 lần (có thể mở lại)
-- so_lan_huy = 3: Đã hủy 3 lần (KHÔNG cho phép mở lại nữa)
