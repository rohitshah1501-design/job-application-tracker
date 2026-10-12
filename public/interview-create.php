<?php
declare(strict_types=1);

use App\Controllers\InterviewController;
use App\Models\Database;
use App\Models\Interview;
use App\Models\JobApplication;

require dirname(__DIR__) . '/app/bootstrap.php';
require_authenticated_user();

$userId = (int) $_SESSION['user_id'];
$applicationOptions = [];
$loadError = false;
try {
    $pdo = Database::connect();
    $applicationOptions = JobApplication::optionsForUser($pdo, $userId);
} catch (Throwable) {
    $loadError = true;
}

$data = ['application_id' => null, 'interview_at' => '', 'interview_type' => 'other', 'notes' => null];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'application_id' => is_string($_POST['application_id'] ?? null) ? $_POST['application_id'] : null,
        'interview_at' => is_string($_POST['interview_at'] ?? null) ? $_POST['interview_at'] : '',
        'interview_type' => is_string($_POST['interview_type'] ?? null) ? $_POST['interview_type'] : 'other',
        'notes' => is_string($_POST['notes'] ?? null) ? $_POST['notes'] : null,
    ];
    if (!csrf_is_valid($_POST['_csrf_token'] ?? null)) {
        $errors[] = 'This form expired. Please try again.';
    } elseif (!$loadError) {
        $result = InterviewController::validate($_POST);
        $data = $result['data'];
        $errors = $result['errors'];
        if ($errors === []) {
            try {
                $interviewId = Interview::createForUser($pdo, $userId, $data);
                if ($interviewId === false) {
                    $errors[] = 'Choose one of your own job applications.';
                } else {
                    set_flash_message('success', 'Interview scheduled.');
                    header('Location: /interview-view.php?id=' . $interviewId);
                    exit;
                }
            } catch (Throwable) {
                $errors[] = 'The interview could not be saved. Check the details and try again.';
            }
        }
    }
}

$pageTitle = 'Schedule interview';
$formAction = '/interview-create.php';
$submitLabel = 'Save interview';
require dirname(__DIR__) . '/app/views/partials/header.php';
?>
<div class="dashboard-shell">
    <div class="page-title-row"><p class="eyebrow">Interview details</p><h1>Schedule an interview</h1><p>Choose one of your applications and add the meeting details.</p></div>
    <section class="panel form-panel">
        <?php if ($loadError): ?><div class="message message-error" role="alert">Applications could not be loaded right now. Please try again.</div>
        <?php elseif ($applicationOptions === []): ?><div class="empty-state"><h3>No applications to attach yet.</h3><p>Add a job application before scheduling its interview.</p><a class="button" href="/application-create.php">Add an application</a></div>
        <?php else: ?>
            <?php if ($errors !== []): ?><div class="message message-error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= escape_html($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <?php require dirname(__DIR__) . '/app/views/interviews/form.php'; ?>
        <?php endif; ?>
    </section>
</div>
<?php require dirname(__DIR__) . '/app/views/partials/footer.php'; ?>
