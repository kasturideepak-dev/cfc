<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;
$q = trim((string) ($_GET['s'] ?? ''));
$hits = [];
if ($q !== '') {
    $needle = cfc_lower($q);
    foreach (cfc_blog_index() as $post) {
        $hay = cfc_lower(($post['title'] ?? '') . ' ' . ($post['excerpt'] ?? '') . ' ' . ($post['slug'] ?? ''));
        if (str_contains($hay, $needle)) {
            $hits[] = $post;
        }
    }
}
?>
<section class="page-wrap">
    <h1>Search<?= $q !== '' ? ': ' . cfc_e($q) : '' ?></h1>
    <?php if ($q === ''): ?>
        <p>Type a search in the address bar using <code>?s=</code>.</p>
    <?php elseif (!$hits): ?>
        <p>No results found.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($hits as $post): ?>
                <li><a href="<?= cfc_e(cfc_url(trim((string) $post['path'], '/') . '/')) ?>"><?= cfc_e((string) ($post['title'] ?? $post['slug'])) ?></a></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
