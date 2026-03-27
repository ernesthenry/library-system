<?php
// REST API for Borrowers CRUD using MySQL
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// ── GET ──────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    if ($id) {
        $found = fetchOne("SELECT * FROM borrowers WHERE id = ?", [$id]);
        $found ? respond($found) : respond(['error' => 'Borrower not found'], 404);
    }

    $search = strtolower($_GET['search'] ?? '');
    $status = $_GET['status'] ?? '';

    $where = ["1=1"];
    $params = [];

    if ($search) {
        $where[] = "(LOWER(name) LIKE ? OR LOWER(email) LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($status) {
        $where[] = "status = ?";
        $params[] = $status;
    }

    $sql = "SELECT * FROM borrowers WHERE " . implode(' AND ', $where);
    $borrowers = fetchAllRows($sql, $params);
    respond($borrowers);
}

// ── POST ─────────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $body = getBody();
    if (empty($body['name']) || empty($body['email'])) {
        respond(['error' => 'Name and email are required'], 400);
    }
    // Check duplicate email
    $existing = fetchOne("SELECT id FROM borrowers WHERE LOWER(email) = ?", [strtolower(trim($body['email']))]);
    if ($existing) {
        respond(['error' => 'Email already registered'], 409);
    }

    $name = trim($body['name']);
    $email = trim($body['email']);
    $phone = trim($body['phone'] ?? '');
    $since = date('Y-m-d');
    $status = 'active';

    try {
        executeUpdate(
            "INSERT INTO borrowers (name, email, phone, memberSince, status) VALUES (?, ?, ?, ?, ?)",
        [$name, $email, $phone, $since, $status]
        );
        $newId = lastId();
        respond(fetchOne("SELECT * FROM borrowers WHERE id = ?", [$newId]), 201);
    }
    catch (Exception $e) {
        respond(['error' => 'Creation failed: ' . $e->getMessage()], 500);
    }
}

// ── PUT ──────────────────────────────────────────────────────────────────────
if ($method === 'PUT') {
    if (!$id)
        respond(['error' => 'ID required'], 400);
    $body = getBody();

    $borrower = fetchOne("SELECT * FROM borrowers WHERE id = ?", [$id]);
    if (!$borrower)
        respond(['error' => 'Borrower not found'], 404);

    $name = trim($body['name'] ?? $borrower['name']);
    $email = trim($body['email'] ?? $borrower['email']);
    $phone = trim($body['phone'] ?? $borrower['phone']);
    $status = trim($body['status'] ?? $borrower['status']);

    try {
        executeUpdate(
            "UPDATE borrowers SET name = ?, email = ?, phone = ?, status = ? WHERE id = ?",
        [$name, $email, $phone, $status, $id]
        );
        respond(fetchOne("SELECT * FROM borrowers WHERE id = ?", [$id]));
    }
    catch (Exception $e) {
        respond(['error' => 'Update failed: ' . $e->getMessage()], 500);
    }
}

// ── DELETE ───────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    if (!$id)
        respond(['error' => 'ID required'], 400);

    $borrower = fetchOne("SELECT * FROM borrowers WHERE id = ?", [$id]);
    if (!$borrower)
        respond(['error' => 'Borrower not found'], 404);

    try {
        executeUpdate("DELETE FROM borrowers WHERE id = ?", [$id]);
        respond(['message' => 'Borrower deleted']);
    }
    catch (Exception $e) {
        respond(['error' => 'Delete failed: Borrower may have active loans'], 400);
    }
}

respond(['error' => 'Method not allowed'], 405);

// helper fetchOne since it was not in my previous write_to_file? Wait.
// Let me check my config.php again.
function fetchOne($sql, $params = [])
{
    return fetchRow($sql, $params);
}