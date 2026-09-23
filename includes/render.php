<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

function cfc_asset(string $path): string
{
    return cfc_url(ltrim($path, '/'));
}

function cfc_media(string $path): string
{
    $path = ltrim(str_replace('\\', '/', $path), '/');
    if ($path !== '' && !str_contains($path, '..') && preg_match('/\.(png|jpe?g)$/i', $path)) {
        $webp = (string) preg_replace('/\.(png|jpe?g)$/i', '.webp', $path);
        if ($webp !== '' && is_file(CFC_ROOT . '/assets/' . $webp)) {
            $path = $webp;
        }
    }
    if ($path !== '' && !str_contains($path, '..') && preg_match('/\.gif$/i', $path)) {
        $mp4 = (string) preg_replace('/\.gif$/i', '.mp4', $path);
        if ($mp4 !== '' && is_file(CFC_ROOT . '/assets/' . $mp4)) {
            $path = $mp4;
        }
    }
    return cfc_url('assets/' . $path);
}

function cfc_is_video_url(string $url): bool
{
    $path = (string) (parse_url($url, PHP_URL_PATH) ?: $url);
    return (bool) preg_match('/\.(mp4|webm|ogg)$/i', $path);
}

function cfc_is_current(string $slug): bool
{
    $path = cfc_request_path();
    if ($slug === '' || $slug === '/') {
        return $path === '/';
    }
    return $path === '/' . trim($slug, '/') . '/' || str_starts_with($path, '/' . trim($slug, '/') . '/');
}

function cfc_render_custom(string $view, array $meta = [], int $status = 200): never
{
    $pageFile = CFC_ROOT . '/pages/' . $view . '.php';
    if (!is_file($pageFile)) {
        cfc_render_404();
    }

    $title = (string) ($meta['title'] ?? 'CHENNAPATNAM FILTER COFFEE');
    $description = (string) ($meta['description'] ?? 'Authentic South Indian filter coffee franchise from the house of Andaal.');
    $theme = (string) ($meta['theme'] ?? 'inner');
    $canonical = (string) ($meta['canonical'] ?? cfc_abs_url(ltrim(cfc_request_path(), '/')));
    $bodyClass = 'cfc-page cfc-page--' . preg_replace('/[^a-z0-9-]+/', '-', $view) . ' cfc-theme--' . $theme;
    $meta['view'] = $view;

    ob_start();
    include CFC_ROOT . '/includes/layout.php';
    cfc_send($status, (string) ob_get_clean());
}

function cfc_render_404(): never
{
    cfc_render_custom('notfound', [
        'title' => cfc_cms('notfound.seo_title'),
        'description' => cfc_cms('notfound.title'),
    ], 404);
}

