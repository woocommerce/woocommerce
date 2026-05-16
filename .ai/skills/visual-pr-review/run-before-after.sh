#!/usr/bin/env bash
set -euo pipefail
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
SKILL_DIR="${REPO_ROOT}/.ai/skills/visual-pr-review"
SCREENSHOT_DIR="${SKILL_DIR}/screenshots"
cd "${REPO_ROOT}"

PR_REF="$(git rev-parse --abbrev-ref HEAD)"
[ "$PR_REF" = "HEAD" ] && PR_REF="$(git rev-parse HEAD)"
git fetch --quiet origin trunk
TRUNK_SHA="$(git merge-base HEAD origin/trunk)"

CHANGED="$(git diff --name-only "${TRUNK_SHA}...HEAD")"
NEEDS_ADMIN_BUILD=false; NEEDS_BLOCKS_BUILD=false
grep -q "plugins/woocommerce/client/admin/"  <<<"$CHANGED" && NEEDS_ADMIN_BUILD=true
grep -q "plugins/woocommerce/client/blocks/" <<<"$CHANGED" && NEEDS_BLOCKS_BUILD=true

if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
  echo "✗ Uncommitted changes; refusing to swap branches." >&2; exit 1
fi
trap 'git checkout --quiet "$PR_REF" || true' EXIT

build_if_needed() {
  [ "$NEEDS_ADMIN_BUILD" = "true" ]  && pnpm --filter=@woocommerce/plugin-woocommerce build:admin
  [ "$NEEDS_BLOCKS_BUILD" = "true" ] && pnpm --filter=@woocommerce/plugin-woocommerce build:blocks
}
run_canaries() {
  local out="$1"; rm -rf "$out"; mkdir -p "$out"
  cd "${REPO_ROOT}/plugins/woocommerce"
  VISUAL_REVIEW_OUTPUT_DIR="$out" pnpm exec playwright test \
    --config="${SKILL_DIR}/playwright.config.ts" --project=visual-canaries
  cd "${REPO_ROOT}"
}

pnpm --filter=@woocommerce/plugin-woocommerce env:start
pnpm --filter=@woocommerce/plugin-woocommerce exec playwright install chromium --with-deps

echo "=== Phase 1: trunk (before) ==="
git checkout --quiet "$TRUNK_SHA"
build_if_needed
run_canaries "${SCREENSHOT_DIR}/before"

echo "=== Phase 2: PR (after) ==="
git checkout --quiet "$PR_REF"
build_if_needed
run_canaries "${SCREENSHOT_DIR}/after"

ls -la "${SCREENSHOT_DIR}/before/" "${SCREENSHOT_DIR}/after/" 2>/dev/null || true
