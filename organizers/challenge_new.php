<?php
require_once __DIR__ . '/../framework/bootstrap.php';
require_role('organizer');

$user = auth_user();
$error = '';
$selectedType = $_GET['type'] ?? $_POST['plugin_type'] ?? 'fill_blank';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_challenge'])) {
    if (!csrf_verify()) {
        $error = 'Invalid security token.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $pluginType = $_POST['plugin_type'] ?? '';
        $template = trim($_POST['template'] ?? '');
        $points = (int)($_POST['points'] ?? 100);
        $datasets = $_POST['datasets'] ?? [];

        if (!$title || !$template || empty($datasets)) {
            $error = 'Title, template, and at least one dataset row are required.';
        } else {
            $plugin = get_plugin($pluginType);
            if (!$plugin) {
                $error = 'Invalid plugin type.';
            } else {
                $challengeModel = new Challenge();
                $challengeId = $challengeModel->create($title, $description, $pluginType, $template, $points, (int)$user['id']);

                foreach ($datasets as $i => $ds) {
                    $dataValue = trim($ds['data_value'] ?? '');
                    $answers = trim($ds['acceptable_answers'] ?? '');
                    if (!$dataValue || !$answers) continue;

                    $imagePath = null;
                    if ($pluginType === 'image_question' && isset($_FILES['images']['tmp_name'][$i]) && $_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
                        $allowed = ['jpg','jpeg','png','gif','webp'];
                        if (in_array($ext, $allowed, true)) {
                            $filename = 'img_' . $challengeId . '_' . $i . '_' . time() . '.' . $ext;
                            $dest = UPLOAD_DIR . $filename;
                            if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $dest)) {
                                $imagePath = $filename;
                            }
                        }
                    }

                    $challengeModel->addDataset($challengeId, $dataValue, $answers, $imagePath);
                }

                flash('Challenge created successfully!', 'success');
                redirect(APP_URL . '/organizers/index.php');
            }
        }
    }
}

$plugin = get_plugin($selectedType);
$pageTitle = 'New Challenge';
include __DIR__ . '/../framework/views/header.php';
?>

<div class="card">
  <div class="card-header">
    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Create New Challenge</h5>
  </div>
  <div class="card-body">
    <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Plugin type selector -->
    <div class="mb-4">
      <label class="form-label fw-bold">Challenge Type</label>
      <div class="d-flex gap-3">
        <a href="?type=fill_blank" class="btn <?= $selectedType === 'fill_blank' ? 'btn-primary' : 'btn-outline-primary' ?>">
          <i class="fas fa-pen me-1"></i>Fill in the Blank
        </a>
        <a href="?type=image_question" class="btn <?= $selectedType === 'image_question' ? 'btn-primary' : 'btn-outline-primary' ?>">
          <i class="fas fa-image me-1"></i>Image Question
        </a>
      </div>
    </div>

    <form method="POST" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="plugin_type" value="<?= htmlspecialchars($selectedType) ?>">
      <input type="hidden" name="save_challenge" value="1">

      <div class="row mb-3">
        <div class="col-md-8">
          <label class="form-label">Challenge Title <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Points</label>
          <input type="number" class="form-control" name="points" value="<?= (int)($_POST['points'] ?? 100) ?>" min="1">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea class="form-control" name="description" rows="2"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
      </div>

      <hr>
      <h6 class="mb-3"><i class="fas fa-database me-1"></i>Plugin Configuration</h6>

      <?= $plugin ? $plugin->renderForm() : '' ?>

      <div class="mt-4">
        <button type="submit" class="btn btn-success me-2"><i class="fas fa-save me-1"></i>Save Challenge</button>
        <a href="<?= APP_URL ?>/organizers/index.php" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script>
let datasetCount = document.querySelectorAll('.dataset-row').length;

function addDatasetRow() {
    const container = document.getElementById('datasets-container');
    const index = datasetCount;
    const type = '<?= htmlspecialchars($selectedType) ?>';
    let html = '';
    if (type === 'fill_blank') {
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
