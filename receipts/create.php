<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pdo = get_db();
$projects = $pdo->query('SELECT * FROM projects WHERE is_active = 1 ORDER BY project_name')->fetchAll();

if (!$projects) {
    flash_set('error', 'Please create a project first before issuing a receipt.');
    redirect(app_base_url() . '/projects/form.php');
}

$selectedProjectId = (int) ($_GET['project_id'] ?? $projects[0]['id']);

$errors = [];
$formData = [
    'project_id' => $selectedProjectId,
    'customer_name' => '',
    'customer_address' => '',
    'unit_no' => '',
    'amount' => '',
    'receipt_date' => date('Y-m-d'),
    'payment_mode' => 'UPI',
    'reference_no' => '',
    'remarks' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $formData['project_id'] = (int) ($_POST['project_id'] ?? 0);
    $formData['customer_name'] = trim((string) ($_POST['customer_name'] ?? ''));
    $formData['customer_address'] = trim((string) ($_POST['customer_address'] ?? ''));
    $formData['unit_no'] = trim((string) ($_POST['unit_no'] ?? ''));
    $formData['amount'] = trim((string) ($_POST['amount'] ?? ''));
    $formData['receipt_date'] = trim((string) ($_POST['receipt_date'] ?? date('Y-m-d')));
    $formData['payment_mode'] = trim((string) ($_POST['payment_mode'] ?? 'UPI'));
    $formData['reference_no'] = trim((string) ($_POST['reference_no'] ?? ''));
    $formData['remarks'] = trim((string) ($_POST['remarks'] ?? ''));

    $allowedModes = ['Cash', 'Cheque', 'UPI', 'Bank Transfer', 'Card', 'Other'];

    if (!$formData['project_id']) {
        $errors[] = 'Please select a project.';
    }
    if ($formData['customer_name'] === '') {
        $errors[] = 'Customer name is required.';
    }
    if ($formData['amount'] === '' || !is_numeric($formData['amount']) || (float) $formData['amount'] <= 0) {
        $errors[] = 'Please enter a valid amount greater than zero.';
    }
    if (!strtotime($formData['receipt_date'])) {
        $errors[] = 'Please enter a valid receipt date.';
    }
    if (!in_array($formData['payment_mode'], $allowedModes, true)) {
        $errors[] = 'Invalid payment mode.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ?');
        $stmt->execute([$formData['project_id']]);
        $project = $stmt->fetch();
        if (!$project) {
            $errors[] = 'Selected project not found.';
        } else {
            try {
                require_once __DIR__ . '/../includes/PdfGenerator.php';
                require_once __DIR__ . '/../includes/ImageGenerator.php';

                $receiptNo = next_receipt_number($pdo, $project);
                $amount = (float) $formData['amount'];

                $insert = $pdo->prepare(
                    'INSERT INTO receipts (project_id, receipt_no, receipt_date, customer_name, customer_address, unit_no, amount, amount_words, payment_mode, reference_no, remarks, created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
                );
                $insert->execute([
                    $project['id'],
                    $receiptNo,
                    $formData['receipt_date'],
                    $formData['customer_name'],
                    $formData['customer_address'],
                    $formData['unit_no'],
                    $amount,
                    amount_in_words($amount),
                    $formData['payment_mode'],
                    $formData['reference_no'],
                    $formData['remarks'],
                    current_user()['id'] ?? null,
                ]);
                $receiptId = (int) $pdo->lastInsertId();

                $stmt = $pdo->prepare('SELECT * FROM receipts WHERE id = ?');
                $stmt->execute([$receiptId]);
                $receipt = $stmt->fetch();

                $pdfPath = generate_receipt_pdf($project, $receipt);
                $jpgPath = generate_receipt_jpg($project, $receipt);

                $update = $pdo->prepare('UPDATE receipts SET pdf_path = ?, jpg_path = ? WHERE id = ?');
                $update->execute([$pdfPath, $jpgPath, $receiptId]);

                flash_set('success', 'Receipt ' . $receiptNo . ' created and exported to PDF & JPG.');
                redirect(app_base_url() . '/receipts/view.php?id=' . $receiptId);
            } catch (Throwable $e) {
                $errors[] = 'Could not create receipt: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'New Receipt';
require __DIR__ . '/../includes/layout_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">New Receipt</h4>
  <a href="list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" class="card">
  <div class="card-body">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Project *</label>
        <select name="project_id" class="form-select" required>
          <?php foreach ($projects as $p): ?>
            <option value="<?= (int) $p['id'] ?>" <?= $formData['project_id'] == $p['id'] ? 'selected' : '' ?>>
              <?= e($p['project_name']) ?> — <?= e($p['company_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Receipt Date *</label>
        <input type="date" name="receipt_date" class="form-control" required value="<?= e($formData['receipt_date']) ?>">
      </div>

      <div class="col-md-6">
        <label class="form-label">Customer Name *</label>
        <input type="text" name="customer_name" class="form-control" required value="<?= e($formData['customer_name']) ?>" placeholder="e.g. Ms. Pooja w/o Vimal Khatri">
      </div>
      <div class="col-md-6">
        <label class="form-label">Unit No.</label>
        <input type="text" name="unit_no" class="form-control" value="<?= e($formData['unit_no']) ?>" placeholder="e.g. A4">
      </div>

      <div class="col-12">
        <label class="form-label">Customer Address</label>
        <textarea name="customer_address" class="form-control" rows="2" placeholder="e.g. 7, Kundli (55), Sonipat, Haryana - 131028"><?= e($formData['customer_address']) ?></textarea>
      </div>

      <div class="col-md-4">
        <label class="form-label">Amount (₹) *</label>
        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required value="<?= e($formData['amount']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Payment Mode *</label>
        <select name="payment_mode" class="form-select" required>
          <?php foreach (['Cash', 'Cheque', 'UPI', 'Bank Transfer', 'Card', 'Other'] as $mode): ?>
            <option value="<?= e($mode) ?>" <?= $formData['payment_mode'] === $mode ? 'selected' : '' ?>><?= e($mode) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Reference / UTR No.</label>
        <input type="text" name="reference_no" class="form-control" value="<?= e($formData['reference_no']) ?>">
      </div>

      <div class="col-12">
        <label class="form-label">Remarks (optional)</label>
        <input type="text" name="remarks" class="form-control" value="<?= e($formData['remarks']) ?>">
      </div>
    </div>

    <div class="mt-4">
      <button type="submit" class="btn btn-success"><i class="bi bi-receipt me-1"></i>Generate Receipt (PDF + JPG)</button>
    </div>
  </div>
</form>
<?php require __DIR__ . '/../includes/layout_footer.php'; ?>
