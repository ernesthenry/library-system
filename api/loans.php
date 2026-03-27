<?php
// REST API for Loan management using MySQL
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Helper: enrichment function for loans (joining names)
function getEnrichedLoan($id)
{
    return fetchRow("
        SELECT l.*, b.title as bookTitle, br.name as borrowerName
        FROM loans l
        JOIN books b ON l.bookId = b.id
        JOIN borrowers br ON l.borrowerId = br.id
        WHERE l.id = ?
    ", [$id]);
}

// ── GET ──────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    if ($id) {
        $loan = getEnrichedLoan($id);
        $loan ? respond($loan) : respond(['error' => 'Loan not found'], 404);
    }

    $status = $_GET['status'] ?? '';
    $borrowerId = isset($_GET['borrowerId']) ? (int)$_GET['borrowerId'] : 0;
    $bookId = isset($_GET['bookId']) ? (int)$_GET['bookId'] : 0;

    $where = ["1=1"];
    $params = [];

    if ($status) {
        $where[] = "l.status = ?";
        $params[] = $status;
    }
    if ($borrowerId) {
        $where[] = "l.borrowerId = ?";
        $params[] = $borrowerId;
    }
    if ($bookId) {
        $where[] = "l.bookId = ?";
        $params[] = $bookId;
    }

    $sql = "
        SELECT l.*, b.title as bookTitle, br.name as borrowerName
        FROM loans l
        JOIN books b ON l.bookId = b.id
        JOIN borrowers br ON l.borrowerId = br.id
        WHERE " . implode(' AND ', $where);

    $loans = fetchAllRows($sql, $params);

    // Auto-overdue detection on active loans
    $today = date('Y-m-d');
    foreach ($loans as &$l) {
        if ($l['status'] === 'active' && $l['dueDate'] < $today) {
            $l['status'] = 'overdue';
        }
    }

    respond($loans);
}

// ── POST (borrow copy) ───────────────────────────────────────────────────────
if ($method === 'POST') {
    $body = getBody();
    $bookId = (int)($body['bookId'] ?? 0);
    $borrowerId = (int)($body['borrowerId'] ?? 0);

    if (!$bookId || !$borrowerId)
        respond(['error' => 'bookId and borrowerId required'], 400);

    // 1. Availability check
    $book = fetchRow("SELECT * FROM books WHERE id = ?", [$bookId]);
    if (!$book)
        respond(['error' => 'Book not found'], 404);
    if ($book['available'] <= 0)
        respond(['error' => 'No copies available'], 409);

    // 2. Borrower check
    $borrower = fetchRow("SELECT id FROM borrowers WHERE id = ?", [$borrowerId]);
    if (!$borrower)
        respond(['error' => 'Borrower not found'], 404);

    $dueDate = date('Y-m-d', strtotime('+14 days'));
    $borrowedDate = date('Y-m-d');

    try {
        // Use transaction for consistency
        global $db;
        $db->beginTransaction();

        executeUpdate(
            "INSERT INTO loans (bookId, borrowerId, borrowedDate, dueDate, status) VALUES (?, ?, ?, ?, 'active')",
        [$bookId, $borrowerId, $borrowedDate, $dueDate]
        );
        $newId = lastId();

        // Update book availability
        executeUpdate("UPDATE books SET available = available - 1 WHERE id = ?", [$bookId]);

        $db->commit();
        respond(getEnrichedLoan($newId), 201);
    }
    catch (Exception $e) {
        if ($db->inTransaction())
            $db->rollBack();
        respond(['error' => 'Loan failed: ' . $e->getMessage()], 500);
    }
}

// ── PUT (return copy) ────────────────────────────────────────────────────────
if ($method === 'PUT') {
    if (!$id)
        respond(['error' => 'Loan ID required'], 400);

    $loan = fetchRow("SELECT * FROM loans WHERE id = ?", [$id]);
    if (!$loan)
        respond(['error' => 'Loan not found'], 404);
    if ($loan['status'] === 'returned')
        respond(['error' => 'Book already returned'], 409);

    try {
        global $db;
        $db->beginTransaction();

        $returnedDate = date('Y-m-d');
        executeUpdate("UPDATE loans SET returnedDate = ?, status = 'returned' WHERE id = ?", [$returnedDate, $id]);

        // Update book availability
        executeUpdate("UPDATE books SET available = available + 1 WHERE id = ?", [$loan['bookId']]);

        $db->commit();
        respond(getEnrichedLoan($id));
    }
    catch (Exception $e) {
        if ($db->inTransaction())
            $db->rollBack();
        respond(['error' => 'Return failed: ' . $e->getMessage()], 500);
    }
}

// ── DELETE ───────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    if (!$id)
        respond(['error' => 'ID required'], 400);
    $loan = fetchRow("SELECT * FROM loans WHERE id = ?", [$id]);
    if (!$loan)
        respond(['error' => 'Loan not found'], 404);

    executeUpdate("DELETE FROM loans WHERE id = ?", [$id]);
    respond(['message' => 'Loan record removed']);
}

respond(['error' => 'Method not allowed'], 405);