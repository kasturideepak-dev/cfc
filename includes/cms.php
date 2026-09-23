<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

function cfc_cms_file(): string
{
    return CFC_DATA . '/cms/content.json';
}

function cfc_cms_upload_dir(): string
{
    return CFC_ROOT . '/assets/uploads/cms';
}

function cfc_cms_schema(): array
{
    static $schema = null;
    if ($schema === null) {
        $schema = require CFC_ROOT . '/includes/cms-schema.php';
        foreach ($schema as $key => $page) {
            if (!is_array($page)) {
                continue;
            }
            if ($key !== 'site') {
                $schema[$key] = cfc_cms_attach_seo($page);
            }
        }
    }
    return $schema;
}

function cfc_cms_view_key(string $view): string
{
    return match ($view) {
        'media-hub' => 'media',
        'thank-you' => 'thankyou',
        default => $view,
    };
}

function cfc_cms_attach_seo(array $page): array
{
    $extra = [
        ['key' => 'primary_keyword', 'type' => 'text', 'label' => 'Primary keyword', 'default' => ''],
        ['key' => 'secondary_keyword', 'type' => 'text', 'label' => 'Secondary keyword', 'default' => ''],
        ['key' => 'keywords', 'type' => 'textarea', 'label' => 'Keywords (comma separated)', 'default' => ''],
        ['key' => 'seo_robots', 'type' => 'select', 'label' => 'Robots', 'options' => [
            'index,follow' => 'Index, follow',
            'noindex,follow' => 'Noindex, follow',
            'index,nofollow' => 'Index, nofollow',
            'noindex,nofollow' => 'Noindex, nofollow',
        ], 'default' => 'index,follow'],
        ['key' => 'og_title', 'type' => 'text', 'label' => 'Social title (optional)', 'default' => ''],
        ['key' => 'og_description', 'type' => 'textarea', 'label' => 'Social description (optional)', 'default' => ''],
    ];
    $found = false;
    foreach ($page['groups'] ?? [] as $i => $group) {
        $keys = array_column($group['fields'] ?? [], 'key');
        if (in_array('seo_title', $keys, true) || in_array('seo_description', $keys, true)) {
            $page['groups'][$i]['label'] = 'SEO';
            $page['groups'][$i]['fields'] = array_merge($group['fields'] ?? [], $extra);
            $found = true;
            break;
        }
    }
    if (!$found) {
        $page['groups'][] = [
            'label' => 'SEO',
            'fields' => array_merge([
                ['key' => 'seo_title', 'type' => 'text', 'label' => 'Page title', 'default' => ''],
                ['key' => 'seo_description', 'type' => 'textarea', 'label' => 'Meta description', 'default' => ''],
            ], $extra),
        ];
    }
    if (!isset($page['repeaters']) || !is_array($page['repeaters'])) {
        $page['repeaters'] = [];
    }
    $page['groups'][] = [
        'label' => 'Custom code snippets',
        'fields' => [
            ['key' => 'code_head', 'type' => 'code', 'label' => 'Header code (inside <head>)', 'default' => ''],
            ['key' => 'code_body', 'type' => 'code', 'label' => 'Body code (after <body>)', 'default' => ''],
            ['key' => 'code_footer', 'type' => 'code', 'label' => 'Footer code (before </body>)', 'default' => ''],
        ],
    ];
    $page['repeaters']['faqs'] = [
        'label' => 'FAQ',
        'add' => 'Add question',
        'fields' => [
            ['key' => 'question', 'type' => 'text', 'label' => 'Question'],
            ['key' => 'answer', 'type' => 'textarea', 'label' => 'Answer'],
        ],
        'default' => [],
    ];
    return $page;
}

function cfc_cms_keyword_list(string $pageKey): array
{
    $parts = [];
    foreach (['primary_keyword', 'secondary_keyword', 'keywords'] as $key) {
        $val = trim((string) preg_replace('/\s+/', ' ', cfc_cms($pageKey . '.' . $key)));
        if ($val === '') {
            continue;
        }
        foreach (preg_split('/\s*,\s*/', $val) ?: [] as $bit) {
            $bit = trim($bit);
            if ($bit !== '' && !in_array($bit, $parts, true)) {
                $parts[] = $bit;
            }
        }
    }
    return $parts;
}

function cfc_cms_faqs(string $pageKey): array
{
    $out = [];
    foreach (cfc_cms_items($pageKey . '.faqs') as $row) {
        $q = trim((string) ($row['question'] ?? ''));
        $a = trim((string) ($row['answer'] ?? ''));
        if ($q !== '' && $a !== '') {
            $out[] = ['question' => $q, 'answer' => $a];
        }
    }
    return $out;
}

function cfc_cms_robots(?string $val): string
{
    $val = strtolower(preg_replace('/\s+/', '', (string) $val) ?? '');
    $ok = ['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'];
    return in_array($val, $ok, true) ? $val : 'index,follow';
}

function cfc_cms_snippet(string $path): string
{
    $raw = str_replace("\0", '', cfc_cms($path));
    return trim($raw) === '' ? '' : $raw;
}

function cfc_cms_join_snippets(string ...$parts): string
{
    $out = [];
    foreach ($parts as $part) {
        $part = str_replace("\0", '', $part);
        if (trim($part) !== '') {
            $out[] = $part;
        }
    }
    return implode("\n", $out);
}

function cfc_faq_html(array $faqs): string
{
    if ($faqs === []) {
        return '';
    }
    $html = '<section class="cfc-faq" aria-label="Frequently asked questions"><h2>Frequently asked questions</h2><div class="cfc-faq__list">';
    foreach ($faqs as $i => $faq) {
        $id = 'faq-' . ($i + 1);
        $html .= '<details class="cfc-faq__item">';
        $html .= '<summary id="' . $id . '">' . cfc_e($faq['question']) . '</summary>';
        $html .= '<div class="cfc-faq__a">' . nl2br(cfc_e($faq['answer']), false) . '</div>';
        $html .= '</details>';
    }
    return $html . '</div></section>';
}

function cfc_cms_decode_store(?string $raw): array
{
    $decoded = $raw ? json_decode($raw, true) : [];
    if (!is_array($decoded)) {
        return [];
    }
    if ($decoded !== [] && function_exists('array_is_list') && array_is_list($decoded)) {
        return [];
    }
    return $decoded;
}

function cfc_cms_store(bool $reload = false): array
{
    static $data = null;
    if ($reload) {
        $data = null;
    }
    if ($data !== null) {
        return $data;
    }
    if (cfc_db_ready()) {
        $data = cfc_db_pages_load();
        return $data;
    }
    $data = cfc_cms_decode_store(cfc_read(cfc_cms_file()));
    return $data;
}

