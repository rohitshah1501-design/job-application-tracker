<?php
declare(strict_types=1);

/**
 * Private database settings. Credentials come from the process environment,
 * so no password needs to be stored in the project files.
 *
 * @return array{host: string, port: int, database: string, charset: string, username: string, password: ?string}
 */
$username = getenv('JOB_TRACKER_DB_USER');
$passwordFromEnvironment = getenv('JOB_TRACKER_DB_PASSWORD');
$allowEmptyPassword = getenv('JOB_TRACKER_DB_ALLOW_EMPTY_PASSWORD') === '1';

if ($allowEmptyPassword) {
    $password = $passwordFromEnvironment === false ? '' : $passwordFromEnvironment;
} else {
    $password = $passwordFromEnvironment === false || $passwordFromEnvironment === ''
        ? null
        : $passwordFromEnvironment;
}

return [
    'host' => getenv('JOB_TRACKER_DB_HOST') ?: '127.0.0.1',
    'port' => (int) (getenv('JOB_TRACKER_DB_PORT') ?: 3307),
    'database' => getenv('JOB_TRACKER_DB_NAME') ?: 'job_tracker',
    'charset' => 'utf8mb4',
    'username' => $username === false ? '' : $username,
    'password' => $password,
];

