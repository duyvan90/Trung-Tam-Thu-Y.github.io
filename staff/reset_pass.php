<?php
// Kết nối database
require_once '../config/db.php'; 

// Reset password for all authorized doctor accounts
$authorized_accounts = [
    'bsduy' => '123456',
    'bsthuy' => '123456',
    'bstina' => '123456'
];

echo "<div style='font-family: sans-serif; padding: 20px; max-width: 800px; margin: 0 auto;'>";
echo "<h2 style='color: #0097a7;'>🔧 Reset Password for Doctor Accounts</h2>";
echo "<hr>";

$success_count = 0;
$fail_count = 0;

foreach ($authorized_accounts as $username => $new_password) {
    // Check if account exists
    $check_stmt = $conn->prepare("SELECT id, name FROM doctors WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $doctor = $result->fetch_assoc();
    
    if (!$doctor) {
        echo "<p style='color: red;'>❌ Account <strong>{$username}</strong> NOT FOUND in database!</p>";
        $fail_count++;
        continue;
    }
    
    // Tạo mã hóa chuẩn bằng chính server của bạn
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Verify the hash works before saving
    if (!password_verify($new_password, $new_hash)) {
        echo "<p style='color: red;'>❌ Hash generation failed for <strong>{$username}</strong>!</p>";
        $fail_count++;
        continue;
    }
    
    // Cập nhật vào Database
    $sql = "UPDATE doctors SET password = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $new_hash, $username);
    
    if ($stmt->execute()) {
        // Double-check by verifying the password
        $verify_stmt = $conn->prepare("SELECT password FROM doctors WHERE username = ?");
        $verify_stmt->bind_param("s", $username);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        $updated_doctor = $verify_result->fetch_assoc();
        
        if (password_verify($new_password, $updated_doctor['password'])) {
            echo "<p style='color: green;'>✅ <strong>{$username}</strong> ({$doctor['name']}) - Password reset successfully and verified!</p>";
            $success_count++;
        } else {
            echo "<p style='color: orange;'>⚠️ <strong>{$username}</strong> - Password updated but verification failed. Please check PHP version.</p>";
            $fail_count++;
        }
    } else {
        echo "<p style='color: red;'>❌ Failed to update <strong>{$username}</strong>: " . $conn->error . "</p>";
        $fail_count++;
    }
}

echo "<hr>";
echo "<h3>Summary:</h3>";
echo "<p>✅ Success: <strong>{$success_count}</strong></p>";
echo "<p>❌ Failed: <strong>{$fail_count}</strong></p>";

echo "<hr>";
echo "<h3>Login Credentials:</h3>";
echo "<table style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
echo "<tr style='background: #f5f5f5;'><th style='padding: 8px; text-align: left; border: 1px solid #ddd;'>Username</th><th style='padding: 8px; text-align: left; border: 1px solid #ddd;'>Password</th></tr>";
foreach ($authorized_accounts as $username => $password) {
    echo "<tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>{$username}</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{$password}</td></tr>";
}
echo "</table>";

echo "<br><a href='login.php' style='background: #0097a7; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px;'>➡️ Go to Login Page</a>";
echo "<br><a href='check_login.php' style='background: #4caf50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>🔍 Run Diagnostic Check</a>";
echo "</div>";
?>