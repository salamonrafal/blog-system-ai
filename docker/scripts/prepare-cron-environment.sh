#!/bin/sh
set -eu

cron_env_file='/var/www/app/.cron.env'

echo "Preparing environment for cron..."

{
    echo '#!/bin/sh'

    for variable_name in APP_ENV APP_DEBUG APP_SECRET DATABASE_URL; do
        variable_value=$(printenv "${variable_name}" || true)

        if [ -n "${variable_value}" ]; then
            escaped_value=$(printf "%s" "${variable_value}" | sed "s/'/'\"'\"'/g")
            printf "export %s='%s'\n" "${variable_name}" "${escaped_value}"
        fi
    done
} > "${cron_env_file}"

chown www-data:www-data "${cron_env_file}"
chmod 600 "${cron_env_file}"
