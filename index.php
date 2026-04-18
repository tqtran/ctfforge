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
                unset($_SESSION['csrf_token']);
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

$audiences = [
    [
        'title' => 'Challenge Authors',
        'description' => 'Turn ideas into standout challenges, earn badges, unlock commission opportunities, and build a reputation for creating memorable learning experiences.',
        'icon' => 'fa-pen-ruler',
    ],
    [
        'title' => 'Organizers',
        'description' => 'Launch polished CTFs faster with dashboards, flexible challenge workflows, and tools that make event setup and delivery easier.',
        'icon' => 'fa-layer-group',
    ],
    [
        'title' => 'Participants',
        'description' => 'Learn by solving, team up with friends, and enjoy practical security challenges that make every event rewarding and fun.',
        'icon' => 'fa-user-group',
    ],
];
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
    :root {
      color-scheme: dark;
    }

    body {
      min-height: 100vh;
      background:
        radial-gradient(circle at top left, rgba(34, 211, 238, 0.2), transparent 28%),
        radial-gradient(circle at bottom right, rgba(250, 204, 21, 0.16), transparent 24%),
        linear-gradient(135deg, #0f172a 0%, #16213e 50%, #0f3460 100%);
    }

    .glass-panel {
      backdrop-filter: blur(12px);
      background: rgba(15, 23, 42, 0.78);
      border: 1px solid rgba(148, 163, 184, 0.18);
      box-shadow: 0 24px 60px rgba(15, 23, 42, 0.32);
    }

    .hero-kicker {
      letter-spacing: 0.16em;
      text-transform: uppercase;
      color: #67e8f9;
    }

    .hero-title {
      font-size: clamp(2.5rem, 5vw, 4.5rem);
      line-height: 1.05;
    }

    .brand-icon {
      font-size: 3.5rem;
      color: #facc15;
    }

    .audience-card {
      height: 100%;
      border-radius: 1.25rem;
    }

    .audience-icon {
      width: 3rem;
      height: 3rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 999px;
      background: rgba(34, 211, 238, 0.14);
      color: #67e8f9;
    }

    .login-card .input-group-text,
    .login-card .form-control {
      background: rgba(15, 23, 42, 0.92);
      color: #fff;
      border-color: rgba(148, 163, 184, 0.25);
    }

    .benefit-list li + li {
      margin-top: 0.75rem;
    }
  </style>
</head>
<body class="text-white">
<div class="container py-5">
  <div class="row align-items-center g-4 g-xl-5">
    <div class="col-lg-7">
      <div class="glass-panel rounded-4 p-4 p-lg-5">
        <p class="hero-kicker fw-semibold mb-3">Create. Host. Learn. Compete.</p>
        <h1 class="hero-title fw-bold mb-3">CTFForge helps every CTF audience feel at home.</h1>
        <p class="lead text-white-50 mb-4">
          <span class="fw-semibold text-white">Authors</span> can earn badges, commissions, and recognition.
          <span class="fw-semibold text-white">Organizers</span> can create and run polished CTFs with less friction.
          <span class="fw-semibold text-white">Participants</span> can learn new skills, solve creative problems, and have fun.
        </p>
        <ul class="benefit-list list-unstyled text-white-50 mb-0">
          <li><i class="fas fa-check-circle text-info me-2"></i>Reward challenge authors for quality content and creativity.</li>
          <li><i class="fas fa-check-circle text-info me-2"></i>Give organizers a faster path from setup to launch day.</li>
          <li><i class="fas fa-check-circle text-info me-2"></i>Make learning cybersecurity social, practical, and enjoyable for participants.</li>
        </ul>
      </div>
      <div class="row row-cols-1 row-cols-md-3 g-3 mt-1">
        <?php foreach ($audiences as $audience): ?>
        <div class="col">
          <div class="glass-panel audience-card p-4">
            <div class="audience-icon mb-3">
              <i class="fas <?= htmlspecialchars($audience['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
            </div>
            <h2 class="h5 mb-2"><?= htmlspecialchars($audience['title'], ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="text-white-50 mb-0"><?= htmlspecialchars($audience['description'], ENT_QUOTES, 'UTF-8') ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="text-center mb-4">
        <i class="fas fa-flag brand-icon"></i>
        <h2 class="fw-bold mt-2 mb-2"><?= APP_NAME ?></h2>
        <p class="text-white-50 mb-0">Capture The Flag Platform</p>
      </div>
      <div class="card glass-panel login-card border-0 rounded-4 shadow-lg">
        <div class="card-body p-4 p-lg-5">
          <h3 class="h4 mb-4 text-center">Sign In</h3>
          <?php if ($error): ?>
          <div class="alert alert-danger py-2"><i class="fas fa-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>
          <form method="POST" action="index.php">
            <?= csrf_field() ?>
            <div class="mb-3">
              <label class="form-label">Username</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
              </div>
            </div>
            <div class="mb-4">
              <label class="form-label">Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" class="form-control" name="password" required>
              </div>
            </div>
            <button type="submit" class="btn btn-warning w-100 fw-bold"><i class="fas fa-sign-in-alt me-1"></i>Login</button>
          </form>
        </div>
      </div>
      <p class="text-center text-white-50 mt-3 small mb-0">Contact an administrator for account access.</p>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
