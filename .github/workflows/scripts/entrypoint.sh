#!/bin/sh

# Set variables
GENERATE_ZIP=false
BUILD_PATH="./build"
WORKING_DIRECTORY="$GITHUB_WORKSPACE/plugins/woocommerce"
ZIP_DIRECTORY="plugins/woocommerce/build"

# Set options based on user input
if [ -z "$1" ]; then
  GENERATE_ZIP="$1"
fi

# If not configured defaults to repository name
if [ -z "$PLUGIN_SLUG" ]; then
  PLUGIN_SLUG=${GITHUB_REPOSITORY#*/}
fi

# Set GitHub "path" output
DEST_PATH="$BUILD_PATH/$PLUGIN_SLUG"
echo "::set-output name=path::$DEST_PATH"

echo "Installing PHP and JS dependencies..."

// Make sure PNPM is available
npm install -g pnpm

// Make sure Grunt is available
npm install -g grunt-cli

// Install repo dependencies
echo "PNPM install..."
pnpm install

// Install WooCommerce dependencies
echo "Composer install..."
cd "$WORKING_DIRECTORY" || exit
composer install || exit "$?"

echo "Running JS Build..."
pnpm nx build woocommerce || exit "$?"
echo "Cleaning up PHP dependencies..."
composer install --no-dev || exit "$?"

echo "Generating build directory..."
rm -rf "$BUILD_PATH"
mkdir -p "$DEST_PATH"

if [ -r "${WORKING_DIRECTORY}/.distignore" ]; then
  if [ -z "$BUILD_ENV" ]; then
    echo "No build environment specified, defaulting to a production build zip."
  fi

  if [ "$BUILD_ENV" = "e2e" ]; then
    echo "Creating a zip for e2e tests."
    mkdir -p "$DEST_PATH/node_modules/.bin" &&
    cp "${WORKING_DIRECTORY}/node_modules/.bin/wc-e2e" "$DEST_PATH/node_modules/.bin" &&
    cp "${WORKING_DIRECTORY}/package.json" "$DEST_PATH" &&
    cp "${WORKING_DIRECTORY}/project.json" "$DEST_PATH" &&
    cp -r "${WORKING_DIRECTORY}/tests" "$DEST_PATH" &&
    cp -r "${WORKING_DIRECTORY}/sample-data" "$DEST_PATH"
  fi

  if [ "$BUILD_ENV" = "mirrors" ]; then
    echo "Creating a zip for production mirror repository."
    cp "${WORKING_DIRECTORY}/composer.json" "$DEST_PATH"
  fi

  rsync -rc --exclude-from="$WORKING_DIRECTORY/.distignore" "$WORKING_DIRECTORY/" "$DEST_PATH/"
else
  rsync -rc "$WORKING_DIRECTORY/" "$DEST_PATH/" --delete
fi

if ! $GENERATE_ZIP; then
  echo "Generating zip file..."
  cd "$BUILD_PATH" || exit
  zip -r "${PLUGIN_SLUG}.zip" "$PLUGIN_SLUG/"
  # Set GitHub "zip_path" output
  echo "::set-output name=zip_path::${ZIP_DIRECTORY}/${PLUGIN_SLUG}.zip"
  echo "Zip file generated!"
fi

echo "Build done!"
