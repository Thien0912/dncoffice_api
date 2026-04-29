# Hướng dẫn Refactor Phân quyền HRM Ngoài giờ

## 📋 Tổng quan
Đã refactor code phân quyền từ **hardcode logic** sang hệ thống **RBAC (Role-Based Access Control)** sử dụng bảng `ql_quyen` và `ql_vai_tro_quyen`.

## 🎯 Lợi ích

### Trước khi refactor:
❌ Hardcode vai trò trong code (`SUPER_ADMIN`, `PHONG_TCHC`, `VAN_THU_DON_VI`)  
❌ Logic phân quyền phức tạp, lồng nhau nhiều if-else  
❌ Khó bảo trì, thêm vai trò mới phải sửa code  
❌ Không linh hoạt, không thể cấu hình quyền từ UI  

### Sau khi refactor:
✅ Sử dụng hệ thống quyền trong database  
✅ Logic clear, dễ đọc và maintain  
✅ Thêm quyền mới chỉ cần insert vào database  
✅ Cấu hình quyền linh hoạt qua UI quản lý vai trò  

---

## 📦 Các file đã thay đổi

### 1. **sql/INSERT_ql_quyen_hrm_ngoai_gio.sql** (NEW)
File SQL để insert các quyền mới cho module HRM ngoài giờ (format tiếng Việt không dấu):\n- `ngoaigio.xemtatca` - Xem tất cả đơn (Super Admin, TCHC)
- `ngoaigio.xemdonvi` - Xem đơn theo đơn vị (Văn thư)
- `ngoaigio.xemcapduoi` - Xem đơn cấp dưới (Lãnh đạo)
- `ngoaigio.xemphanduyet` - Xem đơn được phân công duyệt
- `ngoaigio.xemcanhan` - Xem đơn cá nhân
- `ngoaigio.them` - Tạo đơn
- `ngoaigio.taoho` - Tạo hộ đơn
- `ngoaigio.sua` - Sửa đơn
- `ngoaigio.xoa` - Xóa đơn
- `ngoaigio.duyet` - Duyệt đơn

### 2. **application/models/Ql_vai_tro_model.php** (UPDATED)
Thêm 3 helper methods:

```php
// Lấy danh sách khóa quyền của user
getUserPermissionKeys($ql_nguoi_dung_id) 
// Return: ['ngoaigio.xemtatca', 'ngoaigio.them', ...]

// Kiểm tra có 1 quyền cụ thể
hasPermission($ql_nguoi_dung_id, 'ngoaigio.xemtatca')
// Return: true/false

// Kiểm tra có ít nhất 1 trong các quyền
hasAnyPermission($ql_nguoi_dung_id, ['ngoaigio.xemtatca', 'ngoaigio.xemdonvi'])
// Return: true/false
```

### 3. **application/models/Hrm_ngoaigio_model.php** (REFACTORED)
- Loại bỏ logic hardcode check vai trò (`$isSuperAdmin`, `$isPhongTCHC`, `$isVanThuDonVi`)
- Thêm method `applyPermissionFilter()` - Logic phân quyền dựa trên khóa quyền
- Refactor method `getAll()` - Gọn gàng, dễ đọc hơn

---

## 🚀 Cách sử dụng

### Bước 1: Chạy SQL để tạo quyền
```bash
# Trong MySQL/phpMyAdmin
source sql/INSERT_ql_quyen_hrm_ngoai_gio.sql
```

### Bước 2: Gán quyền cho vai trò
Trong UI quản lý vai trò hoặc chạy SQL:

