-- Quyền cho module HRM Ngoài giờ
-- Refactor từ hardcode logic sang hệ thống RBAC
-- Format: controller.function tiếng Việt không dấu (VD: ngoaigio.xemtatca, nghiphep.taoho)
-- Phân quyền từ cao đến thấp: xemtatca > xemdonvi > xemcanhan

INSERT INTO `ql_quyen` 
(`ql_quyen_ten`, `ql_quyen_khoa`, `ql_quyen_parent_id`, `ql_quyen_loai_module`, `ql_quyen_action_type`, `ql_quyen_url`, `ql_quyen_mo_ta`) 
VALUES

-- Quyền cha - Module ngoài giờ
('Ngoài giờ', 'ngoaigio', NULL, 2, NULL, NULL, 'Module quản lý ngoài giờ'),

-- === QUYỀN XEM (từ cao đến thấp) ===

-- Cấp 1: Xem tất cả (Super Admin, Phòng TCHC)
('Xem tất cả đơn ngoài giờ', 'ngoaigio.xemtatca', NULL, 2, 1, NULL, 'Xem được tất cả đơn ngoài giờ của toàn bộ hệ thống'),

-- Cấp 2: Xem theo đơn vị (Lãnh đạo, Văn thư, Người duyệt)
('Xem đơn ngoài giờ đơn vị', 'ngoaigio.xemdonvi', NULL, 2, 1, NULL, 'Xem đơn theo đơn vị: (1) Lãnh đạo xem cấp dưới, (2) Văn thư xem đơn vị, (3) Người duyệt xem đơn được phân duyệt'),

-- Cấp 3: Xem cá nhân (Nhân viên thường - mặc định)
('Xem đơn ngoài giờ cá nhân', 'ngoaigio.xemcanhan', NULL, 2, 1, NULL, 'Chỉ xem được đơn ngoài giờ của chính mình'),

-- === QUYỀN THAO TÁC ===

-- Tạo đơn ngoài giờ
('Tạo đơn ngoài giờ', 'ngoaigio.them', NULL, 2, 2, NULL, 'Tạo đơn đăng ký ngoài giờ cho bản thân'),

-- Tạo hộ đơn ngoài giờ
('Tạo hộ đơn ngoài giờ', 'ngoaigio.taoho', NULL, 2, 5, NULL, 'Tạo đơn ngoài giờ thay cho nhân viên khác'),

-- Sửa đơn ngoài giờ
('Sửa đơn ngoài giờ', 'ngoaigio.sua', NULL, 2, 3, NULL, 'Chỉnh sửa đơn ngoài giờ'),

-- Xóa đơn ngoài giờ
('Xóa đơn ngoài giờ', 'ngoaigio.xoa', NULL, 2, 4, NULL, 'Xóa đơn ngoài giờ'),

-- Duyệt đơn ngoài giờ
('Duyệt đơn ngoài giờ', 'ngoaigio.duyet', NULL, 2, 5, NULL, 'Duyệt/từ chối đơn ngoài giờ');

-- Update parent_id cho các quyền con
UPDATE ql_quyen 
SET ql_quyen_parent_id = (SELECT ql_quyen_id FROM (SELECT * FROM ql_quyen) AS temp WHERE temp.ql_quyen_khoa = 'ngoaigio')
WHERE ql_quyen_khoa LIKE 'ngoaigio.%';
