<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../templates/receipt_html.php';
require_login();

$pdo = get_db();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM receipts WHERE id = ?');
$stmt->execute([$id]);
$receipt = $stmt->fetch();
if (!$receipt) {
    http_response_code(404);
    exit('Receipt not found.');
}

$stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ?');
$stmt->execute([$receipt['project_id']]);
$project = $stmt->fetch();

echo render_receipt_html($project, $receipt, false);
