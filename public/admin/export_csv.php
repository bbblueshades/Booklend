<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/stats.php';
requireAdmin();

// Stops spreadsheet programs from running a cell that starts with = + - @
function csvSafe(string $v): string
{
    return preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="booklend-report-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // lets Excel read UTF-8 correctly

fputcsv($out, ['Book', 'Subject', 'Times borrowed']);
foreach (getBookBorrowTable() as $row) {
    fputcsv($out, [csvSafe($row['title']), csvSafe($row['subject_name']), $row['times_borrowed']]);
}

fputcsv($out, []);
fputcsv($out, ['Request status', 'Total']);
foreach (getStatusStats() as $row) {
    fputcsv($out, [$row['status'], $row['total']]);
}
fclose($out);