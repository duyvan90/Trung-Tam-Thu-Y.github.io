<?php
// Diagnostic script to check and fix doctor login issues
require_once '../config/db.php';

$username = 'bsduy';
$test_password = '123456';

echo "<h2>🔍 Doctor Login Diagnostic Tool</h2>";
echo "<hr>";

// 1. Check if doctor exists
$stmt = $conn->prepare("SELECT * FROM doctors WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();

if (!$doctor) {
    echo "<p style='color: red;'>❌ Doctor with username '{$username}' NOT FOUND in database!</p>";
    echo "<p>Please check if the database schema has been imported correctly.</p>";
    exit;
}

echo "<p style='color: green;'>✅ Doctor found:</p>";
echo "<ul>";
echo "<li>ID: {$doctor['id']}</li>";
echo "<li>Name: {$doctor['name']}</li>";
echo "<li>Username: {$doctor['username']}</li>";
echo "<li>Email: {$doctor['email']}</li>";
echo "<li>Password field exists: " . (isset($doctor['password']) ? 'Yes' : 'No') . "</li>";
echo "<li>Password is empty: " . (empty($doctor['password']) ? 'Yes ⚠️' : 'No ✅') . "</li>";
if (!empty($doctor['password'])) {
    echo "<li>Password hash: <code style='font-size: 10px;'>" . substr($doctor['password'], 0, 30) . "...</code></li>";
}
echo "</ul>";

echo "<hr>";

// 2. Test password verification
if (empty($doctor['password'])) {
    echo "<p style='color: orange;'>⚠️ Password is empty. Updating password now...</p>";
    
    $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
    $update_stmt = $conn->prepare("UPDATE doctors SET password = ? WHERE username = ?");
    $update_stmt->bind_param("ss", $new_hash, $username);
    
    if ($update_stmt->execute()) {
        echo "<p style='color: green;'>✅ Password has been set successfully!</p>";
        echo "<p>New hash: <code style='font-size: 10px;'>" . $new_hash . "</code></p>";
        
        // Re-fetch to test
        $stmt->execute();
        $result = $stmt->get_result();
        $doctor = $result->fetch_assoc();
    } else {
        echo "<p style='color: red;'>❌ Failed to update password: " . $conn->error . "</p>";
        exit;
    }
}

// 3. Test password verification
echo "<h3>Testing Password Verification:</h3>";
echo "<p>Testing password: <strong>{$test_password}</strong></p>";

$verify_result = password_verify($test_password, $doctor['password']);

if ($verify_result) {
    echo "<p style='color: green; font-size: 18px;'>✅ PASSWORD VERIFICATION SUCCESSFUL!</p>";
    echo "<p>The login should work correctly now.</p>";
} else {
    echo "<p style='color: red; font-size: 18px;'>❌ PASSWORD VERIFICATION FAILED!</p>";
    echo "<p>Regenerating password hash...</p>";
    
    // Regenerate hash
    $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
    $update_stmt = $conn->prepare("UPDATE doctors SET password = ? WHERE username = ?");
    $update_stmt->bind_param("ss", $new_hash, $username);
    
    if ($update_stmt->execute()) {
        echo "<p style='color: green;'>✅ Password hash has been regenerated!</p>";
        
        // Test again
        $verify_result = password_verify($test_password, $new_hash);
        if ($verify_result) {
            echo "<p style='color: green; font-size: 18px;'>✅ NEW PASSWORD VERIFICATION SUCCESSFUL!</p>";
        } else {
            echo "<p style='color: red;'>❌ Still failing - there may be a PHP version issue.</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Failed to update: " . $conn->error . "</p>";
    }
}

echo "<hr>";
echo "<h3>Summary:</h3>";
echo "<p><strong>Username:</strong> {$username}</p>";
echo "<p><strong>Password:</strong> {$test_password}</p>";
echo "<p><strong>Status:</strong> " . ($verify_result ? "✅ Ready to login" : "❌ Please check database connection") . "</p>";

echo "<hr>";
echo "<p><a href='login.php' style='background: #0097a7; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px;'>Go to Login Page</a></p>";

?>

