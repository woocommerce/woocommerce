#!/usr/bin/env bash

set -euo pipefail

# Script to prepare email-editor package for publishing to Packagist.org
# This script creates a build directory with the required structure and files

# Define paths
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PACKAGE_DIR="$(dirname "$SCRIPT_DIR")"
BUILD_DIR="$PACKAGE_DIR/build"

echo "🚀 Preparing email-editor package for Packagist.org publishing..."

# Clean up existing build directory
if [ -d "$BUILD_DIR" ]; then
    echo "🧹 Cleaning up existing build directory..."
    rm -rf "$BUILD_DIR"
fi

# Create build directory structure
echo "📁 Creating build directory structure..."
mkdir -p "$BUILD_DIR/woocommerce/email-editor"

# Create mirrors.txt file
echo "📝 Creating mirrors.txt file..."
echo "woocommerce/email-editor" > "$BUILD_DIR/mirrors.txt"

# Copy required files and directories using rsync
echo "📋 Copying package files..."
rsync -avhW --quiet \
    "$PACKAGE_DIR/src" \
    "$PACKAGE_DIR/composer.json" \
    "$PACKAGE_DIR/composer.lock" \
    "$PACKAGE_DIR/changelog.md" \
    "$PACKAGE_DIR/license.txt" \
    "$PACKAGE_DIR/SECURITY.md" \
    "$BUILD_DIR/woocommerce/email-editor/"

# Copy vendor-prefixed directory
echo "📋 Copying vendor-prefixed directory..."
mkdir -p "$BUILD_DIR/woocommerce/email-editor/vendor-prefixed"
rsync -avhW --quiet \
    "$PACKAGE_DIR/vendor-prefixed/classes" \
    "$PACKAGE_DIR/vendor-prefixed/packages" \
    "$BUILD_DIR/woocommerce/email-editor/vendor-prefixed/"

# Copy mirror-readme.md as README.md
echo "📝 Copying mirror-readme.md as README.md..."
cp "$PACKAGE_DIR/tasks/mirror-readme.md" "$BUILD_DIR/woocommerce/email-editor/README.md"

echo "✅ Build completed successfully!"

