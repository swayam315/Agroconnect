<?php
// Start the session to access it
session_start();

// Set response header to JSON
header('Content-Type: application/json');

// Unset all session variables
$_SESSION = [];

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Send a success response
echo json_encode([
    'status' => 'success',
    'message' => 'You have been logged out successfully.'
]);
?>