# 📚 HƯỚNG DẪN LOGIC HỆ THỐNG ĐĂNG KÝ NGOÀI GIỜ

> **File:** `application/controllers/api/v2/admin/hrm/Ngoaigio.php`
> **Ngày cập nhật:** 08/04/2026

---

## 📋 MỤC LỤC

1. [Tổng quan hệ thống](#1-tổng-quan-hệ-thống)
2. [Bảng phân quyền](#2-bảng-phân-quyền)
3. [Chi tiết từng API](#3-chi-tiết-từng-api)
4. [Flow nghiệp vụ](#4-flow-nghiệp-vụ)
5. [Bảng dữ liệu](#5-bảng-dữ-liệu)

---

## 1. TỔNG QUAN HỆ THỐNG

### 🎯 Mục đích

Quản lý đăng ký, duyệt và theo dõi giờ làm ngoài giờ của nhân viên.

### 🔑 Quyền cần thiết

| Quyền                    | Mô tả                                                    |
| ------------------------- | ---------------------------------------------------------- |
| `ngoaigio.duyet`        | Duyệt đơn ngoài giờ (dành cho lãnh đạo đơn vị) |
| `ngoaigio.duyetdotxuat` | Duyệt đơn đột xuất (dành cho TCHC)                  |
| `ngoaigio.xemtatca`     | Xem tất cả đơn (không giới hạn đơn vị)           |

### 📊 Luồng duyệt

```
Nhân viên tạo đơn 
  ↓
Kiểm tra bảng chấm công (Mở/Khóa)
  ↓
├─ Bảng MỞ: Lãnh đạo đơn vị duyệt
└─ Bảng KHÓA: 
   ├─ Nhân viên: ❌ Không được tạo
   └─ Lãnh đạo: ✅ Tạo được → TCHC duyệt
  ↓
Hoàn thành (1 cấp duyệt)
```

---

## 2. BẢNG PHÂN QUYỀN

| STT                                      | API                  | Method | Endpoint                                  | Quyền cần thiết                                                                    | Vai trò                                            |
| ---------------------------------------- | -------------------- | ------ | ----------------------------------------- | ------------------------------------------------------------------------------------- | --------------------------------------------------- |
| **A. QUẢN LÝ ĐƠN NGOÀI GIỜ** |                      |        |                                           |                                                                                       |                                                     |
| 1                                        | Danh sách đơn     | GET    | `/ngoaigio/index`                       | -                                                                                     | Mọi người (chỉ xem đơn của mình/phòng ban) |
| 2                                        | Tạo đơn           | POST   | `/ngoaigio/create`                      | -                                                                                     | Mọi người                                        |
| 3                                        | Duyệt đơn         | POST   | `/ngoaigio/approve`                     | `ngoaigio.duyet` + Là lãnh đạo đơn vị`<br>`HOẶC `ngoaigio.duyetdotxuat` | Lãnh đạo / TCHC                                  |
| 4                                        | Cập nhật đơn     | POST   | `/ngoaigio/update`                      | -                                                                                     | Người tạo đơn (khi đơn đang Chờ duyệt)    |
| 5                                        | Xóa đơn           | POST   | `/ngoaigio/delete/:id`                  | -                                                                                     | Người tạo đơn (khi đơn đang Chờ duyệt)    |
| 6                                        | Chi tiết đơn      | GET    | `/ngoaigio/detail/:id`                  | -                                                                                     | Mọi người                                        |
| **B. QUẢN LÝ KỲ CHẤM CÔNG**   |                      |        |                                           |                                                                                       |                                                     |
| 7                                        | DS kỳ chấm công   | GET    | `/ngoaigio/bang_cham_cong`              | -                                                                                     | Mọi người                                        |
| 8                                        | Tạo kỳ             | POST   | `/ngoaigio/bang_cham_cong_create`       | -                                                                                     | TCHC                                                |
| 9                                        | Cập nhật kỳ       | POST   | `/ngoaigio/bang_cham_cong_update`       | -                                                                                     | TCHC                                                |
| 10                                       | Khóa/Mở kỳ        | POST   | `/ngoaigio/bang_cham_cong_khoa`         | -                                                                                     | TCHC                                                |
| 11                                       | Duyệt kỳ           | POST   | `/ngoaigio/bang_cham_cong_duyet`        | -                                                                                     | TCHC                                                |
| 12                                       | Xóa kỳ             | POST   | `/ngoaigio/bang_cham_cong_delete`       | -                                                                                     | TCHC                                                |
| 13                                       | Khóa ngày cụ thể | POST   | `/ngoaigio/bang_cham_cong_lock_dates`   | -                                                                                     | TCHC                                                |
| 14                                       | Mở khóa ngày      | POST   | `/ngoaigio/bang_cham_cong_unlock_dates` | -                                                                                     | TCHC                                                |
| **C. TIỆN ÍCH**                  |                      |        |                                           |                                                                                       |                                                     |
| 15                                       | DS ngày lễ         | GET    | `/ngoaigio/ngay_le`                     | -                                                                                     | Mọi người                                        |
| 16                                       | Thống kê giờ      | GET    | `/ngoaigio/tong_gio_theo_loai_ngay`     | -                                                                                     | Mọi người                                        |
| 17                                       | Xuất Excel          | POST   | `/ngoaigio/export_excel`                | -                                                                                     | Mọi người                                        |

---

## 3. CHI TIẾT TỪNG API

### 📌 A. QUẢN LÝ ĐƠN NGOÀI GIỜ

#### **1. index_get** - Danh sách đơn ngoài giờ

**Endpoint:** `GET /api/v2/admin/hrm/ngoaigio/index`

**Quyền:** Không yêu cầu quyền đặc biệt

**Logic:**

1. Lấy thông tin user hiện tại
2. Xác định `id_nhan_vien`, `id_don_vi`, `ma_don_vi`
3. Gọi model `getAll()` để lọc dữ liệu
4. Trả về danh sách đơn (có phân trang)

**Phạm vi xem:**

- Nhân viên: Chỉ xem đơn của mình
- Lãnh đạo: Xem đơn của phòng ban
- TCHC có quyền `ngoaigio.xemtatca`: Xem tất cả

**Input:**

```json
{
  "start": 0,
  "length": 10,
  "searchValue": "string",
  "searchKey": {
    "id_don_vi": "number",
    "trang_thai": "Cho_duyet|Da_duyet|Tu_choi"
  }
}
```

---

#### **2. create_post** - Tạo đơn ngoài giờ

**Endpoint:** `POST /api/v2/admin/hrm/ngoaigio/create`

**Quyền:** Không yêu cầu quyền (mọi người đều tạo được)

**Logic:**

1. **Nhận dữ liệu:** `id_nhan_vien[]`, `data[]` (danh sách ngày đăng ký)
2. **Loop qua từng nhân viên:**
   - Lấy `id_don_vi_cong_tac`
   - Kiểm tra người tạo có phải lãnh đạo không
   - Lấy danh sách lãnh đạo đơn vị
3. **Loop qua từng ngày đăng ký:**
   - **Validation:**
     - Tìm bảng chấm công chứa ngày đăng ký
     - Kiểm tra bảng có bị khóa không
     - Kiểm tra ngày có trong `locked_dates` không
     - Kiểm tra trùng giờ
   - **Xử lý đặc biệt:** Nếu lãnh đạo tạo đơn trên bảng khóa
     - Set `canBypassLockedDates = true`
     - Người duyệt = TCHC có quyền `ngoaigio.duyetdotxuat`
   - **Trường hợp bình thường:**
     - Người duyệt = Danh sách lãnh đạo đơn vị
4. **Insert:**
   - Bảng `hrm_ngoai_gio`: Đơn chính
   - Bảng `hrm_ngoai_gio_nguoi_duyet`: Danh sách người duyệt với `cap_duyet = 1`

**Input:**

```json
{
  "id_nhan_vien": [1, 2, 3],
  "data": [
    {
      "ngay_dang_ky": "2026-04-10",
      "gio_bat_dau": "17:30:00",
      "gio_ket_thuc": "21:30:00",
      "so_gio": 4,
      "noi_dung": "Hỗ trợ sự kiện"
    }
  ]
}
```

**Output:**

```json
{
  "success": true,
  "message": "Đăng ký ngoài giờ thành công cho 3 nhân viên, tổng 3 đơn",
  "data": {
    "ids": [101, 102, 103],
    "total": 3,
    "employees_count": 3
  }
}
```

**Các trường hợp đặc biệt:**

| Tình huống                                    | Người tạo | Bảng chấm công | Người duyệt             | Kết quả       |
| ----------------------------------------------- | ------------ | ----------------- | -------------------------- | --------------- |
| **1. Đăng ký bình thường**          | Nhân viên  | MỞ               | Lãnh đạo đơn vị      | ✅ Thành công |
| **2. Đăng ký trên bảng khóa**       | Nhân viên  | KHÓA             | -                          | ❌ Bị chặn    |
| **3. Đăng ký đột xuất**             | Lãnh đạo  | KHÓA             | TCHC (quyền duyetdotxuat) | ✅ Thành công |
| **4. Đăng ký trên ngày khóa**       | Nhân viên  | locked_dates      | -                          | ❌ Bị chặn    |
| **5. Đăng ký đột xuất ngày khóa** | Lãnh đạo  | locked_dates      | TCHC                       | ✅ Thành công |

---

#### **3. approve_post** - Duyệt/Từ chối đơn

**Endpoint:** `POST /api/v2/admin/hrm/ngoaigio/approve`

**Quyền:**

- **Đơn bình thường:** `ngoaigio.duyet` + Phải là lãnh đạo đơn vị
- **Đơn đột xuất:** `ngoaigio.duyetdotxuat`

**Logic:**

1. **Nhận dữ liệu:** `ids_ngoai_gio[]`, `hanh_dong` (duyet/tu_choi), `ly_do`
2. **Loop qua từng đơn:**
   - Kiểm tra đơn tồn tại
   - Kiểm tra trạng thái = "Cho_duyet"
   - Lấy thông tin nhân viên và đơn vị
   - **Kiểm tra quyền:**
     ```
     (Là lãnh đạo đơn vị AND có quyền ngoaigio.duyet)
     OR
     (Có quyền ngoaigio.duyetdotxuat)
     ```
   - **Cập nhật bảng `hrm_ngoai_gio_nguoi_duyet`:**
     - Nếu đã có record → Update trạng thái
     - Nếu chưa có → Insert mới (trường hợp TCHC duyệt đơn đột xuất)
   - **Cập nhật bảng `hrm_ngoai_gio`:**
     - Duyệt → `trang_thai_tong = 'Da_duyet'`
     - Từ chối → `trang_thai_tong = 'Tu_choi'`

**Input:**

```json
{
  "ids_ngoai_gio": [101, 102, 103],
  "hanh_dong": "duyet",
  "ly_do": "Đồng ý"
}
```

**Output:**

```json
{
  "success": true,
  "message": "Hoàn tất quá trình phê duyệt",
  "data": {
    "success_count": 2,
    "fail_count": 1,
    "details": [
      {"id": 101, "success": true, "message": "Xử lý thành công"},
      {"id": 102, "success": true, "message": "Xử lý thành công"},
      {"id": 103, "success": false, "message": "Bạn không phải là lãnh đạo đơn vị này"}
    ]
  }
}
```

**Message lỗi có thể gặp:**

- `"Bạn không phải là lãnh đạo đơn vị này"` - Không có trong bảng `e_lanh_dao_don_vi`
- `"Bạn không có quyền ngoaigio.duyet"` - Thiếu quyền
- `"Đơn đã được xử lý kết quả cuối cùng"` - Đơn không còn ở trạng thái Chờ duyệt

---

#### **4. update_post** - Cập nhật đơn

**Endpoint:** `POST /api/v2/admin/hrm/ngoaigio/update`

**Quyền:** Chỉ người tạo đơn

**Điều kiện:**

- Đơn phải ở trạng thái `Cho_duyet`
- `created_user_id` = user hiện tại

**Logic:**

1. Kiểm tra đơn tồn tại
2. Kiểm tra quyền (người tạo)
3. Kiểm tra trạng thái
4. Kiểm tra trùng giờ (nếu sửa giờ)
5. Update các trường: `ngay_dang_ky`, `gio_bat_dau`, `gio_ket_thuc`, `so_gio`, `noi_dung`

**Input:**

```json
{
  "id_ngoai_gio": 101,
  "gio_bat_dau": "18:00:00",
  "gio_ket_thuc": "22:00:00",
  "so_gio": 4
}
```

---

#### **5. delete_post** - Xóa đơn

**Endpoint:** `POST /api/v2/admin/hrm/ngoaigio/delete/:id`

**Quyền:** Chỉ người tạo đơn

**Điều kiện:** Giống `update_post`

**Logic:**

1. Soft delete đơn chính (`deleted_at`, `deleted_user_id`)
2. Hard delete bảng `hrm_ngoai_gio_nguoi_duyet`

---

#### **6. detail_get** - Chi tiết đơn

**Endpoint:** `GET /api/v2/admin/hrm/ngoaigio/detail/:id`

**Quyền:** Không yêu cầu

**Logic:**

1. Join nhiều bảng:
   - `hrm_ngoai_gio`
   - `hrm_nhan_vien`
   - `e_don_vi`
   - `hrm_api_cham_cong` (lấy giờ chấm công thực tế)
2. Lấy danh sách người duyệt từ `hrm_ngoai_gio_nguoi_duyet`
3. Encrypt avatar

**Output:**

```json
{
  "id_ngoai_gio": 101,
  "ho_va_ten": "Nguyễn Văn A",
  "ten_don_vi": "Phòng CNTT",
  "ngay_dang_ky": "2026-04-10",
  "gio_bat_dau": "17:30:00",
  "gio_ket_thuc": "21:30:00",
  "so_gio": 4,
  "trang_thai_tong": "Cho_duyet",
  "thoi_gian_bat_dau_cham_cong": "17:28:00",
  "thoi_gian_ket_thuc_cham_cong": "21:35:00",
  "danh_sach_nguoi_duyet": [
    {
      "cap_duyet": 1,
      "ql_nguoi_dung_ho_ten": "Trần Văn B",
      "ten_don_vi": "Phòng CNTT",
      "trang_thai": "Cho_duyet",
      "thoi_gian_duyet": null,
      "ly_do_duyet": null
    }
  ]
}
```

---

### 📌 B. QUẢN LÝ KỲ CHẤM CÔNG

#### **7. bang_cham_cong_get** - Danh sách kỳ

**Endpoint:** `GET /api/v2/admin/hrm/ngoaigio/bang_cham_cong`

**Input:**

```json
{
  "start": 0,
  "length": 10,
  "searchValue": "Tháng 4",
  "trang_thai": "MO|KHOA|DA_DUYET|BI_TU_CHOI",
  "thang": "2026-04"
}
```

#### **8. bang_cham_cong_create_post** - Tạo kỳ mới

**Endpoint:** `POST /api/v2/admin/hrm/ngoaigio/bang_cham_cong_create`

**Logic:**

1. Kiểm tra trùng kỳ
2. Insert với `trang_thai = 'MO'`

**Input:**

```json
{
  "thang": "2026-04",
  "ngay_bat_dau": "2026-04-01",
  "ngay_ket_thuc": "2026-04-30",
  "ten_bang": "Bảng chấm công tháng 4/2026",
  "ghi_chu": "Ghi chú"
}
```

#### **9. bang_cham_cong_khoa_post** - Khóa/Mở kỳ

**Endpoint:** `POST /api/v2/admin/hrm/ngoaigio/bang_cham_cong_khoa`

**Input:**

```json
{
  "id": 1,
  "trang_thai": "KHOA"
}
```

**Trạng thái:**

- `MO` - Mở (cho phép tạo đơn bình thường)
- `KHOA` - Khóa (chỉ lãnh đạo mới tạo được)

#### **10. bang_cham_cong_lock_dates_post** - Khóa ngày cụ thể

**Endpoint:** `POST /api/v2/admin/hrm/ngoaigio/bang_cham_cong_lock_dates`

**Logic:**

1. Lấy `locked_dates` hiện tại (JSON array)
2. Thêm khoảng thời gian mới
3. Update lại

**Input:**

```json
{
  "id": 1,
  "start_date": "2026-04-15",
  "end_date": "2026-04-20"
}
```

**Cấu trúc `locked_dates`:**

```json
[
  {
    "start": "2026-04-15",
    "end": "2026-04-20",
    "locked_by": 123,
    "locked_at": "2026-04-08 10:00:00"
  }
]
```

#### **11. bang_cham_cong_unlock_dates_post** - Mở khóa ngày

**Endpoint:** `POST /api/v2/admin/hrm/ngoaigio/bang_cham_cong_unlock_dates`

**Input:**

```json
{
  "id": 1,
  "index": 0
}
```

---

### 📌 C. TIỆN ÍCH

#### **15. ngay_le_get** - Danh sách ngày lễ

**Endpoint:** `GET /api/v2/admin/hrm/ngoaigio/ngay_le?year=2026`

**Output:**

```json
{
  "2026-01-01": {
    "ten": "Tết Dương lịch",
    "mo_ta": "Nghỉ 1 ngày",
    "la_nghi_buoi": false
  },
  "2026-04-30": {
    "ten": "30/4",
    "mo_ta": "Giải phóng miền Nam",
    "la_nghi_buoi": false
  }
}
```

#### **16. tong_gio_theo_loai_ngay_get** - Thống kê giờ

**Endpoint:** `GET /api/v2/admin/hrm/ngoaigio/tong_gio_theo_loai_ngay`

**Logic:**

- Gọi model `getTotalHoursByDayType()`
- Tính tổng giờ theo loại ngày: Ngày thường / Cuối tuần / Ngày lễ

**Input:**

```json
{
  "start_date": "2026-04-01",
  "end_date": "2026-04-30",
  "id_nhan_vien": 1,
  "id_don_vi": 5
}
```

**Output:**

```json
{
  "data": [
    {
      "id_nhan_vien": 1,
      "ho_va_ten": "Nguyễn Văn A",
      "gio_ngay_thuong": 20,
      "gio_cuoi_tuan": 8,
      "gio_ngay_le": 4,
      "tong_gio": 32
    }
  ]
}
```

#### **17. export_excel_post** - Xuất báo cáo Excel

**Endpoint:** `POST /api/v2/admin/hrm/ngoaigio/export_excel`

**Logic:**

1. Nhận `searchKey` với `id_bang_cham_cong[]`
2. Loop qua từng bảng chấm công
3. Tạo sheet Excel cho mỗi bảng
4. Export file

**Input:**

```json
{
  "searchKey": {
    "id_bang_cham_cong": [1, 2],
    "id_don_vi": 5
  }
}
```

---

## 4. FLOW NGHIỆP VỤ

### 🔄 Flow 1: Đăng ký ngoài giờ bình thường

```
1. Nhân viên chọn ngày và giờ làm ngoài giờ
   ↓
2. Hệ thống kiểm tra:
   ✅ Ngày thuộc kỳ chấm công nào
   ✅ Kỳ có đang mở không
   ✅ Ngày có bị khóa không
   ✅ Giờ có trùng với đơn khác không
   ↓
3. Tạo đơn thành công
   ├─ Insert hrm_ngoai_gio
   └─ Insert hrm_ngoai_gio_nguoi_duyet (danh sách lãnh đạo)
   ↓
4. Lãnh đạo nhận thông báo
   ↓
5. Lãnh đạo duyệt
   ├─ Kiểm tra: Có phải lãnh đạo đơn vị + có quyền ngoaigio.duyet
   ├─ Update hrm_ngoai_gio_nguoi_duyet (tracking)
   └─ Update hrm_ngoai_gio: trang_thai_tong = 'Da_duyet'
   ↓
6. Hoàn thành
```

### 🔄 Flow 2: Đăng ký đột xuất (Lãnh đạo tạo trên bảng khóa)

```
1. Lãnh đạo đăng ký cho nhân viên trên bảng chấm công đã khóa
   ↓
2. Hệ thống kiểm tra:
   ✅ Người tạo có phải lãnh đạo không
   ✅ Bảng chấm công đã khóa
   ↓
3. Set canBypassLockedDates = true
   ↓
4. Tạo đơn thành công
   ├─ Insert hrm_ngoai_gio
   └─ Insert hrm_ngoai_gio_nguoi_duyet (TCHC có quyền duyetdotxuat)
   ↓
5. TCHC nhận thông báo
   ↓
6. TCHC duyệt
   ├─ Kiểm tra: có quyền ngoaigio.duyetdotxuat
   ├─ Insert hrm_ngoai_gio_nguoi_duyet (nếu chưa có record)
   └─ Update hrm_ngoai_gio: trang_thai_tong = 'Da_duyet'
   ↓
7. Hoàn thành
```

### 🔄 Flow 3: Quản lý kỳ chấm công

```
1. TCHC tạo kỳ chấm công mới (tháng)
   ↓
2. Kỳ ở trạng thái MỞ → Nhân viên đăng ký bình thường
   ↓
3. TCHC khóa một số ngày cụ thể (locked_dates)
   ↓
4. Ngày bị khóa → Chỉ lãnh đạo mới tạo được (đơn đột xuất)
   ↓
5. Cuối kỳ: TCHC khóa toàn bộ kỳ (trang_thai = KHOA)
   ↓
6. Kỳ bị khóa → Nhân viên không tạo được nữa
   ↓
7. TCHC duyệt kỳ (trang_thai = DA_DUYET)
   ↓
8. Hoàn thành
```

---

## 5. BẢNG DỮ LIỆU

### 📊 hrm_ngoai_gio (Đơn ngoài giờ)

```sql
id_ngoai_gio            INT PRIMARY KEY
id_nhan_vien            INT NOT NULL
id_bang_cham_cong       INT NOT NULL -- FK → hrm_bang_cham_cong_thang
ngay_dang_ky            DATE NOT NULL
gio_bat_dau             TIME NOT NULL
gio_ket_thuc            TIME NOT NULL
noi_dung                TEXT
so_gio                  FLOAT
trang_thai_tong         VARCHAR(50) -- 'Cho_duyet', 'Da_duyet', 'Tu_choi'
cap_duyet_hien_tai      INT DEFAULT 1
tong_so_cap_duyet       INT DEFAULT 1
created_user_id         INT
created_at              DATETIME
updated_at              DATETIME
deleted_at              DATETIME
```

### 📊 hrm_ngoai_gio_nguoi_duyet (Tracking người duyệt)

```sql
id_ngoai_gio_nguoi_duyet  INT PRIMARY KEY
id_ngoai_gio              INT NOT NULL
cap_duyet                 INT NOT NULL -- Luôn = 1
id_nguoi_duyet            INT NOT NULL
trang_thai                VARCHAR(50) -- 'Cho_duyet', 'Da_duyet', 'Tu_choi'
thoi_gian_duyet           DATETIME NULL
ly_do_duyet               TEXT NULL
duyet_ho                  TINYINT DEFAULT 0
id_duyet_ho               INT NULL
created_at                DATETIME
updated_at                DATETIME
```

### 📊 hrm_bang_cham_cong_thang (Kỳ chấm công)

```sql
id                INT PRIMARY KEY
thang             VARCHAR(10) -- '2026-04'
ngay_bat_dau      DATE
ngay_ket_thuc     DATE
ten_bang          VARCHAR(255)
trang_thai        VARCHAR(50) -- 'MO', 'KHOA', 'DA_DUYET', 'BI_TU_CHOI'
locked_dates      JSON -- [{"start":"2026-04-15","end":"2026-04-20","locked_by":123}]
ghi_chu           TEXT
nguoi_duyet       INT
ngay_duyet        DATETIME
ly_do_tu_choi     TEXT
created_user_id   INT
updated_user_id   INT
deleted_at        DATETIME
```

### 📊 e_lanh_dao_don_vi (Lãnh đạo đơn vị)

```sql
id                    INT PRIMARY KEY
id_don_vi             INT NOT NULL
ql_nguoi_dung_id      INT NOT NULL
deleted_at            DATETIME
```

---

## 6. CÁC TRƯỜNG HỢP ĐẶC BIỆT

### ❓ Trường hợp 1: Nhiều lãnh đạo cùng đơn vị, ai duyệt trước?

**Trả lời:** Bất kỳ lãnh đạo nào duyệt trước cũng được. Chỉ cần 1 lãnh đạo duyệt là đơn hoàn tất.

### ❓ Trường hợp 2: Lãnh đạo tạo đơn cho chính mình?

**Trả lời:**

- Nếu bảng MỞ → Người duyệt vẫn là lãnh đạo khác trong đơn vị
- Nếu bảng KHÓA → Người duyệt là TCHC

### ❓ Trường hợp 3: TCHC có thể duyệt đơn bình thường không?

**Trả lời:** Có, nếu TCHC có quyền `ngoaigio.duyetdotxuat`

### ❓ Trường hợp 4: Nhân viên có thể sửa/xóa đơn đã duyệt không?

**Trả lời:** Không. Chỉ sửa/xóa được khi đơn ở trạng thái "Chờ duyệt"

### ❓ Trường hợp 5: Có thể xóa kỳ chấm công đã có đơn không?

**Trả lời:** Không. Hệ thống sẽ kiểm tra và chặn nếu có đơn liên kết

---

## 7. CHECKLIST KIỂM TRA HỆ THỐNG

### ✅ Checklist Tạo đơn

- [ ] Kiểm tra ngày thuộc kỳ chấm công
- [ ] Kiểm tra kỳ có đang mở không
- [ ] Kiểm tra ngày có bị khóa không
- [ ] Kiểm tra trùng giờ với đơn khác
- [ ] Xác định đúng người duyệt (Lãnh đạo / TCHC)
- [ ] Insert đầy đủ 2 bảng

### ✅ Checklist Duyệt đơn

- [ ] Kiểm tra quyền `ngoaigio.duyet` + Là lãnh đạo đơn vị
- [ ] HOẶC quyền `ngoaigio.duyetdotxuat`
- [ ] Update bảng tracking
- [ ] Update trạng thái tổng đơn
- [ ] Ghi log

### ✅ Checklist Khóa kỳ

- [ ] Kiểm tra có đơn đang Chờ duyệt không
- [ ] Thông báo cho nhân viên trước khi khóa
- [ ] Chỉ TCHC mới thực hiện được

---

## 📞 LIÊN HỆ HỖ TRỢ

- **Phòng TCHC** - Quản lý kỳ chấm công, duyệt đơn đột xuất
- **Phòng CNTT** - Hỗ trợ kỹ thuật, phân quyền

---

*Tài liệu này được cập nhật lần cuối: 08/04/2026*
