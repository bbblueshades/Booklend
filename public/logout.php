<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    logoutUser();
    session_start(); // fresh session just to carry the message
    session_regenerate_id(true);
    setFlash('success', 'You have been logged out.');
}
redirect('login.php');