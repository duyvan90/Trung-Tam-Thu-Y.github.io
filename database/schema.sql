-- PetCare Database Schema
-- Create database (if not exists)
CREATE DATABASE IF NOT EXISTS petcare_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE petcare_db;

-- Table: doctors
CREATE TABLE IF NOT EXISTS doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    degree VARCHAR(255),
    specialty TEXT,
    description TEXT,
    image VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(20),
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: services
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    short_description VARCHAR(500),
    description TEXT,
    image VARCHAR(255),
    price DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: doctor_services (Many-to-Many relationship)
CREATE TABLE IF NOT EXISTS doctor_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    service_id INT NOT NULL,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    UNIQUE KEY unique_doctor_service (doctor_id, service_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: bookings (appointments)
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255),
    user_id INT NULL,
    pet_name VARCHAR(255) NOT NULL,
    pet_type VARCHAR(50) NOT NULL,
    service_id INT,
    doctor_id INT,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    note TEXT,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: blogs
CREATE TABLE IF NOT EXISTS blogs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(500) NOT NULL,
    slug VARCHAR(500) UNIQUE,
    content TEXT NOT NULL,
    image VARCHAR(255),
    excerpt TEXT,
    author VARCHAR(255),
    published_at DATE,
    status ENUM('draft', 'published') DEFAULT 'published',
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: contacts (contact form submissions)
CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(255),
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: testimonials
CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    rating INT DEFAULT 5 CHECK (rating >= 1 AND rating <= 5),
    status ENUM('pending', 'approved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data for doctors (bao gồm tài khoản đăng nhập staff)
-- Password for all authorized accounts: 123456
-- Hash generated with: password_hash('123456', PASSWORD_DEFAULT)
INSERT INTO doctors (name, degree, specialty, description, image, email, phone, username, password) VALUES
('BS. Đào Văn Duy', 'Thạc sĩ Thú Y (M.V.Sc)', 'Chuyên khoa: Khám tổng quát, Điều trị bệnh nội khoa', 
 'BS. Duy là chuyên gia về khám sức khỏe định kỳ và chẩn đoán, điều trị các bệnh lý nội khoa phổ biến như tiêu hóa, hô hấp, da liễu.', 
 'assets/img/doctors/doctor-duy.jpg', 'duy@petcare.vn', '0901000001', 'bsduy', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('BS. Nguyễn Diễm Thùy', 'Bác sĩ Thú Y (D.V.M)', 'Chuyên khoa: Tiêm phòng, Tẩy giun & Khám tổng quát', 
 'BS. Thùy là người phụ trách chính chương trình Tiêm phòng và Tẩy giun tại PetCare.', 
 'assets/img/doctors/doctor-thuy.jpg', 'thuy@petcare.vn', '0901000002', 'bsthuy', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('BS. Trần Ti Na', 'Thạc sĩ Thú Y (D.V.M)', 'Chuyên khoa: Xét nghiệm, Chẩn đoán & Bệnh phức tạp', 
 'BS. Ti Na chuyên sâu về phân tích kết quả Xét nghiệm và chẩn đoán bệnh lý phức tạp ở chó, mèo.', 
 'assets/img/doctors/doctor-tina.jpg', 'tina@petcare.vn', '0901000003', 'bstina', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('BS. Phan Thành Đức Nhân', 'Thạc sĩ Ngoại khoa (M.S)', 'Chuyên khoa: Phẫu thuật Cấp cứu, Chẩn đoán Hình ảnh', 
 'BS. Đức Nhân là người thực hiện các kỹ thuật Chẩn đoán Hình ảnh như Siêu âm và X-Quang.', 
 'assets/img/doctors/doctor-nhan.jpg', 'nhan@petcare.vn', '0901000004', 'bsnhan', '$2y$10$iQt4BrkDuLVcea6JkW54Qe.6QPBajbb.AoGk4rxHgM3EbJKd4x/YG'),
('BS. Phạm Quang Thảo', 'Tiến sĩ Thú Y (Ph.D)', 'Chuyên khoa: Phẫu thuật Triệt sản, Chỉnh hình & Ngoại khoa chuyên sâu', 
 'BS. Quang Thảo là Phẫu thuật viên chính, chuyên trách các ca phức tạp cần kỹ thuật cao.', 
 'assets/img/doctors/doctor-thao.jpg', 'thao@petcare.vn', '0901000005', 'bsthao', '$2y$10$iQt4BrkDuLVcea6JkW54Qe.6QPBajbb.AoGk4rxHgM3EbJKd4x/YG');

-- Update passwords for authorized doctor accounts to 123456
-- Password: 123456
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- Only 3 accounts are authorized: bsduy, bsthuy, bstina
UPDATE doctors SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'bsduy';
UPDATE doctors SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'bsthuy';
UPDATE doctors SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'bstina';

-- Insert sample data for services
INSERT INTO services (name, short_description, description, image) VALUES
('Khám tổng quát', 'Khám sức khỏe định kỳ', 'Khám tổng quát, xét nghiệm cơ bản, tư vấn dinh dưỡng.', '/assets/img/service/service1.png'),
('Tiêm phòng', 'Vaccine & phòng bệnh', 'Tiêm phòng định kỳ, phác đồ tiêm an toàn.', '/assets/img/service/service2.png'),
('Phẫu thuật', 'Sát trùng & vô trùng', 'Phẫu thuật tiểu phẫu và đại phẫu, chăm sóc hậu phẫu.', '/assets/img/service/service3.png'),
('Chẩn đoán hình ảnh', 'X-Quang & Siêu âm', 'Chẩn đoán hình ảnh tiên tiến với X-Quang và Siêu âm.', '/assets/img/service/service4.png'),
('Spa & Grooming', 'Chăm sóc & làm đẹp', 'Dịch vụ tắm, cắt tỉa lông, chăm sóc móng chuyên nghiệp.', '/assets/img/service/service5.png'),
('Pet Hotel', 'Lưu trú thú cưng', 'Hệ thống phòng lưu trú cao cấp, sạch sẽ, có giám sát sức khỏe liên tục.', '/assets/img/service/service6.png');

-- Insert doctor-service relationships
INSERT INTO doctor_services (doctor_id, service_id) VALUES
(1, 1), -- BS. Duy - Khám tổng quát
(2, 1), -- BS. Thùy - Khám tổng quát
(2, 2), -- BS. Thùy - Tiêm phòng
(3, 1), -- BS. Ti Na - Khám tổng quát
(3, 4), -- BS. Ti Na - Chẩn đoán hình ảnh
(4, 3), -- BS. Đức Nhân - Phẫu thuật
(4, 4), -- BS. Đức Nhân - Chẩn đoán hình ảnh
(5, 3); -- BS. Quang Thảo - Phẫu thuật

-- Insert sample blogs
INSERT INTO blogs (title, slug, content, image, excerpt, author, published_at, status) VALUES
('5 Bước Chăm Sóc Toàn Diện Cho Thú Cưng Mùa Hè', '5-buoc-cham-soc-toan-dien-cho-thu-cung-mua-he',
 '<p>Mùa hè là thời điểm thú cưng rất dễ bị say nắng, mất nước và các vấn đề da liễu. Bài viết này hướng dẫn bạn 5 bước chăm sóc toàn diện.</p>
<h2>1. Cung cấp nước đầy đủ</h2>
<p>Luôn đảm bảo thú cưng có nước sạch, mát, và kiểm tra thường xuyên.</p>
<h2>2. Chế độ ăn hợp lý</h2>
<p>Thêm trái cây, rau củ tươi, giảm thức ăn nặng và nhiều dầu mỡ.</p>
<h2>3. Bảo vệ da và lông</h2>
<p>Tắm đúng cách, dùng dầu gội chuyên dụng, cắt tỉa lông phù hợp.</p>
<h2>4. Tránh nắng trực tiếp</h2>
<p>Không để thú cưng ra ngoài trời quá lâu, đặc biệt từ 10h sáng đến 4h chiều.</p>
<h2>5. Kiểm tra sức khỏe định kỳ</h2>
<p>Đưa thú cưng đến Pet Care khám tổng quát định kỳ, kiểm tra da liễu, ký sinh trùng, và tiêm phòng đầy đủ.</p>',
 'assets/img/blog/blog1.png',
 'Mùa hè là thời điểm thú cưng dễ bị say nắng, mất nước và các vấn đề da liễu. Hãy theo dõi 5 bước chăm sóc toàn diện.',
 'PetCare Team', '2025-11-18', 'published'),
('Chế Độ Dinh Dưỡng Chuẩn Cho Chó Mọi Lứa Tuổi', 'che-do-dinh-duong-chuan-cho-cho-moi-lua-tuoi',
 '<p>Bài viết này hướng dẫn cách xây dựng chế độ dinh dưỡng hợp lý giúp chó phát triển khỏe mạnh.</p>
<h2>1. Chó con</h2>
<p>Dinh dưỡng giàu protein, canxi để phát triển xương và cơ bắp.</p>
<h2>2. Chó trưởng thành</h2>
<p>Giữ cân nặng hợp lý, đầy đủ vitamin, chất xơ và khoáng chất.</p>
<h2>3. Chó già</h2>
<p>Giảm năng lượng, tăng chất xơ, hỗ trợ tiêu hóa và sức khỏe tim mạch.</p>',
 'assets/img/blog/blog2.jpg',
 'Bài viết này hướng dẫn cách xây dựng chế độ dinh dưỡng hợp lý giúp chó phát triển khỏe mạnh, từ chó con đến chó trưởng thành.',
 'PetCare Team', '2025-10-10', 'published');

-- Insert sample testimonials
INSERT INTO testimonials (customer_name, content, rating, status) VALUES
('Mai Anh', 'Bác sĩ ở đây rất tận tâm, thú cưng của mình được chăm sóc kỹ lưỡng. Highly recommend!', 5, 'approved'),
('Ngọc Trâm', 'Phòng khám sạch sẽ, dịch vụ nhanh chóng, nhân viên dễ thương.', 5, 'approved');

DELETE s1 FROM services s1
INNER JOIN services s2 
WHERE 
    s1.name = s2.name AND s1.id > s2.id;