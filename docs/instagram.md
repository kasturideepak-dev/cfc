# Instagram Feed (official Graph API)

Media Hub can display the latest posts from the Chennapatnam Filter Coffee Instagram professional account using Meta’s **Instagram API with Instagram Login**. Public pages never call Instagram. Cron (or CMS → Instagram Feed → Refresh) writes rows into MySQL `cfc_instagram_posts` and caches covers under `assets/instagram/`.

Instagram **Basic Display API** was shut down on 4 December 2024. Do not use it.

## Which API (2026)

| Product | Use here? | Notes |
| --- | --- | --- |
| **Instagram API with Instagram Login** (`graph.instagram.com`) | **Yes (default)** | Business Login for Instagram. No Facebook Page required. Permission: `instagram_business_basic`. |
| Instagram API with Facebook Login (`graph.facebook.com`) | Optional | Needs a Facebook Page linked to the IG professional account. Permissions: `instagram_basic`, `pages_show_list`, `pages_read_engagement`. |
| Instagram Basic Display | No | Deprecated. |

Official overview: [Instagram Platform](https://developers.facebook.com/docs/instagram-platform/overview/).  
Login + tokens: [Business Login for Instagram](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-instagram-login/business-login).  
Media: `GET /{ig-user-id}/media` ([Get started](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-instagram-login/get-started/)).

This site pins Graph version **v25.0** (configurable in CMS). Latest IG Media docs list v25.0.

**Standard Access** is enough while the app only serves *your* Instagram professional account (the account is added in the App Dashboard). **Advanced Access** + App Review + Business Verification are required only if other businesses you do not manage will log in.

## 1. Instagram account

1. Convert [@chennapatnamfiltercoffee](https://www.instagram.com/chennapatnamfiltercoffee/) to a **Professional** account (Business or Creator) in the Instagram app: Settings → Account type.
2. A Facebook Page is **not** required for Instagram Login. It **is** required if you switch the CMS to Facebook Login mode.

## 2. Meta app

1. Register at [developers.facebook.com](https://developers.facebook.com/).
2. Create an app → type **Business**.
3. Add product **Instagram** → **API setup with Instagram login**.
4. In **Business login settings**:
   - Copy **Instagram App ID** and **Instagram App Secret**.
   - Add OAuth redirect URI (exact match, including `https` and trailing path):
     `https://chennapatnamfiltercoffee.com/admin/?p=instagram&ig_oauth=1`
   - Add the Instagram professional account as a tester / connected account.
5. Request **Standard Access** for `instagram_business_basic` (read the account’s own media). You do **not** need `instagram_business_content_publish` for a website feed.

## 3. Access token

Two supported ways:

**A. App Dashboard (simplest for a first-party site)**  
Instagram → API setup with Instagram login → **Generate token** next to the account. That token is already **long-lived (60 days)**. Paste it in CMS → Instagram Feed.

**B. Business Login (Connect button)**  
CMS builds:

```
https://www.instagram.com/oauth/authorize
  ?client_id={INSTAGRAM_APP_ID}
  &redirect_uri={EXACT_REDIRECT}
  &response_type=code
  &scope=instagram_business_basic
  &state={csrf}
```

The site then:

1. `POST https://api.instagram.com/oauth/access_token` → short-lived token (1 hour).
2. `GET https://graph.instagram.com/access_token?grant_type=ig_exchange_token&client_secret=…` → long-lived token (60 days).
3. Cron / sync calls `GET https://graph.instagram.com/refresh_access_token?grant_type=ig_refresh_token` before expiry (token must be ≥ 24 hours old). Tokens unused for 60 days expire and cannot be refreshed — reconnect in CMS.

Tokens are stored in `cfc_settings` (`instagram_access_token`, `instagram_app_secret`). Never commit them. `config.local.php` may override them; that file is gitignored.

## 4. Endpoints this site calls

Instagram Login mode:

```
GET https://graph.instagram.com/v25.0/me?fields=user_id,username,account_type
GET https://graph.instagram.com/v25.0/{IG_ID}/media
    ?fields=id,media_type,media_url,thumbnail_url,permalink,caption,timestamp,username,alt_text,children{id,media_type,media_url,thumbnail_url}
    &limit={1-24}
```

Facebook Login mode (optional):

```
GET https://graph.facebook.com/v25.0/me/accounts?fields=instagram_business_account{id,username}
GET https://graph.facebook.com/v25.0/{IG_ID}/media?fields=…
```

`media_url` is omitted by Meta when a reel uses copyrighted audio. The sync still keeps `permalink` and uses `thumbnail_url` when present. CDN URLs expire, so covers are copied to `assets/instagram/{media-id}.webp`.

## 5. Cron

```cron
*/30 * * * * /usr/bin/php /home1/chennuih/public_html/cron/sync-instagram.php >/dev/null 2>&1
```

Force a run:

```bash
php cron/sync-instagram.php --force
```

If Instagram is down, rate-limited, or the token is bad, the previous rows in `cfc_instagram_posts` stay on Media Hub.

## 6. CMS

Admin → **Instagram Feed** (admins only):

- Enable/disable live feed
- Cache minutes (15–1440)
- Number of posts (1–24)
- App ID / secret / token
- Connect with Instagram
- Refresh Instagram Feed
- Last sync time and last error (secrets redacted)

If the live feed is off or the table is empty, Media Hub falls back to the manually curated tiles.
