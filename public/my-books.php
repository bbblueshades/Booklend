<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

$user = currentUser();

$stmt = $pdo->prepare(
    'SELECT b.book_id, b.title, b.book_condition, b.availability,
            b.cover_url, b.qr_code, s.subject_name
       FROM books b
       JOIN subjects s ON s.subject_id = b.subject_id
      WHERE b.owner_id = ?
      ORDER BY b.created_at DESC'
);
$stmt->execute([$user['user_id']]);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'My Books';
require __DIR__ . '/../includes/header.php';

function statusBadge(string $a): string {
    $cls = $a === 'available' ? 'text-bg-success' : 'text-bg-secondary';
    return '<span class="badge ' . $cls . '">' . e($a) . '</span>';
}
function coverImg(?string $url, string $title): string {
    return $url
        ? '<img src="' . e($url) . '" alt="Cover of ' . e($title) . '" class="book-cover">'
        : '<div class="book-cover book-cover--empty" aria-label="No cover">No cover</div>';
}
?>
<section>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">My Books</h1>
        <a href="list-book.php" class="btn btn-primary">Add new book</a>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success" role="status">Record successfully saved.</div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-success" role="status">Book deleted.</div>
    <?php endif; ?>

    <?php if (!$books): ?>
        <p class="text-muted">No records found. Click “Add new book” to list your first one.</p>
    <?php else: ?>

    <!-- Desktop / tablet: table -->
    <div class="table-responsive d-none d-md-block">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th scope="col">Cover</th><th scope="col">Title</th>
                    <th scope="col">Subject</th><th scope="col">Condition</th>
                    <th scope="col">Status</th><th scope="col">QR</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($books as $b): ?>
                <tr data-book-id="<?= (int)$b['book_id'] ?>">
                    <td><?= coverImg($b['cover_url'], $b['title']) ?></td>
                    <td><?= e($b['title']) ?></td>
                    <td><?= e($b['subject_name']) ?></td>
                    <td><?= e($b['book_condition']) ?></td>
                    <td><?= statusBadge($b['availability']) ?></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-secondary js-qr"
                                data-token="<?= e($b['qr_code']) ?>"
                                data-title="<?= e($b['title']) ?>">Show</button>
                    </td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-primary"
                           href="edit-book.php?book_id=<?= (int)$b['book_id'] ?>">Edit</a>
                        <button type="button" class="btn btn-sm btn-outline-danger js-delete"
                                data-book-id="<?= (int)$b['book_id'] ?>"
                                data-title="<?= e($b['title']) ?>">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Mobile: cards -->
    <div class="d-md-none">
    <?php foreach ($books as $b): ?>
        <div class="card mb-3" data-book-id="<?= (int)$b['book_id'] ?>">
            <div class="card-body d-flex gap-3">
                <?= coverImg($b['cover_url'], $b['title']) ?>
                <div class="flex-grow-1">
                    <h2 class="h5"><?= e($b['title']) ?></h2>
                    <p class="mb-1 small text-muted"><?= e($b['subject_name']) ?> | <?= e($b['book_condition']) ?></p>
                    <p class="mb-2"><?= statusBadge($b['availability']) ?></p>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary js-qr"
                                data-token="<?= e($b['qr_code']) ?>"
                                data-title="<?= e($b['title']) ?>">QR</button>
                        <a class="btn btn-sm btn-outline-primary"
                           href="edit-book.php?book_id=<?= (int)$b['book_id'] ?>">Edit</a>
                        <button type="button" class="btn btn-sm btn-outline-danger js-delete"
                                data-book-id="<?= (int)$b['book_id'] ?>"
                                data-title="<?= e($b['title']) ?>">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <?php endif; ?>
</section>

<!-- QR modal -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center">
            <div class="modal-header">
                <h2 class="modal-title h5" id="qrModalLabel">QR code</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="qrTitle" class="fw-semibold"></p>
                <div id="qrBox" class="d-flex justify-content-center"></div>
            </div>
        </div>
    </div>
</div>

<!-- Delete modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="deleteModalLabel">Delete this book?</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="deleteText" class="mb-2"></p>
                <div id="deleteMsg" class="alert alert-danger d-none mb-0" role="alert"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmDelete" class="btn btn-danger">Delete</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>window.API_BASE = <?= json_encode(url('api/')) ?>;</script>
<script type="module" src="<?= e(url('assets/js/my-books.js')) ?>"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>