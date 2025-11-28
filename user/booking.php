<?php 
include('../config/db.php');
// session_start();

// Khởi tạo biến
$success_message = '';
$error_message = '';
$fullname = $phone = $email = '';

// Nếu người dùng đã login, lấy thông tin cá nhân
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT username, phone, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
        $user = $result->fetch_assoc();
        $fullname = $user['username'];
        $phone = $user['phone'];
        $email = $user['email'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $pet_name = $_POST['pet_name'] ?? '';
    $pet_type = $_POST['pet_type'] ?? '';
    $service_name = $_POST['service'] ?? '';
    $doctor_name = $_POST['doctor'] ?? '';
    $appointment_date = $_POST['date'] ?? '';
    $appointment_time = $_POST['time'] ?? '';
    $note = $_POST['note'] ?? '';
    
    // Map service names to IDs
    $service_map = [
        'khám_điều_trị' => 1,
        'tiêm_phòng' => 2,
        'phẫu_thuật' => 3,
        'chẩn_đoán' => 4
    ];
    
    $service_id = $service_map[$service_name] ?? null;
    
    // Get doctor ID by name
    $doctor_id = null;
    // Lấy doctor_id chỉ khi người dùng chọn một bác sĩ cụ thể
    if (!empty($doctor_name) && $doctor_name !== 'Không cần bác sĩ (Kỹ thuật viên)') {
        // Giả sử hàm getSingleResult là an toàn và đã được định nghĩa
        $doctor = getSingleResult("SELECT id FROM doctors WHERE name = ?", [$doctor_name]);
        $doctor_id = $doctor ? $doctor['id'] : null;
    }
    
    // Validate required fields
    if (empty($fullname) || empty($phone) || empty($pet_name) || empty($pet_type) || 
        empty($appointment_date) || empty($appointment_time) || empty($doctor_name)) { 
        // Bổ sung: Kiểm tra cả doctor_name. Vì nếu không phải Spa, doctor là bắt buộc.
        // Đối với Spa, doctor_name sẽ là 'Không cần bác sĩ (Kỹ thuật viên)' nên vẫn OK.
        $error_message = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
    } else {
        // Validate service_id exists
        if ($service_id === null) {
            $error_message = 'Dịch vụ không hợp lệ. Vui lòng chọn lại dịch vụ.';
        } else {
            // Insert booking
            $sql = "INSERT INTO bookings (user_id, fullname, phone, email, pet_name, pet_type, service_id, doctor_id, appointment_date, appointment_time, note) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            try {
                // Build SQL with proper NULL handling - use NULL directly in SQL for optional integer fields
                $sql = "INSERT INTO bookings (user_id, fullname, phone, email, pet_name, pet_type, service_id, doctor_id, appointment_date, appointment_time, note) 
                        VALUES (";
                
                $placeholders = [];
                $bind_params = [];
                $types = '';
                
                // user_id (can be NULL - guest booking)
                if ($current_user_id === null) {
                    $placeholders[] = 'NULL';
                } else {
                    $placeholders[] = '?';
                    $bind_params[] = $current_user_id;
                    $types .= 'i';
                }
                
                // fullname (required)
                $placeholders[] = '?';
                $bind_params[] = $fullname;
                $types .= 's';
                
                // phone (required)
                $placeholders[] = '?';
                $bind_params[] = $phone;
                $types .= 's';
                
                // email (optional)
                if (empty($email)) {
                    $placeholders[] = 'NULL';
                } else {
                    $placeholders[] = '?';
                    $bind_params[] = $email;
                    $types .= 's';
                }
                
                // pet_name (required)
                $placeholders[] = '?';
                $bind_params[] = $pet_name;
                $types .= 's';
                
                // pet_type (required)
                $placeholders[] = '?';
                $bind_params[] = $pet_type;
                $types .= 's';
                
                // service_id (required)
                $placeholders[] = '?';
                $bind_params[] = (int)$service_id;
                $types .= 'i';
                
                // doctor_id (optional)
                if ($doctor_id === null) {
                    $placeholders[] = 'NULL';
                } else {
                    $placeholders[] = '?';
                    $bind_params[] = (int)$doctor_id;
                    $types .= 'i';
                }
                
                // appointment_date (required)
                $placeholders[] = '?';
                $bind_params[] = $appointment_date;
                $types .= 's';
                
                // appointment_time (required)
                $placeholders[] = '?';
                $bind_params[] = $appointment_time;
                $types .= 's';
                
                // note (optional)
                if (empty($note)) {
                    $placeholders[] = 'NULL';
                } else {
                    $placeholders[] = '?';
                    $bind_params[] = $note;
                    $types .= 's';
                }
                
                $sql .= implode(', ', $placeholders) . ")";
                
                // Execute with prepared statement
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                if (!empty($bind_params)) {
                    $stmt->bind_param($types, ...$bind_params);
                }
                
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                
                $success_message = 'Đặt lịch thành công! Chúng tôi sẽ sớm liên hệ lại để xác nhận 🐾';
                
                // Clear form data
                $fullname = $phone = $email = $pet_name = $pet_type = '';
                $service_name = $doctor_name = $appointment_date = $appointment_time = $note = '';
                
            } catch (Exception $e) {
                // Log error for debugging (remove in production or log to file)
                error_log("Booking error: " . $e->getMessage());
                $error_message = 'Có lỗi xảy ra khi đặt lịch: ' . htmlspecialchars($e->getMessage());
                // For production, use generic message:
                // $error_message = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
            }
        }
    }
}

