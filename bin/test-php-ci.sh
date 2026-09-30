#!/usr/bin/env bash

# Runs CI's PHP unit cells for the core plugin locally, one warm wp-env
# instance per cell:
#
#   pnpm test:php:ci --list
#   pnpm test:php:ci --cell 8.3 --cell 7.4 -- --filter WC_Tests_Product
#
# Install mirrors the "backend" path of setup-woocommerce-monorepo; cells,
# versions and commands come from `pnpm utils ci-jobs`, as in CI. Cells start
# without `--update` so they stay warm; `--fresh` starts them as CI does.
# One invocation at a time.

set -euo pipefail

project="@woocommerce/plugin-woocommerce"
project_dir="plugins/woocommerce"
list=0
all=0
fresh=0
jobs=2
cells=()
phpunit_args=()

usage() {
	cat >&2 <<'EOF'
usage: pnpm test:php:ci --list
       pnpm test:php:ci (--cell <index|name fragment>)... [--jobs N] [--fresh] [-- phpunit args...]
       pnpm test:php:ci --all [--jobs N] [--fresh] [-- phpunit args...]
EOF
	exit 2
}

while [[ $# -gt 0 ]]; do
	case "$1" in
		--list) list=1 ;;
		--all) all=1 ;;
		--fresh) fresh=1 ;;
		--jobs) [[ $# -ge 2 ]] || usage; jobs="$2"; shift ;;
		--cell) [[ $# -ge 2 ]] || usage; cells+=( "$2" ); shift ;;
		--) shift; phpunit_args=( ${@+"$@"} ); break ;;
		*) echo "unknown argument: $1" >&2; usage ;;
	esac
	shift
done
if [[ $list -eq 0 && $all -eq 0 && ${#cells[@]} -eq 0 ]]; then
	usage
fi
[[ "$jobs" =~ ^[1-9][0-9]*$ ]] || usage

cd "$(dirname "$0")/.."
tmp="$(mktemp -d)"

run() {
	printf '\n$' >&2
	printf ' %q' "$@" >&2
	printf '\n' >&2
	"$@"
}

field() { printf '%s' "$1" | cut -f"$2"; }

# Cells, one per line: index, name, command, start, port, slug, env (k=v;k=v).
run pnpm utils ci-jobs --event pull_request --json >/dev/null
mv jobs.json "$tmp/jobs.json"
# shellcheck disable=SC2016 # node source, not shell
node -e '
	const jobs = require( process.argv[ 1 ] ).test.filter(
		( j ) => j.projectName === process.argv[ 2 ] && j.testType === "unit:php"
	);
	jobs.forEach( ( j, i ) => {
		const env = j.testEnv.envVars || {};
		// Values come from api.wordpress.org; keep them out of shell syntax.
		for ( const [ k, v ] of Object.entries( env ) ) {
			if ( ! /^[A-Za-z_][A-Za-z0-9_]*$/.test( k ) || /[;\s]/.test( String( v ) ) ) {
				throw new Error( `unexpected planner env ${ k }=${ v }` );
			}
		}
		const php = ( env.WP_ENV_PHP_VERSION || "" ).replace( ".", "" );
		if ( ! /^\d+$/.test( php ) || ! j.command ) {
			throw new Error( `unexpected planner job ${ j.name }` );
		}
		const wp = /latest - 1/.test( j.name ) ? "latest-1" : /pre-release/.test( j.name ) ? "prerelease" : "latest";
		const base = { latest: 82, "latest-1": 83, prerelease: 84 }[ wp ];
		const row = [
			i + 1, j.name, j.command, j.testEnv.start, `${ base }${ php }`, `php${ php }-wp-${ wp }`,
			Object.entries( env ).map( ( [ k, v ] ) => `${ k }=${ v }` ).join( ";" ),
		];
		process.stdout.write( row.join( "\t" ) + "\n" );
	} );
' "$tmp/jobs.json" "$project" > "$tmp/cells.tsv"
cell_rows=()
while IFS= read -r line; do cell_rows+=( "$line" ); done < "$tmp/cells.tsv"
if [[ ${#cell_rows[@]} -eq 0 ]]; then
	echo "error: the planner reports no unit:php cells for $project" >&2
	exit 3
fi

if [[ $list -eq 1 ]]; then
	printf '%s\n' "${cell_rows[@]}" | awk -F'\t' '{ printf "%2d  %-88s  port %s  %s\n", $1, $2, $5, $7 }'
	exit 0
fi

tab=$'\t'
selected=()
if [[ $all -eq 1 ]]; then
	for row in "${cell_rows[@]}"; do selected+=( "${row%%"$tab"*}" ); done
else
	for want in ${cells[@]+"${cells[@]}"}; do
		matches=()
		for row in "${cell_rows[@]}"; do
			index="${row%%"$tab"*}"
			name="$(field "$row" 2)"
			if [[ "$want" =~ ^[0-9]+$ ]]; then
				[[ "$want" == "$index" ]] && matches+=( "$index" )
			elif [[ "$name" == *"$want"* ]]; then
				matches+=( "$index" )
			fi
		done
		if [[ ${#matches[@]} -ne 1 ]]; then
			echo "error: --cell '$want' matches ${#matches[@]} cells; use --list and pick one index" >&2
			exit 2
		fi
		[[ " ${selected[*]:-} " == *" ${matches[0]} "* ]] || selected+=( "${matches[0]}" )
	done
fi

# Install as CI does for a unit:php job.
run composer install --working-dir="$project_dir" --quiet &
composer_pid=$!
pnpm_status=0
run pnpm install --filter="$project" --frozen-lockfile --ignore-scripts || pnpm_status=$?
composer_status=0
wait "$composer_pid" || composer_status=$?
[[ $pnpm_status -eq 0 && $composer_status -eq 0 ]] || exit 1
run pnpm --filter=@woocommerce/admin-library build:project:feature-config

# A fresh CI database has no leftover scheduled actions; phpunit never drops these tables.
truncate_action_scheduler() {
	local config="$1" tables sql
	tables="$(WC_TEST_ENV_CONFIG="$config" pnpm --filter="$project" --silent wp-env:test run cli wp db tables 'wp_actionscheduler_*' --all-tables-with-prefix --format=csv | tr -d '\r')" || return 1
	# shellcheck disable=SC2016 # SQL identifier quotes, not shell
	sql="$(printf '%s' "$tables" | tr ',' '\n' | { grep -E '^wp_actionscheduler_[a-z_]+$' || true; } | sed 's/.*/TRUNCATE TABLE `&`;/' | tr '\n' ' ')"
	[[ -n "$sql" ]] || return 0
	WC_TEST_ENV_CONFIG="$config" pnpm --filter="$project" --silent wp-env:test run cli wp db query "$sql" >/dev/null
}

cell_env() {
	# k=v;k=v -> one line per pair, for `env`.
	[[ -n "$1" ]] && printf '%s\n' "${1//;/$'\n'}"
}

# Start each distinct instance once. WP_ENV_PORT would override every cell's port.
unset WP_ENV_PORT WP_ENV_MYSQL_PORT
started=()
for index in "${selected[@]}"; do
	row="${cell_rows[$(( index - 1 ))]}"
	slug="$(field "$row" 6)"
	[[ " ${started[*]:-} " == *" $slug "* ]] && continue
	started+=( "$slug" )
	config=".wp-env.test-${slug}.json"
	node -e '
		const fs = require( "fs" );
		const base = JSON.parse( fs.readFileSync( process.argv[ 1 ], "utf8" ) );
		fs.writeFileSync( process.argv[ 2 ], JSON.stringify( { ...base, port: Number( process.argv[ 3 ] ) }, null, "\t" ) + "\n" );
	' "$project_dir/.wp-env.test.json" "$project_dir/$config" "$(field "$row" 5)"
	envs=()
	while IFS= read -r line; do envs+=( "$line" ); done < <( cell_env "$(field "$row" 7)" )
	start_at=$(date +%s)
	if [[ $fresh -eq 1 ]]; then
		# shellcheck disable=SC2046,SC2086 # the planner's start string is a pnpm script name plus flags
		CI=true WC_TEST_ENV_CONFIG="$config" run env ${envs[@]+"${envs[@]}"} pnpm --filter="$project" $(field "$row" 4)
	else
		CI=true WC_TEST_ENV_CONFIG="$config" run env ${envs[@]+"${envs[@]}"} pnpm --filter="$project" wp-env:test start
	fi
	echo "instance $slug ready in $(( $(date +%s) - start_at ))s (port $(field "$row" 5))" >&2
done

( cd "$project_dir" && run sh ./client/blocks/bin/copy-blocks-json.sh )

# Run the selected cells, --jobs at a time; cells sharing an instance run in sequence.
group_slugs=()
for index in "${selected[@]}"; do
	slug="$(field "${cell_rows[$(( index - 1 ))]}" 6)"
	[[ " ${group_slugs[*]:-} " == *" $slug "* ]] || group_slugs+=( "$slug" )
done

run_group() {
	local slug="$1"
	local config=".wp-env.test-${slug}.json"
	for index in "${selected[@]}"; do
		local row="${cell_rows[$(( index - 1 ))]}"
		[[ "$(field "$row" 6)" == "$slug" ]] || continue
		local name command log start_at rc
		name="$(field "$row" 2)"
		command="$(field "$row" 3)"
		command="${command/test:php:env/test:php:env:run}"
		log="$tmp/cell-$index.log"
		envs=()
		while IFS= read -r line; do envs+=( "$line" ); done < <( cell_env "$(field "$row" 7)" )
		start_at=$(date +%s)
		rc=0
		if truncate_action_scheduler "$config" > "$log" 2>&1; then
			CI=true WC_TEST_ENV_CONFIG="$config" env ${envs[@]+"${envs[@]}"} pnpm --filter="$project" "$command" ${phpunit_args[@]+"${phpunit_args[@]}"} >> "$log" 2>&1 || rc=$?
		else
			rc=$?
			echo "could not reset the Action Scheduler tables" >> "$log"
		fi
		printf '%s\t%s\t%s\t%s\n' "$index" "$rc" "$(( $(date +%s) - start_at ))" "$log" >> "$tmp/results.tsv"
		if [[ $rc -ne 0 ]]; then
			echo "FAIL  $name (exit $rc), last lines of $log:" >&2
			tail -n 40 "$log" >&2
		fi
	done
}

: > "$tmp/results.tsv"
running=0
for slug in "${group_slugs[@]}"; do
	run_group "$slug" &
	running=$(( running + 1 ))
	if [[ $running -ge $jobs ]]; then
		wait
		running=0
	fi
done
wait

echo
echo "Results (logs in $tmp):"
failed=0
for index in "${selected[@]}"; do
	row="${cell_rows[$(( index - 1 ))]}"
	result="$(grep -E "^$index	" "$tmp/results.tsv" || true)"
	if [[ -z "$result" ]]; then
		printf '%-5s %-88s  %s\n' FAIL "$(field "$row" 2)" "no result recorded"
		failed=1
		continue
	fi
	rc="$(field "$result" 2)"; secs="$(field "$result" 3)"; log="$(field "$result" 4)"
	summary="$(grep -aE '^(OK|Tests:|FAILURES|ERRORS)' "$log" | sed "s/$(printf '\033')\[[0-9;]*m//g" | tail -1 || true)"
	[[ -n "$summary" ]] || summary="$(tail -n 1 "$log")"
	printf '%-5s %-88s %4ss  %s\n' "$([[ $rc -eq 0 ]] && echo PASS || echo FAIL)" "$(field "$row" 2)" "$secs" "$summary"
	[[ $rc -eq 0 ]] || failed=1
done
exit $failed
