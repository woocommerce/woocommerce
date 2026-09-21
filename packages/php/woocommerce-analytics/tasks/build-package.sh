#!/usr/bin/env bash

set -euo pipefail

# Script to prepare woocommerce-analytics package for publishing to Packagist.org
# This script creates a build directory with the required structure and files

# Define paths
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PACKAGE_DIR="$(dirname "$SCRIPT_DIR")"
BUILD_DIR="$PACKAGE_DIR/build"
DIST_DIR="$BUILD_DIR/Automattic/woocommerce-analytics"

echo "Preparing woocommerce-analytics package for Packagist.org publishing..."

# Clean up existing build directory
if [ -d "$BUILD_DIR" ]; then
    echo "Cleaning up existing build directory..."
    rm -rf "$BUILD_DIR"
fi

# Install JS dependencies. The mirror workflow does not run `pnpm install`
# before invoking us, so node_modules may be missing.
echo "Installing JS dependencies..."
pnpm install --filter "@automattic/woocommerce-analytics" --frozen-lockfile

# Build JS assets first
echo "Building JS assets..."
pnpm run build-production

# Create build directory structure
echo "Creating build directory structure..."
mkdir -p "$DIST_DIR"

# Create mirrors.txt file
echo "Creating mirrors.txt file..."
echo "Automattic/woocommerce-analytics" > "$BUILD_DIR/mirrors.txt"

# Copy PHP source
echo "Copying PHP source files..."
rsync -avhW --quiet \
    "$PACKAGE_DIR/src/" \
    "$DIST_DIR/src/" \
    --exclude="client/"

# Stamp the composer.json version into the shipped copy. The constant in the
# repo is not bumped by the release tooling, so this is what keeps the version
# a release reports about itself correct.
echo "Stamping PACKAGE_VERSION..."
PACKAGE_VERSION="$(node -p "require('$PACKAGE_DIR/composer.json').version")"
if ! [[ "$PACKAGE_VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+ ]]; then
    echo "composer.json has no usable version (got '$PACKAGE_VERSION')" >&2
    exit 1
fi
MAIN_CLASS_FILE="$DIST_DIR/src/class-woocommerce-analytics.php"
perl -pi -e "s/const PACKAGE_VERSION = '[^']*';/const PACKAGE_VERSION = '$PACKAGE_VERSION';/" "$MAIN_CLASS_FILE"
if ! grep -qF "const PACKAGE_VERSION = '$PACKAGE_VERSION';" "$MAIN_CLASS_FILE"; then
    echo "Failed to stamp PACKAGE_VERSION $PACKAGE_VERSION into $MAIN_CLASS_FILE" >&2
    exit 1
fi

# Copy built JS assets (main bundle, asset manifest, and all chunks)
echo "Copying built JS assets..."
mkdir -p "$DIST_DIR/build"
rsync -avhW --quiet \
    --include="*.js" \
    --include="*.asset.php" \
    --exclude="*" \
    "$PACKAGE_DIR/build/" \
    "$DIST_DIR/build/"

# Copy package metadata
echo "Copying package metadata..."
cp "$PACKAGE_DIR/composer.json" "$DIST_DIR/"
cp "$PACKAGE_DIR/package.json" "$DIST_DIR/"
cp "$PACKAGE_DIR/CHANGELOG.md" "$DIST_DIR/"
cp "$PACKAGE_DIR/LICENSE.txt" "$DIST_DIR/LICENSE.txt"
cp "$PACKAGE_DIR/SECURITY.md" "$DIST_DIR/"

cp "$PACKAGE_DIR/.gitattributes" "$DIST_DIR/"

# Copy .github workflows for the mirror repo (autotagger, readonly)
echo "Copying mirror GitHub workflows..."
cp -r "$PACKAGE_DIR/tasks/mirror-github/." "$DIST_DIR/.github/"

# Copy README.md
echo "Copying README.md..."
cp "$PACKAGE_DIR/README.md" "$DIST_DIR/README.md"

echo "Build completed successfully!"