// Get services and doctors from database
$services = getResults("SELECT * FROM services ORDER BY name");
$doctors = getResults("SELECT * FROM doctors ORDER BY name");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Đặt lịch hẹn - PetCare</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php include('../includes/header.php'); ?>


<section class="banner-sub" style="background-image: url('assets/img/banner-booking.jpg');">
  <div class="container banner-inner-sub">
    <h1>ĐẶT LỊCH HẸN TRỰC TUYẾN</h1>
    <p>Chọn dịch vụ, bác sĩ và thời gian phù hợp - chúng tôi sẽ xác nhận sớm nhất!</p>
  </div>
</section>

<main class="container">
  <section class="section booking-section">
    <h2 class="section-title-mini">ĐẶT LỊCH</h2>
    <h2 class="section-title">Điền Thông Tin Cần Thiết</h2>
    
    <?php if ($error_message): ?>
    <div class="notice error-notice" style="display:block; background-color: #ffebee; color: #c62828; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
      <?php echo htmlspecialchars($error_message); ?>
    </div>
    <?php endif; ?>
    
    <?php if ($success_message): ?>
    <div class="notice success-notice" style="display:block; background-color: #e8f5e9; color: #2e7d32; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
      <?php echo htmlspecialchars($success_message); ?>
    </div>
    <?php endif; ?>
    
    <form class="form booking-form" method="POST" action="">
      <div class="form-grid">
        <div class="form-group">
          <label for="fullname">Họ và tên:</label>
          <input type="text" id="fullname" name="fullname" required placeholder="Nguyễn Văn A" value="<?php echo htmlspecialchars($fullname); ?>">
        </div>

        <div class="form-group">
          <label for="phone">Số điện thoại:</label>
          <input type="tel" id="phone" name="phone" required placeholder="0123 456 789" value="<?php echo htmlspecialchars($phone); ?>">
        </div>
        
        <div class="form-group">
          <label for="email">Email (tùy chọn):</label>
          <input type="email" id="email" name="email" placeholder="example@gmail.com" value="<?php echo htmlspecialchars($email); ?>">
        </div>
        
        <div class="form-group">
          <label for="pet_name">Tên thú cưng:</label>
          <input type="text" id="pet_name" name="pet_name" required placeholder="Miu / Cún / Bông..." value="<?php echo isset($_POST['pet_name']) ? htmlspecialchars($_POST['pet_name']) : ''; ?>">
        </div>

        <div class="form-group">
          <label for="pet_type">Giống loài:</label>
          <select id="pet_type" name="pet_type" required>
            <option value="">-- Chọn giống loài --</option>
            <option value="Chó" <?php echo (isset($_POST['pet_type']) && $_POST['pet_type'] === 'Chó') ? 'selected' : ''; ?>>Chó</option>
            <option value="Mèo" <?php echo (isset($_POST['pet_type']) && $_POST['pet_type'] === 'Mèo') ? 'selected' : ''; ?>>Mèo</option>
            <option value="Khác" <?php echo (isset($_POST['pet_type']) && $_POST['pet_type'] === 'Khác') ? 'selected' : ''; ?>>Khác</option>
          </select>
        </div>

        <div class="form-group">
          <label for="service">Dịch vụ:</label>
          <select id="service" name="service" required>
            <option value="">-- Chọn dịch vụ --</option>
            <?php 
            $service_options = [
                'khám_điều_trị' => 'Khám tổng quát & Điều trị',
                'tiêm_phòng' => 'Tiêm phòng & Tẩy giun',
                'phẫu_thuật' => 'Phẫu thuật (Cấp cứu/Ngoại khoa)',
                'chẩn_đoán' => 'Chẩn đoán hình ảnh & Xét nghiệm'
            ];
            foreach ($service_options as $value => $label): 
            ?>
            <option value="<?php echo $value; ?>" <?php echo (isset($_POST['service']) && $_POST['service'] === $value) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($label); ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="doctor">Bác sĩ phụ trách:</label>
          <select id="doctor" name="doctor" required>
            <option value="">-- Vui lòng chọn dịch vụ trước --</option>
            </select>
        </div>

        <div class="form-group">
          <label for="date">Ngày hẹn:</label>
          <input type="date" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo isset($_POST['date']) ? htmlspecialchars($_POST['date']) : ''; ?>">
        </div>

        <div class="form-group">
          <label for="time">Giờ hẹn:</label>
          <input type="time" id="time" name="time" required value="<?php echo isset($_POST['time']) ? htmlspecialchars($_POST['time']) : ''; ?>">
        </div>
      </div>
      
      <div class="form-group full-width">
        <label for="note">Ghi chú thêm (nếu có):</label>
        <textarea id="note" name="note" rows="4" placeholder="Ví dụ: thú cưng sợ tiêm, hoặc cần hỗ trợ vận chuyển..."><?php echo isset($_POST['note']) ? htmlspecialchars($_POST['note']) : ''; ?></textarea>
      </div>

      <button type="submit" class="btn primary-btn full-width submit-btn">GỬI YÊU CẦU ĐẶT LỊCH</button>
    </form>
  </section>
