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
    $month = cfc_submissions_count_since(date('Y-m-01'));
    $cards .= '<a class="cms-card" href="' . cfc_e(cfc_admin_url('p=submissions')) . '"><strong>Form submissions</strong><span>'
        . (int) $n . ' received'
        . ($n > 0 ? ' &middot; ' . (int) $month . ' in ' . date('F') : '')
        . '</span></a>';
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
    // The site never shows that the live feed has frozen, so say it here, where
    // whoever can actually fix it lands. Only admins can reconnect the account.
    if (cfc_admin_is_admin()) {
        $ig = cfc_instagram_health();
        if ($ig['level'] === 'err' || $ig['level'] === 'warn') {
            $intro .= '<div class="cms-flash ' . ($ig['level'] === 'err' ? 'cms-flash--err' : 'cms-flash--warn') . '">'
                . cfc_e($ig['message'])
                . ' <a href="' . cfc_e(cfc_admin_url('p=instagram')) . '">Open Instagram Feed</a></div>';
        }
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
        // Snippet fields exist to hold <script> tags, so a web application
        // firewall inspecting them always sees what looks like an attack and
        // rejects the save. admin.js base64-encodes just these on submit and
        // cfc_cms_save_page() decodes them, which keeps every other field,
        // and the whole public site, inspected as before.
        $mark = $type === 'code' ? ' data-cms-code' : '';
        return '<div class="cms-field"><label class="cap">' . $label . '</label><textarea class="' . $cls . '" name="' . cfc_e($name) . '"' . $wys . $ph . $mark . '>' . cfc_e($value) . '</textarea></div>';
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

function cfc_admin_bytes(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }
    $num = (int) $value;
    return match (strtolower(substr($value, -1))) {
        'g' => $num * 1024 * 1024 * 1024,
        'm' => $num * 1024 * 1024,
        'k' => $num * 1024,
        default => $num,
    };
}

/**
 * Largest single file the one-file-per-request uploader can push through, which
 * is whichever of upload_max_filesize, post_max_size (less form overhead) and
 * the CMS's own 16 MB ceiling is smallest.
 */
function cfc_admin_upload_limit_bytes(): int
{
    $post = cfc_admin_bytes((string) ini_get('post_max_size'));
    $limits = [16 * 1024 * 1024];
    $upload = cfc_admin_bytes((string) ini_get('upload_max_filesize'));
    if ($upload > 0) {
        $limits[] = $upload;
    }
    if ($post > 262144) {
        $limits[] = $post - 262144;
    }
    return (int) min($limits);
}

function cfc_admin_gallery_tile(string $ref, array $img, int $n): string
{
    $src = cfc_media((string) ($img['thumb'] ?? $img['full'] ?? ''));
    return '<div class="cms-gitem" data-img data-ref="' . cfc_e($ref) . '" draggable="true">'
        . '<img src="' . cfc_e($src) . '" alt="" loading="lazy" draggable="false">'
        . '<div class="cms-gitem__row">'
        . '<label class="cms-pick"><input type="checkbox" data-img-pick><span>Select</span></label>'
        . '<span class="cms-gitem__n" data-img-n>' . $n . '</span>'
        . '</div>'
        . '<div class="cms-gitem__acts">'
        . '<button type="button" class="cms-mini" data-img-step="-1" title="Move earlier" aria-label="Move earlier">&#8592;</button>'
        . '<button type="button" class="cms-mini" data-img-step="1" title="Move later" aria-label="Move later">&#8594;</button>'
        . '<button type="button" class="cms-mini cms-mini--danger" data-img-del title="Remove photo" aria-label="Remove photo">&#10005;</button>'
        . '</div></div>';
}

