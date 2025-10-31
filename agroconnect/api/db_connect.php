<?php
// Set your database credentials
define('DB_HOST', 'localhost'); // Or your server address
define('DB_NAME', 'agroconnect_db');
define('DB_USER', 'root');      // Your database username
define('DB_PASS', '');          // Your database password

// Set the default timezone
date_default_timezone_set('Asia/Kolkata');

// Create a new PDO database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    
    // Set PDO attributes for error handling and fetch mode
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // If connection fails, stop the script and show an error
    // In a real production app, you'd log this error, not display it to the user
    http_response_code(500);
    echo json_encode(
        [
            "status" => "error",
            "message" => "Database connection failed: " . $e->getMessage()
        ]
    );
    exit; // Stop script execution
}

// We don't close the $pdo connection here. 
// It will be included in other files and stay open as long as those scripts are running.
?>