</main>

<?php include('../includes/footer.php'); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const serviceSelect = document.getElementById('service');
    const doctorSelect = document.getElementById('doctor');

    // Service to service ID mapping
    const serviceToId = {
        'khám_điều_trị': 1,
        'tiêm_phòng': 2,
        'phẫu_thuật': 3,
        'chẩn_đoán': 4
    };

    // Load doctors from API when service changes
    async function updateDoctors() {
        const selectedService = serviceSelect.value;
        doctorSelect.innerHTML = '';
        doctorSelect.disabled = true;
        
        let defaultOption = document.createElement('option');
        defaultOption.value = '';
        
        if (!selectedService) {
            defaultOption.textContent = '-- Vui lòng chọn dịch vụ trước --';
            doctorSelect.appendChild(defaultOption);
            return Promise.resolve();
        }

        doctorSelect.disabled = false;
        
        // --- Xử lý cho dịch vụ 'spa' (Chỉ có Kỹ thuật viên) ---
        if (selectedService === 'spa') {
            let option = document.createElement('option');
            option.value = 'Không cần bác sĩ (Kỹ thuật viên)';
            option.textContent = 'Không cần bác sĩ (Kỹ thuật viên)';
            doctorSelect.appendChild(option);
            doctorSelect.value = 'Không cần bác sĩ (Kỹ thuật viên)';
            return Promise.resolve();
        }
        // ---------------------------------------------------------
        
        // --- Xử lý cho các dịch vụ cần bác sĩ (Không có tùy chọn Kỹ thuật viên) ---
        defaultOption.textContent = '-- Đang tải... --';
        doctorSelect.appendChild(defaultOption);

        try {
            const serviceId = serviceToId[selectedService];
            
            // serviceId phải tồn tại ở đây vì 'spa' đã được lọc
            if (!serviceId) { 
                 defaultOption.textContent = '-- Dịch vụ không hợp lệ --';
                 doctorSelect.value = '';
                 return Promise.reject('Invalid service');
            }

            const apiUrl = `../api/doctors.php?service_id=${serviceId}`;
            console.log('Fetching doctors from:', apiUrl);
            
            const response = await fetch(apiUrl);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('API Error Response:', errorText);
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            console.log('API Response:', result);
            
            // Check if result is valid
            if (!result) {
                throw new Error('Invalid response from server');
            }
            
            // Check for API error message
            if (result.error || (result.success === false)) {
                throw new Error(result.error || result.message || 'API returned error');
            }
            
            doctorSelect.innerHTML = '';
            defaultOption.textContent = '-- Chọn bác sĩ phù hợp --';
            doctorSelect.appendChild(defaultOption);
            
            // KHÔNG THÊM TÙY CHỌN KỸ THUẬT VIÊN/KHÔNG CẦN BÁC SĨ TẠI ĐÂY

            if (result.success && result.data && Array.isArray(result.data) && result.data.length > 0) {
                result.data.forEach(doctor => {
                    let option = document.createElement('option');
                    option.value = doctor.name;
                    option.textContent = doctor.name + (doctor.specialty ? ' - ' + doctor.specialty : '');
                    doctorSelect.appendChild(option);
                });
            } else {
                let option = document.createElement('option');
                option.value = '';
                option.textContent = 'Không có bác sĩ phù hợp cho dịch vụ này';
                option.disabled = true;
                doctorSelect.appendChild(option);
                console.warn('No doctors found for service:', serviceId, result);
            }
            
            return Promise.resolve();
        } catch (error) {
            console.error('Error loading doctors:', error);
            doctorSelect.innerHTML = '';
            defaultOption.textContent = '-- Lỗi tải danh sách bác sĩ. Vui lòng thử lại. --';
            defaultOption.disabled = true;
            doctorSelect.appendChild(defaultOption);
            return Promise.reject(error);
        }
    }

    // Initialize
    updateDoctors();

    // Listen for service changes
    serviceSelect.addEventListener('change', updateDoctors);
    
    // Restore selected values if form was submitted with errors
    <?php if (isset($_POST['service'])): ?>
    serviceSelect.value = '<?php echo htmlspecialchars($_POST['service'], ENT_QUOTES); ?>';
    
    // Sửa lỗi: Thay setTimeout bằng .then() để đảm bảo tính đồng bộ
    updateDoctors().then(() => {
        const postDoctor = '<?php echo isset($_POST['doctor']) ? htmlspecialchars($_POST['doctor'], ENT_QUOTES) : ''; ?>';
        
        // Chỉ set giá trị nếu giá trị đã post TỒN TẠI trong danh sách mới
        if (postDoctor && doctorSelect.querySelector(`option[value="${postDoctor}"]`)) {
             doctorSelect.value = postDoctor;
        } else if (postDoctor === 'Không cần bác sĩ (Kỹ thuật viên)') {
             // Khôi phục tùy chọn kỹ thuật viên (Chỉ xảy ra khi chọn Spa)
             doctorSelect.value = 'Không cần bác sĩ (Kỹ thuật viên)';
        }
    }).catch(error => {
        console.error("Error during doctor restoration:", error);
    });
    <?php endif; ?>
});
</script>

</body>
</html>