<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notify.php';
requireLogin();

$user = currentUser();
$uid  = (int)$user['user_id'];

createDueSoonNotifications($pdo, $uid);   // runs before the header so the badge is correct

$stmt = $pdo->prepare(
    'SELECT notification_id, request_id, message, is_read,
            TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age
       FROM notifications
      WHERE user_id = ?
      ORDER BY created_at DESC, notification_id DESC
      LIMIT 50'
);
$stmt->execute([$uid]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unread = 0;
$maxId  = 0;
foreach ($items as $n) {
    if (!$n['is_read']) $unread++;
    $maxId = max($maxId, (int)$n['notification_id']);
}

$pageTitle = 'Notifications';
require __DIR__ . '/../includes/header.php';
?>
<section id="notifRoot" data-unread="<?= $unread ?>" data-max-id="<?= $maxId ?>">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Notifications</h1>
        <button type="button" id="markAll" class="btn btn-secondary" <?= $unread ? '' : 'disabled' ?>>Mark all as read</button>
    </div>

    <div id="notifMsg" role="status" aria-live="polite"></div>
    <div id="newBanner" class="alert alert-info d-none" role="status">
        New notifications are available. <a href="notifications.php" class="alert-link">Show them</a>
    </div>

    <?php if (!$items): ?>
        <p class="text-muted">No records found. You'll be notified about requests, approvals and returns here.</p>
    <?php else: ?>
        <?php foreach ($items as $n):
            [$headline, $detail] = notifSplit($n['message']); ?>
            <article class="card mb-2 notif<?= $n['is_read'] ? '' : ' notif--unread' ?>">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <h2 class="h6 mb-1 notif-head"><?= e($headline) ?></h2>
                        <?php if (!$n['is_read']): ?>
                            <span class="badge text-bg-primary js-unread-badge">Unread</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($detail !== ''): ?>
                        <p class="mb-1"><?= e($detail) ?></p>
                    <?php endif; ?>
                    <p class="small text-muted mb-0">
                        <?= e(notifTimeAgo((int)$n['age'])) ?>
                        <?php if ($n['request_id']): ?>
                            &middot; <a href="requests.php">View requests</a>
                        <?php endif; ?>
                    </p>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<script>window.API_BASE = <?= json_encode(url('api/')) ?>;</script>
<script type="module" src="<?= e(url('assets/js/notifications.js')) ?>"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>