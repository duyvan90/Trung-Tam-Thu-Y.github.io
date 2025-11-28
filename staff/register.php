<?php
// Registration is disabled - only authorized accounts are allowed
header('Location: login.php');
exit;

session_start();
require_once '../config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $specialty = trim($_POST['specialty']);

    // 1. KIỂM TRA DỮ LIỆU ĐẦU VÀO (VALIDATION)
    if (empty($fullname) || empty($username) || empty($password)) {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    } 
    // Kiểm tra định dạng email chuẩn quốc tế
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Địa chỉ Email không hợp lệ! (Thiếu @ hoặc sai ký tự)';
    }
    // Kiểm tra đuôi email phải phổ biến (.com, .vn, .net...)
    elseif (!preg_match("/\.(com|vn|net|org|edu|gov|info)$/", $email)) {
        $error = 'Đuôi email không hợp lệ! (Phải là .com, .vn, .net, .org...)';
    }
    // Chặn cứng trường hợp gõ nhầm .con
    elseif (strpos($email, '.con') !== false) {
        $error = 'Có vẻ bạn gõ nhầm ".com" thành ".con"? Vui lòng kiểm tra lại.';
    }
    elseif ($password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp!';
    } else {
        // 2. KIỂM TRA TRÙNG LẶP TRONG DATABASE
        $check = $conn->prepare("SELECT id FROM doctors WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $error = 'Tên đăng nhập hoặc Email này đã có người sử dụng!';
        } else {
            // 3. ĐĂNG KÝ THÀNH CÔNG
            // Mã hóa mật khẩu
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Ảnh đại diện mặc định
            $default_image = 'assets/img/doctor-duy.jpg'; 

            $sql = "INSERT INTO doctors (name, email, username, password, specialty, image) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $fullname, $email, $username, $hashed_password, $specialty, $default_image);
            
            if ($stmt->execute()) {
                $success = 'Đăng ký thành công! Đang chuyển hướng về trang đăng nhập...';
            } else {
                $error = 'Có lỗi hệ thống xảy ra, vui lòng thử lại.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký nhân viên - PetCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/staff-style.css">
    <style>
        .login-container { max-width: 450px; }
        .success-msg { color: #155724; background: #d4edda; padding: 15px; border-radius: 5px; margin-bottom: 15px; text-align: center; font-weight: 600; }
        .error-msg { color: #721c24; background: #f8d7da; padding: 15px; border-radius: 5px; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body class="login-page">

    <div class="login-container">
        <div class="login-header">
            <h1>📝 Đăng Ký Mới</h1>
            <p>Tạo tài khoản Bác sĩ / Nhân viên</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-msg">⚠️ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-msg">✅ <?php echo $success; ?></div>
            <script>
                // Chuyển trang sau 2 giây
                setTimeout(function(){ window.location.href = 'login.php'; }, 2000);
            </script>
        <?php endif; ?>

        <form class="login-form" action="" method="POST">
            <div class="form-group">
                <label>Họ và tên</label>
                <input type="text" name="fullname" placeholder="VD: Nguyễn Văn A" required>
            </div>

            <div class="form-group">
                <label>Chuyên khoa / Chức vụ</label>
                <select name="specialty" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;" required>
                    <option value="">-- Vui lòng chọn --</option>
                    <optgroup label="Đội ngũ Bác sĩ">
                        <option value="Bác sĩ Thú Y (Tổng quát)">Bác sĩ Thú Y (Tổng quát)</option>
                        <option value="Bác sĩ Phẫu thuật">Bác sĩ Phẫu thuật (Ngoại khoa)</option>
                        <option value="Bác sĩ Chẩn đoán hình ảnh">Bác sĩ Chẩn đoán hình ảnh (Siêu âm/X-Quang)</option>
                        <option value="Bác sĩ Cấp cứu">Bác sĩ Cấp cứu</option>
                    </optgroup>
                    <optgroup label="Bộ phận Khác">
                        <option value="Y tá / Kỹ thuật viên Thú Y">Y tá / Kỹ thuật viên Thú Y</option>
                        <option value="Chuyên viên Grooming (Spa)">Chuyên viên Grooming (Spa & Cắt tỉa)</option>
                        <option value="Lễ tân / CSKH">Lễ tân / Chăm sóc khách hàng</option>
                        <option value="Quản lý phòng khám">Quản lý phòng khám</option>
                    </optgroup>
                </select>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" 
                       name="email" 
                       placeholder="email@example.com" 
                       pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                       title="Vui lòng nhập đúng định dạng email (VD: ten@gmail.com)"
                       required>
            </div>
            
            <div class="form-group">
                <label>Tên đăng nhập</label>
                <input type="text" name="username" placeholder="VD: bsnguyenvana" required>
            </div>
            
            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <div class="form-group">
                <label>Xác nhận mật khẩu</label>
                <input type="password" name="confirm_password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-login">ĐĂNG KÝ TÀI KHOẢN</button>
            
            <div class="login-footer">
                <p>Đã có tài khoản? <a href="login.php" style="color: var(--primary); font-weight: bold;">Đăng nhập ngay</a></p>
            </div>
        </form>
    </div>

</body>
</html>