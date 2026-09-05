<?php
require_once __DIR__ . '/../config/database.php';

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
        redirect(app_base_url() . '/login.php');
    }
}

function attempt_login(string $email, string $password): bool
{
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'    => $user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => $user['role'],
    ];

    return true;
}

function logout(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

/**
 * Absolute URL path to the application root (e.g. "" or "/receipts"),
 * regardless of which subfolder the current script lives in.
 */
function app_base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $root = realpath(__DIR__ . '/..');
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;

    if ($root && $docRoot && str_starts_with($root, $docRoot)) {
        $base = str_replace('\\', '/', substr($root, strlen($docRoot)));
    } else {
        $base = '';
    }

    return $base = rtrim($base, '/');
}
