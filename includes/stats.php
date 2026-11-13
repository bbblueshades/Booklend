<?php
function getCounts(): array
{
    $db = getDB();
    return [
        'users'  => (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'books'  => (int) $db->query('SELECT COUNT(*) FROM books')->fetchColumn(),
        'active' => (int) $db->query("SELECT COUNT(*) FROM borrow_requests WHERE status = 'Borrowed'")->fetchColumn(),
    ];
}

// Borrow_requests -> Books -> Subjects, grouped by subject
function getSubjectStats(int $limit = 5): array
{
    $stmt = getDB()->prepare(
        "SELECT s.subject_name, COUNT(r.request_id) AS total
         FROM subjects s
         JOIN books b ON b.subject_id = s.subject_id
         JOIN borrow_requests r ON r.book_id = b.book_id
         WHERE r.status IN ('Borrowed', 'Returned')
         GROUP BY s.subject_id, s.subject_name
         ORDER BY total DESC, s.subject_name
         LIMIT :lim"
    );
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getStatusStats(): array
{
    return getDB()->query(
        'SELECT status, COUNT(*) AS total FROM borrow_requests GROUP BY status ORDER BY total DESC'
    )->fetchAll();
}

function getBookBorrowTable(): array
{
    return getDB()->query(
        "SELECT b.title, s.subject_name, COUNT(r.request_id) AS times_borrowed
         FROM books b
         JOIN subjects s ON s.subject_id = b.subject_id
         LEFT JOIN borrow_requests r
                ON r.book_id = b.book_id AND r.status IN ('Borrowed', 'Returned')
         GROUP BY b.book_id, b.title, s.subject_name
         ORDER BY times_borrowed DESC, b.title"
    )->fetchAll();
}