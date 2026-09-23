<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

function cfc_instagram_username(): string
{
    $url = cfc_cms_href((string) cfc_cms('media.instagram'));
    if (preg_match('#instagram\.com/([A-Za-z0-9._]+)#i', $url, $m)) {
        $user = strtolower($m[1]);
        if (!in_array($user, ['p', 'reel', 'reels', 'stories', 'tv', 'explore'], true)) {
            return $user;
        }
    }
    return 'chennapatnamfiltercoffee';
}

function cfc_instagram_shortcode(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    if (preg_match('#(?:instagram\.com|instagr\.am)/(?:reel|reels|p|tv)/([A-Za-z0-9_-]{5,15})#i', $url, $m)) {
        return $m[1];
    }
    if (preg_match('#^([A-Za-z0-9_-]{5,15})$#', $url, $m)) {
        return $m[1];
    }
    return '';
}

function cfc_instagram_post_url(string $shortcode): string
{
    return 'https://www.instagram.com/reel/' . $shortcode . '/';
}

function cfc_instagram_parse_urls(string $raw): array
{
    $out = [];
    $seen = [];
    if (preg_match_all('#https?://(?:www\.)?(?:instagram\.com|instagr\.am)/[^\s<>"\']+#i', $raw, $m)) {
        foreach ($m[0] as $url) {
            $url = rtrim((string) $url, '.,);]');
            $code = cfc_instagram_shortcode($url);
            if ($code === '' || isset($seen[$code])) {
                continue;
            }
            $seen[$code] = true;
            $out[] = cfc_instagram_post_url($code);
        }
    }
    return $out;
}

function cfc_instagram_host_ok(string $url): bool
{
    $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?: ''));
    if ($host === '') {
        return false;
    }
    if (in_array($host, ['instagram.com', 'www.instagram.com', 'graph.instagram.com', 'i.instagram.com', 'scontent.cdninstagram.com', 'cdninstagram.com'], true)) {
        return true;
    }
    return str_ends_with($host, '.cdninstagram.com') || str_ends_with($host, '.fbcdn.net');
}

function cfc_instagram_http_get(string $url, int $timeout = 12): ?string
{
    $timeout = max(3, min(20, $timeout));
    for ($hop = 0; $hop < 6; $hop++) {
        if (!preg_match('#^https://#i', $url) || !cfc_instagram_host_ok($url)) {
            return null;
        }
        $code = 0;
        $body = '';
        $location = '';
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return null;
            }
            $headers = [];
            $opts = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
                CURLOPT_HTTPHEADER => [
                    'Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                    'Referer: https://www.instagram.com/',
                ],
                CURLOPT_HEADERFUNCTION => static function ($ch, string $header) use (&$headers): int {
                    $headers[] = $header;
                    return strlen($header);
                },
            ];
            if (defined('CURLPROTO_HTTPS')) {
                $opts[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
                $opts[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTPS;
            }
            curl_setopt_array($ch, $opts);
            $res = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $body = is_string($res) ? $res : '';
            foreach ($headers as $header) {
                if (preg_match('/^Location:\s*(.+)$/i', trim($header), $m)) {
                    $location = trim($m[1]);
                }
            }
        } else {
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => "User-Agent: Mozilla/5.0\r\nReferer: https://www.instagram.com/\r\n",
                    'timeout' => $timeout,
                    'follow_location' => 0,
                    'ignore_errors' => true,
                ],
            ]);
            $res = @file_get_contents($url, false, $ctx);
            $body = is_string($res) ? $res : '';
            foreach ($http_response_header ?? [] as $header) {
                if (preg_match('#^HTTP/\S+\s+(\d+)#', $header, $m)) {
                    $code = (int) $m[1];
                }
                if (preg_match('/^Location:\s*(.+)$/i', $header, $m)) {
                    $location = trim($m[1]);
                }
            }
        }
        if ($code >= 300 && $code < 400 && $location !== '') {
            if (str_starts_with($location, '//')) {
                $location = 'https:' . $location;
            } elseif (str_starts_with($location, '/')) {
                $parts = parse_url($url);
                $location = 'https://' . ($parts['host'] ?? 'www.instagram.com') . $location;
            }
            $url = $location;
            continue;
        }
        if ($code >= 200 && $code < 300 && $body !== '') {
            return $body;
        }
        return null;
    }
    return null;
}

function cfc_instagram_cover_dir(): string
{
    return CFC_ROOT . '/assets/media-hub';
}

function cfc_instagram_cover_path(string $shortcode): string
{
    return 'media-hub/' . $shortcode . '.webp';
}

