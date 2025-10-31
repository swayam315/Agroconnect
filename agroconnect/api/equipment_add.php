<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

// --- 1. Security Check: Farmer Only ---
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in.']);
    exit;
}
if ($_SESSION['role'] !== 'farmer') {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Only farmers can add equipment.']);
    exit;
}

$response = ['status' => 'error', 'message' => 'An error occurred.'];
$owner_id = $_SESSION['user_id'];
$hub_id = $_SESSION['hub_id'];

try {
    // --- 2. Get Data from POST (form-data) ---
    // Note: For file uploads, we use $_POST, not JSON input
    if (empty($_POST['equipment_name']) || empty($_POST['rental_price']) || empty($_POST['price_unit'])) {
        throw new Exception('Name, price, and unit are required.');
    }

    $equipment_name = $_POST['equipment_name'];
    $description = $_POST['description'] ?? '';
    $rental_price = (float)$_POST['rental_price'];
    $price_unit = $_POST['price_unit']; // 'per_hour' or 'per_day'

    // --- 3. Handle File Upload ---
    $image_url = null; // Default if no image
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        
        $upload_dir = '../uploads/equipment/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Create a unique filename
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $unique_filename = uniqid('equip_', true) . '.' . $file_ext;
        $target_file = $upload_dir . $unique_filename;

        // Validate file type and size (e.g., allow jpg, png, jpeg)
        $allowed_types = ['jpg', 'jpeg', 'png'];
        if (!in_array($file_ext, $allowed_types)) {
            throw new Exception('Invalid file type. Only JPG, JPEG, and PNG are allowed.');
        }

        // Move the file
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            // We store the *relative path* to be used by the frontend
            $image_url = 'uploads/equipment/' . $unique_filename;
        } else {
            throw new Exception('Failed to upload image.');
        }
    }

    // --- 4. Insert into Database ---
    $sql = "INSERT INTO equipment (owner_id, equipment_name, description, image_url, rental_price, price_unit, current_hub_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $owner_id,
        $equipment_name,
        $description,
        $image_url,
        $rental_price,
        $price_unit,
        $hub_id
    ]);

    $response['status'] = 'success';
    $response['message'] = 'Equipment listed successfully!';

} catch (Exception $e) {
    http_response_code(400); // Bad Request
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>