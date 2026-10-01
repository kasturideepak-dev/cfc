<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

$page = max(1, (int) ($GLOBALS['cfc_blog_page'] ?? 1));
$category = (string) ($GLOBALS['cfc_blog_category'] ?? '');
$per = 9;
$all = cfc_blog_index();
$filtered = $all;
if ($category !== '') {
    $filtered = array_values(array_filter(
        $all,
        static fn(array $p): bool => ($p['category'] ?? '') === $category
    ));
}

$total = count($filtered);
$pages = max(1, (int) ceil($total / $per));
if ($page > $pages) {
    $page = $pages;
}
$offset = ($page - 1) * $per;
$render = $page === 1 ? $all : array_slice($filtered, $offset, $per);
$initial = $page === 1 ? $per : count($render);
$hasMore = $page < $pages;
$nextUrl = $category !== ''
    ? cfc_url('category/' . $category . '/page/' . ($page + 1) . '/')
    : cfc_url('blog/page/' . ($page + 1) . '/');

$readIcon = '<svg viewBox="0 0 512 512" aria-hidden="true"><path fill="currentColor" d="M432 320h-32a16 16 0 0 0-16 16v112H64V128h144a16 16 0 0 0 16-16V80a16 16 0 0 0-16-16H48A48 48 0 0 0 0 112v352a48 48 0 0 0 48 48h352a48 48 0 0 0 48-48V336a16 16 0 0 0-16-16zM488 0H360c-21.37 0-32.05 25.91-17 41l35.73 35.73L135 320.37a24 24 0 0 0 0 34L157.67 377a24 24 0 0 0 34 0l266.61-266.68L471 169c15 15 41 4.5 41-17V24A24 24 0 0 0 488 0z"/></svg>';
$placeholder = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
?>
<section class="blog-page" data-blog-list data-blog-per="<?= (int) $per ?>" data-blog-initial="<?= (int) $initial ?>">
    <nav class="blog-filters" aria-label="Blog categories">
        <a href="<?= cfc_e(cfc_url('blog/')) ?>" class="blog-filters__item<?= $category === '' ? ' is-on' : '' ?>" data-blog-filter=""><?= cfc_cms_e('blog.filter_all') ?></a>
        <a href="<?= cfc_e(cfc_url('category/franchise/')) ?>" class="blog-filters__item<?= $category === 'franchise' ? ' is-on' : '' ?>" data-blog-filter="franchise"><?= cfc_cms_e('blog.filter_franchise') ?></a>
    </nav>

    <div class="blog-grid">
        <?php foreach ($render as $i => $post):
            $href = cfc_url(cfc_blog_post_url($post));
            $img = (string) ($post['image'] ?? '');
            $catSlug = (string) ($post['category'] ?? '');
            $catName = (string) ($post['category_name'] ?? ($catSlug !== '' ? ucwords(str_replace('-', ' ', $catSlug)) : ''));
            $hide = $page === 1 && $i >= $per;
            ?>
            <article class="blog-card" data-blog-card data-cat="<?= cfc_e($catSlug) ?>"<?= $hide ? ' hidden' : '' ?>>
                <h2 class="blog-card__title">
                    <a href="<?= cfc_e($href) ?>"><?= cfc_e((string) ($post['title'] ?? $post['slug'])) ?></a>
                </h2>
                <?php if ($img !== ''):
                    $src = cfc_media($img);
                    $eager = !$hide && $i < 4;
                    ?>
                    <a class="blog-card__media" href="<?= cfc_e($href) ?>">
                        <?php if ($eager): ?>
                            <img src="<?= cfc_e($src) ?>" alt="<?= cfc_e((string) ($post['title'] ?? '')) ?>" width="720" height="405" decoding="async"<?= $i === 0 ? ' fetchpriority="high"' : '' ?>>
                        <?php else: ?>
                            <img src="<?= $placeholder ?>" data-src="<?= cfc_e($src) ?>" data-cfc-lazy-img alt="<?= cfc_e((string) ($post['title'] ?? '')) ?>" width="720" height="405" decoding="async">
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
                <div class="blog-card__body">
                    <?php if ($catName !== ''): ?>
                        <p class="blog-card__cat">
                            <a href="<?= cfc_e(cfc_url('category/' . $catSlug . '/')) ?>"><?= cfc_e($catName) ?></a>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($post['excerpt'])): ?>
                        <p class="blog-card__excerpt"><?= cfc_e((string) $post['excerpt']) ?></p>
                    <?php endif; ?>
                    <p class="blog-card__more">
                        <a href="<?= cfc_e($href) ?>"><?= $readIcon ?><span><?= cfc_cms_e('blog.read_more') ?></span></a>
                    </p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ($hasMore): ?>
        <p class="blog-more">
            <a class="blog-more__btn" href="<?= cfc_e($nextUrl) ?>" data-blog-more><?= cfc_cms_e('blog.load_more') ?></a>
        </p>
    <?php endif; ?>
    <p class="blog-end" data-blog-end hidden><?= cfc_cms_e('blog.end') ?></p>
</section>
