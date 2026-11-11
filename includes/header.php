<?php
$pageTitle = $pageTitle ?? 'Booklend';
$user  = currentUser();
$flash = getFlash();

// Badge markup, hidden when there is nothing unread
$unread = $user && $user['role'] === 'user' ? unreadCount($user['user_id']) : 0;
$badge  = '<span class="badge text-bg-danger ms-1 notif-badge' . ($unread ? '' : ' d-none') . '"'
        . ' id="notifBadge">' . $unread . '</span>';
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
<body class="d-flex flex-column min-vh-100">
<header>
    <nav class="navbar navbar-expand-lg navbar-dark site-navbar" aria-label="Main navigation">
        <div class="container-fluid">
            <a class="navbar-brand brand" href="<?= e(url('index.php')) ?>">
                <span class="brand-logo" aria-hidden="true">&#128214;</span> BOOK LEND
            </a>

            <!-- Mobile: a "Menu" button instead of the hamburger icon -->
            <button class="btn btn-light rounded-pill px-3 d-lg-none" type="button"
                    data-bs-toggle="collapse" data-bs-target="#mainNav"
                    aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation menu">
                Menu <?php if ($unread): ?><span class="badge text-bg-danger"><?= $unread ?></span><?php endif; ?>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <?php if (!$user): ?>
                    <?= navLink('login.php', 'Login') ?>
                    <?= navLink('register.php', 'Register') ?>

                <?php elseif ($user['role'] === 'admin'): ?>
                    <?= navLink('admin/dashboard.php', 'Dashboard') ?>
                    <?= navLink('admin/users.php', 'Users') ?>
                    <?= navLink('admin/books.php', 'Books') ?>
                    <?= navLink('admin/reports.php', 'Reports') ?>

                <?php else: ?>
                    <?= navLink('browse.php', 'Browse') ?>
                    <?= navLink('my-books.php', 'My Books') ?>
                    <?= navLink('requests.php', 'Requests') ?>
                    <?= navLink('notifications.php', 'Notifs', $badge) ?>
                    <?= navLink('profile.php', 'Profile') ?>
                <?php endif; ?>

                <?php if ($user): ?>
                    <li class="nav-item ms-lg-2">
                        <form method="post" action="<?= e(url('logout.php')) ?>">
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

<main class="container py-4 flex-grow-1">
<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>