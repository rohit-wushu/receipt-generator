<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pdo = get_db();

$projectId = (int) ($_GET['project_id'] ?? 0);
$search = trim((string) ($_GET['q'] ?? ''));

$where = [];
$params = [];
if ($projectId) {
    $where[] = 'r.project_id = ?';
    $params[] = $projectId;
}
if ($search !== '') {
    $where[] = '(r.customer_name LIKE ? OR r.receipt_no LIKE ? OR r.unit_no LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $pdo->prepare("
    SELECT r.*, p.project_name, p.company_name
    FROM receipts r
    JOIN projects p ON p.id = r.project_id
    $whereSql
    ORDER BY r.created_at DESC
    LIMIT 200
");
$stmt->execute($params);
$receipts = $stmt->fetchAll();

$projects = $pdo->query('SELECT id, project_name FROM projects ORDER BY project_name')->fetchAll();

$pageTitle = 'Receipts';
require __DIR__ . '/../includes/layout_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Receipts</h4>
  <a href="create.php" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i>New Receipt</a>
</div>

<form method="get" class="row g-2 mb-3">
  <div class="col-md-4">
    <select name="project_id" class="form-select" onchange="this.form.submit()">
      <option value="0">All Projects</option>
      <?php foreach ($projects as $p): ?>
        <option value="<?= (int) $p['id'] ?>" <?= $projectId == $p['id'] ? 'selected' : '' ?>><?= e($p['project_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-5">
    <input type="text" name="q" class="form-control" placeholder="Search name, receipt no, unit no..." value="<?= e($search) ?>">
  </div>
  <div class="col-md-2">
    <button class="btn btn-outline-primary w-100" type="submit"><i class="bi bi-search"></i> Search</button>
  </div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Receipt No.</th>
          <th>Date</th>
          <th>Customer</th>
          <th>Project</th>
          <th>Unit</th>
          <th class="text-end">Amount</th>
          <th>Mode</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$receipts): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">No receipts found.</td></tr>
      <?php endif; ?>
      <?php foreach ($receipts as $r): ?>
        <tr>
          <td><code><?= e($r['receipt_no']) ?></code></td>
          <td><?= e(format_date_display($r['receipt_date'])) ?></td>
          <td><?= e($r['customer_name']) ?></td>
          <td><?= e($r['project_name']) ?></td>
          <td><?= e($r['unit_no'] ?: '—') ?></td>
          <td class="text-end"><?= e(format_money((float) $r['amount'])) ?></td>
          <td><span class="badge bg-light text-dark border"><?= e($r['payment_mode']) ?></span></td>
          <td class="text-end">
            <a href="view.php?id=<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
            <a href="download.php?id=<?= (int) $r['id'] ?>&type=pdf" class="btn btn-sm btn-outline-danger" title="Download PDF"><i class="bi bi-file-earmark-pdf"></i></a>
            <a href="download.php?id=<?= (int) $r['id'] ?>&type=jpg" class="btn btn-sm btn-outline-primary" title="Download JPG"><i class="bi bi-file-earmark-image"></i></a>
            <form method="post" action="delete.php" class="d-inline" onsubmit="return confirm('Delete this receipt?');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../includes/layout_footer.php'; ?>
