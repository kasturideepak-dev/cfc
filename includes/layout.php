<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;
/** @var string $title */
/** @var string $description */
/** @var string $theme */
/** @var string $canonical */
/** @var string $bodyClass */
/** @var string $pageFile */
$logoWhite = cfc_cms_src('site.logo_inner');
$logoColor = cfc_cms_src('site.logo_home');
$headerLogo = $theme === 'home' ? $logoColor : $logoWhite;
$view = (string) ($meta['view'] ?? preg_replace('/^.*pages\/([^.]+)\.php$/', '$1', $pageFile));
$pageKey = cfc_cms_view_key($view);
$article = $GLOBALS['cfc_article'] ?? [];
if ($view === 'single') {
    $seoKeywords = [];
    foreach (['primary_keyword', 'secondary_keyword', 'keywords'] as $k) {
        $val = trim((string) preg_replace('/\s+/', ' ', (string) ($article[$k] ?? '')));
        if ($val !== '') {
            $seoKeywords[] = $val;
        }
    }
    $seoKeywords = implode(', ', $seoKeywords);
    if (strlen($seoKeywords) > 500) {
        $seoKeywords = rtrim(substr($seoKeywords, 0, 500), " ,");
    }
    $seoRobots = cfc_cms_robots((string) ($article['seo_robots'] ?? 'index,follow'));
    $ogTitle = trim((string) ($article['og_title'] ?? '')) ?: $title;
    $ogDesc = trim((string) ($article['og_description'] ?? '')) ?: $description;
    $faqs = is_array($article['faqs'] ?? null) ? $article['faqs'] : [];
    $faqClean = [];
    foreach ($faqs as $row) {
        $q = trim((string) ($row['question'] ?? ''));
        $a = trim((string) ($row['answer'] ?? ''));
        if ($q !== '' && $a !== '') {
            $faqClean[] = ['question' => $q, 'answer' => $a];
        }
    }
    $faqs = $faqClean;
} else {
    $seoKeywords = implode(', ', cfc_cms_keyword_list($pageKey));
    if (strlen($seoKeywords) > 500) {
        $seoKeywords = rtrim(substr($seoKeywords, 0, 500), " ,");
    }
    $seoRobots = cfc_cms_robots(cfc_cms($pageKey . '.seo_robots', 'index,follow'));
    $ogTitle = cfc_cms($pageKey . '.og_title') ?: $title;
    $ogDesc = cfc_cms($pageKey . '.og_description') ?: $description;
    $faqs = cfc_cms_faqs($pageKey);
}
if ($view === 'single') {
    $codeHead = cfc_cms_join_snippets(cfc_cms_snippet('site.code_head'), (string) ($article['code_head'] ?? ''));
    $codeBody = cfc_cms_join_snippets(cfc_cms_snippet('site.code_body'), (string) ($article['code_body'] ?? ''));
    $codeFoot = cfc_cms_join_snippets(cfc_cms_snippet('site.code_footer'), (string) ($article['code_footer'] ?? ''));
} elseif (cfc_cms_page_schema($pageKey)) {
    $codeHead = cfc_cms_join_snippets(cfc_cms_snippet('site.code_head'), cfc_cms_snippet($pageKey . '.code_head'));
    $codeBody = cfc_cms_join_snippets(cfc_cms_snippet('site.code_body'), cfc_cms_snippet($pageKey . '.code_body'));
    $codeFoot = cfc_cms_join_snippets(cfc_cms_snippet('site.code_footer'), cfc_cms_snippet($pageKey . '.code_footer'));
} else {
    $codeHead = cfc_cms_snippet('site.code_head');
    $codeBody = cfc_cms_snippet('site.code_body');
    $codeFoot = cfc_cms_snippet('site.code_footer');
}
$schema = cfc_schema_graph($view, $title, $description, $canonical);
$schemaJson = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?: '{}';
?><!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= cfc_e($title) ?></title>
    <meta name="description" content="<?= cfc_e($description) ?>">
    <?php if ($seoKeywords !== ''): ?>
    <meta name="keywords" content="<?= cfc_e($seoKeywords) ?>">
    <?php endif; ?>
    <meta name="robots" content="<?= cfc_e($seoRobots) ?>">
    <link rel="canonical" href="<?= cfc_e($canonical) ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= cfc_e($ogTitle) ?>">
    <meta property="og:description" content="<?= cfc_e($ogDesc) ?>">
    <meta property="og:url" content="<?= cfc_e($canonical) ?>">
    <meta property="og:image" content="<?= cfc_e(cfc_cms_src('site.og_image')) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= cfc_e($ogTitle) ?>">
    <meta name="twitter:description" content="<?= cfc_e($ogDesc) ?>">
    <link rel="icon" href="<?= cfc_e(cfc_cms_src('site.favicon')) ?>" sizes="32x32">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="<?= cfc_e(cfc_asset('assets/fonts/leiko.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <?php if ($theme === 'home'): ?>
        <link rel="preload" href="<?= cfc_e(cfc_media('2024/03/Group-29-1.webp')) ?>" as="image">
        <link rel="preload" href="<?= cfc_e(cfc_cms_src('home.hero_logo')) ?>" as="image">
    <?php else: ?>
        <link rel="preload" href="<?= cfc_e($headerLogo) ?>" as="image">
    <?php endif; ?>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,500;0,600;1,500&family=Nunito+Sans:wght@700;800&family=Oswald:wght@500&family=Roboto:wght@400&family=Roboto+Condensed:wght@500&family=Rubik:wght@400&display=swap" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,500;0,600;1,500&family=Nunito+Sans:wght@700;800&family=Oswald:wght@500&family=Roboto:wght@400&family=Roboto+Condensed:wght@500&family=Rubik:wght@400&display=swap"></noscript>
    <link rel="stylesheet" href="<?= cfc_e(cfc_asset('css/poppins.css')) ?>">
    <link rel="stylesheet" href="<?= cfc_e(cfc_asset('css/cfc.css')) ?>?v=<?= filemtime(CFC_ROOT . '/css/cfc.css') ?>">
    <script type="application/ld+json"><?= $schemaJson ?></script>
    <?= $codeHead ?>
</head>
<body class="<?= cfc_e($bodyClass) ?>">
<?= $codeBody ?>
<script>document.documentElement.classList.add("cfc-js");</script>
<?php include CFC_ROOT . '/includes/header.php'; ?>
<main id="content" class="cfc-main">
    <?php include $pageFile; ?>
    <?= cfc_faq_html($faqs) ?>
</main>
<?php include CFC_ROOT . '/includes/footer.php'; ?>
<script src="<?= cfc_e(cfc_asset('js/cfc-site.js')) ?>?v=<?= filemtime(CFC_ROOT . '/js/cfc-site.js') ?>" defer></script>
<?= $codeFoot ?>
</body>
</html>
