<?php
// api/books.php - REST API for Books CRUD
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$books  = readData(BOOKS_FILE);
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

// ── GET ──────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    if ($id) {
        $book = array_values(array_filter($books, fn($b) => $b['id'] === $id));
        $book ? respond($book[0]) : respond(['error' => 'Book not found'], 404);
    }

    // Optional query filters
    $search = strtolower($_GET['search'] ?? '');
    $genre  = $_GET['genre'] ?? '';
    $avail  = $_GET['available'] ?? '';

    $filtered = array_filter($books, function ($b) use ($search, $genre, $avail) {
        if ($search && !str_contains(strtolower($b['title']), $search)
                    && !str_contains(strtolower($b['author']), $search)
                    && !str_contains(strtolower($b['isbn']), $search)) {
            return false;
        }
        if ($genre && $b['genre'] !== $genre) return false;
        if ($avail === '1' && $b['available'] <= 0) return false;
        return true;
    });

    respond(array_values($filtered));
}

// ── POST ─────────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $body = getBody();
    if (empty($body['title']) || empty($body['author'])) {
        respond(['error' => 'Title and author are required'], 400);
    }
    $new = [
        'id'        => nextId($books),
        'title'     => trim($body['title']),
        'author'    => trim($body['author']),
        'isbn'      => trim($body['isbn'] ?? ''),
        'genre'     => trim($body['genre'] ?? 'General'),
        'year'      => (int)($body['year'] ?? date('Y')),
        'copies'    => max(1, (int)($body['copies'] ?? 1)),
        'available' => max(1, (int)($body['copies'] ?? 1)),
    ];
    $books[] = $new;
    writeData(BOOKS_FILE, $books);
    respond($new, 201);
}

// ── PUT ──────────────────────────────────────────────────────────────────────
if ($method === 'PUT') {
    if (!$id) respond(['error' => 'ID required'], 400);
    $body  = getBody();
    $found = false;
    foreach ($books as &$book) {
        if ($book['id'] === $id) {
            $book['title']     = trim($body['title']   ?? $book['title']);
            $book['author']    = trim($body['author']  ?? $book['author']);
            $book['isbn']      = trim($body['isbn']    ?? $book['isbn']);
            $book['genre']     = trim($body['genre']   ?? $book['genre']);
            $book['year']      = (int)($body['year']   ?? $book['year']);
            $oldCopies         = $book['copies'];
            $newCopies         = (int)($body['copies'] ?? $oldCopies);
            $diff              = $newCopies - $oldCopies;
            $book['copies']    = $newCopies;
            $book['available'] = max(0, $book['available'] + $diff);
            $found = true;
            $updated = $book;
            break;
        }
    }
    if (!$found) respond(['error' => 'Book not found'], 404);
    writeData(BOOKS_FILE, $books);
    respond($updated);
}

// ── DELETE ───────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    if (!$id) respond(['error' => 'ID required'], 400);
    $filtered = array_filter($books, fn($b) => $b['id'] !== $id);
    if (count($filtered) === count($books)) respond(['error' => 'Book not found'], 404);
    writeData(BOOKS_FILE, $filtered);
    respond(['message' => 'Book deleted']);
}

respond(['error' => 'Method not allowed'], 405);
