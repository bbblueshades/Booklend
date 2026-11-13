<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

$user = currentUser();

$stmt = $pdo->prepare('SELECT full_name, email FROM users WHERE user_id = ?');
$stmt->execute([$user['user_id']]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);

$pageTitle = 'Update Profile';
require __DIR__ . '/../includes/header.php';
?>
<section>
    <h1>Update profile</h1>

    <div class="row">
        <div class="col-lg-6">
            <form id="profileForm" novalidate>
                <div class="mb-3">
                    <label for="fullName" class="form-label">Full name</label>
                    <input id="fullName" class="form-control" required maxlength="100"
                           autocomplete="name" value="<?= e($me['full_name']) ?>">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" type="email" class="form-control" required maxlength="100"
                           autocomplete="email" value="<?= e($me['email']) ?>">
                </div>

                <div class="mb-1">
                    <label for="newPassword" class="form-label">New password (optional)</label>
                    <input id="newPassword" type="password" class="form-control" maxlength="72"
                           autocomplete="new-password" aria-describedby="pwHelp strengthLabel">
                </div>
                <div id="pwHelp" class="form-text mb-2">Leave blank to keep your current password. At least 8 characters with a letter and a number.</div>

                <div id="strengthWrap" class="mb-3 d-none">
                    <div class="progress" style="height: 0.6rem;">
                        <div id="strengthBar" class="progress-bar" role="progressbar"
                             style="width: 0%" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"
                             aria-label="Password strength"></div>
                    </div>
                    <div id="strengthLabel" class="form-text" aria-live="polite"></div>
                </div>

                <div class="mb-3">
                    <label for="confirmPassword" class="form-label">Confirm password</label>
                    <input id="confirmPassword" type="password" class="form-control" maxlength="72"
                           autocomplete="new-password">
                </div>

                <div id="profileMsg" role="status" aria-live="polite" class="mb-3"></div>
                <button id="saveProfile" type="submit" class="btn btn-primary w-100">Save profile</button>
            </form>
        </div>
    </div>
</section>

<script>window.API_BASE = <?= json_encode(url('api/')) ?>;</script>
<script type="module" src="<?= e(url('assets/js/profile.js')) ?>"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>