<?php
require_once __DIR__ . '/support/SimpleYaml.php';
require_once __DIR__ . '/support/ConfigRepository.php';

$configRepository = app_config_repository();
$config = $configRepository->loadConfig();
if (!$configRepository->hasSecretsFile()) {
    die($configRepository->missingSecretsMessage());
}
$secrets = $configRepository->loadSecrets();

$rootPath = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
$appUrl = rtrim((string)ConfigRepository::getValue($config, 'app.url', 'http://localhost'), '/');
$uploadDirSetting = (string)ConfigRepository::getValue($config, 'uploads.dir', 'uploads');
$uploadUrlSetting = (string)ConfigRepository::getValue($config, 'uploads.url', '/uploads');

if (!preg_match('/^(\/|[A-Za-z]:[\\\/])/', $uploadDirSetting)) {
    $uploadDirSetting = $rootPath . '/' . ltrim($uploadDirSetting, '/');
}
$uploadDir = rtrim(str_replace('\\', '/', $uploadDirSetting), '/') . '/';
$uploadUrl = preg_match('/^https?:\/\//i', $uploadUrlSetting)
    ? rtrim($uploadUrlSetting, '/')
    : $appUrl . '/' . ltrim($uploadUrlSetting, '/');

if (!defined('APP_NAME')) {
    define('APP_NAME', (string)ConfigRepository::getValue($config, 'app.name', 'CTFForge'));
}
if (!defined('APP_URL')) {
    define('APP_URL', $appUrl);
}
if (!defined('DEBUG')) {
    define('DEBUG', ConfigRepository::normalizeBoolean(ConfigRepository::getValue($config, 'app.debug', false)));
}
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', $uploadDir);
}
if (!defined('UPLOAD_URL')) {
    define('UPLOAD_URL', $uploadUrl);
}
if (!defined('DB_HOST')) {
    define('DB_HOST', (string)ConfigRepository::getValue($secrets, 'database.host', 'localhost'));
}
if (!defined('DB_NAME')) {
    define('DB_NAME', (string)ConfigRepository::getValue($secrets, 'database.name', 'ctfforge'));
}
if (!defined('DB_USER')) {
    define('DB_USER', (string)ConfigRepository::getValue($secrets, 'database.user', 'root'));
}
if (!defined('DB_PASS')) {
    define('DB_PASS', (string)ConfigRepository::getValue($secrets, 'database.password', ''));
}

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

function app_config_repository(): ConfigRepository {
    static $repository = null;
    if ($repository === null) {
        $repository = new ConfigRepository(
            __DIR__ . '/config/config.yaml',
            __DIR__ . '/config/config.template.yaml',
            __DIR__ . '/secrets/db.yaml',
            __DIR__ . '/secrets/db.yaml.example'
        );
    }

    return $repository;
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
    if ($user['role'] !== $role && $user['role'] !== 'admin') {
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
        'admin'       => APP_URL . '/admin/index.php',
        default       => APP_URL . '/index.php',
    };
}