function cfc_admin_gallery_category(string $key, array $section, int $index, string $order): string
{
    $title = (string) ($section['title'] ?? '');
    $level = (($section['level'] ?? 'h2') === 'h1') ? 'h1' : 'h2';
    $count = count($section['images'] ?? []);
    $k = cfc_e($key);

    $html = '<section class="cms-group cms-cat" data-cat data-cat-key="' . $k . '">';
    $html .= '<div class="cms-cat__bar">';
    $html .= '<div class="cms-field cms-cat__name"><label class="cap">Category name</label>'
        . '<input type="text" name="sections[' . $k . '][title]" value="' . cfc_e($title) . '" placeholder="e.g. Highway Outlets" data-cat-title></div>';
    $html .= '<div class="cms-field cms-cat__lvl"><label class="cap">Heading level</label><select name="sections[' . $k . '][level]">'
        . '<option value="h1"' . ($level === 'h1' ? ' selected' : '') . '>H1</option>'
        . '<option value="h2"' . ($level === 'h2' ? ' selected' : '') . '>H2</option>'
        . '</select></div>';
    $html .= '<div class="cms-cat__tools">'
        . '<span class="cms-cat__count" data-cat-count>' . $count . ' photo' . ($count === 1 ? '' : 's') . '</span>'
        . '<button type="button" class="cms-mini" data-cat-step="-1" title="Move category up" aria-label="Move category up">&#8593;</button>'
        . '<button type="button" class="cms-mini" data-cat-step="1" title="Move category down" aria-label="Move category down">&#8595;</button>'
        . '<button type="button" class="cms-mini cms-mini--danger" data-cat-del>Delete category</button>'
        . '</div>';
    $html .= '</div>';
    $html .= '<input type="hidden" name="sections[' . $k . '][order]" value="' . cfc_e($order) . '" data-cat-order>';
    $html .= '<div class="cms-ggrid" data-cat-grid>';
    foreach ($section['images'] ?? [] as $ii => $img) {
        if (!is_array($img)) {
            continue;
        }
        $html .= cfc_admin_gallery_tile($index . ':' . (int) $ii, $img, (int) $ii + 1);
    }
    $html .= '</div>';
    $html .= '<p class="cms-ggrid__empty" data-cat-empty' . ($count > 0 ? ' hidden' : '') . '>No photos yet. Upload some above, or drag photos in from another category.</p>';
    $html .= '<details class="cms-cat__fallback"><summary>Add photos without JavaScript</summary>'
        . '<div class="cms-field"><input type="file" name="add[' . $k . '][]" accept="image/jpeg,image/png,image/webp,image/gif" multiple></div>'
        . '<p class="cms-help">Fallback for when the uploader above cannot run. These are attached to the next save, so only a few files will get through at a time.</p></details>';
    $html .= '</section>';
    return $html;
}

