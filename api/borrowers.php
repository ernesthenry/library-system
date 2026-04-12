<?php
/**
 * ==============================================================================
 * Library Management System - Borrowers API (REST)
 * ==============================================================================
 * 
 * ROLE:
 * Handles registration and profile management for library members.
 * Supports GET (Read), POST (Create), PUT (Update), and DELETE (Remove).
 * 
 * DATAFlow:
 * 1. Receives request from app.js (AJAX).
 * 2. Connects to database via config.php.
 * 3. Processes SQL based on HTTP Method.
 * 4. Responds with JSON data.
 * ==============================================================================
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;

// ── GET: READ DATA ───────────────────────────────────────────────────────────
if ($method === 'GET') {
    // A. Fetch a single member (Profile View)
    if ($id) {
        $borrower = fetchRow("SELECT * FROM borrowers WHERE id = ?", [$id]);
        if (!$borrower) respond(['error' => 'Borrower not found'], 404);
        respond($borrower);
    }
    
    // B. Fetch multiple members (List View with Search)
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? ''; // Filter by 'active' or 'suspended'

    $sql = "SELECT * FROM borrowers WHERE (name LIKE ? OR email LIKE ?)";
    $params = ["%$search%", "%$search%"];

    if ($status) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY name ASC";
    respond(fetchAllRows($sql, $params));
}

// ── POST: REGISTER NEW MEMBER ────────────────────────────────────────────────
if ($method === 'POST') {
    $b = getBody();
    
    if (empty($b['name']) || empty($b['email'])) {
        respond(['error' => 'Name and email are required.'], 400);
    }

    executeUpdate(
        "INSERT INTO borrowers (name, email, phone, status) VALUES (?, ?, ?, ?)",
        [
            $b['name'], 
            $b['email'], 
            $b['phone'] ?? '', 
            $b['status'] ?? 'active'
        ]
    );
    
    respond(['message' => 'Borrower registered successfully', 'id' => lastId()], 201);
}

// ── PUT: UPDATE MEMBER PROFILE ───────────────────────────────────────────────
if ($method === 'PUT') {
    if (!$id) respond(['error' => 'Missing borrower ID'], 400);
    
    $b        = getBody();
    $borrower = fetchRow("SELECT id FROM borrowers WHERE id = ?", [$id]);
    if (!$borrower) respond(['error' => 'Borrower not found'], 404);

    executeUpdate(
        "UPDATE borrowers SET name=?, email=?, phone=?, status=? WHERE id=?",
        [
            $b['name'], 
            $b['email'], 
            $b['phone'], 
            $b['status'], 
            $id
        ]
    );
    
    respond(['message' => 'Profile updated successfully']);
}

// ── DELETE: REMOVE MEMBER ────────────────────────────────────────────────────
if ($method === 'DELETE') {
    if (!$id) respond(['error' => 'Missing borrower ID'], 400);

    // Business Logic: Prevent deletion if member has unreturned books
    $activeLoans = fetchRow("SELECT id FROM loans WHERE borrowerId = ? AND status != 'returned'", [$id]);
    if ($activeLoans) {
        respond(['error' => 'Cannot remove borrower with active unreturned books.'], 409);
    }

    executeUpdate("DELETE FROM borrowers WHERE id = ?", [$id]);
    respond(['message' => 'Borrower record deleted']);
}

// If no method matched
respond(['error' => 'Method Not Allowed'], 405);