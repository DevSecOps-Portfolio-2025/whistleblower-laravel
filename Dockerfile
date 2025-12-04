# ================================================
# WhistleBlower Vault - Dockerfile Hardened
# Base: PHP 8.2-FPM Alpine (Lightweight & Secure)
# ================================================

FROM php:8.2-fpm-alpine

LABEL maintainer="WhistleBlower DevOps Team"
LABEL description="Hardened PHP-FPM container for WhistleBlower Vault"

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    # Build dependencies
    $PHPIZE_DEPS \
    curl \
    git \
    unzip \
    # Runtime dependencies
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        bcmath \
        opcache \
        gd \
        zip \
        pcntl \
    # Install Redis extension via PECL
    && pecl install redis \
    && docker-php-ext-enable redis \
    # Clean up build dependencies to reduce image size
    && apk del $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/* /tmp/* /var/tmp/*

# Copy Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure PHP-FPM and OPcache for production-ready performance
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.revalidate_freq=60'; \
        echo 'opcache.fast_shutdown=1'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# Security: Create non-root user 'wb_user' (WhistleBlower User)
RUN addgroup -g 1000 wb_user && \
    adduser -D -u 1000 -G wb_user wb_user

# Set working directory
WORKDIR /var/www/html

# Copy application files (ownership will be set later)
COPY --chown=wb_user:wb_user . /var/www/html

# Set proper permissions
RUN chown -R wb_user:wb_user /var/www/html \
    && chmod -R 777 /var/www/html/storage \
    && chmod -R 777 /var/www/html/bootstrap/cache

# Note: In development with Windows + Docker volumes, we keep root user
# to avoid permission issues. In production, switch to wb_user.
# Uncomment the line below for production:
# USER wb_user

# Expose PHP-FPM port
EXPOSE 9000

# Start PHP-FPM server
CMD ["php-fpm"]
