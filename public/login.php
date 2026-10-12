<?php
declare(strict_types=1);

use App\Controllers\AuthController;
use App\Models\Database;

require dirname(__DIR__) . '/app/bootstrap.php';

if (is_user_authenticated()) {
    header('Location: /dashboard.php');
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = is_string($_POST['email'] ?? null) ? $_POST['email'] : '';

    if (!csrf_is_valid($_POST['_csrf_token'] ?? null)) {
        $errors[] = 'This form expired. Please try again.';
    } else {
        try {
            $result = AuthController::login(Database::connect(), $_POST);
            if ($result['success']) {
                header('Location: /dashboard.php');
                exit;
            }
            $errors[] = 'The email address or password is incorrect.';
        } catch (Throwable) {
            $errors[] = 'Sign in is temporarily unavailable. Please try again.';
        }
    }
}

$flashMessage = take_flash_message();
$pageTitle = 'Sign in';
require dirname(__DIR__) . '/app/views/partials/header.php';
?>
<div class="auth-layout">
    <section class="welcome-copy">
        <p class="eyebrow">A more focused search</p>
        <h1>Keep every opportunity in one place.</h1>
        <p>Sign in to pick up where you left off and keep your next steps close at hand.</p>
    </section>

    <section class="auth-card" aria-labelledby="form-title">
        <h2 id="form-title">Welcome back</h2>
        <p class="card-intro">Sign in to your account.</p>

        <?php if ($flashMessage !== null): ?>
            <div class="message message-<?= escape_html($flashMessage['type']) ?>" role="status">
                <?= escape_html($flashMessage['message']) ?>
            </div>
        <?php endif; ?>
        <?php if ($errors !== []): ?>
            <div class="message message-error" role="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= escape_html($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="/login.php">
            <input type="hidden" name="_csrf_token" value="<?= escape_html(csrf_token()) ?>">
            <div class="field">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" maxlength="254" autocomplete="email" required value="<?= escape_html($email) ?>">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" maxlength="72" autocomplete="current-password" required>
            </div>
            <button class="button" type="submit">Sign in</button>
        </form>
        <p class="form-footer">New to the tracker? <a href="/register.php">Create an account</a></p>
    </section>
</div>
<?php require dirname(__DIR__) . '/app/views/partials/footer.php'; ?>
