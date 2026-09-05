<?php
/**
 * Include after session_start()+auth check. Expects optional $pageTitle.
 */
$pageTitle = $pageTitle ?? 'Receipt Generator';
$base = app_base_url();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> · Receipt Generator</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= e($base) ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php if ($user): ?>
<nav class="navbar navbar-expand-lg navbar-dark app-navbar">
  <div class="container-fluid">
    <a class="navbar-brand fw-semibold" href="<?= e($base) ?>/index.php"><i class="bi bi-receipt-cutoff me-2"></i>Receipt Generator</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>/index.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>/projects/list.php"><i class="bi bi-building me-1"></i>Projects</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>/receipts/list.php"><i class="bi bi-list-ul me-1"></i>Receipts</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>/receipts/create.php"><i class="bi bi-plus-circle me-1"></i>New Receipt</a></li>
      </ul>
      <ul class="navbar-nav">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-person-circle me-1"></i><?= e($user['name']) ?></a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= e($base) ?>/logout.php">Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
<?php endif; ?>
<main class="container-fluid py-4 px-3 px-md-4">
<?php
$success = flash_get('success');
$error = flash_get('error');
if ($success): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert"><?= e($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif;
if ($error): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert"><?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
