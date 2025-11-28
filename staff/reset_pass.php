<?php
// Kết nối database
require_once '../config/db.php'; 

// Thông tin cần reset
$username = 'bsduy';
$new_password = '123456';

// Tạo mã hóa chuẩn bằng chính server của bạn
$new_hash = password_hash($new_password, PASSWORD_DEFAULT);

// Cập nhật vào Database
$sql = "UPDATE doctors SET password = ? WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $new_hash, $username);

if ($stmt->execute()) {
    echo "<div style='font-family: sans-serif; padding: 20px; text-align: center; line-height: 1.6;'>";
    echo "<h2 style='color: green;'>✅ Đã Reset Mật khẩu thành công!</h2>";
    echo "<p>Tài khoản: <b>$username</b></p>";
    echo "<p>Mật khẩu mới: <b>$new_password</b></p>";
    echo "<p>Mã Hash mới trong DB: <small style='color: gray;'>$new_hash</small></p>";
    echo "<br><a href='login.php' style='background: #0097a7; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Quay lại trang Đăng nhập</a>";
    echo "</div>";
} else {
    echo "Lỗi: " . $conn->error;
}
?>