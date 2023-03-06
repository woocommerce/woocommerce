#!/bin/sh

PLUGIN_SLUG="woocommerce"
PROJECT_PATH=$(pwd)
BUILD_PATH="${PROJECT_PATH}/build"
DEST_PATH="$BUILD_PATH/$PLUGIN_SLUG"

echo "Generating build directory..."
rm -rf "$BUILD_PATH"
mkdir -p "$DEST_PATH"

echo "Cleaning up assets..."
find "$PROJECT_PATH/assets/css/." ! -name '.gitkeep' -type f -exec rm -f {} + && find "$PROJECT_PATH/assets/client/." ! -name '.gitkeep' -type f -exec rm -f {} + && find "$PROJECT_PATH/assets/js/." ! -name '.gitkeep' -type f -exec rm -f {} +

echo "Installing PHP and JS dependencies..."
pnpm install
echo "Running JS Build..."
pnpm -w run build --filter=woocommerce || exit "$?"
echo "Cleaning up PHP dependencies..."
composer install --no-dev || exit "$?"
echo "Run makepot..."
pnpm -r --filter=woocommerce run makepot || exit "$?"
echo "Syncing files..."
rsync -rc --exclude-from="$PROJECT_PATH/.distignore" "$PROJECT_PATH/" "$DEST_PATH/" --delete --delete-excluded

echo "Generating zip file..."
cd "$BUILD_PATH" || exit
zip -q -r "${PLUGIN_SLUG}.zip" "$PLUGIN_SLUG/"

cd "$PROJECT_PATH" || exit
mv "$BUILD_PATH/${PLUGIN_SLUG}.zip" "$PROJECT_PATH"
echo "${PLUGIN_SLUG}.zip file generated!"

echo "Build done!"
