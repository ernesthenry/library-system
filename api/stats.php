<?php
// api/stats.php - Dashboard statistics
require_once 'config.php';

$books     = readData(BOOKS_FILE);
$borrowers = readData(BORROWERS_FILE);
$loans     = readData(LOANS_FILE);

$today = date('Y-m-d');

$totalBooks      = count($books);
$totalCopies     = array_sum(array_column($books, 'copies'));
$availableCopies = array_sum(array_column($books, 'available'));
$totalBorrowers  = count($borrowers);

$activeLoans  = 0;
$overdueLoans = 0;
$returnedLoans = 0;

foreach ($loans as $l) {
    if ($l['status'] === 'returned') {
        $returnedLoans++;
    } elseif ($l['dueDate'] < $today) {
        $overdueLoans++;
    } else {
        $activeLoans++;
    }
}

// Genre breakdown
$genres = [];
foreach ($books as $b) {
    $g = $b['genre'] ?? 'General';
    $genres[$g] = ($genres[$g] ?? 0) + 1;
}

respond([
    'totalBooks'      => $totalBooks,
    'totalCopies'     => $totalCopies,
    'availableCopies' => $availableCopies,
    'checkedOut'      => $totalCopies - $availableCopies,
    'totalBorrowers'  => $totalBorrowers,
    'activeLoans'     => $activeLoans,
    'overdueLoans'    => $overdueLoans,
    'returnedLoans'   => $returnedLoans,
    'genres'          => $genres,
]);
