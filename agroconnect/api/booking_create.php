<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';
// We will create this file in the "Notifications" step
// For now, we just include it.
require_once 'notification_helper.php'; 

// --- Security Check: Farmer Only ---
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'farmer') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Only farmers can book equipment.']);
    exit;
}

$response = ['status' => 'error', 'message' => 'An error occurred.'];
$renter_id = $_SESSION['user_id'];

try {
    $input = json_decode(file_get_contents('php://input'), true);

    // --- 1. Validation ---
    $required_fields = ['equipment_id', 'start_datetime', 'end_datetime', 'total_cost'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("$field is required.");
        }
    }
    
    $equipment_id = (int)$input['equipment_id'];
    $start_datetime = $input['start_datetime'];
    $end_datetime = $input['end_datetime'];
    $total_cost = (float)$input['total_cost'];

    if (new DateTime($start_datetime) >= new DateTime($end_datetime)) {
        throw new Exception('End date must be after the start date.');
    }
    if (new DateTime($start_datetime) < new DateTime()) {
        throw new Exception('Booking cannot be in the past.');
    }

    // --- 2. CRITICAL: Booking Conflict Check ---
    // Check for any 'approved' or 'in_progress' bookings that overlap
    $sql_check = "SELECT booking_id FROM bookings
                  WHERE equipment_id = ?
                  AND booking_status IN ('approved', 'in_progress', 'pending')
                  AND (
                      (start_datetime < ? AND end_datetime > ?) OR
                      (start_datetime BETWEEN ? AND ?) OR
                      (end_datetime BETWEEN ? AND ?)
                  )";
    
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([
        $equipment_id,
        $end_datetime, $start_datetime, // Overlaps the whole period
        $start_datetime, $end_datetime, // Starts within the period
        $start_datetime, $end_datetime  // Ends within the period
    ]);

    if ($stmt_check->fetch()) {
        // A conflicting booking was found
        throw new Exception('This equipment is not available for the selected time slot. It may be pending approval or already booked.');
    }

    // --- 3. Insert the New Booking (as 'pending') ---
    $sql_insert = "INSERT INTO bookings (equipment_id, renter_id, start_datetime, end_datetime, total_cost, booking_status)
                   VALUES (?, ?, ?, ?, ?, 'pending')";
    $stmt_insert = $pdo->prepare($sql_insert);
    $stmt_insert->execute([
        $equipment_id,
        $renter_id,
        $start_datetime,
        $end_datetime,
        $total_cost
    ]);
    $new_booking_id = $pdo->lastInsertId();

    // --- 4. Create Notification for the Admin ---
    // Find the admin(s) for the hub where the equipment is
    $sql_admin = "SELECT u.user_id 
                  FROM users u
                  JOIN equipment e ON u.hub_id = e.current_hub_id
                  WHERE e.equipment_id = ? AND u.role = 'admin'";
    $stmt_admin = $pdo->prepare($sql_admin);
    $stmt_admin->execute([$equipment_id]);
    
    while ($admin = $stmt_admin->fetch()) {
        create_notification(
            $pdo,
            $admin['user_id'],
            'new_booking_request',
            ['booking_id' => $new_booking_id, 'renter_name' => $_SESSION['full_name']]
        );
    }
    
    $response['status'] = 'success';
    $response['message'] = 'Booking request submitted successfully! Awaiting admin approval.';

} catch (Exception $e) {
    http_response_code(400);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>