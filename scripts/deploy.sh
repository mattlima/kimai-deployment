#!/bin/bash

# Define the project directory
PROJECT_DIR="$(dirname "$0")/.."

# Navigate to the project directory
cd "$PROJECT_DIR" || exit 1

echo "Starting deployment..."

# 1. Pull the latest changes from Git
echo "Pulling latest changes..."
git pull origin main

# 2. Build the Docker images
echo "Building Docker images..."
docker compose build kimai

# 3. Restart the containers
echo "Restarting containers..."
docker compose up -d --remove-orphans

# 4. Run database migrations
echo "Running migrations..."
docker compose exec -T kimai bin/console doctrine:migrations:migrate --no-interaction

# 5. Clear Kimai cache (Crucial for templates)
echo "Clearing Kimai cache..."
docker compose exec -T -u www-data kimai bin/console cache:clear
docker compose exec -T -u www-data kimai bin/console cache:warmup

echo "Deployment complete!"
