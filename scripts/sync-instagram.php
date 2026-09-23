#!/usr/bin/env php
<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "CLI only.\n";
    exit(1);
}

require dirname(__DIR__) . '/includes/bootstrap.php';

$args = array_slice($argv, 1);
if ($args === [] || in_array($args[0], ['-h', '--help'], true)) {
    fwrite(STDOUT, "Usage:\n");
    fwrite(STDOUT, "  php scripts/sync-instagram.php https://www.instagram.com/reel/SHORTCODE/ [...]\n");
    fwrite(STDOUT, "  php scripts/sync-instagram.php --graph\n");
    fwrite(STDOUT, "\nURL args prepend manual Media Hub tiles. --graph runs the official Graph sync (cron/sync-instagram.php).\n");
    exit($args === [] ? 1 : 0);
}

@set_time_limit(90);

if ($args[0] === '--graph' || $args[0] === '--force') {
    $result = cfc_instagram_sync(true);
    fwrite(($result['ok'] ? STDOUT : STDERR), cfc_instagram_redact($result['message']) . "\n");
    exit($result['ok'] ? 0 : 1);
}

$urls = [];
foreach ($args as $arg) {
    $urls = array_merge($urls, cfc_instagram_parse_urls((string) $arg));
    $code = cfc_instagram_shortcode((string) $arg);
    if ($code !== '') {
        $urls[] = cfc_instagram_post_url($code);
    }
}
$urls = array_values(array_unique($urls));
if ($urls === []) {
    fwrite(STDERR, "No Instagram post URLs found.\n");
    exit(1);
}

$added = cfc_instagram_import_urls($urls);
$notes = cfc_cms_notes();
if ($notes !== []) {
    fwrite(STDERR, implode("\n", $notes) . "\n");
}
if ($added === []) {
    fwrite(STDOUT, "No new posts added. They may already be in the Media Hub, or covers could not be downloaded.\n");
    exit($notes === [] ? 0 : 1);
}

fwrite(STDOUT, 'Added ' . count($added) . " Instagram post(s) to the top of Media Hub:\n");
foreach ($added as $row) {
    fwrite(STDOUT, '  ' . ($row['href'] ?? '') . ' -> ' . ($row['file'] ?? '') . "\n");
}
exit(0);
