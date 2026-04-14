#!/bin/bash
set -e

echo "Starting deployment..."

# Pull latest images
docker compose -f docker-compose.yml -f docker-compose.prod.yml pull

# Run migrations and cache (since app depends on db, we can run it against the app container)
docker compose -f docker-compose.yml -f docker-compose.prod.yml run --rm app php artisan migrate --force
docker compose -f docker-compose.yml -f docker-compose.prod.yml run --rm app php artisan config:cache
docker compose -f docker-compose.yml -f docker-compose.prod.yml run --rm app php artisan route:cache
docker compose -f docker-compose.yml -f docker-compose.prod.yml run --rm app php artisan view:cache
docker compose -f docker-compose.yml -f docker-compose.prod.yml run --rm app php artisan permission:cache-reset

# Start the full stack
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d

echo "Wait for services to initialize..."
sleep 5

# Health checks
echo "Running health checks..."

        if docker compose exec -T app php artisan about > /dev/null 2>&1; then
            echo "✅ App is healthy (checked via artisan about)"
        else
            echo "❌ App health check failed"
            exit 1
        fi

if docker compose exec -T postgres pg_isready -U postgres -d workflow > /dev/null; then
    echo "✅ Database connection is ready"
else
    echo "❌ DB health check failed"
    exit 1
fi

if docker compose exec -T app touch /var/www/html/storage/app/healthcheck.txt && docker compose exec -T app rm /var/www/html/storage/app/healthcheck.txt; then
    echo "✅ Storage volume is mounted correctly"
else
    echo "❌ Storage volume check failed"
    exit 1
fi

echo "Deployment completed successfully!"
