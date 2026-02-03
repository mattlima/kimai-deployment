#!/bin/bash

# Define the project directory
PROJECT_DIR="$(dirname "$0")/.."
cd "$PROJECT_DIR" || exit 1

echo "Setting up local development environment..."

# 0. Create external volumes if they don't exist
echo "Checking Docker volumes..."
volumes=(caddy_data kimai_data kimai_plugins mysql_data)
for vol in "${volumes[@]}"; do
    if ! docker volume inspect "$vol" >/dev/null 2>&1; then
        echo "Creating volume: $vol"
        docker volume create "$vol"
    fi
done

# 1. Build & Start
echo "Building and starting containers..."
# Ensure we build the custom image
docker compose build kimai
docker compose up -d

# 2. Wait for services
echo "Waiting for services to initialize..."
sleep 3

# 3. Run migrations
echo "Running migrations..."
docker compose exec -T kimai /opt/kimai/bin/console doctrine:migrations:migrate --no-interaction

# 4. Clear Cache (important for dev when changing templates)
echo "Clearing Kimai cache..."
docker compose exec -T -u www-data kimai /opt/kimai/bin/console cache:clear
docker compose exec -T -u www-data kimai /opt/kimai/bin/console cache:warmup

# 5. Open in browser
URL="http://localhost:8888"
echo "Ready! Kimai is running at $URL"
if [[ "$OSTYPE" == "darwin"* ]]; then
  open "$URL"
elif command -v xdg-open > /dev/null; then
  xdg-open "$URL"
fi
