# syntax=docker/dockerfile:1.7

FROM php:8.4-fpm-alpine AS php-base

RUN apk add --no-cache \
        freetype icu-libs libjpeg-turbo libpng libzip oniguruma \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS freetype-dev icu-dev libjpeg-turbo-dev libpng-dev libzip-dev oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) bcmath gd intl mbstring opcache pcntl pdo_mysql posix zip \
    && apk del .build-deps

WORKDIR /var/www/html

FROM php-base AS build

RUN apk add --no-cache git nodejs npm unzip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . .
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader
RUN npm ci && npm run build

FROM php-base AS app

COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html
RUN mkdir -p storage/app/private storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data
EXPOSE 9000
HEALTHCHECK --interval=15s --timeout=3s --retries=5 CMD php-fpm -t || exit 1
CMD ["php-fpm"]

FROM nginx:1.28-alpine AS web

COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /var/www/html/public /var/www/html/public
EXPOSE 80
