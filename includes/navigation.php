<?php
/**
 * Primary nav (live labels / slugs):
 * Home / About us / Menu / Shop / Franchise / Blog / Gallery / Media Hub / Contact
 */
if (!defined('CFC_ROOT')) {
    http_response_code(403);
    exit;
}

function cfc_nav_items(): array
{
    return [
        [cfc_cms('site.nav_home'), ''],
        [cfc_cms('site.nav_about'), 'about-us/'],
        [cfc_cms('site.nav_menu'), 'menu/'],
        [cfc_cms('site.nav_shop'), 'shop/'],
        [cfc_cms('site.nav_franchise'), 'franchise/'],
        [cfc_cms('site.nav_blog'), 'blog/'],
        [cfc_cms('site.nav_gallery'), 'gallery/'],
        [cfc_cms('site.nav_media'), 'media-hub/'],
        [cfc_cms('site.nav_contact'), 'contact-us/'],
    ];
}
