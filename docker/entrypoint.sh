#!/bin/sh
set -e

# Primer arranque: dependencias, .env, clave y base de datos SQLite.
[ -d vendor ] || composer install --no-interaction --prefer-dist
[ -f .env ] || cp .env.example .env
grep -q '^APP_KEY=.\+' .env || php artisan key:generate --force
[ -f database/database.sqlite ] || touch database/database.sqlite

php artisan migrate --force

exec "$@"
