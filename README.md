## 🚀 Giới thiệu dự án

**PetCare** là một website quản lý phòng khám thú y được xây dựng bằng **PHP + MySQL + RESTful API**.  
Phần nội dung kỹ thuật (tên file, class, function, API endpoint) được giữ **tiếng Anh chuẩn code**, còn phần giải thích sẽ dùng **tiếng Việt** để dễ hiểu.

- **Tech stack (English)**: PHP 7.4+, MySQL 5.7+, Apache (mod_rewrite), HTML5, CSS3, JavaScript  
- **Use cases (Giá trị sử dụng)**: Đặt lịch khám, xem bác sĩ, xem dịch vụ, đọc blog, gửi liên hệ, quản lý lịch hẹn/khách hàng qua API.

---

## 🧱 Kiến trúc tổng quan

- **Frontend pages**: các trang PHP kết hợp HTML/CSS (`index.php`, `services.php`, `doctors.php`, `blog-list.php`, `contact.php`, thư mục `user/`, `staff/`...).  
- **Backend API**: các endpoint REST trong thư mục `api/` (`bookings.php`, `doctors.php`, `services.php`, `blogs.php`, `contacts.php`, `testimonials.php`) trả về JSON.  
- **Database layer**: cấu hình kết nối trong `config/db.php` + schema trong `database/schema.sql`.  
- **Shared layout**: `includes/header.php` và `includes/footer.php` dùng lại trên hầu hết các trang.

---

## 🔗 Sơ đồ ERD (Cơ sở dữ liệu dạng flowchart)

Sơ đồ dưới đây vẽ **luồng quan hệ** giữa các bảng chính theo kiểu flowchart/diagram ASCII.

```text
                   +-------------------+
                   |      USERS        |
                   |-------------------|
                   | id (PK)           |
                   | username, email   |
                   +---------+---------+
                             |
                             | 1 - n  (một user có nhiều booking)
                             v
+-------------------+    +-------------------+    +-------------------+
|     DOCTORS       |    |     BOOKINGS      |    |     SERVICES      |
|-------------------|    |-------------------|    |-------------------|
| id (PK)           |    | id (PK)           |    | id (PK)           |
| name, specialty   |    | user_id (FK)      |    | name, price, ...  |
+---------+---------+    | doctor_id (FK)    |    +---------+---------+
          |              | service_id (FK)   |              |
          | 1 - n        | pet_name, ...     |        1 - n |
          +------------->+ status, timeslot  +<-------------+
                         +---------+---------+
                                   |
                                   | 1 - n (một booking có thể sinh contact / testimonial)
                  +----------------+------------------+
                  |                                   |
        +---------v---------+               +---------v---------+
        |     CONTACTS      |               |   TESTIMONIALS    |
        |-------------------|               |-------------------|
        | id (PK)           |               | id (PK)           |
        | name, email, ...  |               | customer_name,... |
        +-------------------+               +-------------------+


  Quan hệ N - N giữa DOCTORS và SERVICES
  --------------------------------------

        +-----------+          +-------------------+          +-----------+
        | DOCTORS   |  1   n   |  DOCTOR_SERVICES  |   n   1  | SERVICES  |
        |-----------|----------|-------------------|----------|-----------|
        | id (PK)   |          | id (PK)           |          | id (PK)   |
        +-----------+          | doctor_id (FK)    |          +-----------+
                               | service_id (FK)   |
                               +-------------------+


  Bảng BLOGS (nội dung)
  ---------------------

        +-------------------+
        |      BLOGS        |
        |-------------------|
        | id (PK)           |
        | title, slug       |
        | content, image    |
        | status, views ... |
        +-------------------+
```

**Tóm tắt ý nghĩa:**
- `USERS` (hộp trên cùng) kết nối xuống `BOOKINGS`: mỗi user có thể có nhiều lịch hẹn.  
- `BOOKINGS` đứng giữa, liên kết tới `DOCTORS` và `SERVICES` (bác sĩ phụ trách và dịch vụ được đặt).  
- `DOCTOR_SERVICES` là bảng trung gian tạo quan hệ **N-N** giữa bác sĩ và dịch vụ.  
- `CONTACTS` và `TESTIMONIALS` là các luồng thông tin/feedback phát sinh từ khách hàng, gắn logic với user/booking ở tầng nghiệp vụ (dù DB không có FK trực tiếp tới `BOOKINGS`).  
- `BLOGS` là khối nội dung bài viết, tách riêng, không ràng buộc khóa ngoại với các bảng còn lại.

