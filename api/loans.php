<?php
// api/loans.php - REST API for Loan management (borrow/return)
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$loans  = readData(LOANS_FILE);
$books  = readData(BOOKS_FILE);
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Helper: enrich loan with book/borrower names
function enrichLoan($loan, $books, $borrowers) {
    $book     = array_values(array_filter($books,     fn($b) => $b['id'] === $loan['bookId']));
    $borrower = array_values(array_filter($borrowers, fn($b) => $b['id'] === $loan['borrowerId']));
    $loan['bookTitle']     = $book[0]['title']  ?? 'Unknown';
    $loan['borrowerName']  = $borrower[0]['name'] ?? 'Unknown';
    // Auto-flag overdue
    if ($loan['status'] === 'active' && $loan['dueDate'] < date('Y-m-d')) {
        $loan['status'] = 'overdue';
    }
    return $loan;
}

// ── GET ──────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $borrowers = readData(BORROWERS_FILE);

    if ($id) {
        $found = array_values(array_filter($loans, fn($l) => $l['id'] === $id));
        if (!$found) respond(['error' => 'Loan not found'], 404);
        respond(enrichLoan($found[0], $books, $borrowers));
    }

    $status     = $_GET['status']     ?? '';
    $borrowerId = isset($_GET['borrowerId']) ? (int)$_GET['borrowerId'] : 0;
    $bookId     = isset($_GET['bookId'])     ? (int)$_GET['bookId']     : 0;

    $filtered = array_filter($loans, function ($l) use ($status, $borrowerId, $bookId, $books) {
        // Auto-detect overdue
        if ($l['status'] === 'active' && $l['dueDate'] < date('Y-m-d')) $l['status'] = 'overdue';
        if ($status     && $l['status']     !== $status)     return false;
        if ($borrowerId && $l['borrowerId'] !== $borrowerId) return false;
        if ($bookId     && $l['bookId']     !== $bookId)     return false;
        return true;
    });

    $enriched = array_map(fn($l) => enrichLoan($l, $books, $borrowers), array_values($filtered));
    respond($enriched);
}

// ── POST (borrow a book) ─────────────────────────────────────────────────────
if ($method === 'POST') {
    $body       = getBody();
    $bookId     = (int)($body['bookId']     ?? 0);
    $borrowerId = (int)($body['borrowerId'] ?? 0);

    if (!$bookId || !$borrowerId) respond(['error' => 'bookId and borrowerId required'], 400);

    // Check book availability
    $bookIdx = null;
    foreach ($books as $i => $b) {
        if ($b['id'] === $bookId) { $bookIdx = $i; break; }
    }
    if ($bookIdx === null) respond(['error' => 'Book not found'], 404);
    if ($books[$bookIdx]['available'] <= 0) respond(['error' => 'No copies available'], 409);

    // Check borrower exists
    $borrowers = readData(BORROWERS_FILE);
    $borrower  = array_filter($borrowers, fn($b) => $b['id'] === $borrowerId);
    if (empty($borrower)) respond(['error' => 'Borrower not found'], 404);

    $dueDate = date('Y-m-d', strtotime('+14 days'));
    $loan = [
        'id'           => nextId($loans),
        'bookId'       => $bookId,
        'borrowerId'   => $borrowerId,
        'borrowedDate' => date('Y-m-d'),
        'dueDate'      => $dueDate,
        'returnedDate' => null,
        'status'       => 'active',
    ];
    $loans[] = $loan;
    writeData(LOANS_FILE, $loans);

    // Decrement available copies
    $books[$bookIdx]['available']--;
    writeData(BOOKS_FILE, $books);

    $borrowers = readData(BORROWERS_FILE);
    respond(enrichLoan($loan, $books, $borrowers), 201);
}

// ── PUT (return a book) ──────────────────────────────────────────────────────
if ($method === 'PUT') {
    if (!$id) respond(['error' => 'Loan ID required'], 400);
    $found = false;
    foreach ($loans as &$loan) {
        if ($loan['id'] === $id) {
            if ($loan['status'] === 'returned') respond(['error' => 'Book already returned'], 409);
            $loan['returnedDate'] = date('Y-m-d');
            $loan['status']       = 'returned';
            $found   = true;
            $updated = $loan;
            // Increment available copies
            foreach ($books as &$book) {
                if ($book['id'] === $loan['bookId']) {
                    $book['available']++;
                    break;
                }
            }
            break;
        }
    }
    if (!$found) respond(['error' => 'Loan not found'], 404);
    writeData(LOANS_FILE, $loans);
    writeData(BOOKS_FILE, $books);
    $borrowers = readData(BORROWERS_FILE);
    respond(enrichLoan($updated, $books, $borrowers));
}

// ── DELETE ───────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    if (!$id) respond(['error' => 'ID required'], 400);
    $filtered = array_filter($loans, fn($l) => $l['id'] !== $id);
    if (count($filtered) === count($loans)) respond(['error' => 'Loan not found'], 404);
    writeData(LOANS_FILE, $filtered);
    respond(['message' => 'Loan record deleted']);
}

respond(['error' => 'Method not allowed'], 405);
