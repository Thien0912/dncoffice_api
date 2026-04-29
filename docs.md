# Tài liệu Cập nhật: Hệ thống Ghi Log Lịch Sử Hồ Sơ Nhân Sự (HRM Audit History)

## 1. Mô tả ngắn gọn
Hệ thống ghi nhận lịch sử thay đổi trong Hồ sơ nhân sự đã được nâng cấp đồng bộ. Thay vì lưu dạng chuỗi văn bản thuần trong bảng `ql_nhat_ky` gây khó khăn cho việc truy xuất và hiển thị, lịch sử thay đổi hiện tại được cấu trúc hóa dưới dạng JSON và lưu vào bảng riêng biệt `hrm_nhan_vien_lich_su`.

Cấu trúc mới hỗ trợ lưu trữ chi tiết từng trường dữ liệu thay đổi, bao gồm cả giá trị cũ (old_value) và giá trị mới (new_value), hỗ trợ hiển thị giao diện timeline (Dòng thời gian) rất thân thiện và rõ ràng trên Frontend ứng dụng.

## 2. Thay đổi cốt lõi (Core/Base)
- **Tệp chỉnh sửa:** `application/libraries/REST_INSTANCE_Controller.php`
- **Mô tả:** Bổ sung phương thức helper `$this->logEmployeeHistory($id_nhan_vien, $action, $changes_array)`. Hàm này sẽ tự động chuyển mảng các thay đổi thành định dạng JSON và chèn vào bảng `hrm_nhan_vien_lich_su`.

## 3. Các Mô-đun (Controllers) đã được tích hợp
Tính năng theo dõi (tracking) chi tiết từng trường (field-level) đã được áp dụng vào toàn bộ nhóm các chức năng CRUD (`create`, `update`, `delete`, `deletes`) của các Controller sau trong thư mục `v2/admin/hrm/`:

1.  `Minhchung.php` (Minh chứng, hồ sơ đính kèm)
2.  `Thongtingiadinh.php` (Thông tin gia đình)
3.  `Bangcap.php` (Bằng cấp)
4.  `Kinhnghiemlamviec.php` (Kinh nghiệm làm việc)
5.  `Chungchi.php` (Chứng chỉ)
6.  `Daotao.php` (Quá trình đào tạo)
7.  `Khenthuong.php` (Khen thưởng)
8.  `Danhgia.php` (Đánh giá nhân viên)
9.  `Hopdong.php` (Hợp đồng lao động)
10. `Quatrinhcongtac.php` (Quá trình công tác)
11. `Thoiviec.php` (Thủ tục thôi việc)

## 4. Quản lý Giao dịch (Transaction)
- Mọi tác vụ ghi log mới đều được đặt gọn và đồng bộ bên trong các khối `$this->db->trans_start()` ... `$this->db->trans_commit()` của Database CI3. 
- Nhờ vậy, nếu xảy ra lỗi trong quá trình thực thi DB, phần log cũng sẽ tự động được rollback, giúp đảm bảo tính toàn vẹn dữ liệu.

## 5. Cấu trúc lưu trữ Log định dạng JSON (Ví dụ)
Một mảng các trường thay đổi (field changes array) sẽ có định dạng cơ bản như sau trong database:

```json
[
  {
    "field": "id_chuc_vu",
    "field_name": "Chức vụ",
    "old_value": {
      "value": "1",
      "label": "Nhân viên IT"
    },
    "new_value": {
      "value": "2",
      "label": "Trưởng phòng IT"
    }
  }
]
```
*(Các tham chiếu ID khóa ngoại đều được tự động truy xuất tên hiển thị - label để lưu thành String text tiện cho việc xuất UI mà không cần join DB lại lần nữa).*

## 6. Các Sửa lỗi Giao diện (Frontend Fixes)
- **Select "Loại hợp đồng" (`FormHopdong.tsx`)**: Đã cập nhật cơ chế lấy dữ liệu từ `onChange` đơn giản thành `onSelectionChange` cùng cấu trúc `Set` chuẩn của `HeroUI v3 / React Aria`. Điều này khắc phục dứt điểm tình trạng click chọn option nhưng UI không bắt/hiển thị được data (Uncontrolled trigger mismatch).
- **Date Input "Ngày cấp chứng chỉ" (`DateInputFloatingLabel.tsx`)**: Đã bổ sung cơ chế kiểm tra và loại trừ các giá trị ngày lỗi từ Database cũ (vd: `0000-00-00`). Đồng thời, cải thiện cơ chế Parse Date của component bằng cách thêm bước xử lý dự phòng (fallback) qua `moment()`, trước lúc truyền cho component `DatePicker` mặc định của `@internationalized/date`. Qua đó giúp ô Input Date luôn khả dụng và không bị lỗi hiển thị.
