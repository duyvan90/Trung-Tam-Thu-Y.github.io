<?php
// customers.php
include('../config/db.php');

// Xử lý thêm/sửa/xóa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $fullname = $_POST['fullname'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $pet_name = $_POST['pet_name'] ?? '';
    $pet_type = $_POST['pet_type'] ?? '';
    $doctor_id = $_POST['doctor_id'] ?? null;
    $service_id = $_POST['service_id'] ?? null;
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '09:00:00';

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO bookings 
            (fullname, phone, email, pet_name, pet_type, doctor_id, service_id, appointment_date, appointment_time) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssiiss", $fullname, $phone, $email, $pet_name, $pet_type, $doctor_id, $service_id, $appointment_date, $appointment_time);
        $stmt->execute();
        $stmt->close();
    }

    if ($action === 'edit') {
        $id = $_POST['id'];
        $stmt = $conn->prepare("UPDATE bookings SET fullname=?, phone=?, email=?, pet_name=?, pet_type=?, doctor_id=?, service_id=?, appointment_date=?, appointment_time=? WHERE id=?");
        $stmt->bind_param("sssssiissi", $fullname, $phone, $email, $pet_name, $pet_type, $doctor_id, $service_id, $appointment_date, $appointment_time, $id);
        $stmt->execute();
        $stmt->close();
    }

    if ($action === 'delete') {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM bookings WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: customers.php");
    exit;
}

// Lấy danh sách bác sĩ
$doctors = $conn->query("SELECT id, name FROM doctors")->fetch_all(MYSQLI_ASSOC);
// Lấy danh sách dịch vụ
$services = $conn->query("SELECT id, name FROM services")->fetch_all(MYSQLI_ASSOC);

// Lấy danh sách bookings
$sql = "SELECT b.id, b.fullname, b.pet_name, b.pet_type, b.appointment_date, d.name AS doctor_name, s.name AS service_name
        FROM bookings b
        LEFT JOIN doctors d ON b.doctor_id = d.id
        LEFT JOIN services s ON b.service_id = s.id
        ORDER BY b.appointment_date DESC";
