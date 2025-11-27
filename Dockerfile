FROM composer:latest AS composer

FROM php:8.4-apache

# Set working directory
WORKDIR /var/www

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    nodejs \
    npm \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_sqlite mbstring exif pcntl bcmath gd

# Install Redis PHP extension via PECL
RUN pecl install redis && docker-php-ext-enable redis

# Get Composer from composer stage
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Copy existing application directory contents
COPY . /var/www

# Create SQLite database directory
RUN mkdir -p /var/www/database

# Copy production environment file
RUN cp /var/www/.env.docker /var/www/.env

# Install PHP dependencies
# Use --classmap-authoritative instead of --optimize-autoloader to avoid segfaults
# Also disable opcache entirely during composer operations
RUN php -d opcache.enable=0 -d opcache.jit=off -d memory_limit=-1 /usr/bin/composer install --no-dev --classmap-authoritative --no-interaction

# Install and build frontend assets
RUN npm ci && npm run build

# Configure Apache
RUN a2enmod rewrite && \
    { echo "ServerName localhost"; } >> /etc/apache2/apache2.conf

# Configure Apache virtual host to serve from public directory
RUN echo '<VirtualHost *:80>' > /etc/apache2/sites-available/000-default.conf && \
    echo '    ServerAdmin webmaster@localhost' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    DocumentRoot /var/www/public' >> /etc/apache2/sites-available/000-default.conf && \
    echo '' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    <Directory /var/www/public>' >> /etc/apache2/sites-available/000-default.conf && \
    echo '        Options Indexes FollowSymLinks' >> /etc/apache2/sites-available/000-default.conf && \
    echo '        AllowOverride All' >> /etc/apache2/sites-available/000-default.conf && \
    echo '        Require all granted' >> /etc/apache2/sites-available/000-default.conf && \
    echo '        DirectoryIndex index.php' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    </Directory>' >> /etc/apache2/sites-available/000-default.conf && \
    echo '' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    ErrorLog ${APACHE_LOG_DIR}/error.log' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    CustomLog ${APACHE_LOG_DIR}/access.log combined' >> /etc/apache2/sites-available/000-default.conf && \
    echo '</VirtualHost>' >> /etc/apache2/sites-available/000-default.conf

# Set permissions
RUN chown -R www-data:www-data /var/www
RUN chmod -R 755 /var/www/public

# Copy entrypoint script
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port 80
EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
