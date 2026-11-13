<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

$user = currentUser();
$uid  = $user['user_id'];

// Active requests first, newest first within each status
$order = "FIELD(r.status,'Requested','Approved','Borrowed','Returned','Rejected'), r.requested_at DESC";

$mine = $pdo->prepare(
    "SELECT r.request_id, r.status, r.due_date, b.title, u.full_name AS owner_name
       FROM borrow_requests r
       JOIN books b ON b.book_id = r.book_id
       JOIN users u ON u.user_id = b.owner_id
      WHERE r.borrower_id = ?
      ORDER BY $order LIMIT 100"
);
$mine->execute([$uid]);
$myRequests = $mine->fetchAll(PDO::FETCH_ASSOC);

$inc = $pdo->prepare(
    "SELECT r.request_id, r.status, b.title, u.full_name AS borrower_name
       FROM borrow_requests r
       JOIN books b ON b.book_id = r.book_id
       JOIN users u ON u.user_id = r.borrower_id
      WHERE b.owner_id = ?
      ORDER BY $order LIMIT 100"
);
$inc->execute([$uid]);
$incoming = $inc->fetchAll(PDO::FETCH_ASSOC);

// Success messages after an action (looked up from a fixed list, never echoed raw)
$messages = [
    'requested' => 'Request sent. The owner has been notified.',
    'approved'  => 'Request approved. The borrower has been notified.',
    'rejected'  => 'Request rejected. The borrower has been notified.',
    'borrowed'  => 'Handover recorded. The due date has been set.',
    'returned'  => 'Return recorded. The book is available again.',
];
$key   = isset($_GET['requested']) ? 'requested' : ($_GET['done'] ?? '');
$flash = $messages[$key] ?? null;

function reqBadge(string $status): string {
    $cls = [
        'Requested' => 'text-bg-warning',
        'Approved'  => 'text-bg-info',
        'Borrowed'  => 'text-bg-primary',
        'Returned'  => 'text-bg-success',
        'Rejected'  => 'text-bg-danger',
    ][$status] ?? 'text-bg-secondary';
    return '<span class="badge ' . $cls . '">' . e($status) . '</span>';
}

function dueText(?string $d): string {
    return $d ? date('M j, Y', strtotime($d)) : '-';
}

function incomingActions(array $r): string {
    $id    = (int)$r['request_id'];
    $title = e($r['title']);
    switch ($r['status']) {
        case 'Requested':
            return '<button type="button" class="btn btn-sm btn-success js-respond" data-id="' . $id . '" data-decision="approve">Approve</button> '
                 . '<button type="button" class="btn btn-sm btn-outline-danger js-respond" data-id="' . $id . '" data-decision="reject">Reject</button>';
        case 'Approved':
            return '<button type="button" class="btn btn-sm btn-primary js-scan" data-id="' . $id . '" data-title="' . $title . '" data-mode="handover">Scan QR: handover</button>';
        case 'Borrowed':
            return '<button type="button" class="btn btn-sm btn-primary js-scan" data-id="' . $id . '" data-title="' . $title . '" data-mode="return">Scan QR: return</button>';
        default:
            return '<span class="text-muted">-</span>';
    }
}

$pageTitle = 'Requests';
require __DIR__ . '/../includes/header.php';
?>
<section>
    <div id="pageMsg" role="alert" aria-live="polite" <?= $flash ? 'class="alert alert-success"' : '' ?>><?= $flash ? e($flash) : '' ?></div>

    <!-- ===== My requests ===== -->
    <h1>My requests</h1>
    <?php if (!$myRequests): ?>
        <p class="text-muted">No records found. Find a book on <a href="browse.php">Browse</a> and request it.</p>
    <?php else: ?>
        <div class="table-responsive d-none d-md-block">
            <table class="table align-middle">
                <thead><tr>
                    <th scope="col">Book</th><th scope="col">Owner</th>
                    <th scope="col">Status</th><th scope="col">Due Date</th>
                </tr></thead>
                <tbody>
                <?php foreach ($myRequests as $r): ?>
                    <tr>
                        <td><?= e($r['title']) ?></td>
                        <td><?= e($r['owner_name']) ?></td>
                        <td><?= reqBadge($r['status']) ?></td>
                        <td><?= e(dueText($r['due_date'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="d-md-none">
        <?php foreach ($myRequests as $r): ?>
            <div class="card mb-3"><div class="card-body">
                <h2 class="h5"><?= e($r['title']) ?></h2>
                <p class="small text-muted mb-2">Owner: <?= e($r['owner_name']) ?> | Due: <?= e(dueText($r['due_date'])) ?></p>
                <?= reqBadge($r['status']) ?>
            </div></div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ===== Incoming requests ===== -->
    <h2 class="mt-5">Incoming requests</h2>
    <?php if (!$incoming): ?>
        <p class="text-muted">No records found. Requests for your books will appear here.</p>
    <?php else: ?>
        <div class="table-responsive d-none d-md-block">
            <table class="table align-middle">
                <thead><tr>
                    <th scope="col">Book</th><th scope="col">Borrower</th>
                    <th scope="col">Status</th><th scope="col">Actions</th>
                </tr></thead>
                <tbody>
                <?php foreach ($incoming as $r): ?>
                    <tr>
                        <td><?= e($r['title']) ?></td>
                        <td><?= e($r['borrower_name']) ?></td>
                        <td><?= reqBadge($r['status']) ?></td>
                        <td class="text-nowrap"><?= incomingActions($r) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="d-md-none">
        <?php foreach ($incoming as $r): ?>
            <div class="card mb-3"><div class="card-body">
                <h2 class="h5"><?= e($r['title']) ?></h2>
                <p class="small text-muted mb-2">Borrower: <?= e($r['borrower_name']) ?></p>
                <p class="mb-2"><?= reqBadge($r['status']) ?></p>
                <div class="d-flex flex-wrap gap-2"><?= incomingActions($r) ?></div>
            </div></div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<<!-- Scan modal -->
<div class="modal fade" id="scanModal" tabindex="-1" aria-labelledby="scanTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="scanTitle">Scan QR code</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="scanBook" class="fw-semibold mb-2"></p>

                <!-- Step 1: camera -->
                <div id="scanView">
                    <div id="reader" class="mb-2"></div>
                    <p class="small text-muted text-center">Point the camera at the book's QR code</p>
                    <label for="manualToken" class="form-label small">No camera? Type the code under the QR instead</label>
                    <div class="input-group">
                        <input id="manualToken" class="form-control" maxlength="100" autocomplete="off">
                        <button id="manualSubmit" type="button" class="btn btn-secondary">Confirm</button>
                    </div>
                </div>

                <!-- Step 2: confirm before committing -->
                <div id="confirmPanel" class="d-none">
                    <div class="border rounded p-3 mb-3">
                        <p id="confirmText" class="fw-semibold mb-1"></p>
                        <p id="confirmDetail" class="small text-muted mb-0"></p>
                    </div>
                    <div class="d-flex gap-2">
                        <button id="confirmScan" type="button" class="btn btn-primary flex-grow-1">Confirm</button>
                        <button id="cancelScan" type="button" class="btn btn-secondary flex-grow-1">Cancel</button>
                    </div>
                </div>

                <div id="scanMsg" role="alert" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
<script>window.API_BASE = <?= json_encode(url('api/')) ?>;</script>
<script type="module" src="<?= e(url('assets/js/requests.js')) ?>"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>