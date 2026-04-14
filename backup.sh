#!/bin/bash
set -e

BACKUP_DIR="./backups"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p "$BACKUP_DIR"

echo "Backing up database..."
docker compose exec -T postgres pg_dump -U postgres workflow | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"

echo "Backing up storage..."
# Ensure the storage/app directory exists locally if bind-mounted or use docker
docker compose exec -T app tar -czf - -C /var/www/html/storage/app . > "$BACKUP_DIR/storage_$DATE.tar.gz"

echo "Cleaning up backups older than 30 days..."
find "$BACKUP_DIR" -type f -mtime +30 -name "*.gz" -delete

echo "Backup completed successfully!"
