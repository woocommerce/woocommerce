#!/bin/sh

PLUGIN_SLUG="woocommerce"
PROJECT_PATH=$(pwd)
BUILD_PATH="${PROJECT_PATH}/build"
DEST_PATH="$BUILD_PATH/$PLUGIN_SLUG"

echo "Generating build directory..."
rm -rf "$BUILD_PATH"
mkdir -p "$DEST_PATH"

echo "Installing PHP and JS dependencies..."
npm install
composer install || exit "$?"
echo "Running JS Build..."
npm run build || exit "$?"
echo "Cleaning up PHP dependencies..."
composer install --no-dev || exit "$?"

echo "Syncing files..."
rsync -rc --exclude-from="$PROJECT_PATH/.distignore" "$PROJECT_PATH/" "$DEST_PATH/" --delete --delete-excluded

echo "Restoring PHP dependencies..."
composer install || exit "$?"
npm run build || exit "$?"

echo "Generating zip file..."
cd "$BUILD_PATH" || exit
zip -q -r "${PLUGIN_SLUG}.zip" "$PLUGIN_SLUG/"
echo "$BUILD_PATH/${PLUGIN_SLUG}.zip file generated!"

echo "Build done!"
