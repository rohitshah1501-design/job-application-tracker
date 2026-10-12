<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use Throwable;

final class Interview
{
    /** @return list<array<string, mixed>> */
    public static function listForUser(PDO $pdo, int $userId): array
    {
        $statement = $pdo->prepare(
            'SELECT i.id, i.application_id, i.interview_at, i.interview_type, i.notes,
                    a.company_name, a.job_title
             FROM interviews AS i
             INNER JOIN job_applications AS a
                ON a.id = i.application_id AND a.user_id = i.user_id
             WHERE i.user_id = :user_id
             ORDER BY i.interview_at ASC, i.id ASC'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findForUser(PDO $pdo, int $interviewId, int $userId): ?array
    {
        $statement = $pdo->prepare(
            'SELECT i.id, i.application_id, i.interview_at, i.interview_type, i.notes,
                    i.created_at, i.updated_at, a.company_name, a.job_title
             FROM interviews AS i
             INNER JOIN job_applications AS a
                ON a.id = i.application_id AND a.user_id = i.user_id
             WHERE i.id = :interview_id AND i.user_id = :user_id
             LIMIT 1'
        );
        $statement->execute(['interview_id' => $interviewId, 'user_id' => $userId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    public static function createForUser(PDO $pdo, int $userId, array $data): int|false
    {
        $pdo->beginTransaction();

        try {
            $application = $pdo->prepare(
                'SELECT id FROM job_applications
                 WHERE id = :application_id AND user_id = :user_id
                 FOR UPDATE'
            );
            $application->execute([
                'application_id' => $data['application_id'],
                'user_id' => $userId,
            ]);

            if ($application->fetchColumn() === false) {
                $pdo->rollBack();
                return false;
            }

            $statement = $pdo->prepare(
                'INSERT INTO interviews (user_id, application_id, interview_at, interview_type, notes)
                 VALUES (:user_id, :application_id, :interview_at, :interview_type, :notes)'
            );
            $statement->execute([
                'user_id' => $userId,
                'application_id' => $data['application_id'],
                'interview_at' => $data['interview_at'],
                'interview_type' => $data['interview_type'],
                'notes' => $data['notes'],
            ]);
            $interviewId = (int) $pdo->lastInsertId();
            $pdo->commit();

            return $interviewId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public static function updateForUser(
        PDO $pdo,
        int $interviewId,
        int $userId,
        array $data
    ): bool {
        $pdo->beginTransaction();

        try {
            $ownedInterview = $pdo->prepare(
                'SELECT id FROM interviews
                 WHERE id = :interview_id AND user_id = :user_id
                 FOR UPDATE'
            );
            $ownedInterview->execute(['interview_id' => $interviewId, 'user_id' => $userId]);
            if ($ownedInterview->fetchColumn() === false) {
                $pdo->rollBack();
                return false;
            }

            $ownedApplication = $pdo->prepare(
                'SELECT id FROM job_applications
                 WHERE id = :application_id AND user_id = :user_id
                 FOR UPDATE'
            );
            $ownedApplication->execute([
                'application_id' => $data['application_id'],
                'user_id' => $userId,
            ]);
            if ($ownedApplication->fetchColumn() === false) {
                $pdo->rollBack();
                return false;
            }

            $statement = $pdo->prepare(
                'UPDATE interviews
                 SET application_id = :application_id,
                     interview_at = :interview_at,
                     interview_type = :interview_type,
                     notes = :notes
                 WHERE id = :interview_id AND user_id = :user_id'
            );
            $statement->execute([
                'application_id' => $data['application_id'],
                'interview_at' => $data['interview_at'],
                'interview_type' => $data['interview_type'],
                'notes' => $data['notes'],
                'interview_id' => $interviewId,
                'user_id' => $userId,
            ]);
            $pdo->commit();

            return true;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public static function deleteForUser(PDO $pdo, int $interviewId, int $userId): bool
    {
        $statement = $pdo->prepare(
            'DELETE FROM interviews WHERE id = :interview_id AND user_id = :user_id'
        );
        $statement->execute(['interview_id' => $interviewId, 'user_id' => $userId]);

        return $statement->rowCount() === 1;
    }
}

