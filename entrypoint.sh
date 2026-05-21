#!/bin/bash
set -e

# Map MYSQL_URL to DATABASE_URL if DATABASE_URL is not set (common on Railway)
if [ -z "$DATABASE_URL" ] && [ -n "$MYSQL_URL" ]; then
    export DATABASE_URL="$MYSQL_URL"
    echo "Exported DATABASE_URL from MYSQL_URL"
fi

# Map REDISURL or REDIS_URL from Railway
if [ -z "$REDIS_URL" ] && [ -n "$REDISURL" ]; then
    export REDIS_URL="$REDISURL"
    echo "Exported REDIS_URL from REDISURL"
fi

# If REDIS_URL is set, ensure other Redis-related env vars use it as a base if not already set
if [ -n "$REDIS_URL" ]; then
    # Strip trailing slash if present
    BASE_REDIS_URL=$(echo $REDIS_URL | sed 's/\/$//')
    
    export REDIS_URL="${REDIS_URL}"
    export REDIS_CACHE_URL="${REDIS_CACHE_URL:-$BASE_REDIS_URL/2}"
    export REDIS_RATE_LIMITER_URL="${REDIS_RATE_LIMITER_URL:-$BASE_REDIS_URL/1}"
    export REDIS_SESSION_URL="${REDIS_SESSION_URL:-$BASE_REDIS_URL/2}"
    echo "Configured Redis environment variables using REDIS_URL"
fi

# Set APP_ENV to prod if not set
export APP_ENV=${APP_ENV:-prod}

# Dump environment variables for Symfony to pick up, bypassing PHP-FPM clear_env
echo "Dumping environment variables..."
composer dump-env $APP_ENV --no-interaction
chown www-data:www-data .env.local.php || true

# Run migrations if we have a database URL
if [ -n "$DATABASE_URL" ]; then
    echo "Running database migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || echo "Migrations failed, continuing..."
fi

echo "Starting PHP-FPM..."
php-fpm -F &
PHP_PID=$!

echo "Waiting for PHP-FPM to start..."
sleep 2

echo "Starting Nginx..."
nginx -g "daemon off;"

wait $PHP_PID