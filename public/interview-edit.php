<?php
declare(strict_types=1);

use App\Controllers\InterviewController;
use App\Models\Database;
use App\Models\Interview;
use App\Models\JobApplication;

require dirname(__DIR__) . '/app/bootstrap.php';
require_authenticated_user();

$userId = (int) $_SESSION['user_id'];
$interviewId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$interview = null;
$applicationOptions = [];
$loadError = false;
if ($interviewId !== false) {
    try {
        $pdo = Database::connect();
        $interview = Interview::findForUser($pdo, (int) $interviewId, $userId);
        if ($interview !== null) {
            $applicationOptions = JobApplication::optionsForUser($pdo, $userId);
        }
    } catch (Throwable) {
        $loadError = true;
    }
}

if ($loadError || $interview === null) {
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

$data = $interview;
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid($_POST['_csrf_token'] ?? null)) {
        $errors[] = 'This form expired. Please try again.';
    } else {
        $result = InterviewController::validate($_POST);
        $data = $result['data'];
        $errors = $result['errors'];
        if ($errors === []) {
            try {
                $updated = Interview::updateForUser($pdo, (int) $interviewId, $userId, $data);
                if (!$updated) {
                    $errors[] = 'Choose one of your own job applications.';
                } else {
                    set_flash_message('success', 'Interview updated.');
                    header('Location: /interview-view.php?id=' . (int) $interviewId);
                    exit;
                }
            } catch (Throwable) {
                $errors[] = 'The interview could not be updated. Check the details and try again.';
            }
        }
    }
}

$pageTitle = 'Edit interview';
$formAction = '/interview-edit.php?id=' . (int) $interviewId;
$submitLabel = 'Save changes';
require dirname(__DIR__) . '/app/views/partials/header.php';
?>
<div class="dashboard-shell">
    <div class="page-title-row"><p class="eyebrow">Interview details</p><h1>Edit interview</h1><p>Update the scheduled time, type, notes, or linked application.</p></div>
    <section class="panel form-panel">
        <?php if ($applicationOptions === []): ?><div class="message message-error" role="alert">Add a job application before editing this interview.</div>
        <?php else: ?>
            <?php if ($errors !== []): ?><div class="message message-error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= escape_html($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <?php require dirname(__DIR__) . '/app/views/interviews/form.php'; ?>
        <?php endif; ?>
    </section>
</div>
<?php require dirname(__DIR__) . '/app/views/partials/footer.php'; ?>
