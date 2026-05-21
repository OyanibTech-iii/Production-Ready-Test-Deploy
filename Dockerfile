# Stage 1: Build PHP dependencies
FROM php:8.2-fpm-alpine AS php_builder

WORKDIR /app

# Install system dependencies for PHP
RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    icu-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    bash

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo_mysql \
    zip \
    intl \
    gd \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files and install dependencies
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Stage 2: Build Frontend Assets
FROM node:18-alpine AS node_builder

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm install

COPY . .
RUN npm run build

# Stage 3: Final Production Image
FROM php:8.2-fpm-alpine

WORKDIR /app

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    icu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    gettext \
    bash

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo_mysql \
    zip \
    intl \
    gd \
    opcache

# Copy PHP production configuration
COPY docker/php-opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Copy Nginx configuration
COPY nginx-main.conf /etc/nginx/nginx.conf
COPY nginx.conf /etc/nginx/conf.d/default.conf.template

# Copy application files
COPY . .

# Copy vendors from php_builder
COPY --from=php_builder /app/vendor ./vendor

# Copy built assets from node_builder
COPY --from=node_builder /app/public/build ./public/build

# Finalize Composer for production
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer dump-autoload --no-dev --classmap-authoritative \
    && composer dump-env prod

# Set permissions for Symfony
RUN mkdir -p var/cache var/log public/uploads \
    && chown -R www-data:www-data var public/uploads

# Set up the entrypoint
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Default Railway port
ENV PORT=8080
EXPOSE 8080

ENTRYPOINT ["entrypoint.sh"]
