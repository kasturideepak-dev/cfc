<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

$live = cfc_instagram_feed_posts();
$posts = $live !== [] ? $live : cfc_cms_media_hub();
$ig = cfc_cms_href(cfc_cms('media.instagram')) ?: 'https://www.instagram.com/chennapatnamfiltercoffee/';
$play = '<svg class="media-hub__play" viewBox="0 0 68 48" aria-hidden="true"><path d="M66.5 7.7a8 8 0 0 0-5.6-5.7C55.8.8 34 .8 34 .8S12.2.8 7.1 2a8 8 0 0 0-5.6 5.7A83 83 0 0 0 .4 24a83 83 0 0 0 1.1 16.3 8 8 0 0 0 5.6 5.7c5.1 1.2 26.9 1.2 26.9 1.2s21.8 0 26.9-1.2a8 8 0 0 0 5.6-5.7A83 83 0 0 0 67.6 24a83 83 0 0 0-1.1-16.3z" fill="#212121" fill-opacity=".8"/><path d="M45 24 27 13.8v20.4z" fill="#fff"/></svg>';
$placeholder = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
?>
<section class="media-hub">
    <h1 class="media-hub__title"><?= cfc_cms_e('media.title') ?></h1>
    <div class="media-hub__grid" data-media-grid data-media-step="12">
        <?php foreach ($posts as $i => $post):
            $href = cfc_cms_href((string) ($post['href'] ?? ''));
            if ($href === '') {
                $href = cfc_cms_href($ig);
            }
            $file = cfc_cms_normalize_media((string) ($post['file'] ?? ''));
            $src = $file !== '' ? cfc_media($file) : cfc_instagram_safe_cdn_url((string) ($post['remote'] ?? ''));
            if ($src === '') {
                continue;
            }
            $alt = trim((string) ($post['alt'] ?? ''));
            if ($alt === '') {
                $alt = 'Chennapatnam Filter Coffee on Instagram';
            }
            $isVideo = in_array(strtolower((string) ($post['type'] ?? 'video')), ['video', 'carousel_album'], true);
            $eager = $i < 3;
            ?>
            <a class="media-hub__item" href="<?= cfc_e($href) ?>" target="_blank" rel="noopener noreferrer">
                <?php if ($eager): ?>
                    <img src="<?= cfc_e($src) ?>" alt="<?= cfc_e($alt) ?>" width="480" height="600" decoding="async"<?= $i === 0 ? ' fetchpriority="high"' : '' ?>>
                <?php else: ?>
                    <img src="<?= $placeholder ?>" data-src="<?= cfc_e($src) ?>" data-cfc-lazy-img alt="<?= cfc_e($alt) ?>" width="480" height="600" decoding="async">
                <?php endif; ?>
                <?= $isVideo ? $play : '' ?>
            </a>
        <?php endforeach; ?>
    </div>
    <div class="media-hub__actions">
        <?php /* Reveals the tiles already on the page. Hidden until the script
                 confirms there are some to reveal, so with JavaScript off every
                 tile shows and no button promises anything it cannot do. */ ?>
        <button class="media-hub__more" type="button" data-media-more hidden><?= cfc_cms_e('media.load_more') ?></button>
        <a class="media-hub__follow" href="<?= cfc_e(cfc_cms_href($ig)) ?>" target="_blank" rel="noopener noreferrer">
            <svg viewBox="0 0 448 512" aria-hidden="true"><path fill="currentColor" d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1S1.8 108.3.1 144.1c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z"/></svg>
            <span><?= cfc_cms_e('media.follow') ?></span>
        </a>
    </div>
</section>
