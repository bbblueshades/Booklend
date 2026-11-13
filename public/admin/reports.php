<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/stats.php';
requireAdmin();

$subjects = getSubjectStats(8);
$statuses = getStatusStats();
$table    = getBookBorrowTable();

$pageTitle = 'Reports';
require __DIR__ . '/../../includes/header.php';
?>
<section>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Reports</h1>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= e(url('admin/export_csv.php')) ?>">Export CSV</a>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6"><div class="card p-3">
            <h2 class="h5">Most-borrowed subjects</h2>
            <?php if ($subjects): ?>
                <canvas id="subjectChart" height="200"
                        data-labels="<?= e(json_encode(array_column($subjects, 'subject_name'))) ?>"
                        data-values="<?= e(json_encode(array_map('intval', array_column($subjects, 'total')))) ?>"
                        role="img" aria-label="Bar chart of the most-borrowed subjects"></canvas>
            <?php else: ?><p class="text-muted mb-0">No records found.</p><?php endif; ?>
        </div></div>
        <div class="col-lg-6"><div class="card p-3">
            <h2 class="h5">Requests by status</h2>
            <?php if ($statuses): ?>
                <canvas id="statusChart" height="200"
                        data-labels="<?= e(json_encode(array_column($statuses, 'status'))) ?>"
                        data-values="<?= e(json_encode(array_map('intval', array_column($statuses, 'total')))) ?>"
                        role="img" aria-label="Pie chart of requests by status"></canvas>
            <?php else: ?><p class="text-muted mb-0">No records found.</p><?php endif; ?>
        </div></div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="table-light"><tr><th>Book</th><th>Times borrowed</th><th>Subject</th></tr></thead>
            <tbody>
            <?php if (!$table): ?>
                <tr><td colspan="3" class="text-center text-muted">No records found.</td></tr>
            <?php endif; ?>
            <?php foreach ($table as $row): ?>
                <tr>
                    <td><?= e($row['title']) ?></td>
                    <td><?= (int) $row['times_borrowed'] ?></td>
                    <td><?= e($row['subject_name']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= e(url('assets/js/charts.js')) ?>"></script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>