<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$subjects = getDB()->query('SELECT subject_id, subject_name FROM subjects ORDER BY subject_name')
                   ->fetchAll();

// Get the books from the database
$books = getDB()->query(
    "SELECT b.book_id, b.title, b.author, b.cover_url, b.availability, s.subject_name
     FROM books b
     JOIN subjects s ON s.subject_id = b.subject_id
     ORDER BY b.created_at DESC"
)->fetchAll();

$pageTitle = 'Browse';
require __DIR__ . '/../includes/header.php';
?>
<section>
    <h1>Browse books</h1>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3">
        <?php if (!$books): ?>
            <p class="text-muted">No records found.</p>
        <?php endif; ?>

        <?php foreach ($books as $book): ?>
            <div class="col">
                <article class="card h-100">
                    <?php if (!empty($book['cover_url'])): ?>
                        <img src="<?= e($book['cover_url']) ?>" class="card-img-top browse-cover"
                            alt="Cover of <?= e($book['title']) ?>">
                    <?php else: ?>
                        <div class="browse-cover browse-cover--empty"
                            role="img" aria-label="No cover available for <?= e($book['title']) ?>">No cover</div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h2 class="h5 card-title"><?= e($book['title']) ?></h2>
                        <p class="card-text small text-muted">
                            <?= e($book['author']) ?>, <?= e($book['subject_name']) ?>
                        </p>
                        <span class="badge text-bg-<?= $book['availability'] === 'available' ? 'success' : 'secondary' ?>">
                            <?= e(ucfirst($book['availability'])) ?>
                        </span>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>