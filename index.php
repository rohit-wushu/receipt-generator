<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$pdo = get_db();

$totalProjects = (int) $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn();
$totalReceipts = (int) $pdo->query('SELECT COUNT(*) FROM receipts')->fetchColumn();
$totalAmount = (float) $pdo->query('SELECT COALESCE(SUM(amount),0) FROM receipts')->fetchColumn();

$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$stmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM receipts WHERE receipt_date = ?');
$stmt->execute([$today]);
$todayAmount = (float) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM receipts WHERE receipt_date >= ?');
$stmt->execute([$monthStart]);
$monthAmount = (float) $stmt->fetchColumn();

$recent = $pdo->query('
    SELECT r.*, p.project_name
    FROM receipts r JOIN projects p ON p.id = r.project_id
    ORDER BY r.created_at DESC LIMIT 10
')->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/layout_header.php';
?>
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card stat-card"><div class="card-body">
      <div class="text-muted small">Total Projects</div>
      <div class="display-6"><?= $totalProjects ?></div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card stat-card"><div class="card-body">
      <div class="text-muted small">Total Receipts</div>
      <div class="display-6"><?= $totalReceipts ?></div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card stat-card"><div class="card-body">
      <div class="text-muted small">Collected Today</div>
      <div class="display-6 text-success"><?= e(format_money($todayAmount)) ?></div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card stat-card"><div class="card-body">
      <div class="text-muted small">Collected (This Month)</div>
      <div class="display-6 text-success"><?= e(format_money($monthAmount)) ?></div>
    </div></div>
  </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Recent Receipts</h5>
  <a href="receipts/create.php" class="btn btn-success btn-sm"><i class="bi bi-plus-circle me-1"></i>New Receipt</a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr><th>Receipt No.</th><th>Date</th><th>Customer</th><th>Project</th><th class="text-end">Amount</th><th class="text-end">Actions</th></tr>
      </thead>
      <tbody>
      <?php if (!$recent): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No receipts yet. Create your first project, then issue a receipt.</td></tr>
      <?php endif; ?>
      <?php foreach ($recent as $r): ?>
        <tr>
          <td><code><?= e($r['receipt_no']) ?></code></td>
          <td><?= e(format_date_display($r['receipt_date'])) ?></td>
          <td><?= e($r['customer_name']) ?></td>
          <td><?= e($r['project_name']) ?></td>
          <td class="text-end"><?= e(format_money((float) $r['amount'])) ?></td>
          <td class="text-end">
            <a href="receipts/view.php?id=<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/includes/layout_footer.php'; ?>
