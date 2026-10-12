<?php
declare(strict_types=1);

use App\Models\Database;
use App\Models\JobApplication;

require dirname(__DIR__) . '/app/bootstrap.php';
require_authenticated_user();

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$rawId = $isPost ? ($_POST['application_id'] ?? null) : ($_GET['id'] ?? null);
$applicationId = filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($applicationId === false) {
    http_response_code(404);
    $application = null;
} else {
    $application = null;
    try {
        $pdo = Database::connect();
        $application = JobApplication::findForUser($pdo, (int) $applicationId, (int) $_SESSION['user_id']);
    } catch (Throwable) {
        http_response_code(503);
    }
}

if ($application === null) {
    $pageTitle = 'Application unavailable';
    require dirname(__DIR__) . '/app/views/partials/header.php';
    ?>
    <section class="dashboard-shell panel form-panel">
        <h1>Application unavailable</h1>
        <p>This application could not be found or loaded.</p>
        <a href="/dashboard.php">Return to applications</a>
    </section>
    <?php
    require dirname(__DIR__) . '/app/views/partials/footer.php';
    exit;
}

if ($isPost) {
    if (!csrf_is_valid($_POST['_csrf_token'] ?? null) || ($_POST['confirm_delete'] ?? '') !== 'yes') {
        set_flash_message('error', 'The delete request could not be confirmed. No application was removed.');
        header('Location: /dashboard.php');
        exit;
    }

    try {
        $deleted = JobApplication::deleteForUser($pdo, (int) $applicationId, (int) $_SESSION['user_id']);
        if ($deleted) {
            set_flash_message('success', 'Application deleted.');
        } else {
            set_flash_message('error', 'This application is no longer available.');
        }
    } catch (Throwable) {
        set_flash_message('error', 'The application could not be deleted. Please try again.');
    }

    header('Location: /dashboard.php');
    exit;
}

$pageTitle = 'Confirm deletion';
require dirname(__DIR__) . '/app/views/partials/header.php';
?>
<div class="dashboard-shell">
    <section class="panel form-panel" aria-labelledby="delete-heading">
        <p class="eyebrow">Please confirm</p>
        <h1 id="delete-heading">Delete this application?</h1>
        <p class="confirm-copy"><strong><?= escape_html((string) $application['job_title']) ?></strong> at <strong><?= escape_html((string) $application['company_name']) ?></strong> will be permanently removed. Its status history and any linked interview records will also be removed.</p>
        <div class="confirm-actions">
            <form method="post" action="/application-delete.php">
                <input type="hidden" name="_csrf_token" value="<?= escape_html(csrf_token()) ?>">
                <input type="hidden" name="application_id" value="<?= (int) $application['id'] ?>">
                <input type="hidden" name="confirm_delete" value="yes">
                <button class="button button-danger" type="submit">Delete application</button>
            </form>
            <a class="button button-secondary" href="/application-view.php?id=<?= (int) $application['id'] ?>">Keep application</a>
        </div>
    </section>
</div>
<?php require dirname(__DIR__) . '/app/views/partials/footer.php'; ?>
