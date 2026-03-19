#!/bin/bash
set -e

echo "Running entrypoint script..."

echo "Checking files ..."

if [ ! -d "./var/cache/" ] ; then
    mkdir -p ./var/cache/
    chown www-data:www-data ./var/cache/
    chmod 777 ./var/cache/
fi

if [ ! -d "./var/log/" ] ; then
    mkdir -p ./var/log/
    chown www-data:www-data ./var/log/
    chmod 777 ./var/log/
fi

echo "Install app dependencies with composer..."
composer install

echo 'starting php-fpm in background'
nohup php-fpm -D >/dev/null 2>&1 &

echo 'starting nginx'
nginx -g 'daemon off;'

sleep infinity
