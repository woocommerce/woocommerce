#!/bin/bash
# Fast CJS build using esbuild (already installed as transitive dep).
# Replaces `tsc --project tsconfig-cjs.json --noCheck` for build speed.
# esbuild handles TS/JSX transpilation ~20-100x faster than tsc.
set -euo pipefail

# Find repo root (where node_modules lives)
REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ESBUILD="$REPO_ROOT/node_modules/.pnpm/esbuild@0.18.20/node_modules/esbuild/bin/esbuild"

if [ ! -x "$ESBUILD" ]; then
  echo "esbuild not found, falling back to tsc" >&2
  exec tsc --project tsconfig-cjs.json --noCheck
fi

find src \( -name '*.ts' -o -name '*.tsx' -o -name '*.js' -o -name '*.jsx' \) \
  ! -path '*/test/*' ! -path '*/stories/*' \
  -print0 | xargs -0 "$ESBUILD" \
  --outdir=build \
  --outbase=src \
  --format=cjs \
  --platform=node \
  --target=esnext \
  --jsx=transform \
  --jsx-factory=createElement \
  --jsx-fragment=Fragment \
  --loader:.js=jsx
