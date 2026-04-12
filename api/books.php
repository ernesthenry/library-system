<?php
/**
 * api/books.php - Books Resource API
 * 
 * This file handles all data operations related to Books.
 * It uses the 'RequestMethod' to decide what action to take.
 */

require_once 'config.php';

// Detect the HTTP method (GET, POST, PUT, or DELETE)
$method = $_SERVER['REQUEST_METHOD'];

// Get the 'id' parameter if it's provided in the URL (e.g., books.php?id=123)
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// ── GET: Retrieval ──────────────────────────────────────────────────────────
/** 
 * Used for fetching data. 
 * - If an ID is provided, fetch one specific book.
 * - Otherwise, fetch a list of books based on filters (search, genre).
 */
if ($method === 'GET') {
    // Case 1: Fetch single book by ID
    if ($id) {
        $book = fetchRow("SELECT * FROM books WHERE id = ?", [$id]);
        $book ? respond($book) : respond(['error' => 'Book not found'], 404);
    }

    // Case 2: Fetch list with filters
    $search = strtolower($_GET['search'] ?? '');
    $genre  = $_GET['genre'] ?? '';
    $avail  = $_GET['available'] ?? '';

    $where  = ["1=1"]; // Base condition so we can easily append 'AND ...'
    $params = [];

    // Filter by search text (title, author, or ISBN)
    if ($search) {
        $where[]  = "(LOWER(title) LIKE ? OR LOWER(author) LIKE ? OR isbn LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    // Filter by genre
    if ($genre) {
        $where[]  = "genre = ?";
        $params[] = $genre;
    }
    // Filter for available books only
    if ($avail === '1') {
        $where[] = "available > 0";
    }

    // Combine conditions and execute query
    $sql   = "SELECT * FROM books WHERE " . implode(' AND ', $where);
    $books = fetchAllRows($sql, $params);
    respond($books);
}

// ── POST: Creation ───────────────────────────────────────────────────────────
/** Used for adding a new book to the database */
if ($method === 'POST') {
    $body = getBody(); // Get JSON data from the request body
    
    // Validate required fields
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
        // Run the INSERT query
        executeUpdate(
            "INSERT INTO books (title, author, isbn, genre, year, copies, available, coverUrl) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$title, $author, $isbn, $genre, $year, $copies, $copies, $coverUrl]
        );
        
        // Fetch and return the newly created book
        $newId = lastId();
        respond(fetchRow("SELECT * FROM books WHERE id = ?", [$newId]), 201);
    } catch (Exception $e) {
        respond(['error' => 'Failed to create book: ' . $e->getMessage()], 500);
    }
}

// ── PUT: Update ──────────────────────────────────────────────────────────────
/** Used for updating an existing book's information */
if ($method === 'PUT') {
    if (!$id) respond(['error' => 'ID required'], 400);
    $body = getBody();

    // Verify the book exists first
    $book = fetchRow("SELECT * FROM books WHERE id = ?", [$id]);
    if (!$book) respond(['error' => 'Book not found'], 404);

    // Use provided values or keep existing ones
    $title    = trim($body['title']    ?? $book['title']);
    $author   = trim($body['author']   ?? $book['author']);
    $isbn     = trim($body['isbn']     ?? $book['isbn']);
    $genre    = trim($body['genre']    ?? $book['genre']);
    $year     = (int)($body['year']    ?? $book['year']);
    $coverUrl = trim($body['coverUrl'] ?? $book['coverUrl']);
    
    // Logic for updating copies and availability
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

// ── DELETE: Removal ──────────────────────────────────────────────────────────
/** Used for removing a book record */
if ($method === 'DELETE') {
    if (!$id) respond(['error' => 'ID required'], 400);

    $book = fetchRow("SELECT * FROM books WHERE id = ?", [$id]);
    if (!$book) respond(['error' => 'Book not found'], 404);

    try {
        executeUpdate("DELETE FROM books WHERE id = ?", [$id]);
        respond(['message' => 'Book deleted']);
    } catch (Exception $e) {
        // Prevent deletion if the book is currently borrowed (Foreign Key Constraint)
        respond(['error' => 'Delete failed: Book may be referenced in a loan'], 400);
    }
}

// If no matching method is found
respond(['error' => 'Method not allowed'], 405);