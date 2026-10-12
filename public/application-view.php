<?php
declare(strict_types=1);

use App\Models\Database;
use App\Models\JobApplication;

require dirname(__DIR__) . '/app/bootstrap.php';
require_authenticated_user();

$applicationId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$application = null;
$loadError = false;
if ($applicationId !== false) {
    try {
        $application = JobApplication::findForUser(
            Database::connect(),
            (int) $applicationId,
            (int) $_SESSION['user_id']
        );
    } catch (Throwable) {
        $loadError = true;
    }
}

if ($loadError || $application === null) {
    http_response_code($loadError ? 503 : 404);
    $pageTitle = $loadError ? 'Applications unavailable' : 'Application not found';
    require dirname(__DIR__) . '/app/views/partials/header.php';
    ?>
    <section class="dashboard-shell panel form-panel">
        <h1><?= $loadError ? 'Applications unavailable' : 'Application not found' ?></h1>
        <p><?= $loadError ? 'This application could not be loaded right now.' : 'This application is unavailable.' ?></p>
        <a href="/dashboard.php">Return to applications</a>
    </section>
    <?php
    require dirname(__DIR__) . '/app/views/partials/footer.php';
    exit;
}

$statusLabels = [
    'saved' => 'Saved', 'applied' => 'Applied', 'assessment' => 'Assessment',
    'interview' => 'Interview', 'offer' => 'Offer', 'rejected' => 'Rejected', 'withdrawn' => 'Withdrawn',
];
$employmentLabels = [
    'full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract',
    'internship' => 'Internship', 'temporary' => 'Temporary', 'other' => 'Other',
];
$salary = 'Not provided';
if ($application['salary_min'] !== null || $application['salary_max'] !== null) {
    $min = $application['salary_min'] === null ? '—' : number_format((float) $application['salary_min'], 2);
    $max = $application['salary_max'] === null ? '—' : number_format((float) $application['salary_max'], 2);
    $salary = trim((string) ($application['salary_currency'] ?? '') . ' ' . $min . ' – ' . $max);
}
$postingUrl = is_string($application['posting_url'] ?? null) ? $application['posting_url'] : '';
$postingScheme = strtolower((string) parse_url($postingUrl, PHP_URL_SCHEME));
$hasSafePostingUrl = $postingUrl !== ''
    && filter_var($postingUrl, FILTER_VALIDATE_URL) !== false
    && in_array($postingScheme, ['http', 'https'], true);
$flashMessage = take_flash_message();
$pageTitle = 'Application details';
require dirname(__DIR__) . '/app/views/partials/header.php';
?>
<div class="dashboard-shell">
    <?php if ($flashMessage !== null): ?>
        <div class="message message-<?= escape_html($flashMessage['type']) ?> dashboard-alert" role="status"><?= escape_html($flashMessage['message']) ?></div>
    <?php endif; ?>
    <div class="page-title-row">
        <p class="eyebrow">Application details</p>
        <h1><?= escape_html((string) $application['job_title']) ?></h1>
        <p><?= escape_html((string) $application['company_name']) ?></p>
    </div>
    <section class="panel form-panel" aria-labelledby="details-heading">
        <div class="panel-head"><h2 id="details-heading">Role information</h2><span class="status-badge status-<?= escape_html((string) $application['status']) ?>"><?= escape_html($statusLabels[$application['status']] ?? 'Unknown') ?></span></div>
        <dl class="detail-grid">
            <div class="detail-item"><dt>Company</dt><dd><?= escape_html((string) $application['company_name']) ?></dd></div>
            <div class="detail-item"><dt>Job title</dt><dd><?= escape_html((string) $application['job_title']) ?></dd></div>
            <div class="detail-item"><dt>Location</dt><dd><?= escape_html((string) ($application['location'] ?? 'Not provided')) ?></dd></div>
            <div class="detail-item"><dt>Employment type</dt><dd><?= escape_html($employmentLabels[$application['employment_type']] ?? 'Not provided') ?></dd></div>
            <div class="detail-item"><dt>Salary range</dt><dd><?= escape_html($salary) ?></dd></div>
            <div class="detail-item"><dt>Date applied</dt><dd><?= escape_html((string) ($application['applied_on'] ?? 'Not provided')) ?></dd></div>
            <div class="detail-item"><dt>Follow-up date</dt><dd><?= escape_html((string) ($application['follow_up_date'] ?? 'Not provided')) ?></dd></div>
            <div class="detail-item"><dt>Contact person</dt><dd><?= escape_html((string) ($application['contact_person'] ?? 'Not provided')) ?></dd></div>
            <div class="detail-item detail-wide"><dt>Job posting</dt><dd><?php if ($hasSafePostingUrl): ?><a href="<?= escape_html($postingUrl) ?>" target="_blank" rel="noopener noreferrer">Open job posting</a><?php elseif ($postingUrl !== ''): ?>Unavailable<?php else: ?>Not provided<?php endif; ?></dd></div>
            <div class="detail-item detail-wide"><dt>Notes</dt><dd class="detail-notes"><?= escape_html((string) ($application['notes'] ?? 'No notes')) ?></dd></div>
        </dl>
        <div class="form-actions">
            <a class="button" href="/application-edit.php?id=<?= (int) $application['id'] ?>">Edit application</a>
            <a class="button button-secondary" href="/dashboard.php">Back to applications</a>
            <a class="button button-secondary" href="/application-delete.php?id=<?= (int) $application['id'] ?>">Delete</a>
        </div>
    </section>
</div>
<?php require dirname(__DIR__) . '/app/views/partials/footer.php'; ?>
