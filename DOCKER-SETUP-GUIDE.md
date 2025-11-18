# Docker Setup Guide for Laravel Projects

This guide explains how to set up a Laravel project with Docker for deployment via Portainer, based on the successful setup of the `economy` and `pdf-to-markdown` projects.

## Overview

The goal is to create a **single-container** Laravel application that:
- ✅ Runs everything in one container (PHP + Apache)
- ✅ Automatically runs migrations on startup
- ✅ Requires ZERO manual commands after deployment
- ✅ Works seamlessly with Portainer
- ✅ Uses SQLite with persistent storage

## File Structure

You'll need these files in your Laravel project:

```
your-project/
├── docker/
│   └── docker-entrypoint.sh
├── .dockerignore
├── .env.docker
├── docker-compose.yml
└── Dockerfile
```

---

## Step 1: Create `.dockerignore`

This excludes unnecessary files from the Docker build.

```
.git
.gitignore
.gitattributes
node_modules
vendor
.env
.env.backup
.phpunit.result.cache
Homestead.json
Homestead.yaml
npm-debug.log
yarn-error.log
.DS_Store
Thumbs.db
/storage/*.key
/public/hot
/public/storage
.idea
.vscode
*.swp
*.swo
*~
.phpintel
_ide_helper.php
_ide_helper_models.php
.phpstorm.meta.php
tests
.editorconfig
.styleci.yml
docker-compose.yml
Dockerfile
.dockerignore
README.md
CLAUDE.md
```

---

## Step 2: Create `.env.docker`

Production environment settings. Copy your `.env` and modify these values:

```env
APP_NAME="Your App Name"
APP_ENV=production
APP_KEY=base64:YOUR_EXISTING_APP_KEY_HERE
APP_DEBUG=false
APP_URL=http://localhost:YOUR_PORT

# Keep your existing locale settings
APP_LOCALE=no
APP_FALLBACK_LOCALE=en

# Production logging
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

# SQLite database
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/database/database.sqlite

# Keep your existing settings for these:
SESSION_DRIVER=database
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

# Mail (set to log for production)
MAIL_MAILER=log

# Copy any other app-specific env vars you need
# (API keys, service URLs, etc.)
```

---

## Step 3: Create `docker/docker-entrypoint.sh`

This script runs automatically when the container starts.

```bash
#!/bin/bash
set -e

echo "Starting application..."

# Create database file if it doesn't exist
if [ ! -f /var/www/database/database.sqlite ]; then
    echo "Creating SQLite database file..."
    touch /var/www/database/database.sqlite
    chown www-data:www-data /var/www/database/database.sqlite
    chmod 664 /var/www/database/database.sqlite
fi

# Run migrations
echo "Running database migrations..."
php artisan migrate --force

# Seed database if needed (optional, only on first run)
if [ ! -s /var/www/database/database.sqlite ]; then
    echo "Database is empty, running seeders..."
    php artisan db:seed --force || echo "No seeders to run or seeding failed, continuing..."
fi

# Clear and cache config for production
echo "Optimizing Laravel for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Application ready!"

# Start Apache
exec apache2-foreground
```

**Important**: Make sure this file has executable permissions in your repo (Git on Windows can be tricky with this).

---

## Step 4: Create `Dockerfile`

**Key points**:
- Use `php:8.4-apache` (or your PHP version with `-apache`)
- Do NOT use `php:8.4-fpm` - that requires a separate web server
- Install all PHP extensions your app needs
- Build frontend assets during image build

```dockerfile
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
# Add/remove based on your app's needs
RUN docker-php-ext-install pdo_sqlite mbstring exif pcntl bcmath gd

# Get Composer from composer stage
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Copy application
COPY . /var/www

# Create SQLite database directory
RUN mkdir -p /var/www/database

# Copy production environment file
RUN cp /var/www/.env.docker /var/www/.env

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

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
```

---

## Step 5: Create `docker-compose.yml`

**Key points**:
- Use `image: your-app-name:latest` (NOT `build:`)
- Only mount the database volume (NOT the entire app directory)
- Use a unique port for each project

```yaml
services:
  app:
    image: your-app-name:latest
    container_name: your-app-name-app
    restart: unless-stopped
    ports:
      - "YOUR_UNIQUE_PORT:80"  # e.g., 27270:80
    volumes:
      - sqlite-data:/var/www/database
    networks:
      - your-app-network
    environment:
      - APP_ENV=production
      - DB_CONNECTION=sqlite
      - DB_DATABASE=/var/www/database/database.sqlite

  # Optional: Uncomment when you need background processing
  # redis:
  #   image: redis:alpine
  #   container_name: your-app-name-redis
  #   restart: unless-stopped
  #   networks:
  #     - your-app-network

  # queue:
  #   image: your-app-name:latest
  #   container_name: your-app-name-queue
  #   restart: unless-stopped
  #   volumes:
  #     - sqlite-data:/var/www/database
  #   command: php artisan queue:work --sleep=3 --tries=3
  #   depends_on:
  #     - app
  #     - redis
  #   networks:
  #     - your-app-network
  #   environment:
  #     - APP_ENV=production
  #     - DB_CONNECTION=sqlite
  #     - DB_DATABASE=/var/www/database/database.sqlite

networks:
  your-app-network:
    driver: bridge

volumes:
  sqlite-data:
    driver: local
```

