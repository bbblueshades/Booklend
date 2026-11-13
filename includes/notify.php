<?php
/** Notification helpers. */

/**
 * Creates a "Return due soon" notification for each of this user's Borrowed
 * requests that is due within 2 days (or already overdue), once per request.
 */
function createDueSoonNotifications(PDO $pdo, int $userId): void
{
    $sql = <<<'SQL'
INSERT INTO notifications (user_id, request_id, message)
SELECT r.borrower_id, r.request_id,
       CONCAT('Return due soon: "', LEFT(b.title, 120), '" is due on ',
              DATE_FORMAT(r.due_date, '%b %e, %Y'), '.')
  FROM borrow_requests r
  JOIN books b ON b.book_id = r.book_id
 WHERE r.borrower_id = :uid
   AND r.status = 'Borrowed'
   AND r.due_date IS NOT NULL
   AND r.due_date <= DATE_ADD(CURDATE(), INTERVAL 2 DAY)
   AND NOT EXISTS (
         SELECT 1 FROM notifications n
          WHERE n.request_id = r.request_id
            AND n.message LIKE 'Return due soon%')
SQL;
    $pdo->prepare($sql)->execute([':uid' => $userId]);
}

function unreadNotificationCount(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

/** "New request received: "Title" requested by Name" -> [headline, detail] */
function notifSplit(string $message): array
{
    $pos = strpos($message, ': ');
    return $pos === false
        ? [$message, '']
        : [substr($message, 0, $pos), substr($message, $pos + 2)];
}

function notifTimeAgo(int $seconds): string
{
    if ($seconds < 60)     return 'just now';
    if ($seconds < 3600)   return intdiv($seconds, 60) . ' min ago';
    if ($seconds < 86400)  { $h = intdiv($seconds, 3600); return $h . ' hour' . ($h === 1 ? '' : 's') . ' ago'; }
    if ($seconds < 172800) return 'yesterday';
    return intdiv($seconds, 86400) . ' days ago';
}