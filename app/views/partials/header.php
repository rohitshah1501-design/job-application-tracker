<?php
declare(strict_types=1);

$pageTitle = isset($pageTitle) && is_string($pageTitle) ? $pageTitle : 'Job Application Tracker';
$currentPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#173D32">
    <title><?= escape_html($pageTitle) ?> · Job Application Tracker</title>
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to content</a>
    <header class="site-header">
        <a class="brand" href="/" aria-label="Job Application Tracker home">
            <span class="brand-mark" aria-hidden="true"></span>
            <span>Job Application Tracker</span>
        </a>
        <?php if (is_user_authenticated()): ?>
            <nav class="site-nav" aria-label="Main navigation">
                <a href="/dashboard.php" <?= $currentPage === 'dashboard.php' ? 'aria-current="page"' : '' ?>>Dashboard</a>
                <a href="/interviews.php" <?= in_array($currentPage, ['interviews.php', 'interview-create.php', 'interview-edit.php', 'interview-view.php', 'interview-delete.php'], true) ? 'aria-current="page"' : '' ?>>Interviews</a>
                <form method="post" action="/logout.php">
                    <input type="hidden" name="_csrf_token" value="<?= escape_html(csrf_token()) ?>">
                    <button class="nav-signout" type="submit">Sign out</button>
                </form>
            </nav>
        <?php else: ?>
            <span class="header-note">A calmer way to manage your search</span>
        <?php endif; ?>
    </header>
    <main class="page-shell" id="main-content">
