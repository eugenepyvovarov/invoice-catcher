# Gmail Catcher

## Running the service locally

Copy example config and setup all required variables
- `cp html/.env.example html/.env`

Build services:

App uses [wkhtmltopdf](https://github.com/wkhtmltopdf/packaging) so, get the suitable package url from https://github.com/wkhtmltopdf/packaging/releases/0.12.6-1
and set it as build argument.

The default url is set to stretch_arm64.deb package.

- `docker compose build --build-arg=WKHTMLTOPDF_URL=https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6-1/wkhtmltox_0.12.6-1.stretch_arm64.deb`

Start services:
- `docker compose up -d`

Install dependencies:
- `docker exec -i catcher_php composer install --prefer-dist --no-dev --working-dir=/var/www/html --no-plugins --no-scripts`

Create database:
- `docker exec -i catcher_php touch database/database.sqlite`

Run migrations:
- `docker exec -i catcher_php php artisan migrate --force`

Navigate to: http://localhost:8000
