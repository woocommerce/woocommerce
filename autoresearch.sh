#!/bin/bash
set -euo pipefail

cd "$(dirname "$0")"

# Clean build artifacts to ensure a full rebuild
pnpm run clean:build 2>&1 >/dev/null

# Also clear wireit cache so it doesn't skip tasks
find . -name '.wireit' -type d -maxdepth 5 -exec rm -rf {} + 2>/dev/null || true

# Run the full build and time it
START=$(date +%s)
pnpm run build 2>&1 | tail -20
END=$(date +%s)

ELAPSED=$((END - START))
echo "METRIC build_seconds=$ELAPSED"
