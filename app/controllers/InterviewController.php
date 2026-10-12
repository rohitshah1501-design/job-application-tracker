<?php
declare(strict_types=1);

namespace App\Controllers;

final class InterviewController
{
    public const TYPES = ['in_person', 'phone', 'video', 'other'];

    /** @return array{errors: list<string>, data: array{application_id: int|null, interview_at: string, interview_type: string, notes: ?string}} */
    public static function validate(array $input): array
    {
        $errors = [];
        $rawApplicationId = is_string($input['application_id'] ?? null)
            ? $input['application_id']
            : '';
        $applicationId = filter_var(
            $rawApplicationId,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if ($applicationId === false) {
            $errors[] = 'Choose a job application for this interview.';
            $applicationId = null;
        }

        $dateTime = is_string($input['interview_at'] ?? null)
            ? trim($input['interview_at'])
            : '';
        $interviewAt = self::validateDateTime($dateTime, $errors);

        $type = is_string($input['interview_type'] ?? null)
            ? $input['interview_type']
            : '';
        if (!in_array($type, self::TYPES, true)) {
            $errors[] = 'Choose a valid interview type.';
        }

        $notes = is_string($input['notes'] ?? null) ? trim($input['notes']) : '';
        if (strlen($notes) > 60000 || preg_match('//u', $notes) !== 1) {
            $errors[] = 'Interview notes must be valid text of 60,000 bytes or fewer.';
        }

        return [
            'errors' => $errors,
            'data' => [
                'application_id' => $applicationId === false ? null : $applicationId,
                'interview_at' => $interviewAt,
                'interview_type' => $type,
                'notes' => $notes === '' ? null : $notes,
            ],
        ];
    }

    /** @param list<string> $errors */
    private static function validateDateTime(string $value, array &$errors): string
    {
        $dateTime = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);
        $dateErrors = \DateTimeImmutable::getLastErrors();
        if ($dateTime === false || ($dateErrors !== false
                && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
            || $dateTime->format('Y-m-d\TH:i') !== $value) {
            $errors[] = 'Enter a valid interview date and time.';
            return '';
        }

        return $dateTime->format('Y-m-d H:i:s');
    }
}