function cfc_cms_reload(): void
{
    $store = cfc_cms_store(true);
    $GLOBALS['cfc_cms_store_override'] = $store;
}

function cfc_cms_commit_store(array $store): bool
{
    $fileOk = cfc_cms_write_json(cfc_cms_file(), $store);
    if (cfc_db_ready()) {
        $dbOk = cfc_db_pages_save($store);
        if ($dbOk) {
            $GLOBALS['cfc_cms_store_override'] = $store;
            return true;
        }
        cfc_cms_notes('Could not save page copy to MySQL. Check the database connection.');
        return false;
    }
    if ($fileOk) {
        $GLOBALS['cfc_cms_store_override'] = $store;
    }
    return $fileOk;
}

function cfc_cms_store_live(): array
{
    if (isset($GLOBALS['cfc_cms_store_override']) && is_array($GLOBALS['cfc_cms_store_override'])) {
        return $GLOBALS['cfc_cms_store_override'];
    }
    return cfc_cms_store();
}

function cfc_cms_page_data(string $page): array
{
    $store = cfc_cms_store_live();
    return (isset($store[$page]) && is_array($store[$page])) ? $store[$page] : [];
}

function cfc_cms_notes(?string $msg = null): array
{
    if (!isset($GLOBALS['cfc_cms_notes']) || !is_array($GLOBALS['cfc_cms_notes'])) {
        $GLOBALS['cfc_cms_notes'] = [];
    }
    if ($msg !== null && $msg !== '') {
        $GLOBALS['cfc_cms_notes'][] = $msg;
    }
    return $GLOBALS['cfc_cms_notes'];
}

function cfc_cms_normalize_media(string $val): string
{
    $val = trim(str_replace('\\', '/', $val));
    $val = str_replace("\0", '', $val);
    if ($val === '' || str_contains($val, '..')) {
        return '';
    }
    if (preg_match('#^https?://#i', $val)) {
        return $val;
    }
    if (str_starts_with($val, 'data:image/')) {
        return $val;
    }
    $base = trim((string) CFC_BASE, '/');
    if ($base !== '' && (str_starts_with($val, '/' . $base . '/') || str_starts_with($val, $base . '/'))) {
        $val = (string) preg_replace('#^/?' . preg_quote($base, '#') . '/#', '', $val);
    }
    $val = ltrim($val, '/');
    if (preg_match('#(?:^|/)assets/(.+)$#', $val, $m)) {
        $val = $m[1];
    }
    if ($val === '' || str_contains($val, '..') || str_contains($val, ':')) {
        return '';
    }
    return $val;
}

function cfc_cms_href(string $url): string
{
    $url = trim($url);
    if ($url === '' || str_contains($url, "\0") || str_contains($url, "\n") || str_contains($url, "\r")) {
        return '';
    }
    if (preg_match('#^(https?://|mailto:|tel:)#i', $url)) {
        return $url;
    }
    if (str_contains($url, ':') || str_starts_with($url, '//') || str_contains($url, '..')) {
        return '';
    }
    return $url;
}

function cfc_youtube_id(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    if (preg_match('#(?:youtube\.com/embed/|youtube-nocookie\.com/embed/|youtu\.be/)([A-Za-z0-9_-]{6,})#i', $url, $m)) {
        return $m[1];
    }
    if (preg_match('#(?:youtube\.com/watch\?(?:.*&)?v=|youtube\.com/shorts/)([A-Za-z0-9_-]{6,})#i', $url, $m)) {
        return $m[1];
    }
    return '';
}

function cfc_lite_youtube(string $url, string $title = 'Video'): string
{
    $id = cfc_youtube_id($url);
    if ($id === '') {
        $src = cfc_cms_embed_src($url);
        return $src !== '' ? '<iframe src="' . cfc_e($src) . '" title="' . cfc_e($title) . '" allowfullscreen loading="lazy"></iframe>' : '';
    }
    $poster = 'https://i.ytimg.com/vi/' . rawurlencode($id) . '/hqdefault.jpg';
    return '<button type="button" class="cfc-yt" data-cfc-yt="' . cfc_e($id) . '" aria-label="' . cfc_e('Play ' . $title) . '">'
        . '<img src="' . cfc_e($poster) . '" alt="" width="480" height="360" loading="lazy" decoding="async">'
        . '<span class="cfc-yt__play" aria-hidden="true"></span>'
        . '</button>';
}

function cfc_cms_embed_src(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    if (preg_match('#(?:youtube\.com/embed/|youtube-nocookie\.com/embed/|youtu\.be/)([A-Za-z0-9_-]{6,})#i', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1] . '?rel=0&modestbranding=1';
    }
    if (preg_match('#(?:youtube\.com/watch\?(?:.*&)?v=|youtube\.com/shorts/)([A-Za-z0-9_-]{6,})#i', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1] . '?rel=0&modestbranding=1';
    }
    if (preg_match('#player\.vimeo\.com/video/(\d+)#i', $url, $m)) {
        return 'https://player.vimeo.com/video/' . $m[1];
    }
    if (preg_match('#vimeo\.com/(\d+)#i', $url, $m)) {
        return 'https://player.vimeo.com/video/' . $m[1];
    }
    return '';
}

function cfc_cms_object_position(string $val): string
{
    $val = trim($val);
    return preg_match('/^[0-9.%\s-]+$/', $val) ? $val : '50% 0%';
}

function cfc_cms_write_json(string $file, array $data): bool
{
    $dir = dirname($file);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        return false;
    }
    if ($data === [] && basename($file) === 'content.json') {
        $json = '{}';
    } else {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }
    if ($json === false) {
        return false;
    }
    // Unique per process: two writers sharing one .tmp path would overwrite each
    // other's temp file, and the loser's rename() then fails on a missing file.
    $tmp = $file . '.' . getmypid() . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (file_put_contents($tmp, $json . "\n", LOCK_EX) === false) {
        @unlink($tmp);
        return false;
    }
    if (!rename($tmp, $file)) {
        @unlink($tmp);
        return false;
    }
    @chmod($file, basename($file) === 'users.json' || basename($file) === 'secrets.json' ? 0600 : 0640);
    return true;
}

function cfc_cms_page_schema(string $page): ?array
{
    $schema = cfc_cms_schema();
    return isset($schema[$page]) && is_array($schema[$page]) ? $schema[$page] : null;
}

function cfc_cms_field_map(array $pageSchema): array
{
    $out = [];
    foreach ($pageSchema['groups'] ?? [] as $group) {
        foreach ($group['fields'] ?? [] as $field) {
            if (!empty($field['key'])) {
                $out[(string) $field['key']] = $field;
            }
        }
    }
    return $out;
}

