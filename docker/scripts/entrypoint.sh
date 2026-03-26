#!/bin/bash
set -Eeuo pipefail

echo "Running entrypoint script..."

echo "Checking tasks..."
/var/scripts/checking-tasks.sh

echo "Create .env file..."
/var/scripts/create-env.sh

echo 'Starting php-fpm in background...'
nohup php-fpm -D >/dev/null 2>&1 &

echo "Starting nginx..."
exec nginx -g 'daemon off;'