---

## 🗂️ Cấu trúc thư mục (Folder structure)

Các tên thư mục/file là **tiếng Anh chuẩn code**, mô tả bên cạnh là **tiếng Việt**, vẽ dạng cây từ gốc project:

```text
./                        # Root project PetCare
├── api/                  # Tầng REST API (trả JSON)
│   ├── index.php         # API router - nhận /api/* và điều hướng
│   ├── config.php        # Cấu hình API: header JSON, CORS, parse URL, body
│   ├── bookings.php      # Endpoint CRUD cho bảng bookings
│   ├── doctors.php       # Endpoint cho danh sách bác sĩ
│   ├── services.php      # Endpoint cho danh sách dịch vụ
│   ├── blogs.php         # Endpoint cho blog
│   ├── contacts.php      # Endpoint cho liên hệ
│   └── testimonials.php  # Endpoint cho đánh giá khách hàng
│
├── assets/               # Tài nguyên tĩnh (static assets)
│   ├── css/
│   │   ├── style.css     # CSS giao diện chính
│   │   └── staff-style.css # CSS cho khu vực staff
│   └── img/              # Ảnh banner, dịch vụ, bác sĩ, gallery...
│
├── config/
│   └── db.php            # Kết nối MySQL + tính BASE_URL toàn site
│
├── database/
│   └── schema.sql        # Lược đồ CSDL + dữ liệu mẫu (doctors, services, blogs...)
│
├── includes/
│   ├── header.php        # Header + navbar, xử lý session user, link điều hướng
│   └── footer.php        # Footer dùng chung
│
├── js/
│   └── script.js         # JS chung cho frontend (hiệu ứng, xử lý nhẹ phía client)
│
├── service-list/         # Các trang chi tiết từng nhóm dịch vụ
│   ├── kham.php          # Gói khám tổng quát / chẩn đoán
│   ├── tiem.php          # Dịch vụ tiêm phòng
│   ├── phauthuat.php     # Dịch vụ phẫu thuật
│   ├── spa.php           # Spa & grooming
│   ├── hotel.php         # Pet hotel
│   └── shop.php          # Cửa hàng / pet shop
│
├── user/                 # Khu vực dành cho khách hàng đã đăng nhập
│   ├── index-guest.php   # Trang giới thiệu/landing cho khách
│   ├── booking.php       # Form đặt lịch khám gắn với user hiện tại
│   ├── history.php       # Lịch sử đặt lịch của user (JOIN bảng bookings)
│   ├── profile.php       # Quản lý thông tin tài khoản + đổi mật khẩu
│   ├── login.php         # Đăng nhập user
│   └── logout.php        # Đăng xuất user
│
├── staff/                # Khu vực nội bộ dành cho bác sĩ / nhân viên
│   ├── login.php         # Đăng nhập staff (dựa trên bảng doctors)
│   ├── dashboard.php     # Tổng quan số liệu, lịch hẹn, thông tin nhanh
│   ├── schedule.php      # Lịch làm việc / danh sách ca khám
│   ├── emr-list.php      # Danh sách hồ sơ bệnh án (electronic medical record)
│   └── medical-record.php# Chi tiết một hồ sơ bệnh án
│
├── WebAdmin/             # Template HTML tĩnh cho trang admin (demo UI)
│   ├── index.html        # Dashboard admin demo
│   ├── accounts.html     # (tuỳ chỉnh) Quản lý tài khoản
│   ├── doctors.html      # (tuỳ chỉnh) Quản lý bác sĩ
│   ├── services.html     # (tuỳ chỉnh) Quản lý dịch vụ
│   └── ...               # Các file HTML demo khác
│
├── index.php             # Trang chủ website
├── services.php          # Trang danh sách dịch vụ chính
├── doctors.php           # Trang danh sách bác sĩ
├── blog-list.php         # Trang liệt kê blog
├── blog.php              # Trang chi tiết một bài blog
├── contact.php           # Trang liên hệ
├── feedback.php          # Trang xem/gửi đánh giá
├── introduce.php         # Trang giới thiệu phòng khám
└── README.md             # Tài liệu dự án (file bạn đang đọc)
```

