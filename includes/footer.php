<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;
$social = cfc_cms_items('site.social');
$brands = cfc_cms_items('site.footer_brands');
$email = cfc_cms('site.email');
$phone = cfc_cms('site.footer_phone');
$phoneTel = cfc_cms('site.footer_phone_tel');
$alt = cfc_cms('site.phone_alt');
$altTel = cfc_cms('site.phone_alt_tel');
$wa = cfc_cms_digits('site.whatsapp');
?>
<footer class="cfc-footer">
    <div class="cfc-footer__inner">
        <div class="cfc-footer__brand">
            <a class="cfc-footer__logo" href="<?= cfc_e(cfc_url()) ?>">
                <img src="<?= cfc_e(cfc_cms_src('site.logo_footer')) ?>" alt="Chennapatnam Filter Coffee">
            </a>
            <p><?= cfc_cms_e('site.footer_tagline') ?></p>
            <div class="cfc-social">
                <?php foreach ($social as $item):
                    $label = (string) ($item['label'] ?? '');
                    $href = cfc_cms_href((string) ($item['url'] ?? ''));
                    if ($label === '' || $href === '') {
                        continue;
                    }
                    ?>
                    <a href="<?= cfc_e($href) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= cfc_e($label) ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="<?= cfc_cms_social_icon($label) ?>"></path></svg>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="cfc-footer__col">
            <h2><?= cfc_cms_e('site.quick_heading') ?></h2>
            <ul>
                <li><a href="<?= cfc_e(cfc_url('menu/')) ?>"><?= cfc_cms_e('site.nav_menu') ?></a></li>
                <li><a href="<?= cfc_e(cfc_url('about-us/')) ?>">Our Story</a></li>
                <li><a href="<?= cfc_e(cfc_url('franchise/')) ?>"><?= cfc_cms_e('site.nav_franchise') ?></a></li>
                <li><a href="<?= cfc_e(cfc_url('shop/')) ?>"><?= cfc_cms_e('site.nav_shop') ?></a></li>
                <li><a href="<?= cfc_e(cfc_url('contact-us/')) ?>">Contact us</a></li>
                <li><a href="<?= cfc_e(cfc_url('terms-and-conditions/')) ?>">Terms and Conditions</a></li>
                <li><a href="<?= cfc_e(cfc_url('privacy-policy/')) ?>">Privacy Policy</a></li>
            </ul>
        </div>
        <div class="cfc-footer__col">
            <h2><?= cfc_cms_e('site.brands_heading') ?></h2>
            <ul>
                <?php foreach ($brands as $brand):
                    $label = (string) ($brand['label'] ?? '');
                    $href = cfc_cms_href(trim((string) ($brand['url'] ?? '')));
                    if ($label === '') {
                        continue;
                    }
                    if ($href === '') {
                        echo '<li>' . cfc_e($label) . '</li>';
                        continue;
                    }
                    $abs = preg_match('#^https?://#i', $href) ? $href : cfc_url($href);
                    $ext = preg_match('#^https?://#i', $href);
                    echo '<li><a href="' . cfc_e($abs) . '"' . ($ext ? ' target="_blank" rel="noopener noreferrer"' : '') . '>' . cfc_e($label) . '</a></li>';
                endforeach; ?>
            </ul>
        </div>
        <div class="cfc-footer__col">
            <h2><?= cfc_cms_e('site.office_heading') ?></h2>
            <ul>
                <li><a href="mailto:<?= cfc_e($email) ?>"><?= cfc_e($email) ?></a></li>
                <li><a href="tel:<?= cfc_e($phoneTel) ?>"><?= cfc_e($phone) ?></a> <a href="tel:<?= cfc_e($altTel) ?>"><?= cfc_e($alt) ?></a></li>
                <li><?= cfc_e(cfc_cms('site.address')) ?></li>
            </ul>
        </div>
    </div>
</footer>
<?php if ($wa !== ''): ?>
<a class="cfc-whatsapp" href="https://wa.me/<?= cfc_e($wa) ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp us">
    <svg viewBox="0 0 32 32" aria-hidden="true"><path fill="#fff" d="M19.11 17.22c-.28-.14-1.64-.81-1.9-.9-.25-.1-.44-.14-.62.14-.18.28-.71.9-.87 1.08-.16.18-.32.2-.6.07-.28-.14-1.17-.43-2.23-1.37-.82-.73-1.38-1.64-1.54-1.92-.16-.28-.02-.43.12-.57.12-.12.28-.32.42-.48.14-.16.18-.28.28-.46.1-.18.05-.35-.02-.48-.07-.14-.62-1.5-.85-2.05-.22-.53-.45-.46-.62-.46h-.53c-.18 0-.48.07-.73.35-.25.28-.96.94-.96 2.3s.98 2.67 1.12 2.85c.14.18 1.93 2.95 4.67 4.14 1.74.75 2.2.82 2.99.69.46-.08 1.64-.67 1.87-1.32.23-.65.23-1.2.16-1.32-.07-.11-.25-.18-.53-.32z"/><path fill="#fff" d="M16.02 3C9.4 3 4.03 8.37 4.03 15c0 2.25.63 4.35 1.72 6.15L4 29l7.05-1.85A11.9 11.9 0 0 0 16.02 27C22.65 27 28 21.63 28 15S22.65 3 16.02 3zm0 21.82c-2.03 0-3.91-.55-5.53-1.5l-.4-.24-4.18 1.1 1.12-4.07-.26-.42a9.77 9.77 0 0 1-1.5-5.2c0-5.41 4.4-9.81 9.82-9.81 5.41 0 9.81 4.4 9.81 9.81 0 5.42-4.4 9.82-9.88 9.82z"/></svg>
</a>
<?php endif; ?>
<button class="cfc-top" type="button" aria-label="Scroll to top" data-scroll-top>↑</button>
