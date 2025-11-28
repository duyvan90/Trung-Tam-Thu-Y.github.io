<?php
session_start();
require_once '../config/db.php';

// 1. KIỂM TRA BẢO MẬT
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// --- LẤY THÔNG TIN USER ---
$stmt = $conn->prepare("SELECT name, specialty, image FROM doctors WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// Avatar đồng bộ
$avatar_url = "../" . ($user_data['image'] ?? 'assets/img/default-avatar.png');
if (!file_exists($avatar_url) || empty($user_data['image'])) {
    $avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($user_data['name']) . "&background=0097a7&color=fff&size=128";
}

// --- LẤY LỊCH HẸN (LOGIC MỚI: ẨN ĐÃ XONG / ĐÃ HỦY) ---
$today = date('Y-m-d'); 

// Logic: 
// 1. Ngày >= Hôm nay
// 2. Của tôi HOẶC Chưa có bác sĩ
// 3. Trạng thái KHÁC 'completed' (đã xong) VÀ KHÁC 'cancelled' (đã hủy)
$sql_bookings = "SELECT b.*, s.name as service_name 
                 FROM bookings b
                 LEFT JOIN services s ON b.service_id = s.id
                 WHERE b.appointment_date >= ? 
                 AND (b.doctor_id = ? OR b.doctor_id IS NULL OR b.doctor_id = 0)
                 AND b.status != 'completed' 
                 AND b.status != 'cancelled'
                 ORDER BY b.appointment_date ASC, b.appointment_time ASC";

$stmt_b = $conn->prepare($sql_bookings);
$stmt_b->bind_param("si", $today, $user_id);
$stmt_b->execute();
$result_bookings = $stmt_b->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bác sĩ Dashboard - PetCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/staff-style.css">
    <style>
        .reason-text { font-size: 12px; color: #777; font-style: italic; display: block; margin-top: 5px; }
        .status.pending { color: #f57c00; font-weight: 600; }
        .status.waiting { color: #0097a7; font-weight: 600; }
        .status.completed { color: #388e3c; font-weight: 600; }
        .status.cancelled { color: #c62828; background: #ffebee; padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 12px; }
        
        .active-row { background-color: #f0fdf4; border-left: 4px solid #2ecc71; }
        .date-badge { display: inline-block; padding: 4px 8px; background: #e3f2fd; color: #1565c0; border-radius: 4px; font-weight: 600; font-size: 12px; margin-bottom: 4px; }
        .unassigned-badge { background: #ffebee; color: #c62828; font-size: 11px; padding: 2px 6px; border-radius: 4px; border: 1px solid #ffcdd2; display: inline-block; margin-bottom: 5px; }
    </style>
</head>
<body>

<div class="admin-layout">
    <aside class="sidebar">
        <div class="brand">🐾 PetCare <span class="badge">Doctor</span></div>
        
        <a href="staff-profile.php" class="user-panel" style="text-decoration: none;">
            <img src="<?php echo $avatar_url; ?>" alt="Avatar"> 
            <div class="info">
                <h4 style="margin:0; font-size:15px; font-weight:600;"><?php echo htmlspecialchars($user_data['name']); ?></h4>
                <small style="color:#b0bec5; font-size: 12px; display:block; margin-top:2px;"><?php echo htmlspecialchars($user_data['specialty']); ?></small>
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
            <div class="stat-card"><h3><?php echo $result_bookings->num_rows; ?></h3><p>Lịch hẹn cần xử lý</p></div>
        </div>

        <section class="schedule-section">
            <div class="section-header">
                <h3>Danh sách các cuộc hẹn sắp tới</h3>
                <button class="btn-save" onclick="location.reload()" style="padding: 8px 15px; font-size: 14px;">🔄 Làm mới dữ liệu</button>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Khách hàng</th>
                            <th>Thú cưng</th>
                            <th>Dịch vụ</th> 
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_bookings->num_rows > 0): ?>
                            <?php while($row = $result_bookings->fetch_assoc()): 
                                $service_name = $row['service_name'] ?? 'Dịch vụ chung';
                                $status = $row['status'];
                                $row_class = ($status == 'waiting') ? 'active-row' : '';
                                
                                $date_obj = date_create($row['appointment_date']);
                                $date_str = date_format($date_obj, 'd/m/Y');
                                $time_str = date('H:i', strtotime($row['appointment_time']));
                                
                                $is_unassigned = empty($row['doctor_id']);
                            ?>
                            <tr id="row-<?php echo $row['id']; ?>" class="<?php echo $row_class; ?>">
                                <td>
                                    <?php if($is_unassigned): ?>
                                        <span class="unassigned-badge">Chưa có BS</span><br>
                                    <?php endif; ?>
                                    <span class="date-badge"><?php echo $date_str; ?></span><br>
                                    <b><?php echo $time_str; ?></b>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($row['fullname']); ?><br>
                                    <small style="color:var(--primary);"><?php echo htmlspecialchars($row['phone']); ?></small>
                                </td>
                                <td>🐶 <?php echo htmlspecialchars($row['pet_name']); ?> <small>(<?php echo htmlspecialchars($row['pet_type']); ?>)</small></td>
                                <td><span class="tag service"><?php echo $service_name; ?></span></td>
                                
                                <td>
                                    <?php if($status == 'pending'): ?>
                                        <span class="status pending" id="status-<?php echo $row['id']; ?>">⏳ Chờ Check-in</span>
                                    <?php elseif($status == 'waiting' || $status == 'confirmed'): ?>
                                        <span class="status waiting" id="status-<?php echo $row['id']; ?>">🩺 Đang khám</span>
                                    <?php endif; ?>
                                </td>

                                <td id="action-<?php echo $row['id']; ?>">
                                    <?php if($status == 'pending'): ?>
                                        <button class="btn-save" style="background:#2196f3; padding:6px 12px; font-size:12px; margin-right:5px;" onclick="handleCheckIn(<?php echo $row['id']; ?>)">
                                            <?php echo $is_unassigned ? 'Nhận ca & Check-in' : 'Check-in'; ?>
                                        </button>
                                        <button class="btn-save" style="background:#ef5350; padding:6px 12px; font-size:12px;" onclick="handleCancel(<?php echo $row['id']; ?>)">Hủy</button>
                                    <?php elseif($status == 'waiting' || $status == 'confirmed'): ?>
                                        <a href="medical-record.php?id=<?php echo $row['id']; ?>" class="btn-save" style="background:#0097a7; padding:6px 12px; font-size:12px; text-decoration:none;">Vào khám</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding: 40px; color: #999;">
                                    <div style="font-size: 40px; margin-bottom: 10px;">🎉</div>
                                    Tuyệt vời! Bạn đã hoàn thành hết các lịch hẹn (hoặc chưa có lịch mới).
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<script>
    if (localStorage.getItem('darkMode') === 'enabled') {
        document.body.classList.add('dark-mode');
    }

    async function updateStatusAPI(id, status) {
        try {
            const response = await fetch('update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, status: status })
            });
            const result = await response.json();
            if (!result.success) throw new Error(result.error);
            return true;
        } catch (error) {
            alert('Lỗi: ' + error.message);
            return false;
        }
    }

    async function handleCheckIn(id) {
        if(confirm('Xác nhận nhận ca và khách hàng đã đến?')) {
            if (await updateStatusAPI(id, 'waiting')) location.reload();
        }
    }

    async function handleCancel(id) {
        if(confirm("Hủy lịch này?")) {
            if (await updateStatusAPI(id, 'cancelled')) location.reload();
        }
    }
</script>
</body>
</html>