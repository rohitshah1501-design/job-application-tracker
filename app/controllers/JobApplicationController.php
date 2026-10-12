<?php
declare(strict_types=1);

namespace App\Controllers;

final class JobApplicationController
{
    public const STATUSES = [
        'saved',
        'applied',
        'assessment',
        'interview',
        'offer',
        'rejected',
        'withdrawn',
    ];

    public const EMPLOYMENT_TYPES = [
        'full_time',
        'part_time',
        'contract',
        'internship',
        'temporary',
        'other',
    ];

    /** @return array{errors: list<string>, data: array<string, mixed>} */
    public static function validate(array $input): array
    {
        $errors = [];
        $company = self::inputString($input, 'company_name');
        $title = self::inputString($input, 'job_title');
        $url = self::inputString($input, 'posting_url');
        $location = self::inputString($input, 'location');
        $employmentType = self::inputString($input, 'employment_type');
        $salaryMin = self::inputString($input, 'salary_min');
        $salaryMax = self::inputString($input, 'salary_max');
        $currency = strtoupper(self::inputString($input, 'salary_currency'));
        $appliedOn = self::inputString($input, 'applied_on');
        $status = self::inputString($input, 'status');
        $contact = self::inputString($input, 'contact_person');
        $notes = self::inputString($input, 'notes');
        $followUp = self::inputString($input, 'follow_up_date');

        self::validateText($company, 'Company name', 180, true, $errors);
        self::validateText($title, 'Job title', 180, true, $errors);
        self::validateText($location, 'Location', 180, false, $errors);
        self::validateText($contact, 'Contact person', 180, false, $errors);

        if ($url !== '') {
            $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
            if (strlen($url) > 2048 || filter_var($url, FILTER_VALIDATE_URL) === false
                || !in_array($scheme, ['http', 'https'], true)) {
                $errors[] = 'Enter a valid job URL beginning with http:// or https://.';
            }
        }

        if ($employmentType !== '' && !in_array($employmentType, self::EMPLOYMENT_TYPES, true)) {
            $errors[] = 'Choose a valid employment type.';
        }

        if (!in_array($status, self::STATUSES, true)) {
            $errors[] = 'Choose a valid application status.';
        }

        $salaryPattern = '/^\d{1,10}(?:\.\d{1,2})?$/';
        foreach (['minimum' => $salaryMin, 'maximum' => $salaryMax] as $label => $amount) {
            if ($amount !== '' && preg_match($salaryPattern, $amount) !== 1) {
                $errors[] = 'Salary ' . $label . ' must be a non-negative amount with up to 2 decimal places.';
            }
        }

        if ($salaryMin !== '' && $salaryMax !== '' && (float) $salaryMin > (float) $salaryMax) {
            $errors[] = 'Salary minimum cannot be greater than salary maximum.';
        }

        $hasSalary = $salaryMin !== '' || $salaryMax !== '';
        if ($hasSalary && preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            $errors[] = 'Enter a 3-letter currency code when providing salary.';
        } elseif (!$hasSalary && $currency !== '' && preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            $errors[] = 'Currency must be a 3-letter code.';
        } elseif (!$hasSalary && $currency !== '') {
            $errors[] = 'Add a salary amount before selecting a currency.';
        }

        $appliedOnValue = self::validateDate($appliedOn, 'Date applied', $errors);
        $followUpValue = self::validateDate($followUp, 'Follow-up date', $errors);
        if ($appliedOnValue !== null && $followUpValue !== null && $followUpValue < $appliedOnValue) {
            $errors[] = 'Follow-up date cannot be earlier than the application date.';
        }

        if (strlen($notes) > 60000) {
            $errors[] = 'Notes must be 60,000 bytes or fewer.';
        }
        if (preg_match('//u', $notes) !== 1) {
            $errors[] = 'Notes must contain valid text.';
        }

        return [
            'errors' => $errors,
            'data' => [
                'company_name' => $company,
                'job_title' => $title,
                'posting_url' => $url === '' ? null : $url,
                'location' => $location === '' ? null : $location,
                'employment_type' => $employmentType === '' ? null : $employmentType,
                'salary_min' => $salaryMin === '' ? null : $salaryMin,
                'salary_max' => $salaryMax === '' ? null : $salaryMax,
                'salary_currency' => $currency === '' ? null : $currency,
                'applied_on' => $appliedOnValue,
                'status' => $status,
                'contact_person' => $contact === '' ? null : $contact,
                'notes' => $notes === '' ? null : $notes,
                'follow_up_date' => $followUpValue,
            ],
        ];
    }

    private static function inputString(array $input, string $key): string
    {
        return is_string($input[$key] ?? null) ? trim($input[$key]) : '';
    }

    /** @param list<string> $errors */
    private static function validateText(
        string $value,
        string $label,
        int $maxCharacters,
        bool $required,
        array &$errors
    ): void {
        $length = preg_match_all('/./us', $value);
        if (($required && $value === '') || $length === false || $length > $maxCharacters) {
            $errors[] = $required
                ? $label . ' is required and must contain no more than ' . $maxCharacters . ' characters.'
                : $label . ' must contain no more than ' . $maxCharacters . ' characters.';
        }
    }

    /** @param list<string> $errors */
    private static function validateDate(string $value, string $label, array &$errors): ?string
    {
        if ($value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $dateErrors = \DateTimeImmutable::getLastErrors();
        if ($date === false || ($dateErrors !== false
                && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
            || $date->format('Y-m-d') !== $value) {
            $errors[] = $label . ' must be a valid date.';
            return $value;
        }

        return $date->format('Y-m-d');
    }
}

