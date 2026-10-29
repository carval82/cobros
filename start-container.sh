#!/bin/bash

set -e

# The image may contain a config cache built without APP_KEY or the database.
php artisan config:clear

if [ "$RAILPACK_SKIP_MIGRATIONS" != "true" ]; then
  echo "Running migrations ..."
  php artisan migrate --force
fi

php artisan cobros:usuario-maestro

php artisan storage:link
php artisan optimize

echo "Starting Laravel server ..."

docker-php-entrypoint --config /Caddyfile --adapter caddyfile 2>&1
