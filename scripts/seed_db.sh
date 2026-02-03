#!/bin/bash

# Define the project directory
PROJECT_DIR="$(dirname "$0")/.."
cd "$PROJECT_DIR" || exit 1

SEED_FILE="seed.sql"

if [ ! -f "$SEED_FILE" ]; then
    echo "Error: $SEED_FILE not found in project root."
    exit 1
fi

echo "Seeding database from $SEED_FILE..."

# We access the environment variables that are present inside the container.
# The -T flag disables pseudo-tty allocation, allowing us to pipe the file content.
docker compose exec -T sqldb sh -c 'exec mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' < "$SEED_FILE"

if [ $? -eq 0 ]; then
    echo "Database seeded successfully!"
else
    echo "Error seeding database."
    exit 1
fi