function cfc_admin_gallery(): never
{
    $sections = cfc_cms_gallery();
    $total = 0;
    foreach ($sections as $section) {
        $total += count($section['images'] ?? []);
    }
    $maxBytes = cfc_admin_upload_limit_bytes();
    $maxMb = max(1, (int) floor($maxBytes / 1048576));

    $html = '<form method="post" action="' . cfc_e(cfc_admin_url('p=gallery-images')) . '" enctype="multipart/form-data" data-gallery data-max-bytes="' . $maxBytes . '">';
    $html .= '<input type="hidden" name="cfc_csrf" value="' . cfc_e(cfc_csrf_token()) . '">';
    $html .= '<input type="hidden" name="cms_action" value="save_gallery">';

    $html .= '<section class="cms-group"><h2>Add photos in bulk</h2>';
    $html .= '<p class="cms-help">Pick as many images as you like. They are sent one at a time, so the usual cap on how many files one form can carry does not apply. Each photo is resized to 1920px wide, converted to WebP and given a small thumbnail, so the gallery stays fast. JPG, PNG, WebP or GIF, up to ' . $maxMb . ' MB each.</p>';
    if ($sections === []) {
        $html .= '<p class="cms-help"><strong>Add a category below and save it first</strong>, then come back here to upload.</p>';
    } else {
        $html .= '<div class="cms-upload">';
        $html .= '<div class="cms-field"><label class="cap">Put them in this category</label><select data-up-target>';
        foreach ($sections as $si => $section) {
            $label = trim((string) ($section['title'] ?? ''));
            if ($label === '') {
                $label = 'Category ' . ((int) $si + 1);
            }
            $html .= '<option value="' . (int) $si . '" data-title="' . cfc_e(trim((string) ($section['title'] ?? ''))) . '">' . cfc_e($label) . '</option>';
        }
        $html .= '</select></div>';
        $html .= '<div class="cms-field"><label class="cap">Choose images</label>'
            . '<input type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple data-up-input></div>';
        $html .= '<div class="cms-upload__go">'
            . '<button class="cms-btn" type="button" data-up-start disabled>Upload</button> '
            . '<button class="cms-btn cms-btn--ghost" type="button" data-up-stop hidden>Stop</button></div>';
        $html .= '<div class="cms-up" data-up-panel hidden>'
            . '<div class="cms-up__bar"><span data-up-fill></span></div>'
            . '<p class="cms-up__stat" data-up-stat></p>'
            . '<ul class="cms-up__log" data-up-log></ul>'
            . '<button class="cms-btn cms-btn--ghost" type="button" data-up-reload hidden>Reload to arrange them</button>'
            . '</div>';
        $html .= '</div>';
    }
    $html .= '</section>';

    $html .= '<section class="cms-group"><h2>Categories &amp; order</h2>';
    $html .= '<p class="cms-help">Drag a photo to move it inside its category or across to another one, or use the arrows. Tick photos to move or remove several at once. The order here is the order on the public gallery page. Nothing is applied until you press <strong>Save gallery</strong>.</p>';
    $html .= '<p class="cms-help" data-total>' . $total . ' photo' . ($total === 1 ? '' : 's') . ' in ' . count($sections) . ' categor' . (count($sections) === 1 ? 'y' : 'ies') . '.</p>';
    $html .= '</section>';

    $html .= '<div data-cat-list>';
    foreach ($sections as $si => $section) {
        $refs = [];
        foreach (array_keys($section['images'] ?? []) as $ii) {
            $refs[] = (int) $si . ':' . (int) $ii;
        }
        $html .= cfc_admin_gallery_category((string) (int) $si, $section, (int) $si, implode(',', $refs));
    }
    $html .= '</div>';

    $html .= '<div class="cms-actions">'
        . '<button class="cms-btn" type="submit">Save gallery</button> '
        . '<button class="cms-btn cms-btn--ghost" type="button" data-cat-add>Add category</button> '
        . '<a class="cms-btn cms-btn--ghost" href="' . cfc_e(cfc_url('gallery/')) . '" target="_blank" rel="noopener">Preview</a>'
        . '<span class="cms-dirty" data-dirty hidden>Unsaved changes</span>'
        . '</div>';
    $html .= '</form>';

    $html .= '<div class="cms-bulk" data-bulk hidden>'
        . '<strong data-bulk-n>0 selected</strong>'
        . '<select data-bulk-cat aria-label="Category to move the selected photos to"></select>'
        . '<button type="button" class="cms-btn" data-bulk-move>Move here</button>'
        . '<button type="button" class="cms-btn cms-btn--ghost" data-bulk-del>Remove</button>'
        . '<button type="button" class="cms-btn cms-btn--ghost" data-bulk-clear>Clear</button>'
        . '</div>';

    $html .= '<template data-cat-template>'
        . cfc_admin_gallery_category('__k__', ['title' => '', 'level' => 'h2', 'images' => []], 0, '')
        . '</template>';

    cfc_admin_layout('Gallery photos', $html, 'gallery-images');
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

/**
 * Permalink picker. Switching rewrites nothing: the stored path stays the
 * storage key and only the public URL changes, so this is reversible. Old URLs
 * keep working because every structure stays routable and redirects to the one
 * in force, which is also why the warning below says "redirected" and not
 * "broken".
 */
function cfc_admin_permalink_form(int $postCount): string
{
    $current = cfc_blog_permalink();
    $sample = cfc_blog_index()[0] ?? null;

    // Preview each structure against a real post where there is one, so the
    // editor sees their own URL rather than a made-up example.
    $options = '';
    foreach (cfc_blog_permalinks() as $key => $meta) {
        $example = $sample === null ? $meta['example'] : '/' . cfc_blog_post_url_as($sample, $key);
        $options .= '<option value="' . cfc_e($key) . '"' . ($key === $current ? ' selected' : '') . '>'
            . cfc_e($meta['label']) . ' — ' . cfc_e($example) . '</option>';
    }

    $label = cfc_blog_permalinks()[$current]['label'] ?? $current;
    $html = '<details class="cms-group cms-permalink"><summary><strong>Post URLs</strong> — currently '
        . cfc_e($label) . '</summary>';
    $html .= '<form method="post" action="' . cfc_e(cfc_admin_url('p=blog-posts')) . '">';
    $html .= '<input type="hidden" name="cfc_csrf" value="' . cfc_e(cfc_csrf_token()) . '">';
    $html .= '<input type="hidden" name="cms_action" value="save_permalink">';
    $html .= '<div class="cms-field"><label class="cap" for="cfc-permalink">Permalink structure</label>'
        . '<select id="cfc-permalink" name="permalink">' . $options . '</select></div>';
    $html .= '<p class="cms-help">Changes the address of all ' . $postCount . ' posts. Nothing is lost: '
        . 'the old addresses keep working and redirect to the new ones, and you can switch back at any time. '
        . 'Search engines will take a while to catch up, so pick one and leave it.</p>';
    if ($current !== 'name') {
        $html .= '<p class="cms-help"><strong>Post name only</strong> puts posts at the top level, so a post '
            . 'sharing a slug with a page (for example <code>menu</code>) would be unreachable. The other three are safe.</p>';
    }
    $html .= '<div class="cms-actions"><button class="cms-btn" type="submit">Save post URLs</button></div>';
    $html .= '</form></details>';
    return $html;
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
      </div>'
      . cfc_admin_permalink_form(count($all))
      . '<div class="cms-filters">' . $filters . '</div>
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
        $html .= '<a class="cms-btn cms-btn--ghost" href="' . cfc_e(cfc_url(cfc_blog_post_url($post))) . '" target="_blank" rel="noopener">View</a>';
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

/** The columns an export carries, in order. Message last: it is the long one. */
function cfc_submissions_columns(): array
{
    return [
        'created_at' => 'When',
        'form_id' => 'Form',
        'name' => 'Name',
        'email' => 'Email',
        'mobile' => 'Mobile',
        'city' => 'City',
        'source' => 'Page',
        'message' => 'Message',
    ];
}

/** The filters currently in force, read from the query string. */
function cfc_submissions_filters(): array
{
    $norm = static function (string $v): string {
        $v = trim($v);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : '';
    };
    return [
        'form' => preg_replace('/[^a-z0-9_-]/i', '', (string) ($_GET['form'] ?? '')) ?: '',
        'from' => $norm((string) ($_GET['from'] ?? '')),
        'to' => $norm((string) ($_GET['to'] ?? '')),
        'q' => trim((string) ($_GET['q'] ?? '')),
    ];
}

/**
 * One filter for the table and the exports alike, so what you download is
 * always what you were looking at.
 */
function cfc_submissions_apply_filters(array $rows, array $f): array
{
    $needle = $f['q'] !== '' ? cfc_lower($f['q']) : '';
    $out = [];
    foreach ($rows as $row) {
        if ($f['form'] !== '' && (string) ($row['form_id'] ?? '') !== $f['form']) {
            continue;
        }
        if ($f['from'] !== '' || $f['to'] !== '') {
            $stamp = strtotime((string) ($row['created_at'] ?? ''));
            $day = $stamp === false ? '' : date('Y-m-d', $stamp);
            if ($day === '') {
                continue;
            }
            if ($f['from'] !== '' && $day < $f['from']) {
                continue;
            }
            if ($f['to'] !== '' && $day > $f['to']) {
                continue;
            }
        }
        if ($needle !== '') {
            $hay = cfc_lower(implode(' ', [
                $row['name'] ?? '', $row['email'] ?? '', $row['mobile'] ?? '',
                $row['city'] ?? '', $row['message'] ?? '', $row['source'] ?? '',
            ]));
            if (!str_contains($hay, $needle)) {
                continue;
            }
        }
        $out[] = $row;
    }
    return $out;
}

function cfc_submissions_export_rows(array $rows): array
{
    $cols = cfc_submissions_columns();
    $out = [array_values($cols)];
    foreach ($rows as $row) {
        $line = [];
        foreach (array_keys($cols) as $key) {
            $v = (string) ($row[$key] ?? '');
            if ($key === 'created_at') {
                $stamp = strtotime($v);
                $v = $stamp === false ? $v : date('Y-m-d H:i', $stamp);
            }
            $line[] = str_replace(["\r\n", "\r"], "\n", $v);
        }
        $out[] = $line;
    }
    return $out;
}

function cfc_submissions_filename(string $ext, int $count): string
{
    return 'cfc-enquiries-' . date('Y-m-d') . '-' . $count . '.' . $ext;
}

/** Escape for XML, and drop the control characters XML 1.0 forbids outright. */
function cfc_xlsx_text(string $v): string
{
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $v) ?? '';
    if (mb_strlen($v, 'UTF-8') > 32000) {
        $v = mb_substr($v, 0, 32000, 'UTF-8') . '...';
    }
    return htmlspecialchars($v, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

/** A1, B1 ... Z1, AA1. */
function cfc_xlsx_ref(int $col, int $row): string
{
    $name = '';
    for ($n = $col; $n > 0; $n = intdiv($n - 1, 26)) {
        $name = chr(65 + (($n - 1) % 26)) . $name;
    }
    return $name . $row;
}

/**
 * A genuine .xlsx, not a CSV wearing the extension. Every cell is written as an
 * inline string, which is what keeps a ten-digit mobile number readable: as a
 * number Excel would round it or show it as 9.87E+09, and a leading +91 would
 * be parsed as a formula.
 */
function cfc_xlsx_build(array $grid): string
{
    $sheet = '';
    $rowNo = 0;
    foreach ($grid as $cells) {
        $rowNo++;
        $style = $rowNo === 1 ? ' s="1"' : ' s="2"';
        $sheet .= '<row r="' . $rowNo . '">';
        $colNo = 0;
        foreach ($cells as $cell) {
            $colNo++;
            $sheet .= '<c r="' . cfc_xlsx_ref($colNo, $rowNo) . '" t="inlineStr"' . $style
                . '><is><t xml:space="preserve">' . cfc_xlsx_text((string) $cell) . '</t></is></c>';
        }
        $sheet .= '</row>';
    }
    $cols = count($grid[0] ?? []);
    $last = cfc_xlsx_ref(max(1, $cols), max(1, $rowNo));
    $widths = '';
    foreach ([16, 11, 20, 28, 15, 16, 20, 70] as $i => $w) {
        if ($i >= $cols) {
            break;
        }
        $widths .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
    }

    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    $rel = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    $parts = [
        '[Content_Types].xml' => $xml
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>',
        '_rels/.rels' => $xml
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="' . $rel . '/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>',
        'xl/workbook.xml' => $xml
            . '<workbook xmlns="' . $ns . '" xmlns:r="' . $rel . '">'
            . '<sheets><sheet name="Enquiries" sheetId="1" r:id="rId1"/></sheets></workbook>',
        'xl/_rels/workbook.xml.rels' => $xml
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="' . $rel . '/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="' . $rel . '/styles" Target="styles.xml"/>'
            . '</Relationships>',
        'xl/styles.xml' => $xml
            . '<styleSheet xmlns="' . $ns . '">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border/></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1">'
            . '<alignment vertical="top" wrapText="1"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>',
        'xl/worksheets/sheet1.xml' => $xml
            . '<worksheet xmlns="' . $ns . '" xmlns:r="' . $rel . '">'
            . '<dimension ref="A1:' . $last . '"/>'
            . '<sheetViews><sheetView workbookViewId="0">'
            . '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
            . '</sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . ($widths !== '' ? '<cols>' . $widths . '</cols>' : '')
            . '<sheetData>' . $sheet . '</sheetData>'
            . '<autoFilter ref="A1:' . $last . '"/>'
            . '</worksheet>',
    ];

    $tmp = tempnam(sys_get_temp_dir(), 'cfcxlsx');
    if ($tmp === false) {
        return '';
    }
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
        @unlink($tmp);
        return '';
    }
    foreach ($parts as $name => $body) {
        $zip->addFromString($name, $body);
    }
    $zip->close();
    $blob = (string) file_get_contents($tmp);
    @unlink($tmp);
    return $blob;
}

/**
 * Spreadsheet formula injection guard. These enquiries come from a public form,
 * so a message beginning =, +, - or @ is a live formula the moment the client
 * double-clicks the CSV. The apostrophe is visible in the cell; that is the
 * cost of not shipping the client a file that can run commands. The .xlsx
 * download needs no such guard, which is why it is the one offered first.
 */
function cfc_csv_safe(string $v): string
{
    return $v !== '' && strpbrk($v[0], "=+-@\t\r") !== false ? "'" . $v : $v;
}

function cfc_submissions_send_csv(array $grid, string $filename): never
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store, private');
    // Excel reads a CSV as the system codepage unless the BOM tells it UTF-8,
    // which is what keeps names and city spellings from arriving as mojibake.
    echo "\xEF\xBB\xBF";
    foreach ($grid as $i => $row) {
        $cells = [];
        foreach ($i === 0 ? $row : array_map('cfc_csv_safe', $row) as $v) {
            // Quoting every field unconditionally is valid CSV and sidesteps
            // the delimiter, newline and backslash-escape edge cases that
            // fputcsv has historically got wrong.
            $cells[] = '"' . str_replace('"', '""', (string) $v) . '"';
        }
        echo implode(',', $cells), "\r\n";
    }
    exit;
}

function cfc_submissions_send_xlsx(array $grid, string $filename): never
{
    $blob = cfc_xlsx_build($grid);
    if ($blob === '') {
        cfc_admin_flash(['err', 'Could not build the Excel file. Download the CSV instead.']);
        cfc_redirect('admin/?p=submissions');
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($blob));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store, private');
    echo $blob;
    exit;
}

/**
 * Sends one fixed message to a typed address. This exists because the only
 * other thing that sends mail is a real enquiry, so the alternative way to
 * check an SMTP password was to post the live form -- which mails the client
 * and leaves a fake enquiry in the list that nothing in the CMS can delete.
 *
 * Deliberately bypasses mail_enabled: that switch governs enquiry
 * notifications, and you want to be able to prove the credentials work before
 * turning it on. The caller says so in the result when it is still off.
 */
function cfc_admin_send_test_mail(array $post): array
{
    $to = trim(cfc_cms_post_str($post['test_to'] ?? ''));
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Enter a valid email address to send the test to.'];
    }
    // Behind an admin login already, but a compromised account should not turn
    // this into a way to post mail from the client's domain all afternoon.
    if (!cfc_rate_limit_hit('testmail:' . cfc_client_ip(), 5, 900)) {
        return [false, 'Too many test emails. Wait a few minutes and try again.'];
    }
    if (!cfc_smtp_ready()) {
        return [false, 'SMTP is not configured: set smtp_user and smtp_pass (a Gmail App Password) in config/config.local.php.'];
    }

    $who = (string) ($_SESSION['cfc_admin_user'] ?? 'someone');
    $host = (string) cfc_config('smtp_host', 'smtp.gmail.com');
    $when = date('j M Y, g:i a');
    $body = "This is a test message from the Chennapatnam Filter Coffee CMS.\n\n"
        . "If you are reading it, the SMTP settings on the site are working and\n"
        . "enquiry emails can be delivered.\n\n"
        . "Sent: {$when}\n"
        . "Sent by: {$who}\n"
        . "Server: {$host}\n";

    cfc_mail_error('');
    $ok = cfc_send_mail($to, 'CFC CMS test email', $body);
    if (!$ok) {
        $err = cfc_mail_error();
        return [false, 'Test email failed: ' . ($err !== '' ? $err : 'reason not reported by the mail server') . '.'];
    }

    $msg = 'Test email sent to ' . $to . ' from ' . cfc_mail_from() . ' via ' . $host
        . '. Check the inbox, and the spam folder if it is not there.';
    if (empty(cfc_config('mail_enabled'))) {
        $msg .= ' Note that mail_enabled is still false, so real enquiries are NOT being emailed yet.';
    }
    return [true, $msg];
}

