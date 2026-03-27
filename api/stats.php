<?php
// api/stats.php - Dashboard statistics using SQLite
require_once 'config.php';

$today = date('Y-m-d');

// 1. Core Totals
$totalBooks     = fetchRow("SELECT COUNT(*) as count FROM books")['count'];
$totalCopies    = fetchRow("SELECT SUM(copies) as count FROM books")['count'] ?? 0;
$availCopies    = fetchRow("SELECT SUM(available) as count FROM books")['count'] ?? 0;
$totalBorrowers = fetchRow("SELECT COUNT(*) as count FROM borrowers")['count'];

// 2. Loan Status breakdown (using overdue logic)
$returnedLoans = fetchRow("SELECT COUNT(*) as count FROM loans WHERE status = 'returned'")['count'];
$overdueLoans  = fetchRow("SELECT COUNT(*) as count FROM loans WHERE status = 'active' AND dueDate < ?", [$today])['count'];
$activeLoans   = fetchRow("SELECT COUNT(*) as count FROM loans WHERE status = 'active' AND dueDate >= ?", [$today])['count'];

// 3. Genre breakdown
$genreStats = fetchAllRows("SELECT genre, COUNT(*) as count FROM books GROUP BY genre");
$genres = [];
foreach ($genreStats as $row) {
    $genres[$row['genre'] ?: 'General'] = $row['count'];
}

respond([
    'totalBooks'      => (int)$totalBooks,
    'totalCopies'     => (int)$totalCopies,
    'availableCopies' => (int)$availCopies,
    'checkedOut'      => (int)$totalCopies - (int)$availCopies,
    'totalBorrowers'  => (int)$totalBorrowers,
    'activeLoans'     => (int)$activeLoans,
    'overdueLoans'    => (int)$overdueLoans,
    'returnedLoans'   => (int)$returnedLoans,
    'genres'          => $genres,
]);