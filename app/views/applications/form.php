<?php
declare(strict_types=1);

$value = static fn (string $key): string => escape_html((string) ($data[$key] ?? ''));
$statusLabels = [
    'saved' => 'Saved',
    'applied' => 'Applied',
    'assessment' => 'Assessment',
    'interview' => 'Interview',
    'offer' => 'Offer',
    'rejected' => 'Rejected',
    'withdrawn' => 'Withdrawn',
];
$employmentLabels = [
    'full_time' => 'Full-time',
    'part_time' => 'Part-time',
    'contract' => 'Contract',
    'internship' => 'Internship',
    'temporary' => 'Temporary',
    'other' => 'Other',
];
?>
<form method="post" action="<?= escape_html($formAction) ?>" class="application-form">
    <input type="hidden" name="_csrf_token" value="<?= escape_html(csrf_token()) ?>">
    <div class="form-grid">
        <div class="field">
            <label for="company_name">Company name <span aria-hidden="true">*</span></label>
            <input id="company_name" name="company_name" type="text" maxlength="180" required value="<?= $value('company_name') ?>">
        </div>
        <div class="field">
            <label for="job_title">Job title <span aria-hidden="true">*</span></label>
            <input id="job_title" name="job_title" type="text" maxlength="180" required value="<?= $value('job_title') ?>">
        </div>
        <div class="field field-wide">
            <label for="posting_url">Job posting URL</label>
            <input id="posting_url" name="posting_url" type="url" maxlength="2048" inputmode="url" placeholder="https://example.com/job" value="<?= $value('posting_url') ?>">
        </div>
        <div class="field">
            <label for="location">Location</label>
            <input id="location" name="location" type="text" maxlength="180" value="<?= $value('location') ?>">
        </div>
        <div class="field">
            <label for="employment_type">Employment type</label>
            <select id="employment_type" name="employment_type">
                <option value="">Choose a type</option>
                <?php foreach ($employmentLabels as $type => $label): ?>
                    <option value="<?= escape_html($type) ?>" <?= ($data['employment_type'] ?? '') === $type ? 'selected' : '' ?>><?= escape_html($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="salary_min">Salary minimum</label>
            <input id="salary_min" name="salary_min" type="number" min="0" max="9999999999.99" step="0.01" inputmode="decimal" value="<?= $value('salary_min') ?>">
        </div>
        <div class="field">
            <label for="salary_max">Salary maximum</label>
            <input id="salary_max" name="salary_max" type="number" min="0" max="9999999999.99" step="0.01" inputmode="decimal" value="<?= $value('salary_max') ?>">
        </div>
        <div class="field">
            <label for="salary_currency">Salary currency</label>
            <input id="salary_currency" name="salary_currency" type="text" maxlength="3" pattern="[A-Za-z]{3}" placeholder="USD" value="<?= $value('salary_currency') ?>">
            <span class="field-help">Use a 3-letter code, such as USD or INR.</span>
        </div>
        <div class="field">
            <label for="applied_on">Date applied</label>
            <input id="applied_on" name="applied_on" type="date" value="<?= $value('applied_on') ?>">
        </div>
        <div class="field">
            <label for="status">Status <span aria-hidden="true">*</span></label>
            <select id="status" name="status" required>
                <?php foreach ($statusLabels as $status => $label): ?>
                    <option value="<?= escape_html($status) ?>" <?= ($data['status'] ?? 'saved') === $status ? 'selected' : '' ?>><?= escape_html($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="contact_person">Contact person</label>
            <input id="contact_person" name="contact_person" type="text" maxlength="180" value="<?= $value('contact_person') ?>">
        </div>
        <div class="field">
            <label for="follow_up_date">Follow-up date</label>
            <input id="follow_up_date" name="follow_up_date" type="date" value="<?= $value('follow_up_date') ?>">
        </div>
        <div class="field field-wide">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="5" maxlength="60000"><?= $value('notes') ?></textarea>
        </div>
    </div>
    <p class="required-note"><span aria-hidden="true">*</span> Required fields</p>
    <div class="form-actions">
        <button class="button" type="submit"><?= escape_html($submitLabel) ?></button>
        <a class="button button-secondary" href="/dashboard.php">Cancel</a>
    </div>
</form>
