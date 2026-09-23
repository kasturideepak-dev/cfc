<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

function cfc_instagram_cache_dir(): string
{
    return CFC_ROOT . '/assets/instagram';
}

function cfc_instagram_sync_lock_file(): string
{
    return CFC_DATA . '/cms/instagram-sync.lock';
}

function cfc_instagram_cache_fresh(): bool
{
    $last = cfc_instagram_setting('last_sync_at');
    if ($last === '' || cfc_instagram_setting('last_sync_ok') !== '1') {
        return false;
    }
    $unix = strtotime($last);
    if ($unix === false) {
        return false;
    }
    $minutes = max(15, min(1440, (int) cfc_instagram_setting('cache_minutes')));
    return (time() - $unix) < ($minutes * 60);
}

/**
 * @return array{ok:bool,synced:int,message:string,code:string}
 */
function cfc_instagram_sync(bool $force = false): array
{
    if (!cfc_db_ready()) {
        return ['ok' => false, 'synced' => 0, 'message' => 'MySQL is required to cache Instagram posts.', 'code' => 'db'];
    }
    cfc_instagram_ensure_schema();
    if (!cfc_instagram_has_token()) {
        $msg = 'Connect an Instagram professional account first.';
        cfc_instagram_set_status(false, $msg, 'token');
        return ['ok' => false, 'synced' => 0, 'message' => $msg, 'code' => 'token'];
    }

    $lock = cfc_instagram_sync_lock_file();
    $dir = dirname($lock);
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    $fh = @fopen($lock, 'c');
    if ($fh === false) {
        return ['ok' => false, 'synced' => 0, 'message' => 'Could not lock the Instagram sync.', 'code' => 'lock'];
    }
    if (!flock($fh, LOCK_EX | LOCK_NB)) {
        fclose($fh);
        return ['ok' => false, 'synced' => 0, 'message' => 'An Instagram sync is already running.', 'code' => 'lock'];
    }

    try {
        if (!$force && cfc_instagram_cache_fresh()) {
            return ['ok' => true, 'synced' => 0, 'message' => 'Instagram cache is still fresh.', 'code' => ''];
        }
        if (cfc_instagram_token_should_refresh()) {
            cfc_instagram_refresh_long_lived();
        }

        @set_time_limit(90);
        $limit = max(1, min(24, (int) cfc_instagram_setting('limit')));
        $fetch = cfc_instagram_fetch_media($limit);
        if (!$fetch['ok']) {
            $msg = $fetch['error'] !== '' ? $fetch['error'] : 'Instagram sync failed.';
            if ($fetch['kind'] === 'token') {
                $msg = 'Instagram access token expired or is invalid. Reconnect the account.';
            } elseif ($fetch['kind'] === 'rate_limit') {
                $msg = 'Instagram rate limit reached. Cached posts will stay on the site until the next cron run.';
            } elseif ($fetch['kind'] === 'permission') {
                $msg = 'Instagram permission error. The token needs instagram_business_basic (Instagram Login) or instagram_basic (Facebook Login).';
            }
            cfc_instagram_set_status(false, $msg, $fetch['code']);
            return ['ok' => false, 'synced' => 0, 'message' => $msg, 'code' => $fetch['code']];
        }
        if ($fetch['items'] === []) {
            $msg = 'Instagram returned no published media. Existing cached posts were kept.';
            cfc_instagram_set_status(true, '');
            return ['ok' => true, 'synced' => 0, 'message' => $msg, 'code' => ''];
        }

        $n = cfc_instagram_persist_posts($fetch['items']);
        cfc_instagram_set_status(true, '');
        return [
            'ok' => true,
            'synced' => $n,
            'message' => 'Synced ' . $n . ' Instagram ' . ($n === 1 ? 'post' : 'posts') . '.',
            'code' => '',
        ];
    } finally {
        flock($fh, LOCK_UN);
        fclose($fh);
    }
}

