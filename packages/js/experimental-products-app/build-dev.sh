#!/usr/bin/env bash
# Full dev build: compile TS → admin webpack. Run from anywhere in the repo.
set -e
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ADMIN_DIR="$SCRIPT_DIR/../../../plugins/woocommerce/client/admin"

echo "1/3 compiling CJS..."
cd "$SCRIPT_DIR" && npx tsc --project tsconfig-cjs.json --noCheck

echo "2/3 compiling ESM..."
npx tsc --project tsconfig.json --noCheck

echo "3/3 rebuilding admin bundle (clearing cache)..."
cd "$ADMIN_DIR" && rm -rf node_modules/.cache/webpack-development
node_modules/.bin/webpack --config webpack.config.js

echo "Done."
