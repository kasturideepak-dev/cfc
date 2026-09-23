<?php
declare(strict_types=1);

if (
    PHP_SAPI !== 'cli'
    && isset($_SERVER['SCRIPT_FILENAME'])
    && realpath((string) $_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)
) {
    http_response_code(403);
    exit;
}

/**
 * Runtime configuration. Override any value in config.local.php
 * (gitignored). Do not put live passwords or API keys in this file.
 */

$detectedBase = '';
if (PHP_SAPI !== 'cli') {
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $scriptName = preg_replace('#/admin/index\.php$#', '/index.php', $scriptName) ?? $scriptName;
    $detectedBase = rtrim(dirname($scriptName), '/');
    if ($detectedBase === '/' || $detectedBase === '.') {
        $detectedBase = '';
    }
}

return [
    'site_name' => 'CHENNAPATNAM FILTER COFFEE',
    'live_origin' => 'https://chennapatnamfiltercoffee.com',
    'public_origin' => '',
    'base_path' => $detectedBase,
    'timezone' => 'Asia/Kolkata',
    'debug' => false,
    'force_https' => false,
    'canonical_host' => '',

    'admin_user' => '',
    'admin_password_hash' => '',
    'setup_key' => '',

    'session_name' => 'cfc_session',
    'admin_idle_seconds' => 14400,
    'form_rate_limit' => 5,
    'form_rate_window' => 600,

    // Only set this if the site really sits behind a CDN or reverse proxy, and
    // list only that proxy's addresses. Anything in here is allowed to tell us
    // who the visitor is, so an over-broad entry hands attackers a way to forge
    // their address and slip past every per-IP limit. Empty = trust nobody.
    // Cloudflare publishes its ranges at https://www.cloudflare.com/ips/
    'trusted_proxies' => [],

    // Failed and successful sign-ins counted per IP. The in-session counter
    // alone is bypassed by simply discarding the cookie.
    'admin_login_limit' => 20,
    'admin_login_window' => 900,

    // Bulk gallery upload sends one request per photo, so this ceiling is set
    // well above any human pace: it is there to stop a runaway loop, not to
    // throttle normal work. Roughly 600 photos an hour per editor.
    'gallery_upload_limit' => 600,
    'gallery_upload_window' => 3600,

    // Refuse new uploads once the disk gets this low (bytes).
    'upload_min_free_bytes' => 209715200,

    'turnstile_site_key' => '',
    'turnstile_secret' => '',

    'mail_to' => 'chennapatnamfiltercoffee@gmail.com',
    'mail_from' => '',
    'mail_from_name' => 'Chennapatnam Filter Coffee',
    'mail_enabled' => false,
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',
    'smtp_user' => '',
    'smtp_pass' => '',

    // Real database credentials belong in config/config.local.php, which is
    // gitignored. Leaving them here would commit them to history permanently.
    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_name' => '',
    'db_user' => '',
    'db_pass' => '',
    'db_dsn' => '',

    'whatsapp' => '919457309999',
    'phone_display' => '94573 09999',
    'phone_alt' => '94674 52222',

    // Warn in the CMS when the Instagram feed has not synced for this long.
    // The check also allows several cache windows, so raising cache_minutes
    // will not make this nag.
    'instagram_stale_hours' => 24,

    // Optional Instagram Graph overrides (prefer CMS → Instagram Feed).
    // Never put live tokens in this file; use config.local.php or MySQL.
    'instagram_access_token' => '',
    'instagram_app_id' => '',
    'instagram_app_secret' => '',
    'instagram_user_id' => '',
];
