<?php
// If accessed directly (not through index.php), include config
if (!function_exists('sendSuccess')) {
    require_once __DIR__ . '/config.php';
}

try {
    // Handle both /api/testimonials.php?id=1 and /api/testimonials/1
    $id = (isset($segments) && !empty($segments)) ? $segments[0] : ($_GET['id'] ?? null);
    
    if ($method === 'GET') {
        if ($id) {
            // Get single testimonial
            $testimonial = getSingleResult(
                "SELECT * FROM testimonials WHERE id = ? AND status = 'approved'",
                [$id]
            );
            
            if (!$testimonial) {
                sendError('Testimonial not found', 404);
            }
            
            sendSuccess($testimonial);
        } else {
            // Get all approved testimonials
            $testimonials = getResults(
                "SELECT * FROM testimonials 
                 WHERE status = 'approved' 
                 ORDER BY created_at DESC"
            );
            
            sendSuccess($testimonials);
        }
    } elseif ($method === 'POST') {
        // Create new testimonial (will be pending approval)
        $required = ['customer_name', 'content'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                sendError("Field '$field' is required", 400);
            }
        }
        
        $customer_name = $input['customer_name'];
        $content = $input['content'];
        $rating = isset($input['rating']) ? intval($input['rating']) : 5;
        
        // Validate rating
        if ($rating < 1 || $rating > 5) {
            sendError('Rating must be between 1 and 5', 400);
        }
        
        $sql = "INSERT INTO testimonials (customer_name, content, rating) 
                VALUES (?, ?, ?)";
        
        $stmt = executeQuery($sql, [$customer_name, $content, $rating]);
        
        $testimonial_id = $GLOBALS['conn']->insert_id;
        
        $testimonial = getSingleResult("SELECT * FROM testimonials WHERE id = ?", [$testimonial_id]);
        
        sendSuccess($testimonial, 'Testimonial submitted successfully. It will be reviewed before publishing.', 201);
    } else {
        sendError('Method not allowed', 405);
    }
} catch (Exception $e) {
    sendError($e->getMessage(), 500);
}
?>