function cfc_instagram_bytes_are_image(string $bytes): bool
{
    $head = substr($bytes, 0, 16);
    if (str_starts_with($head, "\xff\xd8\xff")) {
        return true;
    }
    if (str_starts_with($head, "\x89PNG")) {
        return true;
    }
    if (str_starts_with($head, 'GIF8')) {
        return true;
    }
    return str_starts_with($head, 'RIFF') && str_contains($head, 'WEBP');
}

function cfc_instagram_write_cover(string $bytes, string $destAbs): bool
{
    $tmp = $destAbs . '.src-' . bin2hex(random_bytes(4));
    if (file_put_contents($tmp, $bytes) === false) {
        return false;
    }
    $ok = cfc_instagram_make_tile($tmp, $destAbs);
    @unlink($tmp);
    if ($ok) {
        @chmod($destAbs, 0644);
    }
    return $ok;
}

function cfc_instagram_make_tile(string $srcAbs, string $destAbs, int $tw = 480, int $th = 600): bool
{
    if (!function_exists('imagecreatetruecolor')) {
        return copy($srcAbs, $destAbs);
    }
    $info = @getimagesize($srcAbs);
    if (!$info) {
        return false;
    }
    $w = (int) $info[0];
    $h = (int) $info[1];
    $type = (int) $info[2];
    if ($w < 1 || $h < 1 || $w > 8000 || $h > 8000 || ($w * $h) > 40000000) {
        return false;
    }
    $src = match ($type) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($srcAbs),
        IMAGETYPE_PNG => @imagecreatefrompng($srcAbs),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($srcAbs) : false,
        IMAGETYPE_GIF => @imagecreatefromgif($srcAbs),
        default => false,
    };
    if (!$src) {
        return false;
    }
    $scale = max($tw / $w, $th / $h);
    $cropW = min($w, $tw / $scale);
    $cropH = min($h, $th / $scale);
    $srcX = (int) max(0, round(($w - $cropW) / 2));
    $srcY = (int) max(0, round(($h - $cropH) * 0.18));
    $dst = imagecreatetruecolor($tw, $th);
    imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $tw, $th, (int) round($cropW), (int) round($cropH));
    imagedestroy($src);
    $ok = function_exists('imagewebp') ? imagewebp($dst, $destAbs, 82) : imagejpeg($dst, $destAbs, 85);
    imagedestroy($dst);
    return (bool) $ok && is_file($destAbs);
}

function cfc_instagram_download_cover(string $shortcode): ?string
{
    $shortcode = cfc_instagram_shortcode($shortcode);
    if ($shortcode === '') {
        return null;
    }
    $rel = cfc_instagram_cover_path($shortcode);
    $dir = cfc_instagram_cover_dir();
    $abs = $dir . '/' . $shortcode . '.webp';
    if (is_file($abs) && filesize($abs) > 1000) {
        return $rel;
    }
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        cfc_cms_notes('Media Hub image folder is not writable.');
        return null;
    }
    $bytes = cfc_instagram_http_get('https://www.instagram.com/p/' . rawurlencode($shortcode) . '/media/?size=l');
    if ($bytes === null || strlen($bytes) < 1000 || !cfc_instagram_bytes_are_image($bytes)) {
        cfc_cms_notes('Could not download the Instagram cover for ' . $shortcode . '.');
        return null;
    }
    if (!cfc_instagram_write_cover($bytes, $abs)) {
        cfc_cms_notes('Could not convert the Instagram cover for ' . $shortcode . '.');
        return null;
    }
    return $rel;
}

function cfc_instagram_known_codes(array $posts): array
{
    $known = [];
    foreach ($posts as $row) {
        if (!is_array($row)) {
            continue;
        }
        $code = cfc_instagram_shortcode((string) ($row['href'] ?? ''));
        if ($code !== '') {
            $known[$code] = true;
        }
    }
    return $known;
}

function cfc_instagram_import_rows(array $urls, array $known): array
{
    $rows = [];
    foreach ($urls as $url) {
        $code = cfc_instagram_shortcode((string) $url);
        if ($code === '' || isset($known[$code])) {
            continue;
        }
        $file = cfc_instagram_download_cover($code);
        if ($file === null) {
            continue;
        }
        $known[$code] = true;
        $rows[] = [
            'href' => cfc_instagram_post_url($code),
            'type' => 'video',
            'file' => $file,
        ];
    }
    return $rows;
}

function cfc_instagram_import_urls(array $urls): array
{
    $current = cfc_cms_media_hub();
    $known = cfc_instagram_known_codes($current);
    $additions = cfc_instagram_import_rows($urls, $known);
    if ($additions === []) {
        return [];
    }
    if (!cfc_cms_persist_media_hub(array_merge($additions, $current))) {
        return [];
    }
    return $additions;
}

require CFC_ROOT . '/instagram/load.php';
