#!/bin/bash
set -euo pipefail

cd "$(dirname "$0")"

# Clean build artifacts to ensure a full rebuild
pnpm run clean:build 2>&1 >/dev/null

# Also clear wireit cache so it doesn't skip tasks
find . -name '.wireit' -type d -maxdepth 5 -exec rm -rf {} + 2>/dev/null || true

# Run the full build and time it, capture all output for timing analysis
START=$(date +%s%N)
BUILD_LOG=$(mktemp)
pnpm run build 2>&1 | tee "$BUILD_LOG" | tail -5
END=$(date +%s%N)

ELAPSED_MS=$(( (END - START) / 1000000 ))
ELAPSED_S=$(( ELAPSED_MS / 1000 ))

# Extract timing info from build log
echo ""
echo "=== Timing summary ==="
grep -i "compiled\|Ran.*script\|Done\|webpack.*ms" "$BUILD_LOG" | tail -20
echo ""

echo "METRIC build_seconds=$ELAPSED_S"
rm -f "$BUILD_LOG"
