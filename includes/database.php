<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

function cfc_db_configured(): bool
{
    if (trim((string) cfc_config('db_dsn', '')) !== '') {
        return true;
    }
    return trim((string) cfc_config('db_name', '')) !== ''
        && trim((string) cfc_config('db_user', '')) !== '';
}

function cfc_db_dsn(): ?string
{
    $dsn = trim((string) cfc_config('db_dsn', ''));
    if ($dsn !== '') {
        if (!preg_match('#^mysql:#i', $dsn)) {
            return null;
        }
        return $dsn;
    }
    $name = trim((string) cfc_config('db_name', ''));
    $user = trim((string) cfc_config('db_user', ''));
    if ($name === '' || $user === '') {
        return null;
    }
    if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $name)) {
        return null;
    }
    $host = trim((string) cfc_config('db_host', 'localhost')) ?: 'localhost';
    if (!preg_match('/^[A-Za-z0-9.:_-]+$/', $host)) {
        return null;
    }
    $port = (int) cfc_config('db_port', 3306);
    if ($port < 1 || $port > 65535) {
        $port = 3306;
    }
    return 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name . ';charset=utf8mb4';
}

function cfc_pdo(): ?PDO
{
    static $pdo = false;
    static $booting = false;

    if ($pdo instanceof PDO) {
        return $pdo;
    }
    if ($pdo === null) {
        return null;
    }

    $dsn = cfc_db_dsn();
    if ($dsn === null) {
        $pdo = null;
        return null;
    }
    if (!extension_loaded('pdo_mysql')) {
        error_log('CFC: pdo_mysql extension is missing.');
        $pdo = null;
        return null;
    }

    try {
        $conn = new PDO($dsn, (string) cfc_config('db_user', ''), (string) cfc_config('db_pass', ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (Throwable $e) {
        error_log('CFC database connection failed: ' . $e->getMessage());
        $pdo = null;
        return null;
    }

    $pdo = $conn;
    if (!$booting) {
        $booting = true;
        try {
            $flag = $conn->query("SELECT setting_value FROM cfc_settings WHERE setting_key = 'schema_seeded'")->fetchColumn();
            if ($flag !== '1') {
                cfc_db_seed_if_empty($conn);
            }
        } catch (Throwable $e) {
            try {
                cfc_db_ensure_schema($conn);
                cfc_db_seed_if_empty($conn);
            } catch (Throwable $e2) {
                error_log('CFC database setup failed: ' . $e2->getMessage());
            }
        }
        $booting = false;
    }
    if ($pdo instanceof PDO) {
        cfc_db_ensure_instagram_table($pdo);
    }
    return $pdo;
}

function cfc_db_ready(): bool
{
    return cfc_pdo() instanceof PDO;
}

function cfc_db_schema_sql(): string
{
    return <<<'SQL'
CREATE TABLE IF NOT EXISTS cfc_posts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  path VARCHAR(255) NOT NULL,
  slug VARCHAR(191) NOT NULL,
  date DATE NOT NULL,
  title VARCHAR(500) NOT NULL DEFAULT '',
  excerpt TEXT NULL,
  category VARCHAR(64) NOT NULL DEFAULT '',
  category_name VARCHAR(128) NOT NULL DEFAULT '',
  image VARCHAR(500) NOT NULL DEFAULT '',
  body MEDIUMTEXT NULL,
  file VARCHAR(500) NOT NULL DEFAULT '',
  source VARCHAR(32) NOT NULL DEFAULT 'cms',
  seo_title VARCHAR(500) NOT NULL DEFAULT '',
  seo_description TEXT NULL,
  primary_keyword VARCHAR(255) NOT NULL DEFAULT '',
  secondary_keyword VARCHAR(255) NOT NULL DEFAULT '',
  keywords TEXT NULL,
  seo_robots VARCHAR(32) NOT NULL DEFAULT 'index,follow',
  og_title VARCHAR(500) NOT NULL DEFAULT '',
  og_description TEXT NULL,
  code_head MEDIUMTEXT NULL,
  code_body MEDIUMTEXT NULL,
  code_footer MEDIUMTEXT NULL,
  faqs MEDIUMTEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY path (path),
  KEY slug (slug),
  KEY date_idx (date),
  KEY category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cfc_submissions (
  id VARCHAR(32) NOT NULL,
  created_at DATETIME NOT NULL,
  ip VARCHAR(45) NOT NULL DEFAULT '',
  form_id VARCHAR(32) NOT NULL DEFAULT 'contact',
  source VARCHAR(64) NOT NULL DEFAULT '/contact-us/',
  name VARCHAR(120) NOT NULL DEFAULT '',
  email VARCHAR(200) NOT NULL DEFAULT '',
  mobile VARCHAR(30) NOT NULL DEFAULT '',
  city VARCHAR(80) NOT NULL DEFAULT '',
  message TEXT NULL,
  PRIMARY KEY (id),
  KEY created_at (created_at),
  KEY form_id (form_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cfc_users (
  id CHAR(16) NOT NULL,
  username VARCHAR(32) NOT NULL,
  name VARCHAR(120) NOT NULL DEFAULT '',
  role VARCHAR(16) NOT NULL DEFAULT 'editor',
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  last_login_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cfc_settings (
  setting_key VARCHAR(64) NOT NULL,
  setting_value MEDIUMTEXT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cfc_pages (
  page_key VARCHAR(64) NOT NULL,
  payload MEDIUMTEXT NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (page_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cfc_redirects (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  from_path VARCHAR(255) NOT NULL,
  to_url VARCHAR(1000) NOT NULL,
  code SMALLINT UNSIGNED NOT NULL DEFAULT 301,
  PRIMARY KEY (id),
  UNIQUE KEY from_path (from_path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cfc_store (
  store_key VARCHAR(64) NOT NULL,
  store_value MEDIUMTEXT NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (store_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cfc_sessions (
  id VARCHAR(128) NOT NULL,
  data MEDIUMBLOB NOT NULL,
  expires_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cfc_rate_limits (
  rate_key CHAR(64) NOT NULL,
  hits MEDIUMTEXT NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (rate_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
SQL;
}

function cfc_db_ensure_instagram_table(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS cfc_instagram_posts (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT,
              instagram_media_id VARCHAR(64) NOT NULL,
              media_type VARCHAR(32) NOT NULL DEFAULT \'\',
              media_url TEXT NULL,
              thumbnail_url TEXT NULL,
              permalink VARCHAR(500) NOT NULL DEFAULT \'\',
              caption TEXT NULL,
              alt_text VARCHAR(500) NOT NULL DEFAULT \'\',
              username VARCHAR(64) NOT NULL DEFAULT \'\',
              timestamp DATETIME NULL,
              local_file VARCHAR(255) NOT NULL DEFAULT \'\',
              sort_order INT UNSIGNED NOT NULL DEFAULT 0,
              fetched_at DATETIME NOT NULL,
              PRIMARY KEY (id),
              UNIQUE KEY instagram_media_id (instagram_media_id),
              KEY timestamp_idx (timestamp),
              KEY sort_order (sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    } catch (Throwable $e) {
        $done = false;
        error_log('CFC Instagram table setup failed: ' . $e->getMessage());
    }
}

function cfc_db_ensure_schema(PDO $pdo): void
{
    foreach (preg_split('/;\s*(?=CREATE TABLE|\z)/', cfc_db_schema_sql()) ?: [] as $sql) {
        $sql = trim($sql);
        if ($sql !== '') {
            $pdo->exec($sql);
        }
    }
}

function cfc_db_flag(PDO $pdo, string $key): string
{
    $stmt = $pdo->prepare('SELECT setting_value FROM cfc_settings WHERE setting_key = ?');
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return is_string($val) ? $val : '';
}

function cfc_db_seed_if_empty(PDO $pdo): void
{
    if (cfc_db_flag($pdo, 'schema_seeded') === '1') {
        cfc_db_migrate_json_users($pdo);
        cfc_db_migrate_json_settings($pdo);
        return;
    }
    $posts = (int) $pdo->query('SELECT COUNT(*) FROM cfc_posts')->fetchColumn();
    if ($posts === 0) {
        cfc_db_import_posts_from_files($pdo);
    }
    cfc_db_migrate_json_users($pdo);
    cfc_db_migrate_json_settings($pdo);
    cfc_db_migrate_json_pages($pdo);
    cfc_db_migrate_json_redirects($pdo);
    cfc_db_migrate_json_store($pdo, 'gallery', CFC_DATA . '/gallery.json');
    cfc_db_migrate_json_store($pdo, 'media_hub', CFC_DATA . '/media-hub.json');
    cfc_setting_set('schema_seeded', '1');
}

function cfc_blog_index_from_file(): array
{
    $raw = cfc_read(CFC_DATA . '/blog-index.json');
    $index = $raw ? json_decode($raw, true) : [];
    return is_array($index) ? array_values($index) : [];
}

function cfc_db_post_from_row(array $row): array
{
    $faqs = $row['faqs'] ?? [];
    if (is_string($faqs)) {
        $decoded = json_decode($faqs, true);
        $faqs = is_array($decoded) ? $decoded : [];
    }
    if (!is_array($faqs)) {
        $faqs = [];
    }
    return [
        'path' => (string) ($row['path'] ?? ''),
        'slug' => (string) ($row['slug'] ?? ''),
        'date' => (string) ($row['date'] ?? ''),
        'file' => (string) ($row['file'] ?? ''),
        'source' => (string) ($row['source'] ?? 'cms'),
        'title' => (string) ($row['title'] ?? ''),
        'excerpt' => (string) ($row['excerpt'] ?? ''),
        'category' => (string) ($row['category'] ?? ''),
        'category_name' => (string) ($row['category_name'] ?? ''),
        'image' => (string) ($row['image'] ?? ''),
        'body' => (string) ($row['body'] ?? ''),
        'seo_title' => (string) ($row['seo_title'] ?? ''),
        'seo_description' => (string) ($row['seo_description'] ?? ''),
        'primary_keyword' => (string) ($row['primary_keyword'] ?? ''),
        'secondary_keyword' => (string) ($row['secondary_keyword'] ?? ''),
        'keywords' => (string) ($row['keywords'] ?? ''),
        'seo_robots' => (string) ($row['seo_robots'] ?? 'index,follow'),
        'og_title' => (string) ($row['og_title'] ?? ''),
        'og_description' => (string) ($row['og_description'] ?? ''),
        'code_head' => (string) ($row['code_head'] ?? ''),
        'code_body' => (string) ($row['code_body'] ?? ''),
        'code_footer' => (string) ($row['code_footer'] ?? ''),
        'faqs' => $faqs,
    ];
}

function cfc_db_fetch_posts(): array
{
    $pdo = cfc_pdo();
    if (!$pdo) {
        return [];
    }
    $rows = $pdo->query('SELECT * FROM cfc_posts ORDER BY date DESC, id DESC')->fetchAll();
    $out = [];
    foreach ($rows as $row) {
        if (is_array($row)) {
            $out[] = cfc_db_post_from_row($row);
        }
    }
    return $out;
}

function cfc_db_find_post(string $slugPath): ?array
{
    $pdo = cfc_pdo();
    if (!$pdo) {
        return null;
    }
    $slugPath = trim($slugPath, '/');
    $stmt = $pdo->prepare('SELECT * FROM cfc_posts WHERE path = ? OR slug = ? LIMIT 1');
    $stmt->execute([$slugPath, basename($slugPath)]);
    $row = $stmt->fetch();
    return is_array($row) ? cfc_db_post_from_row($row) : null;
}

function cfc_db_upsert_post(array $entry): bool
{
    $pdo = cfc_pdo();
    if (!$pdo) {
        return false;
    }
    $faqs = $entry['faqs'] ?? [];
    if (!is_array($faqs)) {
        $faqs = [];
    }
    $faqsJson = json_encode($faqs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
    $date = (string) ($entry['date'] ?? date('Y-m-d'));
    $created = $date . ' 00:00:00';
    $now = date('Y-m-d H:i:s');
    $sql = 'INSERT INTO cfc_posts (
        path, slug, date, title, excerpt, category, category_name, image, body, file, source,
        seo_title, seo_description, primary_keyword, secondary_keyword, keywords, seo_robots,
        og_title, og_description, code_head, code_body, code_footer, faqs, created_at, updated_at
    ) VALUES (
        :path, :slug, :date, :title, :excerpt, :category, :category_name, :image, :body, :file, :source,
        :seo_title, :seo_description, :primary_keyword, :secondary_keyword, :keywords, :seo_robots,
        :og_title, :og_description, :code_head, :code_body, :code_footer, :faqs, :created_at, :updated_at
    ) ON DUPLICATE KEY UPDATE
        slug = VALUES(slug),
        date = VALUES(date),
        title = VALUES(title),
        excerpt = VALUES(excerpt),
        category = VALUES(category),
        category_name = VALUES(category_name),
        image = VALUES(image),
        body = VALUES(body),
        file = VALUES(file),
        source = VALUES(source),
        seo_title = VALUES(seo_title),
        seo_description = VALUES(seo_description),
        primary_keyword = VALUES(primary_keyword),
        secondary_keyword = VALUES(secondary_keyword),
        keywords = VALUES(keywords),
        seo_robots = VALUES(seo_robots),
        og_title = VALUES(og_title),
        og_description = VALUES(og_description),
        code_head = VALUES(code_head),
        code_body = VALUES(code_body),
        code_footer = VALUES(code_footer),
        faqs = VALUES(faqs),
        updated_at = VALUES(updated_at)';
    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':path' => (string) ($entry['path'] ?? ''),
            ':slug' => (string) ($entry['slug'] ?? ''),
            ':date' => $date,
            ':title' => (string) ($entry['title'] ?? ''),
            ':excerpt' => (string) ($entry['excerpt'] ?? ''),
            ':category' => (string) ($entry['category'] ?? ''),
            ':category_name' => (string) ($entry['category_name'] ?? ''),
            ':image' => (string) ($entry['image'] ?? ''),
            ':body' => (string) ($entry['body'] ?? ''),
            ':file' => (string) ($entry['file'] ?? ''),
            ':source' => (string) ($entry['source'] ?? 'cms'),
            ':seo_title' => (string) ($entry['seo_title'] ?? ''),
            ':seo_description' => (string) ($entry['seo_description'] ?? ''),
            ':primary_keyword' => (string) ($entry['primary_keyword'] ?? ''),
            ':secondary_keyword' => (string) ($entry['secondary_keyword'] ?? ''),
            ':keywords' => (string) ($entry['keywords'] ?? ''),
            ':seo_robots' => (string) ($entry['seo_robots'] ?? 'index,follow'),
            ':og_title' => (string) ($entry['og_title'] ?? ''),
            ':og_description' => (string) ($entry['og_description'] ?? ''),
            ':code_head' => (string) ($entry['code_head'] ?? ''),
            ':code_body' => (string) ($entry['code_body'] ?? ''),
            ':code_footer' => (string) ($entry['code_footer'] ?? ''),
            ':faqs' => $faqsJson,
            ':created_at' => $created,
            ':updated_at' => $now,
        ]);
    } catch (Throwable $e) {
        error_log('CFC post save failed: ' . $e->getMessage());
        return false;
    }
}

function cfc_db_delete_post(string $path): bool
{
    $pdo = cfc_pdo();
    if (!$pdo) {
        return false;
    }
    $path = trim($path, '/');
    $stmt = $pdo->prepare('DELETE FROM cfc_posts WHERE path = ? OR slug = ?');
    $stmt->execute([$path, $path]);
    return $stmt->rowCount() > 0;
}

function cfc_db_import_posts_from_files(?PDO $pdo = null): int
{
    $pdo = $pdo ?? cfc_pdo();
    if (!$pdo) {
        return 0;
    }
    $n = 0;
    foreach (cfc_blog_index_from_file() as $post) {
        if (!is_array($post) || empty($post['path'])) {
            continue;
        }
        $body = '';
        $file = '';
        if (!empty($post['file'])) {
            $file = (string) $post['file'];
            $abs = CFC_ROOT . '/' . ltrim($file, '/');
            if (is_file($abs)) {
                $body = function_exists('cfc_extract_post_html')
                    ? cfc_extract_post_html((string) file_get_contents($abs))
                    : (string) file_get_contents($abs);
            }
        }
        $post['body'] = $body;
        $post['file'] = $file;
        if (cfc_db_upsert_post($post)) {
            $n++;
        }
    }
    return $n;
}

function cfc_db_insert_submission(array $record): bool
{
    $pdo = cfc_pdo();
    if (!$pdo) {
        return false;
    }
    $created = (string) ($record['created_at'] ?? date('c'));
    $ts = strtotime($created);
    $dt = $ts ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');
    try {
        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO cfc_submissions
            (id, created_at, ip, form_id, source, name, email, mobile, city, message)
            VALUES (:id, :created_at, :ip, :form_id, :source, :name, :email, :mobile, :city, :message)'
        );
        return $stmt->execute([
            ':id' => (string) ($record['id'] ?? bin2hex(random_bytes(8))),
            ':created_at' => $dt,
            ':ip' => cfc_clip((string) ($record['ip'] ?? ''), 45),
            ':form_id' => cfc_clip((string) ($record['form_id'] ?? 'contact'), 32),
            ':source' => cfc_clip((string) ($record['source'] ?? '/contact-us/'), 64),
            ':name' => cfc_clip((string) ($record['name'] ?? ''), 120),
            ':email' => cfc_clip((string) ($record['email'] ?? ''), 200),
            ':mobile' => cfc_clip((string) ($record['mobile'] ?? ''), 30),
            ':city' => cfc_clip((string) ($record['city'] ?? ''), 80),
            ':message' => (string) ($record['message'] ?? ''),
        ]);
    } catch (Throwable $e) {
        error_log('CFC submission save failed: ' . $e->getMessage());
        return false;
    }
}

function cfc_db_import_submissions_from_files(?PDO $pdo = null): int
{
    $pdo = $pdo ?? cfc_pdo();
    if (!$pdo) {
        return 0;
    }
    $dir = CFC_DATA . '/submissions';
    if (!is_dir($dir)) {
        return 0;
    }
    $n = 0;
    foreach (glob($dir . '/*.json') ?: [] as $file) {
        $row = json_decode((string) file_get_contents($file), true);
        if (!is_array($row) || empty($row['id'])) {
            continue;
        }
        if (cfc_db_insert_submission($row)) {
            $n++;
        }
    }
    return $n;
}

function cfc_submissions_list(): array
{
    $pdo = cfc_pdo();
    if ($pdo) {
        $rows = $pdo->query('SELECT * FROM cfc_submissions ORDER BY created_at DESC, id DESC')->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $out[] = $row;
            }
        }
        return $out;
    }
    $dir = CFC_DATA . '/submissions';
    $files = is_dir($dir) ? (glob($dir . '/*.json') ?: []) : [];
    if ($files !== []) {
        rsort($files);
    }
    $out = [];
    foreach ($files as $file) {
        $row = json_decode((string) file_get_contents($file), true);
        if (is_array($row)) {
            $out[] = $row;
        }
    }
    return $out;
}

function cfc_submissions_count(): int
{
    $pdo = cfc_pdo();
    if ($pdo) {
        return (int) $pdo->query('SELECT COUNT(*) FROM cfc_submissions')->fetchColumn();
    }
    $dir = CFC_DATA . '/submissions';
    if (!is_dir($dir)) {
        return 0;
    }
    return count(glob($dir . '/*.json') ?: []);
}

function cfc_db_dt(?string $value): string
{
    if ($value === null || $value === '') {
        return date('Y-m-d H:i:s');
    }
    $ts = strtotime($value);
    return $ts ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');
}

function cfc_db_users_list(): array
{
    $pdo = cfc_pdo();
    if (!$pdo) {
        return [];
    }
    $rows = $pdo->query(
        'SELECT id, username, name, role, password_hash, created_at, updated_at, last_login_at
         FROM cfc_users ORDER BY created_at ASC, username ASC'
    )->fetchAll();
    $out = [];
    foreach ($rows as $row) {
        if (is_array($row)) {
            $out[] = $row;
        }
    }
    return $out;
}

function cfc_db_user_upsert(array $user): bool
{
    $pdo = cfc_pdo();
    if (!$pdo) {
        return false;
    }
    $id = (string) ($user['id'] ?? '');
    if ($id === '' || !preg_match('/^[a-f0-9]{16}$/i', $id)) {
        $id = bin2hex(random_bytes(8));
    }
    $hash = (string) ($user['password_hash'] ?? '');
    if ($hash === '' || (int) (password_get_info($hash)['algo'] ?? 0) === 0) {
        return false;
    }
    $sql = 'INSERT INTO cfc_users
        (id, username, name, role, password_hash, created_at, updated_at, last_login_at)
        VALUES
        (:id, :username, :name, :role, :password_hash, :created_at, :updated_at, :last_login_at)
        ON DUPLICATE KEY UPDATE
        username = VALUES(username),
        name = VALUES(name),
        role = VALUES(role),
        password_hash = VALUES(password_hash),
        updated_at = VALUES(updated_at),
        last_login_at = IF(VALUES(last_login_at) IS NULL, last_login_at, VALUES(last_login_at))';
    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id' => strtolower($id),
            ':username' => cfc_clip((string) ($user['username'] ?? ''), 32),
            ':name' => cfc_clip((string) ($user['name'] ?? ''), 120),
            ':role' => (($user['role'] ?? '') === 'admin') ? 'admin' : 'editor',
            ':password_hash' => $hash,
            ':created_at' => cfc_db_dt(isset($user['created_at']) ? (string) $user['created_at'] : null),
            ':updated_at' => cfc_db_dt(isset($user['updated_at']) ? (string) $user['updated_at'] : date('c')),
            ':last_login_at' => !empty($user['last_login_at']) ? cfc_db_dt((string) $user['last_login_at']) : null,
        ]);
    } catch (Throwable $e) {
        error_log('CFC user save failed: ' . $e->getMessage());
        return false;
    }
}

function cfc_db_user_delete(string $id): bool
{
    $pdo = cfc_pdo();
    if (!$pdo) {
        return false;
    }
    $stmt = $pdo->prepare('DELETE FROM cfc_users WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->rowCount() > 0;
}

function cfc_db_user_touch_login(string $id, ?string $rehash = null): void
{
    $pdo = cfc_pdo();
    if (!$pdo) {
        return;
    }
    try {
        if ($rehash !== null && (int) (password_get_info($rehash)['algo'] ?? 0) !== 0) {
            $stmt = $pdo->prepare('UPDATE cfc_users SET last_login_at = ?, password_hash = ?, updated_at = ? WHERE id = ?');
            $stmt->execute([date('Y-m-d H:i:s'), $rehash, date('Y-m-d H:i:s'), $id]);
            return;
        }
        $stmt = $pdo->prepare('UPDATE cfc_users SET last_login_at = ? WHERE id = ?');
        $stmt->execute([date('Y-m-d H:i:s'), $id]);
    } catch (Throwable $e) {
        error_log('CFC login stamp failed: ' . $e->getMessage());
    }
}

function cfc_db_migrate_json_users(PDO $pdo): void
{
    $file = CFC_DATA . '/cms/users.json';
    if (!is_file($file)) {
        return;
    }
    $count = (int) $pdo->query('SELECT COUNT(*) FROM cfc_users')->fetchColumn();
    if ($count === 0) {
        $decoded = json_decode((string) file_get_contents($file), true);
        if (is_array($decoded)) {
            foreach ($decoded as $row) {
                if (!is_array($row) || empty($row['username']) || empty($row['password_hash'])) {
                    continue;
                }
                cfc_db_user_upsert($row);
            }
        }
    }
    if ((int) $pdo->query('SELECT COUNT(*) FROM cfc_users')->fetchColumn() > 0) {
        @unlink($file);
    }
}

function cfc_setting_get(string $key): ?string
{
    $pdo = cfc_pdo();
    if (!$pdo || !preg_match('/^[a-z0-9_]{1,64}$/', $key)) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT setting_value FROM cfc_settings WHERE setting_key = ?');
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return is_string($val) ? $val : null;
}

function cfc_setting_set(string $key, string $value): bool
{
    $pdo = cfc_pdo();
    if (!$pdo || !preg_match('/^[a-z0-9_]{1,64}$/', $key)) {
        return false;
    }
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO cfc_settings (setting_key, setting_value, updated_at)
             VALUES (:k, :v, :t)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at)'
        );
        return $stmt->execute([
            ':k' => $key,
            ':v' => $value,
            ':t' => date('Y-m-d H:i:s'),
        ]);
    } catch (Throwable $e) {
        error_log('CFC setting save failed: ' . $e->getMessage());
        return false;
    }
}

function cfc_db_migrate_json_settings(PDO $pdo): void
{
    $file = CFC_DATA . '/cms/secrets.json';
    if (!is_file($file)) {
        return;
    }
    $decoded = json_decode((string) file_get_contents($file), true);
    if (is_array($decoded)) {
        foreach (['turnstile_site_key', 'turnstile_secret'] as $key) {
            if (!isset($decoded[$key]) || !is_string($decoded[$key])) {
                continue;
            }
            $existing = cfc_setting_get($key);
            if ($existing === null || $existing === '') {
                cfc_setting_set($key, $decoded[$key]);
            }
        }
    }
    @unlink($file);
}

function cfc_settings_apply(): void
{
    if (!cfc_db_ready()) {
        return;
    }
    foreach (['turnstile_site_key', 'turnstile_secret'] as $key) {
        $val = cfc_setting_get($key);
        if ($val !== null && $val !== '') {
            $GLOBALS['cfc_config'][$key] = $val;
        }
    }
}

function cfc_db_json_encode(array $data): string
{
    return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?: '[]';
}

function cfc_db_pages_load(): array
{
    $pdo = cfc_pdo();
    if (!$pdo) {
        return [];
    }
    $rows = $pdo->query('SELECT page_key, payload FROM cfc_pages')->fetchAll();
    $store = [];
    foreach ($rows as $row) {
        if (!is_array($row) || empty($row['page_key'])) {
            continue;
        }
        $decoded = json_decode((string) ($row['payload'] ?? ''), true);
        if (is_array($decoded)) {
            $store[(string) $row['page_key']] = $decoded;
        }
    }
    return $store;
}

function cfc_db_pages_save(array $store): bool
{
    $pdo = cfc_pdo();
    if (!$pdo) {
        return false;
    }
    try {
        $pdo->beginTransaction();
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare(
            'INSERT INTO cfc_pages (page_key, payload, updated_at) VALUES (:k, :p, :t)
             ON DUPLICATE KEY UPDATE payload = VALUES(payload), updated_at = VALUES(updated_at)'
        );
        $keep = [];
        foreach ($store as $key => $payload) {
            $key = (string) $key;
            if (!preg_match('/^[a-z0-9_-]{1,64}$/', $key) || !is_array($payload)) {
                continue;
            }
            $keep[] = $key;
            $stmt->execute([':k' => $key, ':p' => cfc_db_json_encode($payload), ':t' => $now]);
        }
        $existing = $pdo->query('SELECT page_key FROM cfc_pages')->fetchAll(PDO::FETCH_COLUMN);
        if (is_array($existing)) {
            $del = $pdo->prepare('DELETE FROM cfc_pages WHERE page_key = ?');
            foreach ($existing as $key) {
                if (!in_array((string) $key, $keep, true)) {
                    $del->execute([(string) $key]);
                }
            }
        }
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('CFC page save failed: ' . $e->getMessage());
        return false;
    }
}

function cfc_db_migrate_json_pages(PDO $pdo): void
{
    $n = (int) $pdo->query('SELECT COUNT(*) FROM cfc_pages')->fetchColumn();
    if ($n > 0) {
        return;
    }
    $file = CFC_DATA . '/cms/content.json';
    if (!is_file($file)) {
        return;
    }
    $decoded = json_decode((string) file_get_contents($file), true);
    if (is_array($decoded) && $decoded !== []) {
        cfc_db_pages_save($decoded);
    }
}

function cfc_store_row(string $key): ?array
{
    if (!preg_match('/^[a-z0-9_]{1,64}$/', $key)) {
        return null;
    }
    $pdo = cfc_pdo();
    if (!$pdo) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT store_value, updated_at FROM cfc_store WHERE store_key = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if (!is_array($row)) {
        return null;
    }
    $decoded = json_decode(is_string($row['store_value'] ?? '') ? (string) $row['store_value'] : '', true);
    return [
        'value' => is_array($decoded) ? $decoded : [],
        'updated_at' => (string) ($row['updated_at'] ?? ''),
    ];
}

function cfc_store_get(string $key): ?array
{
    $row = cfc_store_row($key);
    return $row === null ? null : $row['value'];
}

function cfc_store_set(string $key, array $value): bool
{
    $pdo = cfc_pdo();
    if (!$pdo || !preg_match('/^[a-z0-9_]{1,64}$/', $key)) {
        return false;
    }
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO cfc_store (store_key, store_value, updated_at) VALUES (:k, :v, :t)
             ON DUPLICATE KEY UPDATE store_value = VALUES(store_value), updated_at = VALUES(updated_at)'
        );
        return $stmt->execute([
            ':k' => $key,
            ':v' => cfc_db_json_encode($value),
            ':t' => date('Y-m-d H:i:s'),
        ]);
    } catch (Throwable $e) {
        error_log('CFC store save failed: ' . $e->getMessage());
        return false;
    }
}

function cfc_db_migrate_json_store(PDO $pdo, string $key, string $file): void
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM cfc_store WHERE store_key = ?');
    $stmt->execute([$key]);
    if ((int) $stmt->fetchColumn() > 0) {
        return;
    }
    if (!is_file($file)) {
        return;
    }
    $decoded = json_decode((string) file_get_contents($file), true);
    if (is_array($decoded)) {
        cfc_store_set($key, $decoded);
    }
}

function cfc_db_redirects_load(): array
{
    $pdo = cfc_pdo();
    if (!$pdo) {
        return [];
    }
    $rows = $pdo->query('SELECT from_path, to_url, code FROM cfc_redirects ORDER BY id ASC')->fetchAll();
    $out = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $out[] = [
            'from' => (string) ($row['from_path'] ?? ''),
            'to' => (string) ($row['to_url'] ?? ''),
            'code' => (int) ($row['code'] ?? 301),
        ];
    }
    return $out;
}

function cfc_db_redirects_save(array $rows): bool
{
    $pdo = cfc_pdo();
    if (!$pdo) {
        return false;
    }
    try {
        $pdo->beginTransaction();
        $pdo->exec('DELETE FROM cfc_redirects');
        $stmt = $pdo->prepare('INSERT INTO cfc_redirects (from_path, to_url, code) VALUES (?, ?, ?)');
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $stmt->execute([
                (string) ($row['from'] ?? ''),
                (string) ($row['to'] ?? ''),
                (int) ($row['code'] ?? 301) === 302 ? 302 : 301,
            ]);
        }
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('CFC redirects save failed: ' . $e->getMessage());
        return false;
    }
}

function cfc_db_migrate_json_redirects(PDO $pdo): void
{
    $n = (int) $pdo->query('SELECT COUNT(*) FROM cfc_redirects')->fetchColumn();
    if ($n > 0) {
        return;
    }
    $file = CFC_DATA . '/cms/redirects.json';
    $rows = [];
    if (is_file($file)) {
        $decoded = json_decode((string) file_get_contents($file), true);
        if (is_array($decoded)) {
            $rows = $decoded;
        }
    }
    if ($rows === []) {
        $rows = [
            ['from' => '/product-category/lemon-tea/', 'to' => 'https://www.andaalhomefoods.com/collections/coffee/products/lemon-tea?variant=42499446767706', 'code' => 301],
            ['from' => '/product-category/coffee-powder/', 'to' => 'https://www.andaalhomefoods.com/products/coffee-powder?variant=42499446243418', 'code' => 301],
            ['from' => '/product-category/honey/', 'to' => 'https://www.andaalhomefoods.com/products/organic-honey?variant=42121826762842', 'code' => 301],
        ];
    }
    if ($rows !== []) {
        cfc_db_redirects_save($rows);
    }
}

function cfc_rate_limit_hit(string $ip, int $limit, int $window): bool
{
    $now = time();
    $key = hash('sha256', $ip);
    $pdo = cfc_pdo();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare('SELECT hits FROM cfc_rate_limits WHERE rate_key = ?');
            $stmt->execute([$key]);
            $raw = $stmt->fetchColumn();
            $hits = is_string($raw) ? json_decode($raw, true) : [];
            $hits = is_array($hits)
                ? array_values(array_filter($hits, static fn($t) => is_int($t) && $t > $now - $window))
                : [];
            if (count($hits) >= $limit) {
                $up = $pdo->prepare('UPDATE cfc_rate_limits SET hits = ?, updated_at = ? WHERE rate_key = ?');
                $up->execute([json_encode($hits), date('Y-m-d H:i:s'), $key]);
                return false;
            }
            $hits[] = $now;
            $stmt = $pdo->prepare(
                'INSERT INTO cfc_rate_limits (rate_key, hits, updated_at) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE hits = VALUES(hits), updated_at = VALUES(updated_at)'
            );
            $stmt->execute([$key, json_encode($hits), date('Y-m-d H:i:s')]);
            if (random_int(1, 40) === 1) {
                $pdo->exec("DELETE FROM cfc_rate_limits WHERE updated_at < DATE_SUB(NOW(), INTERVAL 2 DAY)");
            }
            return true;
        } catch (Throwable $e) {
            error_log('CFC rate limit failed: ' . $e->getMessage());
        }
    }
    $dir = CFC_DATA . '/submissions';
    if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
        return true;
    }
    $file = $dir . '/.rate-' . $key;
    $raw = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
    $hits = is_array($raw)
        ? array_values(array_filter($raw, static fn($t) => is_int($t) && $t > $now - $window))
        : [];
    if (count($hits) >= $limit) {
        @file_put_contents($file, json_encode($hits), LOCK_EX);
        return false;
    }
    $hits[] = $now;
    @file_put_contents($file, json_encode($hits), LOCK_EX);
    @chmod($file, 0600);
    return true;
}

