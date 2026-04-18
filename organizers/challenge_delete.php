<?php
require_once __DIR__ . '/../framework/bootstrap.php';
require_role('organizer');

$challengeModel = new Challenge();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$challenge = $challengeModel->findById($id);

if (!$challenge) {
    flash('Challenge not found.', 'danger');
    redirect(APP_URL . '/organizers/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('Invalid security token.', 'danger');
        redirect(APP_URL . '/organizers/index.php');
    }
    $challengeModel->delete($id);
    flash('Challenge "' . $challenge['title'] . '" deleted.', 'success');
    redirect(APP_URL . '/organizers/index.php');
}

$pageTitle = 'Delete Challenge';
include __DIR__ . '/../framework/views/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card border-danger">
      <div class="card-header bg-danger text-white">
        <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete</h5>
      </div>
      <div class="card-body">
        <p>Are you sure you want to delete the challenge:</p>
        <h5 class="text-danger"><?= htmlspecialchars($challenge['title']) ?></h5>
        <p class="text-muted small">This will also delete all datasets and submission records associated with this challenge.</p>
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= $id ?>">
          <button type="submit" class="btn btn-danger me-2"><i class="fas fa-trash me-1"></i>Yes, Delete</button>
          <a href="<?= APP_URL ?>/organizers/index.php" class="btn btn-secondary">Cancel</a>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../framework/views/footer.php'; ?>
