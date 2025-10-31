<?php
// Start the session *before* any output
session_start();

// Set response header to JSON
header('Content-Type: application/json');

// Include the database connection
require_once 'db_connect.php';

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

try {
    // --- 1. Get Input Data ---
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['login_identifier']) || empty($input['password'])) {
        throw new Exception('Email/Phone and Password are required.');
    }

    $login_identifier = $input['login_identifier']; // This can be email or phone
    $password = $input['password'];

    // --- 2. Find the User ---
    // Check if the identifier is an email or a phone number
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone_number = ?");
    $stmt->execute([$login_identifier, $login_identifier]);
    $user = $stmt->fetch();

    // --- 3. Verify User and Password ---
    if ($user && password_verify($password, $user['password_hash'])) {
        // Password is correct!
        
        // --- 4. Create Session ---
        // Store user data in the session variable
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['hub_id'] = $user['hub_id'];
        $_SESSION['preferred_language'] = $user['preferred_language'];
        $_SESSION['logged_in'] = true;

        // --- 5. Send Success Response ---
        // Send back user data for the frontend to use
        $response['status'] = 'success';
        $response['message'] = 'Login successful!';
        $response['user'] = [
            'user_id' => $user['user_id'],
            'full_name' => $user['full_name'],
            'role' => $user['role'],
            'hub_id' => $user['hub_id'],
            'lang' => $user['preferred_language']
        ];
        
    } else {
        // Invalid credentials
        throw new Exception('Invalid email/phone or password.');
    }

} catch (Exception $e) {
    http_response_code(401); // Unauthorized
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>