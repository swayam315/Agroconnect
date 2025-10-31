<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

// --- Security Check: Logged-in User ---
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in.']);
    exit;
}

$response = ['status' => 'error', 'message' => 'Could not fetch equipment.'];
$user_id = $_SESSION['user_id'];
$hub_id = $_SESSION['hub_id']; // Get the user's hub

try {
    // --- Get Equipment from DB ---
    // Selects equipment that:
    // 1. Is 'is_active' = 1
    // 2. Does NOT belong to the current user (owner_id != ?)
    // 3. Is located at the same hub as the user (current_hub_id = ?)
    // 4. We JOIN 'users' to get the owner's name.
    $sql = "SELECT e.*, u.full_name as owner_name 
            FROM equipment e
            JOIN users u ON e.owner_id = u.user_id
            WHERE e.owner_id != ? 
            AND e.is_active = 1
            AND e.current_hub_id = ?
            ORDER BY e.equipment_name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $hub_id]);
    $equipment = $stmt->fetchAll();

    $response['status'] = 'success';
    $response['message'] = 'Available equipment fetched successfully.';
    $response['data'] = $equipment;

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>