---

## 🛠️ Yêu cầu hệ thống (System requirements)

- **PHP**: 7.4 trở lên (khuyên dùng 8.x nếu hosting hỗ trợ).  
- **MySQL / MariaDB**: MySQL 5.7+ hoặc MariaDB tương đương.  
- **Web server**: Apache với `mod_rewrite` bật; hoặc Nginx (tự cấu hình tương đương).  
- **PHP extensions**: `mysqli`, `json`, `mbstring`.

---

## ⚙️ Cài đặt & chạy dự án (Setup & run)

### 1. Clone hoặc copy source vào `htdocs`

```bash
git clone <repository-url>
cd Trung-Tam-Thu-Y.github.io-main
```

Đặt thư mục project vào `C:\xampp\htdocs\` (hoặc thư mục webroot tương ứng trên server).

### 2. Tạo database & import schema

Trong MySQL:

```sql
CREATE DATABASE petcare_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Sau đó import file `database/schema.sql`:

```bash
mysql -u root -p petcare_db < database/schema.sql
```

Hoặc dùng phpMyAdmin và import file `schema.sql` bằng giao diện.

### 3. Cấu hình kết nối DB (`config/db.php`)

Trong file `config/db.php`, đảm bảo các hằng số kết nối đúng với môi trường local/server:

```php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'petcare_db');
```

File này cũng tự động tính toán `BASE_URL` theo `DOCUMENT_ROOT`, nên khi bạn đổi tên thư mục project trong `htdocs`, link trong navbar sẽ tự cập nhật.

### 4. Chạy trên localhost

Mở trình duyệt và truy cập:

- `http://localhost/<ten-thu-muc-project>/index.php` – Trang chủ  
- `http://localhost/<ten-thu-muc-project>/staff/login.php` – Đăng nhập staff  
- `http://localhost/<ten-thu-muc-project>/user/login.php` – Đăng nhập khách hàng

---

## 🌐 API chính (Main REST API)

**Base URL (tuỳ môi trường):**

```text
http://localhost/<ten-thu-muc-project>/api/
```

- **Bookings**
  - `GET /api/bookings` – Lấy tất cả lịch đặt
  - `GET /api/bookings/{id}` – Lấy chi tiết một lịch đặt
  - `POST /api/bookings` – Tạo lịch đặt mới
  - `PUT /api/bookings/{id}` – Cập nhật lịch đặt
  - `DELETE /api/bookings/{id}` – Xoá lịch đặt  
  - Query hỗ trợ: `?status=pending|confirmed|completed|cancelled`, `?date=YYYY-MM-DD`

- **Doctors**
  - `GET /api/doctors` – Lấy tất cả bác sĩ  
  - `GET /api/doctors/{id}` – Lấy chi tiết 1 bác sĩ + dịch vụ liên quan  
  - `GET /api/doctors?service_id={id}` – Lọc bác sĩ theo dịch vụ

- **Services**
  - `GET /api/services` – Lấy tất cả dịch vụ  
  - `GET /api/services/{id}` – Lấy chi tiết 1 dịch vụ + bác sĩ

- **Blogs**
  - `GET /api/blogs` – Danh sách bài viết đã publish  
  - `GET /api/blogs/{id}` – Chi tiết bài viết, tự tăng `views`
  - Query: `?limit=10`, `?offset=0`

- **Contacts**
  - `POST /api/contacts` – Gửi form liên hệ  
  - `GET /api/contacts` – Lấy danh sách liên hệ (dùng cho admin)

- **Testimonials**
  - `GET /api/testimonials` – Lấy các đánh giá đã được duyệt  
  - `GET /api/testimonials/{id}` – Xem chi tiết 1 đánh giá  
  - `POST /api/testimonials` – Gửi đánh giá mới (ở trạng thái pending)

**Định dạng response chuẩn (JSON, English keys – Vietnamese meaning):**

```json
{
  "success": true,
  "message": "Optional message",
  "data": { }
}
```

```json
{
  "success": false,
  "error": "Error message"
}
```

---

## 🧪 Ví dụ sử dụng API (Examples)

### Tạo booking mới bằng JavaScript