function cfc_schema_graph(string $view, string $title, string $description, string $canonical): array
{
    $origin = rtrim(cfc_abs_url(), '/');
    $sameAs = [];
    foreach (cfc_cms_items('site.social') as $item) {
        $href = cfc_cms_href((string) ($item['url'] ?? ''));
        if ($href !== '') {
            $sameAs[] = $href;
        }
    }
    $local = [
        '@type' => ['Organization', 'LocalBusiness', 'CafeOrCoffeeShop'],
        '@id' => $origin . '/#organization',
        'name' => 'Chennapatnam Filter Coffee',
        'url' => $origin . '/',
        'logo' => cfc_cms_src('site.logo_home'),
        'image' => cfc_cms_src('site.og_image'),
        'email' => cfc_cms('site.email'),
        'telephone' => '+' . cfc_cms_digits('site.whatsapp'),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => '4-2, Mouli towers, Near jyothi convention hall, Chandra mouli puram, Benz circle',
            'addressLocality' => 'Vijayawada',
            'addressRegion' => 'Andhra Pradesh',
            'postalCode' => '520010',
            'addressCountry' => 'IN',
        ],
        'areaServed' => 'IN',
    ];
    if ($sameAs !== []) {
        $local['sameAs'] = $sameAs;
    }

    $graph = [$local];
    $pageKey = cfc_cms_view_key($view);
    $article = $GLOBALS['cfc_article'] ?? [];
    $keywords = [];
    $faqs = [];
    if ($view === 'single') {
        foreach (['primary_keyword', 'secondary_keyword', 'keywords'] as $k) {
            $val = trim((string) ($article[$k] ?? ''));
            if ($val === '') {
                continue;
            }
            foreach (preg_split('/\s*,\s*/', $val) ?: [] as $bit) {
                $bit = trim($bit);
                if ($bit !== '' && !in_array($bit, $keywords, true)) {
                    $keywords[] = $bit;
                }
            }
        }
        foreach ($article['faqs'] ?? [] as $row) {
            $q = trim((string) ($row['question'] ?? ''));
            $a = trim((string) ($row['answer'] ?? ''));
            if ($q !== '' && $a !== '') {
                $faqs[] = ['question' => $q, 'answer' => $a];
            }
        }
    } elseif (cfc_cms_page_schema($pageKey)) {
        $keywords = cfc_cms_keyword_list($pageKey);
        $faqs = cfc_cms_faqs($pageKey);
    }

    $webPage = [
        '@type' => 'WebPage',
        '@id' => $canonical . '#webpage',
        'url' => $canonical,
        'name' => $title,
        'description' => $description,
        'isPartOf' => ['@id' => $origin . '/#website'],
        'about' => ['@id' => $origin . '/#organization'],
    ];
    if ($keywords !== []) {
        $webPage['keywords'] = implode(', ', $keywords);
    }
    $graph[] = $webPage;
    $graph[] = [
        '@type' => 'WebSite',
        '@id' => $origin . '/#website',
        'url' => $origin . '/',
        'name' => 'Chennapatnam Filter Coffee',
        'publisher' => ['@id' => $origin . '/#organization'],
    ];

    if ($view === 'franchise') {
        $reviews = [];
        foreach (cfc_cms_items('franchise.quotes') as $quote) {
            $text = trim((string) ($quote['text'] ?? ''));
            $name = trim((string) ($quote['name'] ?? ''));
            if ($text === '' || $name === '') {
                continue;
            }
            $reviews[] = [
                '@type' => 'Review',
                'author' => ['@type' => 'Person', 'name' => $name],
                'reviewBody' => $text,
                'reviewRating' => ['@type' => 'Rating', 'ratingValue' => '5', 'bestRating' => '5'],
            ];
        }
        $franchise = [
            '@type' => 'Product',
            'name' => 'Chennapatnam Filter Coffee Franchise',
            'description' => $description,
            'brand' => ['@id' => $origin . '/#organization'],
            'url' => $canonical,
        ];
        if ($reviews !== []) {
            $franchise['review'] = $reviews;
            $franchise['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => '5',
                'reviewCount' => (string) count($reviews),
                'bestRating' => '5',
            ];
        }
        $graph[] = $franchise;
    }

    if ($view === 'single') {
        $articleNode = [
            '@type' => 'Article',
            'headline' => (string) ($article['title'] ?? $title),
            'description' => $description,
            'datePublished' => (string) ($article['date'] ?? ''),
            'mainEntityOfPage' => $canonical,
            'author' => ['@id' => $origin . '/#organization'],
            'publisher' => ['@id' => $origin . '/#organization'],
        ];
        if ($keywords !== []) {
            $articleNode['keywords'] = implode(', ', $keywords);
        }
        $graph[] = $articleNode;
    }

    if ($faqs !== []) {
        $main = [];
        foreach ($faqs as $faq) {
            $main[] = [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
            ];
        }
        $graph[] = [
            '@type' => 'FAQPage',
            '@id' => $canonical . '#faq',
            'mainEntity' => $main,
        ];
    }

    return ['@context' => 'https://schema.org', '@graph' => $graph];
}
