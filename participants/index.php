<?php
require_once __DIR__ . '/../framework/bootstrap.php';
require_role('participant');

$user = auth_user();
$competitionModel = new Competition();
$challengeModel = new Challenge();
$submissionModel = new Submission();

$competitions = $competitionModel->findActive();
$totalPoints = $submissionModel->totalPoints((int)$user['id']);
$correctCount = $submissionModel->countCorrect((int)$user['id']);

$pageTitle = 'Participant Dashboard';
include __DIR__ . '/../framework/views/header.php';
?>

<!-- Stats Cards -->
<div class="row mb-4">
  <div class="col-md-4">
    <div class="card bg-primary text-white">
      <div class="card-body d-flex align-items-center">
        <i class="fas fa-star fa-3x me-3 opacity-75"></i>
        <div>
          <div class="fs-4 fw-bold"><?= $totalPoints ?></div>
          <div>Total Points</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card bg-success text-white">
      <div class="card-body d-flex align-items-center">
        <i class="fas fa-check-circle fa-3x me-3 opacity-75"></i>
        <div>
          <div class="fs-4 fw-bold"><?= $correctCount ?></div>
          <div>Correct Answers</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card bg-info text-white">
      <div class="card-body d-flex align-items-center">
        <i class="fas fa-trophy fa-3x me-3 opacity-75"></i>
        <div>
          <div class="fs-4 fw-bold"><?= count($competitions) ?></div>
          <div>Active Competitions</div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if (empty($competitions)): ?>
<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No active competitions right now. Check back later!</div>
<?php else: ?>
<?php foreach ($competitions as $comp):
    $challenges = $competitionModel->getChallenges((int)$comp['id']);
?>
<div class="card mb-4">
  <div class="card-header bg-dark text-white">
    <h5 class="mb-0"><i class="fas fa-flag me-2"></i><?= htmlspecialchars($comp['name']) ?></h5>
    <?php if ($comp['description']): ?><small class="opacity-75"><?= htmlspecialchars($comp['description']) ?></small><?php endif; ?>
  </div>
  <div class="card-body">
    <?php if (empty($challenges)): ?>
    <p class="text-muted">No challenges in this competition yet.</p>
    <?php else: ?>
    <div class="row">
      <?php foreach ($challenges as $challenge):
          $plugin = get_plugin($challenge['plugin_type']);
          $datasets = $challengeModel->getDatasets((int)$challenge['id']);
          $solved = $submissionModel->hasCorrectSubmission((int)$user['id'], (int)$challenge['id']);
          if (empty($datasets)) continue;
          // Deterministic but unpredictable dataset assignment per user+challenge
          $datasetIndex = abs(crc32($user['id'] . ':' . $challenge['id'])) % count($datasets);
          $dataset = $datasets[$datasetIndex];
          $dataset['template'] = $challenge['template'];
      ?>
      <div class="col-md-6 mb-3">
        <div class="card <?= $solved ? 'border-success' : '' ?>">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-puzzle-piece me-1"></i><?= htmlspecialchars($challenge['title']) ?></span>
            <span class="badge <?= $solved ? 'bg-success' : 'bg-secondary' ?>">
              <?= $solved ? '<i class="fas fa-check me-1"></i>Solved' : $challenge['points'] . ' pts' ?>
            </span>
          </div>
          <div class="card-body">
            <?php if ($challenge['description']): ?>
            <p class="text-muted small"><?= htmlspecialchars($challenge['description']) ?></p>
            <?php endif; ?>
            <?php if (!$solved && $plugin): ?>
            <form method="POST" action="<?= APP_URL ?>/participants/submit.php">
              <?= csrf_field() ?>
              <input type="hidden" name="challenge_id" value="<?= $challenge['id'] ?>">
              <input type="hidden" name="dataset_id" value="<?= $dataset['id'] ?>">
              <?= $plugin->renderQuestion($dataset) ?>
              <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane me-1"></i>Submit</button>
            </form>
            <?php elseif ($solved): ?>
            <div class="text-success"><i class="fas fa-trophy me-1"></i>You solved this challenge!</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/../framework/views/footer.php'; ?>
