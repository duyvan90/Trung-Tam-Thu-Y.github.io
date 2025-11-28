<?php
// If accessed directly (not through index.php), include config
if (!function_exists('sendSuccess')) {
    require_once __DIR__ . '/config.php';
}

try {
    // Handle both /api/services.php?id=1 and /api/services/1
    $id = (isset($segments) && !empty($segments)) ? $segments[0] : ($_GET['id'] ?? null);
    
    if ($method === 'GET') {
        if ($id) {
            // Get single service with doctors
            $service = getSingleResult(
                "SELECT * FROM services WHERE id = ?",
                [$id]
            );
            
            if (!$service) {
                sendError('Service not found', 404);
            }
            
            // Get doctors for this service
            $doctors = getResults(
                "SELECT d.* FROM doctors d 
                 INNER JOIN doctor_services ds ON d.id = ds.doctor_id 
                 WHERE ds.service_id = ? 
                 ORDER BY d.name",
                [$id]
            );
            
            $service['doctors'] = $doctors;
            sendSuccess($service);
        } else {
            // Get all services
            $services = getResults("SELECT * FROM services ORDER BY name");
            
            // Get doctors for each service
            foreach ($services as &$service) {
                $doctors = getResults(
                    "SELECT d.* FROM doctors d 
                     INNER JOIN doctor_services ds ON d.id = ds.doctor_id 
                     WHERE ds.service_id = ? 
                     ORDER BY d.name",
                    [$service['id']]
                );
                $service['doctors'] = $doctors;
            }
            
            sendSuccess($services);
        }
    } else {
        sendError('Method not allowed', 405);
    }
} catch (Exception $e) {
    sendError($e->getMessage(), 500);
}
?>

