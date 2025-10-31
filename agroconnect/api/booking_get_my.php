<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

// --- Security Check: Farmer Only ---
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'farmer') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Access denied.']);
    exit;
}

$response = ['status' => 'error', 'message' => 'Could not fetch bookings.'];
$renter_id = $_SESSION['user_id'];

try {
    // --- Get Bookings from DB ---
    // We JOIN equipment to get the name and image
    $sql = "SELECT b.*, e.equipment_name, e.image_url
            FROM bookings b
            JOIN equipment e ON b.equipment_id = e.equipment_id
            WHERE b.renter_id = ? 
            ORDER BY b.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$renter_id]);
    $bookings = $stmt->fetchAll();

    $response['status'] = 'success';
    $response['message'] = 'Your bookings fetched successfully.';
    $response['data'] = $bookings;

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>