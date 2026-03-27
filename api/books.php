<?php
// REST API for Books CRUD using MySQL
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

// ── GET ──────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    if ($id) {
        $book = fetchRow("SELECT * FROM books WHERE id = ?", [$id]);
        $book ? respond($book) : respond(['error' => 'Book not found'], 404);
    }

    $search = strtolower($_GET['search'] ?? '');
    $genre  = $_GET['genre'] ?? '';
    $avail  = $_GET['available'] ?? '';

    $where  = ["1=1"];
    $params = [];

    if ($search) {
        $where[]  = "(LOWER(title) LIKE ? OR LOWER(author) LIKE ? OR isbn LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($genre) {
        $where[]  = "genre = ?";
        $params[] = $genre;
    }
    if ($avail === '1') {
        $where[] = "available > 0";
    }

    $sql   = "SELECT * FROM books WHERE " . implode(' AND ', $where);
    $books = fetchAllRows($sql, $params);
    respond($books);
}

// ── POST ─────────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $body = getBody();
    if (empty($body['title']) || empty($body['author'])) {
        respond(['error' => 'Title and author are required'], 400);
    }

    $title    = trim($body['title']);
    $author   = trim($body['author']);
    $isbn     = trim($body['isbn']     ?? '');
    $genre    = trim($body['genre']    ?? 'General');
    $year     = (int)($body['year']    ?? date('Y'));
    $copies   = max(1, (int)($body['copies']   ?? 1));
    $coverUrl = trim($body['coverUrl'] ?? '');

    try {
        executeUpdate(
            "INSERT INTO books (title, author, isbn, genre, year, copies, available, coverUrl) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$title, $author, $isbn, $genre, $year, $copies, $copies, $coverUrl]
        );
        $newId = lastId();
        respond(fetchRow("SELECT * FROM books WHERE id = ?", [$newId]), 201);
    } catch (Exception $e) {
        respond(['error' => 'Failed to create book: ' . $e->getMessage()], 500);
    }
}

// ── PUT ──────────────────────────────────────────────────────────────────────
if ($method === 'PUT') {
    if (!$id) respond(['error' => 'ID required'], 400);
    $body = getBody();

    $book = fetchRow("SELECT * FROM books WHERE id = ?", [$id]);
    if (!$book) respond(['error' => 'Book not found'], 404);

    $title    = trim($body['title']    ?? $book['title']);
    $author   = trim($body['author']   ?? $book['author']);
    $isbn     = trim($body['isbn']     ?? $book['isbn']);
    $genre    = trim($body['genre']    ?? $book['genre']);
    $year     = (int)($body['year']    ?? $book['year']);
    $coverUrl = trim($body['coverUrl'] ?? $book['coverUrl']);
    
    $oldCopies = $book['copies'];
    $newCopies = (int)($body['copies'] ?? $oldCopies);
    $diff      = $newCopies - $oldCopies;
    $available = max(0, $book['available'] + $diff);

    try {
        executeUpdate(
            "UPDATE books SET title = ?, author = ?, isbn = ?, genre = ?, year = ?, copies = ?, available = ?, coverUrl = ? WHERE id = ?",
            [$title, $author, $isbn, $genre, $year, $newCopies, $available, $coverUrl, $id]
        );
        respond(fetchRow("SELECT * FROM books WHERE id = ?", [$id]));
    } catch (Exception $e) {
        respond(['error' => 'Failed to update book: ' . $e->getMessage()], 500);
    }
}

// ── DELETE ───────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    if (!$id) respond(['error' => 'ID required'], 400);

    $book = fetchRow("SELECT * FROM books WHERE id = ?", [$id]);
    if (!$book) respond(['error' => 'Book not found'], 404);

    try {
        executeUpdate("DELETE FROM books WHERE id = ?", [$id]);
        respond(['message' => 'Book deleted']);
    } catch (Exception $e) {
        respond(['error' => 'Delete failed: Book may be referenced in a loan'], 400);
    }
}

respond(['error' => 'Method not allowed'], 405);