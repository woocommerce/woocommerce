#!/usr/bin/env bash

# PHPUnit runner for the wp-env tests site (.wp-env.test.json).
#
# Local only. CI installs the test suite in its own PHPUnit workflow (see .github/workflows/).
#
# USAGE
#   pnpm run test:php:env [phpunit args]      Run PHPUnit; installs WP + WooCommerce on first run
#   pnpm run test:php:env:setup               Reinstall WP + WooCommerce (e.g. after changing a version)
#   pnpm run test:php:env:help                Show this message
#   woo-extension-tooling phpunit direct <config> [args]   Use a pre-provisioned site
#
# OPTIONS (env vars)
#   WC_ORDER_DATASTORE=hpos   Order storage: cpt (default) or hpos; CI runs both
#   UNIT_WC_VERSION=trunk     WooCommerce: latest (default, as CI), nightly, trunk, or X.Y.Z  (setup only)
#   UNIT_WP_VERSION=7.0       WordPress:   latest (default, as CI), nightly, or X.Y[.Z] (setup only)
#   The variables in package.json config.woo.phpunit.passEnv are passed into the container when set.
#
# EXAMPLES
#   pnpm run test:php:env --filter <TestClassName>
#   WC_ORDER_DATASTORE=hpos pnpm run test:php:env
#   UNIT_WC_VERSION=trunk pnpm run test:php:env:setup && pnpm run test:php:env
#
# Worktrees: export WP_ENV_PORT / WP_ENV_TESTS_PORT first (see AGENTS.md).
set -euo pipefail

if [[ ! -f package.json ]]; then
	echo 'No package.json here. Run this from the plugin root.' >&2
	exit 1
fi

TESTS_DIR=/tmp/wordpress-tests-lib
# wp-env mounts the checkout under its folder name, so this also works in worktrees.
PLUGIN_DIR="wp-content/plugins/${PWD##*/}"

usage() {
	sed -n '/^# USAGE/,/^set -euo/p' "$0" | sed '$d; s/^# \{0,1\}//'
}

compose() {
	local install_path
	install_path=$(pnpm --silent run wp-env:test status --json | node -p 'JSON.parse(require("fs").readFileSync(0, "utf8")).installPath')
	docker compose -f "${install_path}/docker-compose.yml" "$@"
}

# Same as `pnpm run wp-env:test run cli --env-cwd=<dir> <command>` (host user, so files it writes stay
# ours), minus wp-env's argument parser, which turns `7.0` into `7` and keeps `--debug`,
# `--help` and `--version` for itself.
exec_tests_cli() {
	compose exec -u "$(id -u):$(id -g)" -w "$1" cli "${@:2}"
}

setup() {
	local installer
	installer=$(cat "$(dirname "$0")/install.sh")
	compose exec -T -u root cli apk add --no-cache subversion git
	# Run from /tmp: in a git worktree, the checkout's .git points to a host path that
	# doesn't exist in the container, which makes install.sh's `git ls-remote` fail.
	# Pass the shared script as one argument so child commands can still read stdin.
	exec_tests_cli /tmp \
		env WP_TESTS_DIR="$TESTS_DIR" bash -c "$installer" install.sh wordpress_test root password mysql \
		"${UNIT_WP_VERSION:-latest}" "${UNIT_WC_VERSION:-latest}"
}

run() {
	if [ -z "$(compose ps -q --status running cli)" ]; then
		echo "wp-env is not running. Start it with: pnpm run env:test:start (export WP_ENV_PORT / WP_ENV_TESTS_PORT first in a worktree)." >&2
		exit 1
	fi
	# install.sh empties each directory before refilling it, so any missing file means a failed or interrupted setup.
	# wp-tests-config.php is the last file the test library step writes, so it catches a setup that stopped partway through it.
	if ! compose exec -T cli test -f /tmp/wordpress/wp-settings.php -a -f "${TESTS_DIR}/includes/functions.php" -a -f "${TESTS_DIR}/wp-tests-config.php" -a -f /tmp/woocommerce/woocommerce.php; then
		echo "Test suite missing or incomplete in the tests site; running setup first."
		setup
	fi
	# The suite's own test variables (config.woo.phpunit.passEnv), which CI sets on the host.
	local env_args=(WP_TESTS_DIR="$TESTS_DIR" WC_ORDER_DATASTORE="${WC_ORDER_DATASTORE:-cpt}") pass_env var
	pass_env=$(bash "$(dirname "$0")/woo-config.sh" '.phpunit.passEnv[]')
	for var in $pass_env; do
		if [ -n "${!var:-}" ]; then env_args+=("${var}=${!var}"); fi
	done
	exec_tests_cli "/var/www/html/${PLUGIN_DIR}" \
		env "${env_args[@]}" php vendor/bin/phpunit -c phpunit.xml.dist --fail-on-empty-test-suite "$@"
}

case "${1:-run}" in
	setup) setup ;;
	run) shift || true; run "$@" ;;
	direct)
		shift
		if [[ $# -eq 0 ]]; then
			usage >&2
			exit 1
		fi
		if [[ ! -f vendor/autoload.php ]]; then
			echo 'Install the locked PHP dependencies with composer install first.' >&2
			exit 1
		fi
		xml=$1
		shift
		exec pnpm run wp-env:test run cli --env-cwd="$PLUGIN_DIR" -- \
			php vendor/bin/phpunit -c "$xml" --fail-on-empty-test-suite "$@"
		;;
	help | -h | --help) usage ;;
	*) usage; exit 1 ;;
esac
