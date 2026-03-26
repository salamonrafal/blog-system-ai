#!/bin/bash
set -Eeuo pipefail

echo "Running entrypoint script..."

echo "Checking tasks..."
/var/scripts/checking-tasks.sh

echo "Create .env file..."
/var/scripts/create-env.sh

echo "Install app dependencies with composer..."
su -s /bin/sh www-data -c "composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader"

echo 'Starting php-fpm in background...'
nohup php-fpm -D >/dev/null 2>&1 &

echo "Starting nginx..."
exec nginx -g 'daemon off;'