function cfc_cms_default(string $page, string $key): string
{
    $schema = cfc_cms_page_schema($page);
    if (!$schema) {
        return '';
    }
    foreach (cfc_cms_field_map($schema) as $field) {
        if (($field['key'] ?? '') === $key) {
            return (string) ($field['default'] ?? '');
        }
    }
    return '';
}

function cfc_cms_repeater_defaults(string $page, string $key): array
{
    $schema = cfc_cms_page_schema($page);
    if (!$schema) {
        return [];
    }
    $rep = $schema['repeaters'][$key] ?? null;
    if (!is_array($rep)) {
        return [];
    }
    $items = $rep['default'] ?? [];
    return is_array($items) ? $items : [];
}

/**
 * Scalar CMS value. Empty stored values fall back to the schema default.
 */
function cfc_cms(string $path, ?string $fallback = null): string
{
    $parts = explode('.', $path, 2);
    $page = $parts[0];
    $key = $parts[1] ?? '';
    if ($key === '') {
        return (string) ($fallback ?? '');
    }
    $pageData = cfc_cms_page_data($page);
    if (isset($pageData[$key]) && is_scalar($pageData[$key])) {
        $val = (string) $pageData[$key];
        if ($val !== '') {
            return $val;
        }
    }
    $def = cfc_cms_default($page, $key);
    if ($def !== '') {
        return $def;
    }
    return (string) ($fallback ?? '');
}

function cfc_cms_e(string $path, ?string $fallback = null): string
{
    return cfc_e(cfc_cms($path, $fallback));
}

function cfc_cms_br(string $path, ?string $fallback = null): string
{
    return nl2br(cfc_e(cfc_cms($path, $fallback)), false);
}

function cfc_cms_src(string $path, string $fallbackPath = ''): string
{
    $val = cfc_cms_normalize_media(cfc_cms($path, $fallbackPath));
    if ($val === '') {
        $val = cfc_cms_normalize_media($fallbackPath);
    }
    if ($val === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $val) || str_starts_with($val, 'data:image/')) {
        return $val;
    }
    return cfc_media($val);
}

function cfc_cms_items(string $path): array
{
    $parts = explode('.', $path, 2);
    $page = $parts[0];
    $key = $parts[1] ?? '';
    if ($key === '') {
        return [];
    }
    $pageData = cfc_cms_page_data($page);
    if (array_key_exists($key, $pageData) && is_array($pageData[$key])) {
        return array_values($pageData[$key]);
    }
    return cfc_cms_repeater_defaults($page, $key);
}

function cfc_cms_rich(string $path, ?string $fallback = null): string
{
    $raw = trim(cfc_cms($path, $fallback));
    if ($raw === '') {
        return '';
    }
    if (preg_match('/<\/?[a-z][\s\S]*>/i', $raw)) {
        $html = strip_tags($raw, '<p><br><br/><h2><h3><h4><strong><b><em><i><a><ul><ol><li>');
        $html = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html) ?? $html;
        $html = preg_replace('#(href|src)\s*=\s*([\'"])\s*javascript:[^\'"]*\2#i', '$1="#"', $html) ?? $html;
        return $html;
    }
    $paras = preg_split('/\n\s*\n/', $raw) ?: [];
    $out = '';
    foreach ($paras as $p) {
        $p = trim($p);
        if ($p === '') {
            continue;
        }
        $out .= '<p>' . nl2br(cfc_e($p), false) . '</p>';
    }
    return $out;
}

function cfc_cms_digits(string $path, ?string $fallback = null): string
{
    return preg_replace('/\D+/', '', cfc_cms($path, $fallback)) ?? '';
}

function cfc_cms_social_icon(string $label): string
{
    $icons = [
        'instagram' => 'M7 2C4.2 2 2 4.2 2 7v10c0 2.8 2.2 5 5 5h10c2.8 0 5-2.2 5-5V7c0-2.8-2.2-5-5-5H7zm10 2c1.7 0 3 1.3 3 3v10c0 1.7-1.3 3-3 3H7c-1.7 0-3-1.3-3-3V7c0-1.7 1.3-3 3-3h10zm-5 3.2A4.8 4.8 0 1 0 16.8 12 4.8 4.8 0 0 0 12 7.2zm0 1.8A3 3 0 1 1 9 12a3 3 0 0 1 3-3zm4.9-2.4a1.1 1.1 0 1 0 1.1 1.1 1.1 1.1 0 0 0-1.1-1.1z',
        'pinterest' => 'M12 2a10 10 0 0 0-3.6 19.3c-.05-.8-.1-2 .02-2.9.1-.8.7-3.5.7-3.5s-.17-.35-.17-.87c0-.82.48-1.43 1.07-1.43.5 0 .75.38.75.83 0 .5-.32 1.26-.49 1.96-.14.58.3 1.06.87 1.06 1.05 0 1.85-1.1 1.85-2.7 0-1.41-1.01-2.4-2.46-2.4-1.68 0-2.67 1.26-2.67 2.56 0 .5.2 1.05.44 1.34.05.06.05.11.04.17l-.16.67c-.03.1-.09.14-.2.08-1.05-.49-1.7-2.02-1.7-3.25 0-2.65 1.93-5.08 5.55-5.08 2.92 0 5.18 2.08 5.18 4.86 0 2.9-1.83 5.24-4.37 5.24-.85 0-1.66-.44-1.93-.97l-.53 2c-.19.74-.7 1.67-1.05 2.24A10 10 0 1 0 12 2z',
        'facebook' => 'M14 8h3V5h-3c-2.2 0-4 1.8-4 4v2H8v3h2v7h3v-7h2.6l.4-3H13V9c0-.6.4-1 1-1z',
        'linkedin' => 'M6.5 9H3.7v11h2.8V9zM5.1 4C4.1 4 3.4 4.7 3.4 5.6S4.1 7.2 5.1 7.2 6.8 6.5 6.8 5.6 6.1 4 5.1 4zM20.3 20h-2.8v-5.4c0-1.3 0-2.9-1.8-2.9s-2 1.4-2 2.8V20H11V9h2.7v1.5h.04c.37-.7 1.28-1.5 2.64-1.5 2.8 0 3.32 1.86 3.32 4.3V20z',
        'x' => 'M17.5 4h2.7l-5.9 6.7L21.5 20h-5.4l-4.2-5.5L7 20H4.2l6.3-7.2L2.7 4h5.5l3.8 5L17.5 4zm-1 14.4h1.5L7.6 5.5H6L16.5 18.4z',
        'twitter' => 'M17.5 4h2.7l-5.9 6.7L21.5 20h-5.4l-4.2-5.5L7 20H4.2l6.3-7.2L2.7 4h5.5l3.8 5L17.5 4zm-1 14.4h1.5L7.6 5.5H6L16.5 18.4z',
        'youtube' => 'M21.6 7.2a2.8 2.8 0 0 0-2-2C18 5 12 5 12 5s-6 0-7.6.2a2.8 2.8 0 0 0-2 2A29 29 0 0 0 2 12a29 29 0 0 0 .4 4.8 2.8 2.8 0 0 0 2 2C6 19 12 19 12 19s6 0 7.6-.2a2.8 2.8 0 0 0 2-2A29 29 0 0 0 22 12a29 29 0 0 0-.4-4.8zM10 15.5v-7l6 3.5-6 3.5z',
    ];
    $needle = strtolower($label);
    if (isset($icons[$needle])) {
        return $icons[$needle];
    }
    foreach ($icons as $name => $path) {
        if (str_contains($needle, $name)) {
            return $path;
        }
    }
    return $icons['instagram'];
}

