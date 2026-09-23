<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

function cfc_instagram_admin_save(array $post): array
{
    if (!cfc_db_ready()) {
        return ['ok' => false, 'message' => 'MySQL is required to save Instagram settings.'];
    }
    cfc_instagram_ensure_schema();
    $mode = (string) ($post['mode'] ?? 'instagram_login');
    $mode = $mode === 'facebook_login' ? 'facebook_login' : 'instagram_login';
    $appId = preg_replace('/\D+/', '', (string) ($post['app_id'] ?? '')) ?? '';
    $userId = preg_replace('/\D+/', '', (string) ($post['user_id'] ?? '')) ?? '';
    $secret = trim((string) ($post['app_secret'] ?? ''));
    $token = trim((string) ($post['access_token'] ?? ''));
    if ($secret === '') {
        $secret = cfc_instagram_setting('app_secret');
    }
    if ($token === '') {
        $token = cfc_instagram_setting('access_token');
    }
    $ok = cfc_instagram_save_settings([
        'enabled' => empty($post['enabled']) ? '0' : '1',
        'mode' => $mode,
        'app_id' => cfc_clip($appId, 32),
        'app_secret' => cfc_clip($secret, 512),
        'user_id' => cfc_clip($userId, 32),
        'limit' => (string) max(1, min(24, (int) ($post['limit'] ?? 12))),
        'cache_minutes' => (string) max(15, min(1440, (int) ($post['cache_minutes'] ?? 60))),
        'graph_version' => preg_match('/^v\d+\.\d+$/', trim((string) ($post['graph_version'] ?? ''))) ? trim((string) $post['graph_version']) : 'v25.0',
    ]);
    if (!$ok) {
        return ['ok' => false, 'message' => 'Could not save Instagram settings.'];
    }
    if ($token !== cfc_instagram_setting('access_token') && $token !== '') {
        $stored = cfc_instagram_store_token($token, true);
        if (!$stored['ok']) {
            return ['ok' => false, 'message' => $stored['error']];
        }
    } elseif ($token !== '') {
        cfc_instagram_save_settings(['access_token' => $token]);
    }
    return ['ok' => true, 'message' => 'Instagram settings saved.'];
}

function cfc_instagram_admin_refresh_allowed(): bool
{
    $last = (int) ($_SESSION['cfc_ig_refresh_at'] ?? 0);
    if ($last > 0 && (time() - $last) < 20) {
        return false;
    }
    $_SESSION['cfc_ig_refresh_at'] = time();
    return true;
}

function cfc_instagram_handle_oauth(): void
{
    if (isset($_GET['error'])) {
        $desc = trim(str_replace('+', ' ', (string) ($_GET['error_description'] ?? $_GET['error'] ?? 'Authorization cancelled')));
        cfc_admin_flash(['err', cfc_instagram_redact($desc)]);
        cfc_redirect('admin/?p=instagram');
    }
    $state = (string) ($_GET['state'] ?? '');
    $expect = (string) ($_SESSION['cfc_ig_oauth_state'] ?? '');
    unset($_SESSION['cfc_ig_oauth_state']);
    if ($expect === '' || $state === '' || !hash_equals($expect, $state)) {
        cfc_admin_flash(['err', 'Instagram login state did not match. Try Connect again.']);
        cfc_redirect('admin/?p=instagram');
    }
    $code = rtrim((string) ($_GET['code'] ?? ''), '#_');
    $result = cfc_instagram_exchange_code($code);
    if (!$result['ok']) {
        cfc_admin_flash(['err', $result['error']]);
        cfc_redirect('admin/?p=instagram');
    }
    cfc_instagram_save_settings(['enabled' => '1']);
    $sync = cfc_instagram_sync(true);
    $msg = 'Instagram connected' . (!empty($result['username']) ? ' as @' . $result['username'] : '') . '.';
    if ($sync['ok']) {
        $msg .= ' ' . $sync['message'];
    }
    cfc_admin_flash(['ok', $msg]);
    cfc_redirect('admin/?p=instagram');
}

