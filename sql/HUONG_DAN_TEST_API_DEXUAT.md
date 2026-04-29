# HƯỚNG DẪN TEST API ĐỀ XUẤT - HOPPSCOTCH

## I. CHUẨN BỊ

### 1. Import vào Hoppscotch
- Base URL: `http://localhost/dncoffice_api/api/v2/admin/hrm/dexuat`
- Authorization: Bearer Token (lấy từ login)

### 2. Dữ liệu test cần có sẵn
```sql
-- Chạy file migration trước
source dx_de_xuat_add_trang_thai.sql;

-- Tạo loại đề xuất mẫu
INSERT INTO dx_loai_de_xuat (id_dx_loai_de_xuat, ten_loai, mo_ta) VALUES
(1, 'Mua sắm tài sản', 'Đề xuất mua sắm thiết bị, tài sản công ty');

-- Cấu hình routing (3 cấp duyệt)
INSERT INTO dx_loai_de_xuat_don_vi (id_dx_loai_de_xuat, id_don_vi, id_nguoi_duyet, thu_tu) VALUES
(1, 5, 2580, 1),  -- Cấp 1: User 2580 (Trưởng phòng)
(1, 5, 2581, 1),  -- Cấp 1: User 2581 (Phó phòng) - cùng cấp
(1, 3, 2582, 2),  -- Cấp 2: User 2582 (Giám đốc tài chính)
(1, 1, 2585, 3);  -- Cấp 3: User 2585 (Tổng giám đốc) - Cấp cuối

-- User test: 2585 (người tạo), 2580, 2581, 2582 (người duyệt)
```

---

## II. KỊCH BẢN TEST

### **Kịch bản 1: TẠO ĐỀ XUẤT MỚI**

#### Request 1: Tạo đề xuất (nháp)
```http
POST {{baseUrl}}/
Authorization: Bearer <token_user_2585>
Content-Type: application/json
```

**Body:**
```json
{
  "tieu_de": "Đề xuất mua 5 máy tính Dell Precision",
  "noi_dung": "Cần mua 5 máy tính cấu hình cao cho team phát triển phần mềm. Giá dự kiến: 150 triệu",
  "id_dx_loai_de_xuat": 1,
  "nhap": 1,
  "danh_sach_don_vi": [
    {
      "cap": 0,
      "id_don_vi": 40,
      "ids_nguoi_duyet": [46, 627]
    },
    {
      "cap": 1,
      "id_don_vi": 15,
      "ids_nguoi_duyet": [2509, 2515]
    },
    {
      "cap": 1,
      "id_don_vi": 16,
      "ids_nguoi_duyet": [2566]
    }
  ]
}
```

**Response mẫu:**
```json
{
  "status": true,
  "message": "Tạo đề xuất thành công",
  "data": {
    "id_de_xuat": 101,
    "trang_thai": "nhap"
  }
}
```

---

#### Request 2: Gửi đề xuất (chuyển từ nháp → đang xử lý)
```http
PATCH {{baseUrl}}/101
Authorization: Bearer <token_user_2585>
Content-Type: application/json
```

**Body:**
```json
{
  "nhap": 0
}
```

**Response mẫu:**
```json
{
  "status": true,
  "message": "Gửi đề xuất thành công",
  "data": {
    "id_de_xuat": 101,
    "trang_thai": "dang_xu_ly"
  }
}
```

**Kiểm tra database:**
```sql
-- Kiểm tra trạng thái đề xuất
SELECT id_de_xuat, tieu_de, trang_thai FROM dx_de_xuat WHERE id_de_xuat = 101;
-- Expected: trang_thai = 'dang_xu_ly'

-- Kiểm tra danh sách người duyệt cấp 1
SELECT * FROM dx_nguoi_duyet_de_xuat WHERE id_de_xuat = 101 AND cap_duyet = 1;
-- Expected: 2 records (user 2580, 2581) với da_duyet = NULL
```

---

### **Kịch bản 2: DUYỆT CẤP 1 (2 NGƯỜI CÙNG CẤP)**

#### Request 3: User 2580 duyệt (người thứ 1)
```http
POST {{baseUrl}}/duyet/101
Authorization: Bearer <token_user_2580>
Content-Type: application/json
```

**Body:**
```json
{
  "da_duyet": 1,
  "ly_do": "Đồng ý, thiết bị cần thiết cho công việc"
}
```

