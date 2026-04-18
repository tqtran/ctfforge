<?php
require_once __DIR__ . '/config/config.php';

$secretsFile = __DIR__ . '/secrets/db.php';
if (!file_exists($secretsFile)) {
    die('Database credentials not configured. Copy framework/secrets/db.php.example to framework/secrets/db.php and fill in your credentials.');
}
require_once $secretsFile;

require_once __DIR__ . '/model/Database.php';
require_once __DIR__ . '/model/User.php';
require_once __DIR__ . '/model/Challenge.php';
require_once __DIR__ . '/model/Competition.php';
require_once __DIR__ . '/model/Submission.php';
require_once __DIR__ . '/plugins/PluginBase.php';
require_once __DIR__ . '/plugins/FillBlank/FillBlank.php';
require_once __DIR__ . '/plugins/ImageQuestion/ImageQuestion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function auth_user(): ?array {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $userModel = new User();
        $user = $userModel->findById((int)$_SESSION['user_id']);
    }
    return $user;
}

function require_role(string $role): void {
    $user = auth_user();
    if (!$user) {
        redirect(APP_URL . '/index.php');
    }
    if ($user['role'] !== $role) {
        http_response_code(403);
        die('Access denied. Required role: ' . htmlspecialchars($role));
    }
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function flash(string $message, string $type = 'success'): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): bool {
    $token = $_POST['csrf_token'] ?? '';
    return hash_equals(csrf_token(), $token);
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function get_plugin(string $type): ?PluginBase {
    return match($type) {
        'fill_blank'     => new FillBlank(),
        'image_question' => new ImageQuestion(),
        default          => null,
    };
}

function dashboard_url(string $role): string {
    return match($role) {
        'participant' => APP_URL . '/participants/index.php',
        'organizer'   => APP_URL . '/organizers/index.php',
        'author'      => APP_URL . '/authors/index.php',
        default       => APP_URL . '/index.php',
    };
}
