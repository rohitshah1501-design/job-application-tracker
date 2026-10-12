<?php
declare(strict_types=1);

use App\Models\Database;
use App\Models\Interview;
use App\Models\JobApplication;

require dirname(__DIR__) . '/app/bootstrap.php';
require_authenticated_user();

$interviews = [];
$hasApplications = false;
$loadError = false;
try {
    $pdo = Database::connect();
    $userId = (int) $_SESSION['user_id'];
    $interviews = Interview::listForUser($pdo, $userId);
    $hasApplications = JobApplication::optionsForUser($pdo, $userId) !== [];
} catch (Throwable) {
    $loadError = true;
}

$now = date('Y-m-d H:i:s');
$upcoming = [];
$past = [];
foreach ($interviews as $interview) {
    if ((string) $interview['interview_at'] >= $now) {
        $upcoming[] = $interview;
    } else {
        $past[] = $interview;
    }
}
$past = array_reverse($past);
$typeLabels = ['in_person' => 'In person', 'phone' => 'Phone', 'video' => 'Video', 'other' => 'Other'];
$flashMessage = take_flash_message();
$pageTitle = 'Interviews';
require dirname(__DIR__) . '/app/views/partials/header.php';
?>
<div class="dashboard-shell">
    <div class="dashboard-heading">
        <div>
            <p class="eyebrow">Conversations and next steps</p>
            <h1>Interviews</h1>
            <p>Keep scheduled conversations tied to the right application.</p>
        </div>
        <a class="button" href="/interview-create.php">Schedule interview</a>
    </div>

    <?php if ($flashMessage !== null): ?>
        <div class="message message-<?= escape_html($flashMessage['type']) ?> dashboard-alert" role="status"><?= escape_html($flashMessage['message']) ?></div>
    <?php endif; ?>
    <?php if ($loadError): ?>
        <div class="message message-error" role="alert">Interview information could not be loaded right now. Please try again.</div>
    <?php else: ?>
        <?php foreach ([['Upcoming interviews', $upcoming], ['Past interviews', $past]] as [$heading, $items]): ?>
            <section class="panel interviews-section" aria-label="<?= escape_html($heading) ?>">
                <div class="panel-head"><h2><?= escape_html($heading) ?></h2><span><?= count($items) ?> scheduled</span></div>
                <?php if ($items === []): ?>
                    <div class="empty-state">
                        <h3><?= $heading === 'Upcoming interviews' ? 'No upcoming interviews.' : 'No past interviews yet.' ?></h3>
                        <?php if ($heading === 'Upcoming interviews' && !$hasApplications): ?>
                            <p>Add a job application before scheduling an interview.</p>
                            <a class="button" href="/application-create.php">Add an application</a>
                        <?php elseif ($heading === 'Upcoming interviews' && $hasApplications): ?>
                            <p>Schedule a conversation to keep its time and notes together.</p>
                            <a class="button" href="/interview-create.php">Schedule interview</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="interview-list">
                        <?php foreach ($items as $interview): ?>
                            <article class="interview-row">
                                <a class="interview-role" href="/interview-view.php?id=<?= (int) $interview['id'] ?>">
                                    <strong><?= escape_html((string) $interview['company_name']) ?></strong>
                                    <span><?= escape_html((string) $interview['job_title']) ?></span>
                                </a>
                                <div class="interview-time">
                                    <strong><?= escape_html(date('D, M j, Y', strtotime((string) $interview['interview_at']))) ?></strong>
                                    <span><?= escape_html(date('g:i A', strtotime((string) $interview['interview_at']))) ?> · <?= escape_html($typeLabels[$interview['interview_type']] ?? 'Other') ?></span>
                                </div>
                                <div class="interview-actions">
                                    <a href="/interview-edit.php?id=<?= (int) $interview['id'] ?>">Edit</a>
                                    <a class="danger-link" href="/interview-delete.php?id=<?= (int) $interview['id'] ?>">Delete</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php require dirname(__DIR__) . '/app/views/partials/footer.php'; ?>
