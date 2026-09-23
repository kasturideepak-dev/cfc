<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

function cfc_turnstile_enabled(): bool
{
    return trim((string) cfc_config('turnstile_site_key', '')) !== ''
        && trim((string) cfc_config('turnstile_secret', '')) !== '';
}

function cfc_turnstile_widget(string $theme = 'auto'): string
{
    if (!cfc_turnstile_enabled()) {
        return '';
    }
    $key = trim((string) cfc_config('turnstile_site_key', ''));
    $theme = in_array($theme, ['light', 'dark', 'auto'], true) ? $theme : 'auto';
    static $scripted = false;
    $html = '';
    if (!$scripted) {
        $html .= '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
        $scripted = true;
    }
    $html .= '<div class="cfc-turnstile"><div class="cf-turnstile" data-sitekey="' . cfc_e($key) . '" data-theme="' . cfc_e($theme) . '" data-size="normal"></div></div>';
    return $html;
}

function cfc_form_honeypot(): string
{
    return '<div class="cfc-hp" aria-hidden="true"><label>Company website<input type="text" name="website" value="" tabindex="-1" autocomplete="off"></label></div>';
}

function cfc_http_post_https(string $url, array $fields): ?string
{
    if (!preg_match('#^https://#i', $url)) {
        return null;
    }
    $body = http_build_query($fields);
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }
        $opts = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ];
        if (defined('CURLPROTO_HTTPS')) {
            $opts[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
            $opts[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTPS;
        }
        curl_setopt_array($ch, $opts);
        $res = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!is_string($res) || $res === '' || $code < 200 || $code >= 300) {
            return null;
        }
        return $res;
    }
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $body,
            'timeout' => 8,
            'ignore_errors' => true,
        ],
    ]);
    $res = @file_get_contents($url, false, $ctx);
    return is_string($res) && $res !== '' ? $res : null;
}

function cfc_turnstile_token(): string
{
    $token = $_POST['cf-turnstile-response'] ?? $_POST['g-recaptcha-response'] ?? '';
    if (!is_string($token)) {
        return '';
    }
    $token = trim($token);
    if ($token === '' || strlen($token) > 8192) {
        return '';
    }
    return $token;
}

function cfc_turnstile_verify(): array
{
    if (!cfc_turnstile_enabled()) {
        return [true, ''];
    }
    $token = cfc_turnstile_token();
    if ($token === '') {
        return [false, 'Please complete the captcha and try again.'];
    }
    $payload = [
        'secret' => (string) cfc_config('turnstile_secret', ''),
        'response' => $token,
    ];
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        $payload['remoteip'] = $ip;
    }
    $raw = cfc_http_post_https('https://challenges.cloudflare.com/turnstile/v0/siteverify', $payload);
    if ($raw === null) {
        return [false, 'Captcha could not be checked. Please try again in a moment.'];
    }
    $data = json_decode($raw, true);
    if (!is_array($data) || ($data['success'] ?? false) !== true) {
        return [false, 'Please complete the captcha and try again.'];
    }
    return [true, ''];
}

function cfc_form_source(): string
{
    $source = (string) ($_POST['cfc_path'] ?? '/contact-us/');
    $ok = ['/contact-us/', '/franchise/', '/landing/'];
    return in_array($source, $ok, true) ? $source : '/contact-us/';
}

function cfc_form_fail(bool $isAjax, string $message, string $source, int $status = 403): never
{
    if ($isAjax) {
        cfc_json($status, ['success' => false, 'message' => $message]);
    }
    $path = in_array($source, ['/contact-us/', '/franchise/', '/landing/'], true) ? ltrim($source, '/') : 'contact-us/';
    cfc_redirect($path);
}

