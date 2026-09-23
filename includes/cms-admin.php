<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

function cfc_admin_save_flash(bool $ok, string $okMsg, string $errMsg): array
{
    $notes = cfc_cms_notes();
    if (!$ok) {
        return ['err', $notes !== [] ? $errMsg . ' ' . implode(' ', $notes) : $errMsg];
    }
    if ($notes !== []) {
        return ['err', $okMsg . ' Some files were skipped: ' . implode(' ', $notes)];
    }
    return ['ok', $okMsg];
}

function cfc_admin_url(string $query = ''): string
{
    $base = cfc_url('admin/');
    return $query === '' ? $base : $base . '?' . $query;
}

function cfc_admin_flash(?array $set = null): ?array
{
    if ($set !== null) {
        $_SESSION['cfc_admin_flash'] = $set;
        return $set;
    }
    $flash = $_SESSION['cfc_admin_flash'] ?? null;
    unset($_SESSION['cfc_admin_flash']);
    return is_array($flash) ? $flash : null;
}

function cfc_admin_layout(string $title, string $body, string $active = ''): never
{
    $flash = cfc_admin_flash();
    $flashHtml = '';
    if ($flash) {
        $cls = ($flash[0] ?? '') === 'ok' ? 'cms-flash--ok' : 'cms-flash--err';
        $flashHtml = '<div class="cms-flash ' . $cls . '">' . cfc_e((string) ($flash[1] ?? '')) . '</div>';
    }
    $css = cfc_url('admin/admin.css') . '?v=' . filemtime(CFC_ROOT . '/admin/admin.css');
    $js = cfc_url('admin/admin.js') . '?v=' . filemtime(CFC_ROOT . '/admin/admin.js');
    $nav = cfc_admin_nav_html($active);
    $favicon = cfc_cms_src('site.favicon');
    $touch = cfc_media('2024/03/cropped-Fev-180x180.png');
    cfc_send(200, '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>' . cfc_e($title) . ' · CFC CMS</title>
<link rel="icon" href="' . cfc_e($favicon) . '" sizes="32x32">
<link rel="apple-touch-icon" href="' . cfc_e($touch) . '">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="' . cfc_e($css) . '"></head>
<body class="cms">
<div class="cms-shell">
  <aside class="cms-side" data-cms-side>
    <a class="cms-brand" href="' . cfc_e(cfc_admin_url()) . '">
      <span class="cms-brand__name">CFC CMS</span>
      <span class="cms-brand__sub">Content editor</span>
    </a>
    ' . $nav . '
  </aside>
  <div class="cms-main">
    <header class="cms-top">
      <button class="cms-menu-btn" type="button" data-cms-menu aria-label="Open menu">Menu</button>
      <h1>' . cfc_e($title) . '</h1>
      <nav>
        <span class="cms-who">' . cfc_e((string) ($_SESSION['cfc_admin_name'] ?? $_SESSION['cfc_admin_user'] ?? 'Admin')) . '</span>
        <a href="' . cfc_e(cfc_url()) . '" target="_blank" rel="noopener">View site</a>
        <a href="' . cfc_e(cfc_admin_url('logout=1')) . '">Log out</a>
      </nav>
    </header>
    <div class="cms-body">' . $flashHtml . $body . '</div>
  </div>
</div>
<script src="' . cfc_e($js) . '"></script>
</body></html>');
}

function cfc_admin_nav_html(string $active): string
{
    $item = static function (string $id, string $label, string $query = '', bool $child = false) use ($active): string {
        $href = $query === '' ? cfc_admin_url() : cfc_admin_url($query);
        $cls = 'cms-nav__link';
        if ($child) {
            $cls .= ' cms-nav__link--child';
        }
        if ($active === $id) {
            $cls .= ' is-on';
        }
        return '<a class="' . $cls . '" href="' . cfc_e($href) . '">' . cfc_e($label) . '</a>';
    };

    $groups = [
        [
            'id' => 'site',
            'label' => 'Site',
            'ids' => ['site', 'redirects'],
            'html' => $item('site', 'Header & Footer', 'p=site')
                . $item('redirects', 'Redirects', 'p=redirects'),
        ],
        [
            'id' => 'pages',
            'label' => 'Pages',
            'ids' => ['home', 'about', 'menu', 'shop', 'franchise', 'contact', 'landing'],
            'html' => $item('home', 'Home', 'p=home')
                . $item('about', 'About us', 'p=about')
                . $item('menu', 'Menu', 'p=menu')
                . $item('shop', 'Shop', 'p=shop')
                . $item('franchise', 'Franchise', 'p=franchise')
                . $item('contact', 'Contact', 'p=contact')
                . $item('landing', 'Landing', 'p=landing'),
        ],
        [
            'id' => 'content',
            'label' => 'Content',
            'ids' => ['blog-posts', 'blog', 'gallery-images', 'gallery', 'media-images', 'media'],
            'html' => $item('blog-posts', 'Blog posts', 'p=blog-posts')
                . $item('blog', 'Blog page', 'p=blog', true)
                . $item('gallery-images', 'Gallery photos', 'p=gallery-images')
                . $item('gallery', 'Gallery page', 'p=gallery', true)
                . $item('media-images', 'Media Hub photos', 'p=media-images')
                . $item('media', 'Media Hub page', 'p=media', true),
        ],
        [
            'id' => 'legal',
            'label' => 'Legal',
            'ids' => ['thankyou', 'privacy', 'terms', 'notfound'],
            'html' => $item('thankyou', 'Thank you', 'p=thankyou')
                . $item('privacy', 'Privacy', 'p=privacy')
                . $item('terms', 'Terms', 'p=terms')
                . $item('notfound', '404', 'p=notfound'),
        ],
        [
            'id' => 'inbox',
            'label' => 'Inbox',
            'ids' => ['submissions'],
            'html' => $item('submissions', 'Submissions', 'p=submissions'),
        ],
    ];
    if (cfc_admin_is_admin()) {
        $groups[] = [
            'id' => 'settings',
            'label' => 'Settings',
            'ids' => ['users', 'captcha', 'instagram', 'server'],
            'html' => $item('users', 'Users', 'p=users')
                . $item('captcha', 'Captcha', 'p=captcha')
                . $item('instagram', 'Instagram Feed', 'p=instagram')
                . $item('server', 'Server', 'p=server'),
        ];
    }

    $html = '<nav class="cms-nav" aria-label="CMS">';
    $html .= $item('dash', 'Overview');
    foreach ($groups as $group) {
        $open = in_array($active, $group['ids'], true);
        $html .= '<details class="cms-nav__group" data-nav-group="' . cfc_e($group['id']) . '"' . ($open ? ' open' : '') . '>';
        $html .= '<summary class="cms-nav__sum">' . cfc_e($group['label']) . '</summary>';
        $html .= '<div class="cms-nav__list">' . $group['html'] . '</div>';
        $html .= '</details>';
    }
    $html .= '</nav>';
    return $html;
}

function cfc_admin_dashboard(): never
{
    $schema = cfc_cms_schema();
    $cards = '';
    foreach ($schema as $key => $page) {
        $path = (string) ($page['path'] ?? '');
        $slug = $path === '' ? 'Site-wide' : '/' . ltrim($path, '/');
        $cards .= '<a class="cms-card" href="' . cfc_e(cfc_admin_url('p=' . urlencode((string) $key))) . '"><strong>' . cfc_e((string) ($page['label'] ?? $key)) . '</strong><span>' . cfc_e($slug) . '</span></a>';
    }
    $cards .= '<a class="cms-card" href="' . cfc_e(cfc_admin_url('p=gallery-images')) . '"><strong>Gallery images</strong><span>Replace or add outlet photos</span></a>';
    $cards .= '<a class="cms-card" href="' . cfc_e(cfc_admin_url('p=media-images')) . '"><strong>Media Hub images</strong><span>Manual Instagram tiles</span></a>';
    if (cfc_admin_is_admin()) {
        $igSpan = cfc_instagram_enabled()
            ? (cfc_instagram_feed_count() . ' cached posts')
            : 'Connect Graph API';
        $cards .= '<a class="cms-card" href="' . cfc_e(cfc_admin_url('p=instagram')) . '"><strong>Instagram Feed</strong><span>' . cfc_e($igSpan) . '</span></a>';
    }
    $cards .= '<a class="cms-card" href="' . cfc_e(cfc_admin_url('p=blog-posts')) . '"><strong>Blog posts</strong><span>Titles, excerpts, images, body</span></a>';
    $cards .= '<a class="cms-card" href="' . cfc_e(cfc_admin_url('p=redirects')) . '"><strong>Redirects</strong><span>' . count(cfc_redirects()) . ' URL rules</span></a>';
    $n = cfc_submissions_count();
    $cards .= '<a class="cms-card" href="' . cfc_e(cfc_admin_url('p=submissions')) . '"><strong>Form submissions</strong><span>' . (int) $n . ' received</span></a>';
    if (cfc_admin_is_admin()) {
        $cards .= '<a class="cms-card" href="' . cfc_e(cfc_admin_url('p=users')) . '"><strong>Users</strong><span>' . count(cfc_users()) . ' accounts</span></a>';
        $cards .= '<a class="cms-card" href="' . cfc_e(cfc_admin_url('p=captcha')) . '"><strong>Captcha</strong><span>' . (cfc_turnstile_enabled() ? 'Cloudflare Turnstile on' : 'Not configured') . '</span></a>';
        $cards .= '<a class="cms-card" href="' . cfc_e(cfc_admin_url('p=server')) . '"><strong>Server</strong><span>PHP ' . cfc_e(PHP_VERSION) . (cfc_debug() ? ' · debug on' : '') . '</span></a>';
    }
    $intro = '<p>Edit every page’s text and images. Changes go live on the site immediately. Leave a field blank and save to restore the original copy.</p>';
    if (cfc_debug()) {
        $intro = '<div class="cms-flash cms-flash--err">Debug is on. Set <code>debug</code> to false in config/config.local.php before this host goes live.</div>' . $intro;
    }
    foreach (cfc_runtime_report()['issues'] as $issue) {
        if (str_contains((string) $issue, 'Debug is on')) {
            continue;
        }
        $intro .= '<div class="cms-flash cms-flash--err">' . cfc_e((string) $issue) . '</div>';
    }
    cfc_admin_layout('Dashboard', $intro . '<div class="cms-cards">' . $cards . '</div>', 'dash');
}

function cfc_admin_field_value(string $page, array $field)
{
    $key = (string) ($field['key'] ?? '');
    $pageData = cfc_cms_page_data($page);
    if (isset($pageData[$key]) && is_scalar($pageData[$key]) && (string) $pageData[$key] !== '') {
        return (string) $pageData[$key];
    }
    return (string) ($field['default'] ?? '');
}

function cfc_admin_render_field(string $name, array $field, string $value, string $previewId = ''): string
{
    $type = (string) ($field['type'] ?? 'text');
    $label = cfc_e((string) ($field['label'] ?? $field['key'] ?? ''));
    $id = $previewId !== '' ? $previewId : ('f-' . md5($name));
    if ($type === 'select') {
        $opts = '';
        foreach ($field['options'] ?? [] as $ov => $ol) {
            $sel = (string) $ov === $value ? ' selected' : '';
            $opts .= '<option value="' . cfc_e((string) $ov) . '"' . $sel . '>' . cfc_e((string) $ol) . '</option>';
        }
        return '<div class="cms-field"><label class="cap">' . $label . '</label><select name="' . cfc_e($name) . '">' . $opts . '</select></div>';
    }
    if ($type === 'textarea' || $type === 'html' || $type === 'code') {
        $cls = ($type === 'html' || $type === 'code') ? ' html' : '';
        $wys = $type === 'html' ? ' data-wysiwyg' : '';
        $ph = $type === 'code' ? ' placeholder="Paste HTML, CSS, or JavaScript…"' : '';
        return '<div class="cms-field"><label class="cap">' . $label . '</label><textarea class="' . $cls . '" name="' . cfc_e($name) . '"' . $wys . $ph . '>' . cfc_e($value) . '</textarea></div>';
    }
    if ($type === 'image' || $type === 'file') {
        $norm = cfc_cms_normalize_media($value);
        $src = $norm === '' ? '' : (preg_match('#^https?://#i', $norm) || str_starts_with($norm, 'data:image/') ? $norm : cfc_media($norm));
        $img = '';
        if ($type === 'image' && $src !== '') {
            $img = '<img id="' . cfc_e($id) . '" src="' . cfc_e($src) . '" alt="">';
        } else {
            $img = '<img id="' . cfc_e($id) . '" alt="" hidden>';
        }
        $accept = $type === 'file' ? 'image/*,video/mp4,application/pdf' : 'image/*';
        $fileName = preg_replace('/^fields\[(.+)\]$/', 'uploads[$1]', $name) ?? ('uploads[' . $name . ']');
        if (str_starts_with($name, 'repeaters[')) {
            $fileName = preg_replace('/^repeaters/', 'uploads_repeaters', $name) ?? $name;
        }
        $clearName = preg_replace('/^fields\[(.+)\]$/', 'clear[$1]', $name) ?? ('clear[' . $name . ']');
        if (str_starts_with($name, 'repeaters[')) {
            $clearName = preg_replace('/^repeaters/', 'clear_repeaters', $name) ?? $name;
        }
        return '<div class="cms-field"><label class="cap">' . $label . '</label><div class="cms-image">' . $img . '<div class="meta">
            <input type="hidden" name="' . cfc_e($name) . '" value="' . cfc_e($value) . '">
            <input type="file" name="' . cfc_e($fileName) . '" accept="' . $accept . '" data-preview="#' . cfc_e($id) . '">
            <label><input type="checkbox" name="' . cfc_e($clearName) . '" value="1"> Reset to original</label>
            <p class="cms-help">Current: ' . cfc_e($value !== '' ? $value : '(original)') . '</p>
        </div></div></div>';
    }
    $inputType = $type === 'url' ? 'url' : 'text';
    return '<div class="cms-field"><label class="cap">' . $label . '</label><input type="' . $inputType . '" name="' . cfc_e($name) . '" value="' . cfc_e($value) . '"></div>';
}

function cfc_admin_repeater_item(string $page, string $repKey, array $rep, array $item, string $index): string
{
    $fields = '';
    foreach ($rep['fields'] ?? [] as $field) {
        $fkey = (string) ($field['key'] ?? '');
        $val = (string) ($item[$fkey] ?? ($field['default'] ?? ''));
        $name = 'repeaters[' . $repKey . '][' . $index . '][' . $fkey . ']';
        $fields .= cfc_admin_render_field($name, $field, $val, 'p-' . $page . '-' . $repKey . '-' . $index . '-' . $fkey);
    }
    return '<div class="cms-item" data-repeater-item>
      <div class="cms-item__bar"><span>Item</span><button type="button" data-repeater-remove>Remove</button></div>
      ' . $fields . '
    </div>';
}

function cfc_admin_page_editor(string $page): never
{
    $schema = cfc_cms_page_schema($page);
    if (!$schema) {
        cfc_admin_flash(['err', 'Unknown page.']);
        cfc_redirect('admin/');
    }
    $html = '<form method="post" action="' . cfc_e(cfc_admin_url('p=' . urlencode($page))) . '" enctype="multipart/form-data">';
    $html .= '<input type="hidden" name="cfc_csrf" value="' . cfc_e(cfc_csrf_token()) . '">';
    $html .= '<input type="hidden" name="cms_action" value="save_page">';
    foreach ($schema['groups'] ?? [] as $group) {
        $html .= '<section class="cms-group"><h2>' . cfc_e((string) ($group['label'] ?? 'Fields')) . '</h2>';
        foreach ($group['fields'] ?? [] as $field) {
            $key = (string) ($field['key'] ?? '');
            $val = cfc_admin_field_value($page, $field);
            $html .= cfc_admin_render_field('fields[' . $key . ']', $field, $val, 'f-' . $page . '-' . $key);
        }
        $html .= '</section>';
    }
    foreach ($schema['repeaters'] ?? [] as $repKey => $rep) {
        $items = cfc_cms_items($page . '.' . $repKey);
        $list = '';
        foreach ($items as $i => $item) {
            $list .= cfc_admin_repeater_item($page, (string) $repKey, $rep, is_array($item) ? $item : [], (string) $i);
        }
        $blank = [];
        foreach ($rep['fields'] ?? [] as $field) {
            $blank[(string) ($field['key'] ?? '')] = (string) ($field['default'] ?? '');
        }
        $tpl = cfc_admin_repeater_item($page, (string) $repKey, $rep, $blank, '__i__');
        $html .= '<section class="cms-group" data-repeater><h2>' . cfc_e((string) ($rep['label'] ?? $repKey)) . '</h2>';
        $html .= '<input type="hidden" name="repeaters[' . cfc_e((string) $repKey) . '][_ok]" value="1">';
        $html .= '<div data-repeater-list>' . $list . '</div>';
        $html .= '<template data-repeater-template>' . $tpl . '</template>';
        $html .= '<button class="cms-add" type="button" data-repeater-add>' . cfc_e((string) ($rep['add'] ?? 'Add item')) . '</button>';
        $html .= '</section>';
    }
    $view = (string) ($schema['path'] ?? '');
    $viewLink = $view !== '' || $page === 'home' || $page === 'site'
        ? '<a class="cms-btn cms-btn--ghost" href="' . cfc_e(cfc_url($view)) . '" target="_blank" rel="noopener">Preview page</a>'
        : '';
    $html .= '<div class="cms-actions">
        <button class="cms-btn" type="submit">Save changes</button>
        ' . $viewLink . '
        <button class="cms-btn cms-btn--danger" type="submit" name="cms_action" value="reset_page" onclick="return confirm(\'Reset this page to the original live copy?\')">Reset to original</button>
      </div></form>';
    cfc_admin_layout((string) ($schema['label'] ?? $page), $html, $page);
}

function cfc_admin_gallery(): never
{
    $sections = cfc_cms_gallery();
    $html = '<form method="post" action="' . cfc_e(cfc_admin_url('p=gallery-images')) . '" enctype="multipart/form-data">';
    $html .= '<input type="hidden" name="cfc_csrf" value="' . cfc_e(cfc_csrf_token()) . '">';
    $html .= '<input type="hidden" name="cms_action" value="save_gallery">';
    $html .= '<section class="cms-group"><h2>Replace one image</h2><p class="cms-help">Tick “Replace this” on a thumbnail, choose a file here, then save. PHP only accepts a few uploads at once, so gallery uses one replace per save.</p><div class="cms-field"><input type="file" name="replace_one" accept="image/*"></div></section>';
    foreach ($sections as $si => $section) {
        $html .= '<section class="cms-group"><h2>Section ' . ((int) $si + 1) . '</h2>';
        $html .= '<div class="cms-field"><label class="cap">Title</label><input type="text" name="section_title[' . (int) $si . ']" value="' . cfc_e((string) ($section['title'] ?? '')) . '"></div>';
        $html .= '<div class="cms-field"><label class="cap">Heading level</label><select name="section_level[' . (int) $si . ']">';
        $lvl = (string) ($section['level'] ?? 'h2');
        $html .= '<option value="h1"' . ($lvl === 'h1' ? ' selected' : '') . '>H1</option>';
        $html .= '<option value="h2"' . ($lvl === 'h2' ? ' selected' : '') . '>H2</option></select></div>';
        $html .= '<div class="cms-ggrid">';
        foreach ($section['images'] ?? [] as $ii => $img) {
            $src = cfc_media((string) ($img['thumb'] ?? $img['full'] ?? ''));
            $html .= '<div class="cms-gitem">
                <img src="' . cfc_e($src) . '" alt="">
                <label><input type="radio" name="replace_at" value="' . (int) $si . ':' . (int) $ii . '"> Replace this</label>
                <label><input type="checkbox" name="delete[' . (int) $si . '][' . (int) $ii . ']" value="1"> Delete</label>
            </div>';
        }
        $html .= '</div>';
        $html .= '<div class="cms-field"><label class="cap">Add images to this section</label><input type="file" name="add[' . (int) $si . '][]" accept="image/*" multiple></div>';
        $html .= '</section>';
    }
    $html .= '<div class="cms-actions"><button class="cms-btn" type="submit">Save gallery</button>
      <a class="cms-btn cms-btn--ghost" href="' . cfc_e(cfc_url('gallery/')) . '" target="_blank" rel="noopener">Preview</a></div></form>';
    cfc_admin_layout('Gallery images', $html, 'gallery-images');
}

function cfc_admin_media_hub(): never
{
    $posts = cfc_cms_media_hub();
    $html = '<form method="post" action="' . cfc_e(cfc_admin_url('p=media-images')) . '" enctype="multipart/form-data">';
    $html .= '<input type="hidden" name="cfc_csrf" value="' . cfc_e(cfc_csrf_token()) . '">';
    $html .= '<input type="hidden" name="cms_action" value="save_media">';
    $html .= '<section class="cms-group"><h2>Replace one tile</h2><p class="cms-help">Tick “Replace this” on a tile, choose a file, then save.</p><div class="cms-field"><input type="file" name="replace_one" accept="image/*"></div></section>';
    $html .= '<section class="cms-group"><h2>Tiles</h2><div class="cms-ggrid">';
    foreach ($posts as $i => $post) {
        $src = cfc_media((string) ($post['file'] ?? ''));
        $html .= '<div class="cms-gitem">
            <img src="' . cfc_e($src) . '" alt="">
            <input type="url" name="href[' . (int) $i . ']" value="' . cfc_e((string) ($post['href'] ?? '')) . '" placeholder="Instagram URL">
            <label><input type="radio" name="replace_at" value="' . (int) $i . '"> Replace this</label>
            <label><input type="checkbox" name="delete[' . (int) $i . ']" value="1"> Delete</label>
        </div>';
    }
    $html .= '</div></section>';
    $html .= '<section class="cms-group"><h2>Add Instagram posts</h2>
        <p class="cms-help">Paste one or more Instagram reel/post URLs. Covers are fetched automatically and new tiles appear at the <strong>top</strong> of the grid.</p>
        <div class="cms-field"><label class="cap">Instagram URLs</label><textarea name="add_urls" rows="4" placeholder="https://www.instagram.com/reel/..."></textarea></div>
        <div class="cms-field"><label class="cap">Or a single URL</label><input type="url" name="add_href" placeholder="https://www.instagram.com/reel/..."></div>
        <div class="cms-field"><label class="cap">Optional image (only if the automatic cover fails)</label><input type="file" name="add_file" accept="image/*"></div>
      </section>';
    $html .= '<p class="cms-help">These manual tiles are the fallback when the live Graph feed is off or empty. Admins: connect the official API under <a href="' . cfc_e(cfc_admin_url('p=instagram')) . '">Instagram Feed</a>.</p>';
    $html .= '<div class="cms-actions"><button class="cms-btn" type="submit">Save Media Hub</button>
      <a class="cms-btn cms-btn--ghost" href="' . cfc_e(cfc_url('media-hub/')) . '" target="_blank" rel="noopener">Preview</a></div></form>';
    cfc_admin_layout('Media Hub images', $html, 'media-images');
}

function cfc_admin_blog_list(): never
{
    $all = cfc_blog_index();
    $filterCat = trim((string) ($_GET['cat'] ?? ''));
    $query = trim((string) ($_GET['q'] ?? ''));
    $needle = $query !== '' ? cfc_lower($query) : '';

    $cats = [];
    foreach ($all as $post) {
        $slug = (string) ($post['category'] ?? '');
        if ($slug === '') {
            continue;
        }
        if (!isset($cats[$slug])) {
            $cats[$slug] = [
                'slug' => $slug,
                'name' => (string) ($post['category_name'] ?? ucwords(str_replace('-', ' ', $slug))),
                'n' => 0,
            ];
        }
        $cats[$slug]['n']++;
    }

    $filtered = [];
    foreach ($all as $post) {
        $slug = (string) ($post['category'] ?? '');
        if ($filterCat !== '' && $slug !== $filterCat) {
            continue;
        }
        if ($needle !== '') {
            $hay = cfc_lower(($post['title'] ?? '') . ' ' . ($post['excerpt'] ?? '') . ' ' . ($post['slug'] ?? ''));
            if (!str_contains($hay, $needle)) {
                continue;
            }
        }
        $filtered[] = $post;
    }

    $chip = static function (string $label, string $href, bool $on, int $n): string {
        return '<a class="cms-filter' . ($on ? ' is-on' : '') . '" href="' . cfc_e($href) . '">'
            . cfc_e($label) . ' <span>' . $n . '</span></a>';
    };
    $qs = static function (array $extra) use ($filterCat, $query): string {
        $p = array_filter([
            'p' => 'blog-posts',
            'cat' => $extra['cat'] ?? $filterCat,
            'q' => array_key_exists('q', $extra) ? $extra['q'] : $query,
        ], static fn($v): bool => $v !== null && $v !== '');
        return http_build_query($p);
    };

    $filters = $chip('All', cfc_admin_url($qs(['cat' => ''])), $filterCat === '', count($all));
    foreach ($cats as $cat) {
        $filters .= $chip($cat['name'], cfc_admin_url($qs(['cat' => $cat['slug']])), $filterCat === $cat['slug'], $cat['n']);
    }

    $rows = '';
    foreach ($filtered as $post) {
        $path = (string) ($post['path'] ?? '');
        $img = (string) ($post['image'] ?? '');
        $title = (string) ($post['title'] ?? '');
        $date = (string) ($post['date'] ?? '');
        $cat = (string) ($post['category_name'] ?? $post['category'] ?? '');
        $edit = cfc_admin_url('p=blog-posts&edit=' . rawurlencode($path));
        $thumb = $img !== ''
            ? '<img src="' . cfc_e(cfc_media($img)) . '" alt="">'
            : '<span class="cms-post__ph" aria-hidden="true"></span>';
        $rows .= '<article class="cms-post">
            <a class="cms-post__media" href="' . cfc_e($edit) . '">' . $thumb . '</a>
            <div class="cms-post__body">
                <h2 class="cms-post__title"><a href="' . cfc_e($edit) . '">' . cfc_e($title) . '</a></h2>
                <p class="cms-post__meta">
                    ' . ($date !== '' ? '<time>' . cfc_e($date) . '</time>' : '') . '
                    ' . ($cat !== '' ? '<a class="cms-pill" href="' . cfc_e(cfc_admin_url($qs(['cat' => (string) ($post['category'] ?? '')]))) . '">' . cfc_e($cat) . '</a>' : '') . '
                </p>
            </div>
            <a class="cms-post__edit" href="' . cfc_e($edit) . '">Edit</a>
        </article>';
    }
    if ($rows === '') {
        $rows = '<p class="cms-empty">No posts match these filters.</p>';
    }

    $html = '<div class="cms-toolbar">
        <form class="cms-search" method="get" action="' . cfc_e(cfc_admin_url()) . '">
          <input type="hidden" name="p" value="blog-posts">
          ' . ($filterCat !== '' ? '<input type="hidden" name="cat" value="' . cfc_e($filterCat) . '">' : '') . '
          <input type="search" name="q" value="' . cfc_e($query) . '" placeholder="Search titles…" aria-label="Search posts">
          <button class="cms-btn cms-btn--ghost" type="submit">Search</button>
        </form>
        <a class="cms-btn" href="' . cfc_e(cfc_admin_url('p=blog-posts&new=1')) . '">New post</a>
      </div>
      <div class="cms-filters">' . $filters . '</div>
      <p class="cms-count">' . count($filtered) . ' of ' . count($all) . ' posts</p>
      <div class="cms-posts">' . $rows . '</div>';
    cfc_admin_layout('Blog posts', $html, 'blog-posts');
}

function cfc_admin_blog_editor(?string $path, bool $isNew): never
{
    $post = null;
    if ($path) {
        foreach (cfc_blog_index() as $row) {
            if (($row['path'] ?? '') === $path || ($row['slug'] ?? '') === $path) {
                $post = $row;
                break;
            }
        }
        if ($post === null) {
            cfc_admin_flash(['err', 'That blog post was not found.']);
            cfc_redirect('admin/?p=blog-posts');
        }
    }
    $body = '';
    if ($post) {
        $body = trim((string) ($post['body'] ?? ''));
        if ($body === '') {
            $file = cfc_blog_file($post);
            if ($file && is_file($file)) {
                $body = cfc_extract_post_html((string) cfc_read($file));
            }
        }
        $body = preg_replace('/\sclass="isSelectedEnd"/i', '', $body) ?? $body;
        $body = preg_replace('/\sclass="[^"]*"/i', '', $body) ?? $body;
    }
    $post = is_array($post) ? $post : [];
    $title = (string) ($post['title'] ?? '');
    $excerpt = (string) ($post['excerpt'] ?? '');
    $date = (string) ($post['date'] ?? date('Y-m-d'));
    $cat = (string) ($post['category'] ?? 'franchise');
    $catName = (string) ($post['category_name'] ?? 'Franchise');
    $slug = (string) ($post['slug'] ?? '');
    $image = (string) ($post['image'] ?? '');
    $imgHtml = $image !== '' ? '<img src="' . cfc_e(cfc_media($image)) . '" alt="" style="max-width:240px;display:block;margin:8px 0">' : '';
    $action = $isNew ? 'new=1' : 'edit=' . rawurlencode((string) ($post['path'] ?? $path));
    $html = '<form method="post" action="' . cfc_e(cfc_admin_url('p=blog-posts&' . $action)) . '" enctype="multipart/form-data">
      <input type="hidden" name="cfc_csrf" value="' . cfc_e(cfc_csrf_token()) . '">
      <input type="hidden" name="cms_action" value="save_blog">
      <section class="cms-group"><h2>Post</h2>
        <div class="cms-field"><label class="cap">Title</label><input type="text" name="title" value="' . cfc_e($title) . '" required></div>
        <div class="cms-field"><label class="cap">Slug</label><input type="text" name="slug" value="' . cfc_e($slug) . '" ' . ($isNew ? '' : 'readonly') . '></div>
        <div class="cms-field"><label class="cap">Date</label><input type="date" name="date" value="' . cfc_e($date) . '"></div>
        <div class="cms-field"><label class="cap">Category slug</label><input type="text" name="category" value="' . cfc_e($cat) . '"></div>
        <div class="cms-field"><label class="cap">Category label</label><input type="text" name="category_name" value="' . cfc_e($catName) . '"></div>
        <div class="cms-field"><label class="cap">Excerpt</label><textarea name="excerpt">' . cfc_e($excerpt) . '</textarea></div>
        <div class="cms-field"><label class="cap">Featured image</label>' . $imgHtml . '
          <input type="hidden" name="image" value="' . cfc_e($image) . '">
          <input type="file" name="image_file" accept="image/*">
          <label><input type="checkbox" name="clear_image" value="1"> Remove image</label>
        </div>
        <div class="cms-field"><label class="cap">Body</label><textarea name="body" data-wysiwyg rows="16">' . cfc_e($body) . '</textarea></div>
      </section>
      <section class="cms-group"><h2>SEO</h2>
        <div class="cms-field"><label class="cap">SEO title</label><input type="text" name="seo_title" value="' . cfc_e((string) ($post['seo_title'] ?? '')) . '"></div>
        <div class="cms-field"><label class="cap">Meta description</label><textarea name="seo_description">' . cfc_e((string) ($post['seo_description'] ?? '')) . '</textarea></div>
        <div class="cms-field"><label class="cap">Primary keyword</label><input type="text" name="primary_keyword" value="' . cfc_e((string) ($post['primary_keyword'] ?? '')) . '"></div>
        <div class="cms-field"><label class="cap">Secondary keyword</label><input type="text" name="secondary_keyword" value="' . cfc_e((string) ($post['secondary_keyword'] ?? '')) . '"></div>
        <div class="cms-field"><label class="cap">Keywords (comma separated)</label><textarea name="keywords">' . cfc_e((string) ($post['keywords'] ?? '')) . '</textarea></div>
        <div class="cms-field"><label class="cap">Robots</label><select name="seo_robots">';
    $robots = (string) ($post['seo_robots'] ?? 'index,follow');
    foreach (['index,follow' => 'Index, follow', 'noindex,follow' => 'Noindex, follow', 'index,nofollow' => 'Index, nofollow', 'noindex,nofollow' => 'Noindex, nofollow'] as $ov => $ol) {
        $html .= '<option value="' . cfc_e($ov) . '"' . ($robots === $ov ? ' selected' : '') . '>' . cfc_e($ol) . '</option>';
    }
    $html .= '</select></div>
        <div class="cms-field"><label class="cap">Social title (optional)</label><input type="text" name="og_title" value="' . cfc_e((string) ($post['og_title'] ?? '')) . '"></div>
        <div class="cms-field"><label class="cap">Social description (optional)</label><textarea name="og_description">' . cfc_e((string) ($post['og_description'] ?? '')) . '</textarea></div>
      </section>
      <section class="cms-group"><h2>Custom code snippets</h2>
        <div class="cms-field"><label class="cap">Header code (inside &lt;head&gt;)</label><textarea class="html" name="code_head" placeholder="Paste HTML, CSS, or JavaScript…">' . cfc_e((string) ($post['code_head'] ?? '')) . '</textarea></div>
        <div class="cms-field"><label class="cap">Body code (after &lt;body&gt;)</label><textarea class="html" name="code_body" placeholder="Paste HTML, CSS, or JavaScript…">' . cfc_e((string) ($post['code_body'] ?? '')) . '</textarea></div>
        <div class="cms-field"><label class="cap">Footer code (before &lt;/body&gt;)</label><textarea class="html" name="code_footer" placeholder="Paste HTML, CSS, or JavaScript…">' . cfc_e((string) ($post['code_footer'] ?? '')) . '</textarea></div>
      </section>';
    $faqRep = [
        'label' => 'FAQ',
        'add' => 'Add question',
        'fields' => [
            ['key' => 'question', 'type' => 'text', 'label' => 'Question'],
            ['key' => 'answer', 'type' => 'textarea', 'label' => 'Answer'],
        ],
    ];
    $faqItems = is_array($post['faqs'] ?? null) ? $post['faqs'] : [];
    $faqList = '';
    foreach ($faqItems as $i => $item) {
        $faqList .= cfc_admin_repeater_item('blog', 'faqs', $faqRep, is_array($item) ? $item : [], (string) $i);
    }
    $html .= '<section class="cms-group" data-repeater><h2>FAQ</h2>
        <input type="hidden" name="repeaters[faqs][_ok]" value="1">
        <div data-repeater-list>' . $faqList . '</div>
        <template data-repeater-template>' . cfc_admin_repeater_item('blog', 'faqs', $faqRep, ['question' => '', 'answer' => ''], '__i__') . '</template>
        <button class="cms-add" type="button" data-repeater-add>Add question</button>
      </section>
      <div class="cms-actions">
        <button class="cms-btn" type="submit">Save post</button>
        <a class="cms-btn cms-btn--ghost" href="' . cfc_e(cfc_admin_url('p=blog-posts')) . '">Back to list</a>';
    if ($post && !empty($post['path'])) {
        $html .= '<a class="cms-btn cms-btn--ghost" href="' . cfc_e(cfc_url(trim((string) $post['path'], '/') . '/')) . '" target="_blank" rel="noopener">View</a>';
        $html .= '<button class="cms-btn cms-btn--danger" type="submit" name="cms_action" value="delete_blog" onclick="return confirm(\'Remove this post from the listing?\')">Delete from listing</button>';
    }
    $html .= '</div></form>';
    cfc_admin_layout($isNew ? 'New blog post' : 'Edit blog post', $html, 'blog-posts');
}

function cfc_admin_redirect_row(array $row, string $index): string
{
    $from = cfc_e((string) ($row['from'] ?? ''));
    $to = cfc_e((string) ($row['to'] ?? ''));
    $code = (int) ($row['code'] ?? 301) === 302 ? 302 : 301;
    return '<div class="cms-item cms-redir" data-repeater-item>
      <div class="cms-item__bar"><span>Redirect</span><button type="button" data-repeater-remove>Remove</button></div>
      <div class="cms-redir__grid">
        <div class="cms-field"><label class="cap">From path</label><input type="text" name="redirects[' . $index . '][from]" value="' . $from . '" placeholder="/product-category/lemon-tea/"></div>
        <div class="cms-field"><label class="cap">To URL</label><input type="text" name="redirects[' . $index . '][to]" value="' . $to . '" placeholder="https://www.andaalhomefoods.com/..."></div>
        <div class="cms-field"><label class="cap">Type</label><select name="redirects[' . $index . '][code]">
            <option value="301"' . ($code === 301 ? ' selected' : '') . '>301 permanent</option>
            <option value="302"' . ($code === 302 ? ' selected' : '') . '>302 temporary</option>
        </select></div>
      </div>
    </div>';
}

function cfc_admin_redirects(): never
{
    $list = '';
    foreach (cfc_redirects() as $i => $row) {
        $list .= cfc_admin_redirect_row($row, (string) $i);
    }
    $html = '<form method="post" action="' . cfc_e(cfc_admin_url('p=redirects')) . '">
      <input type="hidden" name="cfc_csrf" value="' . cfc_e(cfc_csrf_token()) . '">
      <input type="hidden" name="cms_action" value="save_redirects">
      <section class="cms-group" data-repeater>
        <h2>URL redirects</h2>
        <p class="cms-help">Send old site paths to a new page. The three product-category URLs from the SEO tracker should 301 to Andaal Home Foods. From is a path on this site (example: <code>/product-category/honey/</code>). To is the full https destination.</p>
        <input type="hidden" name="redirects[_ok]" value="1">
        <div data-repeater-list>' . $list . '</div>
        <template data-repeater-template>' . cfc_admin_redirect_row(['from' => '', 'to' => '', 'code' => 301], '__i__') . '</template>
        <button class="cms-add" type="button" data-repeater-add>Add redirect</button>
      </section>
      <div class="cms-actions">
        <button class="cms-btn" type="submit">Save redirects</button>
        <button class="cms-btn cms-btn--ghost" type="submit" name="cms_action" value="reset_redirects" onclick="return confirm(\'Restore the three default product-category redirects? This replaces the current list.\')">Restore defaults</button>
      </div>
    </form>';
    cfc_admin_layout('Redirects', $html, 'redirects');
}

function cfc_admin_captcha(): never
{
    $site = (string) cfc_config('turnstile_site_key', '');
    $hasSecret = trim((string) cfc_config('turnstile_secret', '')) !== '';
    $status = cfc_turnstile_enabled()
        ? '<p class="cms-help">Captcha is <strong>on</strong>. Franchise, contact, and landing forms will show Cloudflare Turnstile.</p>'
        : '<p class="cms-help">Captcha is <strong>off</strong> until both keys are saved. Local forms keep working without a widget.</p>';
    $html = '<form method="post" action="' . cfc_e(cfc_admin_url('p=captcha')) . '">
      <input type="hidden" name="cfc_csrf" value="' . cfc_e(cfc_csrf_token()) . '">
      <input type="hidden" name="cms_action" value="save_captcha">
      <section class="cms-group">
        <h2>Cloudflare Turnstile</h2>
        ' . $status . '
        <p class="cms-help">Create a widget at <a href="https://dash.cloudflare.com/" target="_blank" rel="noopener">Cloudflare Dashboard → Turnstile</a>. Add your live domain, then paste the keys here. Keys are stored in MySQL table <code>cfc_settings</code>, not in JSON. Leave both empty on local so forms work without a widget.</p>
        <div class="cms-field"><label class="cap">Site key</label><input type="text" name="turnstile_site_key" value="' . cfc_e($site) . '" autocomplete="off" maxlength="200" placeholder="0x4AAAAAAA..."></div>
        <div class="cms-field"><label class="cap">Secret key</label><input type="password" name="turnstile_secret" value="" autocomplete="new-password" maxlength="200" placeholder="' . cfc_e($hasSecret ? 'Saved — type a new key to replace' : 'Secret key') . '">
          <p class="cms-help">' . ($hasSecret ? 'A secret key is already saved. Leave this blank to keep it.' : 'The secret key is never shown after save.') . '</p>
        </div>
        <div class="cms-field"><label><input type="checkbox" name="turnstile_disable" value="1"> Disable captcha and remove saved keys</label></div>
      </section>
      <div class="cms-actions">
        <button class="cms-btn" type="submit">Save captcha</button>
      </div>
    </form>';
    cfc_admin_layout('Captcha', $html, 'captcha');
}

function cfc_admin_submissions(): never
{
    $rows = '';
    foreach (cfc_submissions_list() as $row) {
        $rows .= '<tr>';
        foreach (['created_at', 'name', 'email', 'mobile', 'city', 'source', 'message'] as $key) {
            $rows .= '<td>' . cfc_e((string) ($row[$key] ?? '')) . '</td>';
        }
        $rows .= '</tr>';
    }
    if ($rows === '') {
        $rows = '<tr><td colspan="7">No submissions yet.</td></tr>';
    }
    $note = cfc_db_ready()
        ? '<p class="cms-help">Stored in MySQL table <code>cfc_submissions</code> (JSON backup in data/submissions).</p>'
        : '<p class="cms-help">Stored as JSON in data/submissions. Set db_name / db_user / db_pass in config.local.php to use MySQL.</p>';
    $html = $note . '<table class="cms-table"><thead><tr><th>When</th><th>Name</th><th>Email</th><th>Mobile</th><th>City</th><th>Source</th><th>Message</th></tr></thead><tbody>' . $rows . '</tbody></table>';
    cfc_admin_layout('Form submissions', $html, 'submissions');
}

function cfc_admin_users(): never
{
    $me = cfc_admin_me();
    $rows = '';
    foreach (cfc_users() as $user) {
        $id = (string) ($user['id'] ?? '');
        $self = $me && ($me['id'] ?? '') === $id;
        $rows .= '<article class="cms-user">
            <div>
                <h2>' . cfc_e((string) ($user['name'] ?? $user['username'] ?? '')) . ($self ? ' <span class="cms-pill">You</span>' : '') . '</h2>
                <p class="cms-post__meta">
                    <span>@' . cfc_e((string) ($user['username'] ?? '')) . '</span>
                    <span class="cms-pill">' . cfc_e((string) ($user['role'] ?? 'editor')) . '</span>
                </p>
            </div>
            <a class="cms-post__edit" href="' . cfc_e(cfc_admin_url('p=users&id=' . rawurlencode($id))) . '">Edit</a>
        </article>';
    }
    $html = '<div class="cms-toolbar">
        <p class="cms-count" style="margin:0">' . count(cfc_users()) . ' accounts</p>
        <a class="cms-btn" href="' . cfc_e(cfc_admin_url('p=users&new=1')) . '">New user</a>
      </div>
      <div class="cms-posts">' . $rows . '</div>';
    cfc_admin_layout('Users', $html, 'users');
}

function cfc_admin_user_editor(?string $id): never
{
    $user = $id ? cfc_user_find($id) : null;
    if ($id && $user === null) {
        cfc_admin_flash(['err', 'User not found.']);
        cfc_redirect('admin/?p=users');
    }
    $isNew = $user === null;
    $user = $user ?? [];
    $action = $isNew ? 'new=1' : 'id=' . rawurlencode((string) ($user['id'] ?? $id));
    $html = '<form method="post" action="' . cfc_e(cfc_admin_url('p=users&' . $action)) . '">
      <input type="hidden" name="cfc_csrf" value="' . cfc_e(cfc_csrf_token()) . '">
      <input type="hidden" name="cms_action" value="save_user">
      <section class="cms-group"><h2>' . ($isNew ? 'New user' : 'Edit user') . '</h2>
        <div class="cms-field"><label class="cap">Name</label><input type="text" name="name" value="' . cfc_e((string) ($user['name'] ?? '')) . '" required></div>
        <div class="cms-field"><label class="cap">Username</label><input type="text" name="username" value="' . cfc_e((string) ($user['username'] ?? '')) . '" autocomplete="off" required></div>
        <div class="cms-field"><label class="cap">Role</label><select name="role">
            <option value="admin"' . ((($user['role'] ?? '') === 'admin') ? ' selected' : '') . '>Admin — manage users and all content</option>
            <option value="editor"' . ((($user['role'] ?? 'editor') === 'editor') ? ' selected' : '') . '>Editor — edit content only</option>
        </select></div>
        <div class="cms-field"><label class="cap">' . ($isNew ? 'Password' : 'New password (leave blank to keep)') . '</label>
          <input type="password" name="password" autocomplete="new-password"' . ($isNew ? ' required minlength="12"' : ' minlength="12"') . '>
          <p class="cms-help">At least 12 characters. Stored as a one-way hash in MySQL, never in JSON.</p>
        </div>
      </section>
      <div class="cms-actions">
        <button class="cms-btn" type="submit">Save user</button>
        <a class="cms-btn cms-btn--ghost" href="' . cfc_e(cfc_admin_url('p=users')) . '">Back to list</a>';
    if (!$isNew && !empty($user['id'])) {
        $html .= '<button class="cms-btn cms-btn--danger" type="submit" name="cms_action" value="delete_user" onclick="return confirm(\'Delete this user?\')">Delete</button>';
    }
    $html .= '</div></form>';
    cfc_admin_layout($isNew ? 'New user' : 'Edit user', $html, 'users');
}

function cfc_admin_login_page(string $error = ''): never
{
    $token = cfc_e(cfc_csrf_token());
    $err = $error !== '' ? '<p class="err">' . cfc_e($error) . '</p>' : '';
    $css = cfc_e(cfc_url('admin/admin.css') . '?v=' . filemtime(CFC_ROOT . '/admin/admin.css'));
    $favicon = cfc_e(cfc_cms_src('site.favicon'));
    $touch = cfc_e(cfc_media('2024/03/cropped-Fev-180x180.png'));
    cfc_send(200, '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>CFC CMS</title>
<link rel="icon" href="' . $favicon . '" sizes="32x32">
<link rel="apple-touch-icon" href="' . $touch . '">
<link rel="stylesheet" href="' . $css . '"></head>
<body class="cms-login">
<form method="post" action="' . cfc_e(cfc_url('admin/')) . '">
<h1>CFC CMS</h1>
<p class="sub">Edit pages, images, and form inbox</p>
' . $err . '
<input type="hidden" name="cfc_csrf" value="' . $token . '">
<label>Username</label><input name="username" autocomplete="username" required>
<label>Password</label><input type="password" name="password" autocomplete="current-password" required>
<button type="submit">Sign in</button>
</form></body></html>');
}

function cfc_admin_setup_page(string $error = ''): never
{
    $token = cfc_e(cfc_csrf_token());
    $err = $error !== '' ? '<p class="err">' . cfc_e($error) . '</p>' : '';
    $css = cfc_e(cfc_url('admin/admin.css') . '?v=' . filemtime(CFC_ROOT . '/admin/admin.css'));
    $favicon = cfc_e(cfc_cms_src('site.favicon'));
    $touch = cfc_e(cfc_media('2024/03/cropped-Fev-180x180.png'));
    $needKey = !cfc_debug();
    $keyReady = cfc_setup_key_configured();
    if (!cfc_db_ready()) {
        $hint = '<p class="sub">MySQL is required. Create a database, import sql/cfc.sql, and set db_name / db_user / db_pass in config.local.php.</p>';
        $needKey = true;
        $keyReady = false;
    } else {
        $hint = $needKey
            ? ($keyReady
                ? '<p class="sub">No admin accounts on this server yet. Use the setup key from config.local.php. Passwords are stored hashed in MySQL.</p>'
                : '<p class="sub">Copy config/config.local.php.example to config/config.local.php and set a random <strong>setup_key</strong> (16+ characters), then refresh.</p>')
            : '<p class="sub">Create the CMS admin account. The password is hashed in MySQL, not stored in JSON.</p>';
    }
    $keyField = '';
    if ($needKey) {
        $keyField = '<label>Setup key</label><input type="password" name="setup_key" autocomplete="off" required minlength="16"'
            . ($keyReady ? '' : ' disabled') . '>';
    }
    $disabled = $needKey && !$keyReady ? ' disabled' : '';
    cfc_send(200, '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Create admin · CFC CMS</title>
<link rel="icon" href="' . $favicon . '" sizes="32x32">
<link rel="apple-touch-icon" href="' . $touch . '">
<link rel="stylesheet" href="' . $css . '"></head>
<body class="cms-login">
<form class="cms-login__wide" method="post" action="' . cfc_e(cfc_url('admin/')) . '">
<h1>Create admin</h1>
' . $hint . $err . '
<input type="hidden" name="cfc_csrf" value="' . $token . '">
<input type="hidden" name="cfc_setup" value="1">
' . $keyField . '
<label>Username</label><input name="username" autocomplete="username" required minlength="3" maxlength="32" pattern="[A-Za-z0-9._-]+"' . $disabled . '>
<label>Display name</label><input name="name" autocomplete="name"' . $disabled . '>
<label>Password</label><input type="password" name="password" autocomplete="new-password" required minlength="12"' . $disabled . '>
<p class="sub">Password must be at least 12 characters.</p>
<button type="submit"' . $disabled . '>Create admin</button>
</form></body></html>');
}

function cfc_admin_server(): never
{
    $r = cfc_runtime_report();
    $row = static function (string $label, string $value, bool $ok = true): string {
        $cls = $ok ? '' : ' class="is-bad"';
        return '<tr' . $cls . '><th>' . cfc_e($label) . '</th><td>' . cfc_e($value) . '</td></tr>';
    };
    $html = '';
    if ($r['issues'] !== []) {
        $html .= '<div class="cms-flash cms-flash--err">' . cfc_e(implode(' ', $r['issues'])) . '</div>';
    } else {
        $html .= '<div class="cms-flash cms-flash--ok">Server looks ready for production.</div>';
    }
    $html .= '<section class="cms-group"><h2>Runtime</h2><table class="cms-table"><tbody>';
    $html .= $row('PHP', (string) $r['php'], (bool) $r['php_ok']);
    $html .= $row('SAPI', (string) $r['sapi']);
    $html .= $row('HTTPS', !empty($r['https']) ? 'yes' : 'no', cfc_debug() || !empty($r['https']));
    $html .= $row('Debug', !empty($r['debug']) ? 'on' : 'off', empty($r['debug']));
    $html .= $row('Host', (string) $r['host']);
    $html .= $row('Base path', (string) $r['base_path'] === '' ? '(domain root)' : (string) $r['base_path']);
    $html .= $row('Public origin', (string) $r['public_origin'] !== '' ? (string) $r['public_origin'] : '(auto)');
    $html .= $row('config.local.php', !empty($r['local_config']) ? 'present' : 'missing', cfc_debug() || !empty($r['local_config']));
    $html .= $row('.htaccess', !empty($r['htaccess']) ? 'present' : 'missing', !empty($r['htaccess']));
    $html .= $row('CMS users', (string) $r['users'], (int) $r['users'] > 0);
    $html .= $row(
        'Mail',
        !empty($r['mail_enabled'])
            ? (!empty($r['smtp_ready']) ? 'PHPMailer · Gmail SMTP' : 'on, SMTP not configured')
            : 'off (inbox only)',
        empty($r['mail_enabled']) || !empty($r['smtp_ready'])
    );
    $html .= $row(
        'MySQL',
        !empty($r['db_ready'])
            ? 'connected · ' . (int) $r['db_posts'] . ' posts · ' . (int) $r['db_submissions'] . ' forms'
            : (!empty($r['db_configured']) ? 'configured, not connected' : 'not configured'),
        !empty($r['db_ready']) || cfc_debug()
    );
    $html .= '</tbody></table></section>';
    if (!empty($r['db_tables']) && is_array($r['db_tables'])) {
        $html .= '<section class="cms-group"><h2>Database tables</h2><table class="cms-table"><tbody>';
        foreach ($r['db_tables'] as $table => $count) {
            $html .= $row((string) $table, (string) (int) $count . ' rows', true);
        }
        $html .= '</tbody></table></section>';
    }

    $html .= '<section class="cms-group"><h2>Writable folders</h2><table class="cms-table"><tbody>';
    foreach ($r['writable'] as $label => $ok) {
        $html .= $row((string) $label, $ok ? 'writable' : 'not writable', (bool) $ok);
    }
    $html .= '</tbody></table></section>';

    $html .= '<section class="cms-group"><h2>PHP extensions</h2><table class="cms-table"><tbody>';
    $required = ['json', 'fileinfo', 'session', 'filter'];
    if (!empty($r['db_configured'])) {
        $required[] = 'pdo_mysql';
    }
    foreach ($r['extensions'] as $ext => $ok) {
        $html .= $row((string) $ext, $ok ? 'loaded' : 'missing', (bool) $ok || !in_array($ext, $required, true));
    }
    $html .= '</tbody></table></section>';
    $html .= '<p class="cms-help">cPanel: MultiPHP Manager → PHP 8.1+. File Manager permissions 755 for folders, 644 for files. <code>data/</code> and <code>assets/uploads/cms/</code> must stay writable.</p>';
    cfc_admin_layout('Server', $html, 'server');
}
