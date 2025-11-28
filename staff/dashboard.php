<?php
session_start();
require_once '../config/db.php';

// 1. KIỂM TRA BẢO MẬT
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// --- LẤY THÔNG TIN USER (AVATAR) ---
$stmt = $conn->prepare("SELECT name, specialty, image FROM doctors WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

$avatar_url = "../" . ($user_data['image'] ?? 'assets/img/default-avatar.png');
if (!file_exists($avatar_url) || empty($user_data['image'])) {
    $avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($user_data['name']) . "&background=random&size=128";
}

// --- LẤY DANH SÁCH LỊCH HẸN HÔM NAY TỪ DB ---
$today = date('Y-m-d');
// Lấy các lịch hẹn trong ngày hôm nay, sắp xếp theo giờ
$sql_bookings = "SELECT * FROM bookings WHERE appointment_date = ? ORDER BY appointment_time ASC";
$stmt_b = $conn->prepare($sql_bookings);
$stmt_b->bind_param("s", $today);
$stmt_b->execute();
$result_bookings = $stmt_b->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bác sĩ Dashboard - PetCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/staff-style.css">
    <style>
        .reason-text { font-size: 12px; color: #777; font-style: italic; display: block; margin-top: 5px; }
        /* Style cho các trạng thái */
        .status.pending { color: #f57c00; font-weight: 600; } /* Chờ Check-in */
        .status.waiting { color: #0097a7; font-weight: 600; } /* Đang đợi khám */
        .status.completed { color: #388e3c; font-weight: 600; } /* Đã xong */
        .status.cancelled { color: #c62828; background: #ffebee; padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 12px; }
    </style>
</head>
<body>

<div class="admin-layout">
    <aside class="sidebar">
        <div class="brand">🐾 PetCare <span class="badge">Doctor</span></div>
        
        <a href="staff-profile.php" class="user-panel" style="text-decoration: none;">
            <img src="<?php echo $avatar_url; ?>" alt="Avatar"> 
            <div class="info">
                <p>Xin chào,</p>
                <h4><?php echo htmlspecialchars($user_data['name']); ?></h4>
                <small style="color:#b0bec5; font-size: 12px;"><?php echo htmlspecialchars($user_data['specialty']); ?></small>
            </div>
        </a>

        <ul class="menu">
            <li class="active"><a href="dashboard.php">📅 Lịch hẹn hôm nay</a></li>
            <li><a href="emr-list.php">📝 Bệnh án điện tử</a></li>
            <li><a href="schedule.php">🕒 Lịch làm việc</a></li>
            <li><a href="logout.php" class="logout">Đăng xuất</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="top-bar">
            <h2>Quản lý Lịch hẹn (Nghiệp vụ)</h2>
            <div class="date-display">Hôm nay: <b><?php echo date('d/m/Y'); ?></b></div>
        </header>

        <div class="stats-grid">
            <div class="stat-card"><h3><?php echo $result_bookings->num_rows; ?></h3><p>Tổng lịch hôm nay</p></div>
        </div>

        <section class="schedule-section">
            <div class="section-header">
                <h3>Danh sách bệnh nhân hôm nay</h3>
                <button class="btn-refresh" onclick="location.reload()">Làm mới</button>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Giờ</th>
                            <th>Khách hàng</th>
                            <th>Thú cưng</th>
                            <th>Dịch vụ</th> <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_bookings->num_rows > 0): ?>
                            <?php while($row = $result_bookings->fetch_assoc()): 
                                // Xử lý hiển thị tên dịch vụ (Giả sử bạn có map id -> tên, hoặc join bảng)
                                // Ở đây mình tạm hiển thị ID hoặc map đơn giản
                                $service_names = [1 => 'Khám tổng quát', 2 => 'Tiêm phòng', 3 => 'Phẫu thuật'];
                                $service_name = $service_names[$row['service_id']] ?? 'Dịch vụ khác';
                                
                                $status = $row['status'];
                                $row_class = ($status == 'waiting') ? 'active-row' : '';
                                $row_opacity = ($status == 'cancelled') ? '0.6' : '1';
                            ?>
                            <tr id="row-<?php echo $row['id']; ?>" class="<?php echo $row_class; ?>" style="opacity: <?php echo $row_opacity; ?>">
                                <td><b><?php echo date('H:i', strtotime($row['appointment_time'])); ?></b></td>
                                <td><?php echo htmlspecialchars($row['fullname']); ?><br><small><?php echo htmlspecialchars($row['phone']); ?></small></td>
                                <td>🐶 <?php echo htmlspecialchars($row['pet_name']); ?></td>
                                <td><span class="tag service"><?php echo $service_name; ?></span></td>
                                
                                <td>
                                    <?php if($status == 'pending'): ?>
                                        <span class="status pending" id="status-<?php echo $row['id']; ?>">Chờ Check-in</span>
                                    <?php elseif($status == 'waiting' || $status == 'confirmed'): ?>
                                        <span class="status waiting" id="status-<?php echo $row['id']; ?>">Đang đợi khám</span>
                                    <?php elseif($status == 'completed'): ?>
                                        <span class="status completed">Đã xong</span>
                                    <?php elseif($status == 'cancelled'): ?>
                                        <span class="status cancelled">Đã hủy</span>
                                    <?php endif; ?>
                                </td>

                                <td id="action-<?php echo $row['id']; ?>">
                                    <?php if($status == 'pending'): ?>
                                        <button class="btn-action checkin" onclick="handleCheckIn(<?php echo $row['id']; ?>)">✅ Check-in</button>
                                        <button class="btn-action cancel" onclick="handleCancel(<?php echo $row['id']; ?>)">❌ Hủy</button>
                                    <?php elseif($status == 'waiting' || $status == 'confirmed'): ?>
                                        <a href="medical-record.php?id=<?php echo $row['id']; ?>" class="btn-action exam" style="display:inline-block; text-decoration:none;">🩺 Khám ngay</a>
                                    <?php elseif($status == 'completed'): ?>
                                        <a href="medical-record.php?id=<?php echo $row['id']; ?>&view=true" class="btn-action view" style="display:inline-block; text-decoration:none;">👁️ Xem hồ sơ</a>
                                    <?php elseif($status == 'cancelled'): ?>
                                        <span class="reason-text">Lịch đã hủy</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center;">Hôm nay chưa có lịch hẹn nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<script>
    // --- ĐỒNG BỘ DARK MODE ---
    if (localStorage.getItem('darkMode') === 'enabled') {
        document.body.classList.add('dark-mode');
    }

    // --- API UPDATE STATUS ---
    async function updateStatusAPI(id, status) {
        try {
            const response = await fetch('update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, status: status })
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || 'HTTP error: ' + response.status);
            }
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error || 'API returned error');
            }
            
            return true;
        } catch (error) {
            console.error('Lỗi API:', error);
            alert('Có lỗi xảy ra khi lưu dữ liệu: ' + error.message);
            return false;
        }
    }

    // Hàm xử lý Check-in
    async function handleCheckIn(id) {
        if(confirm('Xác nhận khách hàng đã đến và sẵn sàng khám?')) {
            // Gọi API lưu trạng thái 'waiting'
            const success = await updateStatusAPI(id, 'waiting');
            
            if (success) {
                // Cập nhật giao diện ngay lập tức
                const statusSpan = document.getElementById('status-' + id);
                statusSpan.className = 'status waiting';
                statusSpan.innerText = 'Đang đợi khám';

                const actionTd = document.getElementById('action-' + id);
                actionTd.innerHTML = '<a href="medical-record.php?id='+id+'" class="btn-action exam" style="display:inline-block; text-decoration:none;">🩺 Khám ngay</a>';
                
                document.getElementById('row-' + id).classList.add('active-row');
            }
        }
    }

    // Hàm xử lý Hủy lịch
    async function handleCancel(id) {
        if(confirm("Bạn có chắc muốn hủy lịch này không?")) {
            // Gọi API lưu trạng thái 'cancelled'
            const success = await updateStatusAPI(id, 'cancelled');

            if (success) {
                const statusSpan = document.getElementById('status-' + id);
                statusSpan.className = 'status cancelled'; 
                statusSpan.innerText = 'Đã hủy';

                const actionTd = document.getElementById('action-' + id);
                actionTd.innerHTML = '<span class="reason-text">Lịch đã hủy</span>';
                
                document.getElementById('row-' + id).style.opacity = '0.6';
            }
        }
    }
</script>

</body>
</html>