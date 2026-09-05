<?php
/**
 * Copy this file to config.php and fill in your real values.
 * config.php is gitignored so your credentials never get committed.
 */

return [
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'receipt_generator',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    // Used for session cookie name / app title.
    'app' => [
        'name'  => 'Receipt Generator',
        'debug' => false,
    ],
];
