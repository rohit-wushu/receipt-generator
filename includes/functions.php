<?php
require_once __DIR__ . '/NumberToWords.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function format_money(float $amount): string
{
    return '₹' . number_format($amount, 2);
}

function format_date_display(string $ymd): string
{
    $ts = strtotime($ymd);
    return $ts ? date('d/m/Y', $ts) : $ymd;
}

/**
 * Builds the current Indian financial year string, e.g. "26-27" for
 * any date between 1 Apr 2026 and 31 Mar 2027.
 */
function current_financial_year(): string
{
    $year = (int) date('Y');
    $month = (int) date('n');
    if ($month < 4) {
        $start = $year - 1;
    } else {
        $start = $year;
    }
    return substr((string) $start, -2) . '-' . substr((string) ($start + 1), -2);
}

/**
 * Reserves and returns the next receipt number for a project, e.g.
 * "GB/RCT/26-27/046". Increments the project's counter atomically.
 */
function next_receipt_number(PDO $pdo, array $project): string
{
    $pdo->beginTransaction();
    try {
        $lockClause = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? '' : ' FOR UPDATE';
        $stmt = $pdo->prepare('SELECT next_receipt_seq FROM projects WHERE id = ?' . $lockClause);
        $stmt->execute([$project['id']]);
        $seq = (int) $stmt->fetchColumn();
        if ($seq < 1) {
            $seq = 1;
        }

        $update = $pdo->prepare('UPDATE projects SET next_receipt_seq = ? WHERE id = ?');
        $update->execute([$seq + 1, $project['id']]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    $padding = max(1, (int) ($project['receipt_no_padding'] ?? 3));
    $number = str_pad((string) $seq, $padding, '0', STR_PAD_LEFT);

    return sprintf('%s/%s/%s', $project['receipt_prefix'], current_financial_year(), $number);
}

function amount_in_words(float $amount): string
{
    return NumberToWords::convertRupees($amount);
}

/**
 * Turns a receipt number like "GB/RCT/26-27/046" into a filesystem-safe
 * slug like "GB-RCT-26-27-046" for use as a filename.
 */
function slugify_receipt_no(string $receiptNo): string
{
    $slug = preg_replace('/[^A-Za-z0-9]+/', '-', $receiptNo);
    return trim($slug, '-');
}

function ensure_dir(string $path): void
{
    if (!is_dir($path)) {
        mkdir($path, 0775, true);
    }
}

function flash_set(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    if (!empty($_SESSION['flash'][$key])) {
        $message = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $message;
    }
    return null;
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/**
 * Validates and moves an uploaded image (logo/seal/signature) into
 * <app root>/$relDestDir (e.g. "uploads/logos"). Returns the relative
 * path to store in the DB (e.g. "uploads/logos/logo_ab12cd.jpg"), or
 * null if no file was uploaded. Throws on invalid uploads.
 */
function handle_image_upload(string $fieldName, string $relDestDir, string $prefix): ?string
{
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed for ' . $fieldName);
    }

    if ($file['size'] > 4 * 1024 * 1024) {
        throw new RuntimeException('Image too large (max 4MB): ' . $fieldName);
    }

    $info = getimagesize($file['tmp_name']);
    if ($info === false) {
        throw new RuntimeException('Invalid image file: ' . $fieldName);
    }

    $allowed = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
    ];
    if (!isset($allowed[$info[2]])) {
        throw new RuntimeException('Only JPG or PNG images are allowed: ' . $fieldName);
    }

    $relDestDir = trim($relDestDir, '/\\');
    $destDir = app_root() . '/' . $relDestDir;
    ensure_dir($destDir);

    $filename = $prefix . '_' . bin2hex(random_bytes(6)) . '.' . $allowed[$info[2]];
    $destPath = $destDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new RuntimeException('Could not save uploaded file: ' . $fieldName);
    }

    return $relDestDir . '/' . $filename;
}

function app_root(): string
{
    return realpath(__DIR__ . '/..');
}
