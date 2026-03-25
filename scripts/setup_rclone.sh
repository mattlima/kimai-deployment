#!/bin/bash
# Helper to generate rclone.conf via docker

RCLONE_CONFIG_DIR="$(pwd)/rclone_config"
RCLONE_CONFIG_FILE="$RCLONE_CONFIG_DIR/rclone.conf"

mkdir -p "$RCLONE_CONFIG_DIR"
touch "$RCLONE_CONFIG_FILE"

echo "--------------------------------------------------------"
echo "Launching rclone configuration wizard..."
echo "Please configure a new remote named 'dropbox' and follow the instructions."
echo "Your configuration will be saved to: $RCLONE_CONFIG_FILE"
echo ""

docker run --rm -it \
    -v "$RCLONE_CONFIG_DIR":/config/rclone \
    rclone/rclone config

echo ""
echo "Configuration complete. Configuration saved."
