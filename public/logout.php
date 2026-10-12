<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login.php');
    exit;
}

if (!is_user_authenticated()) {
    header('Location: /login.php');
    exit;
}

if (!csrf_is_valid($_POST['_csrf_token'] ?? null)) {
    set_flash_message('error', 'Your sign-out request could not be verified. Please try again.');
    header('Location: /account.php');
    exit;
}

$_SESSION = [];
session_regenerate_id(true);
unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['_csrf_token']);
set_flash_message('success', 'You have been signed out.');
header('Location: /login.php');
exit;
