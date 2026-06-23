# Gmail Catcher

Laravel **13** / PHP **8.3+** (Docker runtime **PHP 8.5**) Gmail invoice/mail catcher.

## Requirements

- Docker & Docker Compose
- Google OAuth credentials (Gmail readonly scope)

## Running locally

Copy example config and set required variables:

```bash
cp html/.env.example html/.env
# set GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI, APP_KEY
```

Build services (optional explicit wkhtmltopdf package for your arch if auto-detect fails):

```bash
docker compose build
# or:
# docker compose build --build-arg=WKHTMLTOPDF_URL=https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-3/wkhtmltox_0.12.6.1-3.bookworm_arm64.deb
```

Start services:

```bash
docker compose up -d
```

Install dependencies & migrate:

```bash
docker exec -i catcher_php composer install --prefer-dist --working-dir=/var/www/html
docker exec -i catcher_php touch /var/www/html/database/database.sqlite
docker exec -i catcher_php php artisan migrate --force
```

Navigate to: http://localhost:8000

Primary auth path is **Gmail OAuth** (`/oauth/gmail/login`). Session login (`/login`) remains for users with passwords; public registration is disabled.

## PHP / Laravel

| Layer | Version |
|-------|---------|
| Composer `php` constraint | `^8.3` |
| Docker image | `php:8.5-fpm-bookworm` |
| Framework | Laravel 13 |
| Gmail integration | First-party (`google/apiclient`), not `dacastro4/laravel-gmail` |
| PDF | `barryvdh/laravel-snappy` + wkhtmltopdf in container |

## Ops notes

- Existing Gmail tokens from the old package path (`storage/app/gmail/tokens/…`) are still used when present under the same filename pattern; if auth fails after upgrade, reconnect Gmail once.
- Local disk root is `storage/app/private` (Laravel 11+ default).