function cfc_cms_allowed_mimes(): array
{
    return [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'video/mp4' => 'mp4',
        'application/pdf' => 'pdf',
    ];
}

function cfc_cms_upload(array $file, string $prefix = 'file', array $allow = []): ?string
{
    $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
        cfc_cms_notes('A file was larger than the server upload limit.');
        return null;
    }
    if ($err !== UPLOAD_ERR_OK) {
        cfc_cms_notes('A file could not be uploaded.');
        return null;
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return null;
    }
    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 16 * 1024 * 1024) {
        cfc_cms_notes('A file was empty or larger than 16 MB.');
        return null;
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmp);
    $map = cfc_cms_allowed_mimes();
    if ($allow !== []) {
        $map = array_intersect_key($map, array_flip($allow));
    }
    if (!isset($map[$mime])) {
        cfc_cms_notes('A file type was not allowed.');
        return null;
    }
    $ext = $map[$mime];
    $dir = cfc_cms_upload_dir();
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        cfc_cms_notes('Upload folder is not writable.');
        return null;
    }
    if (!cfc_cms_disk_has_room($dir, $size)) {
        cfc_cms_notes('The server is low on disk space, so the upload was refused.');
        return null;
    }
    $safe = preg_replace('/[^a-z0-9]+/i', '-', strtolower($prefix)) ?: 'file';
    $name = $safe . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . '/' . $name;
    if (!move_uploaded_file($tmp, $dest)) {
        cfc_cms_notes('A file could not be saved.');
        return null;
    }
    if (str_starts_with($mime, 'image/') && $mime !== 'image/gif') {
        $webp = cfc_cms_optimize_to_webp($dest, 1920);
        if ($webp) {
            return $webp;
        }
    }
    if ($mime === 'image/gif' && !cfc_cms_gif_is_animated($dest)) {
        $webp = cfc_cms_optimize_to_webp($dest, 1920);
        if ($webp) {
            return $webp;
        }
    }
    return 'uploads/cms/' . $name;
}

function cfc_cms_gif_is_animated(string $abs): bool
{
    $raw = @file_get_contents($abs, false, null, 0, 1024 * 1024);
    if (!is_string($raw) || $raw === '') {
        return false;
    }
    return substr_count($raw, "\x00\x21\xF9\x04") >= 2;
}

function cfc_cms_optimize_to_webp(string $abs, int $maxW = 1920): ?string
{
    if (!is_file($abs) || !function_exists('imagewebp')) {
        return null;
    }
    $info = @getimagesize($abs);
    if (!$info) {
        return null;
    }
    $w = (int) $info[0];
    $size = (int) filesize($abs);
    $mime = (string) ($info['mime'] ?? '');
    if ($mime === 'image/webp' && $w <= $maxW && $size > 0 && $size <= 250000) {
        return 'uploads/cms/' . basename($abs);
    }
    $webpAbs = (string) preg_replace('/\.(jpe?g|png|gif|webp)$/i', '.webp', $abs);
    if ($webpAbs === '' || !str_ends_with(strtolower($webpAbs), '.webp')) {
        $webpAbs = $abs . '.webp';
    }
    $tmpAbs = $webpAbs . '.tmp';
    if (!cfc_cms_make_thumb($abs, $tmpAbs, $maxW) || !is_file($tmpAbs) || filesize($tmpAbs) < 32) {
        @unlink($tmpAbs);
        return null;
    }
    if (!rename($tmpAbs, $webpAbs)) {
        @unlink($tmpAbs);
        return null;
    }
    @chmod($webpAbs, 0644);
    if (realpath($abs) !== realpath($webpAbs)) {
        @unlink($abs);
    }
    return 'uploads/cms/' . basename($webpAbs);
}

function cfc_cms_make_thumb(string $srcAbs, string $destAbs, int $maxW = 720): bool
{
    if (!function_exists('imagecreatetruecolor')) {
        return copy($srcAbs, $destAbs);
    }
    $info = @getimagesize($srcAbs);
    if (!$info) {
        return copy($srcAbs, $destAbs);
    }
    $w = (int) $info[0];
    $h = (int) $info[1];
    $type = (int) $info[2];
    $src = match ($type) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($srcAbs),
        IMAGETYPE_PNG => @imagecreatefrompng($srcAbs),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($srcAbs) : false,
        IMAGETYPE_GIF => @imagecreatefromgif($srcAbs),
        default => false,
    };
    if (!$src) {
        return copy($srcAbs, $destAbs);
    }
    if ($w < 1 || $h < 1 || $w > 8000 || $h > 8000 || ($w * $h) > 40000000) {
        imagedestroy($src);
        return false;
    }
    if ($w > $maxW) {
        $nw = $maxW;
        $nh = max(1, (int) round($h * ($maxW / $w)));
    } else {
        $nw = $w;
        $nh = $h;
    }
    $dst = imagecreatetruecolor($nw, $nh);
    if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $ok = function_exists('imagewebp') ? imagewebp($dst, $destAbs, 82) : imagejpeg($dst, $destAbs, 85);
    imagedestroy($src);
    imagedestroy($dst);
    return (bool) $ok;
}

