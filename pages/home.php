<?php declare(strict_types=1);
defined('CFC_ROOT') || exit;
$phone = cfc_cms('site.phone_display');
$tel = cfc_cms('site.phone_tel');
$pdfPresentation = cfc_cms_src('site.pdf_presentation');
$pdf2 = cfc_cms_src('site.pdf_3d');
?>
<section class="home-hero">
    <div class="home-hero__inner">
        <p class="home-hero__welcome" data-cfc-anim="fadeInDown"><?= cfc_cms_e('home.hero_welcome') ?></p>
        <img class="home-hero__mark" src="<?= cfc_e(cfc_cms_src('home.hero_logo')) ?>" alt="Best coffee franchisee" width="588" height="112" fetchpriority="high" decoding="async" data-cfc-anim="fadeInDown">
        <h2 class="home-hero__call" data-cfc-anim="fadeInUp"><?= cfc_cms_e('home.hero_call') ?></h2>
        <a class="home-phone" href="tel:<?= cfc_e($tel) ?>" data-cfc-anim="fadeInUp">
            <span class="home-phone__icon" aria-hidden="true">
                <svg viewBox="0 0 512 512"><path fill="currentColor" d="M497.39 361.8l-112-48a24 24 0 0 0-28 6.9l-49.6 60.6A370.66 370.66 0 0 1 130.6 204.11l60.6-49.6a23.94 23.94 0 0 0 6.9-28l-48-112A24.16 24.16 0 0 0 122.6.61l-104 24A24 24 0 0 0 0 48c0 256.5 207.9 464 464 464a24 24 0 0 0 23.4-18.6l24-104a24.29 24.29 0 0 0-14.01-27.6z"/></svg>
            </span>
            <span><?= cfc_e($phone) ?></span>
        </a>
    </div>
</section>

<section class="home-intro">
    <h2 data-cfc-anim="fadeInUp"><?= cfc_cms_e('home.intro_heading') ?></h2>
    <p><?= cfc_cms_e('home.intro_p1') ?></p>
    <p><?= cfc_cms_e('home.intro_p2') ?></p>
</section>

<section class="home-andal">
    <div class="home-andal__inner">
        <img src="<?= cfc_e(cfc_cms_src('home.andal_image')) ?>" alt="Best coffee franchisee" width="634" height="390" loading="lazy" decoding="async" data-cfc-anim="fadeInLeft">
        <div class="home-copy">
            <p><?= cfc_cms_e('home.andal_p1') ?></p>
            <p><?= cfc_cms_e('home.andal_p2') ?></p>
            <p><?= cfc_cms_e('home.andal_p3') ?></p>
        </div>
    </div>
</section>

<section class="home-quote">
    <img src="<?= cfc_e(cfc_cms_src('home.quote_image')) ?>" alt="We use premium quality utensils and materials in the process and serving of our filter coffee" width="203" height="285" loading="lazy" decoding="async" data-cfc-anim="fadeInLeft">
    <h2 data-cfc-anim="fadeInRight"><?= cfc_cms_br('home.quote_text') ?></h2>
</section>

<section class="home-founder" id="founder">
    <div class="home-founder__top">
        <div>
            <h2 data-cfc-anim="fadeInUp"><?= cfc_cms_e('home.founder_heading') ?></h2>
            <div class="home-copy" data-cfc-anim="fadeInUp">
                <p><?= cfc_cms_e('home.founder_p1') ?></p>
                <p><?= cfc_cms_e('home.founder_p2') ?></p>
            </div>
        </div>
        <div class="home-founder__card">
            <img src="<?= cfc_e(cfc_cms_src('home.founder_image')) ?>" alt="" width="500" height="500" loading="lazy" decoding="async">
            <h3><?= cfc_cms_e('home.founder_name') ?></h3>
            <p><?= cfc_cms_e('home.founder_role') ?></p>
        </div>
    </div>
    <div class="home-copy">
        <p><?= cfc_cms_e('home.founder_p3') ?></p>
        <p><?= cfc_cms_e('home.founder_p4') ?></p>
    </div>
