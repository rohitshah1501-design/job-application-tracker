<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;
use PDOException;

final class AuthController
{
    /** @return array{errors: list<string>, name: string, email: string} */
    public static function register(PDO $pdo, array $input): array
    {
        $name = trim(is_string($input['name'] ?? null) ? $input['name'] : '');
        $email = strtolower(trim(is_string($input['email'] ?? null) ? $input['email'] : ''));
        $password = is_string($input['password'] ?? null) ? $input['password'] : '';
        $confirmation = is_string($input['password_confirmation'] ?? null)
            ? $input['password_confirmation']
            : '';
        $errors = [];

        $nameLength = preg_match_all('/./us', $name);
        if ($name === '' || $nameLength === false || $nameLength > 120) {
            $errors[] = 'Enter your name using no more than 120 characters.';
        }

        if ($email === '' || strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Enter a valid email address (up to 254 characters).';
        }

        $passwordLength = preg_match_all('/./us', $password);
        if ($passwordLength === false || $passwordLength < 12 || strlen($password) > 72) {
            $errors[] = 'Use a password with at least 12 characters and no more than 72 bytes.';
        }

        if ($password !== $confirmation) {
            $errors[] = 'The password confirmation does not match.';
        }

        if ($errors !== []) {
            return ['errors' => $errors, 'name' => $name, 'email' => $email];
        }

        $exists = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $exists->execute(['email' => $email]);
        if ($exists->fetchColumn() !== false) {
            return [
                'errors' => ['An account with that email address already exists.'],
                'name' => $name,
                'email' => $email,
            ];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $insert = $pdo->prepare(
            'INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)'
        );

        try {
            $insert->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => $passwordHash,
            ]);
        } catch (PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1062) {
                return [
                    'errors' => ['An account with that email address already exists.'],
                    'name' => $name,
                    'email' => $email,
                ];
            }

            throw $exception;
        }

        return ['errors' => [], 'name' => $name, 'email' => $email];
    }

    /** @return array{success: bool} */
    public static function login(PDO $pdo, array $input): array
    {
        $email = strtolower(trim(is_string($input['email'] ?? null) ? $input['email'] : ''));
        $password = is_string($input['password'] ?? null) ? $input['password'] : '';

        if ($email === '' || strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || $password === '' || strlen($password) > 72) {
            return ['success' => false];
        }

        $statement = $pdo->prepare(
            'SELECT id, name, password_hash FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if (!is_array($user) || !password_verify($password, $user['password_hash'])) {
            return ['success' => false];
        }

        session_regenerate_id(true);
        unset($_SESSION['_csrf_token']);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = (string) $user['name'];

        return ['success' => true];
    }
}