function cfc_form_is_ajax(): bool
{
    if (strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') {
        return true;
    }
    return str_contains(strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json');
}

function cfc_form_handle(): never
{
    $isAjax = cfc_form_is_ajax();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        if ($isAjax) {
            cfc_json(405, ['success' => false, 'message' => 'Method not allowed']);
        }
        cfc_redirect('contact-us/');
    }

    $source = cfc_form_source();

    if (!cfc_csrf_verify((string) ($_POST['cfc_csrf'] ?? ''))) {
        cfc_form_fail($isAjax, 'Please refresh the page and try again.', $source, 403);
    }

    $honeypot = trim((string) ($_POST['website'] ?? ''));
    if ($honeypot !== '') {
        if ($isAjax) {
            cfc_json(200, [
                'success' => true,
                'message' => 'Thank you. We will get in touch shortly.',
                'redirectUrl' => cfc_url('thank-you/'),
            ]);
        }
        cfc_redirect('thank-you/');
    }

    if (!cfc_form_rate_ok()) {
        cfc_form_fail($isAjax, 'Too many submissions. Please wait a few minutes.', $source, 429);
    }

    [$captchaOk, $captchaMsg] = cfc_turnstile_verify();
    if (!$captchaOk) {
        cfc_form_fail($isAjax, $captchaMsg, $source, 403);
    }

    $name = '';
    if (isset($_POST['names']) && is_array($_POST['names'])) {
        $parts = array_filter([
            trim((string) ($_POST['names']['first_name'] ?? '')),
            trim((string) ($_POST['names']['middle_name'] ?? '')),
            trim((string) ($_POST['names']['last_name'] ?? '')),
        ], static fn($p) => $p !== '');
        $name = implode(' ', $parts);
    }
    if ($name === '') {
        $rawName = $_POST['input_text'] ?? $_POST['names_first_name'] ?? '';
        $name = is_string($rawName) ? trim($rawName) : '';
    }

    $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $mobileRaw = $_POST['input_mask'] ?? $_POST['phone'] ?? $_POST['mobile'] ?? '';
    $mobile = is_string($mobileRaw) ? trim($mobileRaw) : '';
    $cityRaw = $_POST['input_text_1'] ?? $_POST['city'] ?? '';
    $city = is_string($cityRaw) ? trim($cityRaw) : '';
    $messageRaw = $_POST['description'] ?? $_POST['message'] ?? '';
    $message = is_string($messageRaw) ? trim($messageRaw) : '';

    $name = cfc_clip($name, 120);
    $email = cfc_clip($email, 200);
    $mobile = cfc_clip($mobile, 30);
    $city = cfc_clip($city, 80);
    $message = cfc_clip($message, 4000);

    $formId = is_string($_POST['form_id'] ?? null) ? (string) $_POST['form_id'] : 'contact';
    if (!in_array($formId, ['contact', 'franchise', 'landing'], true)) {
        $formId = 'contact';
    }

    $errors = [];
    if ($name === '') {
        $errors['name'] = 'This field is required';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'This field must contain a valid email';
    }
    $digits = preg_replace('/\D+/', '', $mobile) ?? '';
    if ($mobile === '' || strlen($digits) < 8) {
        $errors['mobile'] = 'This field is required';
    }
    if ($city === '') {
        $errors['city'] = 'This field is required';
    }

    if ($errors) {
        if ($isAjax) {
            cfc_json(422, ['success' => false, 'errors' => $errors, 'message' => 'Please check the highlighted fields.']);
        }
        $_SESSION['cfc_form_errors'] = $errors;
        cfc_redirect(ltrim($source, '/'));
    }

    $record = [
        'id' => bin2hex(random_bytes(8)),
        'created_at' => date('c'),
        'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        'form_id' => $formId,
        'source' => $source,
        'name' => $name,
        'email' => $email,
        'mobile' => $mobile,
        'city' => $city,
        'message' => $message,
    ];

    $dir = CFC_DATA . '/submissions';
    $fileOk = false;
    if (is_dir($dir) || mkdir($dir, 0750, true) || is_dir($dir)) {
        $file = $dir . '/' . date('Ymd-His') . '-' . $record['id'] . '.json';
        $json = json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        $fileOk = $json !== false && file_put_contents($file, $json, LOCK_EX) !== false;
    }
    $dbOk = cfc_db_insert_submission($record);
    if (cfc_db_configured()) {
        if (!$dbOk) {
            cfc_form_fail($isAjax, 'Could not save your enquiry. Please try again or call us.', $source, 500);
        }
    } elseif (!$fileOk) {
        cfc_form_fail($isAjax, 'Could not save your enquiry. Please try again or call us.', $source, 500);
    }

    if (!empty(cfc_config('mail_enabled'))) {
        $safeName = str_replace(["\r", "\n", "\0"], '', $name);
        $subject = 'CFC enquiry — ' . $safeName;
        $mailBody = "Name: {$name}\nEmail: {$email}\nMobile: {$mobile}\nCity: {$city}\nForm: {$formId}\nPage: {$source}\nMessage:\n{$message}\n";
        $html = '<p><strong>New Chennapatnam Filter Coffee enquiry</strong></p><table cellpadding="6">'
            . '<tr><td>Name</td><td>' . cfc_e($name) . '</td></tr>'
            . '<tr><td>Email</td><td>' . cfc_e($email) . '</td></tr>'
            . '<tr><td>Mobile</td><td>' . cfc_e($mobile) . '</td></tr>'
            . '<tr><td>City</td><td>' . cfc_e($city) . '</td></tr>'
            . '<tr><td>Form</td><td>' . cfc_e($formId) . '</td></tr>'
            . '<tr><td>Page</td><td>' . cfc_e($source) . '</td></tr>'
            . '</table><p>' . nl2br(cfc_e($message), false) . '</p>';
        $to = (string) cfc_config('mail_to');
        if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
            cfc_send_mail($to, $subject, $mailBody, $email, $html);
        }
    }

    if ($isAjax) {
        cfc_json(200, [
            'success' => true,
            'message' => 'Thank you. We will get in touch shortly.',
            'redirectUrl' => cfc_url('thank-you/'),
        ]);
    }
    cfc_redirect('thank-you/');
}

