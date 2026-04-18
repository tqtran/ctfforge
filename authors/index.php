<?php
require_once __DIR__ . '/../framework/bootstrap.php';
require_role('author');

$user = auth_user();
$challengeModel = new Challenge();
$submissionModel = new Submission();

$challenges = $challengeModel->findByAuthor((int)$user['id']);

$pageTitle = 'Author Dashboard';
include __DIR__ . '/../framework/views/header.php';
?>

<div class="row mb-4">
  <div class="col-md-6">
    <div class="card bg-primary text-white">
      <div class="card-body d-flex align-items-center">
        <i class="fas fa-puzzle-piece fa-3x me-3 opacity-75"></i>
        <div><div class="fs-4 fw-bold"><?= count($challenges) ?></div><div>My Challenges</div></div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h5 class="mb-0"><i class="fas fa-list me-2"></i>My Challenges & Stats</h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-dark">
          <tr><th>Title</th><th>Type</th><th>Points</th><th>Total Submissions</th><th>Correct</th><th>Created</th></tr>
        </thead>
        <tbody>
          <?php if (empty($challenges)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No challenges authored yet.</td></tr>
          <?php else: ?>
          <?php foreach ($challenges as $ch):
              $stats = $submissionModel->getStatsByChallenge((int)$ch['id']);
          ?>
          <tr>
            <td><?= htmlspecialchars($ch['title']) ?></td>
            <td><span class="badge bg-secondary"><?= htmlspecialchars($ch['plugin_type']) ?></span></td>
            <td><?= (int)$ch['points'] ?></td>
            <td><?= (int)($stats['total'] ?? 0) ?></td>
            <td>
              <span class="badge bg-success"><?= (int)($stats['correct'] ?? 0) ?></span>
              <?php if ((int)($stats['total'] ?? 0) > 0): ?>
              <small class="text-muted">(<?= round((int)$stats['correct'] / (int)$stats['total'] * 100) ?>%)</small>
              <?php endif; ?>
            </td>
            <td><?= date('Y-m-d', strtotime($ch['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../framework/views/footer.php'; ?>
