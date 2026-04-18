<?php
require_once __DIR__ . '/../framework/bootstrap.php';
require_role('organizer');

$user = auth_user();
$challengeModel = new Challenge();
$competitionModel = new Competition();

// Handle competition creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!csrf_verify()) {
        flash('Invalid security token.', 'danger');
        redirect(APP_URL . '/organizers/index.php');
    }
    if ($_POST['action'] === 'create_competition') {
        $name = trim($_POST['comp_name'] ?? '');
        $desc = trim($_POST['comp_desc'] ?? '');
        $start = $_POST['start_time'] ?: null;
        $end = $_POST['end_time'] ?: null;
        if ($name) {
            $competitionModel->create($name, $desc, $start, $end, (int)$user['id']);
            flash('Competition created successfully!', 'success');
        } else {
            flash('Competition name is required.', 'danger');
        }
        redirect(APP_URL . '/organizers/index.php');
    }
    if ($_POST['action'] === 'add_challenge_to_competition') {
        $compId = (int)($_POST['competition_id'] ?? 0);
        $chalId = (int)($_POST['challenge_id'] ?? 0);
        if ($compId && $chalId) {
            $competitionModel->addChallenge($compId, $chalId);
            flash('Challenge added to competition.', 'success');
        }
        redirect(APP_URL . '/organizers/index.php');
    }
    if ($_POST['action'] === 'remove_challenge_from_competition') {
        $compId = (int)($_POST['competition_id'] ?? 0);
        $chalId = (int)($_POST['challenge_id'] ?? 0);
        if ($compId && $chalId) {
            $competitionModel->removeChallenge($compId, $chalId);
            flash('Challenge removed from competition.', 'success');
        }
        redirect(APP_URL . '/organizers/index.php');
    }
}

$challenges = $challengeModel->findAll();
$competitions = $competitionModel->findAll();
$allChallenges = $challengeModel->findAll();

$pageTitle = 'Organizer Dashboard';
include __DIR__ . '/../framework/views/header.php';
?>

<!-- Quick Stats -->
<div class="row mb-4">
  <div class="col-md-4">
    <div class="card bg-primary text-white">
      <div class="card-body d-flex align-items-center">
        <i class="fas fa-puzzle-piece fa-3x me-3 opacity-75"></i>
        <div><div class="fs-4 fw-bold"><?= count($challenges) ?></div><div>Total Challenges</div></div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card bg-success text-white">
      <div class="card-body d-flex align-items-center">
        <i class="fas fa-flag fa-3x me-3 opacity-75"></i>
        <div><div class="fs-4 fw-bold"><?= count($competitions) ?></div><div>Competitions</div></div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card bg-warning text-dark">
      <div class="card-body d-flex align-items-center">
        <i class="fas fa-plus-circle fa-3x me-3 opacity-75"></i>
        <div><a href="<?= APP_URL ?>/organizers/challenge_new.php" class="text-dark text-decoration-none"><div class="fs-5 fw-bold">New Challenge</div></a></div>
      </div>
    </div>
  </div>
</div>

<!-- Challenges Table -->
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Challenges</h5>
    <a href="<?= APP_URL ?>/organizers/challenge_new.php" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New Challenge</a>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-dark">
          <tr><th>Title</th><th>Type</th><th>Points</th><th>Author</th><th>Created</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php if (empty($challenges)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No challenges yet. <a href="<?= APP_URL ?>/organizers/challenge_new.php">Create one!</a></td></tr>
          <?php else: ?>
          <?php foreach ($challenges as $ch): ?>
          <tr>
            <td><?= htmlspecialchars($ch['title']) ?></td>
            <td><span class="badge bg-secondary"><?= htmlspecialchars($ch['plugin_type']) ?></span></td>
            <td><?= (int)$ch['points'] ?></td>
            <td><?= htmlspecialchars($ch['author'] ?? 'N/A') ?></td>
            <td><?= date('Y-m-d', strtotime($ch['created_at'])) ?></td>
            <td>
              <a href="<?= APP_URL ?>/organizers/challenge_edit.php?id=<?= $ch['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
              <a href="<?= APP_URL ?>/organizers/challenge_delete.php?id=<?= $ch['id'] ?>" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Competitions -->
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="fas fa-flag me-2"></i>Competitions</h5>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#newCompModal">
      <i class="fas fa-plus me-1"></i>New Competition
    </button>
  </div>
  <div class="card-body">
    <?php if (empty($competitions)): ?>
    <p class="text-muted">No competitions yet.</p>
    <?php else: ?>
    <div class="accordion" id="compAccordion">
      <?php foreach ($competitions as $comp): ?>
      <?php $compChallenges = $competitionModel->getChallenges((int)$comp['id']); ?>
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#comp<?= $comp['id'] ?>">
            <i class="fas fa-flag me-2 text-warning"></i>
            <?= htmlspecialchars($comp['name']) ?>
            <span class="badge bg-secondary ms-2"><?= count($compChallenges) ?> challenges</span>
            <?php if ($comp['start_time'] || $comp['end_time']): ?>
            <small class="text-muted ms-2"><?= $comp['start_time'] ? date('Y-m-d', strtotime($comp['start_time'])) : '∞' ?> – <?= $comp['end_time'] ? date('Y-m-d', strtotime($comp['end_time'])) : '∞' ?></small>
            <?php endif; ?>
          </button>
        </h2>
        <div id="comp<?= $comp['id'] ?>" class="accordion-collapse collapse">
          <div class="accordion-body">
            <?php if ($comp['description']): ?><p class="text-muted"><?= htmlspecialchars($comp['description']) ?></p><?php endif; ?>
            <h6>Challenges in this competition:</h6>
            <?php if (empty($compChallenges)): ?>
            <p class="text-muted small">No challenges added yet.</p>
            <?php else: ?>
            <ul class="list-group list-group-flush mb-3">
              <?php foreach ($compChallenges as $cc): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <?= htmlspecialchars($cc['title']) ?>
                <form method="POST" class="d-inline" onsubmit="return confirm('Remove challenge?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="remove_challenge_from_competition">
                  <input type="hidden" name="competition_id" value="<?= $comp['id'] ?>">
                  <input type="hidden" name="challenge_id" value="<?= $cc['id'] ?>">
                  <button class="btn btn-outline-danger btn-sm"><i class="fas fa-times"></i></button>
                </form>
              </li>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <form method="POST" class="d-flex gap-2">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="add_challenge_to_competition">
              <input type="hidden" name="competition_id" value="<?= $comp['id'] ?>">
              <select name="challenge_id" class="form-select form-select-sm" required>
                <option value="">-- Select Challenge --</option>
                <?php foreach ($allChallenges as $ac): ?>
                <option value="<?= $ac['id'] ?>"><?= htmlspecialchars($ac['title']) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn-primary btn-sm text-nowrap"><i class="fas fa-plus me-1"></i>Add</button>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- New Competition Modal -->
<div class="modal fade" id="newCompModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create_competition">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-flag me-2"></i>New Competition</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="comp_name" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="comp_desc" rows="3"></textarea>
        </div>
        <div class="row">
          <div class="col">
            <label class="form-label">Start Time</label>
            <input type="datetime-local" class="form-control" name="start_time">
          </div>
          <div class="col">
            <label class="form-label">End Time</label>
            <input type="datetime-local" class="form-control" name="end_time">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Create</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../framework/views/footer.php'; ?>
