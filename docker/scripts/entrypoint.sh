#!/bin/bash
set -Eeuo pipefail

echo "Running entrypoint script..."

echo "Checking tasks..."
/var/scripts/checking-tasks.sh

if [ "${APP_ENV:-dev}" = "prod" ]; then
    if [ ! -d "./var/cache/prod" ] || [ -z "$(ls -A ./var/cache/prod 2>/dev/null)" ]; then
        echo "Warming Symfony cache..."
        su -s /bin/bash www-data -c 'APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear --env=prod --no-debug'
    else
        echo "Symfony cache already exists, skipping warmup."
    fi
fi

echo 'Starting php-fpm in background...'
nohup php-fpm -D >/dev/null 2>&1 &

/var/scripts/prepare-cron-environment.sh

echo "Starting cron..."
crontab -u www-data /var/www/app/docker/conf/cron/article-queue
service cron start

echo "Starting nginx..."
exec nginx -g 'daemon off;'