/** The test-email control, shown under the status banner. Admins only. */
function cfc_admin_mail_test_form(): string
{
    if (!cfc_admin_is_admin()) {
        return '';
    }
    $to = cfc_mail_recipients();
    $hint = $to === []
        ? 'Send a test to any address to check the SMTP settings.'
        : 'Enquiries go to ' . cfc_e(implode(', ', $to)) . '. Send a test anywhere to check the SMTP settings without posting the live form.';
    return '<form class="cms-mailtest" method="post" action="' . cfc_e(cfc_admin_url('p=submissions')) . '">'
        . '<input type="hidden" name="cfc_csrf" value="' . cfc_e(cfc_csrf_token()) . '">'
        . '<input type="hidden" name="cms_action" value="send_test_mail">'
        . '<label>Send a test email to<input type="email" name="test_to" required placeholder="you@example.com"'
        . ' value="' . cfc_e(cfc_admin_email_guess()) . '"></label>'
        . '<button type="submit" class="cms-btn cms-btn--ghost">Send test</button>'
        . '<span class="cms-help">' . $hint . '</span>'
        . '</form>';
}

/** Prefill the test box with the signed-in admin's own address, if we hold one. */
function cfc_admin_email_guess(): string
{
    $me = cfc_admin_me();
    $email = is_array($me) ? trim((string) ($me['email'] ?? '')) : '';
    return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
}

