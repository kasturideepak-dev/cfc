<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

function cfc_instagram_api_host_ok(string $url): bool
{
    $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?: ''));
    if ($host === '') {
        return false;
    }
    $exact = [
        'graph.instagram.com',
        'graph.facebook.com',
        'api.instagram.com',
        'www.instagram.com',
        'instagram.com',
    ];
    if (in_array($host, $exact, true)) {
        return true;
    }
    return str_ends_with($host, '.cdninstagram.com')
        || str_ends_with($host, '.fbcdn.net')
        || $host === 'scontent.cdninstagram.com'
        || $host === 'cdninstagram.com';
}

/**
 * @return array{ok:bool,status:int,body:string,json:?array,error:string,code:string}
 */
function cfc_instagram_api_request(string $method, string $url, array $fields = [], int $timeout = 15): array
{
    $empty = ['ok' => false, 'status' => 0, 'body' => '', 'json' => null, 'error' => '', 'code' => ''];
    $method = strtoupper($method) === 'POST' ? 'POST' : 'GET';
    $timeout = max(3, min(25, $timeout));
    if (!preg_match('#^https://#i', $url) || !cfc_instagram_api_host_ok($url)) {
        $empty['error'] = 'Blocked non-HTTPS or disallowed host.';
        return $empty;
    }

    $body = '';
    $status = 0;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch === false) {
            $empty['error'] = 'Could not start HTTPS request.';
            return $empty;
        }
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 4,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_USERAGENT => 'CFC-InstagramFeed/1.0 (+https://chennapatnamfiltercoffee.com)',
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ];
        if (defined('CURLPROTO_HTTPS')) {
            $opts[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
            $opts[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTPS;
        }
        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = $fields;
        }
        curl_setopt_array($ch, $opts);
        $res = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cerr = curl_error($ch);
        curl_close($ch);
        if (!is_string($res)) {
            $empty['status'] = $status;
            $empty['error'] = $cerr !== '' ? cfc_instagram_redact($cerr) : 'Empty response from Instagram.';
            return $empty;
        }
        $body = $res;
    } else {
        $header = "Accept: application/json\r\n";
        $http = [
            'method' => $method,
            'header' => $header,
            'timeout' => $timeout,
            'ignore_errors' => true,
            'follow_location' => 1,
        ];
        if ($method === 'POST') {
            $http['header'] .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $http['content'] = http_build_query($fields);
        }
        $ctx = stream_context_create(['http' => $http]);
        $res = @file_get_contents($url, false, $ctx);
        $body = is_string($res) ? $res : '';
        foreach ($http_response_header ?? [] as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d+)#', $line, $m)) {
                $status = (int) $m[1];
            }
        }
        if ($body === '') {
            $empty['status'] = $status;
            $empty['error'] = 'Empty response from Instagram.';
            return $empty;
        }
    }

    $json = json_decode($body, true);
    $json = is_array($json) ? $json : null;
    $err = '';
    $code = '';
    if (is_array($json) && isset($json['error']) && is_array($json['error'])) {
        $err = trim((string) ($json['error']['message'] ?? $json['error']['error_user_msg'] ?? 'Instagram API error'));
        $code = (string) ($json['error']['code'] ?? '');
        $sub = (string) ($json['error']['error_subcode'] ?? '');
        if ($sub !== '') {
            $code = $code !== '' ? $code . '.' . $sub : $sub;
        }
    } elseif ($status >= 400) {
        $err = 'Instagram API HTTP ' . $status . '.';
        $code = (string) $status;
    }

    return [
        'ok' => $status >= 200 && $status < 300 && $err === '',
        'status' => $status,
        'body' => $body,
        'json' => $json,
        'error' => cfc_instagram_redact($err),
        'code' => $code,
    ];
}

function cfc_instagram_classify_error(string $code, string $message): string
{
    $n = (int) $code;
    if ($n === 190 || str_contains($code, '190')) {
        return 'token';
    }
    if (in_array($n, [4, 17, 32, 613], true) || str_contains(strtolower($message), 'limit')) {
        return 'rate_limit';
    }
    if (in_array($n, [10, 200, 294], true) || str_contains(strtolower($message), 'permission')) {
        return 'permission';
    }
    if ($n === 1 || $n === 2 || $n === 0) {
        return 'network';
    }
    return 'api';
}
