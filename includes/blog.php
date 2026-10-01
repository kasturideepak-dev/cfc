<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

function cfc_blog_index(bool $reload = false): array
{
    static $index = null;
    if ($index !== null && !$reload) {
        return $index;
    }
    if (cfc_db_ready()) {
        $index = cfc_db_fetch_posts();
        return $index;
    }
    $index = cfc_blog_index_from_file();
    return $index;
}

/**
 * Permalink structures the blog can serve.
 *
 * The stored path stays date-based whatever is selected. It is the storage key:
 * cfc_db_find_post() looks posts up by it and cfc_blog_file() resolves the
 * imported HTML from it. Only the public URL changes, so switching structure
 * rewrites no rows and is reversible.
 */
function cfc_blog_permalinks(): array
{
    return [
        'day_name' => ['label' => 'Day and name', 'example' => '/2026/02/24/sample-post/'],
        'month_name' => ['label' => 'Month and name', 'example' => '/2026/02/sample-post/'],
        'blog_name' => ['label' => 'Blog and name', 'example' => '/blog/sample-post/'],
        'name' => ['label' => 'Post name only', 'example' => '/sample-post/'],
    ];
}

function cfc_blog_permalink(bool $reload = false): string
{
    static $value = null;
    if ($value !== null && !$reload) {
        return $value;
    }
    $raw = cfc_db_ready() ? (string) (cfc_setting_get('blog_permalink') ?? '') : '';
    $value = isset(cfc_blog_permalinks()[$raw]) ? $raw : 'day_name';
    return $value;
}

function cfc_blog_set_permalink(string $structure): bool
{
    if (!isset(cfc_blog_permalinks()[$structure])) {
        return false;
    }
    $ok = cfc_setting_set('blog_permalink', $structure);
    if ($ok) {
        cfc_blog_permalink(true);
    }
    return $ok;
}

/** Year, month and day for a post, from its stored path or its date column. */
function cfc_blog_post_date_parts(array $post): array
{
    $path = trim((string) ($post['path'] ?? ''), '/');
    if (preg_match('#^(\d{4})/(\d{2})/(\d{2})(?:/|$)#', $path, $m)) {
        return [$m[1], $m[2], $m[3]];
    }
    $stamp = strtotime((string) ($post['date'] ?? ''));
    return $stamp === false ? ['', '', ''] : [date('Y', $stamp), date('m', $stamp), date('d', $stamp)];
}

/**
 * The post's public URL under a given structure, relative and slash-ended.
 * Falls back to the dated form when a post has no usable date, so a URL is
 * never silently truncated to something that would collide with another post.
 */
function cfc_blog_post_url_as(array $post, string $structure): string
{
    $path = trim((string) ($post['path'] ?? ''), '/');
    $slug = trim((string) ($post['slug'] ?? ''), '/');
    if ($slug === '' && $path !== '') {
        $slug = basename($path);
    }
    if ($slug === '' || !preg_match('/^[a-z0-9-]+$/i', $slug)) {
        return $path === '' ? '' : $path . '/';
    }
    [$y, $m, $d] = cfc_blog_post_date_parts($post);
    switch ($structure) {
        case 'month_name':
            return ($y !== '' && $m !== '') ? $y . '/' . $m . '/' . $slug . '/' : 'blog/' . $slug . '/';
        case 'blog_name':
            return 'blog/' . $slug . '/';
        case 'name':
            return $slug . '/';
        default:
            return ($y !== '' && $m !== '' && $d !== '')
                ? $y . '/' . $m . '/' . $d . '/' . $slug . '/'
                : 'blog/' . $slug . '/';
    }
}

/** The post's public URL under the structure currently in force. */
function cfc_blog_post_url(array $post): string
{
    return cfc_blog_post_url_as($post, cfc_blog_permalink());
}

/**
 * Serve a post matched under any structure. Every structure stays routable so
 * links already published keep working; anything that is not the structure in
 * force is redirected to it, which is what keeps one URL per post.
 *
 * Returns normally when no post matches, so the router carries on to its own
 * 404 rather than swallowing unknown paths.
 */
function cfc_blog_route(string $slug, ?string $exactPath = null): void
{
    $post = cfc_blog_find($exactPath ?? $slug);
    if ($post === null) {
        return;
    }
    $want = cfc_blog_post_url($post);
    if ($want !== '' && '/' . ltrim($want, '/') !== cfc_request_path()) {
        $qs = (string) ($_SERVER['QUERY_STRING'] ?? '');
        cfc_redirect($want . ($qs !== '' ? '?' . $qs : ''), 301);
    }
    cfc_render_blog_post(trim((string) ($post['path'] ?? ''), '/'));
}

function cfc_blog_path_ok(string $rel): bool
{
    $rel = str_replace('\\', '/', trim($rel, '/'));
    return $rel !== ''
        && !str_contains($rel, '..')
        && (bool) preg_match('#^[0-9]{4}/[0-9]{2}/[0-9]{2}/[a-z0-9-]+$#i', $rel);
}

function cfc_blog_find(string $slugPath): ?array
{
    $slugPath = trim($slugPath, '/');
    if ($slugPath === '' || str_contains($slugPath, '..')) {
        return null;
    }
    if (cfc_db_ready()) {
        return cfc_db_find_post($slugPath);
    }
    foreach (cfc_blog_index() as $post) {
        if (($post['path'] ?? '') === $slugPath || ($post['slug'] ?? '') === basename($slugPath)) {
            return $post;
        }
    }
    return null;
}

