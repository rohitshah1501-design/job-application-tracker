<?php
declare(strict_types=1);

use App\Models\Database;
use App\Models\Interview;

require dirname(__DIR__) . '/app/bootstrap.php';
require_authenticated_user();

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$rawId = $isPost ? ($_POST['interview_id'] ?? null) : ($_GET['id'] ?? null);
$interviewId = filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$interview = null;
$loadError = false;
if ($interviewId !== false) {
    try {
        $pdo = Database::connect();
        $interview = Interview::findForUser($pdo, (int) $interviewId, (int) $_SESSION['user_id']);
    } catch (Throwable) {
        $loadError = true;
    }
}

if ($interview === null) {
    http_response_code($loadError ? 503 : 404);
    $pageTitle = $loadError ? 'Interviews unavailable' : 'Interview not found';
    require dirname(__DIR__) . '/app/views/partials/header.php';
    ?>
    <div class="dashboard-shell panel form-panel">
        <h1><?= $loadError ? 'Interviews unavailable' : 'Interview not found' ?></h1>
        <p><?= $loadError ? 'This interview could not be loaded right now.' : 'This interview is unavailable.' ?></p>
        <a href="/interviews.php">Return to interviews</a>
    </div>
    <?php
    require dirname(__DIR__) . '/app/views/partials/footer.php';
    exit;
}

if ($isPost) {
    if (!csrf_is_valid($_POST['_csrf_token'] ?? null) || ($_POST['confirm_delete'] ?? '') !== 'yes') {
        set_flash_message('error', 'The delete request could not be confirmed. No interview was removed.');
        header('Location: /interviews.php');
        exit;
    }

    try {
        $deleted = Interview::deleteForUser($pdo, (int) $interviewId, (int) $_SESSION['user_id']);
        set_flash_message(
            $deleted ? 'success' : 'error',
            $deleted ? 'Interview deleted.' : 'This interview is no longer available.'
        );
    } catch (Throwable) {
        set_flash_message('error', 'The interview could not be deleted. Please try again.');
    }

    header('Location: /interviews.php');
    exit;
}

$pageTitle = 'Confirm interview deletion';
require dirname(__DIR__) . '/app/views/partials/header.php';
?>
<div class="dashboard-shell">
    <section class="panel form-panel" aria-labelledby="delete-interview-heading">
        <p class="eyebrow">Please confirm</p>
        <h1 id="delete-interview-heading">Delete this interview?</h1>
        <p class="confirm-copy">The <?= escape_html(date('M j, Y \a\t g:i A', strtotime((string) $interview['interview_at']))) ?> interview for <strong><?= escape_html((string) $interview['job_title']) ?></strong> at <strong><?= escape_html((string) $interview['company_name']) ?></strong> will be permanently removed.</p>
        <div class="confirm-actions">
            <form method="post" action="/interview-delete.php">
                <input type="hidden" name="_csrf_token" value="<?= escape_html(csrf_token()) ?>">
                <input type="hidden" name="interview_id" value="<?= (int) $interview['id'] ?>">
                <input type="hidden" name="confirm_delete" value="yes">
                <button class="button button-danger" type="submit">Delete interview</button>
            </form>
            <a class="button button-secondary" href="/interview-view.php?id=<?= (int) $interview['id'] ?>">Keep interview</a>
        </div>
    </section>
</div>
<?php require dirname(__DIR__) . '/app/views/partials/footer.php'; ?>
