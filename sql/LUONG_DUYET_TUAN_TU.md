# CƠ CHẾ DUYỆT TUẦN TỰ THEO CẤP - LUỒNG XỬ LÝ CHI TIẾT

## I. TỔNG QUAN HỆ THỐNG MỚI

### Đặc điểm chính:
✅ **Duyệt tuần tự theo cấp** (Sequential Approval)  
✅ **Tất cả phải đồng ý mới thông qua** (Unanimous Approval)  
✅ **Có trạng thái tổng thể** của đề xuất  
✅ **1 người từ chối → quay về cấp 1** (Reset on Rejection)  
✅ **Tự động thêm người duyệt cấp tiếp theo** sau khi cấp hiện tại duyệt xong

---

## II. CẤU TRÚC DATABASE MỚI

### 1. Bảng `dx_de_xuat` - Thêm cột `trang_thai`
```sql
trang_thai ENUM(
    'nhap',           -- Nháp (chưa gửi)
    'cho_duyet',      -- Chờ duyệt cấp 1
    'dang_duyet',     -- Đang duyệt các cấp
    'da_duyet',       -- Đã duyệt hết (hoàn tất)
    'bi_tu_choi',     -- Bị từ chối
    'can_chinh_sua'   -- Cần chỉnh sửa sau khi bị từ chối
)
```

### 2. Bảng `dx_loai_de_xuat` - Thêm cột `so_cap_duyet_toi_da`
```sql
so_cap_duyet_toi_da TINYINT(1) DEFAULT 1
-- Số cấp duyệt tối đa cho loại đề xuất này
```

### 3. Bảng `dx_loai_de_xuat_don_vi` - Cấu hình routing
```sql
thu_tu INT           -- Thứ tự duyệt (1, 2, 3, 4...)
id_nguoi_duyet INT   -- Người duyệt của đơn vị này
```

---

## III. LUỒNG XỬ LÝ DUYỆT (`duyet_post`)

### **BƯỚC 1: KIỂM TRA QUYỀN**
```php
kiemTraQuyenDuyet($idDeXuat, $idNguoiDuyet)
```
**Logic:**
1. Kiểm tra user có trong danh sách người duyệt không
2. Lấy cấp duyệt hiện tại: `getCapDuyetHienTai()` (cấp nhỏ nhất chưa duyệt)
3. **CHỈ cho phép duyệt nếu:** `cap_duyet của user == cap hiện tại`

**Ví dụ:**
- Đề xuất ở cấp 2 (cap_duyet = 2)
- User A (cấp 2) → ✅ Được duyệt
- User B (cấp 3) → ❌ Chưa đến lượt

---

### **BƯỚC 2A: ĐỒNG Ý (da_duyet = 1)**

#### **2A.1: Cập nhật trạng thái**
```sql
UPDATE dx_nguoi_duyet_de_xuat 
SET da_duyet = 1, 
    ly_do = 'Đồng ý', 
    thoi_gian_duyet = NOW()
WHERE id_de_xuat = 101 AND id_nguoi_duyet = 2585
```

#### **2A.2: Kiểm tra cấp hiện tại đã duyệt hết chưa**
```php
kiemTraCapDaDuyet($idDeXuat, $capHienTai)
```
**Logic:**
- Đếm tổng số người duyệt cấp này: `COUNT(*) WHERE cap_duyet = 2`
- Đếm số người đã duyệt: `COUNT(*) WHERE cap_duyet = 2 AND da_duyet = 1`
- Trả về `true` nếu: `tổng == số đã duyệt`

**Ví dụ:**
- Cấp 2 có 3 người: User A, B, C
- User A duyệt → 1/3 → ❌ Chưa đủ
- User B duyệt → 2/3 → ❌ Chưa đủ
- User C duyệt → 3/3 → ✅ Cấp 2 hoàn tất!

#### **2A.3: Nếu cấp hiện tại đã duyệt hết**
```php
layDanhSachCapTiepTheo($idDeXuat, $capHienTai)
```

**Trường hợp 1: Còn cấp tiếp theo**
```php
// Lấy cấu hình từ dx_loai_de_xuat_don_vi
SELECT * FROM dx_loai_de_xuat_don_vi 
WHERE id_dx_loai_de_xuat = 1 
  AND thu_tu = 3  -- Cấp tiếp theo

// Thêm người duyệt cấp 3
INSERT INTO dx_nguoi_duyet_de_xuat 
    (id_de_xuat, id_don_vi, id_nguoi_duyet, cap_duyet, da_duyet)
VALUES 
    (101, 5, 2586, 3, NULL),
    (101, 6, 2587, 3, NULL);

// Cập nhật trạng thái
UPDATE dx_de_xuat SET trang_thai = 'dang_duyet' WHERE id_de_xuat = 101;
```
**Response:** "Duyệt thành công. Đề xuất đã chuyển sang cấp 3 để duyệt tiếp"

