<?php
declare(strict_types=1);

use App\Models\Database;
use App\Models\Interview;

require dirname(__DIR__) . '/app/bootstrap.php';
require_authenticated_user();

$interviewId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$interview = null;
$loadError = false;
if ($interviewId !== false) {
    try {
        $interview = Interview::findForUser(
            Database::connect(),
            (int) $interviewId,
            (int) $_SESSION['user_id']
        );
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

$typeLabels = ['in_person' => 'In person', 'phone' => 'Phone', 'video' => 'Video', 'other' => 'Other'];
$flashMessage = take_flash_message();
$pageTitle = 'Interview details';
require dirname(__DIR__) . '/app/views/partials/header.php';
?>
<div class="dashboard-shell">
    <?php if ($flashMessage !== null): ?>
        <div class="message message-<?= escape_html($flashMessage['type']) ?> dashboard-alert" role="status"><?= escape_html($flashMessage['message']) ?></div>
    <?php endif; ?>
    <div class="page-title-row">
        <p class="eyebrow">Interview details</p>
        <h1><?= escape_html((string) $interview['company_name']) ?></h1>
        <p><?= escape_html((string) $interview['job_title']) ?></p>
    </div>
    <section class="panel form-panel" aria-labelledby="interview-info-heading">
        <div class="panel-head"><h2 id="interview-info-heading">Meeting information</h2></div>
        <dl class="detail-grid">
            <div class="detail-item"><dt>Date</dt><dd><?= escape_html(date('l, F j, Y', strtotime((string) $interview['interview_at']))) ?></dd></div>
            <div class="detail-item"><dt>Time</dt><dd><?= escape_html(date('g:i A', strtotime((string) $interview['interview_at']))) ?></dd></div>
            <div class="detail-item"><dt>Type</dt><dd><?= escape_html($typeLabels[$interview['interview_type']] ?? 'Other') ?></dd></div>
            <div class="detail-item"><dt>Application</dt><dd><a href="/application-view.php?id=<?= (int) $interview['application_id'] ?>"><?= escape_html((string) $interview['company_name']) ?> · <?= escape_html((string) $interview['job_title']) ?></a></dd></div>
            <div class="detail-item detail-wide"><dt>Notes</dt><dd class="detail-notes"><?= escape_html((string) ($interview['notes'] ?? 'No notes')) ?></dd></div>
        </dl>
        <div class="form-actions">
            <a class="button" href="/interview-edit.php?id=<?= (int) $interview['id'] ?>">Edit interview</a>
            <a class="button button-secondary" href="/interviews.php">Back to interviews</a>
            <a class="button button-secondary" href="/interview-delete.php?id=<?= (int) $interview['id'] ?>">Delete</a>
        </div>
    </section>
</div>
<?php require dirname(__DIR__) . '/app/views/partials/footer.php'; ?>
