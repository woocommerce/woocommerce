#!/usr/bin/env bash
#
# Playwright E2E runner. The browser test site (.wp-env.e2e.json, `pnpm run wp-env:e2e`) runs
# the packaged plugin: `prepare` unzips deploy/<name>.zip (or $E2E_PLUGIN_ZIP) into
# deploy/e2e-plugin/, and the repository's bin/link_plugins.js writes .wp-env.e2e.json from
# .wp-env.test.json with that folder in place of ".".
#
# Usage: woo-extension-tooling e2e [ prepare | start | setup | reset | run [playwright args] | with-env [playwright args] | help ]
set -euo pipefail

config=$(bash "$(dirname "$0")/woo-config.sh")
plugin_dir=$(jq -r .zip.root <<< "$config")
main_file=$(jq -r '.mainFile | split("/") | last' <<< "$config")
zip_name=$(jq -r .slug <<< "$config")
subcommand=${1:-help}
shift || true

usage() {
	cat <<-'USAGE'
	Playwright E2E runner for the browser test site (.wp-env.e2e.json).

	  pnpm run test:e2e:setup       Package the plugin, start the site, reset and seed it (leaves it up)
	  pnpm run test:e2e             Run Playwright against the running site; arguments go to `playwright test`
	  pnpm run test:e2e:reset       Reset the database and seed it again
	  pnpm run test:e2e:with-env    Full pipeline, as CI: setup, the whole suite, then stop the site
	  woo-extension-tooling e2e prepare   Unzip the package and write .wp-env.e2e.json only
	  woo-extension-tooling e2e start     Start the site only

	Environment:
	  E2E_PLUGIN_ZIP=<zip>          Test this ZIP instead of building deploy/<name>.zip
	  E2E_WP_VERSION, E2E_WC_VERSION  latest (default), nightly or a release tag
	  E2E_PHP_VERSION=X.Y        Select the browser site PHP image
	  E2E_TARGET=<file>             Spec files to run: a path or part of a file name (run only)
	  E2E_FILTER=<regex>            Test titles or tags to run, as --grep (run only)
	  E2E_SHOW_UI=1                 Show the browser, as --headed (run only)
	  WP_ENV_TESTS_PORT=<port>      Port of the site, for worktrees (default 8889)
	USAGE
}

