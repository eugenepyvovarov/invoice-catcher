# Gmail Catcher

## Running the service locally

Copy example config and setup all required variables
- `cp html/.env.example html/.env`

Start services:
- `docker compose up -d`

Install dependencies:
- `docker exec -i catcher_php composer install --prefer-dist --no-dev --working-dir=/var/www/html --no-plugins --no-scripts`

Create database:
- `docker exec -i catcher_php touch database/database.sqlite`

Run migrations:
- `docker exec -i catcher_php php artisan migrate --force`

Navigate to: http://localhost:8000
