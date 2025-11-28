<?php
// If accessed directly (not through index.php), include config
if (!function_exists('sendSuccess')) {
    require_once __DIR__ . '/config.php';
}

try {
    if ($method === 'POST') {
        // Create new contact
        $required = ['name', 'email', 'message'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                sendError("Field '$field' is required", 400);
            }
        }
        
        $name = $input['name'];
        $email = $input['email'];
        $phone = $input['phone'] ?? null;
        $subject = $input['subject'] ?? null;
        $message = $input['message'];
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendError('Invalid email format', 400);
        }
        
        $sql = "INSERT INTO contacts (name, email, phone, subject, message) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = executeQuery($sql, [$name, $email, $phone, $subject, $message]);
        
        $contact_id = $GLOBALS['conn']->insert_id;
        
        $contact = getSingleResult("SELECT * FROM contacts WHERE id = ?", [$contact_id]);
        
        sendSuccess($contact, 'Contact message sent successfully', 201);
    } elseif ($method === 'GET') {
        // Get all contacts (admin only - you might want to add authentication)
        $status = $_GET['status'] ?? null;
        
        $sql = "SELECT * FROM contacts WHERE 1=1";
        $params = [];
        
        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $contacts = getResults($sql, $params);
        sendSuccess($contacts);
    } else {
        sendError('Method not allowed', 405);
    }
} catch (Exception $e) {
    sendError($e->getMessage(), 500);
}
?>

