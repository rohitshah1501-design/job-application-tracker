<?php
declare(strict_types=1);

$applicationValue = (string) ($data['application_id'] ?? '');
$dateTimeValue = (string) ($data['interview_at'] ?? '');
if (strlen($dateTimeValue) >= 16 && $dateTimeValue[10] === ' ') {
    $dateTimeValue = substr($dateTimeValue, 0, 16);
    $dateTimeValue[10] = 'T';
}
$typeLabels = [
    'in_person' => 'In person',
    'phone' => 'Phone',
    'video' => 'Video',
    'other' => 'Other',
];
?>
<form method="post" action="<?= escape_html($formAction) ?>" class="application-form">
    <input type="hidden" name="_csrf_token" value="<?= escape_html(csrf_token()) ?>">
    <div class="form-grid">
        <div class="field field-wide">
            <label for="application_id">Job application <span aria-hidden="true">*</span></label>
            <select id="application_id" name="application_id" required>
                <option value="">Choose an application</option>
                <?php foreach ($applicationOptions as $option): ?>
                    <option value="<?= (int) $option['id'] ?>" <?= $applicationValue === (string) $option['id'] ? 'selected' : '' ?>>
                        <?= escape_html($option['company_name'] . ' — ' . $option['job_title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="interview_at">Date and time <span aria-hidden="true">*</span></label>
            <input id="interview_at" name="interview_at" type="datetime-local" required value="<?= escape_html($dateTimeValue) ?>">
        </div>
        <div class="field">
            <label for="interview_type">Interview type <span aria-hidden="true">*</span></label>
            <select id="interview_type" name="interview_type" required>
                <?php foreach ($typeLabels as $type => $label): ?>
                    <option value="<?= escape_html($type) ?>" <?= ($data['interview_type'] ?? 'other') === $type ? 'selected' : '' ?>><?= escape_html($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field field-wide">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="5" maxlength="60000"><?= escape_html((string) ($data['notes'] ?? '')) ?></textarea>
        </div>
    </div>
    <p class="required-note"><span aria-hidden="true">*</span> Required fields</p>
    <div class="form-actions">
        <button class="button" type="submit"><?= escape_html($submitLabel) ?></button>
        <a class="button button-secondary" href="/interviews.php">Cancel</a>
    </div>
</form>
