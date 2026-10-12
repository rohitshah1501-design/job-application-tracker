<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require_authenticated_user();

header('Location: /dashboard.php');
exit;
