FROM php:8.3-cli

WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1

# System packages + PHP extensions required by Laravel + PostgreSQL
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libcurl4-openssl-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install bcmath curl gd intl mbstring pdo pdo_pgsql xml zip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy app source
COPY . .

# Install PHP dependencies without framework scripts during build
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev --no-scripts --ignore-platform-reqs

# Laravel runtime prep
RUN mkdir -p storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 10000

# Render sets PORT at runtime; default to 10000 for local container runs
CMD ["sh", "-c", "php artisan package:discover --ansi && php artisan optimize:clear && (php artisan storage:link || true) && php artisan migrate --force && php artisan db:seed --force && php artisan config:cache && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
