<?php 
// PHẦN PHP XỬ LÝ DỮ LIỆU ĐƯỢC GIỮ NGUYÊN HOÀN TOÀN
include('../config/db.php');  // db.php đã tự session_start() nếu chưa có

// Ensure BASE_URL is set for header.php
if (!isset($BASE_URL)) {
    $BASE_URL = '/';
    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
        $documentRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
        $projectRoot  = rtrim(str_replace('\\', '/', realpath(__DIR__ . '/..')), '/');
        if ($documentRoot && $projectRoot && strpos($projectRoot, $documentRoot) === 0) {
            $relativePath = trim(substr($projectRoot, strlen($documentRoot)), '/');
            $BASE_URL = '/' . ($relativePath ? $relativePath . '/' : '');
        }
    }
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); 
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';
$username = $phone = $email = '';

// Hàm lấy dữ liệu người dùng
function fetchUserData($conn, $user_id) {
    $stmt = $conn->prepare("SELECT username, phone, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Tải dữ liệu ban đầu
$user_data = fetchUserData($conn, $user_id);
if ($user_data) {
    $username = $user_data['username']; 
    $phone = $user_data['phone'];
    $email = $user_data['email'];
} else {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Xử lý POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $new_username = $_POST['username'] ?? $username;
        $new_phone = $_POST['phone'] ?? $phone;
        $new_email = $_POST['email'] ?? $email;

        if (empty($new_username) || empty($new_phone) || empty($new_email)) {
            $error_message = 'Họ và tên, Số điện thoại và Email không được để trống.';
        } 

        // Kiểm tra trùng Tên đăng nhập
        if (empty($error_message) && $new_username !== $username) {
            // Giả định: $check_sql = "SELECT id FROM users WHERE username = ? AND id != ?";
            // Giả định: $check_user = getSingleResult($check_sql, [$new_username, $user_id]); 
            
            // Thay thế bằng logic an toàn hơn nếu getSingleResult không khả dụng
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $check_stmt->bind_param("si", $new_username, $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            if ($check_result->num_rows > 0) {
                 $error_message = 'Tên đăng nhập này đã có người sử dụng. Vui lòng chọn tên khác.';
            }
        }

        if (empty($error_message)) {
            $update_sql = "UPDATE users SET username = ?, phone = ?, email = ? WHERE id = ?";
            try {
                // Giả định: executeQuery($update_sql, [$new_username, $new_phone, $new_email, $user_id]);
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("sssi", $new_username, $new_phone, $new_email, $user_id);
                $updated = $update_stmt->execute();

                if ($updated) {
                    $success_message = 'Cập nhật thông tin cá nhân thành công!';
                    $user_data = fetchUserData($conn, $user_id);
                    $username = $user_data['username'];
                    $phone = $user_data['phone'];
                    $email = $user_data['email'];
                } else {
                    $error_message = 'Không có gì thay đổi hoặc có lỗi xảy ra.';
                }
            } catch (Exception $e) {
                $error_message = 'Có lỗi xảy ra khi cập nhật. Vui lòng thử lại sau.';
            }
        }
    }

    else if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $password_error = '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $password_error = 'Vui lòng điền đầy đủ mật khẩu hiện tại và mật khẩu mới.';
        } else if ($new_password !== $confirm_password) {
            $password_error = 'Mật khẩu mới và xác nhận mật khẩu không khớp.';
        } else if (strlen($new_password) < 6) {
            $password_error = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
        } else if ($new_password === $current_password) {
            $password_error = 'Mật khẩu mới không được trùng với mật khẩu hiện tại.';
        }

        if (empty($password_error)) {
            // Giả định: $user_auth = getSingleResult("SELECT password FROM users WHERE id = ?", [$user_id]);
            $auth_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $auth_stmt->bind_param("i", $user_id);
            $auth_stmt->execute();
            $user_auth = $auth_stmt->get_result()->fetch_assoc();

            if (!$user_auth || !password_verify($current_password, $user_auth['password'])) {
                $password_error = 'Mật khẩu hiện tại không chính xác.';
            } else {
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE users SET password = ? WHERE id = ?";
                try {
                    // Giả định: executeQuery($update_sql, [$new_hashed_password, $user_id]);
                    $update_pass_stmt = $conn->prepare($update_sql);
                    $update_pass_stmt->bind_param("si", $new_hashed_password, $user_id);
                    $update_pass_stmt->execute();

                    $success_message = 'Đổi mật khẩu thành công!';
                    unset($_POST['current_password'], $_POST['new_password'], $_POST['confirm_password']);
                } catch (Exception $e) {
                    $password_error = 'Có lỗi xảy ra khi cập nhật mật khẩu. Vui lòng thử lại.';
                }
            }
        }

        if ($password_error) {
            $error_message = $password_error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản lý Thông tin cá nhân - PetCare</title>
  <link rel="stylesheet" href="../assets/css/style.css"> 
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  
  <style>
    /* --- CSS RIÊNG CHO PHẦN PROFILE VÀ MODAL --- */
    
    /* Cấu trúc chính - chỉ override cho main content, không ảnh hưởng navbar */
    main.container { width: 90%; max-width: 1100px; margin: 0 auto; padding: 20px 0; }
    
    /* Notice */
    .notice { 
        padding: 10px 15px; 
        margin-bottom: 20px; 
        border-radius: 4px; 
        font-weight: 600; 
        text-align: center;
        width: 100%;
        box-sizing: border-box;
    }
    .error-notice { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .success-notice { 
        background-color: #d4edda; /* Màu xanh nhạt */
        color: #155724; 
        border: 1px solid #c3e6cb; 
        font-size: 1.1em;
    }

    /* Khu vực Form */
    .profile-section {
        /* Đảm bảo nền trắng và đổ bóng cho khu vực chính */
        background-color: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
    }
    
    /* Tiêu đề */
    .section-title { margin-bottom: 30px; } 

    /* Form Inputs - Rất quan trọng để khớp hình ảnh */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr); 
        gap: 20px;
        margin-bottom: 30px;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 400; 
        color: #333;
    }
    .profile-form input {
        width: 100%;
        padding: 8px 10px; 
        border: 1px solid #ccc; 
        border-radius: 4px; 
        box-sizing: border-box; 
        font-size: 1em;
    }
    .profile-form input:disabled {
        background-color: #f2f2f2; /* Màu nền xám nhạt như trong ảnh */
        color: #333;
        cursor: default;
    }
    
    /* --- Các Nút Hành Động --- */
    .profile-actions {
        display: flex;
        align-items: center; 
        gap: 20px;
        flex-wrap: wrap;
        padding-top: 10px;
        border-top: 1px solid #eee; 
    }
    
    .btn {
        padding: 12px 30px;
        border: none;
        border-radius: 100px; /* Bo tròn hết cỡ */
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-transform: uppercase;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2); 
    }
    
    /* Nút CHỈNH SỬA - Xanh Ngọc Đậm (Giống hình ảnh) */
    .primary-btn {
        background-color: #00BCD4; 
        color: white;
        box-shadow: 0 4px 10px rgba(0, 188, 212, 0.4);
    }
    .primary-btn:hover {
        background-color: #00ACC1;
        transform: translateY(-1px);
        box-shadow: 0 6px 12px rgba(0, 188, 212, 0.5);
    }

    /* Nút ĐỔI MẬT KHẨU - Xám Trắng (Giống hình ảnh) */
    .secondary-btn {
        background-color: #f7f7f7;
        color: #333;
        border: 1px solid #ddd;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .secondary-btn:hover {
        background-color: #eee;
        border-color: #ccc;
    }
    
    .logout-text-btn {
        background: none;
        color: #9C27B0; 
        padding: 12px 10px;
        font-weight: 600;
        text-decoration: none;
        margin-left: auto; /* Đẩy sang phải */
    }
    .logout-text-btn:hover {
        color: #7B1FA2;
        text-decoration: underline;
    }

    /* Nút CẬP NHẬT/HỦY - Dạng phẳng, xuất hiện khi chỉnh sửa */
    .flat-success-btn { background-color: #2ecc71; color: white; box-shadow: none; border-radius: 8px; }
    .flat-danger-btn { background-color: #e74c3c; color: white; box-shadow: none; border-radius: 8px; }
    
    /* Modal */
    .modal { display: none; position: fixed; z-index: 999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); overflow: auto; }
    .modal-content { background:#fff; margin:10% auto; padding: 30px; border-radius:10px; width:90%; max-width:400px; position:relative; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
    .modal .close { position:absolute; right:15px; top:10px; font-size:30px; cursor:pointer; color: #aaa; }
    .modal h2 { margin-top: 0; color: #00bcd4; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 20px; }
    .submit-btn { margin-top: 20px; width: 100%; border-radius: 8px; }

    /* Responsive */
    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; }
        .logout-text-btn { margin-left: 0; }
    }
  </style>
</head>
<body>

<?php include('../includes/header.php'); ?>

<section class="banner-new">
  <div class="container banner-inner-sub">
    <h1>QUẢN LÝ TÀI KHOẢN</h1>
    <p>Thông tin cá nhân của bạn.</p>
  </div>
</section>

<main class="container">
  <section class="section profile-section">
    <h2 class="section-title-mini">HỒ SƠ</h2>
    <h2 class="section-title">Thông Tin Cá Nhân</h2>
    
    <?php if ($error_message && !isset($_POST['change_password'])): ?>
      <div class="notice error-notice"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    <?php if ($success_message && !isset($_POST['change_password']) && isset($_POST['update_profile'])): ?>
      <div class="notice success-notice">Cập nhật thông tin cá nhân thành công!</div>
    <?php endif; ?>

    <form id="updateProfileForm" class="form profile-form" method="POST" action="">
      <input type="hidden" name="update_profile" value="1">
      <div class="form-grid">
        <div class="form-group">
          <label for="username">Tên đăng nhập:</label>
          <input type="text" id="username" name="username" disabled required value="<?php echo htmlspecialchars($username); ?>">
        </div>
        <div class="form-group">
          <label for="phone">Số điện thoại:</label>
          <input type="tel" id="phone" name="phone" disabled required value="<?php echo htmlspecialchars($phone); ?>">
        </div>
        <div class="form-group">
          <label for="email">Email:</label>
          <input type="email" id="email" name="email" disabled required value="<?php echo htmlspecialchars($email); ?>">
        </div>
      </div>
    </form>

    <div class="profile-actions">
        <button id="editBtn" type="button" class="btn primary-btn">
            CHỈNH SỬA
        </button>

        <button id="openPasswordModal" type="button" class="btn secondary-btn">
            ĐỔI MẬT KHẨU
        </button>
        
        <button id="saveBtn" type="submit" form="updateProfileForm" class="btn flat-success-btn" style="display:none;">
            CẬP NHẬT THÔNG TIN
        </button>
        <button id="cancelBtn" type="button" class="btn flat-danger-btn" style="display:none;">
            HỦY
        </button>

        <a href="<?php echo isset($BASE_URL) ? rtrim($BASE_URL, '/') : ''; ?>/user/logout.php" class="logout-text-btn">
            ĐĂNG XUẤT
        </a>
    </div>

    <div id="passwordModal" class="modal">
      <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Đổi Mật Khẩu</h2>
        
        <?php if ($error_message && isset($_POST['change_password'])): ?>
          <div class="notice error-notice"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if ($success_message && isset($_POST['change_password'])): ?>
          <div class="notice success-notice">Đổi mật khẩu thành công!</div>
        <?php endif; ?>

        <form class="form profile-form" method="POST" action="">
          <input type="hidden" name="change_password" value="1">
          <div class="form-group">
              <label for="current_password_modal">Mật khẩu hiện tại:</label>
              <input type="password" id="current_password_modal" name="current_password" required>
          </div>
          <div class="form-group">
              <label for="new_password_modal">Mật khẩu mới:</label>
              <input type="password" id="new_password_modal" name="new_password" required>
          </div>
          <div class="form-group">
              <label for="confirm_password_modal">Xác nhận mật khẩu mới:</label>
              <input type="password" id="confirm_password_modal" name="confirm_password" required>
          </div>
          <button type="submit" class="btn secondary-btn submit-btn">ĐỔI MẬT KHẨU</button>
        </form>
      </div>
    </div>

  </section>
</main>

<?php include('../includes/footer.php'); ?>

<script>
// --- LOGIC MODAL ĐỔI MẬT KHẨU ---
const modal = document.getElementById("passwordModal");
const btn = document.getElementById("openPasswordModal");
const span = document.getElementsByClassName("close")[0];

// Mở modal
btn.onclick = () => modal.style.display = "block";

// Đóng modal khi bấm X
span.onclick = () => modal.style.display = "none";

// Đóng modal khi click ngoài
window.onclick = (event) => { 
    if(event.target === modal) {
        modal.style.display = "none"; 
    }
}

// Nếu có lỗi từ POST đổi mật khẩu, mở modal
<?php if ($error_message && isset($_POST['change_password'])): ?>
modal.style.display = "block";
<?php endif; ?>

// Nếu đổi mật khẩu thành công, đóng modal (và reset form)
<?php if ($success_message && isset($_POST['change_password'])): ?>
if (modal.style.display === "block") {
    modal.style.display = "none";
    document.querySelectorAll('#passwordModal input[type="password"]').forEach(input => input.value = '');
}
<?php endif; ?>


// --- LOGIC CHỈNH SỬA THÔNG TIN ---
const editBtn = document.getElementById("editBtn");
const saveBtn = document.getElementById("saveBtn");
const cancelBtn = document.getElementById("cancelBtn");

const usernameInput = document.getElementById("username");
const phoneInput = document.getElementById("phone");
const emailInput = document.getElementById("email");

// Lưu giá trị ban đầu
let oldUsername = usernameInput.value;
let oldPhone = phoneInput.value;
let oldEmail = emailInput.value;

// Hàm chuyển sang chế độ CHỈNH SỬA
function setEditMode(isEditing) {
    usernameInput.disabled = !isEditing;
    phoneInput.disabled = !isEditing;
    emailInput.disabled = !isEditing;

    editBtn.style.display = isEditing ? "none" : "inline-block";
    saveBtn.style.display = isEditing ? "inline-block" : "none";
    cancelBtn.style.display = isEditing ? "inline-block" : "none";
    
    // Ẩn nút ĐỔI MẬT KHẨU và ĐĂNG XUẤT khi đang chỉnh sửa 
    document.getElementById("openPasswordModal").style.display = isEditing ? "none" : "inline-block";
    document.querySelector(".logout-text-btn").style.display = isEditing ? "none" : "inline-block";
    
    // Ẩn các thông báo lỗi/thành công khi bắt đầu chỉnh sửa
    if (isEditing) {
        document.querySelectorAll('.success-notice, .error-notice').forEach(notice => notice.style.display = 'none');
    }
}

// Khi bấm CHỈNH SỬA
editBtn.onclick = () => {
    // Lưu lại giá trị hiện tại trước khi thay đổi
    oldUsername = usernameInput.value;
    oldPhone = phoneInput.value;
    oldEmail = emailInput.value;
    setEditMode(true);
};

// Khi bấm HỦY
cancelBtn.onclick = () => {
    // Khôi phục giá trị cũ
    usernameInput.value = oldUsername;
    phoneInput.value = oldPhone;
    emailInput.value = oldEmail;

    setEditMode(false);
};

// Kiểm tra trạng thái sau khi POST để duy trì chế độ chỉnh sửa nếu có lỗi
<?php if (isset($_POST['update_profile']) && $error_message): ?>
// Nếu có lỗi khi cập nhật, giữ chế độ chỉnh sửa
setEditMode(true);
<?php elseif (isset($_POST['update_profile']) && $success_message): ?>
// Nếu thành công, quay lại chế độ xem
setEditMode(false);
<?php endif; ?>

</script>

</body>
</html>