#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

npx esbuild "${SCRIPT_DIR}/custom-field.tsx" \
  --bundle \
  --format=iife \
  --target=es2017 \
  --outfile="${SCRIPT_DIR}/custom-field.js" \
  --jsx-factory=wp.element.createElement \
  --jsx-fragment=wp.element.Fragment
