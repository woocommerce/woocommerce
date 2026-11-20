#!/usr/bin/env bash
#
# Writes the translation template languages/<slug>.pot with WP-CLI `wp i18n make-pot`.
# config.woo.makepot.exclude lists the paths to skip on top of the WP-CLI defaults, and
# config.woo.makepot.headers sets POT headers.
#
# Usage: woo-extension-tooling makepot, from the plugin root.
#
# WP-CLI: vendor/bin/wp when Composer installed it, else `wp` on PATH. Fails when the main
# file is missing or no template is written; an earlier template is kept until a new one is.
set -euo pipefail

config=$(bash "$(dirname "$0")/woo-config.sh")
slug=$(jq -r .slug <<< "$config")
main_file=$(jq -r .mainFile <<< "$config")
exclude=$(jq -r '.makepot.exclude | join(",")' <<< "$config")
headers=$(jq -c .makepot.headers <<< "$config")
pot=languages/$slug.pot

if [[ ! -f "$main_file" ]]; then
	echo "Missing main plugin file: $main_file" >&2
	exit 1
fi

# Xdebug is not needed here and slows extraction down; large plugins need more than 128M.
php_args='-d xdebug.mode=off -d memory_limit=2048M'

mkdir -p languages
# A fresh temporary file per run, removed on any exit, so a failed or overlapping run never
# leaves a partial template in languages/.
tmp=$(mktemp "languages/.$slug.pot.XXXXXX")
trap 'rm -f "$tmp"' EXIT

args=(i18n make-pot . "$tmp" "--slug=$slug")
[[ -z "$exclude" ]] || args+=("--exclude=$exclude")
[[ "$headers" == '{}' ]] || args+=("--headers=$headers")

if [[ -x vendor/bin/wp ]]; then
	wp=vendor/bin/wp
elif ! wp=$(command -v wp); then
	echo 'WP-CLI not found: run composer install or put wp on PATH.' >&2
	exit 1
fi
# The phar starts with a php shebang. A Composer or WP-CLI launcher is a shell script that
# passes WP_CLI_PHP_ARGS to PHP; handing it to php would print it instead of running it.
if head -n 1 "$wp" | grep -q php; then
	# shellcheck disable=SC2086 # php_args holds several options.
	php $php_args "$wp" "${args[@]}"
else
	WP_CLI_PHP_ARGS=$php_args "$wp" "${args[@]}"
fi

if [[ ! -s "$tmp" ]]; then
	echo "WP-CLI did not write $pot." >&2
	exit 1
fi
chmod 644 "$tmp"
mv "$tmp" "$pot"
