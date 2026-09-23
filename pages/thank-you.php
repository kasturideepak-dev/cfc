<?php declare(strict_types=1); defined('CFC_ROOT') || exit; ?>
<section class="page-wrap" style="text-align:center;padding-top:80px">
    <h1><?= cfc_cms_e('thankyou.title') ?></h1>
    <p><?= cfc_cms_e('thankyou.message') ?></p>
    <p>
        <a class="cfc-btn" href="<?= cfc_e(cfc_url('franchise/')) ?>"><?= cfc_cms_e('site.pdf_franchise_label') ?></a>
        <a class="cfc-btn" href="<?= cfc_e(cfc_url()) ?>"><?= cfc_cms_e('thankyou.home_label') ?></a>
    </p>
</section>
