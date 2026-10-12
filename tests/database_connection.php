<?php
declare(strict_types=1);

use App\Models\Database;

require dirname(__DIR__) . '/app/models/Database.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$username = getenv('JOB_TRACKER_DB_USER');
$password = getenv('JOB_TRACKER_DB_PASSWORD');
$allowEmptyPassword = getenv('JOB_TRACKER_DB_ALLOW_EMPTY_PASSWORD') === '1';
$hasPassword = $password !== false && $password !== '';

if ($username === false || $username === '' || (!$hasPassword && !$allowEmptyPassword)) {
    fwrite(
        STDERR,
        "Database credentials are not configured. Set a non-empty JOB_TRACKER_DB_USER and either a non-empty JOB_TRACKER_DB_PASSWORD or JOB_TRACKER_DB_ALLOW_EMPTY_PASSWORD=1 in this PowerShell session.\n"
    );
    exit(2);
}

try {
    $pdo = Database::connect();
    $statement = $pdo->query('SELECT DATABASE() AS database_name, @@port AS server_port');
    $connection = $statement->fetch(PDO::FETCH_ASSOC);

    if (
        !is_array($connection)
        || $connection['database_name'] !== 'job_tracker'
        || (int) $connection['server_port'] !== 3307
    ) {
        throw new RuntimeException('The connection did not reach the expected database and port.');
    }

    fwrite(STDOUT, "Database connection successful: job_tracker on port 3307.\n");
} catch (Throwable) {
    fwrite(
        STDERR,
        "Database connection failed. Check the local credentials and XAMPP MariaDB availability. Details were withheld.\n"
    );
    exit(1);
}
