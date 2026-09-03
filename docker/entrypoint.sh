#!/usr/bin/env sh
set -e

# Config/route/view caches must be rebuilt inside the running container -
# the values baked in at image-build time won't have the real env vars yet.
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan migrate --force

exec "$@"
