# ─── Stage 1: builder ───────────────────────────────────────────────────────
FROM php:8.4-fpm AS builder

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip bcmath \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Node.js 22
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copy dependency files first (layer caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-autoloader --no-scripts --prefer-dist

COPY package.json package-lock.json* ./
RUN npm ci --no-audit --no-fund

# Copy source code
COPY . .

# Build frontend assets
RUN npm run build

# Generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev

# ─── Stage 2: runtime ────────────────────────────────────────────────────────
FROM php:8.4-fpm AS runtime

# Install runtime system dependencies only
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip bcmath opcache \
    && apt-get install -y redis-tools \
    && rm -rf /var/lib/apt/lists/*

# Install pcntl for queue workers
RUN docker-php-ext-install pcntl

# PHP-FPM configuration
RUN echo "pm = dynamic" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_children = 20" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.start_servers = 5" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.min_spare_servers = 3" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_spare_servers = 10" >> /usr/local/etc/php-fpm.d/www.conf

# PHP opcache configuration
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html

# Copy built application from builder stage
COPY --from=builder /app/vendor ./vendor
COPY --from=builder /app/public/build ./public/build
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
