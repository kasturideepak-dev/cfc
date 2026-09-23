<?php declare(strict_types=1);
defined('CFC_ROOT') || exit;
$tel = cfc_cms('site.phone_tel');
$phone = cfc_cms('site.phone_display');
?>
<section class="page-hero">
    <h1><?= cfc_cms_e('landing.title') ?></h1>
    <p class="phone-cta"><a href="tel:<?= cfc_e($tel) ?>"><?= cfc_e($phone) ?></a></p>
</section>
<section class="page-wrap">
    <p><?= cfc_cms_e('landing.lead') ?></p>
    <form class="form-grid" method="post" action="<?= cfc_e(cfc_url('form/submit')) ?>" data-cfc-form>
        <input type="hidden" name="cfc_csrf" value="<?= cfc_e(cfc_csrf_token()) ?>">
        <input type="hidden" name="cfc_path" value="/landing/">
        <input type="hidden" name="form_id" value="landing">
        <?= cfc_form_honeypot() ?>
        <input name="input_text" required placeholder="Name" maxlength="120">
        <input type="email" name="email" required placeholder="Email" maxlength="200">
        <input name="input_mask" required placeholder="Mobile" maxlength="30">
        <input name="input_text_1" required placeholder="City" maxlength="80">
        <textarea name="description" rows="3" placeholder="Message" maxlength="4000"></textarea>
        <?= cfc_turnstile_widget('light') ?>
        <button class="cfc-btn" type="submit"><?= cfc_cms_e('landing.submit_label') ?></button>
    </form>
</section>