function cfc_blog_file(array $post): ?string
{
    $candidates = [];
    if (!empty($post['file'])) {
        $rel = str_replace('\\', '/', ltrim((string) $post['file'], '/'));
        if ($rel !== '' && !str_contains($rel, '..') && str_starts_with($rel, 'data/blog/')) {
            $candidates[] = CFC_ROOT . '/' . $rel;
        }
    }
    $path = trim((string) ($post['path'] ?? ''), '/');
    if (cfc_blog_path_ok($path)) {
        $candidates[] = CFC_DATA . '/blog/' . $path . '/index.html';
    }
    foreach ($candidates as $file) {
        if (is_file($file)) {
            return $file;
        }
    }
    return null;
}

function cfc_render_blog_post(string $path): never
{
    $rel = trim($path, '/');
    if (!cfc_blog_path_ok($rel)) {
        cfc_render_404();
    }
    $post = cfc_blog_find($rel);
    $post = is_array($post) ? $post : [];
    $raw = trim((string) ($post['body'] ?? ''));
    if ($raw === '') {
        $file = $post !== [] ? cfc_blog_file($post) : (CFC_DATA . '/blog/' . $rel . '/index.html');
        if ($file && is_file($file)) {
            $raw = (string) cfc_read($file);
        }
    }
    if ($raw === '') {
        cfc_render_404();
    }

    $GLOBALS['cfc_article'] = array_merge($post, [
        'title' => $post['title'] ?? basename($rel),
        'date' => $post['date'] ?? '',
        'html' => cfc_localize_content(cfc_extract_post_html($raw)),
    ]);
    cfc_render_custom('single', [
        'title' => (string) (($post['seo_title'] ?? '') !== '' ? $post['seo_title'] : ($post['title'] ?? 'Blog')),
        'description' => (string) (($post['seo_description'] ?? '') !== '' ? $post['seo_description'] : ($post['excerpt'] ?? '')),
    ]);
}

function cfc_extract_post_html(string $html): string
{
    if (preg_match('#<div class="page-content">(.*)$#is', $html, $m)) {
        $body = $m[1];
    } elseif (preg_match('#<div class="[^"]*entry-content[^"]*"[^>]*>(.*)$#is', $html, $m)) {
        $body = $m[1];
    } elseif (preg_match('#<html#i', $html)) {
        $body = $html;
    } else {
        return $html;
    }

    $body = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $body) ?? $body;
    $body = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $body) ?? $body;
    $body = preg_replace('#<(footer|nav|form)\b[^>]*>.*?</\1>#is', '', $body) ?? $body;
    $body = preg_replace('#\sclass="[^"]*elementor[^"]*"#i', '', $body) ?? $body;
    $body = preg_replace('#\sdata-elementor-[a-z-]+="[^"]*"#i', '', $body) ?? $body;
    return trim($body);
}

function cfc_localize_content(string $html): string
{
    $assets = cfc_url('assets/');
    $base = cfc_url();
    $html = str_replace(
        [
            'https://chennapatnamfiltercoffee.com/wp-content/uploads/',
            'http://chennapatnamfiltercoffee.com/wp-content/uploads/',
            '/wp-content/uploads/',
            'https://chennapatnamfiltercoffee.com/',
            'http://chennapatnamfiltercoffee.com/',
        ],
        [
            $assets,
            $assets,
            $assets,
            $base,
            $base,
        ],
        $html
    );
    $html = preg_replace('#(?<=["\'])(?:\.\./)+assets/#', $assets, $html) ?? $html;
    return $html;
}

function cfc_render_blog_page(int $page): never
{
    $GLOBALS['cfc_blog_page'] = $page;
    cfc_render_custom('blog', [
        'title' => cfc_cms('blog.seo_title'),
        'description' => cfc_cms('blog.seo_description'),
    ]);
}

function cfc_render_category(string $slug, int $page = 1): never
{
    $GLOBALS['cfc_blog_page'] = $page;
    $GLOBALS['cfc_blog_category'] = $slug;
    $label = ucwords(str_replace('-', ' ', $slug));
    cfc_render_custom('blog', ['title' => $label . ' | Filter Coffee Blog']);
}

function cfc_sitemap(): never
{
    $origin = rtrim(cfc_abs_url(), '/');
    $urls = [
        '',
        'about-us/',
        'menu/',
        'shop/',
        'franchise/',
        'blog/',
        'gallery/',
        'media-hub/',
        'contact-us/',
        'privacy-policy/',
        'terms-and-conditions/',
        'category/franchise/',
        'category/coffee-facts/',
        'category/history-of-filter-coffee/',
    ];
    foreach (cfc_blog_index() as $post) {
        $url = cfc_blog_post_url($post);
        if ($url !== '') {
            $urls[] = $url;
        }
    }

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u) {
        $xml .= '  <url><loc>' . cfc_e($origin . '/' . ltrim($u, '/')) . '</loc></url>' . "\n";
    }
    $xml .= '</urlset>';
    cfc_send(200, $xml, 'application/xml; charset=UTF-8');
}

function cfc_robots(): never
{
    $body = "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /form/\nDisallow: /config/\nDisallow: /includes/\nDisallow: /pages/\nDisallow: /scripts/\nDisallow: /docs/\nDisallow: /data/\nDisallow: /lib/\nDisallow: /assets/2024/04/CFC-Franchise.pdf\nDisallow: /assets/pdfs/CFC-Franchise.pdf\nDisallow: /assets/pdfs/CFC-Outlet-Presentation.pdf\nSitemap: " . cfc_abs_url('sitemap.xml') . "\n";
    cfc_send(200, $body, 'text/plain; charset=UTF-8');
}
