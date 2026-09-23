-- Instagram feed cache. Applied automatically on boot via cfc_instagram_ensure_schema().
-- Safe to run manually in phpMyAdmin.

CREATE TABLE IF NOT EXISTS cfc_instagram_posts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  instagram_media_id VARCHAR(64) NOT NULL,
  media_type VARCHAR(32) NOT NULL DEFAULT '',
  media_url TEXT NULL,
  thumbnail_url TEXT NULL,
  permalink VARCHAR(500) NOT NULL DEFAULT '',
  caption TEXT NULL,
  alt_text VARCHAR(500) NOT NULL DEFAULT '',
  username VARCHAR(64) NOT NULL DEFAULT '',
  timestamp DATETIME NULL,
  local_file VARCHAR(255) NOT NULL DEFAULT '',
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  fetched_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY instagram_media_id (instagram_media_id),
  KEY timestamp_idx (timestamp),
  KEY sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
