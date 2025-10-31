<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

// --- Security Check: Admin Only ---
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Access denied. Admins only.']);
    exit;
}

$response = ['status' => 'error', 'message' => 'Could not fetch bookings.'];
$admin_hub_id = $_SESSION['hub_id'];

try {
    // --- Get Bookings from DB ---
    // Selects bookings that:
    // 1. Are 'pending'
    // 2. Are for equipment located at the admin's hub
    $sql = "SELECT b.*, e.equipment_name, u.full_name as renter_name
            FROM bookings b
            JOIN equipment e ON b.equipment_id = e.equipment_id
            JOIN users u ON b.renter_id = u.user_id
            WHERE e.current_hub_id = ? 
            AND b.booking_status = 'pending'
            ORDER BY b.start_datetime ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$admin_hub_id]);
    $bookings = $stmt->fetchAll();

    $response['status'] = 'success';
    $response['message'] = 'Pending bookings fetched.';
    $response['data'] = $bookings;

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>