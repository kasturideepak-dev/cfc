<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

function cfc_config(?string $key = null, mixed $default = null): mixed
{
    $cfg = $GLOBALS['cfc_config'] ?? [];
    if ($key === null) {
        return $cfg;
    }
    return $cfg[$key] ?? $default;
}

function cfc_debug(): bool
{
    return defined('CFC_DEBUG') ? CFC_DEBUG : !empty(cfc_config('debug'));
}

function cfc_request_host(): string
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $host = preg_replace('/:\d+$/', '', $host) ?? $host;
    return preg_match('/^[A-Za-z0-9.-]+$/', $host) ? $host : '';
}

function cfc_host_is_local(?string $host = null): bool
{
    $host = $host ?? cfc_request_host();
    return $host === 'localhost' || $host === '127.0.0.1' || $host === '::1';
}

function cfc_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
        return true;
    }
    $fwd = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    if ($fwd === 'https' || str_starts_with($fwd, 'https,')) {
        return true;
    }
    if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')) === 'on') {
        return true;
    }
    $cf = strtolower((string) ($_SERVER['HTTP_CF_VISITOR'] ?? ''));
    return str_contains($cf, '"scheme":"https"');
}

function cfc_prepare_runtime_dirs(): void
{
    $private = [
        CFC_DATA,
        CFC_DATA . '/cms',
        CFC_DATA . '/submissions',
        CFC_DATA . '/sessions',
    ];
    foreach ($private as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
    }
    $uploads = CFC_ROOT . '/assets/uploads/cms';
    if (!is_dir($uploads)) {
        @mkdir($uploads, 0755, true);
    }
    $mediaHub = CFC_ROOT . '/assets/media-hub';
    if (!is_dir($mediaHub)) {
        @mkdir($mediaHub, 0755, true);
    }
    $ig = CFC_ROOT . '/assets/instagram';
    if (!is_dir($ig)) {
        @mkdir($ig, 0755, true);
    }
}

function cfc_enforce_canonical(): void
{
    if (cfc_debug() || headers_sent()) {
        return;
    }
    $host = cfc_request_host();
    if ($host === '' || cfc_host_is_local($host) || filter_var($host, FILTER_VALIDATE_IP)) {
        return;
    }
    $wantHost = strtolower(trim((string) cfc_config('canonical_host', '')));
    $needHttps = !empty(cfc_config('force_https', false)) && !cfc_is_https();
    $needHost = $wantHost !== '' && $host !== $wantHost && (bool) preg_match('/^[A-Za-z0-9.-]+$/', $wantHost);
    if (!$needHttps && !$needHost) {
        return;
    }
    $useHost = $needHost ? $wantHost : $host;
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    if ($uri === '' || $uri[0] !== '/') {
        $uri = '/';
    }
    header('Location: https://' . $useHost . $uri, true, 301);
    exit;
}

