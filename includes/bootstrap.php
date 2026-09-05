<?php
declare(strict_types=1);

error_reporting(E_ALL);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';

$config = app_config();
if (!empty($config['app']['debug'])) {
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
}
