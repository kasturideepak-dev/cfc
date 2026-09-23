<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

$whyCols = [[], []];
foreach (cfc_cms_items('franchise.why') as $item) {
    $col = max(1, min(2, (int) ($item['column'] ?? 1))) - 1;
    $whyCols[$col][] = $item;
}
$quotes = cfc_cms_items('franchise.quotes');
$phone = cfc_cms('site.phone_display');
$tel = cfc_cms('site.phone_tel');
$pdf1 = cfc_cms_src('site.pdf_franchise');
$pdfPresentation = cfc_cms_src('site.pdf_presentation');
$pdf3d = cfc_cms_src('site.pdf_3d');
?>
<section class="franchise-enquiry">
    <div class="franchise-enquiry__inner">
        <h1 class="franchise-enquiry__title"><?= cfc_cms_e('franchise.title') ?></h1>
        <p class="franchise-enquiry__lead"><?= cfc_cms_e('franchise.lead_seo') ?></p>
        <p class="franchise-enquiry__lead"><?= cfc_cms_e('franchise.lead') ?></p>
        <form class="franchise-form" method="post" action="<?= cfc_e(cfc_url('form/submit')) ?>" data-cfc-form>
            <input type="hidden" name="cfc_csrf" value="<?= cfc_e(cfc_csrf_token()) ?>">
            <input type="hidden" name="cfc_path" value="/franchise/">
            <input type="hidden" name="form_id" value="franchise">
            <?= cfc_form_honeypot() ?>

            <label class="franchise-field">
                <span class="franchise-field__label">Name</span>
                <input type="text" name="names[first_name]" placeholder="Enter Your First Name" required autocomplete="given-name" maxlength="120">
            </label>

            <div class="franchise-form__row">
                <label class="franchise-field">
                    <span class="franchise-field__label">Email</span>
                    <input type="email" name="email" placeholder="Email Address" required autocomplete="email" maxlength="200">
                </label>
                <label class="franchise-field">
                    <span class="franchise-field__label">Mobile Number <abbr class="franchise-req" title="required">*</abbr></span>
                    <span class="franchise-field__phone">
                        <span class="franchise-field__cc">+91</span>
                        <input type="text" name="input_mask" placeholder="Mobile Number" required autocomplete="tel" inputmode="tel" maxlength="30">
                    </span>
                </label>
            </div>

            <label class="franchise-field">
                <span class="franchise-field__label">City</span>
                <input type="text" name="city" placeholder="City" required autocomplete="address-level2" maxlength="80">
            </label>

            <label class="franchise-field">
                <span class="franchise-field__label">Message</span>
                <textarea name="description" rows="5" maxlength="4000"></textarea>
            </label>

            <?= cfc_turnstile_widget('dark') ?>
            <div class="franchise-form__actions">
                <button class="franchise-form__submit" type="submit"><?= cfc_cms_e('franchise.submit_label') ?></button>
            </div>
        </form>
    </div>
</section>

<section class="franchise-why">
    <h2><?= cfc_cms_e('franchise.why_heading') ?></h2>
    <div class="franchise-why__grid">
        <?php foreach ($whyCols as $col): ?>
            <div class="franchise-why__col">
                <?php foreach ($col as $item): ?>
                    <article class="franchise-why__item">
                        <h2><?= cfc_e((string) ($item['heading'] ?? '')) ?></h2>
                        <?php if (trim((string) ($item['p1'] ?? '')) !== ''): ?>
                            <p><?= cfc_e((string) $item['p1']) ?></p>
                        <?php endif; ?>
                        <?php if (trim((string) ($item['p2'] ?? '')) !== ''): ?>
                            <p><?= cfc_e((string) $item['p2']) ?></p>
                        <?php endif; ?>
                        <?php if (trim((string) ($item['p3'] ?? '')) !== ''): ?>
                            <p><?= cfc_e((string) $item['p3']) ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="franchise-why__btns">
        <a href="<?= cfc_e($pdf1) ?>" target="_blank" rel="noopener nofollow"><?= cfc_cms_e('franchise.pdf_label') ?></a>
        <a href="<?= cfc_e($pdfPresentation) ?>" target="_blank" rel="noopener nofollow"><?= cfc_cms_e('franchise.pdf_presentation_label') ?></a>
        <a href="<?= cfc_e($pdf3d) ?>" target="_blank" rel="noopener"><?= cfc_cms_e('franchise.pdf_3d_label') ?></a>
    </div>
    <div class="franchise-call">
        <h3><?= cfc_cms_e('franchise.for_heading') ?></h3>
        <a class="franchise-call__phone" href="tel:<?= cfc_e($tel) ?>" data-cfc-anim="fadeInUp">
            <span class="franchise-call__icon" aria-hidden="true">
                <svg viewBox="0 0 512 512"><path fill="currentColor" d="M497.39 361.8l-112-48a24 24 0 0 0-28 6.9l-49.6 60.6A370.66 370.66 0 0 1 130.6 204.11l60.6-49.6a23.94 23.94 0 0 0 6.9-28l-48-112A24.16 24.16 0 0 0 122.6.61l-104 24A24 24 0 0 0 0 48c0 256.5 207.9 464 464 464a24 24 0 0 0 23.4-18.6l24-104a24.29 24.29 0 0 0-14.01-27.6z"/></svg>
            </span>
            <span><?= cfc_e($phone) ?></span>
        </a>
    </div>
</section>

<section class="franchise-photo">
    <img src="<?= cfc_e(cfc_cms_src('franchise.photo')) ?>" alt="Best filter coffee franchise" width="1170" height="824">
</section>

<section class="franchise-quotes" data-quotes>
    <h2><?= cfc_cms_e('franchise.quotes_heading') ?></h2>
    <div class="franchise-quotes__viewport">
        <div class="franchise-quotes__track" data-quotes-track>
            <?php foreach ($quotes as $quote):
                $name = (string) ($quote['name'] ?? '');
                $role = (string) ($quote['role'] ?? '');
                $text = (string) ($quote['text'] ?? '');
                $file = cfc_cms_normalize_media((string) ($quote['image'] ?? ''));
                $pos = cfc_cms_object_position((string) ($quote['position'] ?? '50% 0%'));
                ?>
                <figure class="franchise-quote">
                    <div class="franchise-quote__meta">
                        <?php if ($file !== ''): ?>
                            <img src="<?= cfc_e(cfc_media($file)) ?>" alt="<?= cfc_e($name) ?>" width="85" height="85" style="object-position: <?= cfc_e($pos) ?>">
                        <?php endif; ?>
                        <figcaption>
                            <strong><?= cfc_e($name) ?></strong>
                        </figcaption>
                    </div>
                    <div class="franchise-quote__body">
                        <h3><?= cfc_e($role) ?></h3>
                        <blockquote><?= cfc_e($text) ?></blockquote>
                        <p class="franchise-quote__stars" aria-label="5 out of 5 stars">
                            <span>☆</span><span>☆</span><span>☆</span><span>☆</span><span>☆</span>
                        </p>
                    </div>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="franchise-quotes__dots" data-quotes-dots></div>
</section>
