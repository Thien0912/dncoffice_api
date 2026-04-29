# Hướng dẫn sử dụng API Send-To-Client

## Mô tả

API cho phép các hệ thống bên ngoài gửi dữ liệu trực tiếp tới một client đang kết nối WebSocket thông qua mã định danh (`clientCode`). Hỗ trợ đa hệ thống — mỗi lần gọi có thể chỉ định hệ thống nguồn (`endpoint`) để client biết dữ liệu đến từ đâu.

---

## 1. Luồng hoạt động

```
Hệ thống bên ngoài                  Socket Server                    Client (Browser)
       │                                  │                                │
       │  POST /ws/notify/send-to-client  │                                │
       │ ──────────────────────────────▶  │                                │
       │                                  │   WebSocket message            │
       │                                  │ ─────────────────────────────▶ │
       │                                  │   (event: "direct_data")       │
       │  ◀── JSON response (success)     │                                │
```

## 2. Yêu cầu phía Client (Frontend)

Client cần kết nối WebSocket và đăng ký mã định danh trước khi có thể nhận dữ liệu.

### Bước 1: Kết nối WebSocket

```javascript
const ws = new WebSocket('wss://dkxettuyen.nctu.edu.vn/ws');

ws.onopen = () => {
    console.log('Đã kết nối WebSocket');
};
```

### Bước 2: Đăng ký mã định danh (clientCode)

```javascript
ws.onopen = () => {
    // clientCode là mã duy nhất để định danh client (ví dụ: mã hồ sơ, mã user...)
    ws.send(JSON.stringify({
        action: 'register',
        clientCode: 'MA_DINH_DANH_CUA_BAN'
    }));
};
```

### Bước 3: Lắng nghe dữ liệu

```javascript
ws.onmessage = (event) => {
    const data = JSON.parse(event.data);

    if (data.event === 'direct_data') {
        console.log('Dữ liệu từ hệ thống:', data.endpoint);
        console.log('Nội dung:', data.payload);

        // Xử lý theo endpoint
        switch (data.endpoint) {
            case 'dkxettuyen.nctu.edu.vn':
                // Xử lý dữ liệu từ hệ thống ĐKXT
                break;
            case 'myoffice.nctu.edu.vn':
                // Xử lý dữ liệu từ hệ thống MyOffice
                break;
            default:
                console.log('Endpoint không xác định:', data.endpoint);
        }
    }
};
```

---

## 3. API gửi dữ liệu tới Client

### Endpoint

```
POST /ws/notify/send-to-client
```

### Headers

```
Content-Type: application/json
```

### Request Body

| Field        | Kiểu     | Bắt buộc | Mô tả                                                                 |
|-------------|----------|----------|------------------------------------------------------------------------|
| `clientCode` | `string` | ✅ Có    | Mã định danh của client cần gửi tới                                    |
| `payload`    | `object` | ✅ Có    | Dữ liệu JSON cần gửi cho client                                       |
| `endpoint`   | `string` | ❌ Không | Tên hệ thống gửi. Mặc định: `dkxettuyen.nctu.edu.vn` nếu không truyền |

### Ví dụ gọi API

#### Từ hệ thống ĐKXT (mặc định, không cần truyền endpoint)

```bash
curl -X POST https://dkxettuyen.nctu.edu.vn/ws/notify/send-to-client \
  -H "Content-Type: application/json" \
  -d '{
    "clientCode": "HS-2026-001",
    "payload": {
      "type": "status_update",
      "message": "Hồ sơ của bạn đã được duyệt",
      "status": "approved"
    }
  }'
```

#### Từ hệ thống MyOffice (truyền endpoint)

```bash
curl -X POST https://dkxettuyen.nctu.edu.vn/ws/notify/send-to-client \
  -H "Content-Type: application/json" \
  -d '{
    "clientCode": "USER-42",
    "payload": {
      "type": "task_assigned",
      "message": "Bạn được giao một công việc mới",
      "taskId": "TASK-100"
    },
    "endpoint": "myoffice.nctu.edu.vn"
  }'
```

#### Gọi từ code (Node.js / PHP / bất kỳ backend nào)

```javascript
// Node.js example
const response = await fetch('https://dkxettuyen.nctu.edu.vn/ws/notify/send-to-client', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        clientCode: 'USER-42',
        payload: { type: 'notification', message: 'Xin chào!' },
        endpoint: 'myoffice.nctu.edu.vn'
    })
});
```

```php
// PHP example
$data = [
    'clientCode' => 'USER-42',
    'payload' => ['type' => 'notification', 'message' => 'Xin chào!'],
    'endpoint' => 'myoffice.nctu.edu.vn'
];

$ch = curl_init('https://dkxettuyen.nctu.edu.vn/ws/notify/send-to-client');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
curl_close($ch);
```

---

## 4. Response

### Thành công (200)

```json
{
  "status": "success",
  "message": "Đã gửi dữ liệu thành công tới client: HS-2026-001"
}
```

### Client không online (404)

```json
{
  "status": "error",
  "message": "Không tìm thấy client nào đang online với mã: HS-2026-001"
}
```

### Thiếu dữ liệu (400)

```json
{
  "status": "error",
  "message": "Thiếu clientCode hoặc dữ liệu payload."
}
```

---

## 5. Cấu trúc WebSocket Message mà Client nhận được

```json
{
  "event": "direct_data",
  "endpoint": "myoffice.nctu.edu.vn",
  "timestamp": "2026-03-16T02:20:00.000Z",
  "payload": {
    "type": "task_assigned",
    "message": "Bạn được giao một công việc mới",
    "taskId": "TASK-100"
  }
}
```

| Field       | Mô tả                                           |
|------------|--------------------------------------------------|
| `event`     | Luôn là `"direct_data"` cho loại message này     |
| `endpoint`  | Tên hệ thống gốc đã gửi dữ liệu                |
| `timestamp` | Thời gian gửi (ISO 8601)                         |
| `payload`   | Dữ liệu gốc mà hệ thống bên ngoài đã truyền vào |

---

## 6. Các endpoint hiện hỗ trợ

| Endpoint                  | Hệ thống             |
|--------------------------|----------------------|
| `dkxettuyen.nctu.edu.vn` | Đăng ký xét tuyển    |
| `myoffice.nctu.edu.vn`   | MyOffice             |

> **Lưu ý**: Có thể mở rộng thêm bất kỳ endpoint nào mà không cần sửa code. Chỉ cần truyền tên endpoint mới khi gọi API.
