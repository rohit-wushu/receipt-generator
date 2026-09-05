<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pdo = get_db();
$project = [
    'id' => null,
    'project_name' => '',
    'company_name' => '',
    'company_tagline' => '',
    'location' => '',
    'office_address' => '',
    'email' => '',
    'phone' => '',
    'website' => '',
    'logo_path' => null,
    'seal_path' => null,
    'signature_path' => null,
    'signatory_name' => 'Director',
    'primary_color' => '#163823',
    'secondary_color' => '#e07b28',
    'receipt_prefix' => 'RCT',
    'next_receipt_seq' => 1,
    'receipt_no_padding' => 3,
    'is_active' => 1,
];

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ?');
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        flash_set('error', 'Project not found.');
        redirect('list.php');
    }
    $project = $existing;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $project['project_name'] = trim((string) ($_POST['project_name'] ?? ''));
    $project['company_name'] = trim((string) ($_POST['company_name'] ?? ''));
    $project['company_tagline'] = trim((string) ($_POST['company_tagline'] ?? ''));
    $project['location'] = trim((string) ($_POST['location'] ?? ''));
    $project['office_address'] = trim((string) ($_POST['office_address'] ?? ''));
    $project['email'] = trim((string) ($_POST['email'] ?? ''));
    $project['phone'] = trim((string) ($_POST['phone'] ?? ''));
    $project['website'] = trim((string) ($_POST['website'] ?? ''));
    $project['signatory_name'] = trim((string) ($_POST['signatory_name'] ?? '')) ?: 'Director';
    $project['primary_color'] = trim((string) ($_POST['primary_color'] ?? '#163823'));
    $project['secondary_color'] = trim((string) ($_POST['secondary_color'] ?? '#e07b28'));
    $project['receipt_prefix'] = strtoupper(trim((string) ($_POST['receipt_prefix'] ?? 'RCT')));
    $project['receipt_no_padding'] = max(1, min(6, (int) ($_POST['receipt_no_padding'] ?? 3)));
    $project['next_receipt_seq'] = max(1, (int) ($_POST['next_receipt_seq'] ?? 1));
    $project['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    if ($project['project_name'] === '') {
        $errors[] = 'Project name is required.';
    }
    if ($project['company_name'] === '') {
        $errors[] = 'Company name is required.';
    }
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $project['primary_color'])) {
        $errors[] = 'Primary color must be a valid hex color.';
    }
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $project['secondary_color'])) {
        $errors[] = 'Secondary color must be a valid hex color.';
    }

    if (!$errors) {
        try {
            $logoRel = handle_image_upload('logo', 'uploads/logos', 'logo') ?? $project['logo_path'];
            $sealRel = handle_image_upload('seal', 'uploads/seals', 'seal') ?? $project['seal_path'];
            $signatureRel = handle_image_upload('signature', 'uploads/signatures', 'sig') ?? $project['signature_path'];

            if ($id) {
                $sql = 'UPDATE projects SET project_name=?, company_name=?, company_tagline=?, location=?, office_address=?, email=?, phone=?, website=?, logo_path=?, seal_path=?, signature_path=?, signatory_name=?, primary_color=?, secondary_color=?, receipt_prefix=?, next_receipt_seq=?, receipt_no_padding=?, is_active=? WHERE id=?';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $project['project_name'], $project['company_name'], $project['company_tagline'],
                    $project['location'], $project['office_address'], $project['email'], $project['phone'],
                    $project['website'], $logoRel, $sealRel, $signatureRel, $project['signatory_name'],
                    $project['primary_color'], $project['secondary_color'], $project['receipt_prefix'],
                    $project['next_receipt_seq'], $project['receipt_no_padding'], $project['is_active'], $id,
                ]);
                flash_set('success', 'Project updated successfully.');
            } else {
                $sql = 'INSERT INTO projects (project_name, company_name, company_tagline, location, office_address, email, phone, website, logo_path, seal_path, signature_path, signatory_name, primary_color, secondary_color, receipt_prefix, next_receipt_seq, receipt_no_padding, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $project['project_name'], $project['company_name'], $project['company_tagline'],
                    $project['location'], $project['office_address'], $project['email'], $project['phone'],
                    $project['website'], $logoRel, $sealRel, $signatureRel, $project['signatory_name'],
                    $project['primary_color'], $project['secondary_color'], $project['receipt_prefix'],
                    $project['next_receipt_seq'], $project['receipt_no_padding'], $project['is_active'],
                ]);
                flash_set('success', 'Project created successfully.');
            }
            redirect('list.php');
        } catch (Throwable $e) {
            $errors[] = 'Could not save project: ' . $e->getMessage();
        }
    }
}

