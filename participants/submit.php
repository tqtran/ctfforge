<?php
require_once __DIR__ . '/../framework/bootstrap.php';
require_role('participant');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/participants/index.php');
}

if (!csrf_verify()) {
    flash('Invalid security token.', 'danger');
    redirect(APP_URL . '/participants/index.php');
}

$user = auth_user();
$challengeId = (int)($_POST['challenge_id'] ?? 0);
$datasetId = (int)($_POST['dataset_id'] ?? 0);
$answer = trim($_POST['answer'] ?? '');

if (!$challengeId || !$datasetId || $answer === '') {
    flash('Invalid submission.', 'danger');
    redirect(APP_URL . '/participants/index.php');
}

$challengeModel = new Challenge();
$submissionModel = new Submission();

$challenge = $challengeModel->findById($challengeId);
if (!$challenge) {
    flash('Challenge not found.', 'danger');
    redirect(APP_URL . '/participants/index.php');
}

// Check if already solved
if ($submissionModel->hasCorrectSubmission((int)$user['id'], $challengeId)) {
    flash('You have already solved this challenge!', 'info');
    redirect(APP_URL . '/participants/index.php');
}

$datasets = $challengeModel->getDatasets($challengeId);
$dataset = null;
foreach ($datasets as $ds) {
    if ((int)$ds['id'] === $datasetId) {
        $dataset = $ds;
        break;
    }
}

if (!$dataset) {
    flash('Invalid dataset.', 'danger');
    redirect(APP_URL . '/participants/index.php');
}

$plugin = get_plugin($challenge['plugin_type']);
if (!$plugin) {
    flash('Unknown challenge type.', 'danger');
    redirect(APP_URL . '/participants/index.php');
}

$isCorrect = $plugin->checkAnswer($dataset, $answer);
$submissionModel->create((int)$user['id'], $challengeId, $datasetId, $answer, $isCorrect);

if ($isCorrect) {
    flash('🎉 Correct! You earned ' . $challenge['points'] . ' points!', 'success');
} else {
    flash('❌ Incorrect answer. Try again!', 'danger');
}

redirect(APP_URL . '/participants/index.php');
