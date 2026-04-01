FROM php:8.4.19-fpm AS base
    ARG DEBIAN_FRONTEND=noninteractive
    ENV LANG=en_US.UTF-8
    ENV LANGUAGE=en_US:en
    ENV TZ="Etc/UTC"
    ENV LC_ALL="C.UTF-8"
    
FROM base AS install_dependencies
    RUN apt-get update \
    && apt-get install -y --no-install-recommends mc nano nginx curl zip unzip htop cron \
    && rm -rf /var/lib/apt/lists/*;

FROM install_dependencies AS install_php
    RUN docker-php-ext-install \
    bz2 bcmath ctype curl iconv \
    exif fileinfo gd gmp hash \
    json intl mbstring opcache \
    pdo_sqlite readline session \
    tokenizer xml xmlreader xmlwriter \
    xsl zip; \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer;

FROM node:22-bookworm-slim AS build_assets
    WORKDIR /var/www/app/
    COPY package.json package-lock.json ./
    COPY scripts/ ./scripts/
    RUN npm ci
    COPY public/assets/css/ ./public/assets/css/
    COPY public/assets/js/ ./public/assets/js/
    RUN npm run build:assets:prod

FROM install_php AS final
    COPY ./docker/scripts/ /var/scripts/
    COPY ./docker/conf/nginx/sites-available/application /etc/nginx/sites-available/default
    COPY --chown=www-data:www-data . /var/www/app/
    COPY --from=build_assets --chown=www-data:www-data /var/www/app/public/assets/build/app.min.js /var/www/app/public/assets/build/app.min.js
    COPY --from=build_assets --chown=www-data:www-data /var/www/app/public/assets/build/styles.min.css /var/www/app/public/assets/build/styles.min.css
    RUN chmod 755 /var/scripts/*.sh;
    WORKDIR /var/www/app/
    RUN mkdir -p /tmp/composer && chown -R www-data:www-data /tmp/composer
    RUN su -s /bin/bash www-data -c 'HOME=/tmp/composer \
        APP_ENV=prod \
        APP_DEBUG=0 \
        APP_SECRET=build-time-secret \
        DATABASE_URL=sqlite:///%kernel.project_dir%/var/data.db \
        composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts --no-dev'

ENTRYPOINT ["/var/scripts/entrypoint.sh"]
EXPOSE 8080 8888
