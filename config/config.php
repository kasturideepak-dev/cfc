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

    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_name' => '',
    'db_user' => '',
    'db_pass' => '',
    'db_dsn' => '',

    'whatsapp' => '919457309999',
    'phone_display' => '94573 09999',
    'phone_alt' => '94674 52222',

    // Optional Instagram Graph overrides (prefer CMS → Instagram Feed).
    // Never put live tokens in this file; use config.local.php or MySQL.
    'instagram_access_token' => '',
    'instagram_app_id' => '',
    'instagram_app_secret' => '',
    'instagram_user_id' => '',
];
