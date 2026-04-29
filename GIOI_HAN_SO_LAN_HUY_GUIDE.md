# GIỚI HẠN SỐ LẦN HỦY ĐĂNG KÝ NGOÀI GIỜ

## 📋 Tổng quan

Hệ thống giới hạn số lần hủy/mở lại đăng ký ngoài giờ để tránh lạm dụng.

**Quy định:** Mỗi đăng ký chỉ được phép hủy tối đa **3 lần**. Sau khi hủy lần thứ 3, không thể mở lại đăng ký đó nữa.

---

## 🗄️ Database Changes

### Cột mới: `so_lan_huy`

```sql
ALTER TABLE `hrm_ngoai_gio` 
ADD COLUMN `so_lan_huy` INT(11) DEFAULT 0 
COMMENT 'Số lần đã hủy đăng ký (giới hạn tối đa 3 lần)' 
AFTER `ly_do_huy`;
```

**Giá trị:**
- `0` - Chưa bao giờ hủy (đăng ký mới)
- `1` - Đã hủy 1 lần (có thể mở lại - còn 2 lần)
- `2` - Đã hủy 2 lần (có thể mở lại - còn 1 lần)
- `3` - Đã hủy 3 lần (KHÔNG cho phép mở lại nữa)

---

## 🔄 Logic Flow

### **Khi HỦY đăng ký** (`trang_thai_moi = 'Huy'`)

```
1. Kiểm tra đơn đã được duyệt chưa
   ├─ Nếu Da_duyet → ❌ Không cho phép hủy
   └─ Nếu Cho_duyet/Tu_choi → ✅ Cho phép hủy
   
2. Tăng so_lan_huy lên 1
   - so_lan_huy = so_lan_huy_cu + 1
   
3. Cập nhật database:
   - trang_thai_tong = 'Huy'
   - ly_do_huy = [lý do người dùng nhập]
   - so_lan_huy = [số mới]
   
4. Thông báo cho người dùng:
   ├─ Nếu so_lan_huy < 3: "Hủy đăng ký thành công (Đã hủy X/3 lần - Còn Y lần được phép mở lại)"
   └─ Nếu so_lan_huy >= 3: "Hủy đăng ký thành công (Đã hủy 3 lần - Đây là lần cuối được phép hủy. Không thể mở lại đăng ký này nữa)"
```

### **Khi MỞ LẠI đăng ký** (`trang_thai_moi = 'Cho_duyet'`)

```
1. Kiểm tra so_lan_huy hiện tại
   ├─ Nếu so_lan_huy >= 3 → ❌ TỪ CHỐI
   │  Message: "Đã hết số lần được phép mở lại. Đăng ký này đã bị hủy X lần (tối đa 3 lần). 
   │             Vui lòng tạo đăng ký mới."
   │
   └─ Nếu so_lan_huy < 3 → ✅ CHO PHÉP
   
2. Cập nhật database:
   - trang_thai_tong = 'Cho_duyet'
   - ly_do_huy = null (xóa lý do hủy cũ)
   - KHÔNG thay đổi so_lan_huy (giữ nguyên để tracking)
   
3. Nếu có data mới → cập nhật:
   - ngay_dang_ky, gio_bat_dau, gio_ket_thuc, so_gio, noi_dung, chi_tiet
   
4. Thông báo cho người dùng:
   - "Mở lại đăng ký thành công (Còn X lần được phép hủy)"
```

---

## 📊 Ví dụ Thực tế

### **Scenario 1: Hủy và mở lại bình thường**

```json
// Lần 1: HỦY
Request: POST /api/v2/admin/hrm/ngoaigio/change_status
{
  "id": 123,
  "trang_thai_moi": "Huy",
  "ly_do_huy": "Nhân viên bận việc đột xuất"
}

Response: {
  "success": true,
  "message": "Hủy đăng ký thành công (Đã hủy 1/3 lần - Còn 2 lần được phép mở lại)",
  "data": { "id": 123, "so_lan_huy": 1 }
}

// Database: so_lan_huy = 1, trang_thai_tong = 'Huy'

// ─────────────────────────────────────

// Lần 2: MỞ LẠI
Request: {
  "id": 123,
  "trang_thai_moi": "Cho_duyet"
}

Response: {
  "success": true,
  "message": "Mở lại đăng ký thành công (Còn 2 lần được phép hủy)",
  "data": { "id": 123, "so_lan_huy": 1, "con_lai": 2 }
}

// Database: so_lan_huy = 1 (KHÔNG ĐỔI), trang_thai_tong = 'Cho_duyet'

// ─────────────────────────────────────

// Lần 3: HỦY lần 2
Request: {
  "id": 123,
  "trang_thai_moi": "Huy",
  "ly_do_huy": "Thay đổi lịch làm việc"
}

Response: {
  "success": true,
  "message": "Hủy đăng ký thành công (Đã hủy 2/3 lần - Còn 1 lần được phép mở lại)",
  "data": { "id": 123, "so_lan_huy": 2 }
}

// Database: so_lan_huy = 2, trang_thai_tong = 'Huy'

// ─────────────────────────────────────

// Lần 4: MỞ LẠI lần 2
Request: {
  "id": 123,
  "trang_thai_moi": "Cho_duyet"
}

Response: {
  "success": true,
  "message": "Mở lại đăng ký thành công (Còn 1 lần được phép hủy)",
  "data": { "id": 123, "so_lan_huy": 2, "con_lai": 1 }
}

// ─────────────────────────────────────

// Lần 5: HỦY lần 3 (LẦN CUỐI)
Request: {
  "id": 123,
  "trang_thai_moi": "Huy",
  "ly_do_huy": "Hủy lần cuối"
}

Response: {
  "success": true,
  "message": "Hủy đăng ký thành công (Đã hủy 3 lần - Đây là lần cuối được phép hủy. Không thể mở lại đăng ký này nữa)",
  "data": { "id": 123, "so_lan_huy": 3 }
}

// Database: so_lan_huy = 3, trang_thai_tong = 'Huy'
```

