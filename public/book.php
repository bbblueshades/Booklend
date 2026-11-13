<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

$user   = currentUser();
$bookId = (int)($_GET['book_id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT b.*, s.subject_name, u.full_name AS owner_name
       FROM books b
       JOIN subjects s ON s.subject_id = b.subject_id
       JOIN users u    ON u.user_id    = b.owner_id
      WHERE b.book_id = ?'
);
$stmt->execute([$bookId]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

$isOwn = $book && (int)$book['owner_id'] === (int)$user['user_id'];

// Does this user already have an active request for the book?
$hasActive = false;
if ($book && !$isOwn) {
    $chk = $pdo->prepare(
        "SELECT 1 FROM borrow_requests
          WHERE book_id = ? AND borrower_id = ?
            AND status IN ('Requested','Approved','Borrowed')"
    );
    $chk->execute([$bookId, $user['user_id']]);
    $hasActive = (bool)$chk->fetch();
}

$canRequest = $book && !$isOwn && $user['role'] === 'user'
           && $book['availability'] === 'available' && !$hasActive;

$pageTitle = $book ? $book['title'] : 'Book not found';
if (!$book) http_response_code(404);
require __DIR__ . '/../includes/header.php';
?>
<section>
<?php if (!$book): ?>
    <h1>Book not found</h1>
    <p><a href="browse.php">Back to Browse</a></p>
<?php else: ?>
    <div class="row g-4">
        <div class="col-md-4 col-lg-3">
            <?php if ($book['cover_url']): ?>
                <img src="<?= e($book['cover_url']) ?>" alt="Cover of <?= e($book['title']) ?>"
                     class="img-fluid rounded border">
            <?php else: ?>
                <div class="browse-cover browse-cover--empty rounded border">No cover</div>
            <?php endif; ?>
        </div>

        <div class="col-md-8 col-lg-9">
            <h1><?= e($book['title']) ?></h1>
            <p class="mb-1"><?= e($book['author']) ?></p>
            <p class="mb-1">ISBN: <?= e($book['isbn'] ?? 'Not provided') ?></p>
            <p class="mb-1"><?= e($book['subject_name']) ?> | Condition: <?= e($book['book_condition']) ?></p>
            <p class="mb-2">Owner: <?= e($book['owner_name']) ?></p>
            <p>
                <span class="badge <?= $book['availability'] === 'available' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                    <?= $book['availability'] === 'available' ? 'Available' : 'Borrowed' ?>
                </span>
            </p>

            <div id="qrBox" class="mb-3"></div>

            <?php if ($isOwn): ?>
                <p class="text-muted">This is your book.
                   <a href="edit-book.php?book_id=<?= (int)$book['book_id'] ?>">Edit it</a></p>
            <?php elseif ($hasActive): ?>
                <div class="alert alert-info">You already have an active request for this book.
                    <a href="requests.php">View your requests</a></div>
            <?php elseif ($book['availability'] !== 'available'): ?>
                <button type="button" class="btn btn-secondary" disabled>Unavailable</button>
            <?php elseif ($canRequest): ?>
                <button type="button" id="requestBtn" class="btn btn-primary">Request to borrow</button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($canRequest): ?>
    <div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="requestModalLabel">Confirm request?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">The owner will be notified.</p>
                    <div id="requestMsg" class="alert d-none mb-0" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="sendRequest" class="btn btn-primary">Send request</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        window.API_BASE = <?= json_encode(url('api/')) ?>;
        window.QR_TOKEN = <?= json_encode($book['qr_code']) ?>;
        window.BOOK_ID  = <?= (int)$book['book_id'] ?>;
    </script>
    <script type="module" src="<?= e(url('assets/js/book.js')) ?>"></script>
<?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>