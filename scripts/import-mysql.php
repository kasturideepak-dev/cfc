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

if (!cfc_db_configured()) {
    fwrite(STDERR, "Set db_name, db_user, and db_pass in config/config.local.php first.\n");
    exit(1);
}

$pdo = cfc_pdo();
if (!$pdo) {
    fwrite(STDERR, "Could not connect to MySQL. Check credentials and that pdo_mysql is enabled.\n");
    exit(1);
}

$posts = cfc_db_import_posts_from_files($pdo);
$subs = cfc_db_import_submissions_from_files($pdo);

$contentFile = CFC_DATA . '/cms/content.json';
$pages = 0;
if (is_file($contentFile)) {
    $decoded = json_decode((string) file_get_contents($contentFile), true);
    if (is_array($decoded) && cfc_db_pages_save($decoded)) {
        $pages = count($decoded);
    }
}

$redirFile = CFC_DATA . '/cms/redirects.json';
$redirRows = is_file($redirFile) ? json_decode((string) file_get_contents($redirFile), true) : null;
if (!is_array($redirRows)) {
    $redirRows = cfc_redirects_defaults();
}
cfc_db_redirects_save($redirRows);

foreach (['gallery' => CFC_DATA . '/gallery.json', 'media_hub' => CFC_DATA . '/media-hub.json'] as $key => $file) {
    if (!is_file($file)) {
        continue;
    }
    $decoded = json_decode((string) file_get_contents($file), true);
    if (is_array($decoded)) {
        cfc_store_set($key, $decoded);
    }
}

cfc_db_migrate_json_users($pdo);
cfc_db_migrate_json_settings($pdo);

$counts = [];
foreach (['cfc_pages', 'cfc_posts', 'cfc_store', 'cfc_redirects', 'cfc_submissions', 'cfc_users'] as $table) {
    $counts[$table] = (int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
}

fwrite(STDOUT, "Imported/updated {$posts} posts, {$pages} page records, {$subs} form submissions.\n");
foreach ($counts as $table => $n) {
    fwrite(STDOUT, sprintf("  %-18s %d\n", $table, $n));
}
exit(0);
