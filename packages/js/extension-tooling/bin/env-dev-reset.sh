#!/usr/bin/env bash

# Wipes the development site (.wp-env.json) and seeds it again. Run it as `pnpm run env:dev:reset`.
#
# `wp-env reset` re-applies the configured port, so in a shell without WP_ENV_PORT exported it
# would move a worktree's site back to the default port. Keep the port the site is running on.
set -euo pipefail

if [[ ! -f package.json ]]; then
	echo 'No package.json here. Run this from the plugin root.' >&2
	exit 1
fi

if [ -z "${WP_ENV_PORT:-}" ]; then
	port=$(wp-env status --json 2>/dev/null | node -p 'JSON.parse(require("fs").readFileSync(0, "utf8")).ports?.development || ""' 2>/dev/null || true)
	if [ -n "$port" ]; then
		export WP_ENV_PORT="$port"
	fi
fi

wp-env reset
pnpm run env:dev:seed
