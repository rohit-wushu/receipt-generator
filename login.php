<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect(app_base_url() . '/index.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } elseif (attempt_login($email, $password)) {
        $redirect = $_SESSION['redirect_after_login'] ?? (app_base_url() . '/index.php');
        unset($_SESSION['redirect_after_login']);
        redirect($redirect);
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login · Receipt Generator</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= e(app_base_url()) ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="login-wrapper">
  <div class="card login-card shadow">
    <div class="card-body p-4">
      <div class="text-center mb-3">
        <i class="bi bi-receipt-cutoff display-5 text-success"></i>
        <h4 class="mt-2 mb-0">Receipt Generator</h4>
        <small class="text-muted">Sign in to continue</small>
      </div>
      <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= e($error) ?></div>
      <?php endif; ?>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required autofocus value="<?= e($_POST['email'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success w-100">Login</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
