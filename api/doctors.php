<?php
// Doctors API endpoint
// Can be accessed directly: /api/doctors.php?service_id=1
// Or through router: /api/doctors?service_id=1

// If accessed through router (config.php already included)
if (!function_exists('sendSuccess')) {
    // Direct access - include config
    require_once __DIR__ . '/../config/db.php';
    
    // Helper function for direct access
    function sendSuccess($data, $message = null) {
        $response = ['success' => true];
        if ($message) {
            $response['message'] = $message;
        }
        $response['data'] = $data;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    function sendError($message, $statusCode = 400) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode([
            'success' => false,
            'error' => $message
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}

// Get service_id from query parameter
$service_id = $_GET['service_id'] ?? null;

// If no service_id, return all doctors
if (empty($service_id)) {
    $doctors = getResults("SELECT id, name, specialty, image, email, phone FROM doctors ORDER BY name");
    sendSuccess($doctors);
}

// Validate service_id is numeric
if (!is_numeric($service_id)) {
    sendError('Service ID must be a number', 400);
}

// Query doctors for the specified service
$sql = "SELECT d.id, d.name, d.specialty, d.image, d.email, d.phone 
        FROM doctors d
        INNER JOIN doctor_services ds ON d.id = ds.doctor_id
        WHERE ds.service_id = ?
        ORDER BY d.name";

try {
    $doctors = getResults($sql, [$service_id]);
    
    if ($doctors === false) {
        sendError('Database query failed', 500);
    }
    
    // Always return success with data (even if empty array)
    sendSuccess($doctors ?: []);
    
} catch (Exception $e) {
    sendError('Error fetching doctors: ' . $e->getMessage(), 500);
}
?>