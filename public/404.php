<?php
require_once __DIR__ . '/../includes/auth.php';
http_response_code(404);
$pageTitle = 'Page not found';
require __DIR__ . '/../includes/header.php';
?>
<section class="text-center">
    <h1>Page not found</h1>
    <p>No records found at this address.</p>
    <a class="btn btn-primary" href="<?= e(url('index.php')) ?>">Back to home</a>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>