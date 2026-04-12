<?php
/**
 * ==============================================================================
 * Library Management System - Books API (REST)
 * ==============================================================================
 * 
 * ROLE:
 * Handles all operations related to the book catalogue.
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
    // A. Fetch a single book (Detail View)
    if ($id) {
        $book = fetchRow("SELECT * FROM books WHERE id = ?", [$id]);
        if (!$book) respond(['error' => 'Book not found'], 404);
        respond($book);
    }
    
    // B. Fetch multiple books (List View with Search/Filter)
    $search    = $_GET['search'] ?? '';
    $genre     = $_GET['genre'] ?? '';
    $available = $_GET['available'] ?? '';

    $sql = "SELECT * FROM books WHERE (title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];

    if ($genre) {
        $sql .= " AND genre = ?";
        $params[] = $genre;
    }

    if ($available === '1') {
        // Logic: A book is "available" if its total copies > current borrowed count
        $sql .= " AND (copies - (SELECT COUNT(*) FROM loans WHERE bookId = books.id AND status != 'returned')) > 0";
    }

    $sql .= " ORDER BY title ASC";
    respond(fetchAllRows($sql, $params));
}

// ── POST: CREATE NEW RECORD ──────────────────────────────────────────────────
if ($method === 'POST') {
    $b = getBody();
    
    // Validation: Ensure minimum required fields are present
    if (empty($b['title']) || empty($b['author'])) {
        respond(['error' => 'Title and author are required.'], 400);
    }

    executeUpdate(
        "INSERT INTO books (title, author, isbn, genre, year, copies, coverUrl) VALUES (?, ?, ?, ?, ?, ?, ?)",
        [
            $b['title'], 
            $b['author'], 
            $b['isbn'] ?? '', 
            $b['genre'] ?? 'Other', 
            (int)($b['year'] ?? date('Y')), 
            (int)($b['copies'] ?? 1),
            $b['coverUrl'] ?? ''
        ]
    );
    
    respond(['message' => 'Book added successfully', 'id' => lastId()], 201);
}

// ── PUT: UPDATE EXISTING RECORD ──────────────────────────────────────────────
if ($method === 'PUT') {
    if (!$id) respond(['error' => 'Missing book ID'], 400);
    
    $b    = getBody();
    $book = fetchRow("SELECT id FROM books WHERE id = ?", [$id]);
    if (!$book) respond(['error' => 'Book not found'], 404);

    executeUpdate(
        "UPDATE books SET title=?, author=?, isbn=?, genre=?, year=?, copies=?, coverUrl=? WHERE id=?",
        [
            $b['title'], 
            $b['author'], 
            $b['isbn'], 
            $b['genre'], 
            (int)$b['year'], 
            (int)$b['copies'], 
            $b['coverUrl'], 
            $id
        ]
    );
    
    respond(['message' => 'Book updated successfully']);
}

// ── DELETE: REMOVE RECORD ────────────────────────────────────────────────────
if ($method === 'DELETE') {
    if (!$id) respond(['error' => 'Missing book ID'], 400);

    // Business Logic: Prevent deletion if there are active loans
    $activeLoans = fetchRow("SELECT id FROM loans WHERE bookId = ? AND status != 'returned'", [$id]);
    if ($activeLoans) {
        respond(['error' => 'Cannot delete book with active loans. Please return all copies first.'], 409);
    }

    executeUpdate("DELETE FROM books WHERE id = ?", [$id]);
    respond(['message' => 'Book deleted successfully']);
}

// If no method matched
respond(['error' => 'Method Not Allowed'], 405);