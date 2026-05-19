#!/usr/bin/env bash
#
# Wraps a PHPUnit invocation and verifies it produced a completion summary.
#
# Catches the case where PHP exits from within a test (bare exit()/die() in
# tested code, fatal error, OOM, segfault) before PHPUnit can print its
# Tests:/FAILURES! markers. The process exit code in that scenario is whatever
# `exit` was called with — frequently 0 — so without this guard CI happily
# reports success on a half-run suite.
#
# Usage:
#   run-phpunit-with-completion-check.sh <command...>
#
# The script forwards all arguments verbatim, tees stdout/stderr to a temp
# log, and inspects the log after the command returns. Non-running PHPUnit
# invocations (--list-tests, --version, etc.) are detected by the absence of
# the PHPUnit banner and passed through without summary enforcement.

set -eo pipefail

if [[ $# -eq 0 ]]; then
	echo "Usage: $0 <command...>" >&2
	exit 2
fi

log=$(mktemp)
trap 'rm -f "$log"' EXIT

set +e
"$@" 2>&1 | tee "$log"
status=${PIPESTATUS[0]}
set -e

# Non-running invocations (--list-tests, --help, --version, error before
# startup) never print the PHPUnit banner. Pass them through unchanged.
if ! grep -qE '^PHPUnit [0-9]+\.' "$log"; then
	exit "$status"
fi

# A complete PHPUnit run always prints a "Tests: N, Assertions: N..." line,
# regardless of pass/fail/incomplete/skipped/risky. Its absence means PHP
# terminated mid-suite and the inner exit code is untrustworthy.
if ! grep -qE '^Tests: [0-9]+' "$log"; then
	# Strip ANSI escape sequences in case PHPUnit emitted colors.
	esc=$(printf '\033')
	cleaned=$(sed -E "s/${esc}\[[0-9;]*[mK]//g" "$log")

	# Recover what we can from progress markers. A progress line looks like
	# "..F..E... 6283 / 8417 ( 74%)" — the F/E/etc. for tests that completed
	# before termination are already captured; we just count them.
	progress=$(printf '%s\n' "$cleaned" | grep -E '^[.FERIWS]+[[:space:]]+[0-9]+[[:space:]]*/[[:space:]]*[0-9]+[[:space:]]*\([[:space:]]*[0-9]+%\)' || true)
	failures=0
	errors=0
	other=0
	last_count=""
	if [[ -n "$progress" ]]; then
		markers=$(printf '%s\n' "$progress" | sed -E 's/[[:space:]]+[0-9]+[[:space:]]*\/.*$//' | tr -d '\n')
		failures=$(printf '%s' "$markers" | tr -cd 'F' | wc -c | tr -d ' ')
		errors=$(printf '%s' "$markers" | tr -cd 'E' | wc -c | tr -d ' ')
		other=$(printf '%s' "$markers" | tr -cd 'RIWS' | wc -c | tr -d ' ')
		last_count=$(printf '%s\n' "$progress" | tail -n 1 | grep -oE '[0-9]+[[:space:]]*/[[:space:]]*[0-9]+' | head -n 1)
	fi

	{
		echo ""
		echo "::error::PHPUnit terminated before completion (process exit=${status})."
		echo 'The "Tests: N, Assertions: N" summary line is missing — PHP almost'
		echo "certainly exited from within a test (bare exit()/die() in tested code,"
		echo "fatal error, OOM, or segfault)."
		if [[ -n "$last_count" ]]; then
			echo ""
			echo "Before termination, ${last_count} tests completed."
			if (( failures > 0 || errors > 0 || other > 0 )); then
				echo "Progress markers reveal these silently-masked results:"
				(( failures > 0 )) && echo "  - ${failures} failure(s)   (F)"
				(( errors   > 0 )) && echo "  - ${errors} error(s)     (E)"
				(( other    > 0 )) && echo "  - ${other} risky / skipped / incomplete / warning (R/I/S/W)"
				echo "(The test that triggered termination itself has no marker — PHPUnit"
				echo "prints a marker only after a test completes.)"
			fi
		fi
		echo ""
		echo "The reported exit code cannot be trusted; failing this step explicitly."
		echo ""
	} >&2
	exit 1
fi

exit "$status"
