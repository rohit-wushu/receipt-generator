<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pdo = get_db();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM receipts WHERE id = ?');
$stmt->execute([$id]);
$receipt = $stmt->fetch();
if (!$receipt) {
    flash_set('error', 'Receipt not found.');
    redirect('list.php');
}

$stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ?');
$stmt->execute([$receipt['project_id']]);
$project = $stmt->fetch();

$pageTitle = 'Receipt ' . $receipt['receipt_no'];
require __DIR__ . '/../includes/layout_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Receipt <?= e($receipt['receipt_no']) ?></h4>
  <div>
    <a href="list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
    <a href="download.php?id=<?= (int) $receipt['id'] ?>&type=pdf" class="btn btn-danger btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i>Download PDF</a>
    <a href="download.php?id=<?= (int) $receipt['id'] ?>&type=jpg" class="btn btn-primary btn-sm"><i class="bi bi-file-earmark-image me-1"></i>Download JPG</a>
  </div>
</div>

<div class="receipt-preview p-0">
  <iframe src="preview.php?id=<?= (int) $receipt['id'] ?>" style="width:100%; height:1050px; border:0;" title="Receipt preview"></iframe>
</div>

<?php require __DIR__ . '/../includes/layout_footer.php'; ?>
