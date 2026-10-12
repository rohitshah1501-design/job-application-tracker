<?php
declare(strict_types=1);

use App\Controllers\JobApplicationController;
use App\Models\Database;
use App\Models\JobApplication;

require dirname(__DIR__) . '/app/bootstrap.php';
require_authenticated_user();

$userId = (int) $_SESSION['user_id'];
$applicationId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$application = null;
$loadError = false;
if ($applicationId !== false) {
    try {
        $pdo = Database::connect();
        $application = JobApplication::findForUser($pdo, (int) $applicationId, $userId);
    } catch (Throwable) {
        $loadError = true;
    }
}

if ($application === null) {
    http_response_code($loadError ? 503 : 404);
    $pageTitle = $loadError ? 'Applications unavailable' : 'Application not found';
    require dirname(__DIR__) . '/app/views/partials/header.php';
    echo '<div class="dashboard-shell"><section class="panel form-panel"><h1>'
        . ($loadError ? 'Applications unavailable' : 'Application not found')
        . '</h1><p>'
        . ($loadError ? 'This application could not be loaded right now.' : 'This application is unavailable.')
        . '</p><a href="/dashboard.php">Return to applications</a></section></div>';
    require dirname(__DIR__) . '/app/views/partials/footer.php';
    exit;
}

$data = $application;
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid($_POST['_csrf_token'] ?? null)) {
        $errors[] = 'This form expired. Please try again.';
    } else {
        $result = JobApplicationController::validate($_POST);
        $data = $result['data'];
        $errors = $result['errors'];

        if ($errors === []) {
            try {
                $updated = JobApplication::updateForUser($pdo, (int) $applicationId, $userId, $data);
                if (!$updated) {
                    http_response_code(404);
                    $errors[] = 'This application is unavailable.';
                } else {
                    set_flash_message('success', 'Application updated.');
                    header('Location: /application-view.php?id=' . (int) $applicationId);
                    exit;
                }
            } catch (Throwable) {
                $errors[] = 'The application could not be updated. Check the details and try again.';
            }
        }
    }
}

$pageTitle = 'Edit application';
$formAction = '/application-edit.php?id=' . (int) $applicationId;
$submitLabel = 'Save changes';
require dirname(__DIR__) . '/app/views/partials/header.php';
?>
<div class="dashboard-shell">
    <div class="page-title-row"><p class="eyebrow">Application details</p><h1>Edit application</h1><p>Update the role information or current status.</p></div>
    <section class="panel form-panel">
        <?php if ($errors !== []): ?><div class="message message-error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= escape_html($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <?php require dirname(__DIR__) . '/app/views/applications/form.php'; ?>
    </section>
</div>
<?php require dirname(__DIR__) . '/app/views/partials/footer.php'; ?>
