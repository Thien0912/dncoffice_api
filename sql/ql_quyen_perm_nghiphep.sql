-- 1. Kiểm tra và tạo quyền cha 'nghiphep' nếu chưa tồn tại
INSERT INTO ql_quyen (ql_quyen_ten, ql_quyen_mo_ta, ql_quyen_khoa, ql_quyen_parent_id, ql_quyen_action_type, ql_quyen_url)
SELECT 'Danh sách nghỉ phép', 'Quản lý và xem danh sách đơn nghỉ phép', 'nghiphep', NULL, 1, 'hrm/nghi-phep'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM ql_quyen WHERE ql_quyen_khoa = 'nghiphep');

-- 2. Lấy ID của quyền cha
SET @parentId = (SELECT ql_quyen_id FROM ql_quyen WHERE ql_quyen_khoa = 'nghiphep' LIMIT 1);

-- 3. Xóa các quyền con cũ của nghiphep để cập nhật lại (giữ lại quyền cha)
DELETE FROM ql_quyen WHERE ql_quyen_khoa LIKE 'nghiphep.%';

-- 4. Thêm bộ quyền con với action_type chính xác
INSERT INTO ql_quyen (ql_quyen_ten, ql_quyen_khoa, ql_quyen_mo_ta, ql_quyen_parent_id, ql_quyen_action_type, ql_quyen_url) VALUES 
('Nghỉ phép - tạo mới', 'nghiphep.create', 'Cho phép tạo đơn nghỉ phép mới', @parentId, 2, ''),
('Nghỉ phép - chỉnh sửa', 'nghiphep.update', 'Cho phép sửa đơn nghỉ phép', @parentId, 3, ''),
('Nghỉ phép - thu hồi đơn', 'nghiphep.deletes', 'Cho phép xóa hoặc thu hồi đơn nghỉ phép', @parentId, 4, ''),
('Xuất báo cáo nghỉ phép theo đơn vị', 'nghiphep.export_by_unit', 'Xuất báo cáo Excel tổng hợp theo đơn vị', @parentId, 5, ''),
('Xuất báo cáo nghỉ phép cá nhân', 'nghiphep.export_by_employee', 'Xuất file Word đơn nghỉ phép cá nhân', @parentId, 5, ''),
('Duyệt theo chỉ thị lãnh đạo', 'nghiphep.approve_on_behalf', 'Duyệt hộ theo chỉ thị lãnh đạo', @parentId, 5, ''),
('Duyệt đơn nghỉ phép', 'nghiphep.approve', 'Quyền phê duyệt đơn nghỉ phép dành cho lãnh đạo', @parentId, 5, '');