**Response mẫu:**
```json
{
  "status": true,
  "message": "Duyệt thành công. Đang chờ người duyệt khác cùng cấp 1",
  "data": {
    "trang_thai": "cho_nguoi_khac"
  }
}
```

**Kiểm tra database:**
```sql
SELECT id_nguoi_duyet, da_duyet, thoi_gian_duyet FROM dx_nguoi_duyet_de_xuat 
WHERE id_de_xuat = 101 AND cap_duyet = 1;
-- Expected: 2580 có da_duyet = 1, 2581 vẫn NULL
```

---

#### Request 4: User 2581 duyệt (người thứ 2 - cấp 1 hoàn tất)
```http
POST {{baseUrl}}/duyet/101
Authorization: Bearer <token_user_2581>
Content-Type: application/json
```

**Body:**
```json
{
  "da_duyet": 1,
  "ly_do": "Đồng ý"
}
```

**Response mẫu:**
```json
{
  "status": true,
  "message": "Duyệt thành công. Đề xuất đã chuyển sang cấp 2 để duyệt tiếp",
  "data": {
    "trang_thai": "da_chuyen_cap"
  }
}
```

**Kiểm tra database:**
```sql
-- Kiểm tra cấp 1 đã duyệt hết
SELECT * FROM dx_nguoi_duyet_de_xuat WHERE id_de_xuat = 101 AND cap_duyet = 1;
-- Expected: Cả 2 đều có da_duyet = 1

-- Kiểm tra cấp 2 đã được thêm tự động
SELECT * FROM dx_nguoi_duyet_de_xuat WHERE id_de_xuat = 101 AND cap_duyet = 2;
-- Expected: 1 record (user 2582) với da_duyet = NULL

-- Kiểm tra trạng thái đề xuất
SELECT trang_thai FROM dx_de_xuat WHERE id_de_xuat = 101;
-- Expected: trang_thai = 'dang_xu_ly'
```

---

### **Kịch bản 3: DUYỆT CẤP 2**

#### Request 5: User 2582 duyệt (cấp 2)
```http
POST {{baseUrl}}/duyet/101
Authorization: Bearer <token_user_2582>
Content-Type: application/json
```

**Body:**
```json
{
  "da_duyet": 1,
  "ly_do": "Đã kiểm tra ngân sách, đồng ý phê duyệt"
}
```

**Response mẫu:**
```json
{
  "status": true,
  "message": "Duyệt thành công. Đề xuất đã chuyển sang cấp 3 để duyệt tiếp",
  "data": {
    "trang_thai": "da_chuyen_cap"
  }
}
```

**Kiểm tra database:**
```sql
-- Kiểm tra cấp 3 đã được thêm tự động
SELECT * FROM dx_nguoi_duyet_de_xuat WHERE id_de_xuat = 101 AND cap_duyet = 3;
-- Expected: 1 record (user 2585) với da_duyet = NULL
```

---

### **Kịch bản 4: DUYỆT CẤP 3 (CẤP CUỐI - HOÀN TẤT)**

#### Request 6: User 2585 duyệt (cấp cuối)
```http
POST {{baseUrl}}/duyet/101
Authorization: Bearer <token_user_2585>
Content-Type: application/json
```

**Body:**
```json
{
  "da_duyet": 1,
  "ly_do": "Phê duyệt cuối cùng"
}
```

**Response mẫu:**
```json
{
  "status": true,
  "message": "Duyệt thành công. Đề xuất đã được duyệt hoàn tất",
  "data": {
    "trang_thai": "da_chuyen_cap"
  }
}
```

**Kiểm tra database:**
```sql
-- Kiểm tra trạng thái đề xuất
SELECT trang_thai FROM dx_de_xuat WHERE id_de_xuat = 101;
-- Expected: trang_thai = 'da_duyet' (HOÀN TẤT)

-- Kiểm tra không có cấp 4
SELECT * FROM dx_nguoi_duyet_de_xuat WHERE id_de_xuat = 101 AND cap_duyet = 4;
-- Expected: Không có record nào
```

---

### **Kịch bản 5: TỪ CHỐI Ở CẤP 2 → RESET VỀ CẤP 1**

#### Request 7: Tạo đề xuất mới (ID 102)
```http
POST {{baseUrl}}/
Authorization: Bearer <token_user_2585>
Content-Type: application/json
```

