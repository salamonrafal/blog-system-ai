#!/bin/sh
set -eu

cron_env_file='/run/blog-system-ai/cron.env'

if [ -f "${cron_env_file}" ]; then
    # Load runtime environment prepared during container startup.
    # shellcheck disable=SC1091
    . "${cron_env_file}"
fi

cd /var/www/app
exec /usr/local/bin/php bin/console "$@"
