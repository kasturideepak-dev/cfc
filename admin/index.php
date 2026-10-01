<?php
declare(strict_types=1);

if (!defined('CFC_ROOT')) {
    require dirname(__DIR__) . '/includes/bootstrap.php';
}

require_once CFC_ROOT . '/includes/cms-admin.php';

if (isset($_GET['logout'])) {
    unset($_SESSION['cfc_admin'], $_SESSION['cfc_admin_user'], $_SESSION['cfc_admin_name'], $_SESSION['cfc_admin_role'], $_SESSION['cfc_admin_seen']);
    cfc_redirect('admin/');
}

if (cfc_users_reload() === []) {
    $error = '';
    if (!cfc_db_ready()) {
        $error = 'MySQL is required for CMS logins. Set db_name, db_user and db_pass in '
            . CFC_ROOT . '/config/config.local.php'
            . ' — not in config.php, which is replaced on every deploy.';
        cfc_admin_setup_page($error);
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['cfc_setup'])) {
        $fails = (int) ($_SESSION['cfc_admin_fails'] ?? 0);
        $failAt = (int) ($_SESSION['cfc_admin_fail_at'] ?? 0);
        if ($fails >= 8 && (time() - $failAt) < 600) {
            $error = 'Too many attempts. Try again in a few minutes.';
        } elseif (!cfc_admin_login_rate_ok()) {
            $error = 'Too many attempts. Try again later.';
        } elseif (!cfc_csrf_verify((string) ($_POST['cfc_csrf'] ?? ''))) {
            $error = 'Please refresh and try again.';
        } elseif (!cfc_debug() && !cfc_setup_key_configured()) {
            $error = 'Set setup_key in config/config.local.php (16+ random characters), then refresh.';
        } elseif (!cfc_debug() && !cfc_setup_key_verify((string) ($_POST['setup_key'] ?? ''))) {
            $_SESSION['cfc_admin_fails'] = $fails + 1;
            $_SESSION['cfc_admin_fail_at'] = time();
            $error = 'Setup key does not match config.local.php.';
        } else {
            [$ok, $msg] = cfc_users_create_first(
                (string) ($_POST['username'] ?? ''),
                (string) ($_POST['password'] ?? ''),
                (string) ($_POST['name'] ?? '')
            );
            if ($ok) {
                $account = cfc_user_find(trim((string) ($_POST['username'] ?? '')));
                if ($account) {
                    session_regenerate_id(true);
                    $_SESSION['cfc_admin'] = (string) $account['id'];
                    $_SESSION['cfc_admin_user'] = (string) $account['username'];
                    $_SESSION['cfc_admin_name'] = (string) ($account['name'] ?? $account['username']);
                    $_SESSION['cfc_admin_role'] = 'admin';
                    $_SESSION['cfc_admin_seen'] = time();
                    unset($_SESSION['cfc_admin_fails'], $_SESSION['cfc_admin_fail_at']);
                    cfc_admin_login_rate_clear();
                    cfc_redirect('admin/');
                }
            }
            $_SESSION['cfc_admin_fails'] = $fails + 1;
            $_SESSION['cfc_admin_fail_at'] = time();
            $error = $msg !== '' ? $msg : 'Could not create the admin account.';
        }
    }
    cfc_admin_setup_page($error);
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['username']) && !isset($_POST['cms_action']) && !isset($_POST['cfc_setup'])) {
    $fails = (int) ($_SESSION['cfc_admin_fails'] ?? 0);
    $failAt = (int) ($_SESSION['cfc_admin_fail_at'] ?? 0);
    if ($fails >= 8 && (time() - $failAt) < 600) {
        $error = 'Too many sign-in attempts. Try again in a few minutes.';
    } elseif (!cfc_admin_login_rate_ok()) {
        // Keyed to the IP, so clearing cookies does not hand out a fresh budget.
        $error = 'Too many sign-in attempts. Try again later.';
    } else {
        if ($fails >= 8) {
            $fails = 0;
        }
        $account = null;
        if (cfc_csrf_verify((string) ($_POST['cfc_csrf'] ?? ''))) {
            $account = cfc_user_auth((string) $_POST['username'], (string) ($_POST['password'] ?? ''));
        }
        if ($account) {
            session_regenerate_id(true);
            $_SESSION['cfc_admin'] = (string) $account['id'];
            $_SESSION['cfc_admin_user'] = (string) $account['username'];
            $_SESSION['cfc_admin_name'] = (string) ($account['name'] ?? $account['username']);
            $_SESSION['cfc_admin_role'] = (string) ($account['role'] ?? 'editor');
            $_SESSION['cfc_admin_seen'] = time();
            unset($_SESSION['cfc_admin_fails'], $_SESSION['cfc_admin_fail_at']);
            cfc_admin_login_rate_clear();
            cfc_redirect('admin/');
        }
        $_SESSION['cfc_admin_fails'] = $fails + 1;
        $_SESSION['cfc_admin_fail_at'] = time();
        $error = 'Invalid credentials.';
    }
}

