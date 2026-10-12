<?php
declare(strict_types=1);

require_once __DIR__ . '/middleware/session.php';
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/models/JobApplication.php';
require_once __DIR__ . '/models/Interview.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/JobApplicationController.php';
require_once __DIR__ . '/controllers/InterviewController.php';

start_app_session();

