# Docker Deployment Guide

This guide explains how to run the Debt Manager application using Docker and Portainer.

## Prerequisites

- Docker Engine or Docker Desktop
- Portainer (optional)

## Quick Start

### Step 1: Build the Image

```bash
cd P:\Herd\economy
docker build -t debt-manager:latest .
```

### Step 2: Deploy

**Option A: Using Portainer**

1. Go to Portainer UI → "Stacks" → "Add stack"
2. Name it: `debt-manager`
3. Paste the contents of `docker-compose.yml`
4. Click "Deploy the stack"
5. **Done!** Open `http://localhost:27270` (or your custom port)

**Option B: Using Command Line**

```bash
docker-compose up -d
```

**That's it!** The application automatically:
- ✅ Creates the SQLite database
- ✅ Runs all migrations
- ✅ Seeds the database (if needed)
- ✅ Optimizes Laravel for production

Just open `http://localhost:27270` and start using the app!

## Stopping the Application

**Portainer**: Click "Stop" on the stack

**Command Line**:
```bash
docker-compose down
```

**WARNING**: To delete all data including the database:
```bash
docker-compose down -v
```

## Services

### Active Services
- **app**: PHP 8.4-FPM application server
- **nginx**: Web server (accessible at `http://localhost:8000`)

### Optional Services (Commented Out)
To enable background processing, uncomment these services in `docker-compose.yml`:

- **redis**: In-memory cache and queue backend
- **queue**: Laravel queue worker for background jobs
- **scheduler**: Laravel task scheduler

## Database Persistence

The SQLite database is stored in a Docker volume named `sqlite-data`. This means your data persists even when containers are stopped or rebuilt.

### Backup Database

```bash
# Copy database out of container
docker-compose exec app cp /var/www/database/database.sqlite /var/www/database.backup.sqlite

# From host machine, copy from container to local
docker cp debt-manager-app:/var/www/database/database.sqlite ./database.backup.sqlite
```

### Restore Database

```bash
# Copy local backup into container
docker cp ./database.backup.sqlite debt-manager-app:/var/www/database/database.sqlite

# Fix permissions
docker-compose exec app chown www-data:www-data /var/www/database/database.sqlite
docker-compose exec app chmod 664 /var/www/database/database.sqlite
```

## Configuration

### Change Port

Edit `docker-compose.yml`:

```yaml
nginx:
  ports:
    - "9000:80"  # Change 9000 to your desired port
```

### Enable Background Jobs

1. Uncomment the `redis`, `queue`, and `scheduler` services in `docker-compose.yml`
2. Update `.env.docker`:
   ```
   QUEUE_CONNECTION=redis
   CACHE_STORE=redis
   REDIS_HOST=redis
   ```
3. Rebuild and restart:
   ```bash
   docker-compose down
   docker-compose up -d --build
   ```

## Updating the Application

When you make changes to the code:

```bash
# Step 1: Pull latest code
git pull

# Step 2: Rebuild the image
docker build -t debt-manager:latest .

# Step 3: Restart the stack
# In Portainer: Click "Restart" on the stack
# Or via command line:
docker-compose down && docker-compose up -d
```

**That's it!** The entrypoint script automatically runs migrations and optimizations on startup.

## Troubleshooting

### Container won't start

```bash
# Check logs
docker-compose logs app
docker-compose logs nginx

# Rebuild from scratch
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

### Permission errors

```bash
# Fix permissions inside container
docker-compose exec app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/database
docker-compose exec app chmod -R 775 /var/www/storage /var/www/bootstrap/cache /var/www/database
```

### Database not found

```bash
# Create database file
docker-compose exec app touch /var/www/database/database.sqlite
docker-compose exec app chown www-data:www-data /var/www/database/database.sqlite
docker-compose exec app chmod 664 /var/www/database/database.sqlite

# Run migrations
docker-compose exec app php artisan migrate --force
```

### Port already in use

Either:
1. Change the port in `docker-compose.yml` (nginx service)
2. Stop the conflicting service using that port

### Can't connect to app

1. Verify containers are running: `docker-compose ps`
2. Check nginx logs: `docker-compose logs nginx`
3. Check app logs: `docker-compose logs app`
4. Verify port mapping: `docker port debt-manager-nginx`

## Useful Commands

```bash
# View running containers
docker-compose ps

# View logs (all services)
docker-compose logs

# View logs (specific service)
docker-compose logs app
docker-compose logs nginx

# Follow logs in real-time
docker-compose logs -f

# Execute artisan commands
docker-compose exec app php artisan [command]

# Access app container shell
docker-compose exec app bash

# Restart specific service
docker-compose restart app
docker-compose restart nginx

# Stop all containers
docker-compose down

# Remove everything including volumes (WARNING: deletes database)
docker-compose down -v
```

## Development vs Production

This Docker setup is configured for **production use**:
- `APP_DEBUG=false`
- `APP_ENV=production`
- Optimized autoloader
- Cached routes, views, and config
- No dev dependencies

For development, continue using Laravel Herd on your local machine.
