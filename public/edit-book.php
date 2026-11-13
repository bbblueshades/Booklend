<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

$user   = currentUser();
$bookId = (int)($_GET['book_id'] ?? 0);

// Owner check on page load too
$stmt = $pdo->prepare('SELECT * FROM books WHERE book_id = ? AND owner_id = ?');
$stmt->execute([$bookId, $user['user_id']]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

$subjects   = $pdo->query('SELECT subject_id, subject_name FROM subjects ORDER BY subject_name')
                  ->fetchAll(PDO::FETCH_ASSOC);
$conditions = ['As New', 'Fine', 'Good', 'Poor'];

$pageTitle = 'Edit Book';
if (!$book) http_response_code(404);
require __DIR__ . '/../includes/header.php';
?>
<section>
<?php if (!$book): ?>
    <h1>Book not found</h1>
    <p><a href="my-books.php">Back to My Books</a></p>
<?php else: ?>
    <h1>Edit book</h1>
    <div class="row g-4">
        <div class="col-lg-7">
            <form id="editForm" novalidate>
                <input type="hidden" id="bookId" value="<?= (int)$book['book_id'] ?>">

                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input id="title" class="form-control" required maxlength="255" value="<?= e($book['title']) ?>">
                </div>
                <div class="mb-3">
                    <label for="author" class="form-label">Author</label>
                    <input id="author" class="form-control" required maxlength="150" value="<?= e($book['author']) ?>">
                </div>
                <div class="mb-3">
                    <label for="isbn" class="form-label">ISBN</label>
                    <input id="isbn" class="form-control" maxlength="20" value="<?= e($book['isbn'] ?? '') ?>">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <select id="subject" class="form-select" required>
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?= (int)$s['subject_id'] ?>"
                                    <?= (int)$s['subject_id'] === (int)$book['subject_id'] ? 'selected' : '' ?>>
                                    <?= e($s['subject_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="condition" class="form-label">Condition</label>
                        <select id="condition" class="form-select">
                            <?php foreach ($conditions as $c): ?>
                                <option <?= $c === $book['book_condition'] ? 'selected' : '' ?>><?= e($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div id="editMsg" role="status" class="mb-3"></div>
                <button type="submit" class="btn btn-primary">Save changes</button>
                <a href="my-books.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>

        <div class="col-lg-5">
            <?php if ($book['cover_url']): ?>
                <img src="<?= e($book['cover_url']) ?>" alt="Book cover" class="img-fluid mb-3" style="max-height:260px">
            <?php endif; ?>
            <div id="qrBox"></div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        window.API_BASE = <?= json_encode(url('api/')) ?>;
        window.QR_TOKEN = <?= json_encode($book['qr_code']) ?>;
    </script>
    <script type="module" src="<?= e(url('assets/js/edit-book.js')) ?>"></script>
<?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>