if (empty($_SESSION['cfc_admin'])) {
    cfc_admin_login_page($error);
}
if (cfc_admin_me() === null) {
    unset($_SESSION['cfc_admin'], $_SESSION['cfc_admin_user'], $_SESSION['cfc_admin_name'], $_SESSION['cfc_admin_role'], $_SESSION['cfc_admin_seen']);
    cfc_admin_login_page($error !== '' ? $error : 'Please sign in again.');
}

$idle = max(0, (int) cfc_config('admin_idle_seconds', 14400));
$last = (int) ($_SESSION['cfc_admin_seen'] ?? 0);
if ($idle > 0 && $last > 0 && (time() - $last) > $idle) {
    unset($_SESSION['cfc_admin'], $_SESSION['cfc_admin_user'], $_SESSION['cfc_admin_name'], $_SESSION['cfc_admin_role'], $_SESSION['cfc_admin_seen']);
    cfc_admin_login_page('Signed out after inactivity. Please sign in again.');
}
$_SESSION['cfc_admin_seen'] = time();

if ($_SESSION['cfc_admin'] === true) {
    $legacy = cfc_users()[0] ?? null;
    if ($legacy) {
        $_SESSION['cfc_admin'] = (string) $legacy['id'];
        $_SESSION['cfc_admin_user'] = (string) $legacy['username'];
        $_SESSION['cfc_admin_name'] = (string) ($legacy['name'] ?? $legacy['username']);
        $_SESSION['cfc_admin_role'] = (string) ($legacy['role'] ?? 'admin');
    } else {
        unset($_SESSION['cfc_admin']);
        cfc_admin_login_page('Please sign in again.');
    }
}

$page = (string) ($_GET['p'] ?? '');
$action = (string) ($_POST['cms_action'] ?? '');

// One image per request, answered as JSON, so the bulk uploader is never capped
// by max_file_uploads or post_max_size. Must run before the redirecting handlers.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $action === 'upload_gallery_image') {
    if (!cfc_csrf_verify((string) ($_POST['cfc_csrf'] ?? ''))) {
        cfc_json(403, ['ok' => false, 'error' => 'Your session expired. Reload the page and sign in again.']);
    }
    @set_time_limit(120);
    $file = $_FILES['file'] ?? [];
    $result = cfc_cms_gallery_add_one(
        is_array($file) ? $file : [],
        (int) ($_POST['section'] ?? -1),
        trim(cfc_cms_post_str($_POST['section_title'] ?? ''))
    );
    cfc_json(!empty($result['ok']) ? 200 : 400, $result);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $action !== '') {
    if (!cfc_csrf_verify((string) ($_POST['cfc_csrf'] ?? ''))) {
        cfc_admin_flash(['err', 'Please refresh and try again.']);
        cfc_redirect('admin/' . ($page !== '' ? '?p=' . rawurlencode($page) : ''));
    }

    if ($action === 'save_page' && $page !== '' && cfc_cms_page_schema($page)) {
        $ok = cfc_cms_save_page($page, $_POST, $_FILES);
        cfc_admin_flash(cfc_admin_save_flash($ok, 'Saved.', 'Could not save. Check folder permissions on data/cms.'));
        cfc_redirect('admin/?p=' . rawurlencode($page));
    }
    if ($action === 'reset_page' && $page !== '' && cfc_cms_page_schema($page)) {
        $ok = cfc_cms_reset_page($page);
        cfc_admin_flash($ok ? ['ok', 'Reset to original copy.'] : ['err', 'Could not reset.']);
        cfc_redirect('admin/?p=' . rawurlencode($page));
    }
    if ($action === 'save_gallery') {
        $ok = cfc_cms_save_gallery($_POST, $_FILES);
        cfc_admin_flash(cfc_admin_save_flash($ok, 'Gallery saved.', 'Could not save gallery.'));
        cfc_redirect('admin/?p=gallery-images');
    }
    if ($action === 'save_media') {
        $ok = cfc_cms_save_media_hub($_POST, $_FILES);
        cfc_admin_flash(cfc_admin_save_flash($ok, 'Media Hub saved.', 'Could not save Media Hub.'));
        cfc_redirect('admin/?p=media-images');
    }
    if ($action === 'save_instagram' || $action === 'refresh_instagram' || $action === 'disconnect_instagram') {
        if (!cfc_admin_is_admin()) {
            cfc_admin_flash(['err', 'Only admins can manage the Instagram feed.']);
            cfc_redirect('admin/');
        }
        if ($action === 'save_instagram') {
            $result = cfc_instagram_admin_save($_POST);
            cfc_admin_flash([$result['ok'] ? 'ok' : 'err', $result['message']]);
        } elseif ($action === 'disconnect_instagram') {
            $ok = cfc_instagram_disconnect();
            cfc_admin_flash($ok ? ['ok', 'Instagram disconnected. Media Hub will use the manual tiles.'] : ['err', 'Could not disconnect Instagram.']);
        } else {
            if (!cfc_instagram_admin_refresh_allowed()) {
                cfc_admin_flash(['err', 'Wait a few seconds before refreshing again.']);
            } else {
                $result = cfc_instagram_sync(true);
                cfc_admin_flash([$result['ok'] ? 'ok' : 'err', $result['message']]);
            }
        }
        cfc_redirect('admin/?p=instagram');
    }
    if ($action === 'save_permalink') {
        $ok = cfc_blog_set_permalink((string) ($_POST['permalink'] ?? ''));
        cfc_admin_flash($ok
            ? ['ok', 'Post URLs updated. The old addresses now redirect to the new ones.']
            : ['err', 'Could not save the permalink structure. MySQL is required for this setting.']);
        cfc_redirect('admin/?p=blog-posts');
    }
    if ($action === 'save_blog') {
        $existing = isset($_GET['edit']) ? (string) $_GET['edit'] : null;
        $ok = cfc_cms_save_blog_post($_POST, $_FILES, $existing);
        cfc_admin_flash(cfc_admin_save_flash($ok, 'Post saved.', 'Could not save post.'));
        cfc_redirect('admin/?p=blog-posts');
    }
    if ($action === 'delete_blog' && isset($_GET['edit'])) {
        $ok = cfc_cms_delete_blog_post((string) $_GET['edit']);
        cfc_admin_flash($ok ? ['ok', 'Post removed from listing.'] : ['err', 'Could not delete post.']);
        cfc_redirect('admin/?p=blog-posts');
    }
    if ($action === 'save_redirects') {
        $ok = cfc_redirects_save($_POST);
        cfc_admin_flash($ok ? ['ok', 'Redirects saved.'] : ['err', 'Could not save redirects. Check folder permissions on data/cms.']);
        cfc_redirect('admin/?p=redirects');
    }
    if ($action === 'reset_redirects') {
        $ok = cfc_redirects_reset();
        cfc_admin_flash($ok ? ['ok', 'Default product-category redirects restored.'] : ['err', 'Could not restore redirects.']);
        cfc_redirect('admin/?p=redirects');
    }
    if ($action === 'save_captcha') {
        if (!cfc_admin_is_admin()) {
            cfc_admin_flash(['err', 'Only admins can change captcha keys.']);
            cfc_redirect('admin/');
        }
        $ok = cfc_secrets_save($_POST);
        cfc_admin_flash($ok ? ['ok', 'Captcha keys saved.'] : ['err', 'Could not save keys. Check folder permissions on data/cms.']);
        cfc_redirect('admin/?p=captcha');
    }
    if ($action === 'save_user' || $action === 'delete_user') {
        if (!cfc_admin_is_admin()) {
            cfc_admin_flash(['err', 'Only admins can manage users.']);
            cfc_redirect('admin/');
        }
        if ($action === 'save_user') {
            $id = isset($_GET['id']) ? (string) $_GET['id'] : null;
            [$ok, $msg] = cfc_user_save($_POST, $id);
            cfc_admin_flash([$ok ? 'ok' : 'err', $msg]);
            cfc_redirect('admin/?p=users');
        }
        if ($action === 'delete_user' && isset($_GET['id'])) {
            [$ok, $msg] = cfc_user_delete((string) $_GET['id']);
            cfc_admin_flash([$ok ? 'ok' : 'err', $msg]);
            cfc_redirect('admin/?p=users');
        }
    }
}

