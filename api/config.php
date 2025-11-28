<?php
// API Configuration
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database configuration
require_once __DIR__ . '/../config/db.php';

// Helper function to send JSON response
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Helper function to send error response
function sendError($message, $statusCode = 400) {
    sendResponse([
        'success' => false,
        'error' => $message
    ], $statusCode);
}

// Helper function to send success response
function sendSuccess($data, $message = null) {
    $response = ['success' => true];
    if ($message) {
        $response['message'] = $message;
    }
    $response['data'] = $data;
    sendResponse($response);
}

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove base path if needed
$basePath = dirname($_SERVER['SCRIPT_NAME']);
if ($basePath !== '/') {
    $path = str_replace($basePath, '', $path);
}

// Handle /api prefix
if (strpos($path, '/api') === 0) {
    $path = substr($path, 4); // Remove '/api'
}

$path = trim($path, '/');
$segments = $path ? explode('/', $path) : [];

// Ensure segments is always defined
if (!isset($segments)) {
    $segments = [];
}

// Get request body
$input = json_decode(file_get_contents('php://input'), true);
if ($input === null && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    $input = $_POST;
}
?>

