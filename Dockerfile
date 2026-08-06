# syntax=docker/dockerfile:1.7

FROM node:24-bookworm-slim AS node-runtime

FROM php:8.4-fpm-bookworm AS php-base

RUN --mount=type=secret,id=build_ca,required=false \
    if [ -s /run/secrets/build_ca ] \
        && openssl x509 -in /run/secrets/build_ca -noout > /dev/null 2>&1; then \
        install -m 0644 /run/secrets/build_ca /usr/local/share/ca-certificates/formforge-build-ca.crt; \
        update-ca-certificates; \
    fi

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        $PHPIZE_DEPS \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath gd intl mbstring opcache pcntl pdo_mysql posix zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

FROM php-base AS build

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*
COPY --from=node-runtime /usr/local/bin/node /usr/local/bin/node
COPY --from=node-runtime /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . .
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader
RUN if [ -f /usr/local/share/ca-certificates/formforge-build-ca.crt ]; then \
        export NODE_EXTRA_CA_CERTS=/usr/local/share/ca-certificates/formforge-build-ca.crt; \
    fi; \
    npm ci && npm run build

FROM php-base AS app

RUN apt-get update \
    && apt-get install -y --no-install-recommends default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

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
