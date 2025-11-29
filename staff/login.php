<?php
require_once '../config/db.php'; // Kết nối database (db.php already handles session_start)

$error = ''; // Biến chứa thông báo lỗi

// DEBUG MODE - Set to false to hide debug info
$debug_mode = isset($_GET['debug']) || isset($_POST['debug']);

$debug_info = [];

// Kiểm tra nếu người dùng bấm nút Đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $debug_info[] = "Step 1: Received POST data";
    $debug_info[] = "  - Username: '" . htmlspecialchars($username) . "'";
    $debug_info[] = "  - Password length: " . strlen($password);

    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu!';
        $debug_info[] = "Step 2: Validation failed - empty fields";
    } else {
        // Danh sách tài khoản được cấp phép (chỉ 3 bác sĩ được phép đăng nhập)
        $authorized_accounts = ['bsduy', 'bsthuy', 'bstina'];
        $debug_info[] = "Step 2: Authorization check";
        $debug_info[] = "  - Authorized accounts: " . implode(', ', $authorized_accounts);
        $debug_info[] = "  - Is '$username' authorized? " . (in_array($username, $authorized_accounts) ? 'YES' : 'NO');
        
        // Kiểm tra xem tài khoản có được cấp phép không
        if (!in_array($username, $authorized_accounts)) {
            $error = 'Tài khoản này chưa được cấp phép. Vui lòng liên hệ quản trị viên.';
            $debug_info[] = "Step 3: FAILED - Account not authorized";
        } else {
            $debug_info[] = "Step 3: Database query - Looking for doctor with username='$username'";
            
            // Check database connection
            if (!$conn) {
                $error = 'Lỗi kết nối database!';
                $debug_info[] = "  - Database connection: FAILED";
            } else {
                $debug_info[] = "  - Database connection: OK";
                $debug_info[] = "  - Database name: " . DB_NAME;
                
                // Tìm bác sĩ trong database (Bảng doctors)
                // - Sử dụng bảng doctors cho Staff Portal
                $stmt = $conn->prepare("SELECT * FROM doctors WHERE username = ?");
                
                if (!$stmt) {
                    $error = 'Lỗi chuẩn bị truy vấn: ' . $conn->error;
                    $debug_info[] = "  - Prepare statement: FAILED - " . $conn->error;
                } else {
                    $debug_info[] = "  - Prepare statement: OK";
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    $debug_info[] = "  - Query executed";
                    $debug_info[] = "  - Rows found: " . ($user ? "1" : "0");

                    // Kiểm tra mật khẩu (đã mã hóa)
                    if ($user) {
                        $debug_info[] = "Step 4: User found in database";
                        $debug_info[] = "  - User ID: " . $user['id'];
                        $debug_info[] = "  - User Name: " . $user['name'];
                        $debug_info[] = "  - Username: " . $user['username'];
                        
                        // Debug password field
                        $has_password = isset($user['password']);
                        $password_empty = empty($user['password']);
                        $password_length = $has_password ? strlen($user['password']) : 0;
                        
                        $debug_info[] = "Step 5: Password field check";
                        $debug_info[] = "  - Password field exists: " . ($has_password ? 'YES' : 'NO');
                        $debug_info[] = "  - Password is empty: " . ($password_empty ? 'YES' : 'NO');
                        $debug_info[] = "  - Password hash length: " . $password_length;
                        
                        if ($has_password && !$password_empty) {
                            $debug_info[] = "  - Password hash (first 30 chars): " . substr($user['password'], 0, 30) . "...";
                        }
                        
                        if (empty($user['password'])) {
                            $error = 'Tài khoản chưa có mật khẩu. Vui lòng chạy script <a href="reset_pass.php" style="color: #00bcd4;">reset_pass.php</a> để đặt mật khẩu.';
                            $debug_info[] = "Step 6: FAILED - Password field is empty";
                        } else {
                            $debug_info[] = "Step 6: Password verification";
                            $debug_info[] = "  - Input password: '$password'";
                            $debug_info[] = "  - Stored hash: " . substr($user['password'], 0, 50) . "...";
                            
                            $verify_result = password_verify($password, $user['password']);
                            $debug_info[] = "  - password_verify() result: " . ($verify_result ? 'TRUE ✅' : 'FALSE ❌');
                            
                            // Test if hash format is correct
                            $hash_info = password_get_info($user['password']);
                            $debug_info[] = "  - Hash algorithm: " . ($hash_info['algoName'] ?? 'UNKNOWN');
                            $debug_info[] = "  - Hash options: " . json_encode($hash_info['options'] ?? []);
                            
                            if (!$verify_result) {
                                // Additional debug: Try to create a new hash and verify it works
                                $test_hash = password_hash($password, PASSWORD_DEFAULT);
                                $test_verify = password_verify($password, $test_hash);
                                $debug_info[] = "  - Test: New hash verification works? " . ($test_verify ? 'YES' : 'NO');
                                $debug_info[] = "  - Possible issue: Hash in database may be incompatible with current PHP version";
                                
                                $error = 'Sai mật khẩu! Vui lòng kiểm tra lại hoặc chạy script <a href="reset_pass.php" style="color: #00bcd4;">reset_pass.php</a> để reset mật khẩu.';
                                $debug_info[] = "Step 7: FAILED - Password verification failed";
                            } else {
                                // ĐĂNG NHẬP THÀNH CÔNG
                                $debug_info[] = "Step 7: SUCCESS - Login successful!";
                                $debug_info[] = "  - Setting session variables...";
                                
                                // Lưu thông tin bác sĩ vào Session để các trang khác biết ai đang dùng
                                $_SESSION['user_id'] = $user['id'];
                                $_SESSION['user_name'] = $user['name'];
                                $_SESSION['user_avatar'] = $user['image'];
                                $_SESSION['user_role'] = $user['specialty']; // Lấy chuyên khoa làm chức vụ

                                $debug_info[] = "  - Session variables set";
                                $debug_info[] = "  - Redirecting to dashboard...";

                                // Chuyển hướng vào Dashboard
                                header('Location: dashboard.php');
                                exit;
                            }
                        }
                    } else {
                        $error = 'Không tìm thấy tài khoản này trong hệ thống! Vui lòng kiểm tra lại username hoặc liên hệ quản trị viên.';
                        $debug_info[] = "Step 4: FAILED - User not found in database";
                        $debug_info[] = "  - SQL: SELECT * FROM doctors WHERE username = '$username'";
                        $debug_info[] = "  - Check if username in database matches exactly (case-sensitive)";
                    }
                }
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
        
        <?php if ($debug_mode && !empty($debug_info)): ?>
            <div style="background: #f5f5f5; border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 5px; font-family: monospace; font-size: 12px; max-height: 400px; overflow-y: auto;">
                <h3 style="margin-top: 0; color: #0097a7;">🔍 Debug Information</h3>
                <?php foreach ($debug_info as $info): ?>
                    <div style="margin: 5px 0; padding: 5px; background: white; border-left: 3px solid #0097a7;">
                        <?php echo htmlspecialchars($info); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="login-form" action="" method="POST">
            <?php if ($debug_mode): ?>
                <input type="hidden" name="debug" value="1">
            <?php endif; ?>
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
                <p style="margin-top: 10px;">
                    <a href="?debug=1" style="color: #0097a7; font-size: 12px; text-decoration: none;">
                        🔍 Enable Debug Mode
                    </a> | 
                    <a href="check_login.php" style="color: #0097a7; font-size: 12px; text-decoration: none;">
                        Check Database
                    </a>
                </p>
            </div>
        </form>
    </div>

</body>
</html>