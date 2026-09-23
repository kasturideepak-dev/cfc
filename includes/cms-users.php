<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

function cfc_users(bool $reload = false): array
{
    static $users = null;
    if ($reload) {
        $users = null;
    }
    if ($users !== null) {
        return $users;
    }
    $users = cfc_users_reload();
    return $users;
}

function cfc_users_reload(): array
{
    return cfc_db_users_list();
}

function cfc_users_write(array $users): bool
{
    if (!cfc_db_ready()) {
        return false;
    }
    $ok = true;
    foreach ($users as $user) {
        if (!is_array($user)) {
            continue;
        }
        if (!cfc_db_user_upsert($user)) {
            $ok = false;
        }
    }
    if ($ok) {
        cfc_users(true);
    }
    return $ok;
}

function cfc_password_policy(string $password, string $username = ''): ?string
{
    $len = function_exists('mb_strlen') ? mb_strlen($password, 'UTF-8') : strlen($password);
    if ($len < 12) {
        return 'Password must be at least 12 characters.';
    }
    if ($len > 1024) {
        return 'Password is too long.';
    }
    if ($username !== '' && strcasecmp($password, $username) === 0) {
        return 'Password cannot match the username.';
    }
    return null;
}

function cfc_password_hash(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function cfc_password_verify(string $password, string $hash): bool
{
    if ($hash === '' || (int) (password_get_info($hash)['algo'] ?? 0) === 0) {
        return false;
    }
    return password_verify($password, $hash);
}

function cfc_users_create_first(string $username, string $password, string $name = ''): array
{
    $pdo = cfc_pdo();
    if (!$pdo) {
        return [false, 'MySQL is required for CMS users. Set db_name, db_user, and db_pass in config.local.php.'];
    }
    $username = trim($username);
    $name = trim($name);
    if (!cfc_user_valid_username($username)) {
        return [false, 'Username must be 3–32 letters, numbers, dots, or hyphens.'];
    }
    $policy = cfc_password_policy($password, $username);
    if ($policy !== null) {
        return [false, $policy];
    }
    if ($name === '') {
        $name = $username;
    }
    try {
        $locked = (int) $pdo->query("SELECT GET_LOCK('cfc_create_first_admin', 8)")->fetchColumn();
        if ($locked !== 1) {
            return [false, 'Could not create the admin account. Please try again.'];
        }
        if (cfc_users_reload() !== []) {
            return [false, 'An admin account already exists. Sign in instead.'];
        }
        $ok = cfc_db_user_upsert([
            'id' => bin2hex(random_bytes(8)),
            'username' => $username,
            'name' => $name,
            'role' => 'admin',
            'password_hash' => cfc_password_hash($password),
            'created_at' => date('c'),
        ]);
        if (!$ok) {
            return [false, 'Could not save the admin account. Check MySQL credentials and that table cfc_users exists.'];
        }
        cfc_users(true);
        return [true, 'Admin account created.'];
    } finally {
        try {
            $pdo->query("SELECT RELEASE_LOCK('cfc_create_first_admin')");
        } catch (Throwable $e) {
            // ignore
        }
    }
}

function cfc_user_find(string $idOrName): ?array
{
    foreach (cfc_users() as $user) {
        if (($user['id'] ?? '') === $idOrName || strcasecmp((string) ($user['username'] ?? ''), $idOrName) === 0) {
            return $user;
        }
    }
    return null;
}

function cfc_user_auth(string $username, string $password): ?array
{
    $username = trim($username);
    if ($username === '' || $password === '') {
        return null;
    }
    if (strlen($password) > 1024) {
        return null;
    }
    $user = cfc_user_find($username);
    $hash = is_array($user) ? (string) ($user['password_hash'] ?? '') : '';
    $dummy = '$2y$12$0MNFhCbIW7LdvRmCxRZC3OhSyWAW98kERmisgNWVqBXK6wpi0gXhy';
    $ok = cfc_password_verify($password, $hash !== '' ? $hash : $dummy);
    if (!$ok || $user === null) {
        return null;
    }
    $rehash = null;
    if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        $rehash = cfc_password_hash($password);
    }
    cfc_db_user_touch_login((string) $user['id'], $rehash);
    return $user;
}

/**
 * Per-IP sign-in ceiling. The in-session failure counter is the first line, but
 * an attacker who throws the cookie away gets a fresh counter every time, so
 * the durable limit has to be keyed to something they cannot discard.
 * Counts every attempt, not just failures: the limit is set high enough that a
 * real editor will never reach it.
 */
function cfc_admin_login_rate_ok(): bool
{
    $ip = cfc_client_ip();
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return true;
    }
    $limit = max(3, (int) cfc_config('admin_login_limit', 20));
    $window = max(60, (int) cfc_config('admin_login_window', 900));
    return cfc_rate_limit_hit('admin-login:' . $ip, $limit, $window);
}

