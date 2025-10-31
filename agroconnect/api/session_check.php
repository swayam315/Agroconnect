<?php
// Always start the session first
session_start();

header('Content-Type: application/json');

// Check if the user is logged in by checking our session variable
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user_id'])) {
    
    // User is logged in. Send back their data.
    echo json_encode([
        'status' => 'success',
        'logged_in' => true,
        'user' => [
            'user_id' => $_SESSION['user_id'],
            'full_name' => $_SESSION['full_name'],
            'role' => $_SESSION['role'],
            'hub_id' => $_SESSION['hub_id'],
            'lang' => $_SESSION['preferred_language']
        ]
    ]);

} else {
    // User is not logged in.
    echo json_encode([
        'status' => 'success', // The script ran successfully
        'logged_in' => false
    ]);
}
?>