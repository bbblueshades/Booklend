<?php
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    redirect(homePathFor($_SESSION['role']));
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!verifyCsrf()) {
        $error = 'Invalid request. Please try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Please complete all login credentials.';
    } else {
        $stmt = getDB()->prepare(
            'SELECT user_id, full_name, password_hash, role FROM users WHERE email = ?'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            loginUser($user);
            redirect(homePathFor($user['role']));
        }
        // Same message for wrong email and wrong password
        $error = 'Invalid login credentials.';
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <h1 class="h2 mb-4">Log in</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="" novalidate>
            <?= csrfField() ?>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email"
                       value="<?= e($email) ?>" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Log in</button>
            <p class="text-center mt-3">No account yet? <a href="<?= e(url('register.php')) ?>">Register</a></p>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>