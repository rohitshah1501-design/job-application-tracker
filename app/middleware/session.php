<?php
declare(strict_types=1);

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
    session_name('job_tracker_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function csrf_token(): string
{
    if (!isset($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrf_is_valid(mixed $submittedToken): bool
{
    return is_string($submittedToken)
        && isset($_SESSION['_csrf_token'])
        && is_string($_SESSION['_csrf_token'])
        && hash_equals($_SESSION['_csrf_token'], $submittedToken);
}

function set_flash_message(string $type, string $message): void
{
    $_SESSION['_flash_message'] = [
        'type' => $type,
        'message' => $message,
    ];
}

/** @return array{type: string, message: string}|null */
function take_flash_message(): ?array
{
    $message = $_SESSION['_flash_message'] ?? null;
    unset($_SESSION['_flash_message']);

    return is_array($message) ? $message : null;
}

function is_user_authenticated(): bool
{
    return isset($_SESSION['user_id']) && is_int($_SESSION['user_id']);
}

function require_authenticated_user(): void
{
    if (!is_user_authenticated()) {
        header('Location: /login.php');
        exit;
    }
}

function escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

