#!/bin/bash
set -e

# Generate JWT keys if they don't exist
if [ ! -f config/jwt/private.pem ]; then
    echo "Generating JWT keys..."
    php bin/console lexik:jwt:generate-keypair --skip-if-exists
    chown www-data:www-data config/jwt/*.pem
fi

# Run migrations if database is available
echo "Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Clear and warm up cache
echo "Clearing cache..."
php bin/console cache:clear
php bin/console cache:warmup

# Set up Nginx port dynamically (Railway provides $PORT)
echo "Configuring Nginx to listen on port $PORT"
envsubst '${PORT}' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

# Start PHP-FPM in the background
echo "Starting PHP-FPM..."
php-fpm -D

# Start Nginx in the foreground
echo "Starting Nginx..."
nginx -g 'daemon off;'
