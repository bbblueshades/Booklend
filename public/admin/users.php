<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$pageTitle = 'Manage Users';
require __DIR__ . '/../../includes/header.php';
?>
<section id="usersApp"
         data-api="<?= e(url('api/admin')) ?>"
         data-me="<?= (int) currentUser()['user_id'] ?>">
    <h1>Users</h1>
    <div id="alertBox" aria-live="polite"></div>

    <label for="searchUsers" class="form-label fw-bold">Search users</label>
    <input type="search" id="searchUsers" class="form-control mb-3"
           placeholder="Name or email" autocomplete="off">

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr>
            </thead>
            <tbody id="usersBody"></tbody>
        </table>
    </div>
</section>

<!-- Edit role modal -->
<div class="modal fade" id="roleModal" tabindex="-1" aria-labelledby="roleModalLabel" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title h5" id="roleModalLabel">Edit user</h2>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <p id="roleModalName" class="fw-bold"></p>
            <label for="roleSelect" class="form-label">Role</label>
            <select id="roleSelect" class="form-select">
                <option value="user">user</option>
                <option value="admin">admin</option>
            </select>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="roleSave">Save</button>
        </div>
    </div></div>
</div>

<!-- Delete modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title h5" id="deleteModalLabel">Delete this user?</h2>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <p id="deleteModalName" class="mb-0"></p>
            <p class="text-muted mb-0">Their book listings and requests will also be removed.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" id="deleteConfirm">Delete</button>
        </div>
    </div></div>
</div>

<script type="module" src="<?= e(url('assets/js/admin-users.js')) ?>"></script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>