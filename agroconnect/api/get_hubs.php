<?php
// Set response header to JSON
header('Content-Type: application/json');

// Include the database connection
require_once 'db_connect.php';

// We don't need a session_start() here, as this data is public.
// Anyone (even a non-logged-in user) can see the list of hubs.

$response = ['status' => 'error', 'message' => 'Could not fetch hubs.'];

try {
    // --- 1. Prepare the SQL Query ---
    // We only want to select hubs that are marked as active
    $sql = "SELECT hub_id, hub_name, address, latitude, longitude 
            FROM center_hubs 
            WHERE is_active = 1 
            ORDER BY hub_name ASC";
            
    $stmt = $pdo->prepare($sql);
    
    // --- 2. Execute and Fetch Data ---
    $stmt->execute();
    $hubs = $stmt->fetchAll();

    // --- 3. Send Success Response ---
    $response['status'] = 'success';
    $response['message'] = 'Hubs fetched successfully.';
    $response['data'] = $hubs; // Send the array of hub data

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    $response['message'] = $e->getMessage();
}

// Output the JSON response
echo json_encode($response);
?>