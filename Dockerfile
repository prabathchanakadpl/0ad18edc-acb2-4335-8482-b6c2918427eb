# Use official PHP 8.2 CLI image
FROM php:8.2-cli

# Set working directory
WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install \
    zip \
    mbstring \
    exif \
    pcntl \
    bcmath \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .

# Install PHP dependencies (no dev dependencies for production)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Create storage directory with proper permissions
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && chmod -R 775 storage \
    && chown -R www-data:www-data storage

# Set proper permissions for Laravel
RUN chown -R www-data:www-data /app

# Switch to non-root user
USER www-data

# Expose port (if needed for web server)
# EXPOSE 8000

# Default command (can be overridden in docker-compose)
CMD ["php", "artisan", "list"]
