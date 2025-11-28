<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$user_id = $_SESSION['user_id'];

// Lấy thông tin user
$stmt_u = $conn->prepare("SELECT name, specialty, image FROM doctors WHERE id = ?");
$stmt_u->bind_param("i", $user_id);
$stmt_u->execute();
$user = $stmt_u->get_result()->fetch_assoc();

// Avatar đồng bộ
$avatar_url = "../" . ($user['image'] ?? 'assets/img/default-avatar.png');
if (!file_exists($avatar_url) || empty($user['image'])) {
    $avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($user['name']) . "&background=0097a7&color=fff&size=128";
}

// Tìm kiếm bệnh án
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT mr.*, b.fullname, b.pet_name, b.pet_type, b.appointment_date, d.name as doctor_name 
        FROM medical_records mr
        JOIN bookings b ON mr.booking_id = b.id
        JOIN doctors d ON mr.doctor_id = d.id
        WHERE b.fullname LIKE ? OR b.phone LIKE ? OR b.pet_name LIKE ?
        ORDER BY mr.created_at DESC";

$like_search = "%$search%";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $like_search, $like_search, $like_search);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Kho Bệnh Án - PetCare Staff</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/staff-style.css">
    <style>
        .search-box { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-box input { flex: 1; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-body); color: var(--text-main); }
        .btn-search { background: var(--primary); color: white; border: none; padding: 0 20px; border-radius: 8px; cursor: pointer; }
    </style>
</head>
<body>

<div class="admin-layout">
    <aside class="sidebar">
        <div class="brand">🐾 PetCare <span class="badge">Doctor</span></div>
        
        <a href="staff-profile.php" class="user-panel" style="text-decoration: none;">
            <img src="<?php echo $avatar_url; ?>" alt="Avatar">
            <div class="info">
                <h4 style="margin:0; font-size:15px; font-weight:600;"><?php echo htmlspecialchars($user['name']); ?></h4>
                <small style="color:#b0bec5; font-size: 12px; display:block; margin-top:2px;"><?php echo htmlspecialchars($user['specialty']); ?></small>
            </div>
        </a>

        <ul class="menu">
            <li><a href="dashboard.php">📅 Lịch hẹn hôm nay</a></li>
            <li class="active"><a href="emr-list.php">📝 Bệnh án điện tử</a></li>
            <li><a href="schedule.php">🕒 Lịch làm việc</a></li>
            <li><a href="logout.php" class="logout">Đăng xuất</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="top-bar">
            <h2>Kho Lưu Trữ Bệnh Án</h2>
            <div class="date-display">Tổng số: <?php echo $result->num_rows; ?> hồ sơ</div>
        </header>

        <div class="schedule-section"> 
            <form action="" method="GET" class="search-box">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm theo tên khách hàng, SĐT hoặc tên thú cưng...">
                <button type="submit" class="btn-search">🔍 Tìm kiếm</button>
            </form>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Ngày khám</th>
                            <th>Mã HS</th>
                            <th>Thú cưng</th>
                            <th>Chẩn đoán</th>
                            <th>Bác sĩ</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($row['appointment_date'])); ?></td>
                                <td>#BA<?php echo str_pad($row['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row['pet_name']); ?> 
                                    <small>(<?php echo htmlspecialchars($row['pet_type']); ?>)</small>
                                </td>
                                <td><?php echo htmlspecialchars($row['diagnosis']); ?></td>
                                <td><?php echo htmlspecialchars($row['doctor_name']); ?></td>
                                <td>
                                    <a href="medical-record.php?id=<?php echo $row['booking_id']; ?>" class="btn-save" style="background:var(--primary); padding:5px 10px; font-size:12px; text-decoration:none;">👁️ Xem lại</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center;">Chưa có hồ sơ bệnh án nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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