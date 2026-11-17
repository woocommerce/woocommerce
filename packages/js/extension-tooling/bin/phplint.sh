#!/usr/bin/env bash

# Checks the syntax of every tracked or new PHP file (ignored files excluded) with the PHP binary in use (`php -l`), one file per
# call: PHP before 8.3 lints only the first file it is given. Reports every file that fails.
# Run it from the plugin root. Arguments that are existing paths limit the check to them; others
# are ignored, because `composer check:php -- <argument>` forwards its arguments to every step.
set -euo pipefail

paths=()
for path in "$@"; do
	if [[ -e "$path" ]]; then paths+=("$path"); fi
done
if [[ ${#paths[@]} -eq 0 ]]; then paths=(.); fi

# A tracked file that was deleted without staging the removal has nothing to check.
# shellcheck disable=SC2016 # $1 is read by the child shell for each file.
git ls-files -z --cached --others --exclude-standard -- "${paths[@]}" | { grep -z '\.php$' || true; } \
	| xargs -0 -n 1 -P 4 sh -c '[ ! -f "$1" ] || php -d display_errors=stderr -d log_errors=0 -l "$1" > /dev/null || exit 1' _