if ($page === '' || $page === 'dash') {
    cfc_admin_dashboard();
}
if ($page === 'gallery-images') {
    cfc_admin_gallery();
}
if ($page === 'media-images') {
    cfc_admin_media_hub();
}
if ($page === 'blog-posts') {
    if (isset($_GET['new'])) {
        cfc_admin_blog_editor(null, true);
    }
    if (isset($_GET['edit'])) {
        cfc_admin_blog_editor((string) $_GET['edit'], false);
    }
    cfc_admin_blog_list();
}
if ($page === 'redirects') {
    cfc_admin_redirects();
}
if ($page === 'instagram') {
    if (!cfc_admin_is_admin()) {
        cfc_admin_flash(['err', 'Only admins can manage the Instagram feed.']);
        cfc_redirect('admin/');
    }
    cfc_instagram_admin_page();
}
if ($page === 'captcha') {
    if (!cfc_admin_is_admin()) {
        cfc_admin_flash(['err', 'Only admins can change captcha keys.']);
        cfc_redirect('admin/');
    }
    cfc_admin_captcha();
}
if ($page === 'submissions') {
    cfc_admin_submissions();
}
if ($page === 'server') {
    if (!cfc_admin_is_admin()) {
        cfc_admin_flash(['err', 'Only admins can view server status.']);
        cfc_redirect('admin/');
    }
    cfc_admin_server();
}
if ($page === 'users') {
    if (!cfc_admin_is_admin()) {
        cfc_admin_flash(['err', 'Only admins can manage users.']);
        cfc_redirect('admin/');
    }
    if (isset($_GET['new'])) {
        cfc_admin_user_editor(null);
    }
    if (isset($_GET['id'])) {
        cfc_admin_user_editor((string) $_GET['id']);
    }
    cfc_admin_users();
}
if (cfc_cms_page_schema($page)) {
    cfc_admin_page_editor($page);
}

cfc_admin_dashboard();