/**
 * Whether enquiry emails are actually going out. A failed send is deliberately
 * not allowed to fail the submission, so without this the first sign that
 * notifications stopped would be someone noticing they had missed an enquiry.
 */
function cfc_admin_mail_status(): string
{
    $to = cfc_mail_recipients();
    if (empty(cfc_config('mail_enabled'))) {
        return '<div class="cms-flash cms-flash--warn">Enquiry emails are off. Submissions are still saved and listed below. '
            . 'To turn them on, set <code>mail_enabled</code> to true and <code>smtp_user</code> / <code>smtp_pass</code> in '
            . cfc_e(CFC_ROOT . '/config/config.local.php') . ' — the password must be a Gmail App Password, not the account password.</div>';
    }
    if ($to === []) {
        return '<div class="cms-flash cms-flash--err">Enquiry emails are on but <code>mail_to</code> has no valid address, so nothing is being sent.</div>';
    }
    $list = implode(', ', $to);
    if (!cfc_db_ready()) {
        return '<div class="cms-flash">Enquiry emails go to ' . cfc_e($list) . '.</div>';
    }
    $at = (string) (cfc_setting_get('form_mail_last_at') ?? '');
    $ok = (string) (cfc_setting_get('form_mail_last_ok') ?? '');
    $err = (string) (cfc_setting_get('form_mail_last_error') ?? '');
    if ($at === '') {
        return '<div class="cms-flash">Enquiry emails go to ' . cfc_e($list) . '. Nothing sent yet.</div>';
    }
    if ($ok === '1') {
        return '<div class="cms-flash cms-flash--ok">Enquiry emails go to ' . cfc_e($list) . '. Last one sent ' . cfc_e($at) . '.</div>';
    }
    return '<div class="cms-flash cms-flash--err">The last enquiry email failed on ' . cfc_e($at) . ': '
        . cfc_e($err !== '' ? $err : 'reason not recorded') . '. The enquiry itself was saved and is listed below.</div>';
}

