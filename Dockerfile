FROM php:8.3-fpm

WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y --fix-missing \
    curl \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath xml

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . .

# Install PHP dependencies with increased timeout
RUN COMPOSER_PROCESS_TIMEOUT=600 COMPOSER_MEMORY_LIMIT=-1 composer install --no-interaction --prefer-dist --no-dev --no-security-blocking

# Copy .env file
RUN cp .env.example .env || true

# Generate application key
RUN php artisan key:generate || true

# Startup script (migrations + app boot)
RUN chmod +x docker/start.sh

# Expose port
EXPOSE 8000

CMD ["sh", "docker/start.sh"]
