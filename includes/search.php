<?php
// Search handler - redirects to appropriate service page based on search query
include('../config/db.php');

// Ensure BASE_URL is set
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

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$query_lower = mb_strtolower($query, 'UTF-8');

// Service mapping - keywords to service pages
$service_map = [
    'kham' => ['kham', 'khám', 'chẩn đoán', 'xét nghiệm', 'tổng quát', 'examination', 'diagnosis'],
    'tiem' => ['tiem', 'tiêm', 'vaccine', 'vaccination', 'phòng', 'phòng bệnh', 'tiêm phòng'],
    'phauthuat' => ['phau thuat', 'phẫu thuật', 'surgery', 'phẫu', 'thuật', 'cấp cứu', 'emergency'],
    'spa' => ['spa', 'grooming', 'tắm', 'cắt tỉa', 'làm đẹp', 'chăm sóc', 'tam', 'cat tia'],
    'hotel' => ['hotel', 'lưu trú', 'pet hotel', 'khách sạn', 'luu tru', 'chăm sóc qua đêm'],
    'shop' => ['shop', 'cửa hàng', 'thức ăn', 'phụ kiện', 'cua hang', 'thuc an', 'phu kien', 'pet shop']
];

// Find matching service
$matched_service = null;
foreach ($service_map as $service_file => $keywords) {
    foreach ($keywords as $keyword) {
        if (strpos($query_lower, $keyword) !== false) {
            $matched_service = $service_file;
            break 2; // Break both loops
        }
    }
}

// Redirect to matched service or services page
$base = rtrim($BASE_URL, '/');
if ($matched_service) {
    header("Location: {$base}/service-list/{$matched_service}.php");
    exit;
} else {
    // If no match, redirect to services page with search query
    header("Location: {$base}/services.php?q=" . urlencode($query));
    exit;
}
?>

