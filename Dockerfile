FROM php:8.2-apache

# System packages needed by the app's own dependencies:
# libzip/libpng/libjpeg/freetype -> gd + zip (PhpSpreadsheet, DomPDF images)
# libpq -> pdo_pgsql (Render's managed Postgres)
RUN apt-get update && apt-get install -y \
        git unzip libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libpq-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql pdo_pgsql gd zip bcmath mbstring \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# No .env file is created here on purpose: Laravel falls back cleanly to
# real process environment variables when .env is absent (this is the
# standard pattern for containerized deploys), and copying .env.example's
# leftover local-dev defaults (e.g. DB_PORT=3306, a MySQL default) in was
# exactly what caused this app to try connecting to Render's Postgres
# database on the wrong port.
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Serve the public/ directory, not the repo root.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
