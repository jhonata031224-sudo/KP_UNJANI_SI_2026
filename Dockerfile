# syntax=docker/dockerfile:1

# ---- Stage 1: build frontend assets (Vite) ----
FROM node:20-bookworm AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --include=dev
COPY . .
RUN node node_modules/vite/bin/vite.js build

# ---- Stage 2: PHP runtime + Composer deps ----
FROM php:8.2-cli-bookworm AS app
WORKDIR /app

# System deps + PHP extensions dibutuhkan project ini (ext-zip di composer.json,
# plus pdo_mysql/sqlite3 untuk DB, gd untuk image jika dibutuhkan Laravel).
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libpng-dev libonig-dev libxml2-dev libsqlite3-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install dependency PHP dulu (cache layer terpisah dari copy source)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy seluruh source code
COPY . .

# Copy hasil build Vite dari stage frontend (folder public/build)
COPY --from=frontend /app/public/build ./public/build

# Selesaikan autoload + discover setelah source lengkap ada
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi || true

# Permission storage & cache (Laravel butuh writable)
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

COPY railway-start.sh /app/railway-start.sh
RUN chmod +x /app/railway-start.sh

CMD ["bash", "railway-start.sh"]
