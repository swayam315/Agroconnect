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
$admin_id = $_SESSION['user_id'];
$admin_hub_id = $_SESSION['hub_id'];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['booking_id'])) {
        throw new Exception('Booking ID is required.');
    }
    $booking_id = (int)$input['booking_id'];

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

    // --- 2. Update Booking Status ---
    $sql_update = "UPDATE bookings 
                   SET booking_status = 'rejected', admin_id = ?
                   WHERE booking_id = ? AND booking_status = 'pending'";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute([$admin_id, $booking_id]);

    if ($stmt_update->rowCount() == 0) {
        throw new Exception('Booking could not be rejected. It may no longer be pending.');
    }
    
    // --- 3. Create Notification for the Farmer ---
    create_notification(
        $pdo,
        $booking['renter_id'],
        'booking_rejected',
        ['booking_id' => $booking_id, 'equipment_name' => $booking['equipment_name']]
    );

    $response['status'] = 'success';
    $response['message'] = 'Booking rejected.';

} catch (Exception $e) {
    http_response_code(400);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>