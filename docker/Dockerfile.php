FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    procps \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions (sockets, pdo_mysql, zip, bcmath, pcntl)
RUN docker-php-ext-install pdo_mysql sockets bcmath pcntl opcache zip

# Copy Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
