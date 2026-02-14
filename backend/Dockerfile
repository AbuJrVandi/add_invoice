FROM php:8.3-cli

WORKDIR /var/www/html

# System packages + PHP extensions required by Laravel + PostgreSQL
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install mbstring pdo pdo_pgsql xml zip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy app source
COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Laravel runtime prep
RUN mkdir -p storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 10000

# Render sets PORT at runtime; default to 10000 for local container runs
CMD ["sh", "-c", "php artisan config:clear && php artisan route:clear && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
