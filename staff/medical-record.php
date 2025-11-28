<?php
session_start();
require_once '../config/db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$user_id = $_SESSION['user_id'];

// Lấy ID booking
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($booking_id == 0) { header('Location: dashboard.php'); exit; }

$msg = "";

// --- XỬ LÝ LƯU BỆNH ÁN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $weight = $_POST['weight'];
    $temp = $_POST['temperature'];
    $symptoms = $_POST['symptoms'];
    $diagnosis = $_POST['diagnosis'];
    $treatment = $_POST['treatment'];
    $notes = $_POST['notes'];

    // Kiểm tra xem đã có bệnh án chưa để Insert hoặc Update
    $sql_check = "SELECT id FROM medical_records WHERE booking_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $booking_id);
    $stmt_check->execute();
    
    if ($stmt_check->get_result()->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE medical_records SET weight=?, temperature=?, symptoms=?, diagnosis=?, treatment=?, notes=? WHERE booking_id=?");
        $stmt->bind_param("ddssssi", $weight, $temp, $symptoms, $diagnosis, $treatment, $notes, $booking_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO medical_records (booking_id, doctor_id, weight, temperature, symptoms, diagnosis, treatment, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iidsssss", $booking_id, $user_id, $weight, $temp, $symptoms, $diagnosis, $treatment, $notes);
    }
    
    if ($stmt->execute()) {
        $conn->query("UPDATE bookings SET status = 'completed' WHERE id = $booking_id");
        $msg = "Đã lưu hồ sơ bệnh án thành công!";
    } else {
        $msg = "Lỗi: " . $conn->error;
    }
}

// --- LẤY DỮ LIỆU HIỂN THỊ ---
// 1. Thông tin Booking
$sql_booking = "SELECT b.*, s.name as service_name FROM bookings b LEFT JOIN services s ON b.service_id = s.id WHERE b.id = ?";
$stmt_b = $conn->prepare($sql_booking);
$stmt_b->bind_param("i", $booking_id);
$stmt_b->execute();
$booking = $stmt_b->get_result()->fetch_assoc();

// 2. Thông tin Bệnh án (nếu có)
$record = null;
$res_rec = $conn->query("SELECT * FROM medical_records WHERE booking_id = $booking_id");
if($res_rec) $record = $res_rec->fetch_assoc();

// 3. Thông tin Bác sĩ (cho Sidebar)
$user_info = $conn->query("SELECT name, specialty, image FROM doctors WHERE id = $user_id")->fetch_assoc();
$avatar_url = "../" . ($user_info['image'] ?? 'assets/img/default-avatar.png');
if (!file_exists($avatar_url) || empty($user_info['image'])) {
    $avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($user_info['name']) . "&background=0097a7&color=fff&size=128";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hồ sơ bệnh án</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/staff-style.css">
    <style>
        /* CSS riêng để cân chỉnh bố cục trang này */
        .emr-container { max-width: 900px; margin: 0 auto; }
        
        .patient-info-card {
            display: flex; gap: 20px; align-items: center;
            margin-bottom: 30px;
            border-left: 5px solid var(--primary);
        }
        
        .patient-avatar { 
            width: 80px; height: 80px; 
            border-radius: 12px; 
            background: var(--bg-body); 
            display: flex; align-items: center; justify-content: center; 
            font-size: 35px; 
            border: 1px solid var(--border-color);
        }
        
        .section-title { 
            font-size: 18px; 
            color: var(--primary); 
            margin-bottom: 20px; 
            padding-bottom: 10px; 
            border-bottom: 2px solid var(--border-color); 
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
        }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        
        /* Chỉnh lại alert */
        .alert-success { 
            background: #d4edda; color: #155724; 
            padding: 15px; margin-bottom: 25px; 
            border-radius: 8px; border: 1px solid #c3e6cb;
            display: flex; justify-content: space-between; align-items: center;
        }
    </style>
</head>
<body>

<div class="admin-layout">
    <aside class="sidebar">
        <div class="brand">🐾 PetCare <span class="badge">Doctor</span></div>
        <a href="staff-profile.php" class="user-panel" style="text-decoration: none;">
            <img src="<?php echo $avatar_url; ?>" alt="Avatar">
            <div class="info">
                <h4><?php echo htmlspecialchars($user_info['name']); ?></h4>
                <small><?php echo htmlspecialchars($user_info['specialty']); ?></small>
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
        <div class="emr-container">
            
            <?php if($msg): ?>
                <div class="alert-success">
                    <span>✅ <?php echo $msg; ?></span>
                    <a href="emr-list.php" style="font-weight:bold; color:#155724; text-decoration:none;">Quay lại danh sách →</a>
                </div>
            <?php endif; ?>

            <div class="patient-info-card profile-card">
                <div class="patient-avatar">🐶</div>
                <div class="info">
                    <h2 style="margin: 0 0 8px 0; color: var(--text-main);">
                        <?php echo htmlspecialchars($booking['pet_name']); ?> 
                        <span style="font-size:16px; font-weight:normal; color:var(--text-muted);">
                            (<?php echo htmlspecialchars($booking['pet_type']); ?>)
                        </span>
                    </h2>
                    <p style="margin: 0 0 5px 0; color: var(--text-muted);">
                        👤 Chủ nuôi: <b><?php echo htmlspecialchars($booking['fullname']); ?></b> - 📞 <?php echo htmlspecialchars($booking['phone']); ?>
                    </p>
                    <p style="margin: 0;">
                        <span class="tag service" style="background:var(--bg-body); border:1px solid var(--border-color); color:var(--text-main);"><?php echo htmlspecialchars($booking['service_name']); ?></span> 
                    </p>
                </div>
            </div>

            <form action="" method="POST">
                <div class="form-section">
                    <h3 class="section-title">1. Khám Lâm Sàng</h3>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Cân nặng (kg)</label>
                            <input type="number" step="0.1" name="weight" value="<?php echo $record['weight'] ?? ''; ?>" placeholder="VD: 5.0">
                        </div>
                        <div class="form-group">
                            <label>Nhiệt độ (°C)</label>
                            <input type="number" step="0.1" name="temperature" value="<?php echo $record['temperature'] ?? ''; ?>" placeholder="VD: 37.5">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Triệu chứng / Lý do đến khám</label>
                        <textarea name="symptoms" placeholder="Mô tả chi tiết tình trạng sức khỏe, triệu chứng bất thường..."><?php echo $record['symptoms'] ?? ''; ?></textarea>
                    </div>
                </div>

                <div class="form-section" style="margin-top: 25px;">
                    <h3 class="section-title">2. Chẩn Đoán & Điều Trị</h3>
                    <div class="form-group">
                        <label>Chẩn đoán của bác sĩ</label>
                        <input type="text" name="diagnosis" value="<?php echo $record['diagnosis'] ?? ''; ?>" placeholder="Kết luận bệnh...">
                    </div>
                    <div class="form-group">
                        <label>Phác đồ điều trị / Thuốc sử dụng</label>
                        <textarea name="treatment" placeholder="- Tên thuốc, liều lượng...&#10;- Các thủ thuật đã thực hiện..."><?php echo $record['treatment'] ?? ''; ?></textarea>
                    </div>
                </div>

                <div class="form-section" style="margin-top: 25px;">
                    <h3 class="section-title">3. Ghi Chú / Lời Dặn</h3>
                    <div class="form-group">
                        <label>Lời dặn dò chủ nuôi</label>
                        <textarea name="notes" placeholder="Lịch tái khám, chế độ ăn uống, kiêng khem..."><?php echo $record['notes'] ?? ''; ?></textarea>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="dashboard.php" class="btn-save" style="background:var(--text-muted); color:white; text-decoration:none;">Quay lại</a>
                    <button type="submit" class="btn-save">💾 LƯU HỒ SƠ BỆNH ÁN</button>
                </div>
            </form>

        </div>
    </main>
</div>

<script>
    if (localStorage.getItem('darkMode') === 'enabled') {
        document.body.classList.add('dark-mode');
    }
</script>
</body>
</html>