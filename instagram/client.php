<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

function cfc_instagram_graph_host(): string
{
    return cfc_instagram_setting('mode') === 'facebook_login'
        ? 'https://graph.facebook.com'
        : 'https://graph.instagram.com';
}

function cfc_instagram_graph_url(string $path, array $query = []): string
{
    $version = cfc_instagram_setting('graph_version', 'v25.0');
    $path = ltrim($path, '/');
    $url = rtrim(cfc_instagram_graph_host(), '/') . '/' . $version . '/' . $path;
    $token = cfc_instagram_setting('access_token');
    if ($token !== '' && !isset($query['access_token'])) {
        $query['access_token'] = $token;
    }
    return $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
}

function cfc_instagram_graph_get(string $path, array $query = [], int $timeout = 15): array
{
    return cfc_instagram_api_request('GET', cfc_instagram_graph_url($path, $query), [], $timeout);
}

function cfc_instagram_media_fields(): string
{
    return 'id,media_type,media_url,thumbnail_url,permalink,caption,timestamp,username,alt_text,children{id,media_type,media_url,thumbnail_url}';
}

/**
 * @return array{ok:bool,user_id:string,username:string,account_type:string,error:string,code:string}
 */
function cfc_instagram_fetch_me(): array
{
    $out = ['ok' => false, 'user_id' => '', 'username' => '', 'account_type' => '', 'error' => '', 'code' => ''];
    $mode = cfc_instagram_setting('mode');
    if ($mode === 'facebook_login') {
        $res = cfc_instagram_graph_get('me/accounts', [
            'fields' => 'id,name,access_token,instagram_business_account{id,username}',
        ]);
        if (!$res['ok']) {
            $fallback = cfc_instagram_graph_get('me', ['fields' => 'id,instagram_business_account']);
            if (!$fallback['ok']) {
                $out['error'] = $res['error'] !== '' ? $res['error'] : $fallback['error'];
                $out['code'] = $res['code'] !== '' ? $res['code'] : $fallback['code'];
                return $out;
            }
            $res = $fallback;
        }
        $json = $res['json'] ?? [];
        $rows = $json['data'] ?? null;
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $ig = $row['instagram_business_account'] ?? null;
                if (is_array($ig) && !empty($ig['id'])) {
                    $out['ok'] = true;
                    $out['user_id'] = preg_replace('/\D+/', '', (string) $ig['id']) ?? '';
                    $out['username'] = cfc_clip((string) ($ig['username'] ?? ''), 64);
                    return $out;
                }
            }
        }
        $direct = $json['instagram_business_account'] ?? null;
        if (is_array($direct) && !empty($direct['id'])) {
            $out['ok'] = true;
            $out['user_id'] = preg_replace('/\D+/', '', (string) $direct['id']) ?? '';
            $out['username'] = cfc_clip((string) ($direct['username'] ?? ''), 64);
            return $out;
        }
        $configured = preg_replace('/\D+/', '', cfc_instagram_setting('user_id')) ?? '';
        if ($configured !== '') {
            $out['ok'] = true;
            $out['user_id'] = $configured;
            $out['username'] = cfc_instagram_setting('username');
            return $out;
        }
        $out['error'] = 'No Instagram professional account is linked to this Facebook Page token.';
        $out['code'] = 'no_ig_account';
        return $out;
    }

    $res = cfc_instagram_graph_get('me', ['fields' => 'user_id,username,account_type,id']);
    if (!$res['ok']) {
        $out['error'] = $res['error'] !== '' ? $res['error'] : 'Could not read the Instagram user.';
        $out['code'] = $res['code'];
        return $out;
    }
    $json = $res['json'] ?? [];
    $row = $json;
    if (isset($json['data'][0]) && is_array($json['data'][0])) {
        $row = $json['data'][0];
    }
    $userId = (string) ($row['user_id'] ?? $row['id'] ?? '');
    $userId = preg_replace('/\D+/', '', $userId) ?? '';
    if ($userId === '') {
        $out['error'] = 'Instagram did not return a professional account ID.';
        $out['code'] = 'no_user';
        return $out;
    }
    $out['ok'] = true;
    $out['user_id'] = $userId;
    $out['username'] = cfc_clip((string) ($row['username'] ?? ''), 64);
    $out['account_type'] = cfc_clip((string) ($row['account_type'] ?? ''), 32);
    return $out;
}

/**
 * @return array{ok:bool,items:list<array<string,mixed>>,error:string,code:string,kind:string}
 */
