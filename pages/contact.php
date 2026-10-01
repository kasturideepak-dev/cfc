<?php declare(strict_types=1); defined('CFC_ROOT') || exit; ?>
<section class="contact-page">
    <div class="contact-page__split">
        <div class="contact-page__copy">
            <p class="contact-page__kicker"><?= cfc_cms_e('contact.kicker') ?></p>
            <h1 class="contact-page__title"><?= cfc_cms_e('contact.title') ?></h1>
            <form class="contact-form" method="post" action="<?= cfc_e(cfc_url('form/submit')) ?>" data-cfc-form>
                <input type="hidden" name="cfc_csrf" value="<?= cfc_e(cfc_csrf_token()) ?>">
                <input type="hidden" name="cfc_path" value="/contact-us/">
                <input type="hidden" name="form_id" value="contact">
                <?= cfc_form_honeypot() ?>

                <label class="contact-form__field">
                    <span class="contact-form__label">Name</span>
                    <input type="text" name="input_text" placeholder="Name" required autocomplete="name" maxlength="120">
                </label>
                <label class="contact-form__field">
                    <span class="contact-form__label">Email</span>
                    <input type="email" name="email" placeholder="Email Address" required autocomplete="email" maxlength="200">
                </label>
                <label class="contact-form__field">
                    <span class="contact-form__label">Mobile</span>
                    <input type="text" name="input_mask" placeholder="Mobile" required autocomplete="tel" inputmode="tel" maxlength="30">
                </label>
                <label class="contact-form__field">
                    <span class="contact-form__label">City</span>
                    <input type="text" name="input_text_1" placeholder="City" required autocomplete="address-level2" maxlength="80">
                </label>
                <label class="contact-form__field">
                    <span class="contact-form__label">Message</span>
                    <textarea name="description" rows="3" placeholder="Your Message" maxlength="4000"></textarea>
                </label>
                <?= cfc_turnstile_widget('light') ?>
                <div class="contact-form__actions">
                    <button class="contact-form__submit" type="submit"><?= cfc_cms_e('contact.submit_label') ?></button>
                </div>
            </form>
        </div>
        <div class="contact-page__art">
            <img src="<?= cfc_e(cfc_cms_src('contact.art_image')) ?>" alt="" width="700" height="494" data-cfc-anim="fadeInRight">
        </div>
    </div>
</section>
