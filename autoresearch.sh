#!/bin/bash
set -euo pipefail

cd "$(dirname "$0")"

# Clean build artifacts to ensure a full rebuild
pnpm run clean:build 2>&1 >/dev/null

# Also clear wireit cache so it doesn't skip tasks
find . -name '.wireit' -type d -maxdepth 5 -exec rm -rf {} + 2>/dev/null || true

# Run the full build and time it
START=$(date +%s)
BUILD_LOG=$(mktemp)
pnpm run build 2>&1 > "$BUILD_LOG"
END=$(date +%s)

ELAPSED=$((END - START))

# Show timing breakdown
echo "=== webpack compilations ==="
grep "compiled" "$BUILD_LOG" | head -15 || true
echo ""
echo "=== Slowest tasks (wireit) ==="
grep "✅ Ran" "$BUILD_LOG" | sort -t'n' -k1 -r | head -10 || true

echo ""
echo "METRIC build_seconds=$ELAPSED"
rm -f "$BUILD_LOG"
