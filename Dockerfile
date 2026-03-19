FROM php:8.4.19-fpm AS base
    ARG DEBIAN_FRONTEND=noninteractive
    ENV LANG=en_US.UTF-8
    ENV LANGUAGE=en_US:en
    ENV TZ="Etc/UTC"
    ENV LC_ALL="C.UTF-8"
    
FROM base AS install_dependencies
    RUN apt-get update && apt-get install -y mc nano nginx curl zip unzip htop;

FROM install_dependencies AS install_php
    #  calendar  dba dl_test dom enchant ffi  filter ftp  gettext     ldap  mysqli odbc  pcntl pdo pdo_dblib pdo_firebird pdo_mysql pdo_odbc pdo_pgsql  pgsql phar posix random  reflection session shmop simplexml snmp soap sockets sodium spl standard sysvmsg sysvsem sysvshm tidy zend_test zip
    RUN docker-php-ext-install \
    bz2 \
    bcmath \
    ctype \
    curl \
    iconv \
    exif \
    fileinfo \
    gd \
    gmp \
    hash \
    json \
    intl \
    mbstring \
    opcache \
    pdo_sqlite \
    readline \
    session \
    tokenizer \
    xml \
    xmlreader \
    xmlwriter \
    xsl \
    zip \
    ; \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer;

FROM install_php AS final
    COPY ./docker/scripts/entrypoint.sh /var/scripts/entrypoint.sh
    COPY ./docker/conf/nginx/sites-available/application /etc/nginx/sites-available/default
    COPY . /var/www/app/
    RUN chmod +x /var/scripts/entrypoint.sh;
    WORKDIR /var/www/app/

ENTRYPOINT ["/var/scripts/entrypoint.sh"]
EXPOSE 8080 8888
