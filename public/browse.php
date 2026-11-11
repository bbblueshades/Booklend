<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pageTitle = 'Browse';
require __DIR__ . '/../includes/header.php';
?>
<section>
    <h1>Browse books</h1>
    <p>Welcome, <?= e(currentUser()['full_name']) ?>. (Placeholder, built in Phase 6.)</p>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>