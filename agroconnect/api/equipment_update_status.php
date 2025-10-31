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

$response = ['status' => 'error', 'message' => 'An error occurred.'];
$owner_id = $_SESSION['user_id'];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['equipment_id']) || !isset($input['is_active'])) {
        throw new Exception('Equipment ID and new status are required.');
    }

    $equipment_id = (int)$input['equipment_id'];
    $is_active = (bool)$input['is_active']; // Cast to boolean (0 or 1)

    // --- Update Database ---
    // We add "AND owner_id = ?" to the query.
    // This is a CRITICAL security check to ensure farmers can ONLY update their OWN equipment.
    $sql = "UPDATE equipment SET is_active = ? 
            WHERE equipment_id = ? AND owner_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$is_active, $equipment_id, $owner_id]);
    
    // Check if any row was actually updated
    if ($stmt->rowCount() > 0) {
        $response['status'] = 'success';
        $response['message'] = 'Equipment status updated!';
    } else {
        throw new Exception('Equipment not found or you do not have permission.');
    }

} catch (Exception $e) {
    http_response_code(400);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>