function cfc_cms_save_page(string $page, array $post, array $files): bool
{
    $schema = cfc_cms_page_schema($page);
    if (!$schema) {
        return false;
    }
    $store = cfc_cms_store_live();
    if (!isset($store[$page]) || !is_array($store[$page])) {
        $store[$page] = [];
    }
    $clear = $post['clear'] ?? [];
    $posted = $post['fields'] ?? [];
    $uploads = $files['uploads'] ?? [];

    foreach (cfc_cms_field_map($schema) as $key => $field) {
        $type = (string) ($field['type'] ?? 'text');
        if ($type === 'image' || $type === 'file') {
            if (!empty($clear[$key])) {
                $store[$page][$key] = '';
                continue;
            }
            $allow = $type === 'image'
                ? ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
                : array_keys(cfc_cms_allowed_mimes());
            $uploaded = null;
            if (isset($uploads['name'][$key]) && is_string($uploads['name'][$key])) {
                $uploaded = cfc_cms_upload([
                    'name' => $uploads['name'][$key],
                    'type' => $uploads['type'][$key] ?? '',
                    'tmp_name' => $uploads['tmp_name'][$key] ?? '',
                    'error' => $uploads['error'][$key] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $uploads['size'][$key] ?? 0,
                ], $page . '-' . $key, $allow);
            }
            if ($uploaded) {
                $store[$page][$key] = $uploaded;
            } elseif (isset($posted[$key])) {
                $store[$page][$key] = cfc_cms_normalize_media(trim((string) $posted[$key]));
            }
            continue;
        }
        if (isset($posted[$key])) {
            $val = (string) $posted[$key];
            if ($type === 'url') {
                $val = cfc_cms_href($val);
            }
            if ($type === 'select') {
                $opts = array_map('strval', array_keys($field['options'] ?? []));
                if (!in_array($val, $opts, true)) {
                    $val = (string) ($field['default'] ?? ($opts[0] ?? ''));
                }
            }
            if ($key === 'seo_robots') {
                $val = cfc_cms_robots($val);
            }
            $store[$page][$key] = $val;
        }
    }

    foreach ($schema['repeaters'] ?? [] as $repKey => $rep) {
        if (!isset($post['repeaters']) || !array_key_exists((string) $repKey, $post['repeaters'])) {
            continue;
        }
        $store[$page][$repKey] = cfc_cms_collect_repeater($page, (string) $repKey, $rep, $post, $files);
    }

    return cfc_cms_commit_store($store);
}

function cfc_cms_collect_repeater(string $page, string $repKey, array $rep, array $post, array $files): array
{
    $rows = $post['repeaters'][$repKey] ?? [];
    if (!is_array($rows)) {
        return [];
    }
    $clear = $post['clear_repeaters'][$repKey] ?? [];
    $up = $files['uploads_repeaters'] ?? [];
    $out = [];
    foreach ($rows as $i => $row) {
        if (!is_array($row) || $i === '_ok' || $i === '_present') {
            continue;
        }
        $item = [];
        foreach ($rep['fields'] ?? [] as $field) {
            $fkey = (string) ($field['key'] ?? '');
            if ($fkey === '') {
                continue;
            }
            $type = (string) ($field['type'] ?? 'text');
            if ($type === 'image' || $type === 'file') {
                if (!empty($clear[$i][$fkey])) {
                    $item[$fkey] = '';
                    continue;
                }
                $allow = $type === 'image'
                    ? ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
                    : array_keys(cfc_cms_allowed_mimes());
                $uploaded = null;
                if (isset($up['name'][$repKey][$i][$fkey]) && is_string($up['name'][$repKey][$i][$fkey])) {
                    $uploaded = cfc_cms_upload([
                        'name' => $up['name'][$repKey][$i][$fkey],
                        'type' => $up['type'][$repKey][$i][$fkey] ?? '',
                        'tmp_name' => $up['tmp_name'][$repKey][$i][$fkey] ?? '',
                        'error' => $up['error'][$repKey][$i][$fkey] ?? UPLOAD_ERR_NO_FILE,
                        'size' => $up['size'][$repKey][$i][$fkey] ?? 0,
                    ], $page . '-' . $repKey . '-' . $fkey, $allow);
                }
                $item[$fkey] = $uploaded ?: cfc_cms_normalize_media(trim((string) ($row[$fkey] ?? '')));
                continue;
            }
            $val = (string) ($row[$fkey] ?? '');
            if ($type === 'url') {
                $val = cfc_cms_href($val);
            }
            $item[$fkey] = $val;
        }
        $has = false;
        foreach ($item as $v) {
            if (trim((string) $v) !== '') {
                $has = true;
                break;
            }
        }
        if ($has) {
            $out[] = $item;
        }
    }
    return $out;
}

function cfc_cms_reset_page(string $page): bool
{
    $store = cfc_cms_store_live();
    unset($store[$page]);
    return cfc_cms_commit_store($store);
}

function cfc_cms_gallery(): array
{
    if (cfc_db_ready()) {
        $fromDb = cfc_store_get('gallery');
        if ($fromDb !== null) {
            return $fromDb;
        }
    }
    $raw = cfc_read(CFC_DATA . '/gallery.json');
    $data = $raw ? json_decode($raw, true) : [];
    return is_array($data) ? $data : [];
}

function cfc_cms_persist_gallery(array $out): bool
{
    $fileOk = cfc_cms_write_json(CFC_DATA . '/gallery.json', $out);
    if (cfc_db_ready()) {
        $dbOk = cfc_store_set('gallery', $out);
        if (!$dbOk) {
            cfc_cms_notes('Could not save gallery to MySQL. Check the database connection.');
        }
        return $dbOk;
    }
    return $fileOk;
}

function cfc_cms_last_note(string $fallback): string
{
    $notes = cfc_cms_notes();
    $last = $notes === [] ? '' : trim((string) end($notes));
    return $last !== '' ? $last : $fallback;
}

/**
 * A posted value can be an array when the form is edited by hand, and casting
 * one to string raises a warning that would land in the response. Coerce here.
 */
function cfc_cms_post_str(mixed $value): string
{
    return is_scalar($value) ? (string) $value : '';
}

/**
 * Exclusive lock for the gallery read-modify-write. Bulk uploads save once per
 * file, so two of them landing together would otherwise read the same gallery
 * and the second would silently drop the first one's photo.
 */
function cfc_cms_gallery_lock()
{
    $fh = @fopen(CFC_DATA . '/gallery.lock', 'c');
    if ($fh === false) {
        return null;
    }
    if (!@flock($fh, LOCK_EX)) {
        @fclose($fh);
        return null;
    }
    return $fh;
}

function cfc_cms_gallery_unlock($fh): void
{
    if ($fh) {
        @flock($fh, LOCK_UN);
        @fclose($fh);
    }
}

/**
 * Cheap O(1) check: converting an image needs room for the original, the WebP
 * and the thumbnail, and a full disk breaks far more than the gallery.
 */
function cfc_cms_disk_has_room(string $dir, int $incoming = 0): bool
{
    $min = max(0, (int) cfc_config('upload_min_free_bytes', 209715200));
    if ($min === 0) {
        return true;
    }
    $free = @disk_free_space($dir);
    if ($free === false) {
        return true; // cannot tell; do not block the editor on a guess
    }
    return $free > $min + ($incoming * 3);
}

