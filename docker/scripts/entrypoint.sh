#!/bin/bash
set -Eeuo pipefail

echo "Running entrypoint script..."

echo "Checking tasks..."
/var/scripts/checking-tasks.sh

if [ "${APP_ENV:-dev}" = "prod" ]; then
    echo "Warming Symfony cache..."
    php bin/console cache:clear --env=prod --no-debug
    php bin/console cache:warmup --env=prod --no-debug
fi

echo 'Starting php-fpm in background...'
nohup php-fpm -D >/dev/null 2>&1 &

echo "Starting nginx..."
exec nginx -g 'daemon off;'
