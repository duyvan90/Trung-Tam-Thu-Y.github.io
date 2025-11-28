<?php
// If accessed directly (not through index.php), include config
if (!function_exists('sendSuccess')) {
    require_once __DIR__ . '/config.php';
}

// Get the action from URL segments
// Handle both /api/bookings.php?id=1 and /api/bookings/1
$id = (isset($segments) && !empty($segments)) ? $segments[0] : ($_GET['id'] ?? null);

try {
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single booking
                $booking = getSingleResult(
                    "SELECT b.*, s.name as service_name, d.name as doctor_name 
                     FROM bookings b 
                     LEFT JOIN services s ON b.service_id = s.id 
                     LEFT JOIN doctors d ON b.doctor_id = d.id 
                     WHERE b.id = ?",
                    [$id]
                );
                
                if (!$booking) {
                    sendError('Booking not found', 404);
                }
                
                sendSuccess($booking);
            } else {
                // Get all bookings with optional filters
                $status = $_GET['status'] ?? null;
                $date = $_GET['date'] ?? null;
                
                $sql = "SELECT b.*, s.name as service_name, d.name as doctor_name 
                        FROM bookings b 
                        LEFT JOIN services s ON b.service_id = s.id 
                        LEFT JOIN doctors d ON b.doctor_id = d.id 
                        WHERE 1=1";
                $params = [];
                
                if ($status) {
                    $sql .= " AND b.status = ?";
                    $params[] = $status;
                }
                
                if ($date) {
                    $sql .= " AND b.appointment_date = ?";
                    $params[] = $date;
                }
                
                $sql .= " ORDER BY b.appointment_date DESC, b.appointment_time DESC";
                
                $bookings = getResults($sql, $params);
                sendSuccess($bookings);
            }
            break;
            
        case 'POST':
            // Create new booking
            $required = ['fullname', 'phone', 'pet_name', 'pet_type', 'appointment_date', 'appointment_time'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    sendError("Field '$field' is required", 400);
                }
            }
            
            $fullname = $input['fullname'];
            $phone = $input['phone'];
            $email = $input['email'] ?? null;
            $pet_name = $input['pet_name'];
            $pet_type = $input['pet_type'];
            $service_id = $input['service_id'] ?? null;
            $doctor_id = $input['doctor_id'] ?? null;
            $appointment_date = $input['appointment_date'];
            $appointment_time = $input['appointment_time'];
            $note = $input['note'] ?? null;
            
            // Validate date format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointment_date)) {
                sendError('Invalid date format. Use YYYY-MM-DD', 400);
            }
            
            // Validate time format
            if (!preg_match('/^\d{2}:\d{2}$/', $appointment_time)) {
                sendError('Invalid time format. Use HH:MM', 400);
            }
            
            // Check if date is in the past
            if (strtotime($appointment_date . ' ' . $appointment_time) < time()) {
                sendError('Cannot book appointments in the past', 400);
            }
            
            $sql = "INSERT INTO bookings (fullname, phone, email, pet_name, pet_type, service_id, doctor_id, appointment_date, appointment_time, note) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = executeQuery($sql, [
                $fullname, $phone, $email, $pet_name, $pet_type, 
                $service_id, $doctor_id, $appointment_date, $appointment_time, $note
            ]);
            
            $booking_id = $GLOBALS['conn']->insert_id;
            
            // Get the created booking
            $booking = getSingleResult(
                "SELECT b.*, s.name as service_name, d.name as doctor_name 
                 FROM bookings b 
                 LEFT JOIN services s ON b.service_id = s.id 
                 LEFT JOIN doctors d ON b.doctor_id = d.id 
                 WHERE b.id = ?",
                [$booking_id]
            );
            
            sendSuccess($booking, 'Booking created successfully', 201);
            break;
            
        case 'PUT':
            // Update booking
            if (!$id) {
                sendError('Booking ID is required', 400);
            }
            
            $booking = getSingleResult("SELECT * FROM bookings WHERE id = ?", [$id]);
            if (!$booking) {
                sendError('Booking not found', 404);
            }
            
            $fields = ['fullname', 'phone', 'email', 'pet_name', 'pet_type', 'service_id', 
                      'doctor_id', 'appointment_date', 'appointment_time', 'note', 'status'];
            $updates = [];
            $params = [];
            
            foreach ($fields as $field) {
                if (isset($input[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }
            
            if (empty($updates)) {
                sendError('No fields to update', 400);
            }
            
            $params[] = $id;
            $sql = "UPDATE bookings SET " . implode(', ', $updates) . " WHERE id = ?";
            
            executeQuery($sql, $params);
            
            // Get updated booking
            $updated_booking = getSingleResult(
                "SELECT b.*, s.name as service_name, d.name as doctor_name 
                 FROM bookings b 
                 LEFT JOIN services s ON b.service_id = s.id 
                 LEFT JOIN doctors d ON b.doctor_id = d.id 
                 WHERE b.id = ?",
                [$id]
            );
            
            sendSuccess($updated_booking, 'Booking updated successfully');
            break;
            
        case 'DELETE':
            // Delete booking
            if (!$id) {
                sendError('Booking ID is required', 400);
            }
            
            $booking = getSingleResult("SELECT * FROM bookings WHERE id = ?", [$id]);
            if (!$booking) {
                sendError('Booking not found', 404);
            }
            
            executeQuery("DELETE FROM bookings WHERE id = ?", [$id]);
            sendSuccess(['id' => $id], 'Booking deleted successfully');
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
} catch (Exception $e) {
    sendError($e->getMessage(), 500);
}
?>