</section>

<section class="home-story">
    <div class="home-story__inner">
    <img src="<?= cfc_e(cfc_cms_src('home.story_image')) ?>" alt="We blend our coffee beans in a traditional way to bring the authentic filter coffee experience" width="616" height="601" loading="lazy" decoding="async">
    <div>
        <h2 data-cfc-anim="fadeInUp"><?= cfc_cms_e('home.story_heading') ?></h2>
        <h3 data-cfc-anim="fadeInUp"><?= cfc_cms_e('home.story_sub') ?></h3>
        <div class="home-copy">
            <p><?= cfc_cms_e('home.story_p1') ?></p>
        </div>
        <h3><?= cfc_cms_e('home.story_days_heading') ?></h3>
        <div class="home-copy">
            <p><?= cfc_cms_e('home.story_p2') ?></p>
            <p><?= cfc_cms_e('home.story_p3') ?></p>
            <p><?= cfc_cms_e('home.story_p4') ?></p>
            <p><?= cfc_cms_e('home.story_p5') ?></p>
        </div>
    </div>
    </div>
</section>

<section class="home-cta">
    <h2 data-cfc-anim="fadeInUp"><?= cfc_cms_e('home.cta_heading') ?></h2>
    <p><?= cfc_cms_e('home.cta_p1') ?></p>
    <p><?= cfc_cms_e('home.cta_p2') ?></p>
    <div class="home-cta__btns">
        <a href="<?= cfc_e(cfc_url('franchise/')) ?>" data-cfc-anim="fadeInLeft"><?= cfc_cms_e('site.pdf_franchise_label') ?></a>
        <a href="<?= cfc_e($pdfPresentation) ?>" target="_blank" rel="noopener nofollow" data-cfc-anim="fadeInUp"><?= cfc_cms_e('site.pdf_presentation_label') ?></a>
        <a href="<?= cfc_e($pdf2) ?>" target="_blank" rel="noopener" data-cfc-anim="fadeInRight"><?= cfc_cms_e('site.pdf_3d_label') ?></a>
    </div>
    <h3><?= cfc_cms_e('home.cta_for') ?></h3>
    <a class="home-phone home-phone--on-gold" href="tel:<?= cfc_e($tel) ?>" data-cfc-anim="fadeInUp">
        <span class="home-phone__icon" aria-hidden="true">
            <svg viewBox="0 0 512 512"><path fill="currentColor" d="M497.39 361.8l-112-48a24 24 0 0 0-28 6.9l-49.6 60.6A370.66 370.66 0 0 1 130.6 204.11l60.6-49.6a23.94 23.94 0 0 0 6.9-28l-48-112A24.16 24.16 0 0 0 122.6.61l-104 24A24 24 0 0 0 0 48c0 256.5 207.9 464 464 464a24 24 0 0 0 23.4-18.6l24-104a24.29 24.29 0 0 0-14.01-27.6z"/></svg>
        </span>
        <span><?= cfc_e($phone) ?></span>
    </a>
    <p><a class="home-cta__more" href="<?= cfc_e(cfc_url('franchise/')) ?>"><?= cfc_cms_e('home.cta_read_more') ?></a></p>
</section>

<section class="home-wide">
    <img src="<?= cfc_e(cfc_cms_src('home.wide_image')) ?>" alt="Best filter coffee franchise" width="1170" height="824" loading="lazy" decoding="async">
</section>
<section class="home-strip">
    <img src="<?= cfc_e(cfc_cms_src('home.strip_image')) ?>" alt="Chennapatnam filter coffee - Best coffee franchisee" width="1312" height="250" loading="lazy" decoding="async" data-cfc-anim="fadeInLeft">
</section>
