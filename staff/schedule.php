<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$user_id = $_SESSION['user_id'];
$msg = "";

// Xử lý lưu lịch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_schedule'])) {
    $week_start = $_POST['week_start'];
    $week_end = $_POST['week_end'];
    $shifts = $_POST['shift'] ?? []; 

    $conn->query("DELETE FROM work_schedules WHERE doctor_id = $user_id AND work_date BETWEEN '$week_start' AND '$week_end'");

    if (!empty($shifts)) {
        $stmt = $conn->prepare("INSERT INTO work_schedules (doctor_id, work_date, shift_type) VALUES (?, ?, ?)");
        foreach ($shifts as $date => $types) {
            foreach ($types as $type) {
                $stmt->bind_param("iss", $user_id, $date, $type);
                $stmt->execute();
            }
        }
    }
    $msg = "Đã lưu lịch làm việc thành công!";
}

// Logic thời gian
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$monday_ts = strtotime("this week monday") + ($offset * 7 * 86400);
$sunday_ts = $monday_ts + (6 * 86400);
$start_db = date('Y-m-d', $monday_ts);
$end_db = date('Y-m-d', $sunday_ts);
$start_view = date('d/m', $monday_ts);
$end_view = date('d/m', $sunday_ts);

// Lấy dữ liệu lịch
$registered = [];
$res = $conn->query("SELECT work_date, shift_type FROM work_schedules WHERE doctor_id = $user_id AND work_date BETWEEN '$start_db' AND '$end_db'");
while ($row = $res->fetch_assoc()) {
    $registered[$row['work_date']][] = $row['shift_type'];
}

// Lấy user info
$user = $conn->query("SELECT name, specialty, image FROM doctors WHERE id = $user_id")->fetch_assoc();
$avatar_url = "../" . ($user['image'] ?? 'assets/img/default-avatar.png');
if (!file_exists($avatar_url) || empty($user['image'])) {
    $avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($user['name']) . "&background=0097a7&color=fff&size=128";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch làm việc</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/staff-style.css">
    <style>
        .week-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .schedule-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; text-align: center; }
        .day-col { background: var(--bg-card); border-radius: 8px; padding: 15px 10px; border: 1px solid var(--border-color); display: flex; flex-direction: column; }
        .day-name { font-weight: bold; color: var(--primary); margin-bottom: 5px; display: block; }
        .day-date { font-size: 12px; color: var(--text-muted); margin-bottom: 15px; display: block; }
        .nav-week-btn { background: #607d8b; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 14px; }
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
            <li><a href="emr-list.php">📝 Bệnh án điện tử</a></li>
            <li class="active"><a href="schedule.php">🕒 Lịch làm việc</a></li>
            <li><a href="logout.php" class="logout">Đăng xuất</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="top-bar">
            <h2>Đăng Ký Lịch Làm Việc</h2>
        </header>

        <div class="schedule-section">
            <?php if($msg): ?><div style="background:#d4edda; color:#155724; padding:15px; margin-bottom:20px; border-radius:8px;">✅ <?php echo $msg; ?></div><?php endif; ?>

            <div class="week-header">
                <a href="?offset=<?php echo $offset - 1; ?>" class="nav-week-btn">◀ Tuần trước</a>
                <h3>Từ <?php echo $start_view; ?> đến <?php echo $end_view; ?></h3>
                <a href="?offset=<?php echo $offset + 1; ?>" class="nav-week-btn">Tuần sau ▶</a>
            </div>

            <form action="" method="POST">
                <input type="hidden" name="save_schedule" value="1">
                <input type="hidden" name="week_start" value="<?php echo $start_db; ?>">
                <input type="hidden" name="week_end" value="<?php echo $end_db; ?>">

                <div class="schedule-grid">
                    <?php 
                    $days = ['Mon'=>'Thứ 2','Tue'=>'Thứ 3','Wed'=>'Thứ 4','Thu'=>'Thứ 5','Fri'=>'Thứ 6','Sat'=>'Thứ 7','Sun'=>'CN'];
                    for ($i=0; $i<7; $i++) {
                        $ts = $monday_ts + ($i*86400);
                        $d_code = date('D', $ts);
                        $d_db = date('Y-m-d', $ts);
                        $d_view = date('d/m', $ts);
                        
                        $sang = (isset($registered[$d_db]) && in_array('Sáng', $registered[$d_db])) ? 'checked' : '';
                        $chieu = (isset($registered[$d_db]) && in_array('Chiều', $registered[$d_db])) ? 'checked' : '';
                        $cls_sang = $sang ? 'selected' : '';
                        $cls_chieu = $chieu ? 'selected' : '';
                    ?>
                    <div class="day-col">
                        <span class="day-name" style="<?php echo ($d_code=='Sun')?'color:#ef5350':''; ?>"><?php echo $days[$d_code]; ?></span>
                        <span class="day-date"><?php echo $d_view; ?></span>
                        <?php if ($d_code != 'Sun'): ?>
                            <label class="shift-box <?php echo $cls_sang; ?>">
                                <input type="checkbox" name="shift[<?php echo $d_db; ?>][]" value="Sáng" <?php echo $sang; ?> style="display:none"> Sáng
                            </label>
                            <label class="shift-box <?php echo $cls_chieu; ?>">
                                <input type="checkbox" name="shift[<?php echo $d_db; ?>][]" value="Chiều" <?php echo $chieu; ?> style="display:none"> Chiều
                            </label>
                        <?php else: ?>
                            <div style="padding:20px 0; color:#ef5350; font-size:13px;">Nghỉ</div>
                        <?php endif; ?>
                    </div>
                    <?php } ?>
                </div>

                <div style="text-align:center; margin-top:20px;">
                    <button type="submit" class="btn-save-schedule">LƯU LỊCH ĐĂNG KÝ</button>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
    if (localStorage.getItem('darkMode') === 'enabled') {
        document.body.classList.add('dark-mode');
    }
    const boxes = document.querySelectorAll('.shift-box');
    boxes.forEach(box => {
        const inp = box.querySelector('input');
        box.addEventListener('click', e => { if(e.target!==inp) {} });
        inp.addEventListener('change', () => {
            if(inp.checked) box.classList.add('selected');
            else box.classList.remove('selected');
        });
    });
</script>
</body>
</html>