**Trường hợp 2: Cấp cuối (không còn cấp nào nữa)**
```php
// Hoàn tất
UPDATE dx_de_xuat SET trang_thai = 'da_duyet' WHERE id_de_xuat = 101;
```
**Response:** "Duyệt thành công. Đề xuất đã được duyệt hoàn tất"

#### **2A.4: Nếu cấp hiện tại chưa duyệt hết**
```php
UPDATE dx_de_xuat SET trang_thai = 'dang_duyet' WHERE id_de_xuat = 101;
```
**Response:** "Duyệt thành công. Đang chờ người duyệt khác cùng cấp 2"

---

### **BƯỚC 2B: TỪ CHỐI (da_duyet = 0)**

#### **2B.1: Cập nhật trạng thái từ chối**
```sql
UPDATE dx_nguoi_duyet_de_xuat 
SET da_duyet = 0, 
    ly_do = 'Không đủ ngân sách', 
    thoi_gian_duyet = NOW()
WHERE id_de_xuat = 101 AND id_nguoi_duyet = 2585
```

#### **2B.2: Reset về cấp 1**
```php
resetVeCap1($idDeXuat)
```

**Thao tác:**
```sql
-- Xóa tất cả người duyệt cấp > 1
DELETE FROM dx_nguoi_duyet_de_xuat 
WHERE id_de_xuat = 101 AND cap_duyet > 1;

-- Reset cấp 1 về trạng thái chưa duyệt
UPDATE dx_nguoi_duyet_de_xuat 
SET da_duyet = NULL, 
    ly_do = NULL, 
    thoi_gian_duyet = NULL
WHERE id_de_xuat = 101 AND cap_duyet = 1;
```

#### **2B.3: Cập nhật trạng thái đề xuất**
```sql
UPDATE dx_de_xuat 
SET trang_thai = 'can_chinh_sua' 
WHERE id_de_xuat = 101;
```

**Response:** "Đã từ chối đề xuất. Đề xuất quay về cấp 1 và cần người tạo chỉnh sửa"

---

## IV. FLOW CHART TỔNG QUAN

```
START
  ↓
[Kiểm tra quyền duyệt]
  ├─ ❌ Không có quyền → 403 Forbidden
  ↓
  ✅ Có quyền
  ↓
[Cập nhật trạng thái duyệt của user hiện tại]
  ↓
[da_duyet == 1?]
  ├───────────────────────┐
  ↓ YES (ĐỒNG Ý)          ↓ NO (TỪ CHỐI)
  ↓                       ↓
[Cấp hiện tại duyệt hết?] [Reset về cấp 1]
  ├─────────┐             ↓
  ↓ YES     ↓ NO         [trang_thai = can_chinh_sua]
  ↓         ↓             ↓
[Còn cấp tiếp theo?] [trang_thai = dang_duyet] END
  ├─────────┐         ↓
  ↓ YES     ↓ NO      [Chờ người khác cùng cấp]
  ↓         ↓         ↓
[Thêm người duyệt cấp tiếp theo] [trang_thai = da_duyet]
  ↓         ↓         ↓
[trang_thai = dang_duyet] [HOÀN TẤT]
  ↓         ↓
END       END
```

---

## V. VÍ DỤ THỰC TẾ

### Cấu hình loại đề xuất "Mua sắm tài sản" (ID 1):
```
so_cap_duyet_toi_da = 3
```

### Cấu hình routing (dx_loai_de_xuat_don_vi):
| thu_tu | id_don_vi | id_nguoi_duyet | Vai trò |
|--------|-----------|----------------|---------|
| 1      | 5         | 2580           | Trưởng phòng IT |
| 1      | 5         | 2581           | Phó phòng IT |
| 2      | 3         | 2582           | Giám đốc tài chính |
| 3      | 1         | 2585           | Tổng giám đốc |

---

### **Kịch bản 1: Duyệt thành công tất cả các cấp**

**T1:** User 2585 tạo đề xuất 101
```
dx_de_xuat: trang_thai = 'cho_duyet'
dx_nguoi_duyet_de_xuat:
  - (101, 5, 2580, cap_duyet=1, da_duyet=NULL)
  - (101, 5, 2581, cap_duyet=1, da_duyet=NULL)
```

