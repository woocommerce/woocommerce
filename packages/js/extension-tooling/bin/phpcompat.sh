#!/usr/bin/env bash
set -euo pipefail

if [ "$#" -gt 1 ]; then
    echo "Usage: pnpm run phpcompat [production.zip]" >&2
    exit 1
fi

# The ZIP name and its root folder come from package.json config.woo.
slug="$(bash "$(dirname "$0")/woo-config.sh" .slug)"
plugin_dir="$(bash "$(dirname "$0")/woo-config.sh" .zip.root)"
if [ "$#" -eq 0 ]; then
    pnpm run build:zip
fi
archive="${1:-deploy/$slug.zip}"

# Keep the compatibility checker separate from development dependencies.
composer --working-dir=tools/php-quality install --no-interaction --prefer-dist --no-progress
production_dir="$(mktemp -d)"
trap 'rm -rf "$production_dir"' EXIT
unzip -q "$archive" -d "$production_dir"
php tools/php-quality/vendor/bin/phpcs "$production_dir/$plugin_dir" \
    --standard=phpcs-compat.xml.dist -ps --basepath="$production_dir/$plugin_dir"
