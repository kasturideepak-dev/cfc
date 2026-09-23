<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

$cols = [[], [], []];
foreach (cfc_cms_items('menu.items') as $item) {
    $col = max(1, min(3, (int) ($item['column'] ?? 1))) - 1;
    $cols[$col][] = $item;
}
$n = 0;
?>
<section class="menu-board" aria-label="Menu">
    <?php foreach ($cols as $items): ?>
        <div class="menu-col">
            <?php foreach ($items as $item):
                $type = (string) ($item['type'] ?? 'image');
                $file = cfc_cms_normalize_media((string) ($item['file'] ?? ''));
                $label = (string) ($item['label'] ?? '');
                if ($file === '') {
                    continue;
                }
                $src = cfc_media($file);
                $isVideo = $type === 'video' || cfc_is_video_url($src);
                $n++;
                $lazy = $n > 3;
                ?>
                <figure class="menu-card">
                    <?php if ($isVideo): ?>
                        <video
                            <?= $lazy ? 'data-src="' . cfc_e($src) . '" data-cfc-lazy-video' : 'src="' . cfc_e($src) . '" autoplay' ?>
                            muted loop playsinline
                            preload="<?= $lazy ? 'none' : 'metadata' ?>"
                            controlslist="nodownload"
                            aria-label="<?= cfc_e($label) ?>"
                        ></video>
                    <?php else: ?>
                        <img src="<?= cfc_e($src) ?>" alt="<?= cfc_e($label) ?>" width="720" height="720"<?= $lazy ? ' loading="lazy"' : '' ?> decoding="async">
                    <?php endif; ?>
                </figure>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</section>