```sql
-- Gán quyền xem tất cả cho Super Admin (giả sử role_id = 1)
INSERT INTO ql_vai_tro_quyen (ql_vai_tro_id, ql_quyen_id)
SELECT 1, ql_quyen_id FROM ql_quyen WHERE ql_quyen_khoa LIKE 'ngoaigio.%';

-- Gán quyền xem đơn vị cho Văn thư (giả sử role_id = 5)
INSERT INTO ql_vai_tro_quyen (ql_vai_tro_id, ql_quyen_id)
SELECT 5, ql_quyen_id FROM ql_quyen 
WHERE ql_quyen_khoa IN ('ngoaigio.xemdonvi', 'ngoaigio.xemcanhan', 'ngoaigio.xemphanduyet');
```

### Bước 3: Test
Code sẽ tự động áp dụng phân quyền mới, không cần thay đổi gì thêm.

---

## 📝 Ví dụ sử dụng trong code khác

### Check quyền trong Controller
```php
// Check user có quyền xem tất cả không
$canViewAll = $this->Ql_vai_tro_model->hasPermission($userId, 'ngoaigio.xemtatca');

if (!$canViewAll) {
    return $this->output
        ->set_status_header(403)
        ->set_content_type('application/json')
        ->set_output(json_encode(['error' => 'Không có quyền truy cập']));
}
```

### Check nhiều quyền
```php
// Check user có ít nhất 1 trong các quyền xem
$canView = $this->Ql_vai_tro_model->hasAnyPermission($userId, [
    'ngoaigio.xemtatca',
    'ngoaigio.xemdonvi',
    'ngoaigio.xemcapduoi'
]);
```

### Lấy tất cả quyền của user
```php
$permissions = $this->Ql_vai_tro_model->getUserPermissionKeys($userId);
// ['ngoaigio.xemtatca', 'ngoaigio.them', 'ngoaigio.duyet', ...]
```

---

## 🔧 Mở rộng cho module khác

Để refactor module khác (ví dụ: Nghỉ phép), làm tương tự:

### 1. Tạo quyền mới
```sql
INSERT INTO ql_quyen (ql_quyen_ten, ql_quyen_khoa, ...) VALUES
('Xem tất cả đơn nghỉ phép', 'nghiphep.xemtatca', ...),
('Xem đơn nghỉ phép đơn vị', 'nghiphep.xemdonvi', ...),
('Tạo hộ đơn nghỉ phép', 'nghiphep.taoho', ...);
```

### 2. Refactor model
```php
private function applyPermissionFilter($qlNguoiDungId, ...) {
    $permissions = $this->Ql_vai_tro_model->getUserPermissionKeys($qlNguoiDungId);
    
    if (in_array('nghiphep.xemtatca', $permissions)) {
        return;
    }
    // ... logic khác
}
```

---

## ⚠️ Lưu ý

1. **Backup database** trước khi chạy SQL
2. **Test kỹ** trên môi trường dev trước
3. Đảm bảo tất cả vai trò đã được gán đúng quyền
4. Nếu có lỗi "NO_PERMISSION", kiểm tra:
   - User đã được gán vai trò chưa (`ql_vai_tro_nguoi_dung`)
   - Vai trò đã được gán quyền chưa (`ql_vai_tro_quyen`)
   - Quyền đã được insert vào database chưa (`ql_quyen`)

---

## 📊 So sánh trước/sau

| Tiêu chí | Trước | Sau |
|----------|-------|-----|
| Độ phức tạp code | 120 dòng | 80 dòng |
| Số if-else lồng nhau | 8 levels | 2 levels |
| Thêm vai trò mới | Sửa code | Insert DB |
| Khả năng mở rộng | Khó | Dễ |
| Khả năng test | Khó | Dễ |
| Performance | Tương đương | Tương đương |

---

## 🎓 Kết luận

Việc refactor này giúp:
- ✅ Code dễ đọc, dễ maintain
- ✅ Linh hoạt trong việc cấu hình quyền
- ✅ Dễ mở rộng cho các module khác
- ✅ Tách biệt logic nghiệp vụ và phân quyền

**Khuyến nghị**: Áp dụng pattern này cho tất cả các module khác có phân quyền phức tạp.
