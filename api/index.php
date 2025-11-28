<?php
// API Index - Route requests to appropriate endpoints
require_once __DIR__ . '/config.php';

// Handle routing - support both /api/endpoint and /api/endpoint.php
$endpoint = $segments[0] ?? '';

// Remove .php extension if present
$endpoint = str_replace('.php', '', $endpoint);

switch ($endpoint) {
    case 'bookings':
        // Shift segments to remove endpoint name
        array_shift($segments);
        require_once __DIR__ . '/bookings.php';
        break;
    case 'doctors':
        array_shift($segments);
        require_once __DIR__ . '/doctors.php';
        break;
    case 'services':
        array_shift($segments);
        require_once __DIR__ . '/services.php';
        break;
    case 'blogs':
        array_shift($segments);
        require_once __DIR__ . '/blogs.php';
        break;
    case 'contacts':
        array_shift($segments);
        require_once __DIR__ . '/contacts.php';
        break;
    case 'testimonials':
        array_shift($segments);
        require_once __DIR__ . '/testimonials.php';
        break;
    case '':
        // API root - show documentation
        sendResponse([
            'success' => true,
            'message' => 'PetCare API',
            'version' => '1.0',
            'endpoints' => [
                'GET /api/bookings' => 'Get all bookings',
                'GET /api/bookings/{id}' => 'Get single booking',
                'POST /api/bookings' => 'Create new booking',
                'PUT /api/bookings/{id}' => 'Update booking',
                'DELETE /api/bookings/{id}' => 'Delete booking',
                'GET /api/doctors' => 'Get all doctors',
                'GET /api/doctors/{id}' => 'Get single doctor',
                'GET /api/doctors?service_id={id}' => 'Get doctors by service',
                'GET /api/services' => 'Get all services',
                'GET /api/services/{id}' => 'Get single service',
                'GET /api/blogs' => 'Get all blogs',
                'GET /api/blogs/{id}' => 'Get single blog',
                'POST /api/contacts' => 'Create contact message',
                'GET /api/contacts' => 'Get all contacts',
                'GET /api/testimonials' => 'Get all testimonials'
            ]
        ]);
        break;
    default:
        sendError('Endpoint not found', 404);
}
?>

