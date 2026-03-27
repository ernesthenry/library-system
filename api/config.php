<?php
// api/config.php - Shared configuration and helpers

define('DATA_DIR', __DIR__ . '/../data/');
define('BOOKS_FILE', DATA_DIR . 'books.json');
define('BORROWERS_FILE', DATA_DIR . 'borrowers.json');
define('LOANS_FILE', DATA_DIR . 'loans.json');

// Set CORS and JSON headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Read JSON file
function readData($file) {
    if (!file_exists($file)) return [];
    $content = file_get_contents($file);
    return json_decode($content, true) ?? [];
}

// Write JSON file
function writeData($file, $data) {
    return file_put_contents($file, json_encode(array_values($data), JSON_PRETTY_PRINT));
}

// Send JSON response
function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

// Get next ID
function nextId($items) {
    if (empty($items)) return 1;
    return max(array_column($items, 'id')) + 1;
}

// Get request body
function getBody() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}
