<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

function cfc_redirects_file(): string
{
    return CFC_DATA . '/cms/redirects.json';
}

function cfc_redirects_defaults(): array
{
    return [
        [
            'from' => '/product-category/lemon-tea/',
            'to' => 'https://www.andaalhomefoods.com/collections/coffee/products/lemon-tea?variant=42499446767706',
            'code' => 301,
        ],
        [
            'from' => '/product-category/coffee-powder/',
            'to' => 'https://www.andaalhomefoods.com/products/coffee-powder?variant=42499446243418',
            'code' => 301,
        ],
        [
            'from' => '/product-category/honey/',
            'to' => 'https://www.andaalhomefoods.com/products/organic-honey?variant=42121826762842',
            'code' => 301,
        ],
    ];
}

function cfc_redirect_normalize_from(string $from): string
{
    $from = trim(str_replace('\\', '/', $from));
    $from = str_replace(["\0", "\r", "\n"], '', $from);
    if (preg_match('#^https?://#i', $from)) {
        $path = parse_url($from, PHP_URL_PATH);
        $from = is_string($path) && $path !== '' ? $path : '/';
    }
    $from = preg_replace('#/+#', '/', $from) ?? $from;
    if ($from === '' || !str_starts_with($from, '/')) {
        $from = '/' . ltrim($from, '/');
    }
    if (str_contains($from, '..') || str_contains($from, ':')) {
        return '';
    }
    $base = defined('CFC_BASE') ? (string) CFC_BASE : '';
    if ($base !== '' && ($from === $base || str_starts_with($from, $base . '/'))) {
        $from = substr($from, strlen($base)) ?: '/';
    }
    if ($from !== '/' && !str_contains(basename($from), '.')) {
        $from = rtrim($from, '/') . '/';
    }
    return $from;
}

function cfc_redirect_normalize_to(string $to): string
{
    $to = trim(str_replace(["\0", "\r", "\n"], '', $to));
    if ($to === '') {
        return '';
    }
    if (preg_match('#^http://#i', $to)) {
        $to = 'https://' . substr($to, 7);
    }
    if (preg_match('#^https://#i', $to)) {
        $parts = parse_url($to);
        if (!is_array($parts) || empty($parts['host']) || !preg_match('/^[A-Za-z0-9.-]+$/', (string) $parts['host'])) {
            return '';
        }
        return $to;
    }
    if (str_starts_with($to, '//') || preg_match('#^[a-z][a-z0-9+.-]*:#i', $to)) {
        return '';
    }
    return cfc_redirect_normalize_from($to);
}

function cfc_redirect_normalize(array $row): ?array
{
    $from = cfc_redirect_normalize_from((string) ($row['from'] ?? ''));
    $to = cfc_redirect_normalize_to((string) ($row['to'] ?? ''));
    $code = (int) ($row['code'] ?? 301) === 302 ? 302 : 301;
    if ($from === '' || $to === '' || $from === $to) {
        return null;
    }
    if ($from === '/admin/' || str_starts_with($from, '/admin/')) {
        return null;
    }
    return ['from' => $from, 'to' => $to, 'code' => $code];
}

function cfc_redirects_commit(array $out): bool
{
    $fileOk = cfc_cms_write_json(cfc_redirects_file(), $out);
    if (cfc_db_ready()) {
        return cfc_db_redirects_save($out);
    }
    return $fileOk;
}

function cfc_redirects(bool $reload = false): array
{
    static $rows = null;
    if ($rows !== null && !$reload) {
        return $rows;
    }
    $decoded = null;
    if (cfc_db_ready()) {
        $decoded = cfc_db_redirects_load();
    } else {
        $raw = cfc_read(cfc_redirects_file());
        $decoded = $raw ? json_decode($raw, true) : null;
    }
    if (!is_array($decoded)) {
        $rows = cfc_redirects_defaults();
        return $rows;
    }
    $out = [];
    $seen = [];
    foreach ($decoded as $row) {
        if (!is_array($row)) {
            continue;
        }
        $item = cfc_redirect_normalize($row);
        if ($item === null || isset($seen[$item['from']])) {
            continue;
        }
        $seen[$item['from']] = true;
        $out[] = $item;
    }
    $rows = $out;
    return $rows;
}

function cfc_redirects_save(array $post): bool
{
    $rows = $post['redirects'] ?? [];
    if (!is_array($rows)) {
        $rows = [];
    }
    $out = [];
    $seen = [];
    $n = 0;
    foreach ($rows as $i => $row) {
        if ($i === '_ok' || !is_array($row)) {
            continue;
        }
        $item = cfc_redirect_normalize($row);
        if ($item === null || isset($seen[$item['from']])) {
            continue;
        }
        $seen[$item['from']] = true;
        $out[] = $item;
        if (++$n >= 100) {
            break;
        }
    }
    $ok = cfc_redirects_commit($out);
    if ($ok) {
        cfc_redirects(true);
    }
    return $ok;
}

function cfc_redirects_reset(): bool
{
    $ok = cfc_redirects_commit(cfc_redirects_defaults());
    if ($ok) {
        cfc_redirects(true);
    }
    return $ok;
}

function cfc_redirect_match(string $rawPath): ?array
{
    $key = cfc_redirect_normalize_from($rawPath);
    if ($key === '' || $key === '/') {
        return null;
    }
    foreach (cfc_redirects() as $row) {
        if ($row['from'] === $key) {
            return $row;
        }
    }
    return null;
}

function cfc_redirect_apply(array $row): never
{
    $to = str_replace(["\r", "\n", "\0"], '', (string) ($row['to'] ?? ''));
    $code = (int) ($row['code'] ?? 301) === 302 ? 302 : 301;
    if (preg_match('#^https://#i', $to)) {
        header('Location: ' . $to, true, $code);
        exit;
    }
    cfc_redirect(ltrim($to, '/'), $code);
}