$pageTitle = $id ? 'Edit Project' : 'New Project';
require __DIR__ . '/../includes/layout_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><?= $id ? 'Edit Project' : 'New Project' ?></h4>
  <a href="list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card">
  <div class="card-body">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">

    <h6 class="text-uppercase text-muted small mb-3">Project Details</h6>
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <label class="form-label">Project Name *</label>
        <input type="text" name="project_name" class="form-control" required value="<?= e($project['project_name']) ?>" placeholder="e.g. Himalaya Villas">
      </div>
      <div class="col-md-6">
        <label class="form-label">Location</label>
        <input type="text" name="location" class="form-control" value="<?= e($project['location']) ?>" placeholder="e.g. Tehsil Kotdwar, Distt. Pauri Garhwal, Uttarakhand">
      </div>
    </div>

    <h6 class="text-uppercase text-muted small mb-3">Company / Letterhead</h6>
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <label class="form-label">Company Name *</label>
        <input type="text" name="company_name" class="form-control" required value="<?= e($project['company_name']) ?>" placeholder="e.g. Goyal Buildcon">
      </div>
      <div class="col-md-6">
        <label class="form-label">Tagline</label>
        <input type="text" name="company_tagline" class="form-control" value="<?= e($project['company_tagline']) ?>" placeholder="e.g. Building Trust. Creating Landmarks.">
      </div>
      <div class="col-md-8">
        <label class="form-label">Office Address</label>
        <input type="text" name="office_address" class="form-control" value="<?= e($project['office_address']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Signatory Designation</label>
        <input type="text" name="signatory_name" class="form-control" value="<?= e($project['signatory_name']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= e($project['email']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="<?= e($project['phone']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Website</label>
        <input type="text" name="website" class="form-control" value="<?= e($project['website']) ?>">
      </div>
    </div>

    <h6 class="text-uppercase text-muted small mb-3">Branding Images</h6>
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <label class="form-label">Logo (JPG/PNG)</label>
        <input type="file" name="logo" class="form-control" accept="image/png,image/jpeg">
        <?php if (!empty($project['logo_path'])): ?>
          <img src="<?= e(app_base_url() . '/' . $project['logo_path']) ?>" class="thumb-preview mt-2 d-block">
        <?php endif; ?>
      </div>
      <div class="col-md-4">
        <label class="form-label">Round Seal / Stamp (optional)</label>
        <input type="file" name="seal" class="form-control" accept="image/png,image/jpeg">
        <?php if (!empty($project['seal_path'])): ?>
          <img src="<?= e(app_base_url() . '/' . $project['seal_path']) ?>" class="thumb-preview mt-2 d-block">
        <?php endif; ?>
      </div>
      <div class="col-md-4">
        <label class="form-label">Signature (optional)</label>
        <input type="file" name="signature" class="form-control" accept="image/png,image/jpeg">
        <?php if (!empty($project['signature_path'])): ?>
          <img src="<?= e(app_base_url() . '/' . $project['signature_path']) ?>" class="thumb-preview mt-2 d-block">
        <?php endif; ?>
      </div>
    </div>

    <h6 class="text-uppercase text-muted small mb-3">Theme Colors</h6>
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <label class="form-label">Primary Color</label>
        <input type="color" name="primary_color" class="form-control form-control-color color-swatch" value="<?= e($project['primary_color']) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Secondary Color</label>
        <input type="color" name="secondary_color" class="form-control form-control-color color-swatch" value="<?= e($project['secondary_color']) ?>">
      </div>
    </div>

    <h6 class="text-uppercase text-muted small mb-3">Receipt Numbering</h6>
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <label class="form-label">Receipt Prefix</label>
        <input type="text" name="receipt_prefix" class="form-control" value="<?= e($project['receipt_prefix']) ?>" placeholder="e.g. GB/RCT">
        <div class="form-text">Final number looks like PREFIX/FY/0001</div>
      </div>
      <div class="col-md-3">
        <label class="form-label">Next Sequence No.</label>
        <input type="number" min="1" name="next_receipt_seq" class="form-control" value="<?= (int) $project['next_receipt_seq'] ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Digits (padding)</label>
        <input type="number" min="1" max="6" name="receipt_no_padding" class="form-control" value="<?= (int) $project['receipt_no_padding'] ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label d-block">Status</label>
        <div class="form-check form-switch mt-2">
          <input class="form-check-input" type="checkbox" name="is_active" <?= $project['is_active'] ? 'checked' : '' ?>>
          <label class="form-check-label">Active</label>
        </div>
      </div>
    </div>

    <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-1"></i>Save Project</button>
  </div>
</form>
<?php require __DIR__ . '/../includes/layout_footer.php'; ?>
