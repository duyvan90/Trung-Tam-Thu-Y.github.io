<?php 
include('../config/db.php'); // Bao gồm kết nối DB và các hàm truy vấn (đã session_start nếu cần)

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    // Chuyển hướng người dùng đến trang đăng nhập nếu chưa đăng nhập
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$bookings = [];
$error_message = '';

// Truy vấn để lấy lịch sử đặt lịch của người dùng, kết hợp với tên dịch vụ và tên bác sĩ
// CHỈ hiển thị booking có user_id (không hiển thị guest booking - user_id = NULL)
$sql = "SELECT 
            b.id, 
            b.pet_name, 
            b.pet_type, 
            b.appointment_date, 
            b.appointment_time, 
            b.note, 
            b.status,
            s.name AS service_name,
            d.name AS doctor_name
        FROM bookings b
        LEFT JOIN services s ON b.service_id = s.id
        LEFT JOIN doctors d ON b.doctor_id = d.id
        WHERE b.user_id = ? AND b.user_id IS NOT NULL
        ORDER BY b.appointment_date DESC, b.appointment_time DESC";

try {
    // **LƯU Ý QUAN TRỌNG:** // Trong bảng 'bookings' hiện tại trong schema.sql bạn cung cấp, KHÔNG CÓ cột 'user_id'.
    // Tôi đang GIẢ ĐỊNH bạn đã thêm cột 'user_id' vào bảng 'bookings' 
    // để liên kết lịch hẹn với người dùng đã đăng ký. 
    // Nếu chưa, bạn cần chạy lệnh SQL sau: 
    // ALTER TABLE bookings ADD COLUMN user_id INT NULL AFTER email, ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
    
    // Giả sử hàm getResults có thể thực hiện truy vấn với prepared statement
    $bookings = getResults($sql, [$user_id]);

} catch (Exception $e) {
    $error_message = 'Lỗi truy vấn dữ liệu lịch sử đặt lịch.';
}

// Hàm chuyển trạng thái ENUM sang tiếng Việt
function getStatusVietnamese($status) {
    switch ($status) {
        case 'pending': return '<span class="status-pending">Chờ xác nhận</span>';
        case 'confirmed': return '<span class="status-confirmed">Đã xác nhận</span>';
        case 'completed': return '<span class="status-completed">Hoàn thành</span>';
        case 'cancelled': return '<span class="status-cancelled">Đã hủy</span>';
        default: return $status;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch sử đặt lịch - PetCare</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .status-pending { color: orange; font-weight: 600; }
        .status-confirmed { color: blue; font-weight: 600; }
        .status-completed { color: green; font-weight: 600; }
        .status-cancelled { color: red; font-weight: 600; text-decoration: line-through; }
        .history-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .history-table th, .history-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .history-table th { background-color: #f2f2f2; }
    </style>
</head>
<body>

<?php include('../includes/header.php'); ?>

<section class="banner-new">
    <div class="container banner-inner-sub">
        <h1>LỊCH SỬ ĐẶT LỊCH</h1>
        <p>Xem lại các lịch hẹn bạn đã đặt tại PetCare.</p>
    </div>
</section>

<main class="container">
    <section class="section history-section">
        <h2 class="section-title-mini">LỊCH SỬ</h2>
        <h2 class="section-title">Các Cuộc Hẹn Của Bạn</h2>
        
        <?php if ($error_message): ?>
        <div class="notice error-notice" style="display:block; background-color: #ffebee; color: #c62828; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>
        
        <?php if (empty($bookings)): ?>
            <div class="notice info-notice" style="background-color: #e3f2fd; color: #1565c0; padding: 15px; border-radius: 5px; text-align: center;">
                Bạn chưa có lịch hẹn nào. Hãy <a href="booking.php">đặt lịch ngay</a>!
            </div>
        <?php else: ?>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Thú cưng</th>
                        <th>Dịch vụ</th>
                        <th>Ngày & Giờ hẹn</th>
                        <th>Bác sĩ/KTV</th>
                        <th>Ghi chú</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($booking['id']); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($booking['pet_name']); ?></strong> 
                            (<?php echo htmlspecialchars($booking['pet_type']); ?>)
                        </td>
                        <td>
                            <?php 
                            // Nếu service_id là NULL (ví dụ: Spa), b.service_name là NULL.
                            // Cần hiển thị 'Chăm sóc & Spa' cho dịch vụ không có service_id trong map
                            if (empty($booking['service_name'])) {
                                echo 'Chăm sóc & Spa';
                            } else {
                                echo htmlspecialchars($booking['service_name']);
                            }
                            ?>
                        </td>
                        <td>
                            <?php echo date('d/m/Y', strtotime($booking['appointment_date'])); ?> lúc 
                            <?php echo date('H:i', strtotime($booking['appointment_time'])); ?>
                        </td>
                        <td>
                            <?php 
                            if (!empty($booking['doctor_name'])) {
                                echo htmlspecialchars($booking['doctor_name']);
                            } else {
                                echo 'Kỹ thuật viên';
                            }
                            ?>
                        </td>
                        <td><?php echo !empty($booking['note']) ? htmlspecialchars($booking['note']) : '-'; ?></td>
                        <td><?php echo getStatusVietnamese($booking['status']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>

<?php include('../includes/footer.php'); ?>

</body>
</html>