<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('list.php');
}
csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
if ($id) {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT pdf_path, jpg_path FROM receipts WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if ($row) {
        foreach (['pdf_path', 'jpg_path'] as $col) {
            if (!empty($row[$col])) {
                $path = app_root() . '/' . $row[$col];
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }
        $del = $pdo->prepare('DELETE FROM receipts WHERE id = ?');
        $del->execute([$id]);
        flash_set('success', 'Receipt deleted.');
    }
}

redirect('list.php');
