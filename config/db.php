<?php
// Ensure session is started before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', '127.0.0.1');   // không kèm :port
define('DB_USER', 'root');
define('DB_PASS', '');  // XAMPP default: empty password. Change if you set a password for MySQL root user
define('DB_NAME', 'petcare_db');

// Detect project base url relative to document root (works even inside subfolders)
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

// Create database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, 3306);
    
    // Set charset to UTF-8 for Vietnamese support
    $conn->set_charset("utf8mb4");
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    // If database doesn't exist, try to create it
    $conn_temp = new mysqli(DB_HOST, DB_USER, DB_PASS);
    if ($conn_temp->connect_error) {
        die("Connection failed: " . $conn_temp->connect_error);
    }
    
    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if ($conn_temp->query($sql) === TRUE) {
        $conn_temp->close();
        // Reconnect to the new database
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset("utf8mb4");
    } else {
        die("Error creating database: " . $conn_temp->error);
    }
}

// Helper function to execute queries safely
function executeQuery($sql, $params = []) {
    global $conn;
    $stmt = $conn->prepare($sql);
    if ($params) {
        $types = str_repeat('s', count($params)); // Default to string type
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

// Helper function to get results
function getResults($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    return $data;
}

// Helper function to get single result
function getSingleResult($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data;
}
?>

