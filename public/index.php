<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

header('Location: ' . (is_user_authenticated() ? '/dashboard.php' : '/login.php'));
exit;

