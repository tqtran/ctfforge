<?php
require_once __DIR__ . '/../framework/bootstrap.php';
require_role('admin');

function admin_config_field_value(array $field, array $configValues, array $secretValues): mixed {
    $source = ($field['source'] ?? 'config') === 'secrets' ? 'secrets' : 'config';
    $path = (string)($field['path'] ?? '');
    if ($path === '') {
        return '';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_configuration') {
        $posted = $_POST[$source][$path] ?? null;
        if (($field['secret'] ?? false) && ($posted === null || $posted === '')) {
            return '';
        }
        return $posted ?? '';
    }

    $values = $source === 'secrets' ? $secretValues : $configValues;
    return ConfigRepository::getValue($values, $path, '');
}

$currentUser = auth_user();
$configRepository = app_config_repository();
$userModel = new User();
$challengeModel = new Challenge();
$competitionModel = new Competition();
$submissionModel = new Submission();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!csrf_verify()) {
        flash('Invalid security token.', 'danger');
        redirect(APP_URL . '/admin/index.php');
    }

    $action = $_POST['action'];

    if ($action === 'create_user') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'participant';

        if ($username === '' || $email === '' || $password === '') {
            flash('Username, email, and password are required to create a user.', 'danger');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('Please enter a valid email address.', 'danger');
        } elseif (!in_array($role, User::availableRoles(), true)) {
            flash('Invalid role selected.', 'danger');
        } else {
            try {
                $userModel->create($username, $email, $password, $role);
                flash('User created successfully.', 'success');
            } catch (PDOException) {
                flash('Unable to create user. Check for duplicate usernames or emails.', 'danger');
            }
        }

        redirect(APP_URL . '/admin/index.php');
    }

    if ($action === 'update_user') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'participant';
        $password = trim($_POST['password'] ?? '');
        $managedUser = $userModel->findById($userId);

        if (!$managedUser) {
            flash('User not found.', 'danger');
        } elseif ($username === '' || $email === '') {
            flash('Username and email are required.', 'danger');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('Please enter a valid email address.', 'danger');
        } elseif (!in_array($role, User::availableRoles(), true)) {
            flash('Invalid role selected.', 'danger');
        } elseif ($userId === (int)$currentUser['id'] && $role !== 'admin') {
            flash('You cannot remove your own admin access.', 'danger');
        } else {
            try {
                $userModel->update($userId, $username, $email, $role, $password !== '' ? $password : null);
                flash('User updated successfully.', 'success');
            } catch (PDOException) {
                flash('Unable to update user. Check for duplicate usernames or emails.', 'danger');
            }
        }

        redirect(APP_URL . '/admin/index.php');
    }

    if ($action === 'delete_user') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId === (int)$currentUser['id']) {
            flash('You cannot delete your own account.', 'danger');
        } elseif (!$userModel->findById($userId)) {
            flash('User not found.', 'danger');
        } else {
            try {
                $userModel->delete($userId);
                flash('User deleted successfully.', 'success');
            } catch (PDOException) {
                flash('Unable to delete this user while related records still exist.', 'danger');
            }
        }

        redirect(APP_URL . '/admin/index.php');
    }

    if ($action === 'save_configuration') {
        $configValues = $configRepository->loadConfig();
        $secretValues = $configRepository->loadSecrets();
        $template = $configRepository->loadTemplate();

        foreach (($template['sections'] ?? []) as $section) {
            foreach (($section['fields'] ?? []) as $field) {
                $path = (string)($field['path'] ?? '');
                if ($path === '') {
                    continue;
                }

                $source = ($field['source'] ?? 'config') === 'secrets' ? 'secrets' : 'config';
                $type = (string)($field['type'] ?? 'text');
                $isSecret = (bool)($field['secret'] ?? false);
                $postedValue = $_POST[$source][$path] ?? '';
                $existingValue = ConfigRepository::getValue($source === 'config' ? $configValues : $secretValues, $path, '');

                if ($type === 'boolean') {
                    $normalizedValue = ConfigRepository::normalizeBoolean($postedValue);
                } elseif ($type === 'number') {
                    $normalizedValue = trim((string)$postedValue) === '' ? '' : (int)$postedValue;
                } else {
                    $normalizedValue = trim((string)$postedValue);
                }

                if (($field['required'] ?? false) && $type !== 'boolean' && $normalizedValue === '') {
                    flash(((string)($field['label'] ?? $path)) . ' is required.', 'danger');
                    redirect(APP_URL . '/admin/index.php#configuration');
                }

                if ($isSecret && $normalizedValue === '') {
                    $normalizedValue = $existingValue;
                }

                if ($source === 'config') {
                    ConfigRepository::setValue($configValues, $path, $normalizedValue);
                } else {
                    ConfigRepository::setValue($secretValues, $path, $normalizedValue);
                }
            }
        }

        $configRepository->saveConfig($configValues);
        $configRepository->saveSecrets($secretValues);
        flash('Configuration saved successfully.', 'success');
        redirect(APP_URL . '/admin/index.php#configuration');
    }
}

