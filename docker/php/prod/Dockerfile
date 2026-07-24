FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
    libzip-dev zip \
    freetype-dev libpng-dev libwebp-dev jpeg-dev libjpeg-turbo-dev \
    oniguruma-dev \
    autoconf build-base \
    libintl icu-dev

RUN docker-php-ext-configure zip
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
RUN docker-php-ext-install -j$(nproc) gd zip pdo_mysql exif pcntl bcmath opcache mbstring
RUN docker-php-ext-configure intl && docker-php-ext-install intl
RUN pecl install redis && docker-php-ext-enable redis

# Strip build tools from final image
RUN apk del autoconf build-base

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY ./docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY ./docker/php/opcache.ini  /usr/local/etc/php/conf.d/opcache.ini
COPY ./docker/php/uploads.ini  /usr/local/etc/php/conf.d/uploads.ini

# public/build is pre-built in CI before docker build
COPY --chown=www-data:www-data . /var/www/html

RUN chmod -R 777 /var/www/html/storage && \
    mkdir -p /.config/psysh && chmod -R 777 /.config && \
    mkdir -p /.composer/cache/files && chmod -R 777 /.composer/cache/files

RUN composer install --no-dev --no-cache --prefer-dist --optimize-autoloader