function cfc_runtime_report(): array
{
    $writable = [];
    foreach ([
        'data' => CFC_DATA,
        'data/cms' => CFC_DATA . '/cms',
        'data/submissions' => CFC_DATA . '/submissions',
        'data/sessions' => CFC_DATA . '/sessions',
        'assets/uploads/cms' => CFC_ROOT . '/assets/uploads/cms',
        'assets/media-hub' => CFC_ROOT . '/assets/media-hub',
        'assets/instagram' => CFC_ROOT . '/assets/instagram',
    ] as $label => $path) {
        $writable[$label] = is_dir($path) && is_writable($path);
    }

    $extensions = [];
    foreach (['json', 'mbstring', 'fileinfo', 'session', 'filter', 'openssl', 'gd', 'curl', 'pdo_mysql'] as $ext) {
        $extensions[$ext] = extension_loaded($ext);
    }

    $dbConfigured = function_exists('cfc_db_configured') && cfc_db_configured();
    $dbReady = function_exists('cfc_db_ready') && cfc_db_ready();
    $dbPosts = 0;
    $dbSubs = 0;
    $dbTables = [];
    if ($dbReady) {
        try {
            $pdo = cfc_pdo();
            if ($pdo) {
                foreach ([
                    'cfc_posts', 'cfc_submissions', 'cfc_users', 'cfc_settings',
                    'cfc_pages', 'cfc_redirects', 'cfc_store', 'cfc_sessions',
                    'cfc_instagram_posts',
                ] as $table) {
                    $dbTables[$table] = (int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
                }
                $dbPosts = $dbTables['cfc_posts'] ?? 0;
                $dbSubs = $dbTables['cfc_submissions'] ?? 0;
            }
        } catch (Throwable $e) {
            $dbReady = false;
        }
    }

    $issues = [];
    if (PHP_VERSION_ID < 80100) {
        $issues[] = 'PHP 8.1+ is required (now ' . PHP_VERSION . ').';
    }
    if (cfc_debug()) {
        $issues[] = 'Debug is on. Set debug to false in config.local.php before going live.';
    }
    foreach ($writable as $label => $ok) {
        if (!$ok) {
            $issues[] = $label . ' is not writable.';
        }
    }
    foreach (['json', 'fileinfo', 'session', 'filter'] as $ext) {
        if (empty($extensions[$ext])) {
            $issues[] = 'PHP extension missing: ' . $ext . '.';
        }
    }
    if (!is_file(CFC_ROOT . '/.htaccess')) {
        $issues[] = '.htaccess is missing.';
    }
    if (!cfc_debug() && cfc_users_reload() === []) {
        $issues[] = 'No CMS users yet. Create the first admin at /admin/ or with scripts/create-admin.php.';
    }
    if (!cfc_debug() && !cfc_setup_key_configured() && cfc_users_reload() === []) {
        $issues[] = 'setup_key is missing or still the example value in config.local.php.';
    }
    if (!empty(cfc_config('mail_enabled')) && function_exists('cfc_smtp_ready') && !cfc_smtp_ready()) {
        $issues[] = 'Mail is on but smtp_user / smtp_pass are missing. Add the Gmail address and App Password in config.local.php.';
    }
    if (!empty(cfc_config('mail_enabled')) && empty($extensions['openssl'])) {
        $issues[] = 'PHP extension missing: openssl (needed for Gmail SMTP).';
    }
    if ($dbConfigured && empty($extensions['pdo_mysql'])) {
        $issues[] = 'PHP extension missing: pdo_mysql.';
    }
    if ($dbConfigured && !$dbReady) {
        $issues[] = 'MySQL is configured but the site could not connect. Check db_host, db_name, db_user, and db_pass.';
    }
    if (!cfc_debug() && !$dbConfigured) {
        $issues[] = 'MySQL is not configured. Set db_name, db_user, and db_pass in config.local.php. CMS content, blog, forms, users, and sessions require MySQL in production.';
    }

    return [
        'php' => PHP_VERSION,
        'php_ok' => PHP_VERSION_ID >= 80100,
        'sapi' => PHP_SAPI,
        'debug' => cfc_debug(),
        'https' => cfc_is_https(),
        'host' => cfc_request_host(),
        'base_path' => defined('CFC_BASE') ? CFC_BASE : '',
        'public_origin' => (string) cfc_config('public_origin', ''),
        'writable' => $writable,
        'extensions' => $extensions,
        'users' => count(cfc_users_reload()),
        'htaccess' => is_file(CFC_ROOT . '/.htaccess'),
        'local_config' => is_file(CFC_ROOT . '/config/config.local.php'),
        'mail_enabled' => !empty(cfc_config('mail_enabled')),
        'smtp_ready' => function_exists('cfc_smtp_ready') && cfc_smtp_ready(),
        'setup_key' => cfc_setup_key_configured(),
        'db_configured' => $dbConfigured,
        'db_ready' => $dbReady,
        'db_posts' => $dbPosts,
        'db_submissions' => $dbSubs,
        'db_tables' => $dbTables,
        'issues' => $issues,
    ];
}

function cfc_setup_key_value(): string
{
    return trim((string) cfc_config('setup_key', ''));
}

function cfc_setup_key_configured(): bool
{
    $key = cfc_setup_key_value();
    if (strlen($key) < 16) {
        return false;
    }
    $blocked = [
        'replace-with-a-long-random-string',
        'change-me-change-me',
        'changemechangeme1',
        'your-setup-key-here',
    ];
    return !in_array($key, $blocked, true);
}

function cfc_setup_key_verify(string $given): bool
{
    if (!cfc_setup_key_configured()) {
        return false;
    }
    $key = cfc_setup_key_value();
    if ($given === '' || strlen($given) !== strlen($key)) {
        return false;
    }
    return hash_equals($key, $given);
}

function cfc_mail_from(): string
{
    $from = trim((string) cfc_config('mail_from', ''));
    if ($from !== '' && filter_var($from, FILTER_VALIDATE_EMAIL)) {
        return $from;
    }
    $smtpUser = trim((string) cfc_config('smtp_user', ''));
    if ($smtpUser !== '' && filter_var($smtpUser, FILTER_VALIDATE_EMAIL)) {
        return $smtpUser;
    }
    return 'chennapatnamfiltercoffee@gmail.com';
}

function cfc_phpmailer_load(): bool
{
    static $loaded = null;
    if ($loaded !== null) {
        return $loaded;
    }
    $dir = CFC_ROOT . '/lib/PHPMailer';
    foreach (['Exception.php', 'SMTP.php', 'PHPMailer.php'] as $file) {
        $path = $dir . '/' . $file;
        if (!is_file($path)) {
            $loaded = false;
            return false;
        }
        require_once $path;
    }
    $loaded = class_exists(\PHPMailer\PHPMailer\PHPMailer::class);
    return $loaded;
}

function cfc_smtp_ready(): bool
{
    $user = trim((string) cfc_config('smtp_user', ''));
    $pass = (string) cfc_config('smtp_pass', '');
    return $user !== '' && $pass !== '' && filter_var($user, FILTER_VALIDATE_EMAIL) !== false;
}

function cfc_send_mail(string $to, string $subject, string $body, string $replyTo = '', string $html = ''): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $subject = str_replace(["\r", "\n", "\0"], '', $subject);
    $from = cfc_mail_from();
    $fromName = str_replace(["\r", "\n", "\0"], '', (string) cfc_config('mail_from_name', 'Chennapatnam Filter Coffee'));
    $replyTo = str_replace(["\r", "\n", "\0"], '', $replyTo);

    if (!cfc_phpmailer_load()) {
        error_log('CFC mail: PHPMailer is missing under lib/PHPMailer.');
        return false;
    }
    if (!cfc_smtp_ready()) {
        error_log('CFC mail: set smtp_user and smtp_pass (Gmail App Password) in config.local.php.');
        return false;
    }

    $host = trim((string) cfc_config('smtp_host', 'smtp.gmail.com')) ?: 'smtp.gmail.com';
    if (!preg_match('/^[A-Za-z0-9.-]+$/', $host)) {
        error_log('CFC mail: invalid smtp_host.');
        return false;
    }
    $port = (int) cfc_config('smtp_port', 587);
    if ($port < 1 || $port > 65535) {
        $port = 587;
    }
    $secure = strtolower(trim((string) cfc_config('smtp_secure', 'tls')));
    $user = trim((string) cfc_config('smtp_user', ''));
    $pass = str_replace(' ', '', (string) cfc_config('smtp_pass', ''));

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $user;
        $mail->Password = $pass;
        $mail->Port = $port;
        if ($secure === 'ssl' || $port === 465) {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $port === 587 ? 465 : $port;
        } else {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }
        $mail->Timeout = 15;
        $mail->setFrom($from, $fromName !== '' ? $fromName : $from);
        $mail->addAddress($to);
        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyTo);
        }
        $mail->Subject = $subject;
        if ($html !== '') {
            $mail->isHTML(true);
            $mail->Body = $html;
            $mail->AltBody = $body;
        } else {
            $mail->isHTML(false);
            $mail->Body = $body;
        }
        $mail->send();
        return true;
    } catch (Throwable $e) {
        error_log('CFC SMTP failed: ' . $e->getMessage());
        return false;
    }
}

