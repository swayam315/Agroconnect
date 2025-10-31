<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'notification_helper.php';

// --- Security Check: Admin Only ---
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Access denied. Admins only.']);
    exit;
}

$response = ['status' => 'error', 'message' => 'An error occurred.'];
$admin_hub_id = $_SESSION['hub_id'];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['booking_id']) || empty($input['delivery_status'])) {
        throw new Exception('Booking ID and new delivery status are required.');
    }
    
    $booking_id = (int)$input['booking_id'];
    $new_status = $input['delivery_status']; // e.g., 'out_for_delivery', 'delivered'
    $valid_statuses = ['pending', 'out_for_delivery', 'delivered', 'returned'];

    if (!in_array($new_status, $valid_statuses)) {
        throw new Exception('Invalid delivery status provided.');
    }

    // --- 1. Get Booking Details (and verify hub) ---
    $sql_get = "SELECT b.renter_id, e.current_hub_id, e.equipment_name
                FROM bookings b
                JOIN equipment e ON b.equipment_id = e.equipment_id
                WHERE b.booking_id = ?";
    $stmt_get = $pdo->prepare($sql_get);
    $stmt_get->execute([$booking_id]);
    $booking = $stmt_get->fetch();

    if (!$booking) {
        throw new Exception('Booking not found.');
    }
    if ($booking['current_hub_id'] != $admin_hub_id) {
        throw new Exception('You do not have permission to manage this booking.');
    }

    // --- 2. Update Delivery Status ---
    $sql_update = "UPDATE bookings SET delivery_status = ? WHERE booking_id = ?";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute([$new_status, $booking_id]);
    
    // --- 3. Create Notification for the Farmer ---
    create_notification(
        $pdo,
        $booking['renter_id'],
        'delivery_update',
        [
            'booking_id' => $booking_id, 
            'equipment_name' => $booking['equipment_name'],
            'new_status' => $new_status
        ]
    );

    $response['status'] = 'success';
    $response['message'] = 'Delivery status updated!';

} catch (Exception $e) {
    http_response_code(400);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>