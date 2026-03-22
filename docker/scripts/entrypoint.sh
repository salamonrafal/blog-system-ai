#!/bin/bash
set -e

echo "Running entrypoint script..."

echo "Checking tasks..."
/var/scripts/checking-tasks.sh

echo "Create .env file..."
/var/scripts/create-env.sh

echo "Install app dependencies with composer..."
composer install

echo 'Starting php-fpm in background...'
nohup php-fpm -D >/dev/null 2>&1 &

echo 'Starting nginx...'
nginx -g 'daemon off;'

sleep infinity