function cfc_instagram_admin_page(): never
{
    if (isset($_GET['ig_oauth'])) {
        cfc_instagram_handle_oauth();
    }
    $s = cfc_instagram_settings();
    $connected = cfc_instagram_has_token();
    $count = cfc_instagram_feed_count();
    $oauthUrl = cfc_instagram_oauth_authorize_url();
    $redirect = cfc_instagram_oauth_redirect_uri();
    $expires = $s['token_expires_at'];
    $expiresLabel = '—';
    if ($expires !== '') {
        $unix = strtotime($expires);
        $expiresLabel = $unix === false ? $expires : date('j M Y, g:ia', $unix);
        if ($unix !== false && $unix < time()) {
            $expiresLabel .= ' (expired)';
        }
    }
    $status = '';
    if ($s['enabled'] !== '1') {
        $status = '<div class="cms-flash">Feed is disabled. The Media Hub will keep using the manually saved tiles.</div>';
    } elseif (!$connected) {
        $status = '<div class="cms-flash cms-flash--err">No access token yet. Connect Instagram or paste a long-lived token from the Meta App Dashboard.</div>';
    } elseif ($s['last_sync_ok'] === '0' && $s['last_error'] !== '') {
        $status = '<div class="cms-flash cms-flash--err">Last sync failed: ' . cfc_e($s['last_error']) . ($s['last_error_code'] !== '' ? ' (code ' . cfc_e($s['last_error_code']) . ')' : '') . '</div>';
    } elseif ($s['last_sync_ok'] === '1') {
        $status = '<div class="cms-flash cms-flash--ok">Last successful sync: ' . cfc_e($s['last_sync_at'] !== '' ? $s['last_sync_at'] : 'unknown') . '. Cached posts: ' . (int) $count . '.</div>';
    }

    $preview = '';
    foreach (cfc_instagram_feed_posts(6) as $post) {
        $src = $post['file'] !== '' ? cfc_media($post['file']) : $post['remote'];
        if ($src === '') {
            continue;
        }
        $preview .= '<a class="cms-gitem" href="' . cfc_e($post['href']) . '" target="_blank" rel="noopener"><img src="' . cfc_e($src) . '" alt="' . cfc_e($post['alt']) . '"></a>';
    }

    $html = $status;
    $html .= '<section class="cms-group"><h2>How this connects</h2>
        <p class="cms-help">Uses the official <strong>Instagram API with Instagram Login</strong> (host <code>graph.instagram.com</code>, Graph <code>v25.0</code>). Instagram Basic Display is deprecated. The account must be a <strong>Professional</strong> account (Business or Creator). A Facebook Page is <strong>not</strong> required for Instagram Login. Tokens stay in MySQL <code>cfc_settings</code> and are never printed in page HTML. Full setup: see <code>docs/instagram.md</code>.</p>
        <p class="cms-help">OAuth redirect URI to paste in the Meta App Dashboard:<br><code>' . cfc_e($redirect) . '</code></p>
      </section>';

    $html .= '<form method="post" action="' . cfc_e(cfc_admin_url('p=instagram')) . '">
      <input type="hidden" name="cfc_csrf" value="' . cfc_e(cfc_csrf_token()) . '">
      <input type="hidden" name="cms_action" value="save_instagram">
      <section class="cms-group">
        <h2>Feed settings</h2>
        <div class="cms-field"><label><input type="checkbox" name="enabled" value="1"' . ($s['enabled'] === '1' ? ' checked' : '') . '> Enable live Instagram feed on Media Hub</label></div>
        <div class="cms-field"><label class="cap">API setup</label>
          <select name="mode">
            <option value="instagram_login"' . ($s['mode'] !== 'facebook_login' ? ' selected' : '') . '>Instagram API with Instagram Login (recommended, no Facebook Page)</option>
            <option value="facebook_login"' . ($s['mode'] === 'facebook_login' ? ' selected' : '') . '>Instagram API with Facebook Login (Page-linked professional account)</option>
          </select>
        </div>
        <div class="cms-field"><label class="cap">Posts to display</label><input type="number" name="limit" min="1" max="24" value="' . cfc_e($s['limit']) . '"></div>
        <div class="cms-field"><label class="cap">Cache duration (minutes)</label><input type="number" name="cache_minutes" min="15" max="1440" value="' . cfc_e($s['cache_minutes']) . '">
          <p class="cms-help">Cron skips Instagram if the last successful sync is newer than this. Default 60. Public pages never call the API.</p>
        </div>
        <div class="cms-field"><label class="cap">Graph version</label><input type="text" name="graph_version" value="' . cfc_e($s['graph_version']) . '" maxlength="12"></div>
      </section>
      <section class="cms-group">
        <h2>Credentials</h2>
        <p class="cms-help">App ID and App Secret come from Meta App Dashboard → Instagram → API setup with Instagram login. The access token is never shown after save.</p>
        <div class="cms-field"><label class="cap">Instagram App ID</label><input type="text" name="app_id" value="' . cfc_e($s['app_id']) . '" inputmode="numeric" autocomplete="off" maxlength="32"></div>
        <div class="cms-field"><label class="cap">Instagram App Secret</label><input type="password" name="app_secret" value="" autocomplete="new-password" maxlength="200" placeholder="' . cfc_e($s['app_secret'] !== '' ? 'Saved — type a new secret to replace' : 'App secret') . '">
          <p class="cms-help">' . ($s['app_secret'] !== '' ? 'A secret is already saved.' : 'Required to exchange OAuth codes and refresh 60-day tokens.') . '</p>
        </div>
        <div class="cms-field"><label class="cap">Instagram professional account ID</label><input type="text" name="user_id" value="' . cfc_e($s['user_id']) . '" inputmode="numeric" maxlength="32">
          <p class="cms-help">Filled automatically after Connect. Current username: <strong>@' . cfc_e($s['username'] !== '' ? $s['username'] : 'not connected') . '</strong></p>
        </div>
        <div class="cms-field"><label class="cap">Long-lived access token</label><input type="password" name="access_token" value="" autocomplete="new-password" maxlength="512" placeholder="' . cfc_e($connected ? 'Saved — paste a new token to replace' : 'Paste token from App Dashboard → Generate token') . '">
          <p class="cms-help">' . ($connected ? 'Token on file: ' . cfc_e(cfc_instagram_mask_secret($s['access_token'])) . '. Expires ' . cfc_e($expiresLabel) . '.' : 'Either paste a dashboard token or use Connect with Instagram.') . '</p>
        </div>
      </section>
      <div class="cms-actions">
        <button class="cms-btn" type="submit">Save settings</button>';
    if ($oauthUrl !== '' && $s['mode'] !== 'facebook_login') {
        $html .= '<a class="cms-btn cms-btn--ghost" href="' . cfc_e($oauthUrl) . '">Connect with Instagram</a>';
    }
    $html .= '</div></form>';

    $html .= '<form method="post" action="' . cfc_e(cfc_admin_url('p=instagram')) . '" style="margin-top:18px">
      <input type="hidden" name="cfc_csrf" value="' . cfc_e(cfc_csrf_token()) . '">
      <section class="cms-group">
        <h2>Sync</h2>
        <p class="cms-help">Public visitors never hit Instagram. Cron refreshes the cache; this button forces a sync now. If Instagram is down, the last cached posts stay on the site.</p>
        <p class="cms-help">Cron (every 30 minutes):<br><code>*/30 * * * * /usr/bin/php ' . cfc_e(CFC_ROOT . '/cron/sync-instagram.php') . ' >/dev/null 2>&amp;1</code></p>
        <div class="cms-actions" style="margin-top:0">
          <button class="cms-btn" type="submit" name="cms_action" value="refresh_instagram">Refresh Instagram Feed</button>';
    if ($connected) {
        $html .= '<button class="cms-btn cms-btn--danger" type="submit" name="cms_action" value="disconnect_instagram" onclick="return confirm(\'Remove the stored Instagram token?\')">Disconnect</button>';
    }
    $html .= '<a class="cms-btn cms-btn--ghost" href="' . cfc_e(cfc_url('media-hub/')) . '" target="_blank" rel="noopener">Preview Media Hub</a>
        </div>
      </section>
    </form>';

    if ($preview !== '') {
        $html .= '<section class="cms-group"><h2>Cached posts</h2><div class="cms-ggrid">' . $preview . '</div></section>';
    }

    cfc_admin_layout('Instagram Feed', $html, 'instagram');
}