/**
 * A ceiling on bulk uploads, not a throttle. One request per photo is the whole
 * design, so this is set far above any human pace and only catches a runaway
 * loop or an account being abused. Keyed per editor, falling back to the IP.
 */
function cfc_cms_gallery_upload_rate_ok(): bool
{
    $who = (string) ($_SESSION['cfc_admin'] ?? '');
    if ($who === '') {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $who = filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }
    if ($who === '') {
        return true;
    }
    $limit = max(10, (int) cfc_config('gallery_upload_limit', 600));
    $window = max(60, (int) cfc_config('gallery_upload_window', 3600));
    return cfc_rate_limit_hit('gallery-upload:' . $who, $limit, $window);
}

/** Empty when the file really is WebP; otherwise why it is not. */
function cfc_cms_gallery_convert_note(string $path): string
{
    $lower = strtolower($path);
    if (str_ends_with($lower, '.webp')) {
        return '';
    }
    if (str_ends_with($lower, '.gif')) {
        return 'added as an animated GIF, which cannot be converted to WebP';
    }
    return 'added, but it could not be converted to WebP, so it is not compressed';
}

function cfc_cms_gallery_clean(array $img): array
{
    $full = cfc_cms_normalize_media((string) ($img['full'] ?? ''));
    $thumb = cfc_cms_normalize_media((string) ($img['thumb'] ?? ''));
    if ($full === '' && $thumb === '') {
        return [];
    }
    return [
        'thumb' => $thumb !== '' ? $thumb : $full,
        'full' => $full !== '' ? $full : $thumb,
        'w' => min(10000, max(1, (int) ($img['w'] ?? 720))),
        'h' => min(10000, max(1, (int) ($img['h'] ?? 540))),
    ];
}

/**
 * Resolve a "sectionIndex:imageIndex" coordinate against the saved gallery.
 * Reordering posts only these coordinates, never image paths, so an edited form
 * cannot point the gallery at a file that was not already uploaded here.
 */
function cfc_cms_gallery_image_at(array $current, string $ref): array
{
    if (!preg_match('/^(\d+):(\d+)$/', $ref, $m)) {
        return [];
    }
    $img = $current[(int) $m[1]]['images'][(int) $m[2]] ?? null;
    return is_array($img) ? cfc_cms_gallery_clean($img) : [];
}

/**
 * Rebuild the whole gallery from the posted category order. Each category sends
 * its images as one comma separated list of coordinates, so dragging a photo to
 * another category and dragging it up or down are the same operation, and the
 * form stays well under max_input_vars no matter how many photos there are.
 */
function cfc_cms_save_gallery(array $post, array $files): bool
{
    $current = cfc_cms_gallery();
    $sections = $post['sections'] ?? null;
    if (!is_array($sections) || $sections === []) {
        cfc_cms_notes('The form did not send any categories. Reload the page and try again.');
        return false;
    }
    if (count($sections) > 200) {
        cfc_cms_notes('That is more categories than the gallery supports.');
        return false;
    }
    $seen = [];
    $out = [];
    foreach ($sections as $key => $meta) {
        if (!is_array($meta)) {
            continue;
        }
        $key = (string) $key;
        $title = trim(cfc_cms_post_str($meta['title'] ?? ''));
        if ($title === '' && ctype_digit($key) && isset($current[(int) $key])) {
            $title = trim((string) ($current[(int) $key]['title'] ?? ''));
        }
        if ($title !== '' && strlen($title) > 200) {
            $title = trim(cfc_clip($title, 200));
            cfc_cms_notes('A category name was shortened to 200 characters.');
        }
        $images = [];
        foreach (explode(',', cfc_cms_post_str($meta['order'] ?? '')) as $ref) {
            if (!preg_match('/^\s*(\d{1,9}):(\d{1,9})\s*$/', $ref, $m)) {
                continue;
            }
            $canon = (int) $m[1] . ':' . (int) $m[2]; // 00:07 and 0:7 are one photo
            if (isset($seen[$canon])) {
                continue;
            }
            $img = cfc_cms_gallery_image_at($current, $canon);
            if ($img === []) {
                continue;
            }
            $seen[$canon] = true;
            $images[] = $img;
        }
        foreach (cfc_cms_gallery_add_files($files, $key) as $img) {
            $images[] = $img;
        }
        if ($title === '' && $images === []) {
            continue;
        }
        $out[] = [
            'title' => $title,
            'level' => cfc_cms_post_str($meta['level'] ?? 'h2') === 'h1' ? 'h1' : 'h2',
            'images' => $images,
        ];
    }
    if ($out === []) {
        cfc_cms_notes('Name at least one category before saving.');
        return false;
    }
    return cfc_cms_persist_gallery($out);
}

/**
 * One image per request. The bulk uploader posts files one at a time so it is
 * never capped by max_file_uploads or post_max_size; each file is converted,
 * appended to the chosen category and saved straight away.
 */
