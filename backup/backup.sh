#!/bin/bash

# Configuration and defaults
BACKUP_INTERVAL="${BACKUP_INTERVAL:-86400}" # Default is 1 day (86400 seconds)
BACKUP_DIR="/tmp"
REMOTE_PATH="${RCLONE_REMOTE_NAME:-Dropbox}:${RCLONE_REMOTE_PATH:-/kimai_backups}"
MYSQL_HOST="${MYSQL_HOST:-sqldb}"

echo "Starting Kimai backup service..."
echo "Interval: $BACKUP_INTERVAL seconds"
echo "Remote Path: $REMOTE_PATH"

# Wait for MySQL to be ready (simple check)
echo "Waiting for MySQL at $MYSQL_HOST..."
until mysqladmin ping -h"$MYSQL_HOST" -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" --silent; do
    echo "MySQL is unavailable - sleeping"
    sleep 5
done
echo "MySQL is up!"

while true; do
    echo "----------------------------------------"
    echo "Starting backup process at $(date)"

    TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
    FILENAME="kimai_backup_${TIMESTAMP}.sql.gz"
    FULL_PATH="$BACKUP_DIR/$FILENAME"

    # 1. Create database dump
    echo "Creating database dump..."
    if mysqldump -h "$MYSQL_HOST" -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" --single-transaction --quick --lock-tables=false --no-tablespaces | gzip > "$FULL_PATH"; then
        echo "Database dump created successfully: $FILENAME"
        ls -lh "$FULL_PATH"

        # 2. Upload to Remote Storage
        echo "Uploading to $REMOTE_PATH..."
        if rclone copy "$FULL_PATH" "$REMOTE_PATH"; then
            echo "Upload successful."
        else
            echo "ERROR: Upload failed!"
        fi

        # 3. Cleanup local file
        rm "$FULL_PATH"

        # 4. Prune old backups (Keep recent 30)
        echo "Checking retention policy (keeping last 30)..."
        # Sort by filename which includes timestamp (oldest first)
        EXISTING_BACKUPS=$(rclone lsf "$REMOTE_PATH" --files-only | sort)
        # Filter empty lines
        EXISTING_BACKUPS=$(echo "$EXISTING_BACKUPS" | grep -v "^$")
        COUNT=$(echo "$EXISTING_BACKUPS" | wc -l)

        if [ "$COUNT" -gt 30 ]; then
            TO_DELETE=$((COUNT - 30))
            echo "Found $COUNT backups. Deleting oldest $TO_DELETE..."
            echo "$EXISTING_BACKUPS" | head -n "$TO_DELETE" | while read -r FILE; do
                if [ -n "$FILE" ]; then
                    echo "Deleting old backup: $FILE"
                    rclone deletefile "$REMOTE_PATH/$FILE"
                fi
            done
        else
            echo "Total backups: $COUNT. No pruning needed."
        fi
    else
        echo "ERROR: Database dump failed!"
    fi

    echo "Backup process completed. Sleeping for $BACKUP_INTERVAL seconds..."
    echo "----------------------------------------"
    sleep "$BACKUP_INTERVAL"
done