$users = $userModel->findAll();
$roleCounts = $userModel->countByRole();
$stats = [
    'users' => $userModel->countAll(),
    'challenges' => $challengeModel->countAll(),
    'competitions' => $competitionModel->countAll(),
    'submissions' => $submissionModel->countAll(),
];
$recentChallenges = array_slice($challengeModel->findAll(), 0, 5);
$recentCompetitions = array_slice($competitionModel->findAll(), 0, 5);
$configValues = $configRepository->loadConfig();
$secretValues = $configRepository->loadSecrets();
$configTemplate = $configRepository->loadTemplate();

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../framework/views/header.php';
?>

<div class="row mb-4">
  <div class="col-md-3">
    <div class="card bg-primary text-white">
      <div class="card-body d-flex align-items-center">
        <i class="fas fa-users fa-3x me-3 opacity-75"></i>
        <div><div class="fs-4 fw-bold"><?= $stats['users'] ?></div><div>Users</div></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card bg-success text-white">
      <div class="card-body d-flex align-items-center">
        <i class="fas fa-puzzle-piece fa-3x me-3 opacity-75"></i>
        <div><div class="fs-4 fw-bold"><?= $stats['challenges'] ?></div><div>Challenges</div></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card bg-warning text-dark">
      <div class="card-body d-flex align-items-center">
        <i class="fas fa-flag fa-3x me-3 opacity-75"></i>
        <div><div class="fs-4 fw-bold"><?= $stats['competitions'] ?></div><div>Competitions</div></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card bg-dark text-white">
      <div class="card-body d-flex align-items-center">
        <i class="fas fa-paper-plane fa-3x me-3 opacity-75"></i>
        <div><div class="fs-4 fw-bold"><?= $stats['submissions'] ?></div><div>Submissions</div></div>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-sitemap me-2"></i>System Management</h5>
        <span class="badge bg-secondary">Admin access</span>
      </div>
      <div class="card-body">
        <div class="d-flex flex-wrap gap-2 mb-4">
          <a href="<?= APP_URL ?>/organizers/index.php" class="btn btn-primary"><i class="fas fa-tools me-1"></i>Organizer Dashboard</a>
          <a href="<?= APP_URL ?>/organizers/challenge_new.php" class="btn btn-outline-primary"><i class="fas fa-plus me-1"></i>New Challenge</a>
          <a href="<?= APP_URL ?>/authors/index.php" class="btn btn-outline-secondary"><i class="fas fa-pen me-1"></i>Author Dashboard</a>
          <a href="<?= APP_URL ?>/participants/index.php" class="btn btn-outline-success"><i class="fas fa-trophy me-1"></i>Participant Dashboard</a>
          <a href="#configuration" class="btn btn-outline-dark"><i class="fas fa-sliders-h me-1"></i>Configuration</a>
        </div>
        <div class="row">
          <div class="col-md-6">
            <h6 class="text-muted text-uppercase small mb-3">Recent Challenges</h6>
            <?php if (empty($recentChallenges)): ?>
            <p class="text-muted mb-0">No challenges created yet.</p>
            <?php else: ?>
            <div class="list-group list-group-flush">
              <?php foreach ($recentChallenges as $challenge): ?>
              <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-semibold"><?= htmlspecialchars($challenge['title']) ?></div>
                  <small class="text-muted"><?= htmlspecialchars($challenge['author'] ?? 'Unknown author') ?></small>
                </div>
                <span class="badge bg-secondary"><?= (int)$challenge['points'] ?> pts</span>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <h6 class="text-muted text-uppercase small mb-3">Recent Competitions</h6>
            <?php if (empty($recentCompetitions)): ?>
            <p class="text-muted mb-0">No competitions created yet.</p>
            <?php else: ?>
            <div class="list-group list-group-flush">
              <?php foreach ($recentCompetitions as $competition): ?>
              <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-semibold"><?= htmlspecialchars($competition['name']) ?></div>
                  <small class="text-muted"><?= htmlspecialchars($competition['creator'] ?? 'Unknown creator') ?></small>
                </div>
                <span class="badge bg-info text-dark"><?= $competitionModel->countChallenges((int)$competition['id']) ?> challenges</span>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>Role Distribution</h5>
      </div>
      <div class="card-body">
        <?php foreach ($roleCounts as $role => $count): ?>
        <div class="d-flex justify-content-between align-items-center border rounded px-3 py-2 mb-2">
          <span class="fw-semibold"><?= htmlspecialchars(ucfirst($role)) ?></span>
          <span class="badge bg-dark"><?= $count ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Create User</h5>
      </div>
      <div class="card-body">
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="create_user">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" name="username" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Role</label>
            <select class="form-select" name="role">
              <?php foreach (User::availableRoles() as $role): ?>
              <option value="<?= htmlspecialchars($role) ?>"><?= htmlspecialchars(ucfirst($role)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-success w-100"><i class="fas fa-save me-1"></i>Create User</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-users-cog me-2"></i>User Management</h5>
        <span class="badge bg-secondary"><?= count($users) ?> accounts</span>
      </div>
      <div class="card-body">
        <?php if (empty($users)): ?>
        <p class="text-muted mb-0">No users available.</p>
        <?php else: ?>
        <div class="accordion" id="userAccordion">
          <?php foreach ($users as $index => $managedUser): ?>
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#user<?= (int)$managedUser['id'] ?>">
                <span class="fw-semibold me-2"><?= htmlspecialchars($managedUser['username']) ?></span>
                <span class="badge bg-secondary me-2"><?= htmlspecialchars($managedUser['role']) ?></span>
                <?php if ((int)$managedUser['id'] === (int)$currentUser['id']): ?>
                <span class="badge bg-warning text-dark">You</span>
                <?php endif; ?>
              </button>
            </h2>
            <div id="user<?= (int)$managedUser['id'] ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#userAccordion">
              <div class="accordion-body">
                <form method="POST" class="row g-3">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="update_user">
                  <input type="hidden" name="user_id" value="<?= (int)$managedUser['id'] ?>">
                  <div class="col-md-4">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($managedUser['username']) ?>" required>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($managedUser['email']) ?>" required>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Role</label>
                    <select class="form-select" name="role">
                      <?php foreach (User::availableRoles() as $role): ?>
                      <option value="<?= htmlspecialchars($role) ?>" <?= $managedUser['role'] === $role ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($role)) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-8">
                    <label class="form-label">New Password</label>
                    <input type="password" class="form-control" name="password" placeholder="Leave blank to keep the current password">
                  </div>
                  <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i>Save Changes</button>
                  </div>
                </form>
                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                  <small class="text-muted">Created <?= htmlspecialchars(date('Y-m-d H:i', strtotime($managedUser['created_at']))) ?></small>
                  <?php if ((int)$managedUser['id'] !== (int)$currentUser['id']): ?>
                  <form method="POST" onsubmit="return confirm('Delete this user account?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="<?= (int)$managedUser['id'] ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash me-1"></i>Delete User</button>
                  </form>
                  <?php else: ?>
                  <small class="text-muted">Your admin account cannot be deleted here.</small>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="card mb-4" id="configuration">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i>Configuration</h5>
    <span class="text-muted small">Managed by <code>framework/config/config.template.yaml</code></span>
  </div>
  <div class="card-body">
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_configuration">
      <?php foreach (($configTemplate['sections'] ?? []) as $section): ?>
      <div class="border rounded p-3 mb-3">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div>
            <h6 class="mb-1"><?= htmlspecialchars((string)($section['title'] ?? 'Configuration Section')) ?></h6>
            <?php if (!empty($section['description'])): ?>
            <p class="text-muted small mb-0"><?= htmlspecialchars((string)$section['description']) ?></p>
            <?php endif; ?>
          </div>
        </div>
        <div class="row">
          <?php foreach (($section['fields'] ?? []) as $field): ?>
          <?php
            $fieldType = (string)($field['type'] ?? 'text');
            $fieldSource = ($field['source'] ?? 'config') === 'secrets' ? 'secrets' : 'config';
            $fieldPath = (string)($field['path'] ?? '');
            $fieldName = $fieldSource . '[' . $fieldPath . ']';
            $fieldValue = admin_config_field_value($field, $configValues, $secretValues);
            $isBoolean = $fieldType === 'boolean';
            $isSecret = (bool)($field['secret'] ?? false);
            $inputType = match ($fieldType) {
                'url' => 'url',
                'email' => 'email',
                'number' => 'number',
                'password' => 'password',
                default => 'text',
            };
          ?>
          <div class="col-md-6 mb-3">
            <label class="form-label"><?= htmlspecialchars((string)($field['label'] ?? $fieldPath)) ?></label>
            <?php if ($isBoolean): ?>
            <select class="form-select" name="<?= htmlspecialchars($fieldName) ?>">
              <option value="1" <?= ConfigRepository::normalizeBoolean($fieldValue) ? 'selected' : '' ?>>Enabled</option>
              <option value="0" <?= !ConfigRepository::normalizeBoolean($fieldValue) ? 'selected' : '' ?>>Disabled</option>
            </select>
            <?php else: ?>
            <input
              type="<?= htmlspecialchars($inputType) ?>"
              class="form-control"
              name="<?= htmlspecialchars($fieldName) ?>"
              value="<?= $isSecret ? '' : htmlspecialchars((string)$fieldValue) ?>"
              <?= !empty($field['required']) ? 'required' : '' ?>
              <?= $isSecret ? 'placeholder="Leave blank to keep the current value"' : '' ?>
            >
            <?php endif; ?>
            <?php if (!empty($field['help'])): ?>
            <div class="form-text"><?= htmlspecialchars((string)$field['help']) ?></div>
            <?php elseif ($isSecret): ?>
            <div class="form-text">Stored in <code>framework/secrets/db.yaml</code>.</div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-dark"><i class="fas fa-save me-1"></i>Save Configuration</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../framework/views/footer.php'; ?>
