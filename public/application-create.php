<?php
declare(strict_types=1);

use App\Controllers\JobApplicationController;
use App\Models\Database;
use App\Models\JobApplication;

require dirname(__DIR__) . '/app/bootstrap.php';
require_authenticated_user();

$data = [
    'company_name' => '', 'job_title' => '', 'posting_url' => null, 'location' => null,
    'employment_type' => null, 'salary_min' => null, 'salary_max' => null,
    'salary_currency' => null, 'applied_on' => null, 'status' => 'saved',
    'contact_person' => null, 'notes' => null, 'follow_up_date' => null,
];
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
                $applicationId = JobApplication::create(
                    Database::connect(),
                    (int) $_SESSION['user_id'],
                    $data
                );
                set_flash_message('success', 'Application added.');
                header('Location: /application-view.php?id=' . $applicationId);
                exit;
            } catch (Throwable) {
                $errors[] = 'The application could not be saved. Check the details and try again.';
            }
        }
    }
}

$pageTitle = 'Add application';
$formAction = '/application-create.php';
$submitLabel = 'Save application';
$flashMessage = take_flash_message();
require dirname(__DIR__) . '/app/views/partials/header.php';
?>
<div class="dashboard-shell">
    <div class="page-title-row"><p class="eyebrow">Application details</p><h1>Add a job application</h1><p>Capture the role and the details you want to remember.</p></div>
    <section class="panel form-panel">
        <?php if ($flashMessage !== null): ?><div class="message message-<?= escape_html($flashMessage['type']) ?>" role="status"><?= escape_html($flashMessage['message']) ?></div><?php endif; ?>
        <?php if ($errors !== []): ?>
            <div class="message message-error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= escape_html($error) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <?php require dirname(__DIR__) . '/app/views/applications/form.php'; ?>
    </section>
</div>
<?php require dirname(__DIR__) . '/app/views/partials/footer.php'; ?>