### **Scenario 2: Không thể mở lại sau 3 lần**

```json
// Lần 6: Cố gắng MỞ LẠI (BỊ TỪ CHỐI)
Request: {
  "id": 123,
  "trang_thai_moi": "Cho_duyet"
}

Response: {
  "success": false,
  "message": "Đã hết số lần được phép mở lại. Đăng ký này đã bị hủy 3 lần (tối đa 3 lần). Vui lòng tạo đăng ký mới.",
  "status_code": 400
}

// Database: KHÔNG THAY ĐỔI - so_lan_huy = 3, trang_thai_tong = 'Huy'
```

---

## 🎯 Mục đích

1. **Ngăn chặn lạm dụng:** Tránh việc nhân viên tạo/hủy đơn liên tục
2. **Quản lý tốt hơn:** Lãnh đạo có cơ sở để đánh giá tính nghiêm túc của đơn
3. **Database cleanup:** Hạn chế số lượng record "rác" trong database
4. **Trách nhiệm người dùng:** Nhân viên phải cân nhắc kỹ trước khi đăng ký

---

## 📝 Note quan trọng

- **Counter KHÔNG reset** khi mở lại → Luôn tăng dần
- Sau khi đạt 3 lần hủy → Chỉ có thể **tạo đơn mới**, không thể dùng đơn cũ
- Đơn đã **duyệt** (Da_duyet) **KHÔNG** được phép hủy
- Log sẽ ghi rõ số lần hủy để tracking

---

## 🔧 API Endpoint

**POST** `/api/v2/admin/hrm/ngoaigio/change_status`

**Request Body:**
```typescript
{
  id: number                    // ID đăng ký
  trang_thai_moi: 'Huy' | 'Cho_duyet'
  ly_do_huy?: string           // Bắt buộc khi trang_thai_moi = 'Huy'
  data?: {                     // Optional - Cập nhật data khi mở lại
    ngay_dang_ky?: string
    gio_bat_dau?: string
    gio_ket_thuc?: string
    so_gio?: number
    noi_dung?: string
    chi_tiet?: string
  }
}
```

**Response:**
```typescript
{
  success: boolean
  message: string
  data: {
    id: number
    so_lan_huy: number
    con_lai?: number  // Chỉ có khi mở lại
  }
}
```

---

## ✅ Migration Checklist

- [x] Tạo migration SQL: `ALTER_hrm_ngoai_gio_add_so_lan_huy.sql`
- [x] Cập nhật backend logic trong `Ngoaigio.php::change_status_post()`
- [x] Cập nhật TypeScript types: `OvertimeRequest.so_lan_huy`
- [ ] Test API với Postman/Insomnia
- [ ] Cập nhật UI frontend để hiển thị số lần hủy
- [ ] Thêm warning badge khi so_lan_huy >= 2
- [ ] Document cho user manual

---

## 🎨 UI Suggestions (Frontend)

### Display trong table/card:
```tsx
{record.so_lan_huy > 0 && (
  <Badge color={record.so_lan_huy >= 3 ? 'danger' : record.so_lan_huy >= 2 ? 'warning' : 'default'}>
    Đã hủy {record.so_lan_huy}/3 lần
  </Badge>
)}
```

### Warning khi hủy lần 3:
```tsx
if (soLanHuy === 2) {
  return (
    <Alert severity="warning">
      ⚠️ Cảnh báo: Đây là lần hủy cuối cùng! 
      Sau khi hủy lần này, bạn sẽ không thể mở lại đăng ký này nữa.
    </Alert>
  )
}
```

### Disable nút "Mở lại" khi đã hết lượt:
```tsx
<Button 
  disabled={record.so_lan_huy >= 3}
  onClick={handleReopen}
>
  {record.so_lan_huy >= 3 ? 'Hết lượt mở lại' : 'Mở lại đăng ký'}
</Button>
```
