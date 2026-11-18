#!/usr/bin/env bash

# Starts the wp-env tests site (.wp-env.test.json). The development site is `pnpm run env:dev:start`.
# Extra arguments go to `wp-env start`.
#
# The tests site bind-mounts the git-ignored tests/_output/debug.log; Docker turns a missing
# mount source into a directory, so create the file first.
set -euo pipefail

if [[ ! -f package.json ]]; then
	echo 'No package.json here. Run this from the plugin root.' >&2
	exit 1
fi

mkdir -p tests/_output
rmdir tests/_output/debug.log 2>/dev/null || true
touch tests/_output/debug.log

# Installs that mounted a directory keep one at the mount point, and a file can't mount onto it.
install_path=$(pnpm --silent run wp-env:test status --json 2>/dev/null | sed -n 's/.*"installPath": *"\([^"]*\)".*/\1/p' || true)
if [ -n "$install_path" ]; then
	rmdir "${install_path}/WordPress/wp-content/debug.log" 2>/dev/null || true
fi

pnpm run wp-env:test start "$@"
