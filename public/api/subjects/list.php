<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/db.php';
header('Content-Type: application/json');

try {
    $rows = $pdo->query('SELECT subject_id, subject_name FROM subjects ORDER BY subject_name')
                ->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error.']);
}