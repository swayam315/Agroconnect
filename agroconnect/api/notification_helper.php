<?php
// This file is NOT an API endpoint.
// It's a helper function included by other scripts.

if (!function_exists('create_notification')) {
    /**
     * Creates a new notification in the database.
     *
     * @param PDO $pdo The active database connection.
     * @param int $user_id The ID of the user to notify.
     * @param string $message_key A language-neutral key (e.g., 'booking_approved').
     * @param array $message_params An associative array of data (e.g., ['booking_id' => 123]).
     * @return bool True on success, false on failure.
     */
    function create_notification($pdo, $user_id, $message_key, $message_params = []) {
        try {
            // Convert the params array into a JSON string
            $params_json = json_encode($message_params);

            $sql = "INSERT INTO notifications (user_id, message_key, message_params)
                    VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $message_key, $params_json]);
            
            return true;
        } catch (Exception $e) {
            // In a real app, you might log this error instead of failing silently
            return false;
        }
    }
}
?>