#!/usr/bin/env bash

set -e  # Exit immediately on error

cd "$(dirname "$0")"  # Ensure we're in the correct directory

# Ensure composer is installed
if ! command -v composer >/dev/null 2>&1; then
  echo "❌ Composer is not installed. Please install Composer first."
  exit 1
fi

# Skip if we are not in the monorepo
if [ ! -f "../../../../plugins/woocommerce/vendor/bin/mozart" ]; then
  echo "❌ We are not in the monorepo. Skipping vendor-prefixed setup."
  exit 0 # Returning 0 incase we are in CI environment
fi

# Run composer install
composer install --quiet
