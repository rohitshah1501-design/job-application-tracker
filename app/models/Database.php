<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use RuntimeException;

final class Database
{
    /** Create a PDO connection when application code needs the database. */
    public static function connect(): PDO
    {
        $config = require __DIR__ . '/../config/database.php';

        if ($config['username'] === '' || $config['password'] === null) {
            throw new RuntimeException(
                'Set a non-empty JOB_TRACKER_DB_USER and define JOB_TRACKER_DB_PASSWORD before connecting.'
            );
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        return new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}

