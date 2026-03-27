<?php
// api/borrowers.php - REST API for Borrowers CRUD
require_once 'config.php';

$method    = $_SERVER['REQUEST_METHOD'];
$borrowers = readData(BORROWERS_FILE);
$id        = isset($_GET['id']) ? (int)$_GET['id'] : null;

// ── GET ──────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    if ($id) {
        $found = array_values(array_filter($borrowers, fn($b) => $b['id'] === $id));
        $found ? respond($found[0]) : respond(['error' => 'Borrower not found'], 404);
    }

    $search = strtolower($_GET['search'] ?? '');
    $status = $_GET['status'] ?? '';

    $filtered = array_filter($borrowers, function ($b) use ($search, $status) {
        if ($search && !str_contains(strtolower($b['name']), $search)
                    && !str_contains(strtolower($b['email']), $search)) {
            return false;
        }
        if ($status && $b['status'] !== $status) return false;
        return true;
    });

    respond(array_values($filtered));
}

// ── POST ─────────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $body = getBody();
    if (empty($body['name']) || empty($body['email'])) {
        respond(['error' => 'Name and email are required'], 400);
    }
    // Check duplicate email
    foreach ($borrowers as $b) {
        if (strtolower($b['email']) === strtolower(trim($body['email']))) {
            respond(['error' => 'Email already registered'], 409);
        }
    }
    $new = [
        'id'           => nextId($borrowers),
        'name'         => trim($body['name']),
        'email'        => trim($body['email']),
        'phone'        => trim($body['phone'] ?? ''),
        'memberSince'  => date('Y-m-d'),
        'status'       => 'active',
    ];
    $borrowers[] = $new;
    writeData(BORROWERS_FILE, $borrowers);
    respond($new, 201);
}

// ── PUT ──────────────────────────────────────────────────────────────────────
if ($method === 'PUT') {
    if (!$id) respond(['error' => 'ID required'], 400);
    $body  = getBody();
    $found = false;
    foreach ($borrowers as &$b) {
        if ($b['id'] === $id) {
            $b['name']   = trim($body['name']   ?? $b['name']);
            $b['email']  = trim($body['email']  ?? $b['email']);
            $b['phone']  = trim($body['phone']  ?? $b['phone']);
            $b['status'] = trim($body['status'] ?? $b['status']);
            $found   = true;
            $updated = $b;
            break;
        }
    }
    if (!$found) respond(['error' => 'Borrower not found'], 404);
    writeData(BORROWERS_FILE, $borrowers);
    respond($updated);
}

// ── DELETE ───────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    if (!$id) respond(['error' => 'ID required'], 400);
    $filtered = array_filter($borrowers, fn($b) => $b['id'] !== $id);
    if (count($filtered) === count($borrowers)) respond(['error' => 'Borrower not found'], 404);
    writeData(BORROWERS_FILE, $filtered);
    respond(['message' => 'Borrower deleted']);
}

respond(['error' => 'Method not allowed'], 405);
