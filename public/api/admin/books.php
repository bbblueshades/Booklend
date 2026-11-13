<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$pageTitle = 'Manage Books';
require __DIR__ . '/../../includes/header.php';
?>
<section id="booksApp" data-api="<?= e(url('api/admin')) ?>">
    <h1>All books</h1>
    <div id="alertBox" aria-live="polite"></div>

    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <label for="searchBooks" class="form-label fw-bold">Search all books</label>
            <input type="search" id="searchBooks" class="form-control"
                   placeholder="Title, author or owner" autocomplete="off">
        </div>
        <div class="col-md-4">
            <label for="statusFilter" class="form-label fw-bold">Status</label>
            <select id="statusFilter" class="form-select">
                <option value="">All</option>
                <option value="available">available</option>
                <option value="borrowed">borrowed</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr><th>ID</th><th>Title</th><th>Owner</th><th>Subject</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody id="booksBody"></tbody>
        </table>
    </div>
</section>

<!-- View modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title h5" id="viewModalLabel">Book details</h2>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="viewBody"></div>
    </div></div>
</div>

<!-- Delete modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title h5" id="deleteModalLabel">Delete this book?</h2>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body"><p id="deleteModalName" class="mb-0"></p></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" id="deleteConfirm">Delete</button>
        </div>
    </div></div>
</div>

<script type="module" src="<?= e(url('assets/js/admin-books.js')) ?>"></script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>