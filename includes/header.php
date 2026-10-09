<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;
/** @var string $headerLogo */
/** @var string $theme */
$nav = cfc_nav_items();
?>
<a class="cfc-skip" href="#content">Skip to main content</a>
<header class="cfc-header">
    <div class="cfc-header__inner">
        <a class="cfc-logo" href="<?= cfc_e(cfc_url()) ?>">
            <img src="<?= cfc_e($headerLogo) ?>" alt="Chennapatnam Filter Coffee — authentic South Indian filter coffee franchise" width="916" height="394"<?= ($theme ?? '') === 'home' ? ' data-cfc-anim="fadeInDown"' : '' ?>>
        </a>
        <button class="cfc-nav-toggle" type="button" aria-label="Open menu" aria-expanded="false" data-nav-toggle>
            <span></span><span></span><span></span>
        </button>
        <nav class="cfc-nav" aria-label="Primary" data-nav>
            <button class="cfc-nav-close" type="button" aria-label="Close menu" data-nav-close></button>
            <ul>
                <?php foreach ($nav as [$label, $slug]): ?>
                    <li>
                        <a href="<?= cfc_e(cfc_url($slug)) ?>"<?= cfc_is_current($slug) ? ' aria-current="page"' : '' ?>><?= cfc_e($label) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
</header>
<div class="cfc-nav-backdrop" data-nav-backdrop hidden></div>