function cfc_instagram_fetch_media(int $limit): array
{
    $limit = max(1, min(24, $limit));
    $me = cfc_instagram_fetch_me();
    if (!$me['ok']) {
        return [
            'ok' => false,
            'items' => [],
            'error' => $me['error'],
            'code' => $me['code'],
            'kind' => cfc_instagram_classify_error($me['code'], $me['error']),
        ];
    }
    if ($me['user_id'] !== cfc_instagram_setting('user_id') || $me['username'] !== cfc_instagram_setting('username')) {
        cfc_instagram_save_settings([
            'user_id' => $me['user_id'],
            'username' => $me['username'],
        ]);
    }

    $res = cfc_instagram_graph_get($me['user_id'] . '/media', [
        'fields' => cfc_instagram_media_fields(),
        'limit' => (string) $limit,
    ], 20);
    if (!$res['ok']) {
        $err = $res['error'] !== '' ? $res['error'] : 'Could not list Instagram media.';
        return [
            'ok' => false,
            'items' => [],
            'error' => $err,
            'code' => $res['code'],
            'kind' => cfc_instagram_classify_error($res['code'], $err),
        ];
    }
    $raw = $res['json']['data'] ?? null;
    if (!is_array($raw)) {
        return [
            'ok' => false,
            'items' => [],
            'error' => 'Instagram returned an unexpected media payload.',
            'code' => 'bad_payload',
            'kind' => 'api',
        ];
    }
    $items = [];
    foreach ($raw as $row) {
        $clean = cfc_instagram_sanitize_media($row, $me['username']);
        if ($clean !== null) {
            $items[] = $clean;
        }
    }
    return ['ok' => true, 'items' => $items, 'error' => '', 'code' => '', 'kind' => ''];
}

function cfc_instagram_sanitize_media(mixed $row, string $fallbackUser): ?array
{
    if (!is_array($row)) {
        return null;
    }
    $id = preg_replace('/\D+/', '', (string) ($row['id'] ?? '')) ?? '';
    if ($id === '' || strlen($id) > 64) {
        return null;
    }
    $type = strtoupper(trim((string) ($row['media_type'] ?? '')));
    if (!in_array($type, ['IMAGE', 'VIDEO', 'CAROUSEL_ALBUM'], true)) {
        return null;
    }
    $permalink = cfc_cms_href(trim((string) ($row['permalink'] ?? '')));
    if ($permalink === '' || !preg_match('#^https://www\.instagram\.com/#i', $permalink)) {
        return null;
    }
    $mediaUrl = cfc_instagram_safe_cdn_url((string) ($row['media_url'] ?? ''));
    $thumbUrl = cfc_instagram_safe_cdn_url((string) ($row['thumbnail_url'] ?? ''));
    if ($mediaUrl === '' && $thumbUrl === '' && $type === 'CAROUSEL_ALBUM') {
        $children = $row['children']['data'] ?? $row['children'] ?? null;
        if (is_array($children)) {
            foreach ($children as $child) {
                if (!is_array($child)) {
                    continue;
                }
                $thumbUrl = cfc_instagram_safe_cdn_url((string) ($child['thumbnail_url'] ?? ''));
                $mediaUrl = cfc_instagram_safe_cdn_url((string) ($child['media_url'] ?? ''));
                if ($mediaUrl !== '' || $thumbUrl !== '') {
                    break;
                }
            }
        }
    }
    $ts = trim((string) ($row['timestamp'] ?? ''));
    $when = null;
    if ($ts !== '') {
        $unix = strtotime($ts);
        if ($unix !== false) {
            $when = date('Y-m-d H:i:s', $unix);
        }
    }
    $caption = cfc_clip(trim((string) ($row['caption'] ?? '')), 2200);
    $alt = cfc_clip(trim((string) ($row['alt_text'] ?? '')), 500);
    $user = cfc_clip(trim((string) ($row['username'] ?? $fallbackUser)), 64);
    return [
        'instagram_media_id' => $id,
        'media_type' => $type,
        'media_url' => $mediaUrl,
        'thumbnail_url' => $thumbUrl,
        'permalink' => $permalink,
        'caption' => $caption,
        'alt_text' => $alt,
        'username' => $user,
        'timestamp' => $when,
    ];
}

function cfc_instagram_safe_cdn_url(string $url): string
{
    $url = trim($url);
    if ($url === '' || !preg_match('#^https://#i', $url) || !cfc_instagram_api_host_ok($url)) {
        return '';
    }
    return $url;
}
