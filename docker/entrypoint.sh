#!/usr/bin/env sh
set -e

# Render tells the container which port to listen on via $PORT (defaulting
# to 10000 if unset) and health-checks exactly that port - stock Apache
# always listens on 80 and has no idea about $PORT, so it must be
# reconfigured here at container start (the real value isn't known at
# image-build time).
PORT="${PORT:-80}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Config/route/view caches must be rebuilt inside the running container -
# the values baked in at image-build time won't have the real env vars yet.
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan migrate --force

exec "$@"
