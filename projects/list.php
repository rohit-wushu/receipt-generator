<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pdo = get_db();
$projects = $pdo->query('SELECT * FROM projects ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Projects';
require __DIR__ . '/../includes/layout_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Projects</h4>
  <a href="form.php" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i>New Project</a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Logo</th>
          <th>Project</th>
          <th>Company</th>
          <th>Receipt Prefix</th>
          <th>Next Receipt No.</th>
          <th>Status</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$projects): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No projects yet. Create your first project to start issuing receipts.</td></tr>
      <?php endif; ?>
      <?php foreach ($projects as $p): ?>
        <tr>
          <td>
            <?php if ($p['logo_path']): ?>
              <img src="<?= e(app_base_url() . '/' . $p['logo_path']) ?>" alt="logo" style="height:36px">
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td><?= e($p['project_name']) ?><br><small class="text-muted"><?= e($p['location']) ?></small></td>
          <td><?= e($p['company_name']) ?></td>
          <td><code><?= e($p['receipt_prefix']) ?></code></td>
          <td><?= e(sprintf('%s/%s/%s', $p['receipt_prefix'], current_financial_year(), str_pad((string)$p['next_receipt_seq'], (int)$p['receipt_no_padding'], '0', STR_PAD_LEFT))) ?></td>
          <td>
            <?php if ($p['is_active']): ?>
              <span class="badge bg-success">Active</span>
            <?php else: ?>
              <span class="badge bg-secondary">Inactive</span>
            <?php endif; ?>
          </td>
          <td class="text-end">
            <a href="form.php?id=<?= (int) $p['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
            <a href="<?= e(app_base_url()) ?>/receipts/create.php?project_id=<?= (int) $p['id'] ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-receipt"></i> New Receipt</a>
            <form method="post" action="delete.php" class="d-inline" onsubmit="return confirm('Delete this project? Receipts under it will also be deleted.');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
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
