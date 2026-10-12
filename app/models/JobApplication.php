<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use Throwable;

final class JobApplication
{
    /** @return array{total: int, interview: int, offer: int, rejected: int} */
    public static function statistics(PDO $pdo, int $userId): array
    {
        $statement = $pdo->prepare(
            "SELECT COUNT(*) AS total,
                COUNT(CASE WHEN status = 'interview' THEN 1 END) AS interview,
                COUNT(CASE WHEN status = 'offer' THEN 1 END) AS offer,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) AS rejected
             FROM job_applications
             WHERE user_id = :user_id"
        );
        $statement->execute(['user_id' => $userId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'interview' => (int) ($row['interview'] ?? 0),
            'offer' => (int) ($row['offer'] ?? 0),
            'rejected' => (int) ($row['rejected'] ?? 0),
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function search(PDO $pdo, int $userId, string $query, string $status): array
    {
        $sql = 'SELECT id, company_name, job_title, location, employment_type,
                    salary_min, salary_max, salary_currency, applied_on, status,
                    follow_up_date, updated_at
                FROM job_applications
                WHERE user_id = :user_id';
        $parameters = ['user_id' => $userId];

        if ($query !== '') {
            $sql .= ' AND (company_name LIKE :company_search OR job_title LIKE :title_search)';
            $parameters['company_search'] = '%' . $query . '%';
            $parameters['title_search'] = '%' . $query . '%';
        }
        if ($status !== '') {
            $sql .= ' AND status = :status';
            $parameters['status'] = $status;
        }

        $sql .= ' ORDER BY applied_on DESC, id DESC';
        $statement = $pdo->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array<string, mixed>> */
    public static function followUps(PDO $pdo, int $userId): array
    {
        $statement = $pdo->prepare(
            'SELECT id, company_name, job_title, status, follow_up_date
             FROM job_applications
             WHERE user_id = :user_id AND follow_up_date IS NOT NULL
             ORDER BY follow_up_date ASC, id ASC'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array{id: int, company_name: string, job_title: string}> */
    public static function optionsForUser(PDO $pdo, int $userId): array
    {
        $statement = $pdo->prepare(
            'SELECT id, company_name, job_title
             FROM job_applications
             WHERE user_id = :user_id
             ORDER BY company_name ASC, job_title ASC'
        );
        $statement->execute(['user_id' => $userId]);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'company_name' => (string) $row['company_name'],
                'job_title' => (string) $row['job_title'],
            ],
            $rows
        );
    }

    /** Fetch an application only when it belongs to the supplied session user. */
    public static function findForUser(PDO $pdo, int $applicationId, int $userId): ?array
    {
        $statement = $pdo->prepare(
            'SELECT id, company_name, job_title, posting_url, location, employment_type,
                    salary_min, salary_max, salary_currency, applied_on, status,
                    contact_person, notes, follow_up_date, created_at, updated_at
             FROM job_applications
             WHERE id = :id AND user_id = :user_id
             LIMIT 1'
        );
        $statement->execute(['id' => $applicationId, 'user_id' => $userId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /** Insert an application and its initial status event atomically. */
    public static function create(PDO $pdo, int $userId, array $data): int
    {
        $pdo->beginTransaction();

        try {
            $statement = $pdo->prepare(
                'INSERT INTO job_applications (
                    user_id, company_name, job_title, posting_url, location, employment_type,
                    salary_min, salary_max, salary_currency, applied_on, status,
                    contact_person, notes, follow_up_date
                 ) VALUES (
                    :user_id, :company_name, :job_title, :posting_url, :location, :employment_type,
                    :salary_min, :salary_max, :salary_currency, :applied_on, :status,
                    :contact_person, :notes, :follow_up_date
                 )'
            );
            $statement->execute(self::writeParameters($userId, $data));
            $applicationId = (int) $pdo->lastInsertId();

            $history = $pdo->prepare(
                'INSERT INTO application_status_history (user_id, application_id, status)
                 VALUES (:user_id, :application_id, :status)'
            );
            $history->execute([
                'user_id' => $userId,
                'application_id' => $applicationId,
                'status' => $data['status'],
            ]);

            $pdo->commit();
            return $applicationId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    /** Update only an owned application; record status changes in the same transaction. */
    public static function updateForUser(
        PDO $pdo,
        int $applicationId,
        int $userId,
        array $data
    ): bool {
        $pdo->beginTransaction();

        try {
            $current = $pdo->prepare(
                'SELECT status FROM job_applications
                 WHERE id = :id AND user_id = :user_id
                 FOR UPDATE'
            );
            $current->execute(['id' => $applicationId, 'user_id' => $userId]);
            $currentStatus = $current->fetchColumn();

            if ($currentStatus === false) {
                $pdo->rollBack();
                return false;
            }

            $parameters = self::writeParameters($userId, $data);
            $parameters['id'] = $applicationId;
            $statement = $pdo->prepare(
                'UPDATE job_applications SET
                    company_name = :company_name,
                    job_title = :job_title,
                    posting_url = :posting_url,
                    location = :location,
                    employment_type = :employment_type,
                    salary_min = :salary_min,
                    salary_max = :salary_max,
                    salary_currency = :salary_currency,
                    applied_on = :applied_on,
                    status = :status,
                    contact_person = :contact_person,
                    notes = :notes,
                    follow_up_date = :follow_up_date
                 WHERE id = :id AND user_id = :user_id'
            );
            $statement->execute($parameters);

            if ($currentStatus !== $data['status']) {
                $history = $pdo->prepare(
                    'INSERT INTO application_status_history (user_id, application_id, status)
                     VALUES (:user_id, :application_id, :status)'
                );
                $history->execute([
                    'user_id' => $userId,
                    'application_id' => $applicationId,
                    'status' => $data['status'],
                ]);
            }

            $pdo->commit();
            return true;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    /** Delete only an owned application. Related history/interviews cascade in MariaDB. */
    public static function deleteForUser(PDO $pdo, int $applicationId, int $userId): bool
    {
        $statement = $pdo->prepare(
            'DELETE FROM job_applications WHERE id = :id AND user_id = :user_id'
        );
        $statement->execute(['id' => $applicationId, 'user_id' => $userId]);

        return $statement->rowCount() === 1;
    }

    /** @return array<string, mixed> */
    private static function writeParameters(int $userId, array $data): array
    {
        return [
            'user_id' => $userId,
            'company_name' => $data['company_name'],
            'job_title' => $data['job_title'],
            'posting_url' => $data['posting_url'],
            'location' => $data['location'],
            'employment_type' => $data['employment_type'],
            'salary_min' => $data['salary_min'],
            'salary_max' => $data['salary_max'],
            'salary_currency' => $data['salary_currency'],
            'applied_on' => $data['applied_on'],
            'status' => $data['status'],
            'contact_person' => $data['contact_person'],
            'notes' => $data['notes'],
            'follow_up_date' => $data['follow_up_date'],
        ];
    }
}

