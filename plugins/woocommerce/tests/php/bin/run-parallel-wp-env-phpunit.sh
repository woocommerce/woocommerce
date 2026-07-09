#!/usr/bin/env bash

set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
cd "$PLUGIN_DIR"

if [[ "$#" -gt 0 || "${WC_PHPUNIT_PARALLEL:-1}" == "0" ]]; then
	exec pnpm wp-env:test run --env-cwd='wp-content/plugins/woocommerce' cli vendor/bin/phpunit -c phpunit.xml --verbose "$@"
fi

: "${TMPDIR:?TMPDIR must be set for wp-env shard files}"

repo_hash="$(printf '%s' "$PLUGIN_DIR" | cksum | awk '{ print $1 }')"
work_root="${TMPDIR%/}/wc-phpunit-parallel-${repo_hash}"
wp_env_home="${work_root}/wp-env-home"
config_root="${work_root}/configs"
log_root="${work_root}/logs"
port_base="${WC_PHPUNIT_PARALLEL_PORT_BASE:-8220}"

mkdir -p "$wp_env_home" "$config_root" "$log_root"

shards=(
	"legacy|wc-phpunit-local-shard-legacy|"
	"main-includes|wc-phpunit-local-shard-main-includes|"
	"main-src-ui|wc-phpunit-local-shard-main-src-ui|DISABLE_HPOS=1"
	"main-src-other|wc-phpunit-local-shard-main-src-other|DISABLE_HPOS=1"
	"main-internal-admin|wc-phpunit-local-shard-main-internal-admin|"
	"main-internal-core|wc-phpunit-local-shard-main-internal-core|"
	"main-internal-other|wc-phpunit-local-shard-main-internal-other|"
)

shard_config_path() {
	local shard_name="$1"
	printf '%s/%s/.wp-env.test.json' "$config_root" "$shard_name"
}

shard_port() {
	local index="$1"
	printf '%s' "$(( port_base + index ))"
}

prepare_shard_config() {
	local shard_name="$1"
	local config_path
	config_path="$(shard_config_path "$shard_name")"

	mkdir -p "$(dirname "$config_path")"
	cp .wp-env.test.json "$config_path"
	printf '%s' "$config_path"
}

get_shard_status() {
	local config_path="$1"
	local port="$2"

	WP_ENV_HOME="$wp_env_home" \
	WP_ENV_PORT="$port" \
	pnpm exec wp-env --config "$config_path" status 2>&1 || true
}

is_shard_running() {
	local config_path="$1"
	local port="$2"
	local output

	output="$(get_shard_status "$config_path" "$port")"
	grep -q 'status: running' <<<"$output"
}

remove_partial_shard_if_uninitialized() {
	local shard_name="$1"
	local config_path="$2"
	local port="$3"
	local output
	local install_path
	local project_name
	local container_ids

	output="$(get_shard_status "$config_path" "$port")"
	if ! grep -q 'status: uninitialized\|Environment not initialized' <<<"$output"; then
		return
	fi

	for install_path in "$(awk -F': ' '/install path:/ { print $2 }' <<<"$output")" "$wp_env_home"/wp-env-"$shard_name"-test-*; do
		if [[ "$install_path" != "$wp_env_home"/wp-env-* || ! -d "$install_path" ]]; then
			continue
		fi

		project_name="$(basename "$install_path")"
		container_ids="$(docker ps -aq --filter "name=^/${project_name}-" 2>/dev/null || true)"
		if [[ -n "$container_ids" ]]; then
			docker rm -f $container_ids >/dev/null 2>&1 || true
		fi
		rm -rf "$install_path"
	done
}