**Body:**
```json
{
  "tieu_de": "Đề xuất mua máy photocopy",
  "noi_dung": "Mua máy photocopy Canon IR ADV DX C5870",
  "id_dx_loai_de_xuat": 1,
  "nhap": 0,
  "danh_sach_don_vi": [
    {
      "cap": 1,
      "id_don_vi": 5,
      "ids_nguoi_duyet": [2580, 2581]
    }
  ]
}
```

---

#### Request 8: User 2580 + 2581 duyệt cấp 1
```http
POST {{baseUrl}}/duyet/102
Authorization: Bearer <token_user_2580>
```
```json
{ "da_duyet": 1, "ly_do": "OK" }
```

```http
POST {{baseUrl}}/duyet/102
Authorization: Bearer <token_user_2581>
```
```json
{ "da_duyet": 1, "ly_do": "OK" }
```

---

#### Request 9: User 2582 TỪ CHỐI ở cấp 2
```http
POST {{baseUrl}}/duyet/102
Authorization: Bearer <token_user_2582>
Content-Type: application/json
```

**Body:**
```json
{
  "da_duyet": 0,
  "ly_do": "Ngân sách không đủ, cần giảm chi phí xuống"
}
```

**Response mẫu:**
```json
{
  "status": true,
  "message": "Đã từ chối đề xuất. Đề xuất quay về cấp 1 và cần người tạo chỉnh sửa",
  "data": {
    "trang_thai": "tu_choi"
  }
}
```

**Kiểm tra database:**
```sql
-- Kiểm tra trạng thái đề xuất
SELECT trang_thai FROM dx_de_xuat WHERE id_de_xuat = 102;
-- Expected: trang_thai = 'tu_choi'

-- Kiểm tra cấp 2 và 3 đã bị xóa
SELECT * FROM dx_nguoi_duyet_de_xuat WHERE id_de_xuat = 102 AND cap_duyet > 1;
-- Expected: Không có record nào

-- Kiểm tra cấp 1 đã reset
SELECT id_nguoi_duyet, da_duyet, thoi_gian_duyet FROM dx_nguoi_duyet_de_xuat 
WHERE id_de_xuat = 102 AND cap_duyet = 1;
-- Expected: da_duyet = NULL, thoi_gian_duyet = NULL cho cả 2580 và 2581
```

---

#### Request 10: Người tạo chỉnh sửa và gửi lại
```http
PATCH {{baseUrl}}/102
Authorization: Bearer <token_user_2585>
Content-Type: application/json
```

**Body:**
```json
{
  "noi_dung": "Mua máy photocopy Canon IR ADV DX C5870 - Giảm xuống model thấp hơn để tiết kiệm",
  "trang_thai": "dang_xu_ly"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Cập nhật đề xuất thành công",
  "data": {
    "trang_thai": "dang_xu_ly"
  }
}
```

---

### **Kịch bản 6: KIỂM TRA QUYỀN DUYỆT SAI**

#### Request 11: User 2582 cố duyệt khi đang ở cấp 1
```http
POST {{baseUrl}}/duyet/102
Authorization: Bearer <token_user_2582>
Content-Type: application/json
```

**Body:**
```json
{
  "da_duyet": 1,
  "ly_do": "Test"
}
```

**Response mẫu:**
```json
{
  "status": false,
  "message": "Bạn không có quyền duyệt đề xuất này hoặc chưa đến lượt duyệt của bạn"
}
```

---

#### Request 12: User 2580 duyệt 2 lần (đã duyệt rồi)
```http
POST {{baseUrl}}/duyet/101
Authorization: Bearer <token_user_2580>
Content-Type: application/json
```

**Body:**
```json
{
  "da_duyet": 1,
  "ly_do": "Duyệt lại"
}
```

**Response mẫu:**
```json
{
  "status": false,
  "message": "Bạn không có quyền duyệt đề xuất này hoặc chưa đến lượt duyệt của bạn"
}
```

---

## III. LẤY DANH SÁCH ĐỀ XUẤT

### Request 13: Lấy danh sách tất cả
```http
GET {{baseUrl}}/?tab=all&start=0&length=10
Authorization: Bearer <token_user_2585>
```

**Response mẫu:**
```json
{
  "status": true,
  "message": "Lấy danh sách đề xuất thành công",
  "data": [
    {
      "id_de_xuat": 101,
      "tieu_de": "Đề xuất mua 5 máy tính Dell Precision",
      "trang_thai": "da_duyet",
      "created_at": "2026-01-28 10:00:00",
      "so_luong_binh_luan": 0
    },
    {
      "id_de_xuat": 102,
      "tieu_de": "Đề xuất mua máy photocopy",
      "trang_thai": "tu_choi",
      "created_at": "2026-01-28 11:00:00",
      "so_luong_binh_luan": 0
    }
  ],
  "recordsTotal": 2,
  "recordsFiltered": 2
}
```

