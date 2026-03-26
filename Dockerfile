FROM php:8.4.19-fpm AS base
    ARG DEBIAN_FRONTEND=noninteractive
    ENV LANG=en_US.UTF-8
    ENV LANGUAGE=en_US:en
    ENV TZ="Etc/UTC"
    ENV LC_ALL="C.UTF-8"
    
FROM base AS install_dependencies
    RUN apt-get update && apt-get install -y mc nano nginx curl zip unzip htop;

FROM install_dependencies AS install_php
    RUN docker-php-ext-install \
    bz2 bcmath ctype curl iconv \
    exif fileinfo gd gmp hash \
    json intl mbstring opcache \
    pdo_sqlite readline session \
    tokenizer xml xmlreader xmlwriter \
    xsl zip; \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer;

FROM install_php AS final
    COPY ./docker/scripts/ /var/scripts/
    COPY ./docker/conf/nginx/sites-available/application /etc/nginx/sites-available/default
    COPY --chown=www-data:www-data . /var/www/app/
    RUN chmod 755 /var/scripts/*.sh;
    WORKDIR /var/www/app/
    RUN APP_ENV=prod \
        APP_DEBUG=0 \
        APP_SECRET=build-time-secret \
        DATABASE_URL=sqlite:///%kernel.project_dir%/var/data.db \
        composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

ENTRYPOINT ["/var/scripts/entrypoint.sh"]
EXPOSE 8080 8888
