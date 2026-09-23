<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

function cfc_instagram_setting_defaults(): array
{
    return [
        'enabled' => '0',
        'mode' => 'instagram_login',
        'graph_version' => 'v25.0',
        'app_id' => '',
        'user_id' => '',
        'username' => '',
        'limit' => '12',
        'cache_minutes' => '60',
        'token_expires_at' => '',
        'last_sync_at' => '',
        'last_sync_ok' => '',
        'last_error' => '',
        'last_error_code' => '',
    ];
}

function cfc_instagram_public_keys(): array
{
    return array_keys(cfc_instagram_setting_defaults());
}

function cfc_instagram_secret_keys(): array
{
    return ['app_secret', 'access_token'];
}

function cfc_instagram_settings(): array
{
    $out = cfc_instagram_setting_defaults();
    foreach ($out as $key => $default) {
        $fromCfg = cfc_config('instagram_' . $key, null);
        if (is_string($fromCfg) && $fromCfg !== '') {
            $out[$key] = $fromCfg;
        }
        $fromDb = cfc_setting_get('instagram_' . $key);
        if (is_string($fromDb) && $fromDb !== '') {
            $out[$key] = $fromDb;
        }
    }
    foreach (cfc_instagram_secret_keys() as $key) {
        $fromCfg = cfc_config('instagram_' . $key, null);
        $fromDb = cfc_setting_get('instagram_' . $key);
        $out[$key] = (is_string($fromDb) && $fromDb !== '') ? $fromDb : (is_string($fromCfg) ? $fromCfg : '');
    }
    $out['mode'] = $out['mode'] === 'facebook_login' ? 'facebook_login' : 'instagram_login';
    $out['enabled'] = $out['enabled'] === '1' ? '1' : '0';
    $out['limit'] = (string) max(1, min(24, (int) $out['limit']));
    $out['cache_minutes'] = (string) max(15, min(1440, (int) $out['cache_minutes']));
    if (!preg_match('/^v\d+\.\d+$/', $out['graph_version'])) {
        $out['graph_version'] = 'v25.0';
    }
    return $out;
}

function cfc_instagram_setting(string $key, string $default = ''): string
{
    $all = cfc_instagram_settings();
    return isset($all[$key]) && is_string($all[$key]) ? $all[$key] : $default;
}

function cfc_instagram_has_token(): bool
{
    return trim(cfc_instagram_setting('access_token')) !== '';
}

function cfc_instagram_enabled(): bool
{
    return cfc_instagram_setting('enabled') === '1' && cfc_instagram_has_token();
}

function cfc_instagram_save_settings(array $values): bool
{
    if (!cfc_db_ready()) {
        return false;
    }
    $ok = true;
    foreach ($values as $key => $value) {
        if (!is_string($key) || !preg_match('/^[a-z_]+$/', $key)) {
            continue;
        }
        if (!in_array($key, cfc_instagram_public_keys(), true) && !in_array($key, cfc_instagram_secret_keys(), true)) {
            continue;
        }
        $ok = cfc_setting_set('instagram_' . $key, $value) && $ok;
    }
    return $ok;
}

function cfc_instagram_set_status(bool $ok, string $message, string $code = ''): void
{
    cfc_instagram_save_settings([
        'last_sync_at' => date('Y-m-d H:i:s'),
        'last_sync_ok' => $ok ? '1' : '0',
        'last_error' => $ok ? '' : cfc_clip(cfc_instagram_redact($message), 500),
        'last_error_code' => $ok ? '' : cfc_clip($code, 32),
    ]);
}

function cfc_instagram_redact(string $text): string
{
    $text = preg_replace('/access_token=[^&\s"\']+/i', 'access_token=[redacted]', $text) ?? $text;
    $text = preg_replace('/client_secret=[^&\s"\']+/i', 'client_secret=[redacted]', $text) ?? $text;
    $text = preg_replace('/\bIGQ[A-Za-z0-9]+/', '[redacted]', $text) ?? $text;
    $text = preg_replace('/\bEAA[A-Za-z0-9]+/', '[redacted]', $text) ?? $text;
    return $text;
}

function cfc_instagram_mask_secret(string $value): string
{
    $value = trim($value);
    $len = strlen($value);
    if ($len < 8) {
        return $len === 0 ? '' : '••••';
    }
    return substr($value, 0, 4) . str_repeat('•', min(18, $len - 8)) . substr($value, -4);
}

function cfc_instagram_schema_sql(): string
{
    return <<<'SQL'
CREATE TABLE IF NOT EXISTS cfc_instagram_posts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  instagram_media_id VARCHAR(64) NOT NULL,
  media_type VARCHAR(32) NOT NULL DEFAULT '',
  media_url TEXT NULL,
  thumbnail_url TEXT NULL,
  permalink VARCHAR(500) NOT NULL DEFAULT '',
  caption TEXT NULL,
  alt_text VARCHAR(500) NOT NULL DEFAULT '',
  username VARCHAR(64) NOT NULL DEFAULT '',
  timestamp DATETIME NULL,
  local_file VARCHAR(255) NOT NULL DEFAULT '',
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  fetched_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY instagram_media_id (instagram_media_id),
  KEY timestamp_idx (timestamp),
  KEY sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
}

function cfc_instagram_ensure_schema(?PDO $pdo = null): bool
{
    $pdo = $pdo ?? cfc_pdo();
    if (!$pdo) {
        return false;
    }
    cfc_db_ensure_instagram_table($pdo);
    return true;
}
