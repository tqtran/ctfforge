<?php
$pageTitle = $pageTitle ?? APP_NAME;
$user = auth_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> | <?= APP_NAME ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">

  <!-- Navbar -->
  <nav class="app-header navbar navbar-expand bg-body">
    <div class="container-fluid">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" data-lte-toggle="sidebar" href="#"><i class="fas fa-bars"></i></a></li>
      </ul>
      <ul class="navbar-nav ms-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
            <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($user['username'] ?? '') ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><span class="dropdown-item-text text-muted small"><?= htmlspecialchars(ucfirst($user['role'] ?? '')) ?></span></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= APP_URL ?>/logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </nav>

  <!-- Sidebar -->
  <aside class="app-sidebar bg-dark navbar-dark" data-bs-theme="dark">
    <div class="sidebar-brand">
      <a href="<?= APP_URL ?>" class="brand-link">
        <i class="fas fa-flag text-warning me-2"></i>
        <span class="brand-text fw-bold"><?= APP_NAME ?></span>
      </a>
    </div>
    <div class="sidebar-wrapper">
      <nav class="mt-2">
        <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview">
          <?php if ($user && $user['role'] === 'participant'): ?>
          <li class="nav-item">
            <a href="<?= APP_URL ?>/participants/index.php" class="nav-link">
              <i class="nav-icon fas fa-trophy"></i>
              <p>Dashboard</p>
            </a>
          </li>
          <?php elseif ($user && $user['role'] === 'organizer'): ?>
          <li class="nav-item">
            <a href="<?= APP_URL ?>/organizers/index.php" class="nav-link">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= APP_URL ?>/organizers/challenge_new.php" class="nav-link">
              <i class="nav-icon fas fa-plus-circle"></i>
              <p>New Challenge</p>
            </a>
          </li>
          <?php elseif ($user && $user['role'] === 'author'): ?>
          <li class="nav-item">
            <a href="<?= APP_URL ?>/authors/index.php" class="nav-link">
              <i class="nav-icon fas fa-pencil-alt"></i>
              <p>My Challenges</p>
            </a>
          </li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="app-main">
    <div class="app-content-header">
      <div class="container-fluid">
        <div class="row">
          <div class="col-sm-6">
            <h3 class="mb-0"><?= htmlspecialchars($pageTitle) ?></h3>
          </div>
        </div>
      </div>
    </div>
    <div class="app-content">
      <div class="container-fluid">

        <?php $flash = get_flash(); if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show">
          <?= htmlspecialchars($flash['message']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
