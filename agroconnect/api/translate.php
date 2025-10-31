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

$response = ['status' => 'error', 'message' => 'An error occurred.'];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['text']) || empty($input['target_lang'])) {
        throw new Exception('Source text and target language are required.');
    }

    $source_text = $input['text'];
    $target_lang = $input['target_lang']; // 'hi', 'kn'
    $source_lang = 'en'; // We assume source is always English
    
    // Create a unique key for this specific text
    $message_key = md5($source_text);

    // --- 1. Check Cache First ---
    $sql_check = "SELECT translation_text FROM translations 
                  WHERE message_key = ? AND language_code = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$message_key, $target_lang]);
    $cached = $stmt_check->fetch();

    if ($cached) {
        // --- Found in Cache! ---
        $response['status'] = 'success';
        $response['text'] = $cached['translation_text'];
        $response['source'] = 'cache';
    } else {
        // --- 2. Not in Cache: Call External API ---
        // Using MyMemory API (free, no API key needed)
        $api_url = sprintf(
            "https://api.mymemory.translated.net/get?q=%s&langpair=%s|%s",
            urlencode($source_text),
            $source_lang,
            $target_lang
        );
        
        // Use file_get_contents to call the API
        $api_response_json = @file_get_contents($api_url);
        if ($api_response_json === FALSE) {
            throw new Exception('Could not connect to translation service.');
        }
        
        $api_response = json_decode($api_response_json, true);
        
        if ($api_response['responseStatus'] != 200) {
            throw new Exception('Translation API error: ' . $api_response['responseDetails']);
        }
        
        $translated_text = $api_response['responseData']['translatedText'];

        // --- 3. Save to Cache ---
        $sql_cache = "INSERT INTO translations (message_key, language_code, translation_text)
                      VALUES (?, ?, ?)
                      ON DUPLICATE KEY UPDATE translation_text = ?"; // Avoid race conditions
        $stmt_cache = $pdo->prepare($sql_cache);
        $stmt_cache->execute([$message_key, $target_lang, $translated_text, $translated_text]);

        // --- Return API Result ---
        $response['status'] = 'success';
        $response['text'] = $translated_text;
        $response['source'] = 'api';
    }

} catch (Exception $e) {
    http_response_code(400);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>