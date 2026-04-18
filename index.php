<?php
require_once __DIR__ . '/framework/bootstrap.php';

$user = auth_user();
if ($user) {
    redirect(dashboard_url($user['role']));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($username && $password) {
            $userModel = new User();
            $found = $userModel->findByUsername($username);
            if ($found && $userModel->verifyPassword($found, $password)) {
                session_regenerate_id(true);
                unset($_SESSION['csrf_token']); // regenerate CSRF token on login
                $_SESSION['user_id'] = $found['id'];
                $_SESSION['username'] = $found['username'];
                $_SESSION['role'] = $found['role'];
                redirect(dashboard_url($found['role']));
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Please enter username and password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= APP_NAME ?> - CTF Platform</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); min-height: 100vh; }
    .login-card { backdrop-filter: blur(10px); background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); }
    .brand-icon { font-size: 4rem; color: #ffc107; }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
      <div class="text-center mb-4">
        <i class="fas fa-flag brand-icon"></i>
        <h1 class="text-white fw-bold mt-2"><?= APP_NAME ?></h1>
        <p class="text-light opacity-75">Capture The Flag Platform</p>
      </div>
      <div class="card login-card shadow-lg">
        <div class="card-body p-4">
          <h4 class="text-white mb-4 text-center">Sign In</h4>
          <?php if ($error): ?>
          <div class="alert alert-danger py-2"><i class="fas fa-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>
          <form method="POST" action="index.php">
            <?= csrf_field() ?>
            <div class="mb-3">
              <label class="form-label text-light">Username</label>
              <div class="input-group">
                <span class="input-group-text bg-dark text-light border-secondary"><i class="fas fa-user"></i></span>
                <input type="text" class="form-control bg-dark text-white border-secondary" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
              </div>
            </div>
            <div class="mb-4">
              <label class="form-label text-light">Password</label>
              <div class="input-group">
                <span class="input-group-text bg-dark text-light border-secondary"><i class="fas fa-lock"></i></span>
                <input type="password" class="form-control bg-dark text-white border-secondary" name="password" required>
              </div>
            </div>
            <button type="submit" class="btn btn-warning w-100 fw-bold"><i class="fas fa-sign-in-alt me-1"></i>Login</button>
          </form>
        </div>
      </div>
      <p class="text-center text-light opacity-50 mt-3 small">Contact an administrator for account access.</p>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