function cfc_cms_gallery_add_one(array $file, int $si, string $expectTitle = ''): array
{
    $current = cfc_cms_gallery();
    if ($si < 0 || !isset($current[$si]) || !is_array($current[$si])) {
        return ['ok' => false, 'fatal' => true, 'error' => 'That category no longer exists. Reload the page.'];
    }
    // The browser picked this category by position. If the categories have been
    // renamed or reordered since the page loaded, the position now means a
    // different category, so refuse rather than file the photo in the wrong one.
    if ($expectTitle !== '' && trim((string) ($current[$si]['title'] ?? '')) !== $expectTitle) {
        return ['ok' => false, 'fatal' => true, 'error' => 'The categories changed since this page loaded. Reload and upload again.'];
    }

    // Both of these will reject every remaining file in the batch as well, so
    // they are flagged fatal and checked before any conversion work is done.
    if (!cfc_cms_gallery_upload_rate_ok()) {
        return [
            'ok' => false,
            'fatal' => true,
            'error' => 'The hourly upload limit has been reached. Wait a while, then upload the rest.',
        ];
    }
    if (!cfc_cms_disk_has_room(cfc_cms_upload_dir(), (int) ($file['size'] ?? 0))) {
        return [
            'ok' => false,
            'fatal' => true,
            'error' => 'The server is low on disk space. Free some space before uploading more.',
        ];
    }

    $path = cfc_cms_upload($file, 'gallery-' . $si, ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
    if ($path === null) {
        return ['ok' => false, 'error' => cfc_cms_last_note('The image could not be uploaded.')];
    }
    $entry = cfc_cms_gallery_entry($path);

    // Re-read inside the lock: converting the image takes long enough that
    // another upload could have saved in the meantime.
    $lock = cfc_cms_gallery_lock();
    $current = cfc_cms_gallery();
    if (!isset($current[$si]) || !is_array($current[$si])) {
        cfc_cms_gallery_unlock($lock);
        return ['ok' => false, 'fatal' => true, 'error' => 'That category was removed while the image was converting. Reload the page.'];
    }
    $images = $current[$si]['images'] ?? [];
    if (!is_array($images)) {
        $images = [];
    }
    $images[] = $entry;
    $current[$si]['images'] = array_values($images);
    $saved = cfc_cms_persist_gallery($current);
    cfc_cms_gallery_unlock($lock);

    if (!$saved) {
        return ['ok' => false, 'error' => cfc_cms_last_note('The image was converted but could not be saved.')];
    }
    $result = ['ok' => true, 'image' => $entry];
    $note = cfc_cms_gallery_convert_note($path);
    if ($note !== '') {
        $result['warning'] = $note;
    }
    return $result;
}

function cfc_cms_gallery_add_files(array $files, int|string $si): array
{
    $bag = $files['add'] ?? null;
    if (!is_array($bag) || !isset($bag['name'][$si]) || !is_array($bag['name'][$si])) {
        return [];
    }
    $out = [];
    foreach ($bag['name'][$si] as $i => $name) {
        $path = cfc_cms_upload([
            'name' => $name,
            'type' => $bag['type'][$si][$i] ?? '',
            'tmp_name' => $bag['tmp_name'][$si][$i] ?? '',
            'error' => $bag['error'][$si][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $bag['size'][$si][$i] ?? 0,
        ], 'gallery-' . $si, ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
        if ($path) {
            $out[] = cfc_cms_gallery_entry($path);
        }
    }
    return $out;
}

function cfc_cms_gallery_entry(string $fullPath): array
{
    $abs = CFC_ROOT . '/assets/' . ltrim($fullPath, '/');
    $w = 720;
    $h = 480;
    $info = @getimagesize($abs);
    if ($info) {
        $w = (int) $info[0];
        $h = (int) $info[1];
    }
    $thumbRel = $fullPath;
    $dir = cfc_cms_upload_dir();
    $base = pathinfo($abs, PATHINFO_FILENAME);
    // Without imagewebp, make_thumb falls back to JPEG, so do not name it .webp.
    $thumbExt = function_exists('imagewebp') ? 'webp' : 'jpg';
    $thumbAbs = $dir . '/' . $base . '-thumb.' . $thumbExt;
    if (cfc_cms_make_thumb($abs, $thumbAbs, 720)) {
        $thumbRel = 'uploads/cms/' . $base . '-thumb.' . $thumbExt;
        $tinfo = @getimagesize($thumbAbs);
        if ($tinfo) {
            $w = (int) $tinfo[0];
            $h = (int) $tinfo[1];
        }
    }
    return ['thumb' => $thumbRel, 'full' => $fullPath, 'w' => $w, 'h' => $h];
}

function cfc_cms_media_hub_file(): string
{
    return CFC_DATA . '/media-hub.json';
}

function cfc_cms_media_hub_from_file(): array
{
    $raw = cfc_read(cfc_cms_media_hub_file());
    $data = $raw ? json_decode($raw, true) : [];
    return is_array($data) ? $data : [];
}

function cfc_cms_persist_media_hub(array $out): bool
{
    $fileOk = cfc_cms_write_json(cfc_cms_media_hub_file(), $out);
    if (cfc_db_ready()) {
        $dbOk = cfc_store_set('media_hub', $out);
        if (!$dbOk) {
            cfc_cms_notes('Could not save Media Hub to MySQL. Check the database connection.');
        }
        return $dbOk;
    }
    return $fileOk;
}

function cfc_cms_media_hub(): array
{
    $fileData = cfc_cms_media_hub_from_file();
    $fileTime = is_file(cfc_cms_media_hub_file()) ? (int) filemtime(cfc_cms_media_hub_file()) : 0;

    if (cfc_db_ready()) {
        $row = cfc_store_row('media_hub');
        if ($row !== null) {
            $dbTime = strtotime((string) ($row['updated_at'] ?? '')) ?: 0;
            $dbData = is_array($row['value'] ?? null) ? $row['value'] : [];
            if ($dbTime >= $fileTime) {
                return $dbData;
            }
            if ($fileData !== [] && $fileData !== $dbData) {
                cfc_store_set('media_hub', $fileData);
            }
            return $fileData;
        }
        if ($fileData !== []) {
            cfc_store_set('media_hub', $fileData);
        }
    }
    return $fileData;
}

function cfc_cms_save_media_hub(array $post, array $files): bool
{
    $current = cfc_cms_media_hub();
    $hrefs = $post['href'] ?? [];
    $delete = $post['delete'] ?? [];
    $replaceAt = preg_match('/^\d+$/', (string) ($post['replace_at'] ?? '')) ? (int) $post['replace_at'] : null;
    $out = [];
    foreach ($current as $i => $row) {
        if (!empty($delete[$i])) {
            continue;
        }
        $file = (string) ($row['file'] ?? '');
        if ($replaceAt === (int) $i && isset($files['replace_one'])) {
            $uploaded = cfc_cms_upload($files['replace_one'], 'media-hub', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
            if ($uploaded) {
                $file = $uploaded;
            }
        }
        $href = cfc_cms_href(trim((string) ($hrefs[$i] ?? ($row['href'] ?? ''))));
        $out[] = [
            'href' => $href,
            'type' => (string) ($row['type'] ?? 'video'),
            'file' => $file,
        ];
    }

    $additions = [];
    $known = cfc_instagram_known_codes($out);
    $addHref = cfc_cms_href(trim((string) ($post['add_href'] ?? '')));
    $addFile = $files['add_file'] ?? null;
    $uploaded = null;
    if (is_array($addFile)) {
        $uploaded = cfc_cms_upload($addFile, 'media-hub', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
    }
    if ($uploaded) {
        $href = $addHref !== '' ? $addHref : 'https://www.instagram.com/' . cfc_instagram_username() . '/';
        $code = cfc_instagram_shortcode($href);
        if ($code === '' || !isset($known[$code])) {
            $additions[] = [
                'href' => $href,
                'type' => 'video',
                'file' => $uploaded,
            ];
            if ($code !== '') {
                $known[$code] = true;
            }
        }
    }

    $urls = cfc_instagram_parse_urls((string) ($post['add_urls'] ?? ''));
    if ($addHref !== '' && $uploaded === null) {
        $urls[] = $addHref;
    }
    if ($urls !== []) {
        @set_time_limit(90);
    }
    foreach (cfc_instagram_import_rows($urls, $known) as $row) {
        $additions[] = $row;
        $code = cfc_instagram_shortcode((string) ($row['href'] ?? ''));
        if ($code !== '') {
            $known[$code] = true;
        }
    }

    $out = array_merge($additions, $out);
    return cfc_cms_persist_media_hub($out);
}

function cfc_cms_save_blog_index(array $index): bool
{
    $clean = [];
    foreach ($index as $row) {
        if (!is_array($row)) {
            continue;
        }
        unset($row['body']);
        $clean[] = $row;
    }
    $ok = cfc_cms_write_json(CFC_DATA . '/blog-index.json', array_values($clean));
    if ($ok) {
        cfc_blog_index(true);
    }
    return $ok;
}

function cfc_cms_slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '';
    return trim($value, '-') ?: 'post';
}

function cfc_cms_sanitize_html(string $html): string
{
    $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? $html;
    $html = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html) ?? $html;
    $html = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html) ?? $html;
    $html = preg_replace('#(href|src)\s*=\s*([\'"])\s*javascript:[^\'"]*\2#i', '$1="#"', $html) ?? $html;
    return $html;
}

function cfc_cms_blog_rel(string $rel): ?string
{
    $rel = str_replace('\\', '/', ltrim($rel, '/'));
    if ($rel === '' || str_contains($rel, '..') || !str_starts_with($rel, 'data/blog/')) {
        return null;
    }
    if (!str_ends_with($rel, '.html') && !str_ends_with($rel, '.htm')) {
        return null;
    }
    return $rel;
}

function cfc_cms_save_blog_post(array $post, array $files, ?string $existingPath = null): bool
{
    $index = cfc_blog_index();
    $title = trim((string) ($post['title'] ?? ''));
    $excerpt = trim((string) ($post['excerpt'] ?? ''));
    $category = cfc_cms_slugify((string) ($post['category'] ?? 'franchise'));
    $categoryName = trim((string) ($post['category_name'] ?? ''));
    if ($categoryName === '') {
        $categoryName = $category !== '' ? ucwords(str_replace('-', ' ', $category)) : '';
    }
    $body = cfc_cms_sanitize_html((string) ($post['body'] ?? ''));
    $date = trim((string) ($post['date'] ?? date('Y-m-d')));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $date = date('Y-m-d');
    }

    $found = null;
    $foundI = null;
    if ($existingPath) {
        foreach ($index as $i => $row) {
            if (($row['path'] ?? '') === $existingPath || ($row['slug'] ?? '') === $existingPath) {
                $found = $row;
                $foundI = $i;
                break;
            }
        }
        if ($found === null) {
            cfc_cms_notes('That blog post was not found.');
            return false;
        }
    }

    $slug = $found['slug'] ?? cfc_cms_slugify((string) ($post['slug'] ?? $title));
    $path = $found['path'] ?? (str_replace('-', '/', $date) . '/' . $slug);
    $fileRel = cfc_cms_blog_rel((string) ($found['file'] ?? ('data/blog/' . $path . '/index.html')));
    if ($fileRel === null) {
        $fileRel = 'data/blog/' . $path . '/index.html';
    }
    if ($foundI === null) {
        $n = 2;
        $baseSlug = $slug;
        while (cfc_blog_find($path)) {
            $slug = $baseSlug . '-' . $n;
            $path = str_replace('-', '/', $date) . '/' . $slug;
            $fileRel = 'data/blog/' . $path . '/index.html';
            $n++;
            if ($n > 50) {
                return false;
            }
        }
    }
    $image = cfc_cms_normalize_media((string) ($found['image'] ?? ''));
    if (!empty($post['clear_image'])) {
        $image = '';
    }
    $uploaded = isset($files['image_file']) ? cfc_cms_upload($files['image_file'], 'blog', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']) : null;
    if ($uploaded) {
        $image = $uploaded;
    } elseif (isset($post['image']) && trim((string) $post['image']) !== '') {
        $image = cfc_cms_normalize_media((string) $post['image']);
    }

    $abs = CFC_ROOT . '/' . $fileRel;
    $dir = dirname($abs);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        return false;
    }
    $html = "<div class=\"page-content\">\n" . $body . "\n</div>\n";
    if (file_put_contents($abs, $html) === false) {
        return false;
    }

    $faqs = [];
    foreach ($post['repeaters']['faqs'] ?? [] as $row) {
        if (!is_array($row)) {
            continue;
        }
        $q = trim((string) ($row['question'] ?? ''));
        $a = trim((string) ($row['answer'] ?? ''));
        if ($q !== '' && $a !== '') {
            $faqs[] = ['question' => $q, 'answer' => $a];
        }
    }
    $robots = cfc_cms_robots((string) ($post['seo_robots'] ?? 'index,follow'));

    $entry = [
        'path' => $path,
        'slug' => $slug,
        'date' => $date,
        'file' => $fileRel,
        'source' => $found['source'] ?? 'cms',
        'title' => $title !== '' ? $title : $slug,
        'excerpt' => $excerpt,
        'category' => $category,
        'category_name' => $categoryName,
        'image' => $image,
        'body' => $body,
        'seo_title' => trim((string) ($post['seo_title'] ?? '')),
        'seo_description' => trim((string) ($post['seo_description'] ?? '')),
        'primary_keyword' => trim((string) ($post['primary_keyword'] ?? '')),
        'secondary_keyword' => trim((string) ($post['secondary_keyword'] ?? '')),
        'keywords' => trim((string) ($post['keywords'] ?? '')),
        'seo_robots' => $robots,
        'og_title' => trim((string) ($post['og_title'] ?? '')),
        'og_description' => trim((string) ($post['og_description'] ?? '')),
        'code_head' => (string) ($post['code_head'] ?? ''),
        'code_body' => (string) ($post['code_body'] ?? ''),
        'code_footer' => (string) ($post['code_footer'] ?? ''),
        'faqs' => $faqs,
    ];
    $indexEntry = $entry;
    unset($indexEntry['body']);
    if ($foundI !== null) {
        $index[$foundI] = $indexEntry;
    } else {
        array_unshift($index, $indexEntry);
    }
    $fileOk = cfc_cms_save_blog_index($index);
    if (cfc_db_configured()) {
        $dbOk = cfc_db_upsert_post($entry);
        if (!$dbOk) {
            cfc_cms_notes('Saved on disk but MySQL did not update. Check database credentials.');
        }
        return $fileOk || $dbOk;
    }
    return $fileOk;
}

function cfc_cms_delete_blog_post(string $path): bool
{
    $index = cfc_blog_index();
    $next = [];
    $removed = false;
    foreach ($index as $row) {
        if (($row['path'] ?? '') === $path || ($row['slug'] ?? '') === $path) {
            $removed = true;
            continue;
        }
        $next[] = $row;
    }
    $fileOk = $removed && cfc_cms_save_blog_index($next);
    $dbOk = cfc_db_configured() && cfc_db_delete_post($path);
    return $fileOk || $dbOk;
}