prepare() {
	if [[ -n "${E2E_PHP_VERSION+x}" && ! "${E2E_PHP_VERSION:-}" =~ ^[0-9]+\.[0-9]+$ ]]; then
		echo "Invalid E2E_PHP_VERSION (expected major.minor)." >&2
		exit 1
	fi

	local zip=${E2E_PLUGIN_ZIP:-}
	if [[ -z "$zip" ]]; then
		pnpm run build:zip
		zip="deploy/$zip_name.zip"
	fi
	if [[ ! -f "$zip" ]]; then
		echo "Plugin ZIP not found: $zip" >&2
		exit 1
	fi

	mkdir -p deploy
	rm -rf deploy/e2e-plugin
	unzip -q "$zip" -d deploy/e2e-plugin
	if [[ ! -f "deploy/e2e-plugin/$plugin_dir/$main_file" ]]; then
		echo "Plugin ZIP must contain $plugin_dir/$main_file: $zip" >&2
		exit 1
	fi

	export WP_VERSION=${E2E_WP_VERSION:-${WP_VERSION:-latest}}
	export WC_VERSION=${E2E_WC_VERSION:-${WC_VERSION:-latest}}
	[[ "$WP_VERSION" != trunk ]] || WP_VERSION=nightly
	[[ "$WC_VERSION" != trunk ]] || WC_VERSION=nightly
	if [[ "$WP_VERSION" != latest && "$WP_VERSION" != nightly && ! "$WP_VERSION" =~ ^[0-9]+\.[0-9]+(\.[0-9]+)?(-[[:alnum:]]+([.-][[:alnum:]]+)*)?$ ]]; then
		echo "Invalid WordPress version: $WP_VERSION (expected latest, nightly or a release tag)" >&2
		exit 1
	fi
	if [[ "$WC_VERSION" != latest && "$WC_VERSION" != nightly && ! "$WC_VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+(-[[:alnum:]]+([.-][[:alnum:]]+)*)?$ ]]; then
		echo "Invalid WooCommerce version: $WC_VERSION (expected latest, nightly or a release tag)" >&2
		exit 1
	fi
	if [[ -f bin/hooks/e2e-prepare.sh ]]; then
		bash bin/hooks/e2e-prepare.sh
	fi
	node ./bin/link_plugins.js
}

start() {
	# Start every site session with an empty log so the warnings check sees only this run.
	mkdir -p tests/_output
	: > tests/_output/e2e-debug.log
	chmod a+rw tests/_output/e2e-debug.log
	pnpm run wp-env:e2e start "$@"
}

reset() {
	: > tests/_output/e2e-debug.log
	pnpm run wp-env:e2e reset
	pnpm run env:test:e2e:seed
}

run_playwright() {
	local arg port
	local args=( test --config tests/e2e/playwright.config.js )
	[[ -z "${E2E_TARGET:-}" ]] || args+=( "$E2E_TARGET" )
	[[ -z "${E2E_FILTER:-}" ]] || args+=( --grep "$E2E_FILTER" )
	[[ -z "${E2E_SHOW_UI:-}" ]] || args+=( --headed )
	args+=( "$@" )

	for arg in "$@"; do
		case "$arg" in
			--list|--help|-h|--version|-V)
				exec pnpm exec playwright "${args[@]}"
				;;
		esac
	done

	if [[ -z "${WP_BASE_URL:-}" ]]; then
		if ! port=$(pnpm --silent run wp-env:e2e status --json 2>/dev/null | node -e '
			const { status, ports } = JSON.parse(require("node:fs").readFileSync(0, "utf8"));
			const port = ports?.development;
			if (status !== "running" || !/^\d+$/.test(port) || port < 1 || port > 65535) process.exit(1);
			console.log(port);
		' 2>/dev/null); then
			echo "The browser test site is unavailable. Run pnpm run test:e2e:setup or set WP_BASE_URL." >&2
			return 1
		fi
		export WP_BASE_URL="http://localhost:$port"
		export WP_ENV_TESTS_PORT="$port"
	fi

	exec pnpm exec playwright "${args[@]}"
}

stop_site() {
	local result=$?
	if ! pnpm run wp-env:e2e stop && [[ "$result" == 0 ]]; then
		result=1
	fi
	exit "$result"
}

case "$subcommand" in
	prepare)
		prepare
		;;
	start)
		start "$@"
		;;
	setup)
		prepare
		start --update
		reset
		;;
	reset)
		reset
		;;
	run)
		run_playwright "$@"
		;;
	with-env)
		unset E2E_TARGET E2E_FILTER E2E_SHOW_UI
		if [[ -f .wp-env.e2e.json ]]; then
			# After a failed first start, `wp-env status` reports "Environment not initialized"
			# and exits 1. That site has nothing to stop; prepare writes .wp-env.e2e.json again.
			status_errors=$(mktemp)
			if status=$(pnpm --silent run wp-env:e2e status --json 2> "$status_errors"); then
				state=$(node -p 'JSON.parse(require("node:fs").readFileSync(0, "utf8")).status' <<< "$status" 2> /dev/null) || state=unknown
			elif grep -q 'Environment not initialized' "$status_errors"; then
				state=uninitialized
			else
				state=unknown
			fi
			rm -f "$status_errors"
			case "$state" in
				running)
					echo "The test site is already running. Use pnpm run test:e2e." >&2
					exit 1
					;;
				stopped|uninitialized) ;;
				*)
					echo "Could not determine whether the test site is running." >&2
					exit 1
					;;
			esac
		fi
		prepare
		trap stop_site EXIT
		start --update
		reset
		pnpm run test:e2e "$@"
		;;
	help|-h|--help)
		usage
		;;
	*)
		echo "Unknown E2E command: $subcommand" >&2
		usage >&2
		exit 2
		;;
esac
