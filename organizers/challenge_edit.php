<?php
require_once __DIR__ . '/../framework/bootstrap.php';
require_role('organizer');

$user = auth_user();
$challengeModel = new Challenge();
$id = (int)($_GET['id'] ?? 0);
$challenge = $challengeModel->findById($id);

if (!$challenge) {
    flash('Challenge not found.', 'danger');
    redirect(APP_URL . '/organizers/index.php');
}

$datasets = $challengeModel->getDatasets($id);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Invalid security token.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $template = trim($_POST['template'] ?? '');
        $points = (int)($_POST['points'] ?? 100);
        $postDatasets = $_POST['datasets'] ?? [];

        if (!$title || !$template || empty($postDatasets)) {
            $error = 'Title, template, and at least one dataset row are required.';
        } else {
            $challengeModel->update($id, $title, $description, $template, $points);
            $challengeModel->deleteDatasets($id);

            foreach ($postDatasets as $i => $ds) {
                $dataValue = trim($ds['data_value'] ?? '');
                $answers = trim($ds['acceptable_answers'] ?? '');
                if (!$dataValue || !$answers) continue;

                $imagePath = $ds['existing_image'] ?? null;
                if ($challenge['plugin_type'] === 'image_question' && isset($_FILES['images']['tmp_name'][$i]) && $_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmpPath = $_FILES['images']['tmp_name'][$i];
                    $ext = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
                    $allowedExts = ['jpg','jpeg','png','gif','webp'];
                    $allowedMimes = ['image/jpeg','image/png','image/gif','image/webp'];
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($tmpPath);
                    if (in_array($ext, $allowedExts, true) && in_array($mime, $allowedMimes, true)) {
                        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
                        $dest = UPLOAD_DIR . $filename;
                        if (move_uploaded_file($tmpPath, $dest)) {
                            $imagePath = $filename;
                        }
                    }
                }

                $challengeModel->addDataset($id, $dataValue, $answers, $imagePath);
            }

            flash('Challenge updated successfully!', 'success');
            redirect(APP_URL . '/organizers/index.php');
        }
    }
}

$plugin = get_plugin($challenge['plugin_type']);
$formData = [
    'template' => $_POST['template'] ?? $challenge['template'],
    'datasets' => !empty($_POST['datasets']) ? $_POST['datasets'] : $datasets,
];

$pageTitle = 'Edit Challenge';
include __DIR__ . '/../framework/views/header.php';
?>

<div class="card">
  <div class="card-header">
    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Challenge: <?= htmlspecialchars($challenge['title']) ?></h5>
  </div>
  <div class="card-body">
    <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="alert alert-info py-2">
      <i class="fas fa-info-circle me-1"></i>Type: <strong><?= htmlspecialchars($challenge['plugin_type']) ?></strong> (cannot be changed after creation)
    </div>

    <form method="POST" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="row mb-3">
        <div class="col-md-8">
          <label class="form-label">Challenge Title <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($_POST['title'] ?? $challenge['title']) ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Points</label>
          <input type="number" class="form-control" name="points" value="<?= (int)($_POST['points'] ?? $challenge['points']) ?>" min="1">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea class="form-control" name="description" rows="2"><?= htmlspecialchars($_POST['description'] ?? $challenge['description']) ?></textarea>
      </div>

      <hr>
      <h6 class="mb-3"><i class="fas fa-database me-1"></i>Plugin Configuration</h6>

      <?= $plugin ? $plugin->renderForm($formData) : '' ?>

      <div class="mt-4">
        <button type="submit" class="btn btn-success me-2"><i class="fas fa-save me-1"></i>Update Challenge</button>
        <a href="<?= APP_URL ?>/organizers/index.php" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script>
let datasetCount = document.querySelectorAll('.dataset-row').length;
const pluginType = '<?= htmlspecialchars($challenge['plugin_type']) ?>';

function addDatasetRow() {
    const container = document.getElementById('datasets-container');
    const index = datasetCount;
    let html = '';
    if (pluginType === 'fill_blank') {
        html = `<div class="dataset-row border rounded p-3 mb-2" data-index="${index}">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>Dataset Row #<span class="row-num">${index + 1}</span></strong>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeDatasetRow(this)"><i class="fas fa-trash"></i></button>
            </div>
            <div class="mb-2"><label class="form-label">Data Value</label>
            <input type="text" class="form-control" name="datasets[${index}][data_value]" required></div>
            <div class="mb-2"><label class="form-label">Acceptable Answers <small class="text-muted">(comma-separated)</small></label>
            <input type="text" class="form-control" name="datasets[${index}][acceptable_answers]" required></div>
        </div>`;
    } else {
        html = `<div class="dataset-row border rounded p-3 mb-2" data-index="${index}">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>Dataset Row #<span class="row-num">${index + 1}</span></strong>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeDatasetRow(this)"><i class="fas fa-trash"></i></button>
            </div>
            <div class="mb-2"><label class="form-label">Data Value <small class="text-muted">(descriptive text)</small></label>
            <input type="text" class="form-control" name="datasets[${index}][data_value]" required></div>
            <div class="mb-2"><label class="form-label">Image Upload</label>
            <input type="file" class="form-control" name="images[${index}]" accept="image/*"></div>
            <div class="mb-2"><label class="form-label">Acceptable Answers <small class="text-muted">(comma-separated)</small></label>
            <input type="text" class="form-control" name="datasets[${index}][acceptable_answers]" required></div>
        </div>`;
    }
    container.insertAdjacentHTML('beforeend', html);
    datasetCount++;
    renumberRows();
}

function removeDatasetRow(btn) {
    btn.closest('.dataset-row').remove();
    renumberRows();
}

function renumberRows() {
    document.querySelectorAll('.dataset-row').forEach((row, i) => {
        const span = row.querySelector('.row-num');
        if (span) span.textContent = i + 1;
    });
}
</script>

<?php include __DIR__ . '/../framework/views/footer.php'; ?>
