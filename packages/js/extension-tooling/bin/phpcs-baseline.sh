#!/usr/bin/env bash
set -euo pipefail

report="$(mktemp)"
trap 'rm -f "$report"' EXIT
status=0
php vendor/bin/phpcs -q --report=json > "$report" || status=$?
case "$status" in
    0|1|2) ;;
    3)
        # shellcheck disable=SC2016 # The PHP process reads its own argument.
        if ! php -r 'exit(is_array(json_decode(file_get_contents($argv[1]), true)["files"] ?? null) ? 0 : 1);' "$report"; then
            cat "$report"
            exit "$status"
        fi
        ;;
    *) cat "$report"; exit "$status" ;;
esac

SARB=vendor/bin/sarb
[ -f "$SARB" ] || SARB=tools/php-quality/vendor/bin/sarb
php "$SARB" create --input-format=phpcodesniffer-json phpcs.baseline "$@" < "$report"
