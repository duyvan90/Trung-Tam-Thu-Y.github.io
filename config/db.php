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
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    if ($params) {
        // Auto-detect parameter types: 'i' for integers, 'd' for doubles, 's' for strings
        $types = '';
        $bind_params = [];
        foreach ($params as $param) {
            if ($param === null) {
                // For NULL values, we need to handle them specially
                // MySQLi doesn't bind NULL directly, so we'll use a workaround
                $types .= 's';
                $bind_params[] = '';
            } elseif (is_int($param)) {
                $types .= 'i';
                $bind_params[] = $param;
            } elseif (is_float($param)) {
                $types .= 'd';
                $bind_params[] = $param;
            } else {
                $types .= 's';
                $bind_params[] = $param;
            }
        }
        $stmt->bind_param($types, ...$bind_params);
    }
    
    if (!$stmt->execute()) {
        $error = $stmt->error ?: $conn->error;
        $stmt->close();
        throw new Exception("Execute failed: " . $error);
    }
    
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

