#!/usr/bin/env bash
set -e

# SQLite di filesystem ephemeral cuma dipakai kalau DB_CONNECTION=sqlite
# (fallback lokal/demo). Untuk production disarankan pakai DB_CONNECTION=mysql
# yang nunjuk ke service MySQL Railway (persisten via volume MySQL itu sendiri).
DB_CONNECTION="${DB_CONNECTION:-sqlite}"
if [ "$DB_CONNECTION" = "sqlite" ]; then
  DB_PATH="${DB_DATABASE:-/app/database/database.sqlite}"
  mkdir -p "$(dirname "$DB_PATH")"
  touch "$DB_PATH"
fi

php artisan config:clear
php artisan migrate --force
php artisan storage:link || true

php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