function cfc_security_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if (cfc_is_https() && !cfc_debug()) {
        header('Strict-Transport-Security: max-age=15552000; includeSubDomains');
    }
}

function cfc_url(string $path = ''): string
{
    $path = ltrim($path, '/');
    $base = CFC_BASE;
    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }
    return ($base === '' ? '' : $base) . '/' . $path;
}

function cfc_abs_url(string $path = ''): string
{
    $forced = rtrim((string) cfc_config('public_origin', ''), '/');
    if ($forced !== '' && preg_match('#^https://[A-Za-z0-9.-]+#', $forced)) {
        return $forced . cfc_url($path);
    }
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9.:-]*$/', $host)) {
        $live = parse_url((string) cfc_config('live_origin', ''), PHP_URL_HOST);
        $host = is_string($live) && $live !== '' ? $live : 'localhost';
    }
    $https = cfc_is_https() || !cfc_debug();
    return ($https ? 'https' : 'http') . '://' . $host . cfc_url($path);
}

function cfc_request_path(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';
    $base = CFC_BASE;
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base)) ?: '/';
    }
    $path = '/' . ltrim($path, '/');
    if ($path !== '/') {
        $path = rtrim($path, '/') . '/';
    }
    return $path;
}

function cfc_e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function cfc_lower(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
}

