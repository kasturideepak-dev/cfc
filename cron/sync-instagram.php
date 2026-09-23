#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Refresh the Instagram feed cache from the official Graph API.
 * Safe to run every 15–60 minutes. Skips Instagram when the cache is still fresh
 * unless --force is passed. Never prints access tokens.
 *
 * crontab (every 30 minutes):
 *   0,30 * * * * /usr/bin/php /home/USER/public_html/cron/sync-instagram.php >/dev/null 2>&1
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "CLI only.\n";
    exit(1);
}

require dirname(__DIR__) . '/includes/bootstrap.php';

$force = in_array('--force', $argv, true);
if (!cfc_instagram_has_token()) {
    fwrite(STDOUT, "Instagram is not connected. Skipping.\n");
    exit(0);
}
$result = cfc_instagram_sync($force);
$line = ($result['ok'] ? 'OK' : 'FAIL') . ' ' . $result['message'] . ($result['code'] !== '' ? ' [' . $result['code'] . ']' : '') . "\n";
fwrite($result['ok'] ? STDOUT : STDERR, cfc_instagram_redact($line));
exit($result['ok'] ? 0 : 1);
