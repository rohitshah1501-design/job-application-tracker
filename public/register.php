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
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = is_string($_POST['name'] ?? null) ? $_POST['name'] : '';
    $email = is_string($_POST['email'] ?? null) ? $_POST['email'] : '';

    if (!csrf_is_valid($_POST['_csrf_token'] ?? null)) {
        $errors[] = 'This form expired. Please try again.';
    } else {
        try {
            $result = AuthController::register(Database::connect(), $_POST);
            $errors = $result['errors'];
            $name = $result['name'];
            $email = $result['email'];

            if ($errors === []) {
                set_flash_message('success', 'Your account is ready. Sign in to continue.');
                header('Location: /login.php');
                exit;
            }
        } catch (Throwable) {
            $errors[] = 'We could not create your account right now. Please try again.';
        }
    }
}

$pageTitle = 'Create account';
require dirname(__DIR__) . '/app/views/partials/header.php';
?>
<div class="auth-layout">
    <section class="welcome-copy">
        <p class="eyebrow">Make room for what is next</p>
        <h1>Keep your job search clear and moving.</h1>
        <p>Create a private account to organize applications and keep important dates in view.</p>
    </section>

    <section class="auth-card" aria-labelledby="form-title">
        <h2 id="form-title">Create your account</h2>
        <p class="card-intro">It takes a minute to get started.</p>

        <?php if ($errors !== []): ?>
            <div class="message message-error" role="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= escape_html($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="/register.php">
            <input type="hidden" name="_csrf_token" value="<?= escape_html(csrf_token()) ?>">
            <div class="field">
                <label for="name">Full name</label>
                <input id="name" name="name" type="text" maxlength="120" autocomplete="name" required value="<?= escape_html($name) ?>">
            </div>
            <div class="field">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" maxlength="254" autocomplete="email" required value="<?= escape_html($email) ?>">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" minlength="12" maxlength="72" autocomplete="new-password" required aria-describedby="password-help">
                <span class="field-help" id="password-help">At least 12 characters; maximum 72 bytes.</span>
            </div>
            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" minlength="12" maxlength="72" autocomplete="new-password" required>
            </div>
            <button class="button" type="submit">Create account</button>
        </form>
        <p class="form-footer">Already have an account? <a href="/login.php">Sign in</a></p>
    </section>
</div>
<?php require dirname(__DIR__) . '/app/views/partials/footer.php'; ?>
