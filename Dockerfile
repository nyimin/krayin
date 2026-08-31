FROM php:8.3-apache

# system deps
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev \
    libfreetype6-dev libwebp-dev libxpm-dev libgmp-dev libonig-dev \
    zlib1g-dev libcurl4-openssl-dev \
    && rm -rf /var/lib/apt/lists/*

# php extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
RUN docker-php-ext-configure intl
RUN docker-php-ext-install bcmath calendar exif gd gmp intl mysqli pdo pdo_mysql zip opcache mbstring curl soap

RUN a2enmod rewrite

# composer
COPY --from=composer:2.7 /usr/bin/composer /usr/local/bin/composer
ARG COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# clone Krayin 2.2.3 (security-patched)
RUN git clone --depth 1 --branch v2.2.3 https://github.com/krayin/laravel-crm.git krayin

WORKDIR /var/www/html/krayin
RUN cp .env.example .env && composer install --no-interaction --no-progress --prefer-dist
RUN php artisan storage:link >/dev/null 2>&1 || true
RUN chown -R www-data:www-data storage bootstrap/cache

# apache vhost -> public (correct document root)
RUN printf '<VirtualHost *:80>\n  DocumentRoot /var/www/html/krayin/public\n  <Directory /var/www/html/krayin/public>\n    AllowOverride All\n    Require all granted\n    Options -Indexes\n  </Directory>\n  ErrorLog ${APACHE_LOG_DIR}/error.log\n  CustomLog ${APACHE_LOG_DIR}/access.log combined\n</VirtualHost>\n' > /etc/apache2/sites-available/000-default.conf

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
