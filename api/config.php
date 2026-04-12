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
define('DB_PASS', ''); // Set your MySQL password here

/**
 * REST API HEADERS:
 * - Content-Type: Tells the browser we are sending JSON data.
 * - Access-Control-*: Enables Cross-Origin Resource Sharing (CORS), allowing
 *   the frontend to talk to the backend even if they are on different ports/domains.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight "OPTIONS" requests (required by some browsers for CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * DATABASE CONNECTION:
 * We use PDO because it is secure (prevents SQL injection) and supports multiple database types.
 */
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $db = new PDO($dsn, DB_USER, DB_PASS);
    
    // Configure PDO to throw exceptions on errors and return data as associative arrays (key: value)
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If connection fails, send a JSON error message back to the frontend
    respond(['error' => 'Database connection failed. Please ensure MySQL is running and credentials are correct.'], 500);
}

// --- Database Utility Functions ---

/**
 * Executes a SQL query with parameters.
 * Parameters (?) are used to safely inject variables and prevent SQL injection attacks.
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

/** Fetches ALL rows matching a query */
function fetchAllRows($sql, $params = []) {
    return query($sql, $params)->fetchAll();
}

/** Fetches a SINGLE row matching a query */
function fetchRow($sql, $params = []) {
    return query($sql, $params)->fetch();
}

/** Executes an INSERT, UPDATE, or DELETE query */
function executeUpdate($sql, $params = []) {
    query($sql, $params);
}

/** Gets the ID of the last row inserted into the database */
function lastId() {
    global $db;
    return $db->lastInsertId();
}

// --- Response and Data Handlers ---

/**
 * Sends a JSON response to the client and terminates the script.
 * @param mixed $data - The data (array or object) to send.
 * @param int $code - HTTP Status code (200=OK, 201=Created, 400=Bad Request, etc.)
 */
function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

/**
 * Reads the raw JSON data sent in the request body (e.g., from a POST or PUT request).
 * PHP's $_POST only works with form-data, so we read 'php://input' for JSON.
 */
function getBody() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}