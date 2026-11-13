<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/stats.php';
requireAdmin();

$counts   = getCounts();
$subjects = getSubjectStats();

$pageTitle = 'Admin Dashboard';
require __DIR__ . '/../../includes/header.php';
?>
<section>
    <h1>Admin dashboard</h1>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card p-3"><div class="fw-bold">Total users</div>
            <div class="fs-3"><?= $counts['users'] ?></div></div></div>
        <div class="col-md-4"><div class="card p-3"><div class="fw-bold">Total books</div>
            <div class="fs-3"><?= $counts['books'] ?></div></div></div>
        <div class="col-md-4"><div class="card p-3"><div class="fw-bold">Active borrows</div>
            <div class="fs-3"><?= $counts['active'] ?></div></div></div>
    </div>

    <div class="card p-3">
        <h2 class="h5">Most-borrowed subjects</h2>
        <?php if (!$subjects): ?>
            <p class="text-muted mb-0">No records found.</p>
        <?php else: ?>
            <canvas id="subjectChart" height="110"
                    data-labels="<?= e(json_encode(array_column($subjects, 'subject_name'))) ?>"
                    data-values="<?= e(json_encode(array_map('intval', array_column($subjects, 'total')))) ?>"
                    aria-label="Bar chart of the most-borrowed subjects" role="img"></canvas>
        <?php endif; ?>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= e(url('assets/js/charts.js')) ?>"></script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>