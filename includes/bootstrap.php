<?php
declare(strict_types=1);

define('CFC_ROOT', dirname(__DIR__));

if (PHP_VERSION_ID < 80100) {
    $msg = 'This site requires PHP 8.1 or newer. Current version: ' . PHP_VERSION
        . '. In cPanel open MultiPHP Manager and set PHP 8.1, 8.2, or 8.3 for this domain.';
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $msg . PHP_EOL);
        exit(1);
    }
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $msg;
    exit;
}

$config = require CFC_ROOT . '/config/config.php';
$local = CFC_ROOT . '/config/config.local.php';
if (is_file($local)) {
    $config = array_replace($config, require $local);
}

date_default_timezone_set((string) $config['timezone']);

define('CFC_BASE', (string) $config['base_path']);
define('CFC_LIVE', rtrim((string) $config['live_origin'], '/'));
define('CFC_DATA', CFC_ROOT . '/data');
define('CFC_DEBUG', !empty($config['debug']));

$GLOBALS['cfc_config'] = $config;

@ini_set('expose_php', '0');
@ini_set('allow_url_include', '0');

if (CFC_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
}

require CFC_ROOT . '/includes/functions.php';
require CFC_ROOT . '/includes/database.php';

cfc_prepare_runtime_dirs();
cfc_settings_apply();

if (!CFC_DEBUG) {
    $log = CFC_DATA . '/php-error.log';
    if (is_dir(CFC_DATA) && (is_writable(CFC_DATA) || (is_file($log) && is_writable($log)))) {
        ini_set('error_log', $log);
    }
}

if (PHP_SAPI !== 'cli') {
    cfc_security_headers();
    cfc_enforce_canonical();
}

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE && cfc_session_needed()) {
    $sessionDir = CFC_DATA . '/sessions';
    if (is_dir($sessionDir) && is_writable($sessionDir)) {
        session_save_path($sessionDir);
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cache_limiter', 'nocache');
    session_name((string) $config['session_name']);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => CFC_BASE !== '' ? CFC_BASE . '/' : '/',
        'secure' => cfc_is_https() || (!CFC_DEBUG && !empty($config['force_https'])),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require CFC_ROOT . '/includes/render.php';
require CFC_ROOT . '/includes/cms.php';
require CFC_ROOT . '/includes/instagram.php';
require CFC_ROOT . '/includes/cms-users.php';
require CFC_ROOT . '/includes/forms.php';
require CFC_ROOT . '/includes/blog.php';
require CFC_ROOT . '/includes/navigation.php';
require CFC_ROOT . '/includes/redirects.php';
require CFC_ROOT . '/includes/router.php';