---

### Request 14: Lấy danh sách đã gửi
```http
GET {{baseUrl}}/?tab=da_gui&start=0&length=10
Authorization: Bearer <token_user_2585>
```

---

### Request 15: Lấy danh sách nháp
```http
GET {{baseUrl}}/?tab=nhap&start=0&length=10
Authorization: Bearer <token_user_2585>
```

---

### Request 16: Lọc theo trạng thái
```http
GET {{baseUrl}}/?searchKey[trang_thai]=dang_xu_ly&start=0&length=10
Authorization: Bearer <token_user_2585>
```

---

## IV. CHI TIẾT ĐỀ XUẤT

### Request 17: Xem chi tiết đề xuất
```http
GET {{baseUrl}}/detail/101
Authorization: Bearer <token_user_2585>
```

**Response mẫu:**
```json
{
  "status": true,
  "message": "Lấy chi tiết đề xuất thành công",
  "data": {
    "id_de_xuat": 101,
    "tieu_de": "Đề xuất mua 5 máy tính Dell Precision",
    "noi_dung": "Cần mua 5 máy tính cấu hình cao...",
    "trang_thai": "da_duyet",
    "ten_loai_de_xuat": "Mua sắm tài sản",
    "created_at": "2026-01-28 10:00:00",
    "nguoi_tao": "Nguyễn Văn A",
    "nguoi_duyet": [
      {
        "id_nguoi_duyet": 2580,
        "ten_nguoi_duyet": "Trần Văn B",
        "cap_duyet": 1,
        "da_duyet": 1,
        "ly_do": "Đồng ý, thiết bị cần thiết cho công việc",
        "thoi_gian_duyet": "2026-01-28 10:30:00"
      },
      {
        "id_nguoi_duyet": 2581,
        "ten_nguoi_duyet": "Lê Thị C",
        "cap_duyet": 1,
        "da_duyet": 1,
        "ly_do": "Đồng ý",
        "thoi_gian_duyet": "2026-01-28 10:35:00"
      },
      {
        "id_nguoi_duyet": 2582,
        "ten_nguoi_duyet": "Phạm Văn D",
        "cap_duyet": 2,
        "da_duyet": 1,
        "ly_do": "Đã kiểm tra ngân sách, đồng ý phê duyệt",
        "thoi_gian_duyet": "2026-01-28 11:00:00"
      },
      {
        "id_nguoi_duyet": 2585,
        "ten_nguoi_duyet": "Hoàng Văn E",
        "cap_duyet": 3,
        "da_duyet": 1,
        "ly_do": "Phê duyệt cuối cùng",
        "thoi_gian_duyet": "2026-01-28 14:00:00"
      }
    ],
    "binh_luan": [],
    "file_dinh_kem": []
  }
}
```

---

## V. CHECKLIST TEST

### ✅ Test Cases cần chạy:

- [ ] **TC1:** Tạo đề xuất nháp (nhap = 1) → `trang_thai = 'nhap'`
- [ ] **TC2:** Gửi đề xuất (nhap = 0) → `trang_thai = 'dang_xu_ly'`, tạo người duyệt cấp 1
- [ ] **TC3:** User cấp 1 duyệt (1/2) → vẫn `trang_thai = 'dang_xu_ly'`, chờ người khác
- [ ] **TC4:** User cấp 1 duyệt (2/2) → tự động tạo cấp 2
- [ ] **TC5:** User cấp 2 duyệt → tự động tạo cấp 3
- [ ] **TC6:** User cấp 3 duyệt → `trang_thai = 'da_duyet'` (hoàn tất), không tạo cấp 4
- [ ] **TC7:** User cấp 2 từ chối → `trang_thai = 'tu_choi'`, xóa cấp > 1, reset cấp 1
- [ ] **TC8:** User không có quyền duyệt → 403 Forbidden
- [ ] **TC9:** User duyệt sai cấp (cấp 3 duyệt khi đang cấp 1) → 403 Forbidden
- [ ] **TC10:** User duyệt 2 lần → 403 Forbidden
- [ ] **TC11:** Lấy danh sách theo tab (all, da_gui, nhap)
- [ ] **TC12:** Lọc theo trang_thai

---

---

