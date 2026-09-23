<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

$sections = cfc_cms_gallery();
$shown = 0;
$placeholder = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
?>
<section class="gallery-page">
    <?php foreach ($sections as $section):
        $title = (string) ($section['title'] ?? '');
        $level = (($section['level'] ?? 'h2') === 'h1') ? 'h1' : 'h2';
        $images = $section['images'] ?? [];
        if ($title === '' || !$images) {
            continue;
        }
        ?>
        <div class="gallery-block">
            <<?= $level ?> class="gallery-block__title"><?= cfc_e($title) ?></<?= $level ?>>
            <div class="gallery-grid" data-gallery>
                <?php foreach ($images as $img):
                    $thumb = (string) ($img['thumb'] ?? '');
                    $full = (string) ($img['full'] ?? $thumb);
                    if ($thumb === '') {
                        continue;
                    }
                    $src = cfc_media($thumb);
                    $eager = $shown < 8;
                    $shown++;
                    $w = min(720, max(1, (int) ($img['w'] ?? 720)));
                    $h = min(540, max(1, (int) ($img['h'] ?? 540)));
                    ?>
                    <a class="gallery-item" href="<?= cfc_e(cfc_media($full)) ?>" data-gallery-item>
                        <?php if ($eager): ?>
                            <img src="<?= cfc_e($src) ?>" alt="<?= cfc_e($title) ?>" width="<?= $w ?>" height="<?= $h ?>" decoding="async"<?= $shown === 1 ? ' fetchpriority="high"' : '' ?>>
                        <?php else: ?>
                            <img src="<?= $placeholder ?>" data-src="<?= cfc_e($src) ?>" data-cfc-lazy-img alt="<?= cfc_e($title) ?>" width="<?= $w ?>" height="<?= $h ?>" decoding="async">
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</section>
<div class="gallery-lb" data-gallery-lb hidden>
    <button class="gallery-lb__close" type="button" data-gallery-close aria-label="Close"></button>
    <button class="gallery-lb__prev" type="button" data-gallery-prev aria-label="Previous"></button>
    <img class="gallery-lb__img" alt="" data-gallery-img>
    <button class="gallery-lb__next" type="button" data-gallery-next aria-label="Next"></button>
</div>
