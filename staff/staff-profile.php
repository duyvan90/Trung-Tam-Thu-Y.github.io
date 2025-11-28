<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$user_id = $_SESSION['user_id'];
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_info'])) {
        $name = $_POST['name'];
        $specialty = $_POST['specialty']; 
        $stmt = $conn->prepare("UPDATE doctors SET name = ?, specialty = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $specialty, $user_id);
        if ($stmt->execute()) $msg = "Cập nhật thành công!";
    }
    // Upload ảnh... (Giữ nguyên logic nếu có)
}

$stmt = $conn->prepare("SELECT * FROM doctors WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$avatar_url = "../" . ($user['image'] ?? 'assets/img/default-avatar.png');
if (!file_exists($avatar_url) || empty($user['image'])) {
    $avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($user['name']) . "&background=0097a7&color=fff&size=128";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hồ sơ cá nhân</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/staff-style.css">
</head>
<body>

<div class="admin-layout">
    <aside class="sidebar">
        <div class="brand">🐾 PetCare <span class="badge">Staff</span></div>
        
        <a href="staff-profile.php" class="user-panel active-panel" style="text-decoration: none;">
            <img src="<?php echo $avatar_url; ?>" alt="Avatar">
            <div class="info">
                <h4 style="margin:0; font-size:15px; font-weight:600;"><?php echo htmlspecialchars($user['name']); ?></h4>
                <small style="color:#b0bec5; font-size: 12px; display:block; margin-top:2px;"><?php echo htmlspecialchars($user['specialty']); ?></small>
            </div>
        </a>

        <ul class="menu">
            <li><a href="dashboard.php">📅 Lịch hẹn hôm nay</a></li>
            <li><a href="emr-list.php">📝 Bệnh án điện tử</a></li>
            <li><a href="schedule.php">🕒 Lịch làm việc</a></li>
            <li><a href="logout.php" class="logout">Đăng xuất</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="top-bar">
            <h2>Quản lý Hồ Sơ Cá Nhân</h2>
        </header>

        <div class="profile-container">
            <?php if($msg): ?><div style="background:#d4edda; color:#155724; padding:15px; margin-bottom:20px; border-radius:8px;">✅ <?php echo $msg; ?></div><?php endif; ?>

            <div class="profile-grid" style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                <div class="profile-card center-text" style="text-align:center;"> 
                    <img src="<?php echo $avatar_url; ?>" style="width:120px; height:120px; border-radius:50%; object-fit:cover; border:4px solid var(--border-color);"> 
                    <h3 style="margin:15px 0 5px;"><?php echo htmlspecialchars($user['name']); ?></h3>
                    <p style="color:var(--text-muted); font-size:14px;"><?php echo htmlspecialchars($user['specialty']); ?></p>
                    <hr style="margin:20px 0; border:0; border-top:1px solid var(--border-color);">
                    
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span>Giao diện Tối</span>
                        <label class="switch">
                            <input type="checkbox" id="darkModeToggle">
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>

                <div class="profile-card">
                    <form action="" method="POST">
                        <input type="hidden" name="update_info" value="1">
                        <div class="form-group">
                            <label>Họ và tên</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Tên đăng nhập</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Chức vụ</label>
                            <select name="specialty">
                                <option value="<?php echo $user['specialty']; ?>"><?php echo $user['specialty']; ?></option>
                                <option value="Bác sĩ Thú Y">Bác sĩ Thú Y</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-save">Lưu thay đổi</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    const toggle = document.getElementById('darkModeToggle');
    const body = document.body;

    if (localStorage.getItem('darkMode') === 'enabled') {
        body.classList.add('dark-mode');
        if(toggle) toggle.checked = true;
    }

    if(toggle) {
        toggle.addEventListener('change', () => {
            if (toggle.checked) {
                body.classList.add('dark-mode');
                localStorage.setItem('darkMode', 'enabled');
            } else {
                body.classList.remove('dark-mode');
                localStorage.setItem('darkMode', 'disabled');
            }
        });
    }
</script>
</body>
</html>