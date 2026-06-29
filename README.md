# 🌿 Plantify Co

Website thương mại điện tử và giới thiệu dịch vụ cây xanh nội thất, xây dựng trên PHP thuần theo mô hình MVC.

---

## Mục lục

- [Giới thiệu](#giới-thiệu)
- [Tính năng](#tính-năng)
- [Công nghệ sử dụng](#công-nghệ-sử-dụng)
- [Cấu trúc thư mục](#cấu-trúc-thư-mục)
- [Cài đặt](#cài-đặt)
- [Tài khoản mặc định](#tài-khoản-mặc-định)
- [Luồng hoạt động](#luồng-hoạt-động)
- [API Chatbot](#api-chatbot)

---

## Giới thiệu

Plantify Co là website giới thiệu và bán cây cảnh nội thất, hỗ trợ đầy đủ quy trình từ duyệt sản phẩm, đặt hàng đến quản trị nội dung. Hệ thống có hai phân hệ chính: **giao diện khách hàng** và **bảng điều khiển admin**.

---

## Tính năng

### Khách hàng (Frontend)

| Phân hệ | Mô tả |
|---|---|
| Trang chủ | Sản phẩm nổi bật, giới thiệu thương hiệu, CTA |
| Cửa hàng | Lọc theo danh mục, sắp xếp theo giá, tìm kiếm, phân trang |
| Chi tiết sản phẩm | Thêm vào giỏ, sản phẩm liên quan |
| Giỏ hàng | Tăng/giảm số lượng, xóa sản phẩm, đặt hàng qua modal |
| Tin tức | Danh sách bài viết, tìm kiếm, xem chi tiết, bình luận |
| FAQ | Tìm kiếm, lọc theo nhóm, tích hợp chatbot AI |
| Giới thiệu | Hero video HLS, timeline quy trình, bản đồ nhúng |
| Liên hệ | Form gửi tin nhắn, validation client + server |

### Thành viên (Dashboard)

- Cập nhật hồ sơ, đổi avatar (lưu vào `storage/`)
- Đổi mật khẩu
- Xem lịch sử đơn hàng và chi tiết từng đơn

### Quản trị (Admin)

- **Tổng quan:** thống kê nhanh, biểu đồ Chart.js, danh sách đơn hàng gần nhất
- **Sản phẩm:** CRUD, upload ảnh, đánh dấu nổi bật
- **Đơn hàng:** danh sách, chi tiết, cập nhật trạng thái
- **Tin tức:** CRUD, auto-slug tiếng Việt, upload thumbnail
- **Bình luận:** duyệt/ẩn/xóa, phân trang, tìm kiếm
- **Liên hệ:** đánh dấu đã đọc, tìm kiếm, lọc theo trạng thái
- **FAQ:** thêm/sửa/xóa, kéo thả sắp xếp (AJAX)
- **Nội dung trang:** chỉnh văn bản tĩnh, upload ảnh giới thiệu, upload video HLS
- **Cấu hình cửa hàng:** chỉnh nhãn, placeholder, tiêu đề theo nhóm
- **Thành viên:** khoá/mở, reset mật khẩu, xóa tài khoản

---

## Công nghệ sử dụng

| Thành phần | Chi tiết |
|---|---|
| Backend | PHP 8.x, PDO (MySQL), Session |
| Frontend | Bootstrap 5.3, Font Awesome 6, AOS.js |
| Video | HLS.js (phát `.m3u8`) |
| Admin UI | SRTDash template, Chart.js, simple-datatables |
| Database | MySQL / MariaDB |
| Chatbot | Python RAG server tại `http://127.0.0.1:1884/chat` (tùy chọn) |

---

## Cấu trúc thư mục

```
plantify/
├── app/
│   ├── Controllers/        # Xử lý request (MVC Controller)
│   │   ├── AdminController.php
│   │   ├── AuthController.php
│   │   ├── CartController.php
│   │   ├── DashboardController.php
│   │   ├── HomeController.php
│   │   ├── NewsController.php
│   │   ├── ShopController.php
│   │   └── ...
│   ├── Core/               # Lõi framework
│   │   ├── Auth.php        # Middleware xác thực
│   │   ├── BaseController.php
│   │   ├── Bootstrap.php   # Khai báo hằng số, autoload
│   │   ├── Database.php    # PDO Singleton
│   │   ├── Env.php         # Đọc file .env
│   │   └── Helpers.php     # Hàm tiện ích (e, asset, app_url...)
│   ├── Models/             # Tương tác database
│   │   ├── Comment.php
│   │   ├── Content.php
│   │   ├── Data.php
│   │   ├── News.php
│   │   ├── Order.php
│   │   ├── Product.php
│   │   └── User.php
│   └── Views/              # Giao diện (PHP template)
│       ├── admin/          # Giao diện admin (SRTDash)
│       ├── auth/           # Đăng nhập, đăng ký
│       ├── dashboard/      # Trang thành viên
│       ├── news/           # Tin tức
│       ├── pages/          # Trang công khai (home, shop, cart...)
│       └── partials/       # Header, footer dùng chung
├── public/                 # Document root của web server
│   ├── assets/
│   │   ├── css/            # style.css, news.css, admin-srtdash.css
│   │   ├── js/             # main.js
│   │   ├── images/
│   │   ├── uploads/        # Ảnh sản phẩm, tin tức, trang
│   │   ├── videos/         # File .m3u8 + .ts (HLS)
│   │   └── vendor/srtdash/ # Thư viện admin
│   └── index.php           # Entry point + Router
├── storage/
│   └── uploads/avatars/    # Avatar thành viên (ngoài public)
├── database/
│   └── schema.sql          # Cấu trúc và dữ liệu mẫu
└── .env                    # Biến môi trường (không commit)
```

---

## Cài đặt

### Yêu cầu

- PHP >= 8.0 với extension `pdo_mysql`, `fileinfo`, `mbstring`
- MySQL >= 5.7 hoặc MariaDB >= 10.3
- Web server Apache (mod_rewrite) hoặc Nginx

### Các bước

**1. Clone repository**

```bash
git clone https://github.com/your-username/plantify.git
cd plantify
```

**2. Tạo file `.env`** tại thư mục gốc

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=plantify
DB_USERNAME=root
DB_PASSWORD=

APP_URL=http://localhost/plantify/public
```

**3. Import database**

```bash
mysql -u root -p < database/schema.sql
```

Hoặc mở phpMyAdmin và import file `database/schema.sql`.

**4. Cấu hình web server**

Trỏ document root về thư mục `public/`. Nếu dùng XAMPP, đặt toàn bộ dự án vào `htdocs/plantify` rồi truy cập `http://localhost/plantify/public`.

Đảm bảo `mod_rewrite` được bật và file `.htaccess` trong `public/` được phép hoạt động (`AllowOverride All`).

**5. Phân quyền thư mục**

```bash
chmod -R 755 public/assets/uploads
chmod -R 755 storage
```

**6. Truy cập website**

```
http://localhost/plantify/public
```

---

## Tài khoản mặc định

| Vai trò | Username | Password |
|---|---|---|
| Admin | `admin` | `123456` |
| Thành viên | `thanhvien` | `123456` |

> Đổi mật khẩu ngay sau khi triển khai lên môi trường production.

---

## Luồng hoạt động

```
Request → public/index.php
              │
              ▼
         Router (phân tích URI)
              │
              ├─ /admin/*       → AdminController    (yêu cầu role=admin)
              ├─ /auth/*        → AuthController
              ├─ /dashboard/*   → DashboardController (yêu cầu đăng nhập)
              ├─ /shop/*        → ShopController
              ├─ /news/*        → NewsController
              ├─ /cart/*        → CartController
              └─ /contact, /about, /faq → Controller tương ứng
                                    │
                                    ▼
                              Model (PDO)  ←→  MySQL
                                    │
                                    ▼
                               View (.php)  →  HTML Response
```

### Phân quyền

| Trạng thái | Quyền truy cập |
|---|---|
| Khách (guest) | Xem trang công khai, tin tức, sản phẩm, FAQ, liên hệ |
| Thành viên | Thêm vào giỏ hàng, đặt hàng, bình luận, quản lý hồ sơ |
| Admin | Toàn bộ chức năng + bảng quản trị |

### Giỏ hàng & Đặt hàng

Giỏ hàng được lưu trong `$_SESSION['cart']`. Khi checkout, giá sản phẩm được lấy lại từ database (không tin vào dữ liệu client) trước khi tạo đơn hàng bằng transaction SQL.

### Bình luận

Bình luận mới có trạng thái `pending` (chờ duyệt). Admin có thể duyệt (`approved`) hoặc ẩn (`hidden`). Chỉ bình luận `approved` hiển thị ngoài website.

---

## API Chatbot

Widget chatbot trên trang FAQ gửi POST request đến server RAG nội bộ:

```
POST http://127.0.0.1:1884/chat
Content-Type: application/json

{ "question": "Plantify có khảo sát trực tiếp không?" }
```

Nếu server không chạy, widget hiển thị thông báo lỗi kết nối — website vẫn hoạt động bình thường.

---

## Ghi chú bảo mật

- Mật khẩu được hash bằng `password_hash()` với `PASSWORD_DEFAULT`
- Tất cả truy vấn SQL dùng PDO Prepared Statements
- Dữ liệu đầu vào được escape bằng `htmlspecialchars()` trước khi hiển thị
- File avatar lưu ngoài `public/` (`storage/uploads/avatars/`), truy cập qua `FileController`
- Tài khoản bị khoá (`status=locked`) bị chặn ngay khi đăng nhập