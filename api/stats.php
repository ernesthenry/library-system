<?php
/**
 * ==============================================================================
 * Library Management System - Dashboard Statistics API
 * ==============================================================================
 * 
 * ROLE:
 * Aggregates data from multiple tables to provide an overview of the system state.
 * Used exclusively by the 'Dashboard' view in the frontend.
 * 
 * DATA POINTS:
 * - Total Unique Books
 * - Total physical copies vs. available copies
 * - Registered members
 * - Active circulation counts (Active, Overdue, Returned)
 * - Inventory breakdown by Genre
 * ==============================================================================
 */

require_once 'config.php';

// 1. Basic Counts
$totalBooks     = fetchRow("SELECT COUNT(*) as count FROM books")['count'];
$totalBorrowers = fetchRow("SELECT COUNT(*) as count FROM borrowers")['count'];

// 2. Circulation Counts
$activeLoans   = fetchRow("SELECT COUNT(*) as count FROM loans WHERE status = 'active'")['count'];
$overdueLoans  = fetchRow("SELECT COUNT(*) as count FROM loans WHERE status = 'active' AND dueDate < CURDATE()")['count'];
$returnedLoans = fetchRow("SELECT COUNT(*) as count FROM loans WHERE status = 'returned'")['count'];

// 3. Inventory Logic
// Available = Total Copies - Count of currently borrowed books (not yet returned)
$totalCopies     = fetchRow("SELECT SUM(copies) as count FROM books")['count'] ?? 0;
$availableCopies = $totalCopies - $activeLoans;

// 4. Genre Breakdown
$genresRaw = fetchAllRows("SELECT genre, COUNT(*) as count FROM books GROUP BY genre");
$genres = [];
foreach ($genresRaw as $row) {
    $genres[$row['genre']] = (int)$row['count'];
}

// 5. Build the composite dashboard response
respond([
    'totalBooks'      => (int)$totalBooks,
    'totalCopies'     => (int)$totalCopies,
    'availableCopies' => (int)$availableCopies,
    'totalBorrowers'  => (int)$totalBorrowers,
    'activeLoans'     => (int)$activeLoans,
    'overdueLoans'    => (int)$overdueLoans,
    'returnedLoans'   => (int)$returnedLoans,
    'genres'          => $genres
]);