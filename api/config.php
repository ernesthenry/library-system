<?php
// api/config.php - Shared configuration for MySQL database

// --- Database Credentials ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'library_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // Set your MySQL password here

// Set CORS and JSON headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database Connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $db = new PDO($dsn, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    respond(['error' => 'Database connection failed. Please ensure MySQL is running and credentials are correct.'], 500);
}

// Database Utility Functions
function query($sql, $params = []) {
    global $db;
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        respond(['error' => 'Database Error: ' . $e->getMessage()], 500);
    }
}

function fetchAllRows($sql, $params = []) {
    return query($sql, $params)->fetchAll();
}

function fetchRow($sql, $params = []) {
    return query($sql, $params)->fetch();
}

function executeUpdate($sql, $params = []) {
    query($sql, $params);
}

function lastId() {
    global $db;
    return $db->lastInsertId();
}

// Response and Data Handlers
function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

function getBody() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}