### **Kịch bản 7: CẬP NHẬT ĐỀ XUẤT (THAY ĐỔI NGƯỜI DUYỆT)**

#### Request: Cập nhật thông tin và người duyệt
```http
POST {{baseUrl}}/update/{{id_de_xuat}}
Authorization: Bearer <token_user_tao>
Content-Type: application/json
```

**Body:**
```json
{
  "tieu_de": "5Tan",
  "noi_dung": "Nội dung test",
  "id_dx_loai_de_xuat": 1,
  "nhap": 0,
  "danh_sach_don_vi": [
    {
      "cap": 0,
      "id_don_vi": 40,
      "ids_nguoi_duyet": [46, 627]
    },
    {
      "cap": 1,
      "id_don_vi": 15,
      "ids_nguoi_duyet": [2509, 639]
    },
    {
      "cap": 1,
      "id_don_vi": 16,
      "ids_nguoi_duyet": [2566]
    }
  ]
}
```

**Response mẫu:**
```json
{
    "status": true,
    "message": "Cập nhật đề xuất thành công",
    "data": {
        "id_de_xuat": 101,
        "trang_thai": "dang_xu_ly"
    }
}
```

## VI. QUERIES HỖ TRỢ DEBUG

```sql
-- Xem trạng thái đề xuất
SELECT id_de_xuat, tieu_de, trang_thai, nhap FROM dx_de_xuat ORDER BY id_de_xuat DESC;

-- Xem lịch sử duyệt
SELECT 
    nd.id_de_xuat,
    nd.id_nguoi_duyet,
    nd.cap_duyet,
    nd.da_duyet,
    nd.ly_do,
    nd.thoi_gian_duyet,
    nd.created_at
FROM dx_nguoi_duyet_de_xuat nd
WHERE nd.id_de_xuat = 101
ORDER BY nd.cap_duyet, nd.id_nguoi_duyet;

-- Xem cấp duyệt hiện tại
SELECT MIN(cap_duyet) as cap_hien_tai
FROM dx_nguoi_duyet_de_xuat
WHERE id_de_xuat = 101 AND (da_duyet IS NULL OR da_duyet = 0);

-- Kiểm tra cấu hình routing
SELECT * FROM dx_loai_de_xuat_don_vi 
WHERE id_dx_loai_de_xuat = 1 
ORDER BY thu_tu;

-- Reset toàn bộ để test lại
DELETE FROM dx_nguoi_duyet_de_xuat WHERE id_de_xuat >= 101;
DELETE FROM dx_de_xuat WHERE id_de_xuat >= 101;
```

---

## VII. LƯU Ý QUAN TRỌNG

### 🔴 **Các lỗi thường gặp:**

1. **"Chưa đến lượt duyệt của bạn"**
   - Nguyên nhân: Đang duyệt sai cấp (VD: cấp 3 duyệt khi đang cấp 1)
   - Giải pháp: Kiểm tra `getCapDuyetHienTai()` trả về đúng cấp

2. **"Không có người duyệt cấp tiếp theo"**
   - Nguyên nhân: Chưa cấu hình routing trong `dx_loai_de_xuat_don_vi`
   - Giải pháp: Thêm record với `thu_tu` tăng dần

3. **Transaction bị rollback**
   - Nguyên nhân: Lỗi trong quá trình thêm người duyệt cấp tiếp theo
   - Giải pháp: Check log database, kiểm tra FK constraints

---

## VIII. KẾT QUẢ MONG ĐỢI

### **Trạng thái đề xuất theo flow:**

```
[Tạo mới]
nhap
  ↓ (gửi đề xuất)
dang_xu_ly (cấp 1)
  ↓ (cấp 1 duyệt hết)
dang_xu_ly (cấp 2)
  ↓ (cấp 2 duyệt)
dang_xu_ly (cấp 3)
  ↓ (cấp 3 duyệt - cấp cuối)
da_duyet ✅ (hoàn tất)

[Nếu từ chối ở bất kỳ cấp nào]
tu_choi ❌ → Reset về cấp 1
```

### **Database sau khi hoàn tất (đề xuất 101):**

| Bảng | Dữ liệu mong đợi |
|------|------------------|
| **dx_de_xuat** | trang_thai = 'da_duyet' |
| **dx_nguoi_duyet_de_xuat** | 4 records (2 cấp 1, 1 cấp 2, 1 cấp 3) - tất cả `da_duyet = 1` |

---

**🎯 LOGIC ĐÃ OK! Sẵn sàng test!** 🚀
