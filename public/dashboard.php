<?php
declare(strict_types=1);

use App\Controllers\JobApplicationController;
use App\Models\Database;
use App\Models\JobApplication;

require dirname(__DIR__) . '/app/bootstrap.php';
require_authenticated_user();

$userId = (int) $_SESSION['user_id'];
$search = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$status = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
$filterErrors = [];
$searchLength = preg_match_all('/./us', $search);
if ($searchLength === false || $searchLength > 120) {
    $filterErrors[] = 'Search must be 120 characters or fewer.';
    $search = '';
}
if ($status !== '' && !in_array($status, JobApplicationController::STATUSES, true)) {
    $filterErrors[] = 'Choose a valid status filter.';
    $status = '';
}

$stats = ['total' => 0, 'interview' => 0, 'offer' => 0, 'rejected' => 0];
$applications = [];
$followUps = [];
$loadError = false;
try {
    $pdo = Database::connect();
    $stats = JobApplication::statistics($pdo, $userId);
    $applications = JobApplication::search($pdo, $userId, $search, $status);
    $followUps = JobApplication::followUps($pdo, $userId);
} catch (Throwable) {
    $loadError = true;
}

$today = date('Y-m-d');
$followUpGroups = ['overdue' => [], 'today' => [], 'upcoming' => []];
foreach ($followUps as $followUp) {
    $date = (string) $followUp['follow_up_date'];
    $group = $date < $today ? 'overdue' : ($date === $today ? 'today' : 'upcoming');
    $followUpGroups[$group][] = $followUp;
}

$flashMessage = take_flash_message();
$statusLabels = [
    'saved' => 'Saved',
    'applied' => 'Applied',
    'assessment' => 'Assessment',
    'interview' => 'Interview',
    'offer' => 'Offer',
    'rejected' => 'Rejected',
    'withdrawn' => 'Withdrawn',
];
$pageTitle = 'Dashboard';
require dirname(__DIR__) . '/app/views/partials/header.php';
?>
<div class="dashboard-shell">
    <div class="dashboard-heading">
        <div>
            <p class="eyebrow">Your job search</p>
            <h1>Applications</h1>
            <p>Keep your opportunities and next steps in one clear view.</p>
        </div>
        <a class="button" href="/application-create.php">Add application</a>
    </div>

    <?php if ($flashMessage !== null): ?>
        <div class="message message-<?= escape_html($flashMessage['type']) ?> dashboard-alert" role="status">
            <?= escape_html($flashMessage['message']) ?>
        </div>
    <?php endif; ?>
    <?php if ($filterErrors !== []): ?>
        <div class="message message-error dashboard-alert" role="alert">
            <ul><?php foreach ($filterErrors as $error): ?><li><?= escape_html($error) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <?php if ($loadError): ?>
        <div class="message message-error dashboard-alert" role="alert">Applications could not be loaded right now. Please try again.</div>
    <?php else: ?>
        <section class="stats-grid" aria-label="Application statistics">
            <article class="stat-card stat-accent"><span>Total applications</span><strong><?= $stats['total'] ?></strong></article>
            <article class="stat-card"><span>In interview</span><strong><?= $stats['interview'] ?></strong></article>
            <article class="stat-card stat-amber"><span>Offers</span><strong><?= $stats['offer'] ?></strong></article>
            <article class="stat-card"><span>Rejections</span><strong><?= $stats['rejected'] ?></strong></article>
        </section>

        <section class="follow-up-panel panel" aria-labelledby="follow-up-heading">
            <div class="panel-head"><h2 id="follow-up-heading">Follow-ups</h2></div>
            <div class="follow-up-groups">
                <?php foreach (['overdue' => 'Overdue', 'today' => 'Due today', 'upcoming' => 'Upcoming'] as $groupKey => $groupLabel): ?>
                    <section class="follow-up-group">
                        <h3><?= escape_html($groupLabel) ?><span><?= count($followUpGroups[$groupKey]) ?></span></h3>
                        <?php if ($followUpGroups[$groupKey] === []): ?>
                            <p class="follow-up-empty">Nothing scheduled.</p>
                        <?php else: ?>
                            <ul>
                                <?php foreach ($followUpGroups[$groupKey] as $followUp): ?>
                                    <li>
                                        <a href="/application-view.php?id=<?= (int) $followUp['id'] ?>">
                                            <strong><?= escape_html((string) $followUp['company_name']) ?></strong>
                                            <span><?= escape_html((string) $followUp['job_title']) ?></span>
                                        </a>
                                        <time datetime="<?= escape_html((string) $followUp['follow_up_date']) ?>"><?= escape_html((string) $followUp['follow_up_date']) ?></time>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="panel" aria-labelledby="application-list-heading">
            <div class="panel-head">
                <h2 id="application-list-heading">Your applications</h2>
                <span class="muted-count"><?= count($applications) ?> shown</span>
            </div>
            <form class="filter-form" method="get" action="/dashboard.php">
                <div class="filter-control">
                    <label for="q">Search company or job title</label>
                    <input id="q" name="q" type="search" maxlength="120" value="<?= escape_html($search) ?>" placeholder="e.g. Northstar or Designer">
                </div>
                <div class="filter-control">
                    <label for="status-filter">Status</label>
                    <select id="status-filter" name="status">
                        <option value="">All statuses</option>
                        <?php foreach ($statusLabels as $statusValue => $label): ?>
                            <option value="<?= escape_html($statusValue) ?>" <?= $status === $statusValue ? 'selected' : '' ?>><?= escape_html($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="button" type="submit">Apply filters</button>
                <?php if ($search !== '' || $status !== ''): ?><a class="filter-reset" href="/dashboard.php">Clear</a><?php endif; ?>
            </form>

            <?php if ($applications === []): ?>
                <div class="empty-state">
                    <?php if ($stats['total'] === 0 && $search === '' && $status === ''): ?>
                        <h3>Your tracker is ready for its first application.</h3>
                        <p>Add a role to keep its status, dates, and notes together.</p>
                        <a class="button" href="/application-create.php">Add your first application</a>
                    <?php else: ?>
                        <h3>No applications match these filters.</h3>
                        <p>Try another company, title, or status.</p>
                        <a href="/dashboard.php">Clear filters</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="applications-table">
                        <thead>
                            <tr><th>Company / role</th><th>Location</th><th>Date applied</th><th>Status</th><th>Follow-up</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($applications as $application): ?>
                            <?php $applicationId = (int) $application['id']; ?>
                            <tr>
                                <td class="company-cell">
                                    <strong><?= escape_html((string) $application['company_name']) ?></strong>
                                    <span><?= escape_html((string) $application['job_title']) ?></span>
                                </td>
                                <td><?= escape_html((string) ($application['location'] ?? '—')) ?></td>
                                <td><?= escape_html((string) ($application['applied_on'] ?? '—')) ?></td>
                                <td><span class="status-badge status-<?= escape_html((string) $application['status']) ?>"><?= escape_html($statusLabels[$application['status']] ?? 'Unknown') ?></span></td>
                                <td><?= escape_html((string) ($application['follow_up_date'] ?? '—')) ?></td>
                                <td>
                                    <div class="row-actions">
                                        <a href="/application-view.php?id=<?= $applicationId ?>">View</a>
                                        <a href="/application-edit.php?id=<?= $applicationId ?>">Edit</a>
                                        <a class="danger-link" href="/application-delete.php?id=<?= $applicationId ?>">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
<?php require dirname(__DIR__) . '/app/views/partials/footer.php'; ?>
