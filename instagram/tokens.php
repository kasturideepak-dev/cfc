<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

function cfc_instagram_oauth_redirect_uri(): string
{
    return rtrim(cfc_abs_url('admin/'), '/') . '/?p=instagram&ig_oauth=1';
}

function cfc_instagram_oauth_authorize_url(): string
{
    $appId = cfc_instagram_setting('app_id');
    if ($appId === '' || !preg_match('/^[0-9]{5,32}$/', $appId)) {
        return '';
    }
    $state = bin2hex(random_bytes(16));
    $_SESSION['cfc_ig_oauth_state'] = $state;
    $params = [
        'client_id' => $appId,
        'redirect_uri' => cfc_instagram_oauth_redirect_uri(),
        'response_type' => 'code',
        'scope' => 'instagram_business_basic',
        'state' => $state,
        'enable_fb_login' => '0',
    ];
    return 'https://www.instagram.com/oauth/authorize?' . http_build_query($params);
}

function cfc_instagram_exchange_code(string $code): array
{
    $code = trim($code);
    $code = rtrim($code, '#_');
    $appId = cfc_instagram_setting('app_id');
    $secret = cfc_instagram_setting('app_secret');
    if ($code === '' || $appId === '' || $secret === '') {
        return ['ok' => false, 'error' => 'Missing Instagram App ID, App Secret, or authorization code.', 'code' => 'config'];
    }
    $res = cfc_instagram_api_request('POST', 'https://api.instagram.com/oauth/access_token', [
        'client_id' => $appId,
        'client_secret' => $secret,
        'grant_type' => 'authorization_code',
        'redirect_uri' => cfc_instagram_oauth_redirect_uri(),
        'code' => $code,
    ]);
    if (!$res['ok']) {
        $msg = $res['error'];
        if (is_array($res['json'])) {
            $msg = (string) ($res['json']['error_message'] ?? $res['json']['error_type'] ?? $msg);
        }
        return ['ok' => false, 'error' => cfc_instagram_redact($msg !== '' ? $msg : 'Could not exchange the Instagram code.'), 'code' => $res['code']];
    }
    $json = $res['json'] ?? [];
    $row = $json;
    if (isset($json['data'][0]) && is_array($json['data'][0])) {
        $row = $json['data'][0];
    }
    $token = trim((string) ($row['access_token'] ?? $json['access_token'] ?? ''));
    if ($token === '') {
        return ['ok' => false, 'error' => 'Instagram did not return an access token.', 'code' => 'no_token'];
    }
    return cfc_instagram_store_token($token, false);
}

function cfc_instagram_store_token(string $token, bool $alreadyLongLived): array
{
    $token = trim($token);
    if ($token === '' || strlen($token) > 512) {
        return ['ok' => false, 'error' => 'That access token is empty or too long.', 'code' => 'token'];
    }
    cfc_instagram_save_settings(['access_token' => $token]);
    if (!$alreadyLongLived) {
        $long = cfc_instagram_exchange_long_lived($token);
        if ($long['ok']) {
            $token = $long['token'];
        }
    }
    $expires = date('Y-m-d H:i:s', time() + 60 * 24 * 3600);
    cfc_instagram_save_settings([
        'access_token' => $token,
        'token_expires_at' => $expires,
    ]);
    $me = cfc_instagram_fetch_me();
    if (!$me['ok']) {
        return ['ok' => false, 'error' => $me['error'] !== '' ? $me['error'] : 'Token saved but the account could not be read. Check permissions.', 'code' => $me['code']];
    }
    cfc_instagram_save_settings([
        'user_id' => $me['user_id'],
        'username' => $me['username'],
        'last_error' => '',
        'last_error_code' => '',
    ]);
    return ['ok' => true, 'error' => '', 'code' => '', 'username' => $me['username']];
}

function cfc_instagram_exchange_long_lived(string $shortToken): array
{
    $secret = cfc_instagram_setting('app_secret');
    if ($secret === '') {
        return ['ok' => false, 'token' => $shortToken, 'error' => 'Instagram App Secret is required to create a 60-day token.'];
    }
    $url = 'https://graph.instagram.com/access_token?' . http_build_query([
        'grant_type' => 'ig_exchange_token',
        'client_secret' => $secret,
        'access_token' => $shortToken,
    ]);
    $res = cfc_instagram_api_request('GET', $url);
    $token = is_array($res['json']) ? trim((string) ($res['json']['access_token'] ?? '')) : '';
    $seconds = is_array($res['json']) ? (int) ($res['json']['expires_in'] ?? 0) : 0;
    if (!$res['ok'] || $token === '') {
        return ['ok' => false, 'token' => $shortToken, 'error' => $res['error']];
    }
    $expires = $seconds > 0 ? date('Y-m-d H:i:s', time() + $seconds) : date('Y-m-d H:i:s', time() + 60 * 24 * 3600);
    cfc_instagram_save_settings([
        'access_token' => $token,
        'token_expires_at' => $expires,
    ]);
    return ['ok' => true, 'token' => $token, 'error' => ''];
}

function cfc_instagram_refresh_long_lived(): array
{
    $token = cfc_instagram_setting('access_token');
    if ($token === '') {
        return ['ok' => false, 'error' => 'No Instagram access token is stored.', 'code' => 'token'];
    }
    if (cfc_instagram_setting('mode') === 'facebook_login') {
        return ['ok' => true, 'error' => '', 'code' => ''];
    }
    $url = 'https://graph.instagram.com/refresh_access_token?' . http_build_query([
        'grant_type' => 'ig_refresh_token',
        'access_token' => $token,
    ]);
    $res = cfc_instagram_api_request('GET', $url);
    $new = is_array($res['json']) ? trim((string) ($res['json']['access_token'] ?? '')) : '';
    $seconds = is_array($res['json']) ? (int) ($res['json']['expires_in'] ?? 0) : 0;
    if (!$res['ok'] || $new === '') {
        $kind = cfc_instagram_classify_error($res['code'], $res['error']);
        if ($kind === 'token') {
            cfc_instagram_set_status(false, 'Instagram access token expired or was revoked. Reconnect in CMS → Instagram Feed.', $res['code']);
        }
        return ['ok' => false, 'error' => $res['error'] !== '' ? $res['error'] : 'Could not refresh the Instagram token.', 'code' => $res['code']];
    }
    $expires = $seconds > 0 ? date('Y-m-d H:i:s', time() + $seconds) : date('Y-m-d H:i:s', time() + 60 * 24 * 3600);
    cfc_instagram_save_settings([
        'access_token' => $new,
        'token_expires_at' => $expires,
    ]);
    return ['ok' => true, 'error' => '', 'code' => ''];
}

function cfc_instagram_token_should_refresh(): bool
{
    if (cfc_instagram_setting('mode') === 'facebook_login') {
        return false;
    }
    $raw = cfc_instagram_setting('token_expires_at');
    if ($raw === '') {
        return true;
    }
    $unix = strtotime($raw);
    if ($unix === false) {
        return true;
    }
    return $unix - time() < 10 * 24 * 3600;
}

function cfc_instagram_disconnect(): bool
{
    return cfc_instagram_save_settings([
        'enabled' => '0',
        'access_token' => '',
        'token_expires_at' => '',
        'user_id' => '',
        'username' => '',
        'last_error' => '',
        'last_error_code' => '',
    ]);
}
