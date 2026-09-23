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

function cfc_instagram_ago(int $seconds): string
{
    if ($seconds < 3600) {
        $n = max(1, (int) round($seconds / 60));
        return $n . ' minute' . ($n === 1 ? '' : 's') . ' ago';
    }
    if ($seconds < 172800) {
        $n = (int) round($seconds / 3600);
        return $n . ' hour' . ($n === 1 ? '' : 's') . ' ago';
    }
    $n = (int) round($seconds / 86400);
    return $n . ' day' . ($n === 1 ? '' : 's') . ' ago';
}

/**
 * Whether the cached feed is actually healthy.
 *
 * Public pages read only cached rows, so a stopped cron is invisible on the
 * site: the last synced posts simply sit there until the 60-day token lapses,
 * and only then does the Media Hub quietly drop back to the manual tiles. This
 * turns that silence into something the CMS can say out loud.
 *
 * Levels: off (not connected, nothing to report), ok, warn, err.
 *
 * @return array{level:string,message:string}
 */
function cfc_instagram_health(): array
{
    $quiet = ['level' => 'off', 'message' => ''];
    if (!cfc_db_ready()) {
        return $quiet;
    }
    $s = cfc_instagram_settings();
    // Switched off or never connected is a choice, not a fault.
    if ($s['enabled'] !== '1' || !cfc_instagram_has_token()) {
        return $quiet;
    }

    $now = time();
    $expiresAt = $s['token_expires_at'] !== '' ? strtotime($s['token_expires_at']) : false;

    if ($expiresAt !== false && $expiresAt <= $now) {
        return [
            'level' => 'err',
            'message' => 'The Instagram access token expired on ' . date('j M Y', $expiresAt)
                . ', so the Media Hub is showing the manually saved tiles instead of the live feed. Reconnect the account.',
        ];
    }
    if ($s['last_sync_ok'] === '0' && $s['last_error'] !== '') {
        // set_status() already redacts on the way in; redact on the way out too,
        // so no future caller writing this field can leak a token into the CMS.
        return ['level' => 'err', 'message' => 'The last Instagram sync failed: ' . cfc_instagram_redact($s['last_error'])];
    }

    // last_sync_at is written on failures too, so it only means "healthy"
    // alongside last_sync_ok, which is why that is checked first.
    $lastUnix = $s['last_sync_at'] !== '' ? strtotime($s['last_sync_at']) : false;
    $cacheMinutes = max(15, min(1440, (int) $s['cache_minutes']));
    // A sync only really runs once the cache goes stale, so allow several cache
    // windows before calling it a problem and crying wolf at the client.
    $staleAfter = max(
        max(1, (int) cfc_config('instagram_stale_hours', 24)) * 3600,
        $cacheMinutes * 60 * 4
    );
    if ($lastUnix === false || ($now - $lastUnix) > $staleAfter) {
        $age = ($lastUnix === false || $lastUnix > $now)
            ? 'it has never run'
            : 'last run ' . cfc_instagram_ago($now - $lastUnix);
        $msg = 'The Instagram feed has stopped updating (' . $age . '). The scheduled sync is probably not running';
        if ($expiresAt !== false) {
            $days = (int) floor(($expiresAt - $now) / 86400);
            $msg .= ', and the access token lapses in ' . $days . ' day' . ($days === 1 ? '' : 's') . ' unless it starts';
        }
        return ['level' => 'warn', 'message' => $msg . '. Check the cron job, or press Refresh now.'];
    }

    // Syncing renews the token 10 days out, so this only shows when it is not.
    if ($expiresAt !== false && ($expiresAt - $now) < 7 * 86400) {
        $days = max(0, (int) floor(($expiresAt - $now) / 86400));
        return [
            'level' => 'warn',
            'message' => 'The Instagram access token expires in ' . $days . ' day' . ($days === 1 ? '' : 's')
                . '. A working sync renews it by itself, so if this stays, reconnect the account.',
        ];
    }
    return ['level' => 'ok', 'message' => ''];
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
