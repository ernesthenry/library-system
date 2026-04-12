<?php
/**
 * api/config.php - Shared Backend Configuration
 * 
 * ROLE:
 * 1. Defines the database credentials.
 * 2. Establishes a secure connection using PDO (PHP Data Objects).
 * 3. Sets necessary HTTP headers for API communication (JSON + CORS).
 * 4. Provides helper functions to simplify database queries and JSON responses.
 */

// --- Database Credentials ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'library_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// 2. ERROR REPORTING
// Set to 1 for development to see SQL/PHP errors; set to 0 for production.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 3. API HEADERS
// Ensures the browser understands we are sending JSON and allows cross-origin if needed.
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight "OPTIONS" requests (required by some browsers for CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    /**
     * Establish a persistent connection to the database using PDO.
     * We use PDO because it supports Prepared Statements, which are vital for 
     * preventing SQL Injection attacks.
     */
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $db = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // If the database is unreachable, return a 500 error and stop.
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// ── DATABASE UTILITIES ───────────────────────────────────────────────────────

/**
 * Executes a SQL query with parameters.
 * Parameters (?) are used to safely inject variables and prevent SQL injection attacks.
 * 
 * @param string $sql - The SQL statement with placeholders.
 * @param array $params - The values to bind to the placeholders.
 * @returns PDOStatement - The prepared and executed statement.
 */
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

/** 
 * Fetches ALL rows matching a query. 
 * @returns array[] - Array of associative arrays.
 */
function fetchAllRows($sql, $params = []) {
    return query($sql, $params)->fetchAll();
}

/** 
 * Fetches a SINGLE row matching a query.
 * @returns array|false - The matching row or false if not found.
 */
function fetchRow($sql, $params = []) {
    return query($sql, $params)->fetch();
}

/** 
 * Executes an INSERT, UPDATE, or DELETE query without returning results.
 */
function executeUpdate($sql, $params = []) {
    query($sql, $params);
}

/** 
 * Returns the ID of the last row inserted into the database.
 * @returns string - The row ID.
 */
function lastId() {
    global $db;
    return $db->lastInsertId();
}

// ── RESPONSE HANDLERS ────────────────────────────────────────────────────────

/**
 * Sends a standardized JSON response to the client and terminates the script.
 * 
 * @param mixed $data - The data (array or object) to send.
 * @param int $code - HTTP Status code (200=OK, 201=Created, 400=Bad Request, etc.)
 */
function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

/**
 * Reads and decodes the raw JSON data sent in the request body.
 * PHP's standard $_POST only handles form-data; use this for 'fetch' JSON requests.
 * 
 * @returns array - The decoded JSON as an associative array.
 */
function getBody() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}