function cfc_admin_submissions(): never
{
    $all = cfc_submissions_list();
    $filters = cfc_submissions_filters();
    $rows = cfc_submissions_apply_filters($all, $filters);
    $active = $filters['form'] !== '' || $filters['from'] !== '' || $filters['to'] !== '' || $filters['q'] !== '';

    // The export sits inside the page handler on purpose: it is behind exactly
    // the same sign-in as the table, and it can only ever hand back the rows
    // the filters already selected.
    $export = (string) ($_GET['export'] ?? '');
    if ($export === 'csv' || $export === 'xlsx') {
        $grid = cfc_submissions_export_rows($rows);
        $name = cfc_submissions_filename($export, count($rows));
        if ($export === 'xlsx') {
            cfc_submissions_send_xlsx($grid, $name);
        }
        cfc_submissions_send_csv($grid, $name);
    }

    // Counts come from the unfiltered set, so the tallies stay a fixed point of
    // reference while you narrow things down.
    $byForm = [];
    foreach ($all as $row) {
        $id = (string) ($row['form_id'] ?? '');
        if ($id === '') {
            $id = 'contact';
        }
        $byForm[$id] = ($byForm[$id] ?? 0) + 1;
    }
    ksort($byForm);

    $label = static fn (string $id): string => ucwords(str_replace(['-', '_'], ' ', $id));

    $options = '<option value="">All forms (' . count($all) . ')</option>';
    foreach ($byForm as $id => $n) {
        $options .= '<option value="' . cfc_e($id) . '"' . ($filters['form'] === $id ? ' selected' : '') . '>'
            . cfc_e($label($id)) . ' (' . $n . ')</option>';
    }

    $filterForm = '<form class="cms-filters" method="get" action="' . cfc_e(cfc_admin_url()) . '">'
        . '<input type="hidden" name="p" value="submissions">'
        . '<label>Form<select name="form">' . $options . '</select></label>'
        . '<label>From<input type="date" name="from" value="' . cfc_e($filters['from']) . '"></label>'
        . '<label>To<input type="date" name="to" value="' . cfc_e($filters['to']) . '"></label>'
        . '<label>Search<input type="search" name="q" value="' . cfc_e($filters['q'])
        . '" placeholder="Name, email, mobile, city, message"></label>'
        . '<span class="cms-filters__go"><button type="submit" class="cms-btn">Filter</button>'
        . ($active ? ' <a class="cms-btn cms-btn--ghost" href="' . cfc_e(cfc_admin_url('p=submissions')) . '">Clear</a>' : '')
        . '</span></form>';

    $tally = [];
    foreach ($byForm as $id => $n) {
        $tally[] = cfc_e($label($id)) . ' ' . $n;
    }
    $count = '<p class="cms-count"><strong>' . count($rows) . '</strong> '
        . (count($rows) === 1 ? 'enquiry' : 'enquiries')
        . ($active ? ' matching, out of ' . count($all) . ' in total' : ' in total')
        . ($tally !== [] ? ' <span class="cms-count__split">' . implode(' &middot; ', $tally) . '</span>' : '')
        . '</p>';

    $query = array_filter([
        'p' => 'submissions',
        'form' => $filters['form'],
        'from' => $filters['from'],
        'to' => $filters['to'],
        'q' => $filters['q'],
    ], static fn ($v): bool => $v !== '');
    $base = http_build_query($query);
    $downloads = $rows === []
        ? '<p class="cms-help">Nothing to export with these filters.</p>'
        : '<p class="cms-downloads">'
            . '<a class="cms-btn" href="' . cfc_e(cfc_admin_url($base . '&export=xlsx')) . '">Download Excel (.xlsx)</a> '
            . '<a class="cms-btn cms-btn--ghost" href="' . cfc_e(cfc_admin_url($base . '&export=csv')) . '">Download CSV</a>'
            . '<span class="cms-help">' . count($rows) . ' '
            . (count($rows) === 1 ? 'row' : 'rows')
            . ($active ? ', matching the filters above' : '')
            . '. Excel keeps mobile numbers exactly as typed; CSV is for Google Sheets and other tools.</span></p>';

    // The table is capped so the page stays usable as the enquiries pile up.
    // The downloads above are never capped.
    $cap = 500;
    $shown = count($rows) > $cap ? array_slice($rows, 0, $cap) : $rows;
    $body = '';
    foreach ($shown as $row) {
        $stamp = strtotime((string) ($row['created_at'] ?? ''));
        $when = $stamp === false
            ? '<span>' . cfc_e((string) ($row['created_at'] ?? '')) . '</span>'
            : '<span>' . cfc_e(date('j M Y', $stamp)) . '</span><time>' . cfc_e(date('g:i a', $stamp)) . '</time>';
        $body .= '<tr><td class="cms-when">' . $when . '</td>'
            . '<td>' . cfc_e($label((string) ($row['form_id'] ?? ''))) . '</td>';
        foreach (['name', 'email', 'mobile', 'city', 'source', 'message'] as $key) {
            $cell = cfc_e((string) ($row[$key] ?? ''));
            $body .= $key === 'message' ? '<td class="cms-msg">' . $cell . '</td>' : '<td>' . $cell . '</td>';
        }
        $body .= '</tr>';
    }
    if ($body === '') {
        $body = '<tr><td colspan="8">' . ($all === [] ? 'No submissions yet.' : 'No enquiries match these filters.') . '</td></tr>';
    }
    $more = count($rows) > $cap
        ? '<p class="cms-help">Showing the ' . $cap . ' most recent of ' . count($rows)
            . '. Narrow the dates, or download the file for the full set.</p>'
        : '';

    $note = cfc_db_ready()
        ? '<p class="cms-help">Stored in MySQL table <code>cfc_submissions</code> (JSON backup in data/submissions).</p>'
        : '<p class="cms-help">Stored as JSON in data/submissions. Set db_name / db_user / db_pass in config.local.php to use MySQL.</p>';

    $html = cfc_admin_mail_status() . cfc_admin_mail_test_form() . $filterForm . $count . $downloads . $note . $more
        . '<div class="cms-scroll"><table class="cms-table cms-table--subs">'
        . '<thead><tr><th>When</th><th>Form</th><th>Name</th><th>Email</th>'
        . '<th>Mobile</th><th>City</th><th>Page</th><th>Message</th></tr></thead><tbody>'
        . $body . '</tbody></table></div>';
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
        $hint = '<p class="sub">MySQL is required. Import sql/cfc.sql, then set db_name / db_user / db_pass in <code>'
            . cfc_e(CFC_ROOT . '/config/config.local.php') . '</code>. Not in config.php: that file is replaced on every deploy.</p>';
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
