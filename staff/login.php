<?php
session_start(); // Khởi động phiên làm việc
require_once '../config/db.php'; // Kết nối database

$error = ''; // Biến chứa thông báo lỗi

// Kiểm tra nếu người dùng bấm nút Đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu!';
    } else {
        // Danh sách tài khoản được cấp phép (chỉ 3 bác sĩ được phép đăng nhập)
        $authorized_accounts = ['bsduy', 'bsthuy', 'bstina'];
        
        // Kiểm tra xem tài khoản có được cấp phép không
        if (!in_array($username, $authorized_accounts)) {
            $error = 'Tài khoản này chưa được cấp phép. Vui lòng liên hệ quản trị viên.';
        } else {
            // Tìm bác sĩ trong database (Bảng doctors)
            // - Sử dụng bảng doctors cho Staff Portal
            $stmt = $conn->prepare("SELECT * FROM doctors WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            // Kiểm tra mật khẩu (đã mã hóa)
            if ($user) {
                // Debug: Check if password field exists and is not empty
                if (empty($user['password'])) {
                    $error = 'Tài khoản chưa có mật khẩu. Vui lòng liên hệ quản trị viên để đặt mật khẩu.';
                } elseif (password_verify($password, $user['password'])) {
                    // ĐĂNG NHẬP THÀNH CÔNG
                    // Lưu thông tin bác sĩ vào Session để các trang khác biết ai đang dùng
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_avatar'] = $user['image'];
                    $_SESSION['user_role'] = $user['specialty']; // Lấy chuyên khoa làm chức vụ

                    // Chuyển hướng vào Dashboard
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Sai mật khẩu! Vui lòng kiểm tra lại hoặc chạy script reset password.';
                }
            } else {
                $error = 'Không tìm thấy tài khoản này trong hệ thống!';
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
    <title>Đăng nhập hệ thống - PetCare Staff</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/staff-style.css">
    <style>
        .error-msg { color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 14px; text-align: center;}
        
        /* CSS bổ sung cho link Quên mật khẩu đẹp hơn */
        .forgot-link {
            text-align: right;
            margin-bottom: 20px;
            margin-top: -10px;
        }
        .forgot-link a {
            color: #00bcd4; /* Màu xanh của PetCare */
            font-size: 14px;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        .forgot-link a:hover {
            color: #00838f;
            text-decoration: underline;
        }
    </style>
</head>
<body class="login-page">

    <div class="login-container">
        <div class="login-header">
            <h1>🐾 PetCare Staff</h1>
            <p>Cổng thông tin dành cho Nhân viên & Bác sĩ</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-msg">⚠️ <?php echo $error; ?></div>
        <?php endif; ?>

        <form class="login-form" action="" method="POST">
            <div class="form-group">
                <label>Tên đăng nhập</label>
                <input type="text" name="username" placeholder="Nhập mã nhân viên (VD: bsduy)" required>
            </div>
            
            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <div class="forgot-link">
                <a href="forgot-password.php">Quên mật khẩu?</a>
            </div>

            <button type="submit" class="btn-login">ĐĂNG NHẬP</button>
            
            <div class="login-footer">
                <p style="color: #666; font-size: 13px;">Tài khoản được cấp bởi quản trị viên</p>
            </div>
        </form>
    </div>

</body>
</html>