<?php
// Set response header to JSON
header('Content-Type: application/json');

// Include the database connection file
require_once 'db_connect.php';

// Initialize a response array
$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

try {
    // --- 1. Get Input Data ---
    // We get the data from the 'php://input' stream, as it's common for JSON payloads
    $input = json_decode(file_get_contents('php://input'), true);

    // Basic validation
    if (empty($input['full_name']) || empty($input['email']) || empty($input['phone']) || empty($input['password']) || empty($input['role']) || empty($input['hub_id'])) {
        throw new Exception('All fields are required.');
    }
    
    // Assign variables
    $full_name = $input['full_name'];
    $email = $input['email'];
    $phone = $input['phone'];
    $password = $input['password'];
    $role = $input['role']; // 'farmer' or 'admin'
    $hub_id = (int)$input['hub_id'];
    $lang = $input['preferred_language'] ?? 'en'; // Default to 'en'

    // --- 2. Check for Existing User ---
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? OR phone_number = ?");
    $stmt->execute([$email, $phone]);
    if ($stmt->fetch()) {
        throw new Exception('Email or phone number already in use.');
    }

    // --- 3. Hash Password ---
    // Never store plain text passwords!
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // --- 4. Insert New User ---
    $sql = "INSERT INTO users (full_name, email, phone_number, password_hash, `role`, hub_id, preferred_language) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        $full_name,
        $email,
        $phone,
        $password_hash,
        $role,
        $hub_id,
        $lang
    ]);

    // --- 5. Send Success Response ---
    $response['status'] = 'success';
    $response['message'] = 'Registration successful! You can now log in.';

} catch (Exception $e) {
    // Handle any errors that occurred
    http_response_code(400); // Bad Request
    $response['message'] = $e->getMessage();
}

// Output the JSON response
echo json_encode($response);
?>