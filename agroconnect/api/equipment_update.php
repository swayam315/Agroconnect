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
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Only farmers can update equipment.']);
    exit;
}

$response = ['status' => 'error', 'message' => 'An error occurred.'];
$owner_id = $_SESSION['user_id'];

try {
    // --- 2. Get Data from POST ---
    if (empty($_POST['equipment_id']) || empty($_POST['equipment_name']) || empty($_POST['rental_price']) || empty($_POST['price_unit'])) {
        throw new Exception('Equipment ID, name, price, and unit are required.');
    }

    $equipment_id = (int)$_POST['equipment_id'];
    $equipment_name = $_POST['equipment_name'];
    $description = $_POST['description'] ?? '';
    $rental_price = (float)$_POST['rental_price'];
    $price_unit = $_POST['price_unit'];

    // --- 3. Verify Equipment Ownership ---
    $stmt = $pdo->prepare("SELECT owner_id, image_url FROM equipment WHERE equipment_id = ?");
    $stmt->execute([$equipment_id]);
    $equipment = $stmt->fetch();

    if (!$equipment) {
        throw new Exception('Equipment not found.');
    }
    if ($equipment['owner_id'] != $owner_id) {
        http_response_code(403);
        throw new Exception('You can only update your own equipment.');
    }

    // --- 4. Handle File Upload (if new image provided) ---
    $image_url = $equipment['image_url']; // Keep existing image by default

    if (isset($_FILES['equipment_image']) && $_FILES['equipment_image']['error'] == 0) {
        $upload_dir = '../uploads/equipment/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Create a unique filename
        $file_ext = strtolower(pathinfo($_FILES['equipment_image']['name'], PATHINFO_EXTENSION));
        $unique_filename = uniqid('equip_', true) . '.' . $file_ext;
        $target_file = $upload_dir . $unique_filename;

        // Validate file type
        $allowed_types = ['jpg', 'jpeg', 'png'];
        if (!in_array($file_ext, $allowed_types)) {
            throw new Exception('Invalid file type. Only JPG, JPEG, and PNG are allowed.');
        }

        // Validate file size (5MB max)
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($_FILES['equipment_image']['size'] > $max_size) {
            throw new Exception('File size exceeds 5MB limit.');
        }

        // Move the file
        if (move_uploaded_file($_FILES['equipment_image']['tmp_name'], $target_file)) {
            // Delete old image if exists
            if ($equipment['image_url'] && file_exists('../' . $equipment['image_url'])) {
                unlink('../' . $equipment['image_url']);
            }
            $image_url = 'uploads/equipment/' . $unique_filename;
        } else {
            throw new Exception('Failed to upload image.');
        }
    }

    // --- 5. Update Database ---
    $sql = "UPDATE equipment
            SET equipment_name = ?, description = ?, image_url = ?, rental_price = ?, price_unit = ?
            WHERE equipment_id = ? AND owner_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $equipment_name,
        $description,
        $image_url,
        $rental_price,
        $price_unit,
        $equipment_id,
        $owner_id
    ]);

    $response['status'] = 'success';
    $response['message'] = 'Equipment updated successfully!';

} catch (Exception $e) {
    http_response_code(400);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
