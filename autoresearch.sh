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
pnpm run build 2>&1 > "$BUILD_LOG"
END=$(date +%s%N)

ELAPSED_MS=$(( (END - START) / 1000000 ))
ELAPSED_S=$(( ELAPSED_MS / 1000 ))

# Extract per-package timing info
echo "=== Per-task timing ==="
grep -oP '(?<=✅ Ran 1 script and skipped 0 in )[\d.]+s' "$BUILD_LOG" | sort -t. -k1 -n -r | head -10 || true
echo ""
echo "=== webpack compilations ==="
grep "compiled" "$BUILD_LOG" | head -10 || true
echo ""
echo "=== Slowest wireit tasks ==="
grep "✅ Ran" "$BUILD_LOG" | sed 's/.*\(✅.*\)/\1/' | sort -t' ' -k8 -n -r | head -10 || true

echo ""
echo "METRIC build_seconds=$ELAPSED_S"
rm -f "$BUILD_LOG"
