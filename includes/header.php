<?php
$pageTitle = $pageTitle ?? 'Booklend';
$user = currentUser();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Booklend</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<header>
    <nav class="navbar navbar-expand-lg bg-secondary navbar-dark" aria-label="Main navigation">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold fst-italic" href="<?= e(url('index.php')) ?>">BOOK LEND</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#mainNav" aria-controls="mainNav"
                    aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <?php if (!$user): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('login.php')) ?>">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('register.php')) ?>">Register</a></li>
                <?php elseif ($user['role'] === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('admin/dashboard.php')) ?>">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('admin/users.php')) ?>">Users</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('admin/books.php')) ?>">Books</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('admin/reports.php')) ?>">Reports</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('browse.php')) ?>">Browse</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('my-books.php')) ?>">My Books</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('requests.php')) ?>">Requests</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('notifications.php')) ?>">Notifs</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('profile.php')) ?>">Profile</a></li>
                <?php endif; ?>
                <?php if ($user): ?>
                    <li class="nav-item">
                        <form method="post" action="<?= e(url('logout.php')) ?>" class="d-inline">
                            <?= csrfField() ?>
                            <button type="submit" class="btn btn-light btn-sm rounded-pill px-3">Logout</button>
                        </form>
                    </li>
                <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>
<main class="container py-4">
<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>