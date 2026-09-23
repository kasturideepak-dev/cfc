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

if (!cfc_db_ready()) {
    fwrite(STDERR, "MySQL is required for CMS users. Set db_name, db_user, and db_pass in config/config.local.php.\n");
    exit(1);
}

$username = trim((string) ($argv[1] ?? ''));
$password = (string) ($argv[2] ?? '');
$name = trim((string) ($argv[3] ?? ''));

if ($username === '' || $password === '') {
    fwrite(STDERR, "Usage: php scripts/create-admin.php <username> <password> [display name]\n");
    fwrite(STDERR, "Password must be at least 12 characters.\n");
    exit(1);
}

if (!cfc_user_valid_username($username)) {
    fwrite(STDERR, "Username must be 3–32 letters, numbers, dots, or hyphens.\n");
    exit(1);
}

if (strlen($password) < 12) {
    fwrite(STDERR, "Password must be at least 12 characters.\n");
    exit(1);
}

$users = cfc_users_reload();
$existing = null;
$index = null;
foreach ($users as $i => $row) {
    if (strcasecmp((string) ($row['username'] ?? ''), $username) === 0) {
        $existing = $row;
        $index = $i;
        break;
    }
}

if ($existing !== null && $index !== null) {
    $users[$index]['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    $users[$index]['updated_at'] = date('c');
    if ($name !== '') {
        $users[$index]['name'] = $name;
    }
    if (!cfc_users_write($users)) {
        fwrite(STDERR, "Could not update user. Check the MySQL connection.\n");
        exit(1);
    }
    fwrite(STDOUT, "Password updated for {$username}.\n");
    exit(0);
}

if ($users === []) {
    [$ok, $msg] = cfc_users_create_first($username, $password, $name);
    fwrite($ok ? STDOUT : STDERR, $msg . "\n");
    exit($ok ? 0 : 1);
}

$users[] = [
    'id' => bin2hex(random_bytes(8)),
    'username' => $username,
    'name' => $name !== '' ? $name : $username,
    'role' => 'admin',
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'created_at' => date('c'),
];
if (!cfc_users_write($users)) {
    fwrite(STDERR, "Could not save user. Check the MySQL connection.\n");
    exit(1);
}
fwrite(STDOUT, "Admin created: {$username}\n");
exit(0);
