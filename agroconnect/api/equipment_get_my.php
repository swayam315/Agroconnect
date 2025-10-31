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

$response = ['status' => 'error', 'message' => 'Could not fetch equipment.'];
$owner_id = $_SESSION['user_id'];

try {
    // --- Get Equipment from DB ---
    $sql = "SELECT * FROM equipment 
            WHERE owner_id = ? 
            ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$owner_id]);
    $equipment = $stmt->fetchAll();

    $response['status'] = 'success';
    $response['message'] = 'Equipment fetched successfully.';
    $response['data'] = $equipment;

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>