#!/usr/bin/env bash

set -e  # Exit immediately on error

cd "$(dirname "$0")"  # Ensure we're in the script directory

# Ensure composer is installed
if ! command -v composer >/dev/null 2>&1; then
  echo "❌ Composer is not installed. Please install Composer first."
  exit 1
fi

# Determine which PHPStan config file to use
CONFIG_FILE="phpstan.neon"
for arg in "$@"; do
  if [ "$arg" = "php7" ]; then
    CONFIG_FILE="phpstan-7.neon"
    break
  fi
done

# Check if phpstan is available via composer
if ! ./vendor/bin/phpstan --version >/dev/null 2>&1; then
  echo "🔧 PHPStan not found. Installing dependencies..."
  composer install --quiet
fi

# Run PHPStan
echo "▶️ Running PHPStan with config: $CONFIG_FILE"
vendor/bin/phpstan analyse -c "$CONFIG_FILE" --memory-limit=2G

# Clean up PHPStan directories by default unless --skip-cleanup is specified
CLEANUP=true
for arg in "$@"; do
  if [ "$arg" = "--skip-cleanup" ]; then
    CLEANUP=false
    break
  fi
done

if [ "$CLEANUP" = true ]; then
  echo "🧹 Cleaning up PHPStan directories."
  rm -rf vendor/ temp/
fi

echo "✅ PHPStan completed successfully."