function cfc_csrf_token(): string
{
    if (empty($_SESSION['cfc_csrf']) || !is_string($_SESSION['cfc_csrf'])) {
        $_SESSION['cfc_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['cfc_csrf'];
}

function cfc_csrf_verify(?string $token): bool
{
    return is_string($token)
        && $token !== ''
        && isset($_SESSION['cfc_csrf'])
        && is_string($_SESSION['cfc_csrf'])
        && hash_equals($_SESSION['cfc_csrf'], $token);
}

function cfc_session_needed(): bool
{
    if (PHP_SAPI === 'cli') {
        return false;
    }
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if ($method !== 'GET' && $method !== 'HEAD') {
        return true;
    }
    $path = strtolower((string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/'));
    if (str_contains($path, '/admin')) {
        return true;
    }
    foreach (['/contact-us', '/franchise', '/landing', '/form/'] as $needle) {
        if (str_contains($path, $needle)) {
            return true;
        }
    }
    return false;
}

function cfc_send(int $status, string $body, string $type = 'text/html; charset=UTF-8'): never
{
    http_response_code($status);
    header('Content-Type: ' . $type);
    cfc_security_headers();
    if (
        $status === 200
        && str_starts_with($type, 'text/html')
        && session_status() !== PHP_SESSION_ACTIVE
        && in_array(strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')), ['GET', 'HEAD'], true)
        && !array_key_exists('s', $_GET)
    ) {
        header('Cache-Control: public, max-age=120');
        header_remove('Expires');
        header_remove('Pragma');
    }
    echo $body;
    exit;
}

function cfc_json(int $status, array $payload): never
{
    cfc_send($status, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}', 'application/json; charset=UTF-8');
}

function cfc_redirect(string $path, int $code = 302): never
{
    $path = str_replace(["\r", "\n", "\0"], '', $path);
    if (preg_match('#^https://#i', $path)) {
        $host = strtolower((string) parse_url($path, PHP_URL_HOST));
        $allowed = [
            'www.andaalhomefoods.com',
            'andaalhomefoods.com',
        ];
        $liveHost = strtolower((string) parse_url((string) cfc_config('live_origin', ''), PHP_URL_HOST));
        if ($liveHost !== '') {
            $allowed[] = $liveHost;
            $allowed[] = str_starts_with($liveHost, 'www.') ? substr($liveHost, 4) : 'www.' . $liveHost;
        }
        $reqHost = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')) ?? '');
        if ($reqHost !== '') {
            $allowed[] = $reqHost;
        }
        if (!in_array($host, $allowed, true)) {
            $path = cfc_url('');
        }
        header('Location: ' . $path, true, $code);
        exit;
    }
    if (str_starts_with($path, '//') || str_contains($path, '://') || str_contains($path, '..')) {
        $path = '';
    }
    header('Location: ' . cfc_url(ltrim($path, '/')), true, $code);
    exit;
}

function cfc_read(string $path): ?string
{
    return is_file($path) ? (string) file_get_contents($path) : null;
}

function cfc_clip(string $value, int $max): string
{
    $value = str_replace("\0", '', $value);
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $max) ?: '';
    }
    return substr($value, 0, $max);
}
