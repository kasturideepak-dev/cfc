# Chennapatnam Filter Coffee — custom PHP site

Pixel-faithful rebuild of [chennapatnamfiltercoffee.com](https://chennapatnamfiltercoffee.com/) as standalone PHP. No WordPress, Elementor, or Node runtime.

## Local (MAMP)

Apache is expected on port **8888**.

```text
http://localhost:8888/cfc-site/
```

The project should be linked into MAMP:

```bash
ln -sfn /Users/deepak/Desktop/Brackvector/cfc-site /Applications/MAMP/htdocs/cfc-site
```

Confirm `AllowOverride All` for `htdocs` so `.htaccess` clean URLs work.

Copy `config/config.local.php.example` to `config/config.local.php` and set `'debug' => true` for local error display.

Admin: `http://localhost:8888/cfc-site/admin/`

CMS logins require MySQL (`cfc_users`). Passwords are hashed; they are never stored in JSON. Point `config.local.php` at MAMP MySQL, then create the first admin at `/admin/`.

## cPanel production

Requires PHP 8.1+. Upload the site to the domain document root (not a subfolder), copy `config.local.php.example` → `config.local.php`, set `setup_key`, then create the admin at `/admin/`.

Full steps: [docs/cpanel.md](docs/cpanel.md)

```bash
bash scripts/package-cpanel.sh   # writes dist/cfc-cpanel.zip
php scripts/check-server.php
php scripts/create-admin.php youruser 'a-long-random-password'
php scripts/import-mysql.php   # after MySQL credentials are set
```

MySQL dump for phpMyAdmin: `sql/cfc.sql` (pages, blog, gallery, media hub, redirects). CMS users and form inbox are created on the server, not in the dump.

## What runs

| Layer | Implementation |
|--------|----------------|
| Pages | PHP views in `pages/` |
| Layout | `includes/header.php`, `footer.php`, `layout.php` |
| Assets | `assets/` (images, videos, fonts, PDFs) |
| Routes | `index.php` + `.htaccess` matching live slugs |
| Forms | PHP + CSRF → MySQL `cfc_submissions` |
| Blog | MySQL `cfc_posts` |
| CMS copy | MySQL `cfc_pages` |
| Gallery / Media Hub | MySQL `cfc_store` |
| Admin | `/admin/` (users in MySQL `cfc_users`) |

## URL map

| Live | Local |
|------|--------|
| `/` | `/cfc-site/` |
| `/about-us/` | `/cfc-site/about-us/` |
| `/menu/` | `/cfc-site/menu/` |
| `/shop/` | `/cfc-site/shop/` |
| `/franchise/` | `/cfc-site/franchise/` |
| `/blog/` | `/cfc-site/blog/` |
| `/YYYY/MM/DD/slug/` | `/cfc-site/YYYY/MM/DD/slug/` |
| `/gallery/` | `/cfc-site/gallery/` |
| `/media-hub/` | `/cfc-site/media-hub/` |
| `/contact-us/` | `/cfc-site/contact-us/` |
| `/landing/` | `/cfc-site/landing/` |
| `/thank-you/` | `/cfc-site/thank-you/` |
| `/privacy-policy/` | `/cfc-site/privacy-policy/` |
| `/terms-and-conditions/` | `/cfc-site/terms-and-conditions/` |
| `/?s=` | `/cfc-site/?s=` |

Shop CTA still goes to `https://www.andaalhomefoods.com/`. WooCommerce is not rebuilt.
