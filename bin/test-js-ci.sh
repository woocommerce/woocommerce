#!/usr/bin/env bash

# Runs a project's CI unit test job the way CI runs it, so a CI failure can be
# reproduced without any other tool:
#
#   pnpm test:js:ci <project> [test runner args...]
#   pnpm test:js:ci @woocommerce/components -- --maxWorkers=4
#
# The three steps mirror .github/actions/setup-woocommerce-monorepo/action.yml
# (install, then build-type "dependencies") and the "Run tests" step of
# .github/workflows/ci.yml. The dependency build is the step people skip
# locally, and the reason some packages only fail on a laptop. The test
# command is the project's own unit job command from `config.ci.tests` in its
# package.json (`test:js` for most projects), the same one CI runs.
#
# Run `nvm use` first: CI uses the Node version in .nvmrc.

set -euo pipefail

usage() {
	echo "usage: pnpm test:js:ci <project> [test runner args...]" >&2
	echo "       e.g. pnpm test:js:ci @woocommerce/number" >&2
	exit 2
}

if [[ "${1:-}" == "--" ]]; then
	shift
fi
project="${1:-}"
if [[ -z "$project" || "$project" == -* ]]; then
	usage
fi
shift
if [[ "${1:-}" == "--" ]]; then
	shift
fi

cd "$(dirname "$0")/.."

project_dir="$(pnpm --filter="${project}" list --depth -1 --parseable)"
if [[ -z "$project_dir" ]]; then
	echo "error: no workspace project named '${project}'" >&2
	usage
fi

command="$(node -e '
	const tests = ( require( process.argv[ 1 ] ).config?.ci?.tests ) || [];
	const unit = tests.find( ( t ) => ! t.testType || t.testType === "unit" );
	process.stdout.write( ( unit && unit.command ) || "test:js" );
' "${project_dir}/package.json")"

run() {
	printf '\n$' >&2
	printf ' %q' "$@" >&2
	printf '\n' >&2
	"$@"
}

run pnpm install --filter="${project}..." --frozen-lockfile
BROWSERSLIST_IGNORE_OLD_DATA=true run pnpm --if-present --workspace-concurrency=Infinity --stream --filter="${project}^..." '/^build:project:.*$/'
CI=true GITHUB_ACTIONS=true FORCE_COLOR=1 run pnpm --filter="${project}" "${command}" "$@"