```javascript
fetch('/api/bookings', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    fullname: 'Nguyễn Văn A',
    phone: '0123456789',
    email: 'example@email.com',
    pet_name: 'Miu',
    pet_type: 'Mèo',
    service_id: 1,
    doctor_id: 1,
    appointment_date: '2025-12-25',
    appointment_time: '10:00',
    note: 'Thú cưng sợ tiêm'
  })
})
  .then(res => res.json())
  .then(console.log);
```

### Lấy danh sách bác sĩ theo dịch vụ

```javascript
fetch('/api/doctors?service_id=1')
  .then(res => res.json())
  .then(console.log);
```

---

## 🧯 Xử lý lỗi thường gặp (Troubleshooting)

- **Không kết nối được database**  
  - Kiểm tra lại `DB_USER`, `DB_PASS`, `DB_HOST`, `DB_NAME` trong `config/db.php`.  
  - Đảm bảo MySQL/MariaDB đang chạy.  
  - Kiểm tra port, nếu không phải 3306 thì cần chỉnh lại khi tạo `mysqli`.

- **Gọi API trả về 404 / "Endpoint not found"**  
  - Kiểm tra URL: phải dạng `/api/bookings`, `/api/doctors`, ...  
  - Với Apache: đảm bảo `.htaccess` hoạt động và `mod_rewrite` đã bật.  
  - Nếu deploy dưới subfolder, cần cấu hình `DocumentRoot` / `Alias` đúng để router `api/config.php` parse được đường dẫn thực tế.

- **Link navbar bị lệch (nhân đôi path)**  
  - Thường do project nằm trong nhiều lớp thư mục; phiên bản mới đã dùng `BASE_URL` tự tính.  
  - Nếu vẫn lỗi, in ra `$_SERVER['DOCUMENT_ROOT']` và `__DIR__` để kiểm tra đường dẫn thực tế.

---

## 🧾 Ghi chú thay đổi chính (Commit notes)

- `includes/header.php`  
  - Thiết kế lại navbar 3 vùng: **Logo** (trái), **Menu chính** (giữa), **Search + User actions** (phải).  
  - Logic hiển thị:  
    - Chưa đăng nhập: ô tìm kiếm + nút **Đăng nhập / Đăng ký**.  
    - Đã đăng nhập: ô tìm kiếm + các link **Lịch sử**, **Tài khoản**, **Đăng xuất**.  
- `config/db.php`  
  - Thêm `session_start()` an toàn (chỉ chạy khi chưa có session).  
  - Tự động tính `BASE_URL` theo `DOCUMENT_ROOT` nên khi đổi tên thư mục trong `htdocs` navbar vẫn hoạt động.  
- `user/login.php`  
  - Gộp flow **Đăng nhập / Đăng ký** trong một UI.  
  - Thêm link **“Quên mật khẩu?”** dẫn tới `user/forgot_password.php`.  
- `user/forgot_password.php`  
  - Cho phép nhập email đã đăng ký → tạo **mật khẩu tạm** (hash vào DB) và hiển thị ra màn hình để user đăng nhập lại rồi đổi mật khẩu ở trang profile.  
- `user/history.php`, `user/profile.php`, `introduce.php`, các trang trong `service-list/`  
  - Loại bỏ các `session_start()` thừa (đã được xử lý trong `config/db.php`) để tránh notice.  
  - Sửa CSS `.container` bị override cục bộ làm vỡ layout header; tách thành class riêng cho từng trang.  
- `feedback.php`  
  - Nâng cấp UI form gửi đánh giá + nút CTA “Gửi Phản Hồi Của Bạn” và phần animation sao.  
- `index.php` & `assets/css/style.css`  
  - Thêm countdown **2 giờ** cho section “SẮP KẾT THÚC!”, dùng JS cập nhật `HH:MM:SS` mỗi giây.  
  - Sửa overlay chữ “LAST” để không che lên con số thời gian.

## 📝 Ghi chú phát triển (Developer notes)

- Code base ưu tiên **simple PHP + mysqli**, không dùng framework để dễ deploy trên shared hosting.  
- Khi mở rộng tính năng (VD: phân quyền admin chi tiết hơn), nên tách thêm bảng `roles`, `permissions` và bổ sung middleware kiểm tra session/role cho folder `staff/`.  
- Nên bổ sung thêm migration hoặc tool CLI riêng để cập nhật DB thay vì chỉnh thẳng `schema.sql` trên production.

