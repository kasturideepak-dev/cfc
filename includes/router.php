<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

function cfc_dispatch(): never
{
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $rawPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = CFC_BASE;
    if ($base !== '' && str_starts_with($rawPath, $base)) {
        $rawPath = substr($rawPath, strlen($base)) ?: '/';
    }

    $redirect = cfc_redirect_match($rawPath);
    if ($redirect) {
        cfc_redirect_apply($redirect);
    }

    if (
        in_array($method, ['GET', 'HEAD'], true)
        && $rawPath !== '/'
        && !str_ends_with($rawPath, '/')
        && !str_contains(basename($rawPath), '.')
    ) {
        $qs = $_SERVER['QUERY_STRING'] ?? '';
        cfc_redirect(ltrim($rawPath, '/') . '/' . ($qs !== '' ? '?' . $qs : ''), 301);
    }

    $path = cfc_request_path();

    if ($path === '/index.php/') {
        cfc_redirect('');
    }

    if ($path === '/form/submit/' || $path === '/form/submit') {
        cfc_form_handle();
    }

    if ($path === '/sitemap.xml/') {
        cfc_sitemap();
    }
    if ($path === '/robots.txt/') {
        cfc_robots();
    }

    if ($path === '/admin/' || str_starts_with($path, '/admin/')) {
        require CFC_ROOT . '/admin/index.php';
        exit;
    }

    $query = trim((string) ($_GET['s'] ?? ''));
    if (($path === '/' && array_key_exists('s', $_GET)) || $path === '/search/') {
        cfc_render_custom('search', ['title' => 'Search | Chennapatnam Filter Coffee']);
    }

    $custom = [
        '/about-us/' => ['about', 'about', 'inner'],
        '/menu/' => ['menu', 'menu', 'inner'],
        '/shop/' => ['shop', 'shop', 'inner'],
        '/franchise/' => ['franchise', 'franchise', 'inner'],
        '/blog/' => ['blog', 'blog', 'inner'],
        '/gallery/' => ['gallery', 'gallery', 'inner'],
        '/media-hub/' => ['media-hub', 'media', 'inner'],
        '/contact-us/' => ['contact', 'contact', 'inner'],
        '/landing/' => ['landing', 'landing', 'inner'],
        '/thank-you/' => ['thank-you', 'thankyou', 'inner'],
        '/privacy-policy/' => ['privacy', 'privacy', 'inner'],
        '/terms-and-conditions/' => ['terms', 'terms', 'inner'],
    ];
    if ($path === '/') {
        cfc_render_custom('home', [
            'title' => cfc_cms('home.seo_title'),
            'description' => cfc_cms('home.seo_description'),
            'theme' => 'home',
        ]);
    }

    if (isset($custom[$path])) {
        [$view, $cms, $theme] = $custom[$path];
        cfc_render_custom($view, [
            'title' => cfc_cms($cms . '.seo_title'),
            'description' => cfc_cms($cms . '.seo_description'),
            'theme' => $theme,
        ]);
    }

    if (preg_match('#^/blog/page/(\d+)/$#', $path, $m)) {
        cfc_render_blog_page((int) $m[1]);
    }

    if (preg_match('#^/category/([a-z0-9-]+)/(?:page/(\d+)/)?$#', $path, $m)) {
        cfc_render_category($m[1], isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 1);
    }

    if (preg_match('#^/(20[0-9]{2})/([0-9]{2})/([0-9]{2})/([a-z0-9-]+)/$#', $path, $m)) {
        cfc_render_blog_post($m[1] . '/' . $m[2] . '/' . $m[3] . '/' . $m[4]);
    }

    $aliases = [
        '/about/' => '/about-us/',
        '/contact/' => '/contact-us/',
        '/privacy/' => '/privacy-policy/',
        '/terms/' => '/terms-and-conditions/',
        '/home/' => '/',
    ];
    if (isset($aliases[$path])) {
        cfc_redirect(ltrim($aliases[$path], '/'));
    }

    if ($method === 'GET' && preg_match('#\.(?:css|js|png|jpe?g|gif|webp|svg|ico|woff2?|ttf|eot|otf|mp4|webm|pdf|map)$#i', $path)) {
        cfc_send(404, 'Not found', 'text/plain; charset=UTF-8');
    }

    cfc_render_404();
}
