<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pdo = get_db();
$id = (int) ($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'pdf';

$stmt = $pdo->prepare('SELECT * FROM receipts WHERE id = ?');
$stmt->execute([$id]);
$receipt = $stmt->fetch();
if (!$receipt) {
    http_response_code(404);
    exit('Receipt not found.');
}

$column = $type === 'jpg' ? 'jpg_path' : 'pdf_path';
$relPath = $receipt[$column];
if (!$relPath) {
    http_response_code(404);
    exit('File not available for this receipt.');
}

$path = app_root() . '/' . $relPath;
if (!is_file($path)) {
    http_response_code(404);
    exit('File missing on server.');
}

$mime = $type === 'jpg' ? 'image/jpeg' : 'application/pdf';
$downloadName = slugify_receipt_no($receipt['receipt_no']) . '.' . ($type === 'jpg' ? 'jpg' : 'pdf');

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
