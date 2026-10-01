# Deploy to cPanel

Custom PHP site. No WordPress, Node, or Composer on the server.

## Requirements

- PHP **8.1, 8.2, or 8.3** (MultiPHP Manager)
- Apache `mod_rewrite` (default on cPanel)
- `AllowOverride All` for `public_html` (default)
- PHP extensions: `json`, `fileinfo`, `session`, `filter` (standard). `pdo_mysql` for blog and forms. `curl`, `openssl`, `gd`, `mbstring` recommended.

Do not use PHP 7.4 or 8.0. The site will refuse to boot.

## 1. Backup

If WordPress is still in `public_html`, download a full backup (files + database) first. Then remove the WordPress files so they cannot be mixed with this site. Leave `public_html` empty except for cPanel defaults you want to keep.

## 2. PHP version

cPanel → **MultiPHP Manager** → this domain → **PHP 8.2** (or 8.1 / 8.3) → Apply.

Optional: MultiPHP INI Editor → match `.user.ini`:

- `display_errors` = Off
- `upload_max_filesize` = 16M
- `post_max_size` = 24M
- `max_input_vars` = 5000
- `memory_limit` = 128M

Do **not** add `php_flag` / `php_value` lines to `.htaccess`. That causes HTTP 500 on many PHP-FPM vhosts.

## 3. Deploy

The site must live at the document root (`public_html` for the primary domain, or the addon-domain folder), not in a subfolder.

### Git Version Control

This is how the site deploys. cPanel clones the repository and copies it into the document root using `.cpanel.yml` at the repository root. **Shell access is not required** — deployment works even on accounts where SSH is disabled.

First time only:

1. cPanel → **Files → Git™ Version Control** → **Create**
2. Turn on **Clone a Repository**
3. Clone URL: `https://github.com/kasturideepak-dev/cfc.git`
4. Repository Path: `repositories/cfc` — **not** `public_html`. cPanel refuses to clone into a directory that already contains files.
5. **Create**, then wait. The clone is roughly 490 MB, nearly all of it `assets/`.

Every release after that:

1. Push to `main`
2. cPanel → Git™ Version Control → **Manage → Pull or Deploy**
3. **Update from Remote**, then check the HEAD Commit hash matches what you pushed
4. **Deploy HEAD Commit**

Always update before deploying, or you redeploy the previous commit.

`.cpanel.yml` sets `DEPLOYPATH` to the document root. Check the Repository Path cPanel displays and match its home partition: this account is under `/home1`, not `/home`.

What a deploy deliberately leaves alone:

| Untouched | Why |
| --- | --- |
| `data/` | Live sessions, customer enquiries and CMS state. `data/media-hub.json` is compared against the database by modification time, so a freshly copied file always looks newer and would overwrite the live Media Hub tiles. |
| `.htaccess` | The server's copy carries the clean-URL rules. |
| `config/config.local.php` | Production credentials. Not in the repository, never written. |
| `assets/uploads/cms/`, `assets/instagram/` | CMS uploads and cached Instagram covers. No deploy task deletes anything. |

### Manual alternatives

Only if Git Version Control is unavailable.

1. **SFTP / FileZilla** — practical for the ~280 MB of assets.
2. **cPanel File Manager** → upload a zip → Extract → **then delete the zip**. An archive left in the document root is publicly downloadable by anyone.
3. **rsync**, if the host has enabled shell access:

```bash
rsync -av \
  --exclude '.git/' --exclude 'data/' --exclude 'docs/' \
  --exclude 'config/config.local.php' \
  --exclude 'assets/uploads/' --exclude 'assets/instagram/' \
  ./ chennuih@chennapatnamfiltercoffee.com:~/public_html/
```

No `--delete`: the excluded directories hold live server state that must survive.

## 4. Production config

In File Manager, copy `config/config.local.php.example` → `config/config.local.php`.

Edit it:

```php
return [
    'debug' => false,
    'public_origin' => 'https://chennapatnamfiltercoffee.com',
    'canonical_host' => 'chennapatnamfiltercoffee.com',
    'base_path' => '',
    'force_https' => true,
    'setup_key' => 'paste-16-plus-random-characters',
    'mail_enabled' => true,
    'mail_to' => 'chennapatnamfiltercoffee@gmail.com',
    'mail_from' => 'chennapatnamfiltercoffee@gmail.com',
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',
    'smtp_user' => 'chennapatnamfiltercoffee@gmail.com',
    'smtp_pass' => 'the-16-character-app-password',
    'db_host' => 'localhost',
    'db_name' => 'cpaneluser_cfc',
    'db_user' => 'cpaneluser_cfc',
    'db_pass' => 'the-password-from-mysql-databases',
];
```

Generate a setup key:

```bash
php -r "echo bin2hex(random_bytes(16)), PHP_EOL;"
```

Gmail SMTP (PHPMailer) is used for form enquiries. Do **not** use the normal Gmail password.

