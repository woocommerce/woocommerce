#!/bin/bash

# sync-package.sh
# Usage: ./bin/sync-package.sh email-editor [once]
# This will sync changes from ../../packages/php/email-editor to ./packages/email-editor
# Add "once" as a second parameter to perform a one-time sync without watching for changes

PACKAGE_NAME=$1
SYNC_MODE=$2

if [ -z "$PACKAGE_NAME" ]; then
    echo "Error: Please provide a package name"
    echo "Usage: ./bin/sync-package.sh <package-name> [once]"
    exit 1
fi

SOURCE_DIR="../../packages/php/$PACKAGE_NAME"
TARGET_DIR="./packages/$PACKAGE_NAME"

if [ ! -d "$SOURCE_DIR" ]; then
    echo "Error: Source directory $SOURCE_DIR does not exist"
    exit 1
fi

if [ ! -d "$TARGET_DIR" ]; then
    echo "Error: Target directory $TARGET_DIR does not exist"
    exit 1
fi

# Initial sync to make sure we're up to date
echo "Syncing from $SOURCE_DIR to $TARGET_DIR"
rsync -av --exclude="vendor" --exclude="tests" --exclude=".git" "$SOURCE_DIR/" "$TARGET_DIR/"
echo "Sync completed at $(date)"

# Exit if one-time sync was requested
if [ "$SYNC_MODE" = "once" ]; then
    echo "One-time sync complete. Exiting."
    exit 0
fi

# Highlight the stop command with decoration
echo ""
echo "========================================"
echo "  WATCHING FOR CHANGES"
echo "  Press [ Ctrl+C ] to stop watching"
echo "========================================"
echo ""


# Watch for changes and sync when they occur
while true; do
    # Watch for changes in the source directory
    changed_files=$(find "$SOURCE_DIR" -type f -newer "$TARGET_DIR" 2>/dev/null)

    if [ -n "$changed_files" ]; then
        echo "Changes detected. Syncing..."
        rsync -av --exclude="vendor" --exclude="tests" --exclude=".git" "$SOURCE_DIR/" "$TARGET_DIR/"
        echo "Sync completed at $(date)"

        # Touch the target directory to update its timestamp
        touch "$TARGET_DIR"
    fi

    # Sleep a bit to avoid high CPU usage
    sleep 1
done