function cfc_instagram_persist_posts(array $items): int
{
    $pdo = cfc_pdo();
    if (!$pdo) {
        return 0;
    }
    $dir = cfc_instagram_cache_dir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $ids = [];
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare(
        'INSERT INTO cfc_instagram_posts
            (instagram_media_id, media_type, media_url, thumbnail_url, permalink, caption, alt_text, username, timestamp, local_file, sort_order, fetched_at)
         VALUES
            (:id, :type, :media, :thumb, :permalink, :caption, :alt, :username, :ts, :file, :sort, :fetched)
         ON DUPLICATE KEY UPDATE
            media_type = VALUES(media_type),
            media_url = VALUES(media_url),
            thumbnail_url = VALUES(thumbnail_url),
            permalink = VALUES(permalink),
            caption = VALUES(caption),
            alt_text = VALUES(alt_text),
            username = VALUES(username),
            timestamp = VALUES(timestamp),
            local_file = IF(VALUES(local_file) = "", local_file, VALUES(local_file)),
            sort_order = VALUES(sort_order),
            fetched_at = VALUES(fetched_at)'
    );

    $sort = 0;
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $id = (string) ($item['instagram_media_id'] ?? '');
        if ($id === '') {
            continue;
        }
        $existing = cfc_instagram_existing_file($id);
        $local = cfc_instagram_cache_media($id, (string) ($item['media_type'] ?? ''), (string) ($item['media_url'] ?? ''), (string) ($item['thumbnail_url'] ?? ''), $existing);
        $stmt->execute([
            ':id' => $id,
            ':type' => (string) ($item['media_type'] ?? ''),
            ':media' => (string) ($item['media_url'] ?? ''),
            ':thumb' => (string) ($item['thumbnail_url'] ?? ''),
            ':permalink' => (string) ($item['permalink'] ?? ''),
            ':caption' => (string) ($item['caption'] ?? ''),
            ':alt' => (string) ($item['alt_text'] ?? ''),
            ':username' => (string) ($item['username'] ?? ''),
            ':ts' => $item['timestamp'] ?: null,
            ':file' => $local,
            ':sort' => $sort,
            ':fetched' => $now,
        ]);
        $ids[] = $id;
        $sort++;
    }

    if ($ids !== []) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $del = $pdo->prepare('DELETE FROM cfc_instagram_posts WHERE instagram_media_id NOT IN (' . $placeholders . ')');
        $del->execute($ids);
    }
    return count($ids);
}

function cfc_instagram_existing_file(string $id): string
{
    $pdo = cfc_pdo();
    if (!$pdo) {
        return '';
    }
    $stmt = $pdo->prepare('SELECT local_file FROM cfc_instagram_posts WHERE instagram_media_id = ?');
    $stmt->execute([$id]);
    $val = $stmt->fetchColumn();
    return is_string($val) ? $val : '';
}

function cfc_instagram_cache_media(string $id, string $type, string $mediaUrl, string $thumbUrl, string $existing): string
{
    $rel = 'instagram/' . $id . '.webp';
    $abs = CFC_ROOT . '/assets/' . $rel;
    if (is_file($abs) && filesize($abs) > 1000) {
        return $rel;
    }
    if ($existing !== '' && is_file(CFC_ROOT . '/assets/' . $existing) && filesize(CFC_ROOT . '/assets/' . $existing) > 1000) {
        return $existing;
    }
    $src = $type === 'VIDEO' ? ($thumbUrl !== '' ? $thumbUrl : $mediaUrl) : ($mediaUrl !== '' ? $mediaUrl : $thumbUrl);
    if ($src === '') {
        return '';
    }
    $bytes = cfc_instagram_http_get($src, 15);
    if ($bytes === null || strlen($bytes) < 1000 || !cfc_instagram_bytes_are_image($bytes)) {
        return '';
    }
    $dir = cfc_instagram_cache_dir();
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return '';
    }
    return cfc_instagram_write_cover($bytes, $abs) ? $rel : '';
}
