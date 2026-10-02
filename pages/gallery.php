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
                    // Only tiles whose photo cannot fill the 4:3 cell get the
                    // blurred backdrop, so two thirds of the grid pays nothing
                    // for it. Above-the-fold tiles carry the URL inline; the
                    // rest get it from the lazy loader, which would otherwise
                    // be defeated by a background image fetching immediately.
                    $ratio = $h > 0 ? $w / $h : 4 / 3;
                    $bars = $ratio < 4 / 3 ? 1 - ($ratio / (4 / 3)) : 1 - ((4 / 3) / $ratio);
                    $fill = $bars > 0.03;
                    $itemCls = 'gallery-item' . ($fill ? ' gallery-item--fill' : '');
                    $itemStyle = ($fill && $eager) ? ' style="--bg:url(&quot;' . cfc_e($src) . '&quot;)"' : '';
                    // Clamping width and height separately distorted the declared
                    // ratio, so the browser reserved the wrong shape before the
                    // image arrived. Scale both together instead.
                    $w = max(1, (int) ($img['w'] ?? 720));
                    $h = max(1, (int) ($img['h'] ?? 540));
                    if ($w > 720) {
                        $h = max(1, (int) round($h * (720 / $w)));
                        $w = 720;
                    }
                    ?>
                    <a class="<?= cfc_e($itemCls) ?>" href="<?= cfc_e(cfc_media($full)) ?>"<?= $itemStyle ?> data-gallery-item>
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
