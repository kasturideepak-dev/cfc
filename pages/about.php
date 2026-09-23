<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

$brands = cfc_cms_items('about.brands');
$awards = cfc_cms_items('about.awards');
?>
<section class="about-map">
    <video src="<?= cfc_e(cfc_cms_src('about.map_video')) ?>" autoplay muted loop playsinline preload="metadata"></video>
</section>

<section class="about-split">
    <img src="<?= cfc_e(cfc_cms_src('about.story_image')) ?>" alt="Best coffee franchise in India" width="800" height="600" loading="lazy" decoding="async" data-cfc-anim="fadeInLeft">
    <div class="about-story" data-cfc-anim="fadeInUp">
        <h2><?= cfc_cms_e('about.story_heading') ?></h2>
        <h3><?= cfc_cms_e('about.story_sub') ?></h3>
        <p><?= cfc_cms_e('about.story_p1') ?></p>
        <h3><?= cfc_cms_e('about.story_days_heading') ?></h3>
        <p><?= cfc_cms_e('about.story_p2') ?></p>
        <p><?= cfc_cms_e('about.story_p3') ?></p>
        <p><?= cfc_cms_e('about.story_p4') ?></p>
        <p><?= cfc_cms_e('about.story_p5') ?></p>
    </div>
</section>

<section class="about-brands">
    <h2 data-cfc-anim="fadeInUp"><?= cfc_cms_e('about.brands_heading') ?></h2>
    <div class="about-brands__row">
        <?php foreach ($brands as $brand):
            $label = (string) ($brand['label'] ?? '');
            $href = cfc_cms_href(trim((string) ($brand['url'] ?? '')));
            $src = cfc_cms_normalize_media(trim((string) ($brand['image'] ?? '')));
            if ($src === '') {
                continue;
            }
            $img = '<img src="' . cfc_e(cfc_media($src)) . '" alt="' . cfc_e($label) . '" loading="lazy" decoding="async" width="160" height="80">';
            if ($href !== '') {
                echo '<a href="' . cfc_e($href) . '" target="_blank" rel="noopener noreferrer">' . $img . '</a>';
            } else {
                echo '<span>' . $img . '</span>';
            }
        endforeach; ?>
    </div>
</section>

<section class="about-journey" id="journey">
    <h2 data-cfc-anim="fadeInUp"><?= cfc_cms_e('about.journey_heading') ?></h2>
    <p><?= cfc_cms_e('about.journey_lead') ?></p>
    <div class="about-timeline" data-timeline data-cfc-anim="fadeInUp">
        <button class="about-timeline__btn about-timeline__btn--prev" type="button" data-timeline-prev aria-label="Previous"></button>
        <div class="about-timeline__rail" aria-hidden="true"><span class="about-timeline__fill" data-timeline-fill></span></div>
        <div class="about-timeline__scroller" data-timeline-scroller>
            <?php foreach ($awards as $award):
                $year = (string) ($award['year'] ?? '');
                $title = (string) ($award['title'] ?? '');
                $from = (string) ($award['from'] ?? '');
                $img = cfc_cms_normalize_media((string) ($award['image'] ?? ''));
                ?>
                <article class="about-milestone">
                    <div class="about-milestone__meta">
                        <p class="about-milestone__year"><?= cfc_e($year) ?></p>
                        <p class="about-milestone__tag"><?= cfc_cms_e('about.milestone_tag') ?></p>
                    </div>
                    <div class="about-milestone__dot" aria-hidden="true"></div>
                    <div class="about-milestone__card">
                        <?php if ($img !== ''): ?>
                            <img src="<?= cfc_e(cfc_media($img)) ?>" alt="<?= cfc_e($title) ?>" loading="lazy" decoding="async" width="400" height="260">
                        <?php endif; ?>
                        <h3><?= cfc_e($title) ?></h3>
                        <p class="about-milestone__from"><?= cfc_e($from) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <button class="about-timeline__btn about-timeline__btn--next" type="button" data-timeline-next aria-label="Next"></button>
    </div>
</section>

<section class="about-featured">
    <h2 data-cfc-anim="fadeInUp"><?= cfc_cms_e('about.featured_heading') ?></h2>
    <div class="about-videos">
        <?php
        echo cfc_lite_youtube(cfc_cms('about.featured_video_1'), 'Featured video 1');
        echo cfc_lite_youtube(cfc_cms('about.featured_video_2'), 'Featured video 2');
        ?>
    </div>
</section>