start_shard_if_needed() {
	local shard_name="$1"
	local config_path="$2"
	local port="$3"
	local log_path="${log_root}/${shard_name}-start.log"

	if is_shard_running "$config_path" "$port"; then
		printf 'Shard environment ready: %s\n' "$shard_name"
		return
	fi

	remove_partial_shard_if_uninitialized "$shard_name" "$config_path" "$port"

	printf 'Starting shard environment: %s\n' "$shard_name"
	WP_ENV_HOME="$wp_env_home" \
	WP_ENV_PORT="$port" \
	WP_CLI_PREFIX="wp-env --config $config_path run cli" \
		pnpm exec wp-env --config "$config_path" start >"$log_path" 2>&1 || {
			printf 'Failed to start shard environment: %s\n' "$shard_name" >&2
			tail -n 120 "$log_path" >&2 || true
			return 1
		}
}

run_shard() {
	local shard_name="$1"
	local suite_name="$2"
	local config_path="$3"
	local port="$4"
	local phpunit_env="$5"
	local log_path="${log_root}/${shard_name}.log"

	printf 'Running shard: %s\n' "$shard_name"
	if [[ -n "$phpunit_env" ]]; then
		WP_ENV_HOME="$wp_env_home" \
		WP_ENV_PORT="$port" \
			pnpm exec wp-env --config "$config_path" run --env-cwd='wp-content/plugins/woocommerce' cli \
				env $phpunit_env vendor/bin/phpunit -c phpunit.xml --verbose --testsuite="$suite_name" >"$log_path" 2>&1
	else
		WP_ENV_HOME="$wp_env_home" \
		WP_ENV_PORT="$port" \
			pnpm exec wp-env --config "$config_path" run --env-cwd='wp-content/plugins/woocommerce' cli \
				vendor/bin/phpunit -c phpunit.xml --verbose --testsuite="$suite_name" >"$log_path" 2>&1
	fi
}

print_shard_summary() {
	local shard_name="$1"
	local log_path="${log_root}/${shard_name}.log"

	printf '\n[%s]\n' "$shard_name"
	tail -n 18 "$log_path" || true
}

wait_for_shard() {
	local pid="$1"
	local shard_name="$2"
	local heartbeat_pid
	local waited=0
	local exit_code

	(
		while true; do
			sleep 20
			waited="$(( waited + 20 ))"
			printf 'Still waiting for shard: %s (%ss)\n' "$shard_name" "$waited"
		done
	) &
	heartbeat_pid="$!"

	wait "$pid"
	exit_code="$?"
	kill "$heartbeat_pid" >/dev/null 2>&1 || true
	wait "$heartbeat_pid" 2>/dev/null || true
	return "$exit_code"
}

for i in "${!shards[@]}"; do
	IFS='|' read -r shard_name _suite_name <<<"${shards[$i]}"
	config_path="$(prepare_shard_config "$shard_name")"
	port="$(shard_port "$i")"
	start_shard_if_needed "$shard_name" "$config_path" "$port"
done

declare -a shard_pids=()
for i in "${!shards[@]}"; do
	IFS='|' read -r shard_name suite_name phpunit_env <<<"${shards[$i]}"
	config_path="$(shard_config_path "$shard_name")"
	port="$(shard_port "$i")"
	run_shard "$shard_name" "$suite_name" "$config_path" "$port" "$phpunit_env" &
	shard_pids+=( "$!|$shard_name" )
done

failed=0
for entry in "${shard_pids[@]}"; do
	IFS='|' read -r pid shard_name <<<"$entry"
	if wait_for_shard "$pid" "$shard_name"; then
		printf 'Shard passed: %s\n' "$shard_name"
	else
		printf 'Shard failed: %s\n' "$shard_name" >&2
		failed=1
	fi
done

if [[ "$failed" -ne 0 ]]; then
	printf '\nOne or more PHPUnit shards failed. Logs are in %s\n' "$log_root" >&2
	for entry in "${shard_pids[@]}"; do
		IFS='|' read -r _pid shard_name <<<"$entry"
		print_shard_summary "$shard_name" >&2
	done
	exit 1
fi

printf '\nAll PHPUnit shards passed. Logs are in %s\n' "$log_root"
for entry in "${shard_pids[@]}"; do
	IFS='|' read -r _pid shard_name <<<"$entry"
	print_shard_summary "$shard_name"
done