function cfc_secrets_save(array $post): bool
{
    if (!cfc_db_ready()) {
        return false;
    }
    if (!empty($post['turnstile_disable'])) {
        $data = [
            'turnstile_site_key' => '',
            'turnstile_secret' => '',
        ];
    } else {
        $site = cfc_clip(trim((string) ($post['turnstile_site_key'] ?? '')), 200);
        $site = trim($site, " \t\n\r\0\x0B\"'");
        $secret = cfc_clip(trim((string) ($post['turnstile_secret'] ?? '')), 200);
        $secret = trim($secret, " \t\n\r\0\x0B\"'");
        if ($secret === '') {
            $secret = trim((string) (cfc_setting_get('turnstile_secret') ?? cfc_config('turnstile_secret', '')));
        }
        $data = [
            'turnstile_site_key' => $site,
            'turnstile_secret' => $secret,
        ];
    }
    $ok = cfc_setting_set('turnstile_site_key', $data['turnstile_site_key'])
        && cfc_setting_set('turnstile_secret', $data['turnstile_secret']);
    if ($ok) {
        $GLOBALS['cfc_config']['turnstile_site_key'] = $data['turnstile_site_key'];
        $GLOBALS['cfc_config']['turnstile_secret'] = $data['turnstile_secret'];
        $leftover = CFC_DATA . '/cms/secrets.json';
        if (is_file($leftover)) {
            @unlink($leftover);
        }
    }
    return $ok;
}

function cfc_form_rate_ok(): bool
{
    $limit = max(1, (int) cfc_config('form_rate_limit', 5));
    $window = max(60, (int) cfc_config('form_rate_window', 600));
    $now = time();

    $hits = array_values(array_filter(
        $_SESSION['cfc_form_hits'] ?? [],
        static fn($t) => is_int($t) && $t > $now - $window
    ));
    if (count($hits) >= $limit) {
        $_SESSION['cfc_form_hits'] = $hits;
        return false;
    }

    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    if (filter_var($ip, FILTER_VALIDATE_IP) && !cfc_rate_limit_hit($ip, $limit, $window)) {
        $_SESSION['cfc_form_hits'] = $hits;
        return false;
    }

    $hits[] = $now;
    $_SESSION['cfc_form_hits'] = $hits;
    return true;
}
