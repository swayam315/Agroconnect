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

$response = ['status' => 'error', 'message' => 'Could not fetch notifications.'];
$user_id = $_SESSION['user_id'];

try {
    // --- Get Notifications from DB ---
    $sql = "SELECT * FROM notifications
            WHERE user_id = ? AND is_read = 0
            ORDER BY created_at DESC
            LIMIT 20"; // Limit to the most recent 20 unread
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();
    
    // We decode the JSON params back into an array for the frontend
    foreach ($notifications as &$notification) {
        $notification['message_params'] = json_decode($notification['message_params'], true);
    }

    $response['status'] = 'success';
    $response['data'] = $notifications;

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>