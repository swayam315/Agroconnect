<?php
// chatbot.php — Backend proxy for Ollama multilingual chatbot
header("Content-Type: application/json");

// Allow CORS for local development
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$input = json_decode(file_get_contents("php://input"), true);
$userMessage = $input["message"] ?? "";
$lang = $input["lang"] ?? "en";

// 🛑 Guard clause
if (trim($userMessage) === "") {
    echo json_encode(["reply" => ""]);
    exit;
}

// 🔹 Define system context (chat personality)
$context = <<<EOT
You are "AgroBot" — a smart, friendly assistant for the Agroconnect platform,
a digital marketplace where farmers rent, share, and lease agricultural equipment
within their local community.

Your role:
- Help users understand how to rent or share farm equipment.
- Guide them on registration, listing items, and managing rentals.
- Provide simple, trustworthy farming advice and news updates.
- Recommend popular and useful equipment (tractor, rotavator, harvester, tiller).
- Stay polite and refuse unrelated or off-topic questions with:
  "I can only answer farming or Agroconnect-related questions."

Always reply **only** in the same language the user speaks or selects:
English (en), Hindi (hi), or Kannada (kn).
Keep your tone conversational and concise.
EOT;

// 🔹 Send to Ollama (Gemma 2)
$data = [
    "model"  => "gemma2:9b",
    "prompt" => "$context\n\nUser ($lang): $userMessage\nAgroBot ($lang):",
    "stream" => false
];

$ch = curl_init("http://127.0.0.1:11434/api/generate");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
    CURLOPT_POSTFIELDS     => json_encode($data),
]);

$response = curl_exec($ch);
curl_close($ch);

// 🔹 Extract Gemma's reply safely
if (!$response) {
    echo json_encode(["reply" => "⚠️ Unable to connect to the AI engine. Make sure Ollama is running."]);
    exit;
}

$json = json_decode($response, true);
$reply = "";

// Gemma sometimes returns either 'response' (string) or 'message' (array of chunks)
if (isset($json["response"])) {
    $reply = trim($json["response"]);
} elseif (isset($json[0]["response"])) {
    $reply = trim($json[0]["response"]);
} else {
    $reply = "Sorry, I couldn't generate a reply.";
}

echo json_encode(["reply" => $reply]);
?>