1. Open the Gmail account (the one in `smtp_user`).
2. Google Account → **Security** → turn on **2-Step Verification**.
3. **App passwords** → app = Mail, device = Other (“CFC site”) → generate.
4. Paste that 16-character password into `smtp_pass` (spaces are stripped).
5. Keep `smtp_user` and `mail_from` as that same Gmail address.

The enquiry is still saved in MySQL if SMTP fails. Check `data/php-error.log` for `CFC SMTP failed`.

## 5. MySQL (all site data)

cPanel → **MySQL Databases**:

1. Create a database (example `account_cfc`).
2. Create a user with a strong password.
3. Add the user to the database with **ALL PRIVILEGES**.
4. phpMyAdmin → select that database → Import → `sql/cfc.sql`.

Then put `db_host` (`localhost`), `db_name`, `db_user`, and `db_pass` in `config.local.php`.

| Table | Stores |
|-------|--------|
| `cfc_pages` | Page copy, SEO, FAQs (home, about, menu, …) |
| `cfc_posts` | Blog posts (title, SEO, HTML body) |
| `cfc_store` | Gallery sections and Media Hub tiles |
| `cfc_redirects` | URL redirects |
| `cfc_submissions` | Contact / franchise / landing form enquiries |
| `cfc_users` | CMS logins (hashed passwords, never JSON or plaintext) |
| `cfc_settings` | Captcha / API secrets |
| `cfc_sessions` | PHP login sessions |
| `cfc_rate_limits` | Form anti-spam counters |

`sql/cfc.sql` includes pages, posts, gallery, media hub, and redirects. It does **not** include users, secrets, form inbox, or sessions.

If tables are empty on first load, the site imports from the JSON/HTML files once. To re-run:

```bash
php scripts/import-mysql.php
```

Stay on disk: images, videos, fonts, PDFs (`assets/`), and `config/config.local.php` (database password, setup key). Those do not belong in MySQL.

## 6. Permissions

Owner = the cPanel user. Typical:

| Path | Mode |
|------|------|
| Folders | 755 |
| Files | 644 |
| `data/` `data/cms/` `data/submissions/` `data/sessions/` | 755 and writable |
| `assets/uploads/cms/` | 755 and writable |
| `config/config.local.php` | 600 |

Do not use 777.

## 7. SSL

cPanel → **SSL/TLS Status** → Run AutoSSL for the domain. Force HTTPS is already in `.htaccess` (skipped for localhost and raw IPs). `force_https` + `canonical_host` in `config.local.php` send www → apex on https.

## 8. Create the admin

Open `https://your-domain/admin/`.

Enter the setup key, a username, and a password (12+ characters). That account is stored in MySQL `cfc_users` as a one-way password hash. Do not keep passwords in JSON.

With SSH:

```bash
php scripts/create-admin.php youruser 'a-long-random-password'
```

## 9. Check

SSH:

```bash
php scripts/check-server.php
```

Or CMS → Settings → Server.

Visit:

- `https://chennapatnamfiltercoffee.com/`
- `/about-us/` `/menu/` `/shop/` `/franchise/` `/blog/` `/gallery/` `/media-hub/` `/contact-us/`
- `/admin/`
- Submit a test form (inbox at CMS → Submissions)

Shop “Shop now” still goes to `https://www.andaalhomefoods.com/`.

## 10. Instagram feed cron

After connecting the professional account in CMS → Settings → Instagram Feed:

```cron
*/30 * * * * /usr/bin/php /home1/chennuih/public_html/cron/sync-instagram.php >/dev/null 2>&1
```

Full Meta app + token steps: [instagram.md](instagram.md). Public pages never call Instagram; cron writes `cfc_instagram_posts`.

## 11. After go-live

- Confirm `debug` is false
- Turn on Cloudflare Turnstile in CMS → Captcha
- Connect Instagram Feed and add the cron above
- Point DNS A record at the cPanel server
- Keep `data/php-error.log` private (already blocked by `.htaccess`)

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| HTTP 500, “requires PHP 8.1” | MultiPHP Manager → 8.1+ |
| HTTP 500 after edit to `.htaccess` | Remove any `php_flag` / `php_value` lines |
| Pretty URLs 404 | `mod_rewrite` on; if the site is in a subfolder, add `RewriteBase /folder/` (it should not be in a subfolder) |
| CMS cannot save | `data/cms` and `assets/uploads/cms` writable by the cPanel user |
| No first-admin form | `setup_key` still the example string, or too short |
| Enquiry saved, no email | Set `smtp_user` + Gmail **App Password** in `smtp_pass`. 2-Step Verification must be on |
| Blog empty after import | Confirm `cfc_posts` has rows; run `php scripts/import-mysql.php` |
| Forms not in phpMyAdmin | Check `cfc_submissions` and `db_*` in config.local.php |
| Cannot create admin / login | MySQL must be connected. Passwords are not stored in JSON |
| Mixed content / wrong host | Set `public_origin` and `canonical_host` |
| Session logout on every click | `data/sessions` must be writable |

Local MAMP is unchanged: `http://localhost:8888/cfc-site/` with `config.local.php` `'debug' => true`.
