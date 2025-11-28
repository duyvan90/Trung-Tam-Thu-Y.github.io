<?php
// Giả định file này chứa kết nối DB và các hàm truy vấn (như getResults)
include('../config/db.php'); 

header('Content-Type: application/json');

// 1. Lấy service_id từ tham số URL
$service_id = $_GET['service_id'] ?? null;

if (empty($service_id)) {
    echo json_encode(['success' => false, 'message' => 'Service ID is required.']);
    exit;
}

// 2. Truy vấn Database để lọc bác sĩ theo service_id
// Sử dụng JOIN giữa doctors và doctor_services
$sql = "SELECT d.id, d.name, d.specialty 
        FROM doctors d
        JOIN doctor_services ds ON d.id = ds.doctor_id
        WHERE ds.service_id = ?";

// Giả sử hàm getResults có thể thực thi truy vấn với tham số an toàn (prepared statement)
$doctors = getResults($sql, [$service_id]);

// 3. Trả về dữ liệu dưới dạng JSON
if ($doctors) {
    echo json_encode(['success' => true, 'data' => $doctors]);
} else {
    // Trả về mảng rỗng nếu không tìm thấy bác sĩ nào cho dịch vụ đó
    echo json_encode(['success' => true, 'data' => []]);
}
?>