<?php
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    redirect(homePathFor($_SESSION['role']));
}
redirect('login.php');