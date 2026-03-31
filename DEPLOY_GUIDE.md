# Hướng dẫn Deploy AI Assistant lên Render (Dùng Docker)

Tài liệu này hướng dẫn Duy cách triển khai trợ lý ảo lên Render.com sử dụng Docker để hỗ trợ đầy đủ tính năng Real-time (Reverb) và Queue.

## 1. Chuẩn bị trên Render
1. Đăng ký tài khoản tại [Render.com](https://render.com).
2. Tạo một **Web Service** mới.
3. Kết nối với repository GitHub của Duy.
4. Chọn **Runtime** là **Docker**.

## 2. Cấu hình Biến môi trường (Environment Variables)
Duy cần copy cấu hình từ file `.env` vào phần **Environment** trên Render:

| Key | Value (Ví dụ) |
| :--- | :--- |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | `base64:xxx...` (Copy từ .env) |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | `aws-1-ap-northeast-1.pooler.supabase.com` |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | `postgres` |
| `DB_USERNAME` | `postgres.ptbgfnsdheytlmeqcgyl` |
| `DB_PASSWORD` | `hovanduy2005` |
| `GEMINI_API_KEY` | (Khóa Gemini của Duy) |
| `BROADCAST_CONNECTION` | `reverb` |
| `REVERB_APP_ID` | `849301` |
| `REVERB_APP_KEY` | `qyassistantkey` |
| `REVERB_APP_SECRET` | `qyassistantsecret` |
| `REVERB_HOST` | (Tên miền Render của Duy - ví dụ: `qy-assistant.onrender.com`) |
| `REVERB_PORT` | `443` (Nếu dùng HTTPS trên Render) |
| `REVERB_SCHEME` | `https` |

## 3. Lưu ý Quan trọng
*   **Port**: Render sẽ tự động phát hiện cổng 80 từ Dockerfile.
*   **Database**: Vì Duy đã dùng Supabase (Cloud DB), nên container này sẽ tự động kết nối được ngay mà không cần cài thêm DB trong Docker.
*   **Real-time**: Khi deploy xong, Duy cần cập nhật `REVERB_HOST` trong Render Environment thành tên miền thật mà Render cấp cho Duy.

## 4. Cách chạy cục bộ với Docker (Để test)
Nếu Duy cài Docker trên máy:
```powershell
docker build -t qy-assistant .
docker run -p 8000:80 -p 8080:8080 qy-assistant
```

Duy chỉ cần đẩy code (có file Dockerfile và thư mục docker vừa tạo) lên GitHub, Render sẽ tự động build và chạy trợ lý ảo của Duy! 🚀
