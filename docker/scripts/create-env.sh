#!/bin/bash
set -e

if [ ! -f ".env" ] ; then
    echo "Creating .env file..."
    cat <<EOF > .env
        APP_ENV=${E_APP_ENV:-dev}
        APP_DEBUG=${E_APP_DEBUG:-1}
        APP_SECRET="${E_APP_SECRET:-$(openssl rand -hex 32)}"
        DATABASE_URL="${E_DATABASE_URL:-sqlite:///%kernel.project_dir%/var/data.db}"
EOF
else
    echo ".env file already exists, skipping creation."
fi
