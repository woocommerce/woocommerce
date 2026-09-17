#!/bin/sh

# Possible slugs: woocommerce-dev (live branches) and woocommerce (plugin build)
if [ -z "$PLUGIN_SLUG" ]; then
	PLUGIN_SLUG="woocommerce"
fi

PROJECT_PATH=$(pwd)
BUILD_PATH="${PROJECT_PATH}/build"
DEST_PATH="$BUILD_PATH/$PLUGIN_SLUG"
XDEBUG_MODE=off

if [ -z "$ZIP_COMPRESSION_LEVEL" ]; then
	ZIP_COMPRESSION_LEVEL="9"
fi

if ! echo "$ZIP_COMPRESSION_LEVEL" | grep -Eq '^[0-9]$'; then
	echo "ZIP_COMPRESSION_LEVEL must be a number from 0 to 9."
	exit 1
fi

echo "Generating build directory..."
rm -rf "$BUILD_PATH"
mkdir -p "$DEST_PATH"

echo "Cleaning up assets..."
find "$PROJECT_PATH/assets/css/." ! -name '.gitkeep' -type f -exec rm -f {} + && find "$PROJECT_PATH/assets/client/." ! -name '.gitkeep' -type f -exec rm -f {} + && find "$PROJECT_PATH/assets/js/." ! -name '.gitkeep' -type f -exec rm -f {} +

if [ "$SKIP_INSTALL" = "1" ]; then
	echo "Skipping PHP and JS dependency installation..."
else
	echo "Installing PHP and JS dependencies..."
	pnpm install --frozen-lockfile
fi

echo "Running JS Build..."
if [ -z "$NODE_ENV" ]; then
	export NODE_ENV=production
fi
pnpm --filter='@woocommerce/plugin-woocommerce' build || exit "$?"
echo "Cleaning up PHP dependencies..."
composer install --no-dev --quiet || exit "$?"
# Makepot runs by default so every distributed zip ships translation templates;
# only transient builds (e.g. CI test artifacts) should opt out.
if [ "$BUILD_ZIP_WITH_MAKEPOT" = "0" ]; then
	echo "Skipping makepot. Unset BUILD_ZIP_WITH_MAKEPOT to include translation templates."
else
	echo "Run makepot..."
	pnpm --filter=@woocommerce/plugin-woocommerce makepot || exit "$?"
fi
echo "Syncing files..."
rsync -rc --exclude-from="$PROJECT_PATH/.distignore" "$PROJECT_PATH/" "$DEST_PATH/" --delete --delete-excluded

echo "Regenerating autoloader for production..."
cd "$DEST_PATH" || exit
composer dump-autoload --no-dev --quiet --optimize || exit "$?"
# Remove composer files from the build.
rm composer.*

echo "Generating zip file..."
cd "$BUILD_PATH" || exit
zip -q -r "-${ZIP_COMPRESSION_LEVEL}" "${PLUGIN_SLUG}.zip" "$PLUGIN_SLUG/"
cd "$PROJECT_PATH" || exit
mv "$BUILD_PATH/${PLUGIN_SLUG}.zip" "$PROJECT_PATH"
echo "${PLUGIN_SLUG}.zip file generated!"

echo "Build done!"