function cfc_session_register(): bool
{
    if (!cfc_db_ready() || PHP_SAPI === 'cli') {
        return false;
    }
    $handler = new class implements SessionHandlerInterface {
        public function open(string $path, string $name): bool
        {
            return true;
        }

        public function close(): bool
        {
            return true;
        }

        public function read(string $id): string|false
        {
            if (!preg_match('/^[A-Za-z0-9,-]{16,128}$/', $id)) {
                return '';
            }
            $pdo = cfc_pdo();
            if (!$pdo) {
                return '';
            }
            try {
                $stmt = $pdo->prepare('SELECT data FROM cfc_sessions WHERE id = ? AND expires_at > ?');
                $stmt->execute([$id, date('Y-m-d H:i:s')]);
                $data = $stmt->fetchColumn();
                return is_string($data) ? $data : '';
            } catch (Throwable $e) {
                error_log('CFC session read failed: ' . $e->getMessage());
                return '';
            }
        }

        public function write(string $id, string $data): bool
        {
            if (!preg_match('/^[A-Za-z0-9,-]{16,128}$/', $id)) {
                return false;
            }
            $pdo = cfc_pdo();
            if (!$pdo) {
                return false;
            }
            $life = (int) ini_get('session.gc_maxlifetime');
            if ($life < 60) {
                $life = 14400;
            }
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO cfc_sessions (id, data, expires_at) VALUES (:id, :data, :exp)
                     ON DUPLICATE KEY UPDATE data = VALUES(data), expires_at = VALUES(expires_at)'
                );
                return $stmt->execute([
                    ':id' => $id,
                    ':data' => $data,
                    ':exp' => date('Y-m-d H:i:s', time() + $life),
                ]);
            } catch (Throwable $e) {
                error_log('CFC session write failed: ' . $e->getMessage());
                return false;
            }
        }

        public function destroy(string $id): bool
        {
            if (!preg_match('/^[A-Za-z0-9,-]{16,128}$/', $id)) {
                return true;
            }
            $pdo = cfc_pdo();
            if (!$pdo) {
                return true;
            }
            try {
                $stmt = $pdo->prepare('DELETE FROM cfc_sessions WHERE id = ?');
                $stmt->execute([$id]);
                return true;
            } catch (Throwable $e) {
                return false;
            }
        }

        public function gc(int $max_lifetime): int|false
        {
            $pdo = cfc_pdo();
            if (!$pdo) {
                return 0;
            }
            try {
                $stmt = $pdo->prepare('DELETE FROM cfc_sessions WHERE expires_at < ?');
                $stmt->execute([date('Y-m-d H:i:s')]);
                return $stmt->rowCount();
            } catch (Throwable $e) {
                return 0;
            }
        }
    };
    return session_set_save_handler($handler, true);
}
