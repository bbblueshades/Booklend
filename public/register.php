<?php
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    redirect(homePathFor($_SESSION['role']));
}

$errors = [];
$fullName = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!verifyCsrf()) {
        $errors[] = 'Invalid request. Please try again.';
    }
    if ($fullName === '' || mb_strlen($fullName) > 100) {
        $errors[] = 'Full name is required (maximum 100 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors[] = 'Password must be at least 8 characters with a letter and a number.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $db = getDB();
        $check = $db->prepare('SELECT user_id FROM users WHERE email = ?');
        $check->execute([$email]);

        if ($check->fetch()) {
            $errors[] = 'That email is already registered.';
        } else {
            try {
                $insert = $db->prepare(
                    'INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)'
                );
                $insert->execute([$fullName, $email, password_hash($password, PASSWORD_DEFAULT), 'user']);
                setFlash('success', 'Record successfully saved. You can now log in.');
                redirect('login.php');
            } catch (PDOException $ex) {
                error_log('Register failed: ' . $ex->getMessage());
                $errors[] = ($ex->getCode() === '23000')
                    ? 'That email is already registered.'
                    : 'Something went wrong. Please try again.';
            }
        }
    }
}

$pageTitle = 'Register';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <h1 class="h2 mb-4">Create an account</h1>

        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="" novalidate>
            <?= csrfField() ?>
            <div class="mb-3">
                <label for="full_name" class="form-label">Full name</label>
                <input type="text" class="form-control" id="full_name" name="full_name"
                       maxlength="100" value="<?= e($fullName) ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email"
                       maxlength="100" value="<?= e($email) ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password"
                       minlength="8" aria-describedby="pwHelp" required>
                <div id="pwHelp" class="form-text">At least 8 characters with a letter and a number</div>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm password</label>
                <input type="password" class="form-control" id="confirm_password"
                       name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Register</button>
            <p class="text-center mt-3">Already have an account? <a href="<?= e(url('login.php')) ?>">Log in</a></p>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>