$bookings = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Quản Lý Khách Hàng - PetCare</title>
<link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="css/style.css">
<style>
.pet-avatar { display:inline-block; margin-right:5px; }
.sv-kham { background-color:#3C91E6; color:white; padding:2px 6px; border-radius:4px;}
.sv-tiem { background-color:#FFB400; color:white; padding:2px 6px; border-radius:4px;}
.sv-pt { background-color:#FF4D6D; color:white; padding:2px 6px; border-radius:4px;}
.sv-spa { background-color:#6FCF97; color:white; padding:2px 6px; border-radius:4px;}
</style>
</head>
<body>
<section id="sidebar">
    <a href="#" class="brand"><i class='bx bxs-clinic'></i><span class="text">PETCARE</span></a>
    <ul class="side-menu top">
        <li><a href="services.php"><i class='bx bxs-briefcase-alt-2'></i><span class="text">Dịch Vụ</span></a></li>
        <li><a href="doctors.php"><i class='bx bxs-user-voice'></i><span class="text">Bác Sĩ</span></a></li>
		<li class="active"><a href="customers.php"><i class='bx bxs-group'></i><span class="text">Khách Hàng</span></a></li>
		<li><a href="blogs.php"><i class='bx bxs-book-content'></i><span class="text">Blogs</span></a></li>
    </ul>
</section>

<section id="content">
<nav>
    <i class='bx bx-menu'></i>
    <a href="#" class="nav-link">Quản lý Khách Hàng</a>
</nav>

<main>
    <div class="account-header">
        <div>
            <h1>Danh Sách Khám Bệnh</h1>
            <p>Theo dõi lịch sử khám của khách hàng và thú cưng</p>
        </div>
        <button class="add-btn" onclick="openModal()">Tiếp nhận mới</button>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Khách Hàng</th>
                    <th>Thú Cưng</th>
                    <th>Bác Sĩ</th>
                    <th>Dịch Vụ</th>
                    <th>Ngày</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($bookings as $b): ?>
                <tr>
                    <td><?= htmlspecialchars($b['fullname']) ?></td>
                    <td><span class="pet-avatar"><i class='bx <?= $b['pet_type']=='dog'?'bxs-dog':'bxs-cat' ?>'></i></span><?= htmlspecialchars($b['pet_name']) ?></td>
                    <td><?= htmlspecialchars($b['doctor_name']) ?></td>
                    <td>
                        <?php
                        $cls = "sv-spa";
                        if(strpos($b['service_name'],'Khám')!==false) $cls='sv-kham';
                        elseif(strpos($b['service_name'],'Tiêm')!==false) $cls='sv-tiem';
                        elseif(strpos($b['service_name'],'Phẫu')!==false) $cls='sv-pt';
                        echo "<span class='$cls'>".htmlspecialchars($b['service_name'])."</span>";
                        ?>
                    </td>
                    <td><?= date('d-m-Y', strtotime($b['appointment_date'])) ?></td>
                    <td>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <button type="submit" onclick="return confirm('Xóa bản ghi này?')"><i class='bx bx-trash'></i></button>
                        </form>
                        <button onclick='editBooking(<?= json_encode($b) ?>)'><i class='bx bx-edit'></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
</section>

<!-- Modal Add/Edit -->
<div id="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;">
    <div style="background:white;padding:20px;border-radius:8px;width:500px;max-width:90%;">
        <h2 id="modalTitle">Tiếp nhận ca mới</h2>
        <form method="post" id="bookingForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="bookingId">
            <div>
                <label>Tên Khách Hàng</label>
                <input type="text" name="fullname" id="fullname" required>
            </div>
            <div>
                <label>Số điện thoại</label>
                <input type="text" name="phone" id="phone" required>
            </div>
            <div>
                <label>Email</label>
                <input type="email" name="email" id="email">
            </div>
            <div>
                <label>Tên Thú Cưng</label>
                <input type="text" name="pet_name" id="pet_name" required>
            </div>
            <div>
                <label>Loại</label>
                <select name="pet_type" id="pet_type">
                    <option value="dog">Chó</option>
                    <option value="cat">Mèo</option>
                </select>
            </div>
            <div>
                <label>Bác Sĩ</label>
                <select name="doctor_id" id="doctor_id">
                    <?php foreach($doctors as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Dịch Vụ</label>
                <select name="service_id" id="service_id">
                    <?php foreach($services as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Ngày</label>
                <input type="date" name="appointment_date" id="appointment_date" required>
            </div>
            <div>
                <label>Giờ</label>
                <input type="time" name="appointment_time" id="appointment_time" value="09:00:00" required>
            </div>
            <div style="margin-top:10px;">
                <button type="button" onclick="closeModal()">Hủy</button>
                <button type="submit">Lưu thông tin</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('modal').style.display='flex';
    document.getElementById('modalTitle').innerText='Tiếp nhận ca mới';
    document.getElementById('formAction').value='add';
    document.getElementById('bookingForm').reset();
}

function closeModal() { document.getElementById('modal').style.display='none'; }

function editBooking(data) {
    openModal();
    document.getElementById('modalTitle').innerText='Chỉnh sửa thông tin';
    document.getElementById('formAction').value='edit';
    document.getElementById('bookingId').value=data.id;
    document.getElementById('fullname').value=data.fullname;
    document.getElementById('phone').value=data.phone;
    document.getElementById('email').value=data.email;
    document.getElementById('pet_name').value=data.pet_name;
    document.getElementById('pet_type').value=data.pet_type;
    document.getElementById('doctor_id').value=data.doctor_id;
    document.getElementById('service_id').value=data.service_id;
    document.getElementById('appointment_date').value=data.appointment_date;
    document.getElementById('appointment_time').value=data.appointment_time;
}
</script>

</body>
</html>