**T2:** User 2580 (cấp 1) duyệt
```
POST /duyet/101 { da_duyet: 1 }
→ da_duyet = 1 cho user 2580
→ Cấp 1: 1/2 duyệt → Chưa đủ
→ trang_thai = 'dang_duyet'
Response: "Đang chờ người duyệt khác cùng cấp 1"
```

**T3:** User 2581 (cấp 1) duyệt
```
POST /duyet/101 { da_duyet: 1 }
→ da_duyet = 1 cho user 2581
→ Cấp 1: 2/2 duyệt → ✅ Đủ!
→ Thêm người duyệt cấp 2: user 2582
→ trang_thai = 'dang_duyet'
Response: "Duyệt thành công. Đề xuất đã chuyển sang cấp 2"
```

**T4:** User 2582 (cấp 2) duyệt
```
POST /duyet/101 { da_duyet: 1 }
→ da_duyet = 1 cho user 2582
→ Cấp 2: 1/1 duyệt → ✅ Đủ!
→ Thêm người duyệt cấp 3: user 2585
→ trang_thai = 'dang_duyet'
Response: "Duyệt thành công. Đề xuất đã chuyển sang cấp 3"
```

**T5:** User 2585 (cấp 3 - cấp cuối) duyệt
```
POST /duyet/101 { da_duyet: 1 }
→ da_duyet = 1 cho user 2585
→ Cấp 3: 1/1 duyệt → ✅ Đủ!
→ Không còn cấp nào nữa
→ trang_thai = 'da_duyet' (HOÀN TẤT)
Response: "Duyệt thành công. Đề xuất đã được duyệt hoàn tất"
```

---

### **Kịch bản 2: Cấp 2 từ chối**

**T1-T3:** Giống kịch bản 1 (cấp 1 duyệt xong, chuyển cấp 2)

**T4:** User 2582 (cấp 2) TỪ CHỐI
```
POST /duyet/101 { da_duyet: 0, ly_do: "Ngân sách không đủ" }
→ da_duyet = 0 cho user 2582
→ XÓA tất cả cấp > 1 (xóa user 2582, 2585)
→ RESET cấp 1: da_duyet = NULL cho user 2580, 2581
→ trang_thai = 'can_chinh_sua'
Response: "Đã từ chối đề xuất. Đề xuất quay về cấp 1"
```

**T5:** User 2585 (người tạo) chỉnh sửa và gửi lại
```
PATCH /dexuat/101 { noi_dung: "Giảm số lượng xuống 3 máy" }
→ trang_thai = 'cho_duyet'
```

**T6:** Quy trình duyệt lại từ đầu (cấp 1 → 2 → 3)

---

## VI. LỢI ÍCH CỦA HỆ THỐNG MỚI

✅ **Kiểm soát chặt chẽ:** Tất cả phải đồng ý mới thông qua  
✅ **Tuần tự rõ ràng:** Duyệt theo thứ tự cấp 1 → 2 → 3 → 4  
✅ **Tự động hóa:** Tự động thêm người duyệt cấp tiếp theo  
✅ **Feedback loop:** Từ chối → quay về cấp 1 để chỉnh sửa  
✅ **Trạng thái minh bạch:** Biết rõ đề xuất đang ở bước nào  
✅ **Audit trail:** Lưu lại toàn bộ lịch sử duyệt/từ chối

---

## VII. API ENDPOINTS CẦN CẬP NHẬT

### 1. Gửi đề xuất (chuyển từ nháp → chờ duyệt)
```
PATCH /api/v2/admin/hrm/dexuat/:id
Body: { "nhap": 0 }
→ Cập nhật trang_thai = 'cho_duyet'
```

### 2. Chỉnh sửa sau khi bị từ chối
```
PATCH /api/v2/admin/hrm/dexuat/:id
(chỉ cho phép khi trang_thai = 'can_chinh_sua')
→ Cập nhật trang_thai = 'cho_duyet' sau khi sửa
```

### 3. Lấy danh sách (thêm filter theo trang_thai)
```
GET /api/v2/admin/hrm/dexuat?searchKey[trang_thai]=dang_duyet
```

---

## VIII. MIGRATION CHECKLIST

✅ Chạy SQL migration: `dx_de_xuat_add_trang_thai.sql`  
✅ Cập nhật model: `Dx_nguoi_duyet_de_xuat_model.php`  
✅ Cập nhật controller: `Dexuat.php` (hàm duyet_post)  
✅ Cập nhật API create/update để xử lý trang_thai  
✅ Cấu hình routing cho các loại đề xuất (dx_loai_de_xuat_don_vi)  
✅ Test với dữ liệu mẫu

---

**Hệ thống đã sẵn sàng cho cơ chế duyệt tuần tự theo cấp!** 🎉
