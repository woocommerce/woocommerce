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
	cat >&2 <<EOF

::error::PHPUnit terminated before completion (process exit=${status}).
The "Tests: N, Assertions: N" summary line is missing — PHP almost
certainly exited from within a test (bare exit()/die() in tested code,
fatal error, OOM, or segfault). The reported exit code cannot be
trusted; failing this step explicitly.

EOF
	exit 1
fi

exit "$status"