---

## Step 6: Build and Deploy

### Build the Image

```bash
cd /path/to/your-project
docker build -t your-app-name:latest .
```

**Troubleshooting build issues**:
- If npm fails: Check `package.json` and `package-lock.json` exist
- If composer fails: Make sure `composer.json` is valid
- If PHP extensions fail: Add the required `-dev` packages to `apt-get install`

### Deploy to Portainer

1. Open Portainer UI
2. Go to **Stacks** → **Add stack**
3. Name: `your-app-name`
4. Paste contents of `docker-compose.yml`
5. Click **Deploy the stack**
6. **Done!** Open `http://localhost:YOUR_PORT`

### Verify It's Working

1. Check container logs in Portainer:
   - Should see "Running database migrations..."
   - Should see "Optimizing Laravel for production..."
   - Should see "Application ready!"
2. Access the app in your browser
3. Everything should work - no manual commands needed!

---

## Updating the Application

When you make changes to the code:

```bash
# Step 1: Pull latest code
git pull

# Step 2: Rebuild the image
docker build -t your-app-name:latest .

# Step 3: Restart in Portainer
# Click "Restart" on the stack
# OR via command line:
docker-compose down && docker-compose up -d
```

The entrypoint script automatically runs migrations and optimizations on every restart.

---

## Common Mistakes to Avoid

### ❌ DON'T use php:8.4-fpm
- This requires a separate Nginx/Apache container
- More complex and requires mounting config files

### ✅ DO use php:8.4-apache
- Everything in one container
- No config file mounting needed
- Works perfectly with Portainer

### ❌ DON'T mount the entire app directory
```yaml
volumes:
  - ./:/var/www  # BAD - Portainer can't access local files
```

### ✅ DO only mount data directories
```yaml
volumes:
  - sqlite-data:/var/www/database  # GOOD - uses Docker volumes
```

### ❌ DON'T use `build:` in docker-compose.yml
```yaml
app:
  build:
    context: .
    dockerfile: Dockerfile  # BAD - Portainer can't build from local files
```

### ✅ DO use `image:` in docker-compose.yml
```yaml
app:
  image: your-app-name:latest  # GOOD - uses pre-built image
```

---

## Additional Services (Optional)

### Adding Redis for Queues

1. Uncomment the `redis` and `queue` services in `docker-compose.yml`
2. Update `.env.docker`:
   ```env
   QUEUE_CONNECTION=redis
   CACHE_STORE=redis
   REDIS_HOST=redis
   REDIS_PORT=6379
   ```
3. Rebuild and redeploy:
   ```bash
   docker build -t your-app-name:latest .
   # Restart in Portainer
   ```

### Adding Scheduler

Uncomment the `scheduler` service in `docker-compose.yml` and restart.

---

## Database Backups

### Backup Database

```bash
# Copy from container to local machine
docker cp your-app-name-app:/var/www/database/database.sqlite ./backup.sqlite
```

### Restore Database

```bash
# Copy from local machine to container
docker cp ./backup.sqlite your-app-name-app:/var/www/database/database.sqlite

# Fix permissions
docker exec your-app-name-app chown www-data:www-data /var/www/database/database.sqlite
docker exec your-app-name-app chmod 664 /var/www/database/database.sqlite
```

---

## Quick Checklist

Before building your Docker image:

- [ ] `.dockerignore` created
- [ ] `.env.docker` created with production settings
- [ ] `docker/docker-entrypoint.sh` created and executable
- [ ] `Dockerfile` uses `php:X.X-apache` (NOT `-fpm`)
- [ ] `docker-compose.yml` uses `image:` (NOT `build:`)
- [ ] `docker-compose.yml` only mounts volumes (NOT local directories)
- [ ] Unique port chosen for this project
- [ ] All required PHP extensions listed in Dockerfile

Then:
1. `docker build -t your-app-name:latest .`
2. Deploy via Portainer
3. Visit `http://localhost:YOUR_PORT`
4. ✅ Done!

---

## Summary

The key to success:
1. **Use Apache** (`php:8.4-apache`) - everything in one container
2. **Pre-build the image locally** - Portainer just runs it
3. **Use Docker volumes** - not local file mounts
4. **Automate everything** - migrations run on startup via entrypoint script

Follow this pattern and you'll have zero-hassle Docker deployments every time! 🚀
