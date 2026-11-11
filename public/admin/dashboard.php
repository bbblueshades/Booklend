<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$pageTitle = 'Admin Dashboard';
require __DIR__ . '/../../includes/header.php';
?>
<section>
    <h1>Admin dashboard</h1>
    <p>Welcome, <?= e(currentUser()['full_name']) ?>. (Placeholder, built in Phase 10.)</p>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>