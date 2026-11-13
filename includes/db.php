<?php
/**
 * Database connection (PDO).
 * Credentials come from includes/config.php, which is gitignored.
 */
require_once __DIR__ . '/config.php';

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Log the real reason, but never show credentials or host details to the user
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            exit('Unable to connect to the database.');
        }
    }

    return $pdo;
}

// Convenience: files that expect a ready-made $pdo variable can use it directly
$pdo = getDB();