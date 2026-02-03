#!/bin/bash

# Restore the application state by pulling data from backups or resetting the database

# Define variables
BACKUP_DIR="${DATA_FOLDER}/backups"
MYSQL_CONTAINER="sqldb"
KIMAI_CONTAINER="kimai"

# Function to restore MySQL database from backup
restore_mysql() {
    echo "Restoring MySQL database from backup..."
    if [ -d "$BACKUP_DIR" ]; then
        for backup_file in "$BACKUP_DIR"/*.sql; do
            if [ -f "$backup_file" ]; then
                echo "Restoring from $backup_file..."
                docker exec -i "$MYSQL_CONTAINER" mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" < "$backup_file"
            fi
        done
    else
        echo "No backup directory found."
    fi
}

# Function to reset Kimai application state
reset_kimai() {
    echo "Resetting Kimai application state..."
    docker exec -it "$KIMAI_CONTAINER" php bin/console kimai:reset
}

# Main script execution
restore_mysql
reset_kimai

echo "Restore process completed."