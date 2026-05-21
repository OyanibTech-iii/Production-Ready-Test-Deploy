#!/bin/bash
set -e

# Map MYSQL_URL to DATABASE_URL if DATABASE_URL is not set (common on Railway)
if [ -z "$DATABASE_URL" ] && [ -n "$MYSQL_URL" ]; then
    # Append serverVersion if it's not there to ensure Doctrine works correctly
    if [[ "$MYSQL_URL" != *serverVersion* ]]; then
        export DATABASE_URL="${MYSQL_URL}?serverVersion=8.0.32"
    else
        export DATABASE_URL="$MYSQL_URL"
    fi
    echo "Exported DATABASE_URL from MYSQL_URL"
fi

# Set default PORT if not set (Railway sets this)
export PORT=${PORT:-80}
echo "Configuring Nginx to listen on port $PORT"
sed -i "s/listen 80/listen ${PORT}/g" /etc/nginx/conf.d/symfony.conf

echo "Starting PHP-FPM..."
php-fpm -F &
PHP_PID=$!

echo "Waiting for PHP-FPM to start..."
sleep 5

# Run migrations if database is available
if [ -n "$DATABASE_URL" ]; then
    echo "Running migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || echo "Migrations failed, continuing..."
fi

# Generate JWT keys if they don't exist
if [ ! -f config/jwt/private.pem ]; then
    echo "Generating JWT keys..."
    mkdir -p config/jwt
    php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction || echo "JWT key generation failed, continuing..."
    chown -R www-data:www-data config/jwt
fi

# Clear and warm up cache (just in case env vars changed)
echo "Clearing cache..."
php bin/console cache:clear --no-interaction || echo "Cache clear failed, continuing..."

echo "Starting Nginx..."
nginx -g "daemon off;"

wait $PHP_PID