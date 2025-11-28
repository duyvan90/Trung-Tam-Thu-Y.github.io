<?php
/**
 * API endpoint to update booking status
 * Used by staff dashboard for check-in and cancel operations
 */
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['id']) || !isset($input['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields: id and status']);
    exit;
}

$booking_id = (int)$input['id'];
$status = trim($input['status']);

// Validate status value
$allowed_statuses = ['pending', 'confirmed', 'waiting', 'completed', 'cancelled'];
if (!in_array($status, $allowed_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid status. Allowed: ' . implode(', ', $allowed_statuses)]);
    exit;
}

// Map 'waiting' to 'confirmed' for database compatibility
// Database enum only has: pending, confirmed, completed, cancelled
// 'waiting' is used in UI but stored as 'confirmed' in database
$db_status = ($status === 'waiting') ? 'confirmed' : $status;

try {
    // Check if booking exists
    $check_stmt = $conn->prepare("SELECT id FROM bookings WHERE id = ?");
    $check_stmt->bind_param("i", $booking_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Booking not found']);
        exit;
    }
    
    // Update booking status
    $update_stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $update_stmt->bind_param("si", $db_status, $booking_id);
    
    if ($update_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully',
            'data' => [
                'id' => $booking_id,
                'status' => $status
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update status: ' . $conn->error]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>