function cfc_admin_login_rate_clear(): void
{
    $ip = cfc_client_ip();
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        cfc_rate_limit_clear('admin-login:' . $ip);
    }
}

function cfc_admin_me(): ?array
{
    $id = $_SESSION['cfc_admin'] ?? null;
    if (!is_string($id) || $id === '') {
        return null;
    }
    return cfc_user_find($id);
}

function cfc_admin_role(): string
{
    $me = cfc_admin_me();
    if ($me === null) {
        return 'editor';
    }
    $role = (string) ($me['role'] ?? 'editor');
    return $role === 'admin' ? 'admin' : 'editor';
}

function cfc_admin_is_admin(): bool
{
    return cfc_admin_role() === 'admin';
}

function cfc_user_valid_username(string $username): bool
{
    return (bool) preg_match('/^[a-zA-Z0-9._-]{3,32}$/', $username);
}

function cfc_user_valid_role(string $role): bool
{
    return $role === 'admin' || $role === 'editor';
}

function cfc_users_admin_count(array $users): int
{
    $n = 0;
    foreach ($users as $user) {
        if (($user['role'] ?? '') === 'admin') {
            $n++;
        }
    }
    return $n;
}

function cfc_user_save(array $post, ?string $id): array
{
    if (!cfc_db_ready()) {
        return [false, 'MySQL is required for CMS users.'];
    }
    $users = cfc_users_reload();
    $username = trim((string) ($post['username'] ?? ''));
    $name = trim((string) ($post['name'] ?? ''));
    $role = (string) ($post['role'] ?? 'editor');
    $password = (string) ($post['password'] ?? '');
    $me = cfc_admin_me();

    if (!cfc_user_valid_username($username)) {
        return [false, 'Username must be 3–32 letters, numbers, dots, or hyphens.'];
    }
    if (!cfc_user_valid_role($role)) {
        $role = 'editor';
    }
    if ($name === '') {
        $name = $username;
    }

    $existing = null;
    if ($id) {
        foreach ($users as $row) {
            if (($row['id'] ?? '') === $id) {
                $existing = $row;
                break;
            }
        }
        if ($existing === null) {
            return [false, 'User not found.'];
        }
    }

    foreach ($users as $row) {
        if (strcasecmp((string) ($row['username'] ?? ''), $username) === 0 && ($row['id'] ?? '') !== $id) {
            return [false, 'That username is already taken.'];
        }
    }

    if ($existing && ($existing['role'] ?? '') === 'admin' && $role !== 'admin' && cfc_users_admin_count($users) <= 1) {
        return [false, 'Keep at least one admin account.'];
    }
    if ($existing && $me && ($existing['id'] ?? '') === ($me['id'] ?? '') && $role !== 'admin') {
        return [false, 'You cannot remove your own admin role.'];
    }

    if ($password !== '' || $existing === null) {
        $policy = cfc_password_policy($password, $username);
        if ($policy !== null) {
            return [false, $policy];
        }
    }

    $entry = [
        'id' => $existing['id'] ?? bin2hex(random_bytes(8)),
        'username' => $username,
        'name' => $name,
        'role' => $role,
        'password_hash' => $existing['password_hash'] ?? '',
        'created_at' => $existing['created_at'] ?? date('c'),
        'updated_at' => date('c'),
        'last_login_at' => $existing['last_login_at'] ?? null,
    ];
    if ($password !== '') {
        $entry['password_hash'] = cfc_password_hash($password);
    }
    if ($entry['password_hash'] === '' || (int) (password_get_info((string) $entry['password_hash'])['algo'] ?? 0) === 0) {
        return [false, 'Password is required.'];
    }

    if (!cfc_db_user_upsert($entry)) {
        return [false, 'Could not save user. Check the MySQL connection.'];
    }
    cfc_users(true);
    if ($me && ($entry['id'] ?? '') === ($me['id'] ?? '')) {
        $_SESSION['cfc_admin_user'] = $entry['username'];
        $_SESSION['cfc_admin_name'] = $entry['name'];
        $_SESSION['cfc_admin_role'] = $entry['role'];
    }
    return [true, $existing ? 'User updated.' : 'User created.'];
}

function cfc_user_delete(string $id): array
{
    $me = cfc_admin_me();
    if ($me && ($me['id'] ?? '') === $id) {
        return [false, 'You cannot delete your own account.'];
    }
    $users = cfc_users_reload();
    $found = null;
    foreach ($users as $row) {
        if (($row['id'] ?? '') === $id) {
            $found = $row;
            break;
        }
    }
    if ($found === null) {
        return [false, 'User not found.'];
    }
    if (($found['role'] ?? '') === 'admin' && cfc_users_admin_count($users) <= 1) {
        return [false, 'Keep at least one admin account.'];
    }
    if (!cfc_db_user_delete($id)) {
        return [false, 'Could not delete user.'];
    }
    cfc_users(true);
    return [true, 'User deleted.'];
}
