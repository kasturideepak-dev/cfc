<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

function cfc_instagram_feed_posts(?int $limit = null): array
{
    if (!cfc_instagram_enabled() || !cfc_db_ready()) {
        return [];
    }
    $pdo = cfc_pdo();
    if (!$pdo) {
        return [];
    }
    cfc_instagram_ensure_schema();
    $limit = $limit ?? max(1, min(24, (int) cfc_instagram_setting('limit')));
    try {
        $stmt = $pdo->prepare(
            'SELECT instagram_media_id, media_type, media_url, thumbnail_url, permalink, caption, alt_text, username, timestamp, local_file
             FROM cfc_instagram_posts
             ORDER BY sort_order ASC, timestamp DESC
             LIMIT ' . (int) $limit
        );
        $stmt->execute();
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('CFC Instagram feed read failed: ' . $e->getMessage());
        return [];
    }
    $out = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $permalink = cfc_cms_href((string) ($row['permalink'] ?? ''));
        if ($permalink === '') {
            continue;
        }
        $file = cfc_cms_normalize_media((string) ($row['local_file'] ?? ''));
        $remote = '';
        if ($file === '') {
            $type = strtoupper((string) ($row['media_type'] ?? ''));
            $remote = $type === 'VIDEO'
                ? cfc_instagram_safe_cdn_url((string) ($row['thumbnail_url'] ?? ''))
                : cfc_instagram_safe_cdn_url((string) ($row['media_url'] ?? ''));
            if ($remote === '') {
                $remote = cfc_instagram_safe_cdn_url((string) ($row['thumbnail_url'] ?? ''));
            }
        }
        if ($file === '' && $remote === '') {
            continue;
        }
        $caption = trim((string) ($row['caption'] ?? ''));
        $alt = trim((string) ($row['alt_text'] ?? ''));
        if ($alt === '') {
            $alt = $caption !== '' ? cfc_clip($caption, 120) : 'Chennapatnam Filter Coffee on Instagram';
        }
        $out[] = [
            'id' => (string) ($row['instagram_media_id'] ?? ''),
            'href' => $permalink,
            'type' => strtolower((string) ($row['media_type'] ?? 'image')),
            'file' => $file,
            'remote' => $remote,
            'alt' => $alt,
            'caption' => $caption,
            'timestamp' => (string) ($row['timestamp'] ?? ''),
        ];
    }
    return $out;
}

function cfc_instagram_feed_count(): int
{
    if (!cfc_db_ready()) {
        return 0;
    }
    $pdo = cfc_pdo();
    if (!$pdo) {
        return 0;
    }
    try {
        cfc_instagram_ensure_schema();
        return (int) $pdo->query('SELECT COUNT(*) FROM cfc_instagram_posts')->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}
