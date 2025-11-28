<?php
// If accessed directly (not through index.php), include config
if (!function_exists('sendSuccess')) {
    require_once __DIR__ . '/config.php';
}

try {
    // Handle both /api/blogs.php?id=1 and /api/blogs/1
    $id = (isset($segments) && !empty($segments)) ? $segments[0] : ($_GET['id'] ?? null);
    
    if ($method === 'GET') {
        if ($id) {
            // Get single blog
            $blog = getSingleResult(
                "SELECT * FROM blogs WHERE id = ? AND status = 'published'",
                [$id]
            );
            
            if (!$blog) {
                sendError('Blog not found', 404);
            }
            
            // Increment views
            executeQuery("UPDATE blogs SET views = views + 1 WHERE id = ?", [$id]);
            $blog['views'] = $blog['views'] + 1;
            
            sendSuccess($blog);
        } else {
            // Get all published blogs
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
            
            $blogs = getResults(
                "SELECT id, title, slug, excerpt, image, author, published_at, views, created_at 
                 FROM blogs 
                 WHERE status = 'published' 
                 ORDER BY published_at DESC 
                 LIMIT ? OFFSET ?",
                [$limit, $offset]
            );
            
            // Get total count
            $total = getSingleResult("SELECT COUNT(*) as total FROM blogs WHERE status = 'published'");
            
            sendSuccess([
                'blogs' => $blogs,
                'total' => $total['total'],
                'limit' => $limit,
                'offset' => $offset
            ]);
        }
    } else {
        sendError('Method not allowed', 405);
    }
} catch (Exception $e) {
    sendError($e->getMessage(), 500);
}
?>

