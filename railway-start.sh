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

# Regenerate cache discovery package (bootstrap/cache/packages.php &
# services.php) di setiap container start. File ini sengaja TIDAK
# di-commit ke git (lihat .gitignore) supaya production selalu pakai
# daftar provider yang sesuai dependency production (--no-dev) yang
# benar-benar ter-install, bukan snapshot lama yang bisa saja masih
# menyertakan provider dev-only seperti Laravel Pail.
php artisan package:discover --ansi

php artisan migrate --force
php artisan storage:link || true

# --no-reload WAJIB ada supaya PHP_CLI_SERVER_WORKERS (di-set lewat Railway
# variables) beneran dipakai -- tanpa flag ini, Laravel diam-diam nolak
# multi-worker & cuma jalanin 1 proses server (lihat warning "Unable to
# respect the PHP_CLI_SERVER_WORKERS environment variable without the
# --no-reload flag" di php artisan serve). Tanpa worker lebih dari 1, request
# yang ditahan lama (mis. long-polling) bisa nge-block SELURUH situs karena
# cuma ada 1 proses yang gantian ngelayani semua orang.
php artisan serve --host=0.0.0.0 --port="${PORT:-8000}" --no-reload
