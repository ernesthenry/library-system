<?php
/**
 * ==============================================================================
 * Library Management System - Loans & Transactions API (REST)
 * ==============================================================================
 * 
 * ROLE:
 * Handles the "Circulation" logic of the library. 
 * Manages checking out books, returning them, and tracking due dates.
 * 
 * DATAFlow:
 * 1. POST: Creates a new loan record (Checkout).
 * 2. PUT: Marks an existing loan as 'returned' (Return).
 * 3. GET: Lists current and past circulation records.
 * 
 * BUSINESS LOGIC:
 * - A checkout decreases available copies of a book.
 * - A return increases available copies.
 * - Due dates are automatically 14 days from the borrowed date.
 * ==============================================================================
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;

// ── GET: READ CIRCULATION DATA ───────────────────────────────────────────────
if ($method === 'GET') {
    $status = $_GET['status'] ?? ''; // Filter: 'active', 'overdue', 'returned'

    $sql = "SELECT l.*, b.title as bookTitle, br.name as borrowerName 
            FROM loans l
            JOIN books b ON l.bookId = b.id
            JOIN borrowers br ON l.borrowerId = br.id";
    
    $where = [];
    $params = [];

    if ($status === 'active') {
        $where[] = "l.status = 'active'";
    } elseif ($status === 'overdue') {
        $where[] = "l.status = 'active' AND l.dueDate < CURDATE()";
    } elseif ($status === 'returned') {
        $where[] = "l.status = 'returned'";
    }

    if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY l.borrowedDate DESC";
    respond(fetchAllRows($sql, $params));
}

// ── POST: CHECKOUT A BOOK (CREATE LOAN) ──────────────────────────────────────
if ($method === 'POST') {
    $data = getBody();
    $bookId     = $data['bookId']     ?? null;
    $borrowerId = $data['borrowerId'] ?? null;

    if (!$bookId || !$borrowerId) {
        respond(['error' => 'Book ID and Borrower ID are required.'], 400);
    }

    // 1. Verify book availability before proceeding
    $book = fetchRow("SELECT title, copies, 
                      (copies - (SELECT COUNT(*) FROM loans WHERE bookId = ? AND status != 'returned')) as available 
                      FROM books WHERE id = ?", [$bookId, $bookId]);
    
    if (!$book) respond(['error' => 'Book not found.'], 404);
    if ($book['available'] <= 0) {
        respond(['error' => "Sorry, '{$book['title']}' is currently out of stock."], 409);
    }

    // 2. Insert the loan record
    $borrowedDate = date('Y-m-d');
    $dueDate      = date('Y-m-d', strtotime('+14 days'));

    executeUpdate(
        "INSERT INTO loans (bookId, borrowerId, borrowedDate, dueDate, status) VALUES (?, ?, ?, ?, 'active')",
        [$bookId, $borrowerId, $borrowedDate, $dueDate]
    );

    respond(['message' => 'Book checked out successfully', 'dueDate' => $dueDate], 201);
}

// ── PUT: RETURN A BOOK (UPDATE LOAN) ─────────────────────────────────────────
if ($method === 'PUT') {
    if (!$id) respond(['error' => 'Missing loan ID'], 400);

    $loan = fetchRow("SELECT status FROM loans WHERE id = ?", [$id]);
    if (!$loan) respond(['error' => 'Loan record not found'], 404);
    if ($loan['status'] === 'returned') respond(['error' => 'This book has already been returned.'], 409);

    $returnedDate = date('Y-m-d');
    
    executeUpdate(
        "UPDATE loans SET returnedDate = ?, status = 'returned' WHERE id = ?",
        [$returnedDate, $id]
    );

    respond(['message' => 'Book returned successfully']);
}

// ── DELETE: REMOVE LOAN RECORD ───────────────────────────────────────────────
if ($method === 'DELETE') {
    if (!$id) respond(['error' => 'Missing loan ID'], 400);
    
    executeUpdate("DELETE FROM loans WHERE id = ?", [$id]);
    respond(['message' => 'Loan record deleted']);
}

respond(['error' => 'Method Not Allowed'], 405);