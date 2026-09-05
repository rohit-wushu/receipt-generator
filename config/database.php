<?php
/**
 * PDO connection helper. Reads settings from config/config.php.
 */

function app_config(): array
{
    static $config = null;
    if ($config === null) {
        $path = __DIR__ . '/config.php';
        if (!file_exists($path)) {
            throw new RuntimeException(
                'config/config.php not found. Copy config/config.sample.php to config/config.php and fill in your database details.'
            );
        }
        $config = require $path;
    }
    return $config;
}

function get_db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $db = app_config()['db'];
    $driver = $db['driver'] ?? 'mysql';

    if ($driver === 'sqlite') {
        $dsn = 'sqlite:' . $db['path'];
    } else {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $db['host'],
            $db['port'] ?? 3306,
            $db['name'],
            $db['charset'] ?? 'utf8mb4'
        );
    }

    $pdo = new PDO($dsn, $db['user'] ?? null, $db['pass'] ?? null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    if ($driver === 'sqlite') {
        $pdo->exec('PRAGMA foreign_keys = ON');
    }

    return $pdo;
}
