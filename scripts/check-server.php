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

$r = cfc_runtime_report();
$ok = static fn(bool $pass): string => $pass ? 'ok' : 'FAIL';

fwrite(STDOUT, "CFC server check\n");
fwrite(STDOUT, "PHP            {$r['php']} ({$ok($r['php_ok'])})\n");
fwrite(STDOUT, "SAPI           {$r['sapi']}\n");
fwrite(STDOUT, "Debug          " . ($r['debug'] ? 'ON' : 'off') . "\n");
fwrite(STDOUT, "HTTPS          " . ($r['https'] ? 'yes' : 'no') . "\n");
fwrite(STDOUT, "Host           " . ($r['host'] !== '' ? $r['host'] : '(cli)') . "\n");
fwrite(STDOUT, "Base path      " . ($r['base_path'] !== '' ? $r['base_path'] : '(domain root)') . "\n");
fwrite(STDOUT, "config.local   " . ($r['local_config'] ? 'present' : 'missing') . "\n");
fwrite(STDOUT, ".htaccess      " . ($r['htaccess'] ? 'present' : 'missing') . "\n");
fwrite(STDOUT, "CMS users      {$r['users']}\n");
$mailLine = 'off';
if (!empty($r['mail_enabled'])) {
    $mailLine = !empty($r['smtp_ready']) ? 'PHPMailer / Gmail SMTP' : 'on, SMTP not configured';
}
fwrite(STDOUT, "Mail           {$mailLine}\n");
fwrite(STDOUT, "Setup key      " . ($r['setup_key'] ? 'set' : 'not set') . "\n");
$dbLine = 'off';
if (!empty($r['db_ready'])) {
    $dbLine = 'connected (' . (int) $r['db_posts'] . ' posts, ' . (int) $r['db_submissions'] . ' forms)';
} elseif (!empty($r['db_configured'])) {
    $dbLine = 'configured, not connected';
}
fwrite(STDOUT, "MySQL          {$dbLine}\n");
fwrite(STDOUT, "\nWritable folders\n");
foreach ($r['writable'] as $label => $pass) {
    fwrite(STDOUT, sprintf("  %-22s %s\n", $label, $ok($pass)));
}
fwrite(STDOUT, "\nExtensions\n");
foreach ($r['extensions'] as $ext => $pass) {
    fwrite(STDOUT, sprintf("  %-22s %s\n", $ext, $ok($pass)));
}

if ($r['issues'] !== []) {
    fwrite(STDOUT, "\nIssues\n");
    foreach ($r['issues'] as $issue) {
        fwrite(STDOUT, "  - {$issue}\n");
    }
    exit(1);
}

fwrite(STDOUT, "\nReady.\